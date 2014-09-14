<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Utilitys as Util;
class BreadContentSystem extends Module
{
    const CHUNKSIZE = 96000;
    const CHUNKTIMEOUT = 15;
    private $settings;
    private $index;
    function __construct($manager,$name)
    {
        parent::__construct($manager,$name);
    }
    
    function Setup(){
        $this->settings = Site::$settingsManager->RetriveSettings("breadcontentsystem#settings.json",false,new BreadContentSystemSettings());
        $this->settings = Util::CastStdObjectToStruct($this->settings,"Bread\Modules\BreadContentSystemSettings");
        $this->index = Site::$settingsManager->RetriveSettings(Site::ResolvePath('%user-content/content/index.json'),false,new \stdClass());
    }
    
    function GetContent($contentID){
        $File = $this->index->$contentID;
        if(!isset($this->index->$contentID)){
            return 0;
        }
        $fileInfo = pathinfo($File->filename);
        $path = Site::ResolvePath('%user-content/content/' . $File->mimetype . '/' . $contentID . "." . $fileInfo["extension"]);
        $File->data = file_get_contents($path);
        return $File;
    }
    
    function GetContentURL($contentID){
        if(array_key_exists("contentid", $_REQUEST)){
            $contentID = $_REQUEST["contentid"];
        }
        if(!isset($this->index->$contentID)){
            return 0;
        }
        $File = $this->index->$contentID;
        $fileInfo = pathinfo($File->filename);
        $path = Site::ResolvePath('%user-content/content/' . $File->mimetype . '/' . $contentID . "." . $fileInfo["extension"]);
        return $path;
    }
    
    function onDropSubmit(){
        return false;
    }
    
    function DrawUploader(){
        if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadContentSystem.CanUpload")){
            return false;
        }
        Site::AddScript(Site::ResolvePath("%user-modules/BreadContentSystem/js/dropzone.min.js"), "Dropzone", true);
        Site::AddScript(Site::ResolvePath("%user-modules/BreadContentSystem/js/contentUpload.js"), "BreadContentSystem", true);
        foreach($this->settings->allowedMimes as $type){
            Site::AddRawScriptCode("window.acceptedTypes.push('".$type."');", true);
        }
        foreach($this->settings->maxfilesize as $mimetype => $size){
            Site::AddRawScriptCode("window.maxfilesize['".$mimetype."'] = ".$size.";", true);
        }
        Site::AddRawScriptCode("window.chunksize = " . BreadContentSystem::CHUNKSIZE, true);
        
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
        $MediaObject->thumbnail = '';
        $MediaObject->id = 'uploadZone-previewtmp';
        $Body .= $this->manager->FireEvent("Theme.Comment",$MediaObject);
        
        //Upload
        $SelectButton = new \Bread\Structures\BreadFormElement();
        $SelectButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $SelectButton->value = "Select a file";
        $SelectButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Info") . " uploadZoneButtons";
        $SelectButton->id = "uploadZone-selectbutton";
        
