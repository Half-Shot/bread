<?php
namespace Bread\Modules;
class ExampleModule extends Module
{
    private $manager;
	private $name;
	function __construct($manager,$name)
	{
		$this->manager = $manager;
		$this->name = $name;
	}

	function RegisterEvents()
	{
			$this->manager->RegisterEvent($this->name,"Bread.DrawModule","ReturnFirstArgument");
	}
    
    #Do not attempt to save directly to HTML, instead return the html code.
	function ReturnFirstArgument($arguments)
	{
	    return "<p>" . $arguments->gabesize ."</p>";
    }
}
