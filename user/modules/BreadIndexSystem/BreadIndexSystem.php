<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Structures\BreadLinkStructure as BreadLinkStructure;
class BreadIndexSystem extends Module
{
    private $settings;
    private $navbar;
    function __construct($manager,$name)
    {
        parent::__construct($manager,$name);
    }
    
    function Setup()
    {
        $rootSettings = Site::$settingsManager->FindModuleDir("breadindexsystem");
        Site::$settingsManager->CreateSettingsFiles($rootSettings . "index.json", array());
        $this->navbar = Site::$settingsManager->RetriveSettings($rootSettings . "index.json");
        
        Site::$settingsManager->CreateSettingsFiles($rootSettings . "settings.json", new BreadIndexSystemSettings);
        $this->settings = Site::CastStdObjectToStruct(Site::$settingsManager->RetriveSettings($rootSettings . "settings.json"), "\Bread\Modules\BreadIndexSystemSettings");
    }
    
    function GetPages()
    {
        //Filter hidden
        $pages = array();
        foreach($this->navbar as $page){
            if(isset($page->permissionrequired))
                if(!$this->manager->FireEvent("Bread.Security.GetPermission",$page->permissionrequired))
                    continue;
            if(isset($page->hidden))
                if($page->hidden)
                    continue;
            $pages[] = $page;
        }
        return $pages;
    } 
    
    function StringToTerms($string)
    {
        if(substr_count($string, '"') > 1){
            $terms = explode('"', $string); //Split quoted into own terms.
        }
        else {
            $terms = array($string);
        }
        $FinalSearchTerms = array();
        //Odd values are quoted, even are not.
        for($i=floatval(0);$i < count($terms);$i++){
            if($i % 2 === 0){
                $FinalSearchTerms = array_merge(explode(" ",$terms[$i]),$FinalSearchTerms );
            }
            else
            {
                $FinalSearchTerms[] = $terms[$i];
            }
        }//Split by space.
        $FinalSearchTerms = Site::array_clean($FinalSearchTerms);
        return $FinalSearchTerms;
    }
    
    function Search(\Bread\Structures\BreadSearchRequest $searchRequest)
    {
        $ModResults = $this->manager->FireEvent("Bread.GetAllPages",null,false);
        $results = Site::MashArraysToSingleArray($ModResults);
        if(empty($searchRequest->SearchTerm))
            return $results;
        if(empty($results))
            return -1;
        $cleanResults = array();
        $weight = array();
        foreach($results as $result){
            $weight[] = 0;
            $cleanResults[] = clone $result;
        }
        //Remove punctuation
        for($i=0;$i<count($results);$i++){
            $searchItem = $results[$i];
            $searchItem->Name = Site::RemovePunctuation($searchItem->Name);
            for($v=0;$v<count($searchItem->Tags);$v++)
                $searchItem->Tags[$v] = Site::RemovePunctuation($searchItem->Tags[$v]);
            for($v=0;$v<count($searchItem->Categorys);$v++)
                $searchItem->Categorys[$v] = Site::RemovePunctuation($searchItem->Categorys[$v]);
            $searchItem->Author = Site::RemovePunctuation($searchItem->Author);
            $searchItem->Content = Site::RemovePunctuation($searchItem->Content);
            if($searchRequest->IgnoreCase){
                $searchItem = $results[$i];
                $searchItem->Name = strtolower($searchItem->Name);
                for($v=0;$v<count($searchItem->Tags);$v++)
                    $searchItem->Tags[$v] = strtolower($searchItem->Tags[$v]);
                for($v=0;$v<count($searchItem->Categorys);$v++)
                    $searchItem->Categorys[$v] = strtolower($searchItem->Categorys[$v]);
                $searchItem->Author = strtolower($searchItem->Author);
                $searchItem->Content = strtolower($searchItem->Content);
            }
        }
        
        //Search by Terms
        if($searchRequest->IgnoreCase){
            $TermString = strtolower ($searchRequest->SearchTerm);
        }
        else
        {
            $TermString = $searchRequest->SearchTerm;
        }
        $Terms = $this->StringToTerms($TermString);
        foreach($Terms as $Term){
            foreach($results as $pn => $page){
                /* @var $page  \Bread\Structures\BreadSearchItem */
                //Name
                foreach(explode(" ", $page->Name) as $word)
                    if($Term == $word)
                        $weight[$pn] += $searchRequest->Weight_Name; 
                //Tags
                foreach($page->Tags as $Tag)
                    if($Term == $Tag)
                        $weight[$pn] += $searchRequest->Weight_Tags;
                //Categorys
                foreach($page->Categorys as $Category)
                    if($Term == $Category)
                        $weight[$pn] += $searchRequest->Weight_Categorys;
                if($Term == $page->Author)
                    $weight[$pn] += $searchRequest->Weight_Author;
                //Content
                foreach(explode(" ", $page->Content) as $word)
                {
                    if($Term == $word)
                        $weight[$pn] += $searchRequest->Weight_Content;
                }
            }
        }
        unset($results); //Clean up a bit.
        arsort($weight);
        $FinalResults = array();
        foreach($weight as $index => $weight){
            if($weight < $this->settings->MinWeight)
                continue;
            $FinalResults[] = $cleanResults[$index];
        }
        if(empty($FinalResults))
            return -1;
        
        return $FinalResults;
    }
    
