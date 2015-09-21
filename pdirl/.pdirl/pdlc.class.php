<?php

/**
 * PDLC is a Pdirl Directory Listing Class
 * @package pdirl
 */
class pdlc {
	
	function __construct($configuration) {
		if (!isset($configuration))
			$configuration = array();
		$conf = $this -> configuration($configuration);
		$this -> host = $conf['host'];
//		$this -> scriptPath = $conf['scriptpath'];
		$this -> scriptPath = $this -> rewriteScriptPath();
		$this -> ignore = $conf['ignore'];
		$this -> countElements = $conf['countelements'];
		$this -> sortOptions = $conf['sort'];
		$this -> userSort = $conf['usersort'];
		$this -> types = $conf['types'];
		$this -> listHidden = $conf['listhidden'];
		$this -> searchEnabled = $conf['searchenabled'];
		$this -> searchTag = $conf['searchtag'];
		$this -> searchHidden = $conf['searchhidden'];
		$this -> searchCaseSensitive = $conf['searchcasesensitive'];
		$this -> modRewrite = $conf['modrewrite'];
//		$this -> directoryPrefix = $conf['directory'];
//		// strip double prefixes
//		$this -> directory = $this -> stripDoublePrefix(@$_GET['directory'], $this -> directoryPrefix);
		// make the path more secure and remove illegal characters
		$this -> directory = $this -> rewritePath(@$_GET['directory']);
		$this -> sessionSort();
	}
	
	
	/**
	 * Adjusts user configuration with default configuration.
	 * If a configuration key has an empty value, use a default value instead
	 * @param array $configuration
	 * @param array $default
	 * @return array
	 */
	public function configuration($configuration, $default = false) {
		if (!$default) {
			$default = array(
				'host' => $_SERVER['HTTP_HOST'],
//				'scriptpath' => $this -> rewriteScriptPath(),
//				'directory' => '',
				'ignore' => array("index.php", ".htaccess", ".pdirl"),
				'countelements' => true,
				'sort' => array('key' => 'name', 'sort' => "SORT_ASC"),
				'usersort' => true,
				'listhidden' => false,
				'searchenabled' => true,
				'searchtag' => @$_GET['search'],
				'searchhidden' => false,
				'searchcasesensitive' => false,
				'modrewrite' => @$_GET['modrewrite'],
			);
		}
		if (!$configuration)
			return $default;
		foreach ($default as $key => $defaultValue) {
			if (isset($configuration[$key])) {
				// If configuration is empty, use the default value
				if ($configuration[$key] === '')
					$configuration[$key] = $defaultValue;
			}
		}
		return $configuration;
	}
	
	
	/**
	 * Makes path more secure.
	 * Please try to hack this, I'm not very sure if this is the most secure function
	 * @return string
	 */
	private function rewritePath($path) {
		$path = './'.$path.'/';
		if (count($path) > 0) {
			if ($path == './') {
				return '';
			} else {
				if (($path[0] == '.' OR $path[0] == '/') AND $path[0].$path[1] != './')
					return '';
				$search = array ("..", "//", ".?", ".*", "././", "/./");
				$replace= array ("",   "/" , "",   "",   "./",   "/");
				while ($path != str_replace($search, $replace, $path))
					$path = str_replace($search, $replace, $path);
			}
		}	   
		return $path;
	}
	
//	/**
//	 * Returns current directory path with the directory path in the configuration.
//	 * Because you get a double suffix when you list a subdirectory with pdirl (suffix + path)
//	 * it must be removed.
//	 * @return string
//	 */
//	function stripDoublePrefix ($path = false, $prefix = false) {
//		if (!$path && !$prefix) {
//			return '';
//		} elseif (!$path) {
//			return $prefix;
//		} elseif (!$prefix) {
//			return $path;
//		} else {
//			// double prefix to search and to destroy ;-)
//			$prefixSearch = $prefix.$prefix;
//			// Escape . \ + * ? [ ^ ] ( $ )
//			$prefixSearch = quotemeta($prefixSearch);
//			// Also strip something like '//' and '/./'
//			$prefixSearch = $this -> rewritePath ($prefixSearch);
//			// Escape '/', too
//			$prefixSearch = str_replace('/', '\/', $prefixSearch);
//			// Replace the double prefix with the normal prefix
//			return preg_replace("/^{$prefixSearch}/", $prefix, $this -> rewritePath($prefix.$path));
//		}
//	}
	
