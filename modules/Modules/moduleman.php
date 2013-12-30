<?php
namespace Bread\Modules;
class ModuleManager
{
	private $modules;
	private $events;
	function __construct()
	{
		$modules = array();
	}

	function LoadSettings($filepath)
	{
		if(!file_exists($filepath))
		{
			throw new \Exception('Cannot load themes. Manager Settings file not found');
		}
		$tmp = file_get_contents($filepath);
		$this->configuration = json_decode($tmp,true);
	}

	function RegisterModule($ModuleName,$jsonfile)
	{
		if(in_array($ModuleName,$modules))
			throw new Exception('Cannot register module. Module already exists');
		
		//Parse config file
		if(!file_exists($jsonfile))
			throw new Exception('Cannot register module. Module config not found');

		$tmp = file_get_contents($jsonfile);
		//Check through 
	}

}
?>
