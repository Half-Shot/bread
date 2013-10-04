<?
//Global
$uw_posts = array();

//Twitter
$consumerKey = "x4Ek0fQ4qvLOlAZopXhgQ";
$consumerSecret = "gEXSuR5IJhHDIz5UNhwDOIYYu818kWvE8h9IRL079Q";
$accessToken = "366675043-eejAmSUANoim3os3p9rBdMI3OIMh1uSFqVTCcM2R";
$accessTokenSecret = "2M4c1ad47EVvxpcfuJY8Zqk1RrON53h6rSWtGDLhs";
include("Twitter-PHP/twitter.class.php");
$twitter = new Twitter($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
$channel = $twitter->load(Twitter::ME | Twitter::JSON,10);
foreach($channel as $status)
{
	$uw_posts[strtotime($status->created_at)] = $status->text . ":!:Twitter";
}
//Youtube
$youtube = json_decode(file_get_contents("http://gdata.youtube.com/feeds/api/users/TheHalfShot/uploads?alt=json&count=5"),true);
$youtube = $youtube["feed"]["entry"];
foreach($youtube as $ytentry)
{
	$uw_posts[strtotime($ytentry["updated"]['$t'])] = $ytentry["title"]['$t'] . "[Image]" . $ytentry['media$group']['media$thumbnail'][0]['url'] . ":!:Youtube";
}
?>
<div class="row">
 <div class="small-2 columns">
<? foreach($uw_posts as $uw_date => $uw_text){
	$uw_data = explode(':!:', $uw_text);
?>
   <div class="panel"><? echo $uw_date ?></div>
<?} ?>
   </div>
   <div class="small-10 columns">
<? foreach($uw_posts as $uw_date => $uw_text){
	$uw_data = explode(':!:', $uw_text);
?>
    <div class="panel" style="background-color:<? if($uw_data[1] == 'Twitter'){echo 'lightblue';}elseif($uw_data[1] == 'Youtube'){echo 'lightred';}elseif($uw_data[1] == 'Blog'){echo 'lightgreen';} ?>;">
      <? $datasplit = explode("[Image]",$uw_data[0]); ?>
         <h6><?echo $datasplit[0];?></h4>
	 <?if(count($datasplit) > 1){?>
	 <img width="200px" height="150px" src="<? echo $datasplit[1] ?>"/>
      <? }?>
    </div>
<?} ?>
 </div>
</div>
