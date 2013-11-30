<?php
namespace Bread\Themes;
class ThemeManager
{
	private $themes;
	function __construct()
	{
		$themes = array();
	}

	function RegisterTheme($themeconfig)
	{
		//Parse config file
		if(!file_exists($themeconfig))
		{
			throw new \Exception('Cannot register theme. Theme config not found');
		}
		$tmp = file_get_contents($themeconfig);
		$JSON = json_decode($tmp,true);

		if($JSON["theme"] == NULL or $JSON["module"] == NULL)
		{
			echo "<br>Failed to load theme. Config file missing required structures.";
			return false;
		}

		$Theme = $JSON["theme"];
		$Module = $JSON["module"];

		echo("<br>New theme loaded:<br>");
		var_dump($Theme);
	}
}
?>
