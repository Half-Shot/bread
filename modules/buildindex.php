<?
include("Michelf/Markdown.php");
//Options for search
$includeStatic = true;
$extractLength = 150;
if(isset($_GET["searchby"]) || isset($_GET["terms"]))
{
	$searchingby = $_GET["searchby"];
	$terms = explode("/20",$_GET["terms"]);
	
}
else
{
	?> <h2>Malformed Search!</h2> <?
	return;
}
$currenttime = time();
$pagerank = array();

if($searchingby == "name")
{
	foreach ($pages as $pageid => $page) { 
		foreach($terms as $term)
		{
			if(strstr($page["name"],$term))
			{
				$pagerank[$pageid] += 2;
			}

			if(strstr($page["title"],$term))
			{
				$pagerank[$pageid] += 2;
			}

			if(strstr($page["url"],$term))
			{
				$pagerank[$pageid] += 2;
			}
			
			foreach($page["tag"] as $page_tag)
			{
				if($term == $page_tag)
				$pagerank[$pageid] += 1;
			}
		}
	}
}
elseif($searchingby == "date")
{
	foreach ($pages as $page) { 
		if((!$includeStatic && $page["isstatic"]) || $page["ismodule"])
			continue;
		$pagerank[$page["date"]] = $page;
	}
	krsort($pagerank,SORT_NUMERIC);
}
else
{
	?> <h2>What the hell is a <?  echo $searchingby; ?>. How do i search for that!</h2> <?
	return;
}

?>

<h5> Searching for posts by <? echo $searchingby ?> matching <? echo $terms[0] ?></h5>
<h5 class="subheader">Found <? echo count($pagerank); ?> results in <? echo round(GetRequestTime(),3); ?> microseconds </h5>
<div id="results" class="row">
  <div class="small-6 large-2 columns"></div>
  <div class="small-6 large-8 columns">
		<?
		foreach ($pagerank as $pagetime => $page) {
			?>	
				<div class="panel">
				<div class="row">
				  <div class="large-4 columns">
					<? if(isset($page["thumb"])){ ?>
					<a class="th radius" href="<? echo $page["thumb"];?>">
					<img width="150" height="150" src="<?echo $page["thumb"];?>">
					</a>
					<hr>
					<?}?>
					<span>Last edit on <span class="label"><? echo date("F  j, Y, g:i a",$page["date"]); ?></span></span>
					<br>
					<span>Written by <span class="label"><? echo $page["author"]; ?></span></span>
				  </div>
				  <div class="large-8 columns"><a href="index.php?page=<? echo array_search($page,$pages); ?>"><h4><? echo $page["name"]; ?></h4></a>
					<?
					//Get extract
					$pageraw = file_get_contents("pages/" . $page["url"]);
					$pageraw = strip_tags(Markdown::defaultTransform($pageraw));
					if(strlen($pageraw) > $extractLength)
					{
						$pageraw = substr($pageraw,0,$extractLength) . "...";
					}
					echo $pageraw;
					?>
					</div>
				</div>
				</div>
			<?
		}
		?>
  </div>
  <div class="small-12 large-2 columns"></div>
</div>

