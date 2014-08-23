<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Utilitys as Util;
/**
 * The manager responsible for Modules: The plugin system for bread.
 * Any extra code you will do for bread will run through this.
 * It deals with loading and unloading of external code, and hooking it at
 * appropriate times.
 */
class ModuleManager
{
    /**
     * A list of modules
     * @var type 
     */
    private $modules;
    private $moduleList;
    private $moduleConfig;
    private $events;
    private $completed;
        
    const EVENT_INTERNAL = 0;
    const EVENT_EXTERNAL = 1;
    const EVENT_EXTERNAL_NONAJAX = 2;
        
        
	function __construct()
	{
            $this->modules = array();
            $this->moduleList = array();
            $this->events = array();
            $this->completed = array();
            $this->heldevents = array();
	}
        /**
         * Loads the modulelist from its json file.
         * @param string $filepath Filepath of the modulelist.
         */
	function LoadModulesFromConfig()
	{
            $this->settings = Site::$settingsManager->RetriveSettings("modules#settings",true, new BreadModuleManagerSettings());
            $this->moduleList = $this->settings->modules;
            $this->moduleList = Util::ArraySetKeyByProperty($this->moduleList, "name");
            //Resolve path
            foreach ($this->moduleList as $module) {
                $module->file = Site::ResolvePath("%user-modules") . "/" . $module->file;
                $this->RegisterModule($module);
            }
	}

        function GetModuleConfig($Name){
            return clone $this->moduleConfig[$Name];
        }
        
        function GetModuleList(){
            return $this->moduleList;
        }
        /**
         * Blacklist or Unblacklist a module.
         * @param string $Name Name of module to blacklist
         * @param boolean $shouldBlacklist Blacklist or unblacklist?
         */
        function BlacklistModule($Name,$shouldBlacklist = true){
            if($this->manager->FireEvent("Bread.Security.GetPermission","Bread.BlacklistModule")){
                foreach($module as $this->settings->modules){
                    if($module->Name == $Name){
                        $module->blacklist = $shouldBlacklist;
                        break;
                    }
                }
            }
        }
        
        /**
         * Loads and register the module,/O/ constructing it and placing it in the ModuleManager::$modules array.
         * Also runs the RegisterEvents function. This is only run from LoadRequiredModules.
         * *Warning*: Code errors with modules cannot be checked against so any whitepage crashes will be down to module problems.
         * You can debug this by checking the log for the last registered module which is the culprit.
         * @param string $path
         */
	private function RegisterModule($module)
	{
            $modName = $module->name;
            $json = Site::$settingsManager->RetriveSettings($module->file,true);
            if(isset($json->dependencies)){
                $json->dependencies = get_object_vars ($json->dependencies);
            }
            $object = Site::CastStdObjectToStruct($json, "Bread\Structures\BreadModuleStructure");
            if(array_key_exists($modName,$this->modules)){
                Site::$Logger->writeError('Cannot register' . $modName. '.Module already exists',  \Bread\Logger::SEVERITY_MEDIUM);
                return False;
            }
            Site::$Logger->writeMessage('Registered module ' . $modName);
            $this->moduleConfig[$modName] = $object;
            if(isset($json->events)){
                $events = get_object_vars($json->events);
                foreach ($events as $event => $data) {
                    if(!isset($data->security)){
                        $data->security = 0;
                    }
                    if(!isset($this->events[$event])){
                        $this->events[$event] = array();
                    }
                    $this->events[$event][$modName] =$data;
                }
            }
	}

    /**
     * Will load the module if the dependencies are met.
     */
    private function LoadModule($ModuleName)
    {
        if(array_key_exists($ModuleName, $this->modules))
        {
            return False;
        }
        $module = $this->moduleConfig[$ModuleName];
        $deps = $this->DependenciesRegistered($module);
        if(empty($deps)){
            //Stupid PHP cannot validate files without running command trickery.
            include(Site::ResolvePath("%user-modules") . "/" . $module->entryfile);
            //Modules should be inside the namespace Bread\Modules but can differ if need be.
            $class = 'Bread\Modules\\'  . $module->entryclass;
            if(!is_null($module->namespace)){
                $namespace = $module->namespace;
                $class = $module->namespace . "\\" . $module->entryclass;
            }
            $this->modules[$ModuleName] = new $class($this,$ModuleName);
            if(method_exists($this->modules[$ModuleName],"RegisterEvents")){ //Only used in old bread modules.
                $this->modules[$ModuleName]->RegisterEvents(); //Legacy!
            }
            Site::$Logger->writeMessage('Loaded module ' . $ModuleName);
        }
        else
        {
            Site::$Logger->writeError("Module '" . $name . "' could not be registered due to missing depedencies: ", \Bread\Logger::SEVERITY_HIGH);
            Site::$Logger->writeMessage(var_export($deps,true));
        }
        return True;
    }
    
