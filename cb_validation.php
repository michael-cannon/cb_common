<?php

	/**
	 * Cannon BOSE's centralization of common validation functions
	 *
	 * Copyright (C) 2002 Michael Cannon <michael@peimic.com>
	 * See full GNU Lesser General Public License in LICENSE.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: cb_validation.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */



	/**
	 * Returns boolean depending upon type and value of $item.
	 *
	 * Warning from PHP.net: 
	 * -1 is considered TRUE, like any other non-zero (whether negative or
	 * positive) number!
	 *
	 * @param mixed $item
	 * @return boolean
	 */
	function cb_bool($item)
	{
		$true_strings = array('y', 'yes', 'true');
		$false_strings = array('n', 'no', 'false');

		// $item string then do special Y/N/Yes/No comparison checking
		if ( is_string($item) )
		{
			// check string contents
			if ( in_array(strtolower($item), $true_strings) )
			{
				return true;
			}

			elseif ( in_array(strtolower($item), $false_strings) )
			{
				return false;
			}
		}
			
		// otherwise let PHP use natural way to figure out true/false of item
		// use internal PHP type casting
		return (bool) $item;
	}



	/**
	 * Returns boolean depending upon $alpha containing only alphas.
	 *
	 * @param alpha $alpha text sequence, ex: 'abcd' (true), 'a9df' (false)
	 * @return boolean
	 */
	function is_alpha2($alpha)
	{
		return ( preg_match('/^[[:alpha:]]+$/', $alpha) ) 
			? true 
			: false;
	}



	/**
	 * Returns boolean depending upon $alnum containing [:alpha:] or [:digit:].
	 *
	 * @param alnum $alnum text sequence, ex: 'abcd' (true), 'a9df' (true)
	 * @return boolean
	 */
	function is_alnum($alnum)
	{
		return ( preg_match('/^[[:alnum:]]+$/', $alnum) ) 
			? true 
			: false;
	}



	/**
	 * Returns boolean depending upon $string being empty or blank.
	 *
	 * @param string $string text sequence, ex: '', 'Now is the...'
	 * @return boolean
	 */
	function is_blank($string)
	{
		return ( '' == $string ) 
			? true 
			: false;
	}



	/**
	 * Returns boolean depending upon $digit containing only digits.
	 *
	 * @param digit $digit text sequence, ex: '1234', 1234
	 * @return boolean
	 */
	function is_digit($digit)
	{
		return ( preg_match('/^[[:digit:]]+$/', $digit) ) 
			? true 
			: false;
	}



	/**
	 * Verify that $email meets requirements specified by regular expression. 
	 * Store various parts in $check_pieces array and then checks to see that
	 * the top level domain is valid, but not the username itself.
	 *
	 * strstr() returns all of first parameter found after second parameter.
	 * substr() returns all of the string found between the first and second 
	 * parameters.
	 * getmxrr() verifies that domain MX record exists.
	 * checkdnsrr() checks DNS's not MX'd.
	 *
	 * Resource:
	 * 1. Gilmore, W.J. PHP Networking. April 5, 2001.  
	 *    http://www.onlamp.com/lpt/a//php/2001/04/05/networking.html.
	 *    wj@wjgilmore.com.
	 *
	 * @param string $email
	 * @param boolean $check_mx verify mail exchange or DNS records
	 * @return boolean true if valid e-mail address
	 */
	function cb_is_email($email, $check_mx = true)
	{
		// all characters except @ and whitespace
		$name = '[^@\s]+';

		// letters, numbers, hyphens separated by a period
		$sub_domain = '[-a-z0-9]+\.'; 

		// country codes
		$cc = '[a-z]{2}';

		// top level domains
		$tlds =
		"$cc|com|net|edu|org|gov|mil|int|biz|pro|info|arpa|aero|coop|name|museum";

		$email_pattern = "/^$name@($sub_domain)+($tlds)$/ix";

		if ( preg_match($email_pattern, $email, $check_pieces) )
		{
			// check mail exchange or DNS
			if ( $check_mx )
			{
				$host = substr(strstr($check_pieces[0], '@'), 1).".";
			
				if ( getmxrr($host, $validate_email_temp) )
				{ 
					return true;
				}
				  
				// THIS WILL CATCH DNSs THAT ARE NOT MX. 
				if ( checkdnsrr($host, 'ANY') )
				{
					return true;
				}
			}

			// e-mail regex pass, then assume valid without MX check
			else
			{
				return true;
			}
		}
		 
		return false;
	}



	/**
	 * Returns boolean depending upon $variable being false or not.
	 *
	 * @param variable $variable text sequence, ex: false, 'Now is the...'
	 * @return boolean
	 */
	function is_false($variable)
	{
		if ( !is_array($variable) )
		{
			return ( false === $variable || preg_match('/^false$/i', $variable) 
				&& !is_numeric($variable) )
				? true 
				: false;
		}

		return false;
	}



	/**
	 * Returns boolean depending upon if $HTTP_HOST exists.
	 *
	 * @return boolean
	 */
	function is_http_host()
	{
		return ( isset($_SERVER['HTTP_HOST']) || 
			isset($HTTP_SERVER_VARS['HTTP_HOST']) )
			? true
			: false;
	}


	
	/**
	 * Returns boolean depending upon $variable being true or not.
	 *
	 * @param variable $variable text sequence, ex: true, 'Now is the...'
	 * @return boolean
	 */
	function is_true($variable)
	{
		if ( !is_array($variable) )
		{
			return ( true === $variable || preg_match('/^true$/i', $variable) 
				&& !is_numeric($variable) )
				? true 
				: false;
		}

		return false;
	}



	/**
	 * Checks for illegal referer call to prevent outside use of the script 
	 * for spamming. The script simply checks to see that $HTTP_REFERER contains
	 * text from $valid_site_domain.
	 *
	 * @param string/array $valid_site_domain, ex: 'example.com'
	 * @param string/array $HTTP_REFERER, ex: 'http://example.com/asdf.php'
	 * @return boolean
	 */
	function is_valid_referer($valid_site_domain, $HTTP_REFERER) 
	{
		if ( preg_match('/,/', $valid_site_domain) )
		{
			$valid_site_domain = str2arr($valid_site_domain);
		}

		if ( !is_array($valid_site_domain) )
		{
			$valid_site_domain = array($valid_site_domain);
		}

		foreach ( $valid_site_domain AS $key => $value )
		{
			// domain found in referer
	   	if ( preg_match("/$value/i", $HTTP_REFERER) )
			{
				return true;
			}
		}
	   
		// domain not found in referer
		return false;
	}
	
	
	
	/** 
	 * Returns booleans depending upon machine OS.
	 *
	 * @return boolean
	 */
	function is_windows()
	{
		// look at $_ENV['OS']
		// is this running on a Windows machine
		if ( isset($_ENV['OS']) && preg_match('/window/i', $_ENV['OS']) )
		{
			return true;
		}

		else
		{
			return false;
		}
	}



	/**
	 * Returns boolean depending upon whether assumed mysql timestamp or not.
	 *
	 * @param integer mysql timestamp
	 * @boolean success or not
	 */
	function is_mysqltime($mysql)
	{
		return ( is_numeric($mysql) && 14 == strlen($mysql) );
	}



	/**
	 * Returns boolean depending upon whether unix timestamp or not.
	 *
	 * @param integer unix timestamp
	 * @boolean success or not
	 */
	function is_unixtime($unix)
	{
		$earliest = time_unix_earliest();
		$latest = time_unix_latest();

		return ( is_numeric($unix) && between($unix, $earliest, $latest) );
	}

?>
