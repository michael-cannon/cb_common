<?php

/*
   phpMyEdit-slide.class.php - slide show extension for phpMyEdit
   Developed by Ondrej Jombik <nepto@php.net>
   Copyright (c) 2002 Platon SDG, http://www.platon.sk/

   Coding elapsed time: from 8:30 to 10:30 at 30th October 2002 with heavily
   patching phpMyEdit core class. Music used: E-Type (Campione, This is the
   Way and others).

   This should be moved into official documentation, under section
   "Extensions". Main extension configuration is done via $opts['ext'] array.
   All options are optional.

   $opts['ext']['rec'] - initial primary key (ie. ID) of record to display
   $opts['ext']['next'] - next record primary key
   $opts['ext']['prev'] - previous record primary key
   $opts['ext']['next_disable'] - disable functionality of next button
   $opts['ext']['prev_disable'] - disable functionality of prev button
 */

/* $Platon: phpMyEdit/extensions/phpMyEdit-slide.class.php,v 1.3 2002/11/02 19:16:45 nepto Exp $ */

require_once dirname(__FILE__).'/../phpMyEdit.class.php';

class phpMyEdit_slide extends phpMyEdit
{
	// Extension options array
	var $ext;

	function phpMyEdit_slide($opts) /* {{{ */
	{
		$execute = 1;
		isset($opts['execute']) && $execute = $opts['execute'];
		$opts['execute'] = 0;
		parent::phpMyEdit($opts);

		$this->ext = $opts['ext'];

		$execute && $this->execute($opts);
	} /* }}} */

	function display_record_buttons() /* {{{ */
	{
		// TODO: classify this table and cells
		echo '<table border=0 cellpadding=0 cellspacing=0 width="100%" style="border:0;padding:0;">';
		echo '<tr><td align=left style="text-align:left;border:0;padding:0;" nowrap>' . "\n";
		if ($this->change_operation()) {
			echo '<input type="submit" name="savechange" value="'.$this->labels['Save'].'" />'."\n";
			echo '<input type="button" name="cancel" value="'.$this->labels['Cancel'].'" onClick="form.submit();" />'."\n";
			echo '<input type="hidden" name="rec_change" value="1">';
		} elseif ($this->view_operation()) {
			if ($this->change_enabled()) {
				echo '<input type="submit" name="operation" value="'.$this->labels['Change'].'" />'."\n";
			}
			echo '<input type="submit" name="cancel" value="'.$this->labels['Cancel'].'" />'."\n";
		}

		if (! $this->ext['prev_disable']) {
			$disabled = $this->ext['prev'] ? '' : ' disabled';
			echo '<input'.$disabled.' type="submit" name="'.ltrim($disabled).'prev" value="'
				.$this->labels['Prev'].'">&nbsp;';
			echo '<input type="hidden" name="rec_prev" value="'.$this->ext['prev'].'">';
		}
		if (! $this->ext['next_disable']) {
			$disabled = $this->ext['next'] ? '' : ' disabled';
			echo '<input'.$disabled.' type="submit" name="'.ltrim($disabled).'next" value="'
				.$this->labels['Next'].'">';
			echo '<input type="hidden" name="rec_next" value="'.$this->ext['next'].'">';
		}
		echo '</td></tr></table>'."\n";
	} /* }}} */

	function execute($opts) /* {{{ */
	{
		if ($this->get_cgi_var('rec_change')
				&& ($this->next_operation() || $this->prev_operation())) {
			$this->operation = $this->labels['Change'];
		}
		if (! $this->change_operation()) {
			$this->operation = $this->labels['View'];
		}
		if ($this->prev_operation()) {
			! $this->ext['prev_disabled'] && $this->rec = $this->get_cgi_var('rec_prev');
			$this->prev = '';
		}
		if ($this->next_operation()) {
			! $this->ext['next_disabled'] && $this->rec = $this->get_cgi_var('rec_next');
			$this->next = '';
		}
		if (! $this->rec) {
			$this->rec = $this->ext['rec'];
		}

		if (! $this->rec
				|| (! $this->ext['prev_disable'] && ! $this->ext['prev'])
				|| (! $this->ext['next_disable'] && ! $this->ext['next'])) {
			if ($this->connect() == false) {
				return false;
			}
			$query_parts = array(
					'type'   => 'select',
					// XXX FIXME - simplify query
					'select' => 'Table0.'.$this->key,
					'from'   => $this->create_join_clause(),
					'where'  => $this->make_where_from_query_opts());
			// TODO: order by clausule according to default sort order options
			$res = $this->myquery($this->query_make($query_parts), __LINE__);
			$ids = array();
			while (($row = @mysql_fetch_array($res, MYSQL_NUM)) !== false) {
				$ids[] = $row[0];
			}
			@mysql_free_result($res);
			if ($this->rec) {
				$idx = array_search($this->rec, $ids);
				$idx === false && $idx = 0;
			} else {
				$idx = 0;
			}

			$this->rec = $ids[$idx];
			! $this->ext['prev'] && $this->ext['prev'] = $ids[$idx - 1];
			! $this->ext['next'] && $this->ext['next'] = $ids[$idx + 1];
		}
	
		$this->recreate_fdd();
		$this->recreate_displayed();
		parent::execute();
	} /* }}} */

}

/* Modeline for ViM {{{
 * vim:set ts=4:
 * vim600:fdm=marker fdl=0 fdc=0:
 * }}} */

?>
