<?php
namespace Bread;
use Bread\Structures\BreadRequestData as BreadRequestData;
use Bread\Structures\BreadRequestCommand as BreadRequestCommand;
/**
 * The root class of Bread. Contains managers and configuration code.
 * This class should only have its methods called from index.php except
 * getters and setters. The class has 2 important objects, ThemeManager
 * and ModuleManager.
 * Editing this class from any other module or class is discouraged since its
 * bad for security. Use the supplied methods instead and use the module system
 * where possible.
 * @package Bread 
 * @access public
 * @static
 * @see ModuleManager
 * @see ThemeManager
 */
class Site
{       /**
        * Bread's Master configuration file, the only hardcoded path in bread.
        * File path is set in the index.php file.
        * @see LoadConfig()
        * @type array
        */
	private static $configuration;

        /**
        * The global theme manager. There is only one ThemeManager and this
        * is it.
        * @var ThemeManager
        */
	public static $themeManager;
        
        /**
        * The global module manager.There is only one ModuleManager and this
        * is it.
        * @var ModuleManager
        */
	public static $moduleManager;
        
        /**
         * The global settings manager. Although advanced modules may wish to
         * use their own SettingsManager instance, this is the one most will use.
         * @var SettingsManager
         */
        public static $settingsManager;
        
        /**
        * The global Logger which logs messages and throws errors.
        * @var Logger
        */
	public static $Logger;
        
        /**
        * Time of request.
        * @var integer
        */
	public static $timeStarted;

        /**
        * HTML used in the end site. Unable to modify outside site.
        * @var string
        */
	private static $htmlcode = "";
        
        /**
        * Main code used in the body of the site. Modified via AddToBodyCode
        * @see Site::AddToBodyCode()
        * @var string
        */
	private static $bodycode = "";
        /**
         * Is the site in debug Mode
         * @var bool 
         */
        private static $isdebug = False;
        //Getters Setters
        /**
         * Get the configuration file that loads when bread starts.
         * @return array The configuration of the site
         */
        public static function Configuration()
	{
		return static::$configuration;
	}
         /**
         * Is the site in debug mode?
         * @return bool DebugOn
         */
        public static function isDebug()
        {
            return static::$isdebug;
        }
        
