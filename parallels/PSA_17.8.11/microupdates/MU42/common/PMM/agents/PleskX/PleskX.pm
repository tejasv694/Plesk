# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
package PleskX;

#
# Agent interface:
#
# ::new($storagePolicy, $dumpStatusPolicy, $agentsSharedDir)
#
# ->setDumpType(FULL [default] | SHALLOW | CONFIGURATION | ONLY_MAIL)
#
# ->selectDomains(@domains)
# ->selectClients(@clients)
# ->selectAll()
# ->selectAllDomains()
# ->selectServerSettings()
#
# ->excludeDomains(@domains)
# ->excludeClients(@clients)
#
# ->dump()
#
# Plesk agent interface:
#
# ->setDescription()
#

use strict;
use warnings;

use PleskVersion;
use PleskMaxVersion;
use PleskStructure;
use Status;
use SiteApp;
use Mailman;
use AgentConfig;
use DAL;
use CommonPacker;
use StopWatch;
use Logging;
use HelpFuncs;
use Db::DbConnect;
use Db::Connection;
use Db::MysqlUtils;
use Db::PostgresqlUtils;
use ObjectsFilter;
use MiscConfigParser;
use Encode;
use Suspend;

use XmlLogger;

use vars qw|@ISA|;
use vars qw|$FULL $SHALLOW $CONFIGURATION $ONLY_MAIL $ONLY_HOSTING $INCLUDE_APP_DISTRIB $NO_LICENSE %listOfInstalledApplicationsOnServer|;
$FULL          = 0;
$SHALLOW       = 1;
$CONFIGURATION = 2;
$ONLY_MAIL     = 4;
$ONLY_HOSTING  = 8;
$INCLUDE_APP_DISTRIB = 16;
$NO_LICENSE = 32;

sub new {
  my $self = {};
  bless( $self, shift );
  $self->_init(@_);
  return $self;
}

# --- Public instance methods ---
sub _init {
  my ( $self, $storagePolicy, $dumpStatusPolicy, $agentsSharedDir, $backupParameters, $sessionPath ) = @_;

  $self->{collectStatistics} = $self->getCollectStatistics();
  if ($self->{collectStatistics})
  {
    $self->{stopWatch} = StopWatch->new();
    $self->{stopWatch}->createMarker("all");

    use StopWatchPacker;
    $self->{packer} = StopWatchPacker->new( PleskMaxVersion::get(), $storagePolicy );
  }
  else
  {
    use Packer;
    $self->{packer} = Packer->new( PleskMaxVersion::get(), $storagePolicy );
  }
  $self->{dump_status} = $dumpStatusPolicy;
  $self->{configuration_dump} = undef;
  $self->{shallow_dump}       = undef;
  $self->{only_mail_dump}     = undef;
  $self->{only_hosting_dump}  = undef;
  $self->{description}        = undef;
  $self->{dump_vhost}         = 0;
  $self->{dump_full_mail}     = 0;
  $self->{admin_info}         = 0;
  $self->{server_settings}    = 0;
  $self->{dumped_domains}     = [];
  $self->{dumped_clients}     = [];
  $self->{existing_remote_db_servers} = [];
  $self->{suspender} = Suspend->new();
  $self->{backupParameters} = $backupParameters;
  $self->{sessionPath} = $sessionPath;
  $self->{extension_id} = undef;
  $self->{extension_context_file} = undef;
  $self->{extension_data} = undef;
  $self->{markers} = [];
  $self->{includeFiles} = ['']; # include all
  $self->{includeDatabases} = undef; # include all

  # domain name => remote web node id
  # domain name => undef if local
  $self->{remoteWebNodes} = ();

  AgentConfig::init()
    or die "Error: Plesk config file 'psa.conf' is not found\n";
  AgentConfig::setSharedDir($agentsSharedDir);

  AgentConfig::tarBin();    # Pre-caching of values
  AgentConfig::mysqlBin();

  PleskVersion::init( AgentConfig::get('PRODUCT_ROOT_D') );

  if (defined $self->{backupParameters} && defined $self->{backupParameters}->{db}) {
      $self->{dbh} = Db::Connection::getConnection(#host port dbType dbName login password
        'type'      => 'mysql',
        'user'      => $self->{backupParameters}->{db}->{username},
        'password'  => $self->{backupParameters}->{db}->{password},
        'name'      => $self->{backupParameters}->{db}->{dbname},
        'host'      => $self->{backupParameters}->{db}->{host},
        'port'      => (defined($self->{backupParameters}->{db}->{port}) ? $self->{backupParameters}->{db}->{port} : "3306"),
        'utf8names' => 1,
        'connRetries' => AgentConfig::getMaxDbConnectionsRetries()
      );
  } else {
      $self->{dbh} = Db::Connection::getConnection(
        'type'      => 'mysql',
        'user'      => 'admin',
        'password'  => AgentConfig::get('password'),
        'name'      => 'psa',
        'host'      => 'localhost',
        'utf8names' => 1,
        'connRetries' => AgentConfig::getMaxDbConnectionsRetries()
      );
  }

  die "Can not connect to the 'psa' database. Please, check database server is running and connection settings are valid" if (!$self->{dbh});

  if ($self->{collectStatistics})
  {
    $self->{dbh}->startCollectStatistics();
  }

  DAL::init( $self->{dbh} );

  PleskStructure::init( $self->{dbh} );

  Encoding::setDefaultEncoding("UTF-8");

  $self->{filter} = ObjectsFilter->new( $self );

  $self->{has_remote_db_server} = 0;

  foreach my $ptrHash (@{DAL::getDatabaseServers()}) {
    if (HelpFuncs::isRemoteHost($ptrHash->{'host'})) {
      $self->{has_remote_db_server} = 1;
      last;
    }
  }
}

sub getCollectStatistics {
  my $envVal = $ENV{'COLLECT_BACKUP_STAT'};
  if ($envVal)
  {
    return $envVal;
  }
  return 0;
}

sub setDecryptFullDump {
  my ($self) = @_;
  $self->{packer}->setDecryptFullDump();
}

sub setDumpWholeVHost{
  my ($self) = @_;
  $self->{dump_vhost} = 1;
}

sub setDumpWholeMail{
  my ($self) = @_;
  $self->{dump_full_mail} = 1;
}

sub setContentTransport{
  my $self = shift;
  my $contentTransportType = shift;
  $self->{packer}->setContentTransport($contentTransportType, @_)
}

sub setBackupProfileFileName{
 my ($self, $profileName, $profileId ) = @_;
 die "Invalid backup profile name '$profileName'\n" if index( $profileName, '/'  )>=0;
 $self->{packer}->setBackupProfileFileName( $profileName, $profileId ) if $profileName;
}

sub setIncrementalCreationDate{
 my ($self, $incrementalCreationDate) = @_;
 $self->{packer}->setIncrementalCreationDate($incrementalCreationDate);
}

sub getIncrementalCreationDate {
 my ($self) = @_;
 return $self->{packer}->getIncrementalCreationDate();
}

sub getCreationDate {
 my ($self) = @_;
 return $self->{packer}->getCreationDate();
}

sub getBackupPrefix {
  my ($self) = @_;
  return $self->{packer}->{backupPrefix};
}

sub setLastIndexPath{
  my ($self, $lastIndexPath) = @_;
  $self->{packer}->setLastIndexPath($lastIndexPath);
}

sub setLastIncrementFile {
  my ($self, $lastIncrementFile) = @_;
  $self->{packer}->setLastIncrementFile($lastIncrementFile);
}

sub setExcludePatternsFile {
  my ($self, $excludePatternsFile) = @_;
  $self->{packer}->setExcludePatternsFile($excludePatternsFile);
}

sub setBackupOwnerGuid{
  my ($self, $ownerGuid, $ownertype ) = @_;
  $ownerGuid = PleskStructure::getAdminGuid() if not $ownerGuid;
  $ownertype = 'server' if not $ownertype;
  $self->{packer}->setBackupOwnerGuid( $ownerGuid, $ownertype );
}

sub setExtension{
  my ($self, $extensionId, $extensionContextFile ) = @_;

  $self->{extension_id} = $extensionId;
  $self->{extension_context_file} = $extensionContextFile;
  $self->{extension_data} = DAL::getExtensionDataForBackup($extensionId, $extensionContextFile);
  return unless defined($self->{extension_data});

  my $marker = $self->{extension_data}->{'marker'}[0];
  push @{$self->{markers}}, $marker;
  if (defined($self->{extension_data}->{'file'})) {
    $self->{includeFiles} = $self->{extension_data}->{'file'};
  } else {
    $self->{includeFiles} = []; # include nothing
  }
  if (defined($self->{extension_data}->{'db'})) {
    $self->{includeDatabases} = $self->{extension_data}->{'db'};
  } else {
    $self->{includeDatabases} = []; # include nothing
  }
}

sub getMarkers{
  my ($self) = @_;
  return $self->{markers};
}

sub getBackupOwnerGuid{
 my ($self) = @_;
 $self->{packer}->getBackupOwnerGuid();
}

sub getClientGuid {
  my ($self, $name) = @_;
  return PleskStructure::getClientGuid($name);
}

sub getClientId {
  my ($self, $name) = @_;
  return PleskStructure::getClientId($name);
}

sub getDomainGuid {
  my ($self, $name) = @_;
  my $domainPtr = DAL::getDomainPtr($name);
  return ($domainPtr->{'guid'}) ? $domainPtr->{'guid'} : '';
}

sub getDomainId {
  my ($self, $name) = @_;
  my $domainPtr = DAL::getDomainPtr($name);
  return ($domainPtr->{'id'}) ? $domainPtr->{'id'} : '';
}

sub getDomainAsciiName {
  my ($self, $name) = @_;
  my $domainPtr = DAL::getDomainPtr($name);
  return $domainPtr->{'name'};
}

sub setDumpType {
  my ( $self, $type ) = @_;

  if ( $type & $SHALLOW )       { $self->{shallow_dump}       = 1; }
  if ( $type & $CONFIGURATION ) { $self->{configuration_dump} = 1; }
  if ( $type & $ONLY_MAIL ) {
    $self->{only_mail_dump}    = 1;
    $self->{only_hosting_dump} = undef;
  }
  if ( $type & $ONLY_HOSTING ) {
    $self->{only_hosting_dump} = 1;
    $self->{only_mail_dump}    = undef;
  }

  if ( $type & $INCLUDE_APP_DISTRIB) {
    $self->{include_app_distrib} = 1;
  }

  if ( $type & $NO_LICENSE ) {
    $self->{no_license} = 1;
  }
}

sub setDescription {
  my ( $self, $description ) = @_;
  $self->{description} = $description;
}

sub setExistingRemoteDbServers {
  my ( $self, $dbServers ) = @_;
  $self->{existing_remote_db_servers} = $dbServers;
}

sub selectDomains {
  my ( $self, @inputDomains ) = @_;
  @inputDomains = sort { $a cmp $b } @inputDomains;

  my @pleskDomains = sort { $a cmp $b } PleskStructure::getDomains();
  my @missingDomains = HelpFuncs::arrayDifference( \@inputDomains, \@pleskDomains );

  if (@missingDomains) {
    my $msg;
    my $sitesHash = PleskStructure::getSites();
    my @sites = keys %{$sitesHash};
    @sites = sort { $a cmp $b } @sites;

    my @missingDomainsAreSites = HelpFuncs::arrayIntersection(\@missingDomains, \@sites);

    if (@missingDomainsAreSites) {
      $msg = "The following domains are additional domains: " . ( join ",", @missingDomainsAreSites ) . ".\n";
      $msg .= "Backups of specified domains will be included in backups of their subscriptions." . "\n";
      foreach my $site (@missingDomainsAreSites) {
        $msg .= "Domain '" . $site ."' will be backed up with subscription '" . $sitesHash->{$site} . "'.\n";
      }
    } else {
      $msg = "The following domains were not found on the host: "  . ( join ",", @missingDomains );
    }
    print STDERR "$msg\n";
    Logging::warning( $msg, 'MissingDomains' );
    @inputDomains = HelpFuncs::arrayIntersection( \@inputDomains, \@pleskDomains );
  }
  $self->{domains} = \@inputDomains if @inputDomains;
}

sub selectDomainsById {
  my ( $self, @inputDomains ) = @_;

  my @pleskDomains = PleskStructure::getDomainsFromId( \@inputDomains );
  if( @inputDomains ){
    my $msg = "The following domain id's were not found on the host: " . ( join ",", @inputDomains );
    print STDERR "$msg\n";
    Logging::warning( $msg, 'MissingDomains' );
  }
  $self->selectDomains( @pleskDomains ) if @pleskDomains;
}

sub selectClients {
  my ( $self, @inputClients ) = @_;
  @inputClients = sort { $a cmp $b } @inputClients;

  my @pleskClients = sort { $a cmp $b } PleskStructure::getClients();
  my @missingClients = HelpFuncs::arrayDifference( \@inputClients, \@pleskClients );

  if (@missingClients) {
    my $msg = "The following clients were not found on the host: " . ( join ",", @missingClients );
    print STDERR "$msg\n";
    Logging::warning( $msg, 'MissingClients' );
    @inputClients = HelpFuncs::arrayIntersection( \@inputClients, \@pleskClients );
  }
  $self->_selectClients( \@inputClients ) if @inputClients;
}

sub selectClientsById {
  my ( $self, @inputClients ) = @_;

  my @pleskClients = PleskStructure::getClientsFromId( \@inputClients);
  if( @inputClients ){
    my $msg = "The following clients id's were not found on the host: " . ( join ",", @inputClients );
    print STDERR "$msg\n";
    Logging::warning( $msg, 'MissingClients' );
  }
  $self->selectClients( @pleskClients ) if @pleskClients;
}

sub selectResellers {
  my ( $self, @inputResellers ) = @_;
  @inputResellers = sort { $a cmp $b } @inputResellers;

  my @pleskResellers = sort { $a cmp $b } PleskStructure::getResellers();
  my @missingResellers = HelpFuncs::arrayDifference( \@inputResellers, \@pleskResellers );

  if (@missingResellers) {
    my $msg = "The following resellers were not found on the host: " . ( join ",", @missingResellers );
    print STDERR "$msg\n";
    Logging::warning( $msg, 'MissingResellers' );
    @inputResellers = HelpFuncs::arrayIntersection( \@inputResellers, \@pleskResellers );
  }
  $self->_selectClients( \@inputResellers ) if @inputResellers;
}

sub selectResellersById {
  my ( $self, @inputResellers ) = @_;

  my @pleskResellers = PleskStructure::getResellersFromId( \@inputResellers);
  if( @inputResellers ){
    my $msg =  "The following reseller id's were not found on the host: " . ( join ",", @inputResellers );
    print STDERR "$msg\n";
    Logging::warning( $msg, 'MissingResellers' );
  }
  $self->selectResellers( @pleskResellers ) if @pleskResellers;
}

sub _selectClients {
  my( $self, $logins ) = @_;
  my @clients;
  my @resellers;
  my $admin = 0;
  foreach my $client(@{$logins}){
    my $clType = PleskStructure::getClientType( $client );
    push @clients, $client if $clType eq 'client';
    push @resellers, $client if $clType eq 'reseller';
    $admin = 1 if $clType eq 'admin';
  }
  $self->{resellers} = \@resellers if (@resellers);
  $self->{clients} = \@clients if (@clients);
  $self->{dump_all} = 1 if $admin;
}

sub excludeDomains {
  my ( $self, @inputDomains ) = @_;
  @inputDomains =  PleskStructure::getDomains() if scalar(@inputDomains)==0;
  @inputDomains = sort { $a cmp $b } @inputDomains;

  $self->{exclude_domains} = \@inputDomains;
  $self->{domains} = [HelpFuncs::arrayDifference($self->{domains}, \@inputDomains)] if exists $self->{domains};
}

sub excludeClients {
  my ( $self, @inputClients ) = @_;
  @inputClients =  PleskStructure::getClients() if scalar(@inputClients)==0;
  @inputClients = sort { $a cmp $b } @inputClients;

  $self->{exclude_clients} = \@inputClients;
  $self->{clients} = [HelpFuncs::arrayDifference($self->{clients}, \@inputClients)] if exists $self->{clients};
}

sub excludeResellers {
  my ( $self, @inputResellers ) = @_;
  @inputResellers = PleskStructure::getResellers() if scalar(@inputResellers)==0;
  @inputResellers = sort { $a cmp $b } @inputResellers;

  $self->{exclude_resellers} = \@inputResellers;
  $self->{resellers} = [HelpFuncs::arrayDifference($self->{resellers}, \@inputResellers)] if exists $self->{resellers};
}

sub domainExcluded{
 my ($self, $name ) = @_;
 if( exists  $self->{exclude_domains} ){
   foreach my $domain( @{$self->{exclude_domains}} ){
     return 1 if $domain eq $name;
   }
 }
 return 0;
}

sub clientExcluded{
 my ($self, $name ) = @_;
 if( exists  $self->{exclude_clients} ){
   foreach my $client( @{$self->{exclude_clients}} ){
     return 1 if $client eq $name;
   }
 }
 return 0;
}

sub resellerExcluded{
 my ($self, $name ) = @_;
 if( exists  $self->{exclude_resellers} ){
   foreach my $reseller( @{$self->{exclude_resellers}} ){
     return 1 if $reseller eq $name;
   }
 }
 return 0;
}

sub selectAll {
  my ($self) = @_;
  $self->{dump_all} = 1;
}

sub selectAllResellers {
  my ($self) = @_;

  my @pleskResellers =  sort { $a cmp $b } PleskStructure::getResellers();
  $self->{resellers} = \@pleskResellers;
}

sub selectAllClients {
  my ($self) = @_;

  my @pleskClients =  sort { $a cmp $b } PleskStructure::getClients();
  $self->{clients} = \@pleskClients;
}

sub selectAllDomains {
  my ($self) = @_;

  my @pleskDomains =  sort { $a cmp $b } PleskStructure::getDomains();
  $self->{domains} = \@pleskDomains;
}

sub selectServerSettings {
  my ($self) = @_;
  $self->{server_settings} = 1;
  Logging::debug( "Select to backup server settings" );
}

sub selectAdminInfo {
  my ($self) = @_;
  $self->{admin_info} = 1;
  Logging::debug( "Select to backup Administrator info" );
}

sub setSuspend{
  my ( $self, $suspend, $suspendSid ) = @_;
  $self->{suspender}->setSuspend($suspend, $suspendSid);
}

sub Cleanup{
  my $self = shift;
  $self->{suspender}->unSuspendDomain();
}

sub getListOfInstalledApplicationsOnServer {
  my ($self) = @_;

  if (scalar keys %listOfInstalledApplicationsOnServer == 0) {
    my @serverApplications = SiteApp::getServerSettingsForApplications();
    if (@serverApplications) {
      foreach my $ptrRow ( @{DAL::getApsPackages103()} ) {
        my $application = $self->getConcreteApplicationInfo(\@serverApplications, $ptrRow->[6]);
        if (!$application) {
          Logging::debug("Can't get '$ptrRow->[0]-$ptrRow->[1]-$ptrRow->[2]' application info by '$ptrRow->[6]'", 'SiteappError');
          next;
        }

        my $packageArchive = $application->getPackageArchive();
        my $packageArchiveFileName = undef;
        my $packageArchiveDir = undef;

        my $idx = rindex( $packageArchive, '/' );

        if ( $idx > 0 ) {
          $packageArchiveDir = substr( $packageArchive, 0, $idx);
          $packageArchiveFileName = substr( $packageArchive, $idx+1);
        }

        $listOfInstalledApplicationsOnServer{$application->getRegistryUid()} = [$ptrRow->[0], $ptrRow->[1], $ptrRow->[2], $packageArchiveDir, $packageArchiveFileName, $ptrRow->[4], $ptrRow->[5], $application];
      }
    }
  }
  return \%listOfInstalledApplicationsOnServer;
}

sub dump {
  my ($self) = @_;

  $self->checkProgramTerminated();

  Logging::beginObject('server', 'server');

  $self->{packer}->turnOffContent() if $self->{configuration_dump};

  $self->{packer}->setRoot(
    $self->{description},
    $self->{configuration_dump} ? 0 : 1,
    'panel',
    PleskStructure::getAdminGuid(),
    DAL::getEmbeddedInfo(['--backup-server-info'])
  );

  $self->{packer}->setMarkers(@{$self->{markers}});

  my $embeddedInfo = {};
  if ($self->{server_settings} || $self->{admin_info}) {
    $embeddedInfo = DAL::getEmbeddedInfo(['--backup-server']);
  }

  $self->_createLocalDumpNameFile();

  if( exists $self->{dump_all} ) {
    $self->createFullDump();

    $self->makeAdminInfoNode($embeddedInfo) if $self->{admin_info};
    eval {
      $self->makeServerNode($embeddedInfo) if $self->{server_settings};
      $self->{packer}->addServerNodeToDump();
      1;
    } or do {
      $self->checkProgramTerminated();
      $self->excludeProblemServerFromBackup( $@ );
    }
  }
  else{
    my $done;
    $self->beginWriteStatus();
    if( $self->{admin_info} or $self->{server_settings} ) {
      $self->{packer}->addRootAdmin(PleskStructure::getAdminId(), PleskStructure::getAdminGuid(), DAL::getFullHostName());
      $done = 1;
    }
    if (exists $self->{resellers}){
      $self->createResellersDump();
      $done = 1;
    }
    if (exists $self->{clients}) {
      $self->createClientsDump();
      $done = 1;
    }
    if ( exists $self->{domains} ) {
      $self->createDomainsDump();
      $done = 1;
    }

    $self->{packer}->addRootRoles();
    $self->{packer}->addRootUsers();
    $self->{packer}->addRootDomains();
    if( $self->{server_settings} ){
      $self->makeAdminUsersAndRoles();
      eval {
        $self->makeServerNode($embeddedInfo);
        $done = 1;
        1;
      } or do {
        $self->checkProgramTerminated();
        $self->excludeProblemServerFromBackup( $@ );
      }
    }
    if( $self->{admin_info} ) {
      $self->makeAdminInfoNode($embeddedInfo);
      $done = 1;
    }
    $self->{packer}->addServerNodeToDump();
    $self->{dump_status}->finishObjects();
    if ( not $done ) {
      Logging::error("No objects to dump found");
      Logging::endObject();
      return 1;
    }
  }

  $self->{suspender}->unSuspendDomain();
  my $ret = $self->{packer}->finish();

  if ($self->{collectStatistics})
  {
    $self->writeStatistics();
  }

  Logging::endObject();

  $self->_reportDomainStatistics();

  return $ret;
}

###### Size mode functions ######
sub getSize {
  my ($self, $incrementalBackup, $backupToFtp) = @_;

  use BackupSizeCalculator;

  my $backupSizeCalculator = BackupSizeCalculator->new( $self, $incrementalBackup, $backupToFtp );

  return $backupSizeCalculator->getSize();
}
###### End Size mode functions ######

sub writeStatistics {
  my $self = shift;

  my $logPath = $ENV{'COLLECT_BACKUP_STAT_LOG'};
  if (!defined($logPath) || length($logPath) == 0)
  {
    $logPath = "perfomance-statistics.log";
  }

  $logPath = ">>" . $logPath;

  open(LOGFILE, $logPath);

  print $logPath;

  print LOGFILE "Date: " . HelpFuncs::getTime() . "\n";

  my $allTime = $self->{stopWatch}->getDiff("all");
  print LOGFILE "All time: " . $allTime . "\n";

  print LOGFILE "Sql time: " . $self->{dbh}->getStatistics()->{sqlTime} . "\n";

  my $xmlTarTime = $self->{packer}->getStatistics()->{totalTime};
  my $tarTime = $self->{packer}->getStatistics()->{tarTime};

  print LOGFILE "Xml time: " . ($xmlTarTime - $tarTime) . "\n";
  print LOGFILE "Files time: " . $tarTime . "\n";

  print LOGFILE "\n";

  close(LOGFILE);
}

sub getAdminRootPath{
 my ($self) = @_;
 return $self->{packer}->regAdminObjectBackupPath( '' );
}

my %generalRolePermissions = (
  'userManagement' => 1,
  'webSitesAndDomainsManagement' => 1,
  'logRotationManagement' => 1,
  'anonymousFtpManagement' => 1,
  'scheduledTasksManagement' => 1,
  'spamfilterManagement' => 1,
  'antivirusManagement' => 1,
  'databasesManagement' => 1,
  'backupRestoreManagement' => 1,
  'browseStats' => 1,
  'applicationsManagement' => 1,
  'sitebuilderManagement' => 1,
  'filesManagement' => 1,
  'ftpAccountsManagement' => 1,
  'dnsManagement' => 1,
  'mailManagement' => 1,
  'mailListsManagement' => 1,
  'publishFilesOnTheWeb' => 1,
);

sub getRolePermissions {
  my ($self, $roleId) = @_;

  my %permissions = %{DAL::getRolePermissions($roleId)};
  %permissions = map { $_ => $permissions{$_} } grep { $generalRolePermissions{$_} } keys %permissions;

  return \%permissions;
}

