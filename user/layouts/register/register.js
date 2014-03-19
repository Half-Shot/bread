function DoRegister() {
    $.post( "index.php", { ajaxEvent: "Bread.Security.RegisterNewUser", uname: $("#username").val(), pw: $("#password").val(),extrainfo:[{email:$("#email").val()}] }, function(returndata)
    {
        obj = JSON.parse(returndata);
        if(obj.status == "11"){
            window.location = obj.goto;
        }
        else{
            alert(obj.status);
        }
    });
}