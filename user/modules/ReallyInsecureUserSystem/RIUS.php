<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Structures\BreadUser as BreadUser;
/**
 * A really insecure user system.
 * This basically checks the IP and see's if it is local, if it is not local then the user
 * is denied access. DO NOT USE THIS IN A PRODUCTION SITE, ITS FOR TESTING.
 */
class ReallyInsecureUserSystem extends Module
{
        private $currentUser;
        private $settings;
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}

	function RegisterEvents()
	{
            $this->manager->RegisterHook($this->name,"Bread.Security.GetCurrentUser","ReturnUser");
            $this->manager->RegisterHook($this->name,"Bread.ProcessRequest","Setup");
	}
    
        function DoLogin()
        {
            Site::$Logger->writeMessage("[RIUS] User logged in, but there is no login code in this module.");
            Site::Redirect("index.php");
        }
        
        function Setup()
        {
            if(Site::getRequest()->requestType == "login"){
                $this->DoLogin();
            }
            //Settings
            $rootSettings = Site::$settingsManager->FindModuleDir("RIUS");
            Site::$settingsManager->CreateSettingsFiles($rootSettings . "settings.json", new RIUSSettings());
            $this->settings = Site::$settingsManager->RetriveSettings($rootSettings . "settings.json");
            $ip = $_SERVER['REMOTE_ADDR'];
            if(!$this->clientInSameSubnet() && $this->settings->limitToLocalConnections)
            {
                $this->currentUser = false;
                Site::$Logger->writeMessage("[RIUS] As requested, user was denied because the address wasn't local.");
                return;
            }
            //NOT WORKING!!!!!
            if($this->settings->limitToHTTPS)
            {
                if(isset($_SERVER["HTTPS"])){
                 if($_SERVER["HTTPS"] != "on"){
                    $this->currentUser = false;
                    Site::$Logger->writeMessage("[RIUS] As requested, user was denied because the connection isn't HTTPS.");
                    return;
                 }
                }
                else
                {
                    Site::$Logger->writeMessage("[RIUS] As requested, user was denied because the connection isn't HTTPS.");
                    return;
                }
            }
            
            $this->currentUser = new BreadUser;
            $this->currentUser->username = $ip;
            $this->currentUser->sessionVars["Authenticator"] = "Rius";
            $this->currentUser->rights = $this->settings->rights;
        }
        
	function ReturnUser($arguments)
	{
	    return $this->currentUser;
        }

        
/**
 * Check if a client IP is in our Server subnet
 * Taken from http://php.net/manual/en/function.ip2long.php#94290
 * @param string $client_ip
 * @param string $server_ip
 * @return boolean
 */
function clientInSameSubnet($client_ip=false) {
    if (!$client_ip)
        $client_ip = $_SERVER['REMOTE_ADDR'];
    
    $reserved_ips = array( // not an exhaustive list
    '3232235520' => 3232301055, /* 192.168.0.0 - 192.168.255.255 */
    '2130706432' => 2147483647, /*   127.0.0.0 - 127.255.255.255 */
    );

    $ip_long = sprintf('%u', ip2long($client_ip));

    foreach ($reserved_ips as $ip_start => $ip_end)
    {
        if (($ip_long >= $ip_start) && ($ip_long <= $ip_end))
        {
            return TRUE;
        }
    }
    return FALSE;
}

}

class RIUSSettings{
    
    public $limitToHTTPS = true;
    public $limitToLocalConnections = true;
    public $allowedIPs = array();
    public $rights = array();
}