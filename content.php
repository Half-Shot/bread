<?
//Get needed modules
if($haspage){
	include("modules/pagebuilder.php");
}
elseif($hasoverride)
{
	if(strpos($overridemodule,".."))
	{
	$error_dump = ThrowError("You are trying to open files outside the modules directory. Smells like hacking to me.",004);
	$hasoverride = true;
	$haspage = false;
	$curpag_id = -1;
	$overridemodule = "error";
	}
	if(file_exists("modules/" . $overridemodule . ".php"))
		include("modules/" . $overridemodule . ".php");
	else{
		$error_dump = ThrowError("This page dosn't exist, the module that is ". $overridemodule,002);
		$hasoverride = true;
		$haspage = false;
		$curpag_id = -1;
		$overridemodule = "error";
		include("modules/" . $overridemodule . ".php");
	}
}
else
{
	$error_dump = ThrowError("Your trying to build content manually, its a no-show". $overridemodule,003);
	$hasoverride = true;
	$haspage = false;
	$curpag_id = -1;
	$overridemodule = "error";
	include("modules/" . $overridemodule . ".php");
}	
?>
