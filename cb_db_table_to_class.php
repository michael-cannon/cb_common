<?php

	/**
	 * Cannon BOSE's database table to simple class creator.
	 *
	 * Copyright (C) 2002 Michael Cannon <michael@peimic.com>
	 * See full GNU Lesser General Public License in LICENSE.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: cb_db_table_to_class.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */

	

	/**
	 * Returns HTML page top.
	 *
	 * @return string
	 */
	function page_header()
	{
		$out = "
		<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.0 Transitional//EN'>
		<html>
		<head>
			<title>Table to Class Setup</title>
		</head>
		<style type='text/css'>
			body  { font-family: 'Verdana', 'Arial', 'Sans-Serif'; text-align: left }
			h1    { color: #004d9c; font-size: 12pt; font-weight: bold }
			h2    { color: #004d9c; font-size: 10pt; font-weight: bold }
			h3    { color: #004d9c; font-size: 10pt; }
			p     { color: #004d9c; font-size: 8pt; }
			table { border: 2px solid #004d9c; font-size: 8pt; text-align: center; border-collapse: 'collapse'; }
			td    { border: 2px solid; padding: 2px; color: #004d9c; font-size: 8pt; }
		</style>
		<body bgcolor='white'>
		";

		return $out;
	}


	
	/**
	 * Returns HTML page bottom.
	 *
	 * @return string
	 */
	function page_footer()
	{
		$out = '</body></html>';

		return $out;
	}



	/**
	 * Echo htmlentities version of $string.
	 *
	 * @param string $string, ex: "<html><head><title>..."
	 * @return void
	 */
	function echo_html($string) 
	{
		echo htmlentities($string) . "\n";
	}



	// begin script
	page_header();

	$pageTitle    = get_POST('pageTitle');
	$baseFilename = get_POST('baseFilename');
	$submit       = get_POST('submit');
	$hn           = get_POST('hn');
	$un           = get_POST('un');
	$pw           = get_POST('pw');
	$db           = get_POST('db');
	$tb           = get_POST('tb');
	$tp           = get_POST('tp');

	$phpExtension = '.php';

	// directory to put the .php file into
	$destDir = '.';


	// directory to put the .inc file into
	$inclDir = '.';

	if ($tb) 
	{
		if ( '' != $tp )
		{
			$tb = preg_replace("/^$tp/i", '', $tb);
		}

		$phpFile = "{$tb}_table.class{$phpExtension}";
	} 

	elseif ($baseFilename != '') 
	{
		$phpFile = $baseFilename.$phpExtension;
	} 

	else 
	{
		$phpFile = 'index'.$phpExtension;
	}

	$self   = basename($_SERVER['PHP_SELF']);

	if ( $hn && $un )
	{
		$dbl = mysql_pconnect($hn,$un,$pw);
	}

	else
	{
		$dbl = false;
	}

	if ((!$dbl) or empty($submit)) 
	{
		echo '<h1>Please log in to your MySQL database</h1>';

		if (!empty($submit)) 
		{
			echo '  <h2>Sorry - login failed - please try again</h2>'."\n";
		}

	  echo '
	  <form action="'.$self.'" method="POST">
		 <table border="1" cellpadding="1" cellspacing="0" summary="Log in">
			<tr>
			  <td>Hostname:</td>
			  <td><input type="text" name="hn" value="'.$hn.'"></td>
			</tr><tr>
			  <td>Username:</td>
			  <td><input type="text" name="un" value="'.$un.'"></td>
			</tr><tr>
			  <td>Password:</td>
			  <td><input type="password" name="pw" value="'.$pw.'"></td>
			</tr>
		 </table>
		 <br>
		 <input type="submit" name="submit" value="Submit">
	  </form>'."\n";
	}

	else 
	{
		// connect to database
	  if (!$db) 
	  {
		 $dbs = mysql_list_dbs($dbl);
		 $num_dbs = mysql_numrows($dbs);

		 echo '  <h1>Please choose a database</h1>
	  <form action="'.$self.'" method="POST">
		 <input type="hidden" name="hn" value="'.$hn.'">
		 <input type="hidden" name="un" value="'.$un.'">
		 <input type="hidden" name="pw" value="'.$pw.'">
		 <table border="1" cellpadding="1" cellspacing="1" summary="Choose Database">'."\n";

		for ($i=0; $i<$num_dbs; $i++) 
		{
			$db = mysql_dbname($dbs, $i);
			if ($i==0) 
			{
				echo '      <tr><td><input checked type="radio" name="db" value="'.$db.'"></td><td>'.$db.'</td></tr>'."\n";
			} 
			
			else 
			{
				echo '      <tr><td><input type="radio" name="db" value="'.$db.'"></td><td>'.$db.'</td></tr>'."\n";
			}
		}
			  echo '    </table>
		 <br>
		 <input type="submit" name="submit" value="Submit">
		 <input type="submit" name="cancel" value="Cancel">
	  </form>'."\n";
	  }
	  
	  else 
	  {
		// find table to query
		 if (!$tb) 
		 {
			echo '  <h1>Please choose a table from database: '.$db.'</h1>
	  <form action="'.$self.'" method="POST">
		 <input type="hidden" name="hn" value="'.$hn.'">
		 <input type="hidden" name="un" value="'.$un.'">
		 <input type="hidden" name="pw" value="'.$pw.'">
		 <input type="hidden" name="db" value="'.$db.'">
		 Table prefix: <input type="text" name="tp" /><br />
		 <table border="1" cellpadding="1" cellspacing="1" summary="Choose Table">'."\n";
			$tbs = mysql_list_tables($db,$dbl);
			$num_tbs = @mysql_num_rows($tbs);

			for ($j=0; $j<$num_tbs; $j++) 
			{
			  $tb = mysql_tablename($tbs, $j);
				if ($j == 0) 
				{
					echo '      <tr><td><input checked type="radio" name="tb" value="'.$tb.'"></td><td>'.$tb.'</td></tr>'."\n";
				}
				
				else 
				{
					echo '      <tr><td><input type="radio" name="tb" value="'.$tb.'"></td><td>'.$tb.'</td></tr>'."\n";
				}
			}
			echo '    </table>
		 <br>
		 <input type="submit" name="submit" value="Submit">
		 <input type="submit" name="cancel" value="Cancel">
	  </form>'."\n";
		 }
		 
		 else  
		 {
			echo '<h1>Here is your table class script</h1>'."\n";
			mysql_select_db($db) or die("Unable to select $db at : " 
				. __FILE__ . ':' . __LINE__);
			
			// get table details
			$tb_desc_sql = "DESCRIBE `$tp$tb`";
			$tb_desc = mysql_query($tb_desc_sql);

			// class var string
			$var = '';
			$var .= <<<EOL
	var \$_table_name;
	var \$_table_prefix;
	var \$_primary_key;
	var \$_fields;
	var \$REF_1;			// REF_*, extra variables, use as needed
	var \$REF_2;
	var \$REF_3;

EOL;

			// class constructor string
			$constructor = '';
			$constructor .= <<<EOL
		\$this->_table_name = '$tb';
		\$this->_table_prefix = '';

EOL;

			// no fields (column headers) or primary key defined yet
			$pk = array();
			$fields = array();

			// from table details determine fields
			while ( $tb_desc && $field = mysql_fetch_object($tb_desc) )
			{
				// class: var $member;
				$var .= "\tvar \$$field->Field;\n";

				if ( 'PRI' == $field->Key )
				{
					$pk[] = "'$field->Field'";
				}
					
				// build fields list
				$fields[] = "'$field->Field'";

				// do member assignment
				// $this->attribute = ( isset($input->attribute) )
				//		? $input->attribute
				//		: default_value; 
				$constructor .= <<<EOL

		\$this->$field->Field = ( isset(\$$field->Field) )
			? mysql_escape_string(\$$field->Field)
			: '$field->Default';

EOL;
			}
			
			// primary key, denote false if none
			$pk = ( count($pk) )
				? implode(', ', $pk)
				: false;

			$fields = implode(', ', $fields);

			$constructor .= <<<EOL

		\$this->REF_1 = ( isset(\$REF_1) )
			? \$REF_1
			: '';

		\$this->REF_2 = ( isset(\$REF_2) )
			? \$REF_2
			: '';

		\$this->REF_3 = ( isset(\$REF_3) )
			? \$REF_3
			: '';

		\$this->_primary_key = array($pk);

		\$this->_fields = array($fields);
EOL;

			$class = <<<EOL
<?php

	/**
	 * Class $tb
	 *
	 * Helper class for $tb SQL operations.
	 *
	 * @author Michael Cannon, michael@peimic.com
	 * @package TBD 
	 * @version \$Id: cb_db_table_to_class.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $ 
	 */


class {$tb}_table
{
$var

	/**
	 * Class constructor.
	 *
	 * @param mixed \$input, preset array/object
	 * @return void
	 */
	function {$tb}_table(\$input = 0)
	{
		// convert \$input contents to variables
		// extract would work on array, but not object hence the loop
		if ( is_array(\$input) || is_object(\$input) )
		{
			foreach (\$input AS \$key => \$value)
			{
				\$\$key = \$value;
			}
		}

$constructor
	}



	/**
	 * Returns SQL DELETE contents as a string.
	 *
	 * WARNING: \$key = false will return a DELETE to delete all table
	 * information.
	 *
	 * @see where(\$key = true)
	 * @param mixed boolean\string \$key, 
	 *		ex: true (denotes use own primary key)
	 *			false (no where clause)
	 *			34 (denotes use 'WHERE primary_key = '34')
	 * @return mixed string/boolean
	 */
	function delete(\$key = true)
	{
		\$delete = 'DELETE';

		\$delete .= \$this->from();

		// if \$key given, create WHERE clause
		\$where = \$this->where(\$key);

		return ( ( false !== \$where ) ? \$delete . \$where : \$delete );
	}



	/**
	 * Returns SQL FROM contents as a string.
	 *
	 * @return string
	 */
	function from()
	{
		\$from = ' FROM ';
		
		\$from .= '`' . \$this->_table_prefix . \$this->_table_name . '`';

		return \$from;
	}



	/**
	 * Returns SQL INSERT contents as a string.
	 *
	 * @return string
	 */
	function insert()
	{
		\$insert = 'INSERT IGNORE INTO ';

		\$insert .= \$this->set();

		return \$insert;
	}



	/**
	 * Returns SQL REPLACE contents as a string.
	 *
	 * @return string
	 */
	function replace()
	{
		\$replace = 'REPLACE INTO ';

		\$replace .= \$this->set();

		return \$replace;
	}



	/**
	 * Returns SQL SELECT contents as a string.
	 *
	 * @see where(\$key = true)
	 * @param mixed boolean\string \$key, 
	 *		ex: true (denotes use own primary key)
	 *			false (no where clause)
	 *			34 (denotes use 'WHERE primary_key = '34')
	 * @return mixed string/boolean
	 */
	function select(\$key = true)
	{
		\$select = 'SELECT ';

		\$select .= '`' . implode('`, `', \$this->_fields) . '`';

		\$select .= \$this->from();

		// if \$key given, create WHERE clause
		\$where = \$this->where(\$key);

		return ( ( false !== \$where ) ? \$select . \$where : \$select );
	}



	/**
	 * Returns SQL SET contents as a string.
	 *
	 * @param boolean \$full_set, ex: true (table_name SET x='1', ...), 
	 * 	false (x='1', ...)
	 * @return string
	 */
	function set(\$full_set = true)
	{
		\$fields = array();
		\$set = '';

		\$set .= ( true === \$full_set )
			? '`' . \$this->_table_prefix . \$this->_table_name . '` SET '
			: '';
		
		// create csv string with member and value
		foreach ( \$this->_fields AS \$key => \$field )
		{
			// is field value 'safe'?
			\$value = ( is_numeric(\$this->\$field) )
				? \$this->\$field
				: "'" . \$this->\$field . "'";

			\$fields[] = "\$field = \$value";
		}

		\$set .= implode(', ', \$fields);

		return \$set;
	}

	
	
	/**
	 * Returns SQL UPDATE contents as a string.
	 *
	 * @see where(\$key = true)
	 * @param mixed boolean\string \$key, 
	 *		ex: true (denotes use own primary key)
	 *			false (no where clause)
	 *			34 (denotes use 'WHERE primary_key = '34')
	 * @return mixed string/boolean
	 */
	function update(\$key = true)
	{
		\$update = 'UPDATE ';

		\$update .= \$this->set();

		// if \$key given, create WHERE clause
		\$where = \$this->where(\$key);

		return ( ( false !== \$where ) ? \$update . \$where : \$update );
	}



	/**
	 * Returns SQL WHERE clause contents as a string.
	 *
	 * @param mixed boolean\string\array \$key, 
	 *		ex: true (denotes use own primary key)
	 *			false (no where clause)
	 *			34 (denotes use 'WHERE primary_key = '34')
	 * 		array(10, 3, 4) (denotes values to use with primary key fields)
	 * @return mixed string/boolean
	 */
	function where(\$key = true)
	{
		\$where = '';

		// === and !== compares value and type equality
		// false denotes no where clause built
		if ( false !== \$key )
		{
			if ( in_array(false, \$this->_primary_key) )
			{
				// bail if no primary key defined
				return false;
			}

			\$new_key = ( !is_array(\$key) ) 
				? array(\$key) 
				: \$key;

			// begin where clause
			\$where = ' WHERE 1 = 1 ';

			\$primary_keys_size = sizeof(\$this->_primary_key);

			// build conditionals
			for ( \$i = 0; \$i < \$primary_keys_size; \$i++ )
			{
				\$where .= " AND {\$this->_primary_key[\$i]} = '";

				\$where .= ( true === \$key )
					? \$this->{\$this->_primary_key[\$i]}
					: \$new_key[\$i];
				
				\$where .= "'";
			}
		}
		
		return \$where;
	}



	/**
	 * Returns custom SQL WHERE clause contents as a string.
	 *
	 * @param mixed array \$key, 
	 *		ex: array('some_field' => 10, 'another_field' => 'pizza for all')
	 * @param boolean encapsulation off
	 * @return mixed string/boolean
	 */
	function where2(\$key = false, \$no_wrap = false)
	{
		\$where = '';

		// false denotes no where clause built
		if ( \$key && is_array(\$key) )
		{
			// begin where clause
			\$where = ' WHERE 1 = 1 ';

			// build conditionals
			foreach ( \$key AS \$field => \$value )
			{
				// try to ensure valid field
				if ( in_array(\$field, \$this->_fields) )
				{
					\$where .= " AND \$field = ";

					\$where .= ( is_numeric(\$value) || \$no_wrap )
						? \$value
						: "'" . \$value . "'";
				}
			}
		}
		
		return \$where;
	}

}

?>
EOL;


	// remove Windows EOL
	$class = str_replace("", '', $class);


	// write the class file
	echo 'Trying to write class file to: <b>'.$inclDir.'/'.$phpFile.'</b><br>'."\n";
	$filehandle = @fopen($inclDir.'/'.$phpFile,'w+');

	if ($filehandle) 
	{
		fwrite($filehandle,$class);
		flush($filehandle);
		fclose($filehandle);
		echo 'Table to class file written successfully<br>';
	}

	else 
	{
		echo 'Table to class file was NOT written due to inssufficient privileges.<br>';
		echo 'Please copy and paste class listed below to
		<i>'.$inclDir.'/'.$phpFile.'</i> file.';
	}

	echo '<br><hr>';
	echo '<h2>Table to class file follows</h2>'."\n";
	echo '<pre>';
	echo_html($class);
	echo '</pre><hr>'."\n";
}
	  }
	}

	// exit program
	page_footer();

?>
