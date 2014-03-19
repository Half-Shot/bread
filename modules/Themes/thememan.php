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
			$json = Site::$settingsManager->RetriveSettings(Site::ResolvePath("%user-themes"). "/" . $theme->cpath,true);
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
                $moduleResults = Site::$moduleManager->FireEvent("Bread.SelectTheme",NULL);
                if($moduleResults)
                {
                    $this->SetTheme($moduleResults[0]["theme"]);
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
                    Site::AddScript($script);
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
            $moduleResults = Site::$moduleManager->FireEvent("Bread.SelectLayout",NULL);
            if($moduleResults)
            {
                $layout = ($moduleResults[0]["layout"]);
                $this->Theme["layout"] = $layout;
                return True;
            }
            if(!array_key_exists($Request->layout, $this->layouts))
                    Site::$Logger->writeError ("Layout does not exist!", \Bread\Logger::SEVERITY_CRITICAL,"core", true);
            $this->Theme["layout"] = $this->layouts[$Request->layout];
            return True;
                
	}
        
        /**
         * Looks for a CSS file in the common user paths.
         * Ordered by layout, theme and resource.
         * Useful for layouts overriding.
         * @param type $filepath
         * @return string
         * @throws Exception
         */
        function FindFile($filepath)
        {
                if(mb_substr($filepath, 0, 4) == "http")//Is remote.
                    return $filepath;
                $path = Site::ResolvePath("%user-layouts/" . $filepath);
                if(file_exists($path))
                    return $path;
                $path = Site::ResolvePath("%user-themes/" . $filepath);
                if(file_exists($path))
                    return $path; 
                $path = Site::ResolvePath("%user-resource/" . $filepath);
                if(file_exists($path))
                    return $path;
                $path = Site::ResolvePath("%user-modules/" . $filepath);
                if(file_exists($path))
                    return $path;
                throw new \Exception;
        }
        
        /**
         * Add CSS to the HTML page for each file registered.
         */
	function BuildCSS()
	{
		foreach($this->cssFiles as $filepath)
		{
                    try {
                        $cssfilepath = $this->FindFile($filepath);
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
                $endTag = "";
		if($IsRoot)
		{
                        $Layout = $Layout["JSON"];
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
                                    Site::AddScript($this->FindFile($path));
                                } catch (\Exception $exc) {
                                    Site::$Logger->writeError("Failed to find Scriptfile File '" . $path .", ignoring.'",\Bread\Logger::SEVERITY_MEDIUM,"core", false);
                                    continue;
                                }
                            }
                        }
		}    
                else
                {
                        /**
                         * @todo Fix code to use stdClass and not arrays.
                         */
                        $element = $this->ExamineElement($Layout);
                        if($element != False)
                        {
                            //Build tag.
                            $tagStart = "<" . $element["tag"];
                            foreach($element["attributes"] as $key => $data)
                            {
                                $tagStart .= " " . $key . "=" . "'" . $data . "'";
                            }
                            if(isset($element["id"]))
                                $tagStart .= " id='" . $element["id"] . "'";
                            //Not including name, but leaving it here for the future.
                            //if(isset($element["name"]))
                            //    $tagStart .= "name=" . $element["name"];
                            $tagStart .= ">";
                            Site::AddToBodyCode($tagStart);
                            if(\is_array($element["guts"])){
                                foreach($element["guts"] as $bodycode)
                                {
                                     Site::AddToBodyCode($bodycode);
                                }
                            }
                            else {
                                Site::AddToBodyCode($element["guts"]);
                            }
                            $endTag = "</". $element["tag"] .">\n";
                        }
                }
		
		//Draw enclosed elements.
		if(!isset($Layout->elements)){//No enclosed elements.
			Site::AddToBodyCode($endTag);
                        return;
                }
		$elements = $Layout->elements;
		foreach($elements as $element){
			$this->ReadElementsFromLayout($element);
                }
                Site::AddToBodyCode($endTag);
	}
        /**
         * Checks for missing propertys to the element AND
         * any hacks that might be injected.
         * @param array $element Element to scan for hacks
         * @return array Returns a array to be drawn.
         */
        function ExamineElement($element)
        {
            $returnedElement = array();
            //Defaults
            $returnedElement["event"] = "Bread.DrawModule"; //Standard module draw function.
            $returnedElement["arguments"] = array();
            $returnedElement["attributes"] = array();
            $returnedElement["tag"] = "div";
            $returnedElement["id"] = False;
            foreach($returnedElement as $key => $default){
                    if(isset($element->$key)){
                        $returnedElement[$key] = $element->$key;
                    }    
            }
            
            //Check tag for hacks
            if(!\ctype_alpha($returnedElement["tag"]))
            {
                //Non alpha chars in tag name. KILL
                Site::$Logger->writeError("Layout " . $this->Theme["layout"]["JSON"]->name . " has problems. Not drawing problematic tag " . $element->name,\Bread\Logger::SEVERITY_MEDIUM,"core");
                return False;
            }
            
            
            if(isset($element->module))
            {
                    $returnedElement["guts"] = Site::$moduleManager->FireSpecifiedModuleEvent($returnedElement["event"],$element->module,$returnedElement["arguments"]);
            }
            else
            {
                    $returnedElement["guts"] = Site::$moduleManager->FireEvent($returnedElement["event"],$returnedElement["arguments"]);
            }
            
            if(!$returnedElement["guts"])
            {
                $returnedElement["guts"] = "";
            }
            return $returnedElement;
        }
}
?>
