# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
package SpecificConfig;

use strict;
use warnings;
use CommonConfig;
use PleskVersion;

use vars qw|@ISA|;

@ISA=qw|CommonConfig|;

sub _init {
  my ($self) = @_;

  return unless $self->SUPER::_init(@_);

  my $configFileName = "/etc/psa/psa.conf";
  my $passwordFileName = "/etc/psa/.psa.shadow";
  my $sbConfigFileName = "/etc/swsoft/siteeditor/siteeditor.conf";
  return unless -r $configFileName;
  return unless -r $passwordFileName;

  open CONFIG, $configFileName;
  binmode(CONFIG);
  while (<CONFIG>) {
    chomp;
    next if /^#/;
    next if /^$/;
    if (/^\s*(\S+)\s+(\S+)\s*$/) {
      $self->{config}->{$1} = $2;
    }
  }
  close CONFIG;

  open PASSWORD, $passwordFileName;
  binmode(PASSWORD);
  my $password = <PASSWORD>;
  close PASSWORD;

  chomp $password;
  $self->{config}->{'password'} = $password;

  my @paths = ("/usr/local/psa/python2.2/bin",
               "/bin", "/usr/bin", "/usr/local/bin",
               "/sbin", "/usr/sbin", "/usr/local/sbin");
  $self->__addPaths(@paths);

  my $versionFileName = $self->{config}->{'PRODUCT_ROOT_D'}. '/version';
  open VERSION, $versionFileName;
  binmode (VERSION);
  my $out = <VERSION>;
  chomp $out;
  close VERSION;

  if ($out =~ /^(\d+)\.(\d+)\.(\d+)\s(\w+)(.*)$/) {
    $self->{config}->{'os'} = $4;
  }

  $self->{shared_dir} = undef;

  if (-r $sbConfigFileName) {
    open SBCONFIG, $sbConfigFileName;
    binmode(SBCONFIG);
    while(<SBCONFIG>) {
      chomp;
      if (/^\s*(\S+)=(\S+)\s*$/) {
        $self->{config}->{$1} = $2;
      }
    }
    close SBCONFIG;
  }

  return 1;
}

sub _isDebian() {
  my ($self) = @_;
  return -e "/etc/debian_version";
}

sub _isSuse() {
  my ($self) = @_;
  return -e "/etc/SuSE-release";
}

sub getApacheUserInfo {
  my ($self) = @_;
  my %apacheUser;
  if ($self->_isDebian()) {
    $apacheUser{'user'}  = 'www-data';
    $apacheUser{'group'} = 'www-data';
  } elsif ($self->_isSuse()) {
    $apacheUser{'user'}  = 'wwwrun';
    $apacheUser{'group'} = 'www';
  } else {
    $apacheUser{'user'}  = 'apache';
    $apacheUser{'group'} = 'apache';
  }
  $apacheUser{'uid'}  = getpwnam($apacheUser{'user'});
  $apacheUser{'gid'} = getgrnam($apacheUser{'group'});
  return \%apacheUser;
}

sub get {
  my ($self, $param) = @_;
  return $self->{config}->{$param};
}

sub mailmanRoot {
  my ($self) = @_;
  return $self->get("MAILMAN_ROOT_D");
}

sub getMailmanUserInfo {
  my ($self) = @_;
  my %mailmanUser;
  if ($self->_isDebian()) {
    $mailmanUser{'user'}  = 'list';
    $mailmanUser{'group'} = 'list';
  } else {
    $mailmanUser{'user'}  = 'mailman';
    $mailmanUser{'group'} = 'mailman';
  }
  return \%mailmanUser;
}

sub mailContentUser {
  my ($self) = @_;
  return "popuser";
}

sub pythonBin {
  my ($self) = @_;
  return $self->get("PYTHON_ROOT_D") . '/bin/python' if defined $self->get("PYTHON_ROOT_D");
  return $self->get("PYTHON_BIN") if defined $self->get("PYTHON_BIN");
  return $self->__findInPath("python");
}

sub psqlBin {
  my ($self) = @_;
  return $self->get("PGSQL_BIN_D") . "/psql" if defined $self->get("PGSQL_BIN_D");
  return $self->__findInPath("psql");
}

sub pgdumpBin {
  my ($self) = @_;
  return $self->get("PGSQL_BIN_D") . "/pg_dump" if defined $self->get("PGSQL_BIN_D");
  return $self->__findInPath("pg_dump");
}

sub mysqlBin {
  my ($self) = @_;
  return $self->get("MYSQL_BIN_D") . "/mysql" if defined $self->get("MYSQL_BIN_D");
  return $self->__findInPath("mysql");
}

sub mysqldumpBin {
  my ($self) = @_;
  return $self->get("MYSQL_BIN_D") . "/mysqldump" if defined $self->get("MYSQL_BIN_D");
  return $self->__findInPath("mysqldump");
}

sub __detectTar {
  my ($self) = @_;

  my $pleskTar = $self->get("TAR_BIN");
  return [$pleskTar, $self->__tarVersion($pleskTar)] if $pleskTar ne '';

  return $self->SUPER::__detectTar();
}

sub pg_manageBin {
  my ($self) = @_;
  return $self->get("PRODUCT_ROOT_D") . '/admin/sbin/pg_manage';
}

sub dumpDir {
  my ($self) = @_;
  return $self->get("DUMP_D");
}

sub sbbackupBin {
  my ($self) = @_;
  return $self->get("SITEBUILDER_HOME") . '/utils/sbbackup';
}