    function DependenciesRegistered($module)
    {
        $faillist = array();
        Site::$Logger->writeMessage('Checking dependencies of ' . $module->name);
        if(!isset($module->dependencies))
            return $faillist;
        foreach($module->dependencies as $dep => $version)
        {
            Site::$Logger->writeMessage('Checking for '. $dep);
            if(!key_exists($dep, $this->moduleConfig))
            {
                Site::$Logger->writeMessage('No module named ' . $dep . ' is registered');
                $faillist[$dep] = "Failed to find module";
                continue;
            }
            
            if($version == -1)
                continue;
            
            //We don't know if it is an equal, less than or more than.
            Site::$Logger->writeMessage('Checking version ' . $version);
            $operator = Site::findOperator($version);
            $verNumber = (float)Site::filterNumeric($version);
            switch($operator):
                case -2:
                    if(!($module->version <= $verNumber))
                        $faillist[$dep] = "Version number too high.";
                    break;
                case -1:
                    if(!($module->version < $verNumber))
                        $faillist[$dep] = "Version number too high.";
                    break;
                case 0:
                    if(!($module->version == $verNumber))
                        $faillist[$dep] = "Version number not equal.";
                    break;
                case 1:
                    if(!($module->version > $verNumber))
                        $faillist[$dep] = "Version number too low.";
                    break;
                case 2:
                    if(!($module->version <= $verNumber))
                        $faillist[$dep] = "Version number too low.";
                    break;
            endswitch;
        }
        return $faillist;
    }
    
	/**
     * The selected theme from /Bread/Themes/ThemeManager is loaded as a module in here.
     * The method is similar to ModuleManager::RegisterModule()
     * @see /Bread/Themes/ThemeManager
     */
	function RegisterSelectedTheme()
	{
            if(!isset(Site::$themeManager->Theme["data"]))
            {
                    Site::$Logger->writeMessage("Warning: RegisterSelectedTheme called to early, no theme selected.");
            }
            $theme = Site::$themeManager->Theme["data"];
            if(isset($theme->namespace)){
                $namespace = $theme->namespace;
                $class = $namespace . "\\" . $theme->entryclass;
            }
            $this->moduleConfig[$theme->name] = $theme;
            $this->modules[$theme->name] = Site::$themeManager->Theme["class"];
            $this->modules[$theme->name]->RegisterEvents();
            $this->FireEvent("Theme.Load");
            Site::$Logger->writeMessage('Registered theme ' . $theme->name);
	}
	/**
         * Registers an event:
         *  A module can register itself to a event which means any arguments of a hook is passed to 
         *  this modules function. A hook can be called at any time with a string of the event it wants to call.
         * @param string $moduleName The module name as set when the module was registered.
         * @param type $eventName The event wished to be hooked onto.
         * @param string $function Function identifer of the object/module, just put the identifier, not the full location.
         * @param array $dependencies Any depedencies of another event that must be run first, format of EventName => ModuleName
         * @deprecated Please use the settings file provided for adding hooks.
         */
	function RegisterHook($moduleName,$eventName,$function,$securitylevel = 0,$dependencies = array())
	{
	    if(!array_key_exists($eventName,$this->events)){
	        $this->events[$eventName] = array();
		}
        $ModuleObj = new \stdClass();
        $ModuleObj->function = $function;
        $ModuleObj->security = $securitylevel;
        $ModuleObj->dependencies = $dependencies;
	    $this->events[$eventName][$moduleName] = $ModuleObj;
        Site::$Logger->writeMessage('Hook registered ' . $moduleName . '::' . $eventName);    
	}
    /**
     * Same as RegisterHook but uses an array of hooks instead.
     * Format of ["event"],["function"],["dependencies"],["security"]
     * @see self::RegisterHook()
     * @param type $moduleName The module name as set when the module was registered.
     * @param type $hookArray The array of hooks.
     * @deprecated Please use the settings file provided for adding hooks.
     */
    function RegisterHooks($moduleName, $hookArray)
    {
        foreach($hookArray as $hook)
        {
            if(!array_key_exists($hook, "dependencies"))
                    $hook["dependencies"] = array();
            if(!array_key_exists($hook, "security"))
                    $hook["security"] = 0;
            $this->RegisterHook($moduleName, $hook["event"], $hook["function"],$hook["dependencies"],$hook["security"]);
        }
    }
	/*
     * Unregister a set hook.
     */
    function UnregisterHook($moduleName,$eventName,$removeModule = true)
    {
        unset($this->events[$eventName][$moduleName]);
        Site::$Logger->writeMessage('Hook unregistered ' . $moduleName . '::' . $eventName);   
        if($removeModule){
            if(count($this->GetModuleEvents($moduleName)) == 0){
                Site::$Logger->writeMessage($moduleName . ' automatically removed from stack.');  
                unset($this->modules[$moduleName]);
            }
        }

    }
        
