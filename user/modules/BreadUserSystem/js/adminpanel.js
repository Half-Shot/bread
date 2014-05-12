usercell_hovered = '';
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
    selectedUsers = $("tr[selected]");
    if(selectedUsers.length == 0)
        return false;
    selectedUNames = []
    selectedUsers.each(function(){
       selectedUNames.push($(this).children().first().text());
    });
    alert("Selected Users:" + selectedUNames);
});