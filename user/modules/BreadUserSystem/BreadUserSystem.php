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
    const STRETCH_FACTOR = 2;
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
        $username = strtolower($_POST["uname"]);
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
        $hasher = new \PasswordHash(BreadUserSystem::STRETCH_FACTOR, false);
        if($hasher->CheckPassword($pw,$user->hash))
        {
            Site::$Logger->writeMessage("Password was correct!",$this->name);
            if (session_status() === PHP_SESSION_NONE){session_start();}
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
        $rootpasswd = "ILikeToast";
        Site::$Logger->writeError("First time setup, if this is not the first time then somethings wrong.",\Bread\Logger::SEVERITY_MEDIUM,$this->name);
        Site::$Logger->writeError("Pay close attention! You're password for <b>root</b> is <b>".$rootpasswd."</b>",\Bread\Logger::SEVERITY_MEDIUM,$this->name);
        $this->StoreNewUser("root",$rootpasswd,0,array("root"),array("Name"=>"Root"));
        Site::$settingsManager->SaveSetting($this->userDB,$path);
    }
    
    function GetUserByUID($uid)
    {
        foreach($this->userDB as $user)
        {
            if($uid == $user->breaduserdata->uid)
                return $user->breaduserdata;
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
        $newUser->information = new \stdClass();
        $newUser->information = Util::ArrayToStdObject($extrainformation);
        $newUser->rights = $rights;
        $packet = new BreadUserPacket;
        $packet->breaduserdata = $newUser;
        $packet->hash = $hash;
        $this->userDB[] = $packet;
        Site::$settingsManager->SaveSetting($this->userDB,$this->userDBPath);
        return $newUser;
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
            if($user->breaduserdata->username == strtolower($_POST["uname"]))
            {
                Site::$Logger->writeError("Dupe username.",\Bread\Logger::SEVERITY_LOW,$this->name);
                $return["status"] = 12;
                return json_encode($return);
            }
        }
        Site::$Logger->writeMessage("Username looks good.",$this->name);
        $extrainformation = json_decode($_POST["extrainfo"]);
        $extrainformation = get_object_vars($extrainformation);
        $this->StoreNewUser(strtolower($_POST["uname"]),$_POST["pw"],-1,array(),$extrainformation);
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
        $this->settings = Util::CastStdObjectToStruct(Site::$settingsManager->RetriveSettings($rootSettings . "settings.json",true,new BreadUserSystemSettings()), "\Bread\Modules\BreadUserSystemSettings");
        $this->settings->successredirect = Site::CastStdObjectToStruct($this->settings->successredirect, "\Bread\Structures\BreadLinkStructure");
        
        $this->userDBPath = $rootSettings . $this->settings->userfile;
        $this->userDB = Site::$settingsManager->RetriveSettings($this->userDBPath,true,array());
        
        $groupPath = $rootSettings . $this->settings->groupfile;
        $this->groups = Site::$settingsManager->RetriveSettings($groupPath,true,array());
        if(empty($this->groups))
        {
            $rootGroup = new \Bread\Structures\BreadGroup();
            $rootGroup->name = "superadmin";
            $rootGroup->rights = array("root");
            $this->groups[] = $rootGroup;
            Site::$settingsManager->SaveSetting($this->groups,$groupPath);
        }
        $this->groups = Util::ArraySetKeyByProperty($this->groups, "name");

        if(\count($this->userDB) < 1){
           $this->FirstTime($rootSettings . $this->settings->userfile);
        }
        
        $this->CheckSession();
        
    }
    
    function DrawLoginName($args)
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
        Site::AddScript(Site::ResolvePath("%user-modules/BreadUserSystem/js/breaduser.js"), "BreadUserSystem", true);
        if(isset($this->currentUser))
        {
            //$link = new \Bread\Structures\BreadLinkStructure();
            //$link->request = "login";
        }
        else
        {
            //Modal
            $ModalObject = new \Bread\Structures\BreadModal();
            $ModalObject->title = "Login";
            $ModalObject->id = "login-modal";
            //Alert Modal
            //
            //Wrong
            $AlertWrong = array("id"=>"login-alert-fail","body"=>"Oh noes: You're username or password was incorrect.","canClose"=>false,"class"=>$this->manager->FireEvent("Theme.GetClass","Alert.Danger"));
            
            //Correct
            $AlertCorrect = array("id"=>"login-alert-success","body"=>"All good. Welcome back!","canClose"=>false,"class"=>$this->manager->FireEvent("Theme.GetClass","Alert.Success"));
            
            $ModalObject->body = $this->manager->FireEvent("Theme.Alert",$AlertWrong) . $this->manager->FireEvent("Theme.Alert",$AlertCorrect) . $this->manager->FireEvent("BreadFormBuilder.DrawForm","login");
            Site::AddRawScriptCode('$("#login-alert-success").hide();',true);
            Site::AddRawScriptCode('$("#login-alert-fail").hide();',true);
            Site::AddToBodyCode($this->manager->FireEvent("Theme.Modal",$ModalObject));
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
        if($this->currentUser){
            $Button["class"] = "btn-danger " . $ButtonClass;
            $Button["value"] = $LoggedInButton;
            $Button["onclick"] = "breadLogout();";
        }
        else
        {
            $Button["class"] = "btn-success " . $ButtonClass;
            $Button["value"] = $NotLoggedInButton;
            $Button["onclick"] = "$('#login-modal').modal();";
        }
        
        return $this->manager->FireEvent("Theme.Button",$Button);
    }
    
    function GetCurrentUser()
    {
        return $this->currentUser;
    }
    
    function HasPermission($requestedpermission)
    {
        Site::$Logger->writeMessage("Permission Requested:" . $requestedpermission,$this->name);
        if(!isset($this->currentUser))
            return False;
        if(in_array($requestedpermission,$this->currentUser->rights)){
            return True;
        }
        if(in_array("root",$this->currentUser->rights)){
            return True;
        }
        foreach($this->currentUser->groups as $groupName){
            
            if(in_array($requestedpermission,$this->groups[$groupName]->rights) || in_array("root",$this->groups[$groupName]->rights))
                return true;
        }
        return false;
    }
    
    function Logout()
    {        
        if($this->currentUser){ //Logout
            Site::$Logger->writeMessage("User is logging out.",$this->name);
            session_unset();
            session_destroy();
            return Site::getBaseURL();
        }
        else{
            return 0;
        }
    }
    
    function AjaxCheckString($string,$charmin){
        //Obey all the usual limits.
        if(empty($string) || !is_string($string) || strlen($string) < $charmin || ctype_space($string))
            return false;
        
        if(htmlentities($string) != $string){
            Site::$Logger->writeError("User " . $user->uid . "(" . $user->username . ") had a information change attempt but a html injection was detected!",  \Bread\Logger::SEVERITY_LOW);
            return false;
        }
        return true;
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
        $this->currentUser = clone $user;
        Site::$moduleManager->FireEvent("Bread.Security.LoggedIn",NULL);
    }
    
    //
    //Admin Panel Functions
    //
    function ConstructAdminSettings($args)
    {
        if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadUserSystem.AdminPanel.Read")){
            return false;
        }
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

        Site::AddScript(Util::FindFile(Util::GetDirectorySubsection(__DIR__,0,1) . "js/adminpanel.js"),"AdminPanelScript",true);
        //Current Users Table
        $PostConfigurator->Panels[] = $this->ConstructUserPanel();
        
        $ToolsPanel = new \Bread\Structures\BreadModuleSettingsPanel();
        $ToolsPanel->Name = "currentusers";
        $ToolsPanel->HumanTitle = "Users";
        $ToolsPanel->PercentageWidth = 25;
        $PostConfigurator->Panels[] = $ToolsPanel;
        
        $AddNewUserButton = new \Bread\Structures\BreadFormElement();
        $AddNewUserButton->id = "NewUserButton";
        $AddNewUserButton->class = "btn-primary";
        $AddNewUserButton->value = "New User";
        $AddNewUserButton->readonly = !$this->manager->FireEvent("Bread.Security.GetPermission","BreadUserSystem.AddNewUser");
        $ToolsPanel->Body .= Site::$moduleManager->FireEvent("Theme.Button",$AddNewUserButton);
        
        return $MasterSettings;
    }
    
    function ConstructUserPanel(){
        $CurrentUsersPanel = new \Bread\Structures\BreadModuleSettingsPanel();
        $CurrentUsersPanel->Name = "currentusers";
        $CurrentUsersPanel->HumanTitle = "Users";
        $CurrentUsersPanel->PercentageWidth = 75;
        $PostConfigurator->Panels[] = $CurrentUsersPanel;
        
        //Table
        $UserTable = new \Bread\Structures\BreadTableElement();
        $UserTable->class = " table-hover";
        $UserTable->headingRow = new \Bread\Structures\BreadTableRow();
        $Headers = $this->settings->adminPanelSettings->informationKeysToShowInTable;
        
        $UsernameHeader = new \Bread\Structures\BreadTableCell();
        $UsernameHeader->text = "Username";      
        $UserTable->headingRow->cells[] = $UsernameHeader;
        
        $GroupsHeader = new \Bread\Structures\BreadTableCell();
        $GroupsHeader->text = "Groups";
        $UserTable->headingRow->cells[] = $GroupsHeader;
        
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
            $UserRow->id = "user-" . $DataFile->uid;
            //Username
            $UsernameCell = new \Bread\Structures\BreadTableCell();
            $UsernameCell->text = $DataFile->username;
            $UserRow->cells[] = $UsernameCell;
            
            //Group(s)
            $GroupsCell = new \Bread\Structures\BreadTableCell();
            if(isset($DataFile->groups)){
                $GroupsCell->text = implode(",", $DataFile->groups);
            }
            else
            {
                $GroupsCell->text = "";
            }
            
            $UserRow->cells[] = $GroupsCell;    
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
        if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadUserSystem.AdminPanel.Read")){
            $Button->readonly = false;
        }
        $ButtonHTML = Site::$moduleManager->FireEvent("Theme.Button",$Button);
        
        $CurrentUsersPanel->Body = Site::$moduleManager->FireEvent("Theme.Table",$UserTable) . $ButtonHTML;
        
        
        //Edit User Modal
        $ModalData = new \Bread\Structures\BreadModal();
        $ModalData->id = "editUserModal";
        $ModalData->title = "Editing";
        
        //Modal Form
        $ModalForm = new \Bread\Structures\BreadForm;
        $ModalForm->id = "UserEditForm";
        $Javascript = "var BUMulituserElements = new Array();";
        foreach($this->settings->adminPanelSettings->usereditForm as $element)
        {
            if(isset($element->informationKey))
            {
                if($element->informationKey !== false){
                    $element->id = $element->informationKey;
                }
                if(isset($element->multiuser)){
                    if($element->multiuser){
                        $Javascript .= 'BUMulituserElements.push("' . $element->informationKey . '");';
                    }
                }
                $ModalForm->elements[] = Util::CastStdObjectToStruct($element,"\Bread\Modules\BUSUserInformationField");
            }
        }
        
        $FooterButtonGroup = array();
        
        $SubmitButton = new \Bread\Structures\BreadFormElement;
        $SubmitButton->type = "submit";
        $SubmitButton->class = "btn-success";
        $SubmitButton->id = "submitButton";
        $SubmitButton->value = "Change User";
        $SubmitButton->form = $ModalForm->id;
        $FooterButtonGroup[] = $SubmitButton;
                
        $RemoveButton = new \Bread\Structures\BreadFormElement;
        $RemoveButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $RemoveButton->class = "btn-danger";
        $RemoveButton->id = "removeButton";
        $RemoveButton->value = "Delete User";
        $RemoveButton->form = $ModalForm->id;
        $RemoveButton->readonly = !$this->manager->FireEvent("Bread.Security.GetPermission","Bread.Security.RemoveUser");
        $FooterButtonGroup[] = $RemoveButton;
        
            $E_Modal_Confirm = new \Bread\Structures\BreadFormElement;
            $E_Modal_Confirm->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_Modal_Confirm->value = "Disintergrate";
            $E_Modal_Confirm->onclick = "deleteUser();";
            $E_Modal_Confirm->class = "btn-danger";
            
            $E_Modal_Cancel = new \Bread\Structures\BreadFormElement;
            $E_Modal_Cancel->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_Modal_Cancel->value = "Actually...";
            $E_Modal_Cancel->onclick = "$('#warnDeleteUser').modal('hide');";
            $E_Modal_Cancel->class = "btn-info";
            $Buttons = $this->manager->FireEvent("Theme.Button",$E_Modal_Confirm) . $this->manager->FireEvent("Theme.Button",$E_Modal_Cancel);
            $Buttons = $this->manager->FireEvent("Theme.Layout.ButtonGroup",$Buttons);
            
            //Modal for deleting posts.
            $ModalHTML = $this->manager->FireEvent("Theme.Modal",array("id"=>"warnDeleteUser","label"=>"modalDeleteUser","title"=>"Are You Sure?","body"=>"Are you sure you want to delete <strong id='DeleteUserName'>%Username%</strong>?","footer"=>$Buttons));
        
        
        $ModalData->footer = $this->manager->FireEvent("Theme.Layout.ButtonGroup",$FooterButtonGroup);
        $ModalData->body = $this->manager->FireEvent("Theme.Form", $ModalForm);
        Site::AddRawScriptCode($Javascript, true);
        Site::AddToBodyCode($this->manager->FireEvent("Theme.Modal", $ModalData) . $ModalHTML);
        
        return $CurrentUsersPanel;
    }

    function AjaxUserInfo(){  
        if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadUserSystem.AdminPanel.Read")){
            return false;
        }
        $returnData = array_flip($_POST["users"]);
        $userDB = $this->userDB;
        foreach($returnData as $uid => $val){
            $CurrentUser = $this->GetUserByUID(intval($uid));
            if($CurrentUser === false){
                unset($returnData[$uid]);
                continue;
            }
            $returnData[$uid] = array();
            foreach($this->settings->adminPanelSettings->usereditForm as $formElement){
                $key = $formElement->informationKey;
                if($key == "username" || $key == "password" || !is_string($key)){
                    continue;
                }
                if(isset($CurrentUser->information->$key)){
                    $returnData[$uid][$key] = $CurrentUser->information->$key;
                }
                else
                {
                    $returnData[$uid][$key] = "";
                }
            }
        }
        return json_encode(array_values($returnData))   ;
    }
    
    function ValidateUsername($args){
        if($args["user"]->username == strtolower($args["data"])){
            return false; //Nothing to do here.
        }
        return $this->AjaxCheckString(strtolower($args["data"]), $this->settings->minusernamelength);
    }
    
    function ValidatePassword($args){
        $RootUser = false;
        foreach($this->userDB as $u)
        {
            if($args["uid"] == $u->breaduserdata->uid){
                $RootUser = $u;
                break;
            }
        }

        $hasher = new \PasswordHash(BreadUserSystem::STRETCH_FACTOR, false);
        if($hasher->CheckPassword($args["data"],$RootUser->hash)){
            return false;
        }
        return $this->AjaxCheckString($args["data"], $this->settings->minpasswordlength);
        
    }
    
    function AjaxRemoveUser(){
        if(!$this->manager->FireEvent("Bread.Security.GetPermission","Bread.Security.RemoveUser")){
            return false;
        }
        $users = $_POST["users"];
        foreach ($users as $uid){
            $uid = intval($uid);
            if(in_array($uid, $this->settings->adminPanelSettings->hiddenUsers))
                continue; //User should not be edited!
            $this->RemoveUser($uid);
        }
        return true;
    }
    
    
    function RemoveUser($uid){
        if(!$this->manager->FireEvent("Bread.Security.GetPermission","Bread.Security.RemoveUser")){
            return false;
        }
        $user = false;
        $uindex = -1;
        foreach($this->userDB as $index => $u) 
        {
            if($uid == $u->breaduserdata->uid){
                $user = $u;
                $uindex = $index;
                break;
            }
        }
        
        if($user !== false){
            Site::$Logger->writeMessage("User " . $uid . "(" . $user->username . ") has been deleted",$this->name);
            unset($this->userDB[$uindex]);
        }
        Site::$settingsManager->SaveSetting($this->userDB,$this->userDBPath);
        return true;
    }
    
    function ChangeUsername($args){
        
        if(!array_key_exists("user", $args)){
            $user = $this->GetUserByUID($args["uid"]);
        }
        else{
            $user = $args["user"];
        }
        
        if(!$this->CheckUserEditPerms($user,"username"))
            return false;
        
        $user->username = strtolower($args["value"]);
        Site::$Logger->writeMessage("User " . $user->uid . "(" . $user->username . ") has new username" ,$this->name);
        Site::$settingsManager->SaveSetting($this->userDB,$this->userDBPath);
        return true;
    }
    
    function ChangePassword($args){
        $uid = $args["uid"];
        $value = $args["value"];
        
        if(!array_key_exists("user", $args)){
            $user = $this->GetUserByUID($uid);
        }
        else{
            $user = $args["user"];
        }
        
        
        if(!$this->CheckUserEditPerms($user,"password"))
            return false;
        
        $RootUser = false;
        foreach($this->userDB as $u) 
        {
            if($uid == $u->breaduserdata->uid){
                $RootUser = $u;
                break;
            }
        }

        $hasher = new \PasswordHash(BreadUserSystem::STRETCH_FACTOR, false);
        $RootUser->hash = $hasher->HashPassword($value);
        Site::$settingsManager->SaveSetting($this->userDB,$this->userDBPath);
        return true;
    }
    
    function ChangeInformation($args){
        $uid = $args["uid"];
        $value = $args["value"];
        $key = $args["key"];
        if(!array_key_exists("user", $args)){
            $user = $this->GetUserByUID($uid);
        }
        else{
            $user = $args["user"];
        }
        
        if(!$this->CheckUserEditPerms($args["user"],$args["key"]))
            return false;
        
        $user->information->$key = $args["value"];
        Site::$settingsManager->SaveSetting($this->userDB,$this->userDBPath);
        return true;
    }
 
    function CheckUserEditPerms($user,$key){
        $ownUser = ($user->uid == $this->GetCurrentUser()->uid);
        if($ownUser){
            if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadUserSystem.ChangeOwn." . $key)){
                Site::$Logger->writeError("No permission to change own password!",  \Bread\Logger::SEVERITY_MEDIUM,$this->name);
                return false;
            }
        }
        else{
            if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadUserSystem.Change." . $key)){
                Site::$Logger->writeError("No permission to change password!",  \Bread\Logger::SEVERITY_MEDIUM,$this->name);
                return false;
            }
        }
        return true;
    }
    
    function AjaxWriteUserInfo(){
        if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadUserSystem.AdminPanel.Write")){
            return false;
        }
        $users = $_POST["users"];
        
        if($users == 'false' && $this->manager->FireEvent("Bread.Security.GetPermission","BreadUserSystem.AddNewUser"))
        {
            $users = array($this->StoreNewUser("", "")->uid);
        }
        $data = Util::ArraySetKeyByProperty($_POST["data"],"name",true,true);
        foreach ($users as $uid){
            $user = $this->GetUserByUID($uid);
            if($user == false)
                continue; //User does not exist!
            if(in_array($uid, $this->settings->adminPanelSettings->hiddenUsers))
                continue;
            foreach($this->settings->adminPanelSettings->usereditForm as $formElement){
                $key = $formElement->informationKey;
                if(!array_key_exists($key, $data))
                    continue;

                $value = $data[$key];

                if(!$formElement->multiuser && count($users) > 1)
                    continue;

                if(!$this->CheckUserEditPerms($user,$key))
                    continue;

                //Validate
                $validatedOk = true;
                if(!empty($formElement->validationEvent))
                {
                    if(!empty($formElement->validationModule)){
                       $validatedOk = $this->manager->FireSpecifiedModuleEvent($formElement->validationEvent, $formElement->validationModule,array("user"=>$user,"data"=>$value,"uid"=>$uid));
                    }
                    else{
                       $validatedOk = $this->manager->FireSpecifiedModuleEvent($formElement->validationEvent,array("user"=>$user,"data"=>$value,"uid"=>$uid));
                    }
                }
                else
                {
                    Site::$Logger->writeError("Form information key " . $key ." has no validation event, which is very risky!",  \Bread\Logger::SEVERITY_MEDIUM,$this->name);
                    if($user->information->$key === $value){
                        continue; //Nothing to do here.
                    }
                }
                if(!$validatedOk){
                    continue;
                }
                
                $couldChange = false;
                //Make the edit.
                if(!empty($formElement->entryEvent))
                {
                    if(!empty($formElement->entryModule)){
                        $couldChange = $this->manager->FireSpecifiedModuleEvent($formElement->entryEvent, $formElement->entryModule,array("user"=>$user,"data"=>$value,"uid"=>$uid));
                    }
                    else{
                        $couldChange = $this->manager->FireSpecifiedModuleEvent($formElement->entryEvent,array("user"=>$user,"data"=>$value,"uid"=>$uid));
                    }
                }
                else if($key == "username")
                {
                    $couldChange = $this->manager->FireEvent("Bread.Security.ChangeUsername",array("user"=>$user,"uid"=>$uid,"value"=>$value));
                }
                else if($key == "password")
                {
                    $couldChange = $this->manager->FireEvent("Bread.Security.ChangePassword",array("user"=>$user,"uid"=>$uid,"value"=>$value));
                }
                else{
                    $couldChange = $this->manager->FireEvent("Bread.Security.ChangeInformation",array("user"=>$user,"uid"=>$uid,"key"=>$key,"value"=>$value));
                }

                //All Good
                if($couldChange){
                    $this->manager->FireEvent("Bread.Security.UserInformationChanged",array("user"=>$user,"uid"=>$uid,"key"=>$key));
                    Site::$Logger->writeMessage("User " . $user->uid . "(" . $user->username . ") has new " . $key ,$this->name);
                    Site::$Logger->writeMessage("This change was made by " . $this->currentUser->uid . " ( " . $this->currentUser->username  . " ) ",$this->name);
                }
            }
        }
        Site::$settingsManager->SaveSetting($this->userDB,$this->userDBPath);
        return true;
    }
}

