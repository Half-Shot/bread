<?php
namespace Bread;
class Site
{
	private static $Configuration;
	public static $ThemeManager;
	public static $ModuleManager;
	public static function LoadConfig($configurl)
	{
		$tmp = file_get_contents($configurl);
		if(!$tmp)
		{
			throw new \Exception($tmp . " could not be loaded. Game Over!");
		}
		self::$Configuration = json_decode($tmp,true);
	}

	public static function Configuration()
	{

		return self::$Configuration;
	}

	public static function ShowDebug($enable)
	{
		if($enable)
		{
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
		}
		else
		{
			error_reporting(E_NONE);
			ini_set('display_errors', 0);
		}
	}
	public static function CheckBans()
	{
		if(self::$Configuration["core"]["banhammer"])
		{
			//Check for banned user.
			$uip = $_SERVER["REMOTE_ADDR"];
			foreach ($Configuration["bans"] as $banneduser)
			{	
				if($banneduser == $uip){
					echo "BANNED, Get Lost!";
					die(); //Don't give it any more processing time, we are done here.
				}
			}
		}
	}

	public static function LoadClasses($directory)
	{
		$files = preg_grep('/^([^.])/',scandir($directory));
		foreach($files as $file) //Ignore dot files.
		{
			$fullpath = $directory . "/" . $file;
			if(realpath($fullpath) == realpath(__FILE__))
				continue;

			if(is_dir($fullpath))
			{
				self::LoadClasses($fullpath);
			}
			else if(file_exists($fullpath))
			{
				require_once($fullpath);
			}
		}
	}

	public static function CheckClasses()
	{
		$RequiredModules = json_decode(file_get_contents(self::$Configuration["directorys"]["system-settings"] . "/coremodulecheck.json"),true)["checklist"];
		$Failed = false;
		foreach($RequiredModules as $ModuleName)
		{
			if(!class_exists($ModuleName)){
				throw new \Exception("Bread is missing " . $ModuleName);
				$Failed = true;
			}
		}
		if($Failed)
		{
			throw new \Exception("Some core modules could not be found, please redownload them from the repository.");
			die();
		}
	}

	public static function SetupManagers()
	{
		self::$ThemeManager = new Themes\ThemeManager();
		self::$ModuleManager = new Modules\ModuleManager();

		self::$ThemeManager->LoadThemeManagerSettings(self::$Configuration["directorys"]["system-settings"] . "/theme/settings.json");
	}
	
	public static function DrawSite()
	{

	}
}
?>
