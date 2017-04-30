<?php

// The data downloaded from the TiVo expires in:
$mincycle = (60 * 10);			// 10 minutes *default*
//$mincycle = (60 * 30);		// 30 minutes
//$mincycle = (60 * 60);		// 1 hour
//$mincycle = (60 * 60) * 2;		// 2 hours

function msecsToTime($content)
{
	$secs = floor($content / 1000);
	$mins = floor($secs / 60);
	$hours = floor($mins / 60);
	$secs = $secs % 60;
	$mins = $mins % 60; 
	if (strlen($mins) < 2) $mins = "0" . $mins;
	return "$hours" . ":" . "$mins";
} 

function tivoDate($format, $input)
{ 
	return date($format, hexdec($input) + 5);
} 

// Bytes to MB
function toMB ($input)
{
	return floor($input / 1048576);
} 


// Bytes to GB
function toGB ($input)
{
	return floor(($input / 1073741824) * 10) / 10;
}

// MB to GB
function mBtoGB ($input)
{
	return floor(($input / 1024) * 10) / 10;
}

class Tivo_XML {
	var $wget;
	var $mak;
	var $host;
	var $parser;
	var $dvr;
	var $mysyserr;
	var $xml_path;

	function setOpt($option, $param)
	{
		if ($option == "wget") $this->wget = $param;
		if ($option == "xml_path") $this->xml_path = $param;
		if ($option == "mak") $this->mak = $param;
		if ($option == "host") $this->host = $param;
		if ($option == "dvr") {
			$this->dvr = $param;
		} else {
			$this->dvr = "tivo";
		} 
	} 

	function getErr(){
		global $mysyserr;
	  return $mysyserr;
	}

	function init()
	{
		global $depth, $itemcount, $items, $mysyserr,$xml_path;
		if($xml_path == ""){
		 print("OhOh!\n");
		 $this->xml_path = "xml" . delim;
		}

		$items = array();
		$depth = 0;
		$itemcount = 0;
		$mysyserr = false;
		$this->grabTiVoXML(0);

	} 

	function grabTiVoXML($more)
	{
		global $itemcount, $mysyserr, $xml_path;

		if ($more == 0) {
			$url = "\"https://$this->host/TiVoConnect?Command=QueryContainer&Container=%2FNowPlaying&Recurse=Yes&ItemCount=50\"";
		} else $url = "\"https://$this->host/TiVoConnect?Command=QueryContainer&Container=%2FNowPlaying&Recurse=Yes&ItemCount=50&AnchorOffset=$itemcount\"";

		$wgetnp = "$this->wget --http-user=tivo --http-passwd=$this->mak --output-document=" . $xml_path . "$this->dvr\"_nowplaying$more.xml\" $url";

		if (file_exists($xml_path . $this->dvr . "_nowplaying" . $more . ".xml")) {
			$timedif = @(time() - filemtime($xml_path . $this->dvr . "_nowplaying" . $more . ".xml"));
			global $mincycle;  // defined at the top of the file and easly changed
			if ($timedif > $mincycle) {
				$nowplayingresult = system($wgetnp, $retval);
			} 
		} else { $nowplayingresult = system($wgetnp, $retval);}
	if($retval) $mysyserr = true;
	} 

	function arristr($haystack = '', $needle = array())
	{
		foreach($needle as $n) {
			if (stristr($haystack, $n) !== false) {
				return true;
			} 
		} 

		return false;
	} 

