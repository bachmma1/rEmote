<?php

define('TO_ROOT', './');
define('ACTIVE',  'add');

require_once('inc/global.php');
require_once('inc/functions/add.fun.php');
require_once('inc/functions/file.fun.php');
require_once('inc/functions/torrents.fun.php');
require_once('inc/header.php');

$diroptions_arr = array('dirnochange', 'dirchngonce', 'dirchngperm');

logger(LOGDEBUG, 'Called add.php', __FILE__, __LINE__);

if(isset($_SESSION['last']))
	$last = $_SESSION['last'];

$_SESSION['fileview'] = 0;

if(isset($_REQUEST['change_dir']))
{
	$current_dir = clean_dir($_REQUEST['change_dir']);
	if(!is_valid_dir($current_dir))
		$current_dir = $_SESSION['rootdir'];
}
else if(isset($last))
	$current_dir = $last;
else
	$current_dir = clean_dir($_SESSION['rootdir']);



if(isset($_POST['add']))
{

	/* So we got something.. */
	if(isset($_POST['start']) && $_POST['start'] == "true")
		$start = true;
	else
		$start = false;
	
	if(isset($_POST['add_resume_data']) && $_POST['add_resume_data'] == "true")
		$add_resume_data = true;
	else
		$add_resume_data = false;

	logger(LOGDEBUG, 'POST-Parameter "add" is given (start is '.intval($start).')', __FILE__, __LINE__);

	$added_anything = false;

	/* Let's change the directory first */
	$changeback = false;
	if(isset($_POST['diroptions']) && $_POST['diroptions'])
	{
		if($valid_dir = is_valid_dir(clean_dir($_POST['directory'])))
		{
			$dir = clean_dir($_POST['directory']);
			if($_POST['diroptions'] == 2) /* Change permanent */
			{
				$_SESSION['dir'] = clean_dir($_POST['directory']);
				$db->query("UPDATE users SET dir = ? WHERE uid = ?", 'ss', $_SESSION['dir'], $_SESSION['uid']);
			}
		}
	}
	else
	{
		$valid_dir = true;
		$dir = $_SESSION['dir'];
	}

	if($valid_dir)
	{
		if(isset($_POST['public']) && $_POST['public'] == 'true')
			$public = true;
		else
			$public = false;

		if(!$settings['disable_sem'])
		{
			$sem = sem_get(SEM_KEY);
			if(!sem_acquire($sem))
				fatal("Could not acquire Semaphore!", __FILE__, __LINE__);
		}


		set_directory($dir);
		/* So let's have a look on the add-by-url-fields */
		for($x = 1; isset($_POST["addbyurl$x"]); $x++)
		{
			if($_POST["addbyurl$x"] != '')
			{
				if(($err = get_torrent($_POST["addbyurl$x"], $public, $start, $add_resume_data)) != '')
					$invalid[] = "{$_POST["addbyurl$x"]} - $err";
				else
					$added_anything = true;
			}
		}

		/* Now the add Torrens via Upload */
		for($x = 1; isset($_FILES["addbyfile$x"]); $x++)
		{
			if($_FILES["addbyfile$x"]['size'])
			{
				logger(LOGDEBUG, 'Uploaded file (number '.$x.'), libtorrent resume-data is '.intval($add_resume_data), __FILE__, __LINE__);

				if($add_resume_data)
            	add_libtorrent_resume_data($_FILES["addbyfile$x"]['tmp_name']);

				if(($err = add_file($_FILES["addbyfile$x"]['tmp_name'], $_FILES["addbyfile$x"]['name'], $public, $start)) != '')
					$invalid[] = "{$_FILES["addbyfile$x"]['name']} - $err";
				else
					$added_anything = true;
			}
		}

		if(isset($invalid) && count($invalid))
		{
			$error = $lng['adderror'].'<br />';
			foreach($invalid as $oneinvalid)
				$error .= '<br />'.htmlspecialchars($oneinvalid, ENT_QUOTES);
		}

		if(!$settings['disable_sem'])
			sem_release($sem);
	}
	else
		$error = $lng['addinvdir'];

	if($added_anything && !isset($error) && (!isset($_POST['more']) || $_POST['more'] != 'true'))
		$out->redirect("index.php$qsid");
}

$_SESSION['last'] = $current_dir;

if(addJobChecker())
	$m = $out->getMessages();
else
	$m = '';
$out->content = "<div id=\"main\">$header<div id=\"content\">$m<form action=\"add.php$qsid\" method=\"post\" enctype=\"multipart/form-data\">";


if(isset($error))
	$out->content .= "<div class=\"error\">$error</div>";

/********************AUSGETAUSCHT DURCH FILEBROWSER*****************************
$addbyurl  = "<fieldset class=\"box\"><legend>{$lng['addbyurl']}</legend>";
for($x = 1; $x <= $settings['maxaddfieldsurl']; $x++)
	$addbyurl .= "<div class=\"addfield\">$x.&nbsp;<input type=\"text\" class=\"text\" name=\"addbyurl$x\" /></div>";
$addbyurl .= "</fieldset>";
*****************************************************************************/

