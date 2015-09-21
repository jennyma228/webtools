<?php
	
	/**
	 * Adjusts user configuration with default configuration.
	 * If a configuration key has an empty value, use a default value instead
	 * @param array $configuration
	 * @return array
	 */
	function defaultGUIConfiguration($configuration) {
		$default = array(
			'language' => 'en_GB',
			'template' => 'default',
			'icons' => 'default',
		);
		// replace empty configuration variables with the default ones
		return $GLOBALS['pdlc'] -> configuration($configuration,
			$default);		
	}
	
	function modRewriteRedirect () {
		if(!empty($_GET['gosearch'])) {
			if ($GLOBALS['pdlc'] -> modRewrite)
				header('Location: http://'. host() .
				dirname($_SERVER['REQUEST_URI']) .
				'/search:'. $_GET['gosearch']);
			else
				header('Location: http://' .
				host() .
				dirname($_SERVER['REQUEST_URI']) .
				'/?directory=' .
				@$_GET['directory'] .
				'&search='. $_GET['gosearch']);
		}
	}
	
	function setLanguage($language) {
		/*
		 * Sets the language from the configuration to use with gettext.
		 * Thanks to <mario.ugedo@gmail.com>
		 */
		// Set language
		setlocale(LC_ALL, $language.'.utf8');
		putenv('LANG='.$language.'.utf8');
		// Specify location of translation tables
		bindtextdomain('default', './.pdirl/languages/');
		bind_textdomain_codeset('default', 'UTF-8');
		// Select domain
		textdomain('default');
		return true;
	}