    function SearchPage($args)
    {
        $SearchRequest = new \Bread\Structures\BreadSearchRequest;
        if(is_array($args)){
            $args = get_object_vars($args[0]);
        }
        $args += (array)Site::getRequest()->arguments;
        $args = (object)$args;
        if(isset($args->name)){
            $SearchRequest->Weight_Name      = $args->name;
        }
        else
        {
            $SearchRequest->Weight_Name = $this->settings->Weight_Name;
        }
        
        if(isset($args->tags)){
            $SearchRequest->Weight_Tags      = $args->tags;
        }
        else
        {
            $SearchRequest->Weight_Tags = $this->settings->Weight_Tags;
        }
        if(isset($args->categorys)){
            $SearchRequest->Weight_Categorys = $args->categorys;
        }
        else
        {
            $SearchRequest->Weight_Categorys = $this->settings->Weight_Categorys;
        }
        if(isset($args->author)){
            $SearchRequest->Weight_Author    = $args->author;
        }
        else
        {
            $SearchRequest->Weight_Author = $this->settings->Weight_Author;
        }
        if(isset($args->content)){
            $SearchRequest->Weight_Content   = $args->content;
        }
        else
        {
            $SearchRequest->Weight_Content = $this->settings->Weight_Content;
        }
        
        if(isset($args->terms)){
            $SearchRequest->SearchTerm = str_replace("%20", " ", $args->terms);
        }
        
        $results = $this->manager->FireEvent("Bread.RunSearch",$SearchRequest);
        if($results === -1)
        {
            //No results
            $results = array();
        }
        $HTML = "";
        //Stats
        $listOfGrids = array();
        $cell_results = new \stdClass();
        $cell_results->body = "Your search had " . $this->manager->FireEvent("Theme.Badge",(string)count($results)) . " number of results.";
        $cell_results->size = 4;
        $listOfGrids[] = $cell_results;
        
        $cell_time = new \stdClass();
        $cell_time->offset = 4;
        $cell_time->body = "This request took ". $this->manager->FireEvent("Theme.Badge",(string)(Site::GetTimeSinceStart())) . " seconds to complete";
        $listOfGrids[] = $cell_time;
        
        $object = new \stdClass;
        $object->small = true;
        $object->value = $this->manager->FireEvent("Theme.Layout.Grid.HorizonalStack",array($listOfGrids));
        $HTML .= $this->manager->FireEvent("Theme.Layout.Well",$object);
        if(!empty($SearchRequest->SearchTerm)){
            $HTML .= $this->manager->FireEvent("Theme.Title",array("Search Results for",  $SearchRequest->SearchTerm));
        }
        else
        {
            $HTML .= $this->manager->FireEvent("Theme.Title",array("Post Index"));
        }
        //Results.
        foreach($results as $result)
        {
            $ResultObj = new \stdClass;
            $ResultObj->header = $result->Name;
            $ResultObj->headerurl = $result->Url;
            if(count_chars($result->Content) > 150)
            {
                $ResultObj->body = substr($result->Content, 0,150) . "...";
            }
            else
            {
                $ResultObj->body = $result->Content;
            }
            $HTML .= $this->manager->FireEvent("Theme.Comment",$ResultObj);
        }
        
        return $HTML;
    }
    
    function CreateSearchForm()
    {
        $Form = new \Bread\Structures\BreadForm;
        $Form->method = "GET";
        $Form->onsubmit = "";
        $Form->action = Site::getBaseURL();
        $Form->class = "navbar-form";
        $Form->standalone = false;
        
        $RequestElement = new \Bread\Structures\BreadFormElement;
        $RequestElement->type = "hidden";
        $RequestElement->name = "request";
        $RequestElement->value = $this->settings->SearchRequest;
        $Form->elements[] = $RequestElement;
        
        $SearchElement = new \Bread\Structures\BreadFormElement;
        $SearchElement->type = "search";
        $SearchElement->placeholder = "Search";
        $SearchElement->required = true;
        $SearchElement->name = "terms";
        $Form->elements[] = $SearchElement;
        
        $SubmitElement = new \Bread\Structures\BreadFormElement;
        $SubmitElement->type = "button";
        $SubmitElement->value = "Go";
        $SubmitElement->action = "submit";
        $Form->elements[] = $SubmitElement;  
        $HTML = $this->manager->FireEvent("Theme.Form",$Form);
        return $HTML;
    }
}

class BreadIndexSystemSettings{
    public $Weight_Name = 5;
    public $Weight_Tags = 2;
    public $Weight_Categorys = 1;
    public $Weight_Author = 0;
    public $Weight_Content = 1;
    public $IgnoreCase = true;
    public $MinWeight = 1;
    public $SearchRequest = "search";
    public $MaxResults = 25;
}