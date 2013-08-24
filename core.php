<?php
function isCategory($page,$category)
{
	foreach($page["categorys"] as $key => $value)
	{
		if($value == $category){
			return true;
		}
	}
	return false;
}

function ThrowError($reason,$errorcode = 42)
{
	$dump = array();
	$dump[]= $reason;
	$dump[] = $errorcode;
	return $dump;
}

//Config
$rooturl = "http://localhost/blogplatform/";
$webname = "The Blog!";
$plat_version = 0.01;
session_start();
$username = "";
$homepage_php = "homefeed";

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
	$username = "TheName";
}
else {
	$username = "";
}

//Get all avaliable pages.
$dir = opendir('pages');
$pages = array();
while (false !== ($entry = readdir($dir))) {
    if(substr($entry,strlen($entry) - 5) == ".json"){
	$newdata = json_decode(file_get_contents("pages/" . $entry),true);
	if(isset($newdata["insertat"])){
		$pages[$newdata["insertat"]] = $newdata;
	}
	else{
		$pages[] = $newdata;
	}
	}
}

$haspage = isset($_GET["page"]);
$hasoverride = isset($_GET["module"]);

if(!$haspage && !$hasoverride)
{	
	//Home Page
	$curpag_id = -1;
	$hasoverride = true;
	$overridemodule = $homepage_php;	
}
else if($hasoverride)
{
	$curpag_id = -1;
	$overridemodule = $_GET["module"];
}
else if($haspage)
{
	$curpag_id = $_GET["page"];
	if($curpag_id < count($pages)){
	$current_page = $pages[$curpag_id];
	}
	else
	{
		$error_dump = ThrowError("This page doesn't exist. You can wait a few more days or read something else.");
		$hasoverride = true;
		$haspage = false;
		$curpag_id = -1;
		$overridemodule = "error";
	}
}
else
{
		$error_dump = ThrowError("I have NO IDEA how you got here, but i would turn back.");
		$hasoverride = true;
		$haspage = false;
		$curpag_id = -1;
		$overridemodule = "error";
}
?>
<!DOCTYPE html>
<!--[if IE 8]> 				 <html class="no-js lt-ie9" lang="en" > <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en" > <!--<![endif]-->

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <title><? echo $webname ?></title>
  <link rel="stylesheet" href="css/foundation.css">
  <script src="js/vendor/custom.modernizr.js"></script>
</head>
