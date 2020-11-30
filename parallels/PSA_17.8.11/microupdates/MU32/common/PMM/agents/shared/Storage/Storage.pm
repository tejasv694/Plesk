# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
package Storage::Storage;

use strict;
use warnings;
use bigint;
use Storage::Bundle;
use Logging;
use AgentConfig;
use HelpFuncs;
use Error qw|:try|;

use POSIX;
use IPC::Run;
use Symbol;
use File::Copy;

use Storage::Splitter;
use Storage::Counter;

sub new {
  my $self = {};
  bless($self, shift);
  $self->_init(@_);
  return $self;
}

sub DESTROY {
  my $self = shift;
  $self->unreserveSpace();
}

#
# common options:
# 'gzip' => 0|1
#

sub _init {
  my ($self, %options) = @_;

  if ($options{gzip_bundle}) {
    if (!AgentConfig::pigzBin()) {
      Logging::debug('Unable to find \'pigz\', trying \'gzip\'');
      if (!AgentConfig::gzipBin()) {
        my $errmsg = 'Unable to find neither \'pigz\' nor \'gzip\'';
        throw Error(-text => $errmsg);
      }
    }
  }

  $self->{split_size} = $options{split_size};
  $self->{gzip_bundle} = $options{gzip_bundle};
  $self->{output_dir} = $options{output_dir};
  $self->{space_reserved} = $options{space_reserved} if $options{space_reserved};
  $self->{last_used_id} = 0;
  $self->{unpacked_size} = 0;
  $self->{packed_size} = 0;
  $self->{exportFileName} = undef;
  $self->{mainDumpRootPath} = undef;
  $self->{createdVolumes} = [];
}

#
# bundle parameters:
# 'directory' => string
# bundle options:
# 'include' => ref([string])
# 'exclude' => ref([string])
# 'follow-symlinks' => 1
# 'user' => string
#

sub finishExport {

}

#
# Writes the descriptor (XML index) to the storage.
#

sub finish {
  my $self = shift;

  my $ret = $self->_finish(@_);
  return $ret ? 0 : 1;
}

sub _finish {
  my $self = shift;
  my $descriptor = shift;
  $self->unreserveSpace();
  return $self->finishXmlFile( $descriptor, undef, @_ );
}

#
# Writes the descriptor (XML index) to the storage.
#

sub finishChild {
  my $self  = shift;
  my $ret = $self->finishXmlFile( @_ );
  return $ret;
}

sub getFilesFromId{
  my ($self, $id) = @_;
  return $self->{files}->{$id};
}


sub getFilePathFromId{
  my ($self, $id) = @_;
  return $self->{destdir}->{$id};
}

sub getFilesUnpackSizeFromId{
  my ($self, $id) = @_;
  return $self->{unpacksize}->{$id};
}

sub getFullOutputPath{
 my ($self) = @_;
 return AgentConfig::get("DUMP_D");
}

sub createRepositoryIndex{
  my ($self, $index) = @_;
  if ($index) {
    Logging::debug("Create repository index: $index");
    my $destDir = $self->getFullOutputPath() . "/.discovered";
    system("mkdir", "-p", "$destDir") if not -e $destDir;
    open INDEXFILE, "> $destDir/$index";
    close INDEXFILE;
  }
}

sub writeDiscovered{
  my ( $self, $dumpPath, $dumpXmlName, $dumpSize, $ownerGuid, $ownerType, $objectGuid, $objectId ) = @_;

 die "Pure virtual function call";
}

sub moveFileToDiscovered {
  my ($self, $srcPath, $newName, $dumpDirPath, $dumpXmlName) = @_;
  die "Pure virtual function call";
}

sub getDumpFiles{
  my ($self, $fromPath ) = @_;

  my @ret;
  while( my( $id, $data ) = each( %{$self->{files}} ) ) {
    my $path = $self->getFilePathFromId( $id );
    $path = substr( $path, length ($fromPath) ) if $fromPath && index( $path, $fromPath )==0;
    $path .= '/' if $path and substr( $path, -1, 1 ) ne '/';
    $path = substr( $path, 1 ) if substr ( $path, 0, 1 ) eq '/';
    foreach my $filedata( @{$data} ) {
      push @ret, "$path$filedata->[0]";
    }
  }
  return @ret;
}

