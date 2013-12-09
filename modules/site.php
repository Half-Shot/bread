<?php
namespace Bread;
use Bread\Structures\BreadRequestData as BreadRequestData;
use Bread\Structures\BreadRequestCommand as BreadRequestCommand;
class Site
{
	private static $Configuration;

	public static $ThemeManager;
	public static $ModuleManager;
	public static $Logger;
	public static $TimeStarted;

	public static $HTMLCode;

	public static function LoadConfig($configurl)
	{
		$tmp = file_get_contents($configurl);
		if(!$tmp)
		{
			throw new \Exception($tmp . " could not be loaded. Game Over!");
		}
		self::$Configuration = json_decode($tmp,true);
		if(!self::$Configuration)
		{
			throw new \Exception("Configuration could be <b>read</b> but not be <b>loaded</b>. Game Over!");
			die();
		}
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
			foreach (self::$Configuration["bans"] as $banneduser)
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
				Site::$Logger->writeMessage("Loaded core file " . $fullpath);
			}
		}
	}

	public static function SetupLogging()
	{
		self::$Logger = new Logger(self::$Configuration["core"]["logto"]);
	}

	public static function CheckClasses()
	{
		$RequiredModules = json_decode(file_get_contents(self::$Configuration["directorys"]["system-settings"] . "/coremodulecheck.json"),true)["checklist"];
		$Failed = false;
		foreach($RequiredModules as $ModuleName)
		{
			if(!class_exists($ModuleName)){
				Site::$Logger->writeError("Bread is missing module " . $ModuleName,0,True);
				$Failed = true;
			}
		}
		if($Failed)
		{
			throw new Site::$Logger->writeError("Some core modules could not be found, please redownload them from the repository.",0,true);
			die();
		}
	}

	public static function SetupManagers()
	{
		self::$ThemeManager = new Themes\ThemeManager();
		self::$ModuleManager = new Modules\ModuleManager();

		self::$ThemeManager->LoadThemeManagerSettings(self::$Configuration["directorys"]["system-settings"] . "/theme/settings.json");
		self::$ThemeManager->LoadLayouts();

		

	}
	
	public static function ProcessMetadata(BreadRequestData $requestData)
	{
		//Probably gonna need a module for this.
		$Metadata  = "<meta>";
		$Metadata .= '<meta name="description" content="Free Web tutorials">';
		$Metadata .= '<meta name="keywords" content="HTML,CSS,XML,JavaScript">';
		$Metadata .= '<meta name="author" content="Hege Refsnes">';
		$Metadata .= '<meta charset="UTF-8">';
		$Metadata .= "</meta>";
		return $Metadata;
	}
	
	public static function ProcessRequest(BreadRequestData $requestData)
	{
	    Site::$HTMLCode .= "<!DOCTYPE html>\n<html>"; //Obviously.
	    Site::$Logger->writeMessage("Beginning build of page");
	    Site::$Logger->writeMessage("Request data:\n" . var_export($requestData,True));
	    //Process request
	    if(!Site::$ThemeManager->SelectTheme($requestData->command))
	    {
		Site::$Logger->writeError("Couldn't select theme from request.",0,True);
		die();
	    }

	    if(!Site::$ThemeManager->SelectLayout($requestData->command))
	    {
		Site::$Logger->writeError("Couldn't select layout from request.",0,True);
		die();
	    }
	    Site::$ThemeManager->ReadElementsFromLayout(Site::$ThemeManager->SelectedLayout);

	    Site::$HTMLCode .= "<head>";
	    Site::$HTMLCode .= Site::$ThemeManager->SelectedTheme->HeaderInfomation();
	    Site::$HTMLCode .= Site::$ThemeManager->CSSLines;
	    Site::$HTMLCode .= Site::ProcessMetadata($requestData);
	    Site::$HTMLCode .= "</head>";
	    Site::$HTMLCode .= "<body>";
	    
	    Site::$HTMLCode .= "</body>";
	    Site::$HTMLCode .= "</html>";
	    echo Site::$HTMLCode;
	}
	
	public static function ExampleRequest()
	{
	    $requestType = "RawPage";
	    $request = new BreadRequestData($requestType);
	    return $request;
	}

	public static function Cleanup()
	{
		self::$Logger->closeStream();
	}
}

class Logger
{
    const FILEMODE = "w";
    public $logPath = "NOLOG";
    public $errorStack = array();
    public $messageStack = array();
    private $fileStream;
    function __construct($filepath)
    {
	if($filepath == "")
		return;
        $this->logPath = $filepath;
        try
        {
            $this->fileStream = fopen($this->logPath,self::FILEMODE);
	    self::writeMessage("Opened Logger");
        }
        catch(Exception $ex)
        {
            throw new Exception("Couldn't write a new log file. File name " . $this->logPath);
        }
    }
    
    function writeMessage($message)
    {
	if($this->logPath == "NOLOG")
		return;
        $msg = "[MSG][" . (time() - Site::$TimeStarted) . "]" . $message . "\n";
        fwrite($this->fileStream,$msg);
        fflush($this->fileStream);
    }
    
    function writeError($message,$severity,$throw = False)
    {
	if($this->logPath == "NOLOG")
		return;
        $msg = "[ERR " . $severity ."][" . (time() - Site::$TimeStarted) . "]" . $message . "\n";
        fwrite($this->fileStream,$msg);
        fflush($this->fileStream);
	if($throw)
		throw new \Exception($message);
    }
    
    function closeStream()
    {
	if($this->logPath == "NOLOG")
		return;
        self::writeMessage("Closing Log");
        fclose($this->fileStream);
    }
}
?>
