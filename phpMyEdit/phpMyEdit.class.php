<?php

/*
 * phpMyEdit - MySQL table editor
 *
 * phpMyEdit.class.php - main table editor class definition file
 * ____________________________________________________________
 *
 * Copyright (c) 1999-2002 John McCreesh <jpmcc@users.sourceforge.net>
 * Copyright (c) 2001-2002 Jim Kraai <jkraai@users.sourceforge.net>
 * Versions 5.0 and higher developed by Ondrej Jombik <nepto@php.net>
 * Copyright (c) 2002 Platon SDG, http://www.platon.sk/
 * All rights reserved.
 *
 * See README file for more information about this software.
 * See COPYING file for license information.
 *
 * Download the latest version from
 * http://www.platon.sk/projects/phpMyEdit/
 */

/* $Platon: phpMyEdit/phpMyEdit.class.php,v 1.32 2002/11/24 17:01:11 nepto Exp $ */

/*	phpMyEdit intro {{{ */
/*
	This is a generic table editing program. The table and fields to be
	edited are defined in the calling program.

	This program works in three passes. Pass 1 (the last part of
	the program) displays the selected MySQL table in a scrolling table
	on the screen. Radio buttons are used to select a record for editing
	or deletion. If the user chooses Add, Change, or Delete buttons,
	Pass 2 starts, displaying the selected record. If the user chooses
	the Save button from this screen, Pass 3 processes the update and
	the display returns to the original table view (Pass 1).

	version 3.5 - 06-May-01
	
	important variables passed between calls to this program
	
	$fm     first record to display
	$inc    no of records to display (SELECT ... LIMIT $fm,$inc)
	$fl     is the filter row displayed (boolean)
	$rec    unique id of record selected for editing
	$qf0,.. value of filter for column 0
	$qfn    value of all filters used during the last pass
	$sfn    sort field number (- = descending sort order)
	$operation	operation to do: Add, Change, Delete
	$message	informational message to print
	$filter filter query
	$sw     filter display/hide button

	$prev, $next  navigation buttons
	$labels narrative for buttons, etc

	Conversion to PHP Classes by Pau Aliagas (pau@newtral.com)

	ToDo:
	'Copy' button 

	Aggregates:
	nonworking code commented out in list_table()
	doesn't work yet

	Query Building:

	Multi-Part Date Handling:
	Finish converting date handling to internal date handling functions
	Abstract date field gathering to get rid of _many_ redundant lines of code
	There was some kludged fix for dateformat'ting where '%'s are removed
	Better support for more date format macros
	Better documentation for valid date format macros

	Multi-Language support:
	Finish implementing language labels
	Use browser-supplied language if available
	Allow programmer override in setup.php generated .inc file
	Add 'Search' and 'Go!' to labels array

	Data Validation:
	Expand JS field validation to match JS regexes
	Create PHP field validation to match PHP regexes

	Change Tracking/Notification:
	Add change notification (via mail()) support
		Don't die if mail() not available

	CSS:
	Document & solicit feedback to standardize class names

	Even/Odd Coloring:
	Move to CSS
	Put values in setup.php generated file

	Timer Class:
	Solicit user input whether to put timer class into this lib
*/
/* }}} */

if (@include_once dirname(__FILE__).'/timer.class') {
	$phpMyEdit_timer = new timerClass();
}

if (! function_exists('array_search')) { /* {{{ */
	function array_search($needle, $haystack)
	{
		foreach ($haystack as $key => $value) {
			if ($needle == $value)
				return $key;
		}
		return false;
	}
} /* }}} */

class phpMyEdit
{
	var $hn;        // hostname
	var $un;        // user name
	var $pw;        // password
	var $db;        // database
	var $tb;        // table
	var $dbh;       // database handle

	var $key;       // Name of field which is the unique key
	var $key_type;  // Type of key field (int/real/string/date etc)
	var $key_delim;

	var $inc;       // no of records to display (SELECT ... LIMIT $fm, $inc)
	var $fm;        // first record to display
	var $fl;        // is the filter row displayed (boolean)

	var $options;   // Options for users: A(dd) C(hange) D(elete) F(ilter) V(iew) co(P)y U(nsorted)
	var $fdd;       // field definitions
	var $qfn;       // value of all filters used during the last pass
	var $sfn;       // sort field number (- = descending sort order)

	var $rec;       // no. of record selected for editing
	var $prev;      // navigation buttons
	var $next;
	var $sw;        // filter display/hide button
	var $labels;    // labels for buttons, etc (multilingual)
	var $operation; // operation to do: Add, Change, Delete
	var $message;   // informational message to print

	var $saveadd;
	var $moreeadd;
	var $savechange;
	var $savedelete;

	var $fds;       // sql field names
	var $num_fds;   // number of fields

	var $logtable;   // name of optional logtable
	var $navigation; // navigation style
	var $qs;		// query string
	
	function debug_var($name, $val) /* {{{ */
	{
		if (is_array($val) || is_object($val)) {
			echo "<pre>$name\n";
			ob_start();
			//print_r($val);
			var_dump($val);
			$content = ob_get_contents();
			ob_end_clean();
			echo htmlspecialchars($content);
			echo "</pre>\n";
		} else {
			echo 'debug_var()::<i>'.htmlspecialchars($name).'</i>::<b>'
				.htmlspecialchars($val).'</b>::'."<br>\n";
		}
	} /* }}} */

	function myquery($qry, $line = 0, $debug = 0) /* {{{ */
	{
		global $debug_query;
		if ($debug_query || $debug) {
			$line = intval($line);
			echo '<h4>MySQL query at line '.$line.'</h4>'.htmlspecialchars($qry).'<hr>'."\n";
		}
		$this->elog("qry: $qry",$line);
		$ret = @mysql_db_query($this->db, $qry, $this->dbh);
		if (! $ret) {
			$this->elog(mysql_errno($this->dbh).': '.mysql_error($this->dbh).' in '.$qry, __LINE__);
		}
		return $ret;
	} /* }}} */

	function encode($field,$str) /* {{{ */
	{
		if (isset($field['dbencode'])) {
			return eval(
					'return '
					.$field['dbencode']
					.'(\''.$str.'\');');
		} else {
			return $str;
		}
	} /* }}} */

	function elog($str,$line) /* {{{ */
	{
		error_log(__FILE__.":$line::\n$str",0);
		return true;
	} /* }}} */

	function make_language_labels($language) /* {{{ */
	{
		// just try the first language and variant
		// this isn't content-negotiation rfc compliant
		$language = strtoupper(substr($language,0,5));

		// try the full language w/ variant
		$file = $this->dir['lang'].'PME.lang.'.$language.'.inc';

		if (! file_exists($file)) {
			// try the language w/o variant
			$file = $this->dir['lang'].'PME.lang.'.substr($language,0,2).'.inc';
		}
		if (! file_exists($file)) {
			// default to classical English
			$file = $this->dir['lang'].'PME.lang.EN.inc';
		}
		$ret = @include($file);
		// XXX: temporary "Apply" keyword hack -- add it into lang files
		!isset($ret['Apply']) && $ret['Apply']  = 'Apply';
		!isset($ret['of']) && $ret['of']  = '/';
		$small = array(
				'Search' => 'v',
				'Hide'   => '^',
				'Clear'  => 'X',
				'Query'  => htmlspecialchars('>'));
		if ((!$this->nav_text_links() && !$this->nav_graphic_links())
				|| !isset($ret['Search']) || !isset($ret['Query'])
				|| !isset($ret['Hide'])   || !isset($ret['Clear'])) {
			foreach ($small as $key => $val) {
				$ret[$key] = $val;
			}
		}
		return $ret;
	} /* }}} */

	function set_values_from_table($field_num, $prepend = '') /* {{{ */
	{
		/*
		   echo "$field_num, ";
		   var_dump($prepend);
		   echo '<pre>';
		   var_dump($this->fdd);
		   echo '</pre>';
		 */
		if( isset($this->fdd[$field_num]['values']['db']) ) {
			$db = $this->fdd[$field_num]['values']['db'];
		} else {
			$db = $this->db;
		}
		 $table = ( isset($this->fdd[$field_num]['values']['table']) )
			 ? $this->fdd[$field_num]['values']['table']
			 : '';
		 $key = ( isset($this->fdd[$field_num]['values']['column']) )
			 ? $this->fdd[$field_num]['values']['column']
			 : '';
		 $desc = ( isset($this->fdd[$field_num]['values']['description']) )
			 ? $this->fdd[$field_num]['values']['description']
			 : '';
		$qparts['type']   = 'select';
		if ($table) {
			$qparts['select'] = 'DISTINCT '.$key;
			if ($desc) {
				//- $qparts['select'] .= ','.$desc;
				//- $qparts['orderby'] = $desc;
				//	Changes 08/08/02 Shaun Johnston
				if (is_array($desc)) {
					$qparts['select'] .= ',CONCAT('; // )
					$num_cols = sizeof($desc['columns']);
					for ($i = 0; $i <= $num_cols; $i++) {
						if ( isset($desc['columns'][$i]) )
						{
						   $qparts['select'] .= $desc['columns'][$i];
						   if ($desc['divs'][$i]) {
							   $qparts['select'] .= ',"'.$desc['divs'][$i].'"';
						   }
						}
						if ($i < ($num_cols - 1)) {
							$qparts['select'] .= ',';
						}
					}
					$qparts['select'] .= ') AS select_alias_'.$field_num;
					$qparts['orderby'] = empty($desc['orderby'])
						? 'select_alias_'.$field_num : $desc['orderby'];
				} else {
					$qparts['select'] .= ','.$desc;
					$qparts['orderby'] = $desc;
				}
			} else {
				if ($key) {
					$qparts['orderby'] = $key;
				}
			}
			//$qparts['from'] = "$db.$table.$sel;
			$qparts['from'] = "$db.$table";
			$qparts['where'] = (
			   isset($this->fdd[$field_num]['values']['filters']) )
			   ? $this->fdd[$field_num]['values']['filters']
			   : '';
			if ( isset($this->fdd[$field_num]['values']['orderby']) ) {
				$qparts['orderby'] = $this->fdd[$field_num]['values']['orderby'];
			}
		} else { /* simple value extraction */
			$qparts['select'] = 'DISTINCT '.$this->fds[$field_num];
			$qparts['from']   = $this->db.'.'.$this->tb;
		}
		$values = array();
		$res    = $this->myquery($this->query_make($qparts), __LINE__);
		while ($row = @mysql_fetch_array($res, MYSQL_NUM)) {
			$values[$row[0]] = $desc ? $row[1] : $row[0];
		}
		$values2 = ( isset($this->fdd[$field_num]['values2']) )
			? $this->fdd[$field_num]['values2']
			: '';
		is_array($values2) && $values = $values2 + $values;
		is_array($prepend) && $values = $prepend + $values;
		/* old wrong prepending -- REMOVE ME at December 2002
		if (is_array($prepend)) {
			$values[$prepend[0]] = $prepend[1];
		}
		*/
		return $values;
	} /* }}} */

	/*
	 * get the table/field name
	 */
	function fqn($field, $use_qfx = false, $dont_desc = false, $dont_cols = false) /* {{{ */
	{
		if (is_string($field)) {
			$field = array_search($field, $this->fds);
		}

		// on copy/change always use simple key retrieving
		if ($this->add_operation()
				|| $this->copy_operation()
				|| $this->change_operation()) {
				$ret = 'Table0.'.$this->fds[$field];
		} else {
			if (isset($this->fdd[$field]['expression'])) {
				$ret = $this->fdd[$field]['expression'];
			} elseif ( isset($this->fdd[$this->fds[$field]]['values']['description']) && ! $dont_desc) {
				//	Changed 06/08/02 Shaun Johnston
				$desc = $this->fdd[$this->fds[$field]]['values']['description'];
				if (is_array($desc)) {
					$ret = 'CONCAT('; // )
					$num_cols = sizeof($desc['columns']);
					for ($i = 0; $i < $num_cols; $i++) {
						$ret .= 'JoinTable'.$field.'.'.$desc['columns'][$i];
						if ($desc['divs'][$i]) {
							$ret .= ',"'.$desc['divs'][$i].'"';
						}
						if ($i < ($num_cols - 1)) {
							$ret .= ',';
						}
					}
					$ret .= ')';
				} else {
					$ret = 'JoinTable'.$field.'.'.$this->fdd[$this->fds[$field]]['values']['description'];
				}
			} elseif ( isset($this->fdd[$this->fds[$field]]['values']['column']) && ! $dont_cols) {
				$ret = 'JoinTable'.$field.'.'.$this->fdd[$this->fds[$field]]['values']['column'];
			} else {
				$ret = 'Table0.'.$this->fds[$field];
			}
			// TODO: not neccessary, remove me!
			if ( isset($this->fdd[$this->fds[$field]]['values2']) && is_array($this->fdd[$this->fds[$field]]['values2']) ) {
			}
		}

		// what to do with $format XXX
		if ($use_qfx)
			$ret = 'qf'.$field;
		// return the value
		return $ret;
	} /* }}} */

	function create_column_list() /* {{{ */
	{
		$fields = array();
		for ($k = 0; $k < $this->num_fds; $k++) {
			if (! $this->displayed[$k] && $k != $this->key_num)
				continue;
			if ($this->col_is_date($k)) {
				//$fields[] = 'DATE_FORMAT('.$this->fqn($k).',"%Y%m%d%H%i%s") AS qf'.$k;
				$fields[] = $this->fqn($k).' AS qf'.$k;
			} else {
				$fields[] = $this->fqn($k).' AS qf'.$k;
				if ($this->col_has_values($k)) {
					$fields[] = $this->fqn($k, false, true, true).' AS qf'.$k.'_idx';
				}
				//echo '[['.$this->fqn($k).' AS qf'.$k.']]<br>';
			}
		}
		return join(',',$fields);
	} /* }}} */

