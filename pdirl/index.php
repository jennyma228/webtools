<?php
/**
 * "pdirl" (PHP Directory Listing) is a tiny directory lister program.
 * It is meant to be better looking and more useful than the directory
 * listings produced by Apache.
 * 
 * Documentation written in bad english.
 *
 * @name	pdirl
 * @version	1.0.3beta
 * @author	Rene Zuewig <rene@newroots.de>
 * @uses	Tango Icon Theme 0.8.90 <http://tango.freedesktop.org>
 * @link	http://pdirl.newroots.de/
 * @example	http://pdirl.newroots.de/examples
 * @license	http://opensource.org/licenses/mit-license.php MIT/X11 License
 */

	require ('.pdirl/config.php');
	// File search, dir listing class, with some usefull functions
	require ('.pdirl/pdlc.class.php');	
	require ('.pdirl/guifunctions.php'); // Function for the pdirl gui template
	
	// initialise pdlc
	$pdlc = new pdlc($conf); // needs $conf, which was defined in config.php
	// if mod_rewrite is activated, rewrite the URL
	modRewriteRedirect();
	// get the elements for the search/dirlist query
	$pdlc -> requestElements();
	// sort them, for sorting see config
	$pdlc -> sortElements();
	$elements = $pdlc -> getElements();
	
	/* So, now the ehm.. 'GUI' part...
	 * At this line you probably would ask, why there is no GUI class or
	 * something, but just a bunch of more or less useful functions.
	 * The best reason is, that the html template part of pdirl was the best
	 * example to show how syntax higlighting can look like a code LSD trip.
	 * Everytime you would have to use "$pdirl->" and "$pdirlView ->" etc...
	 * The other reason is, that the code and the template weren't
	 * understandable, so I made them simpler.
	 * For GUI configuration use $GUIconf istead of $conf.
	 * $GUIconf has default values if they are empty in $conf
	 */
	$GUIconf = defaultGUIConfiguration($conf);
	
	// Set GNU gettext to the current language
	if (!setLanguage($GUIconf['language']))
		die ('No translation avaible for "' . $GUIconf['language'] . '". Please	look at <a href="https://translations.launchpad.net/pdirl">translations.launchpad.net/pdirl</a>\ if pdirl was translated in your language yet.');
	
	// Get elements and add human readable variables e.g. size, mtime etc.
	addHumanInformation($elements);
	// returns an array for the directories and another for the files.
	seperateDirectoriesAndFiles($elements);
	/* if they are no files and countOnly is activated we will remove
	 * the "Last modification" column
	 */
	$hideMTime = (empty($elements['files']) && $pdlc -> countElements);
	// glues the aforementioned arrays
	if (!empty($elements['directories']) || !empty($elements['files'])) {
		$elements = array_merge($elements['directories'], $elements['files']);
	} else {
		$elements = array();
	}
	
	// include template
	require ('.pdirl/templates/'.$GUIconf['template'].'/index.php');
	
	/*
	// for debugging ;-)
	echo "scriptDirectory(): ".scriptDirectory()."<br />";
	echo "currentDirectory(): ".currentDirectory()."<br />";
	echo "<pre>";
	print_r($elements);
	echo "</pre>";*/
?>