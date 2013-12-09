<?php
namespace Bread\Themes;
use Bread\Site as Site; 
use Bread\Structures\BreadRequestData as BreadRequestData;
abstract class Theme
{
	function __construct()
	{

	}

	abstract function Load();
	abstract function HeaderInfomation();
	abstract function DrawSystemMenu();
	abstract function DrawModule();
	abstract function DrawNavbar();
	abstract function DrawFooter();
	abstract function Unload();

}
