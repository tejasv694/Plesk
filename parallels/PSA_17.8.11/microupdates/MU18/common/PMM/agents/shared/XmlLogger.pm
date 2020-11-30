# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
package XmlLogger;

use strict;
use warnings;

use XmlNode;

sub new {
  my $self = {};
  bless( $self, shift );
  $self->_init(@_);
  return $self;
}

sub _init {
  my ($self, $dumpStatus) = @_;
  $self->{severity} = 'success';
  $self->{dumpStatus} = $dumpStatus;
  $self->{object_stack} = [];
  $self->{root} = XmlNode->new('execution-result');
  push @{$self->{objects_stack}}, \$self->{root};
}

my %severity_levels = (
  'success' => 10,
  'info'    => 20,
  'warning' => 30,
  'error'   => 40
);
my %levels_severity = reverse %severity_levels;

sub _max_severity {
  my ($sev1, $sev2) = @_;
  my $nsev1 = 0 + $severity_levels{$sev1};
  my $nsev2 = 0 + $severity_levels{$sev2};
  return $levels_severity{($nsev1>$nsev2)?$nsev1:$nsev2};
}

sub _checkSeverity {
  my ($self, $severity ) = @_;
  return if ($self->{severity} eq 'error');
  $self->{severity} =_max_severity($severity, $self->{severity});
}

sub _linkNodes {
  my ( $self ) = @_;
  my $child;
  foreach my $ref ( reverse @{$self->{objects_stack}}) {
    if ($child) {
      last if ( $$child->getMetadata('link') );
      $$ref->addChild($$child);
      $$child->setMetadata('link',1);
    }
    $child = $ref;
  }
}

sub beginObject {
  my ( $self, $type, $name, $uuid ) = @_;
  my $objectNode = XmlNode->new('object');
  $objectNode->setAttribute('type',$type);
  $objectNode->setAttribute('name',$name);
  if ( defined $uuid ) {
    $objectNode->setAttribute('uuid', $uuid);
  }
  push @{$self->{objects_stack}}, \$objectNode;
}

sub endObject {
  my ( $self ) = @_;
  my $topObjectRef = @{$self->{objects_stack}}[-1];
  if( scalar($$topObjectRef->getChildren('message')) ) {
    $self->_linkNodes();
  }
  pop @{$self->{objects_stack}};
}

sub addMessage {
  my ( $self, $severity, $id, $description, $code, $objectUid ) = @_;

  if (!defined($code) || $code eq '' ) {
    $code = 'msgtext';
  }

  $self->_checkSeverity($severity);
  my $messageNode = $self->createMessageNode($severity, $id, $description, $code);

  if (defined($objectUid)) {
    $self->addMessageToSpecifiedNode($messageNode, $objectUid);
  } else {
    my $topObjectRef = @{$self->{objects_stack}}[-1];
    $$topObjectRef->addChild($messageNode,1);
  }
}

sub fillByExistingData {
  my ( $self, $content ) = @_;

  eval {
    require XML::Simple;
    1;
  } or do {
    return;
  };

  my $xml;
  my $xs = XML::Simple->new( ForceArray => 1, SuppressEmpty => '' );

  $xml = $xs->XMLin( $content, KeyAttr => [] );

  $self->fillObjectsStack( $xml );
}

sub fillObjectsStack {
  my ( $self, $xml ) = @_;

  $self->addObject( $xml->{'object'} );
}

sub addObject {
  my ( $self, $objectInfo ) = @_;

  foreach my $info ( @{$objectInfo} ) {
    $self->beginObject( $info->{'type'}, $info->{'name'} );
    foreach my $message ( @{$info->{'message'}} ) {
      $self->addMessage( $message->{'severity'}, $message->{'id'}, $message->{'description'}[0], $message->{'code'}, $info->{'uuid'} );
    }
    $self->addObject( $info->{'object'} );
    $self->endObject();
  }
}

sub serializeToFile {
  my ( $self, $filename, $prettyPrint, $omitXmlDeclaration ) = @_;

  my $content;

  if ( -e $filename and -s $filename ) {
    open LOG, "$filename" or return;
    binmode LOG;
    while ( <LOG> ) {
      chomp;
      $content .= $_;
    }
    close LOG;
    $self->fillByExistingData( $content );
    open LOG, "> $filename" or return;
    binmode LOG;
  } else {
    open LOG, "> $filename" or return;
    binmode LOG;
  }

  $self->{root}->setAttribute('log-location', $filename);
  my $res = $self->serialize(\*LOG, $prettyPrint, $omitXmlDeclaration);
  close LOG;
  return $res;
}

sub getSeverity {
  my ( $self ) = @_;
  return $self->{severity} eq 'warning' ? 'warnings' : $self->{severity};
}

sub serialize {
  my ( $self, $fh, $prettyPrint, $omitXmlDeclaration ) = @_;
  while (@{$self->{objects_stack}} >1) {
    $self->endObject();
  }
  $self->{root}->setAttribute('status', $self->getSeverity());
  $self->{root}->setAttribute('local-dump-created', $self->{dumpStatus}->isLocalDumpCreated() ? 'true' : 'false');
  $self->{root}->setAttribute('export-dump-created', $self->{dumpStatus}->isExportDumpCreated() ? 'true' : 'false');
  return $self->{root}->serialize($fh, $prettyPrint, $omitXmlDeclaration);
}

sub createMessageNode {
  my ($self, $severity, $id, $description, $code) = @_;

  my $messageNode = XmlNode->new('message');
  $messageNode->setAttribute('severity',$severity);
  $messageNode->setAttribute('id',$id);
  $messageNode->setAttribute('code',$code);

  my $descriptionNode = XmlNode->new('description');
  $descriptionNode->setText($description);
  $messageNode->addChild($descriptionNode);

  return $messageNode;
}

sub addMessageToSpecifiedNode {
  my ( $self, $messageNode, $uuid ) = @_;

  foreach my $node (@{$self->{objects_stack}}) {
    if ($$node->isAttributeExist('uuid') && $$node->getAttribute('uuid') eq $uuid) {
      $$node->addChild($messageNode);
    }
  }
}


1;
