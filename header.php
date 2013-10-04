<?
//Login Window
?>
<div id="login-window" class="reveal-modal">
  <h2>Login to Editor</h2>
  <form data-abide action="scripts/syslogin.php" method="post">
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
	  <input name="page" type="hidden" value="<? echo $currenturl; ?>">
	  <button type="submit">Log In</button>
  </form>
</div>

