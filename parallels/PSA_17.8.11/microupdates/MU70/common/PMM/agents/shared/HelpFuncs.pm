#!/usr/bin/perl
# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
package HelpFuncs;
# Port to Unix

use strict;
use warnings;
use List::Util qw(max);
use Logging;
use EncodeBase64;
use Socket;
use IPC::Run;

sub negation{
  my ($val) = @_;
  if ($val eq 'true') {
          return 'false';
  }
  if ($val eq 'false') {
          return 'true'; 
  }
}

sub checkValue{
  my ($val, $defaultVal, $checkVal) = @_;
  if( defined $val ) {
          if ( defined $checkVal ) {
                  return $checkVal if $val eq $checkVal;
          }
          else {
                  return $val;
          }
  } 
  return $defaultVal;
}

sub dieWithError{
  my( $msg, $errCode ) = @_;
  $errCode = -1 if not $errCode;
  log_error( "The program faield with error $errCode", $msg );
  my $idx = 1;
  my $package = 1;
  my $tab = ' ';
  my $callstack = '';
  while( $package and $idx<15 ) {
      my  ($package, $filename, $line, $subroutine, $hasargs, $wantarray, $evaltext, $is_require, $hints, $bitmask) = caller($idx);
      if( $package ) {
          $tab = $tab . ' ';
          $callstack .= "\n$tab Stack[$idx]. Sub:$subroutine [Package:$package][Filename:$filename]Line:$line";
      }
     $idx = $idx + 1;
    }
  die "\nDie with error:$msg\nCall stack:\n$callstack";
}

# Returns string, suitable for using in 'IN ($list)' sql clause
sub getSqlList
{
  return join(',', map {"'$_'"} sort {$a <=> $b} @_);
}

sub blockToNum {
  my ( $mask ) = @_;
  my $longMask = unpack( "N", pack( "C4", split( /\./, $mask ) ) );
  my $block;
  for ( $block = 0 ; $block < 32 ; $block = $block + 1 ) {
    my $tmp = 2**( 32 - $block - 1 );
    last if !( $longMask & $tmp );
  }
  return $block;
}

#
# url decode (certificates in Plesk DB encoded by the url encoding)
#
sub urlDecode {
    my $url = $_[0];
    $url =~ tr/+/ /;
    $url =~ s/%([a-fA-F0-9]{2,2})/chr(hex($1))/eg;
    return $url;
}

sub makeMIMEBase64 {
  my ($useModule);
  if (eval "require MIME::Base64") {
    $useModule = 1;
  } else {
    $useModule = 0;
  }

  my $this = {
    'ENCODE' => sub {
      my ($text) = @_;
      if ($useModule) {
        my $encoded = MIME::Base64::encode($text);
        chomp $encoded;
        return $encoded;
      }
      else {
        return EncodeBase64::encode($text);
      }
    }
  };
  return $this;
}

sub randomPasswd {
  my $len = shift;
  if ( $len <= 0 ) {
    $len = 8;
  }
  my $symbols = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  my $seq_length = length($symbols);
  my $passwd = '';

  for (my $i = 0; $i <= $len; ++$i) {
    $passwd .= substr($symbols, rand($seq_length), 1);
  }

  return $passwd;
}

sub getUniqueDir{
  my( $path, $prefix ) = @_;
  my $cnt = 1;
  while( -e "$path/$prefix\[$cnt\]" ){ $cnt += 1; }
  return "$path/$prefix\[$cnt\]";
}

sub deleteFolder{
  my( $path ) = @_;
  opendir( DH, $path );
  my $dir;
  while( ( $dir = readdir( DH ) ) ){
    if( not $dir eq '.' and not $dir eq '..' ){
      if( -d "$path/$dir" ) {
        deleteFolder( "$path/$dir" );
        if( not rmdir "$path/$dir" ) { 
          Logging::error("Cannot delete directory $path/$dir");
        }
      }
      else{ unlink "$path/$dir" or Logging::error("Cannot delete file '$path/$dir'"); }
    }
  }
  closedir( DH );
  if( not rmdir $path ) {
    Logging::error("Cannot delete directory $path"); 
  }
}

