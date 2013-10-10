<?
//Login Window
?>
<div id="login-window" class="reveal-modal">
  <div id="breadload" class="bread-loading">
   <div class="fi-refresh bread-loading-workingicon">
   </div>
  </div>
  <h2>Login to Editor</h2>
  <form data-abide id="bread-login" action="" onsubmit="return breadlogin();">
  	<div id="bread-login-error" class="alert-box alert">
  		Something wasn't quite right. You might wanna check that password or username.
  	</div>
  	<div id="bread-login-success" class="alert-box success">
  		S'all good. Gonna refresh the page for you ;)
  	</div>
	  <div class="name-field">
	    <label>Username <small>required</small></label>
	    <input name="user" type="text" pattern="alpha">
	    <small class="error">Username is required.</small>
	  </div>
	  <div class="password-field">
	    <label>Password <small>required</small></label>
	    <input name="pass" type="password" pattern="">
	    <small class="error">Password is required.</small>
	  </div>
	  <button type="submit">Log In</button>
  </form>
</div>

