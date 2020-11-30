# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
package BackupSizeCalculator;

use strict;
eval{require warnings;1;};
use Logging;
use PleskStructure;
use PleskVersion;
use AgentConfig;
use HelpFuncs;
use HostingDumper;
use ObjectsFilter;
use MiscConfigParser;

###### Common functions ######

sub new {
  my $self = {};
  bless( $self, shift );
  $self->_init(@_);
  return $self;
}

sub _init {
  my ( $self, $pleskX ,$incrementalBackup, $backupToFtp) = @_;
  $self->{agent} = $pleskX;
  $self->{dbh} = $pleskX->{dbh};
  $self->{filter} = ObjectsFilter->new($pleskX);
  $self->{incremental} = $incrementalBackup;
  $self->{backupToFtp} = $backupToFtp;
}

###### Size mode functions ######
sub getSize {
  my ($self) = @_;
  Logging::debug("Get backup size for selected objects");

  my $size = 0;
  if ( $self->{agent}->{configuration_dump} ) {
    Logging::debug("Get backup size finished. Configuration-only mode detected. Backup size is reported as 0 bytes");
    return "$size,$size";
  }
  
  if ( exists $self->{agent}->{dump_all} ) {
    my @resellers = sort { $a cmp $b } PleskStructure::getResellers();
    @resellers = $self->{filter}->filterSelectedResellers( \@resellers );
    foreach my $reseller ( @resellers ) {
      $size += $self->getSelectedResellerBackupSize( $reseller );
    }
    my @clients = sort { $a cmp $b } PleskStructure::getAdminClients();
    @clients = $self->{filter}->filterSelectedClients( \@clients );
    foreach my $client ( @clients ) {
      if( PleskStructure::getClientType( $client ) eq 'client' ){
        $size += $self->getSelectedClientBackupSize( $client );
      }
    }
    my @adminDomains = PleskStructure::getAdminDomains();
    @adminDomains = $self->{filter}->filterSelectedDomains( \@adminDomains );
    foreach my $domainName(@adminDomains) {
      $size += $self->getDomainBackupSize( $domainName );
    }
  }
  elsif ( exists $self->{agent}->{resellers} ){
    foreach my $reseller ( @{ $self->{agent}->{resellers} } ) {
      $size += $self->getSelectedResellerBackupSize( $reseller );
    }
  }
  elsif ( exists $self->{agent}->{clients} ) {
    foreach my $client ( @{ $self->{agent}->{clients} } ) {
      $size += $self->getSelectedClientBackupSize( $client );
    }
  }
  elsif ( exists $self->{agent}->{domains} ) {
    foreach my $domain ( @{$self->{agent}->{domains}} ) {
      $size += $self->getDomainBackupSize( $domain );
    }
  }

  if( $self->{agent}->{server_settings} ){
      $size += $self->getServerSettingsBackupSize();
  }

  Logging::debug("Get backup size finished. Backup size of selected objects is $size bytes");
  return "$size,$size";
}

sub getSelectedResellerBackupSize {
  my ($self, $reseller) = @_;
  my $size = 0;

  $size += $self->getSelectedClientBackupSize($reseller);

  my @myclients = sort { $a cmp $b } PleskStructure::getMyClients($reseller);
  @myclients = $self->{filter}->filterSelectedClients( \@myclients );
  foreach my $client ( @myclients ) {
    $size += $self->getSelectedClientBackupSize( $client );
  }
  return $size;
}

sub getSelectedClientBackupSize {
  my ($self, $client ) = @_;
  my $size = 0;

  my @mydomains = sort { $a cmp $b } PleskStructure::getDomainsForClient($client);
  @mydomains = $self->{filter}->filterSelectedDomains( \@mydomains );
  $size += $self->getClientBackupSize($client);
  foreach my $domain ( @mydomains ) {
    $size += $self->getDomainBackupSize( $domain );
  }
  return $size;
}