sub getDumpFilesSize{
  my ($self) = @_;

  my $size = 0;
  while (my (undef, $files) = each(%{$self->{files}})) {
    foreach my $file (@{$files}) {
      $size += $file->[1];
    }
  }
  return $size;
}

sub getMainDumpXmlFile{
  my ($self) = @_;
  return $self->{dumpxmlfile};
}

sub getMainDumpXmlRelativePath{
  my ($self) = @_;
  return $self->{dumpxmlrelpath};
}

sub isMainDump{
  my ($self, $dumpPath, $dumpXmlName) = @_;
  
  my $dumpRelPath = ($dumpPath) ? $dumpPath.'/'.$dumpXmlName.'.xml' : $dumpXmlName.'.xml';
  return $dumpRelPath eq $self->getMainDumpXmlRelativePath();
}

sub getDefExtension{
  my ($self) = @_;
  return '' if $self->noDefExtension();
  return '.tgz' if  $self->{gzip_bundle};
  return '.tar';
}


sub noDefExtension{
  my ($self) = @_;
  return 1 if exists $self->{nodefext};
  return 0;
}

sub setNoDefExtension{
  my ($self) = @_;
  $self->{nodefext} = 1;
}

# -- Factory --

#
# Additional option: splitsize. see the split(1) for the formats
#

sub createFileStorage {
  my (
    $gzip
    , $output_dir
    , $splitsize
    , $sign
    , $space_reserved
    , $passive_mode
    , $export_dir
    , $ftp
    , $session_path
    , $verbose
    , $keepLocalBackup
    , $keepLocalBackupIfExportFailed
  ) = @_;

  if ($output_dir =~ /^ftps?:\/\//) {
    require Storage::FtpFileStorage;
    return Storage::FtpFileStorage->new('gzip_bundle' => $gzip,
                                     'output_dir' => $output_dir,
                                     'split_size' => $splitsize,
                                     'sign' => $sign,
                                     'space_reserved' => $space_reserved,
                                     'passive_mode' => $passive_mode );
  } elsif (defined($ftp) or defined($export_dir)) {
    require Storage::ArchiveStorage;
    return Storage::ArchiveStorage->new(
      'gzip_bundle' => $gzip
      , 'output_dir' => $output_dir
      , 'split_size' => $splitsize
      , 'sign' => $sign
      , 'space_reserved' => $space_reserved
      , 'export-dir' => $export_dir
      , 'ftp' => $ftp
      ,'session-path' => $session_path
      , 'verbose' => $verbose
      , 'keep-local-backup' => $keepLocalBackup
      , 'keep-local-backup-if-export-failed' => $keepLocalBackupIfExportFailed
    );
  } else {
    require Storage::FileStorage;
    return Storage::FileStorage->new('gzip_bundle' => $gzip,
                                     'output_dir' => $output_dir,
                                     'split_size' => $splitsize,
                                     'sign' => $sign,
                                     'space_reserved' => $space_reserved );
  }
}


sub startCollectStatistics ( $ ) {
  my $self = shift(@_);

  $self->{collectStatistics} = 1;
  $self->{statistics}->{sqlTime} = 0;
  use StopWatch;
  $self->{stopWatch} = StopWatch->new();
}

sub stopCollectStatistics ( $ ) {
  my $self = shift(@_);

  $self->{collectStatistics} = 0;
  delete $self->{statistics};
  delete $self->{stopWatch};
}

sub getStatistics ( $ ) {
  my $self = shift(@_);

  return $self->{statistics};
}

sub getFileSize
{
  my( $fileName ) = @_;
  my ($dev,$ino,$mode,$nlink,$uid,$gid,$rdev,$size,$atime,$mtime,$ctime, $blksize,$blocks) = stat($fileName);
  return $size if defined $size;
  return 0;
}



#
# Checks the validity of proposed id: it should not be too long.
#

