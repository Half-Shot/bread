function DoLogin() {
    $.post( "index.php", { ajaxEvent: "Bread.Security.LoginUser", uname: $("#username").val(), pw: $("#password").val() }, function(returndata)
    {
        if(returndata == "11"){
            alert("Seems alright to me.");
        }
        else{
            alert("That be a bad password.");
        }
    });
}