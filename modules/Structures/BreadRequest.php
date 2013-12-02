<?php
namespace Bread\Structures;
class BreadRequestData
{
	public $command; #BreadRequestCommand
	public $module;
	public $sourcename; # E.g PageID, MenuID
	
	function __construct($Command)
	{
        $this->command = $Command;
	}
	
#	public function __construct(BreadRequestCommand $Command,$Module)
#	{
#	    self::$command = $Command;
#	    self::$module = $Module;
#	}
#	
#	public function __construct(BreadRequestCommand $Command,$Module,$SourceName)
#	{
#	    self::$command = $Command;
#	    self::$module = $Module;
#	    self::$sourcename = $SourceName;
#	}
}

class BreadRequestCommand
{
	static $SystemMenu = 0;
	static $LoginPage  = 1;
	static $RawPage    = 2;
	static $Module     = 3;
}
?>
