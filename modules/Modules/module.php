<?php
namespace Bread\Modules;
class Module
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

	}

    #Don't actually use this function, this is just a template.
	function FireEvent($eventData)
	{

	}
}
?>
