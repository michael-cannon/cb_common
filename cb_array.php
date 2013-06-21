<?php

	/**
	 * Cannon BOSE's centralization of common array functions
	 *
	 * Copyright (C) 2002 Michael Cannon <michael@peimic.com>
	 * See full GNU Lesser General Public License in LICENSE.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: cb_array.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */



	/**
	 * Converts $array to comma separated value string
	 *
	 * @param array $array, ex: array(1, 23, 92, 100)
	 * @param boolean $encapsulate back-quote contents, ex: true (`), false
	 * @param boolean $pretty apply array_unique, sort, and clean up to $array
	 * @return string, ex: 1, 23, 92, 100
	 */
	function arr2csv($array, $encapsulate = false, $pretty = false)
	{
		$array = ( is_array($array) )
			? $array
			: array($array);

		if ( $pretty )
		{
			$array = array_unique($array);
			sort($array);
		}

		$string = '';

		if ( !$encapsulate )
		{
			$string .= implode(', ', $array);
		}

		else
		{
			$string .= "'";
			$string .= implode("', '", $array);
		}

		if ( $encapsulate )
		{
			$string .= "'";
		}

		if ( $pretty )
		{
			$string = preg_replace('/^, /', '', $string);
		}

		return $string;
	}



	/**
	 * Returns unique values of $array1 and $array2 merge.
	 *
	 * @param array $array1
	 * @param array $array2
	 * @return array
	 */
	function array_union($array1, $array2)
	{
		$array = array_merge($array1, $array2);
		$array = array_unique($array);

		return $array;
	}



	/**
	 * Returns an array containing the values of $attribute from within $array.
	 *
	 * @param string $attribute array key
	 * @param array $array indexed-associative array
	 * @return array
	 */
	function array_value_item($attribute, $array)
	{
		$value = array();
				
		foreach ( $array AS $key => $data )
		{
			if ( array_key_exists($attribute, $data) )
			{
				$value[] = $data[$attribute];
			}
		}

		return $value;
	}



	/**
	 * Applies zero prepend to each $value of $array if needed.
	 *
	 * @param array/string $array array containing numeric information
	 * @return mixed array/boolean false if unsuccesful
	 */
	function array_zero_prepend($array)
	{
		$out = array(
			'function'		=> 'array_zero_prepend',
			'array'			=> $array
		);

		// type check
		if ( !is_array($array) )
		{
			return false;
		}

		// ensure numbers less than ten are prepended with 0
		foreach ( $array AS $key => $value )
		{
			$array[$key] = zero_prepend($value);
		}

		return $array;
	}



	/**
	 * Removes and trims whitespace characters from $array.
	 *
	 * @param array $array text to be cleaned
	 * @return array
	 */
	function arr_rm_whitespace($array)
	{
		foreach ( $array AS $key => $value )
		{
			// str_rm_whitespace checks for string type
			// if it's a string type, then remove whitespace
			$array[$key] = str_rm_whitespace($value);
		}

		return $array;
	}



	/**
	 * Converts $str_num, string or numeric, to an array.
	 *
	 * @param string $str_num string or numeric
	 * @return array
	 */
	function str_num2arr($str_num)
	{
		$out = array(
			'function'		=> 'str_num2arr',
			'str_num'		=> $str_num
		);

		return ( is_string($str_num) || is_numeric($str_num) )
			? array($str_num)
			: $str_num;
	}
	
	
	
	/**
	 * Cleanse array values with stripslashes and htmlentities.
	 *
	 * @param array $array to be traversed and safed
	 * @return void
	 */
	function safe_user_input(&$array, $safe_keys = null)
	{
		$array = ( is_array($array) )
			? $array
			: array();

		foreach($array AS $key => $value)
		{
			if ( $safe_keys && !in_array($key, $safe_keys) )
			{
				// remove potentially unsafe or bogus key/value pair
				// echo '<br />good bye ' . $key . ' : ' . $value;
				unset($array[$key]);
			}

			elseif ( !is_object($value) && !is_array($value) )
			{
				$value = trim_strip($value);
				// $value = htmlentities($value);
				// $value = strip_tags($value);
				$array[$key] = $value;
			}
		}
	}



	/**
	 * Returns mixed type depending upon $key _ENV type.
	 *
	 * @param string $_ENV key name
	 * @param mixed false return value
	 * @param boolean strip or encode
	 * @return mixed
	 */
	function get_ENV($key, $return = false, $strip = true)
	{
		return get_predefined_variable($key, $return, '_ENV', $strip);
	}



	/**
	 * Returns mixed type depending upon $key _GET type.
	 *
	 * @param string _GET key name
	 * @param mixed false return value
	 * @param boolean strip or encode
	 * @return mixed
	 */
	function get_GET($key, $return = false, $strip = true)
	{
		return get_predefined_variable($key, $return, '_GET', $strip);
	}



	/**
	 * Returns mixed type depending upon $key _POST type.
	 *
	 * @param string _POST key name
	 * @param mixed false return value
	 * @param boolean strip or encode
	 * @return mixed
	 */
	function get_POST($key, $return = false, $strip = true)
	{
		return get_predefined_variable($key, $return, '_POST', $strip);
	}



	/**
	 * Returns mixed type depending upon $key _SESSION type.
	 *
	 * @param string _SESSION key name
	 * @param mixed false return value
	 * @param boolean strip or encode
	 * @return mixed
	 */
	function get_SESSION($key, $return = false, $strip = true)
	{
		return get_predefined_variable($key, $return, '_SESSION', $strip);
	}
	
	
	
	/**
	 * Returns mixed type depending upon $key _COOKIE type.
	 *
	 * @param string _COOKIE key name
	 * @param mixed false return value
	 * @param boolean strip or encode
	 * @return mixed
	 */
	function get_COOKIE($key, $return = false, $strip = true)
	{
		return get_predefined_variable($key, $return, '_COOKIE', $strip);
	}
	
	
	
	/**
	 * Returns mixed type depending upon $key _FILES type.
	 *
	 * @param string _FILES key name
	 * @param mixed false return value
	 * @return mixed
	 */
	function get_FILES($key, $return = false)
	{
		return get_predefined_variable($key, $return, '_FILES');
	}
	
	
	
	/**
	 * Returns mixed type depending upon $key _REQUEST type.
	 *
	 * @param string _REQUEST key name
	 * @param mixed false return value
	 * @param boolean strip or encode
	 * @return mixed
	 */
	function get_REQUEST($key, $return = false, $strip = true)
	{
		return get_predefined_variable($key, $return, '_REQUEST', $strip);
	}
	
	
	
	/**
	 * Returns scalar type depending upon defined $key.
	 *
	 * @param string SERVER key name
	 * @param mixed false return value
	 * @param boolean strip or encode
	 * @return mixed
	 */
	function get_defined($key, $return = false, $strip = true)
	{
		return get_predefined_variable($key, $return, 'defined', $strip);
	}
	
	
	
	/**
	 * Returns mixed type depending upon $key SERVER type.
	 *
	 * @param string SERVER key name
	 * @param mixed false return value
	 * @param boolean strip or encode
	 * @return mixed
	 */
	function get_SERVER($key, $return = false, $strip = true)
	{
		return get_predefined_variable($key, $return, '_SERVER', $strip);
	}



	/**
	 * Returns mixed type depending upon $key GLOBALS type.
	 *
	 * @param string GLOBALS key name
	 * @param mixed false return value
	 * @param boolean strip or encode
	 * @return mixed
	 */
	function get_GLOBALS($key, $return = false, $strip = true)
	{
		return get_predefined_variable($key, $return, 'GLOBALS', $strip);
	}



	/**
	 * Alias for get_GLOBALS()
	 *
	 * @param string GLOBALS key name
	 * @param mixed false return value
	 * @param boolean strip or encode
	 * @return mixed
	 */
	function get_global($key, $return = false, $strip = true)
	{
		return get_GLOBALS($key, $return, $strip);
	}



	/**
	 * Returns predefined variable mixed value depending.
	 *
	 * @param string GLOBALS key name
	 * @param mixed false return value
	 * @param string PHP array type name
	 * @param boolean strip or encode
	 * @return mixed
	 */
	function get_predefined_variable($key, $return = false, $type = 'GLOBALS',
		$strip = true)
	{
		if ( 'GLOBALS' != $type && 'defined' != $type )
		{
			$item = ( isset($GLOBALS[$type][$key]) )
				? $GLOBALS[$type][$key]
				: $return;
		}
		
		elseif ( 'defined' == $type )
		{
			$defined = get_defined_constants();

			$item = ( isset($defined[$key]) )
				? $defined[$key]
				: $return;
		}
		
		else
		{
			$item = ( isset($GLOBALS[$key]) )
				? $GLOBALS[$key]
				: $return;
		}
		
		$item = trim_strip($item);
		$item = strip_encode($item, $strip);

		return $item;
	}



	/**
	 * Convert base object with members to an array with keys of same name as
	 * object members.
	 *
	 * @param object instance to be converted
	 * @return array, empty if $object not object
	 */
	function obj2arr($object)
	{
		$array = array();

		if ( is_object($object) )
		{
			foreach ( $object AS $key => $value )
			{
				$array[$key] = $value;
			}
		}

		return $array;
	}

?>
