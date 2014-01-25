<?php
namespace Bread\Modules;
class ExampleModule extends Module
{
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}

	function RegisterEvents()
	{
			$this->manager->RegisterEvent($this->name,"Bread.DrawModule","ReturnFirstArgument");
	}
    
	function ReturnFirstArgument($arguments)
	{
	    return "<p>" . $arguments->gabesize ."</p>";
    }
}
