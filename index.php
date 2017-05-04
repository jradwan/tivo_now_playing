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
 *    I added something to the program information like series ID.
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
*/

ini_set("max_execution_time", "180");
ini_set("error_log", "tivo_errors.txt");

error_reporting(0);

if (stristr($_ENV["OS"], "Windows") !== false) {
	define("delim", "\\");
} else {
	define("delim", "/");
}

$binpath = "bin" . delim;  // Defined here to find the settings file.
require_once($binpath . "tivo_settings.php");
require_once($binpath . "class_tivo_xml.php");

// Make a new path for the XML files if needed.
if(!file_exists($xml_path)) {
	if(mkdirV4($xml_path, 0777, true)) print("Success Xml path\n");
	else print("Fail making directory ". $xml_path."\n");
}

// set up archiving-related variables and paths if enabled
if($nplarchives == 1) {
	//*TODO* Set an optional limit of number of archives to make.
	// Each month create a new archive folder.
	$year = Date('Y'); //$year = "2013";
	$month = Date('M'); // "Feb";

	// $arch is directory to save a copy of archived copy of the NowPlaying html file
	// TODO elminate sug_log_path and save the summary and drive size with the archive
	$sug_log_path = "log"  . delim . $year . delim . $month . delim;
	$arch_path    = "arch" . delim . $year . delim . $month . delim;

	// Make a new path for the time date paths if needed.
	if(!file_exists($sug_log_path)) {
		if(mkdirV4($sug_log_path, 0777, true)) print("Success Log path\n");
		else print("Fail making directory ". $sug_log_path."\n");
	}	

	// Make a new path for the archives if needed
	if(!file_exists($rootpath . $arch_path)) {
		if(mkdirV4($rootpath . $arch_path, 0777, true)) print("Success Archive path\n");
		else print("Fail making directory ". $rootpath . $arch_path ."\n");
	}

	// $archdate is used to create a unique name for the archived nowplaying HTML file
	$archdate = date(YmdHi); // 2012122015 YYYYMMDDHHMM timestamp appended to archived nowplaying
}

// PHP does not type and defaults to an empty char or null force a number 0 (zero)
$alltotalsize = 0;
$alltotalsuggestions = 0;
$alltotalnumsuggestions = 0;
$allfreespace = 0;
$alltotalitems = 0;
$alltotallength = 0;
$all_size_gb = 0;
$icnt = 0; // Make a unique ID to enable toggle on page with all dvrs

// Make a header for the summary page
$sum_header .= "<!DOCTYPE html>\n"; // PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n\n";
$sum_header .= "<html><head>\n";
$sum_header .= "<LINK REL=\"shortcut icon\" HREF=\"" .$images. "favicon.ico\" TYPE=\"image/x-icon\">\n\n";
$sum_header .= "<sh>\n<title>TiVo Disk Space - Summary</title><link href=\"" . $summary_css . "\" rel=\"stylesheet\" type=\"text/css\"></sh>\n\n";
$sum_header .= "<h2><img src=images/tivo_show.gif width=\"26\" height=\"26\"> Last Updated: " . date("F j, Y, g:i a") . " </h2>\n";
$sum_header .= "<script src=\"" . $mysorttable . "\" type=\"text/javascript\"></script>\n";
$sum_header .= "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=UTF-8\">\n";

// Start of sortable Summary Table
$sum_table .= "<h4>\n<br><table id=\"Summary\" class=\"sortable\" border=\"2\" cellspacing = \"2\" cellpadding = \"4\" align = \"center\" >\n";
$sum_table .= " <tr> <th> TiVo </th> <th class=\"sorttable_numeric\"> Drive Size </th> <th class=\"sorttable_numeric\"> Used Space </th> <th class=\"sorttable_numeric\"> Available Space </th> <th class=\"sorttable_numeric\"> Percent Free </th> ";
if($nplarchives == 1) {
	$sum_table .= "<th> Suggestions </th>";
}
$sum_table .= "</tr>\n";
// End of header for summary page


// Header for full list of programs from all TiVos
$allheader .= "<!DOCTYPE html\n>"; 
$allheader .= "<html><head>\n";
$allheader .= "<LINK REL=\"shortcut icon\" HREF=\"" .$images. "favicon.ico\" TYPE=\"image/x-icon\">\n\n";
$allheader .= "<title>" . "All TiVos - Now Playing" . "</title><link href=" . $mycss . " rel=\"stylesheet\" type=\"text/css\" ></head>\n\n";
$allheader .= "<body onload=\"init()\">\n";

