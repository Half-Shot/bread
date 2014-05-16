usercell_hovered = '';
ModalElement = $("#editUserModal");
UserEditForm = $("#UserEditForm");
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
    var selectedUNames = []
    var selectedUIds = []
    selectedUsers.each(function(){
       selectedUNames.push($(this).children().first().text());
       selectedUIds.push(parseInt(this.id.split("-")[1]));
    });
    CreateModalData(selectedUNames,selectedUIds);
}); 

function CreateModalData(selectedUNames,selectedUIds)
{
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
    
    
    //Setup Form
    var FormUserName = UserEditForm.find("#username");
    var FormPassword = UserEditForm.find("#password");
    if(selectedUNames.length == 1)
    {
        FormUserName.prop('disabled', false);
        FormUserName.val(selectedUNames[0]);
        
        FormPassword.prop('disabled', false);
    }
    else
    {
        FormUserName.prop('disabled', true);
        FormUserName.val(selectedUNames);
        
        FormPassword.prop('disabled', true);
    }
    
    ModalElement.modal();
}