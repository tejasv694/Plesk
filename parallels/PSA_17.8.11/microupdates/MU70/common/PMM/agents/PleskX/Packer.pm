# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
package Packer;

use strict;
use warnings;

use Status;
use HelpFuncs;
use Storage::FileNameCreator;
use ArchiveContent::ArchiveContent;
use CommonPacker;
use Mailman;
use Logging;
use PerlMD5;
use XPath;
use File::Temp;
use File::Basename qw(dirname);
use IPC::Run qw(run);
use Fcntl qw< S_IROTH S_IWOTH S_ISDIR S_ISLNK >;

use vars qw|@ISA|;

my $DEBUG = undef;

### Common function ###

sub new {
  my $self = {};
  bless( $self, shift );
  $self->_init(@_);
  return $self;
}

sub _init {
  my ( $self, $version, $storagePolicy ) = @_;

  XmlNode::resetCompatibilityProcs();

  $self->{storage} = $storagePolicy;
  $self->{version} = $version;
  $self->{base64} = HelpFuncs::makeMIMEBase64();
  $self->{fnamecreator} = Storage::FileNameCreator->new();
  $self->setBackupProfileFileName( 'backup' );
  $self->{ownerGuid} = '';
  $self->{ownerType} = '';
  $self->{roots} = [];
  $self->{content_transport_type} = 'archive';
  $self->{content_transport} = ArchiveContent::ArchiveContent->new( $self->{storage}, $self );
  $self->{decrypt_full_dump} = 0;
  $self->{apache_user} = AgentConfig::getApacheUserInfo();
  $self->{mailman_user} = AgentConfig::getMailmanUserInfo();
  $self->{incrementalCreationDate} = undef;
  $self->{lastIndexPath} = undef;
  $self->{lastIncrementFile} = undef;
  $self->{excludePatternsFile} = undef;
  $self->{relatedDumps} = ();
  
  $self->flushDumpStatistics();
}

sub flushDumpStatistics {
  my ($self) = @_;
  
  $self->{stat} = ();
  $self->{stat}{vhostDumpsCount} = 0;
  $self->{stat}{vhostSizeOnFS} = 0;
  $self->{stat}{vhostSizeDumped} = 0;
  
  $self->{stat}{dbDumpsCount} = 0;
  $self->{stat}{dbSizeOnFS} = 0;
  $self->{stat}{dbSizeDumped} = 0;
  
  $self->{stat}{mailDumpsCount} = 0;
  $self->{stat}{mailSizeOnFS} = 0;
  $self->{stat}{mailSizeDumped} = 0;
}

sub setContentTransport {
  my ($self) = @_;
  $self->{content_transport} = ArchiveContent::ArchiveContent->new($self->{storage}, $self);
}

sub getContentTransport {
  my ($self) = @_;
  return $self->{content_transport};
}

sub setBackupProfileFileName{
  my ($self, $profileName, $profileId ) = @_;
  $self->{backupname} = $self->{fnamecreator}->normalize_long_string( $profileName, $profileId );
  $profileId = '' if not defined $profileId;
  Logging::debug( "Set backup file name '$self->{backupname}' (profile '$profileName', id='$profileId')\n" );
  $self->{backupPrefix} = $profileName;
}

sub setIncrementalCreationDate{
  my ($self, $incrementalCreationDate) = @_;
  $self->{fnamecreator}->setIncrementalCreationDate($incrementalCreationDate);
  $self->{incrementalCreationDate} = $incrementalCreationDate;
}

sub getIncrementalCreationDate {
  my ($self) = @_;
  return $self->{incrementalCreationDate};
}

sub getCreationDate {
  my ($self) = @_;
  return $self->{fnamecreator}->getCreationDate();
}

sub setLastIndexPath{
  my ($self, $lastIndexPath) = @_;
  $self->{lastIndexPath} = $lastIndexPath;
}

sub setLastIncrementFile {
  my ($self, $lastIncrementFile) = @_;
  $self->{lastIncrementFile} = $lastIncrementFile;
}

sub setExcludePatternsFile {
  my ($self, $excludePatternsFile) = @_;
  $self->{excludePatternsFile} = $excludePatternsFile;
}

sub setBackupOwnerGuid{
  my ($self, $ownerGuid, $ownertype ) = @_;
  $ownerGuid = '' if not $ownerGuid;
  $self->{ownerGuid} = $ownerGuid;
  $ownertype = '' if not $ownertype;
  $self->{ownerType} = $ownertype;
  Logging::debug( "Set backup owner guid '$ownerGuid', type '$ownertype'\n" );
}

sub getBackupOwnerGuid{
  my ($self) = @_;
  return $self->{ownerGuid};
}

sub startCollectStorageStatistics {
  my $self = shift;

  $self->{storage}->startCollectStatistics();
}

sub getStorageStatistics {
  my $self = shift;

  return $self->{storage}->getStatistics();
}

sub setRoot {
  my ( $self, $description, $content, $dumpformat, $adminGuid, $embeddedInfo ) = @_;

  $self->{root} = XmlNode->new( 'migration-dump',
    'attributes' =>
      { 'agent-name' => 'PleskX', 'dump-version' => $self->{version}, 'dump-original-version' => $self->{version} } );
  $self->{root}->setAttribute( 'content-included', $content ? 'true' : 'false' ) if defined $content;
  $self->{root}->setAttribute( 'dump-format', $dumpformat ) if defined $dumpformat;
  my $dumpinfo = $self->{root}->getChild( 'dump-info', 1 );
  if ($description) {
    $dumpinfo->addChild( XmlNode->new( 'description', 'content' => $description ) );
  }

  my $osDescriptionNode = XmlNode->new('os-description');
  $osDescriptionNode->setAttribute('type', 'unix');
  if (my $apacheUid = getpwnam($self->{apache_user}{'user'})) {
    $osDescriptionNode->setAttribute('apache-uid', $apacheUid);
  }
  if (my $apacheGid = getgrnam($self->{apache_user}{'group'})) {
    $osDescriptionNode->setAttribute('apache-gid', $apacheGid);
  }
  if (my $mailmanUid = getpwnam($self->{mailman_user}{'user'})) {
    $osDescriptionNode->setAttribute('mailman-uid', $mailmanUid);
  }
  if (my $mailmanGid = getgrnam($self->{mailman_user}{'group'})) {
    $osDescriptionNode->setAttribute('mailman-gid', $mailmanGid);
  }
  $dumpinfo->addChild($osDescriptionNode);

  my $cpDescriptionNode = XmlNode->new('cp-description');
  my $componentsInstalledNode = XmlNode->new('components-installed');
  my %packages = %{DAL::getSoftwarePackages()};
  while (my ($name, $version) = each(%packages)) {
    my $componentNode = XmlNode->new('component');
    $componentNode->setAttribute('name', $name);
    $componentsInstalledNode->addChild($componentNode);
  }
  $cpDescriptionNode->addChild($componentsInstalledNode);
  if (defined($embeddedInfo->{'services'})) {
    my ($services) = @{$embeddedInfo->{'services'}};
    $self->addEmbeddedInfo($cpDescriptionNode, 'services', $services);
  }
  $dumpinfo->addChild($cpDescriptionNode);

  my $contentTransportDescriptionNode = $self->{content_transport}->getContentTransportDescription();
  if ( defined $contentTransportDescriptionNode ) {
    my $contentTransportNode = XmlNode->new( 'content-transport' );
    $contentTransportNode->addChild( $contentTransportDescriptionNode );
    $dumpinfo->addChild ( $contentTransportNode );
  }

  if ($adminGuid) {
    $dumpinfo->addChild(XmlNode->new('server-id', 'content' => $adminGuid));
  }
}

sub setMarkers{
  my ($self, @markers) = @_;

  my $dumpinfo = $self->{root}->getChild('dump-info', 0);

  foreach my $marker (@markers) {
    $dumpinfo->addChild(XmlNode->new('dump-marker', 'content' => $marker));
  }
}

sub _getRootForStorageFinish {
  my ($self, $dumpDirPath) = @_;
  
  my $dumpinfo = $self->{root}->getChild('dump-info', 0);
  $dumpinfo->removeChildren('related-dumps');

  if (exists $self->{relatedDumps}->{$dumpDirPath}) {
    my $relatedDumpsNode = XmlNode->new('related-dumps');
    $dumpinfo->addChild($relatedDumpsNode);
    foreach my $relatedDump ( @{$self->{relatedDumps}->{$dumpDirPath}} ) {
      $relatedDumpsNode->addChild(XmlNode->new('related-dump', 'content' => $relatedDump));
    }
  }
  return $self->{root};
}

sub setDecryptFullDump {
  my ($self) = @_;
  $self->{decrypt_full_dump} = 1;
  Logging::debug( "Set to decrypt full dump" );
}

sub turnOffContent{
  my ($self) = @_;
  $self->{skip_content} = 1;
  Logging::debug( "The dump of content is switched off" );
}

sub isRootNode{
  my ($self, $node) = @_;
  foreach my $root( @{$self->{roots}} ){
    return 1 if $root==$node;
  }
  return 0;
}

sub finishChild{
    my ($self, $node, $path, $fileName ) = @_;
    my $ret;
    if(scalar( @{$self->{roots}} )==1  && $self->isRootNode($node) ) {
      $ret = $self->{storage}->finish( $self->_getRootForStorageFinish($path), $path, $fileName );
      die "Cannot create main dump file!" if $ret!=0;
      $ret = $self->{storage}->getMainDumpXmlRelativePath();
    }
    else {
      $ret = $self->{storage}->finishChild( $self->_getRootForStorageFinish($path), $node, $path, $fileName );
    }
    return $ret;
}

sub finishDomains {
  my ($self, $fileName, $fh, $usersContentSize, $infoxml) = @_;

  if ( exists $self->{domainNodes} ) {
    my ( $id, $node, $path );
    while ( ($id, $node ) = each( %{$self->{domainNodes}} ) ){
      $path = $self->getDomainsBackupPath( $id );
      Logging::debug( "Save domain dump $path" );
      XmlNode::setStartSavePath( $path );

      print $fh "<file>" . $path . "/" .$self->getDomainsBackupPath( $id, $fileName, 1 ) . ".xml" . "</file>\n";

      my $fileid = $self->finishChild( $node, $path, $self->getDomainsBackupPath( $id, $fileName, 1 ) );
      my $contentSize = $self->getContentSize($node);
      @{$infoxml->{$fileid}} = ( $contentSize, $self->getDomainObjectId( $id ), $node->getAttribute('guid') );
      $self->{storage}->createRepositoryIndex( $node->getAttribute('guid') . '_' . $self->getDomainObjectId( $id ) );

      #Keep total domain content size for each client
      my $clientId = PleskStructure::getClientIdForDomainId( $id );
      $usersContentSize->{$clientId} = (exists $usersContentSize->{$clientId}) ? $usersContentSize->{$clientId} + $contentSize : $contentSize;
    }
  }
}

sub finishClients {
  my ($self, $fileName, $fh, $selectResellers, $usersContentSize, $infoxml) = @_;

  if( exists $self->{clientNodes} ){
    my ( $id, $node, $path );
    while( ($id, $node ) = each( %{$self->{clientNodes}} ) ){
      my $fname;
      if (exists $self->{resellersNodes}->{$id}) {
        next if !$selectResellers;

        $path = $self->getResellersBackupPath( $id );
        $fname = $self->getResellersBackupPath( $id, $fileName, 1 );
        Logging::debug( "Save reseller dump $path" );
      } else {
        next if $selectResellers;

        $path = $self->getClientsBackupPath( $id );
        $fname = $self->getClientsBackupPath( $id, $fileName, 1 );
        Logging::debug( "Save client dump $path" );
      }

      XmlNode::setStartSavePath( $path );

      print $fh "<file>" . $path . "/" . $fname. ".xml" . "</file>\n";

      # Calculate current client size
      $usersContentSize->{$id} = (exists $usersContentSize->{$id})
        ? $usersContentSize->{$id} + $self->getContentSize($node)
        : $self->getContentSize($node);

      # Add current client size to the parent (reseller or admin) total content size
      my $parentId = PleskStructure::getClientParentId(PleskStructure::getClientNameFromId($id));
      $usersContentSize->{$parentId} = (exists $usersContentSize->{$parentId})
        ? $usersContentSize->{$parentId} + $usersContentSize->{$id}
        : $usersContentSize->{$id};

      my $fileid = $self->finishChild( $node, $path, $fname );
      @{$infoxml->{$fileid}} = ( $usersContentSize->{$id}, $self->getClientObjectId( $id ), $node->getAttribute('guid') );
      $self->{storage}->createRepositoryIndex( $node->getAttribute('guid') . '_' . $self->getClientObjectId( $id ) );
    }
  }
}

sub finishCustomers {
  my ($self, $fileName, $fh, $usersContentSize, $infoxml) = @_;
  $self->finishClients($fileName, $fh, 0, $usersContentSize, $infoxml);
}

sub finishResellers {
  my ($self, $fileName, $fh, $usersContentSize, $infoxml) = @_;
  $self->finishClients($fileName, $fh, 1, $usersContentSize, $infoxml);
}

sub finish {
    my ($self) = @_;

    my %infoxml;

    my $ret = 0;
    my $fileName;
    my $mainDumpXmlFile;

    if(scalar( @{$self->{roots}} )!=1 ){

        XmlNode::setStartSavePath( '' );

        $fileName = $self->{fnamecreator}->getFileName( '', $self->{backupname}, '', 'info' );

        $ret = $self->{storage}->finish( $self->_getRootForStorageFinish(''), '', $fileName );

        die "Cannot create main dump file!" if $ret!=0;

        $mainDumpXmlFile = $self->{storage}->getMainDumpXmlRelativePath();

        @{$infoxml{$mainDumpXmlFile}} = ( $self->getContentSize( $self->{root} ), $self->getAdminObjectId(), ( exists $self->{admin} ? $self->{admin}->getAttribute('guid') : $self->{ownerGuid} ) );
        $self->{storage}->createRepositoryIndex( ( exists $self->{admin} ? $self->{admin}->getAttribute('guid') : '' ) . '_' . $self->getAdminObjectId() );
    }
    $fileName = 'info';

    my ($fh, $targetFile);
    ($fh, $targetFile ) = File::Temp::tempfile( AgentConfig::getBackupTmpDir() . "/pmm-xmlFilesList-XXXXXX", UNLINK => 1 );

    print $fh "<dump-files source-directory=\"".$self->{storage}->getFullOutputPath()."\">\n";
    print $fh "<file>" . $self->{storage}->getMainDumpXmlFile() . "</file>\n" if $self->{storage}->getMainDumpXmlFile();

    my %usersContentSize; # Summary size of each customer, reseller and admin content

    $self->finishDomains($fileName, $fh, \%usersContentSize, \%infoxml);
    $self->finishCustomers($fileName, $fh, \%usersContentSize, \%infoxml);
    $self->finishResellers($fileName, $fh, \%usersContentSize, \%infoxml);

    if (exists $infoxml{$mainDumpXmlFile}) {
      my $adminId = PleskStructure::getAdminId();
      @{$infoxml{$mainDumpXmlFile}}[0] += $usersContentSize{$adminId} if exists $usersContentSize{$adminId};
    }

    print $fh "</dump-files>";
    close $fh;


    if ($self->{decrypt_full_dump}) {
      $self->_decryptDump($targetFile);
    } else {
        $self->_encryptDump($targetFile);
    }

    my $path;

    #Create discovered files
    foreach my $fileid(  keys( %infoxml ) ){
       my $idx = rindex( $fileid, '/' );
       $path = $fileid;
       $path = substr( $path, 0, $idx ) if $idx>0;
       my $xmlsize = 0;
       my $files;
       foreach my $id( keys( %infoxml ) ){
         if( $idx<0 || index( $id, $path )==0 ){
            $files = $self->{storage}->getFilesFromId( $id );
            foreach my $filedata( @{$files} ) {
              $xmlsize += $filedata->[1];
            }
         }
       }
       $files = $self->{storage}->getFilesFromId( $fileid );
       if( scalar( @{$files} )==1 ){
          my $xmlfiledata = $files->[0];
          $xmlsize += @{$infoxml{$fileid}}[0];
          my $objId = @{$infoxml{$fileid}}[1];
          my $objGuid = @{$infoxml{$fileid}}[2];
          $self->{storage}->writeDiscovered( $self->{storage}->getFilePathFromId( $fileid ), $xmlfiledata->[0], $xmlsize, $self->{ownerGuid}, $self->{ownerType}, $objGuid, $objId );
       }
    }

    return 0;
}

sub _decryptDump {
  my ($self, $targetFile) = @_;

  eval {
    my $encryptCmd = AgentConfig::getEncryptUtil();
    push(@{$encryptCmd}, '--decrypt-by-plesk', '-backup-files-map', $targetFile);
    Logging::debug("Execute: @{$encryptCmd}");
    my $stderr;
    IPC::Run::run($encryptCmd, '2>', \$stderr) or die($stderr);
  };
  if ($@) {
    Logging::error("Cannot decrypt dump file (this is not fatal error!): $@", 'UtilityError');
  }
}

sub _encryptDump {
  my ($self, $targetFile) = @_;
  my $cryptKey = $ENV{'PLESK_BACKUP_CRYPT_KEY'};
  delete $ENV{'PLESK_BACKUP_CRYPT_KEY'} if ($cryptKey);
  my $password = $ENV{'PLESK_BACKUP_PASSWORD'};
  delete $ENV{'PLESK_BACKUP_PASSWORD'} if ($password);

  eval {
    my $encryptCmd = AgentConfig::getEncryptUtil();
    push(@{$encryptCmd}, '--encrypt-by-plesk', '-backup-files-map', $targetFile);
    Logging::debug("Execute: @{$encryptCmd}");
    my $stderr;
    IPC::Run::run($encryptCmd, '2>', \$stderr) or die($stderr);
  };
  if ($@) {
    Logging::error("Cannot encrypt dump file (this is not fatal error!): $@", 'UtilityError');
  }
    
  $ENV{'PLESK_BACKUP_CRYPT_KEY'} = $cryptKey if ($cryptKey);
  $ENV{'PLESK_BACKUP_PASSWORD'} = $password if ($password);
}

sub getContentSize{
  my ($self, $node) = @_;
  my $size = 0;

  foreach my $child ( $node->getChildren() ) {
    if( $child ) {
      my $childName = $child->getName();
      if( $childName && $childName eq 'cid' ){
        if( !defined($child->getAttribute('referrer')) ) {
          foreach my $cid ( $child->getChildren() ){
            if ($cid->isAttributeExist('size')) {
              $size += $cid->getAttribute('size');
            }
          }
        }
      }
      else {
        $size += $self->getContentSize($child);
      }
    }
  }
  return $size;
}

### Server functions ###

sub setServerSettings {
  my ( $self ) = @_;
  my $serverNode = XmlNode->new('server');
  $self->{serverNode} = $serverNode;
}

sub addServerNodeToDump {
  my ( $self ) = @_;

  my $root = $self->{root};
  unless (defined $root)
  {
    Logging::warning("Root node is not set", 'PackerStructure');
    return;
  }

  if ( ref ($self->{serverNode}) =~ /XmlNode/ ) {
    $root->addChild($self->{serverNode});
  }
}

sub addPanelCertificate {
  my ($self) = @_;
  my $serverNode = $self->{serverNode};

  my $path = AgentConfig::get('PRODUCT_ROOT_D') . "/admin/conf";
  my $httpsdFilename = 'httpsd.pem';

  if ( -e "$path/$httpsdFilename") {
    my $certNode = XmlNode->new('panel-certificate');

    my $content = undef;

    open HTTPSDFILE, $path . "/" . $httpsdFilename;
    binmode(HTTPSDFILE);
    while (<HTTPSDFILE>) {
      $content .= $_;
    }
    close HTTPSDFILE;

    $certNode->addChild(XmlNode->new('cp-certificate', 'content' => $self->{base64}->{'ENCODE'}->($content) ));

    my $rootchainFilename = 'rootchain.pem';
    if ( -e "$path/$rootchainFilename") {
      my $content = undef;
      open ROOTCHAINFILE, $path . "/" . $rootchainFilename;
      binmode(ROOTCHAINFILE);
      while (<ROOTCHAINFILE>) {
        $content .= $_;
      }
      close ROOTCHAINFILE;
      $certNode->addChild(XmlNode->new('cp-rootchain', 'content' => $self->{base64}->{'ENCODE'}->($content) ));
    }

    $serverNode->getChild( 'certificates', 1 )->addChild($certNode);
  }
}

sub addServerSiteIsolationConfig {
  my ($self) = @_;
  my $root = $self->{serverNode};

  my $path = AgentConfig::get('PRODUCT_ROOT_D') . "/admin/conf";
  my $file = 'site_isolation_settings.ini';

  if ( -e "$path/$file") {

    my $siteIsolationNode = XmlNode->new('site-isolation');

    my $configText = undef;

    open CONFIGFILE, $path . "/" . $file;
    binmode(CONFIGFILE);

    while (<CONFIGFILE>) {
      $configText .= $_;
    }

    close CONFIGFILE;

    $siteIsolationNode->addChild(XmlNode->new('config', 'content' => $self->{base64}->{'ENCODE'}->($configText) ));
    $root->addChild($siteIsolationNode);
  }
}

sub setServerEventHandler {
  my ($self, $embeddedInfo) = @_;

  return unless defined($embeddedInfo->{'events'});

  my ($events) = @{$embeddedInfo->{'events'}};
  return unless ref($events) =~ /HASH/;

  my $eventsNode = XmlNode->new('events');

  my ($rotation) = @{$events->{'rotation'}};
  $self->addEmbeddedInfo($eventsNode, 'rotation', $rotation);
  if (defined($events->{'event'})) {
    foreach my $event (@{$events->{'event'}}) {
      $self->addEmbeddedInfo($eventsNode, 'event', $event);
    }
  }

  $self->{serverNode}->addChild($eventsNode);
}

sub addServerNotifications {
  my ( $self, $expirationWarnDays, $ptrNotifications, $ptrNotes ) = @_;
  my $root = $self->{serverNode};
  my @notifications = @{$ptrNotifications};
  my %notes = %{$ptrNotes};

  my $notificationsNode = XmlNode->new('notifications');
  $notificationsNode->setAttribute( 'expiration-warning-days', $expirationWarnDays);
  foreach my $notification (@notifications) {
    my $notificationNode = XmlNode->new( 'notification' );
    $notificationNode->setAttribute( 'id',            $notification->{'id'} );
    $notificationNode->setAttribute( 'send2admin',    $notification->{'send2admin'}    ? 'true' : 'false' );
    $notificationNode->setAttribute( 'send2reseller', $notification->{'send2reseller'} ? 'true' : 'false' );
    $notificationNode->setAttribute( 'send2client',   $notification->{'send2client'}   ? 'true' : 'false' );
    $notificationNode->setAttribute( 'send2email',    $notification->{'send2email'}    ? 'true' : 'false' );

    $notificationNode->setAttribute( 'email', $notification->{'email'} ) if defined $notification->{'email'};
    $notificationNode->setAttribute( 'subj',  $notification->{'subj'} ) if defined $notification->{'subj'};

    my $noteText = (exists $notes{$notification->{'note_id'}}) ? $notes{$notification->{'note_id'}} : '';
    $notificationNode->addChild( XmlNode->new( 'notice-text', 'content' => $noteText ) );
    
    $notificationsNode->addChild( $notificationNode );
  }

  $root->addChild($notificationsNode);
}

