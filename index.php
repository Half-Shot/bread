<?php 
/**
 * The entry PHP, a simple document which has a few variables and calls all the
 * startup functions of PHP. Hopefully will be deprecaited/lessened in the future.
 * The name 'index.php' is completly changeable and your not required to use it at all.
 * @todo Move some or all of this document directly to site and make it autoload.
 */
use Bread\Site as Site;
//apd_set_pprof_trace();
$BREAD_CONFIGURL = "settings/config.json";
$BREAD_SITEMODFILE = "modules/site.php";
include($BREAD_SITEMODFILE);
//Early stage loading.
Site::ShowDebug(true);
Site::LoadConfig($BREAD_CONFIGURL,__DIR__);
Site::CheckBans();
Site::SetupLogging();
//Core loading.
Site::LoadCoreModules(Site::ResolvePath("%system-modules"));
//External Loading
Site::IsAjax();
Site::SetupManagers();//Last step to have a fully set up site.
//Main Processing
Site::DigestRequest();
Site::ProcessRequest();//Draw the site. We are done here.
Site::$Logger->writeMessage("Memory Used: " . (memory_get_usage(False) / 1024) . "kb");
Site::Cleanup();