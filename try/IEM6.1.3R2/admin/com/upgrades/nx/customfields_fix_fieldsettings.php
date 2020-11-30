<?php
/**
* This file is part of the upgrade process.
*
* @package SendStudio
*/

/**
* Do a sanity check to make sure the upgrade api has been included.
*/
if (!class_exists('Upgrade_API', false)) {
	exit();
}

/**
* This class runs one change for the upgrade process.
* The Upgrade_API looks for a RunUpgrade method to call.
* That should return false for failure
* It should return true for success or if the change has already been made.
*
* @package SendStudio
*/
class customfields_fix_fieldsettings extends Upgrade_API
{
	/**
	* RunUpgrade
	* Runs the query for the upgrade process
	* and returns the result from the query.
	* The calling function looks for a true or false result
	*
	* @return Mixed Returns true if the condition is already met (eg the column already exists).
	*  Returns false if the database query can't be run.
	*  Returns the resource from the query (which is then checked to be true).
	*/
	function RunUpgrade()
	{
		$query = 'SELECT fieldid, fieldtype, fieldsettings FROM ' . SENDSTUDIO_TABLEPREFIX . 'customfields';
		$result = $this->Db->Query($query);

		// no need to worry about 'radiobutton' field type because it's new.
		while ($row = $this->Db->Fetch($result)) {

			$new_fieldtype = $row['fieldtype'];
			switch ($row['fieldtype']) {

				case 'checkbox':
					$new_value = array('Key' => array(), 'Value' => array());

					// since checkbox only had an on / off state, we'll make up the key and values so it can be an ssnx-checkbox (which is the old 'multicheckbox').
					$new_value['Key'] = array('1', '0');
					$new_value['Value'] = array('On', 'Off');

					$new_value = serialize($new_value);
				break;

				case 'datebox':
					$new_fieldtype = 'date';

					list ($first, $second, $third, $start_year, $end_year) = explode(',', $row['fieldsettings']);
					$new_value = array('Key' => array($first, $second, $third, $start_year, $end_year));

					$new_value = serialize($new_value);
				break;

				case 'dropdown':
				case 'multicheckbox':
					if ($row['fieldtype'] == 'multicheckbox') {
						$new_fieldtype = 'checkbox';
					}

					$options = explode(';', $row['fieldsettings']);

					$new_options['Key'] = array();
					$new_options['Value'] = array();

					foreach ($options as $opt => $option) {
						if ($option == '' || (strpos($option, '->') === false)) {
							continue;
						}

						list($k, $v) = explode('->', $option);
						$new_options['Key'][] = $k;
						$new_options['Value'][] = $v;
					}

					$new_value = serialize($new_options);
				break;

				case 'longtext':
					$new_fieldtype = 'textarea';

					list($columns, $rows) = explode(',', $row['fieldsettings']);

					$new_options = array(
						'Rows' => $rows,
						'Columns' => $columns
					);

					$new_value = serialize($new_options);
				break;

				case 'number':
					if (strpos($row['fieldsettings'], ',') !== false) {
						list($length, $min_length, $max_length) = explode(',', $row['fieldsettings']);
					} else {
						$length = 30; $min_length = 0; $max_length = 100;
					}

					$new_options = array(
						'FieldLength' => $length,
						'MinLength' => $min_length,
						'MaxLength' => $max_length
					);

					$new_value = serialize($new_options);
				break;

				case 'shorttext':
				case 'text':
					$new_fieldtype = 'text';

					list($size, $min_length, $max_length) = explode(',', $row['fieldsettings']);

					$new_options = array(
						'FieldLength' => $size,
						'MinLength' => $min_length,
						'MaxLength' => $max_length
					);

					$new_value = serialize($new_options);
				break;

			}

			$update_query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "customfields SET fieldtype='" . $new_fieldtype . "', fieldsettings='" . $this->Db->Quote($new_value) . "' WHERE fieldid='" . $row['fieldid'] . "'";
			$update_result = $this->Db->Query($update_query);
			if (!$update_result) {
				return false;
			}
		}
		return true;
	}
}
