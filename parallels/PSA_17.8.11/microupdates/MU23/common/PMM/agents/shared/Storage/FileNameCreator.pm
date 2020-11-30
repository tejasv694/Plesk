#!/usr/bin/perl
# Copyright 1999-2017. Plesk International GmbH. All rights reserved.

package Storage::FileNameCreator;


use strict;
use warnings;

use Fcntl qw(O_RDWR O_CREAT LOCK_EX SEEK_SET);


sub new {
  my $self = {};
  bless( $self, shift );
  $self->_init(@_);
  return $self;
}


sub _init{
  my( $self ) = @_;
  $self->{dateprefix} = undef;
  $self->{incrementalCreationDate} = undef;
}

sub setIncrementalCreationDate{
  my ($self, $incrementalCreationDate) = @_;
  $self->{incrementalCreationDate} = $incrementalCreationDate;
}

sub getFileName{
  my ($self, $normalizedPath, $normalizedbackupfileName, $normalizedObjectId, $fileName ) = @_;
  my $ret = '';
  $ret .= "$normalizedPath/" if $normalizedPath;
  $ret .= "$normalizedbackupfileName\_" if $normalizedbackupfileName;
  $ret .= $fileName . '_' if $fileName;
  $ret .= $self->{incrementalCreationDate} . '_' if defined($self->{incrementalCreationDate});
  $ret .= $self->getCreationDate();
  return $ret;
}

sub getCreationDate {
  my ($self) = @_;

  if (!$self->{dateprefix}) {
    $self->{dateprefix} = $self->_createDatePrefix();
  }
  return $self->{dateprefix};
}

sub _createDatePrefix {
  my ($self) = @_;

  my $lastDumpsHandle = undef;
  if (!sysopen($lastDumpsHandle, AgentConfig::lastDumpsFile(), O_RDWR|O_CREAT)) {
    Logging::debug(sprintf("Unable to open the file '%s': %s. Generate default date perfix.", AgentConfig::lastDumpsFile(), $!));
    return $self->_createDefaultDatePrefix();
  }
  if (!flock($lastDumpsHandle, LOCK_EX)) {
    Logging::debug(sprintf("Unable to lock the file '%s': %s. Generate default date perfix.", AgentConfig::lastDumpsFile(), $!));
    return $self->_createDefaultDatePrefix();
  }

  my @lastDumps = ();
  while (my $lastDump = <$lastDumpsHandle>) {
    chomp($lastDump);
    if (!$lastDump) {
        next;
    }
    push(@lastDumps, $lastDump);
  }

  my $addition = 0;
  my $datePrefix = $self->_createDefaultDatePrefix($addition);
  while (grep(/^$datePrefix$/, @lastDumps)) {
    $datePrefix = $self->_createDefaultDatePrefix(++$addition);
  }
  push(@lastDumps, $datePrefix);

  seek($lastDumpsHandle, 0, SEEK_SET);
  truncate($lastDumpsHandle, 0);
  splice(@lastDumps, 0, -100);
  foreach my $lastDump (@lastDumps) {
    print $lastDumpsHandle "$lastDump\n";
  }
  close($lastDumpsHandle);

  return $datePrefix;
}

sub _createDefaultDatePrefix {
  my (undef, $addition) = @_;
  my (undef, $min, $hour, $mday, $mon, $year) =  localtime(time() + (defined($addition) ? 60 * $addition : 0));
  return sprintf('%0.2d%0.2d%0.2d%0.2d%0.2d', $year % 100, $mon + 1, $mday, $hour, $min);
}

sub normalize_long_string{
  my ($self, $str, $id) = @_;
  return $self->normalize_string( $str, $id, 47 );
}

sub normalize_short_string{
  my ($self, $str, $id) = @_;
  return $self->normalize_string( $str, $id, 25 );
}


sub replace_danger{
 my ($self, $str) = @_;
 #danger:  " ", ":", ">",   "<",   "|", "&",     "!"
 #replace: "_", "-", "-gt-","-lt-","_", "-and-", "_"

 if ( $str =~ /[\ :><\|&!\\\/]/s ) {
    $str =~ s/\ /_/sg;
    $str =~ s/:/-/sg;
    $str =~ s/>/-gt-/sg;
    $str =~ s/</-lt-/sg;
    $str =~ s/\|/_/sg;
    $str =~ s/&/-and-/sg;
    $str =~ s/!/_/sg;
    $str =~ s/\//_/sg;
    $str =~ s/\\/_/sg;
  }
  return $str;
}

sub normalize_string{
 my ($self, $str, $id, $max_length) = @_;

 my $res = $self->replace_danger($str);

 if( length($res) > $max_length ){
      $res = substr( $res, 0, $max_length - length($id) - 1 ) . "_" . $id;
 }
 elsif ($str ne $res or $res =~ /_?\d{10}$/) {
    my $newLen = length($res);
    $newLen = $max_length - length($id) - 1 if $newLen + length($id) + 1 > $max_length;
    $res = substr( $res, 0, $newLen ) . "_" . $id;
 }
 return $res;
}


sub normalize_id_string{
 my ($self, $str, $id, $max_length) = @_;

 my $res = $self->replace_danger( $str );

 if( $max_length and length($res) > $max_length ){
     $res = substr( $res, 0, $max_length );
 }
 return $res;
}






1;