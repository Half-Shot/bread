<?php
namespace Bread\Themes;
use Bread\Site as Site;
class ThemeManager
{
	private $themes;
	private $layouts;
	private $configuration = "";
	public $SelectedTheme;
	public $SelectedLayout;
	public $CSS = "";
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
			throw new \Exception('Cannot load themes. Manager Settings file not found');
		}
		$tmp = file_get_contents($filepath);
		$this->configuration = json_decode($tmp,true);
		if($this->configuration == NULL)
			throw new \Exception('Cannot load themes. Manager Settings has invalid JSON.');

		foreach ($this->configuration["themes"] as $theme)
		{
			$this->RegisterTheme(Site::Configuration()["directorys"]["user-themes"]. "/" . $theme["config-path"]);
		}
	}
	
	function LoadLayouts()
	{
	    $layouts = $this->configuration["settings"]["layouts"];
	    foreach($layouts as $layouttype => $layoutpath)
	    {
		$JSON = json_decode(file_get_contents(Site::Configuration()["directorys"]["user-layouts"]. "/"  . $layoutpath));
		echo "<pre>";
		print_r($JSON);
		echo "</pre>";
	        $this->layouts[$layouttype] = $JSON;
	    }
	}

	function RegisterTheme($themeconfig)
	{
		//Parse config file
		//TODO: Sort out all that jazz about module permissions which is used in themes.
		if(!file_exists($themeconfig))
		{
			throw new \Exception('Cannot register theme. Theme config not found');
		}
		$tmp = file_get_contents($themeconfig);
		$JSON = json_decode($tmp,true);
		if($this->configuration == NULL)
			throw new \Exception('Cannot load theme. Theme data has invalid JSON.');

		if($JSON["theme"] == NULL)
		{
			throw new \Exception('Cannot load theme. Theme data has missing required properties.');
		}
		$this->themes[] = $JSON["theme"];
	}

	///Requests the theme that should be used for the request.
	function SelectTheme($RequestType)
	{
		foreach ($this->configuration["themes"] as $theme)
		{
			foreach ($theme["use-for"] as $usage)
			{
				if($usage == $RequestType or $usage == "all"){
					$this->SelectedTheme = $theme;
					return True;
				}
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
		foreach($Layout->element as $element)
		{
			$this->ReadElementsFromLayout($element);
		}
		//Do some drawing
		if($Layout == $this->SelectedLayout) //Is not root.
			return;	
		//Parse this element
		//Build CSS
		$CSS = "";
		//Build a CSS listing
		$CSS .= "\n";
		$CSS .= "#" . $Layout->attributes()["name"];
		$CSS .= "{";

		if(isset($Layout->dimensions))
		{
			$CSS .= "width:" . $Layout->dimensions->width;
			if(substr($Layout->dimensions->width,-1) != "%") //Percentages
				$CSS .= "px";

			$CSS .= ';';
			$CSS .= "height:" . $Layout->dimensions->height;
			if(substr($Layout->dimensions->height,-1) != "%") //Percentages
				$CSS .= "px";
			$CSS .= ';';
		}

		if(isset($Layout->dimensions))
		{
			echo "Hi";
		}

		$CSS .= "}";
		echo $CSS . "<br>";
	}
}
?>
