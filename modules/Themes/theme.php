<?php
namespace Bread\Themes;
use Bread\Site as Site; 
use Bread\Structures\BreadRequestData as BreadRequestData;
abstract class Theme
{
	private $ThemeFile;
	private $ThemeDirectory;
	private $ContentDirectory;
	public $Configuration;
	public $UseFor;
	public $Request;
	public $Layout;
	function __construct($ThemeFile,$ThemeDirectory,$ContentDirectory,$Configuration,$UseFor,BreadRequestData $Request,$Layout)
	{
		$this->ThemeFile = $ThemeFile;
		$this->ThemeDirectory = $ThemeDirectory;
		$this->ContentDirectory = $ContentDirectory;
		$this->Configuration = $Configuration;
		$this->UseFor = $UseFor;
		$this->Request = $Request;
		$this->Layout = $Layout;
	}

	abstract function Load();
	abstract function DrawSystemMenu();
	abstract function DrawModule();
	abstract function DrawNavbar();
	abstract function DrawFooter();
	abstract function Unload();

}