$adddir    = "<fieldset class=\"box\"><legend>{$lng['diroptions']}</legend>";

$adddir .= "<div id=\"changefolder\">";
$adddir .= "<img src=\"{$imagedir}folder_open.png\" alt=\"dir\" />&nbsp;";
$adddir .= "<input class=\"longinput\" type=\"text\" name=\"directory\" value=\"$current_dir\"\" />&nbsp;";
$adddir .= "<input style=\"width: 500px; display: none;\" type=\"text\" class=\"text\" name=\"change_dir\" value=\"$current_dir\" />";
$adddir .= "<a title=\"{$lng['folder_home']}\" href=\"add.php?change_dir=" . rawurlencode($_SESSION['rootdir']) . "$sid\"><img src=\"{$imagedir}folder_home.png\" alt=\"home\" /></a>";
$adddir .= "<a title=\"{$lng['folder_up']}\" href=\"add.php?change_dir=" . rawurlencode(clean_dir($current_dir.'../')) . "$sid\"><img src=\"{$imagedir}folder_up.png\" alt=\"up\" /></a>";
$adddir .= "</div>";

$adddir  .= "<div class=\"hint\">Der ausgewählte Ordner wird verwendet</div>";
$adddir   .= "<select name=\"diroptions\" style=\"display:none;\">";
foreach($diroptions_arr as $dirkey => $diroption)
	if($diroption == "dirchngonce") 
		$adddir .= "<option value=\"$dirkey\" selected>{$lng[$diroption]}</option>";
	else 
		$adddir .= "<option value=\"$dirkey\">{$lng[$diroption]}</option>";
$adddir .= "</select>";

$data = scandir($current_dir);
$dirs = $files = '';
$adddir .= '<table id="folder">';
$out->jsinfos['browsetype'] = '\'list\'';
foreach($data as $file)
{
	if($file[0] == '.')
	{
		if(!$settings['showinvisiblefiles'] || $file == '.' || $file == '..')
			continue;
	}
	$rdir = rawurlencode($current_dir . $file);
	$filename = htmlspecialchars($file, ENT_QUOTES);
	if(is_dir($current_dir . $file))
	{
		$line  = "<tr><td class=\"icon\"><img src=\"{$imagedir}folder.png\" alt=\"F\" /></td><td class=\"filename\"><a href=\"add.php?change_dir=$rdir$sid\">$filename</a></td>";
		$dirs .= $line;
	}
	else
	{
		$line  = "<tr><td class=\"icon\"><img src=\"{$fileimgs}small/" . get_icon(strtolower(substr($file, -4))) . "\" alt=\"_\" /></td><td class=\"filename\">$filename</td>";
		$files .= $line;
	}
}
$adddir  .= "$dirs</table>";
$adddir  .= "</fieldset>";
$out->addJavascripts('js/filebrowser.js');


$addbyfile = "<fieldset class=\"box\"><legend>{$lng['addbyupl']}</legend>";
for($x = 1; $x <= $settings['maxaddfieldsfile']; $x++)
	$addbyfile .= "<div class=\"addfield\">$x.&nbsp;<input type=\"file\" class=\"file\" name=\"addbyfile$x\" accept=\"application/x-bittorrent\" /></div>";
$addbyfile .= "</fieldset>";



if($settings['def_start_torrent'])
	$checked = ' checked="checked"';
else
	$checked = '';


$addbox    = "<fieldset class=\"box\"><legend>{$lng['add']}</legend>";
$addbox   .= "<div id=\"addadd\"><input type=\"checkbox\" name=\"start\" value=\"true\" id=\"startbox\"$checked /><label for=\"startbox\">&nbsp;{$lng['addstart']}</label>";
$addbox   .= "<input type=\"checkbox\" name=\"add_resume_data\"  value=\"true\" id=\"addresumebox\"  /><label for=\"addresumebox\">&nbsp;{$lng['addresumedat']}</label>";
$addbox   .= "<input type=\"checkbox\" name=\"more\"  value=\"true\" id=\"morebox\"  /><label for=\"morebox\">&nbsp;{$lng['addmore']}</label>";
if($settings['real_multiuser'])
	$addbox   .= "<input type=\"checkbox\" name=\"public\"  value=\"true\" id=\"publicbox\"  /><label for=\"publicbox\">&nbsp;{$lng['addpublic']}</label>";
$addbox   .= "<input type=\"submit\" name=\"add\" value=\"{$lng['addordir']}\" /></div><div class=\"hint\">{$lng['addodirhint']}</div>";
$addbox   .= "</fieldset>";

$out->content .= "$adddir$addbyurl$addbyfile$addbox</form></div></div>";

$out->renderPage($settings['html_title']);

?>
