function DoLogin() {
    $.post( "index.php", { ajaxEvent: "Bread.Security.LoginUser", uname: $("#username").val(), pw: $("#password").val() }, function(returndata)
    {
        obj = JSON.parse(returndata);
        if(obj.status == "11"){
            window.location = obj.goto;
        }
        else{
            alert("That be a bad password.");
        }
    });
}