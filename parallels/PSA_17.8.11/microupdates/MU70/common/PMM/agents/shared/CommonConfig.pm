# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
package CommonConfig;

use strict;
use warnings;


sub new {
  my $self = {};
  bless($self, shift);
  return $self if $self->_init(@_);
}

sub _init {
  my ($self) = @_;
  my @envs = split /:/, $ENV{'PATH'};
  $self->{paths} = \@envs;
  $self->{resolved} = {};
  return 1;
}

sub __addPaths {
  my ($self, @paths) = @_;
  push @{$self->{paths}}, @paths;
}

sub __resolve {
  my ($self, $file) = @_;
  for my $path (@{$self->{paths}}) {
    return "$path/$file" if -x "$path/$file";
  }
}

sub __findInPath {
  my ($self, $file) = @_;

  if (!exists $self->{resolved}->{$file}) {
    $self->{resolved}->{$file} = $self->__resolve($file);
  }

  return $self->{resolved}->{$file};
}

sub shellBin {
  my ($self) = @_;
  my $sh = `echo \$SHELL`;
  $sh =~ s/\s+$//;
  return $sh if -x "$sh";

  my $shCmd = `echo \$0`;
  $shCmd =~ s/\s+$//;
  if( defined $shCmd) {
    $sh = $self->__findInPath($shCmd);
    return $sh if -x "$sh";
  }

  return '/bin/bash' if -x '/bin/bash';
}

sub pythonBin { return shift->__findInPath("python"); }
sub psqlBin { return shift->__findInPath("psql"); }

sub getPostgresqlVersion {
  my $psql = shift->psqlBin();
  my @out  = `$psql --version | awk '{print \$3}'`;
  chomp $out[0];
  if ( $out[0] =~ /(\d+\.\d+\.\d+).*/ ) {
    return $1;
  }
}

sub pgdumpBin { return shift->__findInPath("pg_dump"); }
sub mysqlBin { return shift->__findInPath("mysql"); }
sub mysqldumpBin { return shift->__findInPath("mysqldump"); }
sub gzipBin { return shift->__findInPath("gzip"); }
sub pigzBin { return shift->__findInPath("pigz"); }
sub splitBin { return shift->__findInPath("split"); }
sub pwdBin { return shift->__findInPath("pwd"); }
sub localeBin { return shift->__findInPath("locale"); }
sub iconvBin { return shift->__findInPath("iconv"); }
sub rpmBin { return shift->__findInPath("rpm"); }
sub catBin { return shift->__findInPath("cat"); }
sub grepBin { return shift->__findInPath("grep"); }
sub crontabBin { return shift->__findInPath("crontab"); }
sub findBin { return shift->__findInPath("find"); }
sub apache2ctlBin { return shift->__findInPath("apache2ctl"); }
sub apachectlBin { return shift->__findInPath("apachectl"); }
sub findApacheHttpdBin { return shift->__findInPath("httpd"); }
sub idnconvBin { return shift->__findInPath("idnconv"); }

sub cwd {
  my $pwdBin = shift->pwdBin();
  my $cwd = `$pwdBin`;
  chomp $cwd;
  return $cwd;
}

sub md5Bin {
  my $self = shift;

  my $md5sum = $self->__findInPath("md5sum");
  return $md5sum if $md5sum;

  return $self->__findInPath("md5");
}

sub __tarVersion {
  my ($self, $bin) = @_;

  my $version = `$bin --version`;
  if ($version =~ /tar \(GNU tar\) (\d+)\.(\d+)/) {
    return [$1, $2];
  }
}

sub __detectTar {
  my ($self) = @_;

  my $tar = $self->__findInPath("tar");
  return [$tar, $self->__tarVersion($tar)] if $tar ne '';
}

sub __cacheDetectTar {
  my ($self) = @_;
  unless (defined $self->{tar_resolved}) {
	$self->{tar_resolved} = $self->__detectTar();
  }
  return $self->{tar_resolved};
}

sub tarBin {
  my ($self) = @_;

  return (($self->__cacheDetectTar())->[0]);
}

sub tarVersion {
  my ($self) = @_;

  return (($self->__cacheDetectTar())->[1]);
}

sub getSharedDir {
  my ($self) = @_;
  return $self->cwd();
}

sub getRLimitFsize {
  my ($self) = @_;
  my $shell = $self->shellBin();
  my $limit = `$shell -c ulimit -f`;
  chomp($limit);
  if ($limit =~ /(\d+)/){
    return $1;
  }
  return;
}

1;