sub _createLocalDumpNameFile {
  my ($self) = @_;

  return unless ($self->{sessionPath});

  my $filename;
  if ($self->{dump_all} or $self->{admin_info} or $self->{server_settings}) {
    $filename = $self->{packer}->getAdminBackupPath('info', 1) . ".xml";
  } elsif (exists $self->{resellers} and scalar(@{$self->{resellers}}) == 1){
    my $clientInfo = DAL::getClientShortInfo(@{$self->{resellers}}[0]);
    $filename = $self->getClientRootPath($self->{resellers}->[0]) . "/" .
        $self->{packer}->getClientsBackupPath($clientInfo->{id}, 'info', 1) . ".xml";
  } elsif (exists $self->{clients} and scalar(@{$self->{clients}}) == 1) {
    my $clientInfo = DAL::getClientShortInfo(@{$self->{clients}}[0]);
    $filename = $self->getClientRootPath($self->{clients}->[0]) . "/" .
        $self->{packer}->getClientsBackupPath($clientInfo->{id}, 'info', 1) . ".xml";
  } elsif (exists $self->{domains} and scalar(@{$self->{domains}}) == 1) {
    my $domainInfo = DAL::getDomainShortInfo(@{$self->{domains}}[0]);
    $filename = $self->getDomainRootPath(@{$self->{domains}}[0]) . "/" .
        $self->{packer}->getDomainsBackupPath($domainInfo->{id}, 'info', 1) . ".xml";
  } else {
    $filename = $self->{packer}->getAdminBackupPath('info', 1) . ".xml";
  }

  open DUMP_RES, "> $self->{sessionPath}/local-dump-name";
  print DUMP_RES "$filename";
  close DUMP_RES;
}

sub createFullDump {
  my ($self) = @_;

  $self->getAdminRootPath();

  $self->{packer}->addRootAdmin(PleskStructure::getAdminId(), PleskStructure::getAdminGuid(), DAL::getFullHostName());

  $self->{dump_status}->start(PleskStructure::getClientsCount(''), PleskStructure::getDomainsCount( '' ) );

  my @resellers = sort { $a cmp $b } PleskStructure::getResellers();
  @resellers = $self->{filter}->filterSelectedResellers( \@resellers );

  foreach my $reseller (@resellers) {
        my @myclients = sort { $a cmp $b } PleskStructure::getMyClients($reseller);
        @myclients = $self->{filter}->filterSelectedClients( \@myclients );
        my @domains = sort { $a cmp $b } PleskStructure::getDomainsForClient($reseller);
        @domains = $self->{filter}->filterSelectedDomains( \@domains );
        eval {
          $self->makeClientNode($reseller, \@domains, \@myclients, 0 );
          1;
        } or do {
          $self->checkProgramTerminated();
          $self->excludeProblemClientFromBackup( $reseller, $@ );
        }
  }

  my @clients = sort { $a cmp $b } PleskStructure::getAdminClients();

  foreach my $client (@clients) {
        if( PleskStructure::getClientType( $client ) eq 'client' ){
          my @myclients = sort { $a cmp $b } PleskStructure::getMyClients($client);
          @myclients = $self->{filter}->filterSelectedClients( \@myclients );
          my @domains = sort { $a cmp $b } PleskStructure::getDomainsForClient($client);
          @domains = $self->{filter}->filterSelectedDomains( \@domains );
          eval {
            $self->makeClientNode($client, \@domains, \@myclients, 0 );
            1;
          } or do {
            $self->checkProgramTerminated();
            $self->excludeProblemClientFromBackup( $client, $@ );
          }
        }
  }

  if (exists($self->{dump_all}) && !$self->{server_settings}) {
    my $adminId = PleskStructure::getAdminId();
    $self->dumpTemplates($adminId, 'server', 'reseller');
  }

  my $adminName = PleskStructure::getAdminName();
  $self->{dump_status}->startClient($adminName);

  my @adminDomains = PleskStructure::getAdminDomains();
  @adminDomains = $self->{filter}->filterSelectedDomains( \@adminDomains );
  foreach my $domainName(@adminDomains) {
    eval {
      $self->makeDomainNode($domainName, 0 );
      1;
    } or do {
      $self->checkProgramTerminated();
      $self->excludeProblemDomainFromBackup($domainName, $@);
    };
  }

  $self->makeAdminUsersAndRoles();

  $self->{dump_status}->endClient($adminName);

  $self->{dump_status}->finishObjects();
}

sub excludeProblemClientFromBackup {
  my ( $self, $clientName, $reason ) = @_;

  my $clientType = PleskStructure::getClientType( $clientName );
  my $clientId = PleskStructure::getClientId( $clientName );

  Logging::warning( "$clientType $clientName is skipped from backup due to error: $reason" );
  Logging::debug( "$clientType $clientName is skipped from backup due to error: $reason", 'fatal' );

  if ( $clientType eq 'client' ) {
    delete $self->{packer}->{clientNodes}->{$clientId};
  } elsif ( $clientType eq 'reseller' ) {
    delete $self->{packer}->{resellerNodes}->{$clientId};
  }
}

sub excludeProblemDomainFromBackup {
  my ( $self, $domainName, $reason ) = @_;

  Logging::warning( "Domain $domainName is skipped from backup due to error: $reason" );
  Logging::debug( "Domain $domainName is skipped from backup due to error: $reason", 'fatal' );

  my $domainId = PleskStructure::getDomainId( $domainName );
  delete $self->{packer}->{domainNodes}->{$domainId};
  delete $self->{packer}->{domainShortNodes}->{$domainId};

  $self->{suspender}->unSuspendDomain();
}

sub excludeProblemSiteFromBackup {
  my ( $self, $domainId, $ptrSite, $reason ) = @_;

  Logging::warning( "Domain $ptrSite->{'name'} is skipped from backup due to error: $reason" );
  Logging::debug( "Domain $ptrSite->{'name'} is skipped from backup due to error: $reason", 'fatal' );

  $self->{packer}->removeSiteNode( $domainId, $ptrSite->{'name'} );

  $self->{suspender}->unSuspendDomain();
}

sub excludeProblemDatabaseFromBackup {
  my ( $self, $domainId, $db, $reason ) = @_;

  Logging::warning( "Database $db->{'name'}, type $db->{'type'} is skipped from backup due to error: $reason" );
  Logging::debug( "Database $db->{'name'}, type $db->{'type'} is skipped from backup due to error: $reason", 'fatal' );

  $self->{packer}->removeDatabaseNode( $domainId, $db );
}

sub excludeProblemServerFromBackup {
  my ( $self, $reason ) = @_;

  Logging::warning( "Server settings are skipped from backup due to error: $reason" );
  Logging::debug( "Server settings are skipped from backup due to error: $reason", 'fatal' );

  $self->{packer}->removeServerNode();
}

sub excludeProblemMailUserFromBackup {
  my ( $self, $domainId, $mail, $reason ) = @_;

  my $domainName = PleskStructure::getDomainNameFromId( $domainId );
  Logging::warning( "Mail user $mail->{'mail_name'} on domain $domainName is skipped from backup due to error: $reason" );
  Logging::debug( "Mail user $mail->{'mail_name'} on domain $domainName is skipped from backup due to error: $reason", 'fatal' );

  $self->{packer}->removeMailuserNode( $domainId, $mail );
}

sub excludeProblemMailSystemFromBackup {
  my ( $self, $domainId, $reason ) = @_;

  my $domainName = PleskStructure::getDomainNameFromId( $domainId );

  Logging::warning( "Mail System on domain $domainName is skipped from backup due to error: $reason" );
  Logging::debug( "Mail System on domain $domainName is skipped from backup due to error: $reason", 'fatal' );

  $self->{packer}->removeMailSystemNode( $domainId );
}

sub excludeProblemHostingFromBackup {
  my ( $self, $domainId, $hostingType, $reason ) = @_;

  my $domainName = PleskStructure::getDomainNameFromId( $domainId );

  Logging::warning( "Hosting on domain $domainName is skipped from backup due to error: $reason" );
  Logging::debug( "Hosting on domain $domainName is skipped from backup due to error: $reason", 'fatal' );

  $self->{packer}->removeHostingNode( $domainId, $hostingType );
}

sub excludeProblemDnsZoneFromBackup {
  my ( $self, $domainId, $reason ) = @_;

  my $domainName = PleskStructure::getDomainNameFromId( $domainId );

  Logging::warning( "DNS Service on domain $domainName is skipped from backup due to error: $reason" );
  Logging::debug( "DNS Service on domain $domainName is skipped from backup due to error: $reason", 'fatal' );

  $self->{packer}->removeDomainDnsZone($domainId);
}

sub excludeProblemUserFromBackup {
  my ( $self, $userLogin, $callback, $reason ) = @_;

  Logging::warning( "User $userLogin is skipped from backup due to error: $reason" );
  Logging::debug( "User $userLogin is skipped from backup due to error: $reason", 'fatal' );

  $callback->( $userLogin );
}

sub excludeProblemRoleFromBackup {
  my ( $self, $callback, $roleName, $reason ) = @_;

  Logging::warning( "Role $roleName is skipped from backup due to error: $reason" );
  Logging::debug( "Role $roleName is skipped from backup due to error: $reason", 'fatal' );

  $callback->( $roleName );
}

sub makeAdminLimitsAndPermissions {
  my ($self) = @_;
  my $permsHash = $self->getPermsHash(PleskStructure::getAdminId());
  foreach my $key ( keys %{$permsHash} ) {
    $self->{packer}->addAdminPermission($key, $permsHash->{$key});
  }
}

sub makeAdminUsersAndRoles {
  my ( $self ) = @_;

  my $adminName = PleskStructure::getAdminName();
  my @adminUsersLogins = PleskStructure::getUserLogins( $adminName );

  my $callback = sub {
    $self->{packer}->addAdminUser( @_ );
  };

  my $callbackFailed = sub {
    $self->{packer}->removeAdminUser( @_ );
  };

  $self->addUsers( \@adminUsersLogins, $callback, $callbackFailed );

  my $callbackRole = sub {
    $self->{packer}->removeAdminRole( @_ );
  };

  for my $roleId ( PleskStructure::getRoleIds( $adminName ) ) {
    my @role = grep {$_->{'id'}==$roleId and $_->{'isBuiltIn'}==0 } @{DAL::selectSmbRoles()};
    eval {
      if ( @role ) {
        $self->{packer}->addAdminRole( $role[0]->{'name'}, 0, $self->getRolePermissions($roleId), DAL::getRoleServicePermissions($roleId) ) ;
      }
      1;
    } or do {
      $self->excludeProblemRoleFromBackup( $callbackRole, $role[0]->{'name'}, $@ );
    }
  }
}

sub getClientRootPath{
 my ($self, $clientLogin ) = @_;
 my $clientId = PleskStructure::getClientId($clientLogin);
 my $clientType = PleskStructure::getClientType($clientLogin);
 if( $clientType eq 'reseller' ){
   return $self->{packer}->regResellersObjectBackupPath( $self->getAdminRootPath(), $clientId, $clientLogin );
 }
 elsif( $clientType eq 'client' ){
  my $parentId = PleskStructure::getClientParentId( $clientLogin );
  my $parentLogin = PleskStructure::getClientNameFromId( $parentId );
  return $self->{packer}->regClientObjectBackupPath( $self->getClientRootPath($parentLogin), $clientId, $clientLogin  );
 }
 else{
    return $self->getAdminRootPath();
 }
}

sub beginWriteStatus{
  my ($self) = @_;
  my %accounts;
  my %domains;
  if( exists $self->{resellers} ){
      foreach my $reseller ( @{ $self->{resellers} } ) {
        $accounts{$reseller} = 1;
        my @myclients = sort { $a cmp $b } PleskStructure::getMyClients($reseller);
        @myclients = $self->{filter}->filterSelectedClients( \@myclients );
        for my $client( @myclients ){
          $accounts{$client} = 1;
        }
        foreach my $client( @myclients ){
           my @mydomains = sort { $a cmp $b } PleskStructure::getDomainsForClient($client);
           @mydomains = $self->{filter}->filterSelectedDomains( \@mydomains );
           for my $domain( @mydomains ){
             $domains{$domain} = 1;
           }
        }
      }
  }
  if( exists $self->{clients} ){
    foreach my $client ( @{ $self->{clients} } ) {
      $accounts{$client} = 1;
      my @mydomains = sort { $a cmp $b } PleskStructure::getDomainsForClient($client);
      @mydomains = $self->{filter}->filterSelectedDomains( \@mydomains );
      for my $domain( @mydomains ){
         $domains{$domain} = 1;
      }
    }
  }
 if( exists $self->{domains} ){
   for my $domain( @{$self->{domains}} ){
      $domains{$domain} = 1;
   }
 }

 $self->{dump_status}->start( scalar( keys(%accounts) ), scalar( keys( %domains ) ) );
}

sub createResellersDump{
  my ($self) = @_;

  my( %clients, %domains );

  my $client;
  foreach my $reseller ( @{ $self->{resellers} } ) {
    my @myclients = sort { $a cmp $b } PleskStructure::getMyClients($reseller);
    @myclients = $self->{filter}->filterSelectedClients( \@myclients );
    $clients{$reseller} = \@myclients;

    my @mydomains = sort { $a cmp $b } PleskStructure::getDomainsForClient($reseller);
    @mydomains = $self->{filter}->filterSelectedDomains( \@mydomains );
    $domains{$reseller} = \@mydomains;
  }

  foreach my $reseller ( @{ $self->{resellers} } ) {
    $self->getClientRootPath( $reseller );
    eval {
      $self->makeClientNode($reseller, $domains{$reseller}, $clients{$reseller}, 1 );
      1;
    } or do {
      $self->checkProgramTerminated();
      $self->excludeProblemClientFromBackup( $reseller, $@ );
    }
  }
}

sub createClientsDump {
  my ( $self ) = @_;

  my (%clients);
  foreach my $client ( @{ $self->{clients} } ) {
    my @mydomains = sort { $a cmp $b } PleskStructure::getDomainsForClient($client);
    @mydomains = $self->{filter}->filterSelectedDomains( \@mydomains );
    $clients{$client} = \@mydomains;
  }

  foreach my $client (keys %clients) {
    $self->getClientRootPath( $client );
    eval {
      $self->makeClientNode( $client, $clients{$client}, undef, 1 );
      1;
    } or do {
      $self->checkProgramTerminated();
      $self->excludeProblemClientFromBackup( $client, $@ );
    }
  }
}

sub getDomainRootPathByAscName {
  my ($self, $domainAscName) = @_;

  my $domainPtr = DAL::getDomainPtrByAsciiName($domainAscName);
  return undef unless $domainPtr;
  return $self->_getDomainRootPath($domainPtr->{'id'}, $domainPtr->{'cl_id'}, $domainPtr->{'name'});
}

sub getDomainRootPath{
  my ($self, $domainName ) = @_;

  my $sql = "SELECT id, cl_id, name FROM domains WHERE displayName = BINARY '?'";
  my $ptrHash;
  my $domainId;
  my $clientId;
  my $domainAscName;
  if( $self->{dbh}->execute($sql, $domainName) ) {
    if ( $ptrHash = $self->{dbh}->fetchhash() ) {
      $domainId = $ptrHash->{'id'};
      $clientId = $ptrHash->{'cl_id'};
      $domainAscName = $ptrHash->{'name'};
    }
  }
  $self->{dbh}->finish();

  return $self->_getDomainRootPath($domainId, $clientId, $domainAscName);
}

sub _getDomainRootPath{
  my ($self, $domainId, $clientId, $domainAscName) = @_;

  return undef unless $domainId and $clientId and $domainAscName;

  my $rootPath = $self->getClientRootPath(PleskStructure::getClientNameFromId($clientId));
  return $self->{packer}->regDomainObjectBackupPath($rootPath, $domainId, $domainAscName);
}

sub createDomainsDump {
  my ( $self ) = @_;

  foreach my $domainName ( @{$self->{domains}} ) {
    if ( $self->getDomainRootPath( $domainName ) ) {
      eval {
        $self->makeDomainNode( $domainName, 1 );
        1;
      } or do {
        $self->checkProgramTerminated();
        $self->excludeProblemDomainFromBackup($domainName, $@);
      };
    }
    else {
      my $msg = "Unable to backup domain $domainName because of unappropriate database content";
      print STDERR "$msg\n";
      Logging::warning( $msg, 'PleskDbError' );
    }
  }
}

sub dumpDomainPersonalPermissions {
  my ( $self, $domainId, $permId ) = @_;
  $self->addPermissions( $domainId, 'domain-personal', $permId );
}

sub makeClientNode {
  my ( $self, $clientName, $domains, $childClients, $isroot ) = @_;

  $self->checkProgramTerminated();

  my ($parent, $clientType );
  $clientType = PleskStructure::getClientType( $clientName );

  if( $clientType eq 'client' and $self->clientExcluded( $clientName ) ) {
     Logging::debug("Client '$clientName' is excluded from dump");
     return;
  }
  if( $clientType eq 'reseller' and $self->resellerExcluded( $clientName ) ) {
     Logging::debug("Reseller '$clientName' is excluded from dump");
     return;
  }

  foreach my $dumpedClient( @{$self->{dumped_clients}} ){
     if( $dumpedClient eq $clientName ){
       Logging::debug("Client '$dumpedClient' already dumped");
       return;
     }
  }
  push @{$self->{dumped_clients}}, $clientName;

  my ( $item, $sql, $ptrHash, $value, %client, $ptrRow, $id, %clientParams );
  Logging::beginObject($clientType,$clientName, $self->generateObjectUuidForLogging($clientType,$clientName));
  Logging::debug("Client '$clientName' is started") if $clientType eq 'client';
  Logging::debug("Reseller '$clientName' is started") if $clientType eq 'reseller';
  $self->{dump_status}->startClient($clientName);

  $sql = "SELECT * FROM clients WHERE login = '?'";

  if ( $self->{dbh}->execute($sql, $clientName) ) {
    if (my $ptrHash = $self->{dbh}->fetchhash() ) {
      %client = %{ $ptrHash };
    }
  }
  $self->{dbh}->finish();

  my $clientId = $client{'id'};
  $parent = PleskStructure::getClientNameFromId( $client{'parent_id'} ) if exists $client{'parent_id'};
  if ( exists $client{'vendor_id'} ){
    my $vendorGuid = PleskStructure::getClientGuidFromId( $client{'vendor_id'} );
    $client{'vendor-guid'} = $vendorGuid;
  }
  $client{'country'} = CommonPacker::normalizeCountry( $client{'country'} );

  my %passwd;
  unless ( $self->{shallow_dump} ) {
    $sql = "SELECT password, type FROM accounts WHERE id = ?";

    if ( $self->{dbh}->execute($sql, $client{'account_id'}) ) {
      if ( $ptrHash = $self->{dbh}->fetchhash() ) {
        %passwd  = (
          'password' => $ptrHash->{'password'},
          'type'     => CommonPacker::normalizePasswordType( $ptrHash->{'type'} )
        );
      }
      else {
        %passwd = ( 'password' => '', 'type' => 'plain' );
        my $msg =  "Broken referencial integrity: Account password is not found for client " . $client{'account_id'};
        print STDERR "$msg\n";
        Logging::warning( $msg , 'BrokenDbIntegrity');
      }
    }
    $self->{dbh}->finish();
  }
  my $status = $client{'status'};

  my $doNotDumpDomainTemplates = 0;

  my $parentType;

  if( $parent ) {
    $parentType = PleskStructure::getClientType( $parent );
    $client{'owner-guid'} = PleskStructure::getClientGuid( $parent );
	$client{'owner-name'} = $parent;
  } else{
    $parentType = 'admin';
    $client{'owner-guid'} = PleskStructure::getAdminGuid();
	$client{'owner-name'} = 'admin';
  }

  if( $clientType eq 'client' ){

    if ( exists $client{'vendor_id'} ){
        my $vendorLogin = PleskStructure::getClientNameFromId( $client{'vendor_id'} );
        $client{'vendor-login'} = $vendorLogin;
    }

     if( $isroot ){
        if ($self->{admin_info} or $self->{server_settings}) {
          $self->{packer}->addAdminClient( $clientId, \%client, \%passwd, $status );
        } else {
          $self->{packer}->addRootClient( $clientId, \%client, \%passwd, $status );
        }
     }
     else{

       if( $parentType eq 'admin' ) {
           $self->{packer}->addAdminClient( $clientId, \%client, \%passwd, $status );
       }
       elsif( $parentType eq 'reseller' ) {
         $doNotDumpDomainTemplates = 1;
         $self->{packer}->addResellerClient($client{'parent_id'}, $clientId, \%client, \%passwd, $status );
       }
       else{
            die "Cannot dump client '$clientName' of type '$clientType', parent '$parent' type '$parentType' not supported!";
       }
     }
  }

  elsif( $clientType eq 'reseller' ){

     if( $isroot ){
       if ( $self->{admin_info}  or $self->{server_settings} ) {
         $self->{packer}->addAdminReseller( $clientId, \%client, \%passwd, $status );
       } else {
         $self->{packer}->addRootReseller( $clientId, \%client, \%passwd, $status );
       }
     }
     else{

       if( $parentType eq 'admin' ) {
         $self->{packer}->addAdminReseller( $clientId, \%client, \%passwd, $status );
       }
       else{
            die "Cannot dump client '$clientName' of type '$clientType', parent '$parent' type '$parentType' not supported!";
       }

     }
  }

  else{
     die "Cannot dump client '$clientName'. unknown type '$clientType'!";
  }

  $sql = "SELECT param,val FROM cl_param WHERE cl_id = ?";
  if ( $self->{dbh}->execute_rownum($sql, $clientId) ) {
    while ( $ptrRow = $self->{dbh}->fetchrow() ) {
      $clientParams{ $ptrRow->[0] } = $ptrRow->[1];
    }
  }
  $self->{dbh}->finish();

  unless ( $self->{shallow_dump} ) {

    $self->{packer}->setClientPinfo( $clientId, \%client );

    if ( exists( $client{'locale'} ) && $client{'locale'} ) {
      $self->{packer}->setClientLocale( $clientId, $client{'locale'} );
    }

    if ( ($clientType eq 'reseller')
          or (DAL::isBusinessModelUpgraded() and ($client{'limits_id'} or $client{'perm_id'})) )
    {
      #
      # limits
      #

      if( $clientType eq 'reseller' ) {
        $self->addResellerLimits( $clientId, $client{'limits_id'} );
      }
      else{
        $self->addClientLimits( $clientId, $client{'limits_id'} );
      }

      $self->addClientOverusePolicy( $clientId, \%clientParams );
      $self->{packer}->fixDefaultLimits($clientType,$clientId);

      #
      # end limits
      #

      $self->addPermissions( $clientId, 'client', $client{'perm_id'} );

      #fix bug 88139
      if ( !$client{'perm_id'} )
      {
        $self->{packer}->addClientPermission( $clientId, 'cp_access', 'true' );
      }

      $self->addClientMulitpleLoginPermission($clientId, $clientParams{'multiply_login'} ) if exists $clientParams{'multiply_login'};
    }

    #
    # Domain skeleton
    #

    my $skeletonPath =
      AgentConfig::get('HTTPD_VHOSTS_D') . "/.skel/$clientId";

    if ( !$self->{configuration_dump} && !$self->{only_mail_dump} ) {
      $self->{packer}->addClientDomainSkeleton( $clientId, $skeletonPath, "skel" );
    }
  }

  my %ips = PleskStructure::getClientIps($clientName);

  $self->{packer}->addClientIps( $clientId, \%ips );

  $self->addClientTraffic($clientId);

  unless ($doNotDumpDomainTemplates) {
    $self->dumpDomainTemplates( $clientId, $clientType );
  }

  if( $childClients and @{$childClients} and @{$domains} ) {
    # we are in reseller node and should add nodes <domains> and <clients> before processing of domains, as this could lead to creating users and roles
    $self->{packer}->addResellerDomainsClientsNodes( $clientId );
  }

  # Make childs dump
  if( $childClients ){
    foreach my $myClientName( @{$childClients} ) {
      #TO DO check excluded clients. Not worked now
      my @clientdomains = PleskStructure::getDomainsForClient( $myClientName );
      @clientdomains = sort { $a cmp $b } @clientdomains;
      @clientdomains = $self->{filter}->filterSelectedDomains( \@clientdomains );
      eval {
        $self->makeClientNode($myClientName, \@clientdomains, undef, 0 );
        1;
      } or do {
        $self->checkProgramTerminated();
        $self->excludeProblemClientFromBackup( $myClientName, $@ );
      }
    }
    $self->{packer}->removeResellerChildNodeIfEmpty( $clientId, 'clients' );
  }

  for my $domainName ( @{$domains} ) {
    eval {
       $self->makeDomainNode( $domainName, 0 );
       1;
    } or do {
      $self->checkProgramTerminated();
       $self->excludeProblemDomainFromBackup( $domainName, $@ );
    };
  }
  $self->{packer}->removeResellerChildNodeIfEmpty( $clientId, 'domains' );

  my @users = PleskStructure::getUserLogins($clientName);

  my $callback = sub {
    $self->{packer}->addClientUser( $clientId, @_ );
  };

  my $callbackFailed = sub {
    $self->{packer}->removeClientUser( $clientId, @_ );
  };

  my $callbackRole = sub {
    $self->{packer}->removeClientRole( $clientId, @_ );
  };

  $self->addUsers( \@users, $callback, $callbackFailed );

  for my $roleId (PleskStructure::getRoleIds($clientName) ) {
    my @role = grep {$_->{'id'}==$roleId and $_->{'isBuiltIn'}==0} @{DAL::selectSmbRoles()};
    eval {
      if ( @role ) {
        $self->{packer}->addClientRole($clientId, $role[0]->{'name'}, 0, $self->getRolePermissions($roleId), DAL::getRoleServicePermissions($roleId));
      }
      1;
    } or do {
      $self->excludeProblemRoleFromBackup( $callbackRole, $role[0]->{'name'}, $@ );
    }
  }

  $self->getCustomButtonsByOwner( $clientType, $clientId );

  $self->getSubscription('client', $clientId);

  if (exists $clientParams{'theme_skin'} and not $self->{configuration_dump}) {
    if ( $clientType eq 'reseller' ) {
      $self->{packer}->makeSkinNode($clientParams{'theme_skin'}, $clientName, $clientId);
    }
  }

  $self->dumpApsBundle($clientId, 'client');

  if ( $clientType eq 'reseller' ) {
    my $clientsDescriptions = DAL::getClientsDescriptions( $clientId );
    my $subscriptionsDescriptions = DAL::getSubscriptionsResellerDescriptions( $clientId );
    my @allDescriptions = ( @{$clientsDescriptions}, @{$subscriptionsDescriptions} );
    $self->{packer}->makeResellerDescriptionsNode( $clientId, \@allDescriptions );
  }

  $self->{packer}->makeClParamNode( $clientId, $clientType, \%clientParams );

  $self->{packer}->makeExtensionNode($clientType, $clientId);

  $self->{packer}->finishClient($clientId);
  $self->{dump_status}->endClient($clientName);

  Logging::endObject();
}

