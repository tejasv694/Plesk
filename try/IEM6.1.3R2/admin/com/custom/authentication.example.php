<?php
/**
 * Authentication related customization
 *
 * This file can be used to influence authentication API.
 * You should NOT change the variable name (ie. $custom) or the key (ie. 'allowed_ip')
 * in order for the customization to work.
 *
 * Copy/Rename this file to authentication.php to make the customization effective.
 *
 * Please make sure you read the description of each customization before you
 * decide to customize the application authentication behaviour.
 *
 * @package interspire.iem.customs
 * @subpackage example
 */

$custom = array(
	/**
	 * Holds an array of IP addressses that is allowed to login as "System Administrator" user.
	 * This lists needs to hold a list of IP addresses. So if you have a dynamic hostname pointing to an IP, you will
	 * need to use "gethostbyname" PHP function.
	 *
	 * This is useful to restrict login as a "System Administrator" user to a certain IP addresses.
	 *
	 * You will also need to set $custom['admin_allow_ip_enabled'] to TRUE
	 *
	 * If this is empty, and the $custom['admin_allow_ip_enabled'] is set to TRUE,
	 * you will not be able to login as an admin AT ALL
	 */
	'admin_allow_ip' => array(
		'127.0.0.1',
		gethostbyname('somedomain.somedynamicip.net')
	),


	/**
	 * If this is set to true, only IP addresses that are specified a list of IP addresses
	 * that are allowed to login as "System Administrator" user.
	 *
	 * You will need to list the IP address from $custom['admin_allow_ip']
	 */
	'admin_allow_ip_enabled' => true,


	/**
	 * Holds an array of IP addresses that will be used to restrict "Regular User" login.
	 * It is similar to $custom['admin_allowed_ip'] option.
	 *
	 * This is useful if you want to restrict login as a regular user to a certain IP address.
	 * You will also need to set $custom['regularuser_allow_ip_enabled'] to TRUE.
	 *
	 * If this is empty, and $custom['regularuser_allow_ip_enabled'] is set to TRUE,
	 * users will NOT be able to login as a regular user AT ALL
	 */
	'regularuser_allow_ip' => array(),


	/**
	 * Whether or not to restrict regular user login to certain ip address.
	 * You will need to list the allowable IP address in $custom['regularuser_allow_ip']
	 */
	'regularuser_allow_ip_enabled' => false,


	/**
	 * Whether or not to enable account masquerading for "System Administrator"
	 */
	'enable_masquerade' => true,


	/**
	 * Specify a unique token that delimits "masquerade" username
	 * ie. admin::someotheruser (then the masquerade token separator is "::"
	 */
	'masquerade_token_separator' => '::',


	/**
	 * Whether or not to enable XML API
	 */
	'enable_xmlapi' => true
);
