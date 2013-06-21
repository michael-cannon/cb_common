<?php

	/**
	 * Class DB_Pager
	 * 
	 * Class to build dynamic database paging links. 
	 *
	 * Based upon Joao Prado Maia (jpm@musicalidade.net) class navbar.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: class.DB_Pager.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */


	/**	 

		Below is short example of how to use this class:
		=============================================
	<?php 

		// database connection stuff
		$db = mysql_connect('localhost', 'root', '');
		mysql_select_db('DNAccounting', $db) or die(mysql_errno() . ': ' 
			. mysql_error() . '<br />');

		// create SQL statement WITHOUT LIMIT clause
		$sql = 'SELECT * FROM Reports ';

		// including the DB_Pager class
		include('./class.DB_Pager.php');
		
		// initiate it!
		$nav = new DB_Pager();

		// the third parameter of execute() is optional
		$result = $nav->execute($sql, $db, 'mysql');
		
		// default link display
		echo $nav->display_navbar();
		echo "<hr>\n";

		// display result set
		while ( $data = mysql_fetch_object($result) )
		{
			echo "$data->defrep_id : $data->service_id : $data->quantity<br />\n";
		}

	?>

	*/


	include_once( dirname(__FILE__) . '/cb_array.php');
	include_once( dirname(__FILE__) . '/cb_html.php');
	include_once( dirname(__FILE__) . '/cb_string.php');
	include_once( dirname(__FILE__) . '/cb_validation.php');



class DB_Pager 
{
	// values for the navigation link bar
	var $rows_per_page;
	var $num_links_shown;

	// navigation text	
	var $str_first;
	var $str_previous;
	var $str_next;
	var $str_last;
	var $show_line_options;

	// Variables used internally
	var $_rows_per_page_default;
	var $_num_links_shown_default;
	var $_file;
	var $_row;
	var $_last_page;
	var $_total_records;
	var $_query_string;
	var $_form_hidden;



	/**
	 * Class constructor
	 *
	 * @param boolean $use_text_links, ex: true (display 'First, Previous, ...'),
	 * 	false (display '<<, <, ...')
	 * @return void
	 */
	function DB_Pager($use_text_links = true)
	{
		$this->_rows_per_page_default = 15;
		$this->rows_per_page = $this->_rows_per_page_default;

		$this->_num_links_shown_default = 5;
		$this->num_links_shown = $this->_num_links_shown_default;

		// Default values for the navigation link bar
		if ( true === $use_text_links )
		{
			$this->str_first = '<b>First Page</b>';
			$this->str_previous = '<b>Previous Page</b>';
			$this->str_next = '<b>Next Page</b>';
			$this->str_last = '<b>Last Page</b>';
		}

		else
		{
			$this->str_first = '<b>&lt;&lt;</b>';
			$this->str_previous = '<b>&lt;</b>';
			$this->str_next = '<b>&gt;</b>';
			$this->str_last = '<b>&gt;&gt;</b>';
		}

		$this->show_line_options = array(
			5		=> 5,
			10		=> 10,
			15		=> 15,
			25		=> 25,
			50		=> 50,
			100	=> 100,
			1000	=> '1,000'
		);

		// Variables used internally
		$this->_file = get_SERVER('PHP_SELF', '');
		$this->_row = 0;
		$this->_total_records = 0;
		$this->_query_string = $this->build_url();
	}



