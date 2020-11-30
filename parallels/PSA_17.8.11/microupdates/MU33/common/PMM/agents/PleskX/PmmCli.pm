# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
package PmmCli;

use strict;
use warnings;
use Logging;
use XmlNode;
use File::Temp;
use AgentConfig;
use Encoding;
require POSIX;
use IPC::Run;
use Symbol;
use HelpFuncs;
use XML::Simple;

sub checkResponseResult{
  my $response = shift;
  if( $response =~ /\<\?xml.*\?\>\s*([\w\W\n\r\s]*){1}/mg  ){
    return 1;
  }
  return;
}

sub _getErrorFromResult{
  my ($response, $errCode, $errMsg ) = @_;
  if (exists $response->{'errcode'}) {
    ${$errCode} = $response->{'errcode'};
    ${$errMsg} = $response->{'errmsg'} if exists $response->{'errmsg'};
    return 1;
  }
  return;
}


sub _analyzeResponseErrCode{
  my( $errCode, $errMsg ) = @_;
  $errMsg = "" if not defined $errMsg;
  $errMsg = ": $errMsg" if $errMsg;
  die "Invalid parameter$errMsg\n" if $errCode == 1;
  die "Invalid task id$errMsg\n" if $errCode == 2;
  die "Invalid session id$errMsg\n" if $errCode == 3;
  die "Low disk space for backup$errMsg\n" if $errCode == 11;
  die "Subprocess execute error from pmmcli$errMsg\n" if $errCode == 1000;
  die "Runtime errror from pmmcli$errMsg\n" if $errCode == 1001;
  die "Unhandled exception from pmmcli$errMsg\n" if $errCode == 1002;
  die "Unknown error from pmmcli$errMsg\n" if $errCode != 0;
  return;
}

sub _getResponseErrCode{
  my $response = shift;
  my ($errCode, $errMsg);
  _getErrorFromResult($response, \$errCode, \$errMsg) or die "Could parse response. Error code not found.\n";
  return _analyzeResponseErrCode($errCode, $errMsg);
}

sub getTaskIdFormResult{
  my $xmlbody = shift;
  checkResponseResult($xmlbody) or die "Could not check dump. Invalid xml response:  $xmlbody\n";;

  my $xs = XML::Simple->new(ForceArray => 0, SuppressEmpty => '');
  my $response = $xs->XMLin($xmlbody, KeyAttr => []);
  _getResponseErrCode($response);
  return $response->{'data'}->{'task-id'};
}

# 0 - dumping
# 1 - finished
# 2 - starting
sub getTaskProgressFormResult{
  my ( $xmlbody, $progress, $finished, $logLocation ) = @_;
  checkResponseResult($xmlbody) or die "Could not get task status. Invalid xml response:  $xmlbody\n";

  my $xs = XML::Simple->new(ForceArray => 0, SuppressEmpty => '');
  my $response = $xs->XMLin($xmlbody, KeyAttr => []);
  _getResponseErrCode($response);
  my $taskStatus = $response->{'data'}->{'task-status'};
  if (exists $taskStatus->{'working'}) {
    return 2 if exists $taskStatus->{'working'}->{'starting'};

    my $dumping = $taskStatus->{'working'}->{'dumping'};
    my ($totalDomains, $totalAccounts, $doneDomains, $doneAccounts, $currentObj);
    $totalDomains = $dumping->{'total-domains'} if exists $dumping->{'total-domains'};
    $totalAccounts = $dumping->{'total-accounts'} if exists $dumping->{'total-accounts'};
    $doneDomains = $dumping->{'completed-domains'} if exists $dumping->{'completed-domains'};
    $doneAccounts = $dumping->{'completed-accounts'} if exists $dumping->{'completed-accounts'};
    $currentObj = $dumping->{'current-object'} if exists $dumping->{'current-object'};

    my $progressValue = "";
    $progressValue .= "Accounts [$doneAccounts/$totalAccounts]" if $doneAccounts or $totalAccounts;
    if( $doneDomains or $totalDomains ){
      $progressValue .= ", " if $progressValue;
      $progressValue .= "Domains [$doneDomains/$totalDomains]";
    }
    if( $currentObj ){
      $progressValue .= ", " if $progressValue;
      $progressValue .= "Dumping '$currentObj' in progress";
    }
    $progressValue = "Dumping" if not $progressValue;
    ${$progress} = $progressValue if $progress;
    return 0;
  }
  elsif (exists $taskStatus->{'finished'}) {
    my ($log, $finishState );
    $log = $finishState = "";
    $log = $taskStatus->{'finished'}->{'log-location'} if exists $taskStatus->{'finished'}->{'log-location'};
    $finishState = $taskStatus->{'finished'}->{'status'} if exists $taskStatus->{'finished'}->{'status'};
    if ($finishState ne 'success' and $finishState ne 'error' and $finishState ne 'warnings' and $finishState ne 'info') {
      Logging::error( "Unknown finished state '$finishState'", 'UtilityError' );
      $finishState = 'error';
    }
    ${$finished} = $finishState if $finished;
    ${$logLocation} = $log if $logLocation;
    return 1;
  }

  die "Could not parse task result: $xmlbody\n";
}

sub test{
  print "Check pmm api responses\n";

  my $errMsg = "This is error Message";

  my $data  = <<EOF;
<?xml version="1.0" encoding="UTF-8"?>
<response>
<errcode>0</errcode>
<errmsg>$errMsg</errmsg>
<data>
  <task-id>123987</task-id>
  <task-status>
    <working>
        <dumping total-domains="12" total-clients="24" completed-domains="2" completed-clients="2" current-object="beper"/>
    </working>
  </task-status>
</data>
</response>
EOF


  print "Check task id\n";
  my $taskId = getTaskIdFormResult( $data );
  print "Get task id: $taskId\n";
  die "Could not get task id " if $taskId != 123987;

  print "Check task progress\n";
  my $progress;
  getTaskProgressFormResult( $data, \$progress );
  print "Get task progress: $progress\n";
  return;
}




1;