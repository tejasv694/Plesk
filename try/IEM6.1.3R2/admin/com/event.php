<?php
/**
 * This file contains procdeure and classes to enable "Event"
 *
 * @package interspire.iem
 * @subpackage eventdata
 */


/**
 * Include "Interspire Event" library
 */
require_once(IEM_PATH . '/ext/interspire_event/interspireevent.php');
InterspireEvent::init(IEM_InterspireStash::getInstance(), false);

/**
 * Event data class for "IEM_SYSTEM_STARTUP_BEFORE" event
 *
 * This event is called before framework initialization.
 * It does not contains any data.
 *
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SYSTEM_STARTUP_BEFORE extends InterspireEventData
{
	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_SYSTEM_STARTUP_BEFORE';
	}
}

/**
 * Event data class for "IEM_SYSTEM_STARTUP_AFTER" event
 *
 * This event is called after framework initialization.
 * It does not contains any data.
 *
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SYSTEM_STARTUP_AFTER extends InterspireEventData
{
	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_SYSTEM_STARTUP_AFTER';
	}
}

/**
 * Event data class for "IEM_SYSTEM_SHUTDOWN_BEFORE" event
 *
 * This event is called before framework initialization.
 * It does not contains any data.
 *
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SYSTEM_SHUTDOWN_BEFORE extends InterspireEventData
{
	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_SYSTEM_SHUTDOWN_BEFORE';
	}
}

/**
 * Event data class for "IEM_SYSTEM_SHUTDOWN_AFTER" event
 *
 * This event is called after framework initialization.
 * It does not contains any data.
 *
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SYSTEM_SHUTDOWN_AFTER extends InterspireEventData
{
	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_SYSTEM_SHUTDOWN_AFTER';
	}
}






// TODO <---------------------------------
// Re-do all of these events
// We want an event formatted to something like
// <PRODUCT_NAME>_<SECTION>_<PURPOSE>_<BEFORE|AFTER>
// Each event should carry a generalize event data instead of just to serve the purpose of 1 addon.

/**
 * Event data class for "IEM_JOBSTRIGGEREMAILSAPI_PROCESSJOBSEND"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_JOBSTRIGGEREMAILSAPI_PROCESSJOBSEND extends InterspireEventData
{
	/**
	 * Indicates whether or not an email was sent to the recipient
	 * @var Boolean Email sent indication
	 */
	public $emailsent;

	/**
	 * Subscriberid
	 * @var Integer Subscriber ID
	 */
	public $subscriberid;

	/**
	 * Listid
	 * @var Integer List ID
	 */
	public $listid;

	/**
	 * Newsletter record
	 * @var Array Record of the newsletter that got sent to the subscriber
	 */
	public $newsletter;

	/**
	 * Trigger record
	 * @var Array Record of the trigger that triggering the send
	 */
	public $triggerrecord;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_JOBSTRIGGEREMAILSAPI_PROCESSJOBSEND';
	}
}

/**
 * Event data class for "IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS extends InterspireEventData
{
	/**
	 * Should store an associative array containing "IEM Text Menu"
	 * @var Array An array containing representation of the "Text Menu"
	 */
	public $data;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS';
	}
}


/**
 * Event data class for "IEM_SETTINGSAPI_LOADSETTINGS"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SETTINGSAPI_LOADSETTINGS extends InterspireEventData
{
	/**
	 * Should store "Settings_API" object
	 * @var Settings_API Settings API object
	 */
	public $data;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_SETTINGSAPI_LOADSETTINGS';
	}
}


/**
 * Event data class for "IEM_JOBSAPI_GETJOBLIST"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_JOBSAPI_GETJOBLIST extends InterspireEventData
{
	/**
	 * An array of subqueries string
	 * @var Array An array of subqueries
	 */
	public $subqueries;

	/**
	 * An array of list IDs that will limit which jobs to fetch
	 * @var Array An array of list IDs
	 */
	public $listids;

	/**
	 * Specify whether the operation is "Count Only" operation
	 * @var Boolean Count only operation flag
	 */
	public $countonly;

	/**
	 * Specify the job type to be fetched
	 * @var String Job type
	 */
	public $jobtype;

	/**
	 * Specify the queue type to be fetched
	 * @var String Queue type
	 */
	public $queuetype;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_JOBSAPI_GETJOBLIST';
	}
}


