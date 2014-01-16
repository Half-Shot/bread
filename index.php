<?php 
use Bread\Site as Site;
$BREAD_CONFIGURL = "settings/config.json";
$BREAD_SITEMODFILE = "modules/site.php";
include($BREAD_SITEMODFILE);
Site::$TimeStarted = time();
Site::ShowDebug(true);
Site::LoadConfig($BREAD_CONFIGURL);
Site::CheckBans();
Site::SetupLogging();
Site::LoadClasses(Site::Configuration()["directorys"]["system-modules"]);
Site::CheckClasses();
Site::SetupManagers();//Last step to have a fully set up site.
Site::ProcessRequest(Site::ExampleRequest());//Draw the site. We are done here.
Site::$Logger->writeMessage("Memory Used: " . (memory_get_usage(False) / 1024) . "kb");
Site::Cleanup();
?>