sub getFileNameIdFromId {
  my ($self, $id, $ext, $cansplit ) = @_;

  my $maxLength = &POSIX::PATH_MAX;
  $maxLength -= length( $ext );
  if ( $cansplit && $self->{split_size}) {
    $maxLength -= 4;
  }

  my $destFile = $self->getFullOutputPath() . "/" . $id;
  if (length($destFile) > $maxLength) {
    $id = $self->{last_used_id}++;
    $destFile = $self->getFullOutputPath() . "/" . $id;
  }

  if ($self->{gzip_bundle}) {
    $id .= ":gzipped";
  }

  my $dstDir = $destFile;
  if( $dstDir=~ m/(.*)\/(.*)/ ){
    $dstDir = $1;
    $destFile = $2;
  }
  else{
    $destFile = 'empty';
  }

  return ($dstDir,$destFile, $id);
}

sub getBundleExecutor {
  my ($bundle) = @_;
  return sub {
    eval {
      my $exec = $bundle->run();
      binmode STDOUT;
      my $block;
      my $blocklen;
      my $timeWorking = time();

      while ($blocklen = sysread($exec, $block, 65536)) {
        my $offset = 0;
        do {
          my $written = syswrite(STDOUT, $block, $blocklen, $offset);
          die $! unless defined $written ;
          $offset += $written;
          $blocklen -= $written;
        } while ($blocklen != 0);
        # bug 30101. Prevent ssh connection close(when source host has strong security policy) on big content
        if ( time() - $timeWorking > 30) {
          Logging::debug("Tar is working");
          $timeWorking = time();
        }
      }

      my $exit_code = $bundle->cleanup();
      POSIX::_exit($exit_code);
    };
    if ($@) {
      print STDERR $@;
      POSIX::_exit(1);
    }
  };
}

sub executeAndSave {
  my ($self, $destDir, $destFile, $destExt, $bundle, $outunpackedSize, $createVolumes) = @_;

  my $unpackedSize = 0;
  system( "mkdir", "-p", $destDir ) if $destDir and not -e $destDir;

  #allocating filehandle for creating pipe from subprocess
  my $newhandle = POSIX::open("/dev/null", O_RDWR, 0666);

  my @cmd;
  push @cmd, getBundleExecutor($bundle);

  if ($self->{gzip_bundle} and $createVolumes) {
    # If pigz command does not exists, try gzip and report the warning.
    # If gzip command does not exists, report the error.
    my $cmd_zip = AgentConfig::pigzBin();
    if (!$cmd_zip) {
      $cmd_zip = AgentConfig::gzipBin();
      if (!$cmd_zip) {
        POSIX::close($newhandle);
        my $errmsg = 'Unable to find neither \'pigz\' nor \'gzip\'';
        throw Error(-text => $errmsg);
      }
    }
    push @cmd, "|", [$cmd_zip];
  }

  my $newhandle2 = POSIX::open("/dev/null", O_RDWR, 0666);

  my $splitSize = $createVolumes ? $self->{split_size} : 0;

  my $splitterOut;
  push @cmd, "|", \&Storage::Splitter::run, "$newhandle2>", \$splitterOut,
    init => sub {Storage::Splitter::init_process($newhandle2, $splitSize, $destFile, $destDir, $destExt )};

  my $logHandle = Logging::getOutputHandle();
  my $stderrClone;
  if (!$logHandle) {
    Logging::debug("Clone STDERR and set up as temporary log handler.");
    open($stderrClone, ">&", \*STDERR);
    Logging::setOutputHandle($stderrClone);
  }

  my $stderr;
  my $h = IPC::Run::harness(@cmd,'2>', \$stderr);
  my @files = ();
  while ($h->pump()) {
    my $position = -1;
    while (1) {
      $position = index($splitterOut, "\n", $position + 1);
      last if $position == -1;
      my  $file = substr($splitterOut, 0, $position);
      push @files, $file;
      $splitterOut = substr($splitterOut, $position + 1);
      $position = -1;
      if ($createVolumes) {
        my ($fileName, $fileSize) = split (/ /, $file);
        $self->onVolumeCreate($destDir, $fileName, $fileSize);
      }
    }
  }
  my $result = $h->finish();

  Logging::setOutputHandle($logHandle);
  if ($stderrClone) {
    Logging::debug("Close STDERR clone and restore log handler.");
    close $stderrClone;
  }
  POSIX::close($newhandle);
  POSIX::close($newhandle2);

  if (!$result) {
    my ($total, $avail, $mount) = HelpFuncs::getMountSpace($destDir);
    $total = sprintf("%.2f GB", $total / (1024. * 1024. * 1024.));
    $avail = sprintf("%.2f GB", $avail / (1024. * 1024. * 1024.));
    my $errmsg;
    if ($stderr) {
       $errmsg = $bundle->filterStderr($stderr);
    }
    Logging::warning("Not all the data was backed up into $destDir successfully.\nTotal space: $total; Available space: $avail; Mounted on: $mount.\n" . $errmsg, undef, 'step' => 'content', 'noreport' => 1);
    return if (!@files);
  } elsif ($stderr) {
    Logging::debug($stderr);
  }

  if ($unpackedSize =~ /ERR\s(.*)/) {
    Logging::error("Unable to pipe data through filter: $1");
    return;
  }

  $self->{unpacked_size} += $unpackedSize;
  ${$outunpackedSize} = $unpackedSize;

   my @ret;
   foreach my $line (@files) {
     my ($file_name, $file_size) = split (/ /, $line);
      $self->{packed_size} += $file_size;
      my @filedata;
      push @filedata, $file_name;
      push @filedata, $file_size;
      push @ret, \@filedata;
   }
  return \@ret if (@ret);
  return;
}