	function parseTiVoXML()
	{
		global $items, $xml_path;
		$itemcount = 0;
		$count = 0;
		do {
			$this->parser = xml_parser_create();
			xml_set_object($this->parser, $this);

			xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
			xml_set_element_handler($this->parser, "_tivostart_element", "_tivoend_element");
			xml_set_character_data_handler($this->parser, "_tivodata");
			if ($itemcount != 0) $this->grabTiVoXML($count);
			$file = $xml_path . $this->dvr . "_nowplaying" . $count . ".xml";
			$tivoxmlstr = file_get_contents ($file);
			$tivoxmlstr = str_replace("&", "%amp;%", $tivoxmlstr);
			$tivoxmlstr = str_replace("A%amp;%amp;E", "A%amp;%E", $tivoxmlstr);
			if($tivoxmlstr != ""){
				xml_parse($this->parser, $tivoxmlstr , true) or
			  	  die (sprintf("XML Error: %s at line %d",
				    xml_error_string(xml_get_error_code($this->parser)),
				      xml_get_current_line_number($this->parser)));
			}
			xml_parser_free($this->parser);
			$itemcount += $items[0]['tcitemcount'];
			$count++;
		} while ($itemcount < $items[0]['totalitems']);
		return $items;
	} 

	function _tivostart_element($parser, $name, $attribs)
	{
		global $depth, $currenttag, $items, $itemcount;
		$depth++;

		if ($name == "TotalItems" && $depth == 3) {
			$currenttag = $name;
		} 
		if ($name == "LastChangeDate" && $depth == 3) {
			$currenttag = $name;
		} 
		if ($name == "ContentType" && $depth == 3) {
			$currenttag = "TC" . $name;
		} 
		if ($name == "SourceFormat" && $depth == 3) {
			$currenttag = "TC" . $name;
		} 
		if ($name == "Title" && $depth == 3) {
			$currenttag = "TC" . $name;
		} 
		if ($name == "SortOrder" && $depth == 2) {
			$currenttag = "TC" . $name;
		} 
		if ($name == "ItemStart" && $depth == 2) {
			$currenttag = "TC" . $name;
		} 
		if ($name == "ItemCount" && $depth == 2) {
			$currenttag = "TC" . $name;
		} 
		if ($name == "Item" && $depth == 2) {
			$currenttag = $name;
			$itemcount++;
		} 
		if ($name == "Title" && $depth == 4) {
			$currenttag = $name;
		} 

		if ($name == "Content" && $depth == 4) {
			$ndepth = 5;
			$currenttag = $name;		} 

		if ($name == "CustomIcon" && $depth == 4) {
			$ndepth = 5;
			$currenttag = $name;
		} 

		if ($name == "TiVoVideoDetails" && $depth == 4) {
			$ndepth = 5;
			$currenttag = $name;
		} 

		if ($name == "SourceSize" && $depth == 4) {
			$currenttag = $name;
		} 
		if ($name == "Duration" && $depth == 4) {
			$currenttag = $name;
		} 
		if ($name == "CaptureDate" && $depth == 4) {
			$currenttag = $name;
		} 
		if ($name == "Description" && $depth == 4) {
			$currenttag = $name;
		} 
		if ($name == "SourceChannel" && $depth == 4) {
			$currenttag = $name;
		} 
		if ($name == "SourceStation" && $depth == 4) {
			$currenttag = $name;
		} 
		if ($name == "EpisodeTitle" && $depth == 4) {
			$currenttag = $name;
		} 
		// NEW Prep for folders (also I find this info usefull)
		if ($name == "ProgramId" && $depth == 4) {
			$currenttag = $name;
		} 
		if ($name == "SeriesId" && $depth == 4) {
			$currenttag = $name;
		} 
		if ($name == "EpisodeNumber" && $depth == 4) {
			$currenttag = $name;
		} 
		if ($name == "TvRating" && $depth == 4) {
			$currenttag = $name;
		} 
		if ($name == "MpaaRating" && $depth == 4) {
			$currenttag = $name;
		} 
	} 

	function _tivoend_element($parser, $name)
	{
		global $depth, $items;
		$depth--;
	} 

