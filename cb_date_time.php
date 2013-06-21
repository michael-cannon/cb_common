<?php

	/**
	 * Cannon BOSE's centralization of common date and time functions
	 *
	 * Copyright (C) 2002 Michael Cannon <michael@peimic.com>
	 * See full GNU Lesser General Public License in LICENSE.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: cb_date_time.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */


	
	define('SECOND_IN_SECONDS', 1);
	define('MINUTE_IN_SECONDS', ( 60 * SECOND_IN_SECONDS ) );
	define('HOUR_IN_SECONDS', ( 60 * MINUTE_IN_SECONDS ) );
	define('DAY_IN_SECONDS', ( 24 * HOUR_IN_SECONDS ) );
	define('MONTH_IN_SECONDS', ( 30.4375 * DAY_IN_SECONDS ) );
	define('YEAR_IN_SECONDS', ( 12 * MONTH_IN_SECONDS ) );



	/**
	 * Returns an array of the date/time formats allowed by usertime*()'s.
	 *
	 * @return array
	 */
	function allowed_usertime_types_array()
	{
		$allowed_types = array(
			'M',
			'MM',
			'MMYY',
			'MMDDYYYY',
			'MMDDYYYYHH',
			'MMDDYYYYHHMM',
			'MMDDYYYYHHMMSS',
			'YYYY',
			'YYYYMM',
			'YYYYMMDD',
			'YYYYMMDDHH',
			'YYYYMMDDHHMM',
			'YYYYMMDDHHMMSS',
			'M/D/YY',
			'MM/DD/YYYY',
			'M-D-YY',
			'MM-DD-YYYY',
			'M D YY',
			'MM DD YYYY',
			'M/YY',
			'MM/YYYY',
			'M-YY',
			'MM-YYYY',
			'M YY',
			'MM YYYY',
			'HH:MM',
			'HH:MM:SS',
			'M/D/YY HH:MM',
			'MM/DD/YYYY HH:MM:SS',
			'M-D-YY HH:MM',
			'MM-DD-YYYY HH:MM:SS'
		);

		return $allowed_types;
	}



	/**
	 * Return allowed usertime types in a HTML formatted string.
	 *
	 * @return string
	 */
	function allowed_usertime_types_html()
	{
		$date_types = allowed_usertime_types_array();

		$out = '<ul>';

		foreach ( $date_types AS $key => $value )
		{
			$out .= '<li>' . $value . '</li>';
		}
		
		$out .= '</ul>';

		return $out;
	}



	/**
	 * Returns an associative array of the input variables. If the input
	 * variables are empty, then based upon $type, they are preset to the start
	 * or end point of their period. Month start is 01, end is 12. Minute start
	 * is 00, end is 59. Year if empty is date('Y').
	 *
	 * @param string $type type of date, ex: start, end
	 * @param string $year year period
	 * @param string $month month period
	 * @param string $day day period
	 * @param string $hour hour period
	 * @param string $minute minute period
	 * @param string $second second period
	 * @return array with input variables as keys
	 */
	function create_date_array($type = 'start', $year = '', $month = '', 
		$day = '', $hour = '', $minute = '', $second = '')
	{
		// create starting time
		if ( strtolower($type) != 'end' )
		{
			// pad numbers less than 10 with a zero
			$date = array(
				'year'		=> ( !empty($year) ) ? $year : date('Y'),
				'month'		=> ( !empty($month) ) ? $month : '01',
				'day'			=> ( !empty($day) ) ? $day : '01',
				'hour'		=> ( !empty($hour) ) ? $hour : '00',
				'minute'		=> ( !empty($minute) ) ? $minute : '00',
				'second'		=> ( !empty($second) ) ? $second : '00'
			);
		}
		
		// create ending time's
		else
		{
			$date = array(
				'year'		=> ( !empty($year) ) ? $year : date('Y'),
				'month'		=> ( !empty($month) ) ? $month : '12',
				'day'			=> ( !empty($day) ) ? $day : '31',
				'hour'		=> ( !empty($hour) ) ? $hour : '23',
				'minute'		=> ( !empty($minute) ) ? $minute : '59',
				'second'		=> ( !empty($second) ) ? $second : '59'
			);
		}

		return $date;
	}



	/**
	 * Returns 2-digit month code of a timestamp
	 *
	 * @param integer $time_stamp year month; ex: 20020601235959
	 * @return integer 2-digit, ex: 06
	 */
	function get_month($time_stamp = 0)
	{
		return sub_str($time_stamp, 4, 2);
	}
	
	
	
	/**
	 * Returns 4-digit year code of a timestamp
	 *
	 * @param integer $time_stamp year month; ex: 20020601235959
	 * @return integer 4-digit, ex: 2002
	 */
	function get_year($time_stamp = 0)
	{
		// substr -- Return part of a string
		// string substr ( string string, int start [', int length'])
		
		// strval -- Get string value of a variable 
		// string strval ( mixed var)
		return sub_str($time_stamp, 0, 4);
	}



	/**
	 * Make start and end dates based upon the first and last moments of 
	 * YM + term
	 *
	 * @param integer $YM year month; ex: 200206
	 * @param integer $term or year period
	 * @return array of start and end dates 
	 *		$dates['ssd'] $YM .	"01000000";		// Start Start Date
	 *		$dates['esd'] $YM .	"31235959";		// End Start Date 
	 *		$dates['sed'] $Y + $term . "0101000000";	// Start End Date 
	 *		$dates['eed'] $Y + $term . "1231235959";	// End End Date
	 */
	function mk_start_end_period($YM = 0, $term = 0)
	{
		// determine input type

		// create start period in accuracy given
		// use timestamp length or period input...
		// if YYYY, then year
		// if YYYYMMDD, then days

		// if offset <> 0
		// create ending period with desired offset

		// use mk_mysql_timestamp for creating time periods

		$dates = array();

		// generalize the following into get_period($timestamp, 'year')
		// generalize the following into get_period($timestamp, 'month')
		// etc...
		$Y = get_year($YM);
		$M = get_month($YM);

		$dates['ssd'] = strval($YM) .		"01000000";		// Start Start Date
		$dates['esd'] = strval($YM) .		"31235959";		// End Start Date
		$dates['sed'] = strval($Y + $term) . "0101000000";	// Start End Date
		$dates['eed'] = strval($Y + $term) . "1231235959";	// End End Date

		return $dates;
	}



	/**
	 * @param integer $timestamp being inputted, ex: 20020730123154
	 * @param integer $offset to add to $timestamp, ex: 12, 36, etc.
	 * @param string $time_mode being worked with, ex: MONTH, DAY, YEAR
	 * @return integer of new 14-digit time stamp
	 */
	function mk_mysql_timestamp($timestamp, $offset=0, $time_mode = 'MONTH')
	{
		if ( strlen($timestamp) == 14 && intval($timestamp) > 0 )
		{
			$year  = sub_str($timestamp, 0, 4);
			$month = sub_str($timestamp, 4, 2);
			$day   = sub_str($timestamp, 6, 2);
			$hour  = sub_str($timestamp, 8, 2);
			$min   = sub_str($timestamp, 10, 2);
			$sec   = sub_str($timestamp, 12, 2);

			if ( checkdate($month, $day, $year) )
			{
				switch( strtoupper($time_mode) )
				{
					case 'YEAR':
						//check for leap year:
						if ( ( date('L', mktime($hour, $min, $sec,$month, $day, $year)) ) 
							&& ($month == 2) && ($day == 29) && ($offset != 4) )
						{
							$newTime = mktime($hour, $min, $sec, $month + 1, 0, $year + $offset); 
						}
						else
						{
							$newTime = mktime($hour, $min, $sec, $month, $day, $year + $offset); 
						}
						break;
					
					case 'MONTH':	
						$K = mktime($hour, $min, $sec, $month + $offset, 15, $year);

						$last_day_mnth	= date('t', $K);

						if ($day > $last_day_mnth)
						{
							$newTime = mktime($hour, $min, $sec, $month +$offset + 1, 0, $year);
						}
						else
						{
							$newTime = mktime($hour, $min, $sec, $month + $offset, $day, $year); 
						}
						break;
					
					case 'DAY':
						$newTime = mktime($hour, $min, $sec, $month, $day + $offset, $year); 
						break;
						
					case 'HOUR': 
						$newTime = mktime($hour + $offset, $min, $sec, $month, $day, $year); 
						break;

					case 'MINUTE': 
						$newTime = mktime($hour, $min + $offset, $sec, $month, $day, $year); 
						break;

					default: //must be SECOND
						$newTime = mktime($hour, $min, $sec + $offset, $month, $day, $year); 
						break;
				}//end switch

				$new_mysql_timestamp = time_unix2mysql($newTime);

				return $new_mysql_timestamp;
			}

			else
			{
				err('mk_mysql_timestamp', 'checkdate failure');
			}
		}
		else
		{
			err('mk_mysql_timestamp', 'strlen($timestamp) == 14) && (intval($timestamp) > 0');
		}
	}



	/**
	 * Returns 14-digit mysql timestamp for now.
	 *
	 * @return integer 20021231235959
	 */
	function now()
	{
		return date('YmdHis');
	}



	/**
	 * Returns 8-digit timestamp for today.
	 *
	 * @return integer 20021231
	 */
	function today()
	{
		return date('Ymd');
	}

	
	
	/**
	 * Returns 8-digit timestamp for yesterday.
	 *
	 * @return integer 20021231
	 */
	function yesterdaym()
	{
		return date('Ymd', yesterday() );
	}

	
	
	/**
	 * Checks to see if $year needs to be converted to four digit. Years
	 * over 69 become 1900's, under 2000's.
	 *
	 * @param integer $year
	 * @return integer
	 */
	function two_digit_year2four_digit_year($year)
	{
		if ( $year < 100 )  
		{
			// why not 69?
			$year = ( $year > 69 ) ? (1900 + $year) : (2000 + $year);
		}

		return $year;
	}



	/**
	 * Converts human date/time to array. 
	 * 
	 * @param string $user_time date/time in above format
	 * @param string $time_type starting or ending type of time to create
	 * 	ex: start, end
	 * @return array with keys: year, month, day, hour, minute, second
	 */
	function usertime2array($user_time, $time_type = 'start')
	{
		$date = false;

		// check format of $user_time input
		// is it human readable or mysql

		if ( !is_numeric($user_time) )
		{
			$date = usertime_human2array($user_time, $time_type);
		}

		// possibly year only or in mysql format
		else
		{
			$date = usertime_numeric2array($user_time, $time_type);
		}

		return $date;
	}



	/**
	 * Convert's user inputted time to a format acceptable for use in mysql
	 * database queries where the TIMESTAMP (YYYYMMDDHHMMSS) type is used. If
	 * $user_time is a partial period by year, month, hour, etc. then the
	 * returned time is initialized to that point. 
	 *
	 *		Example: 
	 *			1972 becomes 19720101000000
	 *			197209 becomes 19720901000000
	 *			9/1/1972 becomes 19720901000000
	 *			9-1-1972 10:41 becomes 19720109104100
	 *
	 * If $as_array is true, an end time is created based upon the detail of 
	 * $user_time inputted similar to initialization, but as a terminal period.
	 *
	 *		Example: 
	 *			1972 becomes 19721231235959
	 *			197209 becomes 19720931235959
	 *			9/1/1972 becomes 19720931235959
	 *			9-1-1972 10:41 becomes 19720109104159
	 *
	 * Additionally, all of the internal components of the $user_time converted
	 * to start and end times is returned in an array.
	 *
	 * @param string $user_time human readable or mysql timestamp, ex: see above
	 * @param boolean $return_as_array return result as multi-dimensional array
	 * 	or integer.
	 * @return integer/array
	 */
	function usertime2mysql($user_time = '', $return_as_array = false)
	{
		// internal respresentation of time should be mysql timestamp format
		// YYYYMMDDHHMMSS or at least the components of it.
		// see http://www.mysql.com/doc/en/DATETIME.html for more information

		// time keeper array
		// false indicates not sure, not used, etc.
		$info_array = array( 
			'user_time'		=> $user_time,
			'start_array'	=> false,
			'end_array'		=> false,
			'start'	=> false,
			'start_mktime'	=> false,
			'end'		=> false,
			'end_mktime'	=> false
		);

		$user_time = str_rm_whitespace($user_time);

		// was $user_time set? if not set to now
		if ( empty($user_time) )
		{
			$info_array['user_time'] = $user_time = date('YmdHis');
		}

		// convert usertime to start time array
		$info_array['start_array'] = usertime2array($user_time);

		// bail if fail
		if ( !$info_array['start_array'] )
		{
			return false;
		}

		// convert usertime to start time array
		$info_array['end_array'] = usertime2array($user_time, 'end');

		// bail if fail
		if ( !$info_array['end_array'] )
		{
			return false;
		}
		
		$info_array = adjust_dates($info_array);

		// are the components even valid?
		// is input valid date and time periods?
		if ( !valid_usertime_array($info_array) )
		{
			return false;
		}

		// store time peices to array?
		$info_array['start'] = implode('', $info_array['start_array']);
		$info_array['start_mktime'] = array2mktime($info_array['start_array']);

		// convert usertime to start time array
		$info_array['end'] = implode('', $info_array['end_array']);
		$info_array['end_mktime'] = array2mktime($info_array['end_array']);

		return ( $return_as_array ) 
			? $info_array 
			: $info_array['start'];
	}


	 
	/**
	 * Attempts to convert human $user_time into mysql timestamp components for
	 * entry into an array12/32/2002.
	 *
	 * @param integer $user_time user's entered human time
	 * @param string $time_type starting or ending type of time to create
	 * 	ex: start, end
	 * @return array keys: type, year, month, day, hour, minute, second
	 */
	function usertime_human2array($user_time, $time_type = 'start')
	{
		$temp_date = false;
		$temp_time = false;

		// ways to seperate dates and time
		$date_splitters = array( '-', '/', ' ' );
		$time_splitters = array( ':' );

		// what type of separators of /, -, or ' '
		$temp_date = $new_user_time = str2arr($user_time, $date_splitters);

		// make sure $temp_date just didn't become an unbroken array
		if ( $temp_date != array($user_time) )
		{
			// check last value, assuming to be year since US format, for 2- to
			// 4-digit conversion 
			$year_position = sizeof($temp_date) - 1;
			$temp_date[$year_position] = 
				two_digit_year2four_digit_year($temp_date[$year_position]);

			// break off time portion
			// '1972 10:41' --> '1972', '10:41'
			if ( strpos($temp_date[$year_position], ' ') )
			{
				$year = explode(' ', $temp_date[$year_position]);
				
				// time is in second portion
				$user_time = $year[1];
				
				// date is in first portion
				$temp_date[$year_position] = $year[0];
			}

			$temp_date = year_last2year_first($temp_date);

			$temp_date = array_zero_prepend($temp_date);

			// condense temp_date
			$temp_date_size = sizeof($temp_date);
			$temp_date = implode('', $temp_date);
		}
		
		// make date today for the upcoming $temp_time
		else
		{
			$temp_date = date('Ymd');
		}

		// what type of separators of time :
		// need to remove date from $user_time?
		if ( isset($temp_date_size) )
		{
			// remove date component from $user_time via $new_user_time
			$new_user_time = $new_user_time[$temp_date_size - 1];
			$new_user_time = explode(' ', $new_user_time);
			
			unset( $new_user_time[0] );

			$user_time = ( isset($new_user_time[1]) )
				? $new_user_time[1]
				: false;
		}

		$temp_time = ( $user_time )
			? str2arr($user_time, $time_splitters)
			: false;

		// build new $temp_date as needed
		if ( $temp_time )
		{
			$temp_time = array_zero_prepend($temp_time);

			// check length of $temp_date for month/year entry only
			if ( 8 > strlen($temp_date) )
			{
				// add todays date
				$temp_date .= date('d');
			}

			// combine time with date
			$temp_date .= implode('', $temp_time);
		}

		// how much information is available
		// is it year/month/date, month-date-year, ymd, hours:minutes
		// what content order, year first or last, time only?

		// ah who cares let another function handle it
		return ( $temp_date ) 
			? usertime_numeric2array($temp_date, $time_type) 
			: $temp_date;
	}


	 
	/**
	 * Attempts to convert numeric $user_time into mysql timestamp components for
	 * entry into an array.
	 *
	 * @param integer $user_time user's entered numeric time
	 * @param string $time_type starting or ending type of time to create
	 * 	ex: start, end
	 * @return array keys: type, year, month, day, hour, minute, second
	 */
	function usertime_numeric2array($user_time, $time_type = 'start')
	{
		$date = create_date_array($time_type);

		// how big is the string to help determine components
		$user_time_size = strlen($user_time);

		// determine type
		// numeric selection type have accounting preferences
		switch ( $user_time_size )
		{
			case 1:
			case 2:
				// M
				// MM
				$date['month'] = $user_time;
				break;

			case 3:
				// MYY
				$date['month'] = sub_str($user_time, 0, 1);
				$date['year'] = sub_str($user_time, 1);
				break;

			case 4:
			case 6:
				if ( ( sub_str($user_time, 0 , 2) + 0 ) <= 12 )
				{
					// MMYY
					// MMYYYY
					$date['month'] = sub_str($user_time, 0, 2);
					$date['year'] = sub_str($user_time, 2);
				}

				else
				{
					// YYYY
					// YYYYMM
					$date['year'] = sub_str($user_time, 0, 4);

					if ( 6 == $user_time_size )
					{
						$date['month'] = sub_str($user_time, 4);
					}
				}
				
				break;

			// note cascading case
			case 14:
				$date['second'] = sub_str($user_time, 12, 2);

			case 12:
				$date['minute'] = sub_str($user_time, 10, 2);

			case 10:
				$date['hour'] = sub_str($user_time, 8, 2);

			case 8:
				if ( ( sub_str($user_time, 0 , 4) + 0 ) > 1231 )
				{
					// YYYYMMDD
					$date['year'] = sub_str($user_time, 0, 4);
					$date['month'] = sub_str($user_time, 4, 2);
					$date['day'] = sub_str($user_time, 6, 2);
				}

				else
				{
					// MMDDYYYY
					$date['month'] = sub_str($user_time, 0, 2);
					$date['day'] = sub_str($user_time, 2, 2);
					$date['year'] = sub_str($user_time, 4, 4);
				}
					
				break;

			default:
				echo_ln("case $user_time_size: for $user_time not found");
				$date = false;
				break;
		}
	
		// clean up data for mysql
		if ( $date )
		{
			// check year, for 2- to 4-digit conversion 
			$date['year'] = two_digit_year2four_digit_year($date['year']);

			// ensure numbers less than ten are prepended with 0
			foreach ( $date AS $key => $value )
			{
				$date[$key] = zero_prepend($value);
			}
		}

		return $date;
	}



	/**
	 * Checks for invalid date and time components in $usertime_array.
	 *
	 * @param array $usertime with keys start_array & end_array
	 * @return boolean
	 */
	function valid_usertime_array($usertime)
	{
		// check values of *_array to be in certain ranges
		// year between 1900 and 2100
		if ( !between($usertime['start_array']['year'], 1900, 2100) ||
				!between($usertime['end_array']['year'], 1900, 2100) )
		{
			return false;
		}

		// month between 1 and 12
		if ( !between($usertime['start_array']['month'], 1, 12) ||
				!between($usertime['end_array']['month'], 1, 12) )
		{
			return false;
		}

		// day between 1 and 31
		if ( !between($usertime['start_array']['day'], 1, 31) ||
				!between($usertime['end_array']['day'], 1, 31) )
		{
			return false;
		}


		// hours between 0 and 23
		if ( !between($usertime['start_array']['hour'], 0, 23) ||
				!between($usertime['end_array']['hour'], 0, 23) )
		{
			return false;
		}

		
		// minutes, seconds between 0 and 59
		if ( !between($usertime['start_array']['minute'], 0, 59) ||
				!between($usertime['end_array']['minute'], 0, 59) ||
				!between($usertime['start_array']['second'], 0, 59) ||
				!between($usertime['end_array']['second'], 0, 59) )
		{
			return false;
		}


		return true;
	}



	/**
	 * Rearranges $date from MM/DD/YYYY to YYYY/MM/DD or MM/YYYY to YYYY/MM.
	 *
	 * @param array $date date array
	 * @return array
	 */
	function year_last2year_first($date)
	{
		// year is last array element
		$year_position = sizeof($date) - 1;

		// pick year, month, day if applicable
		$month = $date[0];
		$year = $date[$year_position];

		if ( 2 == $year_position )
		{
			$day = $date[1];
		}

		// rearrange $date to place year in front
		$date[0] = $year;
		$date[1] = $month;

		if ( isset($day) )
		{
			$date[2] = $day;
		}

		return $date;
	}



	/**
	 * Returns the UNIX timestamp for yesterday.
	 *
	 * @return integer
	 */
	function yesterday()
	{
		$yesterday = strtotime('yesterday');

		return $yesterday;
	}



	/**
	 * Create prettied begin/end dates.
	 *
	 * @param mixed string/integer start date
	 * @param mixed string/integer finish date
	 * @param string date format
	 * @return array
	 */
	function pretty_dates($start = false, $finish = false, 
		$format = 'M j, Y H:i')
	{
		$end_date_done = false;

		$date = usertime2mysql($start, true);

		$begin = date($format, $date['start_mktime']);

		$date_end = ( $finish )
			? usertime2mysql($finish, true)
			: $date;

		$end = date($format, $date_end['end_mktime']);

		$array = compact(
			'begin',
			'end'
		);

		return $array;
	}



	/**
	 * Create timestamp from an array populated with year, month, date, hour,
	 * minute, second.
	 *
	 * @param array date/time properties
	 * @return integer
	 */
	function array2mktime($date)
	{
		$time = mktime(
			$date['hour'], 
			$date['minute'], 
			$date['second'], 
			$date['month'], 
			$date['day'], 
			$date['year']
		);

		return $time;
	}


	/**
	 * Attempt to fix bad ending date components.
	 *
	 * @param array date/time properties
	 * @return array
	 */
	function adjust_dates($date)
	{
		// check and adjust for realistic start date
		// 6/31 to 7/1 to correct 6/30
		$start_date_done = false;
		$start_month = $date['start_array']['month'];
		$start_mktime = array2mktime($date['start_array']);

		do
		{
			// compare day
			$date_start_month = date('n', $start_mktime);

			if ( $start_month != $date_start_month )
			{
				$start_mktime = strtotime('-1 day', $start_mktime);
			}

			else
			{
				$start_date_done = true;
			}
		} while ( !$start_date_done );

		$date['start_array']['month'] = date('m', $start_mktime);
		$date['start_array']['day'] = date('d', $start_mktime);

		$end_date_done = false;
		$end_month = $date['end_array']['month'];
		$end_mktime = array2mktime($date['end_array']);

		do
		{
			// compare day
			$date_end_month = date('n', $end_mktime);

			if ( $end_month != $date_end_month )
			{
				$end_mktime = strtotime('-1 day', $end_mktime);
			}

			else
			{
				$end_date_done = true;
			}
		} while ( !$end_date_done );

		$date['end_array']['month'] = date('m', $end_mktime);
		$date['end_array']['day'] = date('d', $end_mktime);

		return $date;
	}



	/**
	 * Convert mysql time in number format to unix-timestamp
	 *
	 * @reference: http://us2.php.net/manual/en/function.mktime.php
	 * @author: phpnotes at ssilk dot de
	 *
	 * @param string mysql timestamp, YYYYMMDDHHMMSS
	 * @return integer unix timestamp in seconds from Epoch
	 */
	function time_mysql2unix($mysql)
	{
		$mysql_timestamp = '/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/';

		if ( preg_match($mysql_timestamp, $mysql, $m) )
		{ 
			return( mktime($m[4],$m[5],$m[6],$m[2],$m[3],$m[1]) ); 
		} 
		
		return false;
	}



	/**
	 * Convert unix time to mysql-timestamp
	 *
	 * @param integer unix timestamp in seconds from Epoch
	 * @return string mysql timestamp, YYYYMMDDHHMMSS
	 */
	function time_unix2mysql($unix)
	{
		if ( is_unixtime($unix) )
		{ 
			return( date('YmdHis', $unix) ); 
		} 
		
		return false;
	}



	/**
	 * Returns earliest unix timestamp
	 *
	 * Reference: http://www.gnu.org/manual/tar-1.12/html_chapter/tar_7.html
	 *
	 * @return integer
	 */
	function time_unix_earliest()
	{
		return mktime( 00, 00, 00, 01, 01, 1970 );
	}



	/**
	 * Returns latest unix timestamp
	 *
	 * @return integer
	 */
	function time_unix_latest()
	{
		return mktime( 23, 59, 59, 01, 01, 2038 );
	}



	/**
	 * Return term difference between two unix timestamps
	 *
	 * @param integer unix timestamp
	 * @param mixed integer/boolean unix timestamp or false for now()
	 * @param string difference type, MONTH
	 * @return mixed float/boolean difference or failure
	 */
	function time_diff($begin, $end = false, $type = 'MONTH')
	{
		$diff = false;

		// get current time if needed
		$end = ( !is_false($end) )
			? $end
			: time();

		if ( is_unixtime($begin) && is_unixtime($end) )
		{
			// result is in seconds
			$diff = $end - $begin;
		}

		switch( $type )
		{
			case 'SECOND':
				$diff = $diff / SECOND_IN_SECONDS;
				break;

			case 'MINUTE':
				$diff = $diff / MINUTE_IN_SECONDS;
				break;

			case 'HOUR':
				$diff = $diff / HOUR_IN_SECONDS;
				break;

			case 'DAY':
				$diff = $diff / DAY_IN_SECONDS;
				break;

			case 'MONTH':
				$diff = $diff / MONTH_IN_SECONDS;
				break;

			case 'YEAR':
				$diff = $diff / YEAR_IN_SECONDS;
				break;

			default:
				break;
		}

		return $diff;
	}



	/**
	 * Returns timestamp for last month.
	 *
	 * @param string unix or mysql timestamp
	 * @return mixed string/integer
	 */
	function last_month($mysql = true)
	{
		$stamp = strtotime('last month');
		
		return ( $mysql )
			? time_unix2mysql($stamp)
			: $stamp;
	}



	/**
	 * Returns YYYYMMDDHHMMSS timestamp for given string.
	 *
	 * @param string time/date string conforming to gettime
	 * @return integer
	 */
	function str2mysqltime($time = false)
	{
		// already mysql format
		if ( is_mysqltime($time) )
		{
			$string = $time;
		}

		// has something
		elseif ( $time )
		{
			$string = date('YmdHis', strtotime($time) ); 
		}

		// grab now
		else
		{
			$string = now();
		}

		return $string;
	}

?>
