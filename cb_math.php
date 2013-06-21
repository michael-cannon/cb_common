<?php

	/**
	 * Cannon BOSE's centralization of common mathmatical functions
	 *
	 * Copyright (C) 2002 Michael Cannon <michael@peimic.com>
	 * See full GNU Lesser General Public License in LICENSE.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: cb_math.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */



	/**
	 * Returns boolean depending upon whether $number is in [$low, $high].
	 *
	 * @param float $number number being looked for
	 * @param float $low low end of number being looked for
	 * @param float $high high end of number being looked for
	 * @return boolean
	 */
	function between($number, $low, $high)
	{
		// make sure dealing with numbers
		return ( $low <= $number && $number <= $high );
	}



	/**
	 * Divide by zero check tool, returns false if trying to divide by zero.
	 *
	 *	@param float $dividend the 'top' number
	 *	@param float $divisor the 'bottom' number
	 *	@return boolean/float
	 */
	function divide_by_zero($dividend, $divisor) 
	{
		return ( ( $divisor != 0 ) ? ($dividend / $divisor) : false );
	}



	/**
	 * Returns the average value of an array of numerical values.
	 *
	 * Mean is the sum of a list of numbers, divided by the total number of
	 * numbers in the list.
	 *
	 * @param array $numeric_array numerical values, ex: array(1, 3, 42)
	 * @return float
	 */
	function mean($numeric_array)
	{
		$mean = 0; 
		
		$count = count($numeric_array); 
		$total = sum($numeric_array); 
		
		$mean = $total / $count; 

		return $mean;
	}
	

	
	/**
	 * Returns the half-way value of an array of alphanumerical values.
	 *
	 * Median is the "Middle value" of a list. The smallest number such that at
	 * least half the numbers in the list are no greater than it. If the list has
	 * an odd number of entries, the median is the middle entry in the list after
	 * sorting the list into increasing order. If the list has an even number of
	 * entries, the median is equal to the sum of the two middle (after sorting)
	 * numbers divided by two. The median can be estimated from a histogram by
	 * finding the smallest number such that the area under the histogram to the
	 * left of that number is 50%.
	 *
	 * @param array $value_array numerical values, ex: array(1, 3, 42)
	 * @return mixed
	 */
	function median($value_array)
	{
		$temp = $value_array;
		$count = count($temp); 
		$median = 0;

		// ascending/increasing order
		sort($temp);

		// even count
		if ( 0 == $count % 2 )
		{
			// 6 elements
			// zero-based counting 0, 1, 2, 3, 4, 5
			// middle two positions are 2 and 3
			$pos2 = $count / 2;
			$pos1 = $pos2 - 1;

			$median = $temp[$pos1] + $temp[$pos2];
			$median /= 2;
		}

		// odd count
		else
		{
			// 5 elements
			// zero-based counting 0, 1, 2, 3, 4
			// middle positions is 2 not 2.5
			$pos = floor($count / 2);

			$median = $temp[$pos];
		}

		return $median;
	}



	/**
	 * Returns the most frequent value(s) of an array of numerical values.
	 *
	 * For lists, the mode is the most common (frequent) value. A list can have
	 * more than one mode. For histograms, a mode is a relative maximum ("bump").
	 *
	 * @param array $value_array alphanumerical values, ex: array(1, 'a', 42)
	 * @return mixed
	 */
	function mode($value_array)
	{
		$mode = array(); 

		// cycle through $value_array
		// create array based upon values of $value_array
		// each subsequent same value increments values value by one
		// since $val can be decimal, create associative keyed array
		foreach ( $value_array AS $key => $val )
		{
			// count the key
			if ( isset($mode["$val"]) )
			{
				$mode["$val"]++; 
			}

			// initialize the key
			else
			{
				$mode["$val"] = 1; 
			}
		} 
		
		reset($value_array); 

		// decreasing order sort on keys, which places lower value keys whose
		// counts might be higher last, resulting in there value being returned
		krsort($mode);

		// create vars for potential modes to go into
		$new_key = false;
		$new_value = 0;

		// get most frequent lowest valued key
		foreach ( $mode AS $key => $value )
		{
			if ( $value >= $new_value )
			{
				$new_value = $value;
				$new_key = $key;
			}
		}

		// get mode from $new_key
		$mode = ( is_numeric($new_key) )
			? $new_key * 1
			: $new_key;

		return $mode;
	}
	

	
	/**
	 * Returns the standard deviation of an array of numerical values.
	 *
	 * Standard deviation tells how spread out numbers are from the average,
	 * calculated by taking the square root of the arithmetic average of the
	 * squares of the deviations from the mean in a frequency distribution.
	 *
	 * @param array $numeric_array numerical values, ex: array(1, 3, 42)
	 * @return float
	 */
	function standard_deviation($numeric_array)  
	{
		$count = count($numeric_array); 
		$total = 0; 

		$mean = mean($numeric_array); 

		foreach ( $numeric_array AS $key => $val )
		{  
			$total += pow(($val - $mean), 2); 
		}  

		$divided = divide_by_zero($total, ($count - 1) ); 

		$sd = ( !$divided )
			? sqrt($divided)
			: $divided; 

		return $sd;  
	}



	/**
	 * Returns an array of
	 * 	count
	 * 	sum
	 * 	min
	 * 	max
	 * 	range
	 *		mean
	 *		median
	 *		mode
	 * 	standard_deviation
	 *
	 * @param array $numeric_array numerical values, ex: array(1, 3, 42)
	 * @return array
	 */
	function statistics($numeric_array)
	{
		$array = array();

		$array['count'] = count($numeric_array);
		$array['sum'] = sum($numeric_array);
		$array['min'] = min($numeric_array);
		$array['max'] = max($numeric_array);
		$array['range'] = $array['max'] - $array['min'];
		$array['mean'] = mean($numeric_array);
		$array['median'] = median($numeric_array);
		$array['mode'] = mode($numeric_array);
		$array['standard_deviation'] = standard_deviation($numeric_array);

		return $array;
	}

	
	
	/**
	 * Returns the total value of an array of numerical values.
	 *
	 * @param array $numeric_array numerical values, ex: array(1, 3, 42)
	 * @return float
	 */
	function sum($numeric_array)
	{
		$sum = 0; 

		foreach ( $numeric_array AS $key => $val )
		{ 
			$sum += $val; 
		} 

		reset($numeric_array); 
		
		return $sum;
	}
	
?>
