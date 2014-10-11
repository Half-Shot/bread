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
            if(count($params) < 1)
                return $url;
            $key = array_keys($params)[0];
            $url .= "?" . $key . "=" .$params[$key];
            unset($params[$key]);
            if(count($params) < 1)
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
        public static function ArraySetKeyByProperty($array,$propName,$removeProp = false,$leaveSingleValue = false)
        {
           $newArray = array();
           foreach($array as $item)
           {
               if(is_array($item))
               {
                   $newArray[$item[$propName]] = $item;
                   
                   if($removeProp)
                       unset($newArray[$item[$propName]][$propName]);
                   
                   if($leaveSingleValue)
                       $newArray[$item[$propName]] = array_values($newArray[$item[$propName]])[0];
               }
               else{
                    $newArray[$item->$propName] = $item;
                    
                    if($leaveSingleValue)
                        $newArray[$item->$propName] = array_values((array)$newArray[$item->$propName])[0];
                    
                    if($removeProp)
                        unset($newArray[$item->$propName]->$propName);
               }

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
            Site::$Logger->writeError("Couldn't resolve path '" . $filepath . "'", Logger::SEVERITY_MEDIUM);
            return "";
        }
        /**
         * Converts a array of arrays into one single array.
         * @param array $arrays
         * @return array
         */
        public static function MashArraysToSingleArray($arrays)
        {
            $newArray = array();
            foreach($arrays as $array){
                if(is_array($array)){
                    $newArray += $array;
                }
            }
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
         * If a object value is false, replace with $ValueToUse
         * @param any $CheckMe Value to check
         * @param any $ValueToUse Value to use.
         */
        static function EmptySub($CheckMe,$ValueToUse){
            if(empty($CheckMe)){
                return $ValueToUse;
            }
            else{
                return $CheckMe;
            }
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
        
        static function curl_exec_follow($ch, &$maxredirect = null) {

          $mr = $maxredirect === null ? 5 : intval($maxredirect);

          if (ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off') {

            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

          } else {

            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

            if ($mr > 0)
            {
              $original_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
              $newurl = $original_url;

              $rch = curl_copy_handle($ch);

              curl_setopt($rch, CURLOPT_HEADER, true);
              curl_setopt($rch, CURLOPT_NOBODY, true);
              curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
              do
              {
                curl_setopt($rch, CURLOPT_URL, $newurl);
                $header = curl_exec($rch);
                if (curl_errno($rch)) {
                  $code = 0;
                } else {
                  $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                  if ($code == 301 || $code == 302) {
                    preg_match('/Location:(.*?)\n/', $header, $matches);
                    $newurl = trim(array_pop($matches));

                    // if no scheme is present then the new url is a
                    // relative path and thus needs some extra care
                    if(!preg_match("/^https?:/i", $newurl)){
                      $newurl = $original_url . $newurl;
                    }   
                  } else {
                    $code = 0;
                  }
                }
              } while ($code && --$mr);

              curl_close($rch);

              if (!$mr)
              {
                if ($maxredirect === null)
                trigger_error('Too many redirects.', E_USER_WARNING);
                else
                $maxredirect = 0;

                return false;
              }
              curl_setopt($ch, CURLOPT_URL, $newurl);
            }
          }
          return curl_exec($ch);
        }
        static function http_parse_headers( $header )
        {
            $retVal = array();
            $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
            foreach( $fields as $field ) {
                if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
                    $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                    if( isset($retVal[$match[1]]) ) {
                        $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                    } else {
                        $retVal[$match[1]] = trim($match[2]);
                    }
                }
            }
            return $retVal;
        }
        /**
        * Copy a file, or recursively copy a folder and its contents
        * @param       string   $source    Source path
        * @param       string   $dest      Destination path
        * @param       string   $permissions New folder creation permissions
        * @return      bool     Returns true on success, false on failure
        * @author      mjolnic <http://stackoverflow.com/a/12763962>
        */
        static function xcopy($source, $dest, $permissions = 0755)
        {
           // Check for symlinks
           if (is_link($source)) {
               return symlink(readlink($source), $dest);
           }
           // Simple copy for a file
           if (is_file($source)) {
               return copy($source, $dest);
           }

           // Make destination directory
           if (!is_dir($dest)) {
               mkdir($dest, $permissions);
           }

           // Loop through the folder
           $dir = dir($source);
           while (false !== $entry = $dir->read()) {
               // Skip pointers
               if ($entry == '.' || $entry == '..') {
                   continue;
               }
                
               // Deep copy directories
               self::xcopy("$source/$entry", "$dest/$entry");
           }

           // Clean up
           $dir->close();
           return true;
        }
        
        /**
         * Does what it says on the tin, removes a directory recursively.
         * @param string $directory Directory to remove.
         */
        static function RecursiveRemove($directory)
        {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $fileinfo) {
                if(is_dir($fileinfo))
                {
                    self::RecursiveRemove($fileinfo->getRealPath());
                }
                else
                {
                    \unlink($fileinfo->getRealPath());
                }
            }
            \rmdir($directory);
        }
        /**
         * Converts an array to a stdObject
         * @param array $array
         * @return StdClass The standard object to be returned.
         */
        static function ArrayToStdObject($array){
            $object = new \stdClass();
            foreach ($array as $key => $value)
            {
                $object->$key = $value;
            }
            return $object;
        }
        /**
         * Replaces spaces and non numerical/alphabetical characters with underscores.
         * @param type $filename
         * @return type
         */
        function URLSafeFileName($filename){
            $filename = preg_replace("/[^a-zA-Z0-9 ]/", "_",$filename);
            $filename = preg_replace('/\s+/', '', $filename);
            return $filename;
        }
        
        /**
         * Checks if every item in an array is an object.
         * @param array $object
         * @return boolean
         */ 
        public static function IsArrayOfObjects(array $object){
            foreach($object as $value){
                if(!is_object($value)){
                    //Not Array 
                    return false;
                }
            }
            return true;
        }
}
