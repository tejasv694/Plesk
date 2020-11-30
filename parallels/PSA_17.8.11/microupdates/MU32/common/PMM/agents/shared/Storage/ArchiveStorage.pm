# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
package Storage::ArchiveStorage;

use strict;
use warnings;

use File::Basename qw(basename);
use IPC::Run;
use HelpFuncs;
use Logging;
use Storage::Storage;

use vars qw|@ISA|;
@ISA = qw|Storage::Storage|;

sub _init {
  my ($self, %options) = @_;

  $self->SUPER::_init(%options);
  $self->{exportDir} = $options{'export-dir'};
  $self->{ftp} = $options{'ftp'};
  $self->{sessionPath} = $options{'session-path'};
  $self->{verbose} = $options{'verbose'};
  $self->{cmd} = undef;
  $self->{cmdInput} = '';
  if ($self->{sessionPath}) {
    $self->{cmdOutputPath} = $self->{sessionPath} . '/export-dump-result';
  } else {
    $self->{cmdOutputPath} = HelpFuncs::mktemp(AgentConfig::getBackupTmpDir() . '/pmm-export-dump-result-XXXXXXXX');
  }
  $self->{keepLocalBackup} = $options{'keep-local-backup'};
  $self->{keepLocalBackupIfExportFailed} = $options{'keep-local-backup-if-export-failed'};

  Logging::debug("-" x 60);
  Logging::debug("ARCHIVE storage initialized.");
  Logging::debug("Base directory: $self->{output_dir}");
  Logging::debug("Space reserved: $self->{space_reserved}");
  Logging::debug("Gzip bundles: " . ($self->{gzip_bundle} ? "yes" : "no"));
  Logging::debug("Bundle split size: " . ($self->{split_size} || "do not split"));
  Logging::debug("-" x 60);
  $self->reserveSpace();
}

sub DESTROY {
  my $self = shift;
  unlink($self->{cmdOutputPath});
  $self->SUPER::DESTROY();
}

sub finishExport {
  my $self = shift;

  my $mainDumpFile = $self->getMainDumpXmlRelativePath();
  if ($mainDumpFile) {
    my $mainDumpFileName = basename($mainDumpFile);
    Logging::debug("Add file $mainDumpFileName to archive");
    $self->{cmdInput} .= "$mainDumpFileName\n";
    eval {
      IPC::Run::pump($self->_getCmd());
    };
    if ($@) {
      Logging::debug($@);
    }
  }
  eval {
    $self->_finishCmd();
    if (!$self->{keepLocalBackup} && $self->{keepLocalBackupIfExportFailed}) {
      $self->_deleteLocalBackup();
    }
  };
  if ($@) {
    if ($self->{keepLocalBackup} || $self->{keepLocalBackupIfExportFailed}) {
      Logging::warning($@, undef, 'step' => 'upload-to-ftp');
      $self->_deleteDumpFiles();
    } else {
      die($@);
    }
  }
}

sub writeDiscovered{
  my ( $self, $dumpPath, $dumpXmlName, $dumpSize, $ownerGuid, $ownerType, $objectGuid, $objectId ) = @_;

  $self->_writeDiscovered($dumpPath, $dumpXmlName, $dumpSize, $ownerGuid, $ownerType, $objectGuid, $objectId);

  Logging::debug("Content $self->{exportDir}");
  my ($content) = $self->{exportDir} =~ m/^ext:\/\/([^\/]+)\/.*/;
  return unless defined $content;

  $content .= "\nlocal";
  Logging::debug("Content $content");

  my $idx = rindex( $dumpXmlName, '.xml' );
  $dumpXmlName = substr( $dumpXmlName, 0, $idx ) if $idx>0;
  my $destDir = $self->getFullOutputPath();
  $destDir .= "/$dumpPath" if $dumpPath;
  $destDir .= "/.discovered/$dumpXmlName";
  Logging::debug("Writing $destDir/storages");
  open(my $handle, '>', "$destDir/storages");
  print $handle $content;
  close($handle);

  my @files = (['storages', length($content)]);
  $self->regIdFiles( $destDir, $destDir, length($content), \@files );
  return [$destDir, \@files];
}

sub moveFileToDiscovered {
  my $self = shift;

  $self->_moveFileToDiscovered(@_);
}

sub addTar {
  my $self = shift;

  my ($ret) = @{$self->_addTar(@_)};
  return $ret;
}

sub onVolumeCreate() {
  my ($self, $destDir, $fileName) = @_;

  my $file;
  eval {
    my $dumpStorage = $self->{mainDumpRootPath} ? sprintf('%s/%s', $self->{output_dir}, $self->{mainDumpRootPath}) : $self->{output_dir};
    my $destDirRelativePath = substr($destDir, length($dumpStorage));
    $destDirRelativePath =~ s/^\/+//;
    $file = $destDirRelativePath ? sprintf('%s/%s', $destDirRelativePath, $fileName) : $fileName;
    Logging::debug("Add file $file to archive");
    $self->{cmdInput} .= "$file\n";
    IPC::Run::pump($self->_getCmd());
  };
  if ($@) {
    Logging::debug($@);
    unless ($self->{keepLocalBackup} || $self->{keepLocalBackupIfExportFailed}) {
      Logging::error(sprintf('Unable to upload the archive %s to the external storage: %s', $file, $self->_getCmdOutput()), undef, 'step' => 'upload-to-ftp');
      if ($self->{sessionPath}) {
        Logging::serializeXmlLog("$self->{sessionPath}/migration.result");
      }
      exit(0);
    }
  }
}

