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
        
        $File = new \Bread\Structures\BreadFile();
        $File->filename = $name;
        $File->size = $totalsize;
        $File->id = $ID;
        $File->mimetype = $type;
        $File->time = time();
        $File->fileAcceptingData = true;
        $types = explode("/", $type);
        $File->majortype = $types[0];
        $File->minortype = $types[1];
        $this->index->$ID = $File;
        
        mkdir(Site::ResolvePath('%system-temp/chunkfiles/' . $ID),0777,true);
        return $ID;
    }
    
    function ContentPanel($args){
        $HTML = "";
        $Modal = new \Bread\Structures\BreadModal();
        $Modal->id = "breadContentModal";
        $Modal->title = "Bread Content Browser";
        
        //Sources

        $LocalSources = new \Bread\Structures\BreadFormElement();
        $LocalSources->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $LocalSources->value = "Local Files";
        $LocalSources->class = $this->manager->FireEvent("Theme.GetClass","Button.Default");
        
        //Hook to other services
        
        $SourceButtonsCell = new \stdClass();
        $SourceButtonsCell->size = 6;
        $SourceButtonsCell->body = Site::$moduleManager->FireEvent("Theme.Layout.ButtonGroup",Site::$moduleManager->FireEvent("Theme.InputElement",$LocalSources));
        
        $Modal->body  .=  Site::$moduleManager->FireEvent("Theme.Layout.Grid.HorizonalStack",array($SourceButtonsCell)) . "<hr/>";
        
        //Types avaiable.
        $ButtonStack = "<h4>Type</h4>";
        $TypesFound = array();
        $TypeButton = new \Bread\Structures\BreadFormElement();
        $TypeButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $TypeButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Default");
        foreach($this->settings->allowedMimes as $mimetype){
            $types = explode("/", $mimetype);
            $major = $types[0];
            if(!in_array($major, $TypesFound)){
                $TypesFound[] = $major;
            }
            else{
                continue;
            }
            $TypeButton->value = $major;
            $TypeButton->onclick = "breadContentModalSetType('".$major."');";
            $ButtonStack .= Site::$moduleManager->FireEvent("Theme.InputElement",$TypeButton);
        }
        $ButtonStack .= "<hr>";
        $TypeButton->value = "Upload New...";
        $TypeButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Info");
        $TypeButton->onclick = "breadContentModalUploadDialog();";
        $ButtonStack .= Site::$moduleManager->FireEvent("Theme.InputElement",$TypeButton);
        //Hook for other types.
        
        $MajorTypesCell = new \stdClass();
        $MajorTypesCell->size = 2;
        $MajorTypesCell->body = $ButtonStack;
        
        
        $FileTable = new \Bread\Structures\BreadTableElement();
        $FileTable->class = " table-hover";
        $FileTable->headingRow = new \Bread\Structures\BreadTableRow();
        $FileTable->headingRow->FillOutRow(array("Filename","Date","Size","Type",""));
        
        $MessageRow = new \Bread\Structures\BreadTableRow();
        $MessageRow->FillOutRow(array("Select","a","type","of","item."));
        
        $FileTable->rows[] = $MessageRow;
        
        $SelectButton = new \Bread\Structures\BreadFormElement();
        $SelectButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $SelectButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Default");
        $SelectButton->value = "Insert";
        $SelectButton->toggle = true;
        $SelectButton->hidden = true;
        $SelectButton->id = "breadContentModal-selectButtonTemplate";
        
        Site::AddToBodyCode($this->manager->FireEvent("Theme.InputElement",$SelectButton));
        
        $FileBrowserCell = new \stdClass();
        $FileBrowserCell->body = Site::$moduleManager->FireEvent("Theme.Table",$FileTable);
        
        $Modal->body .= Site::$moduleManager->FireEvent("Theme.Layout.Grid.HorizonalStack",array($MajorTypesCell,$FileBrowserCell));
        
        $CloseButton = new \Bread\Structures\BreadFormElement();
        $CloseButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $CloseButton->value = "Close";
        $CloseButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Primary");
        $CloseButton->onclick = "$('#breadContentModal').modal('hide');";
        
        $Modal->footer = $this->manager->FireEvent("Theme.Layout.ButtonGroup", $this->manager->FireEvent("Theme.InputElement",$CloseButton));
        
        //Button
        Util::CastStdObjectToStruct($args,"Bread\Structures\BreadFormElement");
        $args->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $args->onclick = "$('#breadContentModal').modal('show');";
        Site::AddToBodyCode($this->manager->FireEvent("Theme.Modal",$Modal));
        $HTML .= $this->manager->FireEvent("Theme.Button",$args);
        return $HTML;
    }
    
    function FindFilesByMajorType(){
        $majortype = $_REQUEST["majortype"];
        $Files = array();
        foreach($this->index as $file){
            $types = explode("/", $file->mimetype);
            $major = $types[0];
            if($major == $majortype){
                $Files[] = $file;
            }
        }
        return $Files;
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
