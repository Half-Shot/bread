<?php
namespace Bread\Modules;
use Bread\Site as Site;
class ExampleModule extends Module
{
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}

	function RegisterEvents()
	{
            $this->manager->RegisterHook($this->name,"Bread.DrawModule","ReturnFirstArgument");

	}
    
	function ReturnFirstArgument($arguments)
	{
	    return "<p>" . $arguments->gabesize ."</p>";
        }

}