sub addDb {
  my ($self, $proposedId, %options) = @_;

  if ($self->{collectStatistics})
  {
    $self->{stopWatch}->createMarker("pack");
  }

  my ($destDir, $destFile, $id) = $self->getFileNameIdFromId($proposedId, $self->{gzip_bundle}, '', 1);
  Logging::debug("DB bundle. id=$id, destFile=$destFile");

  my $bundle = Storage::Bundle::createDbBundle(%options, 'gzip' => 0 );
  return unless $bundle;
  my $size = 0;
  my $files = $self->executeAndSave($destDir, $destFile, '',  $bundle, \$size, 0);
  if ($self->{collectStatistics})
  {
    $self->{statistics}->{packTime} += $self->{stopWatch}->getDiff("pack");
    $self->{stopWatch}->releaseMarker("pack");
  }
  if( $files and @{$files} ){
     my $filename = $files->[0]->[0];
     $filename = substr( $filename, length($destDir)+1 ) if index( $filename, $destDir )==0;
     my $ret = $self->addTar( $proposedId, "directory" => $destDir, "include"   => [$filename] );
     foreach my $file( @{$files} ){
       $filename = $file->[0];
       $filename = substr( $filename, length($destDir)+1 ) if index( $filename, $destDir )==0;
       if (exists $options{'create_local_dump'} && $options{'create_local_dump'} != 0) {
         Logging::debug("Database server ". $options{'type'} . ':' . $options{'host'} . ':' .$options{'port'} . " is registered for source and destination hosts. Content of such database should be saved on source host in " . $options{'dir_for_local_dump'} . " directory");
         mkdir($options{'dir_for_local_dump'}, 0600) if not -e $options{'dir_for_local_dump'};
         system("cp $destDir/$filename $options{'dir_for_local_dump'}");
       }

       unlink "$destDir/$filename" or Logging::error("Cannot delete temp file '$destDir/$filename'");

     }
     return $ret;
  }
  else{
    Logging::warning("Failed to execute backup database");
    return undef;
  }

}

