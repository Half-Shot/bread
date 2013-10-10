//Hide login working overlay.
$('#breadload').hide();

$('#bread-login-success').hide(); //Hide success box.
$('#bread-login-error').hide(); //Hide error box
function breadlogin() 
{
	$('#breadload').fadeIn('fast');
	$.post("scripts/syslogin.php", $("#bread-login").serialize(), function(data){
		if(data == "OK"){
			$('#breadload').fadeOut('fast').hide();
			$('#bread-login-success').show();
			$('#bread-login-error').hide();
			//Reload page.
			window.setTimeout('location.reload()', 3000);
		}
		else{
			$('#breadload').fadeOut('fast').hide();
			$('#bread-login-success').hide();
			$('#bread-login-error').show();
		}
	});
return false;
}

