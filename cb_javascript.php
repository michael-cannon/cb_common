<?php

	/**
	 * Cannon BOSE's centralization of common JavaScript functions
	 *
	 * Copyright (C) 2002 Michael Cannon <michael@peimic.com>
	 * See full GNU Lesser General Public License in LICENSE.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: cb_javascript.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */



	/**
	 * Display's a Javascript alert box with $message as its contents and then
	 * returns the user to the page they came from.
	 *
	 * @param string $message text to be displayed
	 * @param boolean $goto_previous_page return user to previous page after
	 * 	alert()
	 * @return void
	 */
	function alert_message($message, $goto_previous_page = true)
	{
		$previous = ( $goto_previous_page ) 
			? 'history.go(-1);' 
			: '';

		$out = "<script language='javascript' text='text/javascript'>";
		$out .= "alert(\"$message\");$previous";
		$out .= '</script>';

		echo $out;

		exit();
	}



	/**
	 * Returns stirng containing Previous Page link.
	 *
	 * @todo have script get URL from history and that is the link via a header
	 * redirection option
	 *
	 * @param integer $page_count how many pages to go back.
	 * @return string
	 */
	function previous_page($page_count = -1)
	{
		$string = "<a href='javascript:history.go($page_count);'>Previous Page</a>";

		return $string;
	}

?>
