<?php
namespace Bread\Modules;
use Bread\Site as Site;
class BreadAdminTools extends Module
{
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}

	function RegisterEvents()
	{
            $this->manager->RegisterHook($this->name,"Bread.DrawModule","ReturnFirstArgument");
            $this->manager->RegisterHook($this->name,"Bread.GetNavbarIndex","BuildAdminLinks");
            $this->manager->RegisterHook($this->name,"Bread.ProcessRequest","Setup",array("Bread.ProcessRequest"=>"BreadUserSystem"));

	}
    
	function ReturnFirstArgument($arguments)
	{
	    return "<p>Empty Admin Panel</p>";
        }
        
        function Setup()
        {
            if(!$this->manager->FireEvent("Bread.Security.GetCurrentUser"))
                return false;
        }

}