	/**
	 * returns a legal script path
	 * @return string
	 */
	private function rewriteScriptPath() {
		// make sure the path contains no illegal characters
		$scriptPath = $this -> rewritePath($_SERVER['PHP_SELF']);
		// delete the "." at the beginning of the path
		$scriptPath = str_replace('./', '/', $scriptPath);
		// delete the slash at the end
		$scriptPath = rtrim($scriptPath, '/');
		return $scriptPath;
	}
	
	
//	/**
//	 * script path should'nt be part of current directory path, to prevent double bredcrumbs.
//	 * @return string
//	 */
//	function currentDirectoryOnly($currentDir = false, $scriptPath = false) {
//		if (!$currentDir)
//			$currentDir = $this -> getCurrentDirectory();
//		if (!$scriptPath)
//			$scriptPath = $this -> getScriptPath();
//		if (strpos($currentDir, $scriptPath) === 0)
//			$currentDir = substr_replace($currentDir, "", 0, strlen($scriptPath));
//		else
//			return $currentDir;
//	}
	
	/**
	 * Returns formatted path, when modrewrite is inactive it will return the path via GET
	 * @param string $path
	 * @return string
	 */
	public function urlPath($path) {
		$path = $this -> rewritePath($path);
		if (!$this -> modRewrite)
			return "?directory=".urlencode($path);
		else
			// because basename() sucks on foreign characters, I use explode -> array_pop
			return htmlspecialchars($this -> getScriptDirectory()).'/'.$this -> encode($path);
	}
	
	/**
	 * urlencode on everything except the slashes
	 * @param array $path
	 * @return unknown_type
	 */
	public function encode($path) {
		if (!is_array($path)) {
			$path = explode ('/', $path);
		}
		$newPath = array();
		foreach ($path as $pathElement) {
			$newPath[] = rawurlencode($pathElement);
		}
		return implode('/', $newPath);
	}
	