        $Body .= $this->manager->FireEvent("Theme.InputElement",$SelectButton);
        return $Body;
    }
    
    function DetectMimeType($file){
        if(!extension_loaded("fileinfo")){
            return false;//TODO:Find an alternative for fileinfo less servers.
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $file);
        finfo_close($finfo);
        return $mimetype;
    }
    
    function BeginUpload(){
        if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadContentSystem.CanUpload")){
            return false;
        }
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
        $File->lastchunktime = time();
        
        mkdir(Site::ResolvePath('%system-temp/chunkfiles/' . $ID),0777,true);
        return $ID;
    }
    
    function ContentPanel($args){
        if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadContentSystem.UseFiles")){
            return false;
        }
        $HTML = "";
        $UploadModal = new \Bread\Structures\BreadModal();
        $UploadModal->width = 50;
        $UploadModal->id = "breadContentUploadModal";
        $UploadModal->title = "Upload Content...";
        $UploadModal->body = Site::$moduleManager->FireEvent("Bread.ShowUploader");
        
        $CloseButton = new \Bread\Structures\BreadFormElement();
        $CloseButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $CloseButton->value = "Close";
        $CloseButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Info");
        $CloseButton->onclick = "$('#breadContentUploadModal').modal('hide');";
        
        $UploadModal->footer = Site::$moduleManager->FireEvent("Theme.InputElement",$CloseButton);
        
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
        
        $Modal->body  .=  Site::$moduleManager->FireEvent("Theme.Layout.Grid.HorizontalStack",array($SourceButtonsCell)) . "<hr/>";
        
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
        $TypeButton->value = "Upload...";
        $TypeButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Info");
        $TypeButton->onclick = "$('#breadContentUploadModal').modal('show');";
        $ButtonStack .= Site::$moduleManager->FireEvent("Theme.InputElement",$TypeButton);
        //Hook for other types.
        
        $MajorTypesCell = new \stdClass();
        $MajorTypesCell->size = 2;
        $MajorTypesCell->body = $ButtonStack;
        
        
        $FileTable = new \Bread\Structures\BreadTableElement();
        $FileTable->class = " table-hover";
        $FileTable->id = "breadContentModal-fileTable";
        $FileTable->headingRow = new \Bread\Structures\BreadTableRow();
        $FileTable->headingRow->FillOutRow(array("Filename","Date","Size","Type","",""));
        
        
        $SelectButton = new \Bread\Structures\BreadFormElement();
        $SelectButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $SelectButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Primary");
        $SelectButton->value = "Select";
        $SelectButton->toggle = true;
        $SelectButton->hidden = true;
        $SelectButton->id = "breadContentModal-selectButtonTemplate";
        
        $DeleteButton = new \Bread\Structures\BreadFormElement();
        $DeleteButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $DeleteButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Danger");
        $DeleteButton->value = $this->manager->FireEvent("Theme.Icon","trash") . " Delete";
        $DeleteButton->hidden = true;
        $DeleteButton->id = "breadContentModal-deleteButtonTemplate";
        
        Site::AddToBodyCode($this->manager->FireEvent("Theme.InputElement",$SelectButton));
        Site::AddToBodyCode($this->manager->FireEvent("Theme.InputElement",$DeleteButton));
        
        $FileBrowserCell = new \stdClass();
        $FileBrowserCell->body = Site::$moduleManager->FireEvent("Theme.Table",$FileTable);
        
        $Modal->body .= Site::$moduleManager->FireEvent("Theme.Layout.Grid.HorizontalStack",array($MajorTypesCell,$FileBrowserCell));
        
        $InsertButton = new \Bread\Structures\BreadFormElement();
        $InsertButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $InsertButton->value = "Insert Selected";
        $InsertButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Primary");
        $InsertButton->onclick = "breadContentModalinsertFiles();";
        
        $CloseButton = new \Bread\Structures\BreadFormElement();
        $CloseButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $CloseButton->value = "Close";
        $CloseButton->class = $this->manager->FireEvent("Theme.GetClass","Button.Info");
        $CloseButton->onclick = "$('#breadContentModal').modal('hide');";
        
        $Modal->footer = $this->manager->FireEvent("Theme.Layout.ButtonGroup",$this->manager->FireEvent("Theme.Button",$InsertButton) . $this->manager->FireEvent("Theme.Button",$CloseButton));
        
        //Button
        Util::CastStdObjectToStruct($args,"Bread\Structures\BreadFormElement");
        $args->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $args->onclick = "$('#breadContentModal').modal('show');";
        
        Site::AddToBodyCode($this->manager->FireEvent("Theme.Modal",$Modal));
        Site::AddToBodyCode($this->manager->FireEvent("Theme.Modal",$UploadModal));
        
        $HTML .= $this->manager->FireEvent("Theme.Button",$args);
        return $HTML;
    }
    
    function FindFilesByMajorType(){
        if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadContentSystem.UseFiles")){
            return false;
        }
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
    
    function OptimiseCache(){
        if((time() - $this->settings->lastoptimise) > $this->settings->optimiseinterval)
        {
            $this->settings->lastoptimise = time();
            //Check all files exist
            $filesCleaned = 0;
            foreach($this->index as $id => $File){
                if(!$File->fileAcceptingData || ($File->fileAcceptingData && time() - $File->lastchunktime > BreadContentSystem::CHUNKTIMEOUT)){
                    $fileInfo = pathinfo($File->filename);
                    $path = Site::ResolvePath('%user-content/content/' . $File->mimetype . '/' . $id . "." . $fileInfo["extension"]);
                    if(!file_exists($path)){
                        unset($this->index->$id);
                    }
                }
                $filesCleaned += 1;
            }
            Site::$settingsManager->ChangeSetting("breadcontentsystem#settings.json", $this->settings);
            Site::$Logger->writeMessage("Cleaned up cache, removed " . $filesCleaned . " entries.", $this->name);
        }
    }
    
    function UploadChunk(){
        if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadContentSystem.CanUpload")){
            return false;
        }
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
        $File->lastchunktime = time();
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
            if($mime === false){
                //Terrible bad method for finding file mimetypes when we are left with no option. Really insecure.
                $mime = mime_content_type($File->filename);
            }
            if($mime !== $File->mimetype && explode("/",$mime)[1] !== $File->minortype){
                Site::$Logger->writeError("Server file type did not match client file type!",  \Bread\Logger::SEVERITY_MEDIUM, $this->name);
                Site::$Logger->writeError("    Filename: " . $File->filename,  \Bread\Logger::SEVERITY_MEDIUM, $this->name);
                Site::$Logger->writeError("    Mimetype: " . $File->mimetype,  \Bread\Logger::SEVERITY_MEDIUM, $this->name);
                unlink(Site::ResolvePath('%system-temp/' . $id));
                unset($this->index->$id);
                return 3;
            }
            if(in_array($mime, $this->settings->allowedMimes)){
                //Save it properly
                //Index and check it doesn't already exist.
                Site::$Logger->writeError("New file added to content: "     ,  \Bread\Logger::SEVERITY_MESSAGE               , $this->name);
                Site::$Logger->writeError("    Filename: " . $File->filename,  \Bread\Logger::SEVERITY_MESSAGE               , $this->name);
                Site::$Logger->writeError("    Mimetype: " . $File->mimetype,  \Bread\Logger::SEVERITY_MESSAGE               , $this->name);
                $fileInfo = pathinfo($File->filename);
                $newDirectory = Site::ResolvePath('%user-content/content/' . $File->mimetype . '/');
                $newpath = $newDirectory . $id . "." . $fileInfo["extension"];
                if(!file_exists($newDirectory)){
                    mkdir($newDirectory, 0777, true);
                }
                rename($directory,$newpath);
                return $newpath;
            }
            else{
                Site::$Logger->writeError("Mimetype was not allowed!",  \Bread\Logger::SEVERITY_MEDIUM, $this->name);
                Site::$Logger->writeError("    Filename: " . $File->filename,  \Bread\Logger::SEVERITY_MEDIUM, $this->name);
                Site::$Logger->writeError("    Mimetype: " . $File->mimetype,  \Bread\Logger::SEVERITY_MEDIUM, $this->name);
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

    function DeleteContent($args){
        if(is_string($args)){
            $id = $args;
        }
        else{
            $id = $_REQUEST["id"];
        }

        if(!isset($this->index->$id) || !$this->manager->FireEvent("Bread.Security.GetPermission","BreadContentSystem.CanUpload")){
            //File not found
            return 0;
        }

        $File = $this->index->$id;

        $fileInfo = pathinfo($File->filename);
        $path = Site::ResolvePath('%user-content/content/' . $File->mimetype . '/' . $id . "." . $fileInfo["extension"]);
        $worked = unlink($path);
        if($worked){
            unset($this->index->$id);
        }
        else{
            return 0;
        }
        return 1;
    }
}
class BreadContentSystemSettings{
    public $allowedMimes = ['image/png',
                            'image/jpeg',
                            'image/pjpeg',
                            'audio/mpeg3',
                            'audio/mpeg',
                            'video/mpeg3',
                            'audio/ogg',
                            'application/ogg'];
    
    public $maxfilesize = ['image/png'          =>   5000000,
                           'image/jpeg'         =>   5000000,
                           'image/pjpeg'        =>   5000000,
                           'video/mpeg3'        =>   50000000,
                           'audio/mpeg3'        =>   5000000,
                           'audio/mpeg'         =>   5000000,
                           'audio/ogg'          =>   5000000,
                           'application/ogg'    =>   5000000];
    public $optimiseinterval = 15; /* Seconds between cleanups */
    public $lastoptimise = 0;
}
