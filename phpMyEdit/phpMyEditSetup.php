<?php

/*
 * phpMyEdit - MySQL table editor
 *
 * phpMyEditSetup.php - interactive table configuration utility (setup)
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

/* $Platon: phpMyEdit/phpMyEditSetup.php,v 1.15 2002/11/14 23:30:50 nepto Exp $ */

// setup.php version 3.5 27-Apr-01
// Heavily updated by Ondrej Jombik in 9-Aug-2002.

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>phpMyEdit Setup</title>
</head>
<style type="text/css">
	body  { font-family: "Verdana", "Arial", "Sans-Serif"; text-align: left }
	h1    { color: #004d9c; font-size: 12pt; font-weight: bold }
	h2    { color: #004d9c; font-size: 10pt; font-weight: bold }
	h3    { color: #004d9c; font-size: 10pt; }
	p     { color: #004d9c; font-size: 8pt; }
	table { border: 2px solid #004d9c; font-size: 8pt; text-align: center; border-collapse: "collapse"; }
	td    { border: 2px solid; padding: 2px; color: #004d9c; font-size: 8pt; }
</style>
<body bgcolor=white>
<?php

$pageTitle    = @$HTTP_POST_VARS['pageTitle'];
$baseFilename = @$HTTP_POST_VARS['baseFilename'];
$submit       = @$HTTP_POST_VARS['submit'];
$hn           = @$HTTP_POST_VARS['hn'];
$un           = @$HTTP_POST_VARS['un'];
$pw           = @$HTTP_POST_VARS['pw'];
$db           = @$HTTP_POST_VARS['db'];
$tb           = @$HTTP_POST_VARS['tb'];
$id           = @$HTTP_POST_VARS['id'];

$phpExtension = '.phtml';
$phpExtension = '.php';
// directory to put the .php file into
$destDir = '.';
// directory to put the .inc file into
$inclDir = '.';
$headerFile = 'SiteHeader.inc';
$footerFile = 'SiteFooter.inc';
if (isset($baseFilename) && $baseFilename != '') {
	$phpFile = $baseFilename.$phpExtension;
	//$contentFile = $baseFilename.'Content.inc';
	$contentFile = $baseFilename.'.php';
} elseif (isset($tb)) {
	$phpFile = $tb.$phpExtension;
	//$contentFile = $tb.'Content.inc';
	$contentFile = $tb.'.php';
} else {
	$phpFile = 'index'.$phpExtension;
	//$contentFile = 'Content.inc';
	$contentFile = 'phpMyEdit-content.php';
}

$buffer = '';

function echo_html($x) {
	echo htmlentities($x)."\n";
}

function echo_buffer($x) {
	global $buffer;
	$buffer .= $x."\n";
}

$self   = basename($HTTP_SERVER_VARS['PHP_SELF']);
$dbl    = @mysql_pconnect($hn,$un,$pw);

if ((!$dbl) or empty($submit)) {
	echo '  <h1>Please log in to your MySQL database</h1>';
	if (!empty($submit)) {echo '  <h2>Sorry - login failed - please try again</h2>'."\n";}
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
} else {
  if (!isset($db)) {
    $dbs = mysql_list_dbs($dbl);
    $num_dbs = mysql_numrows($dbs);
    echo '  <h1>Please choose a database</h1>
  <form action="'.$self.'" method="POST">
    <input type="hidden" name="hn" value="'.$hn.'">
    <input type="hidden" name="un" value="'.$un.'">
    <input type="hidden" name="pw" value="'.$pw.'">
    <table border="1" cellpadding="1" cellspacing="1" summary="Choose Database">'."\n";
	for ($i=0; $i<$num_dbs; $i++) {
		$db = mysql_dbname($dbs, $i);
		if ($i==0) {
			echo '      <tr><td><input checked type="radio" name="db" value="'.$db.'"></td><td>'.$db.'</td></tr>'."\n";
		} else {
			echo '      <tr><td><input type="radio" name="db" value="'.$db.'"></td><td>'.$db.'</td></tr>'."\n";
		}
	}
   	  echo '    </table>
    <br>
    <input type="submit" name="submit" value="Submit">
    <input type="submit" name="cancel" value="Cancel">
  </form>'."\n";
  } else {
    if (!isset($tb)) {
      echo '  <h1>Please choose a table from database: '.$db.'</h1>
  <form action="'.$self.'" method="POST">
    <input type="hidden" name="hn" value="'.$hn.'">
    <input type="hidden" name="un" value="'.$un.'">
    <input type="hidden" name="pw" value="'.$pw.'">
    <input type="hidden" name="db" value="'.$db.'">
    <table border="1" cellpadding="1" cellspacing="1" summary="Choose Table">'."\n";
      $tbs = mysql_list_tables($db,$dbl);
      $num_tbs = @mysql_num_rows($tbs);
      for ($j=0; $j<$num_tbs; $j++) {
        $tb = mysql_tablename($tbs, $j);
  			if ($j == 0) {
				echo '      <tr><td><input checked type="radio" name="tb" value="'.$tb.'"></td><td>'.$tb.'</td></tr>'."\n";
			} else {
				echo '      <tr><td><input type="radio" name="tb" value="'.$tb.'"></td><td>'.$tb.'</td></tr>'."\n";
			}
      }
    	echo '    </table>
    <br>
    <input type="submit" name="submit" value="Submit">
    <input type="submit" name="cancel" value="Cancel">
  </form>'."\n";
    } else  {
      if (!isset($id)) {
        echo '  <h1>Please choose an identifier from table: '.$tb.'</h1>
  <h2>This field will be used in Changes or Deletes.</h2>
  <p>
	  It must be numeric and must uniquely identify a record.<br>
    If you haven\'t got a suitable field select <i>None</i>.
	</p>
  <form action="'.$self.'" method="POST">
    <input type="hidden" name="hn" value="'.$hn.'">
    <input type="hidden" name="un" value="'.$un.'">
    <input type="hidden" name="pw" value="'.$pw.'">
    <input type="hidden" name="db" value="'.$db.'">
    <input type="hidden" name="tb" value="'.$tb.'">
    <table border="1" cellpadding="1" cellspacing="1" summary="Choose Key">
      <tr><td><input type="radio" name="id" value="">
  <td><i>None</i></td><td><i>No id field required</i></td></tr>'."\n";
        echo "describe $tb<br>\n";
		mysql_select_db($db);
        $tb_desc = mysql_query("describe $tb");
        $fds = mysql_list_fields($db,$tb,$dbl);
        $j   = 0;
        while (1) {
          $fd = @mysql_field_name($fds, $j);
          if ($fd == "") { break; }
          $ff = mysql_field_flags($fds, $j);
		  echo '      <tr><td><input';
		  if (stristr($ff, 'primary_key')) {
			  echo ' checked';
		  }
          echo ' type="radio" name="id" value="'.$fd.'"></td><td>'.$fd.'</td><td>'.$ff.'</td>';
		  $r = mysql_fetch_array($tb_desc,$j);
/*
echo "<tr><td><pre>";
print_r($r);
echo "</pre></td></tr>\n";
echo "</tr>\n";
*/
          ++$j;
        }
    	  echo '    </table>
    <br>
	Page Title: <input type="text" name=pageTitle value ="'.$tb.'">
    <br>
	Base Filename: <input type="text" name=baseFilename value ="'.$tb.'">
    <br>
    <input type="submit" name="submit" value="Submit">
    <input type="submit" name="cancel" value="Cancel">
  </form>'."\n";

      } else  {
  			echo '<h1>Here is your phpMyEdit calling program</h1>'."\n";
  			echo '<h2>You may now copy and paste it into your PHP editor</h2>'."\n";
        //echo '<hr><pre>'."\n";
        echo_buffer('<div class="main">
  <h3>'.$pageTitle.'</h3>
<?php
//  MySQL host name, user name, password, database, and table to edit'.
/* NOTE: coder is strongly urged to remove the hn, un, pw lines from 
   here and set values in PME_site_defaults.inc
   require "PME_site_defaults.inc"; */
'
$opts["hn"] = "'.$hn.'";
$opts["un"] = "'.$un.'";
$opts["pw"] = "'.$pw.'";
$opts["db"] = "'.$db.'";
$opts["tb"] = "'.$tb.'";

// Name of field which is the unique key
$opts["key"] = "'.$id.'";

// Type of key field (int/real/string/date etc)');
if ($id=="") {
	echo_buffer('$opts["key_type"]="";');
} else {
	$fds = mysql_list_fields($db,$tb,$dbl);
	$j=0;
	while (1) {
		$fd = @mysql_field_name($fds, $j);
		if ($fd == '') { break; }
		if ($fd == $id) {
			echo_buffer('$opts["key_type"] = "'.@mysql_field_type($fds, $j).'";');
			break;
		}
		++$j;
	}
}
echo_buffer("// Initial field to sort on");
echo_buffer('$opts["sort_field"] = "'.$id.'";');

$image_dir = $_SERVER['PHP_SELF'];
$image_dir = explode('/', $image_dir);
@array_shift($image_dir);
@array_pop($image_dir);
$image_dir = implode('/', $image_dir);

echo_buffer("
// Number of records to display on the screen
// Note value of -1 lists all records in a table.
\$opts['inc'] = 15;

// Options you wish to give the users
// A - add,  C - change, P - copy, V - view, D - delete,
// F - filter, I - initial sort suppressed
\$opts['options'] = 'ACPVDF';

// Number of lines to display on multiple selection filters
\$opts['multiple'] = '4';

// Number of lines to display on multiple selection filters
//\$opts['default_sort_columns'] = array('pushId','due','priority','task');
\$opts['default_sort_columns'] = array();

// Navigation style: B - buttons (default), T - text links, G - graphic links
// Buttons position: U - up, D - down (default)
\$opts['navigation'] = 'DB';

// Display special page elements
\$opts['display'] = array(
	'query' => false,
	'sort'  => false,
	'time'  => false
	);

// URLs - You can redefine URL for images to store images on different location
// that phpMyEdit class file.
\$opts['url'] = array(
		'images' => '/$image_dir/images/'
		);

// E-mail notification, replace boolean false with e-mail address, ex:
// admin@example.com
\$opt['notify'] = array(
	'delete' => false,
	'insert' => false,
	'update' => false
		);

/*
	Field definitions. Fields will be displayed left to right
	on the screen in the order in which they appear in this list.
	['name'] is the title used for column headings, etc.;
	['sort'] = true means the users may sort the display on this column;
	['type'] is generated by SETUP.php and indicates the mysql field type
		best not edited
	['maxlen'] maximum length to display add/edit/search input boxes
	['trimlen'] maximum length of string content to display in row listing
		if strlen(value) > trimlen, display substr($value,0,trimlen-3).'...'
	['strip_tags'] true or false. whether to strip tags from content
	['width'] is an optional display width specification for the column,
		e.g.  ['width'] = '100px';
		or \$fdd['colname']['width'] = (\$fdd['colname']['trimlen'] * 8).'px';
	['nowrap'] true or false.  whether this field should get a NOWRAP
	['mask'] a string that is used by sprintf() to format field output
	['datemask'] a string that is used by date() to format date fields
		see PHP's date() for valid formatting characters
	['daterange'] a  of numbers
		['daterange']['start'] = 1996;
		['daterange']['end']   = \$fdd['colname']['daterange']['start']+20;
	['URL'] is used to make a field 'clickable' in the display, e.g.:
		['URL'] = 'mailto:\$value' or ['URL'] = 'http://\$value';
		['URL'] = '\$page?stuff';
		Note that the following are available as variables:
			\$key    key field for record
			\$name   name of the field
			\$value  value of the field
			\$page   this HTML page
			\$urlstr all page variables
			\$row    mysql_fetch_assoc() for this row
		['URLtarget']  HTML target link specification (for example: _blank)
		['URLdisp']    what to display as hypertext link (by default \$value)
		['URLprefix']  old 3.5 ['URL'] behaviour, will prepend string before
		['URLpostfix'] similary to ['URLprefix'] will append string after
	['required'] = true will generate javascript to prevent null entries by users
	['options'] is an optional parameter to control whether a field is displayed
		L - list, F - filter, A - add, C - change, P - copy, D - delete, V - view
		Another flags can be:
		R - indicates that a field is read only
		W - indicates that a field is a password field
		H - indicates that a field is to be hidden and marked as hidden
	['textarea']['rows'] and/or ['textarea']['cols'] specifies a
	textarea is to be used to give multi-line input,
		e.g. ['textarea']['rows'] = 5; ['textarea']['cols'] = 10
	['values'] restricts user input to the specified constants,
		e.g. ['values'] = array('A','B','C')
		or   ['values'] = range(1,99);
	['values2'] gives alternative description to ['values'] values
		e.g.	['values'] = array('A','B','C')
				['values2'] = array('Apple', 'Bradley' , 'Chuck')
	['values']['table'] and ['values']['column'] restricts user
		input to the values found in the specified column of another table.
 		The optional ['values']['description'] field allows the value(s) displayed
 		to the user to be different to those in the ['values']['column'] field.
 		This is useful for giving more meaning to column values.  There are two main
 		options when using the ['values']['description'] field. These are whether you
 		want to use a single, or multiple field in your description.  To apply single,
 		you need to use:
 			['values']['description'] = 'desc_column'
 		This may be used, with no other options, and will simply display the description
 		for the corresponding value.
 		For Multiple, use:
 			['values']['description']['columns']['1'] = 'desc_column_1'
 			['values']['description']['divs']['1']    = ' '
 			['values']['description']['columns']['2'] = 'desc_column_2'
 		The 'div' component is what will be used as a divider between the columns
 		in the display.  You don't need to define the last 'div' field if it isn't
 		required.  So, for example...if you have a series of people in a
 		table, with a separate column for id, first name, and last name, you could
 		use:
			['values']['db']='mydb' *optional if table is in another database*
			['values']['table']='mytable'
 			['values']['column']='id'
 			['values']['description']['columns']['1']='name_last'
 			['values']['description']['divs']['1']   =', '
 			['values']['description']['columns']['2']='name_first'
			['values']['filters']='idcolumn in (1,2,3)' *optional WHERE clause*
 			['values']['orderby']='last_name' *optional ORDER BY clause*
		if a column is either SET or ENUM type, then the values are put in 
		by PME Setup.
	['select'] = 'T/D/M' (text, drop-down, or multiple selection for filters)
		if a column is a SET type, then this is automatically 'M'
	Calculated Expressions
		example:
			\$fdd['other']['name']='Col Header';
			\$fdd['other']['expression']='onHand-ordered';
			\$fdd['other']['sort']='T:!';
			\$fdd['other']['select']='T';
			\$fdd['other']['type']='int';
		'other' can be anything, but needs to be unique within the list of \$fdd keys
		'expression' can be any valid MySQL expression
		'type' should reflect the results of the expression, e.g. 'int' or 'string'
		all other options work as with other, normal columns
		this kind of column is always read-only
	Special note:
		Note, that you can create special options for particular \$fdd array
		elements, that depends on performed action. In example if you can
		change name of column in list and filter action, you can define
		\$fdd['column']['name|FL']. Others options works of course also well.
*/");
		mysql_select_db($db);
        $tb_desc = mysql_query("describe $tb");
        $fds = mysql_list_fields($db, $tb, $dbl);
        $num_fds = mysql_num_fields($fds);
		for ($k = 0; $k < $num_fds; $k++) {
			$fd = mysql_field_name($fds,$k);
			$fm = mysql_fetch_field($fds,$k);
			$fn = strtr($fd, "_-.", "   ");
			$fn = preg_replace('/(^| +)id( +|$)/', '\\1ID\\2', $fn); // uppercase IDs
			$fn = ucfirst($fn);
			$row = mysql_fetch_array($tb_desc);
			echo_buffer('$opts[\'fdd\'][\''.$fd.'\'] = array(');
					echo_buffer("	'name'=>'".str_replace('\'','\\\'',$fn)."',");
			echo_buffer("	'help'=>'',");
			echo_buffer("	'strip_tags'=>false,");
					if (substr($row[1],0,3) == 'set') {
					echo_buffer("	'select'=>'M',");
					echo_buffer("	'type'=>'set',");
					} else {
					echo_buffer("	'select'=>'T',");
					echo_buffer("	'type'=>'".mysql_field_type($fds,$k)."',");
					}
					echo_buffer("	'maxlen'=>".mysql_field_len($fds,$k).',');
					echo_buffer("	'nowrap'=>false,");
					if (stristr(mysql_field_flags($fds,$k),'not_null')) {
					echo_buffer("	'required'=>true,");
					}
			else
			{
				echo_buffer("	'required'=>false,");
			}
					// blobs -> textarea
					if (mysql_field_type($fds,$k)=='blob')
					{
					echo_buffer("	'textarea'=>array(");
					echo_buffer("		'rows'=>5,");
					echo_buffer("		'cols'=>50,");
					echo_buffer("		'wrap'=>'virtual'");
					echo_buffer("	),");
					}
					// timestamps are read-only
					if (mysql_field_type($fds,$k)=='timestamp')
					{
						echo_buffer("	'options'=>'R',");
					}
			else
			{
				echo_buffer("	'options'=>null,");
			}

					// SETs and ENUMs get special treatment
					if (substr($row[1],0,3) == 'set') {
						echo_buffer("	'values'=>array".substr($row[1],3).',');
				echo_buffer("	'values2'=>null,");
					}
			elseif (substr($row[1],0,4) == 'enum') {
						echo_buffer("	'values'=>array".substr($row[1],4).',');
				echo_buffer("	'values2'=>null,");
			}
			else
			{
				echo_buffer("	'values'=>null,");
				echo_buffer("	'values2'=>null,");
					}

					// automatic support for Default values
					if ($row[4] != '' && $row[4] != 'NULL') {
						echo_buffer("	'default'=>'".$row[4]."',");
					}
			else {
				echo_buffer("	'default'=>'',");
			}
					echo_buffer("	'sort'=>true");
					echo_buffer(');');
					echo_buffer('');
		}

	echo_buffer("
/* Table-level filter capability (if set, is included in the WHERE clause of any
   generated SELECT statement; this gives you ability to wirk only with subset of
   data from table)

   Some valid examples:
     \$opts['filters'] = \"column1 like '%11%' AND column2<17\";
     \$opts['filters'] = \"section_id = 9\";
     \$opts['filters'] = \"Table0.sessions_count > 200\";
 */

/* CGI variables. You can optionally append or overwrite particular variables
   returned from CGI environment (GET/POST HTTP protocol data). Use these two
   arrays for this purpose, where array key mean CGI variable name and value
   means CGI variable value.

   Examples:
     // This will cause descending sorting according first field if other
	 // type of sort was not specified/selected by user.
     \$opts['cgi']['append']    = array('sfn' => '-0');
	 // This will cause the same sort allways in all cases.
     \$opts['cgi']['overwrite'] = array('sfn' => '-0');
 */

/*
Triggers are files that are included (via require) that perform
actions (before or after) X (inserts, updates, or deletes).

'before' triggers are usually used to verify conditions prior to
executing the main operation.

'after' triggers are usually used to perform follow-on operations
after the main operation.  For example, to update secondary tables
to enforce referential integrity or to update aggregate tables.

The operation sequence is this:  before, main, after.  If any
operation fails, not only should the next operation(s) not be
executed, but the previous ones are 'rolled back' as if they
never happened.  If a database is not able to do this, it is
not 'transaction-safe'.

Triggers are risky in basic MySQL as there is no native transaction
support--it is not transaction-safe by default.  There are
transaction-safe table types in MySQL that can be conditionally built
(see MySQL-Max), but phpMyEdit is currently not set up to support real
transactions.  What that means is that if an operation fails, the
database may be left in an intermediate and invalid state. 

The programmer must understand and accept these risks prior to using
the phpMyEdit triggers mechanism.

If the triggers are used, they execute within the namespace or scope
of the phpMyEdit class.

They _must_ return true or false to indicate success or failure.
*/

/*
\$opts['triggers']['insert']['before']='".substr($contentFile,0,strrpos($contentFile,'.inc')).".TIB.inc';
\$opts['triggers']['insert']['after'] ='".substr($contentFile,0,strrpos($contentFile,'.inc')).".TIA.inc';
\$opts['triggers']['update']['before']='".substr($contentFile,0,strrpos($contentFile,'.inc')).".TUB.inc';
\$opts['triggers']['update']['after'] ='".substr($contentFile,0,strrpos($contentFile,'.inc')).".TUA.inc';
\$opts['triggers']['delete']['before']='".substr($contentFile,0,strrpos($contentFile,'.inc')).".TDB.inc';
\$opts['triggers']['delete']['after'] ='".substr($contentFile,0,strrpos($contentFile,'.inc')).".TDA.inc';
*/

/* Logtable schema

CREATE TABLE changelog (
  updated timestamp(14) NOT NULL,
  user varchar(50) default NULL,
  host varchar(255) NOT NULL default '',
  operation varchar(50) default NULL,
  tab varchar(50) default NULL,
  rowkey varchar(255) default NULL,
  col varchar(255) default NULL,
  oldval blob,
  newval blob
);

\$opts['logtable']= 'changelog';
*/
");

        echo_buffer("
/* Get the user's default language and use it if possible or you can specify
   language particular one you want to use. Available languages are:
   DE EN-US EN FR IT NL PG SK SP */
\$opts['language'] = \$_SERVER['HTTP_ACCEPT_LANGUAGE'];

/* Code execution. Since version 4.0 phpMyEdit will automatically starts its
   execution. You can turn this feature off by setting \$opts['execute'] to 0.
   Default value is 1. */

//  and now the all-important call to phpMyEdit
//  warning - beware of case-sensitive operating systems!
require_once 'phpMyEdit.class.php';

\$MyForm = new phpMyEdit(\$opts);

?>

  </div>");

/*
 * Writting of two files makes only much more confusion on whole stuff.
 * Only real phpMyEdit content file is now written. Everybody can include
 * it from its own files to get the job (headers, footes, etc.).
 *
 * Nepto [29/7/2002]
 */

if (0) {
	// write the php file
	$phpFileContents = '<?php
	require \'PME_site_defaults.inc\';
	$_title = "'.$pageTitle.'";
	$_content = "'.$contentFile.'";
	include "'.$headerFile.'";
	include $_content;
	include "'.$footerFile.'";
	?>';

	echo 'Writing PHP file to: '.$destDir.'/'.$phpFile."<br>\n";
	$filehandle = @fopen($destDir.'/'.$phpFile,'w+');

	if ($filehandle) {
		fwrite($filehandle,$phpFileContents);
		flush($filehandle);
		fclose($filehandle);
		echo 'file written successfully<br>';
	}
	echo '<hr><b>PHP file:</b><pre>';
	echo_html($phpFileContents);
	echo "</pre><hr>\n";
} // if (0)

// write the content include file
echo 'Trying to write content file to: <b>'.$inclDir.'/'.$contentFile.'</b><br>'."\n";

// remove Windows line breaks
$buffer = str_replace("", '', $buffer);

$filehandle = @fopen($inclDir.'/'.$contentFile,'w+');
if ($filehandle) {
	fwrite($filehandle,$buffer);
	flush($filehandle);
	fclose($filehandle);
	echo 'phpMyEdit content file written successfully<br>';
} else {
	echo 'phpMyEdit content file was NOT written due to inssufficient privileges.<br>';
	echo 'Please copy and paste content listed below to <i>'.$inclDir.'/'.$contentFile.'</i> file.';
}
echo '<br><hr>';
echo '<h2>phpMyEdit content file follows</h2>'."\n";
echo '<pre>';
echo_html($buffer);
echo '</pre><hr>'."\n";

      }
    }
  }
}
?>
</body>
</html>
