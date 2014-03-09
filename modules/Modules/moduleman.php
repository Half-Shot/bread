<?php
namespace Bread\Modules;
use Bread\Site as Site;
class ModuleManager
{
	private $modules;
	private $moduleList;
	private $moudleConfig;
	private $configuration;
	private $events;
        private $completed;
	function __construct()
	{
		$this->modules = array();
		$this->moduleList = array();
		$this->moduleConfig = array();
		$this->events = array();
                $this->completed = array();
                $this->heldevents = array();
	}

	function LoadSettings($filepath)
	{
		$this->configuration = Site::$settingsManager->RetriveSettings($filepath);
	}

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
	
	#Only load modules we need.
	function LoadRequiredModules($request)
	{
	    foreach($request->modules as $module)
	    {
                    if(array_search($module, $this->moduleList["enabled"])){
                        $path = Site::ResolvePath("%user-modules") . "/" . $module;
                        $this->RegisterModule($path);
                    }
	    }
	}

	function RegisterModule($path)
	{
                $json = Site::$settingsManager->RetriveSettings($path,true);
                $ModuleName = $json->name;
		if(array_key_exists($ModuleName,$this->modules))
			Site::$Logger->writeError('Cannot register module. Module already exists');
		
		Site::$Logger->writeMessage('Registered module ' . $ModuleName);
		//Stupid PHP cannot validate files without running command trickery.
		include_once(Site::ResolvePath("%user-modules") . "/" . $json->entryfile);
		//Modules should be inside the namespace Bread\Modules but can differ if need be.
		$class = 'Bread\Modules\\'  . $json->entryclass;
		if(isset($json->namespace)){
		    $namespace = $json->namespace;
		    $class = $json->namespace . "\\" . $json->entryclass;
		}
		$this->moduleConfig[$json->name] = $json;
		$this->modules[$json->name] = new $class($this,$ModuleName);
		$this->modules[$json->name]->RegisterEvents();
	}
	
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
	
	function RegisterEvent($moduleName,$eventName,$function,$dependencies = array())
	{
	    if(!array_key_exists($eventName,$this->events)){
	        $this->events[$eventName] = array();
		}
		
	    $this->events[$eventName][$moduleName] = array($function,$dependencies);

	}
	
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
        
	function HookEvent($eventName,$arguments = null)
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
                    $this->HookSpecifedModuleEvent($depEvt,$depMod);
                }
                $returnData[] = $this->modules[$module]->$function($arguments);
                $this->completed[$eventName] = $module;
            }
            if(!array_filter($returnData)){
                return False;
            }
            return $returnData;
	}
	
	function HookSpecifedModuleEvent($eventName,$moduleName,$arguments = null)
	{
            if(!array_key_exists($moduleName,$this->modules)){
	        Site::$Logger->writeError ("Couldn't specifically hook module '" . $moduleName . "'. Module not loaded.", 3); //Module not found.
                return False;
            }
            
            if(!array_key_exists($eventName, $this->events)){
                Site::$Logger->writeError ("Couldn't specifically hook module '" . $moduleName . "'. That event is not called by any module.", 3); //Module not found.
                return False;
            }
            
            if(!array_key_exists($moduleName, $this->events[$eventName])){
                Site::$Logger->writeError ("Couldn't specifically hook module '" . $moduleName . "'. Module does not have that event set.", 3); //Module not found.
                return False;
            }
            $data = $this->events[$eventName][$moduleName];
            $function = $data[0];
            $dependencies = $data[1];
            if(!$this->CanRunEvent($dependencies))
            {
                foreach($dependencies as $event => $module)
                {
                    $this->HookSpecifedModuleEvent($event,$module);
                }
            }
            $this->completed[$eventName] = $moduleName;
	    return $this->modules[$moduleName]->$function($arguments);
	}
}
?>
