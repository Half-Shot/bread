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
    
    #Do not attempt to save directly to HTML, instead return the html code.
	function ReturnFirstArgument($arguments)
	{
	    return "<p>" . $arguments->gabesize ."</p>";
    }
}