sub CleanupFiles()
{
  my $self = shift;

  eval {
    $self->_finishCmd();
  };
  if ($@) {
    Logging::debug($@);
  }
  $self->SUPER::CleanupFiles();
  $self->_deleteDumpFiles();
}

sub _getCmd {
  my $self = shift;

  if (!$self->{cmd}) {
    my $command = [AgentConfig::pmmRasBin(), '--export-dump-as-file'];
    if ($self->{ftp}) {
      $ENV{'DUMP_STORAGE_PASSWD'} = $self->{ftp}->{password};
      push(@{$command}, '--dump-file-specification', sprintf('%s/%s', HelpFuncs::getStorageUrlFromFtpSettings($self->{ftp}), $self->{exportFileName}));
      push(@{$command}, '--use-ftp-passive-mode') if exists($self->{ftp}->{'passive'});
    } else {
      push(@{$command}, '--dump-file-specification', sprintf('%s/%s', $self->{exportDir}, $self->{exportFileName}));
    }
    push(@{$command}, '--dump-storage', sprintf('%s/%s', $self->{output_dir}, $self->{mainDumpRootPath}));
    push(@{$command}, '--remove-dump') unless $self->{keepLocalBackup} || $self->{keepLocalBackupIfExportFailed};
    push(@{$command}, '--split-size', $self->{split_size}) if $self->{split_size};
    push(@{$command}, '--session-path', $self->{sessionPath}) if $self->{sessionPath};
    push(@{$command}, '--debug', '--verbose') if $self->{verbose};
    Logging::debug('Start: ' . join(' ', @{$command}));
    $self->{cmd} = IPC::Run::start($command, \$self->{cmdInput}, "1>$self->{cmdOutputPath}", '2>/dev/null');
  }
  return $self->{cmd};
}

sub _finishCmd {
  my $self = shift;

  if ($self->{cmd}) {
    my @createdVolumes = ();
    eval {
      $self->{cmd}->finish();
    };
    my $finishError = $@;
    if ($finishError) {
      Logging::debug($finishError);
    }

    my $exitCode = 1;
    eval {
      $exitCode = $self->{cmd}->result();
    };
    if ($@) {
      Logging::debug($@);
    }

    $self->{cmd} = undef;
    $self->{cmdInput} = '';
    my $cmdOutput = $self->_getCmdOutput();
    unlink($self->{cmdOutputPath});
    if ($finishError || $exitCode) {
      die(sprintf('Unable to upload the backup to the external storage: %s', $cmdOutput));
    }

    @createdVolumes = split("\n", $cmdOutput) if $cmdOutput;
    $self->{createdVolumes} = \@createdVolumes;
  }
}

sub _getCmdOutput {
  my $self = shift;
  my $cmdOutput = '';
  if (open(RESULT, $self->{cmdOutputPath})) {
    while (<RESULT>) {
      $cmdOutput .=  $_;
    }
    close RESULT;
  }
  return $cmdOutput;
}

sub _deleteLocalBackup {
  my $self = shift;

  Logging::debug("Delete a local backup");
  eval {
    my $command = [AgentConfig::pmmRasBin(), '--delete-dump'];
    push(@{$command}, '--dump-specification', $self->getMainDumpXmlRelativePath());
    push(@{$command}, '--session-path', $self->{sessionPath}) if $self->{sessionPath};
    push(@{$command}, '--debug', '--verbose') if $self->{verbose};

    Logging::debug('Exec: '.join(' ', @{$command}));
    my $stdout;
    IPC::Run::run($command, '1>', \$stdout) or die($stdout);
  };
  if ($@) {
    Logging::debug('Unable to delete a local backup: ' . HelpFuncs::trim($@));
  }
}

sub _deleteDumpFiles {
  my $self = shift;

  Logging::debug("Delete dump files");
  eval {
    my $command = [AgentConfig::pmmRasBin(), '--delete-dump-files'];
    if ($self->{ftp}) {
      $ENV{'DUMP_STORAGE_PASSWD'} = $self->{ftp}->{password};
      push(@{$command}, '--dump-storage', HelpFuncs::getStorageUrlFromFtpSettings($self->{ftp}));
      push(@{$command}, '--dump-file-specification', $self->{exportFileName});
      push(@{$command}, '--use-ftp-passive-mode') if exists($self->{ftp}->{'passive'});
    } else {
      push(@{$command}, '--dump-storage', $self->{exportDir});
      push(@{$command}, '--dump-file-specification', $self->{exportFileName});
    }
    push(@{$command}, '--session-path', $self->{sessionPath}) if $self->{sessionPath};
    push(@{$command}, '--debug', '--verbose') if $self->{verbose};

    Logging::debug('Exec: '.join(' ', @{$command}));
    my $stdout;
    IPC::Run::run($command, '1>', \$stdout) or die($stdout);
  };
  if ($@) {
    Logging::debug('Unable to delete dump files: ' . HelpFuncs::trim($@));
  }
}

1;
