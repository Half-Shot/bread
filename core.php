<?php
include("core/functions.php");
include("core/config.php");

//Variables
$username = "";


if(isset($_GET["logout"]))
{
	$_SESSION['loggedin'] = false;
	$_SESSION['username'] = "";
	session_write_close();
	session_destroy();
	
	//Strip logout=true off current url.
	$currenturl = StripUrlParameter($currenturl,"logout");
?>
	<div data-alert class="alert-box success">
	  Your have logged out.
	  <a href="#" class="close">&times;</a>
	</div>
<?
}

//Check For Existing Login
if(isset($_SESSION["loggedin"]))
{
	if($_SESSION["loggedin"] == true)
	{
		$username = $_SESSION["username"];
	}
}
//Get all avaliable pages.
$dir = opendir('pages');
$pages = array();
while (false !== ($entry = readdir($dir))) {
    if(substr($entry,strlen($entry) - 5) == ".json"){
	$newdata = json_decode(file_get_contents("pages/" . $entry),true);

	if(!strpos($newdata["url"],".md"))
	{	//PHP
		$newdata["date"] = filemtime("modules/" . $newdata["url"] . ".php");
		$newdata["ismodule"] = true;
	}
	else
	{
		//MD
		$newdata["date"] = filemtime("pages/" . $newdata["url"]);
		$newdata["ismodule"] = false;
	}

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
	if($curpag_id < 0)
	{
		$error_dump = ThrowError("Page ID is less than 0, Could be a module?");
		$hasoverride = true;
		$haspage = false;
		$curpag_id = -1;
		$overridemodule = "error";
	}	
	elseif($curpag_id < count($pages)){
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

	if(!isset($current_page["locked"]))
	{
		$current_page["locked"] = false;
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
  <title><? echo $webname; ?></title>
  <link rel="stylesheet" href="css/foundation.css">
  <link rel="stylesheet" href="css/bread.css">
  <link rel="stylesheet" href="fonts/foundation-icons.css">
  <script src="js/vendor/custom.modernizr.js"></script>
</head>
