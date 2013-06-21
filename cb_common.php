<?php

	/**
	 * Cannon BOSE's centralization of common functions
	 *
	 * Copyright (C) 2002 Michael Cannon <michael@peimic.com>
	 * See full GNU Lesser General Public License in LICENSE.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: cb_common.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */


	// CB file's location
	$cb_root = dirname(__FILE__) . '/';
	$cb_root = windows_path_fix($cb_root);

	// module block and setup code
	include_once($cb_root . 'class.CB_Block_Admin.php');

	// database pager
	include_once($cb_root . 'class.DB_Pager.php');

	// array functions
	include_once($cb_root . 'cb_array.php');

	// database functions
	include_once($cb_root . 'cb_database.php');

	// date and time functions
	include_once($cb_root . 'cb_date_time.php');

	// debug tools
	include_once($cb_root . 'debug_msg.php');

	// file and directory functions
	include_once($cb_root . 'cb_dir_file.php');

	// file upload helper
	include_once($cb_root . 'fileupload/fileupload-class.php');

	// e-mail functions
	include_once($cb_root . 'cb_email.php');
	include_once($cb_root . 'htmlMimeMail/htmlMimeMail.php');

	// html functions
	include_once($cb_root . 'cb_html.php');

	// javascript functions
	include_once($cb_root . 'cb_javascript.php');

	// math functions
	include_once($cb_root . 'cb_math.php');

	// phpMyEdit functions
	include_once($cb_root . 'phpMyEdit/phpMyEdit.class.php');

	// string functions
	include_once($cb_root . 'cb_string.php');

	// validation functions
	include_once($cb_root . 'cb_validation.php');



	/**
	 * Fix $path to work on windows machines.
	 *
	 * @param $path URI from PHP function, ex: getcwd(), 'c:/documents/files/'
	 * @return string
	 */
	function windows_path_fix($path)
	{
		// look at $_ENV['OS']
		// is this running on a Windows machine
		if ( isset($_ENV['OS']) && preg_match('/window/i', $_ENV['OS']) )
		{
			// if Windows...
			// convert forward slashes '/' to back slashes '\'
			$path = preg_replace('/\//', '\\', $path);
		}

		return $path;
	}

?>

