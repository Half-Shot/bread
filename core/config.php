<?
//Config
$rooturl = "http://localhost/blogplatform/";
$webname = "Bread";
$plat_version = 0.02;
session_start();
$homepage_php = "homefeed";
$currenturl = strlen($_SERVER['QUERY_STRING']) ? basename($_SERVER['PHP_SELF'])."?".$_SERVER['QUERY_STRING'] : basename($_SERVER['PHP_SELF']);
$databasefile = "tables.db";
?>
