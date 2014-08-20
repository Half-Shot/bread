<?php
namespace Bread;
use Bread\Utilitys as Utilitys;
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
{       
        /**
         *
         * @var string array Names of loaded Scripts
         */
        private static $loadedScripts = array();
    
        private static $configurl;
        /**
        * Bread's Master configuration file, the only hardcoded path in bread.
        * File path is set in the index.php file.
        * @see LoadConfig()
        * @type stdObject
        */
	private static $configuration;

        /**
        * The global theme manager. There is only one ThemeManager and this
        * is it.
        * @var Bread\Themes\ThemeManager
        */
	public static $themeManager;
        
        /**
        * The global module manager.There is only one ModuleManager and this
        * is it.
        * @var Bread\Modules\ModuleManager
        */
	public static $moduleManager;
        
        /**
         * The global settings manager. Although advanced modules may wish to
         * use their own SettingsManager instance, this is the one most will use.
         * @var \Bread\Settings\SettingsManager
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
        /**
         * Is the request an AJAX one.
         * (Do we need to draw any UI)
         * @var bool 
         */
        private static $isAjax = False;
        /**
         * Parameters from parsing the URL;
         * @var array key=> value
         */
        private static $URLParameters;
        /**
         * The root of the site.
         * @var string
         */
        private static $rootPath;
        
        /**
         * Is the request Ajax?
         * @return bool
         */
        public static function GetisAjax()
        {
            return static::$isAjax;
        }
        
        /**
         * The root directory of the site.
         * @return string
         */
        public static function GetRootPath()
        {
            return static::$rootPath;
        }
        
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
         * @return \Bread\Structures\BreadRequestData The requested data by the user.
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
         * @param string $name Name of the script. Used to stop multiple scripts of the same thing being loaded.
         * @param string $mimetype The type of script, directed by mimetype. Default is javascript.
         * @param bool $isLow Is the script low priority, and can be added to the end of the document instead of the head.
         */
        public static function AddScript($location,$name,$isLow = False,$mimetype = 'text/javascript')
        {
            if(!in_array($name,static::$loadedScripts)){
                static::$loadedScripts[] = $name;
                if($isLow){
                    static::$LowPriorityScriptLines .= "<script type='" . $mimetype . "' src='" . $location . "'></script>\n";
                }
                else
                {
                    static::$ScriptLines .= "<script type='" . $mimetype . "' src='" . $location . "'></script>\n";
                }
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
         * Add some code to the document body. For scripts and header information
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
	public static function LoadConfig($configurl,$basepath)
	{
            static::$rootPath = $basepath;
            static::$configurl = $configurl;
            $tmp = file_get_contents($configurl);
            if(!$tmp)
            {
                    throw new \Exception($tmp . " could not be loaded. Game Over!");
            }
            static::$configuration = json_decode($tmp);
            if(!static::$configuration)
            {
                    throw new \Exception("Configuration could be <b>read</b> but not be <b>loaded</b>. Game Over!");
            }
            date_default_timezone_set(static::$configuration->core->timezone);//Setting timezone before its too late.
            static::ShowDebug(static::$configuration->core->debug);
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
			error_reporting(false);
			ini_set('display_errors', 0);
                        error_reporting(0);

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
		if(static::$configuration->core->banhammer)
		{
			//Check for banned user.
			$uip = $_SERVER["REMOTE_ADDR"];
			foreach (static::$configuration->bans as $banneduser)
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
		$files = json_decode(file_get_contents(static::ResolvePath("%system-settings") . "/coremodulecheck.json"),true)["phpfilelist"];
		foreach($files as $file) //Ignore dot files.
		{
			$fullpath = $directory . "/" . $file . ".php";
			if(realpath($fullpath) == realpath(__FILE__))
				continue;
			if(is_dir($fullpath))
			{
				static::LoadCoreModules($fullpath);
			}
			else if(file_exists($fullpath))
			{
				require($fullpath);
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
		static::$Logger = new Logger(static::$configuration->logger->path,
                        static::$configuration->logger->minseveritytolog,
                        static::$configuration->logger->maxlogfiles,
                        static::$configuration->logger->multifilelog,
                        static::$configuration->logger->keepfor);
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
            if(!static::$isAjax){
                static::$themeManager->LoadSettings($path . "/theme/settings.json");
                static::$themeManager->LoadLayouts();
            }
            static::$moduleManager->LoadModulesFromConfig();
	}
	/**
         * Creates metadata tags for the header. This function calls moduleman
         * and asks all applicable modules to add metadata structures.
         * @param BreadRequestData $requestData The request structure generated.
         */
	public static function ProcessMetadata(BreadRequestData $requestData)
	{
		$Metadata  = "<meta>";
                $returnData = static::$moduleManager->FireEvent("Bread.Metadata",$requestData,false);
                if($returnData == False)
                    return False;
                //HookEvent returns an array of results.
                foreach($returnData as $data)
                    $Metadata .= $data;
                
		$Metadata .= "</meta>";
		return $Metadata;
	}
        
        public static function EditConfigurationValues($newObj)
        {
           self::$Logger->writeMessage("Editing the core config!", "core");
           if(!Site::$settingsManager)
               return false;
           $setting = self::$settingsManager->RetriveSettings(self::$configurl);
           foreach($newObj as $catname => $category)
           {
               foreach($category as $key => $value)
               {
                   $setting->$catname->$key = $value;
               }
           }
           self::$settingsManager->ChangeSetting(self::$configurl,$setting);
           
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
            if(static::$configuration->core->debug && array_key_exists("ajax", $Params)){
                    static::$isAjax = true;
                    $requestObject->theme = false;
                    $requestObject->layout = false;
                    $requestObject->modules = $requestDB->master->modules;
                    $requestObject->requestType = "ajax";
                    $requestObject->arguments = $Params;
                    static::$Request = $requestObject;
                    return true;
            }
            if(isset($requestDB->master->layout))
                $requestObject->layout = $requestDB->master->layout;
            
            if(isset($requestDB->master->theme))
                $requestObject->theme  = $requestDB->master->theme;
            /**
             * @todo Find a way to make sure only needed modules are loaded.
             */
            if(isset($requestDB->master->modules))
                $requestObject->modules = $requestDB->master->modules;
            
            if(isset($requestDB->master->requestType))
                $requestObject->requestType = "master";
            
            if(array_key_exists("request", $Params)){
                $requestName = $Params["request"];
            }
            else
            {
                $requestName = static::$configuration->core->defaultrequest;
            }

            if(isset($requestDB->$requestName->layout))
                $requestObject->layout = $requestDB->$requestName->layout;

            if(isset($requestDB->$requestName->theme))
                $requestObject->theme  = $requestDB->$requestName->theme;

            if(isset($requestDB->$requestName->modules))
                $requestObject->modules = $requestDB->$requestName->modules;

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
                        static::$Logger->writeError ("Request includes " . $includedRequest . " but is not defined in the file. Ignoring" ,\Bread\Logger::SEVERITY_MEDIUM);
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
            return true;
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
            if(static::$isAjax)
            {
                // Turn off all error reporting
                static::ShowDebug(false);
                site::$Logger->writeMessage("Request is AJAX!");
                $module = "";
                $event = "Bread.AjaxRequest";
                if(isset($_REQUEST["ajaxEvent"]))
                {
                    $event = $_REQUEST["ajaxEvent"];
                }
                
                if(isset($_REQUEST["ajaxModule"]))
                {
                    $module = $_REQUEST["ajaxModule"];
                }
                else if(isset(static::$URLParameters["ajax"]))
                {
                    $module = static::$URLParameters["ajax"];
                }
                static::$Logger->writeMessage("Event: " . $event);
                if($module != "")
                {
                    static::$Logger->writeMessage("Module: " . $module);
                    $realdata = static::$moduleManager->FireSpecifiedModuleEvent($event,$module,array(),false);
                }
                else {
                    $realdata = static::$moduleManager->FireEvent($event,array(),true,false);
                }
                if($realdata === False)
                {
                   static::$Logger->writeError("Couldn't hook Ajax Request to requested module.",\Bread\Logger::SEVERITY_CRITICAL,"core");
                   return False;
                }
                echo $realdata;
                return True;
            }
            
	    //Draw
	    static::$htmlcode .= "<!DOCTYPE html5>\n<html>\n"; //Obviously.
	    static::$Logger->writeMessage("Beginning build of page");
	    static::$Logger->writeMessage("Request data:\n" . var_export($requestData,True));
	    //Process request
	    if(!static::$themeManager->SelectTheme($requestData)){
			static::$Logger->writeError("Couldn't select theme from request.",\Bread\Logger::SEVERITY_CRITICAL,"core",True);
	    }
	    if(!static::$themeManager->SelectLayout($requestData)){
			static::$Logger->writeError("Couldn't select layout from request.",\Bread\Logger::SEVERITY_CRITICAL,"core",True);
	    }

	    static::$themeManager->ReadElementsFromLayout(static::$themeManager->Theme["layout"]);#Build layout into HTML
            static::$moduleManager->FireEvent("Bread.FinishedLayoutProcess",null,false);
	    static::$htmlcode .= "<head>\n";
	    static::$htmlcode .= static::ProcessMetadata($requestData);
            $titleArray = static::$moduleManager->FireEvent("Bread.PageTitle",null,false);
            if($titleArray == false){
                Site::AddToHeaderCode("<title>" . self::$configuration->strings->sitename ."</title>");
            }
            elseif(is_string($titleArray)){
                Site::AddToHeaderCode("<title>" . $titleArray. " - "  . self::$configuration->strings->sitename ."</title>");
            }
            else{
                foreach($titleArray as $title){
                    if($title){
                        Site::AddToHeaderCode("<title>" . $title . " - " . self::$configuration->strings->sitename ."</title>");
                    }
                }
            }
            static::$htmlcode .= static::$headercode;
	    static::$htmlcode .= static::$themeManager->CSSLines;
            static::$moduleManager->FireEvent("Bread.InsertScript",NULL,false); //Must use add to head.
            static::$htmlcode .= static::$ScriptLines;
            static::$moduleManager->FireEvent("Bread.FinishedHead",NULL,false); //Must use add to head.
	    static::$htmlcode .= "</head>\n";
	    static::$htmlcode .= "<body>\n";
	    static::$htmlcode .= static::$bodycode;
            static::$moduleManager->FireEvent("Bread.LowPriorityScripts",NULL,false);
            static::$htmlcode .= static::$LowPriorityScriptLines;
            static::$moduleManager->FireEvent("Bread.FinishedBody",NULL,false); //Must use add to body.
	    static::$htmlcode .= "</body>\n";
	    static::$htmlcode .= "</html>\n";
	    echo static::$htmlcode;
	}
        
        /**
         * Closes the logger and gives any modules a chance to clean up their work
         * by calling "Bread.Cleanup".
         */
	public static function Cleanup()
	{
                static::$moduleManager->FireEvent("Bread.Cleanup",NULL);//Broadcast that we are cleaning up.
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
                   if(isset(Site::$configuration->directorys->$dir)){
                       $realdir = Site::$configuration->directorys->$dir;
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
         * @deprecated since version 0.2
         */
        public static function DigestURL($url)
        {
            return Utilitys::DigestURL($url);
        }
        
        /**
         * Create a URL from a baseurl and a array of params.
         * @param type $baseurl The base url of the site. Use False to use the current site baseurl.
         * @param type $params The array of params to append to the url. Leave as a blank array for none.
         * @return string The URL
         * @deprecated since version 0.2
         */
        public static function CondenseURLParams($baseurl,$params)
        {
            return Utilitys::CondenseURLParams($baseurl, $params);
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
        
        /**
         * Cast a standard object (say a unserialzed object) into its proper object.
         * @deprecated since version 0.2
         */
        public static function CastStdObjectToStruct($obj,$type)
        {
           return Utilitys::CastStdObjectToStruct($obj, $type);
        }
        
        /**
         * Redirect the user to a new URL and cleanup bread.
         * Careful with this.
         * @param string $url
         */
        public static function Redirect($url)
        {
            Site::$Logger->writeMessage("Redirected to " . $url);
            static::Cleanup();
            header("Location: " . $url);
        }
        
        /**
         * Find and return the operator in a string.
         * -2 : <=
         * -1 : <
         *  0 : ==
         *  1 : >
         *  2 : >=
         * @param \string $string
         * @return int
         * @deprecated since version 0.2
         */
        public static function findOperator($string)
        {
            return Utilitys::findOperator($string);
        }
        
        /**
         * Merges 2 objects.
         * @param object $objA The least important object, will be overwritten.
         * @param object $objB The more important object, will override keys.
         * @return type
         * @deprecated since version 0.2
         */
        public static function ObjMerge($objA,$objB)
        {
            return Utilitys::ObjMerge($objA, $objB);
        }
        
        /**
        * Sets the index of each element by one of its propertys.
        * @param array $array
        * @param string $propName
        * @return array
        * @deprecated since version 0.2
        */
        public static function ArraySetKeyByProperty($array,$propName)
        {
            return Utilitys::ArraySetKeyByProperty($array, $propName);
        }
        
        /**
         * Return a string value from a string which has a mix of letters and
         * numbers.
         * @param \string $string
         * @deprecated since version 0.2
         */
        public static function filterNumeric($string)
        {
            return Utilitys::filterNumeric($string);
        }
        
        /**
         * Looks for a file in the common user paths.
         * Ordered by layout, theme and resource.
         * Useful for layouts overriding.
         * @param type $filepath
         * @return string
         * @throws Exception
         * @deprecated since version 0.2
         */
        static function FindFile($filepath)
        {
            return Utilitys::FindFile($filepath);
        }
        /**
         * Converts a array of arrays into one single array.
         * @param array $arrays
         * @return array
         * @deprecated since version 0.2
         */
        static function MashArraysToSingleArray($arrays)
        {
            return Utilitys::MashArraysToSingleArray($arrays);
        }
        
        /**
         * Removes empty values from arrays.
         * @param array $haystack
         * @return array
         * @deprecated since version 0.2
         */
        static function array_clean(array $haystack)
        {
            return Utilitys::array_clean($haystack);
        }
        /**
         * Removes punctuation from a string, leaving only letters and numbers.
         * @param string $string Input String
         * @return string
         * @deprecated since version 0.2
         */
        static function RemovePunctuation($string)
        {
            return Utilitys::RemovePunctuation($string);
        }
}
/**
 * A class that logs important information and also throws errors for bread.
 * The log file can be found in /temp/breadlog. This can be changed in settings.
 * The main logger is to be found in static::$Logger.
 * @see static::$Logger
 */
class Logger
{
    /**
     * Filemode to use when writing to files
     */
    const FILEMODE = "a";
    /**
     * Non important message only useful for debugging.
     */
    const SEVERITY_MESSAGE = -1;
    /**
     * A message that can ususally be safely ignored but can help the user with any problems.
     */
    const SEVERITY_LOW = 0;
    /**
     * A message that should be looked into which could interphere with the running of a module.
     */
    const SEVERITY_MEDIUM = 1;
    /**
     * A large issue that WILL prevent some features from running and could have large adverse effects on portions of the site.
     */
    const SEVERITY_HIGH = 2;
    /**
     * A critial error which affects bread and will stop it from running properly at any capacity.
     */
    const SEVERITY_CRITICAL = 3;
    
    static $SeverityStringArray = array( -1 => "MESSAGE",0 => "LOW",1 => "WARN",2 => "ERROR",3 => "CRITICAL");
    
    const DATEFORMAT = "DM_H_i_s";
    private $logpath = "NOLOG";
    private $minlog = -1;
    private $logpermodule = true;
    private $messageStack = array();
    private $reportedErrors = array();
    private $fileStreams = array();
    
    /**
     * Gets the messages passed to the logger since startup.
     * Stacked as category => LoggerMessage
     * @return array
     */
    function getMessageStack()
    {
        return $this->messageStack;
    }
    
    /**
     * Creates a new Logger object. 
     * Throws an error if the logger can't create a file.
     * Leave a blank string if you do not want to log to a file.
     * @param string $filepath File name to log to.
     * @return False
     */
    function __construct($filepath,$minlog = 1,$maxlogs = 50,$logpermodule = false,$keepfor = -1)
    {
        if($filepath == ""){
		$this->logpath == "NOLOG";
                return;
        }
        $this->logpermodule = $logpermodule;
        $this->logpath = $filepath. "/" . date(self::DATEFORMAT);
        $this->minlog = $minlog;
        $this->cleanUpLogFiles($maxlogs,$filepath,$keepfor);
        static::writeMessage("Bread Version " . Site::Configuration()->core->version);
        static::writeMessage("Log Date: " . date('l jS \of F Y'));
   }
    /**
     * Function to clean up old log files from the log dir.
     * @param int $limit How many can be stored.
     * @param string $filepath The dir where files are stored.
     * @param string $keepfor Longest time to store for (in seconds), if less than 1 then ignore.
     */
    private function cleanUpLogFiles($limit,$filepath,$keepfor){
        $logs = scandir($filepath);
        unset($logs[array_search(".", $logs)]);
        unset($logs[array_search("..", $logs)]);
        //Past its sell by date.
        $i = -1;
        foreach($logs as $file)
        {
            $i++;
            $file = $filepath . "/" . $file;
            if($keepfor < 1)
                continue;
            if(time() - filemtime($file) > $keepfor){
                  $this->RemoveLog($file);
                  unset($logs[$i]);
            }
            
            if(count($logs) > $limit)
            {
                $this->RemoveLog($file);
                unset($logs[$i]);
            }
        }
        
    } 
    /**
     * Removes a file/directory and its contents.
     * @param string $location
     */
    private function RemoveLog($location)
    {
        if(is_dir($location)){
            foreach(scandir($location) as $logfile){
                if($logfile == "." | $logfile == "..")
                    continue;
                unlink($location . "/"  . $logfile); 
            } 
            rmdir($location);
        }
        else
        {
            try{
                unlink($location);
            }
            catch(FileNotFoundException $e)
            {
                //Eh, must be just a bug.
                $this->writeError("Couldn't delete file " . $location, self::SEVERITY_LOW);
            }
        }
    }
    /**
     * Open a new stream if one does not already exist.
     * @param string $category The category of log file to open the stream for.
     */
    private function openStream($category)
    {
        if(!$this->logpermodule){
            $this->fileStreams["core"] = fopen($this->logpath . ".log",static::FILEMODE);
            return;
        }
        else
        {
            if(!file_exists($this->logpath)){
                    mkdir($this->logpath,0777,true);//TODO: Better filemode system.
            }
            
        }
        $this->fileStreams[$category] = fopen($this->logpath . "/" . $category .".log",static::FILEMODE);
    }
    
    /**
     * Write some information to the log stack. Purely for debugging or user info
     * if the message is a potential problem, use Logger->writeError.
     * @see Logger::writeError()
     * @param string $string Message to be written
     * @param string $category Stream to use
     * @todo Decide to chuck either this or writeError because its pointless having similar functions.
     */
    function writeMessage($string,$category = "core")
    {
        $this->writeError($string,static::SEVERITY_MESSAGE,$category);
    }
    
    /**
     * Write an error to the log stack. If the message is just information,
     * use Logger->writeMessage. 
     * @see Logger::writeMessage()
     * @param string $string Message to be written
     * @param integer $severity Severity of the error
     * @param string $category Stream to write to.
     * @param bool $throw Should the logger throw an error
     * @param string $exception The Exception class to throw.
     * @throws \Exception
     */
    function writeError($string,$severity = -1,$category = "core",$throw = False,$exception = "\Exception")
    {
        if(!$this->logpermodule)
            $category = "core";
        $time = Site::GetTimeSinceStart();
        
        $message = new LoggerMessage;
        $message->time = Site::GetTimeSinceStart();
        $message->category = $category;
        $message->message = $string;
        $message->severity = $severity;
        $this->messageStack[$category][] = $message;
        
        if(isset(Site::$moduleManager)){
            Site::$moduleManager->FireEvent("Bread.LogError",$severity,false);
            if($severity > static::SEVERITY_LOW){
                if(!in_array($string,$this->reportedErrors)){
                    $html = Site::$moduleManager->FireEvent("Theme.DrawError",$message,true);
                    echo $html;
                    $this->reportedErrors[] = $string;
                }
            }
        }
        
	if($this->logpath != "NOLOG" && $this->minlog <= $severity){
            if(!in_array($category, $this->fileStreams))
                    $this->openStream($category);
            fwrite($this->fileStreams[$category],$message->ToString() . "\n");
        }
        if($throw)
            throw new $exception($string);
        
    }
    /**
     * Close the stream to the filestream. Also writes a closing statement.
     */
    function closeStream()
    {
	if($this->logpath == "NOLOG")
		return;
        static::writeMessage("Closing Log");
        foreach($this->fileStreams as $stream)
            fclose($stream);
    }
    
}

class LoggerMessage
{
    public $time = 0;
    public $category = "core";
    public $severity = -1;
    public $message = "No Message";
    /**
     * String representtion of the message.
     * @return string
     */
    public function ToString()
    {
        return "[". Logger::$SeverityStringArray[$this->severity] ."][" . number_format($this->time,3) . "]" . $this->message;
    }
}
?>