	/**
	 * Sorts array columns
	 * @link http://us.php.net/manual/en/function.array-multisort.php#83117 php_multisort
	 * @author <php.net@sebble.com>
	 * @uses array_multisort
	 * @param multi-dimensional_array $elements [optional]
	 * @param array $sortOptions [optional]
	 * @return array
	 */
	public function sortElements($elementsVar = false, $sortOptions = false) {
		if (!$elementsVar) {
			if (!empty($this -> elements)) {
				$elements = $this -> elements;
			} else {
				return false;
			}		
		} else {
			$elements = $elementsVar;
		}
		if (!$sortOptions)
			$sortOptions = $this -> sortOptions;
		foreach($elements as $elementKey => $elementData) {
			$columns[$sortOptions['key']][$elementKey] = $elementData[$sortOptions['key']];
		}
		// $idkeys is a key-index
		$idkeys = array_keys($elements);
		array_multisort(array_map('strtolower', $columns[$sortOptions['key']]), constant($sortOptions['sort']), $idkeys);
		// if we have no idkeys, we'll return false
		$result = false;
		foreach($idkeys as $idkey) {
			// apply new sorting
			$result[$idkey] = $elements[$idkey];
		}
		if (!$elementsVar)
			$this -> elements = $result;
		return $result;
	}
	
	
	/**
	 * 
	 * @param string $file
	 * @return string
	 */
	public function getType($file) {
		if (is_dir($file))
			return 'directory';
		$typeList = $this -> types;
		$pathinfo = pathinfo($file);
		if (isset($pathinfo["extension"]))
			$extension = strtolower($pathinfo["extension"]);
		else
			$extension = '';
		// MIME-type will be split in two parts, type and subtype
		$mimeType = array("","");
		
		// via mime_content_type, the most exact method to get the mime-type, deprecated in 5.3
		if (function_exists('mime_content_type')) {
			// MCT is "mime_content_type", I use this name to separate from the mimeType that should be set
			if ($mctType = mime_content_type($file)) {
				$mimeType = explode('/', $mctType);
			}
			
		// via Fileinfo, "alternative" for mime_content_type()
		// Problems with Fileinfo
		// <http://de3.php.net/manual/de/function.finfo-open.php#74965>
		// <http://de3.php.net/manual/de/ref.fileinfo.php#79063>
		} elseif (function_exists('finfo_open')) {
			$finfoHandler = finfo_open(FILEINFO_MIME);
			if ($finfoType = finfo_file($finfoHandler, $file)) {
				// <http://de3.php.net/manual/de/function.finfo-file.php#77975>
				// "contrary to the documentation, finfo_file seems to be returning a 
				// semicolon delimited string that contains not just the mime type but also the character set."
				$finfoType = explode(';', $finfoType);
				$mimeType = explode('/', $finfoType[0]);
			}
			finfo_close($finfoHandler);
			
		// via Unix file command
		} elseif (array_search(php_uname('s'), array('Linux', 'FreeBSD', 'Mac OS X', 'Solaris')) !== false) {
			// output buffer, "records" what the shell returns
			ob_start();
			// --mime-type returns mime-type only w/o mime-encoding, -b keeps it brief, w/o the path of the file
            system('/usr/bin/file --mime-type -b ' . escapeshellarg(realpath($file)));
			$unixFileType = ob_get_contents();
            if (stristr($unixFileType, 'error') == false) {
				$mimeType = explode('/', $unixFileType);
            }
            ob_clean();
		}
		
		// <https://bugs.launchpad.net/pdirl/+bug/815701>
		$subtype = "";
		if(count($mimeType) > 1){
			$subtype = $mimeType[1];
		}
		$mimeType = array('type' => $mimeType[0], 'subtype' => $subtype);

		// search for MIME-type and extension in $typeList, if found, break foreach
		foreach ($typeList as $key => $value) {
			if (in_array($mimeType['subtype'], $value)) {
				return $key;
				break;
			} elseif ($mimeType['type'] == $key) {
				return $key;
				break;
			// the last method: search in the extension list, that was defined in the config
			} elseif (in_array($extension, $value)) {
				return $key;
				break;
			}
		}
		// if no other type was returned, return "default", which means that the file type is unknown
		return "default";
	}
	
	/**
	 * Checks if file/dir is ignored in configuration
	 * @return boolean
	 */
	private function ignored ($element, $ignoreList=false) {
		if (!$ignoreList)
			$ignoreList = $this -> ignore;
		if(is_array($ignoreList) ) {
			$ignoreList[] = '\/\.(\.)?$';
			foreach($ignoreList as $expression) {
				$expression = str_replace(".", "\.", $expression);
				if(preg_match("/$expression/", $element))
					return true;
			}
		}
		return false; 
	}
	
	/**
	 * Replacement for sql_regcase
	 * @link http://de3.php.net/manual/en/function.sql-regcase.php#86011
	 * @author <irker@irker.net>
	 * @param string $string
	 * @param string $encoding
	 */
	private function iregcase($string,$encoding='auto'){
		$max = mb_strlen($string,$encoding);
		$ret = '';
		for ($i = 0; $i < $max; $i++) {
			$char = mb_substr($string,$i,1,$encoding);
			$up = mb_strtoupper ($char,$encoding);
			$low = mb_strtolower($char,$encoding);
			$ret .= ($up!=$low)?'['.$up.$low.']' : $char;
		}
		return $ret;
	}
	