//	Views were planned for the 1.0 series, but I think, it would be better if 
//	I don't build so much features in pdirl-1.0, but fix some security relevant 
//	problems ;-) So you get the view features maybe in some 1.1.x version
	/**
	 * Returns the current view.
	 * @param object $viewConfiguration [optional]
	 * @return string
	*/
	function getView($viewConfiguration = false) {
//		if ($viewConfiguration === '') {
//			if (!empty($_POST['view'])) {
//				setcookie('view', htmlspecialchars($_POST['view']), time() + 60 * 60 * 24 * 7, dirname($_SERVER['SCRIPT_NAME'].'/'));
//				return htmlspecialchars($_POST['view']);
//			} elseif (!empty($_COOKIE['view'])) {
//				return $_COOKIE['view'];
//			} else {
//				return $this -> defaultView;
//			}	
//		} else {
//			return $this -> view;
//		}
	}
	
	
	/**
	 * Sets the view.
	 * @param object $view
	 * @return 
	 */
	function setView($view) {
//		if (setcookie('view', htmlspecialchars($view), time() + 60 * 60 * 24 * 7, dirname($_SERVER['SCRIPT_NAME'].'/')))
//			return true;
//		else
//			return false;
	}
	
	/**
	 * Converts bytes to a more readable format. ex. '3,21 KB'
	 * @link http://us3.php.net/manual/en/function.filesize.php#84652
	 * @author <nak5ive@gmail.com>
	 * @param interger $size
	 * @param interger $precision [optional]
	 * @return array
	 */
	function sizeReadable ($size, $precision = 0) {
		$sizes = array('YB', 'ZB', 'EB', 'PB', 'TB', 'GB', 'MB', 'KB', 'B');
		$total = count($sizes);
		/*
		 * $total is the number how often while was executed.
		 * If you have 10 units and while was executed 3 times, $total is 7.
		 */
		while($total-- && $size > 1024)
			$size /= 1024;
		$return['number'] = round($size, $precision);
		$return['unit'] = $sizes[$total];
		return $return;
	}
	
	
	/**
	 * Returns time left from supplied date ex. '3 days ago' etc.
	 * @author <andypsv@rcdrugs.com>
	 * @link http://us.php.net/manual/en/function.time.php
	 * @param interger $timestamp
	 * @return string
	 */
	function lastModificationReadable($timestamp) {
		$current = time();
		$difference = $current - $timestamp;
		$lengths = array(1, 60, 3600, 86400, 604800, 2630880, 31570560);
		for ($val = sizeof($lengths) - 1; ($val >= 0) && (($number = $difference / $lengths[$val]) <= 1); $val--);
		if ($val < 0) $val = 0;
		$new_time = $current - ($difference % $lengths[$val]);
		if (floor($number) == 1)
			$periods = array('second', 'minute', 'hour', 'day', 'week', 'month', 'year');
		else
			$periods = array('seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years');
		return sprintf(_('%d %s ago'), $number, _($periods[$val]));
	}
	
	
	/**
	 * Returns (linked) path navigation
	 * @param string $path
	 * @param boolean $link [optional]
	 * @return string
	 */
	function path($path=false, $link=false, $absolute=false) {
//		if (!$path)
//			$path = $_SERVER['REQUEST_URI'];
		if ($path == $GLOBALS['pdlc'] -> getScriptPath())
			$path = dirname($path);
		$path = explode('?', $path);
		preg_match('/^(.*)?(search:([^|]+))?\|?(sort:(name|mtime|size)(:(asc|desc))?)?$/', $path[0], $match);
		$path = $match[1];
		$path = str_replace('./', '', $path);
		$dir_array = explode ("/", $path);
		$counter = 1;
		$path = "";
		foreach ($dir_array as $dir) {
			if ($dir != "") {
				$dir = htmlspecialchars(urldecode($dir));
				if ($link) {
					$linkhref = implode("/", array_slice($dir_array, 0, $counter));					
					if ($GLOBALS['pdlc'] -> modRewrite) {
						if ($absolute) {
							$linkhref = '/'.path(scriptPath()).$linkhref;
						}
						$path .= '<a href="'.$linkhref.'/">'.$dir.'</a>/';
					} else {
						$path .= '<a href="'.scriptPath().'?directory='.$linkhref.'/">'.$dir.'</a>/';					
					}
				} else {
					$path .= $dir."/";
				}
			}
			$counter++;
		}
		return $path;
	}
	
	function goParent($path = false) {
		if (!$path)
			$path = currentDirectory();
		if (modRewriteActive()) {
			return scriptDirectory()."/".currentDirectory()."/../..";
		} else {
			$parentdir = dirname($path);
			if ($parentdir == '.')
				return "?directory=";
			else
				return "?directory=".$parentdir."/";
		}
	}
	
	/**
	 * Seperates directories from files
	 * @param array $elements
	 * @return array
	 */
	function seperateDirectoriesAndFiles(&$elements) {
		$directories = array();
		$files = array();
		if (is_array($elements)) {
			foreach($elements as $element) {
				if ($element['directory']) {
					$directories[] = $element;
				} else {
					$files[] = $element;
				}
			}
		}
		// Sort directories by their number of elements not their bytes
		if (sortKey() == 'bytes' && !empty($directories))
			$directories = sortElements($directories, array('key'=>'numberofelements', 'sort'=>sortOrder()));
		$elements = array('directories' => $directories, 'files' => $files);
		return true;
	}
	
	
	/**
	 * Adds human readable modfication time and size.
	 * @param array $elements
	 * @return array
	 */
	function addHumanInformation (&$elements) {
		if (is_array($elements)) {
			foreach ($elements as $key => $elementData) {
				if ($elementData['readable']) {
					$elements[$key]['sizer'] = sizeReadable($elementData['bytes']);
					$elements[$key]['mtimer'] = lastModificationReadable($elementData['mtime']);
				}
			}
			return true;
		} else {
			return false;
		}
	}
	
	
	/**
	 * abbreviated form of <img src="index.php?icon=blablablabla...
	 * @param string $icon
	 * @param string $iconDesc [optional]
	 * @return string
	 */
	function imgTagIcon ($icon, $iconDesc = '') {
		printf('<img src="%s/%s.png" alt="%s" title="%3$s" class="%2$s" />', iconDirectory(), $icon, _($iconDesc)); 
	}
	
	/**
	 * translated filetype info
	 * @param string $type
	 * @return string
	 */
	function filetypeInfo ($type) {
		$typesList = array (
			'audio'		=> 'Audio file',
			'image'		=> 'Image file',
			'web'		=> 'Web document',
			'text'		=> 'Text document',
			'package'	=> 'Compressed package',
			'program'	=> 'Application',
			'directory' => 'Directory',
			'default'	=> 'Unknown file type',
		);
		return _($typesList[$type]);
	}
	
	
	/**
	 * breadcumb navigation, $pdirlView -> path () doesn't return the actual directory in plain text format, but in slash-seperated links of directories.
	 * e.g. <a>dir</a>/<a>anotherdir</a>/<a>blubb</a>
	 * $pdirl -> getHost ($link) gives out the current hostname, if $link's true, it will give out a link
	 * A combination of getHost(1) and path() returns a breadcumb navigation
	*/
	function printBreadcrumb () {
		$scriptDirPath = scriptDirectory();
		// path: bla/bla/blubb
		$scriptDirPath = explode('/', $scriptDirPath);
		// folder: blubb
		$scriptFolder = array_pop($scriptDirPath);
		// path: bla/bla/
		$scriptDirPath = implode('/', $scriptDirPath);
		$breadcrumbPath = sprintf('%s/%s<a href="%s/%s/">%s</a>%s',
			host($link = true),
			path($scriptDirPath),
			$scriptDirPath,
			$scriptFolder,
			path($scriptFolder),
			path(currentDirectory(), true, true));
		
		if (searchTag()) {
			// It wil return "search for 'searchkeyword' in host/path
			printf(_('Search for "%s" in %s'), searchTag(), $breadcrumbPath);
		} else {
			echo _('Directory listing for '), $breadcrumbPath;
		}
	}
	
	function sortLink($key, $text) {
		if (userSortActive()) {
			$sortlink = "<a ";
			if (sortKey() == $key) {
				$classorder = str_replace(array('SORT_ASC', 'SORT_DESC'), array('asc', 'desc'), sortOrder());
				$sortlink .= 'class="'.$classorder.'" ';
				$linkorder = sortOrder(true); // adesc
			} else {
				$linkorder = "SORT_ASC";
			}
			$sortlink .= ' href="'.scriptPath().'?sortkey='.$key.'&sortorder='.$linkorder.'&directory='.currentDirectory();
			if (searchTag() != '')
				$sortlink .= '&search='.searchTag();
			$sortlink .= '">'.$text.'</a>';
		} else {
			$sortlink = $text;
		}
		echo $sortlink;
	}
	
	function currentTemplate() {
		return $GLOBALS['GUIconf']['template'];
	}
	
	function templateDirectory() {
		return scriptDirectory().'/.pdirl/templates/'.currentTemplate();
	}
	
	function currentIcons() {
		return $GLOBALS['GUIconf']['icons'];
	}
	
	function iconDirectory() {
		return scriptDirectory().'/.pdirl/icons/'.currentIcons();
	}
	
	function searchInput () {
		if (searchTag())
			return searchTag();
		else
			return _('Search...');
	}
	
	function host ($link = false) {
		if ($link) {
			return sprintf('<a href="http://%s">%1$s</a>', $GLOBALS['pdlc'] -> getHost($link));
		} else {
			return $GLOBALS['pdlc'] -> getHost($link);
		}
	}
	function searchTag()		{return $GLOBALS['pdlc'] -> getSearchTag();}
	function scriptPath()		{return $GLOBALS['pdlc'] -> getScriptPath();}
	function scriptDirectory()	{return $GLOBALS['pdlc'] -> getScriptDirectory();}
	function currentDirectory()	{return $GLOBALS['pdlc'] -> getCurrentDirectory();}
	function numberOfElements()	{return $GLOBALS['pdlc'] -> getNumberOfElements();}
	function totalSize()		{return $GLOBALS['pdlc'] -> getTotalSize();}
	function urlPath($path)		{return $GLOBALS['pdlc'] -> urlPath($path);}
	function sortOrder($r=false){return $GLOBALS['pdlc'] -> getSortOrder($r);}
	function sortKey()			{return $GLOBALS['pdlc'] -> getSortKey();}
	function sortElements($elementsVar = false, $sortOptions = false){
		return $GLOBALS['pdlc'] -> sortElements($elementsVar, $sortOptions);
	}
	function userSortActive()	{return $GLOBALS['pdlc'] -> isUserSortActive();}
	function modRewriteActive()	{return $GLOBALS['pdlc'] -> isModRewriteActive();}
?>