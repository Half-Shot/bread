<?php
namespace Bread\Structures;
class BreadRequestData
{
	public $command; #BreadRequestCommand
	public $Module;
	public $SourceName; # E.g PageID, MenuID
}

class BreadRequestCommand
{
	const SystemMenu = 0;
	const LoginPage = 1;
	const RawPage = 2;
	const Module = 3;
}
?>