	/**
	 * recursive glob() function
	 * @link http://de3.php.net/manual/en/function.glob.php#90278
	 * @author <me@lx.sg>
	 * @param string $pattern [optional]
	 * @param string $path [optional]
	 * @param mixed $flags [optional]
	 * @return 
	 */
	private function rglob($pattern='*', $path='', $flags = 0) {
		$dir = dirname($pattern);
	    if (!$path && $dir != './') {
		    return $this -> rglob(basename($pattern), $dir . '/', $flags);
		}
		$path = $this -> escapePathBrackets($path);
		$paths = glob($path . '*', GLOB_ONLYDIR | GLOB_NOSORT);
		$files = glob($path . $pattern, $flags);
		
		// if glob returns false
		if ($paths == false) $paths = array();
		if ($files == false) $files = array();
	    
	    foreach ($paths as $p) $files = array_merge($files, $this -> rglob($pattern, $p . '/', $flags));
	    return $files;
	}

	
	/**
	 * Works like stat, but only for directories.
	 * A patchscript between http://us.php.net/manual/en/function.stat.php#61238
	 * and http://de3.php.net/manual/en/function.filemtime.php#91665
	 * @link http://de3.php.net/manual/en/function.filemtime.php#91665
	 * @author <info@daniel-marschall.de>
	 * @link http://us.php.net/manual/en/function.stat.php#61238
	 * @author <marting.dc@gmail.com>
	 * @param string $directory
	 * @return array
	 */
	private function rstat($directory) {
		$files = $this -> rglob('*', $directory, 0);
		$newestModification = 0;
		$DirectorySize = 0;
		foreach ($files as $val) {
			$stat = @stat($val);
			if ($stat['mtime'] > $newestModification)
				$newestModification = $stat['mtime'];
			$DirectorySize += $stat['size'];
		}
		return array('mtime' => $newestModification, 'size' => $DirectorySize);
	}
	
	/** Counts number of elements in a directory
	 * @param string $directory
	 * @return interger
	 */
	public function countElementsInDir($directory) {
		return count($this -> rglob('*', $directory, 0));
	}
	
	/**
	 * if search query was detected in URL or not, get the elements for the search/dirlist query
	 * @return array
	 */
	public function requestElements () {
		if (!empty($this -> searchTag) && $this -> searchEnabled) {
			$this -> getSearchElements();
		} else {
			$this -> getDirectoryElements();
		}
	}

	/**
	 * Returns all files in a directory
	 * @param string $directory [optional]
	 * @return array
	 */
	public function getDirectoryElements ($directory = '') {
		if (!$directory) {
			$directory = $this -> directory;
		}
		$directory = $this -> escapePathBrackets($directory);
		if ($this -> listHidden) {
			$elements = glob($directory.'{,.}*', GLOB_BRACE); //list hidden files ("."-prefix)
		} else {
			$elements = glob($directory.'*');
		}
		$elements = $this -> getElementInfo($elements);
		$this -> elements = $elements;
		
		return $elements;
	}
	
	
	/**
	 * Searches for $search and returns a array with paths to found files/directories
	 * @param string $search [optional]
	 * @param string $directory [optional]
	 * @return array
	 */
	public function getSearchElements($search = '', $directory = '') {
		if (!$search) {
			$search = $this -> searchTag;
		}
		if (!$directory) {
			$directory = $this -> directory;
		}
		
		if (!empty($search)) {
			// Search for term in current directory
			$search = htmlspecialchars($this -> searchTag);
			# thx to <http://de3.php.net/manual/en/function.glob.php#74109>
			if (!$this -> searchCaseSensitive)
				$search = $this -> iregcase($search);
			$found = $this -> getElementInfo($this -> rglob("*$search*", $directory, 0));
			$this -> elements = $found;
			return $found;
		} else {
			return false;
		}
	}
	

