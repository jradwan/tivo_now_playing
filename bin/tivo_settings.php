<?php

/*
 *  Define file locations from the point of view for index.php
 *  
 *  IE "C:\Program Files\tnpl"
 *  or "/Users/myMac/TNPL/"
 *
 * NOTE: 
 * delim and $binpath has been moved to index.php allowing this settings file to be in a private location
 * 
 */
$root_path = "." . delim;	//The full path so PHP can find its assosiated files. relative locations may me used
$image_path = $root_path . "images" . delim ; // file path to images TODO get the images from an external source
$xml_path = "xml/"; // Temporary location for data downloaded from TiVos may be shared with other instances of index.php
// Location and running options for the wget program
define("wgetpath", delim . "usr" . delim . "local" . delim . "bin" . delim . "wget --no-check-certificate");
define("tivoport", "80");


/*
 * Locations defined from the point of view of the browser
 * Used in the html pages to link to archives and back to the index
 * 
 * TODO find a way to reference the location in the browser 
 * 
 */
//$myurl = "file:///Users/MyMac/Desktop/tnpl20130914/"; // for local server and testing
$myurl = "";

/*
 *  If using the default tree the following needs no modification
 */
$mybin	= $myurl . "bin/";  	// HTML path to find support files ie JavaScript
$images = $myurl . "images/"; 	// HTML path to images

// Java Script
$mytjs = $mybin . "tivo_now_playing.js";
$mysorttable = $mybin . "sorttable.js";

// css files
$mycss = $mybin . "tivo.css"; 	// HTML path for css used in main pages
$summary_css = $mycss;		// HTML path for css used in the summary table page

/*
 *  Settings for the TiVos can be overridden for each box
 */
$mymak    = "1234567890";	// MAK address for your TiVo; find it online or in settings on the TiVo
$mysubnet = "192.168.1";	// First 3 quads of the IP address Saves typing (and typo errors)
$mywrn    = "15";		// When % free space gets below this value color changes to yellow 
$mycrit   = "10";		// Below this value color changes to red

/*
 *  Settings for each TiVo monitored
 *  Note: size_gb will be adjusted upward as drive gets full. Auto size can be overridden see log/$name$_drive_size.php
 */
$tivos = array(
	//
	"t1" => array("ip" => "$mysubnet.101", "mak" => $mymak, "model"=> "648", "size_gb" => "250", "warning" => $mywrn, "critical" => $mycrit, "name" => "TiVo1", "nowplaying" => "Tivo1 - Now Playing", "feedtitle" => "TiVo1", "shorttitle" => "T1", "feeddescription" => "TiVo1 - Now Playing", "feedlink" => $myurl, "css" => $mycss, "js" => $mytjs),
	//
	"t2" => array("ip" => "$mysubnet.102", "mak" => $mymak, "model"=> "652", "size_gb" => "1000", "warning" => $mywrn, "critical" => $mycrit, "name" => "TiVo2", "nowplaying" => "TiVo2 - Now Playing", "feedtitle" => "TiVo2","shorttitle" => "T2", "feeddescription" => "TiVo2 - Now Playing", "feedlink" => $myurl, "css" => $mycss, "js" => $mytjs),
	//
	"t3" => array("ip" => "$mysubnet.103", "mak" => $mymak, "model"=> "748", "size_gb" => "500", "warning" => $mywrn, "critical" => $mycrit, "name" => "TiVo3", "nowplaying" => "TiVo3 - Now Playing", "feedtitle" => "TiVo3", "shorttitle" => "T3", "feeddescription" => "TiVo3 - Now Playing", "feedlink" => $myurl, "css" => $mycss, "js" => $mytjs),
	//
);


/*
 * 	Other options
 */
$dorss = 0; 			// 0 or 1 : create rss files
$disabledownloadlinks = 1; 	// 0 or 1 : 0 = show download links in html
$disablexmllinks = 1; 		// 0 or 1 : hyperlink 0 = show title to xml data
$gfxicons = 0; 			// 0 or 1 : use graphic icons (tivo logos)
$imdblinks = 1; 		// 0 or 1 : create additional image w/links to imdb.com
$nplarchives = 0;		// 0 or 1 : 0 = no NPL archiving; 1 = archiving enabled

?>
