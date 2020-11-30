<?php
/**
 * This file contains Maintenance functions.
 *
 * @package interspire.iem.lib
 */

/**
 * Maintenance class
 *
 * This class provide all maintainace functions currently the maintanance provides
 * - Clearing up stall or user error import temp files
 * - Clearing up stall export data
 * - Clearing up unused cookies
 *
 *
 * @package interspire.iem.lib
 *
 */
class Maintenance
{
	/**
	 * IMPORT_EXPIRY_TIME
	 * @var import files expiry time to be 5 days old
	 */
	const IMPORT_EXPIRY_TIME = 432000;

	/**
	 * EXPORT_STALL_TIME
	 * @var export database query expiry time set to be 4 hour
	 */
	const EXPORT_STALL_TIME = 10800;
	
	/**
	 * ERRORLOG_EXPIRY_TIME
	 * @var error log entries expiry time set to be 30 days
	 */	
	const ERRORLOG_EXPIRY_TIME = 2592000;



	/**
	 * Database access layer
	 * @var Db Database access layer
	 */
	private $_db = null;



	/**
	 * CONSTRUCTOR
	 * @return Maintenance Returns an instance of this object
	 */
	public function __construct()
	{
		$this->_db = IEM::getDatabase();
	}




	/**
	 * clearImportFiles
	 * Clearing the import files (from import to contact list).
	 *
	 * Files are cleared if they are older than the cutoff date.
	 * Cutoff date is specified in the class constat IMPORT_EXPIRY_TIME.
	 *
	 * The import files mainly exist because of fail export operation,
	 * or user stop during process and leaving the garbage temp file
	 *
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 *
	 * @uses Maintenance::IMPORT_EXPIRY_TIME
	 */
	public function clearImportFiles()
	{
		$importdir = IEM_STORAGE_PATH . '/import';

		// Since this might not necessarily a failure (No import have been done before)
		if (!is_dir($importdir)) {
			return true;
		}


		$handle = @opendir($importdir);
		if (!$handle) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Unable to read import directory', E_USER_WARNING);
			return false;
		}

		$cutoff_time = time() - self::IMPORT_EXPIRY_TIME;

		while (false !== ($file = @readdir($handle))) {
			if ($file{0} == '.') {
				continue;
			}

			$filedate = @filemtime($importdir . '/' . $file);
			if ($filedate === false) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Unable to obtain import file timestamp', E_USER_WARNING);
				continue;
			}

