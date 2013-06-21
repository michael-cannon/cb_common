<?php

	/**
	 * Cannon BOSE's centralization of common string and text functions
	 *
	 * Copyright (C) 2002 Michael Cannon <michael@peimic.com>
	 * See full GNU Lesser General Public License in LICENSE.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: cb_string.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */



	/**
	 * Returns string of $attribute as a header.
	 *
	 * @param string $attribute table column header, ex: bulk_refund_id
	 * @return string
	 */
	function attribute2header($attribute)
	{
		// alias pretty much to str2header
		$header = str2header($attribute);
		
		return $header;
	}



	/**
	 * Returns string containing formatted $numeric using number_format.
	 *
	 * @see number_format()
	 *
	 * @param float $numeric
	 * @param integer $sig_fig decimal significant figure
	 * @param boolean $force_format force number_format() usage, 
	 * 	ex: true (1.9999 => 2.00), false (1.9999 => 1.9999), 
	 * 	true (1.5000 => 1.50)
	 * @param string $dec_point decimals point
	 * @param string $thousands_sep thousands separator
	 * @return string
	 */
	function cb_number_format($numeric, $force_format = true, $sig_fig = 2, 
		$dec_point = '.', $thousands_sep = ',')
	{
		$temp_numeric = number_format($numeric, $sig_fig, $dec_point, '');

		// number_format returns string with thousands separators, need to compare
		// numbers equivalently
		if ( $force_format || $temp_numeric == $numeric )
		{
			// go from *.0000 to *.00 or some like
			$numeric = number_format($numeric, $sig_fig, $dec_point, 
				$thousands_sep);
		}

		return $numeric;
	}



	/**
	 * Add escape characters to $string for CSV output.
	 *
	 * Presently checks for and escapes double quotes (") and $separator
	 * characters with double quotes.
	 *
	 * @param string $string text to be checked and escaped if needed
	 * @param string $separator CSV separator type, ex: ',', '|', "\t"
	 * @return string
	 */
	function csv_escape_strings($string, $separator = ',')
	{
		if ( is_string($string) )
		{
			// check for quotes
			$string = ( strpos($string, '"') !== FALSE ) 
				? str_replace('"', '""', $string)
				: $string;
			
			// remove Window's line breaks
			$string = ( strpos($string, "\r") !== FALSE ) 
				? str_replace("\r", '', $string)
				: $string;
			
			// check for line breaks
			$string = ( strpos($string, "\n") !== FALSE ) 
				? str_replace("\n", "\\n", $string)
				: $string;
			
			// check for commas
			$string = ( strpos($string, $separator) !== FALSE ) 
				? '"' . $string . '"'
				: $string;
		}

		return $string;
	}
	
	
	
	/**
	 * Creates new lines based upon console or web operations.
	 *
	 * @param integer $line_count number of new lines to create, ex: 1
	 * @return string
	 */
	function eol($line_count = 1)
	{
		// ensure valid line count
		if ( !is_int($line_count) )
		{
			$line_count = 1;
		}

		// is request from web or console
		$line_break = ( is_http_host() ) ? '<br />' : "\n";

		$out = '';

		// create $line_count line breaks
		for ($i = 1; $i <= $line_count; $i++)
		{
			$out .= $line_break;
		}

		return $out;
	}



	/**
	 * Prepend big numbers that are to be viewed in MS Excel with an apostrophe.
	 *
	 * @param mixed $number to be prepended, ex: 373278407401000
	 * @return mixed
	 */
	function excel_prepend($number)
	{
		if ( is_numeric($number) && 14 < strlen($number) )
		{
			$number = "'$number";
		}

		return $number;
	}



	/**
	 * Convert $filename contents into a string.
	 *
	 * @param string $filename, ex: README, index.html, http://www.example.com
	 * @return string
	 */
	function file2str($filename)
	{
		$tempContents = '';

		// get file and save as a line by line array
		$tempContents = file($filename);

		// convert array to string
		$tempContents = implode('', $tempContents);

		return $tempContents;
	}



	/**
	 * Create $separator typed CSV string from inputted $data.
	 *
	 * @param array/object $data information to be converted
	 * @param string $separator CSV separator type, ex: ',', '|', "\t"
	 * @return string
	 */
	function mk_csv_string($data, $separator = ',')
	{
		$csv = '';

		// check data's type
		if ( !is_object($data) && !is_array($data) )
		{
			// convert data to an array
			$data = array($data);
		}

		// loop through data
		foreach ( $data AS $key => $value )
		{
			// check for numeric types and prepend with ' if necessary
			if ( is_numeric($value) )
			{
				$value = excel_prepend($value);
			}

			// check for text escape line breaks and strings
			else
			{
				$value = csv_escape_strings($value, $separator);
			}

			// prepend $separator if information exists
			if ( !empty($csv) )
			{
				$csv .= $separator . $value;
			}

			else
			{
				$csv = $value;
			}
		}

		// complete line
		$csv .= "\n";

		return $csv;
	}
	
	
	
	/**
	 * Returns an empty textual string.
	 *
	 * @return string
	 */
	function mk_empty_string()
	{
		return '';
	}



	/**
	 * Convert a strings newlines to space for csv output.
	 *
	 * @param string $string to be modified
	 * @return string
	 */
	function nl2space($string)
	{
		$string = str_replace("\r", '', $string);
		$string = str_replace("\n", ' ', $string);

		return $string;
	}



	/**
	 * Convert spaces in $string to '&nbsp;'.
	 *
	 * @param string $string text, ex: "Now is the time for more fun"
	 * @return string
	 */
	function space2nbsp($string)
	{
		// validate $string by checking for space
		if ( !preg_match("/ /", $string ) )
		{
			return $string;
		}

		$string = ereg_replace(' ', '&nbsp;', $string);

		return $string;
	}


	
	/**
	 * Removes and trims whitespace characters from $string.
	 *
	 * @param string $string text to be cleaned
	 * @return string
	 */
	function str_rm_whitespace($string)
	{
		if ( is_string($string) )
		{
			// remove whitespace characters
			$string = preg_replace('/\s/', ' ', $string);

			// reduce multiple spaces to one
			$string = preg_replace('/ +/', ' ', $string);

			$string = trim($string);
		}

		return $string;
	}



	/**
	 * Converts $string into an array based upon $separator.
	 *
	 * @param string $string text to be broken into array
	 * @param string/array $separator string to denote $string break points, ex:
	 * 	array( ',', '|' ), ':'
	 * @return mixed array/boolean false if failure, $string not string or numeric
	 */
	function str2arr($string, $separator = ',')
	{
		// return now in case of bad input
		if ( !is_string($string) && !is_numeric($string) )
		{
			return false;
		}

		// convert $separator to array for foreach
		$separator = ( !is_array($separator) )
			? array($separator)
			: $separator;

		// cycle through $separator looking in $sting
		foreach ( $separator AS $index => $type )
		{
			// once type is found
			if ( !is_false(strpos($string, $type) ) )
			{ 
				// break $string into components 
				$new_array = explode($type, $string); 

				// clean up results
				foreach ( $new_array AS $key => $value )
				{
					$new_array[$key] = str_rm_whitespace($value);

					// remove empty indices
					if ( is_blank($new_array[$key]) )
					{
						unset($new_array[$key]);
					}
				}

				// return results
				return $new_array;
			}
		}

		return array($string);
	}
	
	
	
	/**
	 * Converts $text string to HTML suitable material for display.
	 *
	 * @param string $test to be converted to simple HTML display
	 * @return string
	 */
	function	str2html($text)
	{
		// trim
		$text = trim($text);

		// convert to HTML entities
		$text = htmlentities($text);

		// convert tabs
		$text = tab2space($text);

		// convert space
		$text = space2nbsp($text);

		// convert line breaks
		$text = nl2br($text);

		// return pseduo html
		return $text;
	}



	/**
	 * Returns similar to substr, but with $string processed by strval().
	 *
	 * @param string $string alphanumeric to be processed
	 * @param integer $start zero-based starting position
	 * @param integer $stop zero-based stoping position
entry	 * @return string
	 */
	function sub_str($string, $start, $stop = 0)
	{
		// if no default stopping point, stop at end of string
		if ( 0 == $stop )
		{
			$stop = strlen($string);
		}

		// substr -- Return part of a string
		// string substr ( string string, int start [', int length'])
		
		// strval -- Get string value of a variable 
		// string strval ( mixed var)
		return substr( strval($string), $start, $stop);
	}



	/**
	 * Convert tabs in $string to number of $spaces.
	 *
	 * @param string $string text, ex: "Now is the time\t for more\t fun"
	 * @param integer $spaces number of spaces per tab, ex: 3, 4, 0 (to remove)
	 * @return string
	 */
	function tab2space($string, $spaces = 3)
	{
		// validate $string and check for tabs
		if ( !preg_match("/\t/", $string ) )
		{
			return $string;
		}

		// validate $spaces
		$spaces = ( is_int($spaces) && $spaces > -1 )
			? $spaces
			: 3;

		$space = '';

		// build tab-space replacement
		for ( $i = 0; $i < $spaces; $i++ )
		{
			$space .= ' ';
		}

		$string = ereg_replace("\t", $space, $string);

		return $string;
	}



	/**
	 * Returns a string with $string removed, if $word is the first portion of
	 * $string.
	 *
	 * @param string $word word to be removed
	 * @param string $string text
	 * @return string
	 */
	function trim_first_word($word, $string)
	{
		// remove left side whitespace
		$string = ltrim($string);

		// if $word is the first part of $string remove it
		$string = preg_replace( "/^($word)/i", '', $string );

		$string = ltrim($string);
		
		return $string;
	}
	
	
	
	/**
	 * Word count of $string.
	 *
	 * @param string $string text
	 * @return mixed integer/boolean false if $string not string
	 */
	function wc($string)
	{
		$word_count = false;

		// type check string
		if ( is_string($string) )
		{
			// cb_common function, replaces the following three functions
			$string = str_rm_whitespace($string);

			// convert to array
			$string = explode(' ', $string);

			// get size of array, sub one for zero based counting
			$word_count = ( sizeof($string) - 1);
		}

		return $word_count;
	}



	/**
	 * Ensure numbers between 1 and 9 are prepended with 0.
	 *
	 * @param mixed $number numeric or array of numbers to check and prepend if
	 * 	needed.
	 * @return mixed
	 */
	function zero_prepend($number)
	{
		// is number numeric, less than 10, greater than -1
		if ( is_numeric($number) && -1 < $number && 10 > $number )
		{
			// does number have leading zero
			// $number is 1 to 9
			if ( 1 == strlen($number) )
			{
				$number = '0' . $number;
			}

			// prepend 0
			else if ( 0 == strlen($number) )
			{
				$number = '00';
			}
		}

		return $number;
	}



	/**
	 * Trim and stripslashes from a string.
	 *
	 * @param mixed content
	 * @param mixed
	 */
	function trim_strip($item)
	{
		$item = ( is_string($item) )
			? stripslashes( trim( $item ) )
			: $item;

		return $item;
	}



	/**
	 * Strips or encodes code in a string.
	 *
	 * @param mixed content
	 * @param boolean strip or encode
	 * @param mixed
	 */
	function strip_encode($item, $strip = true)
	{
		if ( is_string($item) )
		{
			$item = ( $strip )
				? strip_tags( $item ) 
				: htmlentities( $item );
		}

		return $item;
	}



	/**
	 * Convert boolean true/false to string true/false
	 *
	 * @param boolean value
	 * @return string
	 */
	function bool2str($boolean)
	{	
		return ( $boolean )
			? 'true'
			: 'false';
	}



	/**
	 * Swap values of passed in parameters.
	 *
	 * @param mixed item one
	 * @param mixed item two
	 * @return void
	 */
	function swap(&$item1, &$item2)
	{
		$temp = $item2;
		$item2 = $item1;
		$item1 = $temp;

		unset($temp);
	}

	

	/**
	 * Returns file system safe name of given input string.
	 *
	 * @param string Long aribtrary string
	 * @return string
	 */
	function str2filename($string)
	{
		// replace all characters that are not alphanumeric, hyphen, underline, or
		// period with an underscore
		$file_name = preg_replace('/[^([:alnum:]-_\.)]/', '_', $string);

		// remove extra underscores
		$file_name = preg_replace('/_+/', '', $file_name);

		$file_name = strtolower($file_name);

		return $file_name;
	}



	/**
	 * Returns string of a filename or string converted to a spaced extension
	 * less header type string.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @param string filename or arbitrary text
	 * @return mixed string/boolean
	 */
	function str2header($str)
	{
		if ( is_string($str) )
		{
			$clean_str = htmlspecialchars($str);

			// remove file extension
			$clean_str = preg_replace('/\.[[:alnum:]]+$/i', '', $clean_str);

			// remove funky characters
			$clean_str = preg_replace('/[^[:print:]]/', '_', $clean_str);

			// Convert camelcase to underscore
			$clean_str = camelcase2underscore($clean_str);

			// change underscore and periods to become space
			$clean_str = preg_replace('/(_|\.)/', ' ', $clean_str);

			// remove extra spaces
			$clean_str = preg_replace('/ +/', ' ', $clean_str);

			// remove beg/end spaces
			$clean_str = trim($clean_str);

			// capitalize
			$clean_str = ucwords($clean_str);

			return $clean_str;
		}

		return false;
	}



	/**
	 * Returns underscored string from camelCaseString like so camel_Case_String.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @param string arbitrary text
	 * @return mixed string/boolean
	 */
	function camelcase2underscore($str)
	{
		if ( is_string($str) )
		{
			$str = preg_replace('/([[:alpha:]][a-z]+)/', "$1_", $str);
			$str = preg_replace('/([[:digit:]]+)/', "$1_", $str);
			return $str;
		}

		return false;
	}

?>
