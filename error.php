<?

if(!isset($error_dump))
{
	$error_dump = array();
	$error_dump[1] = "WHA";
	$error_dump[0] = "Not sure how you got here, there isn't a problem...";
}
?>

<h1> Error Code  <? echo $error_dump[1]; ?><br><small>  <? echo $error_dump[0]; ?></small></h1>

<a href="<? echo $rooturl; ?>" class="button">Go Home</a>

