<?php

	/**
	 * Class CB_Block_Admin
	 *
	 * Basic functions for controlling module position, title, view 
	 * appearance, installation and uninstallation.
	 *
	 * Copyright (C) 2002 Michael Cannon <michael@peimic.com>
	 * See full GNU Lesser General Public License in LICENSE.
	 *
	 * Based upon block_set.inc.php by Matthew McNaney and Edward Ritter.
	 * block_admin operation based upon phpwsmaps's display_options() by
	 * Talal Nehme <Talal@mi4.com>
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: class.CB_Block_Admin.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */



class CB_Block_Admin 
{
	var $menu_table;	
	var $modules_table;	
	var $module_name;
	var $module_dir;
	var $doc_root;
	var $mod_table_prefix;
	var $table_prefix;


	/**
	 * Class constructor
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @return void
	 */
	function CB_Block_Admin() 
	{
		global $mod;				/* module name from URL */
		global $table_prefix;	/* comes from config.php */

		$this->table_prefix = isset($table_prefix)
			? $table_prefix
			: '';
			
		$this->module_name = $mod;
		$this->doc_root = dirname(__FILE__) . '/';
		
		$this->menu_table = $this->table_prefix . 'menu';
		$this->modules_table = $this->table_prefix . 'modules';
		$this->mod_table_prefix = $this->table_prefix . 'mod_' 
			.  $this->module_name . '_';
		$this->module_dir = $this->doc_root . '../' . $this->module_name;

	}



	/**
	 * Controls module placement, visibility, and title appearance.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @return void
	 */
	function display_set_block_options() 
	{
		$box_title = ucfirst($this->module_name) . ' Block Display Options';    
		
		ob_start();
		include_once("$this->doc_root/phpws_modules.php");
		$box_content .= ob_get_contents();
		ob_end_clean();

		$this->Display($box_title, $box_content);
	}



	/**
	 * Displays operation confirmation message
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @param string $operation
	 * @return new op value
	 */
	function get_op_confirmation ($operation) 
	{
		 $action = './mod.php?mod=' . $this->module_name;
		 
		 $box_title = 'Confirm ' . ucfirst($this->module_name) . ' ' . ucwords($operation);
		$box_content = "
			<form action='$action' method='post' name='get_op_confirmation'>
				<input type='hidden' name='op' value='block' />
				Do you really want to $operation $this->module_name?<br />
				<input type='radio' name='baOp' value='NO' checked='checked' />No
				<br />
				<input type='radio' name='baOp' value='YES$operation' />Yes
				<br />
		";

		if ( 'uninstall' == $operation )
		{
			$box_content .= "
				Check to delete module directory and files <input type='checkbox'
				name='delete_dir' value='1' />
				<br />
			";
		}

		
		$box_content .= "
				<input type='submit' value='Submit' />&nbsp;<input type='reset' />
			</form>
		";
		 
		$this->display($box_title, $box_content);
	}



	/**
	 * Display administrator menu for a given module
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @return void
	 */
	function AdminMenu() 
	{
		$box_title = ucfirst($this->module_name) . ' Administration Menu'; 

		$box_content = "
			<a href='./mod.php?mod=$this->module_name&amp;op=admin'>Features</a>
			&nbsp;|&nbsp; 
			<a href='./mod.php?mod=$this->module_name&amp;op=config'>Configuration 
				Options</a> 
			&nbsp;|&nbsp; 
			<a href='./mod.php?mod=$this->module_name&amp;op=block'>Block Display 
				Options</a>
			&nbsp;|&nbsp; 
			<a href='./mod.php?mod=$this->module_name&amp;op=block&amp;baOp=setup'>Module Setup</a>
			&nbsp;|&nbsp; 
			<a href='./admin.php'>Admin Home</a>
		";
		 
		$this->display($box_title, $box_content);
	}