	function _tivodata($parser, $data)
	{
		$data = str_replace("%amp;%", "&amp;", $data);
		$data = str_replace("%quot;%", "&quot;", $data);

		global $currenttag, $items, $itemcount;

		switch ($currenttag) {
			case "Title":
				$items[$itemcount]['title'] = $data;
				$currenttag = "";
				break;
			case "Content":
				if (tivoport != "80") {
					$data = str_replace(":80", ":" . tivoport, $data);
				} 
				$items[$itemcount]['content'] = $data;
				$currenttag = "";
				break;
			case "TiVoVideoDetails":
				$items[$itemcount]['tivovideodetails'] = $data;
				$currenttag = "";
				break;
			case "CustomIcon":
				$items[$itemcount]['customicon'] = $data;
				$currenttag = "";
				break;
			case "SourceSize":
				$items[$itemcount]['sourcesize'] = $data;
				$currenttag = "";
				break;
			case "Duration":
				$items[$itemcount]['duration'] = $data;
				$currenttag = "";
				break;
			case "CaptureDate":
				$items[$itemcount]['capturedate'] = $data;
				$currenttag = "";
				break;
			case "Description":
				$items[$itemcount]['description'] = $data;
				$currenttag = "";
				break;
			case "SourceChannel":
				$items[$itemcount]['sourcechannel'] = $data;
				$currenttag = "";
				break;
			case "SourceStation":
				$items[$itemcount]['sourcestation'] = $data;
				$currenttag = "";
				break;
			case "EpisodeTitle":
				$items[$itemcount]['episodetitle'] = $data;
				$currenttag = "";
				break;
			case "TotalItems":
				$items[0]['totalitems'] = $data;
				$currenttag = "";
				break;
			case "LastChangeDate":
				$items[0]['lastchangedate'] = $data;
				$currenttag = "";
				break;
			case "TCContentType":
				$items[0]['tccontenttype'] = $data;
				$currenttag = "";
				break;
			case "TCSourceFormat":
				$items[0]['tcsourceformat'] = $data;
				$currenttag = "";
				break;
			case "TCTitle":
				$items[0]['tctitle'] = $data;
				$currenttag = "";
				break;
			case "TCSortOrder":
				$items[0]['tcsortorder'] = $data;
				$currenttag = "";
				break;
			case "TCItemStart":
				$items[0]['tcitemstart'] = $data;
				$currenttag = "";
				break;
			case "TCItemCount":
				$items[0]['tcitemcount'] = $data;
				$currenttag = "";
				break;
			// NEW Prep for folders (also I find this info usefull)
			case "ProgramId":
				$items[$itemcount]['programid'] = $data;
				$currenttag = "";
				break;
			case "SeriesId":
				$items[$itemcount]['seriesid'] = $data;
				$currenttag = "";
				break;
			case "EpisodeNumber":
				$items[$itemcount]['episodenumber'] = $data;
				$currenttag = "";
				break;
			case "TvRating":
				$items[$itemcount]['tvrating'] = $data;
				$currenttag = "";
				break;
			case "MpaaRating":
				$items[$itemcount]['mpaarating'] = $data;
				$currenttag = "";
				break;
			default:
				break;
		} 
	} 
}
 
/*
 * 	Overcome PHPv4 limitations mkdir not supporting recursion
 *	Uses same syntac as mkdiros
 *
 *	Note: Currently does not work creating from the root directory (leading '/')
 *
 */
function mkdirV4($dir_name = null, $mode = 0777, $recursive = false){
 if ($dir_name == null){
    return false;
 }

  if($recursive){
    $check_dir = "";
    $dirs = explode('/', $dir_name);
  
    foreach($dirs as $tmp_dirs){
      if ($tmp_dirs == ""){
        continue;
      }
      $check_dir = $check_dir . $tmp_dirs . '/';
      if (is_dir($check_dir)){
        continue;
      } else {
        mkdir($check_dir, $mode);
      }
    }
    return is_dir($dir_name);
  } else {
    return mkdir($dir_name, $mode);
  } 
} 


?>