	function query_make($parts) /* {{{ */
	{
		foreach ($parts as $k => $v) {
			$parts[$k] = trim($parts[$k]);
		}
		
		$parts['DISTINCT'] = ( isset($parts['DISTINCT']) )
			? $parts['DISTINCT']
			: false;
		
		$parts['select'] = ( isset($parts['select']) )
			? $parts['select']
			: '';
		
		$parts['groupby'] = ( isset($parts['groupby']) )
			? $parts['groupby']
			: '';
		
		$parts['having'] = ( isset($parts['having']) )
			? $parts['having']
			: '';
		
		$parts['orderby'] = ( isset($parts['orderby']) )
			? $parts['orderby']
			: '';
		
		$parts['limit'] = ( isset($parts['limit']) )
			? $parts['limit']
			: '';
		
		$parts['procedure'] = ( isset($parts['procedure']) )
			? $parts['procedure']
			: '';
		
		switch ($parts['type']) {
			case 'select':
				$ret  = 'SELECT ';
				if ($parts['DISTINCT'])
					$ret .= 'DISTINCT ';
				$ret .= $parts['select'];
				$ret .= ' FROM '.$parts['from'];
				if ($parts['where'] != '')
					$ret .= ' WHERE '.$parts['where'];
				if ($parts['groupby'] != '')
					$ret .= ' GROUP BY '.$parts['groupby'];
				if ($parts['having'] != '')
					$ret .= ' HAVING '.$parts['having'];
				if ($parts['orderby'] != '')
					$ret .= ' ORDER BY '.$parts['orderby'];
				if ($parts['limit'] != '')
					$ret .= ' LIMIT '.$parts['limit'];
				if ($parts['procedure'] != '')
					$ret .= ' PROCEDURE '.$parts['procedure'];
				break;
			case 'update':
				$ret  = 'UPDATE '.$parts['table'];
				$ret .= ' SET '.$parts['fields'];
				if ($parts['where'] != '')
					$ret .= ' WHERE '.$parts['where'];
				break;
			case 'insert':
				$ret  = 'INSERT INTO '.$parts['table'];
				$ret .= ' VALUES '.$parts['values'];
				break;
			case 'delete':
				$ret  = 'DELETE FROM '.$parts['table'];
				if ($parts['where'] != '')
					$ret .= ' WHERE '.$parts['where'];
				break;
			default:
				die('unknown query type');
				break;
		}
		return $ret;
	} /* }}} */

	function create_join_clause() /* {{{ */
	{
		$tbs[] = $this->tb;
		$join = $this->tb.' AS Table0';
		for ($k = 0,$numfds = sizeof($this->fds); $k<$numfds; $k++) {
			$field = $this->fds[$k];
			if( isset($this->fdd[$field]['values']['db']) ) {
				$db = $this->fdd[$field]['values']['db'];
			} else {
				$db = $this->db;
			}
			$table = ( isset($this->fdd[$field]['values']['table']) )
				?  $this->fdd[$field]['values']['table']
				: '';
			$id    = ( isset($this->fdd[$field]['values']['column']) )
				?  $this->fdd[$field]['values']['column']
				: '';
			$desc  = ( isset($this->fdd[$field]['values']['description']) )
				?  $this->fdd[$field]['values']['description']
				: '';

			if ($desc != '' || $id != '') {
				$alias = 'JoinTable'.$k;
				if (!in_array($alias,$tbs)) {
					$join .= 
						" LEFT OUTER JOIN $db.".
						$table.
						' AS '.$alias.
						' ON '.$alias.
						'.'.$id.
						'='.'Table0.'.$field;
					$tbs[]=$alias;
				}
			}
		}
		return $join;
	} /* }}} */

	function make_where_from_query_opts($qp='') /* {{{ */
	{
		if ($qp == '')
			$qp = $this->query_opts;
		$where = array();
		foreach ($qp as $field => $ov) {
			$where[] = sprintf('%s %s %s',$field,$ov['oper'],$ov['value']);
		}

		// Add any coder specified filters
		if ($this->filters)
			$where[] = '('.$this->filters.')';
		if (count($where) > 0)
			return join(' AND ',$where);

		return false;
	} /* }}} */

	function make_text_where_from_query_opts($qp='') /* {{{ */
	{
		if ($qp == '')
			$qp = $this->query_opts;
		$where = array();
		foreach ($qp as $field => $ov) {
			$where[] = sprintf('%s %s %s',$field,$ov['oper'],$ov['value']);
		}

		if (count($where) > 0)
			return str_replace('%','*',join(' AND ',$where));

		return false;
	} /* }}} */

	/*
	 * functions for get/post/query args
	 */

	function gather_post_vars() /* {{{ */
	{
	   $pv = array();

		foreach ($_POST as $key => $val) {
			if ($val != '' && $val != '*') {
				$pv[$key] = $val;
			}
		}
		$this->pv = $pv;
	} /* }}} */

	function gather_query_opts() /* {{{ */
	{
		// gathers query options into an array, $this->query_opts

		$query_opts = array();
		$qo = array();

		for ($k = 0; $k < $this->num_fds; $k++) {
			$l    = 'qf'.$k;
			$lc   = 'qf'.$k.'_comp';
			$$l   = $this->get_cgi_var($l);
			$$lc  = $this->get_cgi_var($lc);
			$m    = $this->web2plain($$l);  // get the field name and value
			$mc   = $this->web2plain($$lc); // get the comparison operator for numeric/date types
			$type = $this->fdd[$k]['type'];

			if ($m == '') {
				continue;
			}
			if (is_array($m)) { // multiple selection has been used
				if (!in_array('*',$m))	{ // one '*' in a multiple selection is all you need
					$qf_op = '';
					foreach (array_keys($m) as $key) {
						if ($qf_op == '') {
							$qf_op   = 'IN';
							$qf_val  = "'".addslashes($m[$key])."'";
							$afilter =" IN ('".addslashes($m[$key])."'";
						} else {
							$afilter = $afilter.",'".addslashes($m[$key])."'";
							$qf_val .= ",'".addslashes($m[$key])."'";
						}
					}
					$afilter = $afilter.')';
					// XXX: $dont_desc and $dont_cols hack
					$dont_desc = isset($this->fdd[$k]['values']['description']);
					$dont_cols = isset($this->fdd[$k]['values']['column']);
					$qo[$this->fqn($k, false, $dont_desc, $dont_cols)] =
						array( 'oper'  => $qf_op, 'value' => '('.$qf_val.')');
				}
			} else {
				$afilter = $m;
				if ($afilter != '*') {
					/* XXX: This is ugly fqn() hack. We must pass third
					   $dont_desc parameter to fqn() method, as far as we
					   want to return not description column, but ID one. */
					if ($this->fdd[$k]['values']['description']) {
						// DEBUG
						// echo htmlspecialchars(' k = '.$k.' | fqn($k) = '.$this->fqn($k, false, true));
						$qo[$this->fqn($k, false, true, true)] =
							array('oper'  => '=', 'value' => "'".$afilter."'");
					} elseif ($this->fdd[$k]['values']['column']) {
						$qo[$this->fqn($k, false, true, true)] =
							array('oper'  => '=', 'value' => "'".$afilter."'");
					} elseif ($this->col_is_string($k)) {
						// massage the filter for a string comparison
						if (($afilter != '') AND ($afilter != '*')) {
							$afilter = addslashes(addslashes('%'
										.str_replace('*', '%', $afilter).'%'));
							$qo[$this->fqn($k)] =
								array('oper'  => 'like', 'value' => "'".$afilter."'");
						}
					} elseif ($this->col_is_number($k) && ($$lc != '')) {
						if ($$lc != '') {
							$qo[$this->fqn($k)] =
								array('oper'  => $mc, 'value' => $afilter);
						}
					} elseif ($this->col_is_date($k)) {
#if ($$lc != '') {
#	$val = $this->gather_date_fields_into_type($$l,$type);
#	$val = $this->mdate_set(date($this->mdate_masks[$type],$this->mdate_getFromPost($k)),$type); 
#	$val = $this->mdate_getFromPost($k); 
#	if ($val != '') {
#		$qo[$this->fqn($k)] =
#			array( 'oper'  => $mc, 'value' => '"'.$val.'"');
#	}
#}
# massage the filter for a string comparison
						if (($afilter != '') AND ($afilter != '*')) {
							$afilter = addslashes(addslashes('%'
										.str_replace ('*', '%', $afilter).'%'));
							$qo[$this->fqn($k)] =
								array('oper'  => 'like', 'value' => "'".$afilter."'");
						}
					} elseif($this->col_has_values($k)) {
						//debug_var('col_is_string',$this->fdd[$k]['name'].'::'.$this->fdd[$k]['type']);
						$qo[$this->fqn($k)] =
							array( 'oper'  => '=', 'value' => "'".$afilter."'");
					} else {
						// unknown (to mysql/php interface) field type massage the filter for a string comparison
						$afilter = addslashes(addslashes('%'.str_replace ('*', '%', $afilter).'%'));
						$qo[$this->fqn($k)] =
							array('oper'  => 'like', 'value' => "'".$afilter."'");
					}
				}
			}
		} // for

		$this->query_opts = $qo;
	} // gather_query_opts  /* }}} */

	function gather_get_vars() /* {{{ */
	{
	   $ignore_vars = array('fl', 'fm', 'operation', 'qfn', 'rec', 'sfn');
		$vals = array();

		$this->qs = ( isset($_SERVER['QUERY_STRING']) )
			? $_SERVER['QUERY_STRING']
			: '';
			
		 if ( '' != $this->qs )
		 {
		   $parts = split('&',$this->qs);
			foreach ($parts as $part) {
				list($key,$val) = split('=',$part,2);

				if ( !in_array($key, $ignore_vars) )
				{
					$vals[$key] = $val;
				 }
			}

			$this->qs = '';

			$vals_size = sizeof($vals);
			$i = 1;

			// rebuild query string
			foreach ( $vals AS $key => $val )
			{
			   $this->qs .= ( $i < $vals_size )
			   	? "$key=$val&"
			   	: "$key=$val";

				$i++;
			}
		}

		$this->get_opts = $vals;
	} /* }}} */



	/**
	 * qs($query = true)
	 *
	 * Return string containing QUERY_STRING preceeded by query or continuation
	 * character.
	 *
	 * @param boolean $query, ex: true (?), false (&)
	 * @return string
	 */
   function qs($query = true)
   {
	$string = '';

	  if ( '' != $this->qs )
	  {
		 $string = $query
			? '?'
			: '&';

		 $string .= $this->qs;
	  }

	  return $string;
   }

	   

	function unify_opts() /* {{{ */
	{
		$all_opts = array();
		if (count($this->query_opts) > 0) {
			foreach ($this->query_opts as $key=>$val)
				$all_opts[$key] = $val;
		}
		if (count($this->pv) > 0) {
			foreach ($this->pv as $key=>$val)
				$all_opts[$key] = $val;
		}
		if (count($this->get_opts) > 0) {
			foreach ($this->get_opts as $key=>$val)
				$all_opts[$key] = $val;
		}
		$this->all_opts = $all_opts;
	} /* }}} */

	/*
	 * type functions
	 */

	function col_is_date($k)    { return in_array($this->fdd[$k]['type'], $this->dateTypes  ); }
	function col_is_number($k)  { return in_array($this->fdd[$k]['type'], $this->numberTypes); }
	function col_is_string($k)  { return in_array($this->fdd[$k]['type'], $this->stringTypes); }
	function col_is_set($k)     { return $this->fdd[$k]['type'] == 'set'; }
	function col_has_values($k) { return isset($this->fdd[$k]['values']) || isset($this->fdd[$k]['values2']); }

	/*
	 * functions for indicating whether navigation style is enabled
     */

	function nav_buttons()       { return stristr($this->navigation, 'B'); }
	function nav_text_links()    { return stristr($this->navigation, 'T'); }
	function nav_graphic_links() { return stristr($this->navigation, 'G'); }
	function nav_up()            { return stristr($this->navigation, 'U'); }
	function nav_down()          { return stristr($this->navigation, 'D'); }

	/*
	 * functions for indicating whether operations are enabled
	 */

	function initial_sort_suppressed() { return (stristr ($this->options, 'I')); }
	function add_enabled()    { return stristr($this->options, 'A'); }
	function change_enabled() { return stristr($this->options, 'C'); }
	function delete_enabled() { return stristr($this->options, 'D'); }
	function filter_enabled() { return stristr($this->options, 'F'); }
	function view_enabled()   { return stristr($this->options, 'V'); }
	function copy_enabled()   { return stristr($this->options, 'P') && $this->add_enabled(); }
	function hidden($k)       { return stristr($this->fdd[$k]['options'],'H'); }
	function password($k)     { return stristr($this->fdd[$k]['options'],'W'); }
	function readonly($k)     { return stristr($this->fdd[$k]['options'],'R') ||
		isset($this->fdd[$k]['expression']); }

	function add_operation()    { return $this->operation == $this->labels['Add']    && $this->add_enabled();    }
	function change_operation() { return $this->operation == $this->labels['Change'] && $this->change_enabled(); }
	function copy_operation()   { return $this->operation == $this->labels['Copy']   && $this->copy_enabled();   }
	function delete_operation() { return $this->operation == $this->labels['Delete'] && $this->delete_enabled(); }
	function view_operation()   { return $this->operation == $this->labels['View']   && $this->view_enabled();   }
	function filter_operation() { return $this->fl && $this->filter_enabled(); }
	function next_operation()	{ return $this->next == $this->labels['Next']; }
	function prev_operation()	{ return $this->prev == $this->labels['Prev']; }

	function is_values2($k, $val = 'X') /* {{{ */
	{
		return $val === null ||
			(isset($this->fdd[$k]['values2']) && !isset($this->fdd[$k]['values']['table']));
	} /* }}} */

	function processed($k) /* {{{ */
	{
		$options = @$this->fdd[$k]['options'];
		if (! isset($options))
			return true;
		return /* empty($options) || */
			// XXX: woof woof woof hack, probably brokes BC, but here BC == BB (bad behaviour)
			($this->saveadd    == $this->labels['Save']  && stristr($options, 'A')) ||
			($this->moreadd    == $this->labels['More']  && stristr($options, 'A')) ||
			($this->savechange == $this->labels['Save']  && stristr($options, 'C')) ||
			($this->morechange == $this->labels['Apply'] && stristr($options, 'C')) ||
			($this->savechange == $this->labels['Save']  && stristr($options, 'P')) ||
			($this->savedelete == $this->labels['Save']  && stristr($options, 'D'));
	} /* }}} */

	function displayed($k) /* {{{ */
	{
		if (is_numeric($k)) {
			$k = $this->fds[$k];
		}
		$options = $this->fdd[$k]['options'];

		if (! isset($options))
			return true;
		if ($this->hidden($k))
			return false;

		return
			($this->add_operation()    && stristr($options, 'A')) ||
			($this->view_operation()   && stristr($options, 'V')) ||
			($this->change_operation() && stristr($options, 'C')) ||
			($this->copy_operation()   && stristr($options, 'P')) ||
			($this->delete_operation() && stristr($options, 'D')) ||
			((stristr($options,'L')
			  || ($this->filter_operation() && stristr($options, 'F'))) &&
			 ! $this->add_operation()    &&
			 ! $this->view_operation()   && 
			 ! $this->change_operation() &&
			 ! $this->copy_operation()   &&
			 ! $this->delete_operation());
	} /* }}} */

