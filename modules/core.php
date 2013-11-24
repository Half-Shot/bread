<?php

//---
//Configuration Load
//---
$BREAD_CONFIGURL = "settings/config.json";
//Load config file
$tmp = file_get_contents($BREAD_CONFIGURL);
error_reporting(E_ALL);
ini_set('display_errors', 1);
//Run this while loading
if(!$tmp)
{
	//Failure reading file.
	echo "Failed to read config file.\n Please adjust your core.php file.";
	echo "<br><code>Given File Location: " . $BREAD_CONFIGURL . "</code>";
	return;
}
else
{
	$BREAD_CONFIG = json_decode($tmp,true);
	if($BREAD_CONFIG["core"]["debug"]){
		echo "Loaded config ok";
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
	}
}
if(!$BREAD_CONFIG)
{
	echo "Failed to parse config file.\n Please check through the config.json file for syntax errors.";
	return;
}
//---
//Check Ban System
//---
if($BREAD_CONFIG["core"]["banhammer"])
{
	if($BREAD_CONFIG["core"]["debug"]){
		echo "<br>Checking for bans.";
	}
	//Check for banned user.
	$uip = $_SERVER["REMOTE_ADDR"];
	foreach ($BREAD_CONFIG["bans"] as $banneduser)
	{	
		if($banneduser == $uip){
			echo "BANNED, Get Lost!";
			//LogInfomation("User " . $uip . " attempted to connect but is banned.");
			die(); //Don't give it any more processing time, we are done here.
		}
	}
	 
}

//---
//Load Classes
//---
function ScanDirectoryForClasses($directory)
{
$files = preg_grep('/^([^.])/',scandir($directory));
	foreach($files as $file) //Ignore dot files.
	{
		$fullpath = $directory . "/" . $file;
		if(realpath($fullpath) == realpath(__FILE__))
			continue;

		if(is_dir($fullpath))
		{
			ScanDirectoryForClasses($fullpath);
		}
		else if(file_exists($fullpath))
		{
			echo "<br>Found file " . $fullpath;
			require_once($fullpath);
		}
	}
}

ScanDirectoryForClasses($BREAD_CONFIG["directorys"]["system-modules"]); //Get us all used classes inside 
//Run a class checklist
$RequiredModules = json_decode(file_get_contents($BREAD_CONFIG["directorys"]["system-settings"] . "/coremodulecheck.json"),true)["checklist"];
$Failed = false;
foreach($RequiredModules as $ModuleName)
{
	if(!class_exists($ModuleName)){
		echo "<br>Bread is missing " . $ModuleName;
		$Failed = true;
	}
}

if($Failed)
{
	echo "<br>Some core modules could not be found, please redownload them from the repository.";
	die();
}

//Load managers
$BREAD_MANAGER = array();
$BREAD_MANAGER["Theme"] = new Themes\ThemeManager();
$BREAD_MANAGER["Module"] = new Modules\ModuleManager();

if(!file_exists($BREAD_CONFIG["directorys"]["system-settings"] . "/theme/settings.json"))
{
	echo "<br>Missing theme setting file at:<br>" . $BREAD_CONFIG["directorys"]["system-settings"] . "/theme/settings.json";
	die();
}

$globalthemefile = json_decode(file_get_contents($BREAD_CONFIG["directorys"]["system-settings"] . "/theme/settings.json"),true);

$BREAD_MANAGER["Theme"]->RegisterTheme($BREAD_CONFIG["directorys"]["user-themes"] . "/" . $globalthemefile["theme-settings"]["theme-file"]);
?>
