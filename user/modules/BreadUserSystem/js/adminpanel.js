var usercell_hovered = '';
var ModalElement = $("#editUserModal");
var UserEditForm = $("#UserEditForm");
var selectedUNames = [];
var selectedUIds = [];
var setGroups = [];

ModalElement.find("#groups").children().each(function() {
    setGroups.push(this.value);
});

$('tr').prop('selected', 0);

$('td').hover(function() {
    usercell_hovered = $(this).css('background-color');
    $('td').unbind('mouseenter mouseleave');
});

$('td').click(function() {
    parent = $(this).parent();
    parent.toggleClass('tr:hover');
    if (parent.attr('selected') === undefined) {
        parent.find('td').css('background-color', usercell_hovered);
        parent.attr('selected', 1);
    } else {
        parent.find('td').css('background-color', '');
        parent.removeAttr('selected');
    }
});

function GetSelectedGroups(){
    var ModalBody = ModalElement.find(".modal-body");
    var groupElements = ModalBody.find("#groups").children();
    var groups = {};
    for (index = 0; index < groupElements.length; index++) {
        var val = groupElements[index].value;
        groups[val] = groupElements[index].selected;
    }
    return groups;
}

function DisplayGroups(usergrouplist,ModalBody){
    for (index = 0; index < usergrouplist.length; index++) {
        var optionElement = ModalBody.find("#groups").find('option[value="' + usergrouplist[index] + '"]');
        if (optionElement.length !== 0) {
            optionElement[0].selected = true;
        }
    }
}

function CreateModalData(selectedUNames, selectedUIds) {
    //Reset
    ModalElement.find("#removeButton").show();
    ModalBody = ModalElement.find(".modal-body");
    UserEditForm.find("input").val("");
    UserEditForm.find("input #password");
    UserEditForm.find("input").prop('disabled', false);

    ModalBody.find("#groups").empty();

    for (var index = 0; index < setGroups.length; index++) {
        ModalBody.find("#groups").append("<option value='" + setGroups[index] + "'>" + setGroups[index] + "</option>");
    }

    var HeaderText = "Editing ";

    if (selectedUNames.length > 3) {
        HeaderText += selectedUNames.slice(0, 3) + "...";
    } else {
        HeaderText += selectedUNames;
    }

    ModalElement.find(".modal-dialog .modal-content .modal-header .modal-title").text(HeaderText);

    //Load data
    $.post("index.php", {
        ajaxEvent: "BreadUserSystem.GetUserInfo",
        ajaxModule: "BreadUserSystem",
        users: selectedUIds
    }, function(returndata) {
        var obj = JSON.parse(returndata);
        if (selectedUNames.length === 1) {
            var ModalBody = ModalElement.find(".modal-body");
            for (propertyName in obj[0]) {
                if (propertyName === "groups") {
                    DisplayGroups(obj[0].groups,ModalBody);
                } else {
                    ModalBody.find("#" + propertyName).val(obj[0][propertyName]);
                }
            }
        } else {
            for (propertyName in obj[0]) {
                if (propertyName === "groups") {
                    DisplayGroups(obj[0].groups,ModalBody);
                }
            }
        }

    });

    //Setup Form
    var FormUserName = UserEditForm.find("#username");
    if (selectedUNames.length === 1) {
        FormUserName.val(selectedUNames[0]);
    } else {
        FormUserName.val(selectedUNames);
    }

    if (selectedUNames.length > 1) {
        UserEditForm.find("input").prop('disabled', true);
        UserEditForm.each(function() {
            if (BUMulituserElements.indexOf(this.id) != -1) {
                this.prop('disabled', false);
            }
        });
    }
    ModalElement.unbind('submit');
    ModalElement.submit(EditUserFunction);
    ModalElement.modal();
}

ModalElement.on('hidden.bs.modal', function() {
    //Clear array on return
    selectedUNames = [];
    selectedUIds = [];
});

//Buttons
ModalElement.find("#removeButton").click(function() {
    $("#warnDeleteUser").find("#DeleteUserName").html(selectedUNames)
    $("#warnDeleteUser").modal();
});

function deleteUser() {
    $.post("index.php", {
        ajaxEvent: "BreadUserSystem.RemoveUser",
        ajaxModule: "BreadUserSystem",
        users: selectedUIds
    }, function(returndata) {
        if (returndata == true) {
            location.reload();
        } else {
            alert("Failed!");
        }
    });
}

EditUserFunction = function(event) {
    data = UserEditForm.serializeArray();
    $.post("index.php", {
        ajaxEvent: "BreadUserSystem.EditUserInfo",
        ajaxModule: "BreadUserSystem",
        users: selectedUIds,
        data: data
    }, function(returndata) {
        result = JSON.parse(returndata);
        var Message = "";
        for (var uid in result){
            var user = result[uid];
            Message = "<p>Changed ";
            for(var id in user.changed){
                if(user.changed[id]){
                    Message += "<b>"+id+"</b> "; 
                }
            }
            ShowAlert(1,Message);
        }
        ModalElement.modal('hide');
    });
};

NewUserFunction = function(event) {
    data = UserEditForm.serializeArray();
    $.post("index.php", {
        ajaxEvent: "BreadUserSystem.EditUserInfo",
        ajaxModule: "BreadUserSystem",
        users: false,
        data: data
    }, function(returndata) {
        result = JSON.parse(returndata);
        ShowAlert(1,"New ");
        ModalElement.modal('hide');
    });
};

$("#NewUserButton").click(function() {
    ModalElement.find(".modal-dialog .modal-content .modal-header .modal-title").text("Editing New User");
    ModalElement.find("#removeButton").hide();
    ModalElement.unbind('submit');
    ModalElement.submit(NewUserFunction);
    ModalElement.modal();
});

$('#EditUser').click(function() {
    //Get Selected
    var selectedUsers = $("tr[selected]");
    if (selectedUsers.length === 0)
        return false;
    selectedUsers.each(function() {
        selectedUNames.push($(this).children().first().text());
        var id_str = this.id.split("-")[1];
        selectedUIds.push(id_str);
    });

    CreateModalData(selectedUNames, selectedUIds);
});