			if ($filedate < $cutoff_time) {
				if (!unlink($importdir . '/' . $file)) {
					trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Unable to delete old import file', E_USER_WARNING);
					continue;
				}
			}
		}

		@closedir($handle);

		return true;
	}
	/**
	 * pruneErrorLog
	 * To clear up all the expired entries in the error log table...
	 *
	 *
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 */
	public function pruneErrorLog()
	{

		$cutoff_time = time() - self::ERRORLOG_EXPIRY_TIME;

		$selectQuery = "
			DELETE
			FROM	[|PREFIX|]log_system_system
			WHERE	logdate <= {$cutoff_time}
			LIMIT 500
		";

		$result = $this->_db->Query($selectQuery);
		if (!$result) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Error Log Query Error', E_USER_WARNING);
			return false;
		}

		$this->_db->FreeResult($result);

		return true;
	}

	/**
	 * pruneExportQueries
	 * To clear up all the unused entry in the export table...
	 * Check for any stalled or unused query that are stuck in the export table
	 *
	 * Finding out all the export query that are still longer than the EXPORT_STALL_TIME,
	 * foreach of the stalled job there, clear up the related entry in the email queues table.
	 * after all the email queues have been clear, clear up the jobs table entry.
	 *
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 */
	public function pruneExportQueries()
	{
		$stalljobs = array();
		$stalljobsId = array();
		$stallqueuesId = array();

		$cutoff_time = time() - self::EXPORT_STALL_TIME;

		$selectQuery = "
			SELECT	jobid, jobdetails
			FROM	[|PREFIX|]jobs
			WHERE	jobtype = 'export'
					AND jobtime <= {$cutoff_time}
		";

		$result = $this->_db->Query($selectQuery);
		if (!$result) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Export Query Error', E_USER_WARNING);
			return false;
		}

		while ($row = $this->_db->Fetch($result)) {
			array_push($stalljobs, $row['jobdetails']);
			array_push($stalljobsId, $row['jobid']);
		}

		$this->_db->FreeResult($result);

		// Since the queueid is stored inside a serialized jobdetails, we will need to process it.
		foreach ($stalljobs as $stalljob) {
			$jobdetails = unserialize($stalljob);
			if (empty($jobdetails) || !isset($jobdetails['ExportQueue']) || !is_array($jobdetails['ExportQueue'])) {
				continue;
			}

			foreach ($jobdetails['ExportQueue'] as $jobs_queue) {
				if(isset($jobs_queue['queueid'])) {
					array_push($stallqueuesId, $jobs_queue['queueid']);
				}
			}
		}

		// clearing all the stall queues
		if (!empty($stallqueuesId)) {
			$delSchedulesQuery = "
				DELETE FROM [|PREFIX|]queues
				WHERE 	queueid IN (" . implode(',', $stallqueuesId) . ")
						AND queuetype = 'export'
			";

			$status = $this->_db->Query($delSchedulesQuery);
			if (!$status) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Cannot delete export queue -- ' . $this->_db->Error(), E_USER_NOTICE);
				return false;
			}
		}

		// now after finish clearing up queues.. clear the actual job from the email_jobs;

		if (!empty($stalljobsId)) {
			$delJobsQuery = "
				DELETE from [|PREFIX|]jobs
				WHERE	jobid IN (" . implode(',', $stalljobsId) . ")
						AND jobtype = 'export'
			";

			$status = $this->_db->Query($delJobsQuery);
			if (!$status) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Cannot delete export job -- ' . $this->_db->Error(), E_USER_NOTICE);
				return false;
			}
		}

		return true;
	}

	/**
	 * clearOldSession
	 * Clearing up unwanted session files.
	 *
	 * In some servers, session files were not being cleaned up by the server automatically.
	 * This function will attempt to delete these older session files.
	 *
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 *
	 * @todo implementation
	 */
	public function clearOldSession()
	{
		// TODO this method has not been implemented yet
		return true;
	}

	/**
	 * clearEmptySubscriberData
	 * Clear up all the empty records in the subscribers_data table
	 * 
	 * After the fix in IEM-247, these records should not be created anymore.
	 * However, deleting existing ones should improve performance. We thought
	 * about doing this as an Upgrade script, but it might timeout and crash
	 * the upgrade.  Also, there could be other sources of bad subscriber data,
	 * which this will protect against.
	 *
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 */
	public function clearEmptySubscriberData()
	{
		//Delete subscriber data for subscribers that no longer exist.
		$deleteQuery = "
			DELETE FROM [|PREFIX|]subscribers_data 
			WHERE subscriberid NOT IN ( 
				SELECT subscriberid
				FROM [|PREFIX|]list_subscribers
			)
			LIMIT 500
		";

		$result = $this->_db->Query($deleteQuery);
		if (!$result) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Delete Query Error', E_USER_WARNING);
			return false;
		}

		//Delete subscriber data where data is an empty string
		$deleteQuery = "
			DELETE FROM [|PREFIX|]subscribers_data 
			WHERE data = ''
			LIMIT 500
		";

		$result = $this->_db->Query($deleteQuery);
		if (!$result) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Delete Query Error', E_USER_WARNING);
			return false;
		}

		//Delete subscriber data where data is an empty serialized array
		$deleteQuery = "
			DELETE FROM [|PREFIX|]subscribers_data 
			WHERE [|PREFIX|]subscribers_data.data='a:0:{}' 
			AND [|PREFIX|]subscribers_data.fieldid IN (
				SELECT fieldid FROM [|PREFIX|]customfields 
				WHERE fieldtype = 'checkbox'
			)
			LIMIT 500
		";

		$result = $this->_db->Query($deleteQuery);
		if (!$result) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Delete Query Error', E_USER_WARNING);
			return false;
		}

		return true;
	}
}