sub addServerCustomButton {
  my ( $self, $id, $optionsPtr, $customButtonsDir, $icon ) = @_;

  my $parent = $self->{serverNode};
  if ( !defined($parent) ) {
    Logging::warning('Unable to dump custom buttons settings, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $node = $self->makeCustomButtonNode( 'admin', undef, $id, $optionsPtr, $customButtonsDir, $icon );

  $parent->getChild( 'interface-preferences', 1 )->addChild($node);
}

my %dumpedSystemIps;

sub addServerIp {
  my ( $self, $ip ) = @_;

  $self->setServerSettings() unless defined $self->{serverNode};

  if ( ! $dumpedSystemIps{$ip->{'ip_address'}} ) {
    $self->makeSystemIpNode( $self->{serverNode}, $ip );
    $dumpedSystemIps{$ip->{'ip_address'}} = 1;
  }
}

sub setServerDefaultIp {
  my ( $self, $ip ) = @_;

  my $parent = $self->{serverNode};
  if ( !defined($parent) ) {
    Logging::warning('Unable to dump server default IP, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  $parent->getChild( 'properties', 1 )->addChild( XmlNode->new( 'default-ip', 'content' => $ip ) );
}

sub setServerHostname {
  my ( $self, $hostname ) = @_;

  my $parent = $self->{serverNode};
  if ( !defined($parent) ) {
    Logging::warning('Unable to dump server host name, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  $parent->getChild( 'properties', 1 )->addChild( XmlNode->new( 'hostname', 'content' => $hostname ) );
}

sub setServerAdminInfo {
  my ( $self, $ptrAdmin, $passwd, $max_btn_len, $send_announce, $external_id, $cron, $descriptions ) = @_;

  my $parent = $self->{admin};
  if ( !defined($parent) ) {
    Logging::warning('Unable to dump server admin user information, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  $parent->setAttribute( 'max-button-length', $max_btn_len ) if $max_btn_len;
  $parent->setAttribute( 'send-announce', $send_announce ) if $send_announce;
  $parent->setAttribute( 'external-id', $external_id ) if defined $external_id and $external_id ne '';

  my %adminInfo = (
    'cname' => 'company',
    'phone'   => 'phone',
    'fax'     => 'fax',
    'address' => 'address',
    'city'    => 'city',
    'state'   => 'state',
    'pcode'   => 'zip',
    'country' => 'country',
    'email'   => 'email',
    'pname'   => 'name',
    'locale'  => 'locale',
  );

  my $pref = $parent->getChild( 'preferences', 1 );
  while ( my ( $name, $value ) = each( %{$ptrAdmin} ) ) {

    #Attributes transformation. Bug 97408
    $name =~ s/^admin_//g;
    next if not defined $adminInfo{$name};
    $pref->addChild( XmlNode->new( 'pinfo', 'attributes' => { 'name' => $adminInfo{$name} }, 'content'    => $value ) );
  }

  if (defined $cron) {
    $self->addEmbeddedInfo($pref, 'scheduled-tasks', $cron);
  }

  $self->makeDescriptionsNode( $pref, $descriptions );
}

sub addServerDb {
  my ( $self, $dbServerPtr, $passwd, $default ) = @_;

  my %dbServer = %{$dbServerPtr};

  my $parent = $self->{serverNode};
  if ( !defined($parent) ) {
    Logging::warning('Unable to dump server database, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $dbServerNode = $self->makeDbServerNodeWithoutCredentials(\%dbServer);

  if ( $default ) {
    $dbServerNode->setAttribute( 'default', 'true' );
  }

  if ( ($dbServer{'admin_login'}) and ($dbServer{'admin_login'} ne '')) {
    my $adminNode =
      XmlNode->new( 'db-admin',
      "attributes" => { "name" => "$dbServer{'admin_login'}" } );
    if ( $dbServer{'type'} eq 'mysql'
      && $dbServer{'host'} eq 'localhost' )
    {
      $dbServer{'admin_password'} = $passwd;
    }
    my $passwordNode =
      CommonPacker::makePasswordNode( $dbServer{'admin_password'}, 'plain' )
      ;    # password type for dbservers is always plain
    $adminNode->addChild($passwordNode);

    $dbServerNode->addChild($adminNode);
  }

  $parent->getChild( 'db-servers', 1 )->addChild($dbServerNode);
}

sub makeDbServerNodeWithoutCredentials {
  my ($self, $dbServer) = @_;

  my $dbServerNode = XmlNode->new('db-server');

  $dbServerNode->setAttribute( 'type', $dbServer->{'type'} );

  $dbServerNode->addChild( XmlNode->new( 'host', 'content' => "$dbServer->{'host'}" ) );
  $dbServerNode->addChild( XmlNode->new( 'port', 'content' => "$dbServer->{'port'}" ) );

  return $dbServerNode;
}

sub makeDatabaseUserRemoteAccessRulesNode {
    my ($self, $dbUser, $dbId) = @_;

    Logging::debug("Backing up remote access rules for db user " . $dbUser->{'login'});
    my @rules = @{DAL::DatabaseUserRemoteAccessRules($dbUser->{'login'}, $dbId)};
    if ( @rules ) {
        my $rulesNode = XmlNode->new('remote-access-rules');
        for my $ptrRow ( @rules ) {
            my $ruleItemNode = XmlNode->new( 'rule-item' );
            $ruleItemNode->setAttribute( 'type', $ptrRow->[0] );
            $ruleItemNode->setAttribute( 'ip-address', $ptrRow->[1] );
            $ruleItemNode->setAttribute( 'ip-subnet-mask', $ptrRow->[2] );
            $rulesNode->addChild( $ruleItemNode );
        }

        return $rulesNode;
    }

    return;
}

sub addServerKey {
  my ( $self, $keyId, $keyName, $keyDir, $additional, $instance ) = @_;

  my $parent = $self->{serverNode};
  if ( !defined($parent) ) {
    Logging::warning('Unable to dump server license key, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  if ( -e "$keyDir/$keyName" ) {

    my $keyNode =
      XmlNode->new( 'key',
      'attributes' => { 'additional' => $additional } );

    $keyNode->setAttribute('instance-id', $instance) if $instance ne '';

    my $cid = $self->{content_transport}->addAdminContent(
      'key',
      undef,
      "$keyId.key",
      "directory" => $keyDir,
      "include"   => [$keyName]
    );
    $keyNode->getChild( 'content', 1, 1 )->addChild($cid) if $cid;

    $parent->getChild( 'keys', 1 )->addChild($keyNode);
  }

}

sub setServerMail {
  my ( $self, $letterSize, $paramsPtr, $mailSettingsPtr, $blackListPtr, $whiteListPtr, $ipAddressesPtr, $externalWebmailsPtr, $mailCertificate ) = @_;

  my %params = %{$paramsPtr};
  my %mailSettings = %{$mailSettingsPtr};
  my @blackList = @{$blackListPtr};
  my %whiteList = %{$whiteListPtr};
  my @externalWebmails = @{$externalWebmailsPtr};

  my $parent = $self->{serverNode};
  if ( !defined($parent) ) {
    Logging::warning('Unable to dump server mail settings, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $mailNode = XmlNode->new(
    'mail-settings',
    'attributes' => {
      'relay'           => $mailSettings{'relay'},
      'use-vocabulary'  => (defined($params{'use_vocabulary'}) and ($params{'use_vocabulary'} eq 'true'))
        ? 'true'
        : 'false',
      'short-pop3-names' => (defined($params{'allow_short_pop3_names'}) and ($params{'allow_short_pop3_names'})) eq 'enabled'
        ? 'true'
        : 'false',
      'message-submission' => (defined($mailSettings{'message_submission'}) and ($mailSettings{'message_submission'} eq 'true'))
        ? 'true'
        : 'false',
      'sign-outgoing-mail' => (defined($mailSettings{'domain_keys_sign'}) and ($mailSettings{'domain_keys_sign'} eq 'true'))
        ? 'true'
        : 'false',
      'verify-incoming-mail' => (defined($mailSettings{'domain_keys_verify'}) and ($mailSettings{'domain_keys_verify'} eq 'true'))
        ? 'true'
        : 'false',
    }
  );

  if (defined $letterSize) {
    $mailNode->setAttribute('max-letter-size', $letterSize);
  }
  if (defined $mailSettings{'courier_max_connections'}) {
    $mailNode->setAttribute('courier-max-connections', $mailSettings{'courier_max_connections'});
  }
  if (defined $mailSettings{'courier_max_connections_per_ip'}) {
    $mailNode->setAttribute('courier-max-connections-per-ip', $mailSettings{'courier_max_connections_per_ip'});
  }
  if (defined $mailSettings{'dovecot_max_connections'}) {
    $mailNode->setAttribute('dovecot-max-connections', $mailSettings{'dovecot_max_connections'});
  }
  if (defined $mailSettings{'dovecot_max_connections_per_ip'}) {
    $mailNode->setAttribute('dovecot-max-connections-per-ip', $mailSettings{'dovecot_max_connections_per_ip'});
  }
  if (defined $mailSettings{'dmarc_policy'}) {
    $mailNode->setAttribute('dmarc-enabled', $mailSettings{'dmarc_policy'} eq 'true' ? 'true' : 'false');
  }

  $mailNode->setAttribute('certificate', $mailCertificate) if defined $mailCertificate;

  my $spfNode = XmlNode->new(
    'spf',
    'attributes' => {
      'status' => (exists $mailSettings{'spf_enabled'} and $mailSettings{'spf_enabled'} eq 'true') ? 'true' : 'false'
    }
  );
  $spfNode->setAttribute('spf-behavior', (exists $mailSettings{'spf_behavior'} and "$mailSettings{'spf_behavior'}" ne '' ) ? $mailSettings{'spf_behavior'} : 1);
  if (exists $mailSettings{'spf_dnserrignore'}) {
    $spfNode->setAttribute('spf-ignore-dns-error', $mailSettings{'spf_dnserrignore'} eq 'true' ? 'true' : 'false');
  }

  $spfNode->addChild( XmlNode->new( 'spf-rules', 'content' => exists $mailSettings{'spf_rules'} ? $mailSettings{'spf_rules'} : '' ) );
  $spfNode->addChild( XmlNode->new( 'spf-guess', 'content' => exists $mailSettings{'spf_guess'} ? $mailSettings{'spf_guess'} : '' ) );
  $spfNode->addChild( XmlNode->new( 'spf-exp', 'content' => exists $mailSettings{'spf_exp'} ? $mailSettings{'spf_exp'} : '' ) );

  $mailNode->addChild($spfNode);

  my $rblNode = XmlNode->new(
    'rbl',
    'attributes' => {
        'status' => ( exists $mailSettings{'rbl'} and $mailSettings{'rbl'} eq 'true' )
      ? 'true'
      : 'false'
    }
  );
  $rblNode->addChild( XmlNode->new( 'rbl-server', 'content' => exists $mailSettings{'rbl_server'} ? $mailSettings{'rbl_server'} : '' ) );

  $mailNode->addChild($rblNode);

  if ( $mailSettings{'relay'} eq 'auth' ) {
    $mailNode->setAttribute( 'pop-auth', my $disable_pop_auth = (exists $mailSettings{'disable_pop_auth'} and $mailSettings{'disable_pop_auth'} ? 'false' : 'true') );
    $mailNode->setAttribute( 'smtp-auth', (exists $mailSettings{'disable_smtp_auth'} and $mailSettings{'disable_smtp_auth'} ? 'false' : 'true') );
    $mailNode->setAttribute( 'poplock-time', $mailSettings{'poplock_time'} )  if ('false' eq $disable_pop_auth);
  }

  # black list
  my $listNode = XmlNode->new('black-list');

  for my $listItem (@blackList) {
    $listNode->addChild( XmlNode->new( 'list-item', 'content' => $listItem ) );
  }
  $mailNode->addChild($listNode);

  # white list
  $listNode = XmlNode->new('white-list');

  for my $key (keys %whiteList) {
    $listNode->addChild(
      XmlNode->new(
        'list-item', 'content' => $key . '/' . $whiteList{$key}
      )
    );
  }
  $mailNode->addChild($listNode);

  $parent->addChild($mailNode);
  
  if (@externalWebmails) {
    $self->_makeExternalWebmails(\@externalWebmails);
  }

  for my $key (keys %mailSettings) {
    if ($key =~ /^outgoing_messages_/) {
      $self->_makeServerOutgoingMessagesParameter($key, $mailSettings{$key});
    }
  }

  $self->_makeSeverOutgoingEmailMode($mailSettingsPtr, $ipAddressesPtr);
}

sub setServerDNS {
  my ( $self, $paramsPtr, $recordsPtr ) = @_;

  my @records = @{$recordsPtr};

  my %params = %{$paramsPtr};

  my $parent = $self->{serverNode};
  if ( !defined($parent) ) {
    Logging::warning('Unable to dump server DNS settings, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  # dump dns
  my $dnsSettingsAttributes = {};
  if (defined $params{'dns_recursion'} and ($params{'dns_recursion'} eq 'any' or $params{'dns_recursion'} eq 'localnets' or $params{'dns_recursion'} eq 'localhost')) {
    $dnsSettingsAttributes = {
      'recursion' => $params{'dns_recursion'}
    }
  }
  my $dnsNode = XmlNode->new(
    'dns-settings',
    'attributes' => $dnsSettingsAttributes
  );

  my $dnsZone = XmlNode->new(
    'dns-zone',
    'attributes' =>
      { 'email' => 'root@localhost.localdomain', 'type' => 'master' },
    'children' =>
      [ Status::make( (not exists $params{'dns_zone_status'} or $params{'dns_zone_status'} ne 'false') ? 0 : 16 ) ]
  );

  $dnsZone->setAttribute( 'serial-format', $params{'soa_serial_format'} ) if exists $params{'soa_serial_format'};

  my %zone_params = (
    'ttl'     => 1 * 86400,
    'refresh' => 3 * 3600,
    'retry'   => 1 * 3600,
    'expire'  => 7 * 86400,
    'minimum' => 3 * 3600
  );

  my %zone_units = (
    'ttl'     => 86400,
    'refresh' => 3600,
    'retry'   => 3600,
    'expire'  => 86400,
    'minimum' => 3600
  );

  foreach my $zone_param ( keys %zone_params ) {
    my $soa_param = 'soa_' . $zone_param;

    $dnsZone->addChild(
      $self->makeDnsZoneParam(
        $zone_param,
        exists $params{ $soa_param . '_unit' }
        ? $params{ $soa_param . '_unit' }
        : $zone_units{$zone_param},
        exists $params{$soa_param} ? $params{$soa_param}
        : $zone_params{$zone_param},
      )
    );
  }

  # dns records
  for my $ptrHash (@records) {
    my $dnsrec = $self->makeDnsRecord($ptrHash);
    if ($dnsrec) {
      $dnsZone->addChild($dnsrec);
    }
  }

  $dnsNode->addChild($dnsZone);

  # dump common acl for dns zone

  my $aclNode = XmlNode->new('common-acl');

  foreach my $param ( keys %params ) {
    if ( $param =~ /^DNS_Allow_Transfer/ ) {
      $aclNode->addChild( XmlNode->new( 'list-item', 'content' => $params{$param} ) );
    }
  }

  $dnsNode->addChild($aclNode);

  my $subdomainOwnZone = 'false';
  if (exists($params{'subdomain_own_zones'})) {
    $subdomainOwnZone =  $params{'subdomain_own_zones'} eq 'false' ? 'false' : 'true';
  }else {
    $subdomainOwnZone = 'false';
  }

  $dnsNode->addChild( XmlNode->new( 'subdomain-own-zones', 'content' => $subdomainOwnZone ) );

  $parent->addChild($dnsNode);
}

sub setServerWebSettings {
  my ($self, $embeddedInfo) = @_;

  if (defined($embeddedInfo->{'web-settings'})) {
    my ($webSettings) = @{$embeddedInfo->{'web-settings'}};
    $self->addEmbeddedInfo($self->{serverNode}, 'web-settings', $webSettings);
  }
}

sub addEmbeddedInfo {
  my ($self, $parentNode, $embeddedNodeName, $embeddedInfo) = @_;

  eval {require XML::Simple; 1;};
  my $xs = XML::Simple->new(ForceArray => 1);
  my $embeddedInfoXml = $xs->XMLout($embeddedInfo, RootName => $embeddedNodeName);
  $embeddedInfoXml = Encode::encode('UTF-8', $embeddedInfoXml); # workaround for UTF8 symbols
  $parentNode->addChild(XmlNode->new(undef, 'raw' => $embeddedInfoXml));
}

sub addServerCertificate {
  my ( $self, $name, $cert, $csr, $ca_cert, $pvt_key, $default) = @_;

  my $parent = $self->{serverNode};
  if ( !defined($parent) ) {
    Logging::warning('Unable to dump server certificate, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $root = XmlNode->new('certificate');

  addUrlDecodedTextNode( $root, 'certificate-data', $cert ) if defined($cert);
  addUrlDecodedTextNode( $root, 'signing-request',  $csr ) if defined($csr);
  addUrlDecodedTextNode( $root, 'ca-certificate',   $ca_cert ) if defined($ca_cert);
  addUrlDecodedTextNode( $root, 'private-key', $pvt_key ) if defined($pvt_key);
  $root->setAttribute( 'name', $name );

  if ($default) {
    $root->setAttribute('default', 'true');
  }

  $parent->getChild( 'certificates', 1 )->addChild($root);
}

sub addTemplateToServer {
  my ( $self, $templateType, $templateName, $dataPtr ) = @_;
  my %templates = (
    'reseller' => 'reseller-template',
    'domain' => 'domain-template',
    'domain_addon' => 'domain-template'
  );

  unless ( exists( $templates{$templateType} ) ){
    return;
  }

  $self->setServerSettings() unless defined $self->{serverNode};

  my $root = $self->{serverNode};
  my $node = $self->makeTemplateNode($templates{$templateType}, $templateName, $dataPtr);

  $root->getChild( 'account-templates', 1 )->addChild($node);

}

sub makePlanItemNode {
  my ( $self, $planItemPtr, $planItemPropsPtr, $customButtonsDir ) = @_;
  return unless ( ref($planItemPtr) =~ /HASH/ );
  return unless ( ref($planItemPropsPtr) =~ /HASH/ );

  return unless ( exists( $planItemPtr->{'name'}) && exists( $planItemPtr->{'classname'}) && exists( $planItemPtr->{'uuid'}) );

  my $planItemNode = XmlNode->new('plan-item');
  $planItemNode->setAttribute( 'name',    $planItemPtr->{'name'} );
  $planItemNode->setAttribute( 'type',    $planItemPtr->{'classname'} );
  $planItemNode->setAttribute( 'guid',    $planItemPtr->{'uuid'} );
  $planItemNode->setAttribute( 'visible', ($planItemPtr->{'isVisible'})? 'true' : 'false' );

  if ( ( $planItemPtr->{'classname'} eq 'Plan_Item_Custom') && ( defined $planItemPropsPtr->{'file'} ) ) {
    my $file = $planItemPropsPtr->{'file'};
    my $filename = "$customButtonsDir/$file";
    if( -f $filename) {
      my $cid = $self->{content_transport}->addAdminContent('icon', undef, 'icon_planitem'.$planItemPtr->{'id'}, 'directory' => $customButtonsDir, 'include' => [$file]);
      $planItemNode->getChild('content', 1, 1)->addChild($cid) if $cid;
    }
  }
  my $applicableNode = XmlNode->new( 'applicable' );
  $applicableNode->addChild( XmlNode->new( 'applicable-to-subscription' ) ) if ( $planItemPtr->{'applicableToSubscription'} == 1 );
  $applicableNode->addChild( XmlNode->new( 'applicable-to-site'         ) ) if ( $planItemPtr->{'applicableToSite'        } == 1 );
  $applicableNode->addChild( XmlNode->new( 'applicable-to-email'        ) ) if ( $planItemPtr->{'applicableToEmail'       } == 1 );
  $planItemNode->addChild( $applicableNode );

  my $propertiesNode = XmlNode->new( 'properties' );
  while ( my ($name,$value) = each( %{$planItemPropsPtr} ) ) {
    next if ($name eq '');
    my $planItemPropertyNode = XmlNode->new( 'plan-item-property' );
    $planItemPropertyNode->setAttribute( 'name', $name);
    if ( $value ne '' ) {
      $planItemPropertyNode->setText( $value );
    }
    $propertiesNode->addChild( $planItemPropertyNode );
  }
  $planItemNode->addChild( $propertiesNode );

  return $planItemNode;
}

sub addPlanItemToServer {
  my ( $self, $planItemPtr, $planItemPropsPtr, $customButtonsDir ) = @_;
  my $root = $self->{serverNode};

  my $node = $self->makePlanItemNode( $planItemPtr, $planItemPropsPtr, $customButtonsDir );

  $root->getChild( 'account-templates', 1 )->addChild($node) if defined $node;
}

sub setServerAppVault {
  my ( $self ) = @_;

  my $root = $self->{serverNode};
  return $root->getChild('application-vault', 1);
}

sub setServerPackagesPool {
  my ( $self ) = @_;

  my $appVaultNode = $self->setServerAppVault();
  return $appVaultNode->getChild('sapp-packages-pool', 1);
}

my %dumpedApplications;

sub addServerAppPackage {
  my ($self, $name, $version, $release, $distrib_path, $file_name, $is_uploaded, $is_visible, $applicationPackage) = @_;

  my $fullname = $name."-".$version."-".$release;
  if (exists $dumpedApplications{$fullname}) {
        return;
  }

  $dumpedApplications{$fullname} = 1;

  my $appPackagesPoolNode = $self->setServerPackagesPool();

  my $packageNode = XmlNode->new('sapp-package');
  if ( defined $applicationPackage and $applicationPackage->getSappPackageId() ) {
      $packageNode->addChild(  XmlNode->new( 'sapp-package-id', 'content' => $applicationPackage->getSappPackageId() ) );
  }
  $packageNode->addChild(  XmlNode->new( 'sapp-name', 'content' => $name ) );
  $packageNode->addChild(  XmlNode->new( 'sapp-version', 'content' => $version ) )  if ( $version );
  $packageNode->addChild( XmlNode->new( 'sapp-release', 'content' => $release ) )  if ( $release );
  $packageNode->addChild( XmlNode->new( 'sapp-uploaded' ))  if ( defined $is_uploaded && $is_uploaded ne "0" );
  $packageNode->addChild( XmlNode->new( 'sapp-visible' ))  if ( defined $is_visible && $is_visible ne "0" );


  if( $distrib_path && $file_name )
  {
    my $real_path = $distrib_path . "/" . $file_name;

    if ( -e $real_path ) {
      my $cid = $self->{content_transport}->addAdminContent(
        'sapp-distrib',
        undef,
        "sapp-distrib." . HelpFuncs::generateProcessId(),
        "directory" => $distrib_path,
        "include"   => [$file_name]
      );
      $packageNode->getChild( 'content', 1, 1 )->addChild( $cid ) if $cid;
    }
    else { return; }

    if ( defined $applicationPackage ) {
      my $settings = $applicationPackage->getSettings();
      if ( $settings ) {
        my $settingsNode = XmlNode->new('sapp-settings');
        foreach my $setting ( keys %{$settings} ) {
            my $settingNode = XmlNode->new('setting');
            $settingNode->addChild( XmlNode->new( 'name', 'content' => $setting ) );
            if (ref($settings->{$setting}) =~ /ARRAY/) {
              foreach my $value ( @{$settings->{$setting}}) {
                $settingNode->addChild( XmlNode->new( 'value', 'content' => $value ) );
              }
            } else {
              $settingNode->addChild( XmlNode->new( 'value', 'content' => $settings->{$setting} ) );
            }
            $settingsNode->addChild($settingNode);
        }
        $packageNode->addChild($settingsNode);
      }
    }

    $appPackagesPoolNode->addChild($packageNode);
  }
}

sub setServerAppItemsPool {
  my ( $self ) = @_;

  my $appVaultNode = $self->setServerAppVault();
  return $appVaultNode->getChild('sapp-items-pool',1);
}

sub addServerAppItem {
  my ($self, $paramsPtr) = @_;

  my %params = %{$paramsPtr};

  my $appItemsPoolNode = $self->setServerAppItemsPool();

  my $appItemNode = XmlNode->new('sapp-item');
  $appItemNode->setAttribute( 'enabled',
    ( $params{'disabled'} eq 'true' ) ? 'false' : 'true' )
    if defined $params{'disabled'};
  my $appSpecNode = XmlNode->new('sapp-spec');
  $appSpecNode->addChild(
    XmlNode->new( 'sapp-name', 'content' => $params{'sapp_name'} ) );
  $appSpecNode->addChild(
    XmlNode->new(
      'sapp-version', 'content' => $params{'sapp_version'}
    )
  ) if defined $params{'sapp_version'};
  $appSpecNode->addChild(
    XmlNode->new(
      'sapp-release', 'content' => $params{'sapp_release'}
    )
  ) if defined $params{'sapp_release'};
  $appItemNode->addChild($appSpecNode);
  $appItemNode->addChild(
    XmlNode->new(
      'license-type',
      'content' => defined( $params{'license_type_id'} )
      ? $params{'license_type_id'}
      : "0"
    )
  );
  $appItemNode->addChild(
    XmlNode->new( 'shared', 'content' => $params{'shared'} ) )
    if defined $params{'shared'};
  $appItemNode->addChild(
    XmlNode->new( 'description', 'content' => $params{'description'} )
  ) if defined $params{'description'};
  $appItemNode->addChild(
    XmlNode->new(
      'instances-limit', 'content' => $params{'instances_limit'}
    )
  ) if defined $params{'instances_limit'};

  $appItemsPoolNode->addChild($appItemNode);
}

sub setServerAppLicensesPool {
  my ( $self ) = @_;

  my $appVaultNode = $self->setServerAppVault();
  return $appVaultNode->getChild('sapp-licenses-pool',1);
}

sub addServerAppLicense {
  my ($self, $keyNumber, $licenseType, $licenseText) = @_;

  my $licensePoolNode = $self->setServerAppLicensesPool();

  my $licenseNode = XmlNode->new(
    'sapp-license',
    'children' => [
      XmlNode->new( 'key-number',   'content' => $keyNumber ),
      XmlNode->new( 'license-type', 'content' => $licenseType ),
      XmlNode->new( 'license-text', 'content' => $licenseText )
    ]
  );

  $licensePoolNode->addChild($licenseNode);
}

sub setServerSBConfig {
  my ( $self, $configPtr ) = @_;

  my @config = @{$configPtr};

  my $serverNode = $self->{serverNode};

  my $sbNode = XmlNode->new('sb-config');
  for my $ptrHash (@config) {
    $sbNode->addChild(
      XmlNode->new(
        'sb-param',
        'children' => [
          XmlNode->new(
            'sb-param-name', 'content' => $ptrHash->{'param_name'}
          ),
          XmlNode->new(
            'sb-param-value', 'content' => $ptrHash->{'param_value'}
          )
        ]
      )
    );
  }
  $serverNode->addChild($sbNode);
}

sub setGLServerSettings {
  my ( $self, $paramsPtr) = @_;

  my %params = %{$paramsPtr};

  my $serverNode = $self->{serverNode};

  my $glNode = XmlNode->new('grey-listing');

  $glNode->setAttribute('grey-interval', $params{'greyInterval'}) if defined $params{'greyInterval'};
  $glNode->setAttribute('expire-interval', $params{'expireInterval'}) if defined $params{'expireInterval'};
  $glNode->setAttribute('penalty-enabled', $params{'penaltyEnabled'}) if defined $params{'penaltyEnabled'};
  $glNode->setAttribute('penalty-interval', $params{'penaltyInterval'}) if defined $params{'penaltyInterval'};
  $glNode->setAttribute('enabled', $params{'enabled'}) if defined $params{'enabled'};
  $glNode->setAttribute('personal-conf', $params{'personal-conf'}) if defined $params{'personal-conf'};

  my $wlNode = XmlNode->new('white-list');

  foreach my $wdomain (@{$params{'white_domains'}}) {
        $wlNode->addChild(XmlNode->new('list-item', 'content' => $wdomain));
  }

  $glNode->addChild($wlNode);

  my $blNode = XmlNode->new('black-list');

  foreach my $bdomain (@{$params{'black_domains'}}) {
        $blNode->addChild(XmlNode->new('list-item', 'content' => $bdomain));
  }

  $glNode->addChild($blNode);

  $serverNode->addChild($glNode);

}

sub setControlsVisibility {
  my ( $self, $paramsPtr ) = @_;

  my %params = %{$paramsPtr};

  my $serverNode = $self->{serverNode};
  my $controlsVisibilityNode = XmlNode->new('controls-visibility');

  $controlsVisibilityNode->addChild( XmlNode->new( 'hide-domain-registration-buttons', 'content' => HelpFuncs::negation(HelpFuncs::checkValue($params{'domain_registration'},'true','false')) ) );
  $controlsVisibilityNode->addChild( XmlNode->new( 'hide-certificate-purchasing-buttons', 'content' => HelpFuncs::negation(HelpFuncs::checkValue($params{'cert_purchasing'},'true','false')) ) );
  $controlsVisibilityNode->addChild( XmlNode->new( 'hide-extra-services-buttons', 'content' => HelpFuncs::negation(HelpFuncs::checkValue($params{'extras'},'true','false')) ) );
  $controlsVisibilityNode->addChild( XmlNode->new( 'hide-mail-bouncing-controls', 'content' => HelpFuncs::negation(HelpFuncs::checkValue($params{'mail_bounce'},'true','false')) ) );

  $controlsVisibilityNode->addChild( XmlNode->new( 'domain-registration-url', 'content' => $params{'domain_registration_url'})) if defined $params{'domain_registration_url'};
  $controlsVisibilityNode->addChild( XmlNode->new( 'domain-management-url', 'content' => $params{'domain_management_url'})) if defined $params{'domain_management_url'};
  $controlsVisibilityNode->addChild( XmlNode->new( 'cert-purchasing-url', 'content' => $params{'cert_purchasing_url'})) if defined $params{'cert_purchasing_url'};
  $controlsVisibilityNode->addChild( XmlNode->new( 'mpc-portal-url', 'content' => $params{'mpc_portal_url'})) if defined $params{'mpc_portal_url'};

  $serverNode->getChild( 'interface-preferences', 1 )->addChild($controlsVisibilityNode);
}

sub setServerBackupSettings {
  my ( $self, $paramsPtr ) = @_;

  my %params = %{$paramsPtr};

  my $serverNode = $self->{serverNode};

  my ($lowPriority,$doNotCompress,$maxProcesses);

  $lowPriority = $params{'bu_nice'};
  $doNotCompress = $params{'bu_nozip'};
  $maxProcesses = $params{'max_bu_proc_number'};

  if ( defined $lowPriority or defined $doNotCompress or defined $maxProcesses ) {
  	my $serverBackupSettingsNode = XmlNode->new('backup-settings');

  	$serverBackupSettingsNode->setAttribute( 'low-priority', $lowPriority)
			if defined $lowPriority and $lowPriority eq 'true';

  	$serverBackupSettingsNode->setAttribute( 'do-not-compress', $doNotCompress)
			if defined $doNotCompress and $doNotCompress eq 'true';

		$serverBackupSettingsNode->setAttribute( 'max-processes', $maxProcesses)
  		if defined $maxProcesses;

  	$serverNode->addChild($serverBackupSettingsNode);
  }
}

sub addServerPreferences {
  my ( $self, $paramsPtr ) = @_;

  my %params = %{$paramsPtr};

  my $serverNode = $self->{serverNode};

  my $serverPrefsNode = XmlNode->new('server-preferences');

  if ( defined $params{'forbid_create_dns_subzone'} ) {
    $serverPrefsNode->addChild(
      XmlNode->new(
        'forbid-create-dns-subzone',
        'content' => $params{'forbid_create_dns_subzone'}
      )
    );
  }
  if ( defined $params{'force_db_user_prefix'} ) {
    $serverPrefsNode->addChild(
      XmlNode->new(
        'force-db-user-prefix',
        'content' => $params{'force_db_user_prefix'}
      )
    );
  }

  if ( defined $params{'db_user_length'} ) {
    $serverPrefsNode->addChild(
      XmlNode->new(
        'db-user-length', 'content' => $params{'db_user_length'}
      )
    );
  }

  if ( defined $params{'hide_top_advertisement'} ) {
    $serverPrefsNode->addChild(
      XmlNode->new(
        'hide-top-advertisement',
        'attributes' => { 'hide' => $params{'hide_top_advertisement'} }
      )
    );
  }
  if ( defined $params{'traffic_accounting'} ) {
    my $val = $params{'traffic_accounting'};
    if    ( $val == 1 ) { $val = 'in'; }
    elsif ( $val == 2 ) { $val = 'out'; }
    else                { $val = 'both'; }
    $serverPrefsNode->addChild(
      XmlNode->new(
        'traffic-direction', 'attributes' => { 'traffic' => $val }
      )
    );
  }
  if ( defined $params{'restart_apache_interval'} ) {
    $serverPrefsNode->addChild(
      XmlNode->new(
        'restart-apache', 'content' => $params{'restart_apache_interval'}
      )
    );
  }
  if ( defined $params{'stat_ttl'} ) {
    $serverPrefsNode->addChild(
      XmlNode->new( 'stat-keep', 'content' => $params{'stat_ttl'} ) );
  }
  if ( defined $params{'size_count_type'} ) {
    $serverPrefsNode->addChild(
      XmlNode->new(
        'disk-space-count-type',
        'attributes' => {
            'count-type' => $params{'size_count_type'} eq 'byte'
          ? 'byte'
          : 'block'
        }
      )
    );
  }

  my $duNode = XmlNode->new('disk-usage');
  $serverPrefsNode->addChild($duNode);
  $duNode->addChild( XmlNode->new('include-logs') )
    if defined $params{'include_logs'}
      and $params{'include_logs'} eq 'true';
  $duNode->addChild( XmlNode->new('include-databases') )
    if defined $params{'include_databases'}
      and $params{'include_databases'} eq 'true';
  $duNode->addChild( XmlNode->new('include-mailboxes') )
    if defined $params{'include_mailboxes'}
      and $params{'include_mailboxes'} eq 'true';
  $duNode->addChild( XmlNode->new('include-maillists') )
    if defined $params{'include_maillists'}
      and $params{'include_maillists'} eq 'true';
  $duNode->addChild( XmlNode->new('include-domaindumps') )
    if defined $params{'include_domaindumps'}
      and $params{'include_domaindumps'} eq 'true';
  $duNode->addChild( XmlNode->new('include-admindumps') )
    if defined $params{'include_admindumps'}
      and $params{'include_admindumps'} eq 'true';

  $serverPrefsNode->addChild( XmlNode->new( 'force-db-prefix', 'content' => $params{'force_db_prefix'} ) ) if defined $params{'force_db_prefix'};
  $serverPrefsNode->addChild( XmlNode->new( 'multiple-session', 'content' => $params{'multiply_login'} ) ) if defined $params{'multiply_login'};
  $serverPrefsNode->addChild( XmlNode->new( 'lock-screen', 'content' => ($params{'disable_lock_screen'} eq 'true' ? 'false' : 'true' ) ) ) if defined $params{'disable_lock_screen'};

  $serverPrefsNode->addChild( XmlNode->new( 'aps-catalog-url', 'content' => $params{'apscatalog_url'} ) ) if defined $params{'apscatalog_url'};

  $serverPrefsNode->addChild( XmlNode->new( 'mode', 'content' => (defined($params{'power_user_panel'}) and $params{'power_user_panel'} eq 'true') ? 'poweruser' : 'standard'));

  $serverNode->addChild($serverPrefsNode);
}

sub addServerMailmanConfiguration {
  my ($self) = @_;

  unless ( defined Mailman::version() ) {
    Logging::debug("Unable to found Mailman installation");
    return;
  }

  if (Mailman::isMailmanNotConfigured() eq '1') {
    Logging::debug("Mailman is not configured. Nothing to backup");
    return;
  }

  my $adminPassword =  Mailman::getListPassword('mailman');
  my @owners = Mailman::getListOwners('mailman');

  if (defined $adminPassword and defined $owners[0]) {
    my $root = $self->{serverNode};

    my $mailmanNode = XmlNode->new('mailman');

    $mailmanNode->setAttribute('owner', $owners[0]);
    $mailmanNode->setAttribute('password', $adminPassword);

    $root->addChild($mailmanNode);
  }
}

sub addDisableMailUiOption {
  my ($self, $state) = @_;
  my $serverNode = $self->{serverNode};
  my $serverPrefNode = $serverNode->getChild( 'server-preferences', 1 );
  my $disableUiNode = XmlNode->new('disable-mail-ui', 'content' => $state);
  $serverPrefNode->addChild($disableUiNode);
}

sub _makeExternalWebmails {
  my ($self, $webmailsPtr) = @_;
  my $serverNode = $self->{serverNode};
  my $mailPrefNode = $serverNode->getChild( 'mail-settings', 1 );
  my @webmails = @{$webmailsPtr};

  foreach my $webmail (@webmails) {
    my $extWebMailNode = XmlNode->new('external-webmail');
    $extWebMailNode->addChild( XmlNode->new('name', 'content' => $webmail->{'name'}) );
    $extWebMailNode->addChild( XmlNode->new('url', 'content' => $webmail->{'url'}) );
    $extWebMailNode->addChild( XmlNode->new('enabled') ) if $webmail->{'enabled'} == 1;
    $mailPrefNode->addChild($extWebMailNode);
  }
}

sub addRestrictionItem {
  my ( $self, $type, $ip, $mask ) = @_;

  $ip = '' unless defined $ip;
  $mask = '' unless defined $mask;

  my $serverNode = $self->{serverNode};

  my $serverPrefNode = $serverNode->getChild( 'server-preferences', 1 );

  my $adminAccessRestrictionsNode = $serverPrefNode->getChild( 'admin-access-restrictions', 1 );

  my $restrictionItemNode = XmlNode->new( 'restriction-item' );
  $restrictionItemNode->setAttribute( 'type', $type eq 'allow'? 'allow' : 'deny' );
  $restrictionItemNode->setAttribute( 'ip-address', $ip );
  $restrictionItemNode->setAttribute( 'ip-subnet-mask', $mask );

  $adminAccessRestrictionsNode->addChild( $restrictionItemNode );
}

sub addRootAdmin {
  my ($self, $id, $guid, $fullHostName) = @_;

  return if defined $self->{admin};

  my $rootNode = $self->{root};
  if ( !defined($rootNode) ) {
    Logging::warning('Unable to dump Panel admin user, because of dump XML structure is not full', 'PackerStructure');
    return;
  }
  my $root = XmlNode->new( 'admin' );
  $self->{admin} = $root;
  $root->setAttribute( 'id', $id ) if $id;
  $root->setAttribute( 'guid', $guid ) if $guid;
  $root->setAttribute('name', $fullHostName) if $fullHostName;

  $rootNode->addChild($root);
}

### Client functions ##

sub addRootClient {
  my ( $self, $clientId, $clientPtr, $passwdPtr, $status ) = @_;

  my $rootNode = $self->{root};
  if ( !defined($rootNode) ) {
    Logging::warning('Unable to dump root client user, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $root = $self->makeClientNode($clientId, $clientPtr, $passwdPtr, $status, 1);

  $rootNode->addChild($root);
  push @{$self->{roots}}, $root;
}

sub addRootReseller{
  my ( $self, $clientId, $clientPtr, $passwdPtr, $status ) = @_;

  my $rootNode = $self->{root};
  if ( !defined($rootNode) ) {
    Logging::warning('Unable to dump reseller, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $root = $self->makeResellerNode($clientId, $clientPtr, $passwdPtr, $status, 1);

  $rootNode->addChild($root);
  push @{$self->{roots}}, $root;
}

sub addResellerClient {
  my ( $self, $resellerId, $clientId, $clientPtr, $passwdPtr, $status ) = @_;

  my %client = %{$clientPtr};
  my %passwd = %{$passwdPtr};

  my $resellerNode = $self->{resellersNodes}->{$resellerId};
  if (!defined($resellerNode))
  {
    Logging::warning('Unable to dump reseller\'s client, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $root = $self->makeClientNode($clientId, \%client, \%passwd, $status, 0);

  $resellerNode->getChild( 'clients', 1 )->addChild($root);
  $self->regClientObjectBackupPath( $self->getResellersBackupPath( $resellerId ), $clientId, $clientPtr->{'login'} );

  if (exists $self->{resellersShortNodes}->{$resellerId}) {
    $self->{resellersShortNodes}->{$resellerId}->addChild($self->{clientsShortNodes}->{$clientId});
  }
}

sub addAdminClient {
  my ( $self, $clientId, $clientPtr, $passwdPtr, $status ) = @_;

  my %client = %{$clientPtr};
  my %passwd = %{$passwdPtr};

  my $admin = $self->{admin};
  if (!defined($admin))
  {
    Logging::warning('Unable to dump admin\'s client, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $root = $self->makeClientNode($clientId, \%client, \%passwd, $status, 0);

  $admin->getChild( 'clients', 1 )->addChild($root);
  $self->regClientObjectBackupPath( $self->getAdminBackupPath(), $clientId, $clientPtr->{'login'} );
}

sub addAdminReseller {
  my ( $self, $clientId, $clientPtr, $passwdPtr, $status ) = @_;

  my $admin = $self->{admin};
  if (!defined($admin))
  {
    Logging::warning('Unable to dump admin\'s reseller, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $root = $self->makeResellerNode($clientId, $clientPtr, $passwdPtr, $status, 0);

  $admin->getChild( 'resellers', 1 )->addChild($root);
  $self->regResellersObjectBackupPath( $self->getAdminBackupPath(), $clientId, $clientPtr->{'login'} );
}

sub makeResellerNode{
  my ( $self, $clientId, $clientPtr, $passwdPtr, $status, $needFullInfo ) = @_;

  my $root = $self->processClientNode( $clientId, $clientPtr, $passwdPtr, $status, 1, $needFullInfo );

  return $root;

}

sub makeClientNode {
  my ( $self, $clientId, $clientPtr, $passwdPtr, $status, $needFullInfo ) = @_;

  my $root = $self->processClientNode( $clientId, $clientPtr, $passwdPtr, $status, 0, $needFullInfo );

  return $root;
}

sub processClientNode {
  my ( $self, $clientId, $clientPtr, $passwdPtr, $status, $is_reseller, $needFullInfo ) = @_;

  my $root = XmlNode->new( $is_reseller ? 'reseller' : 'client');

  $root->getChild( 'preferences', 1 );
  $root->getChild( 'properties', 1 );

  my %client = %{$clientPtr};
  my %passwd = %{$passwdPtr};

  $root->setAttribute( 'id', $clientId );

  if ( defined $client{'uid'} ) {
    $root->setAttribute( 'uid', $client{'uid'} );
    $root->setAttribute( 'ownership', $client{'ownership'} eq 'true' ? 'true' : 'false' );
  }

  $root->setAttribute( 'guid', $client{'guid'} ) if ( defined $client{'guid'} );

  $root->setAttribute( 'external-id', $client{'external_id'} ) if ( defined $client{'external_id'} and $client{'external_id'} ne '');

  $root->setAttribute( 'vendor-guid', $client{'vendor-guid'} ) if ( defined $client{'vendor-guid'} );

  $root->setAttribute( 'vendor-login', $client{'vendor-login'} ) if ( defined $client{'vendor-login'} );

  $root->setAttribute( 'owner-guid', $client{'owner-guid'} ) if ( defined $client{'owner-guid'} and not $is_reseller );

  if ( exists $client{'login'} ) {
    $root->setAttribute( 'name', ( defined $client{'login'} )? $client{'login'}:'' );
  }
  if ( exists $client{'pname'} ) {
    $root->setAttribute( 'contact', ( defined $client{'pname'} )? $client{'pname'}:'' );
  }
  if ( exists $client{'cr_date'} ) {
    $root->setAttribute( 'cr-date', ( defined $client{'cr_date'} )? $client{'cr_date'}:'' );
  }
  if ( exists $client{'owner-name'} and not $is_reseller ) {
    $root->setAttribute( 'owner-name', $client{'owner-name'} );
  }

  my $item;
  my $props = $root->getChild( 'properties', 1 );
  $item = CommonPacker::makePasswordNode( $passwd{'password'}, CommonPacker::normalizePasswordType( $passwd{'type'} ) );
  $props->addChild($item);

  $props->addChild( Status::make($status) );

  $self->{clientNodes}->{$clientId} = $root;
  $self->{resellersNodes}->{$clientId} = $root if $is_reseller;

  if ($needFullInfo) {
    return $root;
  } else {
    my $shortInfoNodeName = ($is_reseller ? 'reseller' : 'client') . '-info';
    my $shortInfoNode = XmlNode->new($shortInfoNodeName);
    $shortInfoNode->setAttribute('name', ( defined $client{'login'} )? $client{'login'}:'');
    $shortInfoNode->setAttribute( 'guid', $client{'guid'} ) if defined $client{'guid'};
    if ($is_reseller) {
      $self->{resellersShortNodes}->{$clientId} = $shortInfoNode;
    } else {
      $self->{clientsShortNodes}->{$clientId} = $shortInfoNode;
    }
    return $shortInfoNode;
  }

}

sub finishClient {
  my ( $self, $clientId ) = @_;

  my $root = $self->{clientNodes}->{$clientId};
  if ( !defined($root) ) {
    Logging::warning('Unable to finish client node, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  #TODO: uncomment
  #$root->ReleaseCode();
}

sub setClientPinfo {
  my ( $self, $clientId, $clientPtr ) = @_;

  my %client = %{$clientPtr};

  my $root = $self->{clientNodes}->{$clientId};
  if ( !defined($root) ) {
    Logging::warning('Unable to dump client personal information, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my ( $field, $name );

  my %clientsInfo = (
    'company' => 'cname',
    'phone'   => 'phone',
    'fax'     => 'fax',
    'address' => 'address',
    'city'    => 'city',
    'state'   => 'state',
    'zip'     => 'pcode',
    'country' => 'country',
    'email'   => 'email',
  );
  my $prefs = $root->getChild( 'preferences', 1 );
  while ( ( $name, $field ) = each(%clientsInfo) ) {
    if ( exists( $client{$field} ) && $client{$field} ) {
      $prefs->addChild(
        XmlNode->new(
          'pinfo',
          'content'    => $client{$field},
          'attributes' => { 'name' => $name }
        )
      );
    }
  }
}

sub setClientLocale {
  my ( $self, $clientId, $locale ) = @_;

  my $root = $self->{clientNodes}->{$clientId};
  if ( !defined($root) ) {
    Logging::warning('Unable to dump client locale, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  $root->getChild( 'preferences', 1 )->addChild(
    XmlNode->new(
      'pinfo',
      'content'    => $locale,
      'attributes' => { 'name' => 'locale' }
    )
  );
}

sub addClientLimit {
  my ( $self, $clientId, $name, $value ) = @_;

  my $root = $self->{clientNodes}->{$clientId};
  if ( !defined($root) ) {
    Logging::warning('Unable to dump client limits, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  $self->insertLimitNode( $root, $name, $value );
}

sub addClientPermission {
  my ( $self, $clientId, $name, $value ) = @_;

  my $root = $self->{clientNodes}->{$clientId};
  if ( !defined($root) ) {
    Logging::warning('Unable to dump client permissions, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  $self->makePermissionNode( $root->getChild( 'limits-and-permissions', 1 ), $name, $value );
}

sub addClientDomainSkeleton {
  my ( $self, $clientId, $path, $arc_path ) = @_;

  my $root = $self->{clientNodes}->{$clientId};
  if ( !defined($root) ) {
    Logging::warning('Unable to dump client\'s domain skeleton, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $dumpFile = $self->{content_transport}->addClientContent( 'skeleton', $clientId, $arc_path, 'directory' => $path );
  $root->getChild( 'content', 1, 1 )->addChild( $dumpFile) if $dumpFile;
}

sub addServerSkeleton {
  my ( $self, $path, $arc_path) = @_;

  my $root = $self->{serverNode};

  my $dumpFile = $self->{content_transport}->addAdminContent('skeleton', undef, $arc_path, 'directory' => $path);

  $root->getChild('content', 1, 1)->addChild($dumpFile) if $dumpFile;
}

sub addSmbDbDump {
  my ($self, $options) = @_;
  my $root = $self->{serverNode};
  $options->{utf8names} = 1;
  my $cid = $self->{content_transport}->addDbContent('smb-sqldump', $self->getAdminBackupPath('smbdb'), %{$options});
  $root->getChild('content', 1, 1)->addChild($cid) if $cid;
}

sub addApsCache {
  my ($self, $path) = @_;
  my $root = $self->{serverNode};
  my $dumpFile = $self->{content_transport}->addAdminContent('aps-cache', undef, 'aps_cache', 'directory' => $path, 'checkEmptyDir' => 1 );
  $root->getChild('content', 1, 1)->addChild($dumpFile) if $dumpFile;
}

sub addResellerDomainsClientsNodes {
  my ( $self, $clientId ) = @_;

  my $root = $self->{clientNodes}->{$clientId};
  if ( !defined($root) ) {
    Logging::warning('Unable to dump reseller\'s clients and domains, because of dump XML structure is not full', 'PackerStructure');
    return;
  }
  $root->addChild( XmlNode->new('domains') );
  $root->addChild( XmlNode->new('clients') );
}

sub removeSiteNode {
  my ( $self, $domainId, $siteName ) = @_;

  my $domainNode = $self->{domainNodes}->{$domainId};
  my $sitesNode = $domainNode->getChild( 'phosting' )->getChild( 'sites' );

  if ( defined $sitesNode ) {
    $sitesNode->removeChildByAttribute( 'site', 'name', $siteName );
  }
}

sub removeDatabaseNode {
  my ( $self, $domainId, $db ) = @_;
  my $domainNode = $self->{domainNodes}->{$domainId};
  my $dbNode = $domainNode->getChild( 'databases' );
  if ( defined $dbNode ) {
    $dbNode->removeChildByAttribute( 'database', 'id', $db->{'id'} );
  }
}

sub removeServerNode {
  my ( $self ) = @_;
  $self->{serverNode} = undef;
}

sub removeMailuserNode {
  my ( $self, $domainId, $mail ) = @_;
  my $domainNode = $self->{domainNodes}->{$domainId};
  my $mailSystemNode = $domainNode->getChild( 'mailsystem' );
  my $mailUsersNode = $mailSystemNode->getChild( 'mailusers' );

  if ( defined $mailUsersNode ) {
    $mailUsersNode->removeChildByAttribute( 'mailuser', 'id', $mail->{'id'} );
  }
}

sub removeMailSystemNode {
  my ( $self, $domainId ) = @_;
  my $domainNode = $self->{domainNodes}->{$domainId};
  $domainNode->removeChildren( 'mailsystem' );
}

sub removeHostingNode {
  my ( $self, $domainId, $hostingType ) = @_;
  my $domainNode = $self->{domainNodes}->{$domainId};
  my $hostingNodeName;
  if ( $hostingType eq 'vrt_hst' )  {
    $hostingNodeName = 'phosting';
  } elsif ( $hostingType eq 'std_fwd' ) {
    $hostingNodeName = 'shosting';
  } elsif ( $hostingType eq 'frm_fwd' ) {
    $hostingNodeName = 'fhosting';
  }
  $domainNode->removeChildren( $hostingNodeName );
}

sub addClientIps {
  my ( $self, $clientId, $ipsPtr ) = @_;

  my %ips = %{$ipsPtr};

  my $root = $self->{clientNodes}->{$clientId};
  if ( !defined($root) ) {
    Logging::warning('Unable to dump client\'s IP settings, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $ip_pool = XmlNode->new('ip_pool');
  $root->addChild($ip_pool);

  while( my($ip,$iptype) = each(%ips) ){
   $ip_pool->addChild( $self->makeIpNode($ip, $iptype) );
  }
}

sub setClientTraffic{
  my ( $self, $clientId, $trafficValue ) = @_;

  my $parent = $self->{clientNodes}->{$clientId};
  if ( !defined($parent) ) {
    Logging::warning('Unable to dump client traffic, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  $parent->addChild( XmlNode->new( 'traffic', 'content' => $trafficValue ) );
}

sub addResellerDomainTemplate {
  my $self = shift;
  $self->addClientDomainTemplate( @_ );
}

sub addClientDomainTemplate {
  my ( $self, $clientId, $templateName, $dataPtr ) = @_;

  my $root = $self->{clientNodes}->{$clientId};

  my $node = $self->makeTemplateNode('domain-template', $templateName, $dataPtr);

  $root->getChild( 'preferences', 1 )->addChild($node);
}

sub addClientCustomButton {
    my ( $self, $clientId, $id, $optionsPtr, $customButtonsDir, $icon ) = @_;

    my $parent = $self->{clientNodes}->{$clientId};
    if ( !defined($parent) ) {
      Logging::warning('Unable to dump client\'s custom buttons settings, because of dump XML structure is not full', 'PackerStructure');
      return;
    }

    my $node = $self->makeCustomButtonNode( 'clients', $clientId, $id, $optionsPtr, $customButtonsDir,
      $icon );

    $parent->getChild( 'preferences', 1 )->addChild($node);
}

sub setPpbConnection {
    my ($self, $clientParams) = @_;

    my $ppbUrl = undef;
    my $apiVersion = undef;

    my $parent;

    $parent = $self->{serverNode};

    $ppbUrl = $clientParams->{'ppb-url'} if exists $clientParams->{'ppb-url'};

    if (defined $ppbUrl && defined $apiVersion) {
        my $ppbConnectionNode = XmlNode->new('ppb-connection');
        $ppbConnectionNode->addChild(XmlNode->new('ppb-url', 'content' => $ppbUrl));
        $parent->getChild( 'properties', 1)->addChild($ppbConnectionNode);
    }
}

### Domain functions ##
my @_rootDomains;

sub addRootDomain {
  my ( $self, $domainId, $domainName, $domainAsciiName, $domainPtr, $ownerGuid ) = @_;
  $domainAsciiName = $domainName if not $domainAsciiName;

  my $root = $self->makeDomainNode($domainId, $domainName, $domainPtr, 0, 1);
  push @_rootDomains, $root;
}

sub addRootDomains {
  my $self = shift;

  my $rootNode = $self->{root};
  if ( !defined($rootNode) ) {
    Logging::warning('Unable to dump root domains, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  for my $domainNode ( @_rootDomains ) {
    $rootNode->addChild($domainNode);
    push @{$self->{roots}}, $domainNode;
  }
}

sub addAdminDomain {
    my ( $self, $domainId, $domainName, $domainAsciiName, $domainPtr ) = @_;
    $domainAsciiName = $domainName if not $domainAsciiName;

    my $admin = $self->{admin};
    if ( !defined($admin) ) {
      Logging::warning('Unable to dump admin\'s domain, because of dump XML structure is not full', 'PackerStructure');
      return;
    }

    my $root = $self->makeDomainNode($domainId, $domainName, $domainPtr, 0, 0);

    $admin->getChild( 'domains', 1 )->addChild($root);
    $self->regDomainObjectBackupPath( $self->getAdminBackupPath(), $domainId, $domainAsciiName );

}

sub addResellerDomain {
    my ( $self, $resellerId, $domainId, $domainName, $domainAsciiName, $domainPtr ) = @_;
    $domainAsciiName = $domainName if not $domainAsciiName;

    my $resellerNode = $self->getCashedClientNode($resellerId);
    return unless defined $resellerNode;

    my $root = $self->makeDomainNode($domainId, $domainName, $domainPtr, 0, 0);

    $resellerNode->getChild( 'domains', 1 )->addChild($root);
    $self->regDomainObjectBackupPath( $self->getResellersBackupPath( $resellerId ), $domainId, $domainAsciiName );

    if (exists $self->{resellersShortNodes}->{$resellerId}) {
      $self->{resellersShortNodes}->{$resellerId}->addChild($self->{domainShortNodes}->{$domainId});
    }
}

sub addClientDomain {
    my ( $self, $clientId, $domainId, $domainName, $domainAsciiName, $domainPtr ) = @_;
    $domainAsciiName = $domainName if not $domainAsciiName;

    my $clientNode = $self->getCashedClientNode($clientId);
    return unless defined $clientNode;

    my $root = $self->makeDomainNode($domainId, $domainName, $domainPtr, 0, 0);

    $clientNode->getChild( 'domains', 1 )->addChild($root);
    $self->regDomainObjectBackupPath( $self->getClientsBackupPath( $clientId ), $domainId, $domainAsciiName );

    if (exists $self->{clientsShortNodes}->{$clientId}) {
      $self->{clientsShortNodes}->{$clientId}->addChild($self->{domainShortNodes}->{$domainId});
    }
}

my %_rootUsers;
my %_rootRoles;

sub addDomainSite {
    my ( $self, $domainId, $siteId, $siteAsciiName, $siteName, $sitePtr ) = @_;

    my $domainNode = $self->{domainNodes}->{$domainId};
    if ( !defined($domainNode) ) {
      Logging::warning('Unable to dump domain site, because of dump XML structure is not full', 'PackerStructure');
      return;
    }

    my $siteNode = $self->makeDomainNode($siteId, $siteName, $sitePtr, 1, 1);
    my $phostingNode = $domainNode->getChild( 'phosting', 1 );
    $phostingNode->getChild( 'sites', 1 )->addChild($siteNode);
    $self->regSiteObjectBackupPath( $self->getDomainsBackupPath( $domainId ), $siteId, $siteAsciiName );
}

sub addAdminUser {
  my ( $self, $userName, $userPtr ) = @_;

  my $adminNode = $self->{admin};
  if ( !defined($adminNode) ) {
    Logging::warning('Unable to dump admin user, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $userNode = $self->makeUserNode($userName, $userPtr);
  $adminNode->getChild( 'users', 1 )->addChild($userNode) if defined $userNode;
}

sub removeAdminUser {
  my ( $self, $userName ) = @_;

  my $adminNode = $self->{admin};
  my $usersNode = $adminNode->getChild( 'users' );

  if ( defined $usersNode ) {
    $usersNode->removeChildByAttribute( 'user', 'name', $userName );
  }
}

sub addAdminPermission {
  my ( $self, $name, $value ) = @_;

  my $adminNode = $self->{admin};
  if ( !defined($adminNode) ) {
    Logging::warning('Unable to dump admin permissions, because of dump XML structure is not full', 'PackerStructure');
    return;
  }
  $self->makePermissionNode( $adminNode->getChild( 'limits-and-permissions', 1 ), $name, $value );
}

sub addClientUser {
    my ( $self, $ownerId, $userName, $userPtr ) = @_;

    my $clientNode = $self->getCashedClientNode($ownerId);
    return unless defined $clientNode;

    my $userNode = $self->makeUserNode($userName, $userPtr);
    $clientNode->getChild( 'users', 1 )->addChild($userNode) if defined $userNode;
}

sub removeClientUser {
  my ( $self, $ownerId, $userName ) = @_;

  my $clientNode = $self->getCashedClientNode($ownerId);
  my $usersNode = $clientNode->getChild( 'users' );

  if ( defined $usersNode ) {
    $usersNode->removeChildByAttribute( 'user', 'name', $userName );
  }
}

sub addRootUser {
  my ( $self, $userName, $userPtr ) = @_;

  if (!exists $_rootUsers{$userName}) {
    my $userNode = $self->makeUserNode($userName, $userPtr);
    $_rootUsers{$userName} = $userNode if defined $userNode;
  }
}

sub removeRootUser {
  my ( $self, $userName ) = @_;
  if ( exists $_rootUsers{$userName} ) {
    delete $_rootUsers{$userName};
  }
}

sub addRootUsers {
    my ( $self ) = @_;

    my $rootNode = $self->{root};
    if ( !defined($rootNode) ) {
      Logging::warning('Unable to dump root users, because of dump XML structure is not full', 'PackerStructure');
      return;
    }

    if (%_rootUsers) {
      foreach my $userName (keys %_rootUsers) {
        $rootNode->addChild($_rootUsers{$userName});
      }
    }
}

sub addRoleToRoles {
    my ( $self, $roleNode, $rolesNode ) = @_;

    my @roles  = $rolesNode->getChildren( 'role' );
    foreach my $role ( @roles ) {
      return if $role->getAttribute('name') eq $roleNode->getAttribute('name');
    }
    $rolesNode->addChild( $roleNode );
}

sub addAdminRole {
    my ( $self, $roleName, $isBuiltIn, $permsPtr, $servicePermissions ) = @_;

    my $adminNode = $self->{admin};
    if ( !defined($adminNode) ) {
      Logging::warning('Unable to dump admin role, because of dump XML structure is not full', 'PackerStructure');
      return;
    }

    my $roleNode = $self->makeRoleNode($roleName, $isBuiltIn, $permsPtr, $servicePermissions);
    $self->addRoleToRoles( $roleNode, $adminNode->getChild( 'roles', 1 ) );
}

sub removeAdminRole {
  my ( $self, $roleName ) = @_;

  my $adminNode = $self->{admin};
  my $rolesNode = $adminNode->getChild( 'roles' );

  if ( defined $rolesNode ) {
    $rolesNode->removeChildByAttribute( 'role', 'name', $roleName );
  }
}

sub addClientRole {
  my ( $self, $ownerId, $roleName, $isBuiltIn, $permsPtr, $servicePermissions ) = @_;

  my $clientNode = $self->getCashedClientNode($ownerId);
  return unless defined $clientNode;

  my $roleNode = $self->makeRoleNode($roleName, $isBuiltIn, $permsPtr, $servicePermissions);
  $self->addRoleToRoles( $roleNode, $clientNode->getChild( 'roles', 1 ) );
}

sub removeClientRole {
  my ( $self, $ownerId, $roleName ) = @_;

  my $clientNode = $self->getCashedClientNode($ownerId);
  my $rolesNode = $clientNode->getChild( 'roles' );

  if ( defined $rolesNode ) {
    $rolesNode->removeChildByAttribute( 'role', 'name', $roleName );
  }
}

sub addRootRole {
  my ( $self, $roleName, $isBuiltIn, $permsPtr, $servicePermissions ) = @_;

  if (!exists $_rootRoles{$roleName}) {
      my $roleNode = $self->makeRoleNode($roleName, $isBuiltIn, $permsPtr, $servicePermissions);
      $_rootRoles{$roleName} = $roleNode if defined $roleNode;
    }
}

sub removeRootRole {
  my ( $self, $roleName ) = @_;
  if ( exists $_rootRoles{$roleName} ) {
    delete $_rootRoles{$roleName};
  }
}

sub addRootRoles {
  my ( $self ) = @_;

  my $rootNode = $self->{root};
  if ( !defined($rootNode) ) {
    Logging::warning('Unable to dump root roles, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  if (%_rootRoles) {
    foreach my $roleName (keys %_rootRoles) {
      $self->addRoleToRoles( $_rootRoles{$roleName}, $rootNode );
    }
  }
}

sub getCashedClientNode {
  my ($self, $clientId) = @_;
  my $root = $self->{resellersNodes}->{$clientId};
  unless ( defined($root) ) {
    $root = $self->{clientNodes}->{$clientId};
  }
  return $root if defined($root);

  my $caller = (caller(1))[3];
  if (defined $caller) {
    Logging::warning("Error in $caller : empty client parent node for id ($clientId)", 'PackerStructure');
  }
  else {
    Logging::warning('Unable to dump client settings, because of dump XML structure is not full', 'PackerStructure');
  }
  return;
}

my %_requiredLimits = (
  domain => ['max_subdom', 'max_dom_aliases', 'disk_space', 'max_traffic', 'max_wu', 'max_db',
             'max_box', 'mbox_quota', 'max_maillists', 'expiration', 'max_site'],
  client => ['max_dom', 'max_subdom', 'max_dom_aliases', 'disk_space', 'max_traffic', 'max_wu',
             'max_db', 'max_box', 'mbox_quota', 'max_maillists', 'expiration'],
  reseller => ['max_cl', 'max_dom', 'max_subdom', 'max_dom_aliases', 'disk_space', 'max_traffic', 'max_wu',
               'max_db', 'max_box', 'mbox_quota', 'max_maillists', 'expiration']
);

sub fixDefaultLimits {
  my ($self, $type, $id) = @_;
  my $node;
  my $requiredLimits;

  if ( ($type eq 'client') || ($type eq 'reseller') ) {
    $node = $self->getCashedClientNode($id);
  }
  elsif ($type eq 'domain') {
    $node = $self->getCashedDomainNode($id);
  }
  return unless defined $node;

  $requiredLimits = $_requiredLimits{$type};

  my %usedLimits;
  XPath::Select $node, 'limits-and-permissions/limit', sub {
    $usedLimits{ shift->getAttribute( 'name' ) } = 1;
  };

  for my $name ( @{$requiredLimits} ) {
    if ( !exists( $usedLimits{$name} ) ) {
      my $value = ( $name eq 'max_site' ) ? '1' : '-1';
      $self->insertLimitNode( $node, $name, $value );
    }
  }
}

my %smbUserPinfo = (
  'phone'         => 'phone',
  'phoneType'     => 'phone-type',
  'phone2'        => 'phone2',
  'phone2Type'    => 'phone2-type',
  'phone3'        => 'phone3',
  'phone3Type'    => 'phone3-type',
  'imNumber'      => 'im',
  'imType'        => 'im-type',
  'additionalInfo' =>  'comment',
  'email'         =>  'email',
  'companyName'   =>  'company',
  'fax'           =>  'fax',
  'address'       =>  'address',
  'city'          =>  'city',
  'state'         =>  'state',
  'zip'           =>  'zip',
  'country'       =>  'country'
);

sub makeUserNode {
  my ( $self, $userName, $userPtr ) = @_;

  my %user = %{$userPtr};

  my $userNode = XmlNode->new( 'user' );

  $userNode->setAttribute( 'name',        $userName );
  if (defined $user{'contactName'}) {
    $userNode->setAttribute( 'contact',     $user{'contactName'} );
  }else {
    $userNode->setAttribute( 'contact',     $userName );
  }
  $userNode->setAttribute( 'guid',        $user{'uuid'}) if defined $user{'uuid'};
  $userNode->setAttribute( 'email',       $user{'email'} ) if defined $user{'email'};
  $userNode->setAttribute( 'is-built-in', $user{'isBuiltIn'} ? 'true' : 'false' ) if exists $user{'isBuiltIn'};
  $userNode->setAttribute( 'cr-date',     $user{'creationDate'} ) if exists $user{'creationDate'};
  $userNode->setAttribute( 'external-id', $user{'externalId'} ) if defined $user{'externalId'};

  if (defined $user{'subscriptionDomainId'} and $user{'subscriptionDomainId'} != 0) {
    my $subscriptionName = PleskStructure::getDomainNameFromId($user{'subscriptionDomainId'});
    if ($subscriptionName) {
      $userNode->setAttribute( 'subscription-name', $subscriptionName );
    }else{
      $userNode->setAttribute( 'subscription-name', 'removed');
    }
  }

  $userNode->setAttribute( 'is-domain-admin', (defined $user{'isDomainAdmin'} and ( $user{'isDomainAdmin'} eq '1' ) )? 'true' : 'false' );
  $userNode->setAttribute( 'is-legacy-user', (defined $user{'isLegacyUser'} and ( $user{'isLegacyUser'} eq '1' ) ) ? 'true' : 'false' );
  $userNode->setAttribute( 'file-sharing-id', $user{'fileSharingId'} ) if defined $user{'fileSharingId'};

  $userNode->setAttribute( 'external-email', 'true' ) if not defined $user{'mailId'};

  my $propsNode = XmlNode->new('properties');
  my $passwordNode = CommonPacker::makePasswordNode($userPtr->{'password'}, (defined $userPtr->{'passwordType'})? $userPtr->{'passwordType'} : 'plain', (defined $userPtr->{'isBase64'} and $userPtr->{'isBase64'} eq '0') ? undef : 'base64');
  $propsNode->addChild($passwordNode);

  my $status = $userPtr->{'isLocked'} ? $Status::ADMIN : $Status::ENABLED;
  $propsNode->addChild(Status::make($status));

  $userNode->addChild($propsNode);

  my $lpNode = $userNode->getChild('limits-and-permissions', 1);
  $lpNode->addChild(XmlNode->new('role-ref', 'content' => $userPtr->{'SmbRoleName'}));

  foreach my $app (@{$user{'assignedApplications'}}) {
    $lpNode->addChild(XmlNode->new('assigned-application', 'content' => $app));
  }

  my $prefsNode = XmlNode->new('preferences');

  my @usersPhones;
  foreach my $pinfo (keys %user) {
    next unless defined $smbUserPinfo{$pinfo};
    if ( defined $user{$pinfo} and $user{$pinfo} ne '' ) {
      next if ( $smbUserPinfo{$pinfo} =~ /phone(\d?)-type/);
      if ($smbUserPinfo{$pinfo} =~ /^phone(\d?)$/) {
        push @usersPhones,  $user{$pinfo};
        next;
      }
      $prefsNode->addChild(XmlNode->new('pinfo',
                                        'attributes' => {'name' => $smbUserPinfo{$pinfo} },
                                        'content' => $user{$pinfo}
                                        )
                          );
    }
  }
  if (@usersPhones) {
    $prefsNode->addChild( XmlNode->new('pinfo',
                       'attributes' => { 'name' => 'phone' },
                       'content' => join(',', @usersPhones)
              )
    );
  }

  $userNode->addChild($prefsNode);

  return $userNode;
}

sub makeRoleNode {
  my ( $self, $roleName, $isBuiltIn, $permsPtr, $servicePermissions ) = @_;

  my $roleNode = XmlNode->new( 'role' );
  $roleNode->setAttribute( 'name', $roleName );
  $roleNode->setAttribute( 'is-built-in', $isBuiltIn? 'true' : 'false' );

  my $limitsAndPermsNode = XmlNode->new( 'limits-and-permissions' );
  $roleNode->addChild($limitsAndPermsNode);

  while ( my ( $name, $value ) = each( %{$permsPtr} ) ) {
    my $permNode = XmlNode->new( 'permission', 'attributes' => {
                                                'name'  => $name,
                                                'value' => $value? 'true' : 'false' }
                               );
    $limitsAndPermsNode->addChild( $permNode );
  }

  for my $perm (@$servicePermissions) {
    if ($perm->{classname} == "Smb_Service_Provider_Aps") {
      my $permNode = XmlNode->new('service-permission', 'attributes' => {
        'classname' => $perm->{'classname'},
        'description' => $perm->{'description'},
        'externalId' => $perm->{'externalId'},
        'permissionCode' => $perm->{'permissionCode'},
        'class' => $perm->{'class'},
        'value' => 'true',
      });
      $limitsAndPermsNode->addChild( $permNode );
    }
  }

  return $roleNode;
}

sub makeDomainNode {
    my ( $self, $domainId, $domainName, $domainPtr, $issite, $needFullInfo ) = @_;

    unless ( ref($domainPtr) =~ /HASH/ ) {
      Logging::warning("Unable to backup domain $domainName");
      return;
    }

    my %domain = %{$domainPtr};

    my $nodeName = ($issite? 'site' : 'domain');

    my $root = XmlNode->new( $nodeName, 'attributes' => {
        'name' => $domainName,
        'id'   => $domainId }
     );

    $root->getChild( 'preferences', 1 );
    $root->getChild( 'properties', 1 );
    $root->getChild( 'limits-and-permissions', 1 ) unless $issite;

    $root->setAttribute( 'cr-date', $domain{'cr_date'} ) if exists $domain{'cr_date'};
    $root->setAttribute( 'guid', $domain{'guid'} ) if defined $domain{'guid'};

    $root->setAttribute( 'external-id', $domain{'external_id'} ) if ( defined $domain{'external_id'} and $domain{'external_id'} ne '');

    $root->setAttribute( 'vendor-guid', $domain{'vendor-guid'} ) if ( not $issite and defined $domain{'vendor-guid'} );

    $root->setAttribute( 'owner-guid', $domain{'owner-guid'} ) if ( not $issite and defined $domain{'owner-guid'} );

    $root->setAttribute( 'owner-name', $domain{'owner-name'} ) if ( not $issite and defined $domain{'owner-name'} );

    $root->setAttribute( 'parent-domain-name', $domain{'parentDomainName'} ) if ( $issite and defined $domain{'parentDomainName'} );

    $root->setAttribute( 'vendor-login', $domain{'vendor-login'} ) if ( not $issite and defined $domain{'vendor-login'} );

    if($issite) {
      $self->{siteNodes}->{$domainId} = $root;
    }
    else {
      $self->{domainNodes}->{$domainId} = $root;
    }

    if ($needFullInfo) {
      return $root;
    } else {
      my $shortInfoNode = XmlNode->new('domain-info');
      $shortInfoNode->setAttribute('name', $domainName);
      $shortInfoNode->setAttribute('guid', $domain{'guid'}) if defined $domain{'guid'};
      unless($issite) {
        $self->{domainShortNodes}->{$domainId} = $shortInfoNode;
      }
      return $shortInfoNode;
    }
}

sub getCashedDomainNode {
  my ($self, $domainId) = @_;
  my $root = $self->{domainNodes}->{$domainId};
  unless ( defined($root) ) {
    $root = $self->{siteNodes}->{$domainId};
  }
  return $root if defined($root);

  my $caller = (caller(1))[3];
  if (defined $caller) {
    Logging::warning("Error in $caller : empty domain parent node for id ($domainId)", 'PackerStructure');
  }
  else {
    Logging::warning('Error: empty domain parent node', 'PackerStructure');
  }
  return;
}

sub finishDomain {
  my ( $self, $domainId) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  #TODO: uncomment
  #$root->ReleaseCode();
}

sub setDomainWwwStatus {
  my ( $self, $domainId, $status ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  $root->setAttribute( 'www', $status );
}

sub setDomainIP {
  my ( $self, $domainId, $ip, $iptype ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  $root->getChild( 'properties', 1 )->addChild( $self->makeIpNode( $ip, $iptype ), 1 );
}

sub setDomainStatus {
  my ( $self, $domainId, $status ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  $root->getChild( 'properties', 1 )->addChild( Status::make($status) );
}

sub setWebspaceStatus {
  my ( $self, $domainId, $status, $siteStatus ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  if (not defined $siteStatus) {
    $root->getChild( 'properties', 1 )->addChild( Status::make($status, 'webspace-status') );
    $root->getChild( 'properties', 1 )->addChild( Status::make($status) );
  } else {
    $root->getChild( 'properties', 1 )->addChild( Status::make($status, 'webspace-status') );
    $root->getChild( 'properties', 1 )->addChild( Status::make($siteStatus) );
  }
}

sub getDomainARecordIp {
  my ( $self, $domainId ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $name = PleskStructure::getDomainNameFromId($domainId);
  my $aRecordIp;
  XPath::Select $root, 'properties/dns-zone/dnsrec', sub {
    my $rec = shift;
    if (($rec->getAttribute('type') eq 'A') or ($rec->getAttribute('type') eq 'AAAA') and ($rec->getAttribute('src') eq "$name.") ) {
      $aRecordIp = $rec->getAttribute('dst');
    }
  };
  return $aRecordIp;
}

sub addDomainDatabase {
  my ( $self, $dbId, $dbServerId, $domainId, $dbName, $dbType, $optionalPtr, $dbServerPtr, $dbUsersPtr, $contentDescriptionPtr, $skipContent ) = @_;

  $self->regDatabaseObjectBackupPath( $self->getDomainsBackupPath( $domainId ), $dbId, $dbName, $dbServerId );
  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $node = $self->makeDatabaseNode( $dbId, $dbName, $dbType, 'domain', $domainId, $optionalPtr,
                                      $dbServerPtr, $dbUsersPtr, $contentDescriptionPtr, $skipContent );
  my $parentGuid = $root->getAttribute('guid');
  $node->setAttribute( 'guid', $self->getDatabaseGuid( $parentGuid, $dbId ) ) if $parentGuid;
  $node->setAttribute( 'owner-guid', $parentGuid ) if $parentGuid;

  $root->getChild( 'databases', 1 )->addChild($node);
}

sub setDomainDefault {
  my ( $self, $domainId ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  $root->getChild( 'preferences', 1 )->addChild( XmlNode->new('default-domain') );
}

sub addDomainLimit {
  my ( $self, $domainId, $name, $value ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  $self->insertLimitNode( $root, $name, $value );
}

sub setDomainMailService {
  my ( $self, $domainId, $status, $ips ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $mailServiceStatus = Status::make($status);

  my $rootMail = XmlNode->new('mailsystem');
  my $properties = $rootMail->getChild( 'properties', 1 );
  $properties->addChild($mailServiceStatus);

  foreach my $ip (keys %{$ips}) {
    my $ipNode = $self->makeIpNode($ip, $ips->{$ip});
    $properties->addChild($ipNode);
  }

  $root->addChild($rootMail);
}

sub addDomainCustomButton {
  my ( $self, $domainId, $id, $optionsPtr, $customButtonsDir, $icon ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $node = $self->makeCustomButtonNode( 'domains', $domainId, $id, $optionsPtr, $customButtonsDir, $icon );

  $root->getChild( 'preferences', 1 )->addChild( $node);
}

sub addToPreferences {
  my ( $self, $objectType, $objectId, $node ) = @_;

  my $parent;
  if ($objectType eq 'domain') {
    $parent = $self->getCashedDomainNode($objectId);
  }
  elsif($objectType eq 'client') {
    $parent = $self->{clientNodes}->{$objectId};
  }
  if ( !defined($parent) ) {
    Logging::warning('Error: addToPreferences: empty parent node', 'PackerStructure');
    return;
  }

  $parent->getChild( 'preferences', 1 )->addChild( $node);
}

sub setDomainCatchMail {
  my ( $self, $domainId, $catchAllAddr ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $mailService = $root->getChild('mailsystem');
  unless (defined $mailService ) {
    Logging::warning("Error: setDomainCatchMail: there are no node 'mailsystem'", 'PackerStructure');
    return;
  }

  $mailService->getChild( 'preferences', 1 )->addChild(XmlNode->new( 'catch-all', 'content' => $catchAllAddr ) );
}

sub setDomainKeysDomainSupport {
  my ( $self, $domainId, $domainName, $state, $privateKeyPath, $publickKey ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $mailService = $root->getChild('mailsystem');
  unless ( defined $mailService ) {
    Logging::warning("Error: setDomainKeysDomainSupport: there are no node 'mailsystem'", 'PackerStructure');
    return;
  }

  my $domainKeysNode = XmlNode->new( 'domain-keys',
    'attributes' => { 'state' => $state } );

  if ($state) {
    my $privateKey = $self->{content_transport}->addDomainContent( 'key', $domainId,
      "privatekey",
      'directory' => $privateKeyPath,
      'include'   => ['default']
    );

    $domainKeysNode->getChild( 'content', 1, 1 )->addChild( $privateKey ) if $privateKey;

    $domainKeysNode->setAttribute( 'public-key', $publickKey ) if ( defined($publickKey) && $publickKey );
  }

  $mailService->getChild( 'preferences', 1 )->addChild($domainKeysNode);
}

sub setDomainWebMail{
  my ( $self, $domainId, $webmail, $cert ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $mailService = $root->getChild('mailsystem');
  if ( not $mailService ) {
    Logging::warning("Unable to dump domain webmail preferences, because of dump XML structure is not full", 'PackerStructure');
    return;
  }

  my $node = XmlNode->new( 'web-mail' );
  $node->setText( $webmail );
  $node->setAttribute('certificate', $cert) if defined $cert;
  $mailService->getChild( 'preferences', 1 )->addChild( $node );
}

sub setDomainGLSupport {
  my ( $self, $domainId, $state ) = @_;
  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $mailsystem = $root->getChild('mailsystem');
  my $node = XmlNode->new( 'grey-listing' );
  $node->setText($state);
  $mailsystem->getChild('preferences', 1)->addChild($node);
}

sub setDomainDnsZone {
  my ( $self, $domainId, $paramsPtr, $recordsPtr ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  $self->makeDnsZone($root->getChild( 'properties', 1 ), $paramsPtr, $recordsPtr);
}

sub removeDomainDnsZone {
  my ( $self, $domainId ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $propertiesNode = $root->getChild( 'properties');
  if ( $propertiesNode ) {
    $propertiesNode->removeChildren('dns-zone');
  }
}

sub setDomainDnsOld {
  my ( $self, $domainId, $enabled, $type, $masterIp, $recordsPtr ) = @_;

  my @records = @{$recordsPtr};

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $properties = $root->getChild( 'properties', 1 );
  if ( !defined($properties) ) {
    Logging::warning("Error: setDomainDnsOld: empty 'properties' node", 'PackerStructure');
    return;
  }

  my $dnsNode = XmlNode->new('dns-zone');

  $dnsNode->setAttribute(
    'type',
    ( !$type || ( $type eq 'master' ) ) ? 'master' : 'slave'
  );

	$dnsNode->addChild(Status::make( $enabled eq 'true' ? 0 : 16 ));

  for my $hash (@records) {
    my $dnsrec = $self->makeDnsRecord($hash);
    if ($dnsrec) {
      $dnsNode->addChild($dnsrec);
    }
    $hash = undef;
  }

  if ( defined($masterIp) ) {
    $dnsNode->addChild( makeOldMasterRec($masterIp) );
  }

  $properties->addChild($dnsNode);
}

sub addDomainAlias {
  my ( $self, $domainId, $aliasPtr, $dnsParamsPtr, $dnsRecordsPtr) = @_;

  my %alias = %{$aliasPtr};

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $dnsSync = defined $alias{'dns'} ? $alias{'dns'} : 'false';
  
  my $aliasNode = XmlNode->new(
    'domain-alias',
    'attributes' => {
      'name' => $alias{'displayName'},
      'mail' => $alias{'mail'},
      'web'  => $alias{'web'},
      'dns'  => $dnsSync
    },
    'children' => [ Status::make( $alias{'status'} ) ]
  );

  $aliasNode->setAttribute('seo-redirect', defined $alias{'seoRedirect'} ? $alias{'seoRedirect'} : 'false');

  $aliasNode->setAttribute('external-id', $alias{'external_id'}) if defined $alias{'external_id'} and $alias{'external_id'} ne '';

  # DNS zone

  if ( $alias{'dns_zone_id'} != 0 ) {
    $self->makeDnsZone( $aliasNode, $dnsParamsPtr, $dnsRecordsPtr, $dnsSync );
  }

  $root->getChild( 'preferences', 1 )->addChild($aliasNode);
}

sub setDomainMailLists {
  my ( $self, $domainId, $status, $ips) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $mlNode = XmlNode->new('maillists');

  my $propertiesNode = $mlNode->getChild( 'properties', 1 );
  $propertiesNode->addChild( Status::make( $status ) );
  foreach my $ip (keys %{$ips}) {
    my $ipNode = $self->makeIpNode($ip, $ips->{$ip});
    $propertiesNode->addChild($ipNode);
  }

  $root->addChild($mlNode);
}

sub addDomainMailList {
  my ($self, $domainId, $name, $passwd, $state, $ownersPtr, $membersPtr) = @_;

  my @owners = @{$ownersPtr};
  my %members = %{$membersPtr};

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my @mailListServs = $root->getChildren('maillists');
  if ( scalar(@mailListServs) < 1 ) {
    Logging::warning('Unable to dump domain maillist, because of dump XML structure is not full', 'PackerStructure');
    return;
  }
  my $mailListService = $mailListServs[0];

  my $node = XmlNode->new(
    'maillist',
    'attributes' => { 'name', $name },
    'children'   => [ Status::make($state) ]
  );

  for my $listOwner (@owners) {
    $node->addChild( XmlNode->new( 'owner', 'content' => $listOwner ) );
  }

  $node->addChild( CommonPacker::makePasswordNode( $passwd, 'encrypted') );

  for my $memberEmail ( keys %members ) {
    my $member = XmlNode->new( 'recipient', 'content' => $memberEmail );
    if ( $members{$memberEmail} ne "" ) {
      $member->setAttribute( 'fullname', $members{$memberEmail} );
    }
    $node->addChild($member);
  }

  $mailListService->addChild($node);
}

sub setDomainTraffic {
  my ( $self, $domainId, $trafficValue) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  $root->addChild( XmlNode->new( 'traffic', 'content' => $trafficValue ) );
}

sub addDomainCertificate {
  my ( $self, $domainId, $name, $cert, $csr, $ca_cert, $pvt_key, $default) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $certNode = XmlNode->new('certificate');

  addUrlDecodedTextNode( $certNode, 'certificate-data', $cert ) if defined($cert);
  addUrlDecodedTextNode( $certNode, 'signing-request',  $csr ) if defined($csr);
  addUrlDecodedTextNode( $certNode, 'ca-certificate',   $ca_cert ) if defined($ca_cert);
  addUrlDecodedTextNode( $certNode, 'private-key', $pvt_key ) if defined($pvt_key);
  $certNode->setAttribute( 'name', $name );
  $certNode->setAttribute( 'default', 'true' ) if ( defined $default && $default == 1 );

  $root->getChild('certificates', 1 )->addChild($certNode);
}

sub addDomainPersonalPermission {
  my ( $self, $domainId, $name, $value ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  if ( !defined($root) ) {
    Logging::warning('Unable to dump domain personal permissions, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  $self->makePermissionNode( $root->getChild( 'limits-and-permissions', 1 ), $name, $value );
}

sub setDomainPhostingEmpty {
  my ( $self, $domainId, $sysuserPtr, $ips ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $phostingNode = XmlNode->new('phosting');

  my $preferencesNode = XmlNode->new('preferences');
  $phostingNode->addChild( $preferencesNode );

  if (defined $sysuserPtr) {
    $preferencesNode->addChild($self->makeSysUser($sysuserPtr));
  }
  
  my $propertiesNode = $phostingNode->getChild( 'properties', 1 );
  foreach my $ip (keys %{$ips}) {
    my $ipNode = $self->makeIpNode($ip, $ips->{$ip});
    $propertiesNode->addChild($ipNode);
  }

  $root->addChild($phostingNode);

  $self->regPhostingObjectBackupPath( $self->getDomainsBackupPath( $domainId ), $domainId );
  $self->{phostingNodes}->{$domainId} = $phostingNode;
}

sub setDomainPhosting {
  my ( $self, $domainId, $paramsPtr, $sysuserPtr, $scriptingPtr, $ips, $phpSettingsPtr ) = @_;

  my %params = %{$paramsPtr};
  my %scripting = %{$scriptingPtr};

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $phostingNode = XmlNode->new('phosting');

  my $parentGuid = $root->getAttribute('guid');
  if ( $parentGuid ) {
    $phostingNode->setAttribute( 'guid', $self->getPhostingGuid( $parentGuid , $domainId ) );
    $phostingNode->setAttribute( 'owner-guid', $parentGuid );
  }

  ## %params ##
  my %hostingAttribute = (
    'https'   => 'ssl',
    'webstat' => 'webstat',
  );
  $phostingNode->setAttribute( 'shared-content', $params{'shared-content'} ) if defined($params{'shared-content'});
  while ( my ( $xmlName, $fieldName ) = each(%hostingAttribute) ) {
    # MySQL allows 'true', 'false' and '' in enums
    if ( defined( $params{$fieldName} )
      and $params{$fieldName} eq "true" )
    {
      $phostingNode->setAttribute( $xmlName, 'true' );
    }
  }

  if ( defined( $params{'webstat'} ) and $params{'webstat'} ) {
    $phostingNode->setAttribute( 'webstat', $params{'webstat'} );
  }

  if ( defined( $params{'https'} ) and $params{'https'} ) {
    $phostingNode->setAttribute( 'https', $params{'https'} );
  }

  if ( defined( $params{'sslRedirect'} ) and $params{'sslRedirect'} ) {
    $phostingNode->setAttribute( 'sslRedirect', $params{'sslRedirect'} );
  }

  if ( defined($params{'errdocs'} ) ) {
    $phostingNode->setAttribute( 'errdocs', 'true' );
  }

  if ( defined( $params{'www-root'} ) ) {
    $phostingNode->setAttribute( 'www-root', $params{'www-root'} );
  }

  if ( defined( $params{'cgi_bin_mode'} ) ) {
    $phostingNode->setAttribute( 'cgi_bin_mode', $params{'cgi_bin_mode'} );
  }

  if ( defined( $params{'sitebuilder-site-id'} ) ) {
    $phostingNode->setAttribute( 'sitebuilder-site-id', $params{'sitebuilder-site-id'} );
  }

  if ( defined( $params{'sitebuilder-site-published'} ) ) {
    $phostingNode->setAttribute( 'sitebuilder-site-published', $params{'sitebuilder-site-published'} );
  }

  if ( defined( $params{'wu_script'} ) ) {
    $phostingNode->setAttribute( 'wu_script', $params{'wu_script'} );
  }

  if ( defined( $params{'certificate_ref'} ) ) {
    $phostingNode->setAttribute( 'certificate', $params{'certificate_ref'} );
  }

  if ( defined( $params{'original-conf-directory'} ) ) {
    $phostingNode->setAttribute( 'original-conf-directory', $params{'original-conf-directory'} );
  }

  ## %sysuser ##
  my $prefs = $phostingNode->getChild( 'preferences', 1 );
  if (defined $sysuserPtr) {
    my $sysUserNode = $self->makeSysUser($sysuserPtr);
    $prefs->addChild($sysUserNode);
  }

  ## properties ##
  my $propertiesNode = $phostingNode->getChild( 'properties', 1);
  foreach my $ip (keys %{$ips}) {
    my $ipNode = $self->makeIpNode($ip, $ips->{$ip});
    $propertiesNode->addChild($ipNode);
  }

  ## %scripting ##
  my $scripringItem = XmlNode->new('scripting');
  while ( my ( $scriptXmlName, $value ) = each(%scripting) ) {
    $scripringItem->setAttribute( $scriptXmlName, $value ) if defined $value;
  }
  $phostingNode->getChild( 'limits-and-permissions', 1 )->addChild($scripringItem);

  if (defined $phpSettingsPtr and (scalar keys %{$phpSettingsPtr} != 0)) {
    $phostingNode->addChild($self->makePhpSettingsNode($phpSettingsPtr));
  }

  $root->addChild($phostingNode);

  $self->regPhostingObjectBackupPath( $self->getDomainsBackupPath( $domainId ), $domainId );
  $self->{phostingNodes}->{$domainId} = $phostingNode;

}

sub setDomainUserDataContent {
  my ( $self, $domainId, $domainName, $path, $optPtr) = @_;

  if (!$self->getContentTransport()->directoryExists("$path")) {
    Logging::debug("Domain $domainName. Skip backup of non-existent directory $path");
    return;
  }

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;
  
  my $phostings = $root->getChild('phosting');
  
  my ($fhApache, $tmpApache) = File::Temp::tempfile( AgentConfig::getBackupTmpDir() . "/pmm-dudc-incApacheFiles-XXXXXX", UNLINK => 1 );
  my ($fhUser, $tmpUserData) = File::Temp::tempfile( AgentConfig::getBackupTmpDir() . "/pmm-dudc-incUserData-XXXXXX", UNLINK => 1 );
  
  chown scalar(getpwnam('root')), scalar(getgrnam('root')), $tmpApache, $tmpUserData;
  chmod 0600, $tmpApache, $tmpUserData;
  
  my %excludes = map { $_ => 1 } @{$optPtr->{'exclude'}};
  Logging::debug("List files in " . $path);
  $self->_listApacheAndUserFilesInFolder($path, $fhUser, $fhApache, $optPtr->{'include'}, \%excludes);

  close $fhUser;
  close $fhApache;

  my $userDataSize = $self->_setDomainContentIncrementally($domainId, $path, $optPtr, $tmpUserData, $phostings, 'user-data', $optPtr->{'sysuser'});
  if (defined($userDataSize)) {
    my $apacheSize = $self->_setDomainContentIncrementally($domainId, $path, $optPtr, $tmpApache, $phostings, 'apache-files', $self->{apache_user}{'user'});
    if (defined($apacheSize)) {
      $self->{stat}{vhostDumpsCount}++;
      $self->{stat}{vhostSizeDumped} += $userDataSize + $apacheSize;
      return;
    }
  }

  my @apacheFiles;
  my $cmd = "find $path \\( \\( -type d -user $self->{apache_user}{user} -group $self->{apache_user}{group} ! -perm -o+rw -prune \\) -o \\( ! -type d -user $self->{apache_user}{user} -group $self->{apache_user}{group} ! -perm -o+rw \\) \\) -printf \"%P\n\"";
  my $findCmd = `$cmd`;
  if ($? eq '0') {
    @apacheFiles = split "\n", $findCmd;
  }

  my @exclude = (@apacheFiles) ? (@apacheFiles, @{$optPtr->{'exclude'}}) : @{$optPtr->{'exclude'}};
  my $cidUserData = $self->{content_transport}->addDomainContent( 'user-data', $domainId,
    'user-data',
    'directory' => $path,
    'sysuser' => $optPtr->{'sysuser'},
    'exclude' => \@exclude
  );
  $phostings->getChild( 'content', 1, 1 )->addChild( $cidUserData ) if $cidUserData;
  @{$self->{domainVhost}->{$domainId}} = ( 'user-data', $cidUserData, $path )  if $cidUserData;

  my $cidApache;
  if (@apacheFiles) {
    $cidApache = $self->{content_transport}->addDomainContent( 'apache-files', $domainId,
      'apache-files',
      'directory' => $path,
      'sysuser' => $self->{apache_user}{'user'},
      'include' => \@apacheFiles
    );
    $phostings->getChild( 'content', 1, 1 )->addChild( $cidApache ) if $cidApache;
    @{$self->{domainVhost}->{$domainId}} = ( 'apache-files', $cidApache, $path )  if $cidApache;
  }
  
  $self->{stat}{vhostDumpsCount}++;
  $self->{stat}{vhostSizeDumped} +=  $self->_getContentSizeFromCidNode($cidUserData) + $self->_getContentSizeFromCidNode($cidApache);
}

sub _setDomainContentIncrementally {
  my ($self, $domainId, $srcDirPath, $optPtr, $listingFile, $contentOwner, $cidType, $sysuser, $refferedContentType) = @_;

  $refferedContentType = 'domainVhost' unless defined $refferedContentType;
  my $cidSize = undef;
  
  my $changesFile = HelpFuncs::mktemp(AgentConfig::getBackupTmpDir() . "/pmm-dci-chn-XXXXXXXX");
  my $indexFile = HelpFuncs::mktemp(AgentConfig::getBackupTmpDir() . "/pmm-dci-idx-XXXXXXXX");
  my $dependenciesFile = HelpFuncs::mktemp(AgentConfig::getBackupTmpDir() . "/pmm-dci-dep-XXXXXXXX");

  my $oldMask = umask(0077);
  for my $outFile ( ($changesFile, $indexFile, $dependenciesFile) ) {
    open(my $outFileHandle, '>', $outFile);
    close($outFileHandle);
  }
  umask($oldMask);
  
  my $cmd = [
    AgentConfig::pmmRasBin(), '--get-diff',
    '--listing-file', $listingFile,
    '--backup-file', $self->{storage}->{output_dir}."/".$self->getDomainsBackupPath($domainId, $cidType).".tgz",
    '--changes-file', $changesFile,
    '--index-file', $indexFile,
    '--dependencies-file', $dependenciesFile
  ];
  
  my $cidPostfix = '';
  if ($cidType eq 'domainmail') {
    push (@{$cmd}, '--id');
    push (@{$cmd}, $domainId);
    $cidPostfix = '_' . $domainId;
  }
  
  if ($self->{lastIndexPath}) {
    push (@{$cmd}, '--last-index-path');
    push (@{$cmd}, $self->{lastIndexPath});
  }

  if ($self->{lastIncrementFile}) {
    push (@{$cmd}, '--last-increment-file');
    push (@{$cmd}, $self->{lastIncrementFile});
  }

  if ($self->{excludePatternsFile}) {
    push (@{$cmd}, '--exclude-patterns-file');
    push (@{$cmd}, $self->{excludePatternsFile});
  }

  if (defined($self->{storage}->{ftpsettings})) {
    push (@{$cmd}, '--use-ftp-passive-mode') if $self->{storage}->{passive_mode};
    $ENV{'DUMP_STORAGE_PASSWD'} = $self->{storage}->{ftpsettings}->{password};
  }

  if (Logging::getVerbosity() >= Logging::LOG_LEVEL_DEBUG) {
    push (@{$cmd}, '--debug');
  }

  if (Logging::getVerbosity() >= Logging::LOG_LEVEL_TRACE) {
    push (@{$cmd}, '--verbose');
  }

  Logging::debug("Execute: ".join(' ', @{$cmd}));

  my($err);
  run $cmd, '>&', \$err;

  if ($?) {
    Logging::debug("Unable to calculate file diff, full dump will be performed instead. Error (".$?.") ".$err, "IncrementalBackup");
  } else {
    my $dumpXmlRelativePath = $self->getDomainsBackupPath($domainId, 'info', 0);
    my $posNameBegin = rindex( $dumpXmlRelativePath, '/' ) + 1;
    my $dumpName = substr($dumpXmlRelativePath, $posNameBegin);
    my $dumpDirPath = ($posNameBegin > 0) ? substr($dumpXmlRelativePath, 0, $posNameBegin - 1) : "";
    my $indexDirPath = $dumpDirPath;
    if (!$self->{domainNodes}->{$domainId}) {
      my $posSitesBegin = rindex($indexDirPath, '/sites/');
      if ($posSitesBegin > 0) {
        $indexDirPath = substr($indexDirPath, 0, $posSitesBegin);
      }
    }

    my $cid_content = $self->{content_transport}->addDomainContent( $cidType, $domainId,
      $cidType,
      'directory' => $srcDirPath,
      'sysuser' => $sysuser,
      'includes-file' => $changesFile,
      'exclude' => \@{$optPtr->{'exclude'}},
      'no_recursion' => 1,
      'indexed' => 1
    );
    if ($cid_content) {
      $self->{storage}->moveFileToDiscovered($indexFile, "cid_".$cidType.$cidPostfix, $indexDirPath, $dumpName);
      $contentOwner->getChild('content', 1, 1)->addChild($cid_content);
      @{$self->{$refferedContentType}->{$domainId}} = ($cidType, $cid_content, $srcDirPath);
      $cidSize = $self->_getContentSizeFromCidNode($cid_content);
    }

    $self->_keepRelatedDumps($dumpDirPath, $dependenciesFile);
  }

  unlink($changesFile) if -e $changesFile;
  unlink($indexFile) if -e $indexFile;
  unlink($dependenciesFile) if -e $dependenciesFile;

  return $cidSize;
}

sub _getContentSizeFromCidNode {
  my ($self, $cidNode) = @_;
  
  my $size = 0;
  if ($cidNode) {
    foreach my $contentFileNode ( $cidNode->getChildren('content-file') ) {
      if ($contentFileNode) {
        $size += $contentFileNode->getAttribute('size');
      }
    }
  }
  
  return $size;
}

sub _keepRelatedDumps {
  my ($self, $pathToDumpFolder, $relatedDumpsFile) = @_;

  my @relatedDumps = HelpFuncs::loadArrayFromFile($relatedDumpsFile);
  return unless (@relatedDumps);
  
  @relatedDumps = sort(@relatedDumps);  
  $self->_keepRelatedDumpsTo($pathToDumpFolder, \@relatedDumps);
}

sub _keepRelatedDumpsTo {
  my ($self, $pathToDumpFolder, $relatedDumps) = @_;

  if (exists $self->{relatedDumps}->{$pathToDumpFolder}) {
    my @diff = HelpFuncs::arrayDifference($relatedDumps, $self->{relatedDumps}->{$pathToDumpFolder});
    my @merged = (@{$self->{relatedDumps}->{$pathToDumpFolder}}, @diff);
    @{$self->{relatedDumps}->{$pathToDumpFolder}} = sort(@merged);
  } else {
    @{$self->{relatedDumps}->{$pathToDumpFolder}} = @{$relatedDumps};
  }

  return if ($pathToDumpFolder eq '');

  my $pathToParentDumpFolder = dirname(dirname($pathToDumpFolder));
  $pathToParentDumpFolder = "" if ($pathToParentDumpFolder eq '.');

  $self->_keepRelatedDumpsTo($pathToParentDumpFolder, $relatedDumps);
}

sub _listApacheAndUserFilesInFolder {
  my ($self, $srcDirPath, $outUserFiles, $outApacheFiles, $includes, $excludes, $isApacheBranch, $szPathTrim, $usersCache, $groupsCache, $orderNumber) = @_;

  return if !opendir(my $dh, $srcDirPath);
  
  $szPathTrim = length($srcDirPath) + 1 if (not defined $szPathTrim or $szPathTrim == 0);
  $$orderNumber = 0 if not defined($orderNumber);

  my @entriesInfo;
  while (my $entry = readdir($dh)) {
    next if ($entry eq "." || $entry eq "..");
    my $entryFullPath = $srcDirPath."/".$entry;
    my $entryRelPath = substr $entryFullPath, $szPathTrim;

    next unless $self->_isEntryIncluded($entryRelPath, $includes, $excludes);

    my ($dev, $ino, $mode, $nlink, $uid, $gid, $rdev, $size, $atime, $mtime, $ctime, $blksize, $blocks) = lstat($entryFullPath) or next;
    push @entriesInfo, join("\t", HelpFuncs::encodePath($entry), ++$$orderNumber, $mode, $uid, $gid, $size, $mtime, $ctime);
  }
  closedir($dh);

  @entriesInfo = sort(@entriesInfo);

  foreach my $entryInfo (@entriesInfo) {
    my ($entry, $entryOrderNumber, $mode, $uid, $gid, $size, $mtime, $ctime) = split("\t", $entryInfo);
    my $entryFullPath = $srcDirPath . "/" . HelpFuncs::decodePath($entry);
    my $entryRelPath = substr $entryFullPath, $szPathTrim;
    
    my $addToApacheListing = $outApacheFiles && ($isApacheBranch || ($uid == $self->{apache_user}{uid} && $gid == $self->{apache_user}{gid} && !($mode & Fcntl::S_IROTH && $mode & Fcntl::S_IWOTH)));
    my $ftype = (Fcntl::S_ISDIR($mode) && !Fcntl::S_ISLNK($mode)) ? 'd' : 'f';
    my $fsize = ($ftype eq 'd') ? "0" : "$size";
    $mode = sprintf '%04o', $mode & 07777;
    $mtime = HelpFuncs::Max($mtime, $ctime);

    $usersCache->{$uid} = getpwuid($uid) if (not exists $usersCache->{$uid});
    $groupsCache->{$gid} = getgrgid($gid) if (not exists $groupsCache->{$gid});

    my $outFile = ($addToApacheListing) ? $outApacheFiles : $outUserFiles;
    print $outFile join("\t", HelpFuncs::encodePath($entryRelPath), $ftype, $fsize, $mtime, $entryOrderNumber, $usersCache->{$uid}, $groupsCache->{$gid}, $mode) . "\n";

    if ($ftype eq 'd') {
      $self->_listApacheAndUserFilesInFolder($entryFullPath, $outUserFiles, $outApacheFiles, $includes, $excludes, $addToApacheListing, $szPathTrim, $usersCache, $groupsCache, $orderNumber);
    }
  }
}

sub _isEntryIncluded {
  my ($self, $entryRelPath, $includes, $excludes) = @_;

  my $isEntryIncluded = 0;
  foreach my $include (@{$includes}) {
    if (length($include) > length($entryRelPath)) {
      $isEntryIncluded = index($include, $entryRelPath) == 0 ? 1 : 0;
    } else {
      $isEntryIncluded = index($entryRelPath, $include) == 0 ? 1 : 0;
    }
    last if $isEntryIncluded;
  }
  return 0 unless $isEntryIncluded;

  return exists($excludes->{$entryRelPath}) ? 0 : 1;
}

sub setDomainPhostingFullContent {
  my ( $self, $domainId, $domainName, $path, $optPtr) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $phostings = $root->getChild('phosting');
  my $cid_content = $self->{content_transport}->addDomainContent( 'vhost', $domainId,
    'vhost',
    'directory' => $path,
    'sysuser' => $optPtr->{'sysuser'},
    'exclude' => $optPtr->{'exclude'},
  );
  $phostings->getChild( 'content', 1, 1 )->addChild( $cid_content ) if $cid_content;
  @{$self->{domainVhost}->{$domainId}} = ( 'vhost', $cid_content, $path )  if $cid_content;
}

sub setDomainMailNamesContent{
  my ( $self, $domainId, $domainAsciiName, $path, $sysuser ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my %options;
  $options{'directory'} = $path;
  $options{'sysuser'} = $sysuser;

  my ($fhApache, $tmpApache) = File::Temp::tempfile( AgentConfig::getBackupTmpDir() . "/pmm-dmnc-incApacheFiles-XXXXXX", UNLINK => 1 );
  my ($fhUser, $tmpUserData) = File::Temp::tempfile( AgentConfig::getBackupTmpDir() . "/pmm-dmnc-incUserData-XXXXXX", UNLINK => 1 );

  chown scalar(getpwnam('root')), scalar(getgrnam('root')), $tmpApache, $tmpUserData;
  chmod 0600, $tmpApache, $tmpUserData;

  my $optPtr = \%options;
  my %excludes = map { $_ => 1 } @{$optPtr->{'exclude'}};
  Logging::debug("List files in " . $path);
  $self->_listApacheAndUserFilesInFolder($path, $fhUser, $fhApache, [''], \%excludes);

  close $fhUser;
  close $fhApache;

  my $mailsystem = $root->getChild('mailsystem');

  my $mailContentSize = $self->_setDomainContentIncrementally(
    $domainId, $path, $optPtr, $tmpUserData, $mailsystem, 'domainmail', $sysuser, 'domainMailContent');

  $self->{stat}{mailSizeDumped} += $mailContentSize;
  $self->{stat}{mailDumpsCount}++;

}

sub setDomainMailListContent{
  my ( $self, $domainId, $domainAsciiName, $path, $optPtr ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $mailContent = $self->{content_transport}->addDomainContent( 'domainmaillist', $domainId,
      'ml',
      'directory' => $path,
      'include'   => $optPtr->{'include'},
      'follow_symlinks' => $optPtr->{'follow_symlinks'},
      'sysuser' => $optPtr->{'sysuser'}
  );

  $root->getChild( 'maillists' )->getChild( 'content', 1, 1 )->addChild( $mailContent ) if $mailContent;
  @{$self->{domainMailListContent}->{$domainId}} = ( 'domainmaillist', $mailContent, $path ) if $mailContent;
}

sub setDomainPhostingContentCommon {
  my ( $self, $domainId, $domainName, $path, $contentType ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my @phostings = $root->getChildren('phosting');
  if ( scalar(@phostings) < 1 ) {
    Logging::warning('Unable to dump domain phosting content, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $cid_content = $self->{content_transport}->addDomainContent( $contentType, $domainId,
    $contentType,
    'directory' => $path
  );
  $phostings[0]->getChild( 'content', 1, 1 )->addChild( $cid_content ) if $cid_content;
  return $cid_content;
}

sub setDomainPhostingProtectedDirContent {
  my ( $self, $domainId, $domainName, $path ) = @_;
  $self->setDomainPhostingContentCommon($domainId, $domainName, $path, 'pd');
}

sub setDomainPhostingStatisticsContent {
  my ( $self, $domainId, $domainName, $path, $optPtr ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my @phostings = $root->getChildren('phosting');
  if ( scalar(@phostings) < 1 ) {
    Logging::warning('Unable to dump domain phosting statistics, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $cid_content = $self->{content_transport}->addDomainContent( 'statistics', $domainId,
    'statistics',
    'directory' => $path
  );
  $phostings[0]->getChild( 'content', 1, 1 )->addChild( $cid_content ) if $cid_content;
  @{$self->{domainStatistics}->{$domainId}} = ( 'statistics', $cid_content, $path )  if $cid_content;
}

sub setDomainPhostingWebstatContent {
  my ( $self, $domainId, $domainName, $path ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my @phostings = $root->getChildren('phosting');
  if ( scalar(@phostings) < 1 ) {
    Logging::warning('Unable to dump domain phosting webstat content, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $cid_webstat = $self->{content_transport}->addDomainContent( 'webstat', $domainId,
    'webstat',
    'directory' => $path
  );
  $phostings[0]->getChild( 'content', 1, 1 )->addChild( $cid_webstat ) if $cid_webstat;
}

sub setDomainPhostingWebstatSslContent {
  my ( $self, $domainId, $domainName, $path ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my @phostings = $root->getChildren('phosting');
  if ( scalar(@phostings) < 1 ) {
    Logging::warning('Error: setDomainPhostingWebstatSslContent: there are no node "phosting"', 'PackerStructure');
    return;
  }

  my $cid_webstat_ssl = $self->{content_transport}->addDomainContent( 'webstat_ssl', $domainId,
    'webstat-ssl',
    'directory' => $path
  );
  $phostings[0]->getChild( 'content', 1, 1 )->addChild( $cid_webstat_ssl ) if $cid_webstat_ssl;
}

sub setDomainPhostingFtpstatContent {
  my ( $self, $domainId, $domainName, $path ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my @phostings = $root->getChildren('phosting');
  if ( scalar(@phostings) < 1 ) {
    Logging::warning('Unable to dump domain phosting ftpstat content, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $cid_ftpstat = $self->{content_transport}->addDomainContent( 'ftp_stat', $domainId,
    'ftpstat',
    'directory' => $path
  );
  $phostings[0]->getChild( 'content', 1, 1 )->addChild( $cid_ftpstat ) if $cid_ftpstat;
}

sub setDomainPhostingLogsContent {
  my ( $self, $domainId, $domainName, $path ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my @phostings = $root->getChildren('phosting');
  if ( scalar(@phostings) < 1 ) {
    Logging::warning('Error: setDomainPhostingLogsContent: there are no node "phosting"', 'PackerStructure');
    return;
  }

  if ($self->{excludePatternsFile} and -e $self->{excludePatternsFile} and -s $self->{excludePatternsFile}) {
    # Prepare logs directory listing
    my ($fhListing, $listingFile) = File::Temp::tempfile(AgentConfig::getBackupTmpDir() . "/pmm-dplc-lst-XXXXXX", UNLINK => 1);
    chown scalar(getpwnam('root')), scalar(getgrnam('root')), $listingFile;
    chmod 0600, $listingFile;

    Logging::debug("List files in " . $path);
    $self->_listApacheAndUserFilesInFolder($path, $fhListing, undef, ['']);
    close($fhListing);

    # Prepare temporary files for pmm-ras
    my ($fhChages, $changesFile) = File::Temp::tempfile(AgentConfig::getBackupTmpDir() . "/pmm-dplc-ch-XXXXXX", UNLINK => 1);
    my ($fhIndex, $indexFile) = File::Temp::tempfile(AgentConfig::getBackupTmpDir() . "/pmm-dplc-id-XXXXXX", UNLINK => 1);
    my ($fhExcludes, $excludesFile) = File::Temp::tempfile(AgentConfig::getBackupTmpDir() . "/pmm-dplc-ex-XXXXXX", UNLINK => 1);
    close($fhChages);
    close($fhIndex);
    close($fhExcludes);

    # Prepare excludes for logs directory
    my $res = HelpFuncs::replaceLinesInFile($self->{excludePatternsFile}, $excludesFile, '^\/logs\/|^logs\/', '');
    if ($res == 0) {
      my $cmd = [
        AgentConfig::pmmRasBin(), '--get-diff',
        '--listing-file', $listingFile,
        '--backup-file', $self->{storage}->{output_dir}."/".$self->getDomainsBackupPath($domainId, 'logs').".tgz",
        '--changes-file', $changesFile,
        '--index-file', $indexFile,
        '--exclude-patterns-file', $excludesFile
      ];

      if (Logging::getVerbosity() >= Logging::LOG_LEVEL_DEBUG) {
        push (@{$cmd}, '--debug');
      }

      if (Logging::getVerbosity() >= Logging::LOG_LEVEL_TRACE) {
        push (@{$cmd}, '--verbose');
      }

      Logging::debug("Execute: ".join(' ', @{$cmd}));
      my($err);
      run $cmd, '>&', \$err;

      my $dumpSucceed = 0;
      if ($?) {
        Logging::debug("Unable to apply exclusion pattern to log files, all log files will be dumped. Error (".$?.") ".$err, "LogsBackup");
      } else {
        my $cid_logs =  $self->{content_transport}->addDomainContent( 'logs', $domainId,
          'logs',
          'directory' => $path,
          'includes-file' => $changesFile,
          'no_recursion' => 1
        );
        $phostings[0]->getChild( 'content', 1, 1 )->addChild( $cid_logs ) if $cid_logs;
        $dumpSucceed = 1;
      }

      return if ($dumpSucceed);
    }
  }

  my $cid_logs =  $self->{content_transport}->addDomainContent( 'logs', $domainId,
    'logs',
    'directory' => $path
  );
  $phostings[0]->getChild( 'content', 1, 1 )->addChild( $cid_logs ) if $cid_logs;
}

sub setDomainPhostingAnonFtpstatContent {
  my ( $self, $domainId, $domainName, $path ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my @phostings = $root->getChildren('phosting');
  if ( scalar(@phostings) < 1 ) {
    Logging::warning('Error: setDomainPhostingAnonFtpstatContent: there are no node "phosting"', 'PackerStructure');
    return;
  }

  my $cid_anon_ftpstat = $self->{content_transport}->addDomainContent( 'anon_ftpstat', $domainId,
    'anon-ftpstat',
    'directory' => $path
  );
  $phostings[0]->getChild( 'content', 1, 1 )->addChild( $cid_anon_ftpstat ) if $cid_anon_ftpstat;
}

sub setDomainPhostingConfContent {
  my ( $self, $domainId, $domainName, $path, $optPtr ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my @phostings = $root->getChildren('phosting');
  if ( scalar(@phostings) < 1 ) {
    Logging::warning('Error: setDomainPhostingConfContent: there are no node "phosting"', 'PackerStructure');
    return;
  }

  my $cid_conf;
  $cid_conf = $self->{content_transport}->addDomainContent( 'conf', $domainId,
    'conf',
    'directory' => $path,
    'exclude' => $optPtr->{'exclude'}
  );
  $phostings[0]->getChild( 'content', 1, 1 )->addChild( $cid_conf ) if $cid_conf;
}

sub setDomainLogrotation {
  my ( $self, $domainId, $logRotationPtr) = @_;

  my %logRotation = %{$logRotationPtr};

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $phosting = $root->getChild('phosting');
  if ( not $phosting ) {
    Logging::warning('Unable to dump domain log rotation settings, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $node;
  if (keys %logRotation) {
    $self->makeLogrotationNode( $phosting->getChild( 'preferences', 1 ), \%logRotation );
  }
}

sub setDomainAnonFtp {
  my ( $self, $domainId, $domainName, $propsPtr, $pub_path, $incoming_path, $optPtr ) = @_;

  my %props = %{$propsPtr};

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $phosting = $root->getChild('phosting');
  if ( not $phosting ) {
    Logging::warning('Error: setDomainAnonFtp: there are no node "phosting"', 'PackerStructure');
    return;
  }

  my $anonRoot = XmlNode->new('anonftp');
  if ( $props{'status'} =~ /true/ ) {
    $anonRoot->setAttribute( 'pub', 'true' );
  }
  if ( $props{'incoming'} =~ /true/ ) {
    $anonRoot->setAttribute( 'incoming', 'true' );
  }
  if ( defined( $props{'max_conn'} ) ) {
    makeAnonftpLimitNode( $anonRoot, 'max-connections',
      $props{'max_conn'} );
  }
  if ( defined( $props{'bandwidth'} ) ) {
    makeAnonftpLimitNode( $anonRoot, 'bandwidth',
      $props{'bandwidth'} );
  }
  if ( defined( $props{'quota'} ) ) {
    makeAnonftpLimitNode( $anonRoot, 'incoming-disk-quota',
      $props{'quota'} );
  }
  if ( defined( $props{'display_login'} ) ) {
    $anonRoot->setAttribute( 'display-login', $props{'display_login'} );
  }
  if ( $props{'incoming_readable'} =~ /true/ ) {
    makeAnonftpPermissionNode( $anonRoot, 'incoming-download' );
  }
  if ( $props{'incoming_subdirs'} =~ /true/ ) {
    makeAnonftpPermissionNode( $anonRoot, 'incoming-mkdir' );
  }
  if ( defined( $props{'login_text'} ) ) {
    my $loginMessageNode =
      XmlNode->new( 'login-message',
      'content' => $props{'login_text'} );
    $anonRoot->addChild($loginMessageNode);
  }

  $phosting->getChild( 'preferences', 1 )->addChild($anonRoot);
}

sub addDomainProtectedDir {
  my ( $self, $domainId, $pdirPath, $pdirTitle, $pdirNonSSL, $pdirSSL, $pdirCGI, $pdirUsersPtr) = @_;

  my @pdirUsers = @{$pdirUsersPtr};

  my $parent = $self->getCashedDomainNode($domainId);
  return unless defined $parent;

  my $phostingNode = $parent->getChild('phosting');
  unless ( defined $phostingNode ) {
    Logging::warning('Error: addDomainProtectedDir: there are no node "phosting"', 'PackerStructure');
    return;
  }

  my $pdirNode = XmlNode->new('pdir');

  # workaround of CLI inabliity to create '' directory.
  if ( $pdirPath eq '' ) {
    $pdirPath = '/';
  }

  $pdirNode->setAttribute( 'name', $pdirPath );
  if ($pdirTitle) {
    $pdirNode->setAttribute( 'title', $pdirTitle );
  }
  if ($pdirNonSSL) {
    $pdirNode->setAttribute( 'nonssl', $pdirNonSSL );
  }
  if ($pdirSSL) {
    $pdirNode->setAttribute( 'ssl', $pdirSSL );
  }
  if ($pdirCGI) {
    $pdirNode->setAttribute( 'cgi', $pdirCGI );
  }

  my $userNode;
  my $item;
  for my $user (@pdirUsers) {
    $userNode = XmlNode->new('pduser');
    $pdirNode->addChild($userNode);
    $userNode->setAttribute( 'name', $user->{'login'} );

    $item = CommonPacker::makePasswordNode( $user->{'passwd'}, CommonPacker::normalizePasswordType( $user->{'passwdType'} ) );
    $userNode->addChild($item);
  }

  $phostingNode->getChild( 'preferences', 1 )->addChild($pdirNode);
}

sub addDomainSubFtpUser {
  my ( $self, $domainId, $domainName, $sysuserPtr, $ptrFtpUser) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $phostingNode = $root->getChild('phosting');

  my $ftpuserNode = XmlNode->new('ftpuser');
  $ftpuserNode->setAttribute('name', $sysuserPtr->{'login'});
  $ftpuserNode->setAttribute('external-id', $ptrFtpUser->{'external_id'}) if defined $ptrFtpUser->{'external_id'} and $ptrFtpUser->{'external_id'} ne '';
  $ftpuserNode->addChild($self->makeSysUser($sysuserPtr));
  $phostingNode->getChild( 'ftpusers', 1 )->addChild($ftpuserNode);
}

sub setEmptySubFtpUsersNode {
  my ($self, $domainId) = @_;
  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $phostingNode = $root->getChild('phosting', 1);

  $phostingNode->getChild('ftpusers', 1);
}

sub removeResellerChildNodeIfEmpty {
  my ( $self, $clientId, $nodeName ) = @_;
  my $root = $self->getCashedClientNode( $clientId );
  return unless defined $root;

  my $containerNode = $root->getChild( $nodeName );
  if ( defined $containerNode && not $containerNode->getChildren() ) {
    $root->removeChildren( $nodeName );
  }
}

sub removePhostingChildNodeIfEmpty {
  my ( $self, $domainId, $nodeName ) = @_;
  my $root = $self->getCashedDomainNode( $domainId );
  return unless defined $root;

  my $phostingNode = $root->getChild( 'phosting', 1 );

  my $containerNode = $phostingNode->getChild( $nodeName . 's' );
  if ( defined $containerNode && not $containerNode->getChildren( $nodeName ) ) {
    $phostingNode->removeChildren( $nodeName . 's' );
  }
}

sub removeMailSystemChildNodeIfEmpty {
  my ( $self, $domainId, $nodeName ) = @_;
  my $root = $self->getCashedDomainNode( $domainId );
  return unless defined $root;

  my $mailsystemNode = $root->getChild( 'mailsystem' );

  my $containerNode = $mailsystemNode->getChild( $nodeName . 's' );
  if ( defined $containerNode && not $containerNode->getChildren( $nodeName ) ) {
    $mailsystemNode->removeChildren( $nodeName . 's' );
  }
}

sub addDomainWebUser {
  my ( $self, $domainId, $domainName, $sysuserPtr, $scriptingPtr, $webUserHome, $privatePath ) = @_;

  my %sysuser = %{$sysuserPtr};
  my %scripting = %{$scriptingPtr};

  my $parent = $self->getCashedDomainNode($domainId);
  return unless defined $parent;

  my $phostingNode = $parent->getChild('phosting');
  unless( defined $phostingNode ) {
    Logging::warning('Unable to dump domain webuser preferences, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $webuserNode = XmlNode->new('webuser');

  $webuserNode->addChild($self->makeSysUser(\%sysuser));

  my $userName = $sysuser{'login'};
  $webuserNode->setAttribute( 'name', $userName );

  my %webUserScripting = (
    'ssi'         => 'ssi',
    'php'         => 'php',
    'cgi'         => 'cgi',
    'perl'        => 'perl',
    'asp'         => 'asp',
    'python'      => 'python',
    'asp_dot_net' => 'asp_dot_net',
    'fastcgi'     => 'fastcgi',
  );

  my $item = XmlNode->new('scripting');
  while ( my ( $xmlName, $fieldName ) = each(%webUserScripting) ) {
    if ( defined $scripting{$fieldName}
      and ( $scripting{$fieldName} eq 'true' or $scripting{$fieldName} eq 'false' ) )
    {
      $item->setAttribute( $xmlName, $scripting{$fieldName} );
    }
  }

  if (exists $scripting{'write_modify'} && defined $scripting{'write_modify'}) {
    $item->setAttribute('write_modify', $scripting{'write_modify'}) ;
  }

  $webuserNode->addChild($item);

  $phostingNode->getChild( 'webusers', 1 )->addChild($webuserNode);
}

sub setDomainWebalizer {
  my ( $self, $domainId, $directRef, $hiddenRefsPtr, $groupRefsPtr) = @_;

  my @hiddenRefs = @{$hiddenRefsPtr};
  my @groupRefs = @{$groupRefsPtr};

  my $parent = $self->getCashedDomainNode($domainId);
  return unless defined $parent;

  my $phostingNode = $parent->getChild('phosting');
  unless( defined $phostingNode ) {
    Logging::warning('Unable to dump domain webalizer settings, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $webalizerNode = XmlNode->new("webalizer");

  if ($directRef)
  {
    $webalizerNode->setAttribute( 'hide-direct-referrer', 'true' );
  }

  for my $ref (@hiddenRefs) {
    $webalizerNode->addChild(
      XmlNode->new(
        "webalizer-hidden-referrer", 'content' => $ref
      )
    );
  }

  for my $groupRef (@groupRefs ) {
    $webalizerNode->addChild(
      XmlNode->new(
        'webalizer-group-referrer',
        'content'    => $groupRef->{'ref'},
        'attributes' => { 'group-name' => $groupRef->{'name'} }
      )
    );
  }

  $phostingNode->getChild( 'preferences', 1 )->addChild($webalizerNode);
}


sub setComposerInstances {
  my ($self, $domainId, $embeddedInfo) = @_;

  return unless defined($embeddedInfo->{'composer-instances'});

  my $domainNode = $self->getCashedDomainNode($domainId);
  return unless defined $domainNode;

  my ($composerInstances) = @{$embeddedInfo->{'composer-instances'}};
  $self->addEmbeddedInfo($domainNode, 'composer-instances', $composerInstances);
}

sub setDomainPerfomance {
  my ( $self, $domainId, $max_connection, $traffic_bandwidth) = @_;

  my $parent = $self->getCashedDomainNode($domainId);
  return unless defined $parent;

  my $phostingNode = $parent->getChild('phosting');
  unless ( defined $phostingNode ) {
    Logging::warning('Unable to dump domain performance settings, because of dump XML structure is not full', 'PackerStructure');
    return;
  }

  my $perfomanceNode = XmlNode->new('perfomance');
  $perfomanceNode->addChild(
    XmlNode->new(
      'max-connections', 'content' => $max_connection
    )
  );
  $perfomanceNode->addChild(
    XmlNode->new(
      'traffic-bandwidth', 'content' => $traffic_bandwidth
    )
  );

  $phostingNode->getChild( 'preferences', 1 )->addChild( $perfomanceNode );
}

sub appendNodeToPhosting {
  my ( $self, $domainId, $rawXml ) = @_;

  my $parent = $self->getCashedDomainNode($domainId);
  return unless defined $parent;

  my $phostingNode = $parent->getChild('phosting');
  unless ( defined $phostingNode ) {
    Logging::warning('Error: appendNodeToPhosting: there are no node "phosting"', 'PackerStructure');
    return;
  }

  $phostingNode->addChild(XmlNode->new(undef, 'raw' => $rawXml));
}

sub setIisAppPool {
  my ( $self, $domainId, $info ) = @_;

  my $parent = $self->getCashedDomainNode($domainId);
  return unless defined $parent;

  my $phostingNode = $parent->getChild('phosting');
  unless ( defined $phostingNode ) {
    Logging::warning('Error: setIisAppPool: there are no node "phosting"', 'PackerStructure');
    return;
  }

  my $phostingPreferencesNode = $phostingNode->getChild('preferences');
  unless ( defined $phostingPreferencesNode ) {
    Logging::warning('Error: setIisAppPool: there are no node "phosting/preferences"', 'PackerStructure');
    return;
  }

  $phostingPreferencesNode->addChild(XmlNode->new(undef, 'raw' => $info));
}


sub setDomainWebStat{
  my ( $self, $domainId, $stat_ttl) = @_;

  my $parent = $self->getCashedDomainNode($domainId);
  return unless defined $parent;

  my $phostingNode = $parent->getChild('phosting');
  unless( defined $phostingNode ) {
    Logging::warning('Error: setDomainWebStat: there are no node "phosting"', 'PackerStructure');
    return;
  }
  my $webstatNode = XmlNode->new('web-stat');
  $webstatNode->setAttribute( 'stat-ttl', $stat_ttl ) if $stat_ttl;
  $phostingNode->getChild( 'preferences', 1 )->addChild( $webstatNode );
}

sub setDomainShosting {
  my ( $self, $domainId, $forward, $ips) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $shostingNode = XmlNode->new( 'shosting');
  $shostingNode->addChild(XmlNode->new('url', 'content' => $forward->{'redirect'}));

  my $propertiesNode = $shostingNode->getChild('properties', 1);

  foreach my $ip (keys %{$ips}) {
    my $ipNode = $self->makeIpNode($ip, $ips->{$ip});
    $propertiesNode->addChild($ipNode);
  }

  if (defined ($forward->{'http_code'})) {
	my $httpcodeNode = XmlNode->new('httpcode', 'content' => $forward->{'http_code'});
	$shostingNode->addChild($httpcodeNode);
  }

  $root->addChild($shostingNode);
}

sub setDomainFhosting {
  my ( $self, $domainId, $forward, $ips) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $fhostingNode = XmlNode->new( 'fhosting');
  $fhostingNode->addChild(XmlNode->new('url', 'content' => $forward));

  my $propertiesNode = $fhostingNode->getChild('properties', 1);

  foreach my $ip (keys %{$ips}) {
    my $ipNode = $self->makeIpNode($ip, $ips->{$ip});
    $propertiesNode->addChild($ipNode);
  }

  $root->addChild($fhostingNode);
}

sub addSite {
  my ( $self, $domainId, $sitePtr) = @_;

  my $parent = $self->getCashedDomainNode($domainId);
  return unless defined $parent;

  my $phostingNode = $parent->getChild('phosting');
  unless ( defined $phostingNode ) {
    Logging::warning('Error: addSite: there are no node "phosting"', 'PackerStructure');
    return;
  }

  my $siteNode = XmlNode->new( 'site' );

  $phostingNode->getChild( 'sites', 1 )->addChild($siteNode);

}

###### SubDomain functions ######

sub addSubDomain {
  my ( $self, $domainId, $ptrSubDomain, $subDomainName, $subAsciiDomainName, $https, $shared_content, $sysuserPtr, $scriptingPtr) = @_;
  $subAsciiDomainName = $subDomainName if not $subAsciiDomainName;
  my $subDomainId = $ptrSubDomain->{'id'};
  $self->regSubdomainObjectBackupPath( $self->getPhostingBackupPath( $domainId ), $subDomainId, $subAsciiDomainName, $domainId );

  my %sysuser = %{$sysuserPtr};
  my %scripting = %{$scriptingPtr};

  my $parent = $self->getCashedDomainNode($domainId);
  return unless defined $parent;

  my $phosting = $parent->getChild('phosting');
  if ( not $phosting ) {
    Logging::warning('Error: addSubdomain: there are no node "phosting"', 'PackerStructure');
    return;
  }

  my $root = XmlNode->new('subdomain', 'attributes' => {'id' => $subDomainId});
  $self->{subDomainNodes}->{$subDomainId} = $root;
  my $parentGuid = $parent->getAttribute('guid');
  $root->setAttribute( "guid", $self->getSubdomainGuid( $parentGuid, $subDomainId ) ) if $parentGuid;
  my $phostingGuid = $phosting->getAttribute('guid');
  $root->setAttribute( 'owner-guid', $phostingGuid ) if $phostingGuid;

  $root->setAttribute( 'name', $subDomainName );

  if ( 'true' eq $https ) {
    $root->setAttribute( 'https', 'true' );
  }

  if ( $shared_content ne '' ) {
    $root->setAttribute( 'shared-content', $shared_content );
  }

  if ( exists $ptrSubDomain->{'www-root'} ) {
    $root->setAttribute( 'www-root', $ptrSubDomain->{'www-root'} );
  }

  if ( exists $ptrSubDomain->{'certificate_ref'} ) {
    $root->setAttribute( 'certificate', $ptrSubDomain->{'certificate_ref'});
  }

  if ( exists $ptrSubDomain->{'original-conf-directory'} ) {
    $root->setAttribute('original-conf-directory', $ptrSubDomain->{'original-conf-directory'});
  }

  ## %sysuser ##

  if (%sysuser)
  {
    $root->addChild($self->makeSysUser(\%sysuser));
  }

  ## %scripting ##

  my $scripringItem = XmlNode->new('scripting');
  while ( my ( $xmlName, $value ) = each(%scripting) ) {
      $scripringItem->setAttribute( $xmlName, $value );
  }
  $root->addChild($scripringItem);

  $phosting->getChild( 'subdomains', 1 )->addChild($root);

}

sub setSubDomainHttpdocsContent {
  my ( $self, $subDomainId, $path, $exclude_httpdocs_files_ptr, $webspaceId ) = @_;

  my @exclude_httpdocs_files = @{$exclude_httpdocs_files_ptr};

  my $root = $self->{subDomainNodes}->{$subDomainId};

  my $cid_docroot = $self->{content_transport}->addSubDomainPhostingContent( 'docroot', $subDomainId, $webspaceId,
    "httpdocs",
    "directory" => $path,
    "exclude"   => \@exclude_httpdocs_files
  );
  $root->getChild( 'content', 1, 1 )->addChild( $cid_docroot ) if $cid_docroot;
}

sub setSubDomainHttpsdocsContent {
  my ( $self, $subDomainId, $path, $exclude_httpsdocs_files_ptr ) = @_;

  my @exclude_httpsdocs_files = @{$exclude_httpsdocs_files_ptr};

  my $root = $self->{subDomainNodes}->{$subDomainId};

  my $cid_docroot_ssl = $self->{content_transport}->addSubDomainPhostingContent('docroot_ssl', $subDomainId, undef,
    "httpsdocs",
    "directory" => $path,
    "exclude"   => \@exclude_httpsdocs_files
  );
  $root->getChild( 'content', 1, 1 )->addChild( $cid_docroot_ssl ) if $cid_docroot_ssl;
}

sub setSubDomainErrorDocsContent {
  my ( $self, $subDomainId, $path ) = @_;
  my $subdomainNode = $self->{subDomainNodes}->{$subDomainId};

  my $cid_conf = $self->{content_transport}->addSubDomainPhostingContent( 'error_docs', $subDomainId, undef,
    "error-docs",
    "directory" => $path
  );
  $subdomainNode->getChild( 'content', 1, 1 )->addChild( $cid_conf ) if $cid_conf;

}

sub setSubDomainConfContent {
  my ( $self, $subDomainId, $path ) = @_;

  my $root = $self->{subDomainNodes}->{$subDomainId};

  my $cid_conf = $self->{content_transport}->addSubDomainPhostingContent( 'conf', $subDomainId, undef,
    "conf",
    "directory" => $path
  );
  $root->getChild( 'content', 1, 1 )->addChild( $cid_conf ) if $cid_conf;
}
###### End SubDomain functions ######

####### Mail functions #######

sub addMail {
    my ( $self, $domainId, $mailId, $mailName, $passwd, $typePasswd, $userUid, $cpAccessDefault ) = @_;

    $self->regMailnameObjectBackupPath( $self->getDomainsBackupPath( $domainId ), $mailId, $mailName, $domainId );

    my $parent = $self->getCashedDomainNode($domainId);
    return unless defined $parent;

    my $mailService = $parent->getChild('mailsystem');
    if ( not $mailService ) {
      Logging::warning("Error: Packer.addMail: there are no node 'mailsystem'", 'PackerStructure');
      return;
    }

    my $root = XmlNode->new( 'mailuser', 'attributes' => {
        'id'   => $mailId,
        'name' => $mailName
        } );

    if (defined $userUid) {
      $root->setAttribute('user-guid', $userUid);
    }
    
    if (defined $cpAccessDefault) {
      $root->setAttribute('cp-access-default', $cpAccessDefault);
    }

    my $parentGuid = $parent->getAttribute('guid');
    $root->setAttribute( 'guid', $self->getMailnameGuid( $parentGuid, $mailId ) ) if $parentGuid;
    $root->setAttribute( 'owner-guid', $parentGuid ) if $parentGuid;

    my $props = $root->getChild( 'properties', 1 );
    $root->getChild( 'preferences', 1 );

    my $item;
    if ( defined($passwd) && $passwd ) {
      if ( $passwd =~ /NULL/ ) {
        $item = CommonPacker::makePasswordNode( '', 'plain' );
      }
      else {
        $item = CommonPacker::makePasswordNode( $passwd, $typePasswd );
      }

      $props->addChild( $item );
    }

    $self->{mailNodes}->{$mailId} = $root;

    $mailService->getChild( 'mailusers', 1 )->addChild($root);
}

sub setMailBoxQuota {
    my ( $self, $mailId, $mbox_quota ) = @_;

    my $root = $self->{mailNodes}->{$mailId};

    $root->setAttribute( 'mailbox-quota', $mbox_quota );
}

sub setMailBox {
    my ( $self, $mailId, $mailName, $domainAsciiName, $enabled, $path ) = @_;

    my $root = $self->{mailNodes}->{$mailId};
    if ( !defined($root) ) {
      Logging::warning('Unable to dump $mailName mailbox settings, because of dump XML structure is not full', 'PackerStructure');
      return;
    }

    my $item = XmlNode->new( 'mailbox', 'attributes' => { 'type' => 'mdir' } );
    $root->getChild( 'preferences', 1 )->addChild($item);

    $item->setAttribute( 'enabled', $enabled );

    my $cid = $self->{content_transport}->addMailnameContent( 'mailbox', $mailId, "mdir",
      'directory' => $path, 'include_hidden_files' => 1 );
    $item->getChild( 'content', 1, 1 )->addChild( $cid ) if $cid;
}

sub addMailAliase {
    my ( $self, $mailId, $name ) = @_;

    my $root = $self->{mailNodes}->{$mailId};
    if ( !defined($root) ) {
      Logging::warning('Unable to dump $name mail alias settings, because of dump XML structure is not full', 'PackerStructure');
      return;
    }

    my $item = XmlNode->new( 'alias', 'content' => $name );
    $root->getChild( 'preferences', 1 )->addChild($item);
}

sub setMailForwarding {
    my ( $self, $mailId, $enabled, $membersPtr ) = @_;

    unless ( ref($membersPtr) =~ /ARRAY/ ) {
      Logging::warning("Error: setMailForwarding: membersPtr is not ref array",'assert');
      return;
    }

    my @members = @{$membersPtr};

    my $root = $self->{mailNodes}->{$mailId};
    if ( !defined($root) ) {
      Logging::warning('Unable to dump mail forwarding settings, because of dump XML structure is not full', 'PackerStructure');
      return;
    }

    $root->setAttribute( 'forwarding-enabled', $enabled? 'true': 'false' );

    my $item;
    foreach my $member (@members) {
      $item = XmlNode->new( 'forwarding', 'content' => $member );
      $root->getChild( 'preferences', 1 )->addChild($item);
    }
}

sub setMailAutoresponders {
    my ( $self, $mailId, $mailName, $domainAsciiName, $dir, $enabled, $autorespondersPtr, $attachesPtr ) = @_;

    unless ( ref($autorespondersPtr) =~ /ARRAY/ ) {
      Logging::warning("Error: setMailAutoresponders: autorespondersPtr is not ref array",'assert');
      return;
    }
    my @autoresponders = @{$autorespondersPtr};

    unless ( ref($attachesPtr) =~ /ARRAY/ ) {
      Logging::warning("Error: setMailAutoresponders: attachesPtr is not ref array",'assert');
      return;
    }
    my @attaches = @{$attachesPtr};

    my $root = $self->{mailNodes}->{$mailId};
    if ( !defined($root) ) {
      Logging::warning('Error: setMailAutoresponders: empty parent node', 'PackerStructure');
      return;
    }

    my $autorespondersNode = XmlNode->new( 'autoresponders' );

    my $cid = $self->{content_transport}->addMailnameContent( 'attaches', $mailId,  "attach",
      "directory" => $dir, 'checkEmptyDir' => 1 );
    $autorespondersNode->getChild( 'content', 1, 1 )->addChild( $cid ) if $cid;

    foreach my $file (@attaches) {
      $autorespondersNode->addChild(
        XmlNode->new( 'attach', 'attributes' => { 'file' => $file } ) );
    }

    foreach my $autoresponder (@autoresponders) {
      my $node = $self->makeAutoresponderNode ($enabled, $autoresponder, $mailName);
      $autorespondersNode->addChild($node);
    }

    $root->getChild( 'preferences', 1 )->addChild($autorespondersNode);
}

sub setMailAddressbook {
    my ( $self, $mailId, $paramsPtr, $turbaVersion ) = @_;

    my @params = @{$paramsPtr};

    my $parent = $self->{mailNodes}->{$mailId};
    if ( !defined($parent) ) {
      Logging::warning('Unable to dump mailbox addressbook, because of dump XML structure is not full', 'PackerStructure');
      return;
    }

    my $node = XmlNode->new('addressbook');

    my %objectFields = (
      'id'           => 'id',
      'name'         => 'name',
      'alias'        => 'alias',
      'email'        => 'email',
      'title'        => 'title',
      'company'      => 'company',
      'home-phone'   => 'homephone',
      'work-phone'   => 'workphone',
      'mobile-phone' => 'cellphone',
      'fax'          => 'fax',
      'home-address' => 'homeaddress',
      'work-address' => 'workaddress',
      'notes'        => 'notes'
      );

    if ($turbaVersion->{'majorVersion'} = 2 && $turbaVersion->{'minorVersion'} > 2 ) {
        $objectFields{'name'} = 'lastname';
        $objectFields{'home-address'} = 'homestreet';
        $objectFields{'work-address'} = 'workstreet';
        $objectFields{'first-name'} = 'firstname';
        $objectFields{'middle-names'} = 'middlenames';
    }

    foreach my $ptrHash (@params) {
      if ( $ptrHash->{'object_type'} eq 'Group' ) {

        my $group =
          XmlNode->new( 'addressbook-group',
          'attributes' => { 'id' => $ptrHash->{'object_id'} } );

        my $members = $ptrHash->{'object_members'};
        while ( $members =~ /"(.*?)"/g ) {
          my $member = $1;
          chomp $member;

          $group->addChild(
            XmlNode->new(
              'addressbook-member', 'attributes' => { 'id' => $member }
            )
          );
        }

        $node->addChild($group);
      }
      else {
        my $user = XmlNode->new('addressbook-contact');
        my ( $field, $attr );
        while ( ( $field, $attr ) = each %objectFields ) {
          if ( defined $ptrHash->{ 'object_' . $attr } ) {

            # Here 'encoded' attribute means there is no meaning to
            # convert data to UTF as it was already converted manually
            my $value = $ptrHash->{ 'object_' . $attr };
            $value = $self->{base64}->{'ENCODE'}->($value) if $attr ne 'id';
            $user->setAttribute( $field, $value );
          }
        }
        $node->addChild($user);
      }
    }

    $parent->getChild( 'preferences', 1 )->addChild($node);
}

sub setMailSpamSettings {
    my ( $self, $mailId, $mailName, $domainAsciiName, $status, $server_conf,
      $action, $requiredScore, $modify_subj, $subj_text,
      $blackListPtr, $whiteListPtr, $unblackListPtr, $unwhiteListPtr, $path, $server ) = @_;

    my @blackList = @{$blackListPtr};
    my @whiteList = @{$whiteListPtr};
    my @unblackList = @{$unblackListPtr};
    my @unwhiteList = @{$unwhiteListPtr};

    my $parent;
    if (defined($mailId)) {
    	$parent = $self->{mailNodes}->{$mailId};
    }elsif (defined($server)) {
    	$parent = $self->{serverNode};
    }
    if ( !defined($parent) ) {
      Logging::warning('Unable to dump SPAM filter settings, because of dump XML structure is not full', 'PackerStructure');
      return;
    }

    my $item = XmlNode->new( 'spamassassin' );

    $item->setAttribute( 'status', $status ) if defined($status);
    $item->setAttribute( 'action', $action ) if ( defined($action) && $action );
    $item->setAttribute( 'server-conf', $server_conf )
      if ( defined($server_conf) && $server_conf );
    $item->setAttribute( 'hits', $requiredScore )
      if ( defined($requiredScore) && $requiredScore );
    $item->setAttribute( 'modify-subj', $modify_subj )
      if ( defined($modify_subj) && $modify_subj );
    $item->setAttribute( 'subj-text', $subj_text )
      if ( defined($subj_text) && $subj_text );
    $item->setAttribute( 'max-spam-threads', $server->{'spamfilter_max_children'})
      if ( defined($server->{'spamfilter_max_children'}) && $server->{'spamfilter_max_children'});

    foreach my $blMail (@blackList) {
      $item->addChild( XmlNode->new( 'blacklist-member', 'content' => $blMail ) );
    }
    foreach my $wtMail (@whiteList) {
      $item->addChild( XmlNode->new( 'whitelist-member', 'content' => $wtMail ) );
    }
    foreach my $ublMail (@unblackList) {
      $item->addChild(
        XmlNode->new( 'unblacklist-member', 'content' => $ublMail ) );
    }
    foreach my $uwtMail (@unwhiteList) {
      $item->addChild(
        XmlNode->new( 'unwhitelist-member', 'content' => $uwtMail ) );
    }

    if (defined($mailId) && -d "$path") {
      my $cid = $self->{content_transport}->addMailnameContent( 'spam-assasin', $mailId, "sa", "directory" => $path );
      $item->getChild( 'content', 1, 1 )->addChild( $cid ) if $cid;
    }

    if (defined($mailId)) {
	    $parent->getChild( 'preferences', 1 )->addChild($item);
    }elsif(defined($server)) {
    	    $parent->addChild($item);
    }
}

sub setMailVirusSettings {
    my ( $self, $mailId, $state ) = @_;

    my $parent = $self->{mailNodes}->{$mailId};
    if ( !defined($parent) ) {
      Logging::warning('Unable to dump virus protection settings, because of dump XML structure is not full', 'PackerStructure');
      return;
    }

    my $item = XmlNode->new('virusfilter');
    $item->setAttribute( 'state', $state );

    $parent->getChild( 'preferences', 1 )->addChild($item);
}

sub addMailCustomButton {
    my ( $self, $mailId, $id, $optionsPtr, $customButtonsDir, $icon ) = @_;

    my $parent = $self->{mailNodes}->{$mailId};
    if ( !defined($parent) ) {
      Logging::warning('Unable to dump mail custom buttons settings, because of dump XML structure is not full', 'PackerStructure');
      return;
    }

    my $node = $self->makeCustomButtonNode( 'mails', $mailId, $id, $optionsPtr, $customButtonsDir, $icon );

    $parent->getChild( 'preferences', 1 )->addChild($node);
}
####### End Mail functions #######

####### Siteapps functions #######

sub addDomainSapp {
  my ( $self, $domainId, $sappId, $sapp, $licenseType ) = @_;
  my $parent = $self->getCashedDomainNode($domainId);
  return unless defined $parent;

  my $phosting = $parent->getChild('phosting');
  if ( not $phosting ) {
    Logging::warning('Unable to dump domain site applications, because of dump XML structure is not full','assert');
    return;
  }

  my $sapp_installed = $self->makeSappNode($sappId, $sapp, $licenseType);

  $self->{sappNodes}->{$sappId} = $sapp_installed;
  $phosting->getChild( 'applications', 1 )->addChild($sapp_installed);
}

sub addSubDomainSapp {
  my ( $self, $subDomainId, $sappId, $sapp, $licenseType ) = @_;
  my $root = $self->{subDomainNodes}->{$subDomainId};

  my $sapp_installed = $self->makeSappNode( $sappId, $sapp, $licenseType );

  $self->{sappNodes}->{$sappId} = $sapp_installed;
  $root->getChild( 'applications', 1 )->addChild($sapp_installed);
}

sub setSappParams {
  my ( $self, $sappId, $sapp ) = @_;

  my $paramsPtr = $sapp->getParams();

  my %params = %{$paramsPtr};

  my $sapp_installed = $self->{sappNodes}->{$sappId};

  while ( my ( $param_name, $param_value ) = each %params )
  {
    my $name;
    my $value;
    if ( $sapp->encodedParams() ) {
      $name = $param_name;
      $value = $param_value;
    }
    else {
      $name = $self->{base64}->{'ENCODE'}->($param_name);
      $value = $self->{base64}->{'ENCODE'}->($param_value);
    }

    $sapp_installed->addChild(
      XmlNode->new(
        'sapp-param',
        'children' => [
          XmlNode->new(
            'sapp-param-name',
            'attributes' => { 'encoding' => 'base64' },
            'content'    => $name
          ),
          XmlNode->new(
            'sapp-param-value',
            'attributes' => { 'encoding' => 'base64' },
            'content'    => $value
          )
        ]
      )
    );
  }
}

sub setSappSettings {
  my ( $self, $sappId, $sapp ) = @_;

  my $settingsPtr = $sapp->getSettings();

  if (scalar keys %{$settingsPtr} > 0) {

    my %settings = %{$settingsPtr};

    my $sappInstalledNode = $self->{sappNodes}->{$sappId};

    my $sappSettingsNode = XmlNode->new('sapp-settings');

    while ( my ( $setting_name, $setting_value ) = each %settings )
    {
      $sappSettingsNode->addChild(
        XmlNode->new(
          'setting',
          'children' => [
            XmlNode->new(
              'name',
              'content'    => $setting_name
            ),
            XmlNode->new(
              'value',
              'content'    => $setting_value
            )
          ]
        )
      );
    }
    $sappInstalledNode->addChild($sappSettingsNode);
  }
}

sub setSappEntryPoints {
  my ( $self, $sappId, $sapp ) = @_;

  my $sapp_installed = $self->{sappNodes}->{$sappId};

  my @ep = $sapp->getEntryPoints();

  foreach my $node (@ep) {
    my $epNode = XmlNode->new('sapp-entry-point');
    $epNode->addChild(XmlNode->new('label', 'content' => $node->{'label'}));
    $epNode->addChild(XmlNode->new('description', 'content' => $node->{'description'}));
    $epNode->addChild(XmlNode->new('hidden', 'content' => $node->{'hidden'}));
    $epNode->addChild(XmlNode->new('http', 'content' => $node->{'http'}));
    $epNode->addChild(XmlNode->new('icon', 'content' => $node->{'icon'})) if defined $node->{'icon'};

    my $permissionsNode = XmlNode->new('limits-and-permissions');
    $permissionsNode->addChild(
      XmlNode->new('permission', 'attributes' => {'name' => $node->{'permission-name'} , 'class' => $node->{'permission-class'}})
    );
    $epNode->addChild($permissionsNode);
    $sapp_installed->addChild($epNode);
  }
}

sub setDomainSappBackupArchiveContent {
  my ( $self, $domainId, $sappId, $path, $include, $webspaceId ) = @_;
  my $sapp_installed = $self->{sappNodes}->{$sappId};
  my $cid = $self->{content_transport}->addPhostingContent( 'sapp-apsc', $domainId, $webspaceId,
    "sapp-apsc." . $sappId,
    "directory"    => $path,
    "include"      => [$include]
  );
  $sapp_installed->getChild( 'content', 1, 1 )->addChild( $cid ) if $cid;
}

sub setSubDomainSappBackupArchiveContent {
  my ( $self, $subdomainId, $sappId, $path, $include, $webspaceId ) = @_;

  my $sapp_installed = $self->{sappNodes}->{$sappId};

  my $cid = $self->{content_transport}->addSubDomainPhostingContent( 'sapp-apsc', $subdomainId, $webspaceId,
    "sapp-apsc." . $sappId,
    "directory"    => $path,
    "include"      => [$include]
  );
  $sapp_installed->getChild( 'content', 1, 1 )->addChild( $cid ) if $cid;
}


sub addDomainSappDatabase {
  my ( $self, $dbId, $dbServerId, $domainId, $sappId, $dbName, $dbType, $optionalPtr, $dbServerPtr, $dbUsersPtr, $contentDescriptionPtr, $skipContent ) = @_;

  $self->regDatabaseObjectBackupPath( $self->getDomainsBackupPath( $domainId ), $dbId, $dbName, $dbServerId );
  my $parent = $self->{sappNodes}->{$sappId};

  my $domain = $self->getCashedDomainNode($domainId);

  my $node = $self->makeDatabaseNode( $dbId, $dbName, $dbType, 'domain', $domainId, $optionalPtr,
                                      $dbServerPtr, $dbUsersPtr, $contentDescriptionPtr, $skipContent );
  my $domainGuid = $domain->getAttribute('guid');
  $node->setAttribute( 'guid', $self->getDatabaseGuid( $domainGuid, $dbId ) ) if $domainGuid;
  $node->setAttribute( 'owner-guid', $domainGuid ) if $domainGuid;
  $node->setAttribute( 'aps-registry-id', $optionalPtr->{'aps-registry-id'}) if $optionalPtr->{'aps-registry-id'};

  $parent->addChild($node);
}

sub addSubDomainSappDatabase {
  my ( $self, $dbId, $dbServerId, $subdomainId, $sappId, $dbName, $dbType, $optionalPtr, $dbServerPtr, $dbUsersPtr, $contentDescriptionPtr, $skipContent ) = @_;

  $self->regDatabaseObjectBackupPath( $self->getSubdomainsBackupPath( $subdomainId ), $dbId, $dbName, $dbServerId );
  my $subdomain = $self->{subDomainNodes}->{$subdomainId};

  my $parent = $self->{sappNodes}->{$sappId};

  my $node = $self->makeDatabaseNode( $dbId, $dbName, $dbType, 'subdomain', $subdomainId, $optionalPtr,
                                      $dbServerPtr, $dbUsersPtr, $contentDescriptionPtr, $skipContent );
  my $subDomainGuid = $subdomain->getAttribute('guid');
  $node->setAttribute( 'guid', $self->getDatabaseGuid( $subDomainGuid, $dbId ) ) if $subDomainGuid;
  $node->setAttribute( 'owner-guid', $subDomainGuid ) if $subDomainGuid;
  $node->setAttribute( 'aps-registry-id', $optionalPtr->{'aps-registry-id'}) if $optionalPtr->{'aps-registry-id'};

  $parent->addChild($node);
}

sub addDomainSappCustomButton {
    my $self = shift;
    $self->addSappCustomButton( 'domains', @_ );
}

sub addSubDomainSappCustomButton {
    my $self = shift;
    $self->addSappCustomButton( 'subdom', @_ );
}

sub addSappCustomButton {
    my ( $self, $parentType, $parentid, $sappId, $id, $optionsPtr, $customButtonsDir, $icon ) = @_;

    my $parent = $self->{sappNodes}->{$sappId};
    if ( !defined($parent) ) {
      Logging::warning('Unable to dump site applications custom buttons settings, because of dump XML structure is not full', 'PackerStructure');
      return;
    }

    my $node = $self->makeCustomButtonNode( $parentType, $parentid, $id, $optionsPtr, $customButtonsDir, $icon );

    $parent->addChild($node);
}

sub setSappApscNode {
  my ( $self, $sappId, $content) = @_;

  my $parent = $self->{sappNodes}->{$sappId};

  my $sappApscNode = XmlNode->new('sapp-apsc', 'content' => $self->{base64}->{'ENCODE'}->($content));

  $parent->addChild($sappApscNode);
}
####### End Siteapps functions #######

sub insertLimitNode {
    my ( $self, $node, $name, $value ) = @_;
    my $limits = $node->getChild( 'limits-and-permissions', 1 );
    $limits->addChild( XmlNode->new( "limit",
        "attributes" => { "name" => $name },
        "content"    => $value ) );
}

sub makePermissionNode {
  my ($self, $parent, $name, $allowed) = @_;

  #[Bug 111477] Value "-1" for attribute allowed of permission is not among the enumerated set
  if ($allowed =~ /-1/) {
   	$allowed = 'false';
  }
  $parent->addChild(  XmlNode->new('permission', 'attributes' => { 'name' => $name, 'value' => $allowed}) );
}

###### Backup content structure ######

sub getPhostingGuid{
   my ( $self, $ownerguid, $id ) = @_;
   return "$ownerguid\_ph_$id";
}

sub getDatabaseGuid{
   my ( $self, $ownerguid, $id ) = @_;
   return "$ownerguid\_db_$id";
}

sub getSubdomainGuid{
   my ( $self, $ownerguid, $id ) = @_;
   return "$ownerguid\_sb_$id";
}

sub getMailnameGuid{
   my ( $self, $ownerguid, $id ) = @_;
   return "$ownerguid\_mn_$id";
}

sub regObjectBackupPath{
 my( $self, $basePath, $group, $key, $objectId, $parentId ) = @_;
 $basePath .= '/' if $basePath;
 my $path = "$basePath$group";
 $path = $basePath if $group eq 'admin';
 $self->{backupPath}->{"$group\_$key"} = $path;
 $self->{objectId}->{"$group\_$key"} = $objectId;
 $self->{parentId}->{"$group\_$key"} = $parentId;
 return $self->getBackupPath( $group, $key, undef, 0 );
}

sub regAdminObjectBackupPath{
 my( $self, $basePath ) = @_;
 return $self->regObjectBackupPath( $basePath, 'admin', 'admin', ''  );
}

sub regResellersObjectBackupPath{
 my( $self, $basePath, $key, $resellerLogin ) = @_;
 return $self->regObjectBackupPath( $basePath, 'resellers', $key, $self->{fnamecreator}->normalize_short_string( $resellerLogin, $key ) );
}

sub regClientObjectBackupPath{
 my( $self, $basePath, $key, $clientLogin ) = @_;
 return $self->regObjectBackupPath( $basePath, 'clients', $key, $self->{fnamecreator}->normalize_short_string( $clientLogin, $key ) );
}

sub regDomainObjectBackupPath{
 my( $self, $basePath, $key, $domainAscName ) = @_;
 return $self->regObjectBackupPath( $basePath, 'domains', $key, $self->{fnamecreator}->normalize_long_string( $domainAscName, $key ) );
}

sub regSiteObjectBackupPath{
 my( $self, $basePath, $key, $siteName ) = @_;
 return $self->regObjectBackupPath( $basePath, 'sites', $key, $self->{fnamecreator}->normalize_long_string( $siteName, $key ) );
}

sub regMailnameObjectBackupPath{
 my( $self, $basePath, $key, $mailName, $domainId ) = @_;
 return $self->regObjectBackupPath( $basePath, 'mailnames', $key, $self->{fnamecreator}->normalize_short_string( $mailName, $key ), $domainId );
}

sub regDatabaseObjectBackupPath{
 my( $self, $basePath, $key, $databaseName, $dbServerId ) = @_;
 return $self->regObjectBackupPath( $basePath, 'databases', $key, $self->{fnamecreator}->normalize_short_string( "$databaseName\_$dbServerId", $key ) );
}

sub regSubdomainObjectBackupPath{
 my( $self, $basePath, $key, $subdomainName, $domainId ) = @_;
 return $self->regObjectBackupPath( $basePath, 'subdomains', $key, $self->{fnamecreator}->normalize_short_string( $subdomainName, $key ), $domainId );
}

sub regPhostingObjectBackupPath{
 my( $self, $basePath, $key ) = @_;
 return $self->regObjectBackupPath( $basePath, 'phosting', $key, '' );
}

sub getObjectId{
 my( $self, $group, $key ) = @_;
 $key = "$group\_$key";
 return 'phosting' if $group eq 'phosting';
 return 'admin' if $group eq 'admin';
 return $self->{objectId}->{$key};
}

sub getParentId{
 my( $self, $group, $key ) = @_;
 $key = "$group\_$key";
 return $self->{parentId}->{$key}  if exists $self->{parentId} && exists $self->{parentId}->{$key};
 return;
}

sub getPhostingParentId{
 my( $self, $key ) = @_;
 return $key;
}

sub getSubdomainParentId{
 my( $self, $key ) = @_;
 return $self->getParentId( "subdomains", $key );
}

sub getMailNameParentId{
 my( $self, $key ) = @_;
 return $self->getParentId( "mailnames", $key );
}

sub getBackupPath{
 my( $self, $group, $key, $fileName, $onlyFileName ) = @_;
 $key = "$group\_$key";
 my $res = '';
 if( not $onlyFileName ) {
     $res .=  $self->{backupPath}->{$key};
     $res .= '/' . $self->{objectId}->{$key} if $group ne 'phosting' and $group ne 'admin';
 }
 $res = $self->{fnamecreator}->getFileName( $res, $self->{backupname}, $self->{objectId}->{$key}, $fileName ) if defined $fileName;
 return $res;
}

sub getAdminBackupPath{
 my( $self, $fileName, $onlyFileName ) = @_;
 return $self->getBackupPath( 'admin', 'admin', $fileName, $onlyFileName );
}

sub getResellersBackupPath{
 my( $self, $key, $fileName, $onlyFileName ) = @_;
 return $self->getBackupPath( 'resellers', $key, $fileName, $onlyFileName );
}

sub getClientsBackupPath{
 my $self = shift;
 my $key = shift;
 return $self->getBackupPath( 'clients', $key, @_ ) if exists $self->{objectId}->{"clients_$key"};
 return $self->getResellersBackupPath( $key, @_ );
}

sub getDomainsBackupPath{
 my( $self, $key, $fileName, $onlyFileName ) = @_;
 if ( defined $self->{domainNodes}->{$key} ) {
    return $self->getBackupPath( 'domains', $key, $fileName, $onlyFileName );
 }
 return $self->getBackupPath( 'sites', $key, $fileName, $onlyFileName );
}

sub getSitesBackupPath{
 my $self = shift;
 return $self->getBackupPath( 'sites', @_ );
}

sub getPhostingBackupPath{
 my $self = shift;
 return $self->getBackupPath( 'phosting', @_ );
}

sub getDatabasesBackupPath{
 my $self = shift;
 return $self->getBackupPath( 'databases', @_ );
}

sub getMailnamesBackupPath{
 my $self = shift;
 return $self->getBackupPath( 'mailnames', @_ );
}

sub getSubdomainsBackupPath{
 my $self = shift;
 return $self->getBackupPath( 'subdomains', @_ );
}

sub getAdminObjectId{
 my( $self ) = @_;
 return $self->getObjectId( 'admin', 'admin' );
}

sub getResellerObjectId{
 my( $self, $key ) = @_;
 return $self->getObjectId( 'resellers', $key );
}

sub getClientObjectId{
 my( $self, $key ) = @_;
 return $self->getResellerObjectId( $key ) if exists $self->{objectId}->{"resellers_$key"};
 return $self->getObjectId( 'clients', $key );
}

sub getDomainObjectId{
 my( $self, $key ) = @_;
 return $self->getObjectId( 'domains', $key );
}

sub getPhostingObjectId{
 my( $self, $key ) = @_;
 return $self->getObjectId( 'phosting', $key );
}

sub getDatabaseObjectId{
 my( $self, $key ) = @_;
 return $self->getObjectId( 'databases', $key );
}

sub getMailnameObjectId{
 my( $self, $key ) = @_;
 return $self->getObjectId( 'mailnames', $key );
}

sub getSubdomainObjectId{
 my( $self, $key ) = @_;
 return $self->getObjectId( 'subdomains', $key );
}
###### End Backup content structure ######

sub addTemplateItem {
    my ( $self, $node, $name, $value ) = @_;
    $node->addChild( XmlNode->new( "template-item",
        "attributes" => { "name" => $name },
        "content"    => $value ) );
}

sub makeTemplatePlanItem {
  my ( $self, $name, $value ) = @_;

  my $templatePlanItemNode = XmlNode->new( "template-plan-item",
                                           "attributes" => { "name" => $name },
                                           "content"    => $value );
  return $templatePlanItemNode;
}

sub addTemplatePlanItem {
  my ( $self, $node, $name, $value ) = @_;

  my $templatePlanItemNode = $self->makeTemplatePlanItem( $name, $value );
  $node->addChild( $templatePlanItemNode ) if defined $templatePlanItemNode;
}

sub makeLogrotationNode {
  my ( $self, $parent, $logRotationPtr ) = @_;

  my %logRotation = %{$logRotationPtr};

  my $item = XmlNode->new( 'logrotation', 'attributes' => {
        'max-number-of-logfiles' => $logRotation{'max_number_of_logfiles'},
        'compress'               => $logRotation{'compress_enable'}
    } );

  $item->setAttribute( 'email', $logRotation{'email'} ) unless $logRotation{'email'} eq '';
  $item->setAttribute( 'enabled', $logRotation{'turned_on'} );

  my $rottype;
  if ( $logRotation{'period_type'} eq 'by_time' ) {
    $rottype = XmlNode->new( 'logrotation-period',
                             'attributes' => { 'period' => $logRotation{'period'} } );
  }
  else {
    $rottype = XmlNode->new( 'logrotation-maxsize',
                             'content' => $logRotation{'period'} );
  }
  $item->addChild($rottype);
  $parent->addChild($item);
}

sub makeDatabaseNode {
    my ( $self, $dbId, $dbName, $dbType, $parent, $parentId, $optionalPtr, $dbServerPtr, $dbUsersPtr, $contentDescriptionPtr, $skipContent ) = @_;

    my %optional = %{$optionalPtr};
    my %dbServer = %{$dbServerPtr};
    my @dbUsers = @{$dbUsersPtr};
    my %contentDescription = %{$contentDescriptionPtr};

    my $root = XmlNode->new( 'database', 'attributes' => {
        'name' => $dbName,
        'type' => $dbType,
        'id' => $dbId
        } );

    $root->setAttribute( 'version', $optional{'version'} )
      if $optional{'version'};
    $root->setAttribute( 'charset', $optional{'charset'} )
      if $optional{'charset'};
    $root->setAttribute( 'version', $optional{'version'} )
      if $optional{'version'};
    $root->setAttribute( 'db-existent', $optional{'db-existent'} )
      if $optional{'db-existent'};
    $root->setAttribute( 'prefix', $optional{'prefix'} )
      if $optional{'prefix'};
    $root->setAttribute( 'collation', $optional{'collation'} )
      if $optional{'collation'};
    $root->setAttribute( 'external-id', $optional{'external_id'} )
      if defined $optional{'external_id'} and $optional{'external_id'} ne '';
    $root->setAttribute( 'custom-host', $optional{'custom-host'} )
      if (defined($optional{'custom-host'}));

    if (%dbServer) {
      my $dbServerNode = XmlNode->new('db-server');
      $dbServerNode->setAttribute( 'type', $dbServer{'type'} );
      $dbServerNode->addChild( XmlNode->new( 'host', 'content' => $dbServer{'host'} ) );
      $dbServerNode->addChild( XmlNode->new( 'port', 'content' => $dbServer{'port'} ) );

      $root->addChild($dbServerNode);
    }

    $self->makeDatabaseUsersNodes($root, \@dbUsers, $dbId);

    if (defined($optional{'related-sites'})) {
      $self->makeRelatedSitesNode($root, $optional{'related-sites'});
    }

    if (%contentDescription and !$skipContent) {
      if (exists $self->{databaseCids}{$dbId}) {
        $root->getChild('content', 1, 1)->addChild($self->{databaseCids}{$dbId});
      } else {

        my $cid;
        if ($parent eq 'subdomain') {
          $cid = $self->{content_transport}->addSubdomainDbContent('sqldump', $parentId, $dbId, %contentDescription);
        }
        else {
          $cid = $self->{content_transport}->addDbContent('sqldump', $dbId, %contentDescription);
        }
        if ($cid) {
          $root->getChild('content', 1, 1)->addChild($cid);
          $self->{stat}{dbSizeDumped} += $self->_getContentSizeFromCidNode($cid);
          $self->{databaseCids}->{$dbId} = $cid;
        }
      }
    }

    return $root;
}

sub makeDbUsersToAnyDatabasesNode {
  my ( $self, $domainId, $dbUsersPtr ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $rootNode = $root->getChild( 'databases', 1 );

  my $globalDbUsersNode = XmlNode->new('dbusers');

  $self->makeDatabaseUsersNodes($globalDbUsersNode, $dbUsersPtr);

  $rootNode->addChild($globalDbUsersNode);
}

sub makeDatabaseUsersNodes {
    my ( $self, $parentNode, $dbUsersPtr, $dbId ) = @_;
    $dbId ||= 0;

    my @dbUsers = @{$dbUsersPtr};

    my $item;
    foreach my $user (@dbUsers) {
      my $dbUserRoot = XmlNode->new( 'dbuser',
        'attributes' => { 'name' => $user->{'login'}, 'id' => $user->{'id'} } );
 
      $dbUserRoot->setAttribute( 'default', ( defined( $user->{'default'} ) ) ? 'true' : 'false' );

      $dbUserRoot->setAttribute('aps-registry-id', $user->{'aps-registry-id'}) if $user->{'aps-registry-id'};
      $dbUserRoot->setAttribute('db-user-existent', $user->{'db-user-existent'}) if $user->{'db-user-existent'};

      $dbUserRoot->setAttribute('external-id', $user->{'external_id'}) if defined $user->{'external_id'} and $user->{'external_id'} ne '';

      $item = CommonPacker::makePasswordNode( $user->{'password'}, CommonPacker::normalizePasswordType( $user->{'type'} ) );

      $dbUserRoot->addChild($item);

      if ($user->{'host'}) {
        my %dbServer;
        $dbServer{'host'} = $user->{'host'};
        $dbServer{'port'} = $user->{'port'};
        $dbServer{'type'} = $user->{'dbservertype'};

        my $dbServerNode = $self->makeDbServerNodeWithoutCredentials(\%dbServer);
        $dbUserRoot->addChild($dbServerNode);
      }
      my $databaseUserRemoteAccessRulesNode = $self->makeDatabaseUserRemoteAccessRulesNode($user, $dbId);
      if ($databaseUserRemoteAccessRulesNode) {
          $dbUserRoot->addChild($databaseUserRemoteAccessRulesNode);
      }

      if ( exists $user->{'acl'} ) {
        my $aclNode = XmlNode->new( 'acl' );
        foreach my $acl ( @{$user->{'acl'}} ) {
          $aclNode->addChild( XmlNode->new( 'allowed-host', 'content' => $acl ) );
        }
        $dbUserRoot->addChild( $aclNode );
      }

      if (exists($user->{'privileges'})) {
        my $privilegesNode = XmlNode->new('privileges');
        foreach my $privilege (@{$user->{'privileges'}}) {
          $privilegesNode->addChild(XmlNode->new('privilege', 'attributes' =>  $privilege));
        }
        $dbUserRoot->addChild($privilegesNode);
      }

      $parentNode->addChild($dbUserRoot);
    }
}

sub makeRelatedSitesNode {
    my ($self, $parentNode, $relatedSites) = @_;

    my $relatedSitesNode = XmlNode->new('related-sites');
    foreach my $relatedSiteName (@{$relatedSites}) {
      my $relatedSiteNode = XmlNode->new('related-site');
      $relatedSiteNode->setAttribute('name', $relatedSiteName);
      $relatedSitesNode->addChild($relatedSiteNode);
    }
    $parentNode->addChild($relatedSitesNode);
}

sub makeAutoresponderNode {
    my ( $self, $enabled, $autoPtr, $mailName ) = @_;

    my %auto = %{$autoPtr};

    my $root = XmlNode->new('autoresponder');

    my $value = $self->{base64}->{'ENCODE'}->( $auto{'text'} );

    my $item = XmlNode->new( 'text', 'content' => $value );
    if ( exists $auto{'charset'} && $auto{'charset'} ne '' ) {
      $item->setAttribute( 'charset', $auto{'charset'} );
    }
    $root->addChild($item);

    if ( exists $auto{'content_type'} && $auto{'content_type'} ) {
      $root->setAttribute( 'content-type', $auto{'content_type'} );
    }

    $root->setAttribute( 'status', ($enabled eq 'true' && $auto{'resp_on'} eq 'true') ? 'on' : 'off' );

    if ( $auto{'subject'} ) {
      $value = $self->{base64}->{'ENCODE'}->( $auto{'subject'} );
      chomp $value;
      $root->setAttribute( 'subject', $value );
    }

    #
    # forward
    #
    if ( $auto{'redirect'} ) {
      $root->setAttribute( 'redirect', $auto{'redirect'} );
    }

    #
    # end forward
    #

    if (exists($auto{'ans_freq'})) {
      $root->addChild( XmlNode->new( 'autoresponder-limit',
          'content'    => $auto{'ans_freq'},
          'attributes' => { 'name' => 'ans-freq' } ) );
    }
    if (exists($auto{'endDate'})) {
      $root->addChild( XmlNode->new( 'autoresponder-limit',
          'content'    => $auto{'endDate'},
          'attributes' => { 'name' => 'end-date' } ) );
    }

    my @attach = @{$auto{'attach'}};
    if ( @attach ) {
      foreach my $file (@attach) {
        $root->addChild( XmlNode->new( 'attach', 'attributes' => { 'file' => $file } ) );
      }
    }

    return $root;
}

sub parseCustomButtonOptions {
    my ( $node, $options ) = @_;

    #CUSTOM_BUTTON_PUBLIC
    $node->setAttribute( 'visible-to-sublogins', $options & 128 ? 'true' : 'false' );

    #CUSTOM_BUTTON_INTERNAL
    $node->setAttribute( 'open-in-same-frame', $options & 256 ? 'true' : 'false' );

    $node->setAttribute( 'no-frame', $options & 64 ? 'true' : 'false' );

    my %options = (
      "domain-id"           => 1,       #CUSTOM_BUTTON_DOM_ID
      "domain-name"         => 32,      #CUSTOM_BUTTON_DOM_NAME
      "ftp-login"           => 512,     #CUSTOM_BUTTON_FTP_USER
      "ftp-password"        => 1024,    #CUSTOM_BUTTON_FTP_PASS
      "client-id"           => 2,       #CUSTOM_BUTTON_CL_ID
      "client-company-name" => 4,       #CUSTOM_BUTTON_CNAME
      "client-contact-name" => 8,       #CUSTOM_BUTTON_PNAME
      "client-email"        => 16,      #CUSTOM_BUTTON_EMAIL
      );

    while ( my ( $optname, $optmask ) = each %options ) {
      if ( $options & $optmask ) {
        $node->addChild(
          XmlNode->new( "url-option", "attributes" => { "name" => $optname } )
        );
      }
    }
}

sub addAdminContentProxy{
    my $self = shift;
    $self->{content_transport}->addAdminContent(@_);
}

sub addClientContentProxy{
    my $self = shift;
    $self->{content_transport}->addClientContent(@_);
}

sub addDomainContentProxy{
    my $self = shift;
    $self->{content_transport}->addDomainContent(@_);
}

sub addMailnameContentProxy{
    my $self = shift;
    $self->{content_transport}->addMailnameContent(@_);
}

sub addCustomButtonNode {
  my ( $self, $optionsPtr) = @_;

  my %options = %{$optionsPtr};

  my $node = XmlNode->new('custom-button');

  my %attributes = (
    'url'      => 'url',
    'text'     => 'text',
    'sort_key' => 'sort-priority',
    'place'    => 'interface-place',
    'conhelp'  => 'conhelp' );

  foreach my $key (keys %attributes) {
    $node->setAttribute( $attributes{$key}, $options{$key} ) if defined $options{$key};
  }

  return $node;

}

sub makeCustomButtonNode65 {
  my ( $self, $optionsPtr ) = @_;
  my $node = $self->addCustomButtonNode($optionsPtr);

  ## 128 == CUSTOM_BUTTON_PUBLIC
  parseCustomButtonOptions( $node, $optionsPtr->{'options'} | 128 );

  return $node;

}

sub makeCustomButtonNode {
  my ( $self, $parentType, $parentId, $id, $optionsPtr, $customButtonsDir, $icon ) = @_;

  my $node = $self->addCustomButtonNode($optionsPtr);
  my %options = %{$optionsPtr};

  if ( -e $customButtonsDir . "/" . $icon ) {
    if ($icon) {
      my $proc;
      if( $parentType eq 'admin' ) { $proc = \&addAdminContentProxy; }
      elsif( $parentType eq 'clients' ) { $proc = \&addClientContentProxy; }
      elsif( $parentType eq 'domains' ) { $proc = \&addDomainContentProxy; }
      elsif( $parentType eq 'mails' ) { $proc = \&addMailnameContentProxy; }
      else{
        Logging::warning( "Error: makeCustomButtonNode: unsupported parent type '$parentType' ",'assert');
      }
      if( $proc ) {
        my $cid_icon = $self->$proc(
            'icon',
            $parentId,
            $icon . "." . $id,
            "directory" => $customButtonsDir,
            "include"   => [$icon]
        );

        $node->getChild( 'content', 1, 1 )->addChild( $cid_icon ) if $cid_icon;
      }
    }
  }
  parseCustomButtonOptions( $node, $options{'options'} );

  # exists $options{'plan_item_name'}   <=>   (PleskVersion::atLeast( 10, 1, 0))
  if ( defined $options{'plan_item_name'} && ( '' ne $options{'plan_item_name'} ) ) {
    my $templatePlanItemNode = $self->makeTemplatePlanItem( $options{'plan_item_name'}, '' );
    $node->addChild( $templatePlanItemNode );
  }

  return $node;
}

sub makeDnsZoneParam {
  my ( $self, $name, $db_unit, $db_value ) = @_;

  my $dnsZoneParam =
    XmlNode->new( 'dns-zone-param',
    'attributes' => { 'name' => $name, 'value' => $db_value } );

  my %units = (
    'second'  => 1,
    'minutes' => 60,
    'hours'   => 3600,
    'days'    => 86400,
    'weeks'   => 604800
  );
  my $string_unit;

  foreach my $quant ( keys %units ) {
    if ( $units{$quant} == $db_unit ) {
      $string_unit = $quant;
      last;
    }
  }
  $dnsZoneParam->setAttribute( 'unit', $string_unit );

  return $dnsZoneParam;
}

sub makeDnsZone {
  my ( $self, $parent, $paramsPtr, $recordsPtr, $syncWithParent ) = @_;

  my %params = %{$paramsPtr};
  my @records = @{$recordsPtr};

  my $email;
  if ( defined($params{'email'}) and (length( $params{'email'} ) != 0) ) {
    $email = $params{'email'};
  }
  else {

    # should assume some default email or something...
    $email = 'root@localhost.localdomain';
  }

  my $dnsZone = XmlNode->new( 'dns-zone',
    'attributes' => {
        'email'         => $email,
        'type'          => $params{'type'},
        'rnameType'     => $params{'rnameType'},
        'serial-format' => $params{'serial_format'}
    },
    'children' => [ Status::make( $params{'status'} ) ] );

  if ( defined $syncWithParent ) {
    $dnsZone->setAttribute( 'sync-with-parent', $syncWithParent );
  }

  $dnsZone->setAttribute('external-id', $params{'external_id'}) if defined $params{'external_id'} and $params{'external_id'} ne '';

  foreach my $zoneParam ( 'ttl', 'refresh', 'retry', 'expire', 'minimum' ) {
    $dnsZone->addChild(
      $self->makeDnsZoneParam(
        $zoneParam, $params{ $zoneParam . '_unit' },
        $params{$zoneParam}
      )
    );
  }

  # dns records
  for my $hash (@records) {
    my $dnsrec = $self->makeDnsRecord( $hash );
    if ($dnsrec) {
      $dnsZone->addChild($dnsrec);
    }
  }

  $parent->addChild($dnsZone);
}

sub makeDnsRecord {
  my ( $self, $hashPtr ) = @_;

  unless (ref($hashPtr) =~ /HASH/)
  {
    Logging::warning('Error: makeDnsRecord: hashPtr is not hash ptr', 'assert');
    return;
  }
  my %hash = %{$hashPtr};

  return unless $hash{'type'} =~ /^A|AAAA|NS|MX|CNAME|PTR|TXT|master|SRV|AXFR|DS|CAA$/;

  my $host =
    defined $hash{'displayHost'}
    ? $hash{'displayHost'}
    : $hash{'host'};
  my $val =
    defined $hash{'displayVal'}
    ? $hash{'displayVal'}
    : $hash{'val'};

  if ( $hash{'type'} eq 'SRV' ) {
    $host =~ s/\.$//;
    $val  =~ s/\.$//;
  }

  my ($item) =
    XmlNode->new( 'dnsrec',
    'attributes' => { 'type' => $hash{'type'}, 'src' => $host } );
  $item->setAttribute( 'dst', $val ) if ($val);
  $item->setAttribute( 'opt', $hash{'opt'} ) if ( defined $hash{'opt'} );
  $item->setAttribute( 'status', $hash{'status'} ) if (defined $hash{'status'});
  $item->setAttribute('external-id', $hash{'external_id'}) if defined $hash{'external_id'} and $hash{'external_id'} ne '';

  return $item;
}

#
# Creates node representing pre-8.0 DNS master record
#

sub makeOldMasterRec {
  my ($ipAddress) = @_;

  return XmlNode->new(
    "dnsrec",
    "attributes" => {
      "src"  => $ipAddress,
      "type" => "master"
    }
  );
}

sub addUrlDecodedTextNode {
  my ( $parent, $name, $value ) = @_;

  $parent->addChild( XmlNode->new( $name, "content" => HelpFuncs::urlDecode($value) ) )
    if $value;
}

sub makeSysUser {
  my ( $self, $sysuserPtr ) = @_;

  my %sysuser = %{$sysuserPtr};

  #
  # attributes
  #

  my $root =
    XmlNode->new( 'sysuser',
    'attributes' => { 'name' => lc( $sysuser{'login'} ) } );
  $root->setAttribute( 'shell', $sysuser{'shell'} ) if $sysuser{'shell'} and $sysuser{'shell'} ne '/bin/false';

  my $quota;
  if ( $sysuser{'quota'} ) {
    $quota = $sysuser{'quota'};
    $root->setAttribute( 'quota', $quota ) if $quota;
  }

  if ( $sysuser{'home'} ) {
    my $home = $sysuser{'home'};
    if (exists $sysuser{'relative_path'}) {
      if (substr($home,0, length($sysuser{'relative_path'})) eq $sysuser{'relative_path'} ) {
        $home = substr($home, length($sysuser{'relative_path'}));
      }
    }
    $root->setAttribute( 'home', $home) if $home;
  }

  #
  # end attributes
  #

  $root->addChild( CommonPacker::makePasswordNode( $sysuser{'passwd'}, $sysuser{'passwdType'} ) );

  if (defined($sysuser{'scheduled-tasks'})) {
    $self->addEmbeddedInfo($root, 'scheduled-tasks', $sysuser{'scheduled-tasks'});
  } elsif ( $sysuser{'cron'} ) {
    my $cronNode = XmlNode->new( 'cron', 'content' => $sysuser{'cron'} );
    if ( $sysuser{'cron-encoding'} ) {
      $cronNode->setAttribute( 'encoding', $sysuser{'cron-encoding'} );
    }
    $root->addChild( $cronNode );
  }

  return $root;
}

sub makeSappNode {

  my ( $self, $sappId, $sapp, $licenseType ) = @_;

  my $name = $sapp->getName();
  my $version = $sapp->getVersion();
  my $release = $sapp->getRelease();
  my $description = $sapp->getDescription();
  my $isCommercial = $sapp->isCommercial();
  my $isIntegrated = $sapp->isIntegrated();
  my $prefix = $sapp->getInstallPrefix();
  my $isSsl = $sapp->isSsl();
  my $packageId = $sapp->getSappPackageId();

  my $sapp_installed = XmlNode->new('sapp-installed');

  #-----------------------------------------------------------------
  # sapp-spec
  #-----------------------------------------------------------------
  my $sapp_spec = XmlNode->new('sapp-spec');
  if ( $packageId ) {
    $sapp_spec->addChild(
      XmlNode->new( 'sapp-package-id', 'content' => $packageId ) );
  }

  $sapp_spec->addChild( XmlNode->new( 'sapp-name', 'content' => $name ) );
  if ( $version ) {
    $sapp_spec->addChild(
      XmlNode->new( 'sapp-version', 'content' => $version ) );
  }
  if ( $release ) {
    $sapp_spec->addChild(
      XmlNode->new( 'sapp-release', 'content' => $release ) );
  }
  if ( $description ) {
    my $desc = $self->{base64}->{'ENCODE'}->( $description );
    $sapp_spec->addChild(
      XmlNode->new(
        'sapp-description',
        'attributes' => { 'encoding' => 'base64' },
        'content'    => $desc
      )
    );
  }
  if ( $isCommercial ) {
    $sapp_spec->addChild( XmlNode->new('sapp-commercial') );
  }
  if ( $isIntegrated ) {
    $sapp_spec->addChild( XmlNode->new('sapp-integrated') );
  }

  $sapp_installed->addChild($sapp_spec);

  if ( defined($licenseType) ) {
    $sapp_installed->addChild(
      XmlNode->new(
        'sapp-installed-license-type',
        'content' => $licenseType
      )
    );
  }

  return $sapp_installed;
}

sub makeSiteAppInstalled{
  my ( $self, $sappId, $prefix, $isSsl, $apsRegistryId ) = @_;

  my $sapp_installed = $self->{sappNodes}->{$sappId};

  # sapp-installdir
  my $sapp_installdir =
    XmlNode->new( 'sapp-installdir',
    'children' => [ XmlNode->new( 'sapp-prefix', 'content' => $prefix ) ] );
  if ( $isSsl ) {
    $sapp_installdir->addChild( XmlNode->new('sapp-ssl') );
  }
  if ($apsRegistryId) {
    $sapp_installdir->addChild( XmlNode->new( 'aps-registry-id', 'content' => $apsRegistryId ));
  }
  $sapp_installed->addChild($sapp_installdir);
}

sub setSappApsControllerInfo {
  my ( $self, $sappId, $sapp ) = @_;
  my $sapp_installed = $self->{sappNodes}->{$sappId};

  if ( $sapp->getContext() ) {
    $sapp_installed->addChild( XmlNode->new( 'context', 'attributes' => { "type" =>  $sapp->getContext() } ) );
  }

  if ( $sapp->getApplicationApsRegistryId() ) {
    $sapp_installed->addChild( XmlNode->new( 'aps-registry-id', 'content' => $sapp->getApplicationApsRegistryId() ) );
  }
}

sub setSappApsLicense {
  my ( $self, $sappId, $sapp ) = @_;

  my $sapp_installed = $self->{sappNodes}->{$sappId};

  if ( $sapp->isContainLicense() ) {
    my $licenseNode = XmlNode->new('sapp-license');
    $licenseNode->addChild( XmlNode->new('aps-registry-id', 'content' => $sapp->getLicenseApsRegistryId())) if $sapp->getLicenseApsRegistryId();
    $licenseNode->addChild( XmlNode->new('license-type', 'content' => $sapp->getLicenseType()));
    $licenseNode->addChild( XmlNode->new('activation-code', 'content' => $sapp->getActivationCode()));
    $licenseNode->addChild( XmlNode->new('use-stub', 'content' => $sapp->getUseStub()));
    $sapp_installed->addChild($licenseNode);
  }
}

sub makeAnonftpPermissionNode {
  my ( $parent, $name ) = @_;

  $parent->addChild(
    XmlNode->new( 'anonftp-permission', "attributes" => { "name" => $name } ) );
}

sub makeAnonftpLimitNode {
  my ( $parent, $name, $value ) = @_;
  if ( $value != 0 ) {
    $parent->addChild(
      XmlNode->new(
        "anonftp-limit",
        "attributes" => { "name" => $name },
        "content"    => $value
      )
    );
  }
}

sub makeSystemIpNode {
  my ( $self, $parent, $ptrIp ) = @_;
  my $correctMask = HelpFuncs::blockToNum( $ptrIp->{'mask'} );
  my $md5 = PerlMD5->new();
  $md5->add(HelpFuncs::urlDecode($ptrIp->{'pvtkey'}));
  $parent->getChild( 'properties', 1 )->addChild(
    XmlNode->new(
      'system-ip',
      'attributes' => { 'certificate' => $md5->hexdigest() },
      'children'   => [
        XmlNode->new(
          'ip',
          'children' => [
            XmlNode->new( 'ip-type', 'content' => "$ptrIp->{'type'}" ),
            XmlNode->new(
              'ip-address', 'content' => $ptrIp->{'ip_address'}
            )
          ]
        ),
        XmlNode->new( 'ip-netmask',   'content' => "$correctMask" ),
        XmlNode->new( 'ip-interface', 'content' => "$ptrIp->{'iface'}" )
      ]
    )
  );
}

sub makeTemplateNode {
  my ( $self, $nodeName, $templateName, $data ) = @_;

  my ( $templateAttrs, $templatePtr, $planItemsPtr, $apsBundleFilterItemsPtr, $filterType, $phpSettingsPtr, $defaultDbServersPtr );

  $templateAttrs = $data->{'attributes'};
  $templatePtr = $data->{'data'};
  $planItemsPtr = $data->{'items'};
  $apsBundleFilterItemsPtr = $data->{'aps-filter-items'};
  $filterType = $data->{'aps-filter-type'};
  $phpSettingsPtr = $data->{'php-settings'};
  $defaultDbServersPtr = $data->{'default-db-servers'};

  my %logRotation = %{$data->{'log-rotation'}};
  my %ipPool = %{$data->{'ip-pool'}};

  my $node =
    XmlNode->new( $nodeName,
    'attributes' => { 'name' => $templateName } );

  for my $ptrRowAttrs ( @{$templateAttrs} ) {
      my ($attrName, $attrValue ) = @{$ptrRowAttrs};
      $node->setAttribute( $attrName, $attrValue );
  }

  for my $ptrRow ( @{$templatePtr} ) {
      my ( $element, $value ) = @{$ptrRow};
      $self->addTemplateItem( $node, $element, $value );
  }

  while ( my ($itemName, $itemValue) = each ( %{$planItemsPtr} ) ) {
    $self->addTemplatePlanItem( $node, $itemName, $itemValue );
  }

  if (keys %logRotation){
      $self->makeLogrotationNode( $node, \%logRotation );
  }

  if (%ipPool and scalar(keys %ipPool) > 0){
	  my $ipPoolNode = XmlNode->new( 'ip_pool' );

	  for my $ip ( keys %ipPool ) {
        $ipPoolNode->addChild(
	      $self->makeIpNode($ip, $ipPool{$ip} )
	    );
	  }

      $node->addChild($ipPoolNode);
  }

  if (@{$apsBundleFilterItemsPtr}) {
    $self->makeApsBundleFilterNode(undef, undef, $filterType, $apsBundleFilterItemsPtr, $node);
  }

  if (defined $phpSettingsPtr and $nodeName ne 'reseller-template') {
    $node->addChild($self->makePhpSettingsNode($phpSettingsPtr));
  }

  if (defined $defaultDbServersPtr and $nodeName eq 'domain-template') {
    $node->addChild($self->makeDefaultDbServersNode($defaultDbServersPtr));
  }

  return $node;
}

sub makeDomainDefaultDbServersNode {
  my ($self, $domainId, $defaultDbServersPtr) = @_;

  my $parent = $self->getCashedDomainNode($domainId);

  $parent->getChild( 'preferences', 1 )->addChild($self->makeDefaultDbServersNode($defaultDbServersPtr));

}

sub makeDefaultDbServersNode {
  my ($self, $defaultDbServersPtr) = @_;

  my $defaulDbServersNode = XmlNode->new('default-db-servers');

  foreach my $dbServer (@{$defaultDbServersPtr}) {
    $defaulDbServersNode->addChild($self->makeDbServerNodeWithoutCredentials($dbServer));
  }

  return $defaulDbServersNode;
}

sub setDomainPhpSettingsNode {
  my ($self, $domainId, $phpSettingsPtr) = @_;

  my $parent = $self->getCashedDomainNode($domainId);
  return unless defined $parent;

  if (defined $phpSettingsPtr) {
    $parent->addChild($self->makePhpSettingsNode($phpSettingsPtr));
  }
}

sub makePhpSettingsNode {
  my ($self, $phpSettingsPtr) = @_;

  return unless (defined $phpSettingsPtr and (scalar keys %{$phpSettingsPtr} != 0));

  my $node = XmlNode->new('php-settings');

  if (exists $phpSettingsPtr->{'notice'}) {
    $node->addChild(XmlNode->new('notice-text', 'content' => $phpSettingsPtr->{'notice'}));
    delete $phpSettingsPtr->{'notice'};
  }

  foreach my $key (keys %{$phpSettingsPtr}) {
    my $settingNode = XmlNode->new('setting');
    $settingNode->addChild(XmlNode->new('name', 'content' => $key));
    $settingNode->addChild(XmlNode->new('value', 'content' => $phpSettingsPtr->{$key}));
    $node->addChild($settingNode);
  }

  return $node;
}

sub makeIpNode {
  my ($self, $ip, $iptype) = @_;
  return XmlNode->new(
    'ip',
    'children' => [
      XmlNode->new( 'ip-type',    content => $iptype ),
      XmlNode->new( 'ip-address', content => $ip )
    ]
  );
}

sub addServerVirusfilter {
  my ($self, $virusfilter) = @_;

  my $root = $self->{serverNode};

  my $content = defined($virusfilter) ? $virusfilter : 'none';

  my $virusfilterNode = XmlNode->new('virusfilter', 'content' => $content);
  $virusfilterNode->setAttribute( 'state', defined($virusfilter) ? 'inout' : 'none' );

  $root->addChild($virusfilterNode);

  return $virusfilterNode;
}

sub getSb5SitePublished {
  my ($self, $name) = @_;

  my $sb5SitePublishedUtil = AgentConfig::sb5SitePublishedUtil();
  my $cmd = $sb5SitePublishedUtil . " --is-site-builder-site-published " . $name;
  Logging::debug("Exec: $cmd");
  my $result = `$cmd`;
  chomp($result);
  my $retCode = $? >> 8;
  if( $retCode != 0 ) {
    Logging::warning( "Unable to backup Site Builder publishing status on site $name (ErrorCode: $retCode, STDOUT:$result).", 'UtilityError' );
    return;
  }
  return ($result eq 'true')? 'true' : 'false';
}

sub setSb5ServerContent {
  my ($self) = @_;
  my $root = $self->{serverNode};
  my $sb5backupUtil = AgentConfig::sb5BackupUtil();

  unless (defined $sb5backupUtil)
  {
    Logging::debug("Unable to find SiteBuilder backup utility to create server content backup.");
    return;
  }

  my $dstFile = HelpFuncs::mktemp("pmm-sb5-sc-XXXXXXXX");
  my $cmd = "$sb5backupUtil --backup --target=server_settings --log=stdout --file=$dstFile";
  my $result = `$cmd`;
  my $retCode = $? >> 8;

  Logging::debug("Backing up sitebuilder server settings:");
  Logging::debug($cmd);
  Logging::debug($result);

  if( $retCode == 0 ) {
    if( -e "$dstFile.zip") {
      my $cid = $self->{content_transport}->addAdminContent('sb5-server', undef, 'sb_server', "directory" => ".", "include"   => [$dstFile.".zip"]);
      $root->getChild('content', 1, 1)->addChild($cid) if $cid;
    }
    else {
      Logging::warning( "Sitebuilder backup was not created.", 'UtilityError' );
    }
  }
  else {
    Logging::warning( "Return code of sitebuilder bru utility: ".$retCode.". Some errors occured during sitebuilder backup. Please see psadump.log file for more information", 'UtilityError' );
  }
  unlink "$dstFile.zip" if( -e "$dstFile.zip");
}

sub setSb5DomainContent {
  my ($self, $domainId, $domainName, $uuid) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $sb5backupUtil = AgentConfig::sb5BackupUtil();
  unless (defined $sb5backupUtil)
  {
    Logging::debug("Unable to find SiteBuilder backup utility to create domain content backup.");
    return;
  }

  my $dstFile = HelpFuncs::mktemp("pmm-sb5-dc-XXXXXXXX");
  my $cmd = "$sb5backupUtil --backup --target=site --uuid=$uuid --log=stdout --file=$dstFile";
  my $result = `$cmd`;
  my $retCode = $? >> 8;

  Logging::debug("Backing up sitebuilder settings for site $domainName:");
  Logging::debug($cmd);
  Logging::debug($result);
  if( $retCode==0 ){
    if( -e "$dstFile.zip") {
      my $cid = $self->{content_transport}->addDomainContent('sb5-site', $domainId, 'sb_site', "directory" => ".", "include"   => [$dstFile.".zip"]);
      if ( $cid ) {
        my @phostings = $root->getChildren('phosting');
        if ( @phostings ) {
          $phostings[0]->getChild('content', 1, 1)->addChild($cid);
        }
        else {
          Logging::warning('Error: setSb5DomainContent: there are no node "phosting"', 'PackerStructure');
        }
      }
    }
    else {
      Logging::warning( "Sitebuilder backup was not created." , 'UtilityError' );
    }
  }
  else {
    Logging::warning( "Return code of sitebuilder bru utility: ".$retCode.". Some errors occured during sitebuilder backup. Please see psadump.log file for more information", 'UtilityError' );
  }
  unlink "$dstFile.zip" if( -e "$dstFile.zip");
}

sub setSmbSiteEditorDomainContent {
  my ($self, $domainId, $domainName ) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $backupUtil = AgentConfig::sbbackupBin();
  my $dstFile = HelpFuncs::mktemp("pmm-siteeditor-XXXXXXXX");
  my $cmd = "$backupUtil --target=site --name=$domainName --log=stdout --file=$dstFile";
  my $result = `$cmd`;
  my $retCode = $? >> 8;

  Logging::debug("Backing up siteeditor settings for site $domainName:");
  Logging::debug($cmd);
  Logging::debug($result);
  if( $retCode==0 ){
    if( -e "$dstFile.zip") {
      my $cid = $self->{content_transport}->addDomainContent('sb-dump', $domainId, 'sb_dump', "directory" => ".", "include"   => [$dstFile.".zip"]);
	  if ( $cid ) {
        my @phostings = $root->getChildren('phosting');
        if ( @phostings ) {
          $phostings[0]->getChild('preferences', 1)->getChild('sb-domain', 1)->getChild('content', 1, 1)->addChild($cid);
        }
        else {
          Logging::warning('Error: setSmbSiteEditorDomainContent: there are no node "phosting"', 'PackerStructure');
        }
      }
    }
    else {
      Logging::warning( "SiteEditor backup was not created." , 'UtilityError' );
    }
  }
  else {
    Logging::warning( "Return code of SiteEditor sbbackup utility: ".$retCode.". Some errors occured during SiteEditor backup. Please see psadump.log file for more information", 'UtilityError' );
  }
  unlink "$dstFile.zip" if( -e "$dstFile.zip");
}

sub addFileSharingContent {
  my ($self, $path) = @_;
  my $root = $self->{serverNode};

  my $cid = $self->{content_transport}->addAdminContent('file-sharing', undef, 'file_sharing', 'directory' => $path, 'checkEmptyDir' => 1, 'exclude' => ['unlisted']);
  $root->getChild('content', 1, 1)->addChild($cid) if $cid;
}

sub addFileSharingPasswd {
  my ($self, $path) = @_;
  my $root = $self->{serverNode};

  my $cid = $self->{content_transport}->addAdminContent('file-sharing-passwd', undef, 'file_sharing_passwd', 'directory' => $path, 'checkEmptyDir' => 1 );
  $root->getChild('content', 1, 1)->addChild($cid) if $cid;
}

sub addServerCustomApacheTemplates {
  my ($self, $path) = @_;
  my $root = $self->{serverNode};

  my $dumpFile = $self->{content_transport}->addAdminContent('custom-apache-templates', undef, 'custom_apache_templates', 'directory' => $path);
  $root->getChild('content', 1, 1)->addChild($dumpFile) if $dumpFile;

}

sub addServerCustomHealthConfig {
  my ($self) = @_;
  my $root = $self->{serverNode};

  my $path = AgentConfig::get('PRODUCT_ROOT_D') . "/var";
  my $file = 'custom-health-config.xml';

  if ( -e "$path/$file") {
    my $dumpFile = $self->{content_transport}->addAdminContent('drilldowns-config', undef, 'dd_conf', 'directory' => $path, 'include' => [$file]);
    $root->getChild('content', 1, 1)->addChild($dumpFile) if $dumpFile;
  }
}

sub addCustomizationConfig {

  my ($self) = @_;
  my $root = $self->{serverNode};

  my $path = AgentConfig::get('PRODUCT_ROOT_D') . "/admin/conf";
  my $file = 'customizations.conf';

  if ( -e "$path/$file") {

    my $customizationNode = XmlNode->new('customization');

    my $configText = undef;

    open CONFIGFILE, $path . "/" . $file;
    binmode(CONFIGFILE);

    while (<CONFIGFILE>) {
      $configText .= $_;
    }

    close CONFIGFILE;

    $customizationNode->addChild(XmlNode->new('config', 'content' => $self->{base64}->{'ENCODE'}->($configText) ));
    $root->addChild($customizationNode);
  }
}

sub addRestrictedDomains {
  my ($self) = @_;
  my $root = $self->{serverNode};
  my @restrictedDomains = @{DAL::getRestrictedDomains()};

  if ( @restrictedDomains ) {
    my $restrictedDomainNode = XmlNode->new('restricted-domains');

    foreach my $domain (@restrictedDomains) {
        $restrictedDomainNode->addChild(XmlNode->new('hostname', 'content' => $domain->{'name'}));
    }

    $root->addChild($restrictedDomainNode);
  }
}

sub addFail2ban {
  my ($self) = @_;

  my %packages = %{DAL::getSoftwarePackages()};
  if (!exists($packages{'fail2ban'})) {
    return;
  }

  my $fail2banNode = XmlNode->new('fail2ban');
  $fail2banNode->setAttribute('version', $packages{'fail2ban'});

  my $fail2banDir = '/etc/fail2ban';
  my @includeList = ('action.d/', 'filter.d/', 'jail.d/', 'jail.conf');
  for my $localConfig ( ('jail.local', 'fail2ban.local') ) {
    if (-e "$fail2banDir/$localConfig") {
      push(@includeList, $localConfig);
    }
  }

  my $cid = $self->{content_transport}->addAdminContent(
    'fail2ban',
    undef,
    'fail2ban',
    'directory' => $fail2banDir,
    'include' => \@includeList,
    'skip_content' => 0,
  );
  $fail2banNode->getChild('content', 1, 1)->addChild($cid) if $cid;

  $self->{serverNode}->addChild($fail2banNode);
}

sub addModSecurityContent {
  my ($self, $rulesBaseDir, @ruleSetDirs) = @_;

  my $cid = $self->{content_transport}->addAdminContent(
    'modsecurity',
    undef,
    'modsecurity',
    'directory' => $rulesBaseDir,
    'include' => \@ruleSetDirs,
    'skip_content' => 0
  );
  $self->{serverNode}->getChild('content', 1, 1)->addChild($cid) if $cid;
}

sub dumpUnityMobileIntegration {
  my ($self, $domainId, $ptrDomParams) = @_;

  my %domParams = %{$ptrDomParams};

  if ( defined $domParams{'unity_mobile_site_dns_target'}
       and defined $domParams{'unity_mobile_site_key'}
       and defined $domParams{'unity_mobile_site_prefix'}
  ) {

    my $root = $self->getCashedDomainNode($domainId);

    my $umNode = XmlNode->new('unity-mobile-integration');
    my $siteNode = XmlNode->new('mobile-site');
    $siteNode->addChild(XmlNode->new('dns-target', 'content' => $domParams{'unity_mobile_site_dns_target'}));
    $siteNode->addChild(XmlNode->new('key', 'content' => $domParams{'unity_mobile_site_key'}));
    $siteNode->addChild(XmlNode->new('prefix', 'content' => $domParams{'unity_mobile_site_prefix'}));
    $umNode->addChild($siteNode);
    $root->getChild( 'preferences', 1 )->addChild($umNode);
  }
}

sub makeApsBundleFilterNode {
  my ($self, $parentId, $parentType, $filterType, $filterItems, $parentNode) = @_;

  my $apsBundleNode = XmlNode->new('aps-bundle');

  my $filterNode = XmlNode->new('filter', 'attributes' => { 'type' => $filterType});
  $apsBundleNode->addChild($filterNode);

  foreach my $itemHash (@{$filterItems}) {
    my ($name, $value) = each %{$itemHash};
    my $itemNode = XmlNode->new('item');
    $itemNode->addChild(XmlNode->new('name', 'content' => $name));
    $itemNode->addChild(XmlNode->new('value', 'content' => $value));
    $filterNode->addChild($itemNode);
  }

  if (!defined($parentNode)) {
    if ($parentType eq 'client') {
      $parentNode = $self->getCashedClientNode($parentId);
    }elsif ($parentType eq 'domain') {
      $parentNode = $self->getCashedDomainNode($parentId);
    }

    $parentNode->getChild('preferences', 1)->addChild($apsBundleNode);
  }else{
    $parentNode->addChild($apsBundleNode);
  }
}

sub makeSkinNode {
  my ($self, $skinName, $login, $id) = @_;
  $self->makeBrandingCid($id, $login, "-name " . $skinName, "theme-skin") if $skinName ne '';
}

sub makeBrandingCid {
  my ($self, $id, $login, $cmdArgs, $cidType) = @_;

  my $parentNode;
  if (defined $id) {
    $parentNode = $self->{resellersNodes}->{$id};
  }else {
    $parentNode = $self->{serverNode};
  }

  my $command = AgentConfig::brandingUtil();
  my $dstFile = HelpFuncs::mktemp("pmm-brandtheme-XXXXXXXX");

  if (defined $command) {
      my $cmd = $command . " --pack " . $cmdArgs . " -destination " . $dstFile;
      Logging::debug("Exec: $cmd");
      my $ret = `$cmd`;
      my $retCode = $? >> 8;
      if( $retCode!=0 and $retCode != 13){
        Logging::warning( "Cannot pack theme for vendor " . $login . "STDOUT:" . $ret,'UtilityError');
        return;
      }
      if ( -e $dstFile) {
        my $cid = undef;
        if (defined $id) {
          $cid = $self->{content_transport}->addClientContent($cidType, $id, 'branding', 'directory' => '.', 'include' => [$dstFile]);
        }else{
          $cid = $self->{content_transport}->addAdminContent($cidType, undef, 'branding', 'directory' => '.', 'include' => [$dstFile]);
        }
        if ($cid) {
          $parentNode->getChild('content', 1, 1)->addChild($cid);
        }
      }
  } else {
    Logging::warning( "Utility for dumping branding theme is unavailable ", 'UtilityError');
    unlink $dstFile if ( -e $dstFile);
    return;
  }
  unlink $dstFile if ( -e $dstFile);
}

sub addCrontabSecureSettings {
  my ($self, $params) = @_;
  my $serverNode = $self->{serverNode};
  my $serverPrefNode = $serverNode->getChild( 'server-preferences', 1 );
  $serverPrefNode->addChild(XmlNode->new('crontab-secure-shell', 'content' => $params->{'crontab_secure_shell'})) if defined $params->{'crontab_secure_shell'};
  $serverPrefNode->addChild(XmlNode->new('crontab-secure-shell-compatibility-mode', 'content' => $params->{'crontab_secure_shell_compatibility_mode'} eq 'true' ? 'true' : 'false')) if defined $params->{'crontab_secure_shell_compatibility_mode'};
}

sub addKavSettingsParam {
  my ($self, $kavSettingsNode, $param, $value) = @_;

  my %allowedParams = (
      'Check' => 1,
      'AddXHeaders' => 1,
      'AdminAddress' => 1,
      'QuarantinePath' => 1,
      'FilterByName' => 1,
      'FilterByMIME' => 1,
      'SkipByName' => 1,
      'SkipByMIME' => 1,
      'Quarantine' => 1,
      'AdminNotify' => 1,
      'AdminAction' => 1,
      'SenderNotify' => 1,
      'RecipientNotify' => 1,
      'RecipientAttachReport' => 1,
      'RecipientAction' => 1,
      'CuredAdminAction' => 1,
      'CuredRecipientAction' => 1,
      'InfectedAdminAction' => 1,
      'SuspiciousAdminAction' => 1,
      'InfectedRecipientAction' => 1,
      'SuspiciousRecipientAction' => 1,
      'FilteredRecipientAction' => 1,
      'FilteredQuarantine' => 1
    );

  if ( $allowedParams{$param} ) {
    $kavSettingsNode->addChild( XmlNode->new( 'param', 'content' => $value, 'attributes' => { 'name' => $param } ) );
  }
  return;
}

sub makeSubscriptionNode {
  my ($self, $locked, $synchronized, $custom, $externalId) = @_;

  my $subscriptionNode = XmlNode->new( 'subscription' );

  if (defined $locked) {
    $subscriptionNode->setAttribute('locked', $locked);
  }

  if (defined $custom) {
    $subscriptionNode->setAttribute('custom', $custom);
  }

  if (defined $externalId and $externalId ne '') {
    $subscriptionNode->setAttribute('external-id', $externalId);
  }

  if (defined $synchronized) {
    $subscriptionNode->setAttribute('synchronized', $synchronized);
  }

  return $subscriptionNode;
}

sub addSubscriptionPlan {
  my ($self, $subscriptionNode, $quantity, $plan_guid, $is_addon) = @_;

  return unless ( ( defined $quantity ) && ( defined $plan_guid ) );
  my $planNode = XmlNode->new( 'plan' );
  $planNode->setAttribute('quantity', $quantity);
  $planNode->setAttribute('plan-guid', $plan_guid);
  if ( defined $is_addon) {
    $planNode->setAttribute('is-addon', $is_addon);
  }
  $subscriptionNode->addChild($planNode);
  return;
}

sub dumpFileSharingServerSettings {
  my ($self, $settingsPtr) = @_;

  my %settings = %{$settingsPtr};

  my $fsNode = XmlNode->new('file-sharing-settings');

  my $parentNode = $self->{serverNode};

  foreach my $key (keys %settings) {
    $fsNode->addChild(XmlNode->new('setting','attributes' =>
      { 'name' => $key, 'value' => $settings{$key}} ) );
  }
  $parentNode->addChild($fsNode);
}

sub dumpFileSharingUnlistedFiles {
  my ($self, $unlistedFiles) = @_;

  my $unlistedFilesNode = XmlNode->new('file-sharing-unlisted-files');
  foreach my $unlistedFile (@{$unlistedFiles}) {
    $unlistedFilesNode->addChild(XmlNode->new('file-sharing-unlisted-file','attributes' => {
      'creation-date' => $unlistedFile->{'creationDate'},
      , 'salt' => $unlistedFile->{'salt'}
      , 'name' => $unlistedFile->{'name'}
      , 'path' => $unlistedFile->{'path'}
      , 'expiration' => $unlistedFile->{'expiration'}
    }));
  }
  $self->{serverNode}->addChild($unlistedFilesNode);
}

sub dumpUiMode {
  my ($self, $paramsPtr) = @_;

  my %params = %{$paramsPtr};

  my $parent = $self->{serverNode};

  my $uiMode = undef;

  if (!defined($params{'power_user_panel'}) || $params{'power_user_panel'} ne 'true') {
    $uiMode = 'classic';
  } else {
    if (defined $params{'simple_panel'} && $params{'simple_panel'} eq 'true') {
      $uiMode = (defined $params{'simple_panel_lock'} && $params{'simple_panel_lock'} eq 'true') ? 'simple-lock' : 'simple';
    } else {
      $uiMode = 'normal';
    }
  }

  if (defined $uiMode) {
    $parent->getChild( 'interface-preferences', 1 )->addChild(XmlNode->new('ui-mode', 'content' => $uiMode));
  }
}

sub addTechnicalPreviewDomainNode {
  my ($self, $domainName) = @_;
  my $serverNode = $self->{serverNode};
  my $serverPrefNode = $serverNode->getChild( 'server-preferences', 1 );
  $serverPrefNode->addChild(XmlNode->new('technical-domain', 'content' => $domainName));
}

sub addUpdateSettings {
  my ($self, $autoUpdatesValue, $autoUpgradeToStable, $autoUpgradeBranch) = @_;

  my $serverNode = $self->{serverNode};
  my $serverPrefNode = $serverNode->getChild( 'server-preferences', 1 );
  my $updateSettingsNode = XmlNode->new('update-settings');
  $updateSettingsNode->addChild(XmlNode->new('autoupdates', 'content' => $autoUpdatesValue));
  $updateSettingsNode->addChild(XmlNode->new('autoupgrade-stable', 'content' => $autoUpgradeToStable));
  $updateSettingsNode->addChild(XmlNode->new('release-tier', 'content' => $autoUpgradeBranch));

  $serverPrefNode->addChild($updateSettingsNode);
}

sub addFtpOverSslSettings {
  my ($self, $value) = @_;
  
  my $serverNode = $self->{serverNode};
  my $serverPrefNode = $serverNode->getChild( 'server-preferences', 1 );
  $serverPrefNode->addChild(XmlNode->new('ftp-over-ssl', 'content' => $value));
}

sub setMiscParameters {
  my ($self, $paramsPtr) = @_;

  my $serverNode = $self->{serverNode};
  my %params = %{$paramsPtr};

  my $miscNode = $serverNode->getChild( 'misc', 1 );

  foreach my $name (keys %params) {
    my $paramNode = XmlNode->new('param');
    $paramNode->addChild(XmlNode->new('name', 'content' => $name));
    $paramNode->addChild(XmlNode->new('value', 'content' => $params{$name}));
    $miscNode->addChild($paramNode);
  }
}

sub _getChildNodeName {
  my ($self, $parentNodeName) = @_;

  my %nodesMapping = (
    'components' => 'component'
    , 'resource-usage' => 'resource'
    , 'miscellaneous' => 'setting'
    , 'apache-modules' => 'module'
  );

  return $nodesMapping{$parentNodeName};
}

sub _makeNameValueNode {
  my ($self, $nodeName, $name, $value) = @_;

  my $node = XmlNode->new($nodeName);
  my $nameNode = XmlNode->new('name', 'content' => $name);
  my $valueNode = XmlNode->new('value', 'content' => $value);
  $node->addChild($nameNode);
  $node->addChild($valueNode);

  return $node;
}

sub makeDomainParamsNode {
  my ($self, $domainId, $domainParams) = @_;

  my $root = $self->getCashedDomainNode($domainId);
  return unless defined $root;

  my $properties = $root->getChild( 'properties', 1 );
  my $domParam = XmlNode->new('dom-param');
  foreach my $key (keys %{$domainParams}) {
    $domParam->addChild($self->_makeNameValueNode('param', $key, $self->{base64}->{'ENCODE'}->($domainParams->{$key})));
  }
  $properties->addChild($domParam);
}

sub makeSubscriptionPropertiesNode {
  my ($self, $parentNode, $subscriptionProperties) = @_;

  my $propertiesNode = XmlNode->new('properties');
  foreach my $key (keys %{$subscriptionProperties}) {
    $propertiesNode->addChild($self->_makeNameValueNode('property', $key, $self->{base64}->{'ENCODE'}->($subscriptionProperties->{$key})));
  }
  $parentNode->addChild($propertiesNode);
}

sub assignApplicationInfoToMailUser {
  my ($self, $applicationApsRegistryId, $resourceInfo) = @_;

  my $mailUserNode = $self->{mailNodes}->{$resourceInfo->{'id'}};
  return unless defined $mailUserNode;

  my $apsServices = $mailUserNode->getChild('aps-services', 1);

  my $apsService = XmlNode->new('aps-service');
  $apsService->addChild(XmlNode->new('application-registry-id', 'content' => $applicationApsRegistryId));
  $apsService->addChild(XmlNode->new('resource-registry-id', 'content' => $resourceInfo->{'apsRegistryId'}));

  $apsServices->addChild($apsService);
}

sub makeDomainDescriptionsNode {
  my ( $self, $domainId, $description ) = @_;

  my $domainNode = $self->getCashedDomainNode( $domainId );
  my $prefs = $domainNode->getChild( 'preferences', 1 );

  $self->makeDescriptionsNode( $prefs, [{ 'description' => $description }] );
}

sub makeMailUserDescriptionNode {
  my ( $self, $mailUserId, $description ) = @_;

  my $mailUserNode = $self->{mailNodes}->{$mailUserId};
  my $prefs = $mailUserNode->getChild( 'preferences', 1 );

  $self->makeDescriptionsNode( $prefs, [{ 'description' => $description }] );
}

sub makeResellerDescriptionsNode {
  my ( $self, $resellerId, $descriptions ) = @_;

  my $resellerNode = $self->{resellersNodes}->{$resellerId};

  my $prefs = $resellerNode->getChild( 'preferences', 1 );

  $self->makeDescriptionsNode( $prefs, $descriptions );
}

sub makeDescriptionsNode {
  my ( $self, $parentNode, $descriptions ) = @_;

  if ( scalar @{$descriptions} > 0 ) {
    my $descriptionsNode = XmlNode->new( 'descriptions' );
    foreach my $description ( @{$descriptions} ) {
      my $descriptionNode = XmlNode->new( 'description', 'content' => $description->{'description'} );
      $descriptionNode->setAttribute( 'object-name', $description->{'name'}) if $description->{'name'} and $description->{'name'} ne '';
      $descriptionNode->setAttribute( 'object-type', $description->{'type'}) if $description->{'type'} and $description->{'type'} ne '';
      $descriptionsNode->addChild( $descriptionNode );
    }
    $parentNode->addChild( $descriptionsNode );
  }
}

sub makeOutgoingMessagesParameter {
    my ($self, $objectType, $objectDbId, $paramName, $paramValue) = @_;

    my $object = $self->_getObject($objectType, $objectDbId);
    $object->getChild('preferences', 1)->getChild('outgoing-messages', 1)->addChild(XmlNode->new('parameter', 'children' => [
        XmlNode->new('name', 'content' => $paramName),
        XmlNode->new('value', 'content' => $paramValue),
    ]));
}

sub _makeSeverOutgoingEmailMode {
    my ($self, $mailSettingsPtr, $ipAddressesPtr) = @_;
    my %mailSettings = %{$mailSettingsPtr};
    my %ipAddresses = %{$ipAddressesPtr};
    if (!defined($mailSettings{'outgoing_email_mode'})) {
        return;
    }

    my $outgoingEmailMode = $mailSettings{'outgoing_email_mode'};
    Logging::debug("outgoing_email_mode found: $outgoingEmailMode");
    if ($outgoingEmailMode eq 'domain-ip' || $outgoingEmailMode eq 'domain-name') {
        $self->{serverNode}->getChild('mail-settings', 1)->addChild(XmlNode->new('outgoing-email-mode', 'children' => [
            XmlNode->new($outgoingEmailMode)
        ]));
    } elsif ($outgoingEmailMode == 'explicit-ip') {
        my @ips;
        for my $param ('outgoing_email_mode_explicit_ip_v4', 'outgoing_email_mode_explicit_ip_v6') {
            if (defined($mailSettings{$param}) && $mailSettings{$param} ne ''
                && defined($ipAddresses{$mailSettings{$param}})) {
                push @ips, XmlNode->new('ip-address', 'content' => $ipAddresses{$mailSettings{$param}}->{'ip_address'});
            }
        }
        $self->{serverNode}->getChild('mail-settings', 1)->addChild(XmlNode->new('outgoing-email-mode', 'children' => [
            XmlNode->new($outgoingEmailMode, 'children' => \@ips)
        ]));
    }
}

sub _makeServerOutgoingMessagesParameter {
    my ($self, $paramName, $paramValue) = @_;

    $self->{serverNode}->getChild('mail-settings', 1)->getChild('outgoing-messages', 1)->addChild(
        XmlNode->new('parameter', 'children' => [
            XmlNode->new('name', 'content' => $paramName),
            XmlNode->new('value', 'content' => $paramValue),
        ]
    ));
}

# Method return object by their types and id
# returns XmlNode | undef
sub _getObject{
    my ($self, $objectType, $objectDbId) = @_;

    if ($objectType eq 'admin') {
        return $self->{admin};
    } elsif ($objectType eq 'server') {
        return $self->{serverNode};
    } elsif ($objectType eq 'reseller') {
        return $self->{resellersNodes}->{$objectDbId};
    } elsif ($objectType eq 'client' or $objectType eq 'customer') {
        return $self->{clientNodes}->{$objectDbId};
    } elsif ($objectType eq 'domain' or $objectType eq 'subscription' or $objectType eq 'site' ) {
        return $self->getCashedDomainNode($objectDbId);
    } elsif ($objectType eq 'mailsystem') {
        return $self->getCashedDomainNode($objectDbId)->getChild('mailsystem', 1);
    } elsif ($objectType eq 'mailuser') {
        return $self->{mailNodes}->{$objectDbId};
    } else {
        Logging::error("Object type \"$objectType\" is not supported by Packer::_getObject");
    }
    return undef;
}

sub makeClParamNode {
  my ( $self, $clientId, $clientType, $params ) = @_;

  my $parentNode = $self->_getObject($clientType, $clientId)->getChild('properties', 1)->getChild('cl-param', 1);
  foreach my $param ( keys( %{$params} ) ) {
    my $paramNode = XmlNode->new( 'param' );
    $paramNode->addChild( XmlNode->new( 'name', 'content' => $param ) );
    $paramNode->addChild( XmlNode->new( 'value', 'content' => $self->{base64}->{'ENCODE'}->( $params->{$param} ) ) );
    $parentNode->addChild( $paramNode );
  }
}


sub makeExtensionNode {
  my ($self, $objectType, $objectId) = @_;
  Logging::debug("makeExtensionNode $objectType, $objectId");
  $objectType = 'customer' if $objectType eq 'client';
  $objectType = 'subscription' if $objectType eq 'domain';

  my $extensionsHooks =  PleskStructure::getExtensionsHooks();
  foreach my $extId (keys %{$extensionsHooks}) {
    Logging::debug("Check extension $extId");
    next if $extensionsHooks->{$extId}->{$objectType} ne 'true';

    my $xml = DAL::backupExtension( $extId, $objectType, $objectId );
    return if not defined $xml;
    my $extName = $xml->{'name'};
    my $extension = XmlNode->new( 'extension',
        'attributes' => {
            'name'    => $extName,
            'version' => $xml->{'version'},
            'release' => $xml->{'release'},
        }
    );
    if (exists $xml->{'config'}) {
      $extension->addChild(XmlNode->new('config', 'content' => $xml->{'config'}[0]));
    }
    if (exists $xml->{'enabled'}) {
      $extension->setAttribute('enabled', $xml->{'enabled'});
    }
    if (exists $xml->{'settings'} and ref($xml->{'settings'}[0]) eq "HASH") {
      foreach my $setting (@{$xml->{'settings'}[0]->{'setting'}})
      {
        $extension->getChild( 'settings', 1 )->addChild( XmlNode->new( 'setting', 'children' => [
                    XmlNode->new( 'name', 'content' => $setting->{'name'}[0] ),
                    XmlNode->new( 'value', 'content' => $setting->{'value'}[0] ),
                ] ) );
      }
    }
    if (exists $xml->{'content'}) {
      my @include;
      my @exclude;
      my $packageFile;
      if (ref($xml->{'content'}) eq "HASH") {
        foreach my $item (@{$xml->{'content'}->{'include'}}) {
          $item =~ s/^[\\\/]*//;
          push @include, $item;
        }
        foreach my $item (@{$xml->{'content'}->{'exclude'}}) {
          $item =~ s/^[\\\/]*//;
          push @exclude, $item;
        }
        foreach my $item (@{$xml->{'content'}->{'webspace'}}) {
          $extension->getChild( 'webspace-paths', 1 )->addChild(XmlNode->new('path', 'content' => $item));
        }

        if (exists $xml->{'content'}->{'package'}) {
          $packageFile = $xml->{'content'}->{'package'}[0];
        }
      }

      if (scalar(@include) > 0) {
        if ("." ~~ @include) {
          @include = ();
        }
        my $proc;
        if ($objectType eq 'admin' or $objectType eq 'server') { $proc = \&addAdminContentProxy; }
        elsif ($objectType eq 'customer' or $objectType eq 'reseller') { $proc = \&addClientContentProxy; }
        elsif ($objectType eq 'subscription' or $objectType eq 'site') { $proc = \&addDomainContentProxy; }
        else {
          Logging::warning( "Error: makeExtensionNode: unsupported parent type '$objectType' ", 'assert' );
        }
        if ($proc) {
          my $cid = $self->$proc(
              'extension',
              $objectId,
              'ext_'.$extName,
              "directory"            => AgentConfig::get( 'PRODUCT_ROOT_D' )."/var/modules/$extName",
              "include"              => \@include,
              "exclude"              => \@exclude,
              "include_hidden_files" => 1
          );
          $extension->getChild( 'content', 1, 1 )->addChild( $cid ) if $cid;
        }
      }

      if (defined $packageFile && -e $packageFile) {
        my $cid = $self->addAdminContentProxy(
            'extension_dist',
            $objectId,
            'ext_dist_'.$extName,
            "directory" => dirname($packageFile),
        );
        $extension->getChild( 'content', 1, 1 )->addChild( $cid ) if $cid;
        eval {
          File::Path::rmtree(dirname($packageFile));
        };
        Logging::debug('Unable to remove temporary directory with extension archive. ' . $@) if $@;
      }


    }
    $self->_getObject( $objectType, $objectId )->getChild( 'extensions', 1 )->addChild( $extension );
    if ($extensionsHooks->{$extId}->{"$objectType-post-backup"} eq 'true') {
      DAL::postBackupExtension( $extId, $objectType, $objectId );
    }
  }
}

1;
