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
	public $Layout;
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
			$this->RegisterTheme(Site::Configuration()["directorys"]["user-themes"]. "/" . $theme["config-path"],$theme["use-for"]);
		}
	}

	//Adds to the layouts variable.
	function LoadLayouts()
	{
	    $layouts_cfg = $this->configuration["layouts"];
	    foreach($layouts_cfg as $layouttype => $layoutpath)
	    {
	    	$layout = array();
			$layout["JSON"] = json_decode(file_get_contents(Site::Configuration()["directorys"]["user-layouts"]. "/"  . $layoutpath));
			$layout["abs_path"] = Site::Configuration()["directorys"]["user-layouts"]. "/"  . $layoutpath;
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

	///Requests the theme that should be used for the request.
	function SelectTheme($RequestType)
	{
                //If a module wants to force override a theme, it can from this call.
                $moduleResults = Site::$moduleManager->HookEvent("Bread.SelectTheme",NULL);
                if($moduleResults)
                {
                    return $this->SetTheme($moduleResults[0]["theme"]);
                    
                }
                //Else we just shift through the list of themes.
		foreach ($this->themes as $usage => $theme)
		{
                    if($usage == $RequestType or $usage == "all"){
                        return $this->SetTheme($theme);
                    }
		}
		return False;
	}
        
        function SetTheme($suggestedTheme)
        {
            $this->Theme["data"] = $suggestedTheme["module"];
            require_once(Site::Configuration()["directorys"]["user-themes"]. "/" . $this->Theme["data"]["entryfile"]);
            $this->Theme["class"] = new $this->Theme["data"]["entryclass"](Site::$moduleManager,$this->Theme["data"]["name"]);
            if(isset($this->Theme["data"]["css"])){
                    $this->cssFiles = array_merge($this->cssFiles,$this->Theme["data"]["css"]); //Add some css files.
            }
            Site::$moduleManager->RegisterSelectedTheme();
            return True;
        }

	///Requests the layout that should be used for the page request.
	function SelectLayout($RequestType)
	{
		foreach ($this->layouts as $layoutType => $layoutJSON)
		{
			if($layoutType == $RequestType){
				$this->Theme["layout"] = $layoutJSON;
				return True;
			}
		}
		if(isset($this->layouts["master"]))
		{
			$this->Theme["layout"] = $this->layouts["master"];
			return True;
		}
		return False;
	}

	//Returns nothing but reads from layout and does all the calling to modules
	//and adds CSS Files. Yes, this is the biggy.
	
	function BuildCSS()
	{
		foreach($this->cssFiles as $cssfilepath)
		{
			$isRemote = (mb_substr($cssfilepath, 0, 4) == "http");
			if(!$isRemote){
				$cssfilepath = Site::Configuration()["directorys"]["user-layouts"]. "/"  . $cssfilepath;
			}
			else
			{
				Site::$Logger->writeError("Couldn't resolve CSS to be local or external (" . $cssfilepath .")", 5, false);
			}
			$this->CSSLines .= "<link rel='stylesheet' type='text/css' href='" . $cssfilepath . "'>\n";
		}
	}
	
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
			Site::AddToBodyCode("<div id='" . $Layout->id ."'>"); //Start div tag.
			$event = "DrawModule"; //Standard module draw function.
			$arguments = array();
			if(isset($Layout->event))
				$event = $Layout->event;
			if(isset($Layout->arguments))
				$arguments = $Layout->arguments;
			if(isset($Layout->module))
				$moduleReturn = Site::$moduleManager->HookSpecifedModuleEvent($event,$Layout->module,$arguments); //Module returns html data hopefully.
			else
				$moduleReturn = Site::$moduleManager->HookEvent($event,$arguments)[0];
			
			if($moduleReturn != False){
			    //Return value.
			    Site::AddToBodyCode($moduleReturn);
			}
			Site::AddToBodyCode("</div>\n");
			return;
		}
		
		//Draw enclosed elements.
		if(!isset($Layout["JSON"]->elements))//No enclosed elements.
			return;
		$elements = $Layout["JSON"]->elements;
		foreach($elements as $element)
			$this->ReadElementsFromLayout($element);
	}
}
?>
