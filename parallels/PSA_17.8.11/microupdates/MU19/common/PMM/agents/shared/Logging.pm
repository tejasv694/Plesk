# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
package Logging;

use strict;
use warnings;
use POSIX;
use Carp;
use File::Basename qw( dirname );
use File::Path qw( mkpath );
use XmlLogger;
use HelpFuncs;
use IO::Handle;

my $useHiRes = eval( "require Time::HiRes" );

use vars qw|$verbosity $xmlLogger $reportSender|;

use constant {
    LOG_LEVEL_ERROR => 1,
    LOG_LEVEL_WARNING => 2,
    LOG_LEVEL_INFO => 3,
    LOG_LEVEL_DEBUG => 4,
    LOG_LEVEL_TRACE => 5,
};
$verbosity = 5;

my %handlerProc;

sub setXmlLogging {
  $xmlLogger = shift;
}

sub setVerbosity {
  $verbosity = shift;
}

sub getVerbosity {
  return $verbosity;
}

sub setReportSender {
  $reportSender = shift;
}

sub setOnError {
 $handlerProc{onerror} = shift;
}

sub setOnWarning {
 $handlerProc{onwarning} = shift;
}

my $outputHandle;
my $customHandle;

sub getOutputHandle {
  return $outputHandle;
}

sub setOutputHandle {
  $outputHandle = shift;
}

sub open {
  my ($dumpLogPath, $customLogPath) = @_;
  if (defined $dumpLogPath) {
     open $outputHandle, ">> $dumpLogPath";
     $outputHandle->autoflush(1); # disable output buffering
  }
  if ($customLogPath) {
    my $customLogDirPath = dirname($customLogPath);
    if (-e $customLogDirPath or mkpath($customLogDirPath)) {
       open $customHandle, ">> $customLogPath";
       $customHandle->autoflush(1); # disable output buffering
    }
  }
}

sub close {
  if (defined($outputHandle)) {
    close($outputHandle);
    $outputHandle = undef;
  }
  if (defined($customHandle)) {
    close($customHandle);
    $customHandle = undef;
  }
}

sub date {
  if ($useHiRes) {
    my ($seconds, $microseconds) = Time::HiRes::gettimeofday();
    return "[" . $$ . "]: " . POSIX::strftime("%F %T", localtime($seconds)) . sprintf(".%03u", $microseconds/1000);
  } else {
    return "[" . $$ . "]: ".POSIX::strftime("%F %T", gmtime(time()));
  }
}

sub _getMessage {
  my ($marker, $uuid, $message, $code) = @_;
  my $msg = date(). " $marker ";
  $msg .= "$uuid " if defined($uuid);
  $msg .= $message;
  if ( defined $code ) {
    if (( $code eq 'assert') || ($code eq 'fatal')) {
      #local $Carp::CarpLevel = +1;
      $msg .= ": \n" . Carp::longmess();
    }
  }
  $msg .= "\n";
  return $msg;
}

sub _writeMessage {
  my ($message) = @_;
  if ($outputHandle) {
    print $outputHandle ($message);
  } else {
    print STDERR ($message);
  }
  print $customHandle ($message) if ($customHandle);
}

sub error {
  my ($message, $code, %options) = @_;

  my $uuid = HelpFuncs::generateUuid();
  if ($verbosity >= LOG_LEVEL_ERROR) {
    _writeMessage(_getMessage('ERROR', $uuid, $message, $code));
  }
  __addXmlMessage('error', $uuid, $message, $code);
  _sendReport($message, %options);
  $handlerProc{onerror}->( @_ ) if exists $handlerProc{onerror} and ref $handlerProc{onerror} eq 'CODE';
}

sub warning {
  my ($message, $code, %options) = @_;

  my $uuid = HelpFuncs::generateUuid();
  if ($verbosity >= LOG_LEVEL_WARNING) {
    _writeMessage(_getMessage('WARN', $uuid, $message, $code));
  }
  __addXmlMessage('warning', $uuid, $message, $code);
  _sendReport($message, %options);
  $handlerProc{onwarning}->( @_ ) if exists $handlerProc{onwarning} and ref $handlerProc{onwarning} eq 'CODE';
}

sub info {
  my ($message, $code, %options) = @_;

  my $uuid = HelpFuncs::generateUuid();
  if ($verbosity >= LOG_LEVEL_INFO) {
    _writeMessage(_getMessage('INFO', $uuid, $message, $code));
  }
  __addXmlMessage('info', $uuid, $message, $code);
  _sendReport($message, %options);
  $handlerProc{oninfo}->( @_ ) if exists $handlerProc{oninfo} and ref $handlerProc{oninfo} eq 'CODE';
}

sub stacktrace {
  if ($verbosity >= LOG_LEVEL_DEBUG) {
     use Error;
    _writeMessage(_getMessage('DEBUG',undef, Error::Simple->new()->stacktrace()));
  }
}

sub debug {
  my ($message, $code, %options) = @_;

  if ($verbosity >= LOG_LEVEL_DEBUG) {
    _writeMessage(_getMessage('DEBUG', undef, $message, $code));
  }
}

sub trace {
  my ($message, $code, %options) = @_;

  if ($verbosity >= LOG_LEVEL_TRACE) {
    _writeMessage(_getMessage('TRACE', undef, $message, $code));
  }
}

sub beginObject {
  my ( $type, $name, $uuid ) = @_;
  if( ref($xmlLogger) =~ /XmlLogger/ ) {
    $xmlLogger->beginObject( $type, $name, $uuid );
  }
}

sub endObject {
  if( ref($xmlLogger) =~ /XmlLogger/ ) {
    $xmlLogger->endObject();
  }
}

sub __addXmlMessage {
  if( ref($xmlLogger) =~ /XmlLogger/ ) {
    $xmlLogger->addMessage( @_);
  }
}

sub serializeXmlLog {
  my ($filename) = @_;
  if( ref($xmlLogger) =~ /XmlLogger/ ) {
    $xmlLogger->serializeToFile( $filename, 1 );
  }
}

sub getSeverity {
  if( ref($xmlLogger) =~ /XmlLogger/ ) {
    return $xmlLogger->getSeverity();
  }
  return 'success';
}

sub _sendReport {
  my ($message, %options) = @_;

  return unless defined($reportSender);

  return if defined($options{'noreport'});

  my $maxMessageLength = 1024;
  if (length($message) > $maxMessageLength) {
    $message = sprintf("%.${maxMessageLength}s...", $message);
  }

  $reportSender->send(
    defined($options{'operation'}) ? $options{'operation'} : 'backup'
    , defined($options{'step'}) ? $options{'step'} : 'general'
    , sprintf("%s\n\n%s", $message, Carp::longmess())
  );
}

1;