sub getDomainBackupSize {
  my ($self, $domain ) = @_;
  my $size = 0;
  Logging::debug("Get backup size for domain '$domain'");
  if ( $self->{agent}->{configuration_dump} ) {
    Logging::debug("Get backup size for domain '$domain' finished. Configuration-only mode detected. Size is $size bytes.");
    return 0;
  }

  my $domainId = PleskStructure::getDomainId($domain);
  if ( not defined $domainId ) {
    Logging::warning("Failed to get domain id for '$domain'",'PleskDbError');
    return 0;
  }

  my $sql = "SELECT name FROM domains WHERE id=?";
  my $domainAsciiName;
  if ( $self->{dbh}->execute_rownum($sql, $domainId) and my $ptrRow = $self->{dbh}->fetchrow() ) {
    $domainAsciiName = $ptrRow->[0];
  }
  $self->{dbh}->finish();

  if ( not defined $domainAsciiName ) {
    Logging::warning("Failed to get domain name for '$domain'",'PleskDbError');
    return 0;
  }

  my @columns = ();

  if (!$self->{agent}->{only_hosting_dump} and !$self->{agent}->{only_mail_dump}) {
    push(@columns, 'dbases');
  }

  if (!$self->{incremental}) {
    if (!$self->{agent}->{only_hosting_dump}) {
      push(@columns, 'mailboxes');
      push(@columns, 'maillists');
    }

    if (!$self->{agent}->{only_mail_dump}) {
      push(@columns, 'httpdocs');
      push(@columns, 'httpsdocs');
      push(@columns, 'subdomains');
      push(@columns, 'web_users');
      push(@columns, 'anonftp');
    }
  }
  $size = $self->getDomainDiskUsageStats($domainId, @columns);

  if (!$self->{incremental} and !$self->{agent}->{only_mail_dump}) {
    $size += $self->getDomainCustomButtonsSize( $domain, $domainId );
  }
  Logging::debug("Get backup size for domain '$domain' finished. Size is $size bytes.");
  return $size;
}

sub getDomainCustomButtonsSize{
  my ($self, $domain, $domainId ) = @_;
  my $size = 0;
  Logging::debug("Domain '$domain': ". (caller(0))[3]." started.");

  my @buttonIds = @{DAL::getCustomButtonIdsByOwner71('domain-admin', $domainId)};
  foreach my $buttonId ( @buttonIds ) {
    $size += $self->getCustomButtonSize( $buttonId ) if defined $buttonId;
  }

  Logging::debug("Domain '$domain': ". (caller(0))[3]." finished. Size is $size bytes.");
  return $size;
}

sub getDomainDiskUsageStats {
  my ($self, $domainId, @columns) = @_;

  return 0 unless scalar(@columns);

  my $size = 0;
  my $sql = "SELECT ".join( ',', @columns )." FROM disk_usage WHERE dom_id=?";
  Logging::debug($sql);
  if ($self->{dbh}->execute_rownum($sql, $domainId)) {
    my $row = $self->{dbh}->fetchrow();
    for my $i (0 .. scalar(@columns) - 1) {
      $size += $row->[$i];
    }
  }
  $self->{dbh}->finish();

  return $size;
}

sub getClientBackupSize {
  my ($self, $client ) = @_;
  my $size = 0;
  Logging::debug("Get backup size for client '$client'");

  if ( $self->{agent}->{configuration_dump} ) {
    Logging::debug("Get backup size for client '$client' finished. Configuration-only mode detected. Size is $size bytes.");
    return 0;
  }

  my $clientId = PleskStructure::getClientId($client);
  if ( not defined $clientId ) {
    Logging::warning("Failed to get client id for '$client'",'PleskDbError');
    return 0;
  }

  if ( !$self->{agent}->{only_mail_dump} ) {
    $size += $self->getClientSkeletonSize($client, $clientId);
  }

  if ( !$self->{agent}->{only_mail_dump} ) {
    $size += $self->getClientCustomButtonsSize($client, $clientId);
  }

  Logging::debug("Get backup size for client '$client' finished. Size is $size bytes.");
  return $size;
}

sub getClientSkeletonSize {
  my ($self, $client, $clientId ) = @_;
  my $size = 0;
  Logging::debug("Client '$client': ". (caller(0))[3]." started.");

  my $skeletonPath = AgentConfig::get('HTTPD_VHOSTS_D') . "/.skel/$clientId";
  if ( -e $skeletonPath ) {
    $size += $self->getCidSize($skeletonPath);
  }

  Logging::debug("Client '$client': ". (caller(0))[3]." finished. Size is $size bytes.");
  return $size;
}

