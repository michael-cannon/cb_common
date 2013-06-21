<?php

/*
 * phpMyEdit - MySQL table editor
 *
 * extensions/phpMyEdit-report.class.php - phpMyEdit report extension
 * ____________________________________________________________
 *
 * Developed by Ondrej Jombik <nepto@php.net>
 * Copyright (c) 2002 Platon SDG, http://www.platon.sk/
 * All rights reserved.
 *
 * See README file for more information about this software.
 * See COPYING file for license information.
 *
 * Download the latest version from
 * http://www.platon.sk/projects/phpMyEdit/
 */

/* $Platon: phpMyEdit/extensions/phpMyEdit-report.class.php,v 1.1 2002/11/02 19:17:28 nepto Exp $ */

/* Extension TODO:

   - added labels to language files
   - use button for "Select fields"
   - allow user to enable/disable particular field in reporting (maybe 'X' flag
     for indicating that field is forbidden is good idea)
   - make extension work with register globals turned off
   - use table name in cookie names (this will save preferences for various
     report pages)
 */

require_once dirname(__FILE__).'/../phpMyEdit.class.php';

class phpMyEdit_report extends phpMyEdit
{
	// Extension options array
	// var $ext;

	function phpMyEdit_report($opts) /* {{{ */
	{
		$opts['options'] = 'L';

		$execute = 1;
		isset($opts['execute']) && $execute = $opts['execute'];
		$opts['execute'] = 0;
		parent::phpMyEdit($opts);

		// $this->ext = $opts['ext'];

		$execute && $this->execute();
	} /* }}} */

	function make_language_labels($language) /* {{{ */
	{
		$ret = parent::make_language_labels($language);
		strlen($ret['Make report'])        <= 0 && $ret['Make report']        = 'Make report';
		strlen($ret['Select fields'])      <= 0 && $ret['Select fields']      = 'Select fields';
		strlen($ret['Records per screen']) <= 0 && $ret['Records per screen'] = 'Records per screen';
		return $ret;
	} /* }}} */

	function display_report_selection_buttons() /* {{{ */
	{
		// TODO: classify
		echo '<table border=0 cellpadding=0 cellspacing=0 width="100%" style="border:0;padding:0;">';
		echo '<tr><td align=left style="text-align:left;border:0;padding:0;" nowrap>' . "\n";
		echo '<input type=submit name=prepare_filter value="'.$this->labels['Make report'].'">'."\n";
		echo '</td></tr></table>'."\n";
	} /* }}} */