/**
 * Event data class for "IEM_JOBSAPI_GETJOBSTATUS"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_JOBSAPI_GETJOBSTATUS extends InterspireEventData
{
	/**
	 * Job status character
	 * @var String Job status character
	 */
	public $jobstatus;

	/**
	 * Job status message
	 * @var String Job status message
	 */
	public $statusmessage;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(true);
		$this->_eventName = 'IEM_JOBSAPI_GETJOBSTATUS';
	}
}


/**
 * Event data class for "IEM_NEWSLETTERSAPI_DELETE"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_NEWSLETTERSAPI_DELETE extends InterspireEventData
{
	/**
	 * A flag noting whether or not it's OK to delete
	 * @var Boolean Flag on wheter or not it's alright to delete the newsletter
	 */
	public $status;

	/**
	 * Newsletter ID to be deleted
	 * @var Integer Newsletter ID that about to be deleted
	 */
	public $newsletterid;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_NEWSLETTERSAPI_DELETE';
	}
}


/**
 * Event data class for "IEM_STATSAPI_RECORDOPEN"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_STATSAPI_RECORDOPEN extends InterspireEventData
{
	/**
	 * An array containing the keys 'subscriberid', 'statid', and 'opentype' where opentype is h for an HTML email or t for a text email.
	 * @var Array Open details
	 */
	public $open_details;

	/**
	 * The type of statistics entry this is for, 'n' for newsletter or 'a' for autoresponder
	 * @var String Statistic type
	 */
	public $statstype;

	/**
	 * Specify true if this open is being recorded because a link was clicked on or false if it is being recorded because the open image was loaded
	 * @var Boolean A flag specifying whether or not this stats was coming from a link click
	 */
	public $from_link_click;

	/**
	 * Specify whether or not the "open" has previously been recorded
	 * @var Boolean A flag specifying wheter or not "open" has previously been recorded
	 */
	public $have_been_recorded;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(true);
		$this->_eventName = 'IEM_STATSAPI_RECORDOPEN';
	}
}

/**
 * Event data class for "IEM_STATSAPI_RECORDLINKCLICK"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_STATSAPI_RECORDLINKCLICK extends InterspireEventData
{
	/**
	 * An array containing the keys 'subscriberid', 'statid', and 'clicktime'
	 * @var Array Click details
	 */
	public $click_details;

	/**
	 * The type of statistics entry this is for, 'n' for newsletter or 'a' for autoresponder
	 * @var String Stats type
	 */
	public $statstype;

	/**
	 * Specify whether or not the "click" has previously been recorded
	 * @var Boolean A flag specifying whether or not "click" has priviously been recorded
	 */
	public $have_been_recorded;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(true);
		$this->_eventName = 'IEM_STATSAPI_RECORDLINKCLICK';
	}
}


/**
 * Event data class for "IEM_USERAPI_GETPERMISSIONTYPES"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_USERAPI_GETPERMISSIONTYPES extends InterspireEventData
{
	/**
	 * Extra permissions that needed to be passed over to the main application
	 * @var Array An array of permission that needed to be passed over
	 */
	public $extra_permissions;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_USERAPI_GETPERMISSIONTYPES';
	}
}


/**
 * Event data class for "EventData_IEM_NEWSLETTERS_MANAGENEWSLETTERS"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_NEWSLETTERS_MANAGENEWSLETTERS extends InterspireEventData
{
	/**
	 * Message to be displayed in the "Manage Newsletter" page
	 * @var String message
	 */
	public $displaymessage;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_NEWSLETTERS_MANAGENEWSLETTERS';
	}
}


/**
 * Event data class for "IEM_SCHEDULE_EDITJOB"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SCHEDULE_EDITJOB extends InterspireEventData
{
	/**
	 * Job record to be edited
	 * @var Array An associative array of the job record
	 */
	public $jobrecord;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(true);
		$this->_eventName = 'IEM_SCHEDULE_EDITJOB';
	}
}


