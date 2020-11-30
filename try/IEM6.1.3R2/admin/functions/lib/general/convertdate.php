<?php
/**
 * This file only has the ConvertDate class in it.
 *
 * @version     $Id: convertdate.php,v 1.2 2007/10/08 07:01:07 chris Exp $
 * @author Chris <chris@interspire.com>
 *
 * @package Library
 * @subpackage ConvertDate
 */

/**
 * This is the class for the convert-date system.
 * This should handle converting to & from GMT time for easy manipulation of date/time & timestamps.
 *
 * @see http://www.devnewz.com/devnewz-3-20031113OnUserDefinedTimezonesinPHP.html
 *
 * @package Library
 * @subpackage ConvertDate
 */
class ConvertDate {

    /**
     * Server Timezone.
     * This is used to convert to & from GMT based on the timezone so it's critical this is correct.
     *
     * @var String server_timezone
     */
    var $server_timezone = '';

    /**
     * Users Timezone.
     * This is used to convert to & from GMT based on the timezone so it's critical this is correct.
     *
     * @var String user_timezone
     */
    var $user_timezone = '';

    /**
     * ConvertDate
     * Pass in the users timezone in a standard format like:
     * GMT+11:00
     * GMT-05:00
     * so it can be converted internally to what it needs.
     *
     * @param String $server_timezone The server timezone to convert from.
     * @param String $user_timezone The user timezone to convert from.
     *
     * @return Void Doesn't return anything.
     */
    function ConvertDate($server_timezone='GMT', $user_timezone='GMT') {
        $user_hours = $user_mins = 0;
        $user_offset = str_replace('GMT', '', $user_timezone);
        if (strpos($user_offset, ':') !== false) {
            list($user_hours, $user_mins) = explode(':', $user_offset);
        }
        $this->user_timezone = $user_hours . $user_mins;

        $server_hours = $server_mins = 0;
        $server_offset = str_replace('GMT', '', $server_timezone);
        if (strpos($server_offset, ':') !== false) {
            list($server_hours, $server_mins) = explode(':', $server_offset);
        }
        $this->server_timezone = $server_hours . $server_mins;
    }

    /**
     * ConvertToGMT
     *
     * Same as PHP's mktime but with a $tz parameter which
     * is the timezone for converting the timestamp to GMT.
     * If the user is on the east coast of the USA, this
     * would be "-0400" in summer, and "-0500" in winter.
     *
     * @param Int $hr The hour to convert
     * @param Int $min The minute to convert
     * @param Int $sec The second to convert
     * @param Int $mon The month to convert
     * @param Int $day The day to convert
     * @param Int $yr The year to convert
     *
     * @return Int New timestamp in "GMT" format.
     */
    function ConvertToGMT($hr, $min, $sec, $mon, $day, $yr) {
        $args = func_get_args();
        list($hr, $min, $sec, $mon, $day, $yr) = array_map('intval', $args);
        //mktime will return the time in the servers time zone
        $timestamp = mktime($hr, $min, $sec, $mon, $day, $yr);
        $offset = max($this->server_timezone,$this->user_timezone) - min($this->server_timezone,$this->user_timezone);
        $offset = ($offset / 100) * 3600;
        return $timestamp - $offset;
    }

    /**
     * ConvertToGMTFromServer
     *
     * Same as PHP's mktime but with a $tz parameter which
     * is the timezone for converting the timestamp to GMT.
     * If the user is on the east coast of the USA, this
     * would be "-0400" in summer, and "-0500" in winter.
     *
     * @param Int $hr The hour to convert
     * @param Int $min The minute to convert
     * @param Int $sec The second to convert
     * @param Int $mon The month to convert
     * @param Int $day The day to convert
     * @param Int $yr The year to convert
     *
     * @return Int New timestamp in "GMT" format.
     */
    function ConvertToGMTFromServer($hr, $min, $sec, $mon, $day, $yr) {
        $args = func_get_args();
        list($hr, $min, $sec, $mon, $day, $yr) = array_map('intval', $args);
        $timestamp = mktime($hr, $min, $sec, $mon, $day, $yr);
        $offset = (60 * 60) * ($this->server_timezone / 100); // Seconds from GMT
        $timestamp = $timestamp - $offset;
        return $timestamp;
    }

    /**
     * ConvertFromGMT
     *
     * This is also the same format as PHP's date function,
     * but with the additional timezone parameter which
     * specifies the user's timezone.
     *
     * @param Int $timestamp Timestamp to convert back to the users timezone.
     * @param String $format Format to return the timestamp in.
     *
     * @return String The timestamp in local time in the format specified.
     */
    function ConvertFromGMT($timestamp, $format='d M Y') {
        $offset = max($this->server_timezone,$this->user_timezone) - min($this->server_timezone,$this->user_timezone);
        $offset = ($offset / 100) * 3600;
        if((int)($this->user_timezone) < 0){
            $timestamp = $timestamp - $offset;
        }else{
            $timestamp = $timestamp + $offset;
        }
        return date($format, ($timestamp));
    }

}

?>