	/**
	 * This function creates an array of all the links for the navigation bar.
	 * This is useful since it is completely independent from the layout or
	 * design of the page.  The function returns the array of navigation links to
	 * the caller php script, so it can build the layout with the navigation
	 * links content available.
	 *
	 * @param string $option (default to 'all'), ex:
	 * 	'all' - return every navigation link
	 * 	'pages' - return only the page numbering links
	 * 	'sides' - return only the 'Next' and / or 'Previous' links
	 * @param boolean $show_blank parameter (default to 'false'), ex:
	 * 	false - don't show the "Next" or "Previous" when it is not needed
	 * 	true - show the "Next" or "Previous" strings as plain text when it is
	 * 	not neededa
	 * @return array
	 */
	function build_links($option = 'all', $show_blank = false) 
	{
		$array = array();

		if ( 0 == $this->_total_records )
		{
			$array[] = 0;

			return $array;
		}

		$_file = $this->_file;

		// zero based counting adjust
		$number_of_pages = ceil($this->_total_records / $this->rows_per_page);
		$this->_last_page = $number_of_pages - 1;

		$indice = 0;

		if ( $option == 'all' || $option == 'sides' ) 
		{
			// beginning link
			// previous link
			if ( $this->_row != 0 )
			{
				$array[$indice] = mk_link("{$_file}?_row=0{$this->_query_string}", 
					$this->str_first, '', '', 'Go to first page');
				$indice++;
				
				$array[$indice] = mk_link("{$_file}?_row=" . ($this->_row - 1) 
					. $this->_query_string, $this->str_previous, '', '', 
					'Go to previous page');
				$indice++;
			}

			elseif ( $this->_row == 0 && $show_blank )
			{
				$array[$indice] = $this->str_first;
				$indice++;

				$array[$indice] = $this->str_previous;
				$indice++;
			}
		}

		// show all, some, or none of the page links	
		$this->num_links_shown = ( false !== $this->num_links_shown )
			? $this->num_links_shown
			: $this->_last_page;

		$row_diff = $this->_row - $this->num_links_shown;
		$from_link = ( $row_diff < 0 ) 
			? 0 
			: $row_diff;

		$row_sum = $this->_row + $this->num_links_shown;
		$to_link = ( $row_sum > $number_of_pages ) 
			? $number_of_pages
			: $row_sum;
		
		for ($current = $from_link; $current <= $to_link; $current++) 
		{
			// current link
			// other page links
			if ( ( $option == 'all' || $option == 'pages' ) 
				&& $current <= $this->_last_page )
			{
				$one_more = $current + 1;
				$array[$indice] = ( $this->_row != $current )
					? mk_link("{$_file}?_row={$current}{$this->_query_string}", 
						$one_more, '', '', 'Go to page ' . $one_more)
					: '<b>' . $one_more . '</b>';
			}

			// next link
			if ( ( $option == 'all' || $option == 'sides' ) 
				&& $current == $to_link )
			{
				if ( ( $this->_row != $to_link || $from_link == $to_link )
					&& $this->_row != $this->_last_page )
				{
					$array[$indice] = mk_link("{$_file}?_row=" 
						. ($this->_row + 1) . $this->_query_string, $this->str_next,
						'', '', 'Go to next page');
				}
				
				elseif ( ( $this->_row == $to_link || $this->_row == $this->_last_page )
			  		&& $show_blank )
				{
					$array[$indice] = $this->str_next;
				}
			}

			$indice++;
		}
				
		if ( $option == 'all' || $option == 'sides' ) 
		{
			// ending link
			if ($this->_row != $this->_last_page)
			{
				$array[$indice] = mk_link("{$_file}?_row=" 
					. $this->_last_page . $this->_query_string, $this->str_last,
					'', '', 'Go to last page');
			}
			
			elseif ( ($this->_row == $this->_last_page) && $show_blank )
			{
				$array[$indice] = $this->str_last;
			}
		}

		return $array;
	}

	
	
	/**
	 * This function creates a string that is going to be added to the url string
	 * for the navigation links.  This is specially important to have dynamic
	 * links, so if you want to add extra options to the queries, the class is
	 * going to add it to the navigation links dynamically.
	 *
	 * @return string
	 */
	function build_url()
	{
		$string = '';
		$this->form_hidden = '';

		$form_vars = ( 'GET' == $_SERVER['REQUEST_METHOD'] )
			? $_GET
			: $_POST;

		reset($form_vars);
		
		foreach ( $form_vars AS $key => $value )
		{
			if ( '_row' != $key )
			{
				$string .= '&amp;' . $key . '=' . $value;
			}

			else
			{
				$this->_row = $value;
			}

			if ( 'rows_per_page ' == $key )
			{
				$this->rows_per_page = $value;
			}

			$this->form_hidden .= 
				"<input type='hidden' name='$key' value='$value' />\n";
		}

		return $string;
	}



	/**
	 * Simple format and return as string of build_links().
	 *
	 * @param string $sep_left left side separator, ex: ' ', ' ['
	 * @param string $sep_right right side separator, ex: ' ', ' ]'
	 * @return string
	 */
	function display_links($sep_left = ' ', $sep_right = '')
	{
		$string = '';

		$links = $this->build_links();
		// reindex links
		$links = array_values($links);

		foreach ( $links AS $key => $value )
		{
			// middle stuff
			// last item
			if ( 0 != $key )
			{
				$string .= $sep_left . $value . $sep_right;
			}

			// first item
			else
			{
				$string .= ( '' == $sep_right )
					? $value
					: $sep_left . $value . $sep_right;
			}
		}

		return $string;
	}