sub make_path {
  my $arg = {};
  if (@_ and (ref($_[-1]) =~ /HASH/) ) {
    $arg = pop @_;
  }
  if (exists $arg->{owner} and $arg->{owner} =~ /\D/) {
    my $uid = (getpwnam $arg->{owner})[2];
    if (defined $uid) {
      $arg->{owner} = $uid;
    }
    else {
      Logging::error("Unable to map $arg->{owner} to a uid, ownership not changed");
      delete $arg->{owner};
    }
  }
  if (exists $arg->{group} and $arg->{group} =~ /\D/) {
    my $gid = (getgrnam $arg->{group})[2];
    if (defined $gid) {
      $arg->{group} = $gid;
    }
    else {
      Logging::error("Unable to map $arg->{group} to a gid, group ownership not changed");
      delete $arg->{group};
    }
  }
  if (exists $arg->{owner} and not exists $arg->{group}) {
    $arg->{group} = -1; # chown will leave group unchanged
  }
  if (exists $arg->{group} and not exists $arg->{owner}) {
    $arg->{owner} = -1; # chown will leave owner unchanged
  }
  push @_, $arg;
  _make_path(@_);
}

sub _make_path {
  my $arg = pop @_;
  my @paths = @_;
  foreach my $path (@paths) {
    next unless defined($path) and length($path);
    next if -d $path;
    my @dirs = split ('/', $path);
    pop @dirs;
    my $parent = join('/',@dirs);
    unless (-d $parent or $path eq $parent) {
      _make_path( $parent, $arg);
    }
    if (mkdir $path, (exists $arg->{mode})? $arg->{mode}: 0755) {
      if( exists $arg->{owner} and exists $arg->{group}) {
        if (!chown 0+$arg->{owner}, 0+$arg->{group}, $path) {
          Logging::error("Cannot chown on '$path' to '$arg->{owner}:$arg->{group}'");
        }
      }
      if (exists $arg->{mode}) {
        if (!chmod $arg->{mode}, $path) {
          Logging::error("Cannot chmod on '$path' to $arg->{mode}");
        }
      }
    }
    else {
      Logging::error("Cannot mkdir '$path'");
    }
  }
}

# ($total, $avail, $mount) = getMountSpace('path')
sub getMountSpace {
  my ($mount) = @_;

  my $cmd = ['df', '-P', '-k', $mount];
  Logging::debug("Execute: @{$cmd}");
  my ($stdout, $stderr);
  IPC::Run::run($cmd, '1>', \$stdout, '2>', \$stderr) or die($stderr);

  if ( $stdout =~ /^(\S+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+\%)\s+(\S+)$/gm ) {
    return (1024 * $2, 1024 * $4, $6);
  }
  return;
}

sub epoch2CrDate {
  my $epoch = shift;
  unless ( $epoch ) {
    return '1970-01-01';
  }
  my ($mday, $mon, $year) = (localtime($epoch)) [3 .. 5];
  return sprintf("%04d-%02d-%02d", $year+1900, $mon+1, $mday);
}

sub getTime {
  my ($sec, $minute, $hour, $day, $month, $year) =  localtime(time);
  $year+=1900;
  $sec = "0".$sec if length($sec) < 2;
  $minute = "0".$minute if length($minute) < 2;
  $hour = "0".$hour if length($hour) < 2;
  $day = "0".$day if length($day) < 2;
  $month++;
  $month = "0".$month if length($month) < 2;
  return "$year.$month.$day $hour:$minute:$sec";
}

