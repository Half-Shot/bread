<?php

/*
 * The MIT License
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

namespace Bread;

/**
 * Description of Utilitys
 *
 * @author will
 */

use Bread\Site as Site;
use Bread\Structures\BreadRequestData as BreadRequestData;
use Bread\Structures\BreadRequestCommand as BreadRequestCommand;
class Utilitys {
        /**
        * Splits a string path up and locates wildcard paths such as %user-themes
        * and creates the correct path.
        * @param string $path 
        * @return type
        */
        public static function ResolvePath($path)
        {
            return Site::ResolvePath($path);
        }
        
        /**
        * Converts a URL into a array of parameters and the base url.
        * @param type $url
        */
        public static function DigestURL($url)
        {
            
            $parts = \explode("?",$url);
            $baseURL = $parts[0];
            if(count($parts) > 1){
                $parts = \explode("&",$parts[1]);
            }
            $returnedArray = array();
            $returnedArray["BASEURL"] = $baseURL;
            foreach($parts as $part)
            {
               $pair = \explode("=",$part);
               if(count($pair) > 1)
                $returnedArray[$pair[0]] = $pair[1];
               else
                $returnedArray[$pair[0]] = False;
            }
            return $returnedArray;
        }
        
        /**
        * Create a URL from a baseurl and a array of params.
        * @param type $baseurl The base url of the site. Use False to use the current site baseurl.
        * @param type $params The array of params to append to the url. Leave as a blank array for none.
        * @return string The URL
        */
        public static function CondenseURLParams($baseurl,$params)
        {
            if(!$baseurl)
                $baseurl = Site::getBaseURL();
            
            $url = $baseurl;
            if(array_count_values($params) < 1)
                return $url;
            $key = array_keys($params)[0];
            $url .= "?" . $key . "=" .$params[$key];
            unset($params[$key]);
            if(array_count_values($params) < 1)
                return $url;
            foreach ($params as $key => $value)
            {
                $url .= "&" . $key . "=" . $value;
            }
            return $url;
        }
        
        /**
        * Gets the seconds of time since PHP got the request.
        * @param int $dec The decimal time to account to.
        * @return float Microsecond Time.
        */
        public static function GetTimeSinceStart($dec = 3)
        {
            return Site::GetTimeSinceStart($dec);
        }
        
        /**
        * Cast a standard object (say a unserialzed object) into its proper object.
        */
        public static function CastStdObjectToStruct($obj,$type)
        {
            $newObj = new $type;
            
            foreach((array)$obj as $key =>$value)
                if(isset($newObj->$key))
                    $newObj->$key = $value;
            
            return $newObj;
        }
        
        /**
         * Find and return the operator in a string.
         * -2 : <=
         * -1 : <
         *  0 : ==
         *  1 : >
         *  2 : >=
         * @param \string $string
         * @return int
         */
        public static function findOperator($string)
        {
            if(is_numeric($string[0])){
                return 0; // Straightforward ==
            }
            elseif ($string[0] == '<') {
                if($string[1] == '='){
                    return -2; //<=
                }
                else
                {
                    return -1; //<
                }
            }
            elseif($string[0] == '>'){
                if($string[1] == '=')
                {
                    return 1; //>
                }
                else
                {
                    return 2; //>=
                }
            }
        }
        
        /**
        * Merges 2 objects.
        * @param object $objA The least important object, will be overwritten.
        * @param object $objB The more important object, will override keys.
        * @return type
        */
        public static function ObjMerge($objA,$objB)
        {
            $A_finalObj = array();
            $A_objA = (array)$objA;
            $A_objB = (array)$objB;
            $A_finalObj = \array_replace_recursive($A_objA,$A_objB);
            $finalObj = (object)$A_finalObj;
            return $finalObj;
        }
        
        /**
         * Sets the index of each element by one of its propertys.
         * @param array $array
         * @param string $propName
         * @return array
         */
        public static function ArraySetKeyByProperty($array,$propName)
        {
           $newArray = array();
           foreach($array as $item)
           {
               $newArray[$item->$propName] = $item;
           }
           return $newArray;
        }
        
        /**
        * Return a string value from a string which has a mix of letters and
        * numbers.
        * @param \string $string
        */
        public static function filterNumeric($string)
        {
            $numeric = "";
            foreach(str_split($string) as $char){
                if(is_numeric($char) || $char == '.')
                    $numeric .= $char;
            }
            return $numeric;
        }
        
        /**
        * Looks for a file in the common user paths.
        * Ordered by layout, theme and resource.
        * Useful for layouts overriding.
        * @param type $filepath
        * @return string
        * @throws Exception
        */
        public static function FindFile($filepath)
        {
            if(mb_substr($filepath, 0, 4) == "http")//Is remote.
                return $filepath;
            $path = static::ResolvePath("%user-layouts/" . $filepath);
            if(file_exists($path))
                return $path;
            $path = static::ResolvePath("%user-themes/" . $filepath);
            if(file_exists($path))
                return $path; 
            $path = static::ResolvePath("%user-resource/" . $filepath);
            if(file_exists($path))
                return $path;
            $path = static::ResolvePath("%user-modules/" . $filepath);
            if(file_exists($path))
                return $path;
            throw new \Exception;
        }
        /**
         * Converts a array of arrays into one single array.
         * @param array $arrays
         * @return array
         */
        public static function MashArraysToSingleArray($arrays)
        {
            $newArray = array();
            foreach($arrays as $array)
                $newArray += $array;
            return $newArray;
        }
        
        /**
         * Removes empty values from arrays.
         * @param array $haystack
         * @return array
         */
        public static function array_clean(array $haystack)
        {
            foreach ($haystack as $key => $value) {
                if (is_array($value)) {
                    $haystack[$key] = Site::array_clean($value);
                } elseif (is_string($value)) {
                    $value = trim($value);
                }

                if (!$value) {
                    unset($haystack[$key]);
                }
            }

            return $haystack;
        }
        
        /**
         * Removes punctuation from a string, leaving only letters and numbers.
         * @param string $string Input String
         * @return string
         */
        static function RemovePunctuation($string)
        {
            return trim( preg_replace( "/[^0-9a-z]+/i", " ", $string ) );
        }
        
        /**
         * Gets a path, splits it up and works it way backwards
         * @param type $path
         * @param type $start
         * @param type $end
         */
        static function GetDirectorySubsection($path,$start = 0,$end = 1)
        {
           $parts = explode("/", $path);
           $len = count($parts);
           $newpath = "";
           for($i=$len - $start - 1;$i >= $len - $end;$i--){
               $newpath = $parts[$i] . "/" . $newpath;
           }
           return $newpath;
        }
}
