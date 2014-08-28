<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Utilitys as Util;
class BreadContentSystem extends Module
{
    const CHUNKSIZE = 64000;
    private $settings;
    function __construct($manager,$name)
    {
        parent::__construct($manager,$name);
    }
    
    function Setup(){
        if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadCoreSettings.Write")){
            $this->manager->UnregisterModule($this->name);
        }
        $this->settings = Site::$settingsManager->RetriveSettings("breadcontentsystem#settings.json",false,new BreadContentSystemSettings());
        $this->settings = Util::CastStdObjectToStruct($this->settings,"Bread\Modules\BreadContentSystemSettings");
        Site::$settingsManager->SaveSetting($this->settings, "breadcontentsystem#settings.json");
    }
    
    function GetContent($contentID){
        
    }
    
    function onDropSubmit(){
        return false;
    }
    
    function DrawUploader(){
        Site::AddScript(Site::ResolvePath("%user-modules/BreadContentSystem/js/dropzone.min.js"), "Dropzone", true);
        Site::AddScript(Site::ResolvePath("%user-modules/BreadContentSystem/js/contentUpload.js"), "BreadContentSystem", true);
        foreach($this->settings->allowedMimes as $type){
            Site::AddRawScriptCode("window.acceptedTypes.push('".$type."')", true);
        }
        foreach($this->settings->maxfilesize as $mimetype => $size){
            $realmimetype = str_replace("_","/",$mimetype);
            Site::AddRawScriptCode("window.maxfilesize.set('".$realmimetype."',".$size.")", true);
        }
        Site::AddRawScriptCode("window.chunksize = " . BreadContentSystem::CHUNKSIZE, true);
        $UploadPanel = new \Bread\Structures\BreadPanel();
        $UploadPanel->title = "Upload Content";
        
        //Queue
        $Body = $this->manager->FireEvent("Theme.Layout.Well",array("small"=>0,"id"=>"uploadZone-thumbnail"));
        //Dropzone
        $Body .= $this->manager->FireEvent("Theme.Layout.Well",array("small"=>0,"id"=>"content-dropzone"));
        
        //Template
        
        //TemplateButtons
        
        $StartUploadButton = new \Bread\Structures\BreadFormElement();
        $StartUploadButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $StartUploadButton->value = "Upload";
        $StartUploadButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Success")  . " uploadZoneTemplateButtons";
        $StartUploadButton->class .= " uploadZone-templateUploadButton";
        
        $CancelUploadButton = new \Bread\Structures\BreadFormElement();
        $CancelUploadButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $CancelUploadButton->value = "Cancel";
        $CancelUploadButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Warning")  . " uploadZoneTemplateButtons";
        $CancelUploadButton->class .= " uploadZone-templateCancelButton";
        
        //$DeleteUploadButton = new \Bread\Structures\BreadFormElement();
        //$DeleteUploadButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        //$DeleteUploadButton->value = "Delete";
        //$DeleteUploadButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Danger")  . " uploadZoneTemplateButtons";
        //$DeleteUploadButton->class .= " uploadZone-templateTrashButton";
        
        $ButtonToolbar = $this->manager->FireEvent("Theme.Layout.ButtonGroup", $this->manager->FireEvent("Theme.InputElement",$StartUploadButton) . $this->manager->FireEvent("Theme.InputElement",$CancelUploadButton)); //. $this->manager->FireEvent("Theme.InputElement",$DeleteUploadButton));
        
        $MediaObject = new \Bread\Structures\BreadThemeCommentStructure();
        $MediaObject->header = '<p class="name" data-dz-name></p>';
        $MediaObject->body = '<p class="size" data-dz-size></p>' . $ButtonToolbar . '</br>' . ' </br><progress data-dz-uploadprogress id="uploadZoneProgress" value="0" max="100"></progress> ' ;
        $MediaObject->thumbnail = 'Icon';
        $MediaObject->id = 'uploadZone-previewtmp';
        $Body .= $this->manager->FireEvent("Theme.Comment",$MediaObject);
        
        //Upload
        $SelectButton = new \Bread\Structures\BreadFormElement();
        $SelectButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $SelectButton->value = "Select a file";
        $SelectButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Info") . " uploadZoneButtons";
        $SelectButton->id = "uploadZone-selectbutton";
        
        $UploadButton = new \Bread\Structures\BreadFormElement();
        $UploadButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $UploadButton->value = "Upload File";
        $UploadButton->readonly = true; 
        $UploadButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Success"). " uploadZoneButtons";
        $UploadButton->id = "uploadZone-uploadbutton";
        $UploadButton->onclick = "uploadZone.enqueueFiles(myDropzone.getFilesWithStatus(Dropzone.ADDED);";
        
        $Body .= $this->manager->FireEvent("Theme.Layout.ButtonGroup", $this->manager->FireEvent("Theme.InputElement",$SelectButton) . $this->manager->FireEvent("Theme.InputElement",$UploadButton));
        $UploadPanel->body = $Body; 
        $HTML = $this->manager->FireEvent("Theme.Panel",$UploadPanel);
        return $HTML;
    }
    
    function DetectMimeType($file){
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $file);
        finfo_close($finfo);
        return $mimetype;
    }
    
    function BeginUpload(){
        $type = $_REQUEST["type"];
        if(!in_array($type,$this->settings->allowedMimes)){
            return 0;
        }
        $totalsize = $_REQUEST["size"];
        $ID = hash("sha256",$type . $totalsize);
        mkdir(Site::ResolvePath('%system-temp/' . $ID));
        return $ID;
    }
    
    function UploadChunk(){
        $size = $_REQUEST["size"];
        $actualsize = $_REQUEST["actualsize"];
        $id = $_REQUEST["id"];
        $data = $_REQUEST["data"];
        $name = $_REQUEST["name"];
        $ChunkN = $_REQUEST["chunkN"];
        //Check the file is accepting more data.
        if(BreadContentSystem::CHUNKSIZE < $size){
            //Chunk too big.
            return 0;
        }
        $directory = Site::ResolvePath('%system-temp/' . $id);
        if(!file_exists($directory)){
            return -1; //No upload was ever started, probably a hack.
        }
        $data = base64_decode($data);
        file_put_contents($directory . "/" . $ChunkN, $data, FILE_APPEND);
        $ChunksRequired = ceil($actualsize / BreadContentSystem::CHUNKSIZE);
        $Chunks = array_diff(scandir($directory), array('..', '.'));
        if(count($Chunks) == $ChunksRequired){
            //Build File
            $FinalFileData = "";
            natsort($Chunks);
            foreach($Chunks as $Chunk){
                $fdata = file_get_contents($directory . "/" . $Chunk);
                $FinalFileData .= $fdata;
            }
            Util::RecursiveRemove($directory);
            file_put_contents($directory, $FinalFileData);
            //Less than equal amount of bytes, the file must have finished.
            $mime = $this->DetectMimeType($directory);
            if(in_array($mime, $this->settings->allowedMimes)){
                //Save it properly
                //Index and check it doesn't already exist.
                $newpath = Site::ResolvePath('%user-content/content/' . $mime . '/' . $name);
                mkdir(Site::ResolvePath('%user-content/content/' . $mime . '/'), 0777, true);
                rename($directory,$newpath);
                unlink($directory);
                return $newpath;
            }
            else{
                //Error and delete
                unlink(Site::ResolvePath('%system-temp/' . $id));
            }
        }
        else{
            return 1;
        }
    }
}

class BreadContentSystemSettings{
    public $allowedMimes = ['image/png'];
    public $maxfilesize = ['image_png'=>3000000];
}
