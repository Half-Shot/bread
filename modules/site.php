<?php
namespace Bread;
use Bread\Structures\BreadRequestData as BreadRequestData;
use Bread\Structures\BreadRequestCommand as BreadRequestCommand;
class Site
{
	private static $Configuration;
	public static $ThemeManager;
	public static $ModuleManager;
	public static $TimeStarted;
	public static $HTMLCode;
	public static $Logger;
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
		self::$ThemeManager->LoadLayouts();

		

	}
	
	public static function ProcessRequest(BreadRequestData $requestData)
	{
	    Site::$HTMLCode = "<html><marquee>Entire Website</marquee></html>";
	    
	    //Process request
	    
	    //Load required modules only.
	    
	    //Load required themes only.
	    
	    //Draw required layout.
	    
	    echo Site::$HTMLCode;
	}
	
	public static function ExampleRequest()
	{
	    $requestType = BreadRequestCommand::$RawPage;
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
    
    function writeError($message,$severity)
    {
	if($this->logPath == "NOLOG")
		return;
        $msg = "[ERR " . $severity ."][" . time() - Site::$TimeStarted . "]" . $message . "\n";
        fwrite($this->fileStream,$msg);
        fflush($this->fileStream);
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