	/**
	 * Returns string of display_links() and display_play() in div blocks.
	 *
	 * @return string
	 */
	function display_navbar()
	{
		$string = '';

		$links = $this->display_links();
		$page = $this->display_page();
		
		$string = "
			<div style='float: left; clear: both;' class='pagerNavbar'>
				<div class='pagerNavbarLeft' style='float: left;'>
					$links
				</div>
				<div class='pagerNavbarRight' style='float: right;'>
					$page
				</div>
			</div>
		";

		return $string;
	}
	
	

	/**
	 * Returns string of "Page X of XX".
	 *
	 * @return string
	 */
	function display_page()
	{
		$string = '';
		
		if ( 0 != $this->_total_records )
		{
			$string = 'Page ' . ($this->_row + 1) . ' of ' 
				. ($this->_last_page + 1);
		}
		
		else
		{
			$string = 'Page 0 of 0';
		}


		return $string;
	}
	
	
	
	/**
	 * Simple <form> string for selecting lines.
	 *
	 * @return string
	 */
	function display_select_lines()
	{
		$string = '';
		
		$string = "
			<form method='get' action='{$this->_file}?{$this->_query_string}'>
				{$this->form_hidden}
				Show
		";

		$string .= mk_select_list('rows_per_page', $this->show_line_options,
			$this->rows_per_page);

		$string .= "
				items
				<input type='submit' value='Update' />
			</form>
		";

		return $string;
	}
	
	
	
	/**
	 * The next function runs the needed queries.  It needs to run the first time
	 * to get the total number of rows returned, and the second one to get the
	 * limited number of rows.
	 *
	 * Oracle Handling Reference
	 * http://www.php.net/manual/en/ref.oracle.php
	 *
	 * @param string $sql parameter actual SQL query to be performed
	 * @param resource $db database link identifier
	 * @param string $type database type parameter, ex:
	 * 	'mysql' - uses mysql PHP functions
	 * 	'pgsql' - uses pgsql PHP functions
	 * 	'oracle' - uses oracle PHP functions
	 * @return resource
	 */
	function execute($sql, $db, $type = 'mysql') 
	{
		$start = $this->_row * $this->rows_per_page;
		$result = false;

		if ( $type == 'mysql' ) 
		{
			$result = mysql_query($sql, $db);

			if ( $result )
			{
				$this->_total_records = mysql_num_rows($result);
				$sql .= " LIMIT $start, $this->rows_per_page";
				$result = mysql_query($sql, $db);
			}
		} 
		
		elseif ( $type == 'pgsql' ) 
		{
			$result = pg_Exec($db, $sql);
			
			if ( $result )
			{
				$this->_total_records = pg_NumRows($result);
				$sql .= " LIMIT $this->rows_per_page, $start";
				$result = pg_Exec($db, $sql);
			}
		}
		
		elseif ( $type == 'oracle' ) 
		{
			@ora_parse($db, $sql);
			$result = ora_exec($db);
			
			if ( $result )
			{
				$this->_total_records = ora_numrows($result);
				$sql .= " LIMIT $this->rows_per_page, $start";
				
				@ora_parse($db, $sql);
				$result = ora_exec($db);
			}
		}

		return $result;
	}



	/**
	 * Helper function to set var $num_links_shown. Checks for numbers less than
	 * zero and defaults var $num_links_shown if so.
	 *
	 * @param integer $links_to_show, ex: 10, 50
	 * @return void
	 */
	function num_links_shown($links_to_show)
	{
		$this->num_links_shown = (0 < $links_to_show || false == $links_to_show)
			? $links_to_show
			: $this->_num_links_shown_default;
	}



	/**
	 * Helper function to set var $row_per_page. Checks for numbers less than
	 * zero and defaults var $row_per_page if so.
	 *
	 * @param integer $rows_to_show, ex: 10, 50
	 * @return void
	 */
	function rows_per_page($rows_to_show)
	{
		$this->rows_per_page = ( 0 < $rows_to_show )
			? $rows_to_show
			: $this->_rows_per_page_default;
	}



	/**
	 * Returns integer _total_records.
	 *
	 * @return integer
	 */
	function total_records()
	{
		return $this->_total_records;
	}

}

?>
