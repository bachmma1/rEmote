<?php
//
// ===========================================
// ======== Part of the rEmote-WebUI =========
// ===========================================
//
// Contains torrent-list functions
//

function format_time($seconds)
{
	$d = floor($seconds/86400);
	if($d > 0)
	{
		$seconds = $seconds-$d*86400;
		$d .= 'd ';
	}
	else
		$d = '';
	$h = floor($seconds/3600);
	$seconds = $seconds-($h*3600);
	$m = floor($seconds/60);
	$seconds = $seconds-($m*60);
	return(sprintf("%s%02d:%02d:%02d" , $d, $h, $m, floor($seconds)));
}

// Get full list - retrieve full list of torrents
function get_full_list($view, $group, $source, $collapsed)
{
	global $view_arr, $db, $settings, $rpc;

	$request = array('',
			$view_arr[$view],
			'd.complete=',
			'd.completed_bytes=',
			'd.connection_current=',
			'd.down.rate=',
			'd.hash=',
			'd.message=',
			'd.name=',
			'd.peers_complete=',
			'd.peers_connected=',
			'd.peers_not_connected=',
			'd.priority=',
			'd.ratio=',
			'd.size_bytes=',
			'd.up.rate=',
			'd.up.total=',
			'd.is_active=',
			'd.left_bytes=',
			'd.directory=',
			'd.timestamp.started='
	);

	$response = $rpc->request('d.multicall2', $request);

	if($settings['real_multiuser'] && $source < 3)
	{
		switch($source)
		{
			case 0:
				$result = $db->query('SELECT hash FROM torrents WHERE uid = ?', 'i', $_SESSION['uid']);
				break;
			case 1:
				$result = $db->query('SELECT hash FROM torrents WHERE uid = 0');
				break;
			case 2:
				$result = $db->query('SELECT hash FROM torrents WHERE uid = 0 OR uid = ?', 'i', $_SESSION['uid']);
				break;
		}
		$valid_hashes = array();
		while($h = $db->fetch($result))
			$valid_hashes[] = $h['hash'];

		$c = count($response);
		for($x = 0; $x < $c; $x++)
		{
			if(!in_array($response[$x][HASH], $valid_hashes))
				unset($response[$x]);
		}
	}

	if($group == 1)
	{
		if(!($cache = cache_get('tracker')))
			$cache = array();

		$cache_modified = false;
	}
	else if($group == 5)
	{
		$result = $db->query('SELECT hash, uid FROM torrents');
		while($h = $db->fetch($result))
			$hashes[$h['hash']] = intval($h['uid']);
	}

	foreach($response AS $item)
	{
		if($item[LEFT_BYTES] == 0)
			$item[ETA]= '---';
		else if($item[DOWN_RATE] > 0)
			$item[ETA]= format_time($item[LEFT_BYTES]/$item[DOWN_RATE]);
		else
			$item[ETA]= '&infin;';

		$item[PERCENT_COMPLETE] = @round(($item[COMPLETED_BYTES])/($item[SIZE_BYTES])*100);

		if($item[IS_ACTIVE])
		{
			if($item[CONNECTION_CURRENT][0] == 's') // Simply check first character , 's' from 'seed', 'l' from 'leech', only 'seed' and 'leech' possible
				$item[STATUS] = 3;
			else
				$item[STATUS] = 2;
		}
		else
		{
			if($item[COMPLETE])
				$item[STATUS] = 1;
			else
				$item[STATUS] = 0;
		}

		if($group)
		{
			//check the default-collapsed-value and dislay the torrents or not
			//this value can be set in the CP and is saved in the DB per user
			if($collapsed)
				$item[STYLE] = 'display:none';
			else
				$item[STYLE] = '';
			if($group == 1)
			{
				// GROUP BY TRACKER
				if(!isset($cache[$item[HASH]]))
				{
					$response = $rpc->request('t.multicall', array($item[HASH], '', 't.url='));
					$url = parse_url($response[0][0]);
					$cache[$item[HASH]] = $url['host'];
					$cache_modified = true;
				}
				$retarr[$cache[$item[HASH]]][] = $item;
			}
			else if($group == 2)
			{
				// GROUP BY STATUS
				$retarr[$item[STATUS]][] = $item;
			}
			else if($group == 3)
			{
				// GROUP BY MESSAGE
				if($item[MESSAGE] != '')
					$retarr['hasmessage'][] = $item;
				else
					$retarr['nomessage'][] = $item;
			}
			else if($group == 4)
			{
				// GROUP BY TRAFFIC
				if($item[UP_RATE] || $item[DOWN_RATE])
					$retarr['hastraffic'][] = $item;
				else
					$retarr['notraffic'][] = $item;
			}
			else
			{
				// GROUP BY USER
				if($settings['real_multiuser'])
				{
					if(isset($hashes[$item[HASH]]))
						$retarr[$hashes[$item[HASH]]][] = $item;
					else
					{
						$retarr[0] = $item; // Set Torrent Public
						add_missing_hashes();
					}
				}
			}
		}
		else
			$retarr['all'][] = $item;
	}

	if($group == 1 && $cache_modified)
		cache_put('tracker', $cache, $_SESSION['uid'], time() + 864000);   // Cache expires in 10 Days...

	if(isset($retarr))
		return($retarr);
	else
		return(false);
}

function sort_torrents_ASC($a, $b)
{
	return(strtolower($a[$_SESSION['sortkey']]) > strtolower($b[$_SESSION['sortkey']]));
}

function sort_torrents_DESC($a, $b)
{
	return(strtolower($a[$_SESSION['sortkey']]) < strtolower($b[$_SESSION['sortkey']]));
}


?>
