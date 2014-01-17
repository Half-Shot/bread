<?php
class VanillaBreadTheme extends Bread\Modules\Module
{
	
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}

	function RegisterEvents()
	{
			$this->manager->RegisterEvent($this->name,"Theme.Load","Load"); //For each event you want to allow, specify: Name of theme, EventName and function name
			$this->manager->RegisterEvent($this->name,"Theme.HeaderInfo","HeaderInfomation");
			$this->manager->RegisterEvent($this->name,"Theme.DrawSystemMenu","DrawSystemMenu");
			$this->manager->RegisterEvent($this->name,"Theme.DrawNavbar","DrawNavbar");
			$this->manager->RegisterEvent($this->name,"Theme.DrawFooter","DrawFooter");
			$this->manager->RegisterEvent($this->name,"Theme.Unload","Unload");
	}
    
	function Load()
	{
		$HTMLCode = "<p>VanillaBread Load Function</p>";
		return $HTMLCode;
	}

	function HeaderInfomation()
	{
		$HTMLCode = "<!-- Header Stuff from Vanilla -->\n";
		return $HTMLCode;
	}

	function DrawSystemMenu($args)
	{
		$HTMLCode = "<p>VanillaBread DrawSystemMenu Function</p>";
		return $HTMLCode;
	}

	function DrawNavbar()
	{
		$HTMLCode = "";
		return $HTMLCode;
	}

	function DrawFooter()
	{
		$HTMLCode = "<p>VanillaBread DrawFooter Function</p>";
		return $HTMLCode;
	}

	function Unload()
	{
		$HTMLCode = "<p>VanillaBread Unload Function</p>";
		return $HTMLCode;
	}

}
?>
