<?php

class Shoutbox
{
	var $smileyPattern = '#(:\-\)|:\)|:\-\(|:\(|;\-\)|;\)|:\||:\-\||8\-\)|8\)|:O|:\-O)#';
	var $smileys = array(
		':-)' => 'smile.gif',
		':)'  => 'smile.gif',
		':('  => 'sad.gif',
		':-(' => 'sad.gif',
		';-)' => 'wink.gif',
		';)'  => 'wink.gif',
		':|'  => 'neutral.gif',
		':-|' => 'neutral.gif',
		'8)'  => 'cool.gif',
		'8-)' => 'cool.gif',
		':O'  => 'shock.gif',
		':-O' => 'shock.gif'
	);

	function replaceSmileys($string)
	{
		global $smileyimgs;

		if(is_array($string))
			return "<img src=\"{$smileyimgs}{$this->smileys[$string[1]]}\" alt=\"{$string[1]}\" />";

		return preg_replace_callback($this->smileyPattern, array($this, 'replaceSmileys'), $string);
	}

	function getShouts()
	{
		global $db, $lng, $qsid;

		$result = $db->query('SELECT u.name, s.sid, s.uid, s.message, s.time FROM users u INNER JOIN shouts s ON u.uid = s.uid ORDER BY time DESC LIMIT 30');

		$shouts = '<table>';
		while($h = $db->fetch($result))
			$shouts .= sprintf('<tr><td><strong>%s</strong><br /><span class="hint">%s</span></td><td>%s</td><td>%s</td></tr>',
				$db->out($h['name']),
				date('d.m.y H:i', $h['time']),
				$this->replaceSmileys($db->out($h['message'])),
				'&nbsp;' // REPLACE BY DELETE-LINK
			);
		$shouts .= '</table>';

		return $shouts;
	}


	function makeShoutbox()
	{
		global $db, $lng, $qsid;


		if(isset($_POST['shout']) && (trim($_POST['shout']) != ''))
		{
			$hash = sha1($_SESSION['uid'].$_POST['shout'].$_POST['time']);
			if(!intval($db->one_result($db->query('SELECT COUNT(*) AS c FROM shouts WHERE hash = ?', 's', $hash))))
				$db->query('INSERT INTO shouts (uid, time, message, hash) VALUES (?, ?, ?, ?)', 'iiss',
					$_SESSION['uid'],
					time(),
					$_POST['shout'],
					$hash);
		}

		$shouts  = '<div id="shouts">';
		$shouts .= $this->getShouts();
		$shouts .= '</div>';

		$shout  = "<div id=\"shout\"><form action=\"index.php$qsid\" method=\"post\"><div><input type=\"text\" name=\"shout\" class=\"text\" />";
		$shout .= "<input type=\"hidden\" name=\"time\" value=\"".time()."\" /><input class=\"submit\" type=\"submit\" value=\"{$lng['shout']}\" /></div></form></div>";

		return "$shouts<hr />$shout";
	}
}

class BoxArea
{
	const BOX_SPEEDSTATS       = 1;
	const BOX_DISKSTATS        = 2;
	const BOX_BANDWITHSETTINGS = 3;
	const BOX_FILTER           = 4;
	const BOX_REFRESHSETTINGS  = 5;
	const BOX_SERVERSTATS      = 6;
	const BOX_SHOUTBOX         = 7;

	public function renderBoxSpeedstats()
	{
   	global $settings, $global, $imagedir, $lng;
		
		$percup  = $settings['maxupspeed'] == 0 ? 0 : $global['upspeed']*100/($settings['maxupspeed']*1024);
		$percdwn = $settings['maxdownspeed'] == 0 ? 0 : $global['downspeed']*100/($settings['maxdownspeed']*1024);
		
		$box  = "<div class=\"box\" id=\"boxspeed\"><h2>{$lng['speed']}</h2><div class=\"boxcontent\">";
		$box .= "<div class=\"label\"><img src=\"{$imagedir}max_up.png\" alt=\"Up\" /></div><div id=\"boxup\">".progressbar($percup > 100 ? 100 : $percup, format_bytes($global['upspeed']).'/s</div>');
		$box .= "<div class=\"label\"><img src=\"{$imagedir}max_down.png\" alt=\"Down\" /></div><div id=\"boxdown\">".progressbar($percdwn > 100 ? 100 : $percdwn, format_bytes($global['downspeed']).'/s</div>');
		$box .= "</div></div>";

		return $box;
	}
	
	public function renderBoxDiskstats()
	{
		global $lng;

		$free = disk_free_space($_SESSION['dir']); $total = disk_total_space($_SESSION['dir']); $progress = ($total - $free) / $total * 100;
		$free = format_bytes($free); $total = format_bytes($total);
		
		$box  = "<div class=\"box\" id=\"boxdisk\"><h2>{$lng['diskspace']}</h2><div class=\"boxcontent\">";
		$box .= "<div>{$lng['freespace']}:<br />$free/$total</div>";
		$box .= progressbar($progress);
		$box .= '</div></div>';

		return $box;
	}