sub getClientCustomButtonsSize {
  my ($self, $client, $clientId ) = @_;
  my $size = 0;
  Logging::debug("Client '$client': ". (caller(0))[3]." started.");

  my @buttonIds = @{DAL::getCustomButtonIdsByOwner71('client', $clientId)};
  foreach my $buttonId ( @buttonIds ) {
    $size += $self->getCustomButtonSize( $buttonId ) if defined $buttonId;
  }

  Logging::debug("Client '$client': ". (caller(0))[3]." finished. Size is $size bytes.");
  return $size;
}

sub getServerSettingsBackupSize {
  my ($self) = @_;
  my $size = 0;
  Logging::debug("Get backup size for server settings");
  if ( $self->{agent}->{configuration_dump} ) {
    Logging::debug("Get backup size for server settings finished. Configuration-only mode detected. Size is $size bytes.");
    return 0;
  }

  my $skeletonPath = AgentConfig::get('HTTPD_VHOSTS_D') . "/.skel/0";
  $size += $self->getCidSize($skeletonPath);

  $size += $self->getServerSettingsKeysSize();

  $size += $self->getServerCustomButtonsSize();

  Logging::debug("Get backup size of server settings finished. Size is $size bytes.");
  return $size;
}

sub getServerSettingsKeysSize {
  my ($self) = @_;
  my $size = 0;
  Logging::debug("Server: ". (caller(0))[3]." started.");

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
        Logging::debug( "getServerSettingsBackupSize: Found sw key repository: $swkeyrepo" );
        last;
      }
    }
    close PHPINI;
  }
  if( -d "$swkeyrepo/keys" ){
    my $keyDir = "$swkeyrepo/keys";
    Logging::debug( "getServerSettingsBackupSize: Load keys from '$keyDir'" );
    opendir DIR, "$keyDir";
    my @files = readdir( DIR );
    closedir DIR;
    foreach my $key(@files) {
      if( $key ne '.' and $key ne '..' and -f "$keyDir/$key" ){
        $size += $self->getCidSize("$keyDir/$key");
      }
    }
  }
  else{
    Logging::debug( "getServerSettingsBackupSize: Keys directory '$swkeyrepo/keys' is not found. The keys are not included to backup." );
  }

  Logging::debug("Server: ". (caller(0))[3]." finished. Size is $size bytes.");
  return $size;
}

sub getServerCustomButtonsSize {
  my ($self) = @_;
  my $size = 0;
  Logging::debug("Server: ". (caller(0))[3]." started.");

  my @buttonIds = @{DAL::getCustomButtonIdsByOwner71('server', 0)};
  foreach my $buttonId ( @buttonIds ) {
    $size += $self->getCustomButtonSize( $buttonId ) if defined $buttonId;
  }

  Logging::debug("Server: ". (caller(0))[3]." finished. Size is $size bytes.");
  return $size;
}

sub getCustomButtonSize {
  my ($self, $id) = @_;
  my $size = 0;

  my $customButtonsDir = AgentConfig::get("PRODUCT_ROOT_D") . '/admin/htdocs/images/custom_buttons';
  my $sql = "SELECT file FROM custom_buttons WHERE id=? AND place!=''";
  if ( $self->{dbh}->execute_rownum($sql, $id) and my $ptrRow = $self->{dbh}->fetchrow() ) {
    my $file = $ptrRow->[0];
    if ( $file ) {
      $size += $self->getCidSize("$customButtonsDir/$file");
    }
  }
  $self->{dbh}->finish();

  return $size;
}

sub getCidSize {
  my ($self, $path, $no_recursion) = @_;

  use bigint;

  my $cmd = "du -sb";
  if ( defined $no_recursion ) {
    $cmd .= " -S";
  }
  $cmd .= " $path";
  my $sizes = `$cmd`;

  my $unpacksize = 0;
  for(split /[\r\n]+/,$sizes) {
    my ($number, $file) = split /\t/;
    $unpacksize += $number;
  }
  Logging::debug("Retrieve size of '$path'" . ($no_recursion? "without recursion" : "" ) . ": $unpacksize bytes" );
  return $unpacksize;
}

###### End Size mode functions ######

1;