	/**
	 * Display inputted file list.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @param string/array $readmeList readme file name or array of such
	 * @param boolean $use_show_source use PHP's show_source() to parse the 
	 *		$readmeList, result looks good
	 * @return void
	 */
	function display_file($readmeList = 'README', $use_show_source = false)
	{
		$box_title = '';
		$box_content = '';

		// ensure user can see source code, as their style.css may not describe
		// <code> and user has dark background
		if ( $use_show_source ) 
		{
			$div_start = "<div style='background-color: white'>";
			$div_end = '</div>';
		}
		
		// convert $readmeList to array if not one
		if ( !is_array($readmeList) )
		{
			$readmeList = array($readmeList);
		}

		foreach($readmeList AS $readmeFile)
		{
			// simple file processing
			if ( !$use_show_source ) 
			{
				// get file as a string
				$box_content = file2str($readmeFile);
				
				// convert file to rudimentary html
				$box_content = str2html($box_content);
			}
			
			// PHP show_source() file processing
			else
			{
				$box_content = show_source($readmeFile, TRUE);
				$box_content = $div_start . $box_content . $div_end;
			}

			$box_title = "$readmeFile Review"; 
			$this->display($box_title, $box_content);
		}
	}



	/**
	 * Install and Uninstall options  
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @return void
	 */
	function display_setup_options () 
	{
		$install = './mod.php?mod='.$this->module_name.'&amp;op=block&amp;baOp=install';
		$uninstall = './mod.php?mod='.$this->module_name.'&amp;op=block&amp;baOp=uninstall';

		$box_title = ucfirst($this->module_name) . ' Setup Options'; 
		$box_content = "
			<a href='$install'>Install</a><br />
			<a href='$uninstall'>Uninstall</a><br />
		";

		$this->display($box_title, $box_content);
	}



	/**
	 * Display links to mod admin and Admin Page
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @param string $box_title
	 * @param string $box_content
	 * @param int $admin_links - display links back to admin pages
	 * @return function thememainbox($box_title, $box_content)
	 */
	function display($box_title = '', $box_content = '', 
		$admin_links = 0) 
	{
		if ( empty($box_title) ) 
		{
			$box_title = $this->module_name;
		}
		
		if ( $admin_links ) 
		{
			$select = "
				SELECT admin_inc 
				FROM $this->modules_table 
				WHERE plug_dir='$this->module_name'
			";

			$result = mysql_query($select);
					
			ReportMySQLError($result, mk_empty_string(), $select, true);
		
			list ($admin_inc) = mysql_fetch_row ($result);
		
			$box_content .= "
				<p>
					<a
					href='mod.php?mod=$this->module_name&amp;op=$admin_inc'>$this->module_name
					Administration Menu</a> 
					<br />
					<a href='admin.php'>Back to Admin Page</a>
				</p>
			";
		}

	 	thememainbox($box_title, $box_content);
	}



	/**
	 * Displays Cannon BOSE tagline with module name
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @return void
	 */
	function CBTagline() 
	{
		$CBTtitle = '&nbsp;';
		$CBTcontent = "
			<div style='text-align: right; font-size: smaller;'>
				$this->module_name by <a
				href='http://peimic.com' target='_cb'>Cannon BOSE</a><br />
				<a href='http://peimic.com/mod.php?mod=downloads' target='_cb'>Download $this->module_name</a><br />
				<a href='http://mantis.peimic.com/' target='_cb'>Report bug</a> or 
				<a href='http://mantis.peimic.com/' target='_cb'>Request feature</a>
			</div>";

		$this->display($CBTtitle, $CBTcontent);
	}  



	/**
	 * Aid in module Installation to insert module into mod_modules table and
	 * then calls for further sql queries to create tables and install data if 
	 * needed.
	 *
	 * @return void
	 */
	function install_module () 
	{
		// from module_config.php
		global $mod_version;
		global $mod_name_ver;
		global $mod_block_name;
		global $mod_author_tag;

		$box_title = ucfirst($this->module_name) . ' Installation'; 
		$box_content = '';

		$count = "
			SELECT count(*) 
			FROM $this->modules_table 
			WHERE plug_dir='$this->module_name'
		";

		$mod_count = mysql_query($count);
		list($mod_exist) = mysql_fetch_row($mod_count);

		if ( $mod_exist ) 
		{
			$box_content .= "
				<br />Nothing changed, $this->module_name already loaded.
			";
		
			$this->install_module_tables($box_content);
		}

		else 
		{
			$mod_url = 'mod.php?mod=' . $this->module_name;
			$mod_block = $this->module_name . '_block.php';

			// Insert the module data into the module table
			$insert_module = "
				INSERT INTO $this->modules_table 
				VALUES(
					NULL,
					'$mod_block_name',
					'$mod_url',
					'$this->module_name.gif',
					'$this->module_name',
					'1',
					'$mod_block',
					'0',
					'0',
					'admin',
					'$mod_name_ver &copy; $mod_author_tag.'
				)
			";

			// Find out how it went
			$result = mysql_query($insert_module);

			ReportMySQLError($result, $box_content, 
				"INSERT INTO $this->modules_table");

			$this->menu_post($mod_block_name, $mod_url);

			$this->install_module_tables($box_content);
		}

		$this->display($box_title, $box_content, 1);
	}


