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
            $this->manager->RegisterEvent($this->name,"Bread.Security.Logout","Logout");
            $this->manager->RegisterEvent($this->name,"Bread.GetNavbarIndex","CreateLoginLink");
	}
        
        function DoLogin()
        {
            $return = array("status" => 10,"goto" => "");
            session_start();
            Site::$Logger->writeMessage(var_export($_POST,true));
            if(!isset($_POST["uname"]) || !isset($_POST["pw"]))
                return json_encode($return);
            Site::$Logger->writeMessage("Got Right Info!");
            $username = $_POST["uname"];
            $user = NULL;
            foreach($this->userDB as $u)
            {
                if($u->breaduserdata->username == $username)
                {
                    $user = $u;
                    Site::$Logger->writeMessage("Username identified!");
                    break;
                    
                }
                Site::$Logger->writeMessage("Could not log user in because the username is not correct.");
                return json_encode($return);
            }
            $pw = $_POST["pw"];
            $hasher = new \PasswordHash(8, false);
            if($hasher->CheckPassword($pw,$user->hash))
            {
                Site::$Logger->writeMessage("Password was correct!");
                $_SESSION["lastlogin"] = time(); //Setting this is enough.
                $_SESSION["REMOTE_ADDR"] = $_SERVER["REMOTE_ADDR"];
                $_SESSION["HTTP_USER_AGENT"] = $_SERVER["HTTP_USER_AGENT"];
                $_SESSION["uid"] = $user->breaduserdata->uid;
            }
            else
            {
                Site::$Logger->writeMessage("Password failed!");
                return json_encode($return);
            }
            $return["status"] = 11;
            $return["goto"] = $this->settings->successredirect->createURL();
            return json_encode($return);
        }
        
        function FirstTime($path)
        {
            Site::$Logger->writeMessage("First time setup for BreadUserSystem. If this is not the first time the module has started then there is an issue.");
            $hasher = new \PasswordHash(8, false);
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
            Site::$settingsManager->CreateSettingsFiles($rootSettings . "settings.json", new BreadUserSystemSettings());
            $this->settings = Site::$settingsManager->RetriveSettings($rootSettings . "settings.json");
            $this->settings->successredirect = Site::CastStdObjectToStruct($this->settings->successredirect, "\Bread\Structures\BreadLinkStructure");
            
            Site::$settingsManager->CreateSettingsFiles($rootSettings . $this->settings->userfile, array());
            $this->userDB = Site::$settingsManager->RetriveSettings($rootSettings . $this->settings->userfile,TRUE);
            
            if(\count($this->userDB) < 1){
               $this->FirstTime($rootSettings . $this->settings->userfile);
            }
            
            $this->CheckSession();
            if(Site::getRequest()->requestType == "login"){
                if($this->currentUser){ //Logout
                    Site::$Logger->writeMessage("User is logging out.");
                    $this->Logout();
                    Site::Redirect(Site::getBaseURL());
                }
                else{ //Login
                    $result = $this->DoLogin();
                }
                
            }

        }
        
        function CreateLoginLink()
        {
           $link = new \Bread\Structures\BreadLinkStructure();
           if(isset($this->currentUser))
           {
               $link->request = "login";
               $link->text = "Logout";
           }
           else
           {
               $link->request = "loginform";
               $link->text = "Login";
           }
           return array($link);
        }
        
	function ReturnUser($arguments)
	{
	    return $this->currentUser;
        }
        
        function HasPermission($requestedpermission)
        {
            if(!isset($this->currentUser))
                return False;
            return (in_array($requestedpermission,$this->currentUser->rights) || in_array("root",$this->currentUser->rights));
                    
        }
        
        function Logout()
        {
                session_unset();
                session_destroy();
        }
        
        function CheckSession()
        {
            session_start();
            if(!isset($_SESSION["lastlogin"])){
                Site::$moduleManager->HookEvent("Bread.Security.NotLoggedIn",NULL);
                return False;          
            }
            if(time() - $_SESSION["lastlogin"] > $this->settings->sessiontimeout){
                $this->Logout();
                Site::$Logger->writeMessage("Login session timed out.");
                Site::$moduleManager->HookEvent("Bread.Security.SessionTimeout",NULL);
                return False;
            }
            
            //Invalidate the session if any of these have changed.
            if(!is_int($_SESSION["uid"]) 
                    || $_SESSION["REMOTE_ADDR"] != $_SERVER["REMOTE_ADDR"]
                    || $_SESSION["HTTP_USER_AGENT"] != $_SERVER["HTTP_USER_AGENT"])
            {
                Site::$moduleManager->HookEvent("Bread.Security.InvalidSession",0);
                Site::$Logger->writeMessage("Session had a changed host,address,user agent or corrupt uid. Destroying");
                $this->Logout();
                return False;
            }    
            Site::$Logger->writeMessage("User logged in.");
            session_regenerate_id ();
            $this->currentUser = $this->userDB[$_SESSION["uid"]]->breaduserdata;
            Site::$moduleManager->HookEvent("Bread.Security.LoggedIn",NULL);
        }
}

class BreadUserSystemSettings{
    
    function __construct(){
        $this->successredirect = new \Bread\Structures\BreadLinkStructure();
    }
    
    public $limitToHTTPS = true;
    public $userfile  = "users.json";
    public $sessiontimeout = 604800;
    public $successredirect;
}

class BreadUserPacket{
    public $breaduserdata;
    public $hash;
}