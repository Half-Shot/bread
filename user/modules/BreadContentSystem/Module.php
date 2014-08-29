<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Utilitys as Util;
class BreadContentSystem extends Module
{
    const CHUNKSIZE = 256000;
    private $settings;
    private $index;
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
        $this->index = Site::$settingsManager->RetriveSettings(Site::ResolvePath('%user-content/content/index.json'),false,new \stdClass());
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
            Site::AddRawScriptCode("window.maxfilesize.set('".$mimetype."',".$size.")", true);
        }
        Site::AddRawScriptCode("window.chunksize = " . BreadContentSystem::CHUNKSIZE, true);
        $UploadPanel = new \Bread\Structures\BreadPanel();
        $UploadPanel->title = "Upload Content";
        
        //Queue
        $Body = $this->manager->FireEvent("Theme.Layout.Well",array("small"=>0,"id"=>"uploadZone-thumbnail"));
        //Dropzone
        $Body .= $this->manager->FireEvent("Theme.Layout.Well",array("small"=>0,"id"=>"content-dropzone"));
        //Endzone
        $Body .= $this->manager->FireEvent("Theme.Layout.Well",array("small"=>0,"id"=>"content-finished"));
        
        
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
        $name = $_REQUEST["name"];
        if(!in_array($type,$this->settings->allowedMimes)){
            return 0;
        }
        $totalsize = $_REQUEST["size"];
        $ID = hash("sha256",$type . $totalsize);
        
        $File = new BreadContentSystemFile();
        $File->filename = $name;
        $File->size = $totalsize;
        $File->id = $ID;
        $File->mimetype = $type;
        $File->fileAcceptingData = true;
        $this->index->$ID = $File;
        
        mkdir(Site::ResolvePath('%system-temp/chunkfiles/' . $ID),0777,true);
        return $ID;
    }
    
    function ContentPanel(){
        return "<b>Placeholder for content button</b>";
    }
    
    function UploadChunk(){
        $size = $_REQUEST["size"];
        $id = $_REQUEST["id"];
        $data = $_REQUEST["data"];
        $ChunkN = $_REQUEST["chunkN"];
        $ReturnCode = 1;
        if(!isset($this->index->$id)){
            //File not found
            return 0;
        }
        $File = $this->index->$id;
        if($File->fileAcceptingData == false){
            return 0;
        }
        $directory = Site::ResolvePath('%system-temp/chunkfiles/' . $id);
        if(!file_exists($directory)){
            //Shoudln't really ever get here.
            return -1; //No upload was ever started, probably a hack.
        }
        $data = base64_decode($data);
        file_put_contents($directory . "/" . $ChunkN, $data, FILE_APPEND);
        $ChunksRequired = ceil($File->size / BreadContentSystem::CHUNKSIZE);
        $Chunks = array_diff(scandir($directory), array('..', '.'));
        if(count($Chunks) == $ChunksRequired){
            $File->fileAcceptingData = false;
            //Build File
            $FinalFileData = "";
            natsort($Chunks);
            foreach($Chunks as $Chunk){
                $fdata = file_get_contents($directory . "/" . $Chunk);
                $FinalFileData .= $fdata;
            }
            Util::RecursiveRemove($directory);
            //Check MD5s
            $MD5 = md5($FinalFileData);
            foreach($this->index as $OtherFile){
                if($OtherFile->md5 == $MD5){
                    //Duplicate detected!
                    $fileInfo = pathinfo($OtherFile->filename);
                    $newpath = Site::ResolvePath('%user-content/content/' . $mime . '/' . $OtherFile->id . "." . $fileInfo["extension"]);
                    unset($this->index->$id);
                    return $newpath; //Return the old file.
                }
            }
            $File->md5 = $MD5;
            file_put_contents($directory, $FinalFileData);
            //Less than equal amount of bytes, the file must have finished.
            $mime = $this->DetectMimeType($directory);
            if($mime !== $File->mimetype){
                unlink(Site::ResolvePath('%system-temp/' . $id));
                unset($this->index->$id);
                return 0;
            }
            if(in_array($mime, $this->settings->allowedMimes)){
                //Save it properly
                //Index and check it doesn't already exist.
                $fileInfo = pathinfo($File->filename);
                $newpath = Site::ResolvePath('%user-content/content/' . $mime . '/' . $id . "." . $fileInfo["extension"]);
                mkdir(Site::ResolvePath('%user-content/content/' . $mime . '/'), 0777, true);
                rename($directory,$newpath);
                unlink($directory);
                return $newpath;
            }
            else{
                //Error and delete
                unset($this->index->$id);
                unlink($directory);
                return 0;
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

class BreadContentSystemFile{
    public $filename = "";
    public $mimetype = "";
    public $size = 0;
    public $md5 = "";
    public $id = "";
    public $fileAcceptingData = false;
}