/**
 * Event data class for "IEM_SCHEDULE_PAUSEJOB"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SCHEDULE_PAUSEJOB extends InterspireEventData
{
	/**
	 * Job record to be paused
	 * @var Array An associative array of the job record
	 */
	public $jobrecord;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(true);
		$this->_eventName = 'IEM_SCHEDULE_PAUSEJOB';
	}
}


/**
 * Event data class for "IEM_SCHEDULE_RESUMEJOB"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SCHEDULE_RESUMEJOB extends InterspireEventData
{
	/**
	 * Job record to be resumed
	 * @var Array An associative array of the job record
	 */
	public $jobrecord;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(true);
		$this->_eventName = 'IEM_SCHEDULE_RESUMEJOB';
	}
}


/**
 * Event data class for "IEM_SCHEDULE_APPROVEJOB"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SCHEDULE_APPROVEJOB extends InterspireEventData
{
	/**
	 * Job record to be approved
	 * @var Array An associative array of the job record
	 */
	public $jobrecord;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(true);
		$this->_eventName = 'IEM_SCHEDULE_APPROVEJOB';
	}
}


/**
 * Event data class for "IEM_SCHEDULE_DELETEJOBS"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SCHEDULE_DELETEJOBS extends InterspireEventData
{
	/**
	 * An array of job IDs to be deleted
	 * @var Array An array of job ID
	 */
	public $jobids;

	/**
	 * Message to be displayed on "Sechedule list" page
	 * @var String Message to be displayed
	 */
	public $Message;

	/**
	 * Number of successfully deleted jobs
	 * @var Integer Number of successfully deleted jobs
	 */
	public $success;

	/**
	 * Number of failure when trying to delete jobs
	 * @var Integer Number of failure when trying to delete jobs
	 */
	public $failure;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_SCHEDULE_DELETEJOBS';
	}
}


/**
 * Event data class for "IEM_SCHEDULE_RESENDJOB"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SCHEDULE_RESENDJOB extends InterspireEventData
{
	/**
	 * Job record to be resent
	 * @var Array An associative array of the job record
	 */
	public $jobrecord;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(true);
		$this->_eventName = 'IEM_SCHEDULE_RESENDJOB';
	}
}


/**
 * Event data class for "IEM_SCHEDULE_UPDATEJOB"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SCHEDULE_UPDATEJOB extends InterspireEventData
{
	/**
	 * Job record to be updated
	 * @var Array An associative array of the job record
	 */
	public $jobrecord;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(true);
		$this->_eventName = 'IEM_SCHEDULE_UPDATEJOB';
	}
}


/**
 * Event data class for "IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS extends InterspireEventData
{
	/**
	 * Should store an associative array containing "IEM Menu"
	 * @var Array An array containing representation of the "IEM Menu"
	 */
	public $data;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS';
	}
}

/**
 * Event data class for "IEM_SURVEYS_VIEWCONTENT"
 * @package interspire.iem
 * @subpackage eventdata
 */

class EventData_IEM_SURVEYS_VIEWCONTENT extends InterspireEventData
{
	public $content = '';

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_SURVEYS_VIEWCONTENT';
	}
}

class EventData_IEM_SURVEYS_REPLACETAG extends InterspireEventData
{
	public $data = '';

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_SURVEYS_REPLACETAG';
	}
}


class EventData_IEM_EDITOR_SURVEY_BUTTON extends InterspireEventData
{
	/**
	 * Should add the code for the javascript
	 */
	public $code = '';

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_EDITOR_SURVEY_BUTTON';
	}
}



/**
 * Event data class for "IEM_SENDSTUDIOFUNCTIONS_TINYMCEPLUGIN"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_HTMLEDITOR_TINYMCEPLUGIN extends InterspireEventData
{
	/**
	 * Should add the code for the javascript
	 */
	public $code = '';

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_HTMLEDITOR_TINYMCEPLUGIN';
	}

}

/**
 * Event data class for "IEM_DCT_HTMLEDITOR_TINYMCEPLUGIN"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_DCT_HTMLEDITOR_TINYMCEPLUGIN extends InterspireEventData
{
	/**
	 * Should add the code for the javascript
	 */
	public $code = '';

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_DCT_HTMLEDITOR_TINYMCEPLUGIN';
	}

}