	function execute() /* {{{ */
	{
		global $HTTP_GET_VARS;
		global $HTTP_POST_VARS;
		global $HTTP_SERVER_VARS;

		$table_cols     = array();
		$all_table_cols = array();

		if ($this->connect() == false) {
			return false;
		}

		$query_parts = array(
				'type'   => 'select',
				'select' => '*',
				'from'   => $this->tb,
				'limit'  => '1');
		$result = $this->myquery($this->query_make($query_parts), __LINE__);
		$all_table_cols = array_keys(@mysql_fetch_array($result, MYSQL_ASSOC));

		if (count($all_table_cols) <= 0) {
			$this->error('database fetch error');
			return false;
		}

		foreach (array_keys($this->fdd) as $field_name) {
			if (preg_match('/^\d*$/', $field_name))
				continue;
			if (($idx = array_search($field_name, $all_table_cols)) !== false)
				$table_cols[$field_name] = mysql_field_len($result, $idx);
		}
		@mysql_free_result($result);

		if (0) { // DEBUG
			echo "<pre>";
			var_dump($table_cols);
			echo "</pre>";
			echo "<pre>";
			var_dump($all_table_cols);
			echo "</pre>";
		}

		unset($all_table_cols);
		$force_fields_select = false;
		$filter              = $this->get_cgi_var('filter');
		$prepare_filter      = $this->get_cgi_var('prepare_filter');

		if (isset($filter) || isset($prepare_filter)) {
			$force_fields_select = true;
			foreach (array_merge(array('@inc'), array_keys($table_cols)) as $col) {
				$var = ($col[0] == '@' ? substr($col, 1) : "have_$col");
				global $$var;

				if (isset($HTTP_POST_VARS[$var]) || isset($HTTP_GET_VARS[$var])) {
					$$var = $HTTP_POST_VARS[$var];

					if (isset($HTTP_GET_VARS[$var]))
						$$var = $HTTP_GET_VARS[$var];

					session_register($var);

					if ($var != 'inc' && ! empty($$var))
						$force_fields_select = false;

				} else {
					if (session_is_registered($var))
						session_unregister($var);
					unset($$var);
				}
			}

		} else {
			session_start();
		}

		/*
		 * Hackity hack with unregistering (unchecking) fields
		 */
		if ($prepare_filter) {
			$redirect_url = 'http://'.$HTTP_SERVER_VARS['HTTP_HOST'].$HTTP_SERVER_VARS['SCRIPT_NAME'];
			$delim = '?';
			foreach ($HTTP_POST_VARS + $HTTP_GET_VARS as $cgi_var_name => $cgi_var_value) {
				if ($cgi_var_name == 'prepare_filter'){ 
					$cgi_var_name = 'filter';
				}
				$redirect_url .= $delim;
				$redirect_url .= rawurlencode($cgi_var_name).'='.rawurlencode($cgi_var_value);
				if ($delim == '?')
					$delim = '&';
			}
			header('Location: '.$redirect_url);
			exit;
		}

		$inc = $this->get_cgi_var('inc');
		if (empty($inc)) {
			global $HTTP_SESSION_VARS;
			$inc = $HTTP_SESSION_VARS['inc'];
		} else {
			session_register('inc');
		}

		$this->inc     = (intval($inc) <= 0 ? 0 : intval($inc));
		$fields_select = $this->get_cgi_var('fields_select');
		if (isset($fields_select) || $force_fields_select) {
			$this->execute_report_screen($table_cols);
		} else {

			$none_displayed = true;
			$i              = -1;
			foreach (array_keys($this->fdd) as $key) {
				$i++;
				if (preg_match('/^\d*$/', $key))
					continue;
				$have_var = "have_$key";
				global $$have_var;
				$have = isset($$have_var) ? $$have_var : $this->get_cgi_var($have_var);
				$this->fdd[$i]['options'] = $this->fdd[$key]['options'] = $have ? 'LV' : '';
				$this->displayed[$i] = $have ? true : false;
				$have && $none_displayed = false;
			}

			if ($none_displayed) {
				$this->execute_report_screen($table_cols);
			} else {

				/*
				 * Select fields link creation
				 */
				$this->message .= '<a href="'.htmlspecialchars($HTTP_SERVER_VARS['PHP_SELF']).'?fields_select=1';
				for ($i = 0; $i < count($table_cols); $i++) {
					$var    = "qf$i";
					$varval = $this->get_cgi_var($var);
					if (! empty($varval)) {
						$this->message .= htmlspecialchars(
								'&'.rawurlencode($var)
								.'='.rawurlencode($varval));
					}
				}
				$this->message .= '">'.$this->labels['Select fields'].'</a>';

				parent::execute();
			}
		}
	} /* }}} */

	function execute_report_screen($table_cols) /* {{{ */
	{
		global $HTTP_SERVER_VARS;
		echo '<form method=post action="'.htmlspecialchars($HTTP_SERVER_VARS['PHP_SELF']).'">'."\n";
		if ($this->nav_up()) {
			$this->display_report_selection_buttons();
			echo "<hr>\n";
		}
		echo '<table border=0 cellpadding=0 cellspacing=0 width="100%">';

		$i = 0;
		foreach ($table_cols as $key => $val) {
			echo '<tr>';
			echo '<td nowrap>'.$this->fdd[$key]['name'].'</td>';
			$var = "have_$key";
			global $$var;
			$checked = $$var ? ' checked' : ''; 
			echo '<td width=1 align=center><input type=checkbox name="'.$var.'"'.$checked.'></td>';

			global ${"qf$i"};
			echo '<td align=left>';
			echo '<input type=text name="qf'.$i.'" value="'.${"qf$i"}.'"';
			echo ' size="' . min(40, $val) . '"';
			echo ' maxlength="'. min(40, max(10, $val)) .'"';
			echo '></td>';
			echo '</tr>';

			$i++;
		}
		echo '<tr><td colspan=2>'.$this->labels['Records per screen'].'</td>';
		echo '<td align=left><input type=text name=inc value="'.$this->inc.'"></td></tr>';
		echo '</table>';
		if ($this->nav_down()) {
			echo "<hr>\n";
			$this->display_report_selection_buttons();
		}
		echo '</form>';
	} /* }}} */

}

/* Modeline for ViM {{{
 * vim:set ts=4:
 * vim600:fdm=marker fdl=0 fdc=0:
 * }}} */

?>
