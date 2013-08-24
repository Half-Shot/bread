<?
//Get needed modules
if(isset($current_page)){
	include("pagebuilder.php");
}
elseif($hasoverride)
{
	if(file_exists($overridemodule . ".php"))
		include($overridemodule . ".php");
	else{
		$error_dump = ThrowError("This page dosn't exist, the module that is ". $overridemodule,002);
		$hasoverride = true;
		$haspage = false;
		$curpag_id = -1;
		$overridemodule = "error";
		include($overridemodule . ".php");
	}
}
else
{
	$error_dump = ThrowError("Your trying to build content manually, its a no-show". $overridemodule,003);
	$hasoverride = true;
	$haspage = false;
	$curpag_id = -1;
	$overridemodule = "error";
	include($overridemodule . ".php");
}	
?>
