function DoLogin() {
    $.post( "index.php", { ajaxEvent: "Bread.Security.LoginUser", uname: $("#username").val(), pw: $("#password").val() }, function(returndata)
    {
        obj = JSON.parse(returndata);
        if(obj.status == "11"){
            $("#login-alert-success").show().fadeIn();
            window.location = obj.goto;
        }
        else{
            $("#login-alert-fail").show().fadeIn();
        }
    });
}