<?php
/**
* This file has the sending page in it. This only handles sending and scheduling of email campaigns.
*
* @version     $Id: send.php,v 1.80 2008/03/04 07:43:44 chris Exp $
* @author Chris <chris@interspire.com>
* @author Fredrick Gabelmann <fredrick.gabelmann@interspire.com>
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
require_once(dirname(__FILE__) . '/sendstudio_functions.php');

/**
* Class for management of sending newsletters.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Send extends SendStudio_Functions
{
	var $max_image_count = 10;

	/**
	* ValidSorts
	* An array of sorts you can use with send management.
	*
	* @var Array
	*/
	var $ValidSorts = array('name', 'createdate');

	/**
	* Constructor
	* Loads the language file.
	*
	* @see LoadLanguageFile
	* @see PrintHeader
	* @see PrintFooter
	*
	* @return Void Loads up the language file and adds 'send' as a valid popup window type.
	*/
	function Send()
	{
		$this->PopupWindows[] = 'send';
		$this->PopupWindows[] = 'view_report';
		$this->LoadLanguageFile();
	}

	/**
	* Process
	* This works out where you are up to in the send process and takes the appropriate action. Most is passed off to other methods in this class for processing and displaying the right forms.
	*
	* @return Void Doesn't return anything.
	*/
	function Process()
	{
		$action = (isset($_GET['Action'])) ? strtolower($_GET['Action']) : null;
		$user = IEM::userGetCurrent();
		$access = $user->HasAccess('Newsletters', 'send');

		$popup = (in_array($action, $this->PopupWindows)) ? true : false;
		$this->PrintHeader($popup);

		if (!$access) {
			$this->DenyAccess();
			return;
		}

		if ($action == 'processpaging') {
			$this->SetPerPage($_GET['PerPageDisplay']);
			$action = '';
		}

		switch ($action) {
			case 'viewsenderrors':
				$job = (isset($_GET['Job'])) ? (int)$_GET['Job'] : 0;
				if (!$this->CanAccessJobs($job)) {
					$this->DenyAccess();
					return;
				}
				echo $this->PrintSendFailureReport($job);
			break;

			case 'view_report':
				$queueid = IEM::sessionGet('ReportQueue');

				$report_type = (isset($_GET['ReportType'])) ? strtolower($_GET['ReportType']) : null;
				switch ($report_type) {
					case '1':
						$GLOBALS['Heading'] = GetLang('SendProblem_Report_Subscriber_Problem_Heading');
						$GLOBALS['Intro'] = GetLang('SendProblem_Report_Subscriber_Problem_Intro');
					break;

					case '10':
						$GLOBALS['Heading'] = GetLang('SendProblem_Report_Email_Problem_Heading');
						$GLOBALS['Intro'] = GetLang('SendProblem_Report_Email_Problem_Intro');
					break;

					case '20':
						$GLOBALS['Heading'] = GetLang('SendProblem_Report_MailServer_Problem_Heading');
						$GLOBALS['Intro'] = GetLang('SendProblem_Report_MailServer_Problem_Intro');
					break;

					case '30':
						$GLOBALS['Heading'] = GetLang('SendProblem_Report_SMTPMailServer_Problem_Heading');
						$GLOBALS['Intro'] = GetLang('SendProblem_Report_SMTPMailServer_Problem_Intro');
					break;

					default:
						$GLOBALS['Heading'] = GetLang('SendProblem_Report_Invalid_Heading');
						$GLOBALS['Intro'] = GetLang('SendProblem_Report_Invalid_Intro');
						$GLOBALS['EmailList'] = GetLang('SendProblem_InvalidReportURL');
						$this->ParseTemplate('SendProblem_Report_Results_View');
					break 2;
				}

				$api = $this->GetApi('Subscribers');

				$email_list = '';
				$problem_email_addresses = $api->GetUnsentSubscribers($queueid, $report_type);
				foreach ($problem_email_addresses as $emailaddress) {
					$email_list .= htmlspecialchars($emailaddress, ENT_QUOTES, SENDSTUDIO_CHARSET) . "\n";
				}
				$GLOBALS['EmailList'] = $email_list;
				$this->ParseTemplate('SendProblem_Report_Results_View');

			break;


			case 'pausesend':
				$job = (int)$_GET['Job'];
				if (!$this->CanAccessJobs($job)) {
					$this->DenyAccess();
					return;
				}

				$api = $this->GetApi('Jobs');
				$paused = $api->PauseJob($job);
				if ($paused) {
					$GLOBALS['Message'] = $this->PrintSuccess('Send_Paused_Success');
				} else {
					$GLOBALS['Error'] = GetLang('Send_Paused_Failure');
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
				}
				$this->ParseTemplate('Send_Step5_Paused');
			break;

			case 'sendfinished':
				$job = (int)$_GET['Job'];
				if (!$this->CanAccessJobs($job)) {
					$this->DenyAccess();
					return;
				}

				$send_details = IEM::sessionGet('SendDetails');

				$statsapi = $this->GetApi('Stats');

				$statsapi->MarkNewsletterFinished($send_details['StatID'], $send_details['SendSize']);

				$timetaken = $send_details['SendEndTime'] - $send_details['SendStartTime'];
				$timedifference = $this->TimeDifference($timetaken);

				$GLOBALS['SendReport_Intro'] = sprintf(GetLang('SendReport_Intro'), $timedifference);

				$sendreport = '';
				if ($send_details['EmailResults']['success'] > 0) {
					if ($send_details['EmailResults']['success'] == 1) {
						$sendreport .= $this->PrintSuccess('SendReport_Success_One');
					} else {
						$sendreport .= $this->PrintSuccess('SendReport_Success_Many', $this->FormatNumber($send_details['EmailResults']['success']));
					}
				}

				$this->PrintSendFailureReport($job, $sendreport);

				$api = $this->GetApi('Jobs');
				$api->FinishJob($job);
				$api->ClearQueue($send_details['SendQueue'], 'send');
			break;

			case 'send':
				IEM::sessionRemove('ApproveJob');
				API_USERS::creditEvaluateWarnings($user->GetNewAPI());

				$jobid = (int)$_GET['Job'];

				if (!$this->CanAccessJobs($jobid)) {
					$this->DenyAccess();
					return;
				}

				$subscriberApi = $this->GetApi('Subscribers');

				$jobApi = $this->GetApi('Jobs');

				if (!isset($_GET['Started'])) {
					$jobApi->StartJob($jobid);
				}

				$sendqueue = $jobApi->GetJobQueue($jobid);

				$send_api = $this->GetApi('Send');

				$job = $jobApi->LoadJob($jobid);

				$send_api->Set('statid', $send_api->LoadStats($jobid));

				$send_api->Set('jobdetails', $job['jobdetails']);
				$send_api->Set('jobowner', $job['ownerid']);

				if (isset($_GET['Resend'])) {
					// this function handles moving everyone onto the 'live' queue etc so we don't need to worry about any of that.
					$send_api->ResendJob_Setup($jobid);
				}

				$queuesize = $jobApi->QueueSize($sendqueue, 'send');

				$send_details = IEM::sessionGet('SendDetails');

				$send_details['SendQueue'] = $sendqueue;

				$timenow = AdjustTime(0, true, null, true);

				$timediff = ($timenow - $send_details['SendStartTime']);

				$time_so_far = $this->TimeDifference($timediff);

				$num_left_to_send = $send_details['SendSize'] - $queuesize;

				if ($num_left_to_send > 0) {
					$timeunits = $timediff / ($num_left_to_send);
					$timediff = ($timeunits * $queuesize);
				} else {
					$timediff = 0;
				}
				$timewaiting = $this->TimeDifference($timediff);

				$GLOBALS['SendTimeSoFar'] = sprintf(GetLang('Send_TimeSoFar'), $time_so_far);
				$GLOBALS['SendTimeLeft'] = sprintf(GetLang('Send_TimeLeft'), $timewaiting);

				if ($queuesize <= 0) {
					$email = $this->GetApi('Email');
					if (SENDSTUDIO_SAFE_MODE) {
						$email->Set('imagedir', TEMP_DIRECTORY . '/send');
					} else {
						$email->Set('imagedir', TEMP_DIRECTORY . '/send.' . $jobid . '.' . $sendqueue);
					}
					$email->CleanupImages();

					$send_details['SendEndTime'] = AdjustTime(0, true, null, true);
					IEM::sessionSet('SendDetails', $send_details);

					$GLOBALS['Send_NumberLeft'] = GetLang('SendFinished');
					$this->ParseTemplate('Send_Step5');
					?>
						<script>
							window.opener.focus();
							window.opener.document.location = 'index.php?Page=Send&Action=SendFinished&Job=<?php echo $jobid; ?>&r=<?php echo time(); ?>';
							window.close();
						</script>
					<?php
					break;
				}

				if ($queuesize == 1) {
					$GLOBALS['Send_NumberLeft'] = GetLang('Send_NumberLeft_One');
				} else {
					$GLOBALS['Send_NumberLeft'] = sprintf(GetLang('Send_NumberLeft_Many'), $this->FormatNumber($queuesize));
				}

				if ($num_left_to_send == 1) {
					$GLOBALS['Send_NumberAlreadySent'] = GetLang('Send_NumberSent_One');
				} else {
					$GLOBALS['Send_NumberAlreadySent'] = sprintf(GetLang('Send_NumberSent_Many'), $this->FormatNumber($num_left_to_send));
				}

				$send_api->SetupJob($jobid, $sendqueue);

				$recipients = $send_api->FetchFromQueue($sendqueue, 'send', 1, 1);

				$send_api->SetupDynamicContentFields($recipients);

				$send_api->SetupCustomFields($recipients);

				$sent_ok = false;

				foreach ($recipients as $p => $recipientid) {
					$send_results = $send_api->SendToRecipient($recipientid, $sendqueue);

					// save the info in the session, then see if we need to pause between each email.
					if ($send_results['success'] > 0) {
						$sent_ok = true;
						$send_details['EmailResults']['success']++;
					} else {
						$send_details['EmailResults']['failure']++;
					}
					$send_details['EmailResults']['total']++;
					IEM::sessionSet('SendDetails', $send_details);
				}

				$GLOBALS['JobID'] = $jobid;

				$template = $this->ParseTemplate('Send_Step5', true);
				$template .= $this->PrintFooter(true, true);
				echo $template;

				// we should only need to pause if we successfully sent.
				if ($sent_ok) {
					$send_api->Pause();
				}
				exit;
			break;

			case 'step4':
				$newsletter_chosen = $_POST['newsletter'];
				if ($newsletter_chosen == 0) {
					$this->SelectNewsletter(GetLang('Send_Step4_ChooseNewsletter'));
					break;
				}

				if (!$this->CanAccessNewsletter($newsletter_chosen)) {
					$this->DenyAccess();
					break;
				}

				$send_details = IEM::sessionGet('SendDetails');
                
				$send_details['Multipart'] = (isset($_POST['sendmultipart'])) ? 1 : 0;
				$send_details['TrackOpens'] = (isset($_POST['trackopens'])) ? 1 : 0;
				$send_details['TrackLinks'] = (isset($_POST['tracklinks'])) ? 1 : 0;
				$send_details['EmbedImages'] = (isset($_POST['embedimages'])) ? 1 : 0;
				$send_details['Newsletter'] = $_POST['newsletter'];
				$send_details['SendFromName'] = $_POST['sendfromname'];
				$send_details['SendFromEmail'] = $_POST['sendfromemail'];
				$send_details['ReplyToEmail'] = (isset($_POST['replytoemail'])) ? $_POST['replytoemail'] : $send_details['SendFromEmail'];
				$send_details['BounceEmail'] = (isset($_POST['bounceemail'])) ? $_POST['bounceemail'] : $send_details['SendFromEmail'];

				$newsletterapi = $this->GetApi('Newsletters');
				$newsletterapi->Load($send_details['Newsletter']);
				$archive = $newsletterapi->Archive();
				if(empty($archive)) {
					$GLOBALS['Messages'] = $this->PrintWarning('SendNewsletterArchive_DeactivatedWarning');
				}

				$to_firstname = false;
				if (isset($_POST['to_firstname']) && (int)$_POST['to_firstname'] > 0) {
					$to_firstname = (int)$_POST['to_firstname'];
				}

				$send_details['To_FirstName'] = $to_firstname;

				$to_lastname = false;
				if (isset($_POST['to_lastname']) && (int)$_POST['to_lastname'] > 0) {
					$to_lastname = (int)$_POST['to_lastname'];
				}

				$send_details['To_LastName'] = $to_lastname;

				$send_details['Charset'] = SENDSTUDIO_CHARSET;

				$send_details['NotifyOwner'] = (isset($_POST['notifyowner'])) ? 1 : 0;

				$send_details['SendStartTime'] = AdjustTime(0, true, null, true);

				$send_details['EmailResults']['success'] = 0;
				$send_details['EmailResults']['total'] = 0;
				$send_details['EmailResults']['failure'] = 0;

				$jobapi = $this->GetApi('Jobs');

				$scheduletime = AdjustTime(0, true, null, true);

				$statsapi = $this->GetApi('Stats');

				IEM::sessionSet('SendDetails', $send_details);

				$subscriber_count = $send_details['SendSize'];

				$approved = $user->Get('userid');

				$newslettername = '';
				$newsletterApi = $this->GetApi('Newsletters');
				$newsletterApi->Load($send_details['Newsletter']);
				$newslettername = $newsletterApi->Get('name');
				$newslettersubject = $newsletterApi->Get('subject');

				$newsletter_size = 0;
				$html_size = utf8_strlen($newsletterApi->Get('htmlbody'));
				$text_size = utf8_strlen($newsletterApi->Get('textbody'));

				// if you are sending multipart, then put both parts together to work out an approximate size.
				if ($send_details['Multipart']) {
					$newsletter_size += $html_size + $text_size;
				} else {
					// if you are not sending multipart, then try to work out the html part (as a guide for maximum size).
					if ($html_size > 0) {
						$newsletter_size += $html_size;
					} else {
						$newsletter_size += $text_size;
					}
				}

				$attachments = $this->GetAttachments('newsletters', $send_details['Newsletter'], true);
				if (isset($attachments['filelist'])) {
					foreach ($attachments['filelist'] as $p => $attachment) {
						$file = $attachments['path'] . '/' . $attachment;
						// base64 encoding adds about 30% overhead so we need to add it here.
						$newsletter_size += 1.3 * filesize($file);
					}
				}

				$email_api = $this->GetApi('Email');

				$problem_images = array();

				// we'll do a quick check for the images in the html content to make sure they all work.
				$email_api->Set('EmbedImages', true);
				$email_api->AddBody('html', $newsletterApi->Get('htmlbody'));
				$images = $email_api->GetImages();
				if (is_array($images) && !empty($images)) {
					$max_image_count = $this->max_image_count;

					$counter = 0;
					$total_image_size = 0;
					$image_exceed_threshold = (count($images) > $max_image_count);

					foreach ($images as $md5 => $image_url) {
						list($img, $error) = $email_api->GetImage($image_url);
						$image_size = 1.3 * strlen($img);
						if ($img) {
							if ($send_details['EmbedImages']) {
								// base64 encoding adds about 30% overhead so we need to add it here.
								$newsletter_size += $image_size;
							}
						} else {
                                                        $tempurl = html_entity_decode($image_url,ENT_NOQUOTES);
                                                        list($tempimg, $temperror) = $email_api->GetImage($tempurl);
                                                        if ($tempimg) {
                                                            if ($embed_images) {$total_size += 1.3 * strlen($tempimg);}                                                
                                                        } else {
                                                            $problem_images[] = array('img' => $image_url, 'error' => $temperror);                                                    
                                                        }
						}

						// Images exceed "max image count" threshold....
						// We will need to do something about it so that the application doesn't timeout
						if ($image_exceed_threshold) {
							$total_image_size += $image_size;

							if (++$counter >= $max_image_count) {
								$temp = sprintf(GetLang('CannotVerifyAllImages_ExceedThreshold'), $max_image_count);
								$temp .= sprintf(GetLang('CannotVerifyAllImages_OnlyThresholdImagesVerified'), $max_image_count);

								$problem_images[] = $temp;
								if ($send_details['EmbedImages']) {
									$problem_images[] = sprintf(GetLang('CannotVerifyAllImages_SendSizeEstimated'));

									$average_image_size = ($total_image_size / $max_image_count);
									$total_image_count = count($images);
									$newsletter_size += abs($average_image_size * ($total_image_count - $max_image_count));
								}
								break;
							}
						}
					}
				}

				$img_warning = '';

				if (!empty($problem_images)) {
					foreach ($problem_images as $problem_details) {
						if (is_array($problem_images)) {
							$img_warning .= sprintf(GetLang('UnableToLoadImage'), $problem_details['img'], $problem_details['img'], $problem_details['error']);
						} else {
							$img_warning .= "- {$problem_details}<br/>";
						}
					}
				}

				if ($img_warning) {
					if ($send_details['EmbedImages']) {
						$warning_var = 'UnableToLoadImage_Newsletter_List_Embed';
					} else {
						$warning_var = 'UnableToLoadImage_Newsletter_List';
					}
					$GLOBALS['ImageWarning'] = $this->PrintWarning($warning_var, $img_warning);
				}

				if (SENDSTUDIO_EMAILSIZE_MAXIMUM > 0) {
					if ($newsletter_size >= (SENDSTUDIO_EMAILSIZE_MAXIMUM*1024)) {
						$this->SelectNewsletter(sprintf(GetLang('Newsletter_Size_Over_EmailSize_Maximum'), $this->EasySize(SENDSTUDIO_EMAILSIZE_MAXIMUM*1024, 0)));
						break;
					}
				}

				if (($subcount = IEM::sessionGet('SendSize_Many_Extra', false)) === false) {
					$subcount = $subscriber_count;
				}

				$GLOBALS['ApproximateSendSize'] = sprintf(GetLang('Newsletter_SendSize_Approximate'), $this->EasySize($newsletter_size, 0), $this->EasySize($newsletter_size * $subcount, 1));

				if (SENDSTUDIO_EMAILSIZE_WARNING > 0) {
					if ($newsletter_size > (SENDSTUDIO_EMAILSIZE_WARNING*1024)) {
						$GLOBALS['EmailSizeWarning'] = $this->PrintWarning('Newsletter_Size_Over_EmailSize_Warning_Send', $this->EasySize((SENDSTUDIO_EMAILSIZE_WARNING*1024), 0));
					}
				}

				if (SENDSTUDIO_CRON_ENABLED && SENDSTUDIO_CRON_SEND > 0) {
					if (isset($_POST['sendimmediately']) && $_POST['sendimmediately'] == '1') {
						/*
						* Set the date/time to now if sendimmediately was ticked
						*/
                                                $scheduletime = AdjustTime();
					} else {
                                            /*
                                            * the sendtime is in this format:
                                            * hr:minAM
                                            * so we need to look at the character positions rather than exploding on the separator.
                                            */
                                            $hr = $_POST['sendtime_hours'];
                                            $minute = $_POST['sendtime_minutes'];
                                            $ampm = $_POST['sendtime_ampm'];

                                            if (strtolower($ampm) == 'pm') {
                                                    if ($hr != 12) {
                                                            $hr = $hr + 12;
                                                    }
                                            }

                                            if (strtolower($ampm) == 'am' && $hr == 12) {
                                                    $hr = 0;
                                            }

                                            if ($hr > 23) {
                                                    $hr = $hr - 24;
                                            }

                                            $check_schedule_time = AdjustTime(array($hr, $minute, 0, (int)$_POST['datetime']['month'], (int)$_POST['datetime']['day'], (int)$_POST['datetime']['year']), true);
                                            $five_mins_ago = $newsletterApi->GetGmtTime() - (5*60);
                                            if ($check_schedule_time < $five_mins_ago) {
                                                    $this->SelectNewsletter(GetLang('Send_Step4_CannotSendInPast'));
                                                    break;
                                            }
                                            $scheduletime = $check_schedule_time;
                                        }
					/**
					* Since we're using scheduled sending, we need to check user stats for when this is scheduled to send.
					*/
					$check_stats = $statsapi->CheckUserStats($user, $subscriber_count, $scheduletime);

					list($ok_to_send, $not_ok_to_send_reason) = $check_stats;

					if (!$ok_to_send) {
						echo $this->PrintError($not_ok_to_send_reason);
						// Please refer to Mitch about why I comment this out
						//$this->FilterRecipients($send_details['Lists'], GetLang($not_ok_to_send_reason));
						break;
					}

					$send_details['SendStartTime'] = $scheduletime;

					/**
					 * Store required tracker variables in send details
					 */
					if (check($this, 'mailTrack', true)) {
						if ($this->GetApi('module_TrackerFactory', false)) {
							$list = module_Tracker::GetRequestOptionNamesForAllTracker();
							foreach ($list as $each) {
								if (isset($_POST[$each])) {
									$send_details[$each] = $_POST[$each];
								}
							}
						}
					}
					
				}

				/**
				* see if they have hit refresh on this last step.
				* if they have, then there will already be an approvejob session variable.
				*
				* If there is one there already, clean it up.
				* Give the user back their email credits and delete the stats etc.
				*/
				$job_already_started = IEM::sessionGet('ApproveJob');

				if ($job_already_started) {

					$send_size = IEM::sessionGet('JobSendSize');

					$statsapi = $this->GetApi('Stats');
					$jobapi = $this->GetApi('Jobs');
					// we need to start the job
					// then get the queue
					// then we can get the stats
					// so a user can get their credits back
					// if they cancel a send before doing anything.
					$jobapi->StartJob($job_already_started);
					$queueid = $jobapi->GetJobQueue($job_already_started);

					$statid = $statsapi->GetStatsByQueue($queueid);

					$statsapi->Delete($statid, 'n');
					$jobapi->PauseJob($job_already_started);
					$jobapi->Delete($job_already_started);

					IEM::sessionRemove('JobSendSize');
					IEM::sessionRemove('ApproveJob');
				}

				// if we're not using scheduled sending, create the queue and start 'er up!
				if (!SENDSTUDIO_CRON_ENABLED || SENDSTUDIO_CRON_SEND <= 0) {
                                        $jobcreated = $jobapi->Create('send', $scheduletime, $user->userid, $send_details, 'newsletter', $send_details['Newsletter'], $send_details['Lists'], $approved);
                                        IEM::sessionSet('ApproveJob', $jobcreated);
                                        IEM::sessionSet('JobSendSize', $subscriber_count);
					/**
					* Record the user stats for this send.
					* We have to do it here so you can't schedule multiple sends and then it records everything.
					*/
					$statsapi->RecordUserStats($user->userid, $jobcreated, $subscriber_count, $scheduletime);
					
					$subscriberApi = $this->GetApi('Subscribers');

					$sendqueue = $subscriberApi->CreateQueue('Send');

					$jobapi->StartJob($jobcreated);

					$queuedok = $jobapi->JobQueue($jobcreated, $sendqueue);

					$send_criteria = $send_details['SendCriteria'];

					$queueinfo = array('queueid' => $sendqueue, 'queuetype' => 'send', 'ownerid' => $user->userid);

					if (isset($send_details['Segments']) && is_array($send_details['Segments'])) {
						$subscriberApi->GetSubscribersFromSegment($send_details['Segments'], false, $queueinfo, 'nosort');
					} else {
						$subscriberApi->GetSubscribers($send_criteria, array(), false, $queueinfo, $user->userid);
					}

					if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
						$subscriberApi->Db->OptimizeTable(SENDSTUDIO_TABLEPREFIX . "queues");
					}
                    
					$subscriberApi->RemoveDuplicatesInQueue($sendqueue, 'send', $send_details['Lists']);

					$subscriberApi->RemoveBannedEmails($send_details['Lists'], $sendqueue, 'send');

					$subscriberApi->RemoveUnsubscribedEmails($send_details['Lists'], $sendqueue, 'send');

					if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
						$subscriberApi->Db->OptimizeTable(SENDSTUDIO_TABLEPREFIX . "queues");
					}

					$send_details['SendSize'] = $subscriberApi->QueueSize($sendqueue, 'send');

					$newsletterstats = $send_details;
					$newsletterstats['Job'] = $jobcreated;
					$newsletterstats['Queue'] = $sendqueue;
					$newsletterstats['SentBy'] = $queueinfo['ownerid'];

					$statid = $statsapi->SaveNewsletterStats($newsletterstats);

					/**
					 * Process tracker request hwere because cron was not enabled
					 * @todo Result for the call to module_Tracker::ParseOptionsForAllTracker() is not being processed and being ignored
					 */
					if (check($this, 'TrackAllLinks', true)) {
						if ($this->GetApi('module_TrackerFactory', false)) {
							$temp = array_merge($_POST, array(	'statid'		=> $statid,
																'stattype'		=> 'newsletter',
																'newsletterid'	=> $send_details['Newsletter']));

							$status = module_Tracker::ParseOptionsForAllTracker($temp);
						}
					}

					$send_details['StatID'] = $statid;

					/**
					* So we can link user stats to send stats, we need to update it.
					*/
					$statsapi->UpdateUserStats($user->userid, $jobcreated, $statid);

					$jobapi->PauseJob($jobcreated);

					IEM::sessionSet('SendDetails', $send_details);

					$GLOBALS['JobID'] = $jobcreated;
				}

				$listdetails = array();
				$listApi = $this->GetApi('Lists');
				foreach ($send_details['Lists'] as $l => $listid) {
					$listApi->Load($listid);
					$listdetails[] = $listApi->Get('name');
				}
				$listnames = implode(', ', $listdetails);

				$GLOBALS['Send_NewsletterName'] = sprintf(GetLang('Send_NewsletterName'), htmlspecialchars($newslettername, ENT_QUOTES, SENDSTUDIO_CHARSET));
				$GLOBALS['Send_NewsletterSubject'] = sprintf(GetLang('Send_NewsletterSubject'), htmlspecialchars($newslettersubject, ENT_QUOTES, SENDSTUDIO_CHARSET));

				$GLOBALS['Send_SubscriberList'] = sprintf(GetLang('Send_SubscriberList'), htmlspecialchars($listnames, ENT_QUOTES, SENDSTUDIO_CHARSET));

				$last_sent_details = $newsletterApi->GetLastSent($send_details['Newsletter']);

				$last_sent = $last_sent_details['starttime'];
				if ($last_sent <= 0 && $send_details['SendSize'] > 5) {
					$GLOBALS['SentToTestListWarning'] = $this->PrintWarning('SendToTestListWarning');
				}

				$SendInfo = IEM::sessionGet('SendInfoDetails');

				if (SENDSTUDIO_CRON_ENABLED && SENDSTUDIO_CRON_SEND > 0) {
                                        $jobcreated = $jobapi->Create('send', $scheduletime, $user->userid, $send_details, 'newsletter', $send_details['Newsletter'], $send_details['Lists'], 0);
                                        IEM::sessionSet('ApproveJob', $jobcreated);
                                        IEM::sessionSet('JobSendSize', $subscriber_count);
					/**
					* Record the user stats for this send.
					* We have to do it here so you can't schedule multiple sends and then it records everything.
					*/
					$statsapi->RecordUserStats($user->userid, $jobcreated, $subscriber_count, $scheduletime);
					
					$GLOBALS['Send_ScheduleTime'] = sprintf(GetLang('JobScheduled'), $this->PrintTime($scheduletime, true));
					$GLOBALS['Send_TotalRecipients'] = sprintf(GetLang('Send_TotalRecipients_Cron'), $this->FormatNumber($SendInfo['Count']));

					$this->ParseTemplate('Send_Step4_Cron');
					break;
				}

				$GLOBALS['Send_TotalRecipients'] = sprintf(GetLang('Send_TotalRecipients'), $this->FormatNumber($newsletterApi->QueueSize($sendqueue, 'send')));

				$this->ParseTemplate('Send_Step4');
			break;

			case 'step3':
				$this->Step3();
			break;

			case 'step2':
				$filteringOption = 0;
				$lists = array();
				$segments = array();

				if (isset($_POST['ShowFilteringOptions'])) {
					$filteringOption = intval($_POST['ShowFilteringOptions']);
					if ($filteringOption != 0) {
						$user->SetSettings('ShowFilteringOptions', $filteringOption);
					}
				}

				if ($filteringOption == 3 && !$user->HasAccess('Segments', 'Send')) {
					$filteringOption = 1;
				}

				switch ($filteringOption) {
					// This is when a list is selected
					case 1:
					case 2:
						if (isset($_POST['lists'])) {
							$lists = $_POST['lists'];
						}
					break;

					// This is when a segment is selected
					case 3:
						if (isset($_POST['segments']) && is_array($_POST['segments'])) {
							$segments = $_POST['segments'];
						}
					break;

					// A list/segment can be selected using "GET" request
					default:
						if (isset($_GET['list'])) {
							$lists = array((int)$_GET['list']);
							$filteringOption = 1;
							$user->SetSettings('ShowFilteringOptions', 1);
						} elseif (isset($_GET['segment'])) {
							$segments = array(intval($_GET['segment']));
							$filteringOption = 3;
							$user->SetSettings('ShowFilteringOptions', 3);
						}
					break;

				}

				if ($filteringOption == 1 || $filteringOption == 2) {
					if (empty($lists)) {
						$GLOBALS['Error'] = GetLang('Send_Step1_ChooseListToSendTo');
						$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
						$this->ChooseList('Send', 'step2', false);
						break;
					}

					if (!$user->Admin()) {
						$availabeLists = $user->GetLists();

						if (is_array($availabeLists)) {
							$availabeLists = array_keys($availabeLists);
						} else {
							$this->ChooseList('Send', 'step2', false);
							break;
						}

						$intersects = array_intersect($lists, $availabeLists);
						if (count($lists) != count($intersects)) {
							$this->ChooseList('Send', 'step2', false);
							break;
						}
					}
				}

				if ($filteringOption == 1) {
					$this->FilterRecipients($lists);
				} elseif ($filteringOption == 2) {
					$send_details = IEM::sessionGet('SendDetails');

					$send_details['Lists'] = $lists;
					$send_details['SendCriteria'] = array('Confirmed' => 1);
					$send_details['SendSize'] = null;
					$send_details['BackStep'] = 1;

					IEM::sessionSet('SendDetails', $send_details);

					$this->Step3();
				} else {
					if (empty($segments)) {
						$GLOBALS['Error'] = GetLang('Send_Step1_ChooseSegmentToSendTo');
						$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
						$this->ChooseList('Send', 'step2', false);
						break;
					}

					if (!$user->Admin()) {
						$availableSegments = $user->GetSegmentList();

						if (!empty($availableSegments)) {
							$availableSegments = array_keys($availableSegments);
						}

						foreach ($segments as $segment) {
							if (!in_array($segment, $availableSegments)) {
								$this->DenyAccess();
								exit();
							}
						}
					}

					$send_details = IEM::sessionGet('SendDetails');

					$send_details['Lists'] = null;
					$send_details['SendCriteria'] = array();
					$send_details['SendSize'] = null;
					$send_details['Segments'] = $segments;

					IEM::sessionSet('SendDetails', $send_details);

					$this->Step3();
				}
			break;

			case 'resumesend':
				$this->ResumeSend();
			break;

			case 'resend':
				$this->ResendJob();
			break;

			default:
				IEM::sessionRemove('SendDetails');

				$id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;
				if (!$this->CanAccessNewsletter($id)) {
					$id = 0;
				}

				$senddetails['NewsletterChosen'] = $id;

				IEM::sessionSet('SendDetails', $senddetails);

				$newsletterapi = $this->GetApi('Newsletters');

				$newsletterowner = ($user->Admin() ? 0 : $user->userid);

				$newsletters = $newsletterapi->GetLiveNewsletters($newsletterowner);
				if (empty($newsletters)) {
					$all_newsletters = $newsletterapi->GetNewsletters($newsletterowner, array(), true);
					if ($all_newsletters < 1) {
						if ($user->HasAccess('Newsletters', 'Create')) {
							$GLOBALS['Message'] = $this->PrintSuccess('NoNewsletters', GetLang('NoNewsletters_HasAccess'));

							$GLOBALS['Newsletters_AddButton'] = $this->ParseTemplate('Newsletter_Create_Button', true, false);

						} else {
							$GLOBALS['Message'] = $this->PrintSuccess('NoNewsletters', '');
						}
					} else {
						if ($user->HasAccess('Newsletters', 'Approve')) {
							$GLOBALS['Message'] = $this->PrintSuccess('NoLiveNewsletters', GetLang('NoLiveNewsletters_HasAccess'));
						} else {
							$GLOBALS['Message'] = $this->PrintSuccess('NoLiveNewsletters', '');
						}
					}
					$this->ParseTemplate('Newsletters_Send_Empty');
					break;
				}
				$this->ChooseList('Send', 'step2', false);
			break;
		}
		$this->PrintFooter($popup);
	}

	/**
	 * Step3
	 * Perform process for step 3 and print out output to the browser
	 * It shows information like:
	 * Approximately how many subscribers you will be sending to (based on your list/segment sizes or your custom search info)
	 * Whether to send immediately or not (if scheduled sending is enabled)
	 * Whether to notify the list owner(s) when the send starts and ends
	 *
	 * It also asks for information, including:
	 * which email campaign to send
	 * the send from name, email, reply-to email, bounce email
	 * Which custom field(s) to use for the "to" field (eg 'To: "first name last name" <email@address.com>')
	 * and about link & open tracking and embedded images.
	 *
	 * @retrun Void Does not return anything, but prints out directly to breowser
	 */
	function Step3()
	{
		$user = GetUser();

		$send_details = IEM::sessionGet('SendDetails');
		$subscriberApi = $this->GetApi('Subscribers');

		if (!isset($send_details['Segments'])) {
			if (isset($_POST['ShowFilteringOptions'])) {
				$show_filtering_options = $_POST['ShowFilteringOptions'];
				$user->SetSettings('ShowFilteringOptions', $show_filtering_options);
			}

			$send_criteria = array();
			if (isset($send_details['SendCriteria'])) {
				$send_criteria = $send_details['SendCriteria'];
			}

			if (isset($_POST['emailaddress']) && $_POST['emailaddress'] != '') {
				$send_criteria['Email'] = $_POST['emailaddress'];
			}

			if (isset($_POST['format']) && $_POST['format'] != '-1') {
				$send_criteria['Format'] = $_POST['format'];
			}

			if (isset($_POST['confirmed']) && $_POST['confirmed'] != '-1') {
				$send_criteria['Confirmed'] = $_POST['confirmed'];
			}

			if (isset($_POST['datesearch']) && isset($_POST['datesearch']['filter'])) {
				$send_criteria['DateSearch'] = $_POST['datesearch'];

				$send_criteria['DateSearch']['StartDate'] = AdjustTime(array(0, 0, 1, $_POST['datesearch']['mm_start'], $_POST['datesearch']['dd_start'], $_POST['datesearch']['yy_start']));

				$send_criteria['DateSearch']['EndDate'] = AdjustTime(array(0, 0, 1, $_POST['datesearch']['mm_end'], $_POST['datesearch']['dd_end'], $_POST['datesearch']['yy_end']));
			}

			$customfields = array();
			if (isset($_POST['CustomFields']) && !empty($_POST['CustomFields'])) {
				$customfields = $_POST['CustomFields'];
			}

			if (isset($_POST['clickedlink']) && isset($_POST['linkid'])) {
				$send_criteria['LinkType'] = 'clicked';
				if (isset($_POST['linktype']) && $_POST['linktype'] == 'not_clicked') {
					$send_criteria['LinkType'] = 'not_clicked';
				}

				$send_criteria['Link'] = $_POST['linkid'];
			}

			if (isset($_POST['openednewsletter']) && isset($_POST['newsletterid'])) {
				$send_criteria['OpenType'] = 'opened';
				if (isset($_POST['opentype']) && $_POST['opentype'] == 'not_opened') {
					$send_criteria['OpenType'] = 'not_opened';
				}

				$send_criteria['Newsletter'] = $_POST['newsletterid'];
			}

			if (isset($_POST['Search_Options'])) {
				$send_criteria['Search_Options'] = $_POST['Search_Options'];
			}

			$send_criteria['CustomFields'] = $customfields;

			$send_criteria['List'] = $send_details['Lists'];

			// can only send to active subscribers.
			$send_criteria['Status'] = 'a';

			$sortinfo = array();

			$subscriber_count = $subscriberApi->GetSubscribers($send_criteria, $sortinfo, true);

			/**
			* If we didn't get any subscribers with our search, then see if the reason is whether:
			*
			* - there are no subscribers on the list(s)
			* - if it's because of the filtering options chosen
			*
			* This is done so we can show an appropriate error message.
			*/
			if ($subscriber_count < 1) {
				$lists = $user->GetLists();
				$user_list_ids = array_keys($lists);
				$list_subscriber_count = 0;
				foreach ($send_details['Lists'] as $p => $listid) {
					if (in_array($listid, $user_list_ids)) {
						$list_subscriber_count += $lists[$listid]['subscribecount'];
						/**
						* we only need to keep going until we find a count > 0
						* as soon as we do, get out of this loop.
						* we know the problem is because of filtering, not because there are no subscribers.
						*/
						if ($list_subscriber_count > 0) {
							break;
						}
					}
				}

				if ($list_subscriber_count == 0) {
					$GLOBALS['Error'] = GetLang('NoSubscribersOnList');
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
					$this->ChooseList('Send', 'step2', false);
				} else {
					$displaymsg = GetLang('NoSubscribersMatch');

					if ($send_details['BackStep'] == 1) {
						$GLOBALS['Error'] = $displaymsg;
						$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
						$this->ChooseList('Send', 'step2', false);
					} else {
						$this->FilterRecipients($send_details['Lists'], $displaymsg);
					}
				}
				return;
			}

			$send_details['SendSize'] = $subscriber_count;
			$send_details['SendCriteria'] = $send_criteria;
			IEM::sessionSet('SendDetails', $send_details);
		} else {
			$status = $subscriberApi->GetSubscribersFromSegment($send_details['Segments'], true, false);

			$send_details['Lists'] = $status['lists'];
			$send_details['SendSize'] = $status['count'];

			if ($send_details['SendSize'] == 0) {
				$GLOBALS['Error'] = GetLang('NoSubscribersOnSegment');
				$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
				$this->ChooseList('Send', 'step1', false);
				return;
			}

			$subscriber_count = $status['count'];

			IEM::sessionSet('SendDetails', $send_details);
		}

		/**
		* If we're not using scheduled sending, then we check the stats here.
		*/
		if (!SENDSTUDIO_CRON_ENABLED || SENDSTUDIO_CRON_SEND <= 0) {
			$stats_api = $this->GetApi('Stats');

			$check_stats = $stats_api->CheckUserStats($user, $subscriber_count, $stats_api->GetServerTime());

			list($ok_to_send, $not_ok_to_send_reason) = $check_stats;

			if (!$ok_to_send) {
				$GLOBALS['Error'] = GetLang($not_ok_to_send_reason);
				$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
				echo $this->PrintError($not_ok_to_send_reason);
				return;
				// Please refer to Mitch about why I comment this out
				//$this->FilterRecipients($send_details['Lists'], GetLang($not_ok_to_send_reason));
			}
		}

		$this->SelectNewsletter();
	}

	/**
	* FilterRecipients
	* Prints out the search forms to restrict the subscribers you want to send a newsletter to. This includes custom fields, format and so on.
	*
	* @param Array $listids An array of listid's the user is sending to, this is used to print a list of custom fields for more restrictive searching to be done.
	*
	* @see CheckListAccess
	* @see GetApi
	* @see Lists_API::Load
	* @see Lists_API::GetListFormat
	* @see Lists_API::GetCustomFields
	* @see Search_Display_CustomField
	* @see PrintSubscribeDate
	*
	* @return Void Doesn't return anything. Prints the search form and that's it.
	*/
	function FilterRecipients($listids=array(), $msg=false)
	{
		$send_details = IEM::sessionGet('SendDetails');
		$send_details['Lists'] = $listids;
		$send_details['SendCriteria'] = array();
		$send_details['BackStep'] = 2;
		IEM::sessionSet('SendDetails', $send_details);

		if ($msg) {
			$GLOBALS['Error'] = $msg;
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
		}

		$listApi = $this->GetApi('Lists');

		$all_customfields = array();

		$format_either	= '<option value="-1">' . GetLang('Either_Format') . '</option>';
		$format_html	= '<option value="h">' . GetLang('Format_HTML') . '</option>';
		$format_text	= '<option value="t">' . GetLang('Format_Text') . '</option>';

		$format_list = array('h' => $format_html, 't' => $format_text, 'b' => $format_either . $format_html . $format_text);

		$formats_found = array();
		$format = '';

		foreach ($listids as $listid) {
			$listApi->Load($listid);
			$listformat = $listApi->GetListFormat();
			if (!in_array($listformat, $formats_found)) {
				$formats_found[] = $listformat;
			}

			$customfields = $listApi->GetCustomFields($listid);
			if (!empty($customfields)) {
				$all_customfields[$listid] = $customfields;
			}
		}

		// if we only found one format, we only need to display the one option.
		// if there is more than one format, then we need to display the list. It doesn't matter what formats the list(s) support - there will always be one or both (text/html) available.
		if (sizeof($formats_found) == 1) {
			$f = array_pop($formats_found);
			$format = $format_list[$f];
		} else {
			$format = $format_list['b'];
		}
		$GLOBALS['FormatList'] = $format;

		$this->PrintSubscribeDate();

		$GLOBALS['ClickedLinkOptions'] = $this->ShowLinksClickedOptions();

		$GLOBALS['OpenedNewsletterOptions'] = $this->ShowOpenedNewsletterOptions();

		$CustomFieldInfo = '';
		foreach ($all_customfields as $listid => $customfields) {
			if (!empty($customfields)) {
				if ($CustomFieldInfo == '') {
					$customfield_display = $this->ParseTemplate('Subscriber_Search_Step2_CustomFields', true, false);
				} else {
					$customfield_display = '';
				}
				foreach ($customfields as $pos => $customfield_info) {
					$manage_display = $this->Search_Display_CustomField($customfield_info);
					$customfield_display .= $manage_display;
				}
				$CustomFieldInfo .= $customfield_display;
			}
		}
		$GLOBALS['CustomFieldInfo'] = $CustomFieldInfo;

		$user = GetUser();

		if ($user->GetSettings('ShowFilteringOptions') == 2) {
			$GLOBALS['DoNotShowFilteringOptions'] = ' CHECKED';
			$GLOBALS['FilterNext_Display'] = 'display:\'\';';
		} else {
			$GLOBALS['ShowFilteringOptions'] = ' CHECKED';
			$GLOBALS['FilterNext_Display'] = 'display:none;';
		}

		$user_lists = $user->GetLists();

		if (sizeof(array_keys($user_lists)) != 1) {
			$GLOBALS['FilterOptions'] = 'style="display:none;"';
		}

		$this->ParseTemplate('Send_Step2');

		if (sizeof(array_keys($user_lists)) == 1) {
			return;
		}

		if (!$msg && $user->GetSettings('ShowFilteringOptions') == 2) {
                    exit();
		}

	}

	/**
	* SelectNewsletter
	* Displays a list of newsletters you can send.
	* Only gets live newsletters.
	* If cron scheduling is enabled, then you get extra options to choose from (whether to notify the owner and of course what time to send the newsletter).
	* You can also choose the character set for the send to use.
	*
	* @see GetApi
	* @see Newsletters_API::GetLiveNewsletters
	* @see CreateDateTimeBox
	* @see CharsetList
	* @see SENDSTUDIO_CRON_ENABLED
	*
	* @return Void Doesn't return anything, prints out the step where you select the newsletter you want to send to your list(s).
	*/
	function SelectNewsletter($errormsg=false)
	{
		$send_details = IEM::sessionGet('SendDetails');
		$user = IEM::getCurrentUser();
		$newsletterapi = $this->GetApi('Newsletters');

		$sendsize = $send_details['SendSize'];
		if ($sendsize == 1) {
			$sendSizeInfo = GetLang('SendSize_One');
		} else {
			$sendinfo = IEM::sessionGet('SendInfoDetails');
			$sendSizeInfo = $sendinfo['Msg'];
		}

		if (SENDSTUDIO_CRON_ENABLED && SENDSTUDIO_CRON_SEND > 0) {
			$sendSizeInfo .= sprintf(' <a href="javascript:void(0)" onClick="alert(\'%s\')">%s</a>', GetLang('ReadMoreWhyApprox'), GetLang('ReadMore'));
		}

		$GLOBALS['Message'] = '';

		if (!IEM::sessionGet('MyError')) {
			$GLOBALS['Success'] = $sendSizeInfo;
			$GLOBALS['Message'] = $this->ParseTemplate('SuccessMsg', true, false);
		}

		if ($errormsg) {
			$GLOBALS['Error'] = $errormsg;
			$GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		if (IEM::sessionGet('MyError')) {
			$GLOBALS['Message'] .= IEM::sessionGet('MyError') . IEM::sessionGet('ExtraMessage');
		}

		$newsletterowner = ($user->Admin() ? 0 : $user->userid);

		$newsletters = $newsletterapi->GetLiveNewsletters($newsletterowner);
		$newsletterlist = '';
		$count = sizeof(array_keys($newsletters));
		$newsletterlist = '<option value="0">' . GetLang('SelectNewsletterToSend') . '</option>';

		foreach ($newsletters as $pos => $newsletterinfo) {
			$chosen = '';
			if ($newsletterinfo['newsletterid'] == $send_details['NewsletterChosen']) {
				$chosen = ' SELECTED';
			}
			$newsletterlist .= '<option value="' . $newsletterinfo['newsletterid'] . '"' . $chosen . '>' . htmlspecialchars($newsletterinfo['name'], ENT_QUOTES, SENDSTUDIO_CHARSET) . '</option>';
		}

		$list = $send_details['Lists'][0]; // always choose the first list. doesn't matter if there are multiple lists to choose from.
		$listapi = $this->GetApi('Lists');
		$listapi->Load($list);

		$customfields = $listapi->GetCustomFields($send_details['Lists'], 'text');

		if (empty($customfields)) {
			$GLOBALS['DisplayNameOptions'] = 'none';
		} else {
			$GLOBALS['NameOptions'] = '';
			foreach ($customfields as $p => $details) {
				$GLOBALS['NameOptions'] .= "<option value='" . $details['fieldid'] . "'>" . htmlspecialchars($details['name'], ENT_QUOTES, SENDSTUDIO_CHARSET) . "</option>";
			}
		}

		$GLOBALS['SendFromEmail'] = $listapi->Get('owneremail');
		$GLOBALS['SendFromName'] = $listapi->Get('ownername');
		$GLOBALS['ReplyToEmail'] = $listapi->Get('replytoemail');
		$GLOBALS['BounceEmail'] = $listapi->Get('bounceemail');

		$GLOBALS['ShowBounceInfo'] = 'none';

		if ($user->HasAccess('Lists', 'BounceSettings')) {
			$GLOBALS['ShowBounceInfo'] = '';
		}

		$GLOBALS['SendCharset'] = SENDSTUDIO_CHARSET;

		$GLOBALS['SendTimeBox'] = $this->CreateDateTimeBox(0, false, 'datetime', true);

		$GLOBALS['NewsletterList'] = $newsletterlist;

		$GLOBALS['DisplayEmbedImages'] = 'none';
		if (SENDSTUDIO_ALLOW_EMBEDIMAGES) {
			$GLOBALS['DisplayEmbedImages'] = '';
			if (SENDSTUDIO_DEFAULT_EMBEDIMAGES) {
				$GLOBALS['EmbedImages'] = ' CHECKED';
			}
		}

		$cron_options = '';
		if (SENDSTUDIO_CRON_ENABLED && SENDSTUDIO_CRON_SEND > 0) {
			$cron_options = $this->ParseTemplate('Send_Step3_Cron', true);
		}
		$GLOBALS['CronOptions'] = $cron_options;


		if (check('send', $user->Admin(), $list) && (!SENDSTUDIO_CRON_ENABLED || SENDSTUDIO_CRON_SEND == 0)) {
			if ($user->Admin()) {
				$NoCronMessage = 'Send_NoCronEnabled_Explain_Admin';
			} else {
				$NoCronMessage = 'Send_NoCronEnabled_Explain_NotAdmin';
			}

			$GLOBALS['NoCronMessage'] = $this->PrintWarning($NoCronMessage);
		}

		if (check('TrackThisSend', 'enabled')) {
			if ($this->GetApi('module_TrackerFactory', false)) {
				$trackerOptions = module_Tracker::GetDisplayOptionsForAllTracker();
				$GLOBALS['TrackerOptions'] = implode('', $trackerOptions);
			}
		}

		$template = $this->ParseTemplate('Send_Step3');
	}

	/**
	* ResumeSend
	* Sets up the session information ready to send the newsletter again.
	*
	* @see GetApi
	* @see Jobs_API::LoadJob
	* @see API::QueueSize
	* @see API::LoadStats
	* @see Newsletters_API::Load
	* @see Lists_API::Load
	*
	* @return Void This doesn't return anything, it handles it all itself.
	*/
	function ResumeSend()
	{
		$job = (int)$_GET['Job'];
		if (!$this->CanAccessJobs($job)) {
			$this->DenyAccess();
			return;
		}

		$jobApi = $this->GetApi('Jobs');

		IEM::sessionRemove('SendDetails');
		$jobinfo = $jobApi->LoadJob($job);
		$send_details = $jobinfo['jobdetails'];

		$GLOBALS['JobID'] = $job;

		$sendqueue = $jobinfo['queueid'];
		$queuesize = $jobApi->QueueSize($sendqueue, 'send');

		$statsid = $jobApi->LoadStats($job);

		$send_details['StatID'] = $statsid;

		$newslettername = '';
		$newsletterApi = $this->GetApi('Newsletters');
		$newsletterApi->Load($send_details['Newsletter']);
		$newslettername = $newsletterApi->Get('name');
		$newslettersubject = $newsletterApi->Get('subject');

		$listdetails = array();
		$listApi = $this->GetApi('Lists');
		foreach ($send_details['Lists'] as $l => $listid) {
			$listApi->Load($listid);
			$listdetails[] = $listApi->Get('name');
		}
		$listnames = implode(', ', $listdetails);

		$GLOBALS['Send_NewsletterName'] = sprintf(GetLang('Send_NewsletterName'), htmlspecialchars($newslettername, ENT_QUOTES, SENDSTUDIO_CHARSET));
		$GLOBALS['Send_NewsletterSubject'] = sprintf(GetLang('Send_NewsletterSubject'), htmlspecialchars($newslettersubject, ENT_QUOTES, SENDSTUDIO_CHARSET));

		$GLOBALS['Send_SubscriberList'] = sprintf(GetLang('Send_SubscriberList'), $listnames);

		$GLOBALS['Send_TotalRecipients'] = sprintf(GetLang('Send_TotalRecipients'), $this->FormatNumber($jobApi->QueueSize($sendqueue, 'send')));

		IEM::sessionSet('SendDetails', $send_details);

		$this->ParseTemplate('Send_Step4');
	}

	/**
	 * ResendJob
	 *
	 * @return Void Does not return anything
	 *
	 * @todo more phpdoc
	 */
	function ResendJob()
	{
		$job = (int)$_GET['Job'];
		if (!$this->CanAccessJobs($job)) {
			$this->DenyAccess();
			return;
		}

		$jobApi = $this->GetApi('Jobs');

		IEM::sessionRemove('SendDetails');

		$jobinfo = $jobApi->LoadJob($job);
		$send_details = $jobinfo['jobdetails'];

		$GLOBALS['JobID'] = $job;

		$sendqueue = $jobinfo['queueid'];
		$queuesize = $jobApi->UnsentQueueSize($sendqueue);
		$statsid = $jobApi->LoadStats($job);
		//if they need to resend but the queuesize is 0 then they most likely deleted some subscribers while campaign was sending or before it could be resent
		if($queuesize <= 0){ 
			$send_api = $this->GetApi('Send');
			$stats_api = $this->GetApi("Stats");
			$email_api = $this->GetApi("Email");
			echo "<div class='FlashError'><img align='left' width='18' height='18' class='FlashError' src='images/error.gif'> <h3>No recipients found in the unsent queue!</h3><br>Cleaned up job and removed resend flag.<br><br><a href='#' onclick='window.location=\"index.php?Page=Newsletters\";'>Go Back</a></div>";
			//need to clean up the job so it won't show up as a resend
			$stats_api->MarkNewsletterFinished($statsid, $jobinfo['jobdetails']['EmailResults']['success']);
			$send_api->ClearQueue($sendqueue, 'send');
			$email_api->CleanupImages();
			$db = IEM::getDatabase();
            $query = "UPDATE [|PREFIX|]stats_newsletters SET sendsize=".$jobinfo['jobdetails']['EmailResults']['success']." WHERE statid={$statsid}";
			$update_result = $db->Query($query);
			exit();	
		}

		$send_details['StatID'] = $statsid;

		$newslettername = '';
		$newsletterApi = $this->GetApi('Newsletters');
		$newsletterApi->Load($send_details['Newsletter']);
		$newslettername = $newsletterApi->Get('name');
		$newslettersubject = $newsletterApi->Get('subject');

		$listdetails = array();
		$listApi = $this->GetApi('Lists');
		foreach ($send_details['Lists'] as $l => $listid) {
			$listApi->Load($listid);
			$listdetails[] = $listApi->Get('name');
		}
		$listnames = implode(', ', $listdetails);

		if ($jobinfo['resendcount'] > 0) {
			if ($jobinfo['resendcount'] == 1) {
				$left_to_send = SENDSTUDIO_RESEND_MAXIMUM - 1;
				if ($left_to_send > 1) {
					$GLOBALS['Send_ResendCount'] = $this->PrintWarning('Send_Resend_Count_One', $this->FormatNumber($left_to_send));
				} else {
					$GLOBALS['Send_ResendCount'] = $this->PrintWarning('Send_Resend_Count_One_OneLeft');
				}
			} else {
				$left_to_send = SENDSTUDIO_RESEND_MAXIMUM - $jobinfo['resendcount'];
				if ($left_to_send > 1) {
					$GLOBALS['Send_ResendCount'] = $this->PrintWarning('Send_Resend_Count_Many', $this->FormatNumber($jobinfo['resendcount']), $this->FormatNumber($left_to_send));
				} else {
					$GLOBALS['Send_ResendCount'] = $this->PrintWarning('Send_Resend_Count_Many_OneLeft', $this->FormatNumber($jobinfo['resendcount']));
				}
			}
		}

		$GLOBALS['Send_NewsletterName'] = sprintf(GetLang('Send_NewsletterName'), htmlspecialchars($newslettername, ENT_QUOTES, SENDSTUDIO_CHARSET));
		$GLOBALS['Send_NewsletterSubject'] = sprintf(GetLang('Send_NewsletterSubject'), htmlspecialchars($newslettersubject, ENT_QUOTES, SENDSTUDIO_CHARSET));

		$GLOBALS['Send_SubscriberList'] = sprintf(GetLang('Send_SubscriberList'), $listnames);

		$GLOBALS['Send_TotalRecipients'] = sprintf(GetLang('Send_Resend_TotalRecipients'), $this->FormatNumber($queuesize));

		IEM::sessionSet('SendDetails', $send_details);

		if ($jobinfo['resendcount'] < SENDSTUDIO_RESEND_MAXIMUM) {
			if (SENDSTUDIO_CRON_ENABLED && SENDSTUDIO_CRON_SEND > 0) {
				$this->ParseTemplate('Send_Resend_Cron');
				return;
			}

			$this->ParseTemplate('Send_Resend');
			return;
		}

		$GLOBALS['Error'] = sprintf(GetLang('Send_Resend_Count_Maximum'), $this->FormatNumber(SENDSTUDIO_RESEND_MAXIMUM));
		$GLOBALS['Send_ResendCount'] = $this->ParseTemplate('ErrorMsg', true, false);

		$this->ParseTemplate('Send_Resend_Maximum');
	}

	/**
	 * Enter description here...
	 *
	 * @param Integer $job Job ID??
	 * @param String $sendreport ??
	 *
	 * @todo phpdoc
	 */
	function PrintSendFailureReport($job=0, $sendreport='')
	{
		$jobApi = $this->GetApi('Jobs');

		$jobinfo = $jobApi->LoadJob($job);

		$send_details = $jobinfo['jobdetails'];

		IEM::sessionSet('ReportQueue', $jobinfo['queueid']);

		$sendqueue = $jobinfo['queueid'];
		$failure_count = $jobApi->UnsentQueueSize($sendqueue);

		if ($failure_count > 0) {
			$error_report = '';
			$reason_codes = $jobApi->Get_Unsent_Reasons($jobinfo['queueid']);
			foreach ($reason_codes as $error_reason) {
				$GLOBALS['ReportLink'] = sprintf(GetLang('SendReport_Failure_Link'), $error_reason['reasoncode']);
				$reason_message = GetLang('SendReport_Failure_Reason_' . $error_reason['reasoncode']);
				if ($error_reason['count'] == 1) {
					$GLOBALS['ReasonMessage'] = sprintf(GetLang('SendReport_Failure_Reason_One'), $reason_message);
				} else {
					$GLOBALS['ReasonMessage'] = sprintf(GetLang('SendReport_Failure_Reason_Many'), $this->FormatNumber($error_reason['count']), $reason_message);
				}
				$error_report .= $this->ParseTemplate('SendReport_Failure_Reason', true, false);
			}

			if ($failure_count == 1) {
				$GLOBALS['Error'] = GetLang('SendReport_Failure_One');
			} else {
				$GLOBALS['Error'] = sprintf(GetLang('SendReport_Failure_Many'), $this->FormatNumber($failure_count));
			}
			$GLOBALS['Error'] .= '<br/><ul>' . $error_report . '</ul>';
			$sendreport .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		$GLOBALS['Send_Report'] = $sendreport;
		$this->ParseTemplate('Send_Step5_Finished');
	}

	/**
	 * CanAccessJobs
	 *
	 * Check whether or not current user is able to access the job.
	 * The checking that is currently being done is NOT optimized for query performance,
	 * as it WILL query the database multiple times for each job ID (and it does it without caching anything).
	 *
	 * @param Mixed $jobids Job IDs to be checked
	 * @return Boolean Returns TRUE if user has permission, FALSE otherwise
	 */
	function CanAccessJobs($jobids)
	{
		$user = GetUser();

		if ($user->Admin()) {
			return true;
		}

		if (!is_array($jobids)) {
			$jobids = array($jobids);
		}

		$jobids = array_map('intval', $jobids);
		$jobids = array_unique($jobids);

		if (empty($jobids)) {
			return false;
		}

		$jobapi = $this->GetApi('Jobs');
		foreach ($jobids as $jobid) {
			$jobrecords = $jobapi->LoadJob($jobid);
			if ($jobrecords['ownerid'] != $user->userid) {
				return false;
			}
		}

		return true;
	}

	/**
	 * CanAccessNewsletter
	 * Checks whether the current user can access a particular newsletter
	 *
	 * @param Int $$newsletterid The ID of the newsletter.
	 *
	 * @return Boolean True if the user can access the newsletter, otherwise false.
	 */
	function CanAccessNewsletter($newsletterid)
	{
		$user = GetUser();

		if ($user->Admin()) {
			return true;
		}

		$newsletterid = intval($newsletterid);
		$newsletterapi = $this->GetApi('Newsletters');

		if (!$newsletterapi->Load($newsletterid)) {
			return false;
		}

		return ($newsletterapi->ownerid == $user->userid);
	}
}
