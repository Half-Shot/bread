<?php
namespace Modules;
class ModuleManager
{
	private $modules;
	function __construct()
	{
		$modules = array();
	}

	function RegisterModule($ModuleName,$jsonfile)
	{
		if(in_array($ModuleName,$modules))
		{
			throw new Exception('Cannot register module. Module already exists');
		}
		
		//Parse config file
		if(!file_exists($jsonfile))
		{
			throw new Exception('Cannot register module. Module config not found');
		}
		$tmp = file_get_contents($jsonfile);
		//Check through 
		
	}

}
?>
