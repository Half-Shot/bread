<?php
class VanillaBreadTheme extends Bread\Themes\Theme
{

	function Load()
	{
		$HTMLCode = "<p>VanillaBread Load Function</p>";
		return $HTMLCode;
	}

	function HeaderInfomation()
	{
		$HTMLCode = "<!-- Header Stuff from Vanilla -->";
		return $HTMLCode;
	}

	function DrawSystemMenu()
	{
		$HTMLCode = "<p>VanillaBread DrawSystemMenu Function</p>";
		return $HTMLCode;
	}

	function DrawModule()
	{
		$HTMLCode = "<p>VanillaBread DrawModule Function</p>";
		return $HTMLCode;
	}

	function DrawNavbar()
	{
		$HTMLCode = "<p>VanillaBread DrawNavbar Function</p>";
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
