<?php
namespace Bread\Themes;
use Bread\Site as Site;
/**
 * The manager responsible fro loading Theme Modules and Layouts.
 * This loads the themes and layouts and decides which one is right, before calling
 * hooks listed by a module file. The final result is appended to the html.
 * @todo Add a theme structure.
 * @todo Add a layout structure.
 */
class ThemeManager
{
	//Collection
	private $themes;
	private $layouts;
	private $configuration;
	private $cssFiles;
	
	//Selected Items
	public $Theme;
	public $CSSLines = "";

	function __construct()
	{
		$this->themes = array();
		$this->layouts = array();
		$this->cssFiles = array();
	}

        /**
         * Load the settings files and retrive the settings for each listed theme as well.
         * @see Site::$configuration
         * @see Site::$themes
         * @param string $filepath Filepath to load settings from.
         */
        function LoadSettings($filepath)
	{
                $this->configuration = Site::$settingsManager->RetriveSettings($filepath);

		foreach ($this->configuration->themes as $theme)
		{
			$json = Site::$settingsManager->RetriveSettings(Site::ResolvePath("%user-themes"). "/" . $theme,true);
                        $this->themes[$json->module->name] = $json;
                        
                }
	}

	/**
         * Load the layout files listed in the themes settings.
         * @todo Find a more efficent way to load layouts, since the site will only be using one.
         */
	function LoadLayouts()
	{
	    $layouts_cfg = $this->configuration->layouts;
	    foreach($layouts_cfg as $layouttype => $layoutpath)
	    {
	    	$layout = array();
                $layout["JSON"] = Site::$settingsManager->RetriveSettings(Site::ResolvePath("%user-layouts"). "/"  . $layoutpath,true);
                $layout["abs_path"] = Site::ResolvePath("%user-layouts") . "/"  . $layoutpath;
                $layout["path"] = $layoutpath;
                $layout["type"] = $layouttype;
                $this->layouts[$layout["JSON"]->name] = $layout;
	    }
	}
        
	/**
         * Select the correct theme based on the request.
         * @param /Bread/Structures/BreadRequest $Request
         * @return boolean Was the theme set.
         */
	function SelectTheme($Request)
	{
                //If a module wants to force override a theme, it can from this call.
                $moduleResults = Site::$moduleManager->FireEvent("Bread.SelectTheme");
                if($moduleResults)
                {
                    $this->SetTheme($moduleResults["theme"]);
                    return True;
                }
                //Request Theme
                $this->SetTheme($this->themes[$Request->theme]);
                return True;
	}
        
        function SetTheme($suggestedTheme)
        {
            $this->Theme["data"] = $suggestedTheme->module;
            require_once(Site::ResolvePath("%user-themes"). "/" . $this->Theme["data"]->entryfile);
            $this->Theme["class"] = new $this->Theme["data"]->entryclass(Site::$moduleManager,$this->Theme["data"]->name);
            if(isset($this->Theme["data"]->css)){
                $this->cssFiles = array_merge($this->cssFiles,$this->Theme["data"]->css); //Add some css files.
            }
            if(isset($this->Theme["data"]->js)){
                foreach($this->Theme["data"]->js as $script){
                    Site::AddScript(Site::FindFile($script),basename($script));
                }
            }
            Site::$moduleManager->RegisterSelectedTheme();
            return True;
        }
        
	/**
         * Select the correct layout based on the request.
         * @param /Bread/Structures/BreadRequest $Request
         * @return boolean Was the layout set.
         */
	function SelectLayout($Request)
	{
            //If a module wants to force override a theme, it can from this call.
            $moduleResults = Site::$moduleManager->FireEvent("Bread.SelectLayout");
            if($moduleResults)
            {
                $layout = ($moduleResults["layout"]);
                $this->Theme["layout"] = $layout;
                return True;
            }
            if(!array_key_exists($Request->layout, $this->layouts))
                    Site::$Logger->writeError ("Layout does not exist!", \Bread\Logger::SEVERITY_CRITICAL,"core", true);
            $this->Theme["layout"] = $this->ApplyMaster($this->layouts[$Request->layout]);
            return True;
                
	}
        
