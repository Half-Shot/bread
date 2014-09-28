<?php

/*
 * The MIT License
 *
 * Copyright 2014 will.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Bread\Themes;

/**
 * Description of breadxml
 *
 * @author will
 */
use Bread\Site as Site;
class BreadXML {
    private $xsldoc;
    private $variables = array();
    const ELEMENT_VARIABLE = "variable";
    function __construct($XSLFile = false) {
        if ($XSLFile) {
            $this->loadXSLFile($XSLFile);
        } else {
            $this->xsldoc = new \DOMDocument('1.0', 'utf-8');
        }
    }
    
    public function loadXSLFile($XSLFile)
    {
        $this->xsldoc = new \DOMDocument();
        $this->xsldoc->load($XSLFile);
    }
    
    public function convertObjtoElement($obj,\DOMNode $root,\DOMDocument $doc,$id = NULL)
{
        $en = $id;
        if($en == NULL)
           $en = self::ELEMENT_VARIABLE;
        $objRoot = $doc->createElement($en);
        if(is_object($obj) || is_array($obj))
        {
            if($id !== NULL)
                $objRoot->setAttribute ("id", $id);
            foreach($obj as $objtype => $value){
                if(is_numeric($objtype))
                    $objtype = NULL;
                $objRoot->appendChild($this->convertObjtoElement($value,$objRoot,$doc,$objtype));
            }
            return $objRoot;
        }
        else
        {
            if(is_string($obj)){
                $objRoot->appendChild ($doc->createTextNode ($obj));
            }
            elseif(is_bool($obj))
            {
                $objRoot->setAttribute("type", "xs:boolean");
                if($obj){
                    $objRoot->nodeValue = 1;
                }
                else{
                    $objRoot->nodeValue = 0;
                } 
            }
            else
            {
                $objRoot->nodeValue = (string)$obj;
            }
            if($id !== NULL)
                $objRoot->setAttribute ("id", $id);
            return $objRoot;
        }
    }
    
    public function GetHTMLOfElement($ElementId,$Variables = array(),$ElementType = "telement")
    {
        //Allows layouts to set arguments for theme objects
        if(array_key_exists(0,$Variables) && array_key_exists("_inner",$Variables)){
            $Variables = (array)$Variables[0];
        }
        $xmldoc = new \DOMDocument('1.0', 'utf-8');
        $root = $xmldoc->createElement("root");
        $xmldoc->appendChild($root);
        $elementStuff = $xmldoc->createElement($ElementType);
        $elementStuff->setAttribute("id", $ElementId);
        $elementStuff->appendChild($this->convertObjtoElement($Variables,$elementStuff,$xmldoc));
        $root->appendChild($elementStuff);
        $proc = new \XSLTProcessor();
        $proc->importStylesheet($this->xsldoc);
        $output = $proc->transformToXML($xmldoc);
        $properoutput = htmlspecialchars_decode($output);
        return $properoutput;
    }
}