// add link back to Summary page at top 
$allheader .= "<div class=\"dura\"><a href=\"" . $myurl . "summary.htm\" >&larr;&thinsp; back to Summary page </a></div>\n";

// link to expand/collapse all entries on the page
$allheader .= "<img src=\"" .$images. "plus.gif\" id=\"plusminusAll\" onclick=\"toggleAll(" . $icnt . ")\" border=\"0\" align=\"left\"><div class=\"dura\">&thinsp; expand/collapse all</div>\n";

$allheader .= "<h1> <img src=" .$images. "tivo_show.gif width=\"26\" height=\"26\"> " . "All TiVos" . " </h1>\n";
$allheader .= "<h2> Last Updated: " . date("F j, Y, g:i a") . " </h2>\n";
$allheader .= "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html charset=UTF-8\">";

// Java script TiVo_Now_Playing.js
$allheader .= "<script id=\"imagepath\"> \"" . $images . "\" </script>\n";
$allheader .= "<script src=" . $mytjs . " > </script>\n";


// Java script TiVo_Now_Playing.js
$allheader .= "<script id=\"imagepath\"> \"" . $images . "\" </script>\n";
$allcontent = "";

// Loop for each TiVo defined in the array defined in tivo_settings.php
foreach($tivos as $tivo) {
	unset($tivoarray, $totalsize, $totallength, $customicon, $sc, $totalitems, $freespace, $rssheader, $rsscontent, $rssfooter, $header, $content, $footer, $fp1, $fp2, $totalsuggestions, $totalnumsuggestions, $percent_free, $fpt, $auto_size_gb, $recording_suggestion, $sug_header, $sug_table, $sug_footer, $sug_html_file, $sug_log_file, $sug_html_file, $archNowPlaying, $nowPlaying);

	// Collect the data for the TiVo
	$tivoxml = new Tivo_XML();
	if($xml_path == "") $xml_path = "xml" . delim;
	$tivoxml->setOpt("xml_path",$xmlpath);
	$tivoxml->setOpt("wget", wgetpath);
	$tivoxml->setOpt("mak", $tivo['mak']);
	$tivoxml->setOpt("host", $tivo['ip']);
	$tivoxml->setOpt("dvr", $tivo['name']);

	// PHP does not type and defaults to an empty char or null force a number 0 (zero)
	$totalsize = 0;
	$totallength = 0;
	$totalitems = 0;
	$freespace = 0;
	$totalnumsuggestions = 0;
	$totalsuggestions = 0;

	// $nowPlaying Web page for the now playing list
	$nowPlaying 	= $tivo['name'] . "_nowplaying.htm";

	if($nplarchives == 1) {
		// Use suggestions to track free space vs unused space
		// $sug_html_file Web page with table tracking number of suggestions and free space
		$sug_html_file  = $sug_log_path . $tivo['name'] . "_suggestions.html";

		// $sug_log_file the big file with running history
		$sug_log_file   =  $sug_log_path . $tivo['name'] . "_track_suggestions.log";

		// $archNowPlaying copy of $nowPlaying for archiving Web pages
		$archNowPlaying = $arch_path . $tivo['name'] . "_" . $archdate . "_nowplaying.htm";
	}

	// both requested and suggestions show now_recording when in progress. Any in progress
	// recordings before the first non suggestion should be counted as used space.
	$recording_suggestion = false;

	$tivoxml->init();
	if($tivoxml->getErr() == false)
		$tivoarray = $tivoxml->parseTiVoXML();

	if($tivoxml->getErr() == false)
		if ($dorss == 1) {  // rss code moved to external file rss.php
		include($binpath . "rss.php");
	} // end of rss

	$header .= "<!DOCTYPE html\n>"; 
	$header .= "<html><head>\n";
	$header .= "<LINK REL=\"shortcut icon\" HREF=\"" .$images. "favicon.ico\" TYPE=\"image/x-icon\">\n\n";
	$header .= "<title>" . $tivo['nowplaying'] . "</title><link href=" . $tivo['css'] . " rel=\"stylesheet\" type=\"text/css\" ></head>\n\n";
	$header .= "<body onload=\"init()\">\n";

	// add link back to Summary page at top 
	$header .= "<div class=\"dura\"><a href=\"" . $myurl . "summary.htm\" >&larr;&thinsp; back to Summary page </a></div>\n";

	// link to expand/collapse all entries on the page
        $header .= "<img src=\"" .$images. "plus.gif\" id=\"plusminusAll\" onclick=\"toggleAll(" . $icnt . ")\" border=\"0\" align=\"left\"><div class=\"dura\">&thinsp; expand/collapse all</div>\n";

	if (file_exists("$image_path". "tivo_" . $tivo['model'] . ".png")){
		$header .= "<h1> <img src=\"" .$images. "tivo_" . $tivo['model'] . ".png\"><br>" . $tivo['nowplaying'] . " </h1>\n";
	} else {
		print("missing image" . "$image_path". "tivo_" . $tivo['model'] . ".png\n");
	 	$header .= "<h1> <img src=" .$images. "tivo_show.gif width=\"26\" height=\"26\"><br> " . $tivo['nowplaying'] . " </h1>\n";
	}
	$header .= "<h2> Last Updated: " . date("F j, Y, g:i a") . " </h2>\n";
	$header .= "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=UTF-8\">\n";

	// Java script TiVo_Now_Playing.js
	$header .= "<script id=\"imagepath\"> \"" . $images . "\" </script>\n";
	$header .= "<script src=\"" . $tivo['js'] . "\" > </script>\n";

	$sum_table .= "<tr> "; // start of new row in the table for summary page data

	if($tivoarray == null) $content .= "<center><font size=12 face=verdana color=\"red\">This TiVo is currently unavailable</font></center>"; else
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

		// Collect the programid, series and episode information
		$tivoarray[$i]['programid'] = str_replace("&amp;quot;", "&quot;", $tivoarray[$i]['programid']);
		$tivoarray[$i]['seriesid'] = str_replace("&amp;quot;", "&quot;", $tivoarray[$i]['seriesid']);
		$tivoarray[$i]['episodenumber'] = str_replace("&amp;quot;", "&quot;", $tivoarray[$i]['episodenumber']);
		$tivoarray[$i]['tvrating'] = str_replace("&amp;quot;", "&quot;", $tivoarray[$i]['tvrating']);
		$tivoarray[$i]['mpaarating'] = str_replace("&amp;quot;", "&quot;", $tivoarray[$i]['mpaarating']);

		$content .= "<div class=\"programitem\">\n";
		if ($gfxicons == 1) {
			$content .= "<img src=\"" .$images. "tivo_show.gif\" id=\"plusminus" . $icnt . "\" onclick=\"toggleItem(" . $icnt++ . ")\" border=\"0\">\n";
		} else {
			$content .= "<img src=\"" .$images. "plus.gif\" id=\"plusminus" . $icnt . "\" onclick=\"toggleItem(" . $icnt . ")\" border=\"0\">\n";
		}
		if ($customicon[3] != "") {
			$content .= "<img src=\"" .$images. "" . $customicon[3] . ".png\" width=\"16\" height=\"16\">\n";
		}

		if ($imdblinks == 1) {
			$imdb = str_replace(" ", "%20", $tivoarray[$i]['title']);
			$content .= "<a href=\"http://www.imdb.com/find?q=" . $imdb . ";tt=on;nm=on;mx=20\" target=\"_blank\"><img src=\"" .$images. "imdb.gif\" border=\"0\" width=\"16\" height=\"16\"></a>\n";
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

		// Program programid, series and episode information
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

		// # compute suggestions added 10/28/2011
		if($customicon[3] == "suggestion-recording") {
			$totalsuggestions += $tivoarray[$i]['sourcesize'];
			$totalnumsuggestions++;
		}

		// Fix Nagios free space flapping 04/11/12 V.W>
		// Requested in progress recordings are listed first
		if($recording_suggestion == true){
			if($customicon[3] == "in-progress-recording"){
				// print("as a suggestion \n");
				$totalsuggestions += $tivoarray[$i]['sourcesize'];
				$totalnumsuggestions++;
			}
		} else {
			if($customicon[3] != "in-progress-recording"){
				//All in progress recordings should now be suggestions
				$recording_suggestion = true;
			}
		}
	}

	if($tivoarray == null){ // Avoid null values when DVR is off-line
		$totalitems = 0; $freespace = 0; $percent_free = 0.0; $totalsize = 0; $totallength=0; $totalsuggestions = 0;
	} else {
		// adjust for drive size entered too small use total recorded size
		$auto_size_gb = $tivo['size_gb'];
		$auto_size_file_name = ("log". delim . $tivo['name'] . "_drive_size.php");

		if(file_exists($auto_size_file_name)){ // possible new drive size
			// print("yes $auto_size_file_name exists ");
			include($auto_size_file_name);        // $auto_size_gb = "nnnn";
			if($auto_size_gb < 0){
				$auto_size_gb = $tivo['size_gb']; //toGB($totalsize); // disable auto size
			} else {
				//print("new size is $auto_size_gb\n");
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
			// ToDo find some way to update the settings file.
		}
		$totalitems = $tivoarray[0]['totalitems'];
		// Changed to use a better description instead of model using size_gb
		// $freespace = ((intval(trim($tivo['model']))) * 1024) - toMB($totalsize);
		$freespace = ((intval(trim($tivo['size_gb']))) * 1024) - toMB($totalsize);

		// Include suggestions as free space
		$freespace += toMB($totalsuggestions);
		if($freespace < 0) $freespace = 0; // pesky '-' values corrupt nagios
		$percent_free = floor((mBtoGB($freespace) / $tivo['size_gb']) * 1000)/10;
	}

	$footer .= "<br>\n";
	$footer .= "<div class=\"programitem\">\n";
	$footer .= "<div class=\"totalitems\">Total Number of Items: " . $totalitems . "</div>\n";
	$footer .= "<div class=\"totaltime\">Total Length (Recorded Shows): " . mSecsToTime($totallength) . "</div>\n";
	$footer .= "<div class=\"totalsize\">Total Size (Recorded Shows): " . toGB($totalsize) . " GB</div>\n";

	$footer .= "<div class=\"totalsize\">Total Size of ". $totalnumsuggestions ." Suggestions: " . toGB($totalsuggestions) . " GB</div>\n";

	$footer .= "<div class=\"totalsize\">Available Space (including Suggestions): " . mBtoGB($freespace) . " GB\t(" . $percent_free . "% free)</div>\n";

	$footer .= "</div>\n";

	// add a link to the summary page
	$footer .= "<div class=\"dura\"><a href=\"" . $myurl . "summary.htm\" >&larr;&thinsp; back to Summary page </a></div>\n";

	$footer .= "</body></html>";
	$fp1 = @fopen($nowPlaying, "w");
	fwrite($fp1, $header . $content . $footer);
	fclose($fp1);

	// TODO remove debug logging
	// Debug tracking size totals
	// Log file to track drive size and computed drive size history.
	$fpt = @fopen("log". delim . $tivo['name'] . "_track_drive_size.log", 'a');
	fwrite($fpt, "// " .date("F j, Y, g:i a") . "\t" . $tivo['size_gb'] . " GB\t" . toGB($totalsize) . " GB\n");
	fwrite($fpt,"\$auto_size_gb = \"" . $tivo['size_gb'] . "\";\n" );
	fclose($fpt);
	// End of Debug logging code

	if($nplarchives == 1) {	
		/*
	 	* 
	 	*  Archive loop Update once in the first 15 minutes of the hour
	 	*
	 	*  TODO prevent update if run twice in the 15 minutes
	 	*  
	 	*/
		if(date("i") < 16) { // update log only once an hour in the first 15 minutes
			// Track history in a log file in a table body format
			copy($nowPlaying, $rootpath . $archNowPlaying);
			$fpt = @fopen($sug_log_file, 'a');
			fwrite($fpt, "<tr><td>" ."<a href=" . $myurl . $archNowPlaying .">" . $tivo['shorttitle'] .  "</a> </td>");

			// new with link
			fwrite($fpt, "<td sorttable_customkey=\"". date("YmdHi") ."\">" . date("M j, Y, H:i") .  "</td>");
			//fwrite($fpt, "<td>" . toGB($totalsize) . " GB" . "</td>" );
			fwrite($fpt, "<td>" . $tivo['size_gb'] . " GB" . "</td>" );
			fwrite($fpt, "<td>" . mBtoGB($freespace) . " GB"  . "</td>" );
			// Rounding added Dec 11 2012
			fwrite($fpt, "<td>" . floor((mBtoGB($freespace) - toGB($totalsuggestions)) * 100) / 100 . " GB</td>" );

			fwrite($fpt, "<td sorttable_customkey=\"" . $totalsuggestions . "\">(" . $totalnumsuggestions . ") " . toGB($totalsuggestions) . " GB</td></tr>\n" );
			fclose($fpt);

			// Make a header for the summary of suggestions page
			$sug_header .= "<!DOCTYPE html>\n";
			$sug_header .= "<html><head>\n";
			$sug_header .= "<LINK REL=\"shortcut icon\" HREF=\"" .$images. "favicon.ico\" TYPE=\"image/x-icon\">\n\n";
			$sug_header .= "</head><body><sh>\n<title> Suggestions Summary </title><link href=\"" . $summary_css . "\" rel=\"stylesheet\" type=\"text/css\"></sh>\n\n";
			$sug_header .= "<div class=\"dura\"><a href=\"" . $myurl . "summary.htm\" > &larr;&thinsp; back to Summary page </a></div>";
			$sug_header .= "<h2> Last Updated: " . date("F j, Y, g:i a") . " </h2>\n";
			$sug_header .= "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=UTF-8\">\n";
			$sug_header .= "<script src=\"" . $mysorttable . "\" type=\"text/javascript\"></script>";
			// Create the table and add data from the log file
			$sug_table .= "<h4>\n<br><table id=\"Suggestions\" class=\"sortable\" border=\"2\" cellspacing = \"2\" cellpadding = \"4\" align = \"center\" >\n";
			$sug_table .= " <tr> <th> TiVo </th> <th> Date </th> <th class=\"sorttable_numeric\"> Drive Size </th> <th class=\"sorttable_numeric\"> Free Space </th> <th class=\"sorttable_numeric\"> Deleted </th> <th> Suggestions </th> </tr>\n";

			$sug_table .= file_get_contents($sug_log_file);
			$sug_table .= "</table></h4>\n";

			$sug_footer .= "<div class=\"dura\"><a href=\"" . $myurl . "summary.htm\" > &larr;&thinsp; back to Summary page </a></div>";
			$sug_footer .= "</body></html>";

			$fpt1 = @fopen($sug_html_file, 'w');
			fwrite($fpt1, $sug_header . $sug_table . $sug_footer);
			fclose($fpt1);
		} // once an hour

		/// **************************** Suggestions Table
	} // end $nplarchives == 1 check

	// For summary Table
	if($tivoarray == null){		// If TiVo DVR is off line create a place holder
		$sum_table .= "<td bgcolor = \"silver\" ><a href=" . $nowPlaying . " >" . $tivo['shorttitle'] . "</a> </td>";
		$sum_table .= "<td bgcolor = \"silver\">" . $tivo['size_gb'] . " GB</td> ";
		$sum_table .= "<td bgcolor = \"silver\">----</td> ";
		$sum_table .= "<td bgcolor = \"silver\">----</td> ";
		$sum_table .= "<td bgcolor = \"silver\">----</td>";
		$sum_table .= "<td bgcolor = \"silver\"> <a href=" . $sug_html_file . ">----</a></td>";

	}else{ // New add entry to the summary table
		$sum_table .= "<td><a href=" . $nowPlaying . " title=\"Now Playing\">" . $tivo['shorttitle'] . "</a> </td>";

		$sum_table .= "<td>" . $tivo['size_gb'] . " GB</td> ";
		$sum_table .= "<td>" . toGB($totalsize) . " GB</td> ";
		$sum_table .= "<td>" . mBtoGB($freespace) . " GB</td> ";

		// Color indicators for free space warnings.
		if($tivo['critical']  > $percent_free) $sum_table .= "<td bgcolor = \"red\">";
		else if($tivo['warning'] > $percent_free) $sum_table .= "<td bgcolor = \"yellow\">";
		else  $sum_table .= "<td>";
		$sum_table .= $percent_free . "%</td>";

		if($nplarchives ==1) {
			if($totalnumsuggestions < 10) $sum_table .= "<td bgcolor = \"red\">";
			else if($totalnumsuggestions < 20) $sum_table .= "<td bgcolor = \"yellow\">";
			else $sum_table .= "<td>";
			$sum_table .="<a href=" . $sug_html_file . " title=\"Now Playing History\">" .$totalnumsuggestions . "</a></td>";
		}
	}
	// End of Table

	$sum_table .= "</tr>\n";

	$allcontent .= "<br><hr>\n";
	if (file_exists("$image_path". "tivo_" . $tivo['model'] . ".png")){
		$allcontent .= "<h1> <img src=\"" .$images. "tivo_" . $tivo['model'] . ".png\"><br>" . $tivo['nowplaying'] . " </h1>\n";
	} else {
		print("missing image" . "$image_path". "tivo_" . $tivo['model'] . ".png\n");
	 	$allcontent .= "<h1> <img src=" .$images. "tivo_show.gif width=\"26\" height=\"26\"><br> " . $tivo['nowplaying'] . " </h1>\n";
	}
	//TODO alternate colors for each DVR or some way to label which DVR the program is on
	$allcontent .= $content;	// add content to list of all recordings web page

	$alltotalsize 		+= $totalsize;
	$alltotalsuggestions 	+= $totalsuggestions;
	$alltotalnumsuggestions += $totalnumsuggestions;
	$allfreespace 		+= $freespace;
	$alltotalitems 		+=$totalitems;
	$alltotallength 	+= $totallength;
	$all_size_gb 		+= $tivo['size_gb'];

} // End of foreach tivo

$allpercent_free .= floor((mBtoGB($allfreespace) /$all_size_gb)  * 1000)/10;;

/*
 * Add All totals to a line on summary table
 */
$nowPlaying = "alldvrs.htm";
$sum_table .= "<tr> "; // start of new row in the table for summary page data
$sum_table .= "<td><a href=" . $nowPlaying . " title=\"Now Playing\" >" . "ALL" . "</a> </td>";
$sum_table .= "<td>" . $all_size_gb . " GB</td> ";
$sum_table .= "<td>" . toGB($alltotalsize) . " GB</td> ";
$sum_table .= "<td>" . mBtoGB($allfreespace) . " GB</td> ";

// Color indicators for free space warnings.
if($tivo['critical']  > $allpercent_free) $sum_table .= "<td bgcolor = \"red\">";
else if($tivo['warning'] > $allpercent_free) $sum_table .= "<td bgcolor = \"yellow\">";
else  $sum_table .= "<td>";
$sum_table .= $allpercent_free . "%</td>";

if($nplarchives == 1) {
	if($alltotalnumsuggestions < 10) $sum_table .= "<td bgcolor = \"red\">";
	else if($alltotalnumsuggestions < 20) $sum_table .= "<td bgcolor = \"yellow\">";
	else $sum_table .= "<td>";
	$sum_table .= $alltotalnumsuggestions . "</td>";
}
$sum_table .= "</tr>\n";
// end of add all totals

/*
 *  Save totals and summary
 */
// save the summary table
$sum_table  .= "</table>\n</h4>\n";
$sum_footer .= "</body></html>";
$fp1 = @fopen("summary.htm" , "w");
fwrite($fp1, $sum_header . $sum_table . $sum_footer );
fclose($fp1);

// footer for all TiVos
$allfooter .= "<br>\n";
$allfooter .= "<div class=\"programitem\">\n";
$allfooter .= "<div class=\"totalitems\">Total Number of Items: " . $alltotalitems . "</div>\n";
$allfooter .= "<div class=\"totaltime\">Total Length (Recorded Shows): " . mSecsToTime($alltotallength) . "</div>\n";
$allfooter .= "<div class=\"totalsize\">Total Size (Recorded Shows): " . toGB($alltotalsize) . " GB</div>\n";

$allfooter .= "<div class=\"totalsize\">Total Size of ". $alltotalnumsuggestions ." Suggestions: " . toGB($alltotalsuggestions) . " GB</div>\n";

$allfooter .= "<div class=\"totalsize\">Available Space (including Suggestions): " . mBtoGB($allfreespace) . " GB\t(" . $allpercent_free . "% free)</div>\n";

$allfooter .= "</div>\n";

// add a link to the summary page
$allfooter .= "<div class=\"dura\"><a href=\"" . $myurl . "summary.htm\" >&larr;&thinsp; back to Summary page </a></div>";
$allfooter .= "</body></html>";
// end of footer for all TiVos

// all TiVos
$fp1 = @fopen($nowPlaying , "w");
fwrite($fp1, $allheader . $allcontent . $allfooter );
fclose($fp1);

?>
