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
	$urldata = parse_url($url);

	if(isset($urldata["query"]))
		parse_str($urldata["query"],$qdata);
	else
		$qdata = array();

	unset($qdata[$param]);
	$urldata["query"] = http_build_query($qdata);
	$url = build_url($urldata);
	if(empty($qdata))
		$url = substr($url,0,-1);
        return $url;
}

function AppendParameter($url,$param,$var)
{
	$urldata = parse_url($url);
	if(isset($urldata["query"]))
		parse_str($urldata["query"],$qdata);
	else
		$qdata = array();
	$qdata[$param] = $var;
	$urldata["query"] = http_build_query($qdata);
	return build_url($urldata);
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
