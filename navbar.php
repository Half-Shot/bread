<nav class="top-bar">
  <ul class="title-area">
    <!-- Title Area -->
    <li class="name">
      <h1><a href="<?php echo $rooturl ?>"><?php echo $webname ?></a></h1>
    </li>
    <!-- Remove the class "menu-icon" to get rid of menu icon. Take out "Menu" to just have icon alone -->
    <li class="toggle-topbar menu-icon"><a href="#"><span>Menu</span></a></li>
  </ul>
  <section class="top-bar-section">
    <!-- Left Nav Section -->
    <ul class="left">
      <li class="divider"></li>
	<?php
	foreach ($pages as $pageid => $page) { 
	$arg_string = "";
	$class = "";
	if(isset($page["isstatic"]))
		if($page["isstatic"]) {
			if($curpag_id == $pageid){ $class = "active"; } //Add page to navbar
			if(strpos($page["url"],".md")) { $navbar_type = "page"; } else { $navbar_type = "module"; $pageid = $page["url"]; }
			if(isset($page["arguments"])){ foreach($page["arguments"] as $arg_name => $arg_value) { $arg_string = $arg_string . "&" . $arg_name . "=" . $arg_value; }  }?>
      <li class="divider"></li>
      <li><a class="<? echo $class ?>" href="index.php?<? echo $navbar_type; ?>=<?php echo $pageid; echo $arg_string; ?>"><?php echo $page["name"]; ?></a></li>
	<?php }} ?>
    </ul>
    <ul class="right">
	<? if($username == ""){?>
	<li><a data-reveal-id="login-window">Login</a></li>
	<? } else {?>
	<li><a href="<? echo AppendParameter($currenturl,"logout","true"); ?>">Logout</a></li>
	<?}?>
  </section>
 </ul>
</nav>
<?
if($haspage && !isset($error_dump)){?>
<ul class="breadcrumbs">
  <li><a href="<? echo $rooturl ?>">Home</a></li>
  <? foreach($current_page["categorys"] as $key => $category){
	if($category != "static-page")
	?>
	  <li class="unavailable"><? echo $category ?><a href="#"></a></li>
  <?}?>
  <li class="current"><a href="#"><? echo $current_page["name"];?></a></li>
</ul>
<?}?>