sub finishXmlFile {
  my ($self, $descriptor, $child, $savePath, $fileName) = @_;

  if ($self->{collectStatistics})
  {
    $self->{stopWatch}->createMarker("pack");
  }

  $fileName = 'dump' if not $fileName;
  my $dumpFile = $self->_getInfoXmlFileName( $fileName );
  my $relativePath = ($savePath) ? "$savePath/$dumpFile" : $dumpFile;
  $savePath = $self->getFullOutputPath() . "/$savePath";
  system("mkdir", "-p", "$savePath") if not -e $savePath;
  Logging::debug("Writing dump file: $savePath/$dumpFile");

  open DUMPFILE, "> $savePath/$dumpFile";
  if( $child ){
    $descriptor->serializeChild(\*DUMPFILE, $child);
  } else{
    $descriptor->serialize(\*DUMPFILE);
  }
  close DUMPFILE;
  chmod S_IRUSR|S_IWUSR|S_IRGRP, "$savePath/$dumpFile" or Logging::warning("Cannot chmod of '$savePath/$dumpFile'");

  my @files;
  my @file;
  push @file, $dumpFile;
  push @file, getFileSize( "$savePath/$dumpFile" );
  push @files, \@file;
  my $ret = $self->regIdFiles( $relativePath, $savePath, 0, \@files, $child ? undef : 1 );
  if (not $child) {
    $self->{dumpxmlfile} = $dumpFile;
    $self->{dumpxmlrelpath} = $relativePath;
  }

  if ($self->{collectStatistics})
  {
    $self->{statistics}->{packTime} += $self->{stopWatch}->getDiff("pack");
    $self->{stopWatch}->releaseMarker("pack");
  }

  return $ret;
}

sub _getInfoXmlFileName {
  my ($self, $fileName ) = @_;
  return "$fileName.xml";
  #return Storage::Splitter::generateUniqueFileName( $fileName, ".xml" );
}

sub getContentList {
  my ($self) = @_;
  if ($self->{collectStatistics})
  {
    $self->{stopWatch}->createMarker("pack");
  }

  open CONTENT_FILE, $self->_getContentListFileName();
  my $s =  join "", <CONTENT_FILE>;
  close CONTENT_FILE;
  if ($self->{collectStatistics})
  {
    $self->{statistics}->{packTime} += $self->{stopWatch}->getDiff("pack");
    $self->{stopWatch}->releaseMarker("pack");
  }
  return $s;
}

sub checkDirForArchive {
  my ($self, $srcDir, $exclude, $include_hidden_files) = @_;
  # check that directory is not empty
  if (!opendir(SRCDIR, $srcDir)) {
          return;
  }

  my $filename;

  while (defined ($filename = readdir SRCDIR)) {
          my $in_exclude = undef;
          next if $filename =~ /^\.\.?$/;
          if ( ! $include_hidden_files ) {
                  next if $filename =~ /^\..*/;
          }
          if ( ref ($exclude) =~ /ARRAY/ ) {
                  foreach my $ex (@{$exclude}) {
                          $in_exclude = 1 if $filename eq $ex;
                  }
                  next if defined $in_exclude;
          }
          # directory is not empty
          closedir(SRCDIR);
          return 1;
  }
  # directory is empty
  closedir(SRCDIR);
  return;
}

sub reserveSpace {
  my ($self ) = @_;
  if (exists $self->{space_reserved} ) {
    my $avail = (HelpFuncs::getMountSpace($self->getFullOutputPath()))[1];
    if( $avail < $self->{space_reserved} ) {
      my $errmsg = "Available disk space ($avail) is less than required by storage bundle ($self->{space_reserved})";
      Logging::error($errmsg,'fatal');
      print STDERR "$errmsg\n";
      exit(2);
    }
    my $namebase = $self->getFullOutputPath().'/.fs_'.(0+$self).'_';
    my $var = 0;
    while( -e "$namebase$var.tmp"){$var++;}
    $self->{space_reserver} = "$namebase$var";
    Logging::debug("Reserve disk space at $self->{space_reserver}");
    eval {
      my $cmd = ['dd', 'if=/dev/zero', "of=$self->{space_reserver}", "bs=$self->{space_reserved}", 'count=1'];
      Logging::debug('Exec: ' . join(' ', @{$cmd}));
      my $stderr;
      IPC::Run::run($cmd, '2>', \$stderr) or die($stderr);
    };
    if ($@) {
      Logging::debug('Unable to reserve disk space: ' . HelpFuncs::trim($@));
    }
  }
}

sub unreserveSpace {
  my ($self ) = @_;
  if (exists $self->{space_reserver} ) {
    Logging::debug("Free reserved disk space at $self->{space_reserver}");
    if( -f $self->{space_reserver}){
      unlink $self->{space_reserver} or Logging::debug("Cannot delete file ".$self->{space_reserver} );
    }
    delete $self->{space_reserver};
  }

}

