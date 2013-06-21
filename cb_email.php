<?php

	/**
	 * Cannon BOSE's centralization of common e-mail functions
	 *
	 * Copyright (C) 2002 Michael Cannon <michael@peimic.com>
	 * See full GNU Lesser General Public License in LICENSE.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: cb_email.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */



	/**
	 * Remove extraneous stuff from email addresses. Returns an email address 
	 * stripped of everything but the address itself.
	 *
	 * @param string &$email, ex: "first_name last_name <email@example.com>"
	 * @return void
	 */
	function clean_email_address(&$email)
	{
		// clean out whitespace
		$email = trim($email);
		
		// look for angle braces
		$begin = strrpos($email, "<");
		$end = strrpos($email, ">");

		if ( $begin !== false ) 
		{
			// return whatever is between the angle braces
			$email = substr( $email, ($begin + 1), ($end - $begin - 1) );
		}
	}

	
	
	/**
	 * Makes a fake e-mail address out of an accurate one.
	 *
	 * Ex: (michael@example.com -> michaelATexampleDOTcom)
	 *
	 * @param string $email - e-mail to be faked
	 * @return string
	 */
	function mk_fake_email($email) 
	{
		$tempEmail = str_replace('.', ' DOT ', $email);
		$tempEmail = str_replace('@', ' AT ', $tempEmail);
		
		return $tempEmail;
	}

?>