	/**
	 * Creates, alters, or inserts mod tables as needed
	 *
	 * @param string &$box_content
	 * @return void
	 */ 
	function install_module_tables (&$box_content) 
	{
		// from module_config.php
		global $mod_name_tables;
		global $create_tables;
		global $alter_tables;
		global $insert_tables;
		global $dbname;
		
		if ( isset($create_tables) )
		{
			if ( is_array($create_tables) ) 
			{
				$installed_tables = mysql_list_tables($dbname);
				$installed_tables = sql_result2array($installed_tables);

				foreach($create_tables as $table => $create_statement) 
				{
					$table_name = $this->mod_table_prefix . $table;

					// check to see if table exists, if not create it
					$use_create = ( !in_array($table_name, $installed_tables) )
						? true
						: false;

					// create if table doesn't already exist
					if ( $use_create ) 
					{
						$result = mysql_query($create_statement);
							
						ReportMySQLError($result, $box_content, "CREATE TABLE $table");

						$insert_statement = ( isset($insert_tables[$table]) )
							? $insert_tables[$table]
							: false;
					
						if ($insert_statement) 
						{
							// is $insert_statement an array or singular statement
							if (is_array($insert_statement)) 
							{
								foreach ($insert_statement as $insert) 
								{
									$result = mysql_query($insert);
									  
									ReportMySQLError($result, $box_content, 
										"INSERT INTO $table");
								}
							}
							
							else 
							{
								$result = mysql_query($insert_statement);
								 
								ReportMySQLError($result, $box_content, "INSERT INTO $table");
							}
						}
					}
					
					else 
					{
						$alter_statement = isset($alter_tables[$table])
							? $alter_tables[$table]
							: false; 
					
						if ($alter_statement) 
						{
							$result = mysql_query($alter_statement);
							
							ReportMySQLError($result, $box_content, "ALTER TABLE $table");
						}
						
						else 
						{
							$box_content .= "<br />No changes made to $table";
						}
					}
				}
			}

			// sql in file or string
			else
			{
				$create_tables = mquery_str2arr($create_tables);

				foreach ( $create_tables AS $key => $query )
				{
					$result = mysql_query($query);
					
					ReportMySQLError($result, $box_content, $query);
				}
			}
		}
	}	

	
	
	/**
	 * Add module link to main menu.
	 *
	 * @param string $menu_text name of module to add to menu, 
	 * 	ex: 'Downloads'
	 * @param string $menu_url module's URI string, ex: 'mod.php?mod=downloads'
	 * @return void 
	 */
	function menu_post($menu_text, $menu_url)
	{
		$sql_mi = "
			SELECT MAX(menu_id) AS menu_id
			FROM $this->menu_table 
			WHERE menu_id >= '0' 
				AND menu_id < '100'
		";

		$result_id = sql_query_object($sql_mi);
		$menu_id = $result_id->menu_id;

		$sql_mo = "
			SELECT MAX(menu_order) AS menu_order
			FROM $this->menu_table 
			WHERE menu_level='1'
		";
	
		$result_order = sql_query_object($sql_mo);
		$menu_order = $result_order->menu_order;

		$menu_id++;
		$menu_order++;

		$insert = "
			INSERT INTO $this->menu_table 
			(menu_id, menu_text, menu_url, menu_level, menu_active, menu_order) 
			VALUES ('$menu_id', '$menu_text', '$menu_url', '1', '1', '$menu_order')
		";

		$menu_result = sql_query($insert);
	}