sub addDomainOverusePolicy{
  my( $self, $parentId, $params ) = @_;

  if( exists $params->{'DecompositionRule'} ){
    my $oversell = ($params->{'DecompositionRule'} eq 'oversell')? 'true' : 'false';
    $self->{packer}->addDomainLimit( $parentId, 'oversell', $oversell );
  }

  my $overuse = 'normal';
  if( exists $params->{'OveruseBlock'} ){
    if( $params->{'OveruseBlock'} eq 'true' ){
      if ( exists $params->{'OveruseSuspend'}) {
        if ( $params->{'OveruseSuspend'} eq 'true') {
          $overuse = 'block';
        }
        else {
          if ( $params->{'OveruseNotify'} eq 'true') {
            $overuse = 'not_suspend_notify';
          } else {
            $overuse = 'not_suspend';
          }
        }
      }
      else {
        $overuse = 'not_suspend';
      }
    }
    else {
      if( $params->{'OveruseNotify'} eq 'true' ){
        $overuse = 'notify';
      }
      else {
        $overuse = 'normal';
      }
    }
  }
  $self->{packer}->addDomainLimit( $parentId, 'overuse', $overuse );
}

sub addClientOverusePolicy{
  my( $self, $parentId, $params ) = @_;

  if( exists $params->{'DecompositionRule'} ){
    my $oversell = ($params->{'DecompositionRule'} eq 'oversell')? 'true' : 'false';
    $self->{packer}->addClientLimit( $parentId, 'oversell', $oversell );
  }

  my $overuse;
  if( $params->{'OveruseBlock'} eq 'true' ){
    $overuse = 'block';
  }
  elsif( $params->{'OveruseNotify'} eq 'true' ){
    $overuse = 'notify';
  }
  else {
    $overuse = 'normal';
  }

  $self->{packer}->addClientLimit( $parentId, 'overuse', $overuse );
}

sub addPermissions {
  my ( $self, $parent, $parentType, $id ) = @_;

  unless($parentType eq 'client' || $parentType eq 'domain-personal') {
    Logging::warning( "Error: addPermissions: Unexpected type of parent \"$parentType\"",'assert');
    return;
  }

  my $permsHash = $self->getPermsHash($id);
  return unless $permsHash;

  foreach my $key ( keys %{$permsHash} ) {
    if ( $parentType eq 'client' ) {
      $self->{packer}->addClientPermission( $parent, $key, $permsHash->{$key} );
    }
    else {
      $self->{packer}->addDomainPersonalPermission( $parent, $key, $permsHash->{$key} );
    }
  }
}

sub getPermsHash {
  my ( $self, $permsId ) = @_;

  return unless $permsId;

  my $sql = "SELECT permission,value FROM Permissions WHERE id=?";

  my %permsHash;
  if ( $self->{dbh}->execute_rownum($sql, $permsId) ) {
    while ( my $ptrRow = $self->{dbh}->fetchrow() ) {
      my $name  = $ptrRow->[0];
      my $value = HelpFuncs::convertToTrueFalseString($ptrRow->[1]);
      $name  = "manage_virusfilter" if $name eq "manage_drweb";
      $name  = "manage_php_settings" if $name eq "manage_php_safe_mode";

      if ( $name eq 'make_dumps' ) {
        $permsHash{'allow_local_backups'} = $value;
        $permsHash{'allow_ftp_backups'} = $value;
        $permsHash{'allow_account_local_backups'} = $value;
        $permsHash{'allow_account_ftp_backups'} = $value;
        next;
      }

      $permsHash{$name} = $value;
    }
  }
  $self->{dbh}->finish();

  $sql = "SELECT value FROM Permissions WHERE id = ? and ("
        . "permission = 'ipb_allow' or permission = 'nb_allow')";
  if ( $self->{dbh}->execute_rownum($sql, $permsId) ) {
    if ( my $ptrRow = $self->{dbh}->fetchrow() ) {
      my $value = HelpFuncs::convertToTrueFalseString(@{ $ptrRow }[0]);
      $permsHash{'manage_phosting'} = $value;
    }
  }
  $self->{dbh}->finish();

  return \%permsHash;
}

sub addClientMulitpleLoginPermission {
  my ( $self, $id, $multiple_sessions ) = @_;
  $self->{packer}->addClientPermission( $id, 'multiple-sessions', $multiple_sessions );
}

sub insertLimit {
  my ( $self, $parent, $parentType, $name, $value ) = @_;

  if ( (!defined $value) || ($value eq '') ) {
    $value = '-1';
  }
  if( $value ne '-1') {
    if ( $name eq 'expiration' ) {
      my ( $mday, $mon, $year ) = ( localtime($value) )[ 3 .. 5 ];
      $mon++;
      $year += 1900;
      $value = sprintf( '%04d-%02d-%02d', $year, $mon, $mday );
    }
  }

  if ( $parentType eq 'client' || $parentType eq 'reseller' ) {
    $self->{packer}->addClientLimit( $parent, $name, $value );
  }
  elsif ( $parentType eq 'domain' ) {
    $self->{packer}->addDomainLimit( $parent, $name, $value );
  }
  else {
    Logging::warning( "Error: insertLimits: Unexpected type of parent \"$parentType\"",'assert');
  }
}

sub addResellerLimits {
  my ( $self, $resellerId, $limitsId ) = @_;
  return $self->addLimits( $resellerId, 'reseller', $limitsId);
}

sub addClientLimits {
  my ( $self, $clientId, $limitsId ) = @_;
  return $self->addLimits( $clientId, 'client', $limitsId);
}

sub addDomainLimits {
  my ( $self, $domainId, $limitsId ) = @_;
  return $self->addLimits( $domainId, 'domain', $limitsId);
}

sub addLimits {
  my ( $self, $parent, $parentType, $limitId) = @_;

  my $count = 0;

  my ( $sql, $ptrRow, $value, $name );
  $sql = "SELECT limit_name,value FROM Limits WHERE id=?";
  if ( $self->{dbh}->execute_rownum($sql, $limitId) ) {
    $count = 0;
    while ( $ptrRow = $self->{dbh}->fetchrow() ) {
      ( $name, $value ) = @{$ptrRow};
      $self->insertLimit( $parent, $parentType, $name, $value );
      $count++;
    }
  }
  $self->{dbh}->finish();
  return $count;
}

sub dumpDomainTemplates {
  my ( $self, $clientId, $clientType ) = @_;

  $self->dumpTemplates( $clientId, $clientType, 'domain' );
  $self->dumpTemplates( $clientId, $clientType, 'domain_addon' );
}

sub dumpTemplates {
  my ( $self, $parent, $parentType, $templateType ) = @_;
  return $self->dumpTemplates9x($parent, $parentType, undef, $templateType);
}

# explicit function for dumping single plan to avoid legacy suffix 9x
sub dumpTemplate {
  my ( $self, $parent, $parentType, $template, $templateType ) = @_;
  return $self->dumpTemplates9x($parent, $parentType, $template, $templateType);
}

my %dumped_templates;
sub dumpTemplates9x {
  my ( $self, $parent, $parentType, $tmpl_id, $templateType ) = @_;

  my $sql;

  my $ownerGuid;

  my %templateNames;
  my %templateUuids;
  my %templateExtIds;

  $ownerGuid = PleskStructure::getClientGuidFromId($parent);

  $sql = "SELECT * FROM Templates WHERE ";
  my @sqlParams = ();
  if ($tmpl_id) {
    $sql .= "id = ?";
    push(@sqlParams, $tmpl_id);
  } else {
    $sql .= "owner_id = ? AND type = '?'";
    push(@sqlParams, $parent, $templateType);
  }

  if ( $self->{dbh}->execute($sql, @sqlParams) ) {
    while ( my $ptrHash = $self->{dbh}->fetchhash() ) {
      my $id = $ptrHash->{'id'};
      if (not exists $dumped_templates{$id}) {
        $dumped_templates{$id} = 1;
        $templateNames{ $id } = $ptrHash->{'name'};
        $templateUuids{ $id } = $ptrHash->{'uuid'};
        $templateExtIds{ $id } = $ptrHash->{'external_id'} if defined $ptrHash->{'external_id'} and $ptrHash->{'external_id'} ne '';
      }
    }
  }
  $self->{dbh}->finish();

  my @planItemNames = @{$self->getPlanItemNames()};

  while ( my ( $template_id, $tmpl_name ) = each %templateNames ) {

    my @template;

    my $logrotation_id;
    my $ip_pool_id;
    my $overuse_block;
    my $overuse_notify;
    my $overuse_suspend;
    my $aps_bundle_filter_id = undef;
    my %phpSettings;
    my %phpHandlerSettings = ('php_handler_id' => undef, 'php_handler_type' => undef, 'php_version' => undef);
    my @defaultDbServers;
    my %webServerSettings;
    my %planItems;

    if ( $self->{dbh}->execute("SELECT element, value FROM TmplData WHERE tmpl_id=?", $template_id) ) {
      my @tmplData;
      while ( my $ptrRow = $self->{dbh}->fetchrow() ) {
        my @tmplElem = @{$ptrRow};
        push @tmplData, \@tmplElem;
      }

      foreach my $ptrRow (@tmplData) {
        my ( $element, $value ) = @{$ptrRow};

        if ( !defined($value) ) {
          $value = '';
        }

        if ( $element eq 'stat_ttl' and ( $value == 0 or $value eq '' ) ) {
          $value = -1;
        }

        if ( $element eq 'logrotation_id' ) {
          $logrotation_id = $value;
          next;
        }

        if ($element eq 'tmpl_pool_id') {
          $ip_pool_id = $value;
          next;
        }

        if( $element eq 'overuse_block' ){
          $overuse_block = $value;
          next;
        }

        if( $element eq 'overuse_notify' ){
          $overuse_notify = $value;
          next;
        }

        if ( $element eq 'overuse_suspend' ){
          $overuse_suspend = $value;
          next;
        }

        if ( grep $_ eq $element, @planItemNames) {
          $planItems{ $element } = $value;
          next;
        }
        if ($element eq 'aps_bundle_filter_id') {
          $aps_bundle_filter_id = $value;
        }

        if ($element eq 'phpSettingsId') {
          %phpSettings = %{DAL::getPhpSettings($value)};
          my $phpSettingsCustom = DAL::getPhpSettingsCustom($value);
          $phpSettings{'notice'} = $phpSettingsCustom if defined $phpSettingsCustom;
        }

        if ($element eq 'default_server_mysql'
            or $element eq 'default_server_postgresql'
        ) {
          foreach my $ptrHash (@{DAL::getDatabaseServers()}) {
            if ($value == $ptrHash->{'id'}) {
              push @defaultDbServers, $ptrHash;
            }
          }
        }

        if ($element eq 'webServerSettingsId') {
          my $webServerSettings = DAL::getWebServerSettings($value);
          while (my ($settingName, $settingValue) = each(%{$webServerSettings})) {
            my @data = ( $settingName, $self->{packer}->{base64}->{'ENCODE'}->($settingValue) );
            push @template, \@data;
          };
        }

        if ( 'domain_addon' eq $templateType ) {
          # skip items which are not allowed for domain_addon template
          my %template_items_ignore_addon = (
            'cp_access'               => 1,
            'remote_access_interface' => 1,
            'disk_space_soft'         => 1,
            'max_traffic_soft'        => 1,
            'stat_ttl'                => 1,
            'maillists'               => 1,
            'overuse'                 => 1,
            'dns_type'                => 1,
            'nonexist_mail'           => 1
          );
          next if exists $template_items_ignore_addon{$element};
        }
        if ( 'domain' eq $templateType ) {
          # skip items which are not allowed for domain template
          next if ($element eq 'cp_access' or $element eq 'remote_access_interface');
        }

        if ( $element eq 'quota' and ( $value eq 'false' or $value == 0 ) ) {
          $value = -1;
        }

        my %emptyableBoolElementInTemplate = (
          'asp'             => 1,
          'cgi'             => 1,
          'pdir_plesk_stat' => 1,
          'perl'            => 1,
          'php'             => 1,
          'python'          => 1,
          'ssi'             => 1
        );
        if ( $value eq '' and exists $emptyableBoolElementInTemplate{$element} ) {
          $value = 'false';
        }

        if (exists($phpHandlerSettings{$element})) {
          $phpHandlerSettings{$element} = $value;
          next;
        }

        my @data = ( $element, $value );
        push @template, \@data;
      }
    }
    $self->{dbh}->finish();

    my $logRotation = {};
    if ( $logrotation_id and ( $templateType eq 'domain' ) ) {
      $logRotation = DAL::getLogrotation($logrotation_id);
    }

    my %ipPool;
    if ( $ip_pool_id and ( $templateType eq 'reseller' or $templateType eq 'client' ) ) {
      %ipPool = $self->makeTemplateIpPool($ip_pool_id);
    }

    if( $overuse_block and ('domain_addon' ne $templateType )){
        my @data = ( 'overuse' );
        if( $overuse_block eq 'true' ) {
           if ( $overuse_suspend ) {
             if ($overuse_suspend eq 'true') {
               push @data, 'block';
             } else {
               if ($overuse_notify eq 'true') {
                 push @data, 'not_suspend_notify';
               } else {
                 push @data, 'not_suspend';
               }
             }
           } else {
             push @data, 'block';
           }
        }
        elsif( $overuse_notify and $overuse_notify eq 'true' ) { push @data, 'notify'; }
        else{ push @data, 'normal'; }
        push @template, \@data;
    }

    if ($phpHandlerSettings{'php_handler_id'}) {
      my $phpHandlerId = $phpHandlerSettings{'php_handler_id'};
      push(@template, ['php_handler_id', $phpHandlerId]);
      my $phpHandlers = HostingDumper::getPhpHandlers();
      if (exists($phpHandlers->{$phpHandlerId})) {
        push(@template, ['php_handler_type', $phpHandlers->{$phpHandlerId}{'type'}]);
        push(@template, ['php_version', $phpHandlers->{$phpHandlerId}{'version'}]);
      }
    } else {
        push(@template, ['php_handler_type', $phpHandlerSettings{'php_handler_type'}]) if $phpHandlerSettings{'php_handler_type'};
        push(@template, ['php_version', $phpHandlerSettings{'php_version'}]) if $phpHandlerSettings{'php_version'};
    }

    my @templateAttrs;

    if ( defined $templateUuids{$template_id} ) {
      my @guidAttr = ( 'guid', $templateUuids{$template_id});
      push @templateAttrs, \@guidAttr;
    }

    if ( defined $templateExtIds{$template_id} ) {
      my @extIdAttr = ( 'external-id', $templateExtIds{$template_id});
      push @templateAttrs, \@extIdAttr;
    }

    if ( defined $ownerGuid ) {
      my @ownerGuidAttr = ( 'owner-guid', $ownerGuid);
      push @templateAttrs, \@ownerGuidAttr;
    }

    if ( $templateType eq 'domain_addon') {
      my @isAddonAttr = ( 'is-addon', 'true');
      push @templateAttrs, \@isAddonAttr;
    }

    my @apsFilterItems;
    my $filterType;
    if (defined($aps_bundle_filter_id)) {
      @apsFilterItems = @{DAL::getApsBundleFilterItems($aps_bundle_filter_id)};
      $filterType = DAL::getApsBundleFilterType($aps_bundle_filter_id);
    }

    my %packerData;
    $packerData{'attributes'} = \@templateAttrs;
    $packerData{'data'} = \@template;
    $packerData{'items'} = \%planItems;
    $packerData{'log-rotation'} = $logRotation;
    $packerData{'ip-pool'} = \%ipPool;
    $packerData{'aps-filter-items'} = \@apsFilterItems;
    $packerData{'aps-filter-type'} = $filterType;
    $packerData{'php-settings'} = \%phpSettings;
    $packerData{'default-db-servers'} = \@defaultDbServers;

    if ( $parentType eq 'server' ) {
      $self->{packer}->addTemplateToServer($templateType, $tmpl_name, \%packerData);
    }
    elsif ( $parentType eq 'reseller' ) {
      if ( 'domain' eq $templateType or 'domain_addon' eq $templateType) {
        $self->{packer}->addResellerDomainTemplate( $parent, $tmpl_name, \%packerData);
      }
      else {
        Logging::warning("Error: dumpTemplates: unexpected template type \"$templateType\" for parent \"$parentType\"",'assert');
      }
    }
    elsif ( $parentType eq 'client' ) {
      if ( 'domain' eq $templateType or 'domain_addon' eq $templateType) {
        $self->{packer}->addClientDomainTemplate( $parent, $tmpl_name, \%packerData);
      }
      else {
        Logging::warning("Error: dumpTemplates: unexpected template type \"$templateType\" for parent \"$parentType\"",'assert');
      }
    }
    else {
      Logging::warning("Error: dumpTemplates: Unexpected type of parent \"$parentType\"",'assert');
    }
  }
}

sub makeTemplateIpPool {
  my ( $self, $ip_pool_id ) = @_;

  my $sql = "SELECT IP_Addresses.`ip_address`, ip_pool.`type`" .
                          " FROM ip_pool".
                          " LEFT JOIN IP_Addresses ON ip_pool.`ip_address_id` = IP_Addresses.`id`" .
                          " WHERE ip_pool.`id` = ?";

  my %ipPool = ();

  if ( $self->{dbh}->execute_rownum($sql, $ip_pool_id) ) {
    while ( my $ptrRow = $self->{dbh}->fetchrow() ) {
      $ipPool{ $ptrRow->[0] } = $ptrRow->[1];
    }
  }
  $self->{dbh}->finish();

  return %ipPool;
}

