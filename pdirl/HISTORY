pdirl

1.0.4 (05-17-2012)
 - It feels like seeing an old friend again!
 - Users can now sort elements!
 - Directories sorted by elements, when you sort by size
 - No backslashes anymore for expressions in ignore list
 - Home button active when search
 - Some style changes
 - New translations: Simplified Chinese, French, Greek, Hungarian, Polish, Romanian and Turkish
 
 	Bugfixes:
 	 - fixes #759180 - brackets can now be used in directory name, new method getEscapedBracketPath
 	 - Search in bracket directories is now also possible
 	 - fixes #815701 - "undefined offset" bug
 	 - fixes #942879 - documentation error
 	 - Output buffer for MIME types will be now cleaned
 	 - Number of elements in directories reduced by one
 	 - Search is now in selected directory

1.0.3r44 (06-14-2010)
	Bugfixes:
	 - there's now information when you go over the icons
	 - title now shows not only hostname
	 - '.' and '..' are ignored now (they were visible if you turned on the option to show unvisible files)
	 - file type icon path did not contain ".png", so on some servers the server successfully found it but someothers it did not.
	 - didn't check in pdlc::rglob() if glob returns an error, or (sometimes) when some systems do not return an array but false, even if they is not an error.
	 - fixed bug that all not-text-files are of the "default"-type
	 - directories which names that contained html tags were unavaible via search
	 - fixes bug #592880  (breadcrumb url is doubled)
	 - fixes bug, that directories urls are doubled because of the location 
	 	(location doesn't apply on directories listed w/o mod_r.. anymore)

 - replaced deprecated sql_regcase with <http://de3.php.net/manual/en/function.sql-regcase.php#86011>
 - new file type "default" - "Unknown file type"
 - added finfo and unix file command functionality
 - added "locationurl" as element detail
 - "count elements in directory only"-feature to reduce lags when checking the size of a directory (is on by default)
 - if they are no files and countOnly is activated we will remove the "Last modification" column
 
1.0.3r33 (04-01-2010)
	Bugfixes:
	 - files not avaible w/o mod_rewrite/htaccess <https://bugs.launchpad.net/pdirl/+bug/537341>
	 - breadcumb repeats <https://bugs.launchpad.net/pdirl/+bug/537344>
	 - wrong search path "index.php/" without modrewrite
	 - wrong path to stylesheet

 - removed PHP short tags
 - first link in breadcrumb points to parent directory and not to index.php of pdirl
 - added Russian and Spanish translations

1.0.3r25 (02-25-2010)
	
	Bugfixes:
	 - Fixed mysterious bug, that pdirl hides sometimes files that shouldn't be hidden.
	 - Japanese characters, umlauts, acute accents and other characters will be displayed correctly now
	 - Their links will work, too ;-)
	 - '..', '/', '.', '.?', '.*' don't work anymore (Security vulnarability)
	 - Proper display of total filesize
	 - Fixed XSS vulnerarability of directory names like "<script>Dirname"
	 - GNU gettext works now

 - Sorting is now case insensitive
 - the Pdirl Class has its own file now -> pdlc.class.php (Pdirl Directory Listing Class)
 - switched to GNU gettext
 - pdirl doesn't count in decades anymore
 - reorganized pdirl, not more 2 files. Everything pdirl-related in .pdirl directory
 - pdirl also works without mod_rewrite
 - pdirlView is no longer a class
 - pdirl class is now called pdlc, the whole program (pdlc and gui functions) are called pdirl
 - parent directories of script directory aren't linked in breadcumb
 - ignoring files/dirs (and filetypes etc.) with regex is possible now
 - "parent directory" and "home" buttons are disabled in "./"

1.0.2alpha (08-10-2009)

	Bugfixes:
	 - If there's no trailing slash, one will be added <http://forums.digitalpoint.com/showthread.php?p=6251483>

 - Search works
 - Files and directories, that are not accessible are marked with a lock-icon
 - Stats for directories *milestone*


1.0.1alpha (07-26-2009)

 - New and rewritten pdirl class
 - Licensed under MIT/X11-License
 - PHPDocumentor-compatible
 - Lists hidden files (or not if you wish ;-))
 - New icons (Tango only, because they are Public Domain)
 - pdirl shows type-description on mouseover
 - no longer based on PHPDL


0.9.3 (07-15-2009)

	Bugfixes:
	 - If you have files but no directories in a folder you won't see the total size of them.
	 - Wrong title tag/breadcrumb() function
	 - Search order according to path, not to file name
	 
 - Icons are cached
 - Breadcrumb navigation of search item location


0.9.2beta (07-13-2009)

	Bugfixes:
	 - When you put pdirl on a webspace that can't detect MIME-types correctly it shows you some files with an "unknown"-type
 
 - <title>-tag
 - Search (glob-based)
 - the directory listing is glob-based, too.
 - Tango icons
 - You will get the file icons faster (before: "?image=path", now: "?image=type")


0.9.1 (06-04-2009)

 - These file extensions are now assigned (or moved) to following types:
   - archives: .deb, .bz, .bz2 
   - application: .sh
   - document: .ppt, .pptx, .xml, .odf, .odt, .ods, .odp, .odg, .odc, .odi
   - web: .tpl
 - Added MIME-type support (Support for files without extensions or file extensions that are not in our list)!
 - All translations are now in a single file.
 - Fixed a bug in multisort() <http://us.php.net/manual/en/function.array-multisort.php#85166>
 - Fixed size display.
 
 
0.9beta (04-29-2009)

 - breadcumb navigation
 - directory listing in subdirectories
 - "parent directory"-link
 - faster .htaccess
