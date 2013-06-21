<?php

	/**
	 * Debug message reporting tools
	 *
	 * Copyright (C) 2002 Michael Cannon <michael@peimic.com>
	 * See full GNU Lesser General Public License in LICENSE.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: debug_msg.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $ 
	 */



	/**
	 * Add $key $value to debug global $CB_DEBUG_MSG for display or logging.
	 *
	 * @param $key string message text
	 * @param $value mixed $key contents
	 * @return void
	 */
	function debug_add($key, $value = null)
	{
		global $CB_DEBUG_MSG;

		if ( isset($CB_DEBUG_MSG) )
		{
			if ( null != $value )
			{
				$CB_DEBUG_MSG[$key] = $value;
			}

			else
			{
				$CB_DEBUG_MSG[] = $key;
			}
		}

		else
		{
			$CB_DEBUG_MSG = ( null != $value )
				? array($key => $value)
				: array($key);
		}
	}



	/**
	 * Add's $variable contents to $CB_DEBUG_MSG.
	 *
	 * @param mixed array/string $variable member names
	 * @return void
	 */
	function debug_addm($variable)
	{
		if ( !is_array($variable) )
		{
			$variable = str2arr($variable);
		}

		foreach ( $variable AS $value )
		{
			global $$value;

			debug_add($value, $$value);
		}
	}
	
	
	
	/**
	 * E-mail's $CB_DEBUG_MSG contents to $send_to.
	 *
	 * @param string $send_to e-mail recipient
	 * @return void
	 */
	function debug_email($send_to)
	{
		global $CB_DEBUG_MSG;
		global $script_owner_email;

		$script_owner_email = $send_to;

		// e-mail $CB_DEBUG_MSG to script owner
		debug_msg($CB_DEBUG_MSG, false, true);
	}



	/**
	 *
	 * Outputs messages from method call to screen and possible e-mail
	 *
	 * @param string $msg to be outputted, ex: 'Now is the time of'
	 * @param boolean $var_dump use PHP's var_dump() instead of print_r()
	 * @param boolean $send_email e-mail $msg to $email
	 * @return void
	 */
	function debug_msg($msg, $var_dump = false, $send_email = false)
	{
		// show debug messages or not
		global $debug_msg_on;
		global $script_owner_email;

		// don't continue if neither are set
		if ( !$debug_msg_on && !$send_email )
		{
			return;
		}

		// get the variables using PHP's output buffering and own human readable
		// print functions
		ob_start();

	 	echo_ln($msg, $var_dump);

		$msg = ob_get_contents();
		
		ob_end_clean();

		// display error
		if ( $debug_msg_on )
		{
			// fancy printing
			$out = "debug_msg: $msg";

			echo $out;
			flush();
		}

		// e-mail error
		if ( $send_email && $script_owner_email )
		{
			$rtn = "\n"; 
			$date = date('M j, Y H:i:s');
			$subject = "CB_DEBUG_MSG::";
			$subject .= $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
			$subject .= " ($date)";

			$out = $msg . $rtn;
			$out .= php_variables(true);

			mail($script_owner_email, $subject, $out);
		}
	}



	/**
	 * Turns off full PHP error reporting and debug_msg_on.
	 *
	 * @param boolean $show_msg display function off message
	 * @return void
	 */
	function debug_off($show_msg = true)
	{
		global $dev_mode;
		$dev_mode = false;

		global $debug_msg_on;

		error_reporting(5);

		if ( $show_msg )
		{
			debug_msg('debug_off()');
		}
		
		$debug_msg_on = false;
	}



	/**
	 * Turns on full PHP error reporting and debug_msg_on.
	 *
	 * @param boolean $show_msg display function on message
	 * @param string $email script owner's email address
	 * @return void
	 */
	function debug_on($show_msg = true, $email = 'mcannon@intercosmos.com')