    function GetModuleEvents($moduleName)
    {
        $events = array();
        foreach($this->events as $eventname => $event)
        {
            if(array_key_exists($moduleName, $event))
                    $events[$eventname] = $event;
        }
        return $events;
    }
    
    function UnregisterModule($moduleName)
    {
        $events = $this->GetModuleEvents($moduleName);
        foreach($events as $eventName => $event)
        {
            $this->UnregisterHook($moduleName,$eventName,false);
        }
        unset($this->modules[$moduleName]);
        Site::$Logger->writeMessage($moduleName . ' was manually removed from stack.');  
    }
        
    /**
     * Can the event be run in regards to its dependencies.
     * @param type $dependencies A list of dependencies. format of EventName => ModuleName.
     * @return bool Returns if it can run the event. 
     */
    function CanRunEvent($dependencies)
    {
        $needToRun = array();
        if(!is_array($dependencies))
            return $needToRun;
        foreach($dependencies as $dep)
        {
            if(array_key_exists($dep->event, $this->completed)){
                    if(!array_search($dep->module, $this->completed))
                            $needToRun[$dep->event] = $dep->module;
            }
            else {
                $needToRun[$dep->event] = $dep->module;
            }
        }
        return $needToRun;
    }

    /**
     * Send a message to ModuleManager that the event should be fired on all hooked functions.
     * It will call all registered hooks to run their function and return the data if any into a array.
     * @param string $eventName The event to fire.
     * @param any $arguments An array or any datatype to be passed to the function.
     * @param is the event called from internals or from an external ajax. Usually should be true.
     * @param Should all modules be fired and returned or just the top one.
     * @param If only one module is fired, what is the offset of the module.
     * @return array|bool An array of the returned data or false if no data was returned.
     */
    function FireEvent($eventName,$arguments = null,$singleOnly = true, $isInternal = true,$singleOffset = 0)
    {
        if(!array_key_exists($eventName,$this->events))
            return False; //Event not known.
        if(count($this->events[$eventName]) == 0){
            return False; //Event is known but not called by any module.
        }
        
        if($singleOnly){
            if($singleOffset > count($this->events[$eventName]) - 1)
                return False; //Over Offset
            $ModList = array_slice($this->events[$eventName], $singleOffset,1,true);
            $moduleName = array_keys($ModList)[0];
            $data = $ModList[$moduleName];
            if($this->LoadModule($moduleName)){
                $this->FireSpecifiedModuleEvent("Bread.ProcessRequest", $moduleName, null, true);
                return $this->FireEvent($eventName, $arguments,$singleOnly,$isInternal, $singleOffset); //Reload the request as $this->events might have changed!
            }
            
            if((!$isInternal && $data->security < 1)|| ($data->security == static::EVENT_EXTERNAL && !Site::GetisAjax())){
                Site::$Logger->writeError("Security Failed on Event Call.\n EventName: " . $eventName, \Bread\Logger::SEVERITY_CRITICAL, "core", true);
            }
            
            if(!method_exists($this->modules[$moduleName],$data->function)){
                Site::$Logger->writeError("Event failed to fire because the listed function does not exist. Event Name: " . $eventName . ", Module Name: " . $moduleName, \Bread\Logger::SEVERITY_HIGH, "core");
                return False;
            }
            
            if(isset($data->dependencies)){
                $toRun = $this->CanRunEvent($data->dependencies);
                foreach($toRun as $depEvt => $depMod)
                {
                    $this->FireSpecifiedModuleEvent($depEvt,$depMod);
                }
            }
            
            $function = $data->function;
            if(Site::isDebug()){
                $eventTime = microtime();
                $memUsage = (memory_get_usage(False) / 1024);
            }
            $returnData = $this->modules[$moduleName]->$function($arguments);
            if(Site::isDebug()){
                $eventTime = microtime() - $eventTime;
                $memUsage = (memory_get_usage(False) / 1024) - $memUsage;
                Site::$Logger->writeMessage("Event: " . $eventName . " (". $function .") from " . $moduleName . " took " . $eventTime . " ms to complete with " . $memUsage . "kb used.", "eventProfile");
            }
            $this->completed[$eventName] = $moduleName;
        }
        else
        {
            $ModList = $this->events[$eventName];

            //Load the module if not loaded!
            foreach ($ModList as $moduleName => $data) {
                if($moduleName == Site::$themeManager->Theme["data"]->name)
                    break;
                if($this->LoadModule($moduleName)){
                    $this->FireSpecifiedModuleEvent("Bread.ProcessRequest", $moduleName, null, true);
                    return $this->FireEvent($eventName, $arguments, $singleOnly,$isInternal, $singleOffset); //Reload the request as $this->events might have changed!
                }
            }
            $returnData = array();
            foreach($ModList as $moduleName => $data)
            {
                if((!$isInternal && $data->security < 1)|| ($data->security == static::EVENT_EXTERNAL && !Site::GetisAjax())){
                    Site::$Logger->writeError("Security Failed on Event Call.\n EventName: " . $eventName, \Bread\Logger::SEVERITY_CRITICAL, "core", true);
                }
                if(!method_exists($this->modules[$moduleName],$data->function)){
                    Site::$Logger->writeError("Event failed to fire because the listed function does not exist. Event Name: " . $eventName . ", Module Name: " . $moduleName, \Bread\Logger::SEVERITY_HIGH, "core");
                    return False;
                }
                if(isset($data->dependencies)){
                    $toRun = $this->CanRunEvent($data->dependencies);
                    foreach($toRun as $depEvt => $depMod)
                    {
                        $this->FireSpecifiedModuleEvent($depEvt,$depMod);
                    }
                }
                $function = $data->function;
                if(Site::isDebug()){
                    $eventTime = microtime();
                    $memUsage = (memory_get_usage(False) / 1024);
                }
                $returnData[] = $this->modules[$moduleName]->$function($arguments);
                if(Site::isDebug()){
                    $eventTime = microtime() - $eventTime;
                    $memUsage = (memory_get_usage(False) / 1024) - $memUsage;
                    Site::$Logger->writeMessage("Event: " . $eventName . " (". $function .") from " . $moduleName . " took " . $eventTime . " ms to complete with " . $memUsage . "kb used.", "eventProfile");
                }
                $this->completed[$eventName] = $moduleName;
            }
            if(!array_filter($returnData)){
                return False;
            }
        }
        
        return $returnData;
    }
    /**
     * Similar to ModuleManager::FireEvent() but requires a module argument so you can pick which module picks it up.
     * @param string $eventName The event to fire.
     * @param any $arguments An array or any datatype to be passed to the function.
     * @param string $moduleName The module to use.
     * @return boolean|any Returns data from the module or false if it failed.
     */
	function FireSpecifiedModuleEvent($eventName,$moduleName,$arguments = null,$isInternal = true)
	{       
            //Load the module if not loaded!
            if($moduleName != Site::$themeManager->Theme["data"]->name)
            {
                if($this->LoadModule($moduleName)){
                    $this->FireSpecifiedModuleEvent("Bread.ProcessRequest", $moduleName, null, true);
                }
            }

            if(!array_key_exists($moduleName,$this->modules)){
	        Site::$Logger->writeError ("Couldn't specifically hook module '" . $moduleName . "'. Module not loaded.", \Bread\Logger::SEVERITY_LOW); //Module not found.
                return False;
            }
            
            if(!array_key_exists($eventName,$this->events)){
                Site::$Logger->writeError ("Couldn't specifically hook module '" . $moduleName . "'. " . $eventName . " is not called by any module.", \Bread\Logger::SEVERITY_LOW);
                return False;
            }
            
            if(!array_key_exists($moduleName,$this->events[$eventName])){
                Site::$Logger->writeError ("Couldn't specifically hook module '" . $moduleName . "'. Module does not have the " . $eventName . " event set.", \Bread\Logger::SEVERITY_LOW);
                return False;
            }

            $data = $this->events[$eventName][$moduleName];
            $function = $data->function;
            $security = $data->security;
            if((!$isInternal && $security < 1)|| ($security == static::EVENT_EXTERNAL && !Site::GetisAjax())){
                Site::$Logger->writeError("Security Failed on Event Call.\n EventName: " . $eventName . "\n ModuleName: " . $moduleName, \Bread\Logger::SEVERITY_CRITICAL, "core", true);
            }
            if(isset($data->dependencies)){
                if(!$this->CanRunEvent($data->dependencies))
                {
                    foreach($data->dependencies as $dep){
                        $this->FireSpecifiedModuleEvent($dep->event,$dep->module);
                    }
                }
            }
            $this->completed[$eventName] = $moduleName;
	    if(Site::isDebug()){
                $eventTime = microtime();
                $memUsage = (memory_get_usage(False) / 1024);
            }
            $returnData = $this->modules[$moduleName]->$function($arguments);
            if(Site::isDebug()){
                $eventTime = microtime() - $eventTime;
                $memUsage = (memory_get_usage(False) / 1024) - $memUsage;
                Site::$Logger->writeMessage("Event: " . $eventName . " (". $function .") from " . $moduleName . " took " . $eventTime . " ms to complete with " . $memUsage . "kb used.", "eventProfile");
            }
            return $returnData;
	}
}

class BreadModuleManagerSettings{
    public $modules = array();
    public $events = array();
}
?>