sub createContentList{
  my ($self) = @_;
  open CONTENT_FILE, ">" . $self->_getContentListFileName();
  my @files = $self->getDumpFiles( $self->getFullOutputPath() );
  my $fromPath = $self->getFullOutputPath();

  my $fullsize = 0;
  while( my( $id, $data ) = each( %{$self->{files}} ) ) {
    foreach my $filedata( @{$data} ) {
        $fullsize += $filedata->[1];
    }
  }

  print CONTENT_FILE "<contentlist size='$fullsize' >\n";

  while( my( $id, $data ) = each( %{$self->{files}} ) ) {
    my $path = $self->getFilePathFromId( $id );
    $path = substr( $path, length ($fromPath) ) if $fromPath && index( $path, $fromPath )==0;
    $path .= '/' if $path and substr( $path, -1, 1 ) ne '/';
    $path = substr( $path, 1 ) if substr ( $path, 0, 1 ) eq '/';
    foreach my $filedata( @{$data} ) {
        my $mainAttributeValue;
        if ($self->getMainDumpXmlRelativePath() eq $path.$filedata->[0]) {
          $mainAttributeValue = "true";
        }else {
          $mainAttributeValue = "false";
        }
        my $sizeAttributeValue = $filedata->[1];
        print CONTENT_FILE "  <file size='$sizeAttributeValue' main='$mainAttributeValue'>$path$filedata->[0]</file>\n";
    }
  }

  print CONTENT_FILE "  <file size='0'>migration.result</file>\n";
  print CONTENT_FILE "</contentlist>\n";
  close CONTENT_FILE;

}

sub _getContentListFileName {
  my ($self) = @_;
  return $self->getFullOutputPath() . "/content-list.xml";
}

sub regIdFiles{
  my ($self, $id, $destDir, $unpackedSize, $files, $shortid ) = @_;

  if( $files ) {

    if ($destDir eq $self->getFullOutputPath()) {
      $destDir = '';
    } elsif (index($destDir, $self->getFullOutputPath()) == 0) {
      $destDir = substr($destDir, length($self->getFullOutputPath()) + 1);
    }

      if( index( $destDir, -1, 1 ) eq '/' ) {
        $destDir = substr( $destDir, 0, length($destDir)-1 );
      }

      $self->{unpacksize}->{$id} = $unpackedSize;
      $self->{destdir}->{$id} = "$destDir";
      $self->{files}->{$id} = $files;

      for my $file( @{$files} ){
         chmod S_IRUSR|S_IWUSR|S_IRGRP, $self->getFullOutputPath() . '/' . "$destDir/$file->[0]";
      }
      return $id;
  }
  return undef;
}

sub CleanupFiles()
{
  my $self = shift;

  $self->_waitChild();
  $self->_removeFiles();
}

sub setExportFileName() {
  my ($self, $exportFileName) = @_;
  $self->{exportFileName} = $exportFileName;
}

sub setMainDumpRootPath() {
  my ($self, $mainDumpRootPath) = @_;
  $self->{mainDumpRootPath} = $mainDumpRootPath;
}

sub getCreatedVolumes() {
  my ($self) = @_;
  return $self->{createdVolumes};
}

sub onVolumeCreate() {
  # my ($self, $destDir, $fileName, $fileSize) = @_;
}

sub _writeDiscovered {
  my ( $self, $dumpPath, $dumpXmlName, $dumpSize, $ownerGuid, $ownerType, $objectGuid, $objectId ) = @_;

  my $idx = rindex( $dumpXmlName, '.xml' );
  $dumpXmlName = substr( $dumpXmlName, 0, $idx ) if $idx>0;
  my $destDir = $self->getFullOutputPath();
  $destDir .= "/$dumpPath" if $dumpPath;
  $destDir .= "/.discovered/$dumpXmlName";
  push @{$self->{discovered}}, $destDir;

  Logging::debug("Create discovered: $destDir");
  system("mkdir", "-p", "$destDir") if not -e $destDir;

  my @props = ("size_$dumpSize", "owner_$ownerGuid", "ownertype_$ownerType", "GUID_$objectGuid", "objectid_$objectId");
  if ($self->isMainDump($dumpPath, $dumpXmlName)) {
    push(@props, "dump_full");
  } else {
    push(@props, "dump_part");
  }

  my $propsContent = join("\n", @props);
  open(my $propsHandle, '>', "$destDir/props");
  print $propsHandle $propsContent;
  close($propsHandle);

  my @files = (['props', length($propsContent)]);
  $self->regIdFiles( $destDir, $destDir, length($propsContent), \@files );
  return [$destDir, \@files];
}

