<?php 
$BREAD_CONFIGURL = "settings/config.json";
$BREAD_SITEMODFILE = "modules/site.php";
include($BREAD_SITEMODFILE);
use Bread\Site as Site;
Site::ShowDebug(true);
Site::LoadConfig($BREAD_CONFIGURL);
Site::CheckBans();
Site::LoadClasses(Site::Configuration()["directorys"]["system-modules"]);
Site::CheckClasses();
Site::SetupManagers();//Last step to have a fully set up site.
Site::DrawSite();//Draw the site. We are done here.
echo "Still Working";
?>
