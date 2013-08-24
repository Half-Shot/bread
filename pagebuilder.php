<?
//Librarys
include("Michelf/Markdown.php");
$pageraw = file_get_contents("pages/" . $current_page["url"]);
$html = Markdown::defaultTransform($pageraw);
?>

<div data-magellan-expedition="fixed">
  <dl class="sub-nav">
<?
$dom = new DOMDocument;
$dom->loadHTML(trim($html," \t\n\r\0\x0B"));
$pageheaders = $dom->getElementsByTagName('h2');
$pageheaders_cls = array();
foreach($pageheaders as $header){
	$pageheaders_cls[] = str_replace(" ","",trim(trim($header->nodeValue,"/\.,#[]-=(){}@~<>?|!")));
}
$i = 0;
foreach($pageheaders as $header)
{?>
<dd data-magellan-arrival="<?echo $pageheaders_cls[$i] ?>"><a href="#<? echo $pageheaders_cls[$i] ?>"><? echo $header->nodeValue; ?></a></dd>
<?
$i++;
}?>
  </dl>
</div>

<div class="row" id="maincontent">
<?
$linenum = 0;
$pagetags = preg_split('/\n|\r\n?/', $html,-1,PREG_SPLIT_NO_EMPTY);
$i = 0;
foreach($pagetags as $line)
{
	if((strpos($line,"h2")) > 0)
	{
		$content = str_replace("<h2>","",$line); 
		$content = str_replace("</h2>","",$content);

		foreach($pageheaders as $header)
		{
			if(str_replace(" ","",$content) == str_replace(" ","",$header->nodeValue)){
				?>
				<a name="<? echo $pageheaders_cls[$i] ?>" data-magellan-destination="<? echo $pageheaders_cls[$i] ?>"><h2><? echo $content ?></h2></a>
				<?
				$i++;
				break;
			}
		}
		continue;
	}
	else
	{
		echo $line;	
	}
}
?>

<hr size=2>
<div class="row">
  <div class="small-4 large-4 columns">Written by <? echo $current_page["author"] ?></div>
  <div class="small-4 large-4 columns">Comment system goes here.</div>
  <div class="small-4 large-4 columns">Last updated at <? echo date("F j, Y, g:i a",filemtime("pages/" . $current_page["url"]));?></div>
</div>
</div>