	/**
	 * Add module link to main menu.
	 *
	 * @param string $menu_url module's URI string, ex: 'mod.php?mod=downloads'
	 * @return void 
	 */
	function menu_remove($menu_url)
	{
		// find tuples menu_id with $menu_url
		$select = 'menu_id';
		$from = $this->menu_table;
		$where = "menu_url LIKE '%$menu_url%'";

		$result = sql_select($select, $from, $where);
	
		// save results to array
		$menu_ids = sql_result2array($result);

		// found tuples
		if ( count($menu_ids) > 0 )
		{
			// convert array to csv string
			$menu_ids = arr2csv($menu_ids);

			// delete tuples in csv string
			$delete = "
				DELETE 
				FROM $from
				WHERE menu_id IN ($menu_ids)
			";

			$result = sql_query($delete);
		}
	}



	/**
	 * Aids in uninstalling module from modules table, deleting module
	 * specific tables, and deleting files/directory as needed or requested
	 *
	 * @return void
	 */
	function uninstall_module () 
	{
		global $delete_dir;

		$box_title = ucfirst($this->module_name) . ' Uninstallation'; 
		$box_content = '';

		$delete_module = "
			DELETE FROM $this->modules_table WHERE plug_dir='$this->module_name'
		"; 
	 
		$mod_url = 'mod.php?mod=' . $this->module_name;
		$this->menu_remove($mod_url);

		$result = mysql_query($delete_module);

		ReportMySQLError($result, $box_content, 
			"DELETE FROM $this->modules_table");
			
		$this->uninstall_module_tables($box_content);

		if ( $delete_dir )
		{
			$box_content .= $this->delete_directory("./mod/$this->module_name");
		}

		$this->display($box_title, $box_content);
	}


	 
	/**
	 * Drops mod tables as needed
	 *
	 * @param string &$box_content
	 * @return void
	 */ 
	function uninstall_module_tables(&$box_content) 
	{
		global $drop_tables;

		if ( isset($drop_tables) )
		{
			$drop_tables = ( is_array($drop_tables) )
				? $drop_tables
				: mquery_str2arr($drop_tables);

			foreach($drop_tables as $table => $drop_statement) 
			{
				$result = mysql_query($drop_statement);

				ReportMySQLError($result, $box_content, "DROP $table");
			}
		}
	}	



	/**
	 * Removes files from $location and deletes diretory $location.
	 *
	 * @param string $location directory location, ex: "./mod/phpws_mail"
	 * @return string
	 */ 
	function delete_directory($location) 
	{ 
		$response = '';

		if ( substr($location, -1) != '/' )
		{
			$location = $location . '/'; 
		}

		$all = opendir($location); 

		while ( $file = readdir($all) )
		{
			if ( is_dir($location.$file) && $file != '..' && $file != '.' ) 
			{ 
				$this->delete_directory($location.$file); 
				rmdir($location.$file); 

				$response .= "Removed directory ($location$file)<br />"; 

				unset($file); 
			} 
			
			elseif ( !is_dir($location.$file) ) 
			{ 
				unlink($location.$file); 

				$response .= "Removed file ($location$file)<br />"; 
				
				unset($file); 
			} 
		}

		rmdir($location); 

		return $response;
	}



	/**
	 * Class CB_Block_Admin control structure.
	 *
	 * @param string $baOp block admin operation to perform, ex: setup, NO
	 * @return void
	 */
	function BlockController($baOp = '')
	{
		switch ($baOp)
		{
			case 'setup':
				$this->display_setup_options();

				// older PHP doesn't allow show_source(file, true)
				$show_source = ( phpversion() >= "4.2.0" )
					? true
					: false;

				$arr = get_dir_listing($this->module_dir, '^readme', true);
				$this->display_file($arr, $show_source);
				
				$arr = get_dir_listing($this->module_dir, '^install', true);
				$this->display_file($arr, $show_source);
				
				$arr = get_dir_listing($this->doc_root, '^readme', true);
				$this->display_file($arr, $show_source);
				break;
					
			case 'install':
				$this->get_op_confirmation($baOp);
				break;
				
			case 'YESinstall':
				$this->install_module();
				break;
				
			case 'uninstall':
				$this->get_op_confirmation($baOp);
				break;
					
			case 'YESuninstall':
				$this->uninstall_module();
				break;
				
			case 'NO':
				$box_content = 'Nothing changed';
				$this->display($box_title, $box_content, 1);
				break;

			case 'block':
			default:
				$this->display_set_block_options();
				break;			
		}
	}



}	/* end of class block_admin */

?>