sub makeDomainNode {
  my ( $self, $domainName, $isroot ) = @_;

  $self->checkProgramTerminated();

  $self->{isAnyDbDumpedOnDomain} = 0;

  if( $self->domainExcluded( $domainName ) ) {
    Logging::debug("Domain '$domainName' is excluded from dump");
    return;
  }

  foreach my $dumpedDomain( @{$self->{dumped_domains}} ){
    if( $dumpedDomain eq $domainName ){
      Logging::debug("Domain '$domainName' already dumped");
      return;
    }
  }
  push @{$self->{dumped_domains}}, $domainName;

  Logging::debug("Domain '$domainName' is started");
  Logging::beginObject('domain',$domainName, $self->generateObjectUuidForLogging('domain', $domainName));

  $self->{dump_status}->startDomain($domainName);

  my ( $sql, $ptrHash );

  Logging::debug("Getting domain info");
  my $domainPtr;
  $domainPtr = DAL::getDomainPtr($domainName);

  unless( $domainPtr ) {
    Logging::error("Failed to get domain hash for '$domainName'",'PleskDbError');
    Logging::endObject();
    return;
  }
  my %domain = %{$domainPtr};

  # There are no guid field in 'domains' table in the old Plesk, but currently it is a required parameter, so we set empty guid here and it will be replaced with non-empty value by resolver
  $domain{'guid'} = '' if !defined $domain{'guid'};

  my $domainId = $domain{'id'};
  my $clientId = $domain{'cl_id'};

  $self->_initDomainContentStat($domainId);

  my $embeddedInfo = DAL::getEmbeddedInfo(['--backup-subscription', $domain{'name'}]);

  if ( exists $domain{'vendor_id'} ){
    my $vendorGuid = PleskStructure::getClientGuidFromId( $domain{'vendor_id'} );
    $domain{'vendor-guid'} = $vendorGuid;
    my $vendorLogin = PleskStructure::getClientNameFromId( $domain{'vendor_id'} );
    $domain{'vendor-login'} = $vendorLogin;
  }

  if ( defined PleskStructure::getClientGuidFromId($clientId) ) {
    $domain{'owner-guid'} = PleskStructure::getClientGuidFromId($clientId);
	$domain{'owner-name'} = PleskStructure::getClientNameFromId($clientId);
  }

  my $clientPermsId;
  $sql = "SELECT perm_id FROM clients WHERE id = ?";

  if ( $self->{dbh}->execute_rownum($sql, $clientId) and my $ptrRow = $self->{dbh}->fetchrow() ) {
    $clientPermsId = $ptrRow->[0];
  }
  $self->{dbh}->finish();

  my $domainAsciiName;
  $domainName = $domain{'displayName'};
  $domainAsciiName = $domain{'name'};

  my $domainOwner = PleskStructure::getClientNameFromId( $domain{'cl_id'} );
  my $parentType = PleskStructure::getClientType( $domainOwner );
  if( $isroot ){
    if ($self->{admin_info} or $self->{server_settings}) {
      $self->{packer}->addAdminDomain( $domainId, $domainName, $domainAsciiName, \%domain );
    } else {
      $self->{packer}->addRootDomain( $domainId, $domainName, $domainAsciiName, \%domain, PleskStructure::getClientGuid($domainOwner) );
    }
  }
  else{
    if( $parentType eq 'admin' ){
      $self->{packer}->addAdminDomain( $domainId, $domainName, $domainAsciiName, \%domain );
    }
    elsif( $parentType eq 'reseller' ){
      $self->{packer}->addResellerDomain( $domain{'cl_id'}, $domainId, $domainName, $domainAsciiName, \%domain );
    }
    elsif( $parentType eq 'client' ){
      $self->{packer}->addClientDomain( $domain{'cl_id'}, $domainId, $domainName, $domainAsciiName, \%domain );
    }
    else{
       die "Cannot dump domain '$domainName'. Domain owner '$domainOwner' type '$parentType' is not supported!";
    }
  }

  $self->{suspender}->suspendDomain( $domainName );

  $domainAsciiName = $domain{'name'};
  my $domainType      = $domain{'htype'};

  $self->addWwwStatus( $domainId, $domainAsciiName );

  # Domain's IP address
  Logging::debug("Getting domain IP");
  my $ip = PleskStructure::getDomainIp($domainName);
  if ($ip) {
    foreach my $ipAddress (@{$ip}) {
      $self->{packer}->setDomainIP( $domainId, $ipAddress, PleskStructure::getClientIpType( $domainOwner, $ipAddress ) );
    }
  }

  #
  # Status
  #
  Logging::debug("Dumping domain status");

  my $status = 0;
  my $siteStatus = undef;

  $status = $domain{'status'};
  $status += 0;
  $status = $domain{'webspace_status'};
  $siteStatus = $domain{'status'};

  $self->{packer}->setWebspaceStatus( $domainId, $status, $siteStatus );

  #
  # No further info required if shallow dump specified
  #
  if ( $self->{shallow_dump} && !$self->{only_mail_dump} ) {

    # Need to dump information about all databases for DbServers mapping

    Logging::debug("Dumping domain databases");
    foreach my $db (@{DAL::getDomainDatabases($domainId)}) {
      eval {
        $self->makeDatabaseNode( $db->{'id'}, $domainId, 'domain', undef, undef, $embeddedInfo );
        1;
      } or do {
        $self->checkProgramTerminated();
        $self->excludeProblemDatabaseFromBackup( $domainId, $db, $@ );
      }
    }

    eval {
      # Domain's DNS settings
      $self->makeDomainDnsZone( \%domain );

      if ((!defined $ip) && ( $domainType ne 'vrt_hst' ) ) {
        my $preferredIp = $self->{packer}->getDomainARecordIp($domainId);
        my $type;
        $ip = PleskStructure::getIp4DomainWoHosting($domainId, $preferredIp, \$type);
        if ( $ip ) {
          foreach my $ipAddress (@{$ip}) {
            $self->{packer}->setDomainIP( $domainId, $ipAddress, $type );
          }
        } else {
          my $serviceIps = $self->getServiceIps( $domainId, 'mail', $ip );
          if ($serviceIps) {
            foreach my $serviceIp (keys %{$serviceIps}) {
              $self->{packer}->setDomainIP( $domainId, $serviceIp, $serviceIps->{$serviceIp} );
              last;
            }
          }
        }
      }
      1;
    } or do {
      $self->excludeProblemDnsZoneFromBackup( $domainId, $@ );
    };

    $self->{packer}->removeDomainDnsZone($domainId);

    $self->{suspender}->unSuspendDomain();

    $self->{packer}->finishDomain($domainId);
    $self->{dump_status}->endDomain($domainName);
    Logging::endObject();
    $self->{packer}->{stat}{dbDumpsCount}++ if ($self->{isAnyDbDumpedOnDomain} == 1);
    return;
  }

  # Check whether this domain is default on IP

  $sql = "SELECT COUNT(*) FROM IP_Addresses WHERE default_domain_id=?";
  if ( $self->{dbh}->execute_rownum($sql, $domainId) ) {
    if ( @{ $self->{dbh}->fetchrow() }[0] ne "0" ) {
      $self->{packer}->setDomainDefault($domainId);
    }
  }
  $self->{dbh}->finish();

  if ( !$self->{only_mail_dump} ) {
    # Domain's aliases
    $self->dumpDomainAliases($domainId);
  }

  my $domParams = $self->getDomainParams(\%domain);

  Logging::debug("Getting domain limits and permissions");

  if ( !$self->{only_mail_dump} ) {
    my $subscriptionProperties = DAL::getDomainSuscriptionProperties($domainId);
    $self->addDomainLimits( $domainId, $subscriptionProperties->{'limitsId'} );
    $self->addDomainOverusePolicy( $domainId, $subscriptionProperties );
    # The following call of fixDefaultLimits should be before permissions dumping to avoid validation by schema fails
    $self->{packer}->fixDefaultLimits('domain',$domainId);
    $self->dumpDomainPersonalPermissions( $domainId, $subscriptionProperties->{'permissionsId'} );
  }

  if ( !$self->{only_hosting_dump} ) {
    eval {
      $self->dumpMailSystem( $parentType, $domParams, \%domain, $isroot, $ip );
      1;
    } or do {
      $self->checkProgramTerminated();
      $self->excludeProblemMailSystemFromBackup( $domainId, $@ );
    }
  } else {
    Logging::debug("Skip domain mailsystem dump due to settings");
  }

  my @SiteApplications;
  my @excluded_dbs;
  my @excluded_dns_records;
  my $applicationsInstalled = 1;

  $applicationsInstalled = DAL::isInstalledApplications($domainId);

  #-----------------------------------------------------------------
  # Information about domain's site applications
  # should be extracted prior to database/hosting/custom-buttons/dns-records
  # dump, as site applications' linked resources should be dumped
  # separately.
  #-----------------------------------------------------------------

  if ($applicationsInstalled) {
    @SiteApplications = SiteApp::getDomainSiteapps($domainId, $self->{dbh});

    foreach my $sapp (@SiteApplications) {
      foreach my $row ( $sapp->getDatabases() ) {
        push @excluded_dbs, $row->{'id'};
      }

      foreach my $row ( $sapp->getDnsRecords() ) {
        push @excluded_dns_records, $row->{'id'};
      }
    }
  }

  if ( !$self->{only_mail_dump} ) {
    #-----------------------------------------------------------------
    # Domain's databases
    #-----------------------------------------------------------------
    Logging::debug("Dumping domain databases");

    $self->_validateDatabaseIncluded($domainId, $domainName);

    # Databases without those used in site apps
    foreach my $db (@{DAL::getDomainDatabases($domainId, \@excluded_dbs)}) {
      eval {
        $self->makeDatabaseNode( $db->{'id'}, $domainId, 'domain', undef, undef, $embeddedInfo );
        1;
      } or do {
        $self->checkProgramTerminated();
        $self->excludeProblemDatabaseFromBackup( $domainId, $db, $@ );
      }
    }

    Logging::debug("Dumping database users belonging to any database");
    $self->{packer}->makeDbUsersToAnyDatabasesNode( $domainId, DAL::getCommonDatabaseUsers( $domainId, $embeddedInfo ) );
  } else {
    Logging::debug("Skip dumping domain databases due to settings");
  }

  eval {
    # Domain's DNS settings
    $self->makeDomainDnsZone( \%domain, \@excluded_dns_records );
    if ((!defined $ip) && ( $domainType ne 'vrt_hst' ) ) {
      my $preferredIp = $self->{packer}->getDomainARecordIp($domainId);
      my $type;
      $ip = PleskStructure::getIp4DomainWoHosting($domainId, $preferredIp, \$type);
      if ( $ip ) {
        foreach my $ipAddress (@{$ip}) {
          $self->{packer}->setDomainIP( $domainId, $ipAddress, $type );
        }
      } else {
        my $serviceIps = $self->getServiceIps( $domainId, 'mail', $ip );
        if ($serviceIps) {
          foreach my $serviceIp (keys %{$serviceIps}) {
            $self->{packer}->setDomainIP( $domainId, $serviceIp, $serviceIps->{$serviceIp} );
            last;
          }
        }
      }
    }
    1;
  } or do {
    $self->excludeProblemDnsZoneFromBackup( $domainId, $@ );
  };

  $self->{packer}->makeDomainParamsNode($domainId, $domParams);

  $self->makeMailListsNode($domainId, $domainAsciiName, $ip);

  if ( !$self->{only_mail_dump} ) {
    Logging::debug("Dumping domain statistics");

    $self->addDomainTraffic($domainId);

    $self->addDomainCertificates($domainId, $domainName, defined($domain{'cert_rep_id'}) ? $domain{'cert_rep_id'} : '');
  }

  my $subscriptionProperties = undef;

  $subscriptionProperties = $self->getSubscriptionPropertiesHash($domainId, 'domain');

  if (exists $subscriptionProperties->{'phpSettingsId'}) {
      my $phpSettingsId = $subscriptionProperties->{'phpSettingsId'};
      my %phpSettings = %{DAL::getPhpSettings($phpSettingsId)};
      my $phpSettingsCustom = DAL::getPhpSettingsCustom($phpSettingsId);
      $phpSettings{'notice'} = $phpSettingsCustom if defined $phpSettingsCustom;
      $self->{packer}->setDomainPhpSettingsNode($domainId, \%phpSettings);
  }

  eval {
    my $serviceIps = $self->getServiceIps( $domainId, 'web', $ip);

    if ( $domainType eq "vrt_hst" ) {
      $self->makePhostingNode( \%domain, $domParams, \@SiteApplications, 0, $serviceIps, $embeddedInfo);
    }
    elsif ( $domainType eq "std_fwd" ) {
      $self->makeShostingNode( \%domain, $serviceIps );
    }
    elsif ( $domainType eq "frm_fwd" ) {
      $self->makeFhostingNode( \%domain, $serviceIps );
    }
    1;
  } or do {
    $self->checkProgramTerminated();
    $self->excludeProblemHostingFromBackup( $domainId, $domainType, $@ );
  };

  my $subscriptionNode;

  if ( !$self->{only_mail_dump} ) {
    $self->getCustomButtonsByOwner( 'domain-admin', $domainId );

    $subscriptionNode = $self->getSubscription('domain', $domainId);
    $self->{packer}->dumpUnityMobileIntegration($domainId, $domParams);
    $self->getSubscriptionProperties('domain', $domainId);
    $self->dumpApsBundle($domainId, 'domain');

    my @defaultDbServers;
    my @availableTypes = ('default_server_mysql','default_server_postgresql');
    foreach my $defaultDbServerType (@availableTypes) {
      if ( exists $subscriptionProperties->{$defaultDbServerType}) {
        foreach my $ptrHash (@{DAL::getDatabaseServers()}) {
          if ($subscriptionProperties->{$defaultDbServerType} == $ptrHash->{'id'}) {
            push @defaultDbServers, $ptrHash;
          }
        }
      }
    }

    if (@defaultDbServers) {
      $self->{packer}->makeDomainDefaultDbServersNode($domainId, \@defaultDbServers);
    }

    if (ref($subscriptionNode) =~ /XmlNode/) {
      $self->{packer}->makeSubscriptionPropertiesNode($subscriptionNode, $subscriptionProperties);
    }

    if ($applicationsInstalled) {
      foreach my $sapp (@SiteApplications) {
        my $apsRegistryId = $sapp->getApplicationApsRegistryId();
        foreach my $row ( $sapp->getMailUsers() ) {
          $self->{packer}->assignApplicationInfoToMailUser($apsRegistryId, $row);
        }
      }
    }
  }

  $self->{suspender}->unSuspendDomain();

  if ( exists $domainPtr->{'description'} ) {
    $self->{packer}->makeDomainDescriptionsNode( $domainId, $domainPtr->{'description'} );
  }

  if (!$self->{only_mail_dump}) {
    $self->{packer}->setComposerInstances($domainId, $embeddedInfo);
  }

  $self->{packer}->makeExtensionNode('subscription', $domainId);

  $self->{packer}->{stat}{dbDumpsCount}++ if ($self->{isAnyDbDumpedOnDomain} == 1);

  $self->{dump_status}->endDomain($domainName);
  $self->{packer}->finishDomain($domainId);
  Logging::endObject();
}

sub dumpMailSystem {
  my ( $self, $parentType, $domParams, $domain, $isroot, $ip ) = @_;

  $self->checkProgramTerminated();

  Logging::debug("Dumping domain mailsystem");

  my $domainId = $domain->{'id'};
  my $domainName = $domain->{'displayName'};
  my $domainAsciiName = $domain->{'name'};
  my $isSite = exists $domain->{'webspace_id'} ? $domain->{'webspace_id'} ne '0' : 0;

  my $mailServiceStatus = $self->getDomainMailStatus($domainId);
  if ( defined($mailServiceStatus) ) {
    my $serviceIps = $self->getServiceIps( $domainId, 'mail', $ip );
    $self->{packer}->setDomainMailService( $domainId, $mailServiceStatus, $serviceIps );

    my @mails = @{DAL::getDomainMails($domainId)};
    if ( @mails ) {

      if ($self->{dump_full_mail}) {
        $self->{packer}->setDomainMailNamesContent( $domainId, $domainName,
          AgentConfig::getPleskMailnamesDir($domainAsciiName, undef),
          AgentConfig::mailContentUser());
      }

      foreach my $ptrRow (@mails) {
        $ptrRow->[2] = CommonPacker::normalizePasswordType( $ptrRow->[2] );
        eval {
          $self->makeMailUserNode( $domainId, $ptrRow->[0], $ptrRow->[1], $ptrRow->[2], $domainName, $domainAsciiName, $parentType, $domain->{'cl_id'} );
          1;
        } or do {
          $self->excludeProblemMailUserFromBackup( $domainId, $ptrRow, $@ );
        }
      }
      $self->{packer}->removeMailSystemChildNodeIfEmpty( $domainId, 'mailuser' );
    }

    $self->getCatchAllAddress($domainId);

    $self->getDomainKeysDomainSupport( $domainId, $domainName,  $domain->{'dns_zone_id'} );

    my $oldWebMailFlag = undef;
    my $webmail = exists $domParams->{'webmail'} ?  $domParams->{'webmail'} : defined($oldWebMailFlag) ? 'horde' : 'none';
    my $cert = undef;
    if (exists $domParams->{'webmail_certificate_id'}) {
      my $pk = $self->getDomainCertificatePrivateKey($domParams->{'webmail_certificate_id'}, $domainId);
      $cert = $self->getCertificateRef($pk);
    }

    $self->{packer}->setDomainWebMail( $domainId, $webmail, $cert );
    $self->{packer}->setDomainGLSupport( $domainId, (exists $domParams->{'gl_filter'} ) ? $domParams->{'gl_filter'} : 'on' );

    my $sql = "SELECT `parameter`, `value` FROM Parameters INNER JOIN DomainServices ON Parameters.id = DomainServices.parameters_id WHERE DomainServices.dom_id = ? AND Parameters.`parameter` LIKE 'outgoing_messages_%'";
    if ( $self->{dbh}->execute_rownum($sql, $domainId) ) {
      while (my $ptrRow = $self->{dbh}->fetchrow() ) {
        $self->{packer}->makeOutgoingMessagesParameter('mailsystem', $domainId, $ptrRow->[0], $ptrRow->[1]);
      }
    }
    $self->{dbh}->finish();

  }
}

sub getDomainMailStatus {
  my ($self, $domainId) = @_;
  return DAL::getDomainServiceStatus( $domainId, 'mail' );
}

sub getDomainMaillistStatus {
  my ($self, $domainId) = @_;
  return DAL::getDomainServiceStatus( $domainId, 'maillists' );
}

sub getDomainDefaultCert {
  my $self = shift;
  return DAL::getDomainDefaultCert102(@_);
}

sub makeSiteNode {
  my ( $self, $domainAsciiName, $domainName, $ptrApplications, $embeddedInfo ) = @_;

  $self->checkProgramTerminated();

  my ( $sql, $ptrHash );

  Logging::beginObject( 'domain', $domainName, $self->generateObjectUuidForLogging( 'domain', $domainName ) );

  my $domainPtr = DAL::getDomainPtrByAsciiName($domainAsciiName);
  unless( $domainPtr ) {
    Logging::error("Failed to get domain hash for '$domainName'",'PleskDbError');
    Logging::endObject();
    return;
  }
  my %domain = %{$domainPtr};

  my $domainId = $domain{'id'};
  my $webspaceId = $domain{'webspace_id'};

  my $domainType      = $domain{'htype'};

  my $domainOwner = PleskStructure::getClientNameFromId( $domain{'cl_id'} );
  my $parentType = PleskStructure::getClientType( $domainOwner );


  $self->{packer}->addDomainSite( $webspaceId, $domainId, $domainAsciiName, $domainName, \%domain );

  $self->{suspender}->suspendDomain( $domainName );

  $self->addWwwStatus( $domainId, $domainAsciiName );

  #
  # Status
  #
  Logging::debug("Dumping site status");

  my $status = $domain{'status'};
  $self->{packer}->setDomainStatus( $domainId, $status );

  if ( !$self->{only_mail_dump} ) {
    # Domain's aliases
    $self->dumpDomainAliases($domainId);
  }

  #
  # No further info required if shallow dump specified
  #
  if ( $self->{shallow_dump} && !$self->{only_mail_dump} ) {
    $self->{suspender}->unSuspendDomain();
    $self->{packer}->finishDomain($domainId);
    Logging::endObject();
    return;
  }

  my $domParams = $self->getDomainParams(\%domain);

  if ( !$self->{only_hosting_dump} ) {
    eval {
      $self->dumpMailSystem($parentType, $domParams, \%domain, 0, undef);
      1;
    } or do {
      $self->checkProgramTerminated();
      $self->excludeProblemMailSystemFromBackup( $domainId, $@ );
    }
  }
  else { Logging::debug("Skip site mailsystem dump due to settings"); }

  my @SiteApplications;
  my @excluded_dbs;
  my @excluded_dns_records;
  my $applicationsInstalled = 1;

  $applicationsInstalled = DAL::isInstalledApplications($webspaceId);

  #-----------------------------------------------------------------
  # Information about domain's site applications
  # should be extracted prior to database/hosting/custom-buttons/dns-records
  # dump, as site applications' linked resources should be dumped
  # separately.
  #-----------------------------------------------------------------

  if ($applicationsInstalled) {

    @SiteApplications = @{$ptrApplications};

    foreach my $sapp (@SiteApplications) {
      foreach my $row ( $sapp->getDatabases() ) {
        push @excluded_dbs, $row->{'id'};
      }

      foreach my $row ( $sapp->getDnsRecords() ) {
        push @excluded_dns_records, $row->{'id'};
      }
    }
  }

  eval {
    # Domain's DNS settings
    $self->makeDomainDnsZone( \%domain, \@excluded_dns_records );
    1;
  } or do {
    $self->excludeProblemDnsZoneFromBackup( $domainId, $@ );
  };

  $self->{packer}->makeDomainParamsNode($domainId, $domParams);

  $self->makeMailListsNode($domainId, $domainAsciiName);

  if ( !$self->{only_mail_dump} ) {
    Logging::debug("Dumping site statistics");

    $self->addDomainTraffic($domainId);

    $self->addDomainCertificates($domainId, $domainName, defined($domain{'cert_rep_id'}) ? $domain{'cert_rep_id'} : '');

    if ( $domainType eq "vrt_hst" ) {
      $self->makePhostingNode( \%domain, $domParams, \@SiteApplications, 1, undef, $embeddedInfo );
    }
    elsif ( $domainType eq "std_fwd" ) {
      $self->makeShostingNode( \%domain );
    }
    elsif ( $domainType eq "frm_fwd" ) {
      $self->makeFhostingNode( \%domain );
    }

    $self->getCustomButtonsByOwner( 'domain-admin', $domainId );

    $self->getSubscription('domain', $domainId);
    $self->{packer}->dumpUnityMobileIntegration($domainId, $domParams);
  }

  if ( exists $domainPtr->{'description'} ) {
    $self->{packer}->makeDomainDescriptionsNode( $domainId, $domainPtr->{'description'} );
  }

  $self->{packer}->makeExtensionNode('site', $domainId);
  $self->{suspender}->unSuspendDomain();
  $self->{packer}->finishDomain($domainId);
  Logging::endObject();
}

sub makeDomainDnsZone {
  my ( $self, $domainHashPtr, $excludedRecordsPtr) = @_;

  Logging::debug("Dumping domain DNS");

  # dns_zone_id could be null for wildcard subdomains
  if ( $domainHashPtr->{'dns_zone_id'} != 0) {
    return $self->makeDnsZone( $domainHashPtr->{'dns_zone_id'}, $domainHashPtr->{'id'}, 'domain', $excludedRecordsPtr );
  }
}

sub makeDnsRecord {
  my ( $self, $ptrHash) = @_;

  return unless $ptrHash->{'type'}    =~ /^A|AAAA|NS|MX|CNAME|PTR|TXT|master|SRV|AXFR|DS|CAA$/;

  if ( $ptrHash->{'type'} eq 'TXT' ) {
    $ptrHash->{'val'} =~ s/"(.*)"/$1/;
  }

  # Fix broken CNAME mail records (bug #110731)
  if (  $ptrHash->{'type'} eq 'CNAME'
    and $ptrHash->{'host'} eq "mail." . $ptrHash->{'val'} )
  {
    $ptrHash->{'type'} = 'A';
    $ptrHash->{'val'} =~ s/\.$//;
    if (defined($ptrHash->{'displayVal'})) {
      delete($ptrHash->{'displayVal'});
    }
    my $ips = PleskStructure::getDomainIp( $ptrHash->{'val'} ) ;
    foreach my $ip (@{$ips}) {
        if ($ip =~/^\d+\.\d+\.\d+\.\d+$/) {
            $ptrHash->{'val'} = $ip;
        }
    }
  }

  return %{$ptrHash};
}

sub geteDnsZone {
  my ( $self, $dnsZoneId, $paramsPtr, $recordsPtr, $excludedRecordsPtr ) = @_;

  my $sql = "SELECT * FROM dns_zone WHERE id=?";

  if ( !$self->{dbh}->execute_rownum($sql, $dnsZoneId) ) {
    $self->{dbh}->finish();
    my $msg = "Broken referencial integrity: DNS zone id $dnsZoneId is not found in dns_zone";
    print STDERR "$msg\n";
    Logging::warning($msg,'BrokenDbIntegrity');
    return;
  }

  if ( my $hashPtr = $self->{dbh}->fetchhash($sql) ) {
    %{$paramsPtr} = %{ $hashPtr };
  }

  $self->{dbh}->finish();

  $sql = "SELECT dns_recs.*, dns_refs.status FROM dns_recs LEFT OUTER JOIN dns_refs ON dns_refs.zoneRecordId = dns_recs.id WHERE dns_zone_id = ?";
  my @sqlParams = ($dnsZoneId);
  if( ref($excludedRecordsPtr) =~ /ARRAY/ ) {
    my @excludeIds = @{$excludedRecordsPtr};
    if ( @excludeIds ) {
      $sql .= " AND id NOT IN (" . join(',', ("?")x@excludeIds) . ")";
      @sqlParams = (@sqlParams, @excludeIds);
    }
  }

  if ( $self->{dbh}->execute_rownum($sql, @sqlParams) ) {

    while ( my $ptrHash = $self->{dbh}->fetchhash() ) {
      my %dnsrec = $self->makeDnsRecord($ptrHash);
      if (%dnsrec) {
        push @{$recordsPtr}, \%dnsrec;
      }
    }
  }
  $self->{dbh}->finish();
}

sub makeDnsZone{
  my ( $self, $dnsZoneId, $parent, $parentType, $excludedRecordsPtr ) = @_;

  my %params;
  my @records;
  $self->geteDnsZone( $dnsZoneId, \%params, \@records, $excludedRecordsPtr );

  if ( 'domain' eq $parentType ) {
    $self->{packer}->setDomainDnsZone( $parent, \%params, \@records );
  }
  else {
    Logging::warning('Error: makeDnsZone: Unexpected type of parent \"$parentType\"','assert');
  }
}

sub dumpDomainAliases {
  my ( $self, $domainId ) = @_;

  Logging::debug( 'Dumping domain aliases... ', 1 );

  my $aliases = DAL::getDomainAliases($domainId);

  foreach my $alias (@{$aliases}) {

    my %params;
    my @records;
    if ( $alias->{'dns_zone_id'} != 0 ) {
      $self->geteDnsZone( $alias->{'dns_zone_id'}, \%params, \@records );
    }

    $self->{packer}->addDomainAlias( $domainId, $alias, \%params, \@records );
  }

  Logging::debug('OK');
}

sub addWwwStatus {
  my ( $self, $domainId, $domainName ) = @_;

  my $sql;

  # Domain is considered to have 'www' prefix when there is 'CNAME', 'A' or 'AAAA' record with 'www' name.
  # While domain has CNAME record Plesk prohibits creation of 'www' alias or subdomain.
  # If user remove CNAME records then it may create 'www' alias and 'www' A-records will be added to zone.
  # When deployer will restore backup of such domain then it will first create CNAME record (due to 'www' domain flag)
  # and then it will try to restore 'www' alias and will fail due to conflict.
  # So 'www' flag for domain is set only if CNAME records is found, 'A' and 'AAAA' are not checked.
  $sql = "SELECT r.* FROM dns_recs r, domains d WHERE d.id=? "
    . "AND d.dns_zone_id=r.dns_zone_id AND r.type = 'CNAME' AND r.host = '?'";

  if ( $self->{dbh}->execute_rownum($sql, $domainId, "www.$domainName.") ) {
    $self->{packer}->setDomainWwwStatus( $domainId, 'true' );
  }
  else {
    $self->{packer}->setDomainWwwStatus( $domainId, 'false' );
  }
  $self->{dbh}->finish();
}

sub addSpecificUserAndRole {
  my ( $self, $parentType, $userName, $roleName, $userHash, $rolePermsHash, $ownerId ) = @_;

  my $callback;
  eval {
    if( $parentType eq 'root' ) {
      $callback = sub {
        $self->{packer}->removeRootUser( @_ );
      };
      $self->{packer}->addRootUser( $userName, $userHash);
    }
    elsif( $parentType eq 'admin' ) {
      $callback = sub {
        $self->{packer}->removeAdminUser( @_ );
      };
      $self->{packer}->addAdminUser( $userName, $userHash);
    }
    elsif( $parentType eq 'reseller' || $parentType eq 'client') {
      $callback = sub {
        $self->{packer}->removeClientUser( $ownerId, @_ );
      };
      $self->{packer}->addClientUser( $ownerId, $userName, $userHash);
    }
    1;
  } or do {
    $self->excludeProblemUserFromBackup( $userName, $callback, $@ );
  };

  eval {
    if( $parentType eq 'root' ) {
      $callback = sub {
        $self->{packer}->removeRootRole( @_ );
      };
      $self->{packer}->addRootRole( $roleName, 0, $rolePermsHash, []);
    }
    elsif( $parentType eq 'admin' ) {
      $callback = sub {
        $self->{packer}->removeAdminRole( @_ );
      };
      $self->{packer}->addAdminRole( $roleName, 0, $rolePermsHash, []);
    }
    elsif( $parentType eq 'reseller' || $parentType eq 'client') {
      $callback = sub {
        $self->{packer}->removeClientRole( $ownerId, @_ );
      };
      $self->{packer}->addClientRole( $ownerId, $roleName, 0, $rolePermsHash, []);
    }
    1;
  } or do {
    $self->excludeProblemRoleFromBackup( $callback, $roleName, $@ );
  };
}

