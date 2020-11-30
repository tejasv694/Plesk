# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
package MigrationDumpStatus;

use strict;
use warnings;

use DumpStatus;
use Logging;
use XmlNode;

use vars qw|@ISA|;

@ISA = qw|DumpStatus|;

sub _init {
  my ($self, $filename, @args) = @_;
  $self->SUPER::_init(@args);
  $self->{filename} = $filename;

  Logging::debug("-" x 60);
  Logging::debug("Migration status reporting initialized.");
  Logging::debug("Status file: $filename");
  Logging::debug("-" x 60);
}

sub start {
  my ($self, $clients, $domains) = @_;
  $self->{current_client} = undef;
  $self->{current_domain} = undef;
  $self->{clients_count} = $clients;
  $self->{domains_count} = $domains;
  $self->{clients_done} = 0;
  $self->{domains_done} = 0;

  $self->_publish();
}

sub startDomain {
  my ($self, $domain) = @_;
  $self->{current_domain} = $domain;
  $self->_publish();
}

sub endDomain {
  my ($self, $domain) = @_;
  $self->{current_domain} = undef;
  ++$self->{domains_done};
  $self->_publish();
}

sub startClient {
  my ($self, $client) = @_;
  $self->{current_client} = $client;
  $self->_publish();
}

sub endClient {
  my ($self, $client) = @_;
  $self->{current_client} = undef;
  ++$self->{clients_done};
  $self->_publish();
}

sub finishObjects {
  my ($self) = @_;

  $self->{current_client} = undef;
  $self->{current_domain} = undef;
  $self->_publish();
}

sub finish {
}

sub _publish {
  my ($self) = @_;
  my $tmpfile = $self->{filename} . ".tmp";

  open STATUS, ">$tmpfile";
  binmode STATUS;

  my $xmlRoot = XmlNode->new('agent-dump-status' );
  $xmlRoot->setAttribute( 'total-domains', $self->{domains_count} );
  $xmlRoot->setAttribute( 'total-accounts', $self->{clients_count} );
  $xmlRoot->setAttribute( 'completed-domains', $self->{domains_done}<=$self->{domains_count} ? $self->{domains_done} : $self->{domains_count} );
  $xmlRoot->setAttribute( 'completed-accounts', $self->{clients_done}<=$self->{clients_count} ? $self->{clients_done} : $self->{clients_count} );
  if ($self->{current_domain}) {
    my $objectNode = XmlNode->new('current-object', 'content' => $self->{current_domain});
    $objectNode->setAttribute('type', 'domain');
    $xmlRoot->addChild($objectNode);
  } elsif ($self->{current_client}) {
    my $objectNode = XmlNode->new('current-object', 'content' => $self->{current_client});
    $objectNode->setAttribute('type', 'account');
    $xmlRoot->addChild($objectNode);
  }
  $xmlRoot->serialize(\*STATUS);
  close(STATUS);

  rename($tmpfile, $self->{filename});
}


1;
# Local Variables:
# mode: cperl
# cperl-indent-level: 2
# indent-tabs-mode: nil
# tab-width: 4
# End: