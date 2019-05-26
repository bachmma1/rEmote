<?php

// Define Session-Functions

class REmoteSessionHandler
{

	public static $sessionexisted = false;

	static function open()
	{
		return true;
	}

	static function close()
	{
		return true;
	}

	static function read($id)
	{
		global $settings, $db;

		$result = $db->query('SELECT data, time, permanent FROM sessions WHERE sid = ?', 's', $id);
		if($result && ($data = $db->fetch($result)))
		{
			REmoteSessionHandler::$sessionexisted = true;
			if(($data['time'] > (time() - $settings['session_lifetime'])) || intval($data['permanent']))
				return($data['data']);
		}
		return '';
	}

	static function write($id, $data)
	{
		global $db, $settings;

		if(REmoteSessionHandler::$sessionexisted)
		{
			if(!defined('IS_REFRESH') || $settings['refresh_no_timeout'])
				$res = $db->query('UPDATE sessions SET data = ?, time = ? WHERE sid = ?', 'sis',
										$data,
										time(),
										$id);
			else
				$res = $db->query('UPDATE sessions SET data = ? WHERE sid = ?', 'ss',
										$data,
										$id);
		}
		else
			$res = $db->query('INSERT INTO sessions (sid, data, time, uid, permanent) VALUES (?, ?, ?, ?, ?)', 'ssiii',
									$id,
									$data,
									time(),
									$data['uid'],
									$data['permanent']);

		return($db->affected_rows($res));
	}

	static function destroy($id)
	{
		global $db;

		$_SESSION = array();  // Delete CURRENT
		$result = $db->query('DELETE FROM sessions WHERE sid = ?', 's', $id);

		return $db->affected_rows($result);
	}

	static function gc($max)
	{
		global $db;

		logger(LOGINFOS, 'Session-Garbage-Cleaner called', __FILE__, __LINE__);
		$db->query('DELETE FROM sessions WHERE permanent = 0 AND time < ?', 'i', time() - $max - 1); // Give a bonus-second bonus for the case: Valid Called, invalid written...

		return true;
	}
}

/* Tell PHP to use Session functions */
/* ini_set('session.save_handler', 'user'); <== no longer needed since php 7.2+ */ 
session_set_save_handler('REmoteSessionHandler::open', 'REmoteSessionHandler::close', 'REmoteSessionHandler::read', 'REmoteSessionHandler::write', 'REmoteSessionHandler::destroy', 'REmoteSessionHandler::gc');

?>
