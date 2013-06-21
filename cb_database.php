<?php

	/**
	 * Cannon BOSE's centralization of common database functions
	 *
	 * Copyright (C) 2002 Michael Cannon <michael@peimic.com>
	 * See full GNU Lesser General Public License in LICENSE.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: cb_database.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */



	/**
	 * Alias for mk_sql_between_date
	 */
	function mk_sql_date($attribute, $start = false, $finish = false)
	{
		// convert date to mysql
		$start_date = usertime2mysql($start, true);
		$finish_date = usertime2mysql($finish, true);

		$start = ( is_false($start) || is_blank($start) )
			? false
			: $start_date['start'];

		$finish = ( is_false($finish) || is_blank($finish) )
			? false
			: $finish_date['end'];

		return mk_sql_between($attribute, $start, $finish);
	}



	/**
	 * Create inclusive BETWEEN date range based upon $start and $finish input.
	 *
	 * @param string column name
	 * @parameter mixed integer/string/boolean start date
	 * @parameter mixed integer/string/boolean finish date
	 * @return string
	 */
	function mk_sql_between_date($attribute, $start = false, $finish = false)
	{
		$start = ( is_false($start) || is_blank($start) )
			? false
			: $start;

		$finish = ( is_false($finish) || is_blank($finish) )
			? false
			: $finish;

		return mk_sql_date($attribute, $start, $finish);
	}



	/**
	 * Create inclusive BETWEEN, <=, >=, or some range based upon $start and
	 * $finish input.
	 *
	 * @param string column name
	 * @parameter mixed integer/string starting point
	 * @parameter mixed integer/string finishing point
	 * @parameter boolean negate result
	 * @return string
	 */
	function mk_sql_between($attribute, $start = false, $finish = false,
		$negate = false)
	{
		$sql = '';

		$negate = ( !$negate )
			? ''
			: 'NOT ';
		
		// has start and finish
		if ( $start && $finish )
		{
			// correct order?
			if ( $start > $finish )
			{
				swap($start, $finish);
			}

			$sql = 'AND ' . $negate . '(' . $attribute . ' BETWEEN ' 
				. $start 
				. ' AND ' 
				. $finish
				. ')';
		}

		// start only
		elseif ( $start )
		{
			$sql = 'AND ' . $negate . '(' . $attribute . ' >= ' . $start . ')';
		}

		// finish only
		elseif ( $finish )
		{
			$sql = 'AND ' . $negate . '(' . $attribute . ' <= ' . $finish . ')';
		}
	
		// nothing given ie: all
		else
		{
		}

		return $sql;
	}



	/**
	 * Returns a string containing the WHERE clause portion of an SQL IN
	 * condition.
	 *
	 * @param string $attribute name of column
	 * @param array $values attribute values desired, must be of same type
	 * @param boolean $not_in NOT operand
	 * @return string
	 */
	function mk_sql_in($attribute, $values, $not_in = false)
	{
		$in = '';

		// create first portion of IN clause
		$in .= " AND $attribute";

		// determine NOT
		$in .= ( $not_in )
			? ' NOT IN'
			: ' IN';

		// remove duplicates
		$values = array_unique($values);

		// attempt efficiency
		sort($values);

		// convert values to csv
		$csv = arr2csv($values, true);

		// create string
		$in .= " ($csv)\n";

		return $in;
	}
	
	
	
	/**
	 * Creates SELECT statement
	 *
	 * @param string $select clause, ex: '*', 'id, name'
	 * @param string $from table(s), ex: 'Table'
	 * @param string $where clause, ex: 'id = 1', 'name = 'Frank' ORDER BY name
	 * 	ASC
	 * @return string
	 */
	function mk_sql_select($select, $from, $where = '')
	{
		// remove keywords to prevent duplication
		// convert "SELECT *" to "*" or "SELecT a, b, c" to "a, b, c"
			$select = trim_first_word('select', $select);
			$from = trim_first_word('from', $from);
			$where = trim_first_word('where', $where);

		$sql_query = "
			SELECT $select 
			FROM $from
		";

		$sql_query .= ( '' != $where )
			? "\tWHERE $where"
			: ''; 

		debug_msg($sql_query);

		return $sql_query;
	}


	
	/**
	 * Returns WHERE clause string based upon $array contents.
	 *
	 * @param array $array associative, ex: "'account_id' => 282733"
	 * @return string
	 */
	function mk_sql_where($array)
	{
		$string = '';

		// cycle through $array contents to create where clause based upon
		// associative keys as attributes and values as where conditions
		foreach ( $array AS $key => $value )
		{
			$string .= "
				AND AA.$key = '$value'
			";
		}

		return $string;
	}



	/**
	 * Return array containing individual SQL queries as values.
	 *
	 * @param string $mquery
	 * @return array
	 */
	function mquery_str2arr($mquery)
	{
		// convert $mquery string to array
		$mquery = explode("\n", $mquery);

		// remove comments and blank lines
		foreach ( $mquery AS $key => $value )
		{
			$value = trim($value);

			if ( is_blank($value) || preg_match('/^#/', $value) )
			{
				unset($mquery[$key]);
			}
		}

		// recombine query and then break into individual queries
		$mquery = implode('', $mquery);
		$mquery = str2arr($mquery, ';');
		
		return $mquery;
	}
	
	

	/**
	 * Reports MySQL Error 
	 *
	 * @param string $result returned from mysql_query
	 * @param string &$box_content place error description contents into here
	 * @param string $identity short op description
	 * @param boolean $displayHere the error report
	 * @return void
	 */
	function ReportMySQLError ($result, &$box_content, $identity, 
		$displayHere = false) 
	{
		$show = true;

		if ( !$result ) 
		{
			$box_c = "<p>In \"$identity\",<br />MySQL says: \"";
			$box_c .= mysql_error();
			$box_c .= "\", code ";
			$box_c .= mysql_errno();
			$box_c .= ".</p>";
		}

		else 
		{
			$show = false;
			$box_c = "<br />Successful $identity";
		}

		if ( $displayHere && $show ) 
		{
			echo $box_c;
		}

		else 
		{
			$box_content .= $box_c;
		}
	}



	/**
	 * Returns phpWebSite database connetion.
	 *
	 * @return resource
	 */
 	function pws_db_connect()
	{
		global $dbhost;
		global $dbuname;
		global $dbpass;
		global $dbname;
		
		$connection = mysql_connect($dbhost, $dbuname, $dbpass) 
			or die( "Can't connect db: " . mysql_error() . ' ' . mysql_error() ); 

		@mysql_select_db($dbname, $connection) 
			or die( "Can't select db: " . mysql_error() . ' ' . mysql_error() ); 
	
		return $connection;
	}



	/**
	 * Returns integer of affected rows from last MySQL INSERT, UPDATE, DELETE,
	 * or REPLACE operation.
	 *
	 * @param Resource $stream
	 * @return integer
	 */
	function sql_affected_rows($stream = false)
	{
		return ( is_false($stream) )
			? mysql_affected_rows()
			: mysql_affected_rows($stream);
	}



	/**
	 * Returns integer or false depending upon success of last MySQL INSERT.
	 *
	 * @param Resource $stream
	 * @return mixed integer false
	 */
	function sql_insert_id($stream = false)
	{
		$affected_rows = sql_affected_rows($stream);

		if ( 0 < $affected_rows )
		{
			$insert_id = ( is_false($stream) )
				? mysql_insert_id()
				: mysql_insert_id($stream);
		}

		else
		{
			$insert_id = false;
		}

		$out = array(
			'affected_rows'	=> $affected_rows,
			'insert_id' 		=> $insert_id
		);

		return $insert_id;
	}



   /**
	 * MySQL query() wrapper for multiple queries as in a db dump.
	 *
	 * @param string $queries phpMyAdmin db dump
	 * @param string $database, ex: 'Chasm_Db'
	 * @return boolean
	 */
   function sql_queries($queries, $database = '')
	{
		$success = array();

		if ( !is_blank($database) )
		{
      	ReportMySQLError(mysql_select_db($database), mk_empty_string(), 
				$database, true);

			$success[] = false;
		}

		// convert $queries to an SQL array
		$query_array = mquery_str2arr($queries);

		foreach ($query_array as $query )
		{
			$success[] = ( sql_query($query) )
				? true
				: false;
		}
	
      return ( !in_array(false, $success) )
			? true
			: false;
	}



   /**
	 * MySQL query() wrapper.
	 *
	 * @param string $query, ex: "SELECT * FROM Table"
	 * @param string $database, ex: 'Chasm_Db'
	 * @return mixed
	 */
   function sql_query($query, $database = '')
	{
		debug_msg($query);

		if ( !is_blank($database) )
		{
      	ReportMySQLError( mysql_select_db($database), mk_empty_string(), 
				$database, true );
		}
			
      $result = mysql_query($query);

      ReportMySQLError($result, mk_empty_string(), $query, true);

      return $result;
   }
	
	
	
	/**
	 * Returns single object pointing to the created query processed.
	 *
	 * @param string $query, ex: "SELECT * FROM Table WHERE id='1'"
	 * @param string $database being queried, ex: Chasm_db
	 * @return mixed object/boolean false
	 */
	function sql_query_object($query, $database = '')
	{
		$object = false;

		$result = sql_query($query, $database);

		if ( $result )
		{
			$object = mysql_fetch_object($result);
			mysql_free_result($result);
		}

		return $object;
	}


	
	/**
	 * Creates array based upon $result if any. Frees MySQL resource if any.
	 *
	 * Method line count could be reduced by restructuring, however efficiency
	 * would be lost. By check result, attribute, etc. counts or values first
	 * then performing do/while loops efficiency is O(n).
	 *
	 * @param Resource $result database query result
	 * @param mixed array/boolean/string $attributes table attribute desired, 
	 * 	ex: false (single attribute 2D array or all attribute 3D array)
	 * 	true (all attributes 3D array), 
	 * 	'id' (single attribute 2D array), 
	 * 	array('id', 'name') (3D array), 
	 * return array
	 */
	function sql_result2array($result, $attributes = false)
	{
		$array = array();
		$array_size = count($array);

		// ensure result
		// get result
		if ( $result && $data = mysql_fetch_assoc($result) )
		{
			// gets associative keys
			$data_keys = array_keys($data);
		
			// gets count of keys
			$data_key_count = count($data_keys);

			// save all attributes
			if ( is_false($attributes) || is_true($attributes) )
			{
				// single attribute, 2D array
				// keys are associative, if only one key, then single attribute
				// $attributes as true denotes keep indexed associative array
				if ( !is_true($attributes) && 1 == $data_key_count )
				{
					do
					{
						$array[] = array_shift($data);
					} while ( $data = mysql_fetch_assoc($result) );
				}

				// multiple attributes, 3D array
				else
				{
					do
					{
						foreach ( $data AS $attribute => $value )
						{
							$array[$array_size][$attribute] = $value;
						}

						$array_size++;
					} while ( $data = mysql_fetch_assoc($result) );
				}
			}

			// save only defined attributes
			else
			{
				// convert $attributes 'xcvb' or 'xcvb, zxcv, reqw' to an array if
				// needed
				$attributes = ( !is_array($attributes) )
					? str2arr($attributes)
					: $attributes;

				// double check that attributes exist as associative keys in $data
				foreach ( $attributes AS $key => $attribute )
				{
					// bail if bad attribute inputed
					if ( !in_array($attribute, $data_keys) )
					{
						return $array;
					}
				}
			
				// single attribute, no special indice
				if ( 1 == sizeof($attributes) )
				{
					$attributes = array_shift($attributes);

					do
					{
						$array[] = $data[$attributes];
					} while ( $data = mysql_fetch_assoc($result) );
				}

				else
				{
					do
					{
						// only input attributes desired
						foreach ( $attributes AS $key => $attribute )
						{
							$array[$array_size][$attribute] = $data[$attribute];
						}

						$array_size++;
					} while ( $data = mysql_fetch_assoc($result) );
				}
			}

			mysql_free_result($result);
		}

		return $array;
	}



	/**
	 * Convert SQL $result to CSV output separated by $separator.
	 *
	 * If different column names are desired than "cleaned" versions of $from
	 * attributes, then use $headers. $separator allows for TSV and other output
	 * styles.
	 *
	 * @param mixed string/array/boolean $headers data column headings, ex:
	 * 	'First Name, Birth Date', array('First Name', 'Birth Date'), false
	 * 	(returns data only)
	 * @param string $separator data separation method, ex: ',', '|', "\t"
	 * @return string
	 */
	function sql_result2csv($result, $headers = false, $separator = ',')
	{
		$csv = '';

		// see if query was successful
		// if so get result back as object
		if ( $data = mysql_fetch_object($result) )
		{
			// build headers or not
			if ( false !== $headers )
			{
				// empty $headers, use column attribute names
				if ( empty($headers) )
				{
					$csv = sql_result2csv_header($result);
				}

				else
				{
					// create column attribute csv via $headers
					// if $headers not an array, make it one
					$headers = ( !is_array($headers) )
						? explode(',', $headers)
						: $headers;

					$headers = arr_rm_whitespace($headers);
					$csv = mk_csv_string($headers, $separator);
				}
			}

			// play with data now
			// cycle through results
			do
			{
				// output data to csv
				$csv .= mk_csv_string($data, $separator);
			} while ( $data = mysql_fetch_object($result) );

			mysql_free_result($result);
		}

		else
		{
			if ( false !== $headers )
			{
				$csv = 'No data found';
		
				// if web served, give link to previous page
				$csv .= ( is_http_host() )
					? ", back to <a href='javascript:history.go(-1);'> previous page</a>."
					: '';
			}
		}
				
		return $csv;
	}



	/**
	 * Converts SQL result to CSV header string by returning only attribute
	 * names.
	 *
	 * @param resource $result of query
	 * @return string
	 */
	function sql_result2csv_header($result)
	{
		$headers = sql_result_field_names($result);

		// simplified array_walk
		foreach ( $headers AS $key => $value)
		{
			$headers[$key] = attribute2header($value);
		}

		return mk_csv_string($headers);
	}



	/**
	 * Returns array of $result's field names.
	 *
	 * @param resource $result of query
	 * @return array
	 */
	function sql_result_field_names($result)
	{
		$field_names = array();

		// use query result to determine headers
		$num_fields = mysql_num_fields($result);

		for ($i = 0; $i < $num_fields; $i++)
		{ 
			// create array of field names
			$field_names[] = mysql_field_name($result, $i);
		}

		return $field_names;
	}



	/**
	 * Returns ResourceID pointing to the SELECT query processed
	 *
	 * @param string $select clause, ex: '*', 'id, name'
	 * @param string $from table(s), ex: 'Table'
	 * @param string $where clause, ex: 'id = 1', 'name = 'Frank' ORDER BY name
	 * 	ASC
	 * @param string $database being queried, ex: Chasm_db
	 * @return ResouceID
	 */
	function sql_select($select, $from, $where = '', $database = '')
	{
		$query = mk_sql_select($select, $from, $where);

		return sql_query($query, $database);
	}



	/**
	 * Returns single object pointing to the created query processed.
	 *
	 * @param string $select clause, ex: '*', 'id, name'
	 * @param string $from table(s), ex: 'Table'
	 * @param string $where clause, ex: 'id = 1', 'name = 'Frank' ORDER BY name
	 * 	ASC
	 * @param string $database being queried, ex: Chasm_db
	 * @return mixed object/boolean false
	 */
	function sql_select_object($select, $from, $where = '', $database = '')
	{
		$query = mk_sql_select($select, $from, $where);

		return sql_query_object($query, $database);
	}

	
	
	/**
	 * Returns an integer of an unique sequence id.
	 *
	 * @param string sequence table
	 * @return integer
	 */
	function sql_sequence($table)
	{
		$sequence = false;

		$select = 'id + 1 AS id';

		$result = sql_select_object($select, $table);

		if ( $result && isset($result->id) )
		{
			$query = "UPDATE $table";
			$sequence = $result->id;
		}

		// no sequence table tuple
		else
		{
			$query = "INSERT INTO $table";
			$sequence = 1;
		}

		$query .= " SET id = $sequence";
		@sql_query($query);

		return $sequence;
	}



	/**
	 * Returns general search form for some database table.
	 *
	 * @param array data container
	 * 	database, table, action, input_shown, input_hidden
	 * @return string
	 */
	function sql_search_form($data)
	{
		$str = '';

		// connect to database
		// query DESCRIBE XYZ
		// cycle through attribute results
		// Field, Type, Null, Key, Default, Extra
		// create some input field based upon type
		// numerics get >, >=, <, <=, =, !=, BETWEEN, NOT BETWEEN
		// text get =, !=
		// enum get's preset values
		// size's set max size

		$options_start = array(
			'='		=> '=',
			'!='		=> '!='
		);

		$options_finish = array(
			'BETWEEN'			=> 'BETWEEN',
			'NOT_BETWEEN'		=> 'NOT BETWEEN',
			'IN'		=> 'IN',
			'NOT_IN'	=> 'NOT IN',
			'IS_NULL'	=> 'IS NULL',
			'IS_NOT_NULL'	=> 'IS NOT NULL'
		);

		$options_num_part = array(
			'>'		=> '&gt;',
			'>='		=> '&gt;=',
			'<'		=> '&lt;',
			'<='		=> '&lt;='
		);

		$options_text_part = array(
			'LIKE'		=> 'LIKE',
			'NOT_LIKE'		=> 'NOT LIKE'
		);

		$options_num = array_union( $options_start, $options_num_part );
		$options_num = array_union( $options_num, $options_finish );

		$options_text = array_union( $options_start, $options_text_part );
		$options_text = array_union( $options_text, $options_finish );

		$fields_text = array(
			'char',
			'varchar',
			'tinyblob',
			'tinytext',
			'mediumblob',
			'mediumtext',
			'blob',
			'text',
			'longblob',
			'longtext',
			'enum',
			'set',
			'CHAR',
			'VARCHAR',
			'TINYBLOB',
			'TINYTEXT',
			'MEDIUMBLOB',
			'MEDIUMTEXT',
			'BLOB',
			'TEXT',
			'LONGBLOB',
			'LONGTEXT',
			'ENUM',
			'SET'
		);

		$fields = array();
		$fields[] = '';

		$query = 'DESCRIBE ' . $data['table'];
		$result = sql_query($query, $data['database']);

		if ( $result && $rows = mysql_fetch_object($result) )
		{
			$str .= "
				<form action='${data['action']}' method='post'>
					<table cellspacing = '0'>
						<tr style='font-weight: bold; text-align: center;'>
							<td>Select</td>
							<td>Field (type)</td>
							<td>Comparative</td>
							<td>Value</td>
							<td>Between Value</td>
						</tr>
			";

			$key = 0;

			do
			{
				$fields[$rows->Field] = $rows->Field;
				$field = str2header($rows->Field);
				$type = preg_replace('/(\(.+|[^[:alpha:]])/i', '', $rows->Type);

				$operator_selected = ( isset($data['operator'][$key]) )
					? $data['operator'][$key]
					: '=';

				$operator_options = ( !in_array($type, $fields_text) )
					? mk_select_list('operator[]', $options_num, $operator_selected)
					: mk_select_list('operator[]', $options_text, 
						$operator_selected);
						  		
				if ( isset($data['select']) )
				{
					$checked = ( in_array($rows->Field, $data['select']) )
						? "checked='checked'"
						: '';
				}

				else
				{
					$checked = "checked='checked'";
				}

				$value = ( isset($data['value'][$key]) )
					? $data['value'][$key]
					: '';

				$value2 = ( isset($data['value2'][$key]) )
					? $data['value2'][$key]
					: '';

				$str .= "
					<tr>
						<td>
							<input type='checkbox' name='select[]' 
								value='{$rows->Field}' $checked />
						</td>
						<td>
							$field ($type)
						</td>
						<td style='text-align: right;'>$operator_options</td>
						<td>
							<input type='text' name='value[]' value='$value'	/>
							<input type='hidden' name='field[]' 
								value='{$rows->Field}' />
							<input type='hidden' name='type[]' 
								value='{$type}' />
						</td>
						<td>
							<input type='text' name='value2[]' value='$value2'	/>
						</td>
					</tr>
				";

				$key++;
			} while ( $rows = mysql_fetch_object($result) );

			mysql_free_result($result);

			$order_by_selected = ( isset($data['order_by']) )
				? $data['order_by']
				: '';
		
			$order_by_dir = ( isset($data['order_by_dir']) )
				? $data['order_by_dir']
				: 'ASC';

			if ( 'ASC' == $order_by_dir )
			{
				$order_by_asc = "checked='checked'";
				$order_by_desc = '';
			}

			else
			{
				$order_by_asc = '';
				$order_by_desc = "checked='checked'";
			}
		
			$order_by = mk_select_list('order_by', $fields, $order_by_selected);

			$str .= "
				<tr>
					<td colspan='5'>
						<hr />
					</td>
				</tr>

				<tr>
					<td>
						&nbsp;
					</td>
					<td>
						Order By
					</td>
					<td style='text-align: right;'>
						$order_by
					</td>
					<td colspan='2'>
						Ascending 
						<input type='radio' name='order_by_dir'
							$order_by_asc value='ASC' />
						Descending 
						<input type='radio' name='order_by_dir' value='DESC'
					  		$order_by_desc	/>
					</td>
				</tr>
			";

			$input_shown = ( isset($data['input_shown']) )
				? $data['input_shown']
				: false;

			if ( $input_shown )
			{
				$str .= "
					<tr>
						<td>
							&nbsp;
						</td>
						<td colspan='4'>
							$input_shown
						</td>
					</tr>
				";
			}

			$input_hidden = ( isset($data['input_hidden']) )
				? $data['input_hidden']
				: '';

			$str .= "
						<tr>
							<td>
								&nbsp;
							</td>
							<td colspan='4'>
								<input type='hidden' name='database'
									value=${data['database']} />
								<input type='hidden' name='table'
									value=${data['table']} />
								<input type='hidden' name='action'
									value=${data['action']} />
								$input_hidden
								<input type='submit' />
								<input type='reset' />
							</td>
						</tr>
					</table>
				</form>
			";
		}
				
		return $str;
	}



	/**
	 * Given array of sql_search_form results, create a SQL SELECT string
	 *
	 * @param array search contents
	 * @param boolean as array: select, from, where
	 * @return string
	 */
	function sql_search_select($data, $as_array = true)
	{
		$select = implode(',', $data['select']);
		$from = $data['database'] . '.' . $data['table'];
		$where = '1 = 1 ';

		foreach ( $data['value'] AS $key => $val )
		{
			$field = $data['field'][$key];
			$op = $data['operator'][$key];
			$val2 = $data['value2'][$key];

			$null = ( is_false( strpos($op, 'NULL') ) )
				? false
				: true;

			$not = ( is_false( strpos($op, 'NOT') ) )
				? false
				: true;

			if ( !is_blank($val) || $null )
			{
				// create line break
				$where .= chr(10);

				// encapsulate text as needed
				if ( is_false( strpos($op, 'IN') ) )
				{
					$val = ( is_numeric($val) )
						? $val
						: "'" . $val . "'";
					
					$val2 = ( is_numeric($val2) )
						? $val2
						: "'" . $val2 . "'";
				}

				switch ( $op )
				{
					case 'BETWEEN':
					case 'NOT_BETWEEN':
						$where .= mk_sql_between($field, $val, $val2, $not);
						break;

					case 'IN':
					case 'NOT_IN':
						// convert to array however broken up
						$val = str2arr($val);
						$where .= mk_sql_in($field, $val, $not);
						break;

					case 'IS_NULL':
					case 'IS_NOT_NULL':
						$op = str_replace('_', ' ', $op);
						$where .= "AND $field $op";
						break;

					default:
						$op = str_replace('_', ' ', $op);
						$where .= "AND $field $op $val";
						break;
				}
			}
		}

		$order_by = $data['order_by'];
		$order_by_dir = $data['order_by_dir'];

		if ( $order_by )
		{
			$where .= chr(10) . 'ORDER BY ' . $order_by . ' ' . $order_by_dir;
		}


		return ( $as_array )
			? compact('select', 'from', 'where')
			: mk_sql_select($select, $from, $where);
	}
	
?>
