<?php
namespace Bread\Themes;
use Bread\Site as Site;
class ThemeManager
{
	#Collection
	private $themes;
	private $layouts;
	private $configuration = "";
	private $cssFiles;
	
	#Selected Items
	public $Theme;
	public $CSSLines = "";

	function __construct()
	{
		$this->themes = array();
		$this->layouts = array();
		$this->Theme = array();
		$this->Layout = array();
		$this->cssFiles = array();
		$this->configuration = array();
	}

	public static function Configuration()
	{
		return $this->configuration;
	}

	function LoadSettings($filepath)
	{
		if(!file_exists($filepath))
		{
			Site::$Logger->writeError('Cannot load themes. Manager Settings file not found',1,True);
		}
		$tmp = file_get_contents($filepath);
		$this->configuration = json_decode($tmp,true);
		if($this->configuration == NULL)
			Site::$Logger->writeError('Cannot load themes. Manager Settings has invalid JSON.',1,True);

		foreach ($this->configuration["themes"] as $theme)
		{
			$this->RegisterTheme(Site::ResolvePath("%user-themes"). "/" . $theme["config-path"],$theme["use-for"]);
		}
	}

	//Adds to the layouts variable.
	function LoadLayouts()
	{
	    $layouts_cfg = $this->configuration["layouts"];
	    foreach($layouts_cfg as $layouttype => $layoutpath)
	    {
	    	$layout = array();
			$layout["JSON"] = json_decode(file_get_contents(Site::ResolvePath("%user-layouts"). "/"  . $layoutpath));
			$layout["abs_path"] = Site::ResolvePath("%user-layouts") . "/"  . $layoutpath;
			$layout["path"] = $layoutpath;
			$layout["type"] = $layouttype;
			$this->layouts[] = $layout;
	    }
	}

	function RegisterTheme($themeconfig,$usage)
	{
		//Parse config file
		//TODO: Sort out all that jazz about module permissions which is used in themes.
		if(!file_exists($themeconfig))
			Site::$Logger->writeError('Cannot register theme. Theme config not found',1,True);
		$theme = array();
		$tmp = file_get_contents($themeconfig);
		$theme["JSON"] = json_decode($tmp,true);

		if($theme["JSON"] == NULL)
			Site::$Logger->writeError('Cannot load theme. Theme data has invalid JSON.',1,True);

		if($theme["JSON"] == NULL){
			Site::$Logger->writeError('Cannot load theme. Theme data has missing required properties.',1,True);
                }
		foreach($usage as $uses){
			$this->themes[$uses] = $theme["JSON"];
                }
	}

        
        function GetCompatibleThemes($RequestType)
        {
                $return = array();
            	foreach ($this->themes as $usage => $theme)
		{
                    if($usage == $RequestType or $usage == "all"){
                        $return[] = $theme;
                    }
		}
                return $return;
        }
        
        function GetCompatibleLayouts($RequestType)
        {
                $return = array();
            	foreach ($this->layouts as $usage => $layout)
		{
                    if($usage == $RequestType or $usage == "all"){
                        $return[] = $layout;
                    }
		}
                return $return;
        }
        
	///Requests the theme that should be used for the request.
	function SelectTheme($Request)
	{
                //If a module wants to force override a theme, it can from this call.
                $moduleResults = Site::$moduleManager->HookEvent("Bread.SelectTheme",NULL);
                if($moduleResults)
                {
                    return $this->SetTheme($moduleResults[0]["theme"]);
                }
                //If a request wants to override the theme
                $themeToUse = $Request->theme;
                $themes = $this->GetCompatibleThemes($Request->requestType);
                if(count($themes) < $themeToUse + 1)
                    Site::$Logger->writeError ("No compatible theme could be found for the request!", 10);
                $theme = $themes[$themeToUse];
                //Set the theme.
                $this->SetTheme($theme);
		return True;
	}
        
        function SetTheme($suggestedTheme)
        {
            $this->Theme["data"] = $suggestedTheme["module"];
            require_once(Site::ResolvePath("%user-themes"). "/" . $this->Theme["data"]["entryfile"]);
            $this->Theme["class"] = new $this->Theme["data"]["entryclass"](Site::$moduleManager,$this->Theme["data"]["name"]);
            if(isset($this->Theme["data"]["css"])){
                    $this->cssFiles = array_merge($this->cssFiles,$this->Theme["data"]["css"]); //Add some css files.
            }
            Site::$moduleManager->RegisterSelectedTheme();
            return True;
        }

	///Requests the layout that should be used for the page request.
	function SelectLayout($Request)
	{
            //If a module wants to force override a theme, it can from this call.
            $moduleResults = Site::$moduleManager->HookEvent("Bread.SelectLayout",NULL);
            if($moduleResults)
            {
                $layout = ($moduleResults[0]["layout"]);
                $this->Theme["layout"] = $layout;
                return True;
            }
            //If a request wants to override the theme
            $layoutToUse = $Request->layout;
            $layouts = $this->GetCompatibleLayouts($Request->requestType);
            if(count($layouts) < $layoutToUse + 1)
                Site::$Logger->writeError ("No compatible theme could be found for the request!", 10);
            $layout = $layouts[$layoutToUse];
            //Set the theme.
            $this->Theme["layout"] = $layout;
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
        function FindLocalCSSFile($filepath)
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
                throw new \Exception;
        }
        
	function BuildCSS()
	{
		foreach($this->cssFiles as $filepath)
		{
                    try {
                        $cssfilepath = $this->FindLocalCSSFile($filepath);
                    } catch (\Exception $exc) {
                        Site::$Logger->writeError("Failed to find CSS File '" . $filepath .", ignoring.'", 2, false);
                        continue;
                    }
                    
                    $this->CSSLines .= "<link rel='stylesheet' type='text/css' href='" . $cssfilepath . "'>\n";
		}
	}
	
	//Returns nothing but reads from layout and does all the calling to modules
	//and adds CSS Files. Yes, this is the biggy.
	function ReadElementsFromLayout($Layout)
	{
		$IsRoot = ($Layout == $this->Theme["layout"]);
		if($IsRoot)
		{
			if(!isset($Layout["JSON"]->elements))//No enclosed elements.
				Site::$Logger->writeError("Layout contains no elements, page cannot be built.",1,True);
			if(!isset($Layout["JSON"]->css))
				Site::$Logger->writeError("Layout contains no css files, page cannot be built.",1,True);
			$this->cssFiles = array_merge($this->cssFiles,$Layout["JSON"]->css);
			$this->BuildCSS();
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
                            Site::AddToBodyCode("</". $element["tag"] .">\n");
                            return;
                        }
                }
		
		//Draw enclosed elements.
		if(!\is_array($Layout))//No enclosed elements.
			return;
		$elements = $Layout["JSON"]->elements;
		foreach($elements as $element)
			$this->ReadElementsFromLayout($element);
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
                Site::$Logger->writeError("Layout " . $this->Theme["layout"]["JSON"]->name . " has problems. Not drawing problematic tag " . $element->name, 8);
                return False;
            }
            
            
            if(isset($element->module))
            {
                    $returnedElement["guts"] = Site::$moduleManager->HookSpecifedModuleEvent($returnedElement["event"],$element->module,$returnedElement["arguments"]);
            }
            else
            {
                    $returnedElement["guts"] = Site::$moduleManager->HookEvent($returnedElement["event"],$returnedElement["arguments"]);
            }
            
            if(!$returnedElement["guts"])
            {
                $returnedElement["guts"] = "";
            }
            return $returnedElement;
        }
}
?>
