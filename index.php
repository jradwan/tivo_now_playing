<?php
/*
 * 20130915 VicW (HomeUser)
 *  removed un-used code and private data
 *  moved auto_size tracking files from "tmp" to "log"
 *
 * 2013,2013 updates VicW (HomeUser)
 *  Auto size adjustment for drives
 *  Archiving "now playing" lists
 *  Sorting of summary table
 *  Added field deleted to summary table (space not used by programs or suggestions)
 *  Combined now playing for all TiVos
 *  URL path allowing generation when HTML files are not published on local computer
 *  Other changes that I have forgot about.
 *  I added something to the program information like series ID.
 *
 * 20170429 jradwan (windracer)
 *  added 'back to Summary' link at top of page
 *  added 'expand/collapse all' label to plus icon at top of page
 *  moved box images to separate line, removed forced resizing
 *  added box images to ALL page listing and separators between box listings
 *  filtered out Rovi text in program descriptions
 *  general code cleanup
 *  minor text/label changes
 *
 * 20170503 jradwan (windracer)
 *  made archiving/logging a configurable option
 *
 * 20170505 jradwan (windracer)
 *  updates for new graphics
 *  removed old gfxicons option
 *  indent program details block (css)
 *  clean up totals block (css)
 *
 * 20170516 jradwan (windracer)
 *  add sortable table with episode/series info
 *  more graphical updates, css tweaks
 *
 * 20170523 VicW TiVoHomeUser (homeuser)
 *  Added Sortable tables grouped by seriesid by putting everything
 *  in an indexed array $folders adding additional rows every time the same seriesid
 *  is encountered.
 *
 * 20170527 VicW (TiVoHomeUser)
 *  Moved write for _track_drive_size.log inside the size check block.
 *  Now the log file is only written to when the computed storage size has changed.
 *
 * 20170528 VicW
 *  Link to folders from All Suggestions
 *  added tivo short name to message in Suggestions link
 *
 * 20170531 VicW
 *  Sortable tables grouped by seriesid for each DVR
 *  Added $LASTUPDATE for reference in summary header
 *  summary TiVo name now has (Grouped) link to Grouped Now Playing
 *
 * 20170602 VicW
 *  Added link to TiVoHomeUser's branch at github to the bottom of the summary page.
 *  06/-3  modified link's verbage
 *
 * 20170606 VicW
 *  Swapped Groups and TiVo name in summary header
 *
 * 20170608 VicW
 *  Changed Grouped to Groups
 *
 * 20170610 VicW
 *  changed link to github in summary to include the master branch
 *
 * 20170610 jradwan (windracer)
 *  format version line on summary page
 *  use folder icon for groups link
 *
 * 20170615 VicW  (TiVoHomeUser)
 *  Cleanup some Html syntax errors missing closing tags
 *  Fixed the corrupt table with missing DVR(s)
 *  Added wget timeouts to tivo_settings.php wgetpath
 *  Group displays off-line for the off-line DVR same as NowPlaying
 *  Total drive size in summary now excludes off-line DVRs in its calculations
 *
 * 20170618 Vicw
 *  Collapsible Groups working
 *
 * 20170619 VicW
 *  Added new old dates to collapsible headers
 *  TODO toggle All not working with sort tables
 *
 * 20170620 VicW
 *  Fixed some typos and removed obsolete commented out code
 *  Unset the new $group arrays for each tivo loop
 *  Removed the series count from folders/groups easily confused number of episodes
 *  fix for olddate uninitialized null would never test older initialized both old and new JIC
 *
 * 20170622 VicW
 *  Start of tool-tips using simple html (no formatting)
 *
 * 20170623 VicW
 *  Tool Tips for Groups
 *   Episode title	Episode Description
 *   Program ID		Series ID
 *   Record Date	Channel and Duration
 *   Title with		Series ID
 *   Status Icons	Status description
 *   TiVo			Title, Model, and Size
 *   removed SeriesID from table
 *   Check for empty SeriesID labeled table as "Movies and Specials" (Yellow Highlighted)
 *   Modified Folders to match Groups
 *   replaced folders $sort_footer with $allfooter to include totals
 *
 * 20170624 VicW
 * 	The re-used headers expand/collapse all starts toggling with id 0
 *  rather then modify the 2 headers $series_count++ was moved to end of loop
 *
*/
$LASTUPDATE = "20170624";

ini_set("max_execution_time", "180");
ini_set("error_log", "tivo_errors.txt");

error_reporting(0);

if (stristr($_ENV["OS"], "Windows") !== false) {
	define("delim", "\\");
} else {
	define("delim", "/");
}

$binpath = "bin" . delim;  // defined here to find the settings file
require_once($binpath . "tivo_settings.php");
require_once($binpath . "class_tivo_xml.php");

// make a new path for the XML files if needed
if(!file_exists($xml_path)) {
	if(mkdirV4($xml_path, 0777, true)) print("Created XML directory\n");
	else print("Error creating XML directory: ". $xml_path."\n");
}

// set up archiving-related variables and paths if enabled
if($nplarchives == 1) {
	//*TODO* set an optional limit of number of archives to make
	// each month create a new archive folder.
	$year = Date('Y');  // "2013";
	$month = Date('M'); // "Feb";

	// $arch is directory to save a copy of archived copy of the NowPlaying html file
	// TODO eliminate sug_log_path and save the summary and drive size with the archive
	$sug_log_path = "log"  . delim . $year . delim . $month . delim;
	$arch_path    = "arch" . delim . $year . delim . $month . delim;

	// make a new path for the logs if needed
	if(!file_exists($sug_log_path)) {
		if(mkdirV4($sug_log_path, 0777, true)) print("Created Log directory\n");
		else print("Error creating Log directory: ". $sug_log_path."\n");
	}

	// make a new path for the archives if needed
	if(!file_exists($rootpath . $arch_path)) {
		if(mkdirV4($rootpath . $arch_path, 0777, true)) print("Created Archive directory\n");
		else print("Error creating Archive directory: ". $rootpath . $arch_path ."\n");
	}

	// $archdate is used to create a unique name for the archived nowplaying HTML file
	$archdate = date(YmdHi); // 2012122015 YYYYMMDDHHMM timestamp appended to archived nowplaying
}

// PHP does not type and defaults to an empty char or null, so force a number 0 (zero)
$alltotalsize = 0;
$alltotalsuggestions = 0;
$alltotalnumsuggestions = 0;
$allfreespace = 0;
$alltotalitems = 0;
$alltotallength = 0;
$all_size_gb = 0;
$icnt = 0; // make a unique ID to enable toggle on page with all TiVos

// make a header for the summary page
$sum_header .= "<!DOCTYPE html>\n";

//$sum_header .= "\nHello from SUM_HEADER $icnt \n";

$sum_header .= "<html><head>\n";
$sum_header .= "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=UTF-8\">\n";
$sum_header .= "<LINK REL=\"shortcut icon\" HREF=\"" .$images. "favicon.ico\" TYPE=\"image/x-icon\">\n\n";

$sum_header .= "<title>TiVo Disk Space - Summary</title>
    <link href=\"" . $summary_css . "\" rel=\"stylesheet\" type=\"text/css\">";
$sum_header .= "\n</head>\n<body>\n";