sub getTrafficValue {
  my ( $self, $tableName, $idName, $idValue ) = @_;

  my $trafficValue = '';

  my $sql = "SELECT http_in, http_out, ftp_in, ftp_out, smtp_in, smtp_out, "
      . "pop3_imap_in, pop3_imap_out, date FROM $tableName WHERE $idName = ?";

  if ( $self->{dbh}->execute_rownum($sql, $idValue) ) {
     my @keys = ( 'http', 'ftp', 'smtp', 'pop3-imap' );
     my ( $key, $i, $ptrRow );
     while ( $ptrRow = $self->{dbh}->fetchrow() ) {
       for ( $i = 0 ; $i < @keys * 2 ; ++$i ) {
         if ( $ptrRow->[$i] ) {
            $trafficValue .= $ptrRow->[8];
            $trafficValue .= ' ';
            $trafficValue .= $keys[ $i / 2 ];
            $trafficValue .= ' ';
            $trafficValue .= ( $i % 2 ? 'out' : 'in' );
            $trafficValue .= ' ';
            $trafficValue .= $ptrRow->[$i];
            $trafficValue .= "\n";
        }
      }
    }
  }
  $self->{dbh}->finish();
  return $trafficValue;
}

#
# addDomainTraffic - add current traffic
#
#     arguments:
#                 $domainId - ID of domain
#
sub addDomainTraffic {
  my ( $self, $domainId ) = @_;

  my $trafficValue = '';

  $trafficValue = $self->getTrafficValue( 'DomainsTraffic', 'dom_id', $domainId );

  $self->{packer}->setDomainTraffic( $domainId, $trafficValue ) if $trafficValue;
}

sub addDomainCertificates {
  my ($self, $domainId, $domainName, $domainCertRepId) = @_;

    my @defaultCerts = @{$self->getDomainDefaultCert($domainId)};
    my @domainCerts = @{DAL::getCertificateIds($domainCertRepId)};
    foreach my $id (@domainCerts) {
      $self->makeCertificateNode($id, $domainId, 'domain', grep {$_ eq $id} @defaultCerts ? 1 : 0);
    }

    if ($self->{include_app_distrib}) {
      my %hostingParams = %{DAL::getHostingParams($domainId)};
      if (exists($hostingParams{'certificate_id'}) and $hostingParams{'certificate_id'} != 0) {
        my $id = $hostingParams{'certificate_id'};
        if (!(grep {$_ eq $id} @domainCerts)) {
          $self->{packer}->setServerSettings() unless defined $self->{packer}->{serverNode};
          $self->makeCertificateNode($id, 0, 'server', 0);
        }
      } else {
        foreach my $id (@defaultCerts) {
          if (!(grep {$_ eq $id} @domainCerts)) {
            $self->{packer}->setServerSettings() unless defined $self->{packer}->{serverNode};
            $self->makeCertificateNode($id, 0, 'server', 0);
          }
        }
      }
    }
}

sub addClientTraffic {
  my ( $self, $clientId ) = @_;

  my $trafficValue = '';

  $trafficValue = $self->getTrafficValue( 'ClientsTraffic', 'cl_id', $clientId );
  $self->{packer}->setClientTraffic( $clientId, $trafficValue ) if $trafficValue;
}

# There are 3 types of nonexistent user mail handling:
# Bounce with message (bounce:message text)
# Catch to address (email@address)
# SMTP reject (reject)
# Returns bounce|catch|reject or empty string
sub getNonexistentMode {
  my ( $self, $domainId ) = @_;

  my $sql = "SELECT p.value FROM Parameters p, DomainServices ds "
    . "WHERE ds.parameters_id = p.id AND ds.dom_id = ? AND p.parameter = 'nonexist_mail'";
  my $mode;
  if ( $self->{dbh}->execute_rownum($sql, $domainId) ) {
    if (my $rowPtr = $self->{dbh}->fetchrow()) {
      $mode = @{ $rowPtr }[0];
    }
  }
  else {
    $mode = "bounce:This address no longer accepts mail.";
  }
  $self->{dbh}->finish();

  return $mode;
}

sub getCatchAllAddress {
  my ( $self, $domainId ) = @_;

  my $sql;
  my @sqlParams = ();

  my $mode = $self->getNonexistentMode($domainId);

  if ( $mode =~ /^catch$/ ) {
    $sql = "SELECT p.value FROM Parameters p, DomainServices ds "
      . "WHERE ds.parameters_id = p.id AND ds.dom_id = ? AND p.parameter = 'catch_addr'";
    push(@sqlParams, $domainId);
  }
  elsif ( $mode =~ /^bounce$/ ) {
    $sql = "SELECT CONCAT('bounce:', p.value) FROM Parameters p, DomainServices ds "
      . "WHERE ds.parameters_id = p.id AND ds.dom_id = ? AND p.parameter = 'bounce_mess'";
    push(@sqlParams, $domainId);
  }
  elsif ( $mode =~ /^reject$/ ) {
    $sql = "SELECT 'reject'";
  }

  # some not supported mode or default parameter [bug 43901]
  return unless ($sql);

  if ( $self->{dbh}->execute_rownum($sql, @sqlParams) ) {
    if ( my $rowPtr = $self->{dbh}->fetchrow() ) {
      my $catchAllAddr = @{ $rowPtr }[0];
      if ($catchAllAddr) {
        $self->{packer}->setDomainCatchMail( $domainId, $catchAllAddr );
      }
    }
  }
  $self->{dbh}->finish();
}

sub makeMailmanMailListNode {
  my ( $self, $domainId, $mlistId, $mlistName, $mlistState ) = @_;

  unless ( defined Mailman::version() ) {
    Logging::debug("Unable to found Mailman installation");
    return;
  }

  my @owners = Mailman::getListOwners($mlistName);
  if ( !@owners ) {
    Logging::debug("Bad maillist $mlistName, skipped");
    return;
  }

  my %listMembers = Mailman::getListMembers($mlistName);

  $self->{packer}->addDomainMailList( $domainId, $mlistName,
    Mailman::getListPassword($mlistName),
    $mlistState, \@owners, \%listMembers );
}

my %dumpedCerts;

sub makeCertificateNode {
  my ( $self, $certId, $parent, $parentType, $default ) = @_;

  return if $dumpedCerts{$certId};

  my ( $sql, %cert, $item );

  $sql = "SELECT * FROM certificates WHERE id=?";
  unless ( $self->{dbh}->execute_rownum($sql, $certId) ) {
    $self->{dbh}->finish();
    my $msg = "Broken referencial integrity: certificate ID '$certId' is not found";
    print STDERR "$msg\n";
    Logging::warning($msg,'BrokenDbIntegrity');
    return;
  }

  if ( my $hashPtr = $self->{dbh}->fetchhash() ) {
    %cert = %{ $hashPtr };
  }
  $self->{dbh}->finish();

  my $cert;
  my $csr;
  my $ca_cert;
  my $name;

  $cert    = $cert{'cert'};
  $csr     = $cert{'csr'};
  $ca_cert = $cert{'ca_cert'};

  $name = $cert{'name'};

  my $pvt_key = $cert{'pvt_key'};

  if ( 'server' eq $parentType ) {
    $self->{packer}->addServerCertificate( $name, $cert, $csr, $ca_cert, $pvt_key, $default );
  }
  elsif ( 'domain' eq $parentType ) {
    $self->{packer}->addDomainCertificate( $parent, $name, $cert, $csr, $ca_cert, $pvt_key, $default );
  }
  else {
    Logging::warning('Error: makeCertificateNode: unexpected parent type','assert');
  }
  $dumpedCerts{$certId} = 1;
}

sub makeDatabaseNode {
  my ( $self, $dbId, $parent, $parentType, $sappId, $sappDb, $embeddedInfo ) = @_;

  $self->checkProgramTerminated();

  my ( $ptrHash, $item, $charset, $dbServerId, $dbServerHost, $dbServerPort, $skipContent, $dbServerVersion );

  $skipContent = 0;

  my $dbRow;
  my $sql = "SELECT name, type, external_id, host FROM data_bases AS d LEFT JOIN DatabaseCustomHosts AS h ON d.id=h.id WHERE d.id = ?";
  unless ( ( $self->{dbh}->execute_rownum($sql, $dbId) ) && ( $dbRow = $self->{dbh}->fetchrow() ) ) {
    $self->{dbh}->finish();
    my $msg = "Broken referencial integrity: Database id $dbId is not found in data_bases";
    print STDERR "$msg\n";
    Logging::warning($msg ,'BrokenDbIntegrity');
    return;
  }
  my ( $dbName, $dbType ) = @{ $dbRow };
  my %optional;
  $optional{'external_id'} = $dbRow->[2];
  $optional{'custom-host'} = $dbRow->[3];
  $self->{dbh}->finish();

  $dbName = HelpFuncs::trim($dbName);

  if (!$self->_isDatabaseIncluded($dbId)) {
    Logging::debug("Database #$dbId '$dbName' is not included, skip it.");
    return;
  }

  Logging::beginObject($dbType,$dbName, undef);

  if ( $dbType eq "postgres" or $dbType eq "postgresql" ) {
    $dbType = "postgresql";
  }

  my %dbServer;

  $sql = "SELECT host, port, ds.type, ds.server_version FROM DatabaseServers as ds, data_bases as db WHERE "
       . "ds.id = db.db_server_id AND db.id = ?";
  if ( $self->{dbh}->execute_rownum($sql, $dbId) ) {
    if( my $ptrRow = $self->{dbh}->fetchrow() ) {
      $dbServerHost = ( $ptrRow->[0] ne 'localhost' ) ? $ptrRow->[0] : 'localhost';
      $dbServerPort = $ptrRow->[1];
      $dbServerVersion = $ptrRow->[3];

      $dbServer{'type'} = $ptrRow->[2];
      $dbServer{'host'} = $ptrRow->[0];
      $dbServer{'port'} = $ptrRow->[1];
    }
  }
  $self->{dbh}->finish();

  my @dbUsers;
  if ( defined $sappDb ) {
    $optional{'sapp-param'} = $sappDb->{'param'} if ($sappDb->{'param'});
    $optional{'aps-registry-id'} = $sappDb->{'apsRegistryId'} if exists $sappDb->{'apsRegistryId'};
    $optional{'db-existent'} = $sappDb->{'db-existent'} if exists $sappDb->{'db-existent'};
    $optional{'prefix'} = $sappDb->{'prefix'} if exists $sappDb->{'prefix'};
  }
  my %contentDescription;

  if ( !$self->{shallow_dump} ) {
    Logging::debug("Database $dbName");

    foreach my $dbUserHash ( @{DAL::getDatabaseUsers( $dbId, $embeddedInfo )} ) {
        $dbUserHash->{'password'} ||= '';    # NULL -> ''
        my %item;
        $item{'login'}    = $dbUserHash->{'login'};
        $item{'password'} = $dbUserHash->{'password'};
        $item{'type'}     = CommonPacker::normalizePasswordType( $dbUserHash->{'type'} );
        $item{'id'}       = $dbUserHash->{'id'};
        $item{'external_id'} = $dbUserHash->{'external_id'} if $dbUserHash->{'external_id'};
        if ( defined $sappDb->{'apsCreatedUser'} and $sappDb->{'apsCreatedUser'} eq $dbUserHash->{'login'} ) {
          $item{'aps-registry-id'} = $sappDb->{'apsRegistryId'};
        }

        if (exists $dbUserHash->{'default_user_id'} && $dbUserHash->{'default_user_id'} eq $dbUserHash->{'id'}) {
           $item{'default'} = '';
        }

        if (defined $sappDb && exists $sappDb->{'db-user-existent'}) {
          $item{'db-user-existent'} = $sappDb->{'db-user-existent'};
        }

        if (exists $dbUserHash->{'acl'}) {
          $item{'acl'} = $dbUserHash->{'acl'};
        }
        if (exists($dbUserHash->{'privileges'})) {
          $item{'privileges'} = $dbUserHash->{'privileges'};
        }

        push @dbUsers, \%item;
    }

    my ( $dbUser, $dbPasswd );

    $sql = "SELECT admin_login, admin_password, ds.id FROM DatabaseServers as ds, data_bases as db "
         . "WHERE ds.id = db.db_server_id AND db.id = ?";
    my $ptrRow;
    unless ( ( $self->{dbh}->execute_rownum($sql, $dbId) ) && ( $ptrRow = $self->{dbh}->fetchrow() ) ) {
      $self->{dbh}->finish();
      my $msg = "Broken referencial integrity: DatabaseServers vs data_bases for db id $dbId";
      print STDERR "$msg\n";
      Logging::warning($msg ,'BrokenDbIntegrity');
      return;
    }
    ( $dbUser, $dbPasswd, $dbServerId ) = @{ $ptrRow };
    $self->{dbh}->finish();

    my %dbServerInfo;
    $dbServerInfo{'id'} = $dbServerId;
    $dbServerInfo{'type'} = $dbType;
    $dbServerInfo{'host'} = $dbServerHost;
    $dbServerInfo{'admin_password'} = $dbPasswd;

    $dbPasswd = PleskStructure::getDbServerAdminPassword(\%dbServerInfo);
    if (not defined $dbPasswd) {
      Logging::error("Unable to define superuser password for ". $dbType ." server on ". $dbServerHost);
      return;
    }

    if ( $dbType eq "postgresql" ) {
      $optional{'version'} = $dbServerVersion ne '' ? $dbServerVersion : AgentConfig::getPostgresqlVersion();
    }

    if ( $dbType eq "mysql" ) {
      $optional{'version'} = $dbServerVersion ne '' ? $dbServerVersion : Db::MysqlUtils::getVersion();
    }

    %contentDescription = (
      "name"     => $dbName,
      'tables'   => undef,
      "type"     => $dbType,
      "user"     => $dbUser,
      "password" => $dbPasswd,
      "host"     => $dbServerHost,
      "port"     => $dbServerPort,
      "plesk_7"  => 0
    );

    if ( $dbType eq "postgresql" ) {
      my $psql = AgentConfig::psqlBin();
      if ( -e $psql ) {
#[Bug 119082]
#$charset = `PGUSER=$dbUser PGPASSWORD='$dbPasswd' $psql -l template1 | grep '^[ \t]*$dbName ' | awk '{print \$5}'`;
          my $wrapPgsql = Db::DbConnect::getDbConnect(
            $dbType,       $dbUser, $dbPasswd, $dbName,
            $dbServerHost, undef,   undef,     undef,
            undef,         $dbServerPort
          );
          if ( ref($wrapPgsql) eq 'HASH' ) {
            if (
              $wrapPgsql->{'EXECUTE'}->( "select pg_catalog.pg_encoding_to_char(d.encoding) FROM pg_catalog.pg_database d where d.datname='?'", $dbName )
               )
            {
              my $ptrRow;
              if ( ( $ptrRow = $wrapPgsql->{'FETCHROW'}->() ) ) {
                $charset = $ptrRow->[0];
                if ( $charset ne '' ) {
                  $optional{'charset'} = $charset;
                }
              }
              $wrapPgsql->{'FINISH'}->();
            }

            my $conn = $wrapPgsql->{'CONNECTION'};
            my $existentTables = Db::PostgresqlUtils::getTables($conn);
            $contentDescription{'tables'} = $self->_getIncludedDatabaseTables($dbId, $dbName, $existentTables);
          }
          else {
            Logging::warning( "Cannot connect to PostgreSQL $dbServerHost:$dbServerPort (database '$dbName')" );
            $skipContent = 1;
          }
      } else {
          Logging::error("The psql command is absent on a server, so the dump of PostgreSQL databases can not be created.");
          $skipContent = 1;
      }
    }

    if ( $dbType eq "mysql" ) {
      my $wrapMysql = Db::DbConnect::getDbConnect(
        $dbType,       $dbUser, $dbPasswd, $dbName,
        $dbServerHost, undef,   undef,     undef,
        undef,         $dbServerPort
      );
      if ( ref($wrapMysql) eq 'HASH' ) {
        if ( $wrapMysql->{'EXECUTE'}->("SHOW VARIABLES LIKE \"character_set_database\"") )
        {
          my $ptrRow = $wrapMysql->{'FETCHROW'}->();
          my $charset;
          $charset = $ptrRow->[1] if $ptrRow;
          $optional{'charset'} = $charset if $charset;
          $wrapMysql->{'FINISH'}->();
        }

        # We can't always use UTF8 for dump content because it may lead to corruption of latin1-only-databases.
        # See http://bugs.plesk.ru/show_bug.cgi?id=134509 for details.
        # However, using database charset may also lead to corruption if database has latin1 default
        # charset, but some tables or columns have non-latin1 charset.
        # The semi-solution is to query charset for tables and columns and if they all have
        # the same charset - use it, otherwise use utf-8.
        my $conn = $wrapMysql->{'CONNECTION'};
        my %collations = Db::MysqlUtils::getCollations( $conn );
        my %tablesCollations = Db::MysqlUtils::getTablesCollation( $conn );
        my @tablesCharsets = map { $collations{$_} } values(%tablesCollations);
        my %usedCharsets = map { $_, 1 } @tablesCharsets;
        for my $table (keys %tablesCollations) {
          my %columnsCollations = Db::MysqlUtils::getColumnsCollation( $conn, $table );
          $usedCharsets{$collations{$_}} = 1 for (values %columnsCollations);
        }

        my $dumpCharset = (scalar (keys %usedCharsets) == 1) ? (keys %usedCharsets)[0] : 'utf8';
        # ucs2, utf16, and utf32 cannot be used as a client character set (http://dev.mysql.com/doc/refman/5.5/en/charset-connection.html)
        # https://jira.plesk.ru/browse/PPP-13515
        $dumpCharset = 'utf8' if ($dumpCharset =~ /^(ucs2|utf16|utf32)$/);
        $contentDescription{'dump_charset'} = $dumpCharset;

        # Dump database collation
        if ( $wrapMysql->{'EXECUTE'}->("SHOW VARIABLES LIKE \"collation_database\"") ) {
          my $ptrRow = $wrapMysql->{'FETCHROW'}->();
          my $collation = $ptrRow->[1] if $ptrRow;
          $optional{'collation'} = $collation if $collation;
          $wrapMysql->{'FINISH'}->();
        }

        my @existentTables = keys %tablesCollations;
        $contentDescription{'tables'} = $self->_getIncludedDatabaseTables($dbId, $dbName, \@existentTables);
      }
      else {
        Logging::warning( "Cannot connect to mysql $dbServerHost:$dbServerPort (database '$dbName')" );
        $skipContent = 1;
      }
    }

    $contentDescription{'create_local_dump'} = $self->checkExistDbServerOnDestination($dbType, $dbServerHost, $dbServerPort);
    if ($contentDescription{'create_local_dump'} != 0) {
      $contentDescription{'dir_for_local_dump'} = AgentConfig::dumpDir() . '/databases';
    }
  }

  if (defined($contentDescription{'tables'}) and !@{$contentDescription{'tables'}}) {
    Logging::debug(sprintf("List of tables of the database '%s' is empty, skip backup of the database content.", $dbName));
    $skipContent = 1;
  }

  $self->{packer}->setContentTransport();

  if( $sappId ){
        if ( 'domain' eq $parentType ) {
          $self->{packer}->addDomainSappDatabase( $dbId, $dbServerId, $parent, $sappId, $dbName, $dbType, \%optional, \%dbServer, \@dbUsers, \%contentDescription, $skipContent );
        }
        elsif ( 'subdomain' eq $parentType ) {
          $self->{packer}->addSubDomainSappDatabase( $dbId, $dbServerId, $parent, $sappId, $dbName, $dbType, \%optional, \%dbServer, \@dbUsers, \%contentDescription, $skipContent );
        }
        else {
          Logging::warning( "Error: makeDatabaseNode: Unexpected type of parent \"$parentType\"",'assert');
        }
  }
  else{
        if ( 'domain' eq $parentType ) {
          $optional{'related-sites'} = DAL::getDatabaseRelatedSites($dbId, $parent);
          $self->{packer}->addDomainDatabase( $dbId, $dbServerId, $parent, $dbName, $dbType, \%optional, \%dbServer, \@dbUsers, \%contentDescription, $skipContent );
        }
        else {
          Logging::warning( "Error: makeDatabaseNode: Unexpected type of parent \"$parentType\"",'assert');
        }
  }

  $self->{isAnyDbDumpedOnDomain} = 1;

  Logging::endObject();
}

sub checkExistDbServerOnDestination {
  my ( $self, $type, $host, $port ) = @_;

  if ( scalar(@{$self->{existing_remote_db_servers}}) != 0 ) {
    foreach my $dbServerString (@{$self->{existing_remote_db_servers}}) {
      my ($exType, $exHost, $exPort) = split(/:/, $dbServerString);
      if ( $type eq $exType and $host eq $exHost and $port eq $exPort ) {
        return 1;
      }
    }
  }

  return 0;
}

sub makeMailUserNode {
  my ( $self, $domainId, $mailId, $passwd, $typePasswd, $domainName, $domainAsciiName, $parentType, $clientId ) = @_;

  my ( $sql, %mail, $item, $ptrRow, $ptrHash, $dir, $mbox_quota);

  $sql = "SELECT * FROM mail WHERE id = ?";
  unless ( ( $self->{dbh}->execute_rownum($sql, $mailId) ) && ( $ptrHash = $self->{dbh}->fetchhash() ) ) {
    $self->{dbh}->finish();
    my $msg = "Broken referencial integrity: Mail id $mailId is not found in mail";
    print STDERR "$msg\n";
    Logging::warning($msg,'BrokenDbIntegrity');
    return;
  }
  %mail = %{ $ptrHash };
  $self->{dbh}->finish();
  my $mailName = $mail{'mail_name'};
  Logging::beginObject('mailname',$mailName, undef);

  my $userUid = undef;
  my $cpAccessDefault = undef;
  if (exists $mail{'userId'} and $mail{'userId'} != 0) {
    $sql = "SELECT uuid, login, email, isLocked FROM smb_users WHERE id = ?";
    if ( $self->{dbh}->execute_rownum($sql, $mail{'userId'}) ) {
      my $fullMailName = $mailName . "@" . $domainName;
      while ( $ptrRow = $self->{dbh}->fetchrow() ) {
        $userUid = $ptrRow->[0];
        $cpAccessDefault = "true" if ($ptrRow->[3] eq '0' && $ptrRow->[1] eq $fullMailName && $ptrRow->[2] eq $fullMailName);
      }
    }
    $self->{dbh}->finish();
  }

  $self->{packer}->addMail( $domainId, $mailId, $mailName, $passwd, $typePasswd, $userUid, $cpAccessDefault );

  if ( $mail{'mbox_quota'} ) {
    $mbox_quota = $mail{'mbox_quota'};

    $self->{packer}->setMailBoxQuota( $mailId, $mbox_quota );
  }

  my $enable_mailbox = $mail{'postbox'} =~ /true/;

  # Check whether there autoresponder with attach
  # On 'Olde Pleskes' there was bug allowing attaches
  # when mailbox in turned off, so we have to explicitly
  # turn mailbox on if there is attach.
  $sql = "SELECT COUNT(ra.filename) FROM mail_resp AS mr, resp_attach as ra "
       . "WHERE ra.rn_id = mr.id AND mr.mn_id = ?";
  if (  $self->{dbh}->execute_rownum($sql, $mailId)
    and ( $ptrRow = $self->{dbh}->fetchrow() )
    and $ptrRow->[0] != 0 )
  {
    $enable_mailbox = 1;
  }
  $self->{dbh}->finish();

  if ($enable_mailbox) {
    $dir = AgentConfig::getPleskMailnamesDir($domainAsciiName, $mailName) . "Maildir";

    $self->{packer}->setMailBox( $mailId, $mailName, $domainAsciiName,
      ( $mail{'postbox'} =~ /true/ ? 'true' : 'false' ), $dir );
  }

  #
  # aliases
  #
  $sql = "SELECT alias FROM mail_aliases WHERE mn_id=?";
  if ( $self->{dbh}->execute_rownum($sql, $mailId) ) {
    while ( $ptrRow = $self->{dbh}->fetchrow() ) {
      $self->{packer}->addMailAliase( $mailId, $ptrRow->[0] );
    }
  }
  $self->{dbh}->finish();

  #
  # end aliases
  #

  #
  # mail forwarding
  #

  my @members = grep {$_ ne $mailName.'@'.$domainName} @{DAL::getMailRedirects($mailId)};

  my $forwarding_enabled = ($mail{'mail_group'} eq 'true');

  $self->{packer}->setMailForwarding( $mailId, $forwarding_enabled, \@members );

  #
  # end mail forwarding
  #

  #
  # autoresponders
  #
  $dir = AgentConfig::getPleskMailnamesDir($domainAsciiName, $mailName) . "\@attachments";

  my (@autos);
  $sql = "SELECT id FROM mail_resp WHERE mn_id=? ORDER BY id";
  if ( $self->{dbh}->execute_rownum($sql, $mailId) ) {
    while ( $ptrRow = $self->{dbh}->fetchrow() ) {
      push @autos, $ptrRow->[0];
    }
  }
  $self->{dbh}->finish();

  my @autoresponders;
  my @filelist;

  foreach my $id (@autos) {
    my %item = makeAutoresponderNode( $self, $id, $mailName . "@" . $domainName );
    push @autoresponders, \%item;
    push @filelist, @{$item{'attach'}};
  }

  $self->{packer}->setMailAutoresponders( $mailId, $mailName, $domainAsciiName, $dir,
                                          $mail{'autoresponder'}, \@autoresponders, \@filelist );
  $self->makeAddressbookNode( $mailId, $mailName . '@' . $domainAsciiName );

  $dir = AgentConfig::getPleskMailnamesDir($domainAsciiName, $mailName) . ".spamassassin";
  $self->makeSpamassassinNode( $mailId, $mail{'mail_name'}, $domainAsciiName, $mail{'spamfilter'}, $dir );

  my %states_map = ( 'none' => 'none', 'incoming' => 'in', 'outgoing' => 'out', 'any' => 'inout' );
  if ( defined( $states_map{ $mail{'virusfilter'} } ) ) {
    $self->{packer}->setMailVirusSettings( $mailId, $states_map{ $mail{'virusfilter'} } );
  }


  if ( exists $ptrHash->{'description'} ) {
    $self->{packer}->makeMailUserDescriptionNode( $mailId, $ptrHash->{'description'} );
  }

  $self->_makeMailUserOutgoingMessagesNode($mailId);

  Logging::endObject();
}

