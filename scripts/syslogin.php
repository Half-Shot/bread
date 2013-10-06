<?
include("../core/config.php");
include("../core/functions.php");
if(!$_SESSION){
session_start();
}
//Make sure to comment this out when done.
$_SESSION['loggedin'] = false;
$_SESSION['username'] = ""; //Reset Session
if(!isset($_REQUEST["user"]) || !isset($_REQUEST["pass"]) || !isset($_REQUEST["page"]) )
{
	echo "Malformed request.";
	return;
}

$username = $_REQUEST["user"];
$pass = $_REQUEST["pass"];
$res_url = $_REQUEST["page"];
//Do SQLite Connection
$db = new PDO("sqlite:../" . $databasefile);

if (!$db) die ("Could not find database file :(");

//Check against values
$statement = "SELECT * FROM user_auth WHERE user = " . $db->quote($username);

$query = $db->query($statement);
#if (!$query) die ($db->errorInfo()[2]); 
$result = $query->fetchAll(0);
if(!$result)
{
	$res_url = AppendParameter($res_url,"login","false");
	$res_url = AppendParameter($res_url,"lreason","user");
}
else
{
	if($pass == $result[0]["pass"])
	{
		$res_url = AppendParameter($res_url,"login","true");
		$_SESSION['username'] = $username;
		$_SESSION['loggedin'] = true;
	}
	else
	{
		$res_url = AppendParameter($res_url,"login","false");
		$res_url = AppendParameter($res_url,"lreason","pass");
	}
	 
}
//Clean logout
$res_url = StripUrlParameter($res_url,"logout"); print_r($res_url); echo "\n";

$header = "../" . $res_url;
$header_str = "Location: " . $header;
header($header_str);
?>

<h2> Logging in...</h2>