$sum_header .= "<h2><img src=images/tivo_logo.png ><br>Last Updated: " . date("F j, Y, g:i a") . " </h2>\n";
$sum_header .= "<script src=\"" . $mysorttable . "\" type=\"text/javascript\"></script>\n";

// start of sortable summary table
$sum_table .= "<h4>\n<br><table id=\"Summary\" class=\"sortable\" border=\"2\" cellspacing = \"2\" cellpadding = \"4\" align = \"center\" >\n";
$sum_table .= " <tr>
		<th> TiVo </th>
		<th class=\"sorttable_numeric\"> Drive Size </th>
		<th class=\"sorttable_numeric\"> Used Space </th>
		<th class=\"sorttable_numeric\"> Available Space </th>
		<th class=\"sorttable_numeric\"> Percent Free </th> ";
if($nplarchives == 1) {
	$sum_table .= "<th> Suggestions </th>";
}
$sum_table .= "</tr>\n";
// end of header for summary page

// header for full list of programs from all TiVos
$allheader .= "<!DOCTYPE html\n>";

//$allheader .= "\nHello from ALLHEADER $icnt\n";

$allheader .= "<html><head>\n";
$allheader .= "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html charset=UTF-8\">\n";
$allheader .= "<LINK REL=\"shortcut icon\" HREF=\"" .$images. "favicon.ico\" TYPE=\"image/x-icon\">\n\n";
$allheader .= "<title>" . "All TiVos - Now Playing" . "</title><link href=" . $mycss . " rel=\"stylesheet\" type=\"text/css\" ></head>\n\n";
$allheader .= "<body onload=\"init()\">\n";

// link back to Summary page at top
$allheader .= "<div class=\"dura\"><a href=\"" . $myurl . "summary.htm\" >&larr;&thinsp; back to Summary </a></div>\n";

$allheader .= "<div class=\"dura\"><a href=\"" . $myurl. "sort.htm\" >&#8645;&nbsp; sortable episode list </a></div>\n";
$allheader .= "<div class=\"dura\"><a href=\"" . $myurl. "folders.htm\" >&#8645;&nbsp; sortable episode list (grouped) </a></div>\n";

// link to expand/collapse all entries on the page
$allheader .= "<div class=\"dura\" id=\"plusminusAll\" onclick=\"toggleAll(" . $icnt . ")\" >&#8597;&nbsp;&thinsp; expand/collapse all </div>\n";

$allheader .= "<h2><img src=images/tivo_logo.png ><br>Last Updated: " . date("F j, Y, g:i a") . " </h2>\n";

// include javascript tivo_now_playing.js
$allheader .= "<script id=\"imagepath\"> \"" . $images . "\" </script>\n";
$allheader .= "<script src=" . $mytjs . " > </script>\n";

$allcontent = "";

// header for sortable episodes page
$sort_header .= "<!DOCTYPE html>\n";

//$sort_header .= "\nHello from SORT_HEADER $icnt\n";

$sort_header .= "<html><head>\n";
$sort_header .= "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=UTF-8\">\n";
$sort_header .= "<LINK REL=\"shortcut icon\" HREF=\"" . $images . "favicon.ico\" TYPE=\"image/x-icon\">\n\n";
$sort_header .= "\n<title> All TiVos - Sortable Episode List </title><link href=\"" . $summary_css . "\" rel=\"stylesheet\" type=\"text/css\"></head>\n\n";
$sort_header .= "<body onload=\"init()\">\n";

// links between our own pages
$sort_header .= "<div class=\"dura\"><a href=\"" . $myurl . "summary.htm\" >&larr;&thinsp; back to Summary </a></div>\n";
$sort_header .= "<div class=\"dura\"><a href=\"" . $myurl . "alldvrs.htm\" >&larr;&thinsp; back to All TiVos - Now Playing </a></div>\n";
$sort_header .= "<div class=\"dura\"><a href=\"" . $myurl . "sort.htm\" >&#8645;&nbsp; sortable episode list </a></div>\n";
$sort_header .= "<div class=\"dura\"><a href=\"" . $myurl . "folders.htm\" >&#8645;&nbsp; sortable episode list (grouped) </a></div>\n";

// link to expand/collapse all entries on the page
$sort_header .= "<div class=\"dura\" id=\"plusminusAll\" onclick=\"toggleAll(" . $icnt . ")\" >&#8597;&nbsp;&thinsp; expand/collapse all </div>\n";

$sort_header .= "<h1><img src=images/tivo_logo.png > <br> All Now Playing</h1>\n <h2>Last Updated: " . date("F j, Y, g:i a") . " </h2>\n";
$sort_header .= "<script id=\"imagepath\"> \"" . $images . "\" </script>\n";
$sort_header .= "<script src=\"" . $mytjs . "\" > </script>\n";
$sort_header .= "<script src=\"" . $mysorttable . "\" type=\"text/javascript\"></script>\n";

// header for sortable table of episodes, series ids, etc.
$sort_table .= "<br>\n<h4>\n<table id=\"Summary\" class=\"sortable\" border=\"1\" cellspacing = \"2\" cellpadding = \"4\" align = \"center\" >\n";
$sort_table .= " <tr>
        <th class=\"sorrtable\"> TiVo </th>
        <th class=\"sorttable\"> Series Name </th>
        <th class=\"sorttable_alpha\"> Episode </th>
        <th class=\"sorttable\"> Program ID </th>
        <th class=\"sorttable\"> Series ID </th>
        <th class=\"sorttable_numeric\"> Record Date </th>
 </tr>\n";