	/*
	 * Create JavaScripts
	 */

	function create_javascripts() /* {{{ */
	{
		/*
		   Need a lot of work in here
		   using something like:
		   $fdd['fieldname']['validate']['js_regex']='/something/';
		   $fdd['fieldname']['validate']['php_regex']='something';
		 */
		$page_name = htmlspecialchars($this->page_name);

		if ($this->add_operation() || $this->change_operation()) {
			$required_ar = array();
			for ($k = 0; $k < $this->num_fds; $k++) {
				if ($this->displayed[$k]
						&& $this->fdd[$k]['required']
						&& ! $this->col_has_values($k)) {
					$required_ar[] = $k;
					if (isset($this->fdd[$k]['regex']['js'])) {
						/* TODO: Use a javascript regex to validate it */
					}
				}
			}

			if (count($required_ar) > 0) {
				echo '<script type="text/javascript"><!--'."\n";
				echo '
function form_control(theForm)
{'."\n";
				foreach ($required_ar as $field_num) {
					echo '
	if (theForm.'.$this->fds[$field_num].'.value.length == 0) {
		alert("'.$this->labels['Please enter'].' '.$this->fdd[$field_num]['name'].'.");
		theForm.'.$this->fds[$field_num].'.focus();
		return false;
	}'."\n";
				}
				echo ' return true; }'."\n\n";
				// echo ' theForm.submit(); return true; }'."\n\n";
				echo '// --></script>' . "\n";
				echo '<form action="'.$page_name. $this->qs() .'" method="POST"  enctype="multipart/form-data" onSubmit="return form_control(this);">'."\n";
				return true;
			}
		}

		echo '<form action="'.$page_name. $this->qs() .'" enctype="multipart/form-data" method="POST">'."\n";
		return true;
	} /* }}} */

	/*
	 * Display functions
	 */

	function display_add_record() /* {{{ */
	{
		@$this->connect();

		if (0) { // XXX: WTF?
			echo '  <tr>'."\n";
			echo '    <th>Field</th>'."\n";
			echo '    <th>Value</th>'."\n";
			if ($this->guidance)
				echo '    <th>Guidance</th>'."\n";
			echo '  </tr>'."\n";
		}
		for ($k = 0; $k < $this->num_fds; $k++) {
			if (! $this->displayed[$k]) {
				continue;
			}
			echo '<tr>'."\n";
			echo '<td>'.$this->fdd[$k]['name'].'</td>'."\n";

			if ($this->fdd[$k]['select'] != 'M') { $a=''; }
			else { $a = ' multiple size="'.$this->multiple.'"'; } 
			if ($this->col_has_values($k)) {
				echo '    <td>' ."\n";
				$vals = isset($this->fdd[$k]['values']['table'])
					? $this->set_values_from_table($k)
					: (array) $this->fdd[$k]['values2'] + (array) $this->fdd[$k]['values'];
				echo $this->htmlSelect($this->fds[$k], $vals, '', $this->col_is_set($k));
				echo '</td>'."\n";
			} elseif (isset ($this->fdd[$k]['textarea'])) {
				echo '<td><textarea ';
				if (isset ($this->fdd[$k]['textarea']['rows'])) {
					echo 'rows="'.$this->fdd[$k]['textarea']['rows'].'" ';
				}
				if (isset ($this->fdd[$k]['textarea']['cols'])) {
					echo 'cols="'.$this->fdd[$k]['textarea']['cols'].'" ';
				}
				echo 'name="'.$this->fds[$k].'" wrap="virtual">';
				echo $this->htmlDisplay(
						$this->fdd[$k], $this->fdd[$k]['default'],
						false, false, false);
				echo '</textarea></td>'."\n";
			} else {
				// Simple edit box required
				$type = $this->fdd[$k]['type'];
				echo '<td>';
				if ($this->readonly($k)) {
					echo $this->htmlDisplay($this->fdd[$k], '', false, false, false)
						.'<input type="hidden" name="'
						.$this->fds[$k]
						.'" value="'
						.$this->htmlDisplay($this->fdd[$k], $this->fdd[$k]['default'],
								false, false, false)
						.'" />&nbsp;';
				} else {
					$size_ml_props = '';
					if ($this->fdd[$k]['type'] != 'blob') {
						$maxlen = intval($this->fdd[$k]['maxlen']);
						$maxlen > 0 || $maxlen = 300;
						$size   = min($maxlen, 60);
						$size   && $size_ml_props .= ' size="'.$size.'"';
						$maxlen && $size_ml_props .= ' maxlength="'.$maxlen.'"';
					}
					if ($this->col_is_string($k) || $this->col_is_number($k)) {
						// string type
						echo '<input type="text" name="'.$this->fds[$k].'"'
							.$size_ml_props.' value="'
							.$this->htmlDisplay($this->fdd[$k],$this->fdd[$k]['default'],
									false, false, false)
							.'">';
					} elseif ($this->col_is_date($k)) {
						// date type, get date components
						//if ($this->fdd[$k]['default'])
						//	$value = $this->mdate_set($this->fdd[$k]['default'],$this->fdd[$k]['type']);
						//$value = time();
						//echo $this->mdate_disperse($k,$value,true);
						// string type
						echo '<input type="text" name="'.$this->fds[$k].'"'
							.$size_ml_props.' value="'
							.$this->htmlDisplay($this->fdd[$k],$this->fdd[$k]['default'],
									false, false, false)
							.'">';
					} else {
						// unknown type
						echo '<input type="text" name="'.$this->fds[$k].'" value="'
							.$this->htmlDisplay($this->fdd[$k],$this->fdd[$k]['default'],
									false, false, false)
							.'" />';
					}
				}
				echo '</td>';
			} // if elseif else
			if ($this->guidance) {
				if ($this->fdd[$k]['help'])
					echo '<td>'.$this->fdd[$k]['help'].'</td>'."\n";
			}
			echo '</tr>'."\n";
		} // for()
	} // display_add_record  /* }}} */

	function display_copy_change_delete_record() /* {{{ */
	{
		/*
		 * for delete or change: SQL SELECT to retrieve the selected record
		 */

		$qparts['type']   = 'select';
		$qparts['select'] = $this->create_column_list();
		$qparts['from']   = $this->create_join_clause();
		$qparts['where']  = '('.$this->fqn($this->key).'='
			.$this->key_delim.$this->rec.$this->key_delim.')';

		$res = $this->myquery($this->query_make($qparts),__LINE__);
		if ($row = @mysql_fetch_array($res, MYSQL_ASSOC)) {
			for ($k = 0; $k < $this->num_fds; $k++) {
				if ($this->copy_operation()) {
					if ($this->displayed[$k]) {
						echo '<tr>';
						echo '<td>'.$this->fdd[$k]['name'].'</td>'."\n";
						if ($this->readonly($k)) {
							echo $this->display_delete_field($row, $k);
						} elseif ($this->password($k)) {
							echo '<td><input type="password" name="'.$this->fds[$k]
								.'" value="'.$this->htmlDisplay($this->fdd[$k], $row["qf$k"],
										false, true, false)
								.'" /></td>';
						} else {
							echo $this->display_change_field($row, $k);
						}
						if ($this->guidance) {
							if ($this->fdd[$k]['help'])
								echo '<td>'.$this->fdd[$k]['help'].'</td>'."\n";
							else
								echo '<td>&nbsp;</td>'."\n";
						}
						echo '</tr>'."\n";
					} // if field displayed
					elseif ($this->hidden($k)) {
						if ($k != $this->key_num) {
							echo '<input type="hidden" name="'.$this->fds[$k]
								.'" value="'.$this->htmlDisplay($this->fdd[$k], $row["qf$k"],
										false, true, false)
								.'" />'."\n";
						}
					}
				} elseif ($this->change_operation()) {
					if ($this->hidden($k) && isset($row["qf$k"])) {
						echo '<input type="hidden" name="'.$this->fds[$k]
							.'" value="'.$this->htmlDisplay($this->fdd[$k], $row["qf$k"],
									false, true, false)
							.'" />'."\n";
					} elseif ($this->displayed[$k]) {
						echo '<tr>'."\n";
						echo '<td>'.$this->fdd[$k]['name'].'</td>'."\n";
						$this->display_change_field($row, $k);
						if ($this->guidance) {
							if ($this->fdd[$k]['help'])
								echo '<td>'.$this->fdd[$k]['help'].'</td>'."\n";
							else
								echo '<td>&nbsp;</td>'."\n";
						}
						echo '</tr>'."\n";
					}
				} elseif ($this->delete_operation() || $this->view_operation()) {
					if ($this->displayed[$k])  {
						echo '<tr>'."\n";
						echo '<td>'.$this->fdd[$k]['name'].'</td>'."\n";
						$this->display_delete_field($row, $k);
						if ($this->guidance)
							if ($this->fdd[$k]['help'])
								echo '<td>'.$this->fdd[$k]['help'].'</td>'."\n";
							else
								echo '<td>&nbsp;</td>'."\n";
						echo '</tr>'."\n";
					}
				}
			} // for
		} // if row
	} // display_copy_change_delete_record  /* }}} */

	function display_change_field($row, $k) /* {{{ */
	{
		echo '<td>'."\n";

		if ($this->col_has_values($k)) {
			$vals = isset($this->fdd[$k]['values']['table'])
				? $this->set_values_from_table($k)
				: (array) $this->fdd[$k]['values2'] + (array) $this->fdd[$k]['values'];
			echo $this->htmlSelect($this->fds[$k], $vals, $row["qf$k"], $this->col_is_set($k));
		} elseif (isset($this->fdd[$k]['textarea'])) {
			echo '<textarea name="'.$this->fds[$k].'"';
			// rows attr
			if (isset($this->fdd[$k]['textarea']['rows'])) {
				echo ' rows="'.$this->fdd[$k]['textarea']['rows'].'"';
			}
			// cols attr
			if (isset($this->fdd[$k]['textarea']['cols'])) {
				echo ' cols="'.$this->fdd[$k]['textarea']['cols'].'"';
			}
			// wrap attr
			if (isset($this->fdd[$k]['textarea']['wrap'])) {
				echo ' wrap="'.$this->fdd[$k]['textarea']['wrap'].'"';
			} else {
				echo ' wrap="virtual"';
			}
			echo '>';
			echo $this->htmlDisplay($this->fdd[$k], $row["qf$k"], false, true, false);
			echo ( isset($row[$this->fds[$k]]) )
				? $row[$this->fds[$k]]
				: '';
			echo '</textarea>'."\n";
		} else {
			$size_ml_props = '';
			if ($this->fdd[$k]['type'] != 'blob') {
				$maxlen = intval($this->fdd[$k]['maxlen']);
				$maxlen > 0 || $maxlen = 300;
				$size   = min($maxlen, 60);
				$size   && $size_ml_props .= ' size="'.$size.'"';
				$maxlen && $size_ml_props .= ' maxlength="'.$maxlen.'"';
			}
			if ($this->col_is_string($k) || $this->col_is_number($k)) {
				// string type
				echo '<input type="text" '.($this->readonly($k)?'disabled ':'')
					.'name="'.$this->fds[$k].'" value="'
					.$this->htmlDisplay($this->fdd[$k], $row["qf$k"], false, true, false)
					.'" '.$size_ml_props.'>';
			} elseif ($this->col_is_date($k)) {
				# date type, get date components
				#$value = $this->mdate_from_mysql($row[$k]);
				#if ($this->readonly($k)) {
				#	$mask = $this->fdd[$k]['datemask'];
				#	if (! $mask)
				#		$mask = $this->mdate_masks[$this->fdd[$k]['type']];
				#	echo $this->mdate_format($value,$mask);
				#} else {
				#	echo $this->mdate_disperse($k,$value,true);
				#}
				// string type
				echo '<input type="text" '.($this->readonly($k)?'disabled ':'')
					.'name="'.$this->fds[$k].'" value="'
					.$this->htmlDisplay($this->fdd[$k], $row["qf$k"], false, true, false)
					.'" '.$size_ml_props.'>';
			} else {
				// unknown type
				echo '<input type="text" '.($this->readonly($k)?'disabled ':'')
					.'name="'.$this->fds[$k].'" value="'
					.$this->htmlDisplay($this->fdd[$k],$row["qf$k"], false, true, false).'">';
			}
			echo "\n";
		} // if elseif else
		echo '</td>'."\n";
	} // display_change_field($row, $k)  /* }}} */
	
	function display_delete_field($row, $k) /* {{{ */
	{
		echo '<td>';
		if ($this->is_values2($k, $row["qf$k"])) {
			echo nl2br($this->htmlDisplay($k, $this->fdd[$k]['values2'][$row['qf'.$k.'_idx']],
						true, true, true, false));
		} else {
			echo nl2br($this->htmlDisplay($this->fdd[$k], $row["qf$k"]));
		}
		echo '</td>'."\n";
	} /* }}} */

	function htmlHidden($name,$value) /* {{{ */
	{
		return '<input type=hidden name="'.htmlspecialchars($name)
			.'" value="'.htmlspecialchars($value).'" />'."\n";
	} /* }}} */

	/**
	 * Creates HTML select element (tag)
	 *
	 * @param	name		element name
	 * @param	kv_array	key => value array
	 * @param	selected	selected key (it can be single string, array of
	 *						keys or multiple values separated by comma
	 * @param	multiple	bool for mulptiple selection
	 * @param	nat_sort	bool for natural sorting
	 */
	function htmlSelect($name, $kv_array, $selected = null, $multiple = false, $nat_sort = false) /* {{{ */
	{
		$ret = '<select name="'.htmlspecialchars($name);
		if ($multiple) {
			$ret  .= '[]" multiple size="'.$this->multiple;
			!is_array($selected) && $selected = explode(',', $selected);
		}
		$ret .= '">'."\n";

		if ($nat_sort) {
			uasort($kv_array,'strnatcasecmp');
		}
		if (! is_array($selected)) {
			$selected = $selected === null ? array() : array($selected);
		}

		//$keys = array_keys($kv_array);
		//debug_var('selected',$selected);

		$found = false;
		foreach ($kv_array as $key => $value) {
			$ret .= '<option value="'.htmlspecialchars($key).'"';
			if ((! $found || $multiple) && is_numeric(array_search($key, $selected))
					|| (count($selected) == 0 && ! $found)) {
				$ret  .= ' selected';
				$found = true;
			}
			$ret .= '>'.htmlspecialchars(urldecode(strip_tags($value))).'</option>'."\n";
			//debug_var("array search $key",is_numeric(array_search($key,$selected)));
		}
		$ret .= '</select>';
		return $ret;
	} /* }}} */

	/**
	 * Returns HTML text
	 *
	 * @param	field			field name/number
	 * @param	str				str to print
	 * @param	usemask			flag if field mask should be used
	 * @param	usecodec		flag if field codec should be used
	 * @param	disallow_empty	flag if empty string is forbidden on output
	 * @param	escape			flag if output should be HTML escaped
	 */
	function htmlDisplay($field, $str,  /* ...) {{{ */
			$usemask        = true,
			$usecodec       = true,
			$disallow_empty = true,
			$escape         = true)
	{
		// undo the add slashes
		$str = stripslashes($str);

		// if there's a field mask, use it as first arg to sprintf
		if (isset($field['mask']) && $usemask) {
			$str = sprintf($field['mask'], $str);
		}
		// if db codec is in effect, use it
		if ($usecodec && isset($field['dbdecode'])) {
			$str = eval('return '.$field['dbdecode'].'(\''.$str.'\');');
		}
		if ($escape) {
			$str = htmlspecialchars($str);
		}
		if ($disallow_empty) {
			strlen($str) <= 0 && $str = '&nbsp;';
		}
		return $str;
	} /* }}} */

	/* Function extracted from phpPlatonLib
	http://www.platon.sk/projects/phpPlatonLib/ */
	function write_origvars_html($origvars, $default_value = null) /* {{{ */
	{
		foreach (explode('&', $origvars) as $param) {

			$parts = explode('=', $param, 2);
			if (! isset($parts[1]) && isset($default_value))
				$parts[1] = $default_value;

			if (strlen($parts[0]) <= 0)
				continue;

			echo '<input type="hidden" name="' . $parts[0] . '"';	
			if (isset($parts[1]))
				echo ' value="' . $parts[1] . '"';
			echo " />\n";
		}

		return true;
	} /* }}} */

	function get_sfn_cgi_vars($alternative_sfn = null) /* {{{ */
	{
		if ($alternative_sfn == null) { // FAST! (cached return value)
			static $ret = null;
			$ret == null && $ret = $this->get_sfn_cgi_vars($this->sfn);
			return $ret;
		}
		$ret = '';
		$i   = 0;
		foreach ($alternative_sfn as $val) {
			$ret != '' && $ret .= '&';
			$ret .= "sfn[$i]=$val";
			$i++;
		}
		return $ret;
	} /* }}} */

	function get_qf_hidden_fields() /* {{{ */
	{
		/* If the filter input boxes are not displayed, we need to preserve
		   the filter by its emulaion. */
		$this->qfn  = '';
		$hidden_qfs = '';
		for ($k = 0; $k < $this->num_fds; $k++) {
			$l   = 'qf'.$k;
			$lc  = 'qf'.$k.'_comp';
			$$l  = $this->get_cgi_var($l);
			$$lc = $this->get_cgi_var($lc);
			$m   = $this->web2plain($$l);  // get the field name and value
			$mc  = $this->web2plain($$lc); // get the comparison operator for numeric/date types

			if (!isset($m)) {
				continue;
			}
			if (is_array($m)) { // multiple selection has been used
				if (!in_array('*',$m)) {// one '*' in a multiple selection is all you need
					for ($n=0; $n<count($m); $n++) {
						if ($this->plain2web($m[$n]) != '') {
							$this->qfn = $this->qfn.'&qf'.$k.'['.$n.']='
								.$this->plain2web($m[$n]); }
						$hidden_qfs .= '<input type="hidden" name="qf'.$k.'['.$n
							.']" value="'.$this->plain2web($m[$n]).'" />'."\n";
					}
				}
			} else {
				// query field comparison operator (if any)
				if ($this->plain2web($mc) != '') {
					$this->qfn   = $this->qfn.'&qf'.$k.'_comp='.$this->plain2web($mc);
					$hidden_qfs .= '<input type="hidden" name="'.$lc.'"
					value="'.$mc.'" />'."\n";
				}
				// preserve query field & value
				if ($this->plain2web($m) != '') {
					$this->qfn   = $this->qfn.'&qf'.$k.'='.$this->plain2web($m);
					$hidden_qfs .= '<input type="hidden" name="'.$l.'"
					value="'.$m.'" />'."\n";
				}
			}
		}
		return $hidden_qfs;
	} /* }}} */

	function web2plain($x) /* {{{ */
	{
		if (isset($x)) {
			if (is_array($x)) {
				foreach (array_keys($x) as $key) {
					$x[$key] = rawurldecode($x[$key]);
				}
			} else {
				$x = rawurldecode($x);
			}
		}
		return $x;
	} /* }}} */
	
	function plain2web($x) /* {{{ */
	{
		if (isset($x)) {
			if (is_array($x)) {
				for ($n=0; $n<count($x); $n++) {
					$x[$n] = $this->plain2web($x[$n]);
				}
			} else {
				$x = rawurlencode($x);
			}
		}
		return $x;
	} /* }}} */

	function get_cgi_var($name, $default_value = null) /* {{{ */
	{
		//echo 'get_cgi_var(): requested: '.htmlspecialchars($name).'<br>';
		if (isset($this) && isset($this->cgi['overwrite'][$name])) {
			return $this->cgi['overwrite'][$name];
		}

		if ( isset($_GET[$name]) )
		{
			$var = $_GET[$name];
		}

		elseif ( isset($_POST[$name]) )
	 	{
			$var = $_POST[$name];
		}
		
		else 
		{
			$var = $default_value;
		}

		if ( null !== $var )
		{
			if (is_array($var)) 
			{
			   foreach (array_keys($var) as $key) 
			   {
				   $var[$key] = stripslashes($var[$key]);
			   }
			} 
			else 
			{
			   $var = stripslashes($var);
			}
		}

		if (isset($this) && $var === null && isset($this->cgi['append'][$name]))
		{
			return $this->cgi['append'][$name];
		}

		return $var;
	} /* }}} */

	/*
	 * Debug functions
	 */

	function print_get_vars ($miss = 'No GET variables found') // debug only /* {{{ */
	{
		// we parse form GET variables
		if (is_array($_GET)) {
			echo "<p> Variables per GET ";
			foreach ($_GET as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $akey => $aval) {
						// $_GET[$k][$akey] = strip_tags($aval);
						// $$k[$akey] = strip_tags($aval);
						echo "$k\[$akey\]=$aval   ";
					}
				} else {
					// $_GET[$k] = strip_tags($val);
					// $$k = strip_tags($val);
					echo "$k=$v   ";
				}
			}
			echo '</p>';
		} else {
			echo '<p>';
			echo $miss;
			echo '</p>';
		}
	} /* }}} */

	function print_post_vars($miss = 'No POST variables found')  // debug only /* {{{ */
	{
		// we parse form POST variables
		if (is_array($_POST)) {
			echo "<p>Variables per POST ";
			foreach ($_POST as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $akey => $aval) {
						// $_POST[$k][$akey] = strip_tags($aval);
						// $$k[$akey] = strip_tags($aval);
						echo "$k\[$akey\]=$aval   ";
					}
				} else {
					// $_POST[$k] = strip_tags($val);
					// $$k = strip_tags($val);
					echo "$k=$v   ";
				}
			}
			echo '</p>';
		} else {
			echo '<p>';
			echo $miss;
			echo '</p>';
		}
	} /* }}} */

	function print_vars ($miss = 'Current instance variables')  // debug only /* {{{ */
	{
		echo "$miss   ";
		echo 'page_name='.$this->page_name.'   ';
		echo 'hn='.$this->hn.'   ';
		echo 'un='.$this->un.'   ';
		echo 'pw='.$this->pw.'   ';
		echo 'db='.$this->db.'   ';
		echo 'tb='.$this->tb.'   ';
		echo 'key='.$this->key.'   ';
		echo 'key_type='.$this->key_type.'   ';
		echo 'inc='.$this->inc.'   ';
		echo 'options='.$this->options.'   ';
		echo 'fdd='.$this->fdd.'   ';
		echo 'fl='.$this->fl.'   ';
		echo 'fm='.$this->fm.'   ';
		echo 'sfn='.htmlspecialchars($this->get_sfn_cgi_vars()).'   ';
		echo 'qfn='.$this->qfn.'   ';
		echo 'sw='.$this->sw.'   ';
		echo 'rec='.$this->rec.'   ';
		echo 'prev='.$this->prev.'   ';
		echo 'next='.$this->next.'   ';
		echo 'saveadd='.$this->saveadd.'   ';
		echo 'moreadd='.$this->moreadd.'   ';
		echo 'savechange='.$this->savechange.'   ';
		echo 'morechange='.$this->morechange.'   ';
		echo 'savedelete='.$this->savedelete.'   ';
		echo 'operation='.$this->operation.'   ';
		echo "\n";
	} /* }}} */

	/*
	 * Display buttons at top and bottom of page - sparky
	 */
	function display_list_table_buttons($total_recs) /* {{{ */
	{
		// Are we doing a listall?
		$listall = $this->inc <= 0;

		// note that <input disabled isn\'t valid HTML but most browsers support it
		// TODO: classify this table and cells
		echo '<table style="border:0;padding:0;">';
		echo '<tr><td style="text-align:left;border:0;">' . "\n";
		$disabled = ($this->fm > 0 && ! $listall) ? '' : ' disabled';
		echo '<input'.$disabled.' type="submit" name="'.ltrim($disabled).'prev" value="'
			.$this->labels['Prev'].'">&nbsp;';
		if ($this->add_enabled ()) {
			echo '<input type="submit" name="operation" value="'.$this->labels['Add'].'">&nbsp;';
		}

		if ($this->nav_buttons()) {
			if ($this->view_enabled()) {
				echo '<input';
				if (! $total_recs) { echo ' disabled'; }
				echo ' type="submit" name="operation" value="'.$this->labels['View'].'">&nbsp;';
			}
			if ($this->change_enabled()) {
				echo '<input';
				if (! $total_recs) { echo ' disabled'; }
				echo ' type="submit" name="operation" value="'.$this->labels['Change'].'">&nbsp;';
			}
			if ($this->copy_enabled()) {
				echo '<input';
				if (! $total_recs) { echo ' disabled'; }
				echo ' type="submit" name="operation" value="'.$this->labels['Copy'].'">&nbsp;';
			}
			if ($this->delete_enabled()) {
				echo '<input';
				if (! $total_recs) { echo ' disabled'; }
				echo ' type="submit" name="operation" value="'.$this->labels['Delete'].'">&nbsp;';
			} // if else
		}

		$disabled = ($this->fm + $this->inc < $total_recs && ! $listall) ? '' : ' disabled';
		echo '<input'.$disabled.' type="submit" name="'.ltrim($disabled).'next" value="'
			.$this->labels['Next'].'">';

		// Message is now written here
		echo '</td><td style="text-align:center;border:0;" ><b>'.$this->message.'</b></td>';

		// display page and records statistics
		echo '<td style="text-align:right;border:0;" >' . "\n";
		if ($listall) {
			echo $this->labels['Page'].': 1 '.$this->labels['of'].' 1';
		} else {
			echo $this->labels['Page'].': ';
			echo (($this->fm / $this->inc)+1);
			echo ' '.$this->labels['of'].' ';
			echo max(1, ceil($total_recs / abs($this->inc)));
		}
		echo '&nbsp;&nbsp;'.$this->labels['Records'].': '.$total_recs;
		echo '</td></tr></table>'."\n";
	} /* }}} */

	/*
	 * Display buttons at top and bottom of page - sparky
	 */
	function display_record_buttons() /* {{{ */
	{
		// TODO: classify this table and cells
		echo '<table border="0" cellpadding="0" cellspacing="0" style="border:0;">';
		echo '<tr><td align="left" style="text-align:left;border:0;">' . "\n";
		if ($this->change_operation()) {
			echo '<input type="submit" name="savechange" value="'.$this->labels['Save'].'" />'."\n";
			echo '<input type="submit" name="morechange" value="'.$this->labels['Apply'].'" />'."\n";
			echo '<input type="submit" name="cancel" value="'.$this->labels['Cancel'].'" />'."\n";
			// echo '<input type="button" name="cancel" value="'.$this->labels['Cancel'].'" onClick="form.submit();" />'."\n";
		} elseif ($this->add_operation()) {
			echo '<input type="submit" name="saveadd" value="'.$this->labels['Save'].'" />'."\n";
			echo '<input type="submit" name="moreadd" value="'.$this->labels['More'].'" />'."\n";
			echo '<input type="submit" name="cancel" value="'.$this->labels['Cancel'].'" />'."\n";
			// echo '<input type="button" name="cancel" value="'.$this->labels['Cancel'].'" onClick="form.submit();" />'."\n";
		} elseif ($this->copy_operation()) {
			echo '<input type="submit" name="saveadd" value="'.$this->labels['Save'].'" />'."\n";
			echo '<input type="submit" name="cancel" value="'.$this->labels['Cancel'].'" />'."\n";
			// echo '<input type="button" name="cancel" value="'.$this->labels['Cancel'].'" onClick="form.submit();" />'."\n";
		} elseif ($this->delete_operation()) {
			echo '<input type="submit" name="savedelete" value="'.$this->labels['Delete'].'" />'."\n";
			echo '<input type="submit" name="cancel" value="'.$this->labels['Cancel'].'" />'."\n";
		} elseif ($this->view_operation()) {
			if ($this->change_enabled()) {
				echo '<input type="submit" name="operation" value="'.$this->labels['Change'].'" />'."\n";
			}
			echo '<input type="submit" name="cancel" value="'.$this->labels['Cancel'].'" />'."\n";
		}
		// Message is now written here
		echo '</td><td align="center" style="text-align:center;border:0;" ><b>'.$this->message.'</b></td>';
		echo '</td></tr></table>'."\n";
	} /* }}} */


	/*
	 * Table Page Listing
	 */
	function list_table() /* {{{ */
	{
		$PHP_SELF = $_SERVER['PHP_SELF'];
		// Process any navigation buttons

		//if (!isset ($this->fm))
		if ($this->fm == '') {
			$this->fm = 0;
		}
		if ($this->prev_operation()) {
			$this->fm = $this->fm - $this->inc;
			if ($this->fm < 0) {
				$this->fm = 0;
			}
		}
		if ($this->next_operation()) {
			$this->fm += $this->inc;
		}

		/*
		 * If user is allowed to Change/Delete records, we need an extra column
		 * to allow users to select a record
		 */

		$select_recs = $this->key != '' &&
			($this->change_enabled() || $this->delete_enabled() || $this->view_enabled());

		// Are we doing a listall?
		$listall = $this->inc <= 0;

		/*
		 * Display the MySQL table in an HTML table
		 */

		$comp_ops = array(
				''=>'','%3C'=>'%3C','%3C%3D'=>'%3C%3D',
				'%3D'=>'%3D','%3E%3D'=>'%3E%3D','%3E'=>'%3E');
		echo '<form action="'.$this->page_name. $this->qs() .'" enctype="multipart/form-data" method="POST">'."\n";
		$this->write_origvars_html($this->get_sfn_cgi_vars());
		echo '<input type="hidden" name="fl" value="'.$this->fl.'" />'."\n";

		$prev_qfn   = $this->qfn;
		$hidden_qfs = $this->get_qf_hidden_fields();
		// if sort sequence has changed, restart listing
		$this->qfn != $prev_qfn && $this->fm = 0;

		if (0) { // TODO: delete me!
			echo '$this->qfn vs. $prev_qfn comparsion:::::';
			echo '<b>'.htmlspecialchars($this->qfn).'</b>:::::';
			echo '<b>'.htmlspecialchars($prev_qfn).'</b>:::::<br>';
			echo 'comparsion <u>'.($this->qfn == $prev_qfn ? 'proved' : 'failed').'</u>';
			echo '<hr>';
		}

		// Display buttons at top and/or bottom of page.
		// Setup query to get num_rows. (sparky)
		$total_recs  = 0;
		$count_parts = array(
				'type'   => 'select',
				'select' => 'count(*) as num_rows',
				'from'   => $this->create_join_clause(),
				'where'  => $this->make_where_from_query_opts());
		$res = $this->myquery($this->query_make($count_parts), __LINE__);
		$row = @mysql_fetch_array($res, MYSQL_ASSOC);
		$total_recs = $row['num_rows'];

		if ($this->nav_up()) {
			$this->display_list_table_buttons($total_recs);
			echo '<hr>'."\n";
		}

		if (!$this->fl) {
			echo($hidden_qfs);
		}
		echo '<input type=hidden name=qfn
		value="'.htmlspecialchars($this->qfn).'" />'."\n";
		echo '<input type=hidden name=fm value="'.htmlspecialchars($this->fm)
			.'" />'."\n";
		echo '<table border="1" cellpadding="1" cellspacing="0"';
		echo ' summary="'.$this->tb.'">'."\n";
		echo '<tr>'."\n";

		/*
		 * System (navigation, selection) columns counting
		 */
		$sys_cols  = 0;
		$sys_cols += intval($this->filter_enabled() || $select_recs);
		if ($sys_cols > 0) {
			$sys_cols += intval($this->nav_buttons()
					&& ($this->nav_text_links() || $this->nav_graphic_links()));
		}
		
		/*
		 * We need an initial column(s) (sys columns)
		 * if we have filters, Changes or Deletes enabled
		 */
		if ($sys_cols) {
			echo '<th colspan="'.$sys_cols.'" align="center"  width="1%">';
			if ($this->filter_enabled()) {
				if ($this->fl) {
					echo '<input type=submit name=sw value="'.$this->labels['Hide'].'">';
					echo '<input type=submit name=sw value="'.$this->labels['Clear'].'"><br>';
					//echo '<input type=submit name=filter value="'.$this->labels['Query'].'">';
				} else {
					echo '<input type=submit name=sw value="'.$this->labels['Search'].'">';
				}
			} else {
				echo '&nbsp;';
			}
			echo '</th>'."\n";
		}

		for ($k = 0; $k < $this->num_fds; $k++) {
			$fd = $this->fds[$k];

			/*
			if ((stristr($this->fdd[$fd]['options'],'L') || ! isset ($this->fdd[$fd]['options'])) &&
				! $this->hidden($k)
			)
			*/
			if ($this->displayed[$k]) {
				$fdn = $this->fdd[$fd]['name'];
				if (isset ($this->fdd[$fd]['width'])) {
					$w = ' width="'.$this->fdd[$fd]['width'].'"';
				} else {
					$w = '';
				}
				if ( isset($this->fdd[$fd]['sort']) )
				{
					// Clicking on the current sort field reverses the sort order
					$new_sfn = $this->sfn;
					array_unshift($new_sfn, in_array("$k", $new_sfn, 1) ? "-$k" : $k);
					echo '<th'.$w.'><a href="'.$this->page_name.'?fm=0&fl='.$this->fl;
					echo '&'.$this->get_sfn_cgi_vars($new_sfn);
					echo $this->qfn.$this->qs(false).'">'.$fdn.'</a></th>'."\n";
				} else {
					echo '<th'.$w.'>'.$fdn.'</th>'."\n";
				}
			}

			// if we have any aggregates going on, then we have to list all results
			$var_to_total  = 'qf'.$k.'_aggr';
			$$var_to_total = $this->get_cgi_var($var_to_total);
			if ($$var_to_total != '') {
				$listall = true;
			}
		} // for

		echo '</tr>'."\n";


		/*
		 * Prepare the SQL Query from the data definition file
		 */
		$qparts['type']   = 'select';
		$qparts['select'] = $this->create_column_list();
		// Even if the key field isn't displayed, we still need its value
		if ($select_recs) {
			if (!in_array ($this->key, $this->fds)) {
				$qparts['select'] .= ','.$this->fqn($this->key);
			}
		}
		$qparts['from']  = $this->create_join_clause();
		$qparts['where'] = $this->make_where_from_query_opts();
		// build up the ORDER BY clause
		if (isset($this->sfn)) {
			// WTF $raw_sort_fields?
			//$raw_sort_fields = array();
			$sort_fields     = array();
			$sort_fields_w   = array();

			foreach ($this->sfn as $field) {
				if ($field[0] == '-') {
					$field = substr($field, 1);
					$desc  = true;
				} else {
					$field = $field;
					$desc  = false;
				}
				//$raw_sort_field = 'qf'.$field;
				$sort_field     = 'qf'.$field;
				$sort_field_w   = $this->fdd[$field]['name'];
				if ( isset($this->fdd[$field]['expression']) )
				{
				   $sort_field_w .= ' (expression)';
				}
				if ($desc) {
					$sort_field   .= ' DESC';
					$sort_field_w .= ' descending';
				}
				//$raw_sort_fields[] = $raw_sort_field;
				$sort_fields[]     = $sort_field;
				$sort_fields_w[]   = $sort_field_w;
			}
			if (count($sort_fields) > 0) {
				$qparts['orderby'] = join(',', $sort_fields);
			}
		}
		$to = $this->fm + $this->inc;
		if ($listall) {
			$qparts['limit'] = $this->fm.',-1';
		} else {
			$qparts['limit'] = $this->fm.','.$this->inc;
		}

		if ($qparts['orderby'] && $this->display['sort']) {
			// XXX this doesn't preserve filters
			echo '<tr>';
			if (isset($this->sfn)) {
				echo '<td align="center">'.
					'<a class="pme_a_t" href="'.$PHP_SELF.$this->qs().'">'.$this->labels['Clear'].'</a>'.
					'</td>';
				echo '<td colspan="'.($this->num_fields_displayed + $sys_cols - 1).'">Sorted By: ';
			} else {
				echo '<td colspan="'.($this->num_fields_displayed + $sys_cols).'">Default Sort Order: ';
			}
			echo join(', ',$sort_fields_w);
			echo '</td></tr>'."\n";
		}

		/*
		 * FILTER
		 *
		 * Draw the filter and fill it with any data typed in last pass and stored
		 * in the array parameter keyword 'filter'. Prepare the SQL WHERE clause.
		 */

		if ($this->fl) {
			echo '<tr>';
			echo '<td colspan='.$sys_cols.' align="center">';
			echo '<input type="submit" name="filter" value="'
				.$this->labels['Query'].'" /></td>'."\n";
			for ($kk = $k = 0; $k < $this->num_fds; $k++) {
				$this->field_name = $this->fds[$k];
				$fd               = $this->field_name;
				$this->field      = $this->fdd[$fd];
				$l   = 'qf'.$k;
				$lc  = 'qf'.$k.'_comp';
				$$l  = $this->get_cgi_var($l);
				$$lc = $this->get_cgi_var($lc);
				$m   = $this->web2plain($$l);  // get the field name and value
				$mc  = $this->web2plain($$lc); // get the comparison operator for numeric/date types

				$widthStyle = '';
				if (isset($this->fdd[$fd]['width'])) {
					$widthStyle = ' STYLE=\'width: "'.(6*$this->fdd[$fd]['width']).'px"\'';
				}

				$opened = false;
				if ($this->displayed[$k]) {
					echo '<td '.$widthStyle.'>';
					$opened = true;
				}


				$type = $this->fdd[$fd]['type'];
				if ($this->col_has_values($k)) {
					$type = 'string';
				}

				/*
				if (
					stristr($this->fdd[$fd]['options'],'L') or
					!isset ($this->fdd[$fd]['options'])
				)
				*/
				if (! $this->displayed[$k]) {
					continue;
				}
				$kk++; // $kk counts only displayed fields

				if ($this->fdd[$fd]['select'] == 'D' or $this->fdd[$fd]['select'] == 'M') {
					/*       
					 * Multiple fields processing - default size is 2 and array required for values
					 */
					$multiple = $this->fdd[$fd]['select'] == 'M';
					$selected = $m;
					$x = isset($this->fdd[$k]['values']['table']) || !$this->col_has_values($k)
						? $this->set_values_from_table($k, array('*' => '*'))
						: array('*' => '*') + (array) $this->fdd[$k]['values2'] + (array) $this->fdd[$k]['values'];
					echo $this->htmlSelect($l, $x, $selected, $multiple);
				} elseif ($this->fdd[$fd]['select'] == 'T') {
					// this is where we put the comparison selects
					if (! $this->password($k) && ! $this->hidden($k)) {
						$size_ml_props = '';
						if ($type != 'blob') {
							$maxlen = intval($this->fdd[$k]['maxlen']);
							$maxlen > 0 || $maxlen = intval(@mysql_field_len($res, $kk));
							$size   = $maxlen < 30 ? min($maxlen, 8) : 12;	
							$size   && $size_ml_props .= ' size="'.$size.'"';
							$maxlen && $size_ml_props .= ' maxlength="'.$maxlen.'"';
						}
						if ($this->col_is_string($k)) {
							// it's treated as a string
							echo '<input type="text" name="qf'.$k.'"';
							echo ' value="'.stripslashes($m).'"'.$size_ml_props.'>';
						} elseif ($this->col_is_date($k)) {
							// it's a date
							//echo $this->htmlSelect($l.'_comp',$comp_ops,$$lc);
							// first get any date elements that were passed in
							//$filter_val = $this->gather_search_date_fields_into_mysql_timestamp('qf'.$k);
							// display the search formlet
							//if ($mc) {
							//	//echo $this->display_search_field_date($type,'qf'.$k,$filter_val,$this->fdd[$k]['datemask']);
							//	//echo $this->mdate_displayForm($filter_val,$type,'qf'.$k,$this->fdd[$k]['datemask'],true);
							//	echo $this->mdate_disperse($k,true,$filter_val);
							//}
							//else {
							//	//echo $this->display_search_field_date( $type,'qf'.$k,'',$this->fdd[$k]['datemask']);
							//	echo $this->mdate_displayForm('',$type,'qf'.$k,$this->fdd[$k]['datemask'],true);
							//}
							// it's treated as a string
							echo '<input type="text" name="qf'.$k.'"';
							echo ' value="'.stripslashes($m).'"'.$size_ml_props.'>';
						} elseif ($this->col_is_number($k)) {
							// it's a number
							echo $this->htmlSelect($l.'_comp',$comp_ops,$$lc);
							// it's treated as a string
							echo '<input type="text" name="qf'.$k.'"'
								.' value="'.$m.'"'.$size_ml_props.'>';
						} else {
							// type is 'unknown' or not set, it's treated as a string
							echo '<input type="text" name="qf'.$k.'"';
							echo ' value="'.stripslashes($m).'"'.$size_ml_props.'>';
						}
					} else {
						echo "&nbsp;";
					}

					// if it's int or real and if not password or hidden, display aggr options
					/* XXX Disabled until we have time to work on this

					   if ((! $this->password($k) && ! $this->hidden($k))
					   && (($this->col_is_number($k)) && (! isset($this->fdd[$k]['values'])))) {

					   $var_to_total = 'qf'.$k.'_aggr';
					   global $$var_to_total;
					   $aggr_function = $$var_to_total;
					   if (isset($$var_to_total)) {
					   $vars_to_total[] = $this->fqn($k);
					   $aggr_from_clause .=
					   ' '.$aggr_function.'('.
					   $this->fqn($k).
					   ') as '.$var_to_total;
					   }
					   echo '<br>Aggr: ';
					   echo $this->htmlSelect($var_to_total,$this->sql_aggrs,$$var_to_total);
					   if ($$var_to_total != '') {
					   $listall = true;
					   }
					   } else {
					   echo '&nbsp;';
					   }
					 */
					echo '</td>'."\n";
				} else {
					echo '<td>&nbsp;</td>'."\n";
				} // if elseif else
			} // for
			echo '</tr>'."\n";
		} // if first and fl

		/*
		 * Display the current query
		 */
		$text_query = $this->make_text_where_from_query_opts();
		if ($text_query != '' && $this->display['query']) {
			echo '<tr><td colspan='.$sys_cols.' align="center">'
				.'<a class="pme_a_t" href="'.$PHP_SELF.$this->qs();
			echo '?sfn='.$this->get_sfn_cgi_vars().'&fl='.$this->fl.'&fm='.$this->fm;
			echo '">'.$this->labels['Clear'].'</a></td>';
			echo '<td colspan="'.$this->num_fields_displayed.'">Current Query: '
				.htmlspecialchars(stripslashes(stripslashes(stripslashes($text_query))))
				.'</td></tr>'."\n";
		}

		/*
		 * Each row of the HTML table is one record from the SQL Query
		 * Main list_table() query
		 */
		$res      = $this->myquery($this->query_make($qparts), __LINE__);
		$first    = true;
		$rowCount = 0;
				
		$qpviewStr  	= '';
	   $qpcopyStr  		= '';
	   $qpchangeStr		= '';
	   $qpdeleteStr		= '';

		if ($this->nav_text_links() || $this->nav_graphic_links()) {
			// gather query & GET options to preserve for Update/Delete links
			$qstrparts = array();
			if (count($this->query_opts) > 0) {
				foreach ($this->query_opts as $key=>$val) {
					if ($key != '' && $key != 'operation' && ! is_array($val))
						$qstrparts[] = "$key=$val";
				}
			}
			if (count($this->get_opts) > 0) {
				foreach ($this->get_opts as $key=>$val) {
					if ($key != '' && $key != 'operation' && ! is_array($val))
						$qstrparts[] = "$key=$val";
				}
			}

			// preserve sort field number, filter row, and first record to display
			isset($this->sfn) && $qstrparts[] = $this->get_sfn_cgi_vars();
			isset($this->fl)  && $qstrparts[] = 'fl='.$this->fl;
			isset($this->fm)  && $qstrparts[] = 'fm='.$this->fm;

			// do we need to preserve filter (filter query) and sw (filter display/hide button)?

			$qpview      = $qstrparts;
			$qpview[]    = 'operation='.$this->labels['View'];
			$qpviewStr   = '?'.join('&',$qpview).$this->qfn;

			$qpcopy      = $qstrparts;
			$qpcopy[]    = 'operation='.$this->labels['Copy'];
			$qpcopyStr   = '?'.join('&',$qpcopy).$this->qfn;

			$qpchange    = $qstrparts;
			$qpchange[]  = 'operation='.$this->labels['Change'];
			$qpchangeStr = '?'.join('&',$qpchange).$this->qfn;

			$qpdelete    = $qstrparts;
			$qpdelete[]  = 'operation='.$this->labels['Delete'];
			$qpdeleteStr = '?'.join('&',$qpdelete).$this->qfn;
		}

		while ($row = @mysql_fetch_array($res, MYSQL_ASSOC)) {

			echo '<tr class="'.(($rowCount++%2)?'pme_tr_o':'pme_tr_e')."\">\n";
			if ($sys_cols && isset($row['qf'.$this->key_num]) )
			{
				$key_rec    = $row['qf'.$this->key_num];
				$qviewStr   = $qpviewStr  .'&rec='.$key_rec;
				$qcopyStr   = $qpcopyStr  .'&rec='.$key_rec;
				$qchangeStr = $qpchangeStr.'&rec='.$key_rec;
				$qdeleteStr = $qpdeleteStr.'&rec='.$key_rec;
				if ($select_recs) {
					if (! $this->nav_buttons() || $sys_cols > 1) {
						echo '<td style="white-space:nowrap;text-align:center;" width="1%">';
					}
					if ($this->nav_graphic_links()) {
						$printed_out = false;
						if ($this->view_enabled()) {
							$printed_out = true;
							echo '<a class="pme_a_t" href="';
							echo
							htmlspecialchars($this->page_name.$qviewStr.$this->qs(false));
							echo '"><img src="'.$this->url['images'].'pme-view.png"';
							echo ' height="15" width="16" border="0" alt="'
								.htmlspecialchars($this->labels['View']).'"></a>';
						}
						if ($this->change_enabled()) {
							$printed_out && print('&nbsp;');
							$printed_out = true;
							echo '<a class="pme_a_t" href="';
							echo htmlspecialchars($this->page_name.$qchangeStr.$this->qs(false));
							echo '"><img src="'.$this->url['images'].'pme-change.png"';
							echo ' height="15" width="16" border="0" alt="'
								.htmlspecialchars($this->labels['Change']).'"></a>';
						}
						if ($this->copy_enabled()) {
							$printed_out && print('&nbsp;');
							$printed_out = true;
							echo '<a class="pme_a_t" href="';
							echo htmlspecialchars($this->page_name.$qcopyStr.$this->qs(false));
							echo '"><img src="'.$this->url['images'].'pme-copy.png"';
							echo ' height="15" width="16" border="0" alt="'
								.htmlspecialchars($this->labels['Copy']).'"></a>';
						}
						if ($this->delete_enabled()) {
							$printed_out && print('&nbsp;');
							$printed_out = true;
							echo '<a class="pme_a_t" href="';
							echo htmlspecialchars($this->page_name.$qdeleteStr.$this->qs(false));
							echo '"><img src="'.$this->url['images'].'pme-delete.png"';
							echo ' height="15" width="16" border="0" alt="'
								.htmlspecialchars($this->labels['Delete']).'"></a>';
						}
					}
					if ($this->nav_text_links()) {
						if ($this->nav_graphic_links()) {
							echo '<br>';
						}
						$printed_out = false;
						if ($this->view_enabled()) {
							$printed_out = true;
							echo '<a class="pme_a_t" href="'
								.htmlspecialchars($this->page_name.$qviewStr.$this->qs(false)).'">V</a>&nbsp;';
						}
						if ($this->change_enabled()) {
							$printed_out && print('&nbsp;');
							$printed_out = true;
							echo '<a class="pme_a_t" href="'
								.htmlspecialchars($this->page_name.$qchangeStr.$this->qs(false)).'">C</a>&nbsp;';
						}
						if ($this->copy_enabled()) {
							$printed_out && print('&nbsp;');
							$printed_out = true;
							echo '<a class="pme_a_t" href="'
								.htmlspecialchars($this->page_name.$qcopyStr.$this->qs(false)).'">P</a>&nbsp;';
						}
						if ($this->delete_enabled()) {
							$printed_out && print('&nbsp;');
							$printed_out = true;
							echo '<a class="pme_a_t" href="'
								.htmlspecialchars($this->page_name.$qdeleteStr.$this->qs(false)).'">D</a>';
						}
					}
					if (! $this->nav_buttons() || $sys_cols > 1) {
						echo '</td>'."\n";
					}
					if ($this->nav_buttons()) {
						echo '<td align="center" width="1">';
						echo '<input type=radio name=rec value="'.htmlspecialchars($key_rec).'"';
						if ($first) {
							echo ' checked';
							$first = false;
						}
						echo '></td>'."\n";
					}
				} elseif ($this->filter_enabled()) {
					echo '<td colspan='.$sys_cols.'>&nbsp;</td>'."\n";
				}
			}

			// Calculate the url query string for optional URL support
			$urlqueryproto = 'fm='.$this->fm
				.'&sfn='.$this->get_sfn_cgi_vars()
				.'&fl='.$this->fl
				.'&qfn='.$this->qfn;
			for ($k = 0; $k < $this->num_fds; $k++) {
				$fd = $this->fds[$k];
				if ($this->hidden($k) || $this->password($k)) {
					continue;
				} elseif (! $this->displayed[$k]) {
					continue;
				}
				// XXX: echo 'displayed: '.$k.'-'.$fd;

				/* TODO: what's this?!

				   if ((trim($row[$k]) == '') or ($row[$k] == 'NULL')) {
				   echo '      <td>&nbsp;</td>'."\n";
				   } else { */

				// display the contents
				$colattrs = ( isset($this->fdd[$fd]['colattrs']) )
					? $this->fdd[$fd]['colattrs']
					: '';
				if ($colattrs != '')
					$colattrs = ' '.$colattrs;
				if ($this->fdd[$fd]['nowrap'])
					$colattrs .= ' nowrap';
				if (isset($this->fdd[$fd]['width'])) {
					$colattrs .= ' width="'.$this->fdd[$fd]['width'].'"';
				}
				echo '      <td'.$colattrs.'>';
				if (! $this->hidden($k) && ! $this->password($k)) {
					// displayable
					if (isset($this->fdd[$k]['URL'])
							|| isset($this->fdd[$k]['URLprefix'])
							|| isset($this->fdd[$k]['URLpostfix'])) {
						/* It's an URL
						   Put some conveniences in the namespace for the user
						   to be able to use in the URL string. */
						$key     = $key_rec;
						$name    = $this->fds[$k];
						$value   = $row["qf$k"];
						$page    = $this->page_name;
						$urlstr  = $urlqueryproto.'&rec='.$key;
						$urllink = isset($this->fdd[$k]['URL'])
							? eval('return "'.$this->fdd[$k]['URL'].'";')
							: $value;
						$urldisp = isset($this->fdd[$k]['URLdisp'])
							? eval('return "'.$this->fdd[$k]['URLdisp'].'";')
							: $value;
						$target = isset($this->fdd[$k]['URLtarget'])
							? 'target="'.htmlspecialchars($this->fdd[$k]['URLtarget']).'" '
							: '';
						isset($this->fdd[$k]['URLprefix'])  && $urllink  = $this->fdd[$k]['URLprefix'].$urllink;
						isset($this->fdd[$k]['URLpostfix']) && $urllink .= $this->fdd[$k]['URLpostfix'];
						if (strlen($urllink) <= 0 || strlen($urldisp) <= 0) {
							echo '&nbsp;';
						} else {
							$urllink = htmlspecialchars($urllink);
							$urldisp = htmlspecialchars($urldisp);
							echo '<a '.$target.'class="pme_a_u" href="'.$urllink.'">'.$urldisp.'</a>';
						}
					} elseif (isset($this->fdd[$k]['datemask'])) {
						// display date according to a mask if any
						//echo $this->mdate_set($row[$k],$this->fdd[$k]['type'],$this->fdd[$k]['datemask']);
						//echo 
						//	$this->mdate_displayPlain(
						//		$this->mdate_from_mysql(
						//			$row[$k]),
						//			(
						//				$this->fdd[$k]['datemask']?
						//					$this->fdd[$k]['datemask']
						//				:
						//					$this->mdate_masks[$this->fdd[$k]['type']]
						//			)
						//		);
						//echo $row[$k];
						// it's a normal field
						$shortdisp = $row["qf$k"];
						if ($this->fdd[$k]['strip_tags']) {
							$shortdisp = strip_tags($shortdisp);
						}
						if (isset($this->fdd[$k]['trimlen'])
								&& strlen($shortdisp) > $this->fdd[$k]['trimlen']) {
							$shortdisp = ereg_replace("[\r\n\t ]+", ' ', $shortdisp);
							$shortdisp = substr($shortdisp,0,$this->fdd[$k]['trimlen']-3).'...';
						}
						echo nl2br($this->htmlDisplay($this->fdd[$k], $shortdisp));
					} else {
						// it's a normal field
						if ($this->is_values2($k, $row["qf$k"])) {
							$escape_flag = false;
							$shortdisp   = (
							   isset($row['qf'.$k.'_idx'])
							   &&
							   isset($this->fdd[$k]['values2'][$row['qf'.$k.'_idx']])
							   )
							   ? $this->fdd[$k]['values2'][$row['qf'.$k.'_idx']]
							   : '';
						} else {
							$escape_flag = true;
							$shortdisp = $row["qf$k"];
							if ($this->fdd[$k]['strip_tags']) {
								$shortdisp = strip_tags($shortdisp);
							}
							if (isset($this->fdd[$k]['trimlen'])
									&& strlen($shortdisp) > $this->fdd[$k]['trimlen']) {
								$shortdisp = ereg_replace("[\r\n\t ]+",' ',$shortdisp);
								$shortdisp = substr($shortdisp,0,$this->fdd[$k]['trimlen']-3).'...';
							}
						}
						echo nl2br($this->htmlDisplay($this->fdd[$k], $shortdisp,
									true, true, true, $escape_flag));
					}
				} else {
					// it's hidden or a password
					echo '<i>hidden</i>';
				}
				echo '</td>'."\n";
			} // for

			echo '    </tr>'."\n";
			} // while


		/*
		 * Display and accumulate column aggregation info, do totalling query
		 * XXX this feature does not work yet!!!
		 */
		// aggregates listing (if any)
		if ($$var_to_total) {
			// do the aggregate query if necessary
			//if ($vars_to_total) {
				$qp = array();
				$qp['type'] = 'select';
				$qp['select'] = $aggr_from_clause;
				$qp['from'] = $this->create_join_clause ();
				$qp['where'] = $this->make_where_from_query_opts();
				$tot_query = $this->query_make($qp);
				//$this->elog('TOT_QRY: '.$tot_query,__LINE__);
				$totals_result = $this->myquery($tot_query,__LINE__);
				$tot_row       = @mysql_fetch_array($totals_result, MYSQL_ASSOC);
			//}
			$qp_aggr = $qp;
			echo "\n".'<tr>'."\n".'<td>&nbsp;</td>'."\n";
			/*
			echo '<td>';
			echo printarray($qp_aggr);
			echo printarray($vars_to_total);
			echo '</td>';
			echo '<td colspan="'.($this->num_fds-1).'">'.$var_to_total.' '.$$var_to_total.'</td>';
			*/
			// display the results
			for ($k=0;$k<$this->num_fds;$k++) {
				$fd = $this->fds[$k];
				if (stristr($this->fdd[$fd]['options'],'L') or !isset($this->fdd[$fd]['options'])) {
					echo '<td>';
					$aggr_var  = 'qf'.$k.'_aggr';
					$$aggr_var = $this->get_cgi_var($aggr_var);
					if ($$aggr_var) {
						echo $this->sql_aggrs[$$aggr_var].': '.$tot_row[$aggr_var];
					} else {
						echo '&nbsp;';
					}
					echo '</td>'."\n";
				}
			}
			echo '</tr>'."\n";
		}


		echo '  </table>'."\n"; // end of table rows listing


		if ($this->nav_down()) {
			echo '<hr>'."\n";
			$this->display_list_table_buttons($total_recs);
		}

		echo '</form>'."\n";
		//phpinfo();
		/*
		foreach (
			array(
			//	'1999-12-31'=>'%Y-%m-%d',
			//	'99-Mar-31'=>'%y-%M-%d',
			//	'99-1-31'=>'%y-%n-%d'
			//	'March 8, 1999'=>'%F %j, %Y'
			//	'March 8, 1999 09:17:32'=>'%F %j, %Y %H:%i:%s'
				'March 8, 1999 9:17:32'=>'%F %j, %Y %G:%i:%s'
			) as $val=>$mask
		) {
			echo "<br>\n";
			debug_var('val,mask',"$val::$mask");
			debug_var('mdate_parse',date('Y m d H:i:s',$this->mdate_parse($val,$mask)));
		}
		*/
	} /* }}} */

	function display_record() /* {{{ */
	{
		$this->create_javascripts();
	
		echo '<input type="hidden" name="rec"
		value="'.($this->copy_operation()?'':$this->rec).'" />'."\n";
		echo '<input type="hidden" name="fm" value="'.$this->fm.'" />'."\n";
		$this->write_origvars_html($this->get_sfn_cgi_vars());
		echo '<input type="hidden" name="fl" value="'.$this->fl.'" />'."\n";
		echo $this->get_qf_hidden_fields();
		echo '<input type="hidden" name="qfn"
		value="'.htmlspecialchars($this->qfn).'" />'."\n";

		/*
		$this->qfn = '';
		for ($k = 0; $k < $this->num_fds; $k++) {
			$l    = 'qf'.$k;
			$lc   = 'qf'.$k.'_comp';
			$$l   = $this->get_cgi_var($l);
			$$lc  = $this->get_cgi_var($lc);
			$m    = $this->web2plain($$l);  // get the field name and value
			$mc   = $this->web2plain($$lc); // get the comparison operator for numeric/date types
			if (! isset($m)) {
				continue;
			}
			if (is_array($m)) { // multiple selection has been used
				if (!in_array('*',$m)) {	// one '*' in a multiple selection is all you need
					for ($n=0; $n<count($m); $n++) {
						if ($this->plain2web($m[$n]) != '') {
							$this->qfn = $this->qfn.'&qf'.$k.'['.$n.']='
								.$this->plain2web($m[$n]); }
							echo '<input type="hidden" name="qf'.$k.'['.$n.']" value="'
								.$this->plain2web($m[$n]).'" />'."\n";
						}
					}
				}
			} else {
				if ($this->plain2web($m) != '') {
					$this->qfn = $this->qfn.'&qf'.$k.'='.$m;
					echo '<input type="hidden" name="qf'.$k.'"
					value="'.$this->plain2web($m).'" />'."\n";
				}
			}
		}
		*/

		if ($this->nav_up()) {
			$this->display_record_buttons();
			echo '<hr>'."\n";
		}
		echo '<table border="1" cellpadding="1" cellspacing="0" summary="'.$this->tb.'">'."\n";
		if ($this->add_operation()) {
			$this->display_add_record();
		} else {
			$this->display_copy_change_delete_record();
		}
		echo '</table>'."\n";
		if ($this->nav_down()) {
			echo '<hr>'."\n";
			$this->display_record_buttons();
		}
		echo '</form>'."\n";
	} /* }}} */

	/*
	 * Action functions
	 */
	function do_add_record() /* {{{ */
	{
		$REMOTE_USER = ( isset($_SERVER['REMOTE_USER']) )
			? $_SERVER['REMOTE_USER']
			: '';
		$REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
		$tib         = true;
		// check for a before-add trigger
		if (isset($this->triggers['insert']['before'])) {
			$tib = include($this->triggers['insert']['before']);
		}
		if ($tib) {
			// before trigger returned good status let's do the main operation
			$key_col_val = '';
			$qry = '';
			for ($k = 0; $k < $this->num_fds; $k++) {
				if ($this->processed($k)) {
					$fd = $this->fds[$k];
					if ($fd == $this->key) {
						$key_col_val = addslashes($this->encode($this->fdd[$k],$fn));
					}
					if ($qry == '') {
						$qry = 'INSERT INTO '.$this->tb.' (`'.$fd.'`';
					} else {
						$qry = $qry.',`'.$fd.'`';
					}
				}
			}
			$tim = false;
			// do the main operation
			$val = ') VALUES (';
			$vals = array();
			for ($k = 0; $k < $this->num_fds; $k++) {
				$type = $this->fdd[$k]['type'];
				if ($this->processed($k)) {
					$fd = $this->fds[$k];
					// XXX: REMOVE ME!
					// $fn = $this->get_http_post_var_by_name($fd);
					$fn = $this->get_cgi_var($fd);
					/*
					if ($this->col_is_date($k))
					{
						//$vals[$k] = '"'.$this->mdate_set($this->mdate_getFromPost($k),$type,$this->fds[$k]['type']).'"'; 
						if ($type == 'time')
							$vals[$k] = 'date_format(from_unixtime('.$this->mdate_getFromPost($k).'),"%H%i%s")'; 
						elseif ($type == 'year')
							$vals[$k] = 'date_format(from_unixtime('.$this->mdate_getFromPost($k).'),"%Y")'; 
						else
							$vals[$k] = 'from_unixtime('.$this->mdate_getFromPost($k).')'; 
					} else // continued on next line
					*/
					/* Old Jim code: $this->col_is_set($k) && $fn != ''*/
					if (is_array($fn)) {
						$vals[$k] = "'".addslashes($this->encode($this->fdd[$k],join(',',$fn)))."'";
					} else {
						$vals[$k] = "'".addslashes($this->encode($this->fdd[$k],$fn))."'";
					}
				}
			}
			$qry = $qry.$val.join(',',$vals).')';
			$res = $this->myquery($qry,__LINE__);
			if ($res) {
				$tim = true;
			}
			$this->message = @mysql_affected_rows($this->dbh).' '.$this->labels['record added'];
		}
		if (
			$tib &&
			isset($this->triggers['insert']['after']) &&
			$tim
		) {
			// before executed ok
			// main op executed ok
			// let's do the after trigger
			$tia = include($this->triggers['insert']['after']);
		}
		// notify list
		$kv = array();
		if (($this->notify['insert'])) {
			$user = $REMOTE_USER;
			if (! $user)
				$user = $REMOTE_ADDR;
			$body = 'A new item was added to '.$this->page_name." by ".$user." with the following fields:\n";
			for ($k=0;$k<$this->num_fds;$k++) {
				if ($this->processed($k)) {
					$body .= $this->fdd[$k]['name'].': '.$vals[$k]."\n";
					$kv[$this->fds[$k]] = $vals[$k];
				}
			}
			// mail it
			mail($this->notify['insert'],'Record Added to '.$this->tb,$body);
		}
		// note change in log table
		if ($this->logtable) {
			$this->myquery(
				"INSERT INTO ".$this->logtable." VALUES (".
				"NOW(),".
				"'".$REMOTE_USER."',".
				"'".$REMOTE_ADDR."',".
				"'insert','".
				$this->tb."',".
				"'".$key_col_val."','','','".
				addslashes(serialize($kv))."')"
			,__LINE__);
		}
	} /* }}} */

	function do_change_record() /* {{{ */
	{
		$REMOTE_USER = ( isset($_SERVER['REMOTE_USER']) )
			? $_SERVER['REMOTE_USER']
			: '';
		$REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
		$tub         = true;
		// check for a before-add trigger
		if (isset($this->triggers['update']['before'])) {
			$tub = include($this->triggers['update']['before']);
		}
		$tum = false;
		if ($tub) {
			// before trigger returned good status
			// let's do the main operation
			$qry = '';
			$qry_old_rec = '';
			for ($k = 0; $k < $this->num_fds; $k++) {
				$type = $this->fdd[$k]['type'];
				if ($this->processed($k) && ! $this->readonly($k)) {
					$fd = $this->fds[$k];
					if ($fd == $this->key) {
						$key_col_val = addslashes($this->get_cgi_var($fd));
					}
					$fn = $this->get_cgi_var($fd);
					/*
					if ($this->col_is_date($k))
					{
						$fn = date(str_replace('%','',$this->mdate_masks[$type]),$this->mdate_getFromPost($k));
					}
					*/
					/* Old Jim code: $this->col_is_set($k) && $fn != ''*/
					if (is_array($fn)) {
						$newValue = addslashes($this->encode($this->fdd[$k],join(',',$fn)));
					} else {
						$newValue = addslashes($this->encode($this->fdd[$k],$fn));
					}
					if ($qry == '') {
						$qry = 'UPDATE '.$this->tb.' SET `'.$fd.'`=\''.$newValue.'\'';
						$qry_old_rec = 'SELECT `'.$fd.'`';
					} else {
						$qry = $qry.',`'.$fd.'`=\''.$newValue.'\'';
						$qry_old_rec .= ',`'.$fd.'`';
					}
					$newvalues[$this->fds[$k]] = addslashes($fn);
				} elseif ($this->hidden($k)) {
					// XXX do something
				}
			}
			$qry = $qry.' WHERE ('.$this->key.' = '.$this->key_delim.$this->rec.$this->key_delim.')';
			$qry_old_rec .= ' FROM '.$this->tb.' WHERE ('.$this->key.' = '.$this->key_delim.$this->rec.$this->key_delim.')';
		    // get the old data
		    $res_old = $this->myquery($qry_old_rec, __LINE__);
		    $oldvalues = @mysql_fetch_array($res_old);
		    // update the data
			//echo "\n<h4>$qry</h4>\n";
			$res = $this->myquery($qry,__LINE__);
		    // find and accumulate the changes
		    $changes=array();
		    for ($k = 0; $k < $this->num_fds; $k++) {
		      if ($this->processed($k)) {
			  	if ( isset($oldvalues[$this->fds[$k]]) 
					&& $oldvalues[$this->fds[$k]] != stripslashes($newvalues[$this->fds[$k]])) {
			  		$changes[$this->fds[$k]] = array();
			  		$changes[$this->fds[$k]]['was'] = $oldvalues[$this->fds[$k]];
			  		$changes[$this->fds[$k]]['is' ] = $newvalues[$this->fds[$k]];
			  	}
			  }
			}
			if ($res) {
				$tum = true;
			}

/*
echo '<h3>Was:</h3>'."\n";
echo '<pre>';
print_r($oldvalues);
echo '</pre>'."\n";

echo '<h3>Is:</h3>'."\n";
echo '<pre>';
print_r($newvalues);
echo '</pre>'."\n";

echo '<h3>Changes to be sent in e-mail:</h3>'."\n";
echo '<pre>';
print_r($changes);
echo '</pre>'."\n";
echo '<h5>'.@mysql_affected_rows($this->dbh).' '.$this->labels['Change'].'</h5>'."\n";
*/

			$this->message = @mysql_affected_rows($this->dbh).' '.$this->labels['record changed'];
		}
		if (
			$tub &&
			isset($this->triggers['update']['after']) &&
			$tum
		) {
			// before executed ok
			// main op executed ok
			// let's do the after trigger
			$tua = include($this->triggers['update']['after']);
		}
		// notify list
		if (($this->notify['update'])) {
			if (count($changes) > 0) {
				$user = $REMOTE_USER;
				if (! $user)
					$user = $REMOTE_ADDR;
				$body = 'An item with '
					.$this->fdd[$this->key]['name']
					.'='
					.$this->key_delim.$this->rec.$this->key_delim
					.' was updated by '.$user.' in '.$this->page_name." with the following fields:\n";
				foreach ($changes as $key=>$vals) {
					if ($this->processed($k)) {
						$fieldName = $this->fdd[$key]['name'];
						$body .=
							$fieldName.":\n".
							"was:\t\"".$vals['was']."\"\n".
							"is:\t\"".$vals['is']."\"\n";
					}
				}
				// mail it
				mail($this->notify['update'],'Record Updated in '.$this->tb,$body);
			}
		}

		// note change in log table
		if ($this->logtable) {
			foreach ($changes as $key=>$vals) {
				$qry = "INSERT INTO ".$this->logtable." VALUES (".
					"NOW(),'".$REMOTE_USER."','".$REMOTE_ADDR."','update','".
					$this->tb."','".$key_col_val."','".$key."','".
					addslashes($vals['was'])."','".
					addslashes($vals['is'])."')";
				$this->myquery($qry,__LINE__);
			}
		}
	} /* }}} */

	function do_delete_record() /* {{{ */
	{
		$REMOTE_USER = ( isset($_SERVER['REMOTE_USER']) )
			? $_SERVER['REMOTE_USER']
			: '';
		$REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
		$tdb         = true;
		// check for a before-add trigger
		if (isset($this->triggers['delete']['before'])) {
			$tdb = include($this->triggers['delete']['before']);
		}
		$tdm = false;

		$fn = ( isset($fn) )
			? $fn
			: '';

		// before trigger returned good status
		// let's do the main operation
		if ($tdb) {
			// before trigger returned good status
			// let's do the main operation
			for ($k = 0; $k < $this->num_fds; $k++) {
				if ($this->processed($k)) {
					$fd = $this->fds[$k];
					if ($fd == $this->key) {
						$key_col_val = addslashes($this->encode($this->fdd[$k],$fn));
					}
				}
			}

			if ($this->logtable) {
				$res = $this->myquery(
					'select * from '.$this->tb.' where (`'.$this->key.'` = '.$this->key_delim.$this->rec.$this->key_delim.')'
				,__LINE__);
				$oldrow = mysql_fetch_array($res);
			}
			$qry = 'DELETE FROM '.$this->tb.' WHERE (`'.$this->key.'` = '.$this->key_delim.$this->rec.$this->key_delim.')';
			$res = $this->myquery($qry,__LINE__);
			if ($res) {
				$tdm = true;
			}
			$this->message = @mysql_affected_rows($this->dbh).' '.$this->labels['record deleted'];
		}
		if (
			$tdb &&
			isset($this->triggers['delete']['after']) &&
			$tdm
		) {
			// before executed ok
			// main op executed ok
			// let's do the after trigger
			$tda = include($this->triggers['delete']['after']);
		}

		// notify list
		if (($this->notify['delete'])) {
			$user = $REMOTE_USER;
			if (! $user)
				$user = $REMOTE_ADDR;
			$body = 'An item was deleted by '.$user.' from '.$this->page_name."\n";
			foreach ($oldrow as $key=>$val) {
				if (is_string($key)) {
					$body .= $this->fdd[$key]['name'].":\t".$val."\n";
				}
			}
			// mail it
			mail($this->notify['delete'],'Record Deleted in '.$this->tb,$body);
		}
		// note change in log table
		if ($this->logtable) {
			$this->myquery(
				"INSERT INTO ".$this->logtable." VALUES (".
				"SYSDATE(),".
				"'".$REMOTE_USER."',".
				"'".$REMOTE_ADDR."',".
				"'delete','".
				$this->tb."',".
				"'".$key_col_val."',".
				"'".$key."','".
				addslashes(serialize($oldrow))."','')"
			,__LINE__);
		}
	} /* }}} */

	/*
	 * Recreate functions
	 */
	function recreate_fdd() /* {{{ */
	{
		// TODO: one level deeper browsing

		$action_letter = 'L'; // list by default
		$this->filter_operation() && $action_letter = 'F';
		$this->view_operation()   && $action_letter = 'V';
		$this->delete_operation() && $action_letter = 'D';
		$this->add_operation()    && $action_letter = 'A';
		$this->change_operation() && $action_letter = 'C';
		$this->copy_operation()   && $action_letter = 'P';

		// Restore backups (if exists)
		foreach (array_keys($this->fdd) as $column) {
			foreach (array_keys($this->fdd[$column]) as $col_option) {
				if ($col_option[strlen($col_option) - 1] != '~')
					continue;

				$this->fdd[$column][substr($col_option, 0, strlen($col_option) - 1)]
					= $this->fdd[$column][$col_option];
				unset($this->fdd[$column][$col_option]);
			}
		}

		foreach (array_keys($this->fdd) as $column) {
			foreach (array_keys($this->fdd[$column]) as $col_option) {
				if (! strchr($col_option, '|'))
					continue;
				$col_ar = explode('|', $col_option, 2);
				if (! stristr($col_ar[1], $action_letter))
					continue;

				// Make field backups
				$this->fdd[$column][$col_ar[0] .'~'] = $this->fdd[$column][$col_ar[0]];
				$this->fdd[$column][$col_option.'~'] = $this->fdd[$column][$col_option];
				// Set particular field
				$this->fdd[$column][$col_ar[0]] = $this->fdd[$column][$col_option];
				unset($this->fdd[$column][$col_option]);
			}
		}
	} /* }}} */

	function recreate_displayed() /* {{{ */
	{
		$field_num            = 0;
		$num_fields_displayed = 0;
		$this->fds            = array();
		$this->displayed      = array();
		$this->guidance       = false;

		foreach ($this->fdd as $akey => $aval) {
			if (preg_match('/^\d*$/', $akey)) /* skipping numeric keys */
				continue;

			$this->fds[$field_num] = $akey;

			/* We must use here displayed() function, because displayed[] array
			   is not created yet. We will simultaneously create that array as well. */
			if ($this->displayed[$field_num] = $this->displayed($field_num)) {
				$num_fields_displayed++;
			}
			/* I *really* want to know what does this foreach() do!
			   -- Nepto [17/10/2002] */
			if ( isset($aval['values']) && is_array($aval['values'])
				&& !isset($aval['values']['table']) )
			{
				$values = array();
				foreach ($aval['values'] as $val) {
					$values[$val] = $val;
				}
				$aval['values'] = $values;
			}
			$this->fdd[$field_num] = $aval;
			$aval['help'] && $this->guidance = true;
			$field_num++;
		}
		if (0) {
			echo '<b>Displayed array:</b><pre>';
			var_dump($this->displayed);
			echo '</pre><hr>';
		}
		
		$this->num_fds              = $field_num;
		$this->num_fields_displayed = $num_fields_displayed;
		$this->key_num              = array_search($this->key, $this->fds);

		/* Adds first displayed column into sorting fields by replacing last
		   array entry. Also remove duplicite values and change column names to
		   their particular field numbers.

		   Note that entries like [0]=>'9' [1]=>'-9' are correct and they will
		   have desirable sorting behaviour. So there is no need to remove them.
		 */
		for ($k = 0; !isset($this->displayed[$k]); $k++);
		$this->sfn[count($this->sfn) - 1] = "$k"; // important quotes
		$this->sfn = array_unique($this->sfn);
		$check_ar = array();
		foreach ($this->sfn as $key => $val) {
			if (preg_match('/^[-]?\d*$/', $val)) { // skipping numeric keys
				$val = abs($val);
				if (in_array($val, $check_ar)) {
					unset($this->sfn[$key]);
				} else {
					$check_ar[] = $val;
				}
				continue;
			}
			if ($val[0] == '-') {
				$val = substr($val, 1);
				$minus = '-';
			} else {
				$minus = '';
			}
			if (($val = array_search($val, $this->fds)) === false) {
				unset($this->sfn[$key]);
			} else {
				$val = intval($val);
				if (in_array($val, $check_ar)) {
					unset($this->sfn[$key]);
				} else {
					$this->sfn[$key] = $minus.$val;
					$check_ar[] = $val;
				}
			}
		}
		$this->sfn = array_unique($this->sfn);
		return true;
	} /* }}} */

	/*
	 * Error handling function
	 */
	function error($message) /* {{{ */
	{
		echo '<h1>phpMyEdit error: '.htmlspecialchars($message).'</h1>'."\n";
		return false;
	} /* }}} */

	/*
	 * Database connection function
	 */
	function connect() /* {{{ */
	{
		if (!isset($this->db)) {
			$this->error('no database defined');
			return false;
		}
		if (!isset ($this->tb)) {
			$this->error('no table defined');
			return false;
		}

		if ($this->dbh = @mysql_pconnect($this->hn, $this->un, $this->pw)) ;
		else {
			$this->error('could not connect to MySQL');
			return false;
		}

		return true;
	} /* }}} */

	/*
	 * Database disconnection function
	 */
	function disconnect() /* {{{ */
	{
		@mysql_close($this->dbh);
	} /* }}} */

	/*
	 * The workhorse
	 */
	function execute() /* {{{ */
	{
		//  DEBUG -  uncomment to enable
		/*
		//phpinfo();
		$this->print_get_vars();
		$this->print_post_vars();
		$this->print_vars();
		echo "<pre>query opts:\n";
		echo print_r($this->query_opts);
		echo "</pre>\n";
		echo "<pre>get vars:\n";
		echo print_r($this->get_opts);
		echo "</pre>\n";
		 */

		/* Database connection */
		if ($this->connect() == false)
			return false;

		/*
		 * ======================================================================
		 * Pass 3: process any updates generated if the user has selected
		 * a save button during Pass 2
		 * ======================================================================
		 */
		if ($this->saveadd == $this->labels['Save']) {
			$this->do_add_record();
		}
		if ($this->moreadd == $this->labels['More']) {
			$this->do_add_record();
			$this->operation = $this->labels['Add']; // to force add operation
			$this->recreate_fdd();
			$this->recreate_displayed();
		}
		if ($this->savechange == $this->labels['Save']) {
			$this->do_change_record();
		}
		if ($this->morechange == $this->labels['Apply']) {
			$this->do_change_record();
			$this->operation = $this->labels['Change']; // to force change operation
			$this->recreate_fdd();
			$this->recreate_displayed();
		}
		if ($this->savedelete == $this->labels['Delete']) {
			$this->do_delete_record();
		}

		/*
		 * ======================================================================
		 * Pass 2: display an input/edit/confirmation screen if the user has
		 * selected an editing button on Pass 1 through this page
		 * ======================================================================
		 */
		if ($this->add_operation()
				|| $this->change_operation() || $this->delete_operation()
				|| $this->view_operation()   || $this->copy_operation()) {
			$this->display_record();
		}

		/*
		 * ======================================================================
		 * Pass 1 and Pass 3: display the MySQL table in a scrolling window on
		 * the screen (skip this step in 'Add More' mode)
		 * ======================================================================
		 */
		else {
			$this->list_table();
		}

		// $this->disconnect();

		global $phpMyEdit_timer;
		if ($this->display['time'] && $phpMyEdit_timer) {
			echo $phpMyEdit_timer->end();
		}
	} /* }}} */

	/*
	 * Class constructor
	 */
	function phpMyEdit($opts) /* {{{ */
	{
		/*
		 * Creating directory variables
		 */
		$this->dir['root'] = dirname(__FILE__)
			. (strlen(dirname(__FILE__)) > 0 ? '/' : '');
		$this->dir['lang'] = $this->dir['root'].'lang/';

		/*
		 * Creting URL variables
		 */
		$this->url['images'] = 'images/';
		isset($opts['url']['images']) && $this->url['images'] = $opts['url']['images'];

		/*
		 * Instance class variables
		 */
		$this->hn        = $opts['hn'];
		$this->hn        = $opts['hn'];
		$this->un        = $opts['un'];
		$this->pw        = $opts['pw'];
		$this->db        = $opts['db'];
		$this->tb        = $opts['tb'];
		$this->key       = $opts['key'];
		$this->key_type  = $opts['key_type'];
		$this->inc       = $opts['inc'];
		$this->options   = $opts['options'];
		$this->fdd       = $opts['fdd'];
		$this->multiple  = intval($opts['multiple']);
		$this->multiple <= 0 && $this->multiple = 2;
		$this->display   = $opts['display'];
		$this->filters  = ( isset($opts['filters']) )
			? $opts['filters']
			: '';
		$this->triggers  = ( isset($opts['triggers']) )
			? $opts['triggers']
			: '';
		$this->logtable  = ( isset($opts['logtable']) )
			? $opts['logtable']
			: '';
		$this->page_name = ( isset($opts['page_name']) )
			? $opts['page_name']
			: '';
		// REMOVE ME!
		//$this->default_sort_columns = $opts['default_sort_columns'];

		// alternate row background colors
		/* What's this?!

		   if (isset($opts['bgcolorOdd'])) {
		   $this->bgcolorOdd = 'White';
		   } else {
		   $this->bgcolorOdd = $opts['bgcolorOdd'];
		   }
		   if (isset($opts['bgColorEven'])) {
		   $this->bgcolorEven = 'Silver';
		   } else {
		   $this->bgcolorEven = $opts['bgcolorEven'];
		   }
		 */

		// e-mail notification
		if ( isset($opts['notify']) ) 
		{
			$this->notify = $opts['notify'];
		}
		else
		{
		   $this->notify = array(
			 'delete' => false,
			 'insert' => false,
			 'update' => false
			);
		}

		// navigation
		$this->navigation = $opts['navigation'];
		if (! $this->nav_buttons() && ! $this->nav_text_links() && ! $this->nav_graphic_links()) {
			$this->navigation .= 'B'; // buttons are default
		}
		if (! $this->nav_up() && ! $this->nav_down()) {
			$this->navigation .= 'D'; // down position is default
		}

		// language labels (must go after navigation)
		$this->labels = $this->make_language_labels($opts['language']
				? $opts['language'] : $_SERVER['_ACCEPT_LANGUAGE']);

		// CGI variables
		$this->cgi['append']    = ( isset($opts['cgi']['append']) )
			? $opts['cgi']['append']
			: array();
		$this->cgi['overwrite']    = ( isset($opts['cgi']['overwrite']) )
			? $opts['cgi']['overwrite']
			: array();

		/*
		 * Find the URL to post forms
		 */

		$this->page_name = basename($_SERVER['PHP_SELF']);

		/*
		 * Sorting variables
		 */
		$this->sfn   = $this->get_cgi_var('sfn');
		isset($this->sfn)             || $this->sfn          = array();
		is_array($this->sfn)          || $this->sfn          = array($this->sfn);
		isset($opts['sort_field'])    || $opts['sort_field'] = array();
		is_array($opts['sort_field']) || $opts['sort_field'] = array($opts['sort_field']);
		$this->sfn   = array_merge($this->sfn, $opts['sort_field']);
		$this->sfn[] = '0'; // this last entry will be replaced in recreate_displayed()

		/*
		 * Form variables all around
		 */

		$this->operation  = $this->get_cgi_var('operation');
		$this->apply      = $this->get_cgi_var('apply');
		$this->fl         = intval($this->get_cgi_var('fl'));
		$this->fm         = intval($this->get_cgi_var('fm'));

		$this->qfn        = $this->get_cgi_var('qfn');
		$this->sw         = $this->get_cgi_var('sw');
		$this->rec        = $this->get_cgi_var('rec', ''); // Fixed #523390 [7/8/2002] [2/2]
		$this->prev       = $this->get_cgi_var('prev');
		$this->next       = $this->get_cgi_var('next');
		$this->saveadd    = $this->get_cgi_var('saveadd');
		$this->moreadd    = $this->get_cgi_var('moreadd');
		$this->savechange = $this->get_cgi_var('savechange');
		$this->morechange = $this->get_cgi_var('morechange');
		$this->savedelete = $this->get_cgi_var('savedelete');

		/*
		 * Filter setting
		 */
		if (isset($this->sw)) {
			$this->sw == $this->labels['Search'] && $this->fl = 1;
			$this->sw == $this->labels['Hide']   && $this->fl = 0;
			$this->sw == $this->labels['Clear']  && $this->fl = 0;
		}

		/*
		 * Specific $fdd modifications depending on performed action
		 */

		$this->recreate_fdd();

		/*
		 * Extract SQL Field Names and number of fields
		 */

		$this->recreate_displayed();

		/*
		 * Clear action
		 */
		if ($this->sw == $this->labels['Clear']) {
			for ($k = 0; $k < $this->num_fds; $k++) {
				$this->cgi['overwrite']["qf$k"] = '';
				$this->cgi['overwrite']["qf$k".'_comp'] = '';
			}
		}

		/*
		 * Constants
		 */

		// code to use this is commented out
		$this->sql_aggrs = array(
				''      => '',
				'sum'   => 'Total',
				'avg'   => 'Average',
				'min'   => 'Minimum',
				'max'   => 'Maximum',
				'count' => 'Count');

		// to support quick type checking
		$this->stringTypes = array('string','blob','set','enum');
		$this->numberTypes = array('int','real');
		$this->dateTypes   = array('date','datetime','timestamp','time','year');

		// mdate constants
		$this->mdate_masks = array(
				'date'=>'%Y-%m-%d',
				'datetime'=>'%Y-%m-%d %H:%i:%s',
				'timestamp'=>'%Y%m%d%H%i%s',
				'time'=>'%H:%i:%s',
				'year'=>'%Y');

		$this->mdate_daterange = range(date('Y')-10,date('Y')+10);

		$this->months_short = array(
				'~~PME~~'=>0,
				'Jan'=>1, 'Feb'=>2, 'Mar'=>3, 'Apr'=>4,
				'May'=>5, 'Jun'=>6, 'Jul'=>7, 'Aug'=>8,
				'Sep'=>9, 'Oct'=>10, 'Nov'=>11, 'Dec'=>12);
		$this->months_long = array(
				'~~PME~~'=>0,
				'January'=>1,'February'=>2,'March'=>3,
				'April'=>4,'May'=>5,'June'=>6,
				'July'=>7,'August'=>8,'September'=>9,
				'October'=>10,'November'=>11,'December'=>12);
		$this->months_long_keys = array_keys($this->months_long);

		/* If you are phpMyEdit developer, set this to 1.
		   You can also hide some new unfinished and/or untested features under
		   if ($this->development) { new_feature(); } statement.

		   Also note, that this is currently unused. */
		$this->development = 0;

		/*
		 * Preparing some others values
		 * (this was moved from execute() method)
		 */
		set_magic_quotes_runtime(0); // let's do explicit quoting ... it's safer

		// XXX fix this to use col_is_[type]
		if (in_array($this->key_type,
					array('string','blob','date','time','datetime','timestamp','year'))) {
			$this->key_delim = '"';
		} else {
			$this->key_delim = '';
			$this->rec = intval($this->rec); // Fixed #523390 [7/8/2002] [1/2]
		}

		$this->gather_query_opts();
		$this->gather_get_vars();
		$this->gather_post_vars();
		$this->unify_opts();

		// Call to Action
		// Moved this from the setup.php generated file to here
		!isset($opts['execute']) && $opts['execute'] = 1;
		$opts['execute'] && $this->execute();
	} /* }}} */

} // end of phpMyEdit class

/* Modeline for ViM {{{
 * vim:set ts=4:
 * vim600:fdm=marker fdl=0 fdc=0:
 * }}} */

?>
