<?
function isCategory($page,$category)
{
	foreach($page["categorys"] as $key => $value)
	{
		if($value == $category){
			return true;
		}
	}
	return false;
}

function ThrowError($reason,$errorcode = 42)
{
	$dump = array();
	$dump[]= $reason;
	$dump[] = $errorcode;
	return $dump;
}

function GetRequestTime()
{
	$RTime = 0;
	if(isset($_SERVER["REQUEST_TIME_FLOAT"]))
	{
		$RTime = $_SERVER["REQUEST_TIME_FLOAT"];
	}
	else
	{
		$RTime = $_SERVER["REQUEST_TIME"];
	}

	return microtime(true) - $RTime;
}

function StripUrlParameter($url,$param)
{
	$url_data = parse_url($url);
}
function AppendParameter($url,$param,$var)
{
     $url_data = parse_url($url);
     if(!isset($url_data["query"]))
         $url_data["query"]="";

     $params = array();
     parse_str($url_data['query'], $params);
     $params[$param] = $var;   
     $url_data['query'] = http_build_query($params);
     return build_url($url_data);
}


function build_url($url_data) {
     $url="";
     if(isset($url_data['host']))
     {
         $url .= $url_data['scheme'] . '://';
         if (isset($url_data['user'])) {
             $url .= $url_data['user'];
                 if (isset($url_data['pass'])) {
                     $url .= ':' . $url_data['pass'];
                 }
             $url .= '@';
         }
         $url .= $url_data['host'];
         if (isset($url_data['port'])) {
             $url .= ':' . $url_data['port'];
         }
     }
     $url .= $url_data['path'];
     if (isset($url_data['query'])) {
         $url .= '?' . $url_data['query'];
     }
     if (isset($url_data['fragment'])) {
         $url .= '#' . $url_data['fragment'];
     }
     return $url;
 }

?>