        /**
         * Add some code to the document body. For scripts and header infomation
         * checkout HEADERINFOMETHODGOESHERE
         * @param string $code
         */
        public static function AddToBodyCode($code)
        {
            static::$bodycode .= $code;
        }
        /**
         * The first stage of any bread site. This method loads a json config file
         * into $configuration. If this method fails then the site will not start.
         * @todo This is likley to change when the new settings manager is implemented.
         * @param string $configurl The url to load the config from.
         * @throws \Exception
         * @see Site::$configuration
         */
	public static function LoadConfig($configurl)
	{
		$tmp = file_get_contents($configurl);
		if(!$tmp)
		{
			throw new \Exception($tmp . " could not be loaded. Game Over!");
		}
		static::$configuration = json_decode($tmp,true);
		if(!static::$configuration)
		{
			throw new \Exception("Configuration could be <b>read</b> but not be <b>loaded</b>. Game Over!");
		}
	}
        /**
         * Enables/Disables Debug Statements. Very useful for a developer
         * such as yourself. Starts disabled.
         * @param Boolean $enable Toggle
         */
	public static function ShowDebug($enable)
	{
                static::$isdebug = $enable;
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
        /**
         * A simple ban-by-ip system which will be swapped out in the future.
         * Looks inside our loaded config and if its a banned ip then the site will cease.
         * A really horrible ban
         * @todo Hook Ban System 
         */
	public static function CheckBans()
	{
		if(static::$configuration["core"]["banhammer"])
		{
			//Check for banned user.
			$uip = $_SERVER["REMOTE_ADDR"];
			foreach (static::$configuration["bans"] as $banneReadElementsFromLayoutduser)
			{	
				if($banneduser == $uip){
					echo "BANNED, Get Lost!";
					die(); //Don't give it any more processing time, we are done here.
				}
			}
		}
	}
        /**
         * Loads core modules from a directory ($directory).
         * Its probably best to not place anything in this directory
         * or you will fear the wrath of bread.
         * @param string $directory Relative/Fullpath to load classes from.
         */
	public static function LoadCoreModules($directory)
	{
		$files = preg_grep('/^([^.])/',scandir($directory));
		foreach($files as $file) //Ignore dot files.
		{
			$fullpath = $directory . "/" . $file;
			if(realpath($fullpath) == realpath(__FILE__))
				continue;

			if(is_dir($fullpath))
			{
				static::LoadCoreModules($fullpath);
			}
			else if(file_exists($fullpath))
			{
				require_once($fullpath);
				Site::$Logger->writeMessage("Loaded core file " . $fullpath);
			}
		}
	}
        /**
         * Creates a new logger for error reporting.
         * @see Site::$Logger
         */
	public static function SetupLogging()
	{
		static::$Logger = new Logger(static::$configuration["core"]["logto"]);
	}
        /**
         * Checks each module in the core modules that were previously loaded from
         * LoadCoreModules() and if any are missing then bread will throw an error.
         * The file it checks will be inside the root settings directory called 'coremodulecheck.json'
         * @throws Exception
         * @see Site::LoadCoreModules()
         */
	public static function CheckCoreModules()
	{
		$RequiredModules = json_decode(file_get_contents(static::$configuration["directorys"]["system-settings"] . "/coremodulecheck.json"),true)["checklist"];
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
			Site::$Logger->writeError("Some core modules could not be found, please redownload them from the repository.",0,true);
			die();
		}
	}
        /**
         * Sets up all managers that bread uses.
         * This is currently:
         *  ThemeManager
         *  ModuleManager
         * It also loads settings and configuration files for managers.
         * @see Site::$themeManager
         * @see Site::$moduleManager
         */
	public static function SetupManagers()
	{
		static::$themeManager = new Themes\ThemeManager();
		static::$moduleManager = new Modules\ModuleManager();
                static::$settingsManager = new Settings\SettingsManager();
                
		static::$themeManager->LoadSettings(static::$configuration["directorys"]["system-settings"] . "/theme/settings.json");
		static::$themeManager->LoadLayouts();

		static::$moduleManager->LoadSettings(static::$configuration["directorys"]["system-settings"] . "/modules/settings.json");
		static::$moduleManager->LoadModulesFromConfig(static::$configuration["directorys"]["system-settings"] . "/modules/modlist.json");


	}
	/**
         * Creates metadata tags for the header. This function calls moduleman
         * and asks all applicable modules to add metadata structures.
         * @param BreadRequestData $requestData The request structure generated.
         */
	public static function ProcessMetadata(BreadRequestData $requestData)
	{
		$Metadata  = "<meta>";
                $returnData = static::$moduleManager->HookEvent("Bread.Metadata",$requestData);
                if($returnData == False)
                    return False;
                //HookEvent returns an array of results.
                foreach($returnData as $data)
                    $Metadata .= $data;
                
		$Metadata .= "</meta>";
		return $Metadata;
	}
	/**
         * This is the big one. It generates the page when all of bread is ready.
         * It also loads 3 important functions which determine what modules, themes
         * and layouts to load.
         * @param BreadRequestData $requestData The request structure generated.
         * @see ModuleManager::LoadRequiredModules()
         * @see ThemeManager::SelectTheme()
         * @see ThemeManager::SelectLayout()
         */
	public static function ProcessRequest(BreadRequestData $requestData)
	{
	    //Load required modules.
	    static::$moduleManager->LoadRequiredModules($requestData);
	    switch($requestData->command){
	    	case "module":
			break;
	    	case "page":
			break;
		default:
			Site::$Logger->writeMessage("Unknown request command -> " . $requestData->command);
			break;
	    }
	    //Draw
	    Site::$htmlcode .= "<!DOCTYPE html>\n<html>\n"; //Obviously.
	    Site::$Logger->writeMessage("Beginning build of page");
	    Site::$Logger->writeMessage("Request data:\n" . var_export($requestData,True));
	    //Process request
	    if(!Site::$themeManager->SelectTheme($requestData->command)){
			Site::$Logger->writeError("Couldn't select theme from request.",0,True);
	    }
	    if(!Site::$themeManager->SelectLayout($requestData->command)){
			Site::$Logger->writeError("Couldn't select layout from request.",0,True);
	    }

	    Site::$themeManager->ReadElementsFromLayout(Site::$themeManager->Theme["layout"]);#Build layout into HTML
	    Site::$htmlcode .= "<head>\n";
	    Site::$htmlcode .= Site::$themeManager->CSSLines;
	    Site::$htmlcode .= Site::ProcessMetadata($requestData);
	    Site::$htmlcode .= "</head>\n";
	    Site::$htmlcode .= "<body>\n";
	    Site::$htmlcode .= Site::$bodycode;
	    Site::$htmlcode .= "</body>\n";
	    Site::$htmlcode .= "</html>\n";
	    echo Site::$htmlcode;
	}
	