	public function getElementInfo($elements, $directory = false) {
		if (!$directory)
			$directory = $this -> directory;
		$list = array();
		if (is_array($elements)) {
			foreach ($elements as $path) {
				if (is_file($path) || is_dir($path)) {
				    if ($this -> ignored($path) === false) {
				    	
				    	
				    	/*
				    	 * path => "./examples/sub directory"
				    	 * urlpath => "?directory=./examples/sub%20directory/"
				    	 * or with mod_rewrite: urlpath => "./examples/sub%20directory"
				    	 */
				    	
				    	// If not in the directory where pdirl is
				    	if (!empty($directory)) {
				    		// The reason why pdirl is not compatible with PHP <5
				    		// <http://de.php.net/manual/function.strpos.php>
					    	if (strpos($path, $directory) === 0 AND $this -> modRewrite) {
				    			/*
				    			 * If you search with glob e.g. in "examples" you will get paths like "examples/foobar.mp3"
				    			 * The path should be shortened.
				    			 */
					    		$item['path'] = substr_replace($path, "", 0, strlen($directory));
					    	} else {
								/*
								 * Okay, you will probably ask why I save the path of the file/folder
								 * Because of the sorting options - files and folders should be sorted according to their name and not
								 * their path. In version 0.9.2beta files and folders were sorted according to their path (only in search mode).
								 */
							    $item['path'] = $path;
						    }
				    	} else {
				    		// Take absolute path
				    		$item['path'] = $path;
				    	}
				    	
				    	// if you have brackets etc. e.g. '<item>' in your name htmlspecialchars would distort the name it wouldnt be accessible,
				    	// so they are seperate variables for (display)name and pathname which is encoded with urlencode
				    	// There is also 'urlpath' which is especially for using pdirl without modrewrite and adds (if needed) ?directory= before the url
				    	$path_array = explode("/", $item['path']);
						$item['name'] = array_pop($path_array);
						$item['directory'] = is_dir($path);
						$item['location'] = '';
						// pdirl needs location only when you search or
						// modRewrite is on
						if ($this -> modRewrite || $this -> getSearchTag() || !$item['directory']) {
						    $item['location'] = implode('/', $path_array);
							$item['location'] .= '/';
							// Makes location path more secure
							$item['location'] = $this -> rewritePath($item['location']);
						}
					    // urlencode on everything except the slashes
				    	$item['urlpath'] = $this -> encode($item['location'].$item['name']);
						$stat = array('size' => 0, 'mtime' => 0);
						$item['numberofelements'] = 0;
						$item['countonly'] = false;
						if ($item['directory']) {
							if ($item['readable'] = is_readable($path) || is_executable($path)) {
								if ($this -> countElements) {
									$item['numberofelements'] = $this -> countElementsInDir($path) - 1;
									$item['countonly'] = true;
								} else {
									$stat = $this -> rstat($path);
								}
							}
							$item['urlpath'] = $this -> urlPath($directory.$item['location'].$item['name']);
						} else {
							// stat() is slow, but faster than using filemtime() & filesize() instead.
							$stat = @stat($path);
							// if stat() returns an error, we ignore it and convert it to false via the @
							$item['readable'] = (bool) $stat;
						}
						
						$item['path'] = urlencode($item['path']);
						$item['name'] = htmlspecialchars($item['name']);
						$item['locationurl'] = $item['location'];
						$item['location'] = htmlspecialchars($item['location']);
						
						
						/*
						 * Ok, fine, what did I do with $item['readable']? I mark files and directories,
						 * that are not accessible with $item['readable'] = false;
						 */
						$item['bytes'] = $stat['size'];
						$item['mtime'] = $stat['mtime'];
						// Add this items file size to this folders total size
						$this -> totalSize += $item['bytes'];
						$item['type'] = $this -> getType($path);
						$list[] = $item;
				    }
				}
			}
			$this -> elements = $list;
		}
		return $list;
	}
	
	/**
	 * allowing brackets in directory names
	 * http://sockittoemjt.tumblr.com/post/20481660360/managed-to-come-across-your-tumblr-whilst-googling-for
	 * https://bugs.php.net/bug.php?id=33047
	 * returns directory path, but only that the brackets are escaped.
	 * @return string
	 */
	private function escapePathBrackets($directory = false) {
		if ($directory == false) {
			$directory = $this -> directory;
		}
		$escapedBracketPath = str_replace('[', '\[', $directory);
		$escapedBracketPath = str_replace(']', '\]', $escapedBracketPath);
			
		/* Next, replace all "escaped" brackets with brackets like so */
		$escapedBracketPath = str_replace('\[', '[[]', $escapedBracketPath);
		$escapedBracketPath = str_replace('\]', '[]]', $escapedBracketPath);
		return $escapedBracketPath;
	}
	
	
	/**
	 * returns pdirl::elements
	 * @return array 
	 */
	public function getElements () {
		if (empty($this -> elements))
			return false;
		else
			return $this -> elements;
	}
	
