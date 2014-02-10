<?php 
/**
 * The entry PHP, a simple document which has a few variables and calls all the
 * startup functions of PHP. Hopefully will be deprecaited/lessened in the future.
 * The name 'index.php' is completly changeable and your not required to use it at all.
 * @todo Move some or all of this document directly to site and make it autoload.
 */
use Bread\Site as Site;
$BREAD_CONFIGURL = "settings/config.json";
$BREAD_SITEMODFILE = "modules/site.php";
include($BREAD_SITEMODFILE);
Site::ShowDebug(true);
Site::LoadConfig($BREAD_CONFIGURL);
Site::CheckBans();
Site::SetupLogging();
Site::LoadCoreModules(Site::ResolvePath("%system-modules"));
Site::DigestRequest();
Site::CheckCoreModules();
Site::SetupManagers();//Last step to have a fully set up site.
Site::ProcessRequest();//Draw the site. We are done here.
Site::$Logger->writeMessage("Memory Used: " . (memory_get_usage(False) / 1024) . "kb");
Site::Cleanup();
?>
