<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Structures\BreadUser as BreadUser;
/**
 * The standard system for bread.
 * You will need to sort out the password salt yourself .
 */
class BreadUserSystem extends Module
{
        private $currentUser;
        private $settings;
        private $userDB;
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}

	function RegisterEvents()
	{
            $this->manager->RegisterEvent($this->name,"Bread.Security.GetCurrentUser","ReturnUser");
            $this->manager->RegisterEvent($this->name,"Bread.Security.GetPermission","HasPermission");
            $this->manager->RegisterEvent($this->name,"Bread.ProcessRequest","Setup");
            $this->manager->RegisterEvent($this->name,"Bread.Security.LoginUser","DoLogin");
	}
        
        function DoLogin()
        {
            Site::$Logger->writeMessage(var_export($_POST,true));
            if(!isset($_POST["uname"]) || !isset($_POST["pw"]))
                return 10;
            Site::$Logger->writeMessage("Got Right Info!");
            $username = $_POST["uname"];
            $user = NULL;
            $hasher = new \PasswordHash(8, false);
            foreach($this->userDB as $u)
            {
                if($u->breaduserdata->username == $username)
                {
                    $user = $u;
                    Site::$Logger->writeMessage("Username identified!");
                    break;
                    
                }
                Site::$Logger->writeMessage("Could not log user in because the username is not correct.");
                return 10;
            }
            $pw = $_POST["pw"];
            if($hasher->CheckPassword($pw,$user->hash))
            {
                Site::$Logger->writeMessage("Password was correct!");
            }
            else
            {
                Site::$Logger->writeMessage("Password failed!");
                return 10;
            }

            return 11;
        }
        
        function FirstTime($path)
        {
            Site::$Logger->writeMessage("First time setup for BreadUserSystem. If this is not the first time the module has started then there is an issue.");
            $hasher = new \PasswordHash(8, false);
            //Store the salt (shall be stored in settings, which should already be locked out by the user!)
            $this->settings->string = $salt;
            //Create the user db
            $rootUser = new BreadUser;
            $rootUser->uid = 0;
            $rootUser->rights[] = "root";
            $rootUser->username = "root";
            $packet = new BreadUserPacket;
            $packet->breaduserdata = $rootUser;
            $pw = "ILikeToast";
            $packet->hash = $hasher->HashPassword($pw);
            $this->userDB[] = $packet;
            Site::$settingsManager->SaveSetting($this->userDB,$path);
            //Only load the salt if its required
        }
        
        function Setup()
        {          
            require_once("PasswordHash.php");
            $rootSettings = Site::$settingsManager->FindModuleDir("breadusersystem");
            Site::$settingsManager->CreateSettingsFiles($rootSettings . "settings.json", new BreadUserSystemSettings);
            $this->settings = Site::$settingsManager->RetriveSettings($rootSettings . "settings.json");

            Site::$settingsManager->CreateSettingsFiles($rootSettings . $this->settings->userfile, array());
            $this->userDB = Site::$settingsManager->RetriveSettings($rootSettings . $this->settings->userfile,TRUE);
            if(\count($this->userDB) < 1){
               $this->FirstTime($rootSettings . $this->settings->userfile);
            }
            
            if(Site::getRequest()->requestType == "login"){
                $result = $this->DoLogin();
                Site::Redirect("index.php");
            }
            
        }
        
	function ReturnUser($arguments)
	{
	    return $this->currentUser;
        }
        
        function HasPermission($right)
        {
            //Not Finished
            return false;
        }
}

class BreadUserSystemSettings{
    public $limitToHTTPS = true;
    public $userfile  = "users.json";
}

class BreadUserPacket{
    public $breaduserdata;
    public $hash;
}