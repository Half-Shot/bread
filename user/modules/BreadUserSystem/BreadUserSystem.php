<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Utilitys as Util;
use Bread\Structures\BreadUser as BreadUser;
/**
 * The standard system for bread.
 * You will need to sort out the password salt yourself .
 */
class BreadUserSystem extends Module
{
    private $currentUser;
    private $settings;
    private $settingsPath;
    private $userDB;
    private $userDBPath;
    private $groups;
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}
    
    function DoLogin()
    {
        $return = array("status" => 10,"goto" => "");
        Site::$Logger->writeMessage("Post: " . var_export($_POST,true), $this->name);
        if(!array_key_exists("uname",$_POST) || !array_key_exists("uname",$_POST))
            return json_encode($return);
        Site::$Logger->writeMessage("Login information is all here.",$this->name);
        $username = $_POST["uname"];
        $user = NULL;
        foreach($this->userDB as $u)
        {
            if($u->breaduserdata->username == $username)
            {
                $user = $u;
                Site::$Logger->writeMessage("Username identified!",$this->name);
                break;
            }
        }
        if(is_null($user))
        {
            Site::$Logger->writeError("Could not log in because the username does not exist",  \Bread\Logger::SEVERITY_LOW,$this->name);
            return json_encode($return);
        }
        
        $pw = $_POST["pw"];
        $hasher = new \PasswordHash(8, false);
        if($hasher->CheckPassword($pw,$user->hash))
        {
            Site::$Logger->writeMessage("Password was correct!",$this->name);
            session_start();
            $_SESSION["lastlogin"] = time(); //Setting this is enough.
            $_SESSION["REMOTE_ADDR"] = $_SERVER["REMOTE_ADDR"];
            $_SESSION["HTTP_USER_AGENT"] = $_SERVER["HTTP_USER_AGENT"];
            $_SESSION["uid"] = $user->breaduserdata->uid;
        }
        else
        {
            Site::$Logger->writeError("Password Failed!", \Bread\Logger::SEVERITY_LOW,$this->name);
            return json_encode($return);
        }
        $return["status"] = 11;
        $return["goto"] = $this->settings->successredirect->createURL();
        return json_encode($return);
    }
    
    function FirstTime($path)
    {
        Site::$Logger->writeError("First time setup, if this is not the first time then somethings wrong.",\Bread\Logger::SEVERITY_MEDIUM,$this->name);
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
    
    function StoreNewUser($username,$password,$uid = -1,$rights = array(),$extrainformation = array())
    {
        Site::$Logger->writeMessage("Storing new user.",$this->name);
        $newUser = new BreadUser;
        $newUser->uid = 0;
        $min = 1 * pow(10,8);
        $max = 9 * pow(10,8);
        if($uid == -1){
            while($this->GetUserByUID($newUser->uid) !== False){
                $newUser->uid = mt_rand($min,$max); 
            }
        }
        else{
            $newUser->uid = $uid;
        }
        $newUser->username = $username;
        $hasher = new \PasswordHash(8, false);
        $hash = $hasher->HashPassword($password);
        Site::$Logger->writeMessage(var_export($extrainformation,true));
        $newUser->information = $extrainformation;
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
            Site::$Logger->writeMessage("Not enough information sent!",$this->name);
            return json_encode($return);
        }
        
        foreach($this->userDB as $user)
        {
            if($user->breaduserdata->username == $_POST["uname"])
            {
                Site::$Logger->writeError("Dupe username.",\Bread\Logger::SEVERITY_LOW,$this->name);
                $return["status"] = 12;
                return json_encode($return);
            }
        }
        Site::$Logger->writeMessage("Username looks good.",$this->name);
        $extrainformation = json_decode($_POST["extrainfo"]);
        $extrainformation = get_object_vars($extrainformation);
        $this->StoreNewUser($_POST["uname"],$_POST["pw"],-1,array(),$extrainformation);
        Site::$Logger->writeMessage("Stored User OK",$this->name);
        $return["goto"] = $this->settings->successredirect->createURL();
        $return["status"] = 11;
        Site::$Logger->writeMessage("Sent success response header.",$this->name);
        return json_encode($return);
    }
    
    function Setup()
    {          
        require_once("PasswordHash.php");
        $rootSettings = Site::$settingsManager->FindModuleDir("breadusersystem");
        $this->settingsPath = $rootSettings . "settings.json";
        Site::$settingsManager->CreateSettingsFiles($this->settingsPath, new BreadUserSystemSettings());
        $this->settings = Util::CastStdObjectToStruct(Site::$settingsManager->RetriveSettings($rootSettings . "settings.json"), "\Bread\Modules\BreadUserSystemSettings");
        $this->settings->successredirect = Site::CastStdObjectToStruct($this->settings->successredirect, "\Bread\Structures\BreadLinkStructure");
        
        $this->userDBPath = $rootSettings . $this->settings->userfile;
        Site::$settingsManager->CreateSettingsFiles($this->userDBPath, array());
        $this->userDB = Site::$settingsManager->RetriveSettings($this->userDBPath,true);
        
        $groupPath = $rootSettings . $this->settings->groupfile;
        Site::$settingsManager->CreateSettingsFiles($groupPath, array());
        $this->groups = Site::$settingsManager->RetriveSettings($groupPath,true);
        if(empty($this->groups))
        {
            $rootGroup = new \Bread\Structures\BreadGroup();
            $rootGroup->name = "superadmin";
            $rootGroup->rights = "root";
            $this->groups[] = $rootGroup;
            Site::$settingsManager->SaveSetting($this->groups,$groupPath);
        }
        $this->groups = Util::ArraySetKeyByProperty($this->groups, "name");

        if(\count($this->userDB) < 1){
           $this->FirstTime($rootSettings . $this->settings->userfile);
        }
        
        
        $this->CheckSession();
        if(Site::getRequest()->requestType == "login"){
            if($this->currentUser){ //Logout
                Site::$Logger->writeMessage("User is logging out.",$this->name);
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
       if(!$this->settings->showNavbarlinks)
           return $links;
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
    
    function LoginName($args)
    {
        if(!$this->currentUser)
            return false;
        $Class = "";
        $LoggedInText = "Signed in as ";
        if(isset($args[0]))
        {
            if(isset($args[0]->Class))
                $Class = $args[0]->Class;
        }
        $Name = $this->currentUser->username;
        if(isset($this->currentUser->information->Name))
            $Name = $this->currentUser->information->Name;
        if($this->currentUser && $Name)
            return "<p class='" . $Class ."'>" . $LoggedInText . $Name . "</p>";
    }
    
    function LoginButton($args)
    {
        if(isset($this->currentUser))
        {
            $link = new \Bread\Structures\BreadLinkStructure();
            $link->request = "login";
        }
        else
        {
            //Modal
            $ModalObject = new \Bread\Structures\BreadModal();
            $ModalObject->title = "Login";
            $ModalObject->id = "login-modal";
            $ModalObject->body = $this->manager->FireEvent("BreadFormBuilder.DrawForm","login")[0];
            Site::AddToBodyCode($this->manager->FireEvent("Theme.Modal",$ModalObject)[0]);
        }
        $ButtonClass = "";
        if(isset($args[0]))
        {
            if(isset($args[0]->Class))
                $ButtonClass = $args[0]->Class;
        }
        
        $NotLoggedInButton = "Sign In";
        $LoggedInButton = "Sign Out";
        $Button = array();
        if(isset($link)){
            $Button["onclick"] = "window.location = '" . $link->createURL() .  "'";
        }
        else
        {
            $Button["onclick"] = "$('#login-modal').modal();";
        }
        if($this->currentUser){
            $Button["class"] = "btn-danger " . $ButtonClass;
            $Button["value"] = $LoggedInButton;
        }
        else
        {
            $Button["class"] = "btn-success " . $ButtonClass;
            $Button["value"] = $NotLoggedInButton;
        }
        
        return $this->manager->FireEvent("Theme.Button",$Button)[0];
    }
    
    function ReturnUser()
    {
        return $this->currentUser;
    }
    
    function HasPermission($requestedpermission)
    {
        if(!isset($this->currentUser))
            return False;
        if(in_array($requestedpermission,$this->currentUser->rights)){
            return True;
        }
        if(in_array("root",$this->currentUser->rights)){
            return True;
        }
        foreach($this->currentUser->groups as $groupName){
            if(in_array($requestedpermission,$this->groups[$groupName]->rights))
                return true;
        }
                
    }
    
    function Logout()
    {
            session_unset();
            session_destroy();
    }
    
    function CheckSession()
    {
        if(session_status() !== PHP_SESSION_ACTIVE)
            session_start();
        
        if(!isset($_SESSION["lastlogin"])){
            Site::$moduleManager->FireEvent("Bread.Security.NotLoggedIn",NULL);
            return False;          
        }
        if(time() - $_SESSION["lastlogin"] > $this->settings->sessiontimeout){
            $this->Logout();
            Site::$Logger->writeMessage("Login session timed out.",$this->name);
            Site::$moduleManager->FireEvent("Bread.Security.SessionTimeout",NULL);
            return False;
        }
        
        //Invalidate the session if any of these have changed.
        if(!is_int($_SESSION["uid"]) 
                || $_SESSION["REMOTE_ADDR"] != $_SERVER["REMOTE_ADDR"]
                || $_SESSION["HTTP_USER_AGENT"] != $_SERVER["HTTP_USER_AGENT"])
        {
            Site::$moduleManager->FireEvent("Bread.Security.InvalidSession",0);
            Site::$Logger->writeMessage("Session had a changed host,address,user agent or corrupt uid. Destroying",$this->name);
            $this->Logout();
            return False;
        }    
        Site::$Logger->writeMessage("User logged in.",$this->name);
        session_regenerate_id ();
        $user = $this->GetUserByUID($_SESSION["uid"]);
        if(!$user)
        {
            Site::$moduleManager->FireEvent("Bread.Security.InvalidSession",0);
            Site::$Logger->writeMessage("Stored UID does not exist, whoa!",$this->name);
            $this->Logout();
            return False;
        }
        $this->currentUser = clone $user->breaduserdata;
        Site::$moduleManager->FireEvent("Bread.Security.LoggedIn",NULL);
    }
    
    //
    //Admin Panel Functions
    //
    function ConstructAdminSettings($args)
    {
        Site::AddScript(Util::FindFile(Util::GetDirectorySubsection(__DIR__,0,1) . "js/adminpanel.js") , true);
        $MasterSettings = new \Bread\Structures\BreadModuleSettings();
        $MasterSettings->Name = "User Security";
            
        $PostConfigurator = new \Bread\Structures\BreadModuleSettingsTab();
        $PostConfigurator->HumanTitle = "Users";
        $MasterSettings->SettingsGroups[] = $PostConfigurator;
        
        $GlobalSettings = new \Bread\Structures\BreadModuleSettingsTab;
        $GlobalSettings->HumanTitle = "Permissions";
        $MasterSettings->SettingsGroups[] = $GlobalSettings;
        if(!$args[0])
            return $MasterSettings;
        
        //Current Users Table
        $CurrentUsersPanel = new \Bread\Structures\BreadModuleSettingsPanel();
        $CurrentUsersPanel->Name = "currentusers";
        $CurrentUsersPanel->HumanTitle = "Users";
        $CurrentUsersPanel->PercentageWidth = 75;
        $PostConfigurator->Panels[] = $CurrentUsersPanel;
        
        //Table
        $UserTable = new \Bread\Structures\BreadTableElement();
        $UserTable->class = " table-hover";
        $UserTable->headingRow = new \Bread\Structures\BreadTableRow();
        $Headers = $this->settings->adminPanelSettings->informationKeysToShow;
        
        $Header = new \Bread\Structures\BreadTableCell();
        $Header->text = "Username";
        $UserTable->headingRow->cells[] = $Header;
        
        foreach($Headers as $HeaderTitle){
            $Header = new \Bread\Structures\BreadTableCell();
            $Header->text = $HeaderTitle;
            $UserTable->headingRow->cells[] = $Header;
        }
        
        foreach($this->userDB as $UIndex => $userFile){
            /**
             * @var Bread\Structures\BreadUser
             */
            $DataFile = $userFile->breaduserdata;
            if(in_array($DataFile->uid,$this->settings->adminPanelSettings->hiddenUsers))
                continue;
            $UserRow = new \Bread\Structures\BreadTableRow();
            $UserRow->id = "user-" . $UIndex;
            //Username
            $UsernameCell = new \Bread\Structures\BreadTableCell();
            $UsernameCell->text = $DataFile->username;
            $UserRow->cells[] = $UsernameCell;
            
            foreach($Headers as $Item){
                $NewCell = new \Bread\Structures\BreadTableCell();
                if(isset($DataFile->information->$Item)){
                    $NewCell->text = $DataFile->information->$Item;
                }
                else{
                    $NewCell->text = "";
                }
                $UserRow->cells[] = $NewCell;
            }
            
            $UserTable->rows[] = $UserRow;
            
        }
        
        $Button = new \Bread\Structures\BreadFormElement();
        $Button->id = "EditUser";
        $Button->class = "btn-primary";
        $Button->value = "Edit Users";
        $ButtonHTML = Site::$moduleManager->FireEvent("Theme.Button",$Button)[0];
        
        $CurrentUsersPanel->Body = Site::$moduleManager->FireEvent("Theme.Table",$UserTable)[0] . $ButtonHTML;
        
        
        //Edit User Modal
        $ModalData = new \Bread\Structures\BreadModal();
        $ModalData->id = "editUserModal";
        $ModalData->title = "Editing";
        
        //Modal Form
        $ModalForm = new \Bread\Structures\BreadForm;
        $ModalForm->id = "UserEditForm";
        foreach($this->settings->adminPanelSettings->usereditForm as $element)
        {
            if(isset($element->informationKey))
            {
                if($element->informationKey !== false){
                    $element->id = $element->informationKey;
                }
            }
            $ModalForm->elements[] = Util::CastStdObjectToStruct($element,"\Bread\Modules\BUSUserInformationField");
        }
        
        $SubmitButton = new \Bread\Structures\BreadFormElement;
        $ModalForm->elements[] = $SubmitButton;
        
        $SubmitButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $SubmitButton->class = "btn-success";
        $SubmitButton->id = "submitButton";
        $SubmitButton->value = "Change User";
        
        $ModalData->body = $this->manager->FireEvent("Theme.Form", $ModalForm)[0];
        
        Site::AddToBodyCode($this->manager->FireEvent("Theme.Modal", $ModalData)[0]);
        return $MasterSettings;
    }
}

class BreadUserSystemSettings{
    
    function __construct(){
        $this->successredirect = new \Bread\Structures\BreadLinkStructure();
        $this->adminPanelSettings = new \Bread\Modules\BreadUserSystemAdminPanelSettings();
    }
    
    public $limitToHTTPS = true;
    public $userfile  = "users.json";
    public $groupfile = "groups.json";
    public $sessiontimeout = 604800;
    public $successredirect;
    public $showNavbarlinks = true;
    public $adminPanelSettings;
}

class BreadUserPacket{
    public $breaduserdata;
    public $hash;
}

class BreadUserSystemAdminPanelSettings{
    function __construct(){
        $this->usereditForm = array();
        $Username = new \StdClass();
        $this->usereditForm[] = $Username;
        $Password = new \StdClass();
        $this->usereditForm[] = $Password;
        $Name = new \StdClass();
        $this->usereditForm[] = $Name;    
        $Email = new \StdClass();
        $this->usereditForm[] = $Email;    
        
        $Username->label = "Username";
        $Username->required = true;
        $Username->type = "text";
        $Username->informationKey = "username";
        $Username->multiuser = false;
        
        $Password->label = "Password";
        $Password->required = true;
        $Password->type = "password";
        $Password->value = "password";
        $Password->informationKey = "password";
        $Password->multiuser = false;
        
        $Name->label = "Name";
        $Name->required = false;
        $Name->type = "text";
        $Name->informationKey = "Name";
        $Name->multiuser = false;
        
        $Email->label = "E-Mail";
        $Email->required = true;
        $Email->type = "email";
        $Email->informationKey = "E-Mail";
        $Email->multiuser = false;
        
    }
    public $hiddenUsers = array(0);
    public $usereditForm;
    public $informationKeysToShow = array("Name","E-Mail");
}

class BUSUserInformationField extends \Bread\Structures\BreadFormElement{
    public $informationKey = "";
    public $multiuser = false;
}