        function ApplyMaster($layout)
        {
            $LayoutStruct = $layout["JSON"];
            if(!isset($LayoutStruct->master))
                return $layout;
            $problemKeys = array_keys($LayoutStruct->master, $LayoutStruct->name);
            foreach($problemKeys as $key){unset($LayoutStruct->master[$key]);}
            foreach($LayoutStruct->master as $layoutName)
            {
                if(!array_key_exists($layoutName, $this->layouts)){
                    Site::$Logger->writeError("Can't apply master layout" . $layoutName . " to child layout. Master not loaded.", \Bread\Logger::SEVERITY_HIGH, "ThemeManager");
                }
                $masterLayout = $this->ApplyMaster($this->layouts[$layoutName])["JSON"];
                if(isset($masterLayout->css)){
                    if(!isset($LayoutStruct->css)){
                        $LayoutStruct->css = array();
                    }
                    else if(!is_array($LayoutStruct->css)){
                        $LayoutStruct->css = array($LayoutStruct->css);
                    }
                    $LayoutStruct->css = array_merge ($masterLayout->css,$LayoutStruct->css);
                }
                if(isset($masterLayout->scripts)){
                    if(!isset($LayoutStruct->scripts)){
                        $LayoutStruct->scripts = array();
                    }
                    else if(!is_array($LayoutStruct->scripts)){
                        $LayoutStruct->scripts = array($LayoutStruct->scripts);
                    }
                    $LayoutStruct->scripts = array_merge ($masterLayout->scripts,$LayoutStruct->scripts);
                }
                
                $LayoutElements = Site::ArraySetKeyByProperty($LayoutStruct->elements, 'name');
                $MasterElements = Site::ArraySetKeyByProperty($masterLayout->elements, 'name');
                $LayoutElements = array_merge($MasterElements,$LayoutElements);
                $LayoutStruct->elements = $LayoutElements;
            }
            $layout["JSON"] = $LayoutStruct;
            return $layout;
        }
        
        /**
         * Add CSS to the HTML page for each file registered.
         */
	function BuildCSS()
	{
		foreach($this->cssFiles as $filepath)
		{
                    try {
                        $cssfilepath = Site::FindFile($filepath);
                    } catch (\Exception $exc) {
                        Site::$Logger->writeError("Failed to find CSS File '" . $filepath .", ignoring.'", \Bread\Logger::SEVERITY_MEDIUM,"core", false);
                        continue;
                    }
                    
                    $this->CSSLines .= "<link rel='stylesheet' type='text/css' href='" . $cssfilepath . "'>\n";
		}
	}
	/**
         * Add a CSS file to the list.
         * @param string $filepath The relative path of the file.
         */
        function AddCSSFile($filepath)
        {
            $this->cssFiles[] = $filepath;
        }
        