	/**
	 * returns pdirl::directory
	 * @return string
	 */
	public function getCurrentDirectory() {
		if (!isset($this -> directory))
			return false;
		else
			return $this -> directory;
	}
	
	
	/**
	 * returns pdirl::scriptPath
	 * @return string
	 */
	public function getScriptPath() {
		if (!isset($this -> scriptPath))
			return false;
		else
			return $this -> scriptPath;
	}
	
	/**
	 * returns the directory of pdirl::scriptPath
	 * @return string
	 */
	public function getScriptDirectory() {
		if (!isset($this -> scriptPath))
			return false;
		else
			$scriptDirectory = dirname($this -> scriptPath);
			//if the script directory path is only consists of a slash, give out an empty string
			if ($scriptDirectory == '/')
				return '';
			else
				return $scriptDirectory;
	}
	
	
	/**
	 * returns pdirl::host
	 * @return string
	 */
	public function getHost() {
		if (!isset($this -> host))
			return false;
		else
			return $this -> host;
	}
	
	
	/**
	 * return pdirl::search
	 * @return string
	 */
	public function getSearchTag() {
		if (!isset($this -> searchTag))
			return false;
		else
			return $this -> searchTag;
	}
	
	
	/**
	 * Returns number of elements
	 * @return interger
	 */
	public function getNumberOfElements() {
		if (!isset($this -> elements))
			return false;
		else
			return count($this -> elements);
	}
	
	
	/**
	 * return pdirl::totalSize
	 * @return interger
	 */
	public function getTotalSize() {
		if (!isset($this -> totalSize))
			return false;
		else
			return $this -> totalSize;
	}
	
	/**
	 * Returns "asc", if $sorting is "desc". Returns "desc", if it's "asc".
	 * @param $sorting
	 * @return string
	 */
	public function adesc($sorting) {
		if ($sorting == "SORT_ASC")
			return "SORT_DESC";
		elseif ($sorting == "SORT_DESC")
			return "SORT_ASC";
		else
			return "SORT_ASC";
	}
	
	public function getSortOrder($r = false) {
		if (!isset($this -> sortOptions)) {
			return false;
		} else {
			if ($r) {
				return $this -> adesc($this -> sortOptions['sort']);
			} else {
				return $this -> sortOptions['sort'];
			}
		}
	}
	
	public function getSortKey() {
		if (!isset($this -> sortOptions))
			return false;
		else
			return $this -> sortOptions['key'];
	}
	
	private function validizeSortOrder($order) {
		if (in_array($order, array('SORT_ASC', 'SORT_DESC', 'SORT_REGULAR', 'SORT_NUMERIC', 'SORT_STRING'))) {
			return $order;
		} else {
			return 'SORT_ASC';
		}
	}
	
	private function validizeSortKey($order) {
		if (in_array($order, array('name', 'path', 'location', 'bytes', 'mtime', 'type'))) {
			return $order;
		} else {
			return 'name';
		}
	}
	
	private function sessionSort() {
		if ($this -> isUserSortActive()) {
			session_start();
			if (isset($_SESSION['sortorder'])){
				$this -> sortOptions['sort'] = $_SESSION['sortorder'];
			}
			if (isset($_SESSION['sortkey'])){
				$this -> sortOptions['key'] = $_SESSION['sortkey'];
			}
			if (isset($_GET['sortorder']) || isset($_GET['sortkey'])) {
				if (isset($_GET['sortorder'])){
					$_SESSION['sortorder'] = $this -> validizeSortOrder($_GET['sortorder']);
				}
				if (isset($_GET['sortkey'])){
					$_SESSION['sortkey'] = $this -> validizeSortKey($_GET['sortkey']);
				}
				if (!empty($this->searchTag)) {
					header('Location:http://'.$this->host.$this -> getScriptPath()."?search=".$this->searchTag."&directory=".$this -> directory);
				} else {
					header('Location:http://'.$this->host.$this->getScriptDirectory()."/".$this -> directory);
				}
			}
		}
	}
	
	public function isUserSortActive() {
		return $this -> userSort;
	}
	
	public function isModRewriteActive() {
		return $this -> modRewrite;
	}
}

?>