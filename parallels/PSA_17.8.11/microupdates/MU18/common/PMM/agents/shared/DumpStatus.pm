# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
package DumpStatus;

use strict;
use warnings;

use vars qw|@ISA|;

# -- Factory

my @reg_errors;
my @reg_warns;

sub createBackup {
  require BackupDumpStatus;
  return BackupDumpStatus->new();
}

sub createMigration {
  my ($outputFile) = @_;

  require MigrationDumpStatus;
  return MigrationDumpStatus->new($outputFile);
}

# -- DumpStatus interface

sub new {
  my $self = {};
  bless($self, shift);
  $self->_init(@_);
  return $self;
}

sub _init {
  my ($self) = @_;

  $self->{localDumpCreated} = 0;
  $self->{exportDumpCreated} = 0;
}

sub start {
  my ($self, $clients, $domains) = @_;

  die "Pure virtual function call";
}

sub startDomain {
  my ($self, $domain) = @_;

  die "Pure virtual function call";
}

sub startClient {
  my ($self, $client) = @_;

  die "Pure virtual function call";
}

sub finish {
  my ($self) = @_;

  die "Pure virtual function call";
}

sub setLocalDumpCreated {
  my ($self) = @_;

  $self->{localDumpCreated} = 1;
}

sub isLocalDumpCreated {
  my ($self) = @_;

  return $self->{localDumpCreated};
}

sub setExportDumpCreated {
  my ($self) = @_;

  $self->{exportDumpCreated} = 1;
}

sub isExportDumpCreated {
  my ($self) = @_;

  return $self->{exportDumpCreated};
}

1;
# Local Variables:
# mode: cperl
# cperl-indent-level: 2
# indent-tabs-mode: nil
# tab-width: 4
# End: