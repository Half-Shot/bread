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
	<?php foreach ($pages as $pageid => $page) { 
	if($curpag_id == $pageid)
	{
		?><li class="active"><a href="#"><?php echo $page["name"] ?></a></li><?php
	}
	elseif(isset($page["isstatic"]))
		if($page["isstatic"]) { ?>
      <li class="divider"></li>
      <li><a href="index.php?page=<?php echo $pageid ?>"><?php echo $page["name"] ?></a></li>
	<?php }} ?>
    </ul>

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