sub sbRoot {
  my ($self) = @_;
  return $self->get("SITEBUILDER_HOME");
}

sub sb5Root {
  my ($self) = @_;
  return '/usr/local/sb';
}

sub apsInstancesUtil {
    my ($self) = @_;
    return $self->__getPhpUtil($self->__productPath('/admin/plib/api-cli/aps.php'));
}

sub pmmRasBin {
  my ($self) = @_;
  return $self->get("PRODUCT_ROOT_D") . '/admin/sbin/pmm-ras';
}

# Setting directory when shared agents content reside

sub setSharedDir {
  my ($self, $sharedDir) = @_;
  $self->{shared_dir} = $sharedDir;
}

sub getSharedDir {
  my ($self) = @_;
  return $self->{shared_dir};
}

sub __productPath {
  my ($self, $path_in_product) = @_;
  return $self->get("PRODUCT_ROOT_D") . $path_in_product;
}

sub __getSwEnginePath {
  my ($self) = @_;
  return $self->get('PRODUCT_ROOT_D') . '/bin/sw-engine-pleskrun';
}

sub __getPhpUtil {
  my ($self, $path) = @_;
  if (-e $path) {
    return $self->__getSwEnginePath() . " $path";
  }
  else {
    return;
  }
}

sub sb5BackupUtil {
  my ($self) = @_;
  return $self->__getPhpUtil($self->sb5Root() . '/utils/bru.php');
}

sub sb5SitePublishedUtil {
  my ($self) = @_;
  return $self->__getPhpUtil($self->__productPath('/admin/plib/api-cli/domain_pref.php'));
}

sub brandingUtil {
  my ($self) = @_;
  return $self->__getPhpUtil($self->__productPath('/admin/plib/api-cli/branding_theme.php'));
}

sub getSuspendCommand{
  my ($self) = @_;
  return $self->__getPhpUtil($self->__productPath('/admin/plib/backup/suspend_handler/SuspendHandlerRunner.php'));
}

sub getLicenseCommand {
  my ($self) = @_;
  return $self->__getPhpUtil($self->__productPath('/admin/plib/api-cli/license.php'));
}

sub getProductModeCommand{
  my ($self) = @_;
  return $self->__getPhpUtil($self->__productPath('/admin/plib/api-cli/product_info.php'));
}

sub getRemoteAgentExecutorCommand{
  my ($self) = @_;
  return $self->__getPhpUtil($self->__productPath('/admin/plib/api-cli/remote_agent_executor.php'));
}

sub xmllintBin {
  my ($self) = @_;  
  return $self->__findInPath("xmllint");
}

sub getServiceNodeCommand {
  my ($self) = @_;
  return $self->__getPhpUtil($self->__productPath('/admin/plib/api-cli/service_node.php'));
}

sub getEncryptUtil {
  my ($self) = @_;
  return [$self->__productPath('/admin/sbin/backup_encrypt')];
}

sub getBackupRestoreHelperUtil {
  my ($self) = @_;
  return [$self->__getSwEnginePath(), $self->__productPath('/admin/sbin/backup_restore_helper')];
}


sub getModSecurityCtlUtil {
  my ($self) = @_;
  return AgentConfig::get('PRODUCT_ROOT_D') . '/admin/sbin/modsecurity_ctl';
}

sub getDatabaseRegistrarUtil {
  my ($self) = @_;
  return $self->__getPhpUtil($self->__productPath('/admin/plib/api-cli/database-registrar.php'));
}

sub getMailmngServerUtil {
  my ($self) = @_;
  return AgentConfig::get('PRODUCT_ROOT_D') . '/admin/sbin/mailmng-server';
}

sub getMailmngMailnameUtil {
  my ($self) = @_;

  return AgentConfig::get('PRODUCT_ROOT_D') . "/admin/sbin/mailmng-mailname";
}

sub getBackupTmpDir {  
  my ($self) = @_;
  if (exists $self->{config}->{'DUMP_TMP_D'} && -d $self->{config}->{'DUMP_TMP_D'}) {
    return $self->{config}->{'DUMP_TMP_D'};
  }
  return $self->get('PRODUCT_ROOT_D') . "/tmp/";
}

sub getMaxDbConnectionsRetries {
  my ($self) = @_;
  return (exists $self->{config}->{'DB_MAX_CONN_RETRIES'}) ? $self->{config}->{'DB_MAX_CONN_RETRIES'} : 10;
}

sub getPleskMailnamesDir {
  my ($self, $domainName, $mailName) = @_;
  
  $domainName = lc($domainName);
  if (defined $mailName) {
    $mailName = lc($mailName);
  }
  
  my $baseDir = AgentConfig::get('PLESK_MAILNAMES_D');
  $baseDir .= "/" . $domainName . "/";
  $baseDir .= $mailName . "/" if defined $mailName;
  return $baseDir;
}

sub sendErrorReportBin {
  my ($self) = @_;
  return $self->get("PRODUCT_ROOT_D") . '/admin/bin/send-error-report';
}

sub isFeedbackAllowed {
  return 0 if -e '/var/parallels/feedback-disabled';
  return 1 if -e '/var/parallels/feedback-enabled';
  return 0;
}

sub lastDumpsFile {
  my ($self) = @_;
  return sprintf('%s/.last-dumps', $self->dumpDir());
}

sub getChrootShell {
  my ($self) = @_;
  return $self->get("PRODUCT_ROOT_D") . '/bin/chrootsh';
}

1;