        /**
         * A fake, example request that is used for early stage debugging.
         * @see BreadRequestData
         */
	public static function ExampleRequest()
	{
	    $requestType = "RawPage";
	    $request = new BreadRequestData($requestType);
	    return $request;
	}
        /**
         * Closes the logger and gives any modules a chance to clean up their work
         * by calling "Bread.Cleanup".
         */
	public static function Cleanup()
	{
                static::$moduleManager->HookEvent("Bread.Cleanup",NULL);//Broadcast that we are cleaning up.
                static::$settingsManager->SaveChanges(); //Save all changes.
		static::$Logger->closeStream();
	}
        
        public static function ResolvePath($path)
        {
            //Example Path /settings/modules/modlist.json
            $parts = explode("/", $path);
            foreach($parts as $i => $part)
            {
                //If it matches a directory, use it.
                //We will use $DIRNAME
                if($part == "")
                    continue;
                if($part[0] == "%"){
                   $dir = substr($part, 1,strlen($part) - 1);
                   if(isset(static::$configuration["directorys"][$dir])){
                       $realdir = static::$configuration["directorys"][$dir];
                       $parts[$i] = $realdir;
                   }
                }
                      
            }
            //Returns whatever we changed.
            return implode("/",$parts);
        }
}
/**
 * A class that logs important infomation and also throws errors for bread.
 * The log file can be found in /temp/breadlog. This can be changed in settings.
 * The main logger is to be found in Site::$Logger.
 * @see Site::$Logger
 */
class Logger
{
    const FILEMODE = "w";
    private $logpath = "NOLOG";
    private $errorstack = array();
    private $messagestack = array();
    private $fileStream;
    
    /**
     * Gets the messages passed to the logger since startup.
     * @return array
     */
    function getMessagesstack()
    {
        return $messagestack;
    }
    
    /**
     * Gets the errors passed to the logger since startup.
     * @return array
     */
    function getErrorstack()
    {
        return $errorstack;
    }
    
    /**
     * Creates a new Logger object. 
     * Throws an error if the logger can't create a file.
     * Leave a blank string if you do not want to log to a file.
     * @param string $filepath File name to log to.
     * @return False
     */
    function __construct($filepath)
    {
        if($filepath == "")
		$this->logpath == "NOLOG";
        $this->logpath = $filepath;
        $this->fileStream = fopen($this->logpath,static::FILEMODE);
        if(!$this->fileStream){
            $this->fileStream = fopen("php://temp",static::FILEMODE);// No writing possible
            trigger_error("Couldn't write a new log file. File name " . $this->logpath, E_USER_ERROR);
        }
        static::writeMessage("Opened Logger");
    }
    /**
     * Write some infomation to the log stack. Purely for debugging or user info
     * if the message is a potential problem, use Logger->writeError.
     * @see Logger::writeError()
     * @param string $message Message to be written
     */
    function writeMessage($message)
    {
	if($this->logpath == "NOLOG")
		return;
        $messageStack[time() - Site::$timeStarted] = $message;
        $msg = "[MSG][" . (time() - Site::$timeStarted) . "]" . $message . "\n";
        fwrite($this->fileStream,$msg);
        fflush($this->fileStream);
    }
    
    /**
     * Write an error to the log stack. If the message is just infomation,
     * use Logger->writeMessage. 
     * @see Logger::writeMessage()
     * @param string $message Message to be written
     * @param integer $severity Severity of the error
     * @param bool $throw Should the logger throw an error
     * @param string $exception The Exception class to throw.
     * @throws \Exception
     */
    function writeError($message,$severity = -1,$throw = False,$exception = "\Exception")
    {
	if($this->logpath == "NOLOG")
		return;
        $errorStack[time() - Site::$timeStarted] = $message;
        $msg = "[ERR " . $severity ."][" . (time() - Site::$timeStarted) . "]" . $message . "\n";
        fwrite($this->fileStream,$msg);
        fflush($this->fileStream);
	if($throw)
            throw new $exception($message);
    }
    /**
     * Close the stream to the filestream. Also writes a closing statement.
     */
    function closeStream()
    {
	if($this->logpath == "NOLOG")
		return;
        static::writeMessage("Closing Log");
        fclose($this->fileStream);
    }
}
?>