<?php

	/**
	 * Simple, but generally secure mail form script for cb_common
	 *
	 * Required field names: to, from_email, subject
	 * Optional field names: cc, bcc
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @version $Id: cb_formmail.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */


	// include some common code helpers
	$cb_root = dirname(__FILE__) . '/';
	include_once($cb_root . 'cb_common_alt.php');


	// check referrer
	// 	bad, alert
	$SERVER_NAME = get_ENV('SERVER_NAME');
	$HTTP_REFERER = get_SERVER('HTTP_REFERER');

	// bail if the referrer isn't on the same server as the host
	if ( !is_valid_referer($SERVER_NAME, $HTTP_REFERER) )
	{
		$error_msg = "Error - This script must be used from within '$SERVER_NAME' to prevent spam use.";

		alert_message($error_msg);
	}

	// check e-mail
	// 	bad, alert
	$to = get_REQUEST('to');
	$from_email = get_REQUEST('from_email');
	$cc = get_REQUEST('cc');
	$bcc = get_REQUEST('bcc');
		
	if ( !$to )
	{
		$error_msg = "Empty 'to:' field, please re-enter it.";

		alert_message($error_msg);
	}

	if ( !$from_email )
	{
		$error_msg = "Empty 'From E-mail:' field, please re-enter it.";

		alert_message($error_msg);
	}

	$required = get_REQUEST('required');

	if ( $required )
	{
		$required				= explode( ',', $required );
		$error_msg				= '';

		foreach ( $required as $key => $value )
		{
			if ( ! isset( $_REQUEST[ $value ] ) || '' == $_REQUEST[ $value ] )
			{
				$error_msg .= "Empty '$value' field, please complete it. ";
			}
		}

		if ( $error_msg )
		{
			alert_message($error_msg);
		}
	}

	// Put all addresses into a single string
	$to = str_replace(';', ',', $to);
	$all_email_addresses = $to;

	$from_email = str_replace(';', ',', $from_email);
	$all_email_addresses .= ',' . $from_email;

	if ( $cc )
	{
		$cc = str_replace(';', ',', $cc);
		$all_email_addresses .= ',' . $cc;
	}

	// always bcc sender
	if ( $bcc ) 
	{
		$bcc = $from_email . ',' . $bcc;
	}

	else 
	{
		$bcc = $from_email;
	}
	
	$bcc = str_replace(';', ',', $bcc);
	$all_email_addresses .= ',' . $bcc;

	// split address list into array
	$all_email_addresses = split(',', $all_email_addresses);

	foreach ($all_email_addresses AS $key => $value)
	{
		// clean addresses
		clean_email_address($value);

		// validate each address further here
		if ( is_blank($value) )
		{
			continue;
		}

		elseif ( !cb_is_email($value, !is_windows() ) )
		{
			$error_msg = "'$value' is not a valid e-mail address, please re-enter it.";

			alert_message($error_msg);
		}
	}

	// build e-mail
	$subject = get_REQUEST('subject');
	
	if ( !$subject )
	{
		$error_msg = "Empty 'Subject:' field, please re-enter it.";

		alert_message($error_msg);
	}

	$message_body = '';
	$pad_width = 18;
	$pad_string = '.';

	foreach ( $_REQUEST AS $key => $value )
	{
		// grab form pieces, clean them up, format, and append
		$value = trim_strip($value);
		$key = attribute2header($key);
		$key = str_pad($key, $pad_width, $pad_string);
		$message_body .= $key . ' ' . $value . "\r\n";
	}
	
	// build e-mail
	$mail = new htmlMimeMail();

	$mail->setFrom($from_email);
	$mail->setSubject($subject);
	$mail->setText($message_body);

	if ( !is_blank($cc) )
	{
		$mail->setCc($cc);
	}
		
	if ( !is_blank($bcc) )
	{
		$mail->setBcc($bcc);
	}
		
	// send it
	// 	bad, alert
	// echo mail2html($to, $subject, $message_body, $from_email);
	$result = $mail->send( str2arr($to) );

	if ( $result ) 
	{
		$sent_message = 'Your e-mail was successfully sent.';
		
		$thankyou = get_REQUEST('thankyou');

		if ( $thankyou )
		{
			header( "location: $thankyou" );
			exit();
		}

		alert_message($sent_message);
	}

	else 
	{
		$sent_message = 'An unknown error occured while attempting to
			send your e-mail. Please try again in a few moments.';
		
		alert_message($sent_message);
	} 

?>
