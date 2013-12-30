<?php
namespace Bread\Themes;
use Bread\Site as Site;
class ThemeManager
{
	#Collection
	private $themes;
	private $layouts;
	private $configuration = "";

	#Selected Items
	public $Theme;
	public $Layout;
	public $CSSLines = "";

	function __construct()
	{
		$themes = array();
		$layouts = array();
		$Theme = array();
		$Layout = array();
		$configuration = "";
	}

	public static function Configuration()
	{
		return $this->configuration;
	}

	function LoadThemeManagerSettings($filepath)
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
	    $layouts_cfg = $this->configuration["settings"]["layouts"];
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

		if($theme["JSON"]["theme"] == NULL || $theme["JSON"]["module"] == NULL)
			Site::$Logger->writeError('Cannot load theme. Theme data has missing required properties.',1,True);

		foreach($usage as $uses)
			$this->themes[$uses] = $theme["JSON"];
	}

	///Requests the theme that should be used for the request.
	function SelectTheme($RequestType)
	{
		foreach ($this->themes as $usage => $theme)
		{
				if($usage == $RequestType or $usage == "all"){
					$this->Theme["data"] = $theme;
					require_once(Site::Configuration()["directorys"]["user-themes"]. "/" . $theme["module"]["entryfile"]);
					$this->Theme["class"] = new $theme["module"]["entryclass"]();
					return True;
				}
		}
		return False;
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
	function ReadElementsFromLayout($Layout)
	{
		$IsRoot = ($Layout == $this->Theme["layout"]);
		
		if($IsRoot)
		{
			if(!isset($Layout["JSON"]->elements))//No enclosed elements.
				Site::$Logger->writeError("Layout contains no elements, page cannot be built.",1,True);
			if(!isset($Layout["JSON"]->css))
				Site::$Logger->writeError("Layout contains no css files, page cannot be built.",1,True);
			$css_files = $Layout["JSON"]->css;
			foreach($css_files as $cssfilepath)
			{
				$this->CSSLines .= "<link rel='stylesheet' type='text/css' href='" . Site::Configuration()["directorys"]["user-layouts"]. "/"  . $cssfilepath . "'>\n";
			}
		}
		else
		{
			Site::$BodyCode .= "<div id='" . $Layout->id ."'>Drawing element from layout: " .$Layout->human . "</div>\n";
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
