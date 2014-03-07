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
        * Main code used in the header of the site. Modified via AddToHeaderCode()
        * @see static::AddToHeaderCode()
        * @var string
        */
        private static $headercode = "";
        
        /**
        * HTML used in the end site. Unable to modify outside site.
        * @var string
        */
	private static $htmlcode = "";
        
        /**
         * Script files loaded in the header of the document.
         * @var string
         */
        private static $ScriptLines = "";
        
         /**
         * Script files loaded at the end of the body of the document.
         * @var string
         */
        private static $LowPriorityScriptLines = "";
        /**
        * Main code used in the body of the site. Modified via AddToBodyCode
        * @see static::AddToBodyCode()
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
        private static $Request = NULL;
        
        /**
         * The base url of the site, written when the request is digested.
         * @var string 
         */
        private static $baseurl = "";
        private static $isAjax = False;
        private static $URLParameters;
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
         * Get the data parsed from the input url, useful for modules.
         * Set in static::DigestRequest()
         * @see static::DigestRequest()
         * @return Bread\Structures\BreadRequestData The requested data by the user.
         */
        public static function getRequest()
        {
            return static::$Request;
        }
        /**
         * Return the base URL.
         * @return string
         */
        public static function getBaseURL()
        {
            return static::$baseurl;
        }
        
        public static function getURLParams()
        {
            return static::$URLParameters;
        }
         /**
         * Add some code to the document header.
         * DO NOT ADD CSS, SCRIPTS OR METADATA THROUGH THIS
         * @param string $code
         * @see Site::AddToBodyCode()
         */
        public static function AddToHeaderCode($code)
        {
            static::$headercode .= $code;
        }
        
        /**
         * Adds a script to the head of the document.
         * @param string $location The location of the file.
         * @param string $mimetype The type of script, directed by mimetype. Default is javascript.
         * @param bool $isLow Is the script low priority, and can be added to the end of the document instead of the head.
         */
        public static function AddScript($location,$isLow = False,$mimetype = 'text/javascript')
        {
            if($isLow){
                static::$LowPriorityScriptLines .= "<script type='" . $mimetype . "' src='" . $location . "'></script>\n";
            }
            else
            {
                static::$ScriptLines .= "<script type='" . $mimetype . "' src='" . $location . "'></script>\n";
            }
        }
        
        /**
         * Adds raw script code the head of the document.
         * @param string $location The code (no tags please).
         * @param string $mimetype The type of script, directed by mimetype. Default is javascript.
         * @param bool $isLow Is the script low priority, and can be added to the end of the document instead of the head.
         */
        public static function AddRawScriptCode($code,$isLow = False,$mimetype = 'text/javascript')
        {
            if($isLow){
                static::$LowPriorityScriptLines .= "<script type='" . $mimetype . "'>" . $code ."</script>\n";
            }
            else
            {
                static::$ScriptLines .= "<script type='" . $mimetype . "'>" . $code ."</script>\n";
            }
        }
        
        
        /**
         * Add some code to the document body. For scripts and header infomation
         * checkout Site::AddToHeaderCode().
         * @param string $code
         * @see Site::AddToHeaderCode()
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
         * @see static::$configuration
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
         * A really horrible ban system.
         * @todo Hook Ban System 
         */
	public static function CheckBans()
	{
		if(static::$configuration["core"]["banhammer"])
		{
			//Check for banned user.
			$uip = $_SERVER["REMOTE_ADDR"];
			foreach (static::$configuration["bans"] as $banneduser)
			{	
				if($banneduser == $uip){
                                        http_response_code(401); //Be really mean. 404s should deter people.
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
				static::$Logger->writeMessage("Loaded core file " . $fullpath);
			}
		}
	}
        /**
         * Creates a new logger for error reporting.
         * @see static::$Logger
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
         * @see static::LoadCoreModules()
         */
	public static function CheckCoreModules()
	{
		$RequiredModules = json_decode(file_get_contents(static::ResolvePath("%system-settings") . "/coremodulecheck.json"),true)["checklist"];
		$Failed = false;
		foreach($RequiredModules as $ModuleName)
		{
			if(!class_exists($ModuleName)){
				static::$Logger->writeError("Bread is missing module " . $ModuleName,0,True);
				$Failed = true;
			}
		}
		if($Failed)
		{
			static::$Logger->writeError("Some core modules could not be found, please redownload them from the repository.",0,true);
			die();
		}
	}
        /**
         * Sets up all managers that bread uses.
         * This is currently:
         *  ThemeManager
         *  ModuleManager
         * It also loads settings and configuration files for managers.
         * @see static::$themeManager
         * @see static::$moduleManager
         */
	public static function SetupManagers()
	{
            $path = static::ResolvePath("%system-settings");
            if(!static::$isAjax)
                static::$themeManager = new Themes\ThemeManager();
            static::$moduleManager = new Modules\ModuleManager();
            static::$settingsManager = new Settings\SettingsManager();
            static::$moduleManager->LoadSettings($path . "/modules/settings.json");
            if(!static::$isAjax){
                static::$themeManager->LoadSettings($path . "/theme/settings.json");
                static::$themeManager->LoadLayouts();
            }
            static::$moduleManager->LoadModulesFromConfig($path . "/modules/modlist.json");
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
         * Digests a request into the bits we want and puts it into a object.
         * No return values but instead puts in Site::$Request.
         * Users have no need to call this, its done automatically.
         * @see Site::$Request
         */
        public static function DigestRequest()
        {
            //Load the requests file.
            $requestDB = static::$settingsManager->RetriveSettings(static::ResolvePath("%system-requests"),true);
            $requestObject = new BreadRequestData();
            $URL = $_SERVER['REQUEST_URI'];
            $Params = static::DigestURL($URL);
            static::$baseurl = $Params["BASEURL"];
            static::$URLParameters = $Params;
            //Override for ajax.
            //TODO: Allow use to turn this off, could be used as a backdoor.
            if(static::$configuration["core"]["debug"] && array_key_exists("ajax", $Params)){
                    static::$isAjax = true;
            }
            if(isset($requestDB->master->layout))
                $requestObject->layout = $requestDB->master->layout;
            
            if(isset($requestDB->master->theme))
                $requestObject->theme  = $requestDB->master->theme;
            
            if(isset($requestDB->master->modules))
                $requestObject->modules = $requestDB->master->modules;
            
            if(isset($requestDB->master->requestType))
                $requestObject->requestType = "master";
            
            if(array_key_exists("request", $Params)){
                $requestName = $Params["request"];
            }
            else
            {
                $requestName = static::$configuration["core"]["defaultrequest"];
            }

            if(isset($requestDB->$requestName->layout))
                $requestObject->layout = $requestDB->$requestName->layout;

            if(isset($requestDB->$requestName->theme))
                $requestObject->theme  = $requestDB->$requestName->theme;

            if(isset($requestDB->$requestName->modules))
                $requestObject->modules = $requestDB->$requestName->modules;

            if(isset($requestDB->$requestName->requestType))
                $requestObject->requestType = $requestName;

            if(isset($requestDB->$requestName->args))
            {
                foreach($requestDB->$requestName->args as $argpair)
                {
                    $argpair = get_object_vars($argpair);
                    $Params = array_merge($argpair,$Params);
                }
            }
            //Add included stuff (modules)
            if(isset($requestDB->$requestName->include))
            {
                foreach($requestDB->$requestName->include as $includedRequest)
                {
                    if(!isset($requestDB->$includedRequest)){
                        static::$Logger->writeError ("Request includes " . $includedRequest . " but is not defined in the file. Ignoring" , 1);
                        continue;
                    }
                    $requestObject->modules = array_merge($requestObject->modules,$requestDB->$includedRequest->modules);
                }
            }
            //Overrides
            if(array_key_exists("theme", $Params))
                $requestObject->theme = $Params["theme"];
            
            if(array_key_exists("layout", $Params))
                $requestObject->layout = $Params["layout"];
            
            
            $requestObject->arguments = $Params;
            static::$Request = $requestObject;
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
	public static function ProcessRequest()
	{
            $requestData = static::$Request;
	    //Load required modules.
	    static::$moduleManager->LoadRequiredModules($requestData);
	    static::$moduleManager->HookEvent("Bread.ProcessRequest",NULL);
            
            if(static::$isAjax)
            {
                site::$Logger->writeMessage("Request is AJAX!");
                $module = "";
                $event = "Bread.AjaxRequest";
                if(isset($_POST["ajaxEvent"]))
                {
                    $event = $_POST["ajaxEvent"];
                }
                
                if(isset($_POST["ajaxModule"]))
                {
                    $module = $_POST["ajaxModule"];
                }
                else if(static::$URLParameters["ajax"])
                {
                    static::$Logger->writeMessage("Using crappy GET based ajax. Please use POST for production sites.");
                    $module = static::$URLParameters["ajax"];
                }
                else
                {
                    static::$Logger->writeError("No bread module specfied for ajax request. Ignoring request.");
                    return False;
                }
                
                $return = static::$moduleManager->HookSpecifedModuleEvent($event,$module,NULL);
                if(!$return)
                {
                   static::$Logger->writeError("ajaxModule: " . $module);
                   static::$Logger->writeError("Couldn't hook Ajax Request to requested module.");
                   return False;
                }
                echo $return;
                return True;
            }
            
	    //Draw
	    static::$htmlcode .= "<!DOCTYPE html>\n<html>\n"; //Obviously.
	    static::$Logger->writeMessage("Beginning build of page");
	    static::$Logger->writeMessage("Request data:\n" . var_export($requestData,True));
	    //Process request
	    if(!static::$themeManager->SelectTheme($requestData)){
			static::$Logger->writeError("Couldn't select theme from request.",0,True);
	    }
	    if(!static::$themeManager->SelectLayout($requestData)){
			static::$Logger->writeError("Couldn't select layout from request.",0,True);
	    }

	    static::$themeManager->ReadElementsFromLayout(static::$themeManager->Theme["layout"]);#Build layout into HTML
            static::$moduleManager->HookEvent("Bread.FinishedLayoutProcess",NULL);
	    static::$htmlcode .= "<head>\n";
	    static::$htmlcode .= static::ProcessMetadata($requestData);
            static::$htmlcode .= static::$headercode;
	    static::$htmlcode .= static::$themeManager->CSSLines;
            static::$htmlcode .= static::$ScriptLines;
            static::$moduleManager->HookEvent("Bread.FinishedHead",NULL); //Must use add to head.
	    static::$htmlcode .= "</head>\n";
	    static::$htmlcode .= "<body>\n";
	    static::$htmlcode .= static::$bodycode;
            static::$moduleManager->HookEvent("Bread.LowPriorityScripts",NULL);
            static::$htmlcode .= static::$LowPriorityScriptLines;
            static::$moduleManager->HookEvent("Bread.FinishedBody",NULL); //Must use add to body.
	    static::$htmlcode .= "</body>\n";
	    static::$htmlcode .= "</html>\n";
	    echo static::$htmlcode;
	}
	
        /**
         * A fake, example request that is used for early stage debugging.
         * @see BreadRequestData
         */
	public static function ExampleRequest()
	{
	    $request = BreadRequestData;
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
        /**
         * Splits a string path up and locates wildcard paths such as %user-themes
         * and creates the correct path.
         * @param string $path 
         * @return type
         */
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
        /**
         * Converts a URL into a array of parameters and the base url.
         * @param type $url
         */
        public static function DigestURL($url)
        {
            
            $parts = \explode("?",$url);
            $baseURL = $parts[0];
            if(count($parts) > 1){
                $parts = \explode("&",$parts[1]);
            }
            $returnedArray = array();
            $returnedArray["BASEURL"] = $baseURL;
            foreach($parts as $part)
            {
               $pair = \explode("=",$part);
               if(count($pair) > 1)
                $returnedArray[$pair[0]] = $pair[1];
               else
                $returnedArray[$pair[0]] = False;
            }
            return $returnedArray;
        }
        /**
         * Create a URL from a baseurl and a array of params.
         * @param type $baseurl The base url of the site. Use False to use the current site baseurl.
         * @param type $params The array of params to append to the url. Leave as a blank array for none.
         * @return string The URL
         */
        public static function CondenseURLParams($baseurl,$params)
        {
            if(!$baseurl)
                $baseurl = static::$baseurl;
            
            $url = $baseurl;
            if(array_count_values($params) < 1)
                return $url;
            $key = array_keys($params)[0];
            $url .= "?" . $key . "=" .$params[$key];
            unset($params[$key]);
            if(array_count_values($params) < 1)
                return $url;
            foreach ($params as $key => $value)
            {
                $url .= "&" . $key . "=" . $value;
            }
            return $url;
        }
        
        /**
         * Gets the seconds of time since PHP got the request.
         * @param int $dec The decimal time to account to.
         * @return float Microsecond Time.
         */
        public static function GetTimeSinceStart($dec = 3)
        {
            return round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], $dec, PHP_ROUND_HALF_UP);
        }
        /**
         * A simple check to see if the request is an ajax based one. Will set Site::$isAjax
         * @see Site::$isAjax
         */
        public static function IsAjax()
        {
            static::$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'); 
        }
}
/**
 * A class that logs important infomation and also throws errors for bread.
 * The log file can be found in /temp/breadlog. This can be changed in settings.
 * The main logger is to be found in static::$Logger.
 * @see static::$Logger
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
        static::writeMessage("Bread Version " . Site::Configuration()["core"]["version"]);
        static::writeMessage("Log Date: " . date('l jS \of F Y'));
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
        $time = Site::GetTimeSinceStart();
        $messageStack[$time] = $message;
        $msg = "[MSG][" . $time . "]" . $message . "\n";
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
        $time = Site::GetTimeSinceStart();
        $errorStack[$time] = $message;
        $msg = "[ERR " . $severity ."][" . ($time) . "]" . $message . "\n";
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