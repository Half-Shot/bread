<?php
use Bread\Site as Site;
namespace Bread\Modules;
class ModuleManager
{
	private $modules;
	private $configuration;
	private $events;
	function __construct()
	{
		$modules = array();
	}

	function LoadSettings($filepath)
	{
		if(!file_exists($filepath))
		{
			throw new Site::$Logger->writeError('Cannot load themes. Manager Settings file not found');
		}
		$tmp = file_get_contents($filepath);
		$this->configuration = json_decode($tmp,true);
	}

	function LoadModulesFromConfig($filepath)
	{
		if(!file_exists($filepath))
		{
			throw new Site::$Logger->writeError('Cannot load themes. Manager Settings file not found');
		}
		
	}

	function RegisterModule($ModuleName,$jsonfile)
	{
		if(in_array($ModuleName,$modules))
			throw new Site::$Logger->writeError('Cannot register module. Module already exists');
		
		//Parse config file
		if(!file_exists($jsonfile))
			throw new Site::$Logger->writeError('Cannot register module. Module config not found');

		$tmp = file_get_contents($jsonfile);
		//Check through 
	}
}
?>