sub _makeMailUserOutgoingMessagesNode() {
  my ($self, $mailUserId) = @_;

  my $sql = "SELECT param, val FROM mn_param WHERE mn_id = ? AND param LIKE 'outgoing_messages_%'";
  if ($self->{dbh}->execute_rownum($sql, $mailUserId)) {
    while (my $ptrRow = $self->{dbh}->fetchrow()) {
      $self->{packer}->makeOutgoingMessagesParameter('mailuser', $mailUserId, $ptrRow->[0], $ptrRow->[1]);
    }
  }
  $self->{dbh}->finish();
}

sub makeAutoresponderNode {
  my ( $self, $autoId, $mailName ) = @_;

  my ( $name, $value, $sql, %auto, $ptrRow, $item );

  $sql = "SELECT * FROM mail_resp WHERE id=?";
  unless ( ( $self->{dbh}->execute_rownum($sql, $autoId) ) && ( $ptrRow = $self->{dbh}->fetchhash() ) ) {
    $self->{dbh}->finish();
    my $msg = "Broken referencial integrity: autoresponder id $autoId is not found in mail_resp";
    print STDERR "$msg\n";
    Logging::warning($msg,'BrokenDbIntegrity');
    return;
  }

  %auto = %{ $ptrRow };
  $self->{dbh}->finish();

  #
  # forward
  #
  $sql = "SELECT address FROM resp_forward WHERE rn_id=?";
  if ( $self->{dbh}->execute_rownum($sql, $autoId) ) {
    my (@list);
    while ( $ptrRow = $self->{dbh}->fetchrow() ) {

      # skip empty entries - found somewhere @ the wild Net
      push @list, $ptrRow->[0] if $ptrRow->[0];
    }
    if (@list) {
      $auto{'redirect'} = join( ',', @list );
    }
  }
  $self->{dbh}->finish();

  my @attach;
  $sql = "SELECT filename FROM resp_attach WHERE rn_id=?";
  if ( $self->{dbh}->execute_rownum($sql, $autoId) ) {
    while ( $ptrRow = $self->{dbh}->fetchrow() ) {
      push @attach, $ptrRow->[0];
    }
  }
  $self->{dbh}->finish();
  $auto{'attach'} = \@attach;
  return %auto;
}

sub makeAddressbookNode {
  my ( $self, $mailId, $mailName ) = @_;

  my @params;

  my $sql = "SELECT Db FROM mysql.db WHERE db='horde'";
  unless( $self->{dbh}->execute_rownum($sql) ){
    $self->{dbh}->finish();
    return;
  }
  $self->{dbh}->finish();

  $sql = "SHOW TABLES FROM horde LIKE 'turba_objects'";
  unless( $self->{dbh}->execute_rownum($sql) ) {
    $self->{dbh}->finish();
    return;
  }
  $self->{dbh}->finish();

  my $ptrHash;
  $sql = "SELECT * FROM horde.turba_objects WHERE owner_id = '?'";
  if ($self->{dbh}->execute_rownum($sql, $mailName) ) {
    while ( $ptrHash = $self->{dbh}->fetchhash() ) {
      push @params, $ptrHash;
    }
  }
  $self->{dbh}->finish();

  my $ptrRow;
  my %turbaVersion = ();
  $sql = "SELECT value FROM ServiceNodeEnvironment WHERE section='componentsPackages' AND name = 'psa-turba' AND serviceNodeId=1";
  if ($self->{dbh}->execute_rownum($sql)) {
    while ( $ptrRow = $self->{dbh}->fetchrow() ) {
        # Format version is 2.3.5-cos5.build1011110311.18
        if ($ptrRow->[0]=~/(\d+)\.(\d+)\.(\d+)-\S*/) {
                $turbaVersion{'majorVersion'} = $1;
                $turbaVersion{'minorVersion'} = $2;
                $turbaVersion{'patchVersion'} = $3;
        }
    }
  }
  $self->{dbh}->finish();

  $self->{packer}->setMailAddressbook( $mailId, \@params, \%turbaVersion );
}

sub getWebspaceId {
  my ( $self, $domainId ) = @_;

  my $webspace_id;
  my $sql = "SELECT webspace_id FROM domains WHERE id = ?";
  if ( $self->{dbh}->execute_rownum($sql, $domainId) and my $ptrRow = $self->{dbh}->fetchrow() ) {
    $webspace_id = $ptrRow->[0];
  }
  $self->{dbh}->finish();
  return $webspace_id;
}

sub getDomainWwwRoot {
  my ( $self, $domainId, $absolute ) = @_;

  my $www_root = DAL::getDomainWwwRoot($domainId);
  if($absolute) {
    return $www_root;
  }
  my $parentDomainName;
  my $webspace_id = $self->getWebspaceId($domainId);
  if($webspace_id) {
    $parentDomainName = PleskStructure::getDomainAsciiNameFromId($webspace_id);
  }
  else {
    $parentDomainName = PleskStructure::getDomainAsciiNameFromId($domainId);
  }
  my $parentDomainRoot = HostingDumper::getWebspaceRoot($parentDomainName, $self->{remoteWebNodes});
  $www_root = substr($www_root,length($parentDomainRoot));
  substr($www_root,0,1) = '' if substr($www_root,0,1) eq '/' || substr($www_root,0,1) eq '\\';
  return $www_root;
}

sub getDomainCgiRoot {
  my ( $self, $domainId, $absolute ) = @_;

  my $parentDomainName;
  my $webspace_id = $self->getWebspaceId($domainId);
  if($webspace_id) {
    $parentDomainName = PleskStructure::getDomainAsciiNameFromId($webspace_id);
  }
  else {
    $parentDomainName = PleskStructure::getDomainAsciiNameFromId($domainId);
  }
  my $parentDomainRoot = HostingDumper::getWebspaceRoot($parentDomainName, $self->{remoteWebNodes});

  my $cgi_root;
  if( DAL::getDomainCgiBinMode($domainId) eq 'www-root') {
    $cgi_root = DAL::getDomainWwwRoot($domainId) . '/cgi-bin';
  }
  else {
    $cgi_root = $parentDomainRoot . '/cgi-bin';
  }
  if($absolute) {
    return $cgi_root;
  }
  $cgi_root = substr($cgi_root,length($parentDomainRoot));
  substr($cgi_root,0,1) = '' if substr($cgi_root,0,1) eq '/';
  return $cgi_root;
}

