<?php
/*
 * NOTICE: Only an empty string '' means, that the default/dynamic value will be chosen!
 * 0 means off, false means no value. If you want to turn something off, then please use 0 instead of false or ''.
 */

/*
 * "debug mode"
 * <http://php.net/manual/en/function.error-reporting.php>
 * If something's not working, remove the both slashes before error_reporting and report the bug to: 
 * <https://bugs.launchpad.net/pdirl>
 */
//error_reporting(E_ALL);

$conf = array (
	
	/* Language must be in ISO 639 format <http://en.wikipedia.org/wiki/ISO_639>
	 * e.g. for French type 'fr_FR', for Italian 'it_IT' or for Simplified Chinese 'zh_CN' etc.
	 * If you are running pdirl on ubuntu, run "sudo locale-gen xx_YY.UTF-8", xx = Language, YY = Country
	 * If you want to use English, fill in 'en_GB' or leave the field blank ''. */
	'language' => 'en_GB',

	/* The domain that will be shown in the title and the breadcrumb navigation.
	 * You can fill in something like "files.yoursite.org"
	 * Leave it blank to automaticly detect the host with $_SERVER['HTTP_HOST'] */
	'host' => '',

	'ignore' => array( // files/directories pdirl should ignore
		"index.php",
		".htaccess",
		".pdirl"
	),
	
	'listhidden' => '', // Show hidden files
	
	'searchenabled' => '',
	
	'searchtag' => '', // Search string, leave it blank it will change through user input.
	
	/* What does "case-sensitive" mean?
	 * If you search for "a", you will get in a case-insensitive search "amarok" and "Amarok",
	 * in a case-sensitive only "amarok". Case sensitive means that you have to mind the capitalization.
	 * Default value is case-insensitive, $conf['searchcasesensitive'] = false;. */
	'searchcasesensitive' => '',
	
	'searchhidden' => '', // Show hidden files in search

	/* do not check size and modification date of a directory, 
	 * but just the number of elements in it 
	 * it is on by default */
	'countelements' => '',
	
	/* How pdirl should sort the files.
	 * You can sort according to:
	 * 	file/directory name - 'name'
	 * 	path - 'path'
	 * 	location - 'location'
	 * 	size - 'bytes'
	 * 	last modification time - 'mtime'
	 * 	type - 'type'
	 * Sorting order:
	 * 	ascending - 'SORT_ASC'
	 * 	descending - 'SORT_DESC'
	 * 	regular - 'SORT_REGULAR'
	 * 	numeric (strings, too) - 'SORT_NUMERIC'
	 * 	as a string (intergers, too) - 'SORT_STRING'
	 * 
	 * default: array('key' => 'name', 'sort' => 'SORT_ASC')
	 */
  'key' => 'mtime',
	'sort' => '',
		
	/* Allow the user to sort elements
	 * on by default
	 */
	'usersort' => '',
	
	/* Why the hell are MIME-types and extensions mixed here?
	 * Because it's the fastest and simpliest way and very often the MIME-type part after the slash is the same as the file extension.
	 * e.g. application/zip and .zip */
	'types' => array (
		'text'		=> array('ppt', 'pptx', 'doc', 'docx', 'txt', 'rtf', 'odf', 'odt', 'ods', 'odp', 'odg', 'odc', 'odi', 'nfo', 'xml', 'pdf', 'x-office-document'),
		'package'	=> array('7z', 'dmg', 'rar', 'sit', 'zip', 'bzip', 'gz', 'tar', 'deb', 'bz2', 'bz', 'x-compressed-tar', 'iso'),
		'program'	=> array('exe', 'msi', 'mse', 'sh', 'bat', 'x-executable', 'x-ms-dos-executable'),
		'web'		=> array('js', 'html', 'htm', 'xhtml', 'tpl', 'jsp', 'asp', 'aspx', 'php', 'css'),
		'video'		=> array('x-shockwave-flash', 'mv4', 'bup', 'mkv', 'ifo', 'flv', 'vob', '3g2', 'bik', 'xvid', 'divx', 'wmv', 'avi', '3gp', 'mp4', 'mov', '3gpp', '3gp2', 'swf', 'ogv'),
		'audio'		=> array('spx', 'ogg', 'oga', 'mp3', 'wav', 'midi', 'mid', 'aac', 'wma', 'xm'),
		'image'		=> array('ai', 'bmp', 'eps', 'gif', 'ico', 'jpg', 'jpeg', 'png', 'psd', 'psp', 'raw', 'tga', 'tif', 'tiff'),
	),
	
	'template' => '', // The template for pdirl, default value is 'default' ;-)
	
	'icons' => '', // Which icons to use, default value is 'default'
	
	/*
	 * modrewrite makes more readable URLs.
	 * If you leave it blank, pdirl detects if its turned on and activates nicer urls
	 */
	'modrewrite' => '',
	
//	/* The directory you want to list.
//	 * You can fill in something like "downloads/" or leave it blank (default).
//	 * If you leave it blank it will dynamicly select the directory that the user requested.
//	 * It is as I think tecnically impossible to implement a redirection like "/test" to "downloads/test"
//	 * because you can't implement variables of the config into htaccess */
//	'directory' => 'examples/',

//	'scriptpath' => '', // Relative path to this script
)
?>
