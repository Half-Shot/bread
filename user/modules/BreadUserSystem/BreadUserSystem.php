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
        private $userDBPath;
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}

	function RegisterEvents()
	{
            $this->manager->RegisterHook($this->name,"Bread.Security.GetCurrentUser","ReturnUser");
            $this->manager->RegisterHook($this->name,"Bread.Security.GetPermission","HasPermission");
            $this->manager->RegisterHook($this->name,"Bread.ProcessRequest","Setup");
            $this->manager->RegisterHook($this->name,"Bread.Security.LoginUser","DoLogin");
            $this->manager->RegisterHook($this->name,"Bread.Security.RegisterNewUser","RegisterNewUser");
            $this->manager->RegisterHook($this->name,"Bread.Security.Logout","Logout");
            $this->manager->RegisterHook($this->name,"Bread.GetNavbarIndex","CreateLoginLink");
	}
        
        function DoLogin()
        {
            $return = array("status" => 10,"goto" => "");
            session_start();
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
            }
            if(is_null($user))
            {
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
             $this->StoreNewUser("root","ILikeToast",0,array("root"));
            Site::$settingsManager->SaveSetting($this->userDB,$path);
        }
        
        function GetUserByUID($uid)
        {
            foreach($this->userDB as $user)
            {
                if($uid == $user->breaduserdata->uid)
                    return $user;
            }
            return False;
        }
        
        function StoreNewUser($username,$password,$uid = -1,$rights = array(),$extrainfomation = array())
        {
            Site::$Logger->writeMessage("Storing new user.");
            $newUser = new BreadUser;
            $newUser->uid = 0;
            if($uid == -1){
                while($this->GetUserByUID($newUser->uid) !== False){
                    $newUser->uid = mt_rand(1 * pow(10,16),9 * pow(10,16)); 
                }
            }
            else{
                $newUser->uid = $uid;
            }
            $newUser->username = $username;
            $hasher = new \PasswordHash(8, false);
            $hash = $hasher->HashPassword($password);
            Site::$Logger->writeMessage(var_export($extrainfomation,true));
            $newUser->infomation = $extrainfomation;
            $newUser->rights = $rights;
            $packet = new BreadUserPacket;
            $packet->breaduserdata = $newUser;
            $packet->hash = $hash;
            $this->userDB[] = $packet;
            Site::$settingsManager->SaveSetting($this->userDB,$this->userDBPath);
        }
        
        function RegisterNewUser()
        {
            $return = array("status" => 10);
            if(!isset($_POST["uname"]) || !isset($_POST["pw"])|| !isset($_POST["extrainfo"]))
            {
                Site::$Logger->writeMessage("Not enough infomation sent!");
                return json_encode($return);
            }
            
            foreach($this->userDB as $user)
            {
                if($user->breaduserdata->username == $_POST["uname"])
                {
                    Site::$Logger->writeMessage("Dupe username.");
                    $return["status"] = 12;
                    return json_encode($return);
                }
            }
            Site::$Logger->writeMessage("Username looks good.");
            $extrainfomation = json_decode($_POST["extrainfo"]);
            $extrainfomation = get_object_vars($extrainfomation);
            $this->StoreNewUser($_POST["uname"],$_POST["pw"],-1,$extrainfomation);
            Site::$Logger->writeMessage("Stored User OK");
            $return["goto"] = $this->settings->successredirect->createURL();
            $return["status"] = 11;
            Site::$Logger->writeMessage("Sent success response header.");
            return json_encode($return);
        }
        
        function Setup()
        {          
            require_once("PasswordHash.php");
            $rootSettings = Site::$settingsManager->FindModuleDir("breadusersystem");
            Site::$settingsManager->CreateSettingsFiles($rootSettings . "settings.json", new BreadUserSystemSettings());
            $this->settings = Site::$settingsManager->RetriveSettings($rootSettings . "settings.json");
            $this->settings->successredirect = Site::CastStdObjectToStruct($this->settings->successredirect, "\Bread\Structures\BreadLinkStructure");
                        
            $this->userDBPath = $rootSettings . $this->settings->userfile;
            Site::$settingsManager->CreateSettingsFiles($this->userDBPath, array());
            $this->userDB = Site::$settingsManager->RetriveSettings($this->userDBPath,true);
            
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
           $links = array();
           if(isset($this->currentUser))
           {
               $logout = new \Bread\Structures\BreadLinkStructure();
               $logout->request = "login";
               $logout->text = "Logout";
               $links[] = $logout;
           }
           else
           {
               $login = new \Bread\Structures\BreadLinkStructure();
               $login->request = "loginform";
               $login->text = "Login";
               $links[] = $login;
               
               $register = new \Bread\Structures\BreadLinkStructure();
               $register->request = "registerform";
               $register->text = "Register";
               $links[] = $register;
           }
           return $links;
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
                Site::$moduleManager->FireEvent("Bread.Security.NotLoggedIn",NULL);
                return False;          
            }
            if(time() - $_SESSION["lastlogin"] > $this->settings->sessiontimeout){
                $this->Logout();
                Site::$Logger->writeMessage("Login session timed out.");
                Site::$moduleManager->FireEvent("Bread.Security.SessionTimeout",NULL);
                return False;
            }
            
            //Invalidate the session if any of these have changed.
            if(!is_int($_SESSION["uid"]) 
                    || $_SESSION["REMOTE_ADDR"] != $_SERVER["REMOTE_ADDR"]
                    || $_SESSION["HTTP_USER_AGENT"] != $_SERVER["HTTP_USER_AGENT"])
            {
                Site::$moduleManager->FireEvent("Bread.Security.InvalidSession",0);
                Site::$Logger->writeMessage("Session had a changed host,address,user agent or corrupt uid. Destroying");
                $this->Logout();
                return False;
            }    
            Site::$Logger->writeMessage("User logged in.");
            session_regenerate_id ();
            $user = $this->GetUserByUID($_SESSION["uid"]);
            if(!$user)
            {
                Site::$moduleManager->FireEvent("Bread.Security.InvalidSession",0);
                Site::$Logger->writeMessage("Stored UID does not exist, whoa!");
                $this->Logout();
                return False;
            }
            $this->currentUser = $user->breaduserdata;
            Site::$moduleManager->FireEvent("Bread.Security.LoggedIn",NULL);
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