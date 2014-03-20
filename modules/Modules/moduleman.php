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
	function __construct()
	{
		$this->modules = array();
		$this->moduleList = array();
		$this->events = array();
                $this->completed = array();
                $this->heldevents = array();
	}
        /**
         * Loads settings/
         * @todo This actually does pretty much nothing since the configuration is not used.
         * @param string $filepath Filepath of the configuration file.
         */
	function LoadSettings($filepath)
	{
		$this->configuration = Site::$settingsManager->RetriveSettings($filepath);
	}
        /**
         * Loads the modulelist from its json file.
         * @param string $filepath Filepath of the modulelist.
         */
	function LoadModulesFromConfig($filepath)
	{
                $mods = Site::$settingsManager->RetriveSettings($filepath);
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
	function RegisterHook($moduleName,$eventName,$function,$dependencies = array())
	{
	    if(!array_key_exists($eventName,$this->events)){
	        $this->events[$eventName] = array();
		}
		
	    $this->events[$eventName][$moduleName] = array($function,$dependencies);

	}
	
        /**
         * Can the event be run in regards to its dependencies.
         * @param type $dependencies A list of dependencies. format of EventName => ModuleName.
         * @return bool Returns if it can run the event. 
         */
        function CanRunEvent($dependencies)
        {
            $needToRun = array();
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
	function FireEvent($eventName,$arguments = null)
	{
            $returnData = array();
            if(!array_key_exists($eventName,$this->events))
                return False; //Event not used.
            
            foreach($this->events[$eventName] as $module => $data)
            {
                $function = $data[0];
                $dependencies = $data[1];
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
	function FireSpecifiedModuleEvent($eventName,$moduleName,$arguments = null)
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
