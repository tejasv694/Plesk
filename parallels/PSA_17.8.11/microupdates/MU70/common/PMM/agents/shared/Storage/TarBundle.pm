# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
package Storage::TarBundle;

use strict;
use warnings;
use Storage::Bundle;
use AgentConfig;
use vars qw|@ISA $FreeBSD|;
use File::Temp qw/ tempfile tempdir /;
use HelpFuncs;

@ISA=qw|Storage::Bundle|;

$FreeBSD = isFreeBSD();

sub isFreeBSD {
  my $os = `uname -s`;
  chomp($os);
  return ($os eq 'FreeBSD');
}

sub _init {
  my ($self, %options) = @_;
  $self->SUPER::_init(%options);

  $self->{follow_symlinks} = 1 if defined $options{follow_symlinks};

  $self->{add_file} = [];

  if (exists $options{'includes-file'}) {
    $self->{include} = $options{'includes-file'};
    $self->_setTempFileOwner(undef, $self->{include});
  } elsif (exists $options{include} && @{$options{include}}) {
    my @include = ();
    if (HelpFuncs::compareVersions(AgentConfig::tarVersion(), [1,29]) < 0) {
      foreach my $file (@{$options{include}}) {
        if (substr($file, 0, 1) eq "-") {
          push @{$self->{add_file}}, $file;
        } else {
          push @include, $file;
        }
      }
    } else {
      @include = @{$options{include}};
    }
    if (@include) {
      $self->{include} = $self->fileListToFile(\@include);
      if (not defined $self->{include}) {
        return undef;
      }
    }
  }

  if (exists $options{exclude} && @{$options{exclude}}) {
    $self->{exclude} = $self->fileListToFile($options{exclude});
    if (not defined $self->{exclude}) {
        return undef;
    }
  }

  if (exists $options{no_recursion}) {
    $self->{no_recursion} = 1;
  }

  $self->{include_hidden_files} = 1 if defined $options{include_hidden_files};
  
  return -d $self->{srcdir};
}

sub fileListToFile {
  my ($self, $fileList) = @_;

  #FIXME: detect who is passing non-chomped filenames
  chomp @{$fileList};

  my @list;
  foreach my $file ( @{$fileList} ) {
    push @list, $file if $file ne '';
  }

  if ( @list ) {
    my ($fh, $fileName) = tempfile('pmm-tb-fltf-files-XXXXXX', TMPDIR => 1);
    $self->_setTempFileOwner($fh, $fileName);

    foreach my $file ( @list ) {
      print $fh "$file\n";
    }
    close $fh;
    return $fileName;
  }
  return;
}

sub commandLine {
  my ($self) = @_;

  # FIXME: check tar presence
  my $tar = AgentConfig::tarBin();

  # Explicit -f - here because FreeBSD requires it
  my $cmd = "$tar -f - -c";
  $cmd .= ' --verbatim-files-from' if HelpFuncs::compareVersions(AgentConfig::tarVersion(), [1,29]) >= 0;

  $cmd .= " -h" if exists $self->{follow_symlinks};

  # FIXME: --ignore-failed-read

  $cmd .= " --no-recursion" if exists $self->{no_recursion};

  if ( defined $self->{exclude} ) {
    $cmd .= " --anchored ";
    $cmd .= " -X '$self->{exclude}'";
  }

  if ( defined $self->{include} ) {
    $cmd .= " -T '$self->{include}'";
  }
  if ( @{$self->{add_file}} ) {
    foreach my $file ( @{$self->{add_file}} ) {
      $cmd .= " --add-file='$file'";
    }
  }
  if ( ( !defined $self->{include} ) && ( !(@{$self->{add_file}}) ) ) {
    # When using .(dot) tar doesn't omit hidden files (i.e.starting with dot)
    # All files in archive are preceeded with './'
    # Tar archive contains:
    #     ./
    #     ./file1
    #     ./.file2
    #     ./directory1
    #     ./.directory2
    #     ./directory1/file3
    #     ./directory1/.file4
    # As a side effect, tar does backup owner, ownergroup and perms of top-level directory
    if (exists $self->{include_hidden_files}) {
      $cmd .= " -- .";
    }
    # When using *(asterisk) tar does omit hidden files in top directory only
    # All files in archive arent' preceeded with ./
    #     file1
    #     directory1
    #     directory1/file3
    #     directory1/.file4
    else {
      # As mentioned above tar does not pack top-level hidden files, so we collect them manually and pass to tar as explicit file list
      my $tmpFile = $self->_dumpHiddenFilesToTempFile();
      if (defined $tmpFile) {
        $self->{hidden_files_list_file} = $tmpFile;
        $cmd .= " --files-from ".$tmpFile;
      }

      $cmd .= " -- *";
    }
  }

  return $cmd;
}

sub _setTempFileOwner {
  my ($self, $fh, $fileName) = @_;
  
  if (defined $self->{sysuser}) {
    chown 0 + getpwnam($self->{sysuser}), 0 + getgrnam("root"), (defined($fh) ? $fh : $fileName) or
      Logging::debug("Unable to chown file $fileName to " . $self->{sysuser} . ":root. ");
  }

  chmod 0600, $fileName;
}

sub _dumpHiddenFilesToTempFile {
  my ($self) = @_;

  my ($fh, $fileName) = tempfile('pmm-tb-dhfttf-files-XXXXXX', TMPDIR => 1);
  $self->_setTempFileOwner($fh, $fileName);
  close $fh;
  my $cmd = 'find . -maxdepth 1 -regex "\.\/\..*" -printf "%f\n" > '.$fileName;
  Logging::debug('Execute: ' .$cmd);
  if (system($cmd) == 0 && -e $fileName) {
    return $fileName;
  }
  return undef;
}

sub cleanup {
  my ($self) = @_;

  my $exit_code = $self->SUPER::cleanup();

  unlink($self->{include}) if exists $self->{include};
  unlink($self->{exclude}) if exists $self->{exclude};
  unlink($self->{hidden_files_list_file}) if exists $self->{hidden_files_list_file};

  if (1 == $exit_code) {
     # According to GNU tar specification (http://www.gnu.org/software/tar/manual/tar.html) exit code 1 means that some files were changed while being archived
     Logging::debug("Tar bundle. Ignore files changes during archive creation: replace exit code $exit_code to 0");
     $exit_code = 0;
  }

  return $exit_code;
}

sub filterStderr {
  my ($self, $stderr) = @_;

  if ($stderr =~ qr/^\/bin\/tar: (.+): Cannot (open|savedir): Permission denied$/m) {
    my @problemFiles;
    my @problems = split(/\n/ , $stderr);
    foreach my $problem (@problems) {
      if ($problem =~ qr/^\/bin\/tar: (.+): Cannot (open|savedir): Permission denied$/) {
        push @problemFiles, $self->{srcdir} . "/" . $1;
      }
    }
    return "For security reasons, backing up is performed on behalf of subscription's system user. This system user has no read access to:\n" . join("\n", @problemFiles) . "\nSo it was not backed up. All other data was backed up successfully. To fix this issue you may run the command 'plesk repair fs' or grant access read/write manually to the file or directory for system user \"" . $self->{sysuser} . "\" or \"apache\".";
  } else {
    return $stderr;
  }
}



1;