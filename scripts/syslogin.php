<?
include("../core/config.php");
include("../core/functions.php");
if(!$_SESSION){
session_start();
}
//Make sure to comment this out when done.
$_SESSION['loggedin'] = false;
$_SESSION['username'] = ""; //Reset Session
if(!isset($_REQUEST["user"]) || !isset($_REQUEST["pass"]) )
{
	echo "Malformed";
	return;
}

$username = $_REQUEST["user"];
$pass = $_REQUEST["pass"];
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
	echo "Fail";
}
else
{
	if($pass == $result[0]["pass"])
	{
		echo "OK";
		$_SESSION['loggedin'] = true;
		$_SESSION['username'] = $username;
	}
	else
	{
		echo "Fail";
	}
	 
}
?>
