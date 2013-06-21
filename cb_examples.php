<?php

	/**
	 * Cannon BOSE's centralization of sample library usages
	 *
	 * These examples come out of testing scripts, not all library methods are
	 * demonstrated here. Search source code for usages elsewhere.
	 *
	 * Copyright (C) 2002 Michael Cannon <michael@peimic.com>
	 * See full GNU Lesser General Public License in LICENSE.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: cb_examples.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */

	
	 
	if ( 0 )
	{
		// mk_sql_select()
		// sql_query()
		// sql_result2array()
		// debug_msg()

		$db = mysql_connect('localhost', 'root');
		mysql_select_db('test', $db) or die(mysql_errno() . ': ' 
			. mysql_error() . '<br />');

		$select = 'number';
		$from = 'numbers';
		$where = '
			1
			LIMIT 5
		';

		$sql = mk_sql_select($select, $from, $where);

		// std output
		$result = sql_query($sql);
		$array = sql_result2array($result);
		debug_msg( array('std output ()' => $array) );

		// forced indexed associative output
		$result = sql_query($sql);
		$array = sql_result2array($result, true);
		debug_msg( array('forced indexed associative output (true)' => $array) );

		// specific indexed output
		$result = sql_query($sql);
		$array = sql_result2array($result, 'number');
		debug_msg( array('single specific indexed output (number)' => $array) );
		
		$sqlAll = mk_sql_select('*', $from, $where);

		// std output
		$result = sql_query($sqlAll);
		$array = sql_result2array($result);
		debug_msg( array('* std output ()' => $array) );

		// forced indexed associative output
		$result = sql_query($sqlAll);
		$array = sql_result2array($result, true);
		debug_msg( array('* forced indexed associative output (true)' => $array) );

		// specific indexed output
		$result = sql_query($sqlAll);
		$array = sql_result2array($result, 'number');
		debug_msg( array('* single specific indexed output (number)' => $array) );

		// specific indexed output
		$result = sql_query($sqlAll);
		$array = sql_result2array($result, 'number, time');
		debug_msg( array('* multiple specific indexed output (number, time)' => $array) );

		// bad specific indexed output
		$result = sql_query($sqlAll);
		$array = sql_result2array($result, 'number, time, sam');
		debug_msg( array('* bad multiple specific indexed output (number, time, sam' => $array) );
	}


	if ( 0 )
	{
		// debug_msg()
		// statistics()
		// mode()
		// median()

		$a = array(0.5, 0.5, 0.5, 0.5, 1, 4, 2, 9, 2, 1, 4, 5, 9, 9, 2, 3, 21, 9, 2, 5, 3);
		$b = array('asdf', 'asdf', 'asdf', 'asdf', 'asdf', 0.5, 0.5, 0.5, 0.5, 1, 4, 2, 9, 2, 1, 4, 5, 9, 9, 2, 3, 21, 9, 2, 5, 3);

		debug_msg( statistics($a) );
		debug_msg( statistics($b) );
		debug_msg( mode($b), true );
		debug_msg( median($b), true );
	}

	if ( 0 )
	{
		// echo_ln()
		// is_alpha()
		// is_alnum()
		// is_digit()

		$a = array('123', 123, '1.23', 1.23, 'asdf', '9a9', 'a9', '9a', '9a s',
		true, false);
		
		foreach ($a AS $key => $value)
		{
			echo_ln($value);
			debug_msg( is_alpha($value), true);
			debug_msg( is_alnum($value), true);
			debug_msg( is_digit($value), true);
			debug_msg( is_true($value), true);
			debug_msg( is_false($value), true);
		}
	}

	if ( 0 )
	{
		// test zero_prepend()
		debug_msg( array( 
			-100, zero_prepend(-100), 
			-10, zero_prepend(-10), 
			-1, zero_prepend(-1), 
			0, zero_prepend(0), 
			1, zero_prepend(1), 
			10, zero_prepend(10),
			100, zero_prepend(100) 
		) , true);
	}

	if ( 0 )
	{
		// time
		debug_msg( usertime2mysql('19:30', true), true );
		debug_msg( usertime2mysql('17:16:15', true) );
	}

	if ( 0 )
	{
		// time
		debug_msg( usertime2mysql('9-1972 10:41', true), true );
		debug_msg( usertime2mysql('9/1972 10:41', true) );
		debug_msg( usertime2mysql('8-13-1966 9:45:55', true) );
	}

	if ( 0 )
	{
		// now()
		debug_msg( usertime2mysql('', true) );

		debug_msg( usertime2mysql('090119721041', true) );
		debug_msg( usertime2mysql('197209011041', true) );
		debug_msg( usertime2mysql('0813196612', true) );
		debug_msg( usertime2mysql('1966081312', true) );
		debug_msg( usertime2mysql('19990203040506', true) );
		debug_msg( usertime2mysql('02031999040506', true) );

		// mdy
		debug_msg( usertime2mysql('9/1/1972', true) );
		debug_msg( usertime2mysql('09011972', true) );
		
		// m4y
		debug_msg( usertime2mysql('9-1972', true) );
		debug_msg( usertime2mysql('091972', true) );
		
		// 4ym
		debug_msg( usertime2mysql('1972-9', true) );
		debug_msg( usertime2mysql('197209', true) );
		
		// myy
		debug_msg( usertime2mysql('9 72', true) );
		debug_msg( usertime2mysql('972', true) );
		debug_msg( usertime2mysql('0972', true) );
		
		// mmyy
		debug_msg( usertime2mysql('11 72', true) );
		debug_msg( usertime2mysql('1172', true) );
		
		// mmyy
		debug_msg( usertime2mysql('2032', true) );
	}

?>