class BreadUserSystemSettings{
    
    function __construct(){
        $this->successredirect = new \Bread\Structures\BreadLinkStructure();
        $this->successredirect->request = "homepage";
        $this->adminPanelSettings = new \Bread\Modules\BreadUserSystemAdminPanelSettings();
    }
    
    public $limitToHTTPS = true;
    public $userfile  = "users.json";
    public $groupfile = "groups.json";
    public $sessiontimeout = 604800;
    public $successredirect;
    public $showNavbarlinks = true;
    public $adminPanelSettings;
    public $minusernamelength = 4;
    public $minpasswordlength = 8;
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
        $Biography = new \StdClass();
        $this->usereditForm[] = $Biography;
        
        $Username->label = "Username";
        $Username->required = true;
        $Username->type = "text";
        $Username->informationKey = "username";
        $Username->multiuser = false;
        $Username->pattern = ".{4,}";
        $Username->patten_help = "Usernames need to be at least 8 characters.";
        $Username->validationEvent = "BreadUserSystem.ValidateUsername";
        $Username->validationModule = "BreadUserSystem";
        
        $Password->label = "Password";
        $Password->required = true;
        $Password->type = "password";
        $Password->value = "password";
        $Password->informationKey = "password";
        $Password->multiuser = false;
        $Password->pattern = ".{8,}";
        $Password->patten_help = "Passwords need to be at least 8 characters.";
        $Password->validationEvent = "BreadUserSystem.ValidatePassword";
        $Password->validationModule = "BreadUserSystem";
        
        $Name->label = "Name";
        $Name->required = false;
        $Name->type = "text";
        $Name->informationKey = "Name";
        $Name->multiuser = false;
        
        $Email->label = "E-Mail";
        $Email->required = false;
        $Email->type = "email";
        $Email->informationKey = "EMail";
        $Email->multiuser = false;
        
        $Biography->label = "Biography";
        $Biography->required = false;
        $Biography->type = "text";
        $Biography->informationKey = "Biography";
        $Biography->multiuser = true;
        
    }
    public $hiddenUsers = array();
    public $usereditForm;
    public $informationKeysToShowInTable = array("Name","EMail");
}

class BUSUserInformationField extends \Bread\Structures\BreadFormElement{
    public $informationKey = "";
    public $multiuser = false;
    public $validationEvent = false;
    public $validationModule = false;
    public $entryEvent = false;
    public $entryModule = false;
}