sub _moveFileToDiscovered {
  my ($self, $srcPath, $newName, $dumpDirPath, $dumpXmlName) = @_;

  my $destDir = $self->getFullOutputPath();
  $destDir .= "/".$dumpDirPath if ($dumpDirPath);
  $destDir .= "/.discovered/$dumpXmlName";

  if (not -e $destDir) {
    push @{$self->{discovered}}, $destDir;
    Logging::debug("Create discovered: $destDir");
    system("mkdir", "-p", "$destDir");
  }

  my $destPath = $destDir."/".$newName;

  move($srcPath, $destPath);

  return $destDir;
}

sub _addTar {
  my ($self, $proposedId, %options) = @_;

  return [undef, undef, undef] unless -d $options{'directory'};

  if (defined $options{'checkEmptyDir'} ||
     !exists $options{'include'} && !exists $options{'add_file'} && !exists $options{'include_hidden_files'} # don`t run tar ... * in empty dir
  ) {
    return [undef, undef, undef] unless $self->checkDirForArchive($options{'directory'}, $options{'exclude'}, $options{'include_hidden_files'});
  }

  if ($self->{collectStatistics})
  {
    $self->{stopWatch}->createMarker("pack");
  }

  my ($destDir, $destFile, $id) = $self->getFileNameIdFromId( $proposedId, $self->getDefExtension(), 1 );
  Logging::debug("Tar bundle. id=$id, destFile=$destDir/$destFile");

  my $bundle = Storage::Bundle::createTarBundle(%options, 'gzip' => $self->{gzip_bundle});

  unless ($bundle)
  {
    if ($self->{collectStatistics})
    {
      $self->{statistics}->{packTime} += $self->{stopWatch}->getDiff("pack");
      $self->{stopWatch}->releaseMarker("pack");
    }
    return [undef, undef, undef];
  }
  my $size = 0;
  my $files = $self->executeAndSave($destDir, $destFile, $self->getDefExtension(), $bundle, \$size, 1);
  my $ret =  $self->regIdFiles( $id, $destDir, $size, $files );
  if ($self->{collectStatistics})
  {
    $self->{statistics}->{packTime} += $self->{stopWatch}->getDiff("pack");
    $self->{stopWatch}->releaseMarker("pack");
  }

  return [$ret, $destDir, $files];
}

sub _waitChild() {
  my $self = shift;
  Logging::debug('Wait termination of child processes');
  my $cmd = "pkill -P $$";
  Logging::debug("Execute: $cmd");
  system($cmd);
  my $pid;
  while( ( $pid = wait() ) !=-1 ){
    Logging::debug("The child process '$pid' has been terminated" );
  }
}

sub _removeFiles()
{
  my $self = shift;
  my $path = $self->getFullOutputPath();
  my @files = $self->getDumpFiles();
  foreach my $file(@files ){
     Logging::debug("Remove file '$file' from repository '$path' ");
     unlink "$path/$file" or Logging::debug("Cannot remove file '$path/$file'");
  }
  if( exists $self->{discovered} ){
    foreach my $discovered(@{$self->{discovered}} ){
       Logging::debug("Remove discovered '$discovered'");
       opendir DIR, $discovered;
       my @dirfiles = readdir( DIR );
       closedir DIR;
       foreach my $file(@dirfiles){
         if( $file ne '.' and $file ne '..' ){
           unlink "$discovered/$file" or Logging::debug("Cannot remove file '$discovered/$file'");
         }
       }
       rmdir( $discovered ) or Logging::debug("Cannot remove discovered '$discovered'");
    }
  }
}

1;
