<?php
namespace Bread\Modules;
use Bread\Site as Site; //Site Functions 
use Bread\Utilitys as Util; //Utilitys
class ExampleModule extends Module
{
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}
        //LEGACY FUNCTION
	function RegisterEvents()
	{
            //$this->manager->RegisterHook($this->name,"Bread.HelloWorld","HelloWorld");
	}
        
        function HelloWorld($arguments)
        {
            Site::$settingsManager->RetriveSettings("examplemodule#test");
            return "<p><b>Hello World</b></p>";
        }

}