// loop for each TiVo defined in the array defined in tivo_settings.php
foreach($tivos as $tivo) {
	unset($tivoarray, $totalsize, $totallength, $customicon, $sc, $totalitems, $freespace, $rssheader, $rsscontent, $rssfooter, $header,
			 $content, $footer, $fp1, $fp2, $totalsuggestions, $totalnumsuggestions, $percent_free, $fpt, $auto_size_gb, $recording_suggestion,
			 $sug_header, $sug_table, $sug_footer, $sug_html_file, $sug_log_file, $sug_html_file, $archNowPlaying, $nowPlaying,
			 $groups, $groups_series, $groups_count, $groups_newdate, $groups_olddate);
	$icnt=0; // TEMPOARY FIX FOR NOW

	// collect the data for the TiVo
	$tivoxml = new Tivo_XML();
	if($xml_path == "") $xml_path = "xml" . delim;
	$tivoxml->setOpt("xml_path",$xmlpath);
	$tivoxml->setOpt("wget", wgetpath);
	$tivoxml->setOpt("mak", $tivo['mak']);
	$tivoxml->setOpt("host", $tivo['ip']);
	$tivoxml->setOpt("dvr", $tivo['name']);

	// PHP does not type and defaults to an empty char or null, so force a number 0 (zero)
	$totalsize = 0;
	$totallength = 0;
	$totalitems = 0;
	$freespace = 0;
	$totalnumsuggestions = 0;
	$totalsuggestions = 0;

	// $nowPlaying Web page for the Now Playing List
	$nowPlaying 	= $tivo['name'] . "_nowplaying.htm";

	// now Playing for groups html file
	$nowPlayingGroups 	= $tivo['name'] . "_group.htm";
	$summaryhtm = "summary.htm";
	$foldershtm = "folders.htm";	// All grouped by seriesid


	if($nplarchives == 1) {
		// use suggestions to track free space vs unused space
		// $sug_html_file Web page with table tracking number of suggestions and free space
		$sug_html_file  = $sug_log_path . $tivo['name'] . "_suggestions.html";

		// $sug_log_file the big file with running history
		$sug_log_file   =  $sug_log_path . $tivo['name'] . "_track_suggestions.log";

		// $archNowPlaying copy of $nowPlaying for archiving Web pages
		$archNowPlaying = $arch_path . $tivo['name'] . "_" . $archdate . "_nowplaying.htm";
	}

	// both requested and suggestions show now_recording when in progress. any in-progress
	// recordings before the first non-suggestion should be counted as used space
	$recording_suggestion = false;

	$tivoxml->init();
	if($tivoxml->getErr() == false)
		$tivoarray = $tivoxml->parseTiVoXML();

	if($tivoxml->getErr() == false)
		if ($dorss == 1) {
		include($binpath . "rss.php");	// rss code moved to external file rss.php
	}

	$header .= "<!DOCTYPE html>\n";
//	$header .= "\nHello from HEADER $icnt\n";
	$header .= "<html><head>\n";
	$header .= "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=UTF-8\">\n";
	$header .= "<LINK REL=\"shortcut icon\" HREF=\"" .$images. "favicon.ico\" TYPE=\"image/x-icon\">\n\n";
	$header .= "<title>" . $tivo['nowplaying'] . "</title><link href=" . $tivo['css'] . " rel=\"stylesheet\" type=\"text/css\" ></head>\n\n";
	$header .= "<body onload=\"init()\">\n";

	// add link back to Summary page at top
 	$header .= "<div class=\"dura\"><a href=\"" . $myurl . "summary.htm\" >&larr;&thinsp; back to Summary </a></div>\n";

	// link to expand/collapse all entries on the page
 	$header .= "<div class=\"dura\" id=\"plusminusAll\" onclick=\"toggleAll(" . $icnt . ")\" >&#8597;&nbsp;&thinsp; expand/collapse all </div>\n";

	if (file_exists("$image_path". "tivo_" . $tivo['model'] . ".png")){
		$header .= "<h1> <img src=\"" .$images. "tivo_" . $tivo['model'] . ".png\"><br>" . $tivo['nowplaying'] . " </h1>\n";
	} else {
		print("Missing model image: " . "$image_path". "tivo_" . $tivo['model'] . ".png\n");
	 	$header .= "<h1> <img src=" .$images. "tivo_logo.png ><br> " . $tivo['nowplaying'] . " </h1>\n";
	}
	$header .= "<h2> Last Updated: " . date("F j, Y, g:i a") . " </h2>\n";

	// include javascript  tivo_now_playing.js
	$header .= "<script id=\"imagepath\"> \"" . $images . "\" </script>\n";
	$header .= "<script src=\"" . $tivo['js'] . "\" > </script>\n";

	$sum_table .= "<tr> "; // start of new row in the table for summary page data

	if($tivoarray == null){ 			// The DVR is OFF-LINE
		$content .= "<center><font size=12 face=verdana color=\"red\">This TiVo is currently unavailable</font></center>";
		$groups[0] .= "<center><font size=12 face=verdana color=\"red\">This TiVo is currently unavailable</font></center>";										// add the TiVo's name for the first field in the sort table
	} else {
		for ($i = 1;$i < count($tivoarray);$i++) {
			$ci = $tivoarray[$i]['customicon'];
			$customicon = explode(":", $ci);
			$sc = explode("-", $tivoarray[$i]['sourcechannel']);

			$tivoarray[$i]['content'] = str_replace("amp;", "", $tivoarray[$i]['content']);
			$tivoarray[$i]['title'] = str_replace("amp;", "", $tivoarray[$i]['title']);
			$tivoarray[$i]['episodetitle'] = str_replace("amp;", "", $tivoarray[$i]['episodetitle']);
			if ($tivoarray[$i]['description'] != "") {
				$tivoarray[$i]['description'] = str_replace("amp;", "", $tivoarray[$i]['description']);
				$tivoarray[$i]['description'] = str_replace("Copyright Tribune Media Services, Inc.", "", $tivoarray[$i]['description']);
				$tivoarray[$i]['description'] = str_replace("Copyright Rovi, Inc.", "", $tivoarray[$i]['description']);
			}

			$tivoarray[$i]['content'] = str_replace("&amp;quot;", "&quot;", $tivoarray[$i]['content']);
			$tivoarray[$i]['title'] = str_replace("&amp;quot;", "&quot;", $tivoarray[$i]['title']);
			$tivoarray[$i]['episodetitle'] = str_replace("&amp;quot;", "&quot;", $tivoarray[$i]['episodetitle']);
			$tivoarray[$i]['description'] = str_replace("&amp;quot;", "&quot;", $tivoarray[$i]['description']);

			// collect the programid, series and episode information
			$tivoarray[$i]['programid'] = str_replace("&amp;quot;", "&quot;", $tivoarray[$i]['programid']);
			$tivoarray[$i]['seriesid'] = str_replace("&amp;quot;", "&quot;", $tivoarray[$i]['seriesid']);
			$tivoarray[$i]['episodenumber'] = str_replace("&amp;quot;", "&quot;", $tivoarray[$i]['episodenumber']);
			$tivoarray[$i]['tvrating'] = str_replace("&amp;quot;", "&quot;", $tivoarray[$i]['tvrating']);
			$tivoarray[$i]['mpaarating'] = str_replace("&amp;quot;", "&quot;", $tivoarray[$i]['mpaarating']);

			// $content .= "<div class=\"programitem\">\n";
			$content.="<div>\n"; // div.programitem no longer in css file
			$content .= "<img src=\"" .$images. "checkbox.png\" id=\"plusminus" . $icnt . "\" onclick=\"toggleItem(" . $icnt . ")\" border=\"0\" width=\"14\" height=\"14\">\n";

			if ($customicon[3] != "") {
				$content .= "<img src=\"" .$images. "" . $customicon[3] . ".png\" width=\"16\" height=\"16\">\n";
			}
			else {
				$content .= "<img src=\"" .$images. "" . "regular-recording.png\" width=\"16\" height=\"16\">\n";
			}

			if ($imdblinks == 1) {
				$imdb = str_replace(" ", "%20", $tivoarray[$i]['title']);
				$content .= "<a href=\"http://www.imdb.com/find?q=" . $imdb . ";tt=on;nm=on;mx=20\" target=\"_blank\"><img src=\"" .$images. "imdb.png\" border=\"0\" width=\"16\" height=\"16\"></a>\n";
			}

			if ($disablexmllinks == 0){
				$content .= "<a href=" . $tivoarray[$i]['tivovideodetails'] . ">";
			}

			$content .= "<span class=\"name\">" . $tivoarray[$i]['title'] . "</span>";

			if ($tivoarray[$i]['episodetitle'] != "")
				$content .= " - <span class=\"eptitle\">" . $tivoarray[$i]['episodetitle'] . "</span>\n";
			if ($disablexmllinks == 0)
				$content .= "</a>\n";

			$content .= "<div class=\"item\" id=\"myTbody" . $icnt++ . "\">\n";
			if ($tivoarray[$i]['description'] != "") {
				$content .= "<div class=\"desc\">" . $tivoarray[$i]['description'] . "</div>\n";
			}
			$content .= "<br>\n";
			$content .= "<div class=\"date\">Channel: " . $tivoarray[$i]['sourcestation'] . " (" . $sc[0] . ")</div>\n";
			$content .= "<div class=\"date\">Recorded: " . tivoDate("g:i a - F j, Y", $tivoarray[$i]['capturedate']) . "</div>\n";
			$content .= "<div class=\"size\">Size: " . toMB($tivoarray[$i]['sourcesize']) . " MB</div>\n";
			$content .= "<div class=\"dura\">Duration: " . mSecsToTime($tivoarray[$i]['duration']) . "</div>\n";

			// programid, series and episode information
			$content .= "<div class=\"pgid\">ProgramId: " . $tivoarray[$i]['programid'] . "</div>\n";
			$content .= "<div class=\"srid\">SeriesId: " . $tivoarray[$i]['seriesid'] . "</div>\n";
			if($tivoarray[$i]['episodenumber'] > 0)
				$content .= "<div class=\"epnum\">EpisodeNumber: " . $tivoarray[$i]['episodenumber'] . "</div>\n";

			if($tivoarray[$i]['tvrating'] > 0)
				$content .= "<div class=\"epnum\">TvRating: " . $tivoarray[$i]['tvrating'] . "</div>\n";

			if($tivoarray[$i]['mpaarating'] > 0)
				$content .= "<div class=\"epnum\">MPAARating: " . $tivoarray[$i]['mpaarating'] . "</div>\n";

			$content .= "<br>\n";

			if ($disabledownloadlinks == 0) {
				$content .= "<a class=\"download\" href=" . $tivoarray[$i]['content'] . ">Download</a><br>\n";
				$content .= "</div>\n";
			}
			$content .= "</div>\n";
			$content .= "</div>\n";
			$totallength += $tivoarray[$i]['duration'];
			$totalsize += $tivoarray[$i]['sourcesize'];

			// compute suggestions
			if($customicon[3] == "suggestion-recording") {
				$totalsuggestions += $tivoarray[$i]['sourcesize'];
				$totalnumsuggestions++;
			}

			// requested in-progress recordings are listed first
			if($recording_suggestion == true){
				if($customicon[3] == "in-progress-recording"){
					$totalsuggestions += $tivoarray[$i]['sourcesize'];
					$totalnumsuggestions++;
				}
			} else {
				if($customicon[3] != "in-progress-recording"){
					// all in-progress recordings should now be suggestions
					$recording_suggestion = true;
				}
			}

               		// add a row to the sortable table
			$sort_table .= "<tr>";
			$sort_table .= "<td>" . $tivo ['name'] ."</td>";
			$sort_table .= "<td>" . $tivoarray [$i] ['title'] ."</td>";
			$sort_table .= "<td>" . $tivoarray [$i] ['episodetitle'] ."</td>";
			$sort_table .= "<td>" . $tivoarray [$i] ['programid'] ."</td>";
			$sort_table .= "<td>" . $tivoarray [$i] ['seriesid'] ."</td>";
			$sort_table .= "<td sorttable_customkey=\"" . tivoDate ( "YmdHi", $tivoarray [$i] ['capturedate'] ) .
				"\">" .  tivoDate("g:i a - F j, Y", $tivoarray [$i] ['capturedate'] ) . "</td>";
			$sort_table .= "</tr>\n";

			// Collect info for the collapsible tables header
			// save the series name and count the episodes (a multidimensional array would be better)
			$groups_series[$tivoarray [$i] ['seriesid']] = $tivoarray [$i] ['title'];
			$groups_count[$tivoarray [$i] ['seriesid']]++;

			// preload a valid date first encounter
			if($groups_newdate[$tivoarray [$i] ['seriesid']] == "")
 				$groups_newdate[$tivoarray [$i] ['seriesid']]=$tivoarray [$i] ['capturedate'];
 			if($groups_olddate[$tivoarray [$i] ['seriesid']] == "")
 				$groups_olddate[$tivoarray [$i] ['seriesid']]=$tivoarray [$i] ['capturedate'];

			// youngest recording
			if($tivoarray [$i] ['capturedate'] >= $groups_newdate[$tivoarray [$i] ['seriesid']]) {
				$groups_newdate[$tivoarray [$i] ['seriesid']] = $tivoarray [$i] ['capturedate'];
			}
			// oldest recording
			if($tivoarray [$i] ['capturedate'] <= $groups_olddate[$tivoarray [$i] ['seriesid']]) {
				$groups_olddate[$tivoarray [$i] ['seriesid']] = $tivoarray [$i] ['capturedate'];
			}
			// End collect info for the collapsible tables header

			// add the TiVo's name for the first field in the sort table
			$groups[$tivoarray [$i] ['seriesid']] .= "<tr>";
			// add shows title to sort table
// 1 XXXXXX Tool Tip
			$groups[$tivoarray [$i] ['seriesid']] .=
					"<td> <span title=\"" . $tivo ['name'] . "\nModel: " . $tivo ['model'] . "\nSize: " . $tivo ['size_gb'] . " GB\">" .
			 		$tivo ['shorttitle'] ."</td>";

// 2 XXXXXX Tool Tip
			if ($customicon[3] != "") {
				$groups[$tivoarray [$i] ['seriesid']] .=
						"<td> <span title=\"" . $customicon[3] . "\">".
						"<center><img src=\"" .$images. "" .
						$customicon[3] . ".png\" width=\"16\" height=\"16\"></center></td>\n";
			}
			else {
				$groups[$tivoarray [$i] ['seriesid']] .=
						"<td> <span title=\"regular-recording" . "\">".
						"<center><img src=\"" .$images. "" .
						"regular-recording.png\" width=\"16\" height=\"16\"></center></td>\n";
			}

			// add shows title to sort table
// 3 XXXXXX Tool Tip
			$groups[$tivoarray [$i] ['seriesid']] .=
					"<td> <span title=\" Series ID: " . $tivoarray [$i] ['seriesid'] . "\">" .		// tooltip
					$tivoarray [$i] ['title'] ."</td>";									// title

// 4 XXXXXX Tool Tip
			$groups[$tivoarray [$i] ['seriesid']] .=
				 "<td> <span title=\"" . $tivoarray [$i] ['description'] . "\">";		// tooltip
			if($tivoarray [$i] ['episodetitle'] == ""){ 								// No episode title for Movies and Specials
				$groups[$tivoarray [$i] ['seriesid']] .= "<center> - </span> </td>";	// still want the ToolTip
			} else {
				 $groups[$tivoarray [$i] ['seriesid']] .= $tivoarray [$i] ['episodetitle'] . " </span> </td>";	// episode title
			}

// 5 XXXXXX Tool Tip
			$groups[$tivoarray [$i] ['seriesid']] .="<td sorttable_customkey=\"" .
				// record date index on sortable numeric value
				tivoDate ( "YmdHi", $tivoarray [$i] ['capturedate'] ) . "\">" .			// Sort value
				// record date viewable format
				"<span title=\"Channel: " . $tivoarray[$i]['sourcestation'] . " (" . $sc[0] . ")" .
				"\nDuration: " . mSecsToTime($tivoarray [$i] ['duration']) . "\">" .					// tooltip Channel and Duration
				 tivoDate("g:i a - F j, Y", $tivoarray [$i] ['capturedate'] ) ."</td>";	// Date

// 6 XXXXXX Tool Tip
			// Note: ProgramID and Series are for testing may be removed one or both in the future
			//$groups[$tivoarray [$i] ['seriesid']] .= "<td>" . $tivoarray [$i] ['programid'] ."</td>";
			$groups[$tivoarray [$i] ['seriesid']] .=
				 "<td> <span title=\" Series ID: " . $tivoarray [$i] ['seriesid'] . "\">" .			// tooltip
				 $tivoarray [$i] ['programid'] . " </span> </td>";						// programid

			// Removed Series ID from table
			//$groups[$tivoarray [$i] ['seriesid']] .= "<td>" . $tivoarray [$i] ['seriesid'] ."</td>";

			// Collect info for the collapsible tables header for ALL DVRs
			// save the series name and count the episodes (a multidimensional array would be better)
			$folders_series[$tivoarray [$i] ['seriesid']] = $tivoarray [$i] ['title'];
			$folders_count[$tivoarray [$i] ['seriesid']]++;

			// preload a valid date first encounter
 			if($folders_newdate[$tivoarray [$i] ['seriesid']] == "")
 				$folders_newdate[$tivoarray [$i] ['seriesid']]=$tivoarray [$i] ['capturedate'];
 			if($folders_olddate[$tivoarray [$i] ['seriesid']] == "")
 				$folders_olddate[$tivoarray [$i] ['seriesid']]=$tivoarray [$i] ['capturedate'];

			// youngest recording
			if($tivoarray [$i] ['capturedate'] >= $groups_newdate[$tivoarray [$i] ['seriesid']]) {
				$folders_newdate[$tivoarray [$i] ['seriesid']] = $tivoarray [$i] ['capturedate'];
			}
			// oldest recording
			if($tivoarray [$i] ['capturedate'] <= $groups_olddate[$tivoarray [$i] ['seriesid']]) {
				$folders_olddate[$tivoarray [$i] ['seriesid']] = $tivoarray [$i] ['capturedate'];
			}
			// End collect info for the collapsible tables header for ALL DVRs

			// add the TiVo's name for the first field in the sort table
			$folders[$tivoarray [$i] ['seriesid']] .= "<tr>";
			// add show's title to sort table

// 1 XXXXXXXX Tool Tip
			$folders[$tivoarray [$i] ['seriesid']] .=
			"<td> <span title=\"" . $tivo ['name'] . "\nModel: " . $tivo ['model'] . "\nSize: " . $tivo ['size_gb'] . " GB\">" .
			$tivo ['shorttitle'] ."</td>";
//			$folders[$tivoarray [$i] ['seriesid']] .= "<td>" . $tivo ['shorttitle'] ."</td>";


// 2 XXXXXXXX Tool Tip
			if ($customicon[3] != "") {
				$folders[$tivoarray [$i] ['seriesid']] .=
				"<td> <span title=\"" . $customicon[3] . "\">".
				"<center><img src=\"" .$images. "" .
				$customicon[3] . ".png\" width=\"16\" height=\"16\"></center></td>\n";
			}
			else {
				$folders[$tivoarray [$i] ['seriesid']] .=
				"<td> <span title=\"regular-recording" . "\">".
				"<center><img src=\"" .$images. "" .
				"regular-recording.png\" width=\"16\" height=\"16\"></center></td>\n";
			}

// 			if ($customicon[3] != "") {
// 				$folders[$tivoarray [$i] ['seriesid']] .= "<td><center><img src=\"" .$images. "" .
// 				 $customicon[3] . ".png\" width=\"16\" height=\"16\"></center></td>\n";
// 			}
// 			else {
// 				$folders[$tivoarray [$i] ['seriesid']] .= "<td><center><img src=\"" .$images. "" .
// 				 "regular-recording.png\" width=\"16\" height=\"16\"></center></td>\n";
// 			}

			// add show's title to sort table
// 3 XXXXXXXX Tool Tip
			$folders[$tivoarray [$i] ['seriesid']] .=
			"<td> <span title=\" Series ID: " . $tivoarray [$i] ['seriesid'] . "\">" .		// tooltip
			$tivoarray [$i] ['title'] ."</td>";									// title
//			$folders[$tivoarray [$i] ['seriesid']] .= "<td>" . $tivoarray [$i] ['title'] ."</td>";

// 4 XXXXXXXX Tool Tip
			$folders[$tivoarray [$i] ['seriesid']] .=
			"<td> <span title=\"" . $tivoarray [$i] ['description'] . "\">";			// tooltip
			if($tivoarray [$i] ['episodetitle'] == ""){ 								// No episode title for Movies and Specials
				$folders[$tivoarray [$i] ['seriesid']] .= "<center> - </span> </td>";	// still want the ToolTip
			} else {
				$folders[$tivoarray [$i] ['seriesid']] .= $tivoarray [$i] ['episodetitle'] . " </span> </td>";	// episode title
			}
//			$folders[$tivoarray [$i] ['seriesid']] .= "<td>" . $tivoarray [$i] ['episodetitle'] ."</td>";

// 5 XXXXXXXX Tool Tip
			$folders[$tivoarray [$i] ['seriesid']] .="<td sorttable_customkey=\"" .
					// record date index on sortable numeric value
			tivoDate ( "YmdHi", $tivoarray [$i] ['capturedate'] ) . "\">" .			// Sort value
			// record date viewable format
			"<span title=\"Channel: " . $tivoarray[$i]['sourcestation'] . " (" . $sc[0] . ")" .
			"\nDuration: " . mSecsToTime($tivoarray [$i] ['duration']) . "\">" .					// tooltip Channel and Duration
			tivoDate("g:i a - F j, Y", $tivoarray [$i] ['capturedate'] ) ."</td>";	// Date
//			$folders[$tivoarray [$i] ['seriesid']] .="<td sorttable_customkey=\"" .
//				// record date index on sortable numeric value
//				tivoDate ( "YmdHi", $tivoarray [$i] ['capturedate'] ) . "\">" .
//				// record date viewable format
//				tivoDate("g:i a - F j, Y", $tivoarray [$i] ['capturedate'] ) ."</td>";

// 6 XXXXXXXX Tool Tip
			// Note: ProgramID and Series are for testing may be removed one or both in the future
			$folders[$tivoarray [$i] ['seriesid']] .=
				 "<td> <span title=\" Series ID: " . $tivoarray [$i] ['seriesid'] . "\">" .			// tooltip
				 $tivoarray [$i] ['programid'] . " </span> </td>";						// programid
//			$folders[$tivoarray [$i] ['seriesid']] .= "<td>" . $tivoarray [$i] ['programid'] ."</td>";

			// Removed SeriesID from table
			//$folders[$tivoarray [$i] ['seriesid']] .= "<td>" . $tivoarray [$i] ['seriesid'] ."</td>";

		} // loop through tivoarray
	} // if tivoarray is not null

		// adjust for drive size entered too small use total recorded size
		$auto_size_gb = $tivo['size_gb'];
		$auto_size_file_name = ("log". delim . $tivo['name'] . "_drive_size.php");

		if(file_exists($auto_size_file_name)){ // possible new drive size
			include($auto_size_file_name); // $auto_size_gb = "nnnn";
			if($auto_size_gb < 0){
				$auto_size_gb = $tivo['size_gb']; // disable auto size
			} else {
				$tivo['size_gb'] = $auto_size_gb;
			}
		}

		if(toGB($totalsize) > $auto_size_gb) {
			$fpt = @fopen($auto_size_file_name, 'w+');
			fwrite($fpt, "<?php\n\n");
			fwrite($fpt, "\t// Drive size is adjusted when the total drive space used is greater\n");
			fwrite($fpt, "\t// Delete this file to start over\n");
			fwrite($fpt, "\t// " .date("F j, Y, g:i a") . " updating from " . $tivo['size_gb'] . " GB  To " . toGB($totalsize) . " GB\n\n");
			$tivo['size_gb'] = toGB($totalsize);
			fwrite($fpt,"\t\$auto_size_gb = \"" . $tivo['size_gb'] . "\";\t// Set to -1 to disable auto size adjustment\n" );
			fwrite($fpt, "?>\n");
			fclose($fpt);

			// TODO remove debug logging
			// debug tracking size totals
			// log file to track drive size and computed drive size history
			$fpt = @fopen("log". delim . $tivo['name'] . "_track_drive_size.log", 'a');
			fwrite($fpt, "// " .date("F j, Y, g:i a") . "\t" . $tivo['size_gb'] . " GB\t" . toGB($totalsize) . " GB\n");
			fwrite($fpt,"\$auto_size_gb = \"" . $tivo['size_gb'] . "\";\n" );
			fclose($fpt);
			// end of debug logging code
		}
		$totalitems = $tivoarray[0]['totalitems'];
		$freespace = ((intval(trim($tivo['size_gb']))) * 1024) - toMB($totalsize);

		// include suggestions as free space
		$freespace += toMB($totalsuggestions);
		if($freespace < 0) $freespace = 0; // pesky '-' values corrupt nagios
		$percent_free = floor((mBtoGB($freespace) / $tivo['size_gb']) * 1000)/10;

		if($tivoarray == null){ // avoid null values when TiVo is off-line
			$totalitems = 0; $freespace = 0; $percent_free = 0.0; $totalsize = 0; $totallength=0; $totalsuggestions = 0;
			$drivesize = 0;		// for the totals dont count off-line DVR's
		} else 	{
			$drivesize = $tivo['size_gb']; // used for adding to total avaiable drive's size
		}

	$footer .= "<br>\n";
	$footer .= "<div class=\"totalblock\">\n";
	$footer .= "<div class=\"totalitems\">Total Number of Items: " . $totalitems . "</div>\n";
	$footer .= "<div class=\"totaltime\">Total Length (Recorded Shows): " . mSecsToTime($totallength) . "</div>\n";
	$footer .= "<div class=\"totalsize\">Total Size (Recorded Shows): " . toGB($totalsize) . " GB</div>\n";

	$footer .= "<div class=\"totalsize\">Total Size of ". $totalnumsuggestions ." Suggestions: " . toGB($totalsuggestions) . " GB</div>\n";

	$footer .= "<div class=\"totalsize\">Available Space (including Suggestions): " . mBtoGB($freespace) . " GB\t(" . $percent_free . "% free)</div>\n";

	$footer .= "</div>\n";

	// add a link to the summary page
	$footer .= "<div class=\"dura\"><a href=\"" . $myurl . "summary.htm\" >&larr;&thinsp; back to Summary </a></div>\n";

	$footer .= "</body></html>";
	$fp1 = @fopen($nowPlaying, "w");
	fwrite($fp1, $header . $content . $footer);
	fclose($fp1);

	$series_count=0;	// Used to create a unique handle for each group
	$fp1 = @fopen($nowPlayingGroups, "w");
	fwrite($fp1, $header . "<script src=\"" . $mysorttable . "\" type=\"text/javascript\"></script>\n");
	foreach($groups as $x => $x_value) {	// Procress the entire array
		//$series_count++; toggleALL in $header is set to start toggeling at myTbody0 it won't work if myTbody* starts at 1
		// header for each series put in loop to give each table a unique ID from the seriesid
		fwrite($fp1, "<div><img src=\"" .$images. "folder.png\" id=\"plusminus".$series_count."\" onclick=\"toggleItem(".$series_count.")\" border=\"0\" width=\"14\" height=\"14\">\n");

		// Programs that do not have a seriesID will be grouped and classified as Movies and Specials
		if($x == ""){
			fwrite($fp1, "<span class=\"name\" > <mark><i><b>" . "Movies and Specials" . "</b></i></mark></span><span class=\"desc\"> (" . $groups_count[$x]);
		} else {
			fwrite($fp1, "<span class=\"name\">" . $groups_series[$x] . "</span><span class=\"desc\"> (" . $groups_count[$x]);
		}

		if ($groups_count[$x] > 1) {
			fwrite($fp1, " episodes; ");
		}
		else {
			fwrite($fp1, " episode; ");
		}
		fwrite($fp1, tivoDate("F j, Y, g:i a", $groups_olddate[$x]));
		if($groups_count[$x] > 1)
			fwrite($fp1, " &rarr; " . tivoDate("F j, Y, g:i a", $groups_newdate[$x]));
		fwrite($fp1, ") </span>");
		fwrite($fp1, "<div class=\"item\" id=\"myTbody".$series_count."\">\n");
		fwrite($fp1, "<h4>\n<table id=\"$x\" class=\"sortable\" border=\"2\" cellspacing = \"2\" cellpadding = \"4\" align = \"center\" >\n");
		fwrite($fp1, "	<tr>
					<th> TiVo </th>
					<th class=\"sorttable\"> Status </th>
					<th class=\"sorttable\"> Series Name </th>
					<th class=\"sorttable\"> Episode </th>
					<th class=\"sorttable_numeric\"> Record Date </th>
					<th class=\"sorttable\"> Program ID </th>
					</tr>\n");

/*	Removed SeriesID from table
 * 		fwrite($fp1, "	<tr>
 *					<th> TiVo </th>
 *					<th class=\"sorttable\"> Status </th>
 *					<th class=\"sorttable\"> Series Name </th>
 *					<th class=\"sorttable\"> Episode </th>
 *					<th class=\"sorttable_numeric\"> Record Date </th>
 *					<th class=\"sorttable\"> Program ID </th>
 *					<th class=\"sorttable\"> Series ID </th>
 *					</tr>\n");
 */
		fwrite($fp1, $x_value . "\n");	// write the rows of the table collected and formatted in the tivo loop
		fwrite($fp1, "</table>\n</h4></div>\n</div>\n");
$series_count++;
	}

	fwrite($fp1, $footer);

	if($nplarchives == 1) {
	 	// archive loop Update once in the first 15 minutes of the hour
	 	// TODO prevent update if run twice in the 15 minutes
		if(date("i") < 16) {
			copy($nowPlaying, $rootpath . $archNowPlaying);

			// Append to the table for tracking each TiVo links to the copied NowPlaying.html pages.
			// Links are saved in a .log file in a html style table format that can be recovered on successive runs
			$fpt = @fopen($sug_log_file, 'a');
			fwrite($fpt, "<tr><td>" ."<a href=" . $myurl . $archNowPlaying .">" . $tivo['shorttitle'] .  "</a> </td>");

			fwrite($fpt, "<td sorttable_customkey=\"". date("YmdHi") ."\">" . date("M j, Y, H:i") .  "</td>");
			fwrite($fpt, "<td>" . $tivo['size_gb'] . " GB" . "</td>" );
			fwrite($fpt, "<td>" . mBtoGB($freespace) . " GB"  . "</td>" );
			fwrite($fpt, "<td>" . floor((mBtoGB($freespace) - toGB($totalsuggestions)) * 100) / 100 . " GB</td>" );

			fwrite($fpt, "<td sorttable_customkey=\"" . $totalsuggestions . "\">(" . $totalnumsuggestions . ") " . toGB($totalsuggestions) . " GB</td></tr>\n" );
			fclose($fpt);

			// make a header for the summary of suggestions page
			$sug_header .= "<!DOCTYPE html>\n";
			$sug_header .= "<html><head>\n";
			$sug_header .= "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=UTF-8\">\n";
			$sug_header .= "<LINK REL=\"shortcut icon\" HREF=\"" .$images. "favicon.ico\" TYPE=\"image/x-icon\">\n\n";
			$sug_header .= "</head><body><sh>\n<title> Suggestions Summary </title><link href=\"" . $summary_css . "\" rel=\"stylesheet\" type=\"text/css\"></sh>\n\n";
			$sug_header .= "<div class=\"dura\"><a href=\"" . $myurl . "summary.htm\" > &larr;&thinsp; back to Summary </a></div>";
			$sug_header .= "<h2> Last Updated: " . date("F j, Y, g:i a") . " </h2>\n";
			$sug_header .= "<script src=\"" . $mysorttable . "\" type=\"text/javascript\"></script>";
			// create the table and add data from the log file
			$sug_table .= "<h4>\n<br><table id=\"Suggestions\" class=\"sortable\" border=\"2\" cellspacing = \"2\" cellpadding = \"4\" align = \"center\" >\n";
			$sug_table .= " <tr> <th> TiVo </th> <th> Date </th> <th class=\"sorttable_numeric\"> Drive Size </th> <th class=\"sorttable_numeric\"> Free Space </th> <th class=\"sorttable_numeric\"> Deleted </th> <th> Suggestions </th> </tr>\n";

			$sug_table .= file_get_contents($sug_log_file);
			$sug_table .= "</table></h4>\n";

			$sug_footer .= "<div class=\"dura\"><a href=\"" . $myurl . "summary.htm\" > &larr;&thinsp; back to Summary </a></div>";
			$sug_footer .= "</body></html>";

			$fpt1 = @fopen($sug_html_file, 'w');
			fwrite($fpt1, $sug_header . $sug_table . $sug_footer);
			fclose($fpt1);
		} // end once an hour

		// end suggestions table
	} // end $nplarchives == 1 check

	// for summary table
	if($tivoarray == null) {			// if TiVo is off line create a placeholder
		$sum_table .= "<td bgcolor = \"silver\" >";
		$sum_table .= " <a href=" . $nowPlayingGroups . " title=\"". $tivo['shorttitle'] . "'s Now Playing Grouped by series ID\">" . "<img src=\"" .$images. "" . "folder.png\" width=\"16\" height=\"16\">" . "</a>";
		$sum_table .= " <a href=" . $nowPlaying . " title=\"".$tivo['shorttitle']."'s Now Playing\">" . $tivo['shorttitle'] . "</a> ";
		$sum_table .= "</td>";
		$sum_table .= "<td bgcolor = \"silver\">" . $tivo['size_gb'] . " GB</td> ";
		$sum_table .= "<td bgcolor = \"silver\">----</td> ";
		$sum_table .= "<td bgcolor = \"silver\">----</td> ";
		$sum_table .= "<td bgcolor = \"silver\">----</td>";
		if($nplarchives == 1)
			$sum_table .= "<td bgcolor = \"silver\"> <a href=" . $sug_html_file . ">----</a></td>";

	}
	else { 								// new add entry to the summary table
		$sum_table .= "<td> ";
		$sum_table .= " <a href=" . $nowPlayingGroups . " title=\"". $tivo['shorttitle'] . "'s Now Playing Grouped by series ID\">" . "<img src=\"" .$images. "" . "folder.png\" width=\"16\" height=\"16\">" . "</a>";
		$sum_table .= " <a href=" . $nowPlaying . " title=\"".$tivo['shorttitle']."'s Now Playing\">" . $tivo['shorttitle'] . "</a> ";
		$sum_table .= "</td>";
		$sum_table .= "<td>" . $tivo['size_gb'] . " GB</td> ";
		$sum_table .= "<td>" . toGB($totalsize) . " GB</td> ";
		$sum_table .= "<td>" . mBtoGB($freespace) . " GB</td> ";

		// color indicators for free space warnings
		if($tivo['critical']  > $percent_free) $sum_table .= "<td bgcolor = \"red\">";
		else if($tivo['warning'] > $percent_free) $sum_table .= "<td bgcolor = \"yellow\">";
		else  $sum_table .= "<td>";
		$sum_table .= $percent_free . "%</td>";

		if($nplarchives ==1) {
			if($totalnumsuggestions < 10) $sum_table .= "<td bgcolor = \"red\">";
			else if($totalnumsuggestions < 20) $sum_table .= "<td bgcolor = \"yellow\">";
			else $sum_table .= "<td>";
			$sum_table .="<a href=" . $sug_html_file . " title=\"". $tivo['shorttitle'] ." Now Playing History\">" .$totalnumsuggestions . "</a></td>";
		}
	}
	// end of summary table

	$sum_table .= "</tr>\n";

	$allcontent .= "<br><hr>\n";
	if (file_exists("$image_path". "tivo_" . $tivo['model'] . ".png")){
		$allcontent .= "<h1> <img src=\"" .$images. "tivo_" . $tivo['model'] . ".png\"><br>" . $tivo['nowplaying'] . " </h1>\n";
	} else {
		print("Missing model image: " . "$image_path". "tivo_" . $tivo['model'] . ".png\n");
	 	$allcontent .= "<h1> <img src=" .$images. "tivo_logo.png ><br> " . $tivo['nowplaying'] . " </h1>\n";
	}
	//TODO alternate colors for each TiVo or some way to label which box the program is on
	$allcontent .= $content;	// add content to list of all recordings web page

	$alltotalsize 		+= $totalsize;
	$alltotalsuggestions 	+= $totalsuggestions;
	$alltotalnumsuggestions += $totalnumsuggestions;
	$allfreespace 		+= $freespace;
	$alltotalitems 		+=$totalitems;
	$alltotallength 	+= $totallength;
	$all_size_gb 		+= $drivesize; //$tivo['size_gb'];

} // end of foreach tivo

$allpercent_free .= floor((mBtoGB($allfreespace) /$all_size_gb)  * 1000)/10;;

// add All Totals to a line on summary table
$nowPlaying = "alldvrs.htm";
$sum_table .= "<tr> "; // start of new row in the table for summary page data

$sum_table .= "<td style=\"text-align:justify\">";
$sum_table .= " <a href=" . $foldershtm . " title=\" All Now Playing Grouped by series ID\">" . "<img src=\"" .$images. "" . "folder.png\" width=\"16\" height=\"16\">" . "</a>";
$sum_table .= " <a href=" . $nowPlaying . " title=\"'s Now Playing\">" .  "ALL" . "</a> ";
$sum_table .= "</td>";

$sum_table .= "<td>" . $all_size_gb . " GB</td> ";
$sum_table .= "<td>" . toGB($alltotalsize) . " GB</td> ";
$sum_table .= "<td>" . mBtoGB($allfreespace) . " GB</td> ";

// color indicators for free space warnings.
if($tivo['critical']  > $allpercent_free) $sum_table .= "<td bgcolor = \"red\">";
else if($tivo['warning'] > $allpercent_free) $sum_table .= "<td bgcolor = \"yellow\">";
else  $sum_table .= "<td>";
$sum_table .= $allpercent_free . "%</td>";

if($nplarchives == 1) {
	if($alltotalnumsuggestions < 10) $sum_table .= "<td bgcolor = \"red\">";
	else if($alltotalnumsuggestions < 20) $sum_table .= "<td bgcolor = \"yellow\">";
	else $sum_table .= "<td>";
	//$sum_table .= $alltotalnumsuggestions . "</td>";
	$sum_table .="<a href=" . "folders.htm" . " title=\"Sortable episode list\">" . $alltotalnumsuggestions . "</a></td>";
}
$sum_table .= "</tr>\n";
// end of add all totals

// save totals and summary
$sum_table  .= "</table>\n</h4>\n";

$sum_footer .= "<center><div class=\"dura\">";
$sum_footer .= "TNPL v" .$LASTUPDATE . " - Github:
		<a href=\"https://github.com/jradwan/tivo_now_playing\">jradwan [master]</a> |
		<a href=\"https://github.com/TiVoHomeUser/tivo_now_playing\">TiVoHomeUser [fork]</a>
		</div></center>";

$sum_footer .= "</body></html>";
$fp1 = @fopen($summaryhtm , "w");
fwrite($fp1, $sum_header . $sum_table . $sum_footer );
fclose($fp1);

// footer for all TiVos
$allfooter .= "<br>\n";
$allfooter .= "<div class=\"totalblock\">\n";
$allfooter .= "<div class=\"totalitems\">Total Number of Items: " . $alltotalitems . "</div>\n";
$allfooter .= "<div class=\"totaltime\">Total Length (Recorded Shows): " . mSecsToTime($alltotallength) . "</div>\n";
$allfooter .= "<div class=\"totalsize\">Total Size (Recorded Shows): " . toGB($alltotalsize) . " GB</div>\n";

$allfooter .= "<div class=\"totalsize\">Total Size of ". $alltotalnumsuggestions ." Suggestions: " . toGB($alltotalsuggestions) . " GB</div>\n";

$allfooter .= "<div class=\"totalsize\">Available Space (including Suggestions): " . mBtoGB($allfreespace) . " GB\t(" . $allpercent_free . "% free)</div>\n";

$allfooter .= "</div>\n";

// add a link to the summary page
$allfooter .= "<div class=\"dura\"><a href=\"" . $myurl . "summary.htm\" >&larr;&thinsp; back to Summary </a></div>\n";
$allfooter .= "<div class=\"dura\"><a href=\"" . $myurl. "sort.htm\" >&#8645;&nbsp; sortable episode list </a></div>\n";
$allfooter .= "<div class=\"dura\"><a href=\"" . $myurl. "folders.htm\" >&#8645;&nbsp; sortable episode list (grouped) </a></div>\n";
$allfooter .= "</body></html>";
// end of footer for all TiVos

// sort table footer
$sort_table .= "</table>\n</h4>\n";
$sort_footer .= "<div class=\"dura\"><a href=\"" . $myurl . "summary.htm\" >&larr;&thinsp; back to Summary </a></div>\n";
$sort_footer .= "<div class=\"dura\"><a href=\"" . $myurl . "alldvrs.htm\" >&larr;&thinsp; back to All TiVos - Now Playing </a></div>\n";
$sort_footer .= "<div class=\"dura\"><a href=\"" . $myurl. "sort.htm\" >&#8645;&nbsp; sortable episode list </a></div>\n";
$sort_footer .= "<div class=\"dura\"><a href=\"" . $myurl. "folders.htm\" >&#8645;&nbsp; sortable episode list (grouped) </a></div>\n";
$sort_footer .= "</body></html>";

// all TiVos
$fp1 = @fopen($nowPlaying , "w");
fwrite($fp1, $allheader . $allcontent . $allfooter );
fclose($fp1);

// sortable list
$fp1 = @fopen ( "sort.htm", "w" );
fwrite ( $fp1, $sort_header . $sort_table . $sort_footer );
fclose ( $fp1 );

$series_count=0;	// Used to create a unique handle for each group
$fp1 = @fopen($foldershtm, "w");
fwrite($fp1, $sort_header);

foreach($folders as $x => $x_value) {	// Procress the entire array
	//$series_count++; toggleALL in $sort_header is set to start toggeling at myTbody0 it won't work if myTbody* starts at 1
	// header for each series put in loop to give each table a unique ID from the seriesid
	fwrite($fp1, "<div><img src=\"" .$images. "folder.png\" id=\"plusminus".$series_count."\" onclick=\"toggleItem(".$series_count.")\" border=\"0\" width=\"14\" height=\"14\">\n");
	// Programs that do not have a seriesID will be grouped and classified as Movies and Specials
 	if($x == ""){
  		fwrite($fp1, "<span class=\"name\" > <mark><i><b>" . "Movies and Specials" . "</b></i></mark></span><span class=\"desc\"> (" . $folders_count[$x]);
 	} else {
 		fwrite($fp1, "<span class=\"name\">" . $folders_series[$x] . "</span><span class=\"desc\"> (" . $folders_count[$x]);
  	}

	if ($folders_count[$x] > 1) {
		fwrite($fp1, " episodes; ");
	}
	else {
		fwrite($fp1, " episode; ");
	}
	fwrite($fp1, tivoDate("F j, Y, g:i a", $folders_olddate[$x]));
	if($folders_count[$x] > 1)
		fwrite($fp1, " &rarr; " . tivoDate("F j, Y, g:i a", $folders_newdate[$x]));
	fwrite($fp1, ") </span>");
	fwrite($fp1, "<div class=\"item\" id=\"myTbody".$series_count."\">\n");
	fwrite($fp1, "<h4>\n<table id=\"$x\" class=\"sortable\" border=\"2\" cellspacing = \"2\" cellpadding = \"4\" align = \"center\" >\n");
	fwrite($fp1, "	<tr>
					<th> TiVo </th>
					<th class=\"sorttable\"> Status </th>
					<th class=\"sorttable\"> Series Name </th>
					<th class=\"sorttable\"> Episode </th>
					<th class=\"sorttable_numeric\"> Record Date </th>
					<th class=\"sorttable\"> Program ID </th>
					</tr>\n");
/*	B4 Removing Series ID
 * 	fwrite($fp1, "	<tr>
 *					<th> TiVo </th>
 *					<th class=\"sorttable\"> Status </th>
 *					<th class=\"sorttable\"> Series Name </th>
 *					<th class=\"sorttable\"> Episode </th>
 *					<th class=\"sorttable_numeric\"> Record Date </th>
 *					<th class=\"sorttable\"> Program ID </th>
 *					<th class=\"sorttable\"> Series ID </th>
 *					</tr>\n");
 *
 */
	fwrite($fp1, $x_value . "\n");	// write the rows of the table collected and formatted in the tivo loop
	fwrite($fp1, "</table>\n</h4></div>\n</div>\n");
	$series_count++;
}
fwrite($fp1, $allfooter);
//fwrite($fp1, $sort_footer );
//fwrite($fp1, "</body></html>");
fclose ( $fp1 );

?>