	public function renderBoxBandwithsettings()
	{
		global $imagedir, $lng, $global, $qsid;

		$upspeed   = intval($global['uplimit']/1024);
		$downspeed = intval($global['downlimit']/1024);
		$box  = "<div class=\"box\" id=\"boxbandwith\"><h2>{$lng['maxspeeds']}</h2><div class=\"boxcontent\">";
		$box .= "<form action=\"control.php$qsid\" method=\"post\">";
		$box .= "<div class=\"label\"><img src=\"{$imagedir}max_up.png\" alt=\"Up\" /></div><div><input type=\"text\" class=\"num\" name=\"maxup\" value=\"$upspeed\" />&nbsp;KB/s</div>";
		$box .= "<div class=\"label\"><img src=\"{$imagedir}max_down.png\" alt=\"Down\" /></div><div><input type=\"text\" class=\"num\" name=\"maxdown\" value=\"$downspeed\" />&nbsp;KB/s</div>";
		$box .= "<input type=\"submit\" class=\"submit\" name=\"maxspeeds\" value=\"{$lng['apply']}\" /></div>";
		$box .= '</form></div>';

		return $box;
	}

	public function renderBoxFilter()
	{
		global $lng, $qsid, $ftext;

		$box  = "<div class=\"box\" id=\"boxfilter\"><h2>{$lng['filter']}</h2><div class=\"boxcontent\">";
		$box .= "<form action=\"index.php$qsid\" method=\"post\">";
		$box .= "<div><input type=\"text\" name=\"ftext\" class=\"text\" value=\"$ftext\" onkeyup=\"filter( this );\" /></div>";
		$box .= "<div><input type=\"submit\" name=\"fsubmit\" id=\"fsubmit\" class=\"submit\" value=\"{$lng['apply']}\" /></div></form></div></div>";
		
		return $box;
	}

	public function renderBoxRefreshsettings()
	{
		global $lng, $refresh_arr, $qsid;

		$box  = "<div class=\"box\" id=\"boxrefresh\"><h2>{$lng['refresh']}</h2><div class=\"boxcontent\">";
		$box .= "<form action=\"control.php$qsid\" method=\"post\">";
		$box .= "<div><label for=\"refinterval\">{$lng['interval']}:</label> <input type=\"text\" class=\"num\" name=\"refinterval\" id=\"refinterval\" value=\"{$_SESSION['refinterval']}\" />&nbsp;{$lng['sec']}</div>";
		$box .= '<div><select name="refmode">';
		foreach($refresh_arr as $key => $val)
		{
			if($_SESSION['refmode'] == $key)
				$box .= "<option value=\"$key\" selected=\"selected\">{$lng[$val]}</option>";
			else
				$box .= "<option value=\"$key\">{$lng[$val]}</option>";
		}
		$box .= "</select>";
		$box .= "<input type=\"submit\" name=\"refsubmit\" id=\"refsubmit\" class=\"submit\" value=\"{$lng['apply']}\" /></div></form></div></div>";
		
		return $box;
	}

	public function renderBoxServerstats()
	{
		global $lng, $global;

		$box    = "<div class=\"box\" id=\"boxserver\"><h2>{$lng['serverinfo']}</h2>";
		$box   .= "<div class=\"boxcontent\">rEmote: {$global['versions']['remote']}<br />rtorrent: {$global['versions']['rtorrent']}<br />libtorrent: {$global['versions']['libtorrent']}<hr />";
		$l = fopen('/proc/loadavg', 'r');
		$loads = explode(' ', fgets($l));
		fclose($l);
		$perc = $loads[0] > 1 ? 100 : ($loads[0]*100);
		$box .= "<div id=\"boxload\"><div>{$lng['load']}: {$loads[0]} {$loads[1]} {$loads[2]}</div>".progressbar($perc, $perc.'%').'</div>';
		$box .= '</div></div>';

		return $box;
	}

	public function renderBoxShoutbox()
	{
		global $lng;

		$shoutbox = new Shoutbox();

		$box  = "<div class=\"box\" id=\"boxshoutbox\"><h2>{$lng['shoutbox']}</h2>";
		$box .= "<div class=\"boxcontent\">";
		$box .= $shoutbox->makeShoutbox();
		$box .= '</div></div>';
		
		return $box;
	}




	public function renderBox($boxname)
	{
		global $settings;

   	switch($boxname)
		{
			case BoxArea::BOX_SPEEDSTATS:
            return $this->renderBoxSpeedstats();

			case BoxArea::BOX_DISKSTATS:
            return $this->renderBoxDiskstats();

			case BoxArea::BOX_BANDWITHSETTINGS:
            return $this->renderBoxBandwithsettings();

			case BoxArea::BOX_FILTER:
            return $this->renderBoxFilter();

			case BoxArea::BOX_REFRESHSETTINGS: 
            return $this->renderBoxRefreshsettings();

			case BoxArea::BOX_SERVERSTATS:
				if(($_SESSION['status'] <= USER) || !$settings['user_see_serverinfo'])
					return '';
            return $this->renderBoxServerstats();

			case BoxArea::BOX_SHOUTBOX:
				if(!$settings['shoutbox'])
					return '';
            return $this->renderBoxShoutbox();
		}
	}

	public function renderArea($boxes, $id)
	{
		$str = '';

   	foreach($boxes as $b)
		{
			$str .= $this->renderBox($b);
		}

		return "<div class=\"boxarea\" id=\"$id\">$str</div>";
	}
}




?>