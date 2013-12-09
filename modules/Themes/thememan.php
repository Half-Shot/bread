<?php
namespace Bread\Themes;
use Bread\Site as Site;
class ThemeManager
{
	private $themes;
	private $layouts;
	private $configuration = "";
	public $SelectedThemeData;
	public $SelectedTheme;
	public $SelectedLayout;
	public $CSSLines = "";
	function __construct()
	{
		$themes = array();
		$layouts = array();
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
	
	function LoadLayouts()
	{
	    $layouts = $this->configuration["settings"]["layouts"];
	    foreach($layouts as $layouttype => $layoutpath)
	    {
		$JSON = json_decode(file_get_contents(Site::Configuration()["directorys"]["user-layouts"]. "/"  . $layoutpath));
	        $this->layouts[$layouttype] = $JSON;
	    }
	}

	function RegisterTheme($themeconfig,$usage)
	{
		//Parse config file
		//TODO: Sort out all that jazz about module permissions which is used in themes.
		if(!file_exists($themeconfig))
			Site::$Logger->writeError('Cannot register theme. Theme config not found',1,True);

		$tmp = file_get_contents($themeconfig);
		$JSON = json_decode($tmp,true);

		if($this->configuration == NULL)
			Site::$Logger->writeError('Cannot load theme. Theme data has invalid JSON.',1,True);

		if($JSON["theme"] == NULL || $JSON["module"] == NULL)
			Site::$Logger->writeError('Cannot load theme. Theme data has missing required properties.',1,True);

		foreach($usage as $uses)
			$this->themes[$uses] = $JSON;
	}

	///Requests the theme that should be used for the request.
	function SelectTheme($RequestType)
	{
		foreach ($this->themes as $usage => $theme)
		{
				if($usage == $RequestType or $usage == "all"){
					$this->SelectedThemeData = $theme;
					require_once(Site::Configuration()["directorys"]["user-themes"]. "/" . $theme["module"]["entryfile"]);
					$this->SelectedTheme = new $theme["module"]["entryclass"]();
					return True;
				}
		}
		return False;
	}

	///Requests the layout that should be used for the request.
	function SelectLayout($RequestType)
	{

		foreach ($this->layouts as $layoutType => $layoutXML)
		{
			if($layoutType == $RequestType){
				$this->SelectedLayout = $layoutXML;
				return True;
			}
		}
		if(isset($this->layouts["master"]))
		{
			$this->SelectedLayout = $this->layouts["master"];
			return True;
		}
		return False;
	}

	//Returns array with [div tag] => module
	function ReadElementsFromLayout($Layout)
	{
		$IsRoot = ($Layout == $this->SelectedLayout);

		if($IsRoot)
		{
			if(!isset($Layout->elements))//No enclosed elements.
				Site::$Logger->writeError("Layout contains no elements, page cannot be built.",1,True);
			if(!isset($Layout->css))
				Site::$Logger->writeError("Layout contains no css files, page cannot be built.",1,True);
		}
		if(!isset($Layout->elements))//No enclosed elements.
			return;

		foreach($Layout->elements as $element)
		{
			$this->ReadElementsFromLayout($element);
		}
		//Do some drawing
		if(!$IsRoot)
			return;

		foreach($Layout->css as $cssfilepath)
		{
			$this->CSSLines .= "<link rel='stylesheet' type='text/css' href='" . Site::Configuration()["directorys"]["user-layouts"]. "/"  . $cssfilepath . "'>";
		}
	}
}
?>
