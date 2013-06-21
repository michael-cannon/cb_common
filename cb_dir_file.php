<?php

	/**
	 * Cannon BOSE's centralization of common directory and file functions
	 *
	 * Copyright (C) 2002 Michael Cannon <michael@peimic.com>
	 * See full GNU Lesser General Public License in LICENSE.
	 *
	 * @author Michael Cannon <michael@peimic.com>
	 * @package cb_common
	 * @version $Id: cb_dir_file.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
	 */



	/**
	 * Builds an array of file URI's based upon the match of $file_type within a 
	 * filename.
	 *
	 * @param string $directory directory being looked into
	 * @param string $file_type regexp type of files to look for, 
	 * 	ex: readme, install
	 * @param boolean $dir_prepend prepend $dir to found files in array
	 * @param boolean $case_sensitve match $file_type case,
	 * @param mixed boolean/string $file_only return only file names, 
	 * 	ex: true (files only), false (dirs only), 'ALL' (dir contents)
	 * @return array
	 */
	function get_dir_listing($directory = '.', $file_type = '', 
		$dir_prepend = false, $case_sensitive = false, $file_only = true)
	{
		$list = array();

		// create regexp, lookfor case sensitive $file_type
		$regexp = '/' . $file_type . '/';

		// case insensitive
		if ( !$case_sensitive )
		{
			$regexp .= 'i';
		}

		// open module directory
		$dir = dir($directory);

		// cycle through directory listing
		// !== is a PHP 4.0+ comparison type and equality check
		// see http://www.php.net/manual/en/class.dir.php for more info
		while ( false !== ( $file = $dir->read() ) )
		{
			// ignore ./..
			if ( '.' != $file && '..' != $file )
			{
				// compare filenames there with the $file_type being searched for
				// when a case insensitive match is found
				if ( preg_match($regexp, $file) )
				{
					$path_file = $dir->path . '/' . $file;

					// files only
					if ( is_true($file_only) )
					{
						if ( is_file($path_file) )
						{
							// add that filename to $list
							$list[] = $file;
						}
					}

					// dirs only
					elseif ( is_false($file_only) )
					{
						if ( is_dir($path_file) )
						{
							// add that directory to $list
							$list[] = $file;
						}
					}

					// everything
					elseif ( 'ALL' == $file_only )
					{
						// add that directory contents to $list
						$list[] = $file;
					}
				}
			}
		}
		
		if ( $dir_prepend )
		{
			foreach ( $list AS $key => $file )
			{
				// update $file with $dir->path()
				$list[$key] = $dir->path . '/' . $file;
			}
		}

		$out = array(
			'directory'			=> $directory,
			'file_type'			=> $file_type,
			'dir_prepend'		=> $dir_prepend,
			'case_sensitive'	=> $case_sensitive,
			'regexp'				=> $regexp,
			'file_only'			=> $file_only,
			'list'				=> $list
		);

		$dir->close();

		return $list;
	}



	/**
	 * Returns string of virtual directory root based upon $path if any.
	 *
	 * @param string $path web page path, ex: $PHP_SELF, '/mod.php?mod=sam', 
	 * 	false (default: use $_SERVER['PHP_SELF'])
	 * @return string
	 */
	function virtual_root($path = false)
	{
		// false denotes default, check string type
		// otherwise use inputted path
		$path = ( is_false($path) || !is_string($path) )
			? $_SERVER['PHP_SELF']
			: $path;

		// could explode path
		$string = explode('/', $path);
		
		// remove last element which is file
		@array_pop($string);
		
		// implode to recreate path
		$string = implode('/', $string);

		if ( 0 )
		{
			// look for leading '/~'
			// true, create array
			// else, blank string
			$string = preg_match('/^(\/~)/', $path)
				? explode('/', $path)
				: '';

			// create root string with /~*
			if ( !is_blank($string) )
			{
				$string = '/' . $string[1];
			}
		}

		// add trailing slash
		$string .= '/';

		return $string;
	}

?>
