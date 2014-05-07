<?php
namespace Bread\Modules;
use Bread\Site as Site;
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
	private $configuration;
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
	function LoadModulesFromConfig($filepath)
	{
                $mods = Site::$settingsManager->RetriveSettings($filepath,true);
                if(!array_key_exists("enabled", $this->moduleList))
                    $this->moduleList["enabled"] = array();
                
                if(!array_key_exists("blacklisted", $this->moduleList))
                    $this->moduleList["blacklisted"] = array();              
                
		$this->moduleList["enabled"] = array_merge($this->moduleList["enabled"],$mods->enabled);
                $this->moduleList["blacklisted"] = array_merge($this->moduleList["blacklisted"],$mods->blacklisted);
	}
	
	/**
         * Reads the processed request and only loads required modules, saving memory.
         * @param /Bread/Structures/BreadRequest $request The request object.
         */
	function LoadRequiredModules($request)
	{
	    foreach($request->modules as $module)
	    {
                    if(array_search($module, $this->moduleList["enabled"])){
                        $path = Site::ResolvePath("%user-modules") . "/" . $module;
                        $this->RegisterModule($path);
                    }
	    }
            $this->LoadModules();
	}
        /**
         * Loads and register the module, constructing it and placing it in the ModuleManager::$modules array.
         * Also runs the RegisterEvents function. This is only run from LoadRequiredModules.
         * *Warning*: Code errors with modules cannot be checked against so any whitepage crashes will be down to module problems.
         * You can debug this by checking the log for the last registered module which is the culprit.
         * @param string $path
         */
	private function RegisterModule($path)
	{
                $json = Site::$settingsManager->RetriveSettings($path,true);
                if(isset($json->dependencies))
                    $json->dependencies = get_object_vars ($json->dependencies);
                $object = Site::CastStdObjectToStruct($json, "Bread\Structures\BreadModuleStructure");
                $ModuleName = $object->name;
		if(array_key_exists($ModuleName,$this->modules))
			Site::$Logger->writeError('Cannot register module. Module already exists',  \Bread\Logger::SEVERITY_MEDIUM);
		
		Site::$Logger->writeMessage('Registered module ' . $ModuleName);
		$this->moduleConfig[$object->name] = $object;
	}
        /**
         * Will load the module if the dependencies are met.
         */
        private function LoadModules()
        {
            foreach($this->moduleConfig as $name => $module)
            {
                $deps = $this->DependenciesRegistered($module);
                if(empty($deps)){
                    //Stupid PHP cannot validate files without running command trickery.
                    include_once(Site::ResolvePath("%user-modules") . "/" . $module->entryfile);
                    //Modules should be inside the namespace Bread\Modules but can differ if need be.
                    $class = 'Bread\Modules\\'  . $module->entryclass;
                    if(!is_null($module->namespace)){
                        $namespace = $module->namespace;
                        $class = $module->namespace . "\\" . $module->entryclass;
                    }
                    $this->modules[$name] = new $class($this,$name);
                    $this->modules[$name]->RegisterEvents();
                }
                else
                {
                    Site::$Logger->writeError("Module '" . $name . "' could not be registered due to missing depedencies: ", \Bread\Logger::SEVERITY_HIGH);
                    Site::$Logger->writeMessage(var_export($deps,true));
                }
            }
        }
        
        function DependenciesRegistered($module)
        {
            $faillist = array();
            Site::$Logger->writeMessage('Checking dependencies of ' . $module->name);
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
         * The method is simular to ModuleManager::RegisterModule()
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
         */
	function RegisterHook($moduleName,$eventName,$function,$securitylevel = 0,$dependencies = array())
	{
	    if(!array_key_exists($eventName,$this->events)){
	        $this->events[$eventName] = array();
		}
		
	    $this->events[$eventName][$moduleName] = array($function,$dependencies,$securitylevel);
            Site::$Logger->writeMessage('Hook registered ' . $moduleName . '::' . $eventName);    
	}
        /**
         * Same as RegisterHook but uses an array of hooks instead.
         * Format of ["event"],["function"],["dependencies"],["security"]
         * @see self::RegisterHook()
         * @param type $moduleName The module name as set when the module was registered.
         * @param type $hookArray The array of hooks.
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
            if(count($this->GetModuleEvents($moduleName)) == 0 && $removeModule){
                Site::$Logger->writeMessage($moduleName . ' automatically removed from stack.');  
                unset($this->modules[$moduleName]);
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
            foreach($dependencies as $event => $module)
            {
                if(array_key_exists($event, $this->completed)){
                        if(!array_search($module, $this->completed))
                                $needToRun[$event] = $module;
                }
                else {
                    $needToRun[$event] = $module;
                }
            }
            return $needToRun;
        }
        
        /**
         * Send a message to ModuleManager that the event should be fired on all hooked functions.
         * It will call all registered hooks to run their function and return the data if any into a array.
         * @param string $eventName The event to fire.
         * @param any $arguments An array or any datatype to be passed to the function.
         * @return array|bool An array of the returned data or false if no data was returned.
         */
	function FireEvent($eventName,$arguments = null,$isInternal = true)
	{
            $returnData = array();
            if(!array_key_exists($eventName,$this->events))
                return False; //Event not used.
            
            foreach($this->events[$eventName] as $module => $data)
            {
                $function = $data[0];
                $dependencies = $data[1];
                $security = $data[2];
                if((!$isInternal && $security < 1)|| ($security == static::EVENT_EXTERNAL && !Site::GetisAjax())){
                    Site::$Logger->writeError("Security Failed on Event Call.\n EventName: " . $eventName, \Bread\Logger::SEVERITY_CRITICAL, "core", true);
                }
                if(!method_exists($this->modules[$module],$function)){
                    Site::$Logger->writeError("Event failed to fire because the listed function does not exist. Event Name: " . $eventName . ", Module Name: " . $module, \Bread\Logger::SEVERITY_HIGH, "core");
                    return False;
                }
                $toRun = $this->CanRunEvent($dependencies);
                foreach($toRun as $depEvt => $depMod)
                {
                    $this->FireSpecifiedModuleEvent($depEvt,$depMod);
                }
                
                $returnData[] = $this->modules[$module]->$function($arguments);
                $this->completed[$eventName] = $module;
            }
            if(!array_filter($returnData)){
                return False;
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
            if(!array_key_exists($moduleName,$this->modules)){
	        Site::$Logger->writeError ("Couldn't specifically hook module '" . $moduleName . "'. Module not loaded.", \Bread\Logger::SEVERITY_MEDIUM); //Module not found.
                return False;
            }
            
            if(!array_key_exists($eventName, $this->events)){
                Site::$Logger->writeError ("Couldn't specifically hook module '" . $moduleName . "'. That event is not called by any module.", \Bread\Logger::SEVERITY_LOW);
                return False;
            }
            
            if(!array_key_exists($moduleName, $this->events[$eventName])){
                Site::$Logger->writeError ("Couldn't specifically hook module '" . $moduleName . "'. Module does not have that event set.", \Bread\Logger::SEVERITY_MEDIUM);
                return False;
            }
            $data = $this->events[$eventName][$moduleName];
            $function = $data[0];
            $dependencies = $data[1];
            $security = $data[2];
            if((!$isInternal && $security < 1)|| ($security == static::EVENT_EXTERNAL && !Site::GetisAjax())){
                Site::$Logger->writeError("Security Failed on Event Call.\n EventName: " . $eventName . "\n ModuleName: " . $moduleName, \Bread\Logger::SEVERITY_CRITICAL, "core", true);
            }
            if(!$this->CanRunEvent($dependencies))
            {
                foreach($dependencies as $event => $module)
                {
                    $this->FireSpecifiedModuleEvent($event,$module);
                }
            }
            $this->completed[$eventName] = $moduleName;
	    return $this->modules[$moduleName]->$function($arguments);
	}
}
?>