//	function debug_on($show_msg = true, $email = false)
	{
		global $debug_msg_on;
		$debug_msg_on = true;
		
		global $dev_mode;
		$dev_mode = 1;

		global $script_owner_email;
		$script_owner_email = $email;

		error_reporting(E_ALL);
		// error_reporting(E_ALL ^ E_NOTICE);

		if ( $show_msg )
		{
			debug_msg('debug_on()');
		}
	}



	/**
	 * Display $CB_DEBUG_MSG contents.
	 *
	 * @see debug_reset()
	 * @param boolean $reset call debug_reset()
	 * @return void
	 */
	function debug_out($reset = true)
	{
		global $CB_DEBUG_MSG;

		debug_msg($CB_DEBUG_MSG);

		if ( $reset )
		{
			debug_reset();
		}
	}



	/**
	 * Reset $CB_DEBUG_MSG contents.
	 *
	 * @return void
	 */
	function debug_reset()
	{
		global $CB_DEBUG_MSG;

		$CB_DEBUG_MSG = array();
	}



	/**
	 * Creates a new line string out of $variable.
	 *
	 * @param string $msg to be outputted, ex: 'Now is the time of'
	 * @param boolean $var_dump use PHP's var_dump() instead of print_r()
	 * @return void
	 */
	function echo_ln($msg = '', $var_dump = false)
	{
		// get the variables using PHP's output buffering and own human readable
		// print functions
		ob_start();

		( !$var_dump ) 
			? print_r($msg) 
			: var_dump($msg);

		$msg = ob_get_contents();
		
		ob_end_clean();

		// turn obnoxious 8 space tabs into 3 spaces
	 	$msg = tab2space($msg);
		$msg = $msg . eol();
		$msg = "<pre>$msg</pre>";

		echo $msg;
	}



	/**
	 * Checks to see if $result (0/1/true/false) fails, if it does it 
	 * sends a debug message to the script owner with some hopefully helpful 
	 * debug information. 
	 *
	 * @param mixed $result from just ran $method_name($param)
	 * @param string $method_name of function being checked
	 * @param mixed $param parameters to passed to $method_name
	 * @param mixed $msg message to send with debug message
	 * @return boolean true if method result is 1 or true
	 */
	function error_catcher($result, $method_name, $param = '', $msg = '')
	{
		$out = array(
			'method_name'		=> $method_name,
			'param'				=> $param,
			'result'				=> $result,
			'msg'					=> $msg
		);

		// see if function succeeds
		if ( !$result )
		{
			$out[] = array(
				'FAILURE'			=> "$method_name FAILED"
			);

			// e-mail $out to script owner
			debug_msg($out, true, true);

			return false;
		}

		return true;
	}



	/**
	 * Creates a key => value pairing from inputted array.
	 *
	 * @param array $array to be broken down
	 * @param string $array_name
	 * @param string $eol type of end of line character to use
	 * @return string
	 */
	function get_key_value($array, $array_name = '', $eol = "\n")
	{
		$out = "$eol$eol$array_name:$eol";
		
		// print functions
		ob_start();

		print_r($array);

		$array = ob_get_contents();
		
		ob_end_clean();

		// turn obnoxious 8 space tabs into 3 spaces
		$array = tab2space($array);
		
		// fix line ends
		$array = ereg_replace("\r", '', $array);
		$array = ereg_replace("\n", $eol, $array);

		return $out . $array;
	}



	/**
	 * Returns string containing a radio input list of $functions as options.
	 *
	 * @param mixed string/array $functions text list of function and parameters
	 * 	ex: 'foo', array('foo' => 'int, string', 'goo' => 'boolean', 'rue')
	 * @return string
	 */
	function mk_method_input_list($functions)
	{
		$input = '
			<br />
			<dt>Methods</dt>
		';

		$functions = ( is_array($functions) )
			? $functions
			: array($functions);

		foreach ( $functions AS $key => $value )
		{
			$input .= "
				<dd>
					<input type='radio' name='method' value='$key' />
					$key ($value)
				</dd>
			";
		}

		$input .= '<br />';

		return $input;
	}



	/**
	 * Returns microtime for now.
	 *
	 * @return integer
	 */
	function mt_now()
	{
		$mt_array = split( ' ', microtime() ); 

		return  ( $mt_array[0] + $mt_array[1] ); 
	}



	/**
	 * Returns string of all global PHP Variables.
	 *
	 * @param boolean $show_globals include $GLOBALS array, ex: true, false
	 * @return string
	 */
	function php_variables($show_globals = false)
	{
		$out = "\n\nAll PHP Global & Local Script Variables";

		if ( !$show_globals )
		{
			if ( isset($_SERVER) )
			{
				$out .= get_key_value($_SERVER, '_SERVER');
			}

			if ( isset($_ENV) )
			{
				$out .= get_key_value($_ENV, '_ENV');
			}

			if ( isset($_COOKIE) )
			{
				$out .= get_key_value($_COOKIE, '_COOKIE');
			}

			if ( isset($_GET) )
			{
				$out .= get_key_value($_GET, '_GET');
			}

			if ( isset($_POST) )
			{
				$out .= get_key_value($_POST, '_POST');
			}

			if ( isset($_FILES) )
			{
				$out .= get_key_value($_FILES, '_FILES');
			}
			
			if ( isset($_REQUEST) )
			{
				$out .= get_key_value($_REQUEST, '_REQUEST');
			}

			if ( isset($_SESSION) )
			{
				$out .= get_key_value($_SESSION, '_SESSION');
			}

			if ( isset($php_errormsg) )
			{
				$out .= get_key_value($php_errormsg, 'php_errormsg');
			}
		}

		elseif ( isset($GLOBALS) )
		{
			$out .= get_key_value($GLOBALS, 'GLOBALS');
		}

		return $out;
	}



	/**
	 * Standard test_menu and submission form.
	 *
	 * @param mixed string/array $functions text list of function names to make
	 * 	available for testing, ex: 'foo', array('foo', 'goo', 'rue')
	 * @return void
	 */
	function test_form($functions = false)
	{
		global $debugger;
		global $method;
		global $show_var;
		global $parameter;
		global $parameter2;
		global $set_time_limit;

		echo test_menu($functions);

		if ( isset($method) && function_exists($method) )
		{
			set_time_limit($set_time_limit);

			$debugger(false);

			waid('BEGIN: ' . $method);

			$show_var = ( isset($show_var) ) ? $show_var : false;

			if ( !$parameter2 )
			{
				echo "<h3>$method($parameter)</h3>";
				debug_msg( $method($parameter), $show_var );
			}

			else
			{
				echo "<h3>$method($parameter, $parameter2)</h3>";
				debug_msg( $method($parameter, $parameter2), $show_var );
			}

			waid('END: ' . $method);

			debug_off(false);
		}
	}



	/**
	 * Standard test menu form.
	 *
	 * @param mixed string/array $functions text list of function names to make
	 * 	available for testing, ex: 'foo', array('foo', 'goo', 'rue')
	 * @return string
	 */
	function test_menu($functions = false)
	{
		$method = ( isset($_POST['method']) )
			? $_POST['method']
			: '';

		$input = "
			<dd>
				<input type='text' name='method' value='$method' /> (PARAMETER_LIST)
			</dd>\n
		";

		if ( $functions && ( is_string($functions) || is_array($functions) ) )
		{
			$input .= mk_method_input_list($functions);
		}

		$out = "
			<form method='post' action='"
				. $_SERVER['PHP_SELF'] 
				. "'>
				<dl>
					$input

					<dt>Parameter</dt>
						<dd>
							<input type='text' name='parameter' value='" 
							. $_POST['parameter']
							. "' onclick='javascript:this.value=\"\"' />
						</dd>
						<dd>
							<input type='text' name='parameter2' value='" 
							. $_POST['parameter2']
							. "' onclick='javascript:this.value=\"\"' />
						</dd>

					<dt>Show Debug Messages</dt>
						<dd>
							<input type='radio' name='debugger' value='debug_on'
								checked='checked' />
							Yes
							<input type='radio' name='debugger' value='debug_off' />   
							No
							<input type='checkbox' name='show_var' value='true' />
							Var dump?
						</dd>

					<dt>Set Time Limit</dt>
						<dd>
							<input type='text' name='set_time_limit' value='30' /> 
						</dd>

					<dt>Stop at Debug Count</dt>
						<dd>
							<input type='text' name='debug_stop_count' value='" 
							.  $_POST['debug_stop_count'] 
							.  "' onclick='javascript:this.value=\"\"' /> 
						</dd>
				</dl>
				<input type='submit' name='submit' />
				<input type='reset' name='reset' />
			</form>
		";

		return $out;
	}



	/**
	 * Outputs $function_name and timer as a little 'what am I doing' telltale.
	 *
	 * @param string $function_name name of method being used, ex: def_get_time
	 * @return void
	 */
	function waid($function_name)
	{
		// get time for last function run
		global $debug_method_time;
		global $debug_total_time;

		// set now and debug_method_time to same if debug_method_time hasn't been established
		// yet
		if ( isset($debug_method_time) )
		{
			$now = mt_now();
		}

		else
		{
			$debug_total_time = $debug_method_time = $now = mt_now();
		}

		debug_msg(
			eol()
			. 'time elasped: ' . number_format(($now - $debug_method_time), 3) 
			. ' seconds'
			. eol()

			. 'total time elasped: ' . number_format(($now - $debug_total_time ), 			3) . ' seconds'
			. eol()

			. 'function: <b>' . $function_name . '</b>'
			. eol()
		);

		// update for next query
		$debug_method_time = $now;

		flush();
	}

?>