/**
 * Event data class for "IEM_EDITOR_TAG_BUTTON"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_EDITOR_TAG_BUTTON extends InterspireEventData
{
	/**
	 * Should add the code for the javascript
	 */
	public $code = '';

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_EDITOR_TAG_BUTTON';
	}

}

/**
 * Event data class for "IEM_ADDON_DYNAMICCONTENTTAGS_GETALLTAGS"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_ADDON_DYNAMICCONTENTTAGS_GETALLTAGS extends InterspireEventData
{
	/**
	 * All the available tags
	 */
	public $allTags;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_ADDON_DYNAMICCONTENTTAGS_GETALLTAGS';
	}

}

/**
 * Event data class for "IEM_ADDON_DYNAMICCONTENTTAGS_GETALLTAGS"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_ADDON_DYNAMICCONTENTTAGS_REPLACETAGCONTENT extends InterspireEventData
{
	/**
	 * All the available tags
	 */
	public $lists;
	public $contentTobeReplaced;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_ADDON_DYNAMICCONTENTTAGS_REPLACETAGCONTENT';
	}

}

/**
 * Event data class for "IEM_SENDSTUDIOFUNCTIONS_CLEANUPOLDQUEUES"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SENDSTUDIOFUNCTIONS_CLEANUPOLDQUEUES extends InterspireEventData
{
	/**
	 * Current requested page
	 * @var String Current page
	 */
	public $page;

	/**
	 * Current rewuested action on the page
	 * @var String Current action
	 */
	public $action;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_SENDSTUDIOFUNCTIONS_CLEANUPOLDQUEUES';
	}
}


/**
 * Event data class for "IEM_CRON_RUNADDONS"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_CRON_RUNADDONS extends InterspireEventData
{
	/**
	 * List of cron job to run... this is an associative array
	 *
	 * <code>
	 * Array
	 * (
	 * 	'addonid' => 'my_addon_id',
	 * 	'file' => '/full/path/to/file',
	 * )
	 * </code>
	 *
	 * If the process functions require any id's they need to be supplied in a 'jobids' array like this:
	 * <code>
	 * Array
	 * (
	 * 	'addonid' => 'my_addon_id',
	 * 	'file' => '/full/path/to/file',
	 * 	'jobids' => array (
	 * 		1,
	 * 		2,
	 * 		3
	 * 	),
	 * )
	 * </code>
	 *
	 * @var Array Addons cron job to run
	 */
	public $jobs_to_run;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_CRON_RUNADDONS';
	}
}

/**
 * Event data class for "IEM_SENDAPI_SENDTORECIPIENT"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_SENDAPI_SENDTORECIPIENT extends InterspireEventData
{
	/**
	 * Status of sent email
	 *
	 * @var Int True if email was sent
	 */
	public $emailsent;

	/**
	 * Details of the job being processed
	 *
	 * @var Array Associative array of job details
	 */
	public $jobdetails;

	/**
	 * Details of the campaign being sent
	 *
	 * @var Array Associative array of campaign details
	 */
	public $newsletter;

	/**
	 * Information of recipient
	 *
	 * @var Array Associative array of recipient details
	 */
	public $subscriberinfo;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_SENDAPI_SENDTORECIPIENT';
	}
}

/**
 * Event data class for "IEM_SENDAPI_SENDTORECIPIENT"
 * @package interspire.iem
 * @subpackage eventdata
 */
class EventData_IEM_JOBSAUTORESPONDERAPI_ACTIONJOB extends InterspireEventData
{
	/**
	 * Status of sent email
	 *
	 * @var Int True if email was sent
	 */
	public $emailsent;

	/**
	 * Details of the autoresponder being sent
	 *
	 * @var Array Associative array of campaign details
	 */
	public $autoresponder;

	/**
	 * Information of recipient
	 *
	 * @var Array Associative array of recipient details
	 */
	public $subscriberinfo;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		parent::__construct(false);
		$this->_eventName = 'IEM_JOBSAUTORESPONDERAPI_ACTIONJOB';
	}
}