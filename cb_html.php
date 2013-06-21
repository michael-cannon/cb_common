<?php

	/**
	 * Cannon BOSE's centralization of common html functions
	 *
	 * Copyright (C) 2002 Michael Cannon <michael@peimic.com>
	 * See full GNU Lesser General Public License in LICENSE.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: cb_html.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */
	

	
	/**
	 * Send's $data to browser for downloading.
	 *
	 * @param string $filename $data's name of file, ex: 'data.csv'
	 * @param mixed $data $filename contents, ex: arr2cvs($data)
	 * @return void
	 */
	function browser_download($filename, $data)
	{
		$size = strlen($data);
		
		// header("Cache-Control: private");
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=$filename");
		header("Content-Length: $size");

		echo $data;
	}



	/**
	 * Returns pretty printed e-mail contents for display to screen.
	 *
	 * @param string $to e-mail recipient
	 * @param string $subject e-mail subject
	 * @param string $message e-mail message body
	 * @param string $mail_header e-mail mail headers
	 * @return string
	 */
	function mail2html($to, $subject, $message = '', $mail_header = '')
	{
		$to = str2html($to);
		$subject = str2html($subject);
		$mail_header = str2html($mail_header);
		$message = str2html($message);

		$out = "
			<p><b>E-mail Sent:</b></p>
			<p>To: $to<br />
			$mail_header<br />
			Subject: $subject</p>
			$message</p>
		";

		return $out;
	}



	/**
	 * Returns a string containing an <input> list.
	 *
	 * @param string $name <input> name property, ex: 'state', 'country'
	 * @param array $item_array, zero or associatived based array
	 * @param string type input type, ex: radio, checkbox
	 * @param mixed array/string $selected_item key value to be preselected in 
	 * 	option list
	 * @return string
	 */
	function mk_input_list($name, $item_array, $type = 'checkbox', 
		$selected_item = false)
	{
		$out = '';
		
		if ( !is_array($item_array) )
		{
			$item_array = array($item_array);
		}

		if ( !is_array($selected_item) )
		{
			$selected_item = array($selected_item);
		}

		$selected_item = array_values($selected_item);

		$indices = ( 'radio' == $type )
			? ''
			: '[]';

		foreach ( $item_array AS $key => $value )
		{
			$out .= "
				<input type='$type' name='{$name}$indices' value='$key' ";
	 
			if ( in_array($key, $selected_item) )
			{
				$out .= "checked='checked' ";
			}
	 
			$out .= "/>$value<br />";
		}

		
		return $out;
	}



	/**
	 * Create an HTML link.
	 *
	 * @param string $href relative, absolute, or other kind of link, 
	 * 	ex: 'a.php', 'http://peimic.com'
	 * @param string $text link text to display, ex: 'Home'
	 * @param string $target page target, ex: '_blank', '_parent'
	 * @param string $name link name, ex: 'top', 'Barry_Manilow'
	 * @param string $title alternative description, ex: 'Link to top of page'
	 * @return string
	 */
	function mk_link($href, $text = '', $target = '', $name = '', $title = '')
	{
		$out = '<a';

		$out .= ( !is_blank($name) ) ? " name='$name'" : '';
		$out .= ( !is_blank($href) ) ? " href='$href'" : '';
		$out .= ( !is_blank($target) ) ? " target='$target'" : '';
		$out .= ( !is_blank($title) ) ? " title='$title'" : '';
		$out .= '>';

		$out .= ( '' != $text ) ? $text : $href;
		$out .= '</a>';

		return $out;
	}



	/**
	 * Create drop down list from $item_array.
	 *
	 * @param array $item_array, zero or associatived based array
	 * @param string $selected_item key value to be preselected in option list
	 * @return string
	 */
	function mk_option_list($item_array, $selected_item = false)
	{
		$out = '';

		if ( !is_array($item_array) )
		{
			$item_array = array($item_array);
		}

		foreach($item_array AS $key => $value)
		{
			$out .= "<option value='$key' ";
	 
			if ($selected_item == $key)
			{
				$out .= "selected='selected' ";
			}
	 
			$out .= ">$value</option>\n";
		}

		return $out;
	}
	
	
	
	/**
	 * Create <SELECT> drop down list from $item_array.
	 *
	 * @param string $name <SELECT> name property, ex: 'state', 'country'
	 * @param array $item_array, zero or associatived based array
	 * @param string $selected_item key value to be preselected in option list
	 * @return string
	 */
	function mk_select_list($name, $item_array, $selected_item = false)
	{
		$out = '';

	 	$out .= "<select name='$name'>\n";
	 	$out .= mk_option_list($item_array, $selected_item);
	 	$out .= "</select>\n";

		return $out;
	}


	/**
	 * Returns string containting phpWS admin login link.
	 *
	 * @param string admin link content
	 * @return string
	 */
	function mk_phpws_admin_link($content = 'Site admin')
	{
		global $phpws_url;

		$link = "$phpws_url/admin.php";

		$string = mk_link($link, $content);

		return $string;
	}

?>
