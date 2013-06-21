<?php

	error_reporting(E_ALL);

	// database connection stuff
	$db = mysql_connect("localhost", "root", "");
	// $db = mysql_connect("localhost", "root", "");
	mysql_select_db("DNAccounting", $db) or die(mysql_errno() . ': ' 
		. mysql_error() . '<br />');
	
	$sql = "SELECT * FROM Reports ";

	// including the DB_Pager class
	// include("./class.DB_Pager.php");

	// initiate it!
	$nav = new DB_Pager();

	// set how many records to show at a time
	if ( isset($show) )
	{
		$nav->rows_per_page($show);
	}

	$nav->str_first = 'Alpha';
	$nav->str_last = 'Omega';

	// the third parameter of execute() is optional
	$result = $nav->execute($sql, $db);

	// build the returned array of navigation links
	$links = $nav->build_links();
	$links = array_values($links);

	// display links
	for ($y = 0; $y < count($links); $y++) {
		echo ' ' . $links[$y] . ' ';
	}
	echo '<hr />';

	// handle the returned result set
	while ( $data = mysql_fetch_object($result) )
	{
		echo "$data->defrep_id : $data->service_id : $data->quantity<br />\n";
	}
	
	echo "<hr>\n";
	// default link display
	// semi-, ala- Google
	echo $nav->display_links();

	echo "<hr>\n";
	// "piped" link display
	echo $nav->display_links(' | ');
	
	echo "<hr>\n";
	// "blocked" link display
	echo $nav->display_links(' [', ']');

?>
