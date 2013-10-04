<?php if($username != ""){ ?>
<div class="large-1 columns"  style="background:  #0087BD; height: 35px;">
</div>

<div class="large-10 columns" style="background:  #0087BD; height: 35px;">

<?
//Setup Icon Bar.

//--Edit Page Buttons
?>
<div class="button-bar">
  <ul class="button-group">
<?
if($haspage) //IsEditable
{
?> <span data-tooltip class="has-tip" title="Edit this page"><li><a href="#" class="small button<?
if($current_page["locked"])
	echo "alert";
?>">Edit Page</a></li></span><?
}
?>
  </ul>
</div>
</div>

<div class="large-1 columns"  style="background:  #0087BD; height: 35px;">
</div>
<?php } ?>
