var usercell_hovered = '';
var ModalElement = $("#editUserModal");
var UserEditForm = $("#UserEditForm");
var selectedUNames = [];
var selectedUIds = [];
$('tr') .prop('selected', 0);
$('td') .hover(function ()
{
    usercell_hovered = $(this) .css('background-color');
    $('td') .unbind('mouseenter mouseleave');
});
$('td') .click(function ()
{
    parent = $(this) .parent();
    parent.toggleClass('tr:hover');
    if (parent.attr('selected') == undefined) {
        parent.find('td') .css('background-color', usercell_hovered);
        parent.attr('selected',1);
    } 
    else
    {
        parent.find('td') .css('background-color', '');
        parent.removeAttr('selected');
    }
});

$('#EditUser').click(function () {
    var selectedUsers = $("tr[selected]");
    if(selectedUsers.length == 0)
        return false;
    selectedUsers.each(function(){
       selectedUNames.push($(this).children().first().text());
       var id_str = this.id.split("-")[1];
       selectedUIds.push(id_str);
    });
    CreateModalData(selectedUNames,selectedUIds);
}); 

function CreateModalData(selectedUNames,selectedUIds)
{
    //Reset
    UserEditForm.find("input").val("");
    UserEditForm.find("input #password");
    UserEditForm.find("input").prop('disabled', false);
    
    var HeaderText = "Editing ";
    if(selectedUNames.length > 3)
    {
        HeaderText += selectedUNames.slice(0,3) + "...";
    }
    else
    { 
        HeaderText += selectedUNames;
    }
    ModalElement.find(".modal-dialog .modal-content .modal-header .modal-title").text(HeaderText);
    //Load data
    $.post( "index.php", { ajaxEvent: "BreadUserSystem.GetUserInfo",ajaxModule:"BreadUserSystem",users:selectedUIds}, function(returndata)
    {
        var obj = JSON.parse(returndata);
        if(selectedUNames.length == 1){
           var ModalBody = ModalElement.find(".modal-dialog .modal-content .modal-body");
           for(propertyName in obj[0]){
               ModalBody.find("#" + propertyName).val(obj[0][propertyName]);
           }
        }
    });
    
    //Setup Form
    var FormUserName = UserEditForm.find("#username");
    if(selectedUNames.length == 1)
    {
        FormUserName.val(selectedUNames[0]);
    }
    else
    {
        FormUserName.val(selectedUNames);
    }
    
    if(selectedUNames.length > 1){
        UserEditForm.find("input").prop('disabled', true);
        UserEditForm.each(function(){
            if(BUMulituserElements.indexOf(this.id) != -1)
            {
                this.prop('disabled', false);
            }
        });
    }
    ModalElement.modal();
}

ModalElement.on('hidden.bs.modal', function () {
    //Clear array on return
    selectedUNames = [];
    selectedUIds = [];
});

//Buttons
ModalElement.find("#removeButton").click(function(){
   alert("User is attempting suicide!");
   $.post( "index.php", { ajaxEvent: "BreadUserSystem.RemoveUser",ajaxModule:"BreadUserSystem",users:selectedUIds}, function(returndata)
   {
       
   });
});

ModalElement.submit(function( event ){
    data = UserEditForm.serializeArray();
    $.post( "index.php", { ajaxEvent: "BreadUserSystem.EditUserInfo",ajaxModule:"BreadUserSystem",users:selectedUIds,data:data}, function(returndata)
    {
        if(returndata == true){
            alert("Done!");
        }
        else{
            alert("Failed!");
        }
    });
}); 