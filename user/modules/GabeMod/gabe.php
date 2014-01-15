<?php
namespace Bread\Modules;
class GabeMod extends Module
{
    private $manager;
	function __construct($manager)
	{
		$this->manager = $manager;
	}

	function RegisterEvents()
	{
	    
	}
    
    #Do not attempt to save directly to HTML, instead return the html code.
	function PrintMyGaben($eventData)
	{
	    return "<iframe frameBorder='0' style='width: 100%; height: 100%;' src='http://gaben.tv/'></iframe>";
	}
	#Events must always return a value, a
	function MakeEpisode3Appear($eventData)
	{
	    return False;
	}
	
	function DrawModule($eventData)
	{
	    return $this->PrintMyGaben($eventData);
	}
}
