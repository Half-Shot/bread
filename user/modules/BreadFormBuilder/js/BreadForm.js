var BreadFormSend = function(Form,BreadEvent,BreadModule,Method){
    var Data = $(Form).serializeArray();
    alert(Data);
    return true;
    $.ajax("index.php",{type:Method,async:false,data:{ ajaxEvent: BreadEvent,ajaxModule:BreadModule, form: Data},success:function(data)
    {
        alert("Worked!")
    }});
}