sub getRelativePath {
  my ($subpath, $path) = @_;
  if ( !$subpath || !$path) {
    return;
  }
  $subpath =~ /^($path)\/*(.*)/;
  if ( defined $2 ) {
    return $2;
  }
  return;
}

my @CHARS = (qw/ A B C D E F G H I J K L M N O P Q R S T U V W X Y Z
                 a b c d e f g h i j k l m n o p q r s t u v w x y z
                 0 1 2 3 4 5 6 7 8 9 _
             /);
sub mktemp {
  my $path = shift;
  $path =~ s/X(?=X*\z)/$CHARS[ int( rand( $#CHARS ) ) ]/ge;
  return $path;
}

my $lastId = 0;

sub generateProcessId {
  return $$ . "-" . $lastId++;
}

# Returns the elements that present in first array, but not in the second.
# Arrays must be sorted.
# Linear complexity.

sub arrayDifference {
  my ( $a1, $a2 ) = @_;
  my @ret;

  my $i1 = 0;
  my $i2 = 0;
  while ( $i1 < scalar(@$a1) && $i2 < scalar(@$a2) ) {
    if ( $a1->[$i1] eq $a2->[$i2] ) { $i1++; $i2++; next; }
    if ( $a1->[$i1] lt $a2->[$i2] ) { push @ret, $a1->[$i1]; $i1++; next; }
    if ( $a1->[$i1] gt $a2->[$i2] ) { $i2++; next; }
  }

  while ( $i1 < scalar(@$a1) ) {
    push @ret, $a1->[$i1];
    $i1++;
  }
  return @ret;
}

# Returns the elements that present in both arrays
# Arrays must be storted
# Linear complexity

sub arrayIntersection {
  my ( $a1, $a2 ) = @_;
  my @ret;

  my $i1 = 0;
  my $i2 = 0;
  while ( $i1 < scalar(@$a1) && $i2 < scalar(@$a2) ) {
    if ( $a1->[$i1] eq $a2->[$i2] ) {
      push @ret, $a1->[$i1];
      $i1++;
      $i2++;
      next;
    }
    if ( $a1->[$i1] lt $a2->[$i2] ) { $i1++; next; }
    if ( $a1->[$i1] gt $a2->[$i2] ) { $i2++; next; }
  }

  return @ret;
}

sub inArray {
  my ($items, $value) = @_;
  
  return 0 unless ref($items) =~ /ARRAY/;
  
  foreach my $item(@{$items}) {
    return 1 if $item eq $value;
  }
  return 0;
}

sub processArray (&@) {
  my $code = shift;
  my $result;
  if( @_ ) {
    foreach my $value ( @_ ) {
      $result = $code->($result, $value);
    }
  }
  return $result;
}

sub loadArrayFromFile {
  my ($path) = @_;
  
  my $fp;
  unless (open $fp, $path) {
    return;
  }
  my @arr = ();
  while (my $line = <$fp>){
    chomp $line;
    push(@arr, $line);
  }
  close $fp;
  return @arr;
}

sub min2 { $_[0] <= $_[1] ? $_[0] : $_[1]; }

sub max2 { $_[0] <= $_[1] ? $_[1] : $_[0]; }

sub getArraySpecialValue {
  my $code = shift;
  return processArray {(defined $_[0])? ( (defined $_[1])? $code->($_[0], $_[1]) : $_[0] ) : $_[1]}
  grep{ defined } @_;
}

sub Min {
  return getArraySpecialValue(\&min2,@_);
}

sub Max {
  return getArraySpecialValue(\&max2,@_);
}

sub hash2XmlNode {
  my $hashPtr = shift;
  my $doNotEscape = shift;
  return unless ( ref($hashPtr) =~ /HASH/ );

  my %hash = %{$hashPtr};

  my $name = $hash{'_name'};
  return unless defined $name;

  my $xmlNode = XmlNode->new( $name );
  if ( defined ( $hash{'_value'} ) and ( $hash{'_value'} ne '' ) ) {
    if ($doNotEscape) {
      $xmlNode->setTextAsIs( $hash{'_value'} );
    } else {
      $xmlNode->setText( $hash{'_value'} );
    }
  }

  if ( defined ( $hash{'_attrs'} ) and ( ref ( $hash{'_attrs'} ) =~ /HASH/ ) ) {
    my %attrs = %{$hash{'_attrs'}};
    foreach my $attrName ( keys %attrs ) {
      $xmlNode->setAttribute( $attrName, $attrs{$attrName}, undef, $doNotEscape ) if defined $attrs{$attrName};
    }
  }

  if ( defined ( $hash{'_children'} ) and ( ref ( $hash{'_children'} ) =~ /ARRAY/ ) ) {
    my @children = @{$hash{'_children'}};
    foreach my $child ( @children ) {
      next unless ( ref( $child ) =~ /HASH/ );
      my $childXmlNode = hash2XmlNode($child, $doNotEscape);
      $xmlNode->addChild($childXmlNode) if defined $childXmlNode;
    }
  }

  return $xmlNode;
}

sub convertPathToUnixFormat{
  my ( $path ) = @_;
  if (defined $path) {
    $path =~ s/\\/\//g;
    $path =~ s/\/{2,}/\//g;
  }
  return $path;
}

sub convertToTrueFalseString {
  my ($str) = @_;
  return ($str && ($str eq '1' || $str eq 'true')) ? 'true' : 'false';
}


my %ipCache;   # Local IP addresses list
my %hostCache; # host -> IP address
sub isRemoteHost {
  my ($host) = @_;
  return 0 if ($host eq 'localhost' || $host eq '127.0.0.1');
  
  if (!exists $hostCache{$host}) {
    $hostCache{$host} = inet_ntoa(scalar(gethostbyname($host)) || scalar(gethostbyname('localhost')));
  }
  if (!%ipCache) {
    my @ips = (`ifconfig -a` =~ /inet addr:(\S+)/g);
    @ipCache{@ips} = ();
  }
  
  return (!exists $ipCache{$hostCache{$host}}) ? 1 : 0;
}

sub _isPseudoFs {
  my ($type) = @_;

  my %pseudofs = (
                  'autofs' => 1,
                  'binfmt_misc' => 1,
                  'cd9660' => 1,
                  'devfs' => 1,
                  'devpts' => 1,
                  'fdescfs' => 1,
                  'iso9660' => 1,
                  'linprocfs' => 1,
                  'proc' => 1,
                  'procfs' => 1,
                  'romfs' => 1,
                  'sysfs' => 1,
                  'tmpfs' => 1,
                  'usbdevfs' => 1,
                  'usbfs' => 1,
                  'rpc_pipefs' => 1,
                  'fdesc' => 1,
                  'none' => 1
                  );

  return 1 if defined($pseudofs{$type});
  # MacOS pseudofs, such as <volfs>
  return 1 if $type =~ /^<.*>$/;
}

sub calculateFreeSpace {
  my ($path) = @_;

  my %mounts;

  Logging::debug("Execute: uname -s");
  my $osname = `uname -s`;
  chomp $osname;

  if ($osname eq 'FreeBSD') {
    Logging::debug("Execute: mount -p");
    foreach my $mountinfo (`mount -p`) {
      chomp $mountinfo;
      my ($device, $mountpoint, $type, $options) = split /\s+/, $mountinfo;
      my $mode = 'rw';
      $mode = 'ro' if ($options =~ /(^|,)ro(,|$)/);

      unless (_isPseudoFs($type)) {
            $mounts{$mountpoint} = ();
            $mounts{$mountpoint}->{'device'} = $device;
            $mounts{$mountpoint}->{'mode'} = $mode;
            $mounts{$mountpoint}->{'type'} = $type;
      }
    }
  } elsif ($osname eq 'Linux') {
    Logging::debug("Execute: mount");
    foreach my $mountinfo (`mount`) {
      chomp $mountinfo;
      # unable to use 'undef' here - perl 5.004 compatibility
      my ($device, $undef, $mountpoint, $undef2, $type, $options) = split /\s+/, $mountinfo;
      my $mode = 'rw';
      $mode = 'ro' if ($options =~ /[(,]ro[,)]/);

      my ($fs, $opts) = split /,/, $type, 2;

      unless (_isPseudoFs($fs)) {
            $mounts{$mountpoint} = ();
            $mounts{$mountpoint}->{'device'} = $device;
            $mounts{$mountpoint}->{'mode'} = $mode;
            $mounts{$mountpoint}->{'type'} = $fs;
      }
    }
  } elsif ($osname eq 'Darwin') {
    Logging::debug("Execute: mount");
    foreach my $mountinfo (`mount`) {
      chomp $mountinfo;
      my ($device, $mountpoint, $options) = ($mountinfo =~ /^(.*)\son\s(.*?)(?:\s(\(.*\)))?$/);
      my $mode = 'rw';
      $mode = 'ro' if ($options =~ /[( ]read-only[,)]/);

      unless(_isPseudoFs($device)) {
        $mounts{$mountpoint} = ();
        $mounts{$mountpoint}->{'device'} = $device;
        $mounts{$mountpoint}->{'mode'} = $mode;
        $mounts{$mountpoint}->{'type'} = 'unknown';
      }
    }
  } else {
    Logging::debug("Unknown OS type");
    return;
  }

  my %partitions;

  my ($stdout, $stderr);
  eval {
    Logging::debug("Execute: LANG=C POSIXLY_CORRECT= df -Pl $path | head -n 1");
    IPC::Run::run(
      ['df', '-Pl', $path]
      , '|', ['head', '-n', '1']
      , '1>', \$stdout, '2>', \$stderr
      , init => sub { $ENV{'LANG'}='C', $ENV{'POSIXLY_CORRECT'}='' }
    ) or die($stderr);
  };
  if ($@) {
    Logging::debug("Execution failed: $@", 'UtilityError');
    return;
  }
  unless ($stdout =~ /\s(\d+)-/) {
    Logging::debug("Unable to determine block size in df output. First line looks like '$stdout'");
    return;
  }
  my $blocksize = $1;

  ($stdout, $stderr) = (undef, undef);
  eval {
    Logging::debug("Execute: LANG=C POSIXLY_CORRECT= df -Pl $path | tail -n +2");
    IPC::Run::run(
      ['df', '-Pl', $path]
      , '|', ['tail', '-n', '+2']
      , '1>', \$stdout, '2>', \$stderr
      , init => sub { $ENV{'LANG'}='C', $ENV{'POSIXLY_CORRECT'}='' }
    ) or die($stderr);
  };
  if ($@) {
    Logging::debug("Execution failed: $@", 'UtilityError');
    return;
  }
  foreach my $dfinfo (split("\n", $stdout)) {
    # unable to use 'undef' here - perl 5.004 compatibility
    my ($undef, $size, $undef2, $free, $undef3, $mountpoint) =
        ($dfinfo =~ m|^(.*)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+%)\s+(/.*)$|);

    if (exists $mounts{$mountpoint}) {
      # for brain-dead NFS shares:
      $free = $size if $free > $size;

      $size *= $blocksize;
      $free *= $blocksize;

      next if $free =~ /[eE]\+/ or $size =~ /[eE]\+/;

      $partitions{$mountpoint} = $mounts{$mountpoint};
      $partitions{$mountpoint}->{'size'} = $size;
      $partitions{$mountpoint}->{'free'} = $free;
    }
  }

  my $result;
  foreach my $partition (keys %partitions) {
    $result = $partitions{$partition}->{'free'};
  }
  
  return $result;
}

sub generateUuid {
  # http://www.ietf.org/rfc/rfc4122.txt
  my @chars = split(//, 'xxxxxxxx-xxxx-4xxx-8xxx-xxxxxxxxxxxx');
  my @digs  = split(//, '0123456789abcdef');
  my $uuid = '';
  foreach my $char (@chars) {
    if($char eq 'x'){
      $uuid.= $digs[rand(scalar(@digs))];
    }
    else {
      $uuid.= $char;
    }
  }
  return $uuid;
}

sub trim {
 my $string = shift;
 $string =~ s/^\s+//;
 $string =~ s/\s+$//;
 return $string;
}

# Heads up: one can't just call eval "require Module" multiple times,
# because if first eval will fail with runtime error (missing .so,
# for example), subsequent ones won't return any error. So, we cache
# the results here.
# TODO: add cache invalidation (for example, if you want to play
# with 'lib' pragma in the middle of execution).
my %_loadableModulesCache = ();
sub isModuleLoadable {
  my ($moduleName) = @_;

  if (not exists($_loadableModulesCache{$moduleName})) {
    eval "require $moduleName";
    if ($@) {
      $_loadableModulesCache{$moduleName} = 0;
    } else {
      $_loadableModulesCache{$moduleName} = 1;
    }
  }
  return $_loadableModulesCache{$moduleName};
}

my %encodedPathCharacters = (
    "\\" => '\\\\',
    "\n" => '\n',
    "\r" => '\r',
    "\t" => '\t',
);
my %decodedPathCharacters = reverse %encodedPathCharacters;

sub encodePath {
  my ($path) = @_;
  $path =~ s/([\\\n\r\t])/$encodedPathCharacters{$1}/sg;
  return $path;
}

sub decodePath {
  my ($path) = @_;
  $path =~ s/(\\\\|\\n|\\r|\\t)/$decodedPathCharacters{$1}/sg;
  return $path;
}

sub getStorageUrlFromFtpSettings {
  my ($ftp) = @_;
  my $url = $ftp->{'protocol'}."://".$ftp->{'login'}."@".$ftp->{'server'};
  $url .= ":$ftp->{port}" if $ftp->{port};
  $url .= "/$ftp->{path}" if $ftp->{path};
  $url =~ s/\/+$//;
  return $url;
}

sub replaceLinesInFile {
  my ($inFile, $outFile, $regexPattern, $replacement) = @_;

  my ($fhIn, $fhOut);
  return 1 unless (open $fhIn, $inFile);
  unless (open $fhOut, ">$outFile") {
    close $fhIn;
    return 1;
  }
  while (my $line = <$fhIn>) {
    chomp $line;
    $line =~ s/$regexPattern/$replacement/g;
    print $fhOut ($line."\n");
  }
  close $fhIn;
  close $fhOut;
  return 0;
}

sub getStorageType {
  my ($outputFile) = @_;

  if (index($outputFile, 'ftp://') == 0 or index($outputFile, 'ftps://') == 0) {
    return 'foreign-ftp';
  }
  if (index($outputFile, 'ext://') == 0) {
    return 'extension';
  }
  return 'file';
}

sub compareVersions {
  my ($left, $right) = @_;
  my $len = max(scalar(@{$left}), scalar(@{$right}));
  push @{$left}, (0)x($len - scalar(@{$left}));
  push @{$right}, (0)x($len - scalar(@{$right}));
  for (my $i = 0 ; $i < $len; ++$i) {
    return -1 if $left->[$i] < $right->[$i];
    return  1 if $left->[$i] > $right->[$i];
  }
  return 0;
}

1;