# Get domain name that could be safely used in Plesk CLI tools
# This is a workaround for wildcard subdomains, to be correct display name should be
# used instead of name, but there are problems passing it to Plesk CLI tool when there
# are no locales installed (easy steps to reproduce such situation - obtain a Debian 6 Virtuozzo box)
# and name contains UTF-8 symbols
sub getCliDomainName {
    my ( $domainName ) = @_;
    $domainName =~ s/_/*/;
    return $domainName;
}

my %wwwRootIndex = ();

sub makePhostingNode {
  my ( $self, $ptrDomain, $ptrDomParams, $ptrSiteApplications, $is_site, $ips, $embeddedInfo ) = @_;

  $self->checkProgramTerminated();

  Logging::beginObject('hosting',$ptrDomain->{'name'}, undef);
  my @SiteApplications = @{$ptrSiteApplications};

  unless ( ref($ptrDomain) =~ /HASH/ ) {
    Logging::warning("Error: makePhostNode: bad arguments",'assert');
    Logging::endObject();
    return undef;
  }

  my (
    $domainName, $domainRoot, $path,    $sql, $domainServiceDir,
    %hosting,    $domainId,   $xmlName, $fieldName, $id
  );
  $domainName = $ptrDomain->{'name'};
  $domainId   = $ptrDomain->{'id'};

  my $webspaceId = $self->getWebspaceId($domainId); # used for Site content-related subs
  my $webspaceName;

  if ($is_site) {
    $webspaceName = PleskStructure::getDomainAsciiNameFromId($webspaceId);
    if (exists $self->{remoteWebNodes}->{$webspaceName} && defined $self->{remoteWebNodes}->{$webspaceName}) {
      $self->{remoteWebNodes}->{$domainName} = $self->{remoteWebNodes}->{$webspaceName};
    } else {
      $self->{remoteWebNodes}->{$domainName} = undef;
    }
  }

  $self->{packer}->setContentTransport();

  my %hostingParams;

  %hosting = %{DAL::getHostingParams($domainId)};

  if ( defined( $hosting{'webstat'} ) and $hosting{'webstat'} ) {
    $hostingParams{'webstat'} = $hosting{'webstat'};
  }

  if ( defined( $hosting{'ssl'} ) and $hosting{'ssl'} ) {
    $hostingParams{'https'} = $hosting{'ssl'};
  }

  if ( defined( $hosting{'sslRedirect'} ) and $hosting{'sslRedirect'} ) {
    $hostingParams{'sslRedirect'} = $hosting{'sslRedirect'};
  }

  if ( defined( $hosting{'same_ssl'} ) and ( $hosting{'same_ssl'} eq 'true' ) ) {
    $hostingParams{'shared-content'} = 'true';
  } else {
    $hostingParams{'shared-content'} = 'false';
  }

  my $wwwroot = $self->getDomainWwwRoot($domainId);

  if ($wwwroot eq '') {
    my $index = $webspaceId == 0 ? $domainId : $webspaceId;
    $wwwRootIndex{$index} += 1;
    $hostingParams{'www-root'} = "site" . $wwwRootIndex{$index};
  } else {
    $hostingParams{'www-root'} = $wwwroot;
  }

  my $domParams = $self->getDomainParams($ptrDomain);
  $hostingParams{'errdocs'} = 'true' if ($domParams->{'apacheErrorDocs'} eq 'true');

  if (defined $domParams->{'cgi_bin_mode'}) {
    $hostingParams{'cgi_bin_mode'} = $domParams->{'cgi_bin_mode'} if ($domParams->{'cgi_bin_mode'} =~ /^old-style$|^www-root$|^webspace$/);
  }

  my %sysuser;

  #
  # sysuser
  #
  Logging::beginObject('sysuser', $domainName, undef);

  if ( $id = $hosting{'sys_user_id'} ) {
    %sysuser = $self->makePleskSysUserNode($id, $embeddedInfo);
    $sysuser{'relative_path'} = HostingDumper::getWebspaceRoot($domainName, $self->{remoteWebNodes});
  }

  #
  # end sysuser
  #
  Logging::endObject();

  my $domUser = lc( $sysuser{'login'} );

  my ($phpSettings, $scripting) = HostingDumper::getScripting(\%hosting);

  my %phpSettings = %{$phpSettings};

  #
  # end scripting
  #

  $hostingParams{'wu_script'} = 'true' if exists $ptrDomParams->{'wu_script'} && $ptrDomParams->{'wu_script'} eq 'true';

  $hostingParams{'sitebuilder-site-id'} = $ptrDomParams->{'site_builder_site_id'} if exists $ptrDomParams->{'site_builder_site_id'};

  my $published = $self->{packer}->getSb5SitePublished(getCliDomainName($domainName));
  $hostingParams{'sitebuilder-site-published'} = $published if defined $published;

  my @sites = @{ DAL::getSitesByWebspaceId($domainId) };

  my $pk = $self->getDomainCertificatePrivateKey($hosting{'certificate_id'}, $domainId);
  if ($pk) {
    $hostingParams{'certificate_ref'} = $self->getCertificateRef($pk);
  }

  $domainRoot = HostingDumper::getDomainRoot($domainName, $self->{remoteWebNodes});
  my $webspaceRoot = $webspaceName ? HostingDumper::getDomainRoot($webspaceName, $self->{remoteWebNodes}) : $domainRoot;
  $domainServiceDir = HostingDumper::getSystemDomainRoot($domainName, $self->{remoteWebNodes});

  if ('true' eq $self->{packer}->getContentTransport()->hasLink("$domainRoot/conf", "$domainServiceDir/conf")) {
    $hostingParams{'original-conf-directory'} = "$domainRoot/conf/";
  } elsif (defined($ptrDomain->{'parentDomainName'})) {
    my ($subDomainDirName) = $domainName =~ /([^.]+)/;
    my $parentDomainRoot = HostingDumper::getDomainRoot($ptrDomain->{'parentDomainName'}, $self->{remoteWebNodes});
    if ('true' eq $self->{packer}->getContentTransport()->hasLink("$parentDomainRoot/subdomains/$subDomainDirName/conf", "$domainServiceDir/conf")) {
      $hostingParams{'original-conf-directory'} = "$parentDomainRoot/subdomains/$subDomainDirName/conf/";
    } else {
      $hostingParams{'original-conf-directory'} = "$domainServiceDir/conf/";
    }
  } else {
    $hostingParams{'original-conf-directory'} = "$domainServiceDir/conf/";
  }

  if ( !$self->{only_mail_dump} ) {
      if (exists $domParams->{'phpSettingsId'}) {
        %phpSettings = %{DAL::getPhpSettings($domParams->{'phpSettingsId'})};
        my $phpSettingsCustom = DAL::getPhpSettingsCustom($domParams->{'phpSettingsId'});
        $phpSettings{'notice'} = $phpSettingsCustom if defined $phpSettingsCustom;
      }

    $self->{packer}->setDomainPhosting( $domainId,
                                        \%hostingParams,
                                        $is_site? undef: \%sysuser,
                                        $scripting,
                                        $ips,
                                        \%phpSettings
                                      );

    if (defined($embeddedInfo->{'web-settings'})) {
      foreach my $webSettings (@{$embeddedInfo->{'web-settings'}}) {
        if (ref($webSettings) =~ /HASH/ && $webSettings->{'vhost-name'} eq $domainName) {
          eval {require XML::Simple; 1;};
          my $xs = XML::Simple->new(ForceArray => 1);
          my $webSettingsXml = $xs->XMLout($webSettings, RootName => 'web-settings');
          $webSettingsXml = Encode::encode('UTF-8', $webSettingsXml); # workaround for UTF8 symbols
          $self->{packer}->appendNodeToPhosting($domainId, $webSettingsXml);
        }
      }
    }

  }
  else {
    if( @sites ) {
      $self->{packer}->setDomainPhostingEmpty( $domainId, $is_site? undef: \%sysuser, $ips );
    }
  }

  if ( !$self->{only_mail_dump} ) {
    #-----------------------------------------------------------------
    # Site applications content should not be included into dump
    # together with other website content.
    #-----------------------------------------------------------------
    my @exclude_httpdocs_files;
    push @exclude_httpdocs_files, 'plesk-stat';
    my @exclude_httpsdocs_files;
    push @exclude_httpsdocs_files, 'plesk-stat';
    my @exclude_cgi_bin_files;
    foreach my $sapp (@SiteApplications) {
      next if !$sapp->isDomainSiteapp($ptrDomain->{'displayName'});

      if ( $sapp->isSsl() ) {
        push @exclude_httpsdocs_files, $sapp->getHtdocsFiles();
      }
      else {
        push @exclude_httpdocs_files, $sapp->getHtdocsFiles();
      }
      push @exclude_cgi_bin_files, $sapp->getCgibinFiles();
    }

    # Hosting content dump

    if (!$self->{configuration_dump} ) {
      my @exclude_vhost_files;
      my $httpdocsDir = $self->getDomainWwwRoot($domainId);
      push @exclude_vhost_files, "$httpdocsDir/plesk-stat";

      push @exclude_vhost_files, "httpsdocs/plesk-stat";

      if(@sites) {
        foreach my $ptrHash (@sites) {
          my $siteHttpdocsDir = $self->getDomainWwwRoot($ptrHash->{'id'});
          push @exclude_vhost_files, "$siteHttpdocsDir/plesk-stat";

          # Fix to dump subdomains content, which became sites after upgrade to 11.5 and some its content unavailable for sysuser
          # http://plesk-process.parallels.com/TargetProcess2/Project/QA/Bug/View.aspx?BugID=141214
          # Exclude conf directory, because its content must be moved to system directory on upgrade and exclude error_docs, because it does not used
          my $subdomainName = $ptrHash->{'name'};
          $subdomainName =~ s/\.$ptrDomain->{'name'}//g;
          my $subdomainDataDir = "subdomains/$subdomainName";
          push @exclude_vhost_files, "$subdomainDataDir/conf" if (-d "$domainRoot/$subdomainDataDir/conf");
          push @exclude_vhost_files, "$subdomainDataDir/error_docs" if (-d "$domainRoot/$subdomainDataDir/error_docs");
        }
      }
      push(@exclude_vhost_files, 'logs');
      if (%sysuser && ($sysuser{'shell'} eq AgentConfig::getChrootShell() || ref($sysuser{'scheduled-tasks'}) =~ /HASH/)) {
        # chroot dirs:
        push(@exclude_vhost_files, 'bin', 'sbin', 'dev', 'lib', 'lib64', 'usr', 'libexec', 'etc', 'tmp', 'var');
      }

      my $options = {
        'sysuser' => $domUser
        , 'include' => $self->{includeFiles}
        , 'exclude' => \@exclude_vhost_files
      };
      if (!$is_site && $self->{dump_vhost}) {
        $self->{packer}->setDomainUserDataContent( $domainId, $domainName, $domainRoot, $options);
      } elsif (!$is_site) {
        $self->{packer}->setDomainPhostingFullContent($domainId, $domainName, $domainRoot, $options);
      }
    }

    if ( !$self->{configuration_dump} ) {
      if ($self->{dump_vhost}) {
        $self->{packer}->setDomainPhostingStatisticsContent( $domainId, $domainName, "$domainServiceDir/statistics", {} );
      } else {
        my $webstatRoot = $domainServiceDir . "/statistics/webstat";
        $self->{packer}->setDomainPhostingWebstatContent( $domainId, $domainName, $webstatRoot );
        $self->{packer}->setDomainPhostingWebstatSslContent( $domainId, $domainName, "$domainServiceDir/statistics/webstat-ssl" );
        $self->{packer}->setDomainPhostingFtpstatContent( $domainId, $domainName, "$domainServiceDir/statistics/ftpstat" );
        $self->{packer}->setDomainPhostingAnonFtpstatContent( $domainId, $domainName, "$domainServiceDir/statistics/anon_ftpstat" );
      }

      $self->{packer}->setDomainPhostingProtectedDirContent( $domainId, $domainName, "$domainServiceDir/pd" );
      $self->{packer}->setDomainPhostingLogsContent( $domainId, $domainName, "$domainServiceDir/logs" );
    }

    if ( !$self->{configuration_dump} && !$self->{only_mail_dump} ) {
      $self->addSb5DomainContent( $domainId, $domainName, $ptrDomParams->{'site_builder_site_id'});
    }
    else {
      Logging::debug("Skip domain sitebuilder content dumping due to settings");
    }

    #-----------------------------------------------------------------
    # Dump of installed site applications
    #-----------------------------------------------------------------
    foreach my $sapp (@SiteApplications) {
      if ( !$sapp->isDomainSiteapp($ptrDomain->{'displayName'}) ) {
        next;
      }
       $self->dumpSiteApplication( $sapp, $domainId, 'domain', $webspaceId? $webspaceId:$domainId, $embeddedInfo);
    }

    $self->makeDomainLogrotationNode($domainId);
    $self->addAnonFtp( $domainId, $domainName, { 'sysuser' => $domUser } );
    #
    # protected dirs
    #
    $sql = "SELECT id, path, realm, non_ssl, cgi_bin, `ssl` FROM protected_dirs WHERE dom_id=? ORDER BY id";
    if ( $self->{dbh}->execute_rownum($sql, $domainId) ) {
      my (@dirs);
      while ( my $ptrRow = $self->{dbh}->fetchrow() ) {
        push @dirs, [ @{$ptrRow} ];
      }
      foreach my $ptrRow (@dirs) {
        my $pdirId = $ptrRow->[0];
        my $pdirPath = $ptrRow->[1];
        my $pdirTitle = $ptrRow->[2];
        my $pdirNonSSL = $ptrRow->[3];
        my $pdirCGI  = $ptrRow->[4];
        my $pdirSSL = $ptrRow->[5];
        $self->makeProtDirNode( $domainId, $pdirId, $pdirPath, $pdirTitle, $pdirNonSSL, $pdirSSL, $pdirCGI);
      }
    }
    $self->{dbh}->finish();
    #
    # end protected dirs
    #

    unless ($is_site) {
      #
      # web users
      #
      my $webs = DAL::getWebUsers($domainId);

      foreach my $ptrHash (@{$webs}) {
        $self->makeWebUserNode( $ptrHash, $domainId, $domainName );
      }
      #
      # end web users
      #
    }

    unless ($is_site) {
      #
      # ftpusers
      #
      my $ftps = DAL::getAdditionalFtpUsers($domainId);
      foreach my $ptrHash (@{$ftps}) {
        $self->makeSubFtpUserNode( $ptrHash, $domainId, $domainName );
      }
      #
      # end ftpusers
      #
    }

    #
    # subdomains
    #

    $self->{packer}->setEmptySubFtpUsersNode($domainId);
    $self->{packer}->removePhostingChildNodeIfEmpty( $domainId, 'subdomain' );
    $self->{packer}->removePhostingChildNodeIfEmpty( $domainId, 'ftpuser' );

    #
    # end subdomains
    #
  }

  if( @sites ) {
    foreach my $ptrHash ( @sites ) {
      eval {
        $self->makeSiteNode( $ptrHash->{'name'}, $ptrHash->{'displayName'}, \@SiteApplications, $embeddedInfo );
        1;
      } or do {
        $self->checkProgramTerminated();
        $self->excludeProblemSiteFromBackup( $domainId, $ptrHash, $@ );
      }
    }
    $self->{packer}->removePhostingChildNodeIfEmpty( $domainId, 'site' );
  }

  if ( !$self->{only_mail_dump} ) {
    #
    # configuration files (conf/vhost[_ssl].conf)
    #
    my $confDir = "$domainServiceDir/conf/";
    my @excludeConfFiles;
    push(@excludeConfFiles, 'httpd.include', 'last_httpd.include', 'last_httpd.conf', '*.*_httpd.include', '*.*_httpd.conf');
    push(@excludeConfFiles, 'last_nginx.conf', '*.*_nginx.conf', 'nginx.conf');
    push(@excludeConfFiles, 'siteapp.d', 'stat_ttl.conf', 'webalizer.conf', 'prev_month.found');
    Logging::debug('Exclude conf files: ' . join(", ", @excludeConfFiles));
    $self->{packer}->setDomainPhostingConfContent( $domainId, $domainName, $confDir, { 'exclude' =>  \@excludeConfFiles } );
    #
    # end configuration files (conf/vhost[_ssl].conf)
    #

    #
    # Webalizer configuration
    #
    my ( $directRef, @hiddenRefs, @groupRefs );

    my $sql = "SELECT referrer FROM webalizer_hidden_referrer "
            . " WHERE dom_id=?";
    if ( $self->{dbh}->execute_rownum($sql, $domainId) ) {
      while ( my $ptrRow = $self->{dbh}->fetchrow() ) {
        if ( $ptrRow->[0] eq "Direct Request" ) {
          $directRef = 'true';
        }
        else {
          push @hiddenRefs, $ptrRow->[0];
        }
      }
    }
    $self->{dbh}->finish();

    $sql = "SELECT referrer, group_name FROM webalizer_group_referrer "
         . " WHERE dom_id=?";
    if ( $self->{dbh}->execute_rownum($sql, $domainId) ) {
      while ( my $ptrRow = $self->{dbh}->fetchrow() ) {
        push @groupRefs, { 'ref' => $ptrRow->[0], 'name' => $ptrRow->[1] };
      }
    }
    $self->{dbh}->finish();

    $self->{packer}->setDomainWebalizer( $domainId, $directRef, \@hiddenRefs, \@groupRefs );

    #
    # Perfomance
    #
    if (    ( defined( $hosting{'max_connection'} ) and $hosting{'max_connection'} )
         or ( defined( $hosting{'traffic_bandwidth'} ) and $hosting{'traffic_bandwidth'} )
       ) {
      $self->{packer}->setDomainPerfomance( $domainId,
                                            $hosting{'max_connection'},
                                            $hosting{'traffic_bandwidth'} );
    }

    if( exists $ptrDomParams->{'stat_ttl'} ){
      $self->{packer}->setDomainWebStat( $domainId, $ptrDomParams->{'stat_ttl'} );
    }

  }

  Logging::endObject();
}

sub dumpSiteApplication {
  my ( $self, $sapp, $parent, $parentType, $webspaceId, $embeddedInfo ) = @_;

  my $sapp_id = $parentType . "_" . $parent . "_" . $sapp->getInstallPrefix();

  my $prefix = $sapp->getInstallPrefix();

  my $licenseType = $sapp->getAPSClientItemLicenseType();

  if ( 'domain' eq $parentType ) {
    $self->{packer}->addDomainSapp(
      $parent, $sapp_id, $sapp, $licenseType
    );
  }
  elsif ( 'subdomain' eq $parentType ) {
    $self->{packer}->addSubDomainSapp(
      $parent, $sapp_id, $sapp, $licenseType
    );
  }
  else {
    Logging::warning(
      "Error: dumpSiteApplication: Unexpected type of parent \"$parentType\"",'assert');
  }

  $self->{packer}->setSappParams( $sapp_id, $sapp );


  #-----------------------------------------------------------------
  # Linked resources
  #-----------------------------------------------------------------

  # Databases
  foreach my $row ( $sapp->getDatabases() ) {
    $self->makeDatabaseNode( $row->{'id'}, $parent, $parentType, $sapp_id, $row, $embeddedInfo );
  }

  # Custom buttons
  foreach my $row ( $sapp->getCustomButtons() ) {
    $self->getCustomButtonById71( $row->{'id'}, $parent, $parentType, $sapp_id );
  }

  my $installDirApsRegistryId = undef;
  $installDirApsRegistryId = $sapp->getInstallDirApsRegistryId();
  $self->{packer}->makeSiteAppInstalled( $sapp_id, $prefix, $sapp->isSsl(),  $installDirApsRegistryId );
  $self->{packer}->setSappEntryPoints( $sapp_id, $sapp );
  $self->{packer}->setSappApsControllerInfo( $sapp_id, $sapp );

  # backupArchive

  my $backupArchiveFile = $sapp->getBackupArchiveFile();
  unless ( open( BAFILE, "<$backupArchiveFile" ) ) {
    Logging::warning("Error: unable to open BackupArchive from APS controller:" . $backupArchiveFile);
  }
  my $backupArchiveContent;
  while(<BAFILE>) {
    $backupArchiveContent .= $_;
  }
  close(BAFILE);
  unlink($backupArchiveFile);

  $self->{packer}->setSappApscNode($sapp_id, $backupArchiveContent);
  $self->{packer}->setSappApsLicense($sapp_id, $sapp);
  $self->{packer}->setSappSettings($sapp_id, $sapp);

  my $apsResourceId = $sapp->getApplicationApsResourceId();
}

sub makeDomainLogrotationNode {
  my ( $self, $domainId ) = @_;

  my $logrotation_id = DAL::getDomainParam($domainId, 'logrotation_id');
  if ( defined $logrotation_id ) {
    $self->{packer}->setDomainLogrotation( $domainId, DAL::getLogrotation($logrotation_id) );
  }
}

sub makeWebUserNode {
  my ( $self, $ptrWebUser, $domainId, $domainName ) = @_;
  unless ( ref($ptrWebUser) =~ /HASH/ ) {
    Logging::warning("Error: makeWebUserNode: bad argumets", 'assert');
    return;
  }
  my ( $home, $userName, $item, $xmlName, $fieldName );

  my %sysuser;

  %sysuser = $self->makePleskSysUserNode( $ptrWebUser->{'sys_user_id'} );
  $userName = $sysuser{'login'};

  my $domainRoot = HostingDumper::getDomainRoot($domainName, $self->{remoteWebNodes});

  my $webUserHome = $domainRoot . "/web_users/$userName";
  my $privateData = $domainRoot . "/private/$userName";

  $self->{packer}->addDomainWebUser(
    $domainId,   $domainName,    \%sysuser,
    $ptrWebUser, $webUserHome, $privateData
  );
}

#
# makeSubFtpUserNode - make node for additionals ftp accounts
#

sub makeSubFtpUserNode {
  my ( $self, $ptrFtpUser, $domainId, $domainName ) = @_;

  my %sysuser = $self->makePleskSysUserNode($ptrFtpUser->{'sys_user_id'});

  $sysuser{'relative_path'} = HostingDumper::getWebspaceRoot($domainName, $self->{remoteWebNodes});

  $self->{packer}->addDomainSubFtpUser($domainId, $domainName, \%sysuser, $ptrFtpUser);
}

sub makeProtDirNode {
  my ( $self, $domainId, $pdirId, $pdirPath, $pdirTitle, $pdirNonSSL, $pdirSSL, $pdirCGI ) = @_;

  my ( $sql, $item, $ptrRow );

  # workaround of CLI inabliity to create '' directory.
  if ( $pdirPath eq '' ) {
    $pdirPath = '/';
  }

  $sql = "SELECT p.login, a.password, a.type FROM pd_users p "
      . " LEFT JOIN accounts a ON p.account_id = a.id "
      . " WHERE p.pd_id=? ORDER BY p.id";

  my @users;
  if ( $self->{dbh}->execute_rownum($sql, $pdirId) ) {
    while ( $ptrRow = $self->{dbh}->fetchrow() ) {
      push @users,
        {
        'login'      => $ptrRow->[0],
        'passwd'     => $ptrRow->[1],
        'passwdType' => $ptrRow->[2]
        };
    }
  }
  $self->{dbh}->finish();
  $self->{packer}->addDomainProtectedDir( $domainId, $pdirPath, $pdirTitle, $pdirNonSSL, $pdirSSL, $pdirCGI, \@users );
}

sub addAnonFtp {
  my ( $self, $domainId, $domainName, $optPtr ) = @_;

  my ( $ptrHash, $count, $domainRoot, $sql );
  $count = 0;
  $sql   = "SELECT * FROM anon_ftp WHERE dom_id=?";
  if ( $self->{dbh}->execute_rownum($sql, $domainId) ) {
    while ( $ptrHash = $self->{dbh}->fetchhash() ) {
      $domainRoot = HostingDumper::getDomainRoot($domainName, $self->{remoteWebNodes});
      my ( $pub_path, $incoming_path );

      $pub_path      = "$domainRoot/anon_ftp/pub";
      $incoming_path = "$domainRoot/anon_ftp/incoming";

      $self->{packer}->setDomainAnonFtp( $domainId, $domainName, $ptrHash, $pub_path, $incoming_path, $optPtr );

      $count++;
    }
  }
  $self->{dbh}->finish();
  return $count;
}

sub addSb5DomainContent {
  my ( $self, $domainId, $domainName, $uuid ) = @_;
  unless ( defined $uuid) {
    Logging::debug( "addSb5DomainContent: domain $domainName site_builder_site_id is not found. skip");
    return;
  }
  $self->{packer}->setSb5DomainContent( $domainId, $domainName, $uuid);
}

sub makePleskSysUserNode {
  my ( $self, $sysUserId, $embeddedInfo ) = @_;
  my %sysuser = %{ HostingDumper::getSysUserInfo($sysUserId) };

  Logging::trace("Making system user node: $sysuser{'login'}");

  my $sysUserInfo = undef;
  if (defined($embeddedInfo->{sysuser})) {
    $sysUserInfo = (grep { $_->{name} eq $sysuser{login} } @{$embeddedInfo->{sysuser}})[0];
  }

  if ( $sysuser{'account_id'} != 0 ) {
    ( $sysuser{'passwd'}, $sysuser{'passwdType'} ) = $self->makeAccountPasswordNode( $sysuser{'account_id'} );
  }

  if (defined($sysUserInfo) && defined($sysUserInfo->{'scheduled-tasks'})) {
    my $scheduledTasks = $sysUserInfo->{'scheduled-tasks'}->[0];
    if (ref($scheduledTasks) =~ /HASH/ && defined($scheduledTasks->{'failure'})) {
      my $message = $scheduledTasks->{'failure'}[0]->{'message'}[0];
      Logging::warning($message);
    } else {
      $sysuser{'scheduled-tasks'} = $scheduledTasks;
    }
  }

  return %sysuser;
}

sub getCron {
  my ( $self, $account ) = @_;

  my $crontabmng = AgentConfig::get("PRODUCT_ROOT_D") . "/admin/sbin/crontabmng";
  if ( -x $crontabmng ) {
    open( CRONTAB, "$crontabmng get $account |" );
    my $crontab = "";
    while (<CRONTAB>) {
      last if $_ eq "0\n";
      $crontab .= $_;
    }
    close(CRONTAB);
    if ( $crontab ne "\n" ) {
      return $crontab;
    }
  }
}

sub makeSyntheticSysUserNode {
  my ( $self, $name, $password, $passtype ) = @_;

  my %sysuser;
  $sysuser{'login'}      = $name;
  $sysuser{'passwd'}     = $password;
  $sysuser{'passwdType'} = $passtype;
  return %sysuser;
}

sub makeFhostingNode {
  my ( $self, $ptrDomain, $ips ) = @_;

  return if ( $self->{only_mail_dump} );

  unless ( ref($ptrDomain) =~ /HASH/ ) {
    Logging::warning("Error: makeFhostingNode: bag arguments", 'assert');
    return;
  }
  my ( $sql, $domainId, $forward, $rowPtr );
  $domainId = $ptrDomain->{'id'};
  $sql      = "SELECT redirect FROM forwarding WHERE dom_id=?";
  unless ( ( $self->{dbh}->execute_rownum($sql, $domainId) ) && ( $rowPtr = $self->{dbh}->fetchrow() ) ) {
    $self->{dbh}->finish();
    my $msg = "Broken referencial integrity: forward for domain " . $ptrDomain->{'name'} . " is not found in forwarding";
    print STDERR "$msg\n";
    Logging::warning($msg,'BrokenDbIntegrity');
    return;
  }

  ($forward) = @{ $rowPtr };
  $self->{packer}->setDomainFhosting( $domainId, $forward, $ips);
  $self->{dbh}->finish();
}

sub makeShostingNode {
  my ( $self, $ptrDomain, $ips ) = @_;

  return if ( $self->{only_mail_dump} );

  unless ( ref($ptrDomain) =~ /HASH/ ) {
    Logging::warning("Error: makeShostingNode: bag arguments", 'assert');
    return;
  }

  my ( $sql, $domainId, %forward, $hashPtr );
  $domainId = $ptrDomain->{'id'};

  $sql = "SELECT * FROM forwarding WHERE dom_id=?";
  unless ( ( $self->{dbh}->execute_rownum($sql, $domainId) ) && ( $hashPtr = $self->{dbh}->fetchhash() ) ) {
    $self->{dbh}->finish();
    my $msg = "Broken referencial integrity: forward for domain " . $ptrDomain->{'name'} . " is not found in forwarding";
    print STDERR "$msg\n";
    Logging::warning($msg,'BrokenDbIntegrity');
    return;
  }

  $self->{dbh}->finish();

  %forward = %{ $hashPtr };
  $self->{packer}->setDomainShosting( $domainId, \%forward, $ips );
  $self->{dbh}->finish();
}

sub _makePasswordData{
  my ($password, $type) = @_;

  if (!defined $password) {
    Logging::warning( "'undef' password passed to _makePasswordData. Change to empty!" );
    $password = '';
  }

  if (!$password) {
    $type = 'plain';
  }
  return ($password, $type);
}

my $tableAccounts = undef;

sub makeAccountPasswordNode {
  my ( $self, $accountId ) = @_;

  my ( $passwd, $type );
  my %values;

  if( not defined $tableAccounts ){
    if ( $self->{dbh}->execute( "SELECT id, password, type FROM accounts" ) ) {
       while( my $ptrRow = $self->{dbh}->fetchrow() ){
         $values{$ptrRow->[0]} = ();
         push @{$values{$ptrRow->[0]}}, $ptrRow->[1];
         push @{$values{$ptrRow->[0]}}, $ptrRow->[2];
      }
    }
    $self->{dbh}->finish();
    $tableAccounts = \%values;
  }

  if ( exists $tableAccounts->{$accountId} )
  {
    ( $passwd, $type ) = @{$tableAccounts->{$accountId}};
    ( $passwd, $type ) = _makePasswordData( $passwd, CommonPacker::normalizePasswordType($type) );
  }
  else {

    # generates a stub node
    ( $passwd, $type ) = _makePasswordData();
  }
  $self->{dbh}->finish();

  return ( $passwd, $type );
}

my @_planItemNames;

sub getPlanItemNames {
  my ( $self ) = @_;

  if ( @_planItemNames ) {
    return \@_planItemNames;
  }

  my $planItemsPtr = DAL::getPlanItems();
  foreach my $planItemPtr ( @{$planItemsPtr} ) {
    push @_planItemNames, $planItemPtr->{'name'};
  }

  return \@_planItemNames;
}

sub getSubscriptionProperties {
  my ( $self, $type, $id ) = @_;

  my @planItemNames = @{$self->getPlanItemNames()};

  my $subscriptionProperties = $self->getSubscriptionPropertiesHash($id, $type);

  foreach my $key (keys %{$subscriptionProperties}) {
      if ( grep $_ eq $key, @planItemNames) {
        my $templatePlanItemNode = $self->{packer}->makeTemplatePlanItem( $key,  $subscriptionProperties->{$key} );
        $self->{packer}->addToPreferences('domain', $id, $templatePlanItemNode) if defined $templatePlanItemNode;
      }
  }
}

sub getSubscriptionPropertiesHash {
  my ( $self, $id, $type ) = @_;

  my %subscriptionProperties;

  my $sql = "SELECT name, value FROM SubscriptionProperties INNER JOIN Subscriptions ON id=subscription_id  WHERE object_id=? AND object_type='?'";
  if ( $self->{dbh}->execute_rownum($sql, $id, $type) ) {
    while ( my $ptrHash = $self->{dbh}->fetchhash() ) {
      $subscriptionProperties{$ptrHash->{'name'}} = $ptrHash->{'value'};
    }
  }
  $self->{dbh}->finish();

  return \%subscriptionProperties;
}

sub getSubscription {
  my ( $self, $type, $id) = @_;
  my $subscriptionNode = $self->makeSubscriptionNode($type, $id);
  $self->{packer}->addToPreferences($type, $id, $subscriptionNode) if defined $subscriptionNode;
  return $subscriptionNode;
}

sub makeSubscriptionNode {
  my ( $self, $type, $id ) = @_;

  my $rowPtr = DAL::getSubscription($type, $id);
  if ( !defined($rowPtr) ) {
    $self->{dbh}->finish();
    return $self->{packer}->makeSubscriptionNode(undef, undef, undef); # subscription node is needed for dump subscription properties
  }
  my $subscription_id = $rowPtr->{'id'};
  my $locked = $rowPtr->{'locked'};
  my $synchronized = defined$rowPtr->{'synchronized'} ? $rowPtr->{'synchronized'} : undef;
  my $custom = defined $rowPtr->{'custom'} ?  $rowPtr->{'custom'} : undef;
  my $externalId = defined $rowPtr->{'external_id'} ? $rowPtr->{'external_id'} : undef;

  $self->{dbh}->finish();

  my $subscriptionNode = $self->{packer}->makeSubscriptionNode($locked, $synchronized, $custom, $externalId);

  my %planQuantity;
  my $sql = "SELECT plan_id, quantity FROM PlansSubscriptions WHERE subscription_id = ?";
  if ( $self->{dbh}->execute_rownum($sql, $subscription_id) ) {
    while ( my $row = $self->{dbh}->fetchrow() ) {
      $planQuantity{$row->[0]} = $row->[1];
    }
  }
  $self->{dbh}->finish();

  while ( my ($planId, $quantity) = each(%planQuantity) ) {
    $sql = "SELECT type, uuid FROM Templates WHERE id = ?";
    unless ( $self->{dbh}->execute_rownum($sql, $planId) ) {
      Logging::warning( "Error during getting uuid from plan '$planId'");
      $self->{dbh}->finish();
      next;
    }
    if ( my $row = $self->{dbh}->fetchrow() ) {
      my ($planType, $planGuid) = @$row;
      my $is_addon;
      if ($planType eq 'domain_addon') {
        $is_addon = 'true';
      }
      $self->{packer}->addSubscriptionPlan($subscriptionNode, $quantity, $planGuid, $is_addon);
    }
    $self->{dbh}->finish();
  }

  return $subscriptionNode;
}

sub makeSpamassassinNode {
  my ( $self, $mailId, $mailNm, $domainAsciiname, $status, $dir ) = @_;

  my $mailname = $mailNm . '@' . $domainAsciiname;

  my $is_server = $mailname eq '*@*';

  my $spamFilter = DAL::getMailnameSpamfilter($mailname);
  unless ( exists $spamFilter->{'id'} ) {
    Logging::debug( "Unable get information about spam filter for $mailname");
    return;
  }

  my ( @blacckList, @whiteList, @unblackList, @unwhiteList, $action, $requiredScore, $subj_text );

  my $filter_id = $spamFilter->{'id'};
  if ( exists $spamFilter->{'reject_spam'} ) {
    $action = ( $spamFilter->{'reject_spam'} eq 'true') ? 'delete' : 'mark';
  }

  my $spamFilterPreferences = DAL::getSpamfilterPreferences($filter_id);
  unless ( $is_server || scalar(@$spamFilterPreferences) ) {
    $filter_id = DAL::getServerSpamfilterId();
    $spamFilterPreferences = DAL::getSpamfilterPreferences($filter_id) if defined $filter_id;
  }

  if(@$spamFilterPreferences) {
    foreach my $row (@$spamFilterPreferences) {
      if ($row->[0] eq "blacklist_from") {
        push @blacckList,  $row->[1];
      }
      elsif ($row->[0] eq "whitelist_from") {
        push @whiteList,   $row->[1];
      }
      elsif ($row->[0] eq "unblacklist_from") {
        push @unblackList, $row->[1];
      }
      elsif ($row->[0] eq "unwhitelist_from") {
        push @unwhiteList, $row->[1];
      }
      elsif ($row->[0] eq "required_score") {
        $requiredScore = $row->[1];
      }
      elsif ($row->[0] eq "rewrite_header") {
        $row->[1] =~ s/^subject //;
        $subj_text = $row->[1];
      }
      elsif ($row->[0] eq "action") {
        if ($row->[1] ne 'delete' && $row->[1] ne 'move') {
          $action = 'mark';
        } else {
          $action = $row->[1];
        }
      }
    }
  }

  my $spamServerSettings;
  if ($is_server) {
    $spamServerSettings = DAL::getSpamServerSettings();
    $status = $spamServerSettings->{'spamfilter_enabled'} if exists $spamServerSettings->{'spamfilter_enabled'};
  }

  $self->{packer}->setMailSpamSettings(
    $mailId, $mailNm, $domainAsciiname,
    ( $status eq 'true' ? 'on' : 'off' ),
    undef, $action,
    $requiredScore, undef, $subj_text,
    \@blacckList,    \@whiteList,
    \@unblackList,   \@unwhiteList,
    $dir, (keys(%$spamServerSettings)? $spamServerSettings : undef)
  );
}

sub getCustomButtonById71 {
  my ( $self, $id, $parent, $parentType, $sappId ) = @_;

  my $options;

  # several times there was empty button place in database, which is incorrect
  my $sql = "SELECT * FROM custom_buttons WHERE id=? AND place!=''";
  unless ( ( $self->{dbh}->execute_rownum($sql, $id) ) && ( $options = $self->{dbh}->fetchhash() ) ) {
    $self->{dbh}->finish();
    return;
  }
  $self->{dbh}->finish();


  my $customButtonsDir = AgentConfig::get("PRODUCT_ROOT_D") . '/admin/htdocs/images/custom_buttons';

  my $icon = $options->{'file'};

  if( $sappId ){
    if ( 'domain' eq $parentType ) {
      $self->{packer}->addDomainSappCustomButton( $parent, $sappId, $id, $options, $customButtonsDir, $icon );
    }
    elsif ( 'subdomain' eq $parentType ) {
      $self->{packer}->addSubDomainSappCustomButton( $parent, $sappId, $id, $options, $customButtonsDir, $icon );
    }
    else {
      Logging::warning( "Error: getCustomButtonById71: Unexpected type of siteapp parent '$parentType'",'assert');
    }
  }
  else{
    if ( 'server' eq $parentType ) {
      $self->{packer}->addServerCustomButton( $id, $options, $customButtonsDir, $icon );
    }
    elsif ( 'client' eq $parentType || 'reseller' eq $parentType ) {
      $self->{packer}->addClientCustomButton( $parent, $id, $options, $customButtonsDir, $icon );
    }
    elsif ( 'domain-admin' eq $parentType ) {
      $self->{packer}->addDomainCustomButton( $parent, $id, $options, $customButtonsDir, $icon );
    }
    elsif ( 'mailuser' eq $parentType ) {
      $self->{packer}->addMailCustomButton( $parent, $id, $options, $customButtonsDir, $icon );
    }
    elsif ( 'sapp' eq $parentType ) {
      $self->{packer}->addSappCustomButton( $parent, $id, $options, $customButtonsDir, $icon );
    }
    else {
      Logging::warning("Error: getCustomButtonById71: Unexpected type of parent '$parentType'",'assert');
    }
  }
}

sub getCustomButtonsByOwner71 {
  my ( $self, $owner_type, $owner_id ) = @_;
  my @ids = @{DAL::getCustomButtonIdsByOwner71($owner_type, $owner_id)};
  return unless @ids;
  return  map { $self->getCustomButtonById71( $_, $owner_id, $owner_type ) } @ids;
}

sub getCustomButtonsByOwner {
  my $self = shift;
  return $self->getCustomButtonsByOwner71(@_)
}

sub makeAdminInfoNode {
  my ( $self, $embeddedInfo ) = @_;

  my ( $max_btn_len, $send_announce, $external_id );

  my $ptrAdmin = DAL::getAdminMiscParams();

  if( exists $ptrAdmin->{'max_button_length'} ){
    $max_btn_len = $ptrAdmin->{'max_button_length'};
    delete $ptrAdmin->{'max_button_length'}
  }
  if( exists $ptrAdmin->{'send_announce'} ){
    $send_announce = $ptrAdmin->{'send_announce'};
    delete $ptrAdmin->{'send_announce'};
  }

  $external_id = DAL::getClientExternalId( PleskStructure::getAdminId() );

  $self->{packer}->addRootAdmin(PleskStructure::getAdminId(), PleskStructure::getAdminGuid(), DAL::getFullHostName());

  my $passwd = AgentConfig::get { 'password' };
  my $cron = undef;
  if (defined($embeddedInfo->{'scheduled-tasks'})) {
    $cron = $embeddedInfo->{'scheduled-tasks'}->[0];
  }

  my $subscriptionDescriptions = DAL::getSubscriptionsAdminDescriptions();
  my $clientsDescriptions = DAL::getClientsDescriptions( PleskStructure::getAdminId() );
  my @allAdminDescriptions = (@{$subscriptionDescriptions}, @{$clientsDescriptions});

  $self->{packer}->setServerAdminInfo( $ptrAdmin, $passwd, $max_btn_len, $send_announce, $external_id, $cron, \@allAdminDescriptions );

  my $adminClientParams = DAL::getAdminClientParams();
  my $adminId = PleskStructure::getAdminId();
  $self->{packer}->makeClParamNode($adminId, 'admin', $adminClientParams);

  $self->makeAdminLimitsAndPermissions();
}

sub dumpPlanItems {
  my ( $self) = @_;

  my @planItems = @{DAL::getPlanItems()};
  if ( @planItems ) {
    my $customButtonsDir = AgentConfig::get("PRODUCT_ROOT_D") . '/admin/htdocs/images/custom_buttons';
    foreach my $planItemPtr ( @planItems ) {
      my %planItemProps = %{DAL::getPlanItemProperties($planItemPtr->{'id'})};
      if ( keys %planItemProps ) {
        $self->{packer}->addPlanItemToServer($planItemPtr, \%planItemProps, $customButtonsDir);
      }
    }
  }
}

sub addServerIps {
  my ($self, $certPtr, $ipPtr) = @_;

  foreach my $ptrHash (@{DAL::getServerIps($certPtr)}) {
    if (defined $ipPtr) {
      foreach my $domainIp (@{$ipPtr}) {
        if ($domainIp eq $ptrHash->{'ip_address'}) {
          $ptrHash->{'type' } = PleskStructure::isExclusiveIp( $ptrHash->{'ip_address'} ) ? 'exclusive' : 'shared';
          $self->{packer}->addServerIp($ptrHash);
        }
      }
    } else {
      $ptrHash->{'type' } = PleskStructure::isExclusiveIp( $ptrHash->{'ip_address'} ) ? 'exclusive' : 'shared';
      $self->{packer}->addServerIp($ptrHash);
    }
  }
}

sub makeServerNode {
  my ( $self, $embeddedInfo ) = @_;

  $self->checkProgramTerminated();

  $self->{packer}->setServerSettings();

  if ( $self->{shallow_dump} ) {
    return;
  }

  #Dump server skeleton
  if ( !$self->{configuration_dump} && !$self->{only_mail_dump} ) {
    my $skeletonPath = AgentConfig::get('HTTPD_VHOSTS_D') . "/.skel/0";
    $self->{packer}->addServerSkeleton( $skeletonPath, "skel" );

    my $customTemplatesPath =  AgentConfig::get('PRODUCT_ROOT_D')."/admin/conf/templates/custom";
    if (-d $customTemplatesPath) {
      $self->{packer}->addServerCustomApacheTemplates($customTemplatesPath);
    }

    $self->{packer}->setSb5ServerContent();
    $self->{packer}->addServerCustomHealthConfig();

    if (-d AgentConfig::get('HTTPD_VHOSTS_D')."/fs") {
      $self->{packer}->addFileSharingContent(AgentConfig::get('HTTPD_VHOSTS_D')."/fs");
    }
  }

  #Dump system and default ip
  $self->addServerIps();

  #Dump hostname
  my $fullHostName = DAL::getFullHostName();
  $self->{packer}->setServerHostname($fullHostName) if $fullHostName;

  #Dump connection information to Billing
  my %adminClientParams = %{DAL::getAdminClientParams()};
  $self->{packer}->setPpbConnection(\%adminClientParams);

  # Dump database servers
  my %def = %{DAL::getDefaultServerParams()};

  foreach my $ptrHash (@{DAL::getDatabaseServers()}) {
    my $param = 'default_server_' . $ptrHash->{'type'};
    my $default = exists $def{$param} && $def{$param} == $ptrHash->{'id'};

    my $passwd;
    if ( $ptrHash->{'type'} eq 'mysql'
        && $ptrHash->{'host'} eq 'localhost' ) {
      $passwd = AgentConfig::get('password');
    }
    $self->{packer}->addServerDb( $ptrHash, $passwd, $default );
  }

  my %params = %{DAL::getMisc()};
  my %mailSettings = %{DAL::getMailServerSettings()};

    if( !$self->{configuration_dump} ){
      # Dump key info
      if (!$self->{no_license}) {
        my $phpini = AgentConfig::get('PRODUCT_ROOT_D') . "/admin/conf/php.ini";
        my $swkeyrepo = '/etc/sw/keys';
        if( -e $phpini ){
          open PHPINI, $phpini;
          while (<PHPINI>) {
            chomp;
            next if /^#/;
            next if /^$/;
            if ( $_ =~ /swkey\.repository_dir\s*=\s*[\"\']?(.+)[\"\']\s*$/) {
              $swkeyrepo = $1;
              Logging::debug( "Found sw key repository: $swkeyrepo" );
              last;
            }
          }
          close PHPINI;
        }
        if( -d "$swkeyrepo/keys" ){
          my $keyDir = "$swkeyrepo/keys";
          Logging::debug( "Load keys from '$keyDir'" );
          opendir DIR, "$keyDir";
          my @files = readdir( DIR );
          closedir DIR;
          foreach my $key(@files){
            if( $key ne '.' and $key ne '..' and -f "$keyDir/$key" ){
              my $cmd = AgentConfig::getLicenseCommand();
              if ($cmd) {
                $cmd .= " --get-instance-id $keyDir/$key";
                my $exec = `$cmd`;
                chomp($exec);
                $self->{packer}->addServerKey( $key, $key, $keyDir, 'false', $exec);
              }
            }
          }
        }
        else{
          Logging::warning( "Keys directory '$swkeyrepo/keys' is not found. The keys are not included to backup." ,'assert');
        }
      }
    }

      # Dump server settings

      $mailSettings{'rbl_server'} =~ s/;/,/g if ($mailSettings{'rbl_server'});

      # Dump mail settings
      my $mailmng = AgentConfig::getMailmngServerUtil();
      my $letter_size = undef;
      if ( -e $mailmng ) {
        $letter_size = `$mailmng --get-max-letter-size`;
        chomp($letter_size);
      }

      my @blackList = @{DAL::getBadmailfrom()};
      my %whiteList = %{DAL::getSmtpPoplocks()};
      my %ipAddresses = %{DAL::getIpAddresses()};
      my @externalWebmails = @{DAL::getExternalWebmail()};
      my $mailCertificate;
      if (exists $mailSettings{'inbound_ssl'} and $mailSettings{'inbound_ssl'} eq 'true' and exists $mailSettings{'inbound_ssl_cert_id'}) {
        my $certPk = $self->getDomainCertificatePrivateKey($mailSettings{'inbound_ssl_cert_id'});
        $mailCertificate = $self->getCertificateRef($certPk) if defined $certPk;
      }
      $self->{packer}->setServerMail($letter_size, \%params, \%mailSettings, \@blackList, \%whiteList, \%ipAddresses, \@externalWebmails, $mailCertificate);
      $self->{packer}->setServerDNS( \%params, DAL::getDnsRecsT() );
      $self->{packer}->setServerWebSettings($embeddedInfo);

      my $defaultCert;
      foreach my $id (@{DAL::getCertificateIds($params{'cert_rep_id'})}) {
        if ( $params{'default_certificate_id'} == $id ) {
          $defaultCert = 1;
        }
        else {
          $defaultCert = 0;
        }
        $self->makeCertificateNode( $id, 0, 'server', $defaultCert );
      }

      $self->{packer}->addPanelCertificate();
      $self->getCustomButtonsByOwner( 'server', 0 );
      $self->{packer}->setControlsVisibility( \%params );
      $self->{packer}->dumpUiMode(\%params);

      my $adminId = PleskStructure::getAdminId();
      $self->dumpTemplates( $adminId, 'server', 'domain' );
      $self->dumpTemplates( $adminId, 'server', 'domain_addon' );
      $self->dumpTemplates( $adminId, 'server', 'reseller' );
      $self->dumpPlanItems();

  if (!$self->{only_mail_dump}) {
    my %applicationsList = %{$self->getListOfInstalledApplicationsOnServer()};

    foreach my $application ( keys %applicationsList ) {
      my @applicationInfo = @{$applicationsList{$application}};
      $self->{packer}->addServerAppPackage( $applicationInfo[0], $applicationInfo[1], $applicationInfo[2], $applicationInfo[3], $applicationInfo[4], $applicationInfo[5], $applicationInfo[6], $applicationInfo[7]);
    }
  }

  $self->{packer}->setServerBackupSettings( \%params );

  $self->dumpServerPreferences( \%params );

  $self->makeSpamassassinNode(undef, "*", "*", undef, undef);

  # Server wide grey-listing preferences
  my $glParams = DAL::getGLParams();
  $glParams->{'personal-conf'} = defined($mailSettings{'spamfilter_use_mailuser_prefs'}) ? $mailSettings{'spamfilter_use_mailuser_prefs'} : "false";

  $self->{packer}->setGLServerSettings($glParams);
  $self->{packer}->addServerVirusfilter($mailSettings{'virusfilter'});
  $self->{packer}->setServerEventHandler($embeddedInfo);
  $self->{packer}->addServerSiteIsolationConfig();
  $self->dumpServerNotifications();
  $self->{packer}->addServerMailmanConfiguration();

  if (exists $params{'theme_skin'} and not $self->{configuration_dump}) {
    $self->{packer}->makeSkinNode($params{'theme_skin'}, 'admin', undef);
  }

  my %fsSettings = %{DAL::getFileSharingSettings()};
  $self->{packer}->dumpFileSharingServerSettings(\%fsSettings);
  if (!$self->{configuration_dump} && !$self->{only_mail_dump}) {
    $self->{packer}->dumpFileSharingUnlistedFiles(DAL::getFileSharingUnlistedFiles());
  }

  $self->{packer}->setMiscParameters(\%params);
  $self->{packer}->addCustomizationConfig();
  $self->{packer}->addRestrictedDomains();
  $self->{packer}->addFail2ban();
  $self->addModSecurityContent($embeddedInfo);
  $self->{packer}->makeExtensionNode('server', '');
}

sub dumpServerNotifications {
  my ( $self ) = @_;

  my $expirationWarnDays = DAL::getExpirationWarnDays() || 1;

  $self->{packer}->addServerNotifications( $expirationWarnDays, DAL::getNotifications(), DAL::getNotes() );
}

sub dumpServerPreferences {
  my ( $self, $params ) = @_;

  $self->{packer}->addServerPreferences( $params );

  foreach my $access ( @{DAL::getCpAccess()} ) {
    $self->{packer}->addRestrictionItem( $access->[0], $access->[1], $access->[2] );
  }

  if (defined($params->{'disable_mail_ui'})) {
    $self->{packer}->addDisableMailUiOption($params->{'disable_mail_ui'});
  }
  if (defined($params->{'crontab_secure_shell'}) || defined($params->{'crontab_secure_shell_compatibility_mode'})) {
    $self->{packer}->addCrontabSecureSettings($params);
  }

  my $technicalDomainName = undef;
  if (defined($params->{'preview_zone_domain_id'})) {
    $technicalDomainName = DAL::getDomainNameById($params->{'preview_zone_domain_id'});
  } elsif (defined($params->{'preview_zone_external_domain_name'})) {
    $technicalDomainName = $params->{'preview_zone_external_domain_name'};
  }
  $self->{packer}->addTechnicalPreviewDomainNode($technicalDomainName) if $technicalDomainName;

  my $autoUpdates = DAL::getSingleSmbSetting('automaticUpdates');
  my $autoUpdatesValue = (defined($autoUpdates) && $autoUpdates eq 'true') ? 'true' : 'false';
  my $autoUpgradeToStable = (defined($params->{'autoupgrade_to_stable'}) && $params->{'autoupgrade_to_stable'} eq 'true') ? 'true' : 'false';
  my $autoUpgradeBranch = (defined($params->{'autoupgrade_branch'})) ? $params->{'autoupgrade_branch'} : 'release';

  $self->{packer}->addUpdateSettings($autoUpdatesValue, $autoUpgradeToStable, $autoUpgradeBranch);

  my $ftpSettings = DAL::getFtpServerSettings();
  $self->{packer}->addFtpOverSslSettings($ftpSettings->{'ftpOverSsl'}) if exists $ftpSettings->{'ftpOverSsl'};
}

sub getApsArchiveFileName {
  my ( $self, $distrib_path, $package_info ) = @_;

  my %mapHash = MiscConfigParser::parseApsIndexFile( $distrib_path . "/archive-index.xml" );
  my $file_name;

  foreach my $tfile_name ( keys %mapHash ) {
    if ( $package_info eq join( "-", @{ $mapHash{$tfile_name} } ) ) {
      $file_name = $tfile_name;
    }
  }

  return $file_name;
}

sub getDomainKeysDomainSupport {
  my ( $self, $domainId, $domainName, $dnsZoneId ) = @_;

  my $state = DAL::getDomainKeysState($domainId);
  return unless defined $state;

  my $publickKey = DAL::getDomainKeysPublicKey($domainName, $dnsZoneId);

  $self->{packer}->setDomainKeysDomainSupport( $domainId, $domainName, $state,
    '/etc/domainkeys/' . $domainName, $publickKey );
}

sub getOldWebMailStatus {
  my ($self, $dnsZoneId) = @_;
  my $sql;
  my $retCode = undef;
  $sql = "SELECT * FROM dns_recs WHERE dns_zone_id=?";
  if ( $self->{dbh}->execute_rownum($sql, $dnsZoneId) ) {
    while ( my $ptrHash = $self->{dbh}->fetchhash() ) {
      if ($ptrHash->{'displayHost'} =~ /.*webmail.*/) {
        $retCode = 1;
      }
    }
  }
  $self->{dbh}->finish();
  return $retCode;
}