        /**
         * Read the elements from the layout and puts them in the right place.
         * @param stdClass $Layout
         */
	function ReadElementsFromLayout($Layout)
	{
		$IsRoot = ($Layout == $this->Theme["layout"]);
		if($IsRoot){
                       $Layout = $Layout["JSON"];
                       $this->DoRootStuff($Layout);
                }
                if(isset($Layout->elements))
                {
                    $ReturnedData = array();
                    foreach ($Layout->elements as $element)
                    {
                       $ReturnedData[] = $this->ReadElementsFromLayout($element);
                    }
                    if(!$IsRoot){
                       $ReturnedData = $this->ExamineElement($Layout,$ReturnedData);
                    }
                }
                else
                {
                    $ReturnedData = $this->ExamineElement($Layout);
                }
                if($IsRoot)
                    $this->WriteHTML($ReturnedData);
                return $ReturnedData;
	}
        /**
         * Checks for missing propertys to the element AND
         * any hacks that might be injected.
         * @param array $element Element to scan for hacks
         * @return array Returns a array to be drawn.
         */
        function ExamineElement($element,$extraargs = false)
        {
            $returnedElement = array();
            //Defaults
            $returnedElement["event"] = ""; //Standard module draw function.
            $returnedElement["arguments"] = array();
            $returnedElement["attributes"] = array();
            $returnedElement["tag"] = "div";
            $returnedElement["id"] = False;
            
            foreach($returnedElement as $key => $default){
                    if(isset($element->$key)){
                        $returnedElement[$key] = $element->$key;
                    }    
            }
            if(empty($returnedElement["event"]) && !isset($element->module))
            {
                $returnedElement["guts"] = false;
                return $returnedElement;
            }
            //Check tag for hacks
            if(!\ctype_alpha($returnedElement["tag"]))
            {
                //Non alpha chars in tag name. KILL
                Site::$Logger->writeError("Layout " . $this->Theme["layout"]["JSON"]->name . " has problems. Not drawing problematic tag " . $element->name,\Bread\Logger::SEVERITY_MEDIUM,"core");
                $returnedElement["guts"] = false;
                return $returnedElement;
            }
            if(!is_array($returnedElement["arguments"])){
             $returnedElement["arguments"] = array($returnedElement["arguments"]);
            }
            $returnedElement["arguments"]["_inner"] = $extraargs;
            if(isset($element->attributes))
                $returnedElement["attributes"] = get_object_vars($element->attributes);
            if(isset($element->module))
            {
                    $returnedElement["guts"] = Site::$moduleManager->FireSpecifiedModuleEvent($returnedElement["event"],$element->module,$returnedElement["arguments"]);
            }
            else
            {
                    $returnedElement["guts"] = Site::$moduleManager->FireEvent($returnedElement["event"],$returnedElement["arguments"]);
            }
            
            if(!is_string($returnedElement["guts"]))
            {
                Site::$Logger->writeError("Element failed to draw due to event returning non HTML.", \Bread\Logger::SEVERITY_MESSAGE);
                $returnedElement["guts"] = false;
            }
            return $returnedElement;
        }
        
        function DoRootStuff($Layout){
            if(!isset($Layout->elements))//No enclosed elements.
                   Site::$Logger->writeError("Layout contains no elements, page cannot be built.",\Bread\Logger::SEVERITY_CRITICAL,"core",True);
            if(isset($Layout->css)){
               $this->cssFiles = array_merge($this->cssFiles,$Layout->css);
               $this->BuildCSS();
            }
            if(isset($Layout->scripts)){
               foreach($Layout->scripts as $path)
               {
                   try {
                       Site::AddScript(Site::FindFile($path),basename($path));
                   } catch (\Exception $exc) {
                       Site::$Logger->writeError("Failed to find Scriptfile File '" . $path .", ignoring.'",\Bread\Logger::SEVERITY_MEDIUM,"core", false);
                       continue;
                   }
               }
            }
            return $Layout;
        }
        
        function WriteHTML($ReturnedData)
        {
            foreach($ReturnedData as $Data)
            {
                if(!isset($Data["guts"]))
                {
                    $this->WriteHTML($Data);
                }
                else {
                    Site::AddToBodyCode($this->FormatElementHTML($Data));
                }
            }
        }
        
        function FormatElementHTML($returnedElement)
        {
            if(!$returnedElement["guts"]){
                return "";
            }
            $HTML = "<" . $returnedElement["tag"] . " id='" . $returnedElement["id"] . "'";
            foreach($returnedElement["attributes"] as $key => $data)
            {
                $HTML .= " " . $key . ":'" . $data . "'";
            }
            $HTML .= ">" . $returnedElement["guts"];
            $HTML .= "</" . $returnedElement["tag"] . ">";
            return $HTML;
        }
}
?>
