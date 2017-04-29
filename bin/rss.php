<?php
//   RSS code cut from index.php 
		$rssheader = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><rss version=\"2.0\"><channel><title>" . $tivo['feedtitle'] . "</title><link>" . $tivo['feedlink'] . "</link> <description>" . $tivo['feeddescription'] . "</description>";
		for ($i = 1;$i < count($tivoarray);$i++) {
			$sc = explode("-", $tivoarray[$i]['sourcechannel']);
			$rsscontent .= "<item>\n";
			$rsscontent .= "<title>" . $tivoarray[$i]['title'];
			if ($tivoarray[$i]['episodetitle'] != "")
				$rsscontent .= ":" . $tivoarray[$i]['episodetitle'];
			$rsscontent .= "</title>\n";
			$rsscontent .= "<description>\n";
			if ($tivoarray[$i]['description'] != "") {
				$rsscontent .= "Description:" . $tivoarray[$i]['description'] . "&lt;br&gt;";
			} 
			$rsscontent .= "Channel: " . $tivoarray[$i]['sourcestation'] . " (" . $sc[0] . ")&lt;br&gt;";
			$rsscontent .= "Recorded: " . tivoDate("g:i a - F j, Y", $tivoarray[$i]['capturedate']) . "&lt;br&gt;";
			$rsscontent .= "Size:" . toMB($tivoarray[$i]['sourcesize']) . " MB&lt;br&gt;";
			$rsscontent .= "Duration:" . mSecsToTime($tivoarray[$i]['duration']) . "&lt;br&gt;";
			$rsscontent .= "</description>\n";
			$rsscontent .= "</item>\n";
		} 
		$rssfooter .= "</channel>\n";
		$rssfooter .= "</rss>";

		$fp = @fopen($tivo['name'] . "_rss_nowplaying.xml", "w");
		fwrite($fp, $rssheader . $rsscontent . $rssfooter);
		fclose($fp);
// end of RSS code cut from index.php
 ?>