sub dumpApsBundle {
  my ($self, $parentId, $parentType) = @_;

  my $properties = $self->getSubscriptionPropertiesHash($parentId, $parentType);

  my $filterId = $properties->{'aps_bundle_filter_id'};

  if (!$filterId or $filterId eq '') {
    return;
  }

  my $filterType = DAL::getApsBundleFilterType($filterId);
  my $items = DAL::getApsBundleFilterItems($filterId);
  $self->{packer}->makeApsBundleFilterNode($parentId, $parentType, $filterType, $items, undef);
}

sub getServiceIps {
  my ($self, $domainId, $service , $ip) = @_;
  my %serviceIpAdresses;
  my @ips = @{DAL::getServiceIps( $domainId, $service)};
  foreach my $serviceIpAddress (@ips) {
    $serviceIpAdresses{$serviceIpAddress} = PleskStructure::getIpType($serviceIpAddress);
  }
  return \%serviceIpAdresses;
}

sub getConcreteApplicationInfo {
  my ( $self, $ptrApplications, $registryUid ) = @_;
  my @applications = @{$ptrApplications};
  foreach my $application ( @applications ) {
    if ( $application->getRegistryUid() eq $registryUid) {
      return $application;
    }
  }
}

sub makeMailListsNode {
  my ($self, $domainId, $domainAsciiName, $ip) = @_;

  if ( !$self->{only_hosting_dump} ) {
    Logging::debug("Dumping maillists");
    my $maillistsStatus = $self->getDomainMaillistStatus($domainId);
    if (defined $maillistsStatus) {
      my $serviceIps = $self->getServiceIps( $domainId, 'maillists', $ip);
      $self->{packer}->setDomainMailLists( $domainId, $maillistsStatus, $serviceIps);

      my $archiveDir = AgentConfig::get("MAILMAN_VAR_D") . "/archives/private";

      my @maillists = @{DAL::getDomainMaillists($domainId)};
      if ( @maillists ) {
        my @archiveDirs;
        for my $ptrRow ( @maillists ) {
          my $maillistDir = lc($ptrRow->[1]);
          push @archiveDirs, $maillistDir  if -d "$archiveDir/" . $maillistDir;
          my $datafile = $maillistDir . ".mbox";
          push @archiveDirs, $datafile  if -d "$archiveDir/$datafile";
          $self->makeMailmanMailListNode( $domainId, @{$ptrRow} );
        }
        if (@archiveDirs) {
          my $mailmanUserInfo = AgentConfig::getMailmanUserInfo();
          $self->{packer}->setDomainMailListContent( $domainId, $domainAsciiName, $archiveDir,
                                                     { 'include' => \@archiveDirs, 'follow_symlinks' => 1, 'sysuser' => $mailmanUserInfo->{'user'}} );
       }
      }
    }
  } else {
    Logging::debug("Skip maillists due to settings");
  }
}

sub generateObjectUuidForLogging {
  my $self = shift;
  return join('#', @_);
}

sub getDomainParams {
  my ( $self, $domainPtr ) = @_;
  my %domain = %{$domainPtr};
  my %domainParams = %{DAL::getDomainParams($domain{'id'})};
  return \%domainParams;
}

sub addUsers {
  my ( $self, $usersList, $callback, $callbackFailed ) = @_;

  for my $userLogin ( @{$usersList} ) {
    eval {
      my $userHash = DAL::getUserHash( $userLogin );
      next if (! keys %$userHash);  # if hash is empty (error occurred)
      my $assignedApplications = DAL::getUserAssignedApplications( $userHash->{'id'} );
      $userHash->{'assignedApplications'} = $assignedApplications;
      $callback->( $userLogin, $userHash );
      1;
    } or do {
      $self->excludeProblemUserFromBackup( $userLogin, $callbackFailed, $@ );
    }
  }
}

sub addModSecurityContent {
  my ($self, $embeddedInfo) = @_;
  return unless defined($embeddedInfo->{'web-settings'});

  my $ruleSet = '';
  foreach my $webSettings (@{$embeddedInfo->{'web-settings'}}) {
    if ('' eq $webSettings) {
        last;
    }
    foreach my $setting (@{$webSettings->{'setting'}}) {
      my ($name) = @{$setting->{'name'}};
      my ($value) = @{$setting->{'value'}};
        if ('ruleSet' eq $name) {
          $ruleSet = $value;
          last;
      }
    }
  }
  return if ('' eq $ruleSet);

  my $rulesBaseDir = undef;
  my $cmd = AgentConfig::getModSecurityCtlUtil() . ' --rules-base-dir';
  Logging::debug("Exec: $cmd");
  my $ret = `$cmd`;
  if (0 == ($? >> 8)) {
      $rulesBaseDir = HelpFuncs::trim($ret);
  } else {
    Logging::warning('Cannot execute command: ' . $cmd . ', STDOUT: ' . $ret, 'UtilityError');
    return;
  }

  my @ruleSetDirs = ();
  $cmd = AgentConfig::getModSecurityCtlUtil() . ' --list-rulesets';
  Logging::debug("Exec: $cmd");
  $ret = `$cmd`;
  if (0 == ($? >> 8)) {
    foreach my $ruleSetDir (split(/\n/, $ret)) {
      if ($ruleSetDir =~ /\.saved-\d+/) {
        next;
      }
      push(@ruleSetDirs,  "$ruleSetDir/");
    }
  } else {
    Logging::warning('Cannot execute command: ' . $cmd . ', STDOUT: ' . $ret, 'UtilityError');
    return;
  }

  if (@ruleSetDirs) {
    $self->{packer}->addModSecurityContent($rulesBaseDir, @ruleSetDirs);
  }
}

sub getDomainCertificatePrivateKey {
  my ($self, $certificateId, $domainId) = @_;

  if (0 != $certificateId) {
    return DAL::getCertificatePrivateKey($certificateId);
  }
  my @defaultCerts = @{$self->getDomainDefaultCert($domainId)};
  if (@defaultCerts) {
    return DAL::getCertificatePrivateKey($defaultCerts[0]);
  }
  return undef;
}

sub getCertificateRef {
  my ($self, $privateKey) = @_;

  my $md5 = PerlMD5->new();
  $md5->add(HelpFuncs::urlDecode($privateKey));
  return $md5->hexdigest();
}

sub _initDomainContentStat {
  my ($self, $domainId) = @_;

  my $sql = "SELECT httpdocs + httpsdocs, dbases, mailboxes FROM disk_usage WHERE dom_id=?";
  if ($self->{dbh}->execute_rownum($sql, $domainId)) {
    if (my $ptrRow = $self->{dbh}->fetchrow()) {
      $self->{packer}->{stat}{vhostSizeOnFS} += @{$ptrRow}[0];
      $self->{packer}->{stat}{dbSizeOnFS} += @{$ptrRow}[1];
      $self->{packer}->{stat}{mailSizeOnFS} += @{$ptrRow}[2];
    }
  }
  $self->{dbh}->finish();
}

sub _reportDomainStatistics {
  my ($self) = @_;

  my ($storageType, $storageName) = ('local', '');
  if (ref($self->{packer}->{storage}) =~ /Storage::ArchiveStorage/) {
    if ($self->{packer}->{storage}->{exportDir} =~ /^ftps?:\/\//) {
      $storageType = 'foreign-ftp';
    } elsif ($self->{packer}->{storage}->{exportDir} =~ /^ext:\/\/([^\/]+)/) {
      $storageType = 'extension';
      $storageName = $1;
    }
  }

  my ($objectType, undef, undef, undef, $objectId) = @{$self->getMainDumpInfo()};

  my $xml =
    "<stat>".
      "<storage>".
        "<type>".$storageType."</type>".
        "<name>".$storageName."</name>".
      "</storage>".
      "<object>".
        "<type>".$objectType."</type>".
        "<id>".$objectId."</id>".
      "</object>".
      "<dump>".
        "<incremental>".($self->getIncrementalCreationDate() ? 'true' : 'false')."</incremental>".
        "<size>".int($self->{packer}->{storage}->getDumpFilesSize()/1048576)."</size>".
      "</dump>".
      "<vhost>".
        "<count>".$self->{packer}->{stat}{vhostDumpsCount}."</count>".
        "<size>".$self->{packer}->{stat}{vhostSizeOnFS}."</size>".
        "<dumpsize>".$self->{packer}->{stat}{vhostSizeDumped}."</dumpsize>".
      "</vhost>".
      "<db>".
        "<count>".$self->{packer}->{stat}{dbDumpsCount}."</count>".
        "<size>".$self->{packer}->{stat}{dbSizeOnFS}."</size>".
        "<dumpsize>".$self->{packer}->{stat}{dbSizeDumped}."</dumpsize>".
      "</db>".
      "<mail>".
        "<count>".$self->{packer}->{stat}{mailDumpsCount}."</count>".
        "<size>".$self->{packer}->{stat}{mailSizeOnFS}."</size>".
        "<dumpsize>".$self->{packer}->{stat}{mailSizeDumped}."</dumpsize>".
        "</mail>".
    "</stat>";

  my $cmd = AgentConfig::getBackupRestoreHelperUtil();
  push(@{$cmd}, '--report-backup-info', "\"$xml\"");

  Logging::debug("Exec: @{$cmd}");
  `@{$cmd}`;
}

sub _isDatabaseIncluded {
  my ($self, $id) = @_;

  return 1 unless defined($self->{includeDatabases});
  return (grep { $_->{'id'} eq $id } @{$self->{includeDatabases}}) ? 1 : 0;
}

sub _validateDatabaseIncluded {
  my ($self, $domainId, $domainName) = @_;

  return unless defined($self->{extension_data}) and defined($self->{includeDatabases});

  my @domainDatabases = @{DAL::getDomainDatabases($domainId)};

  my @includeDatabases = ();
  foreach my $includedDatabase (@{$self->{includeDatabases}}) {
    if (grep { $_->{'id'} eq $includedDatabase->{'id'} } @domainDatabases) {
      push @includeDatabases, $includedDatabase;
    } else {
      Logging::warning(sprintf(
        "The database #%d passed by the extension '%s' does not exist or belong to the subscription '%s'. It shall not be backed up."
        , $includedDatabase->{'id'}, $self->{extension_data}->{name}, $domainName
      ));
    }
  }
  $self->{includeDatabases} = \@includeDatabases;
}

sub _getIncludedDatabaseTables {
  my ($self, $id, $name, $existentTables) = @_;

  return undef unless defined($self->{extension_data}) and defined($self->{includeDatabases});

  my @database = grep { $_->{'id'} eq $id } @{$self->{includeDatabases}};
  return undef unless @database and defined($database[0]->{'table'}) and @{$database[0]->{'table'}};

  my @includedTables = ();
  my @notExistentTables = ();
  foreach my $includedTable (@{$database[0]->{'table'}}) {
    if (grep { $_ eq $includedTable } @{$existentTables}) {
      push @includedTables, $includedTable;
    } else {
      push @notExistentTables, $includedTable;
    }
  }
  if (@notExistentTables) {
    Logging::warning(sprintf(
        "The following tables passed by the extension '%s' do not exist in the database #%d '%s': %s. %s"
        , $self->{extension_data}->{name}, $id, $name, join(', ', map {"'$_'"} @notExistentTables)
        , @includedTables ? 'They shall not be backed up.' : 'The database content shall not be backed up.'
    ));
  }
  return \@includedTables;
}

sub isProgramTerminated {
  my ($self) = @_;

  return 0 unless $self->{sessionPath};
  return (-e "$self->{sessionPath}/task_stopped") ? 1 : 0;
}

sub checkProgramTerminated {
  my ($self) = @_;

  if ($self->isProgramTerminated()) {
    die('Program has been terminated');
  }
}

sub getMainDumpInfo {
  my ($self) = @_;

  my ($type, $name, $normalizedName, $guid, $id);
  if ($self->{dump_all} && $self->{dump_all} == 1) {
    $type = 'server';
  } elsif (exists $self->{resellers} && scalar @{$self->{resellers}} > 0) {
    if (scalar @{$self->{resellers}} == 1) {
      $type = 'reseller';
      $name = $self->{resellers}->[0];
      $id = $self->getClientId($name);
      $normalizedName = $self->{packer}->{fnamecreator}->normalize_short_string($name, $id);
      $guid = $self->getClientGuid($name);
    } else {
      $type = 'server';
    }
  } elsif (exists $self->{clients} && scalar @{$self->{clients}} > 0) {
    if (scalar @{$self->{clients}} == 1) {
      $type = 'client';
      $name = $self->{clients}->[0];
      $id = $self->getClientId($name);
      $normalizedName = $self->{packer}->{fnamecreator}->normalize_short_string($name, $id);
      $guid = $self->getClientGuid($name);
    } else {
      $type = 'server';
    }
  } elsif (exists $self->{domains} && scalar @{$self->{domains}} > 0) {
    if (scalar @{$self->{domains}} == 1) {
      $type = 'domain';
      $name = $self->getDomainAsciiName($self->{domains}->[0]);
      $id = $self->getDomainId($self->{domains}->[0]);
      $normalizedName = $self->{packer}->{fnamecreator}->normalize_long_string($name, $id);
      $guid = $self->getDomainGuid($self->{domains}->[0]);
    } else {
      $type = 'server';
    }
  }
  return [$type, $name, $normalizedName, $guid, $id];
}

1;
