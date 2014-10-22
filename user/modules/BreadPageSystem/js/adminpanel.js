/* 
 * The MIT License
 *
 * Copyright 2014 will.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
BREADURL = 
postTable = $('#postTable');
postTable.find('tr').prop('selected', 0);

postTable.find('td').hover(function() {
    usercell_hovered = $(this).css('background-color');
    postTable.find('td').unbind('mouseenter mouseleave');
});

postTable.find('td').click(function() {
    parent = $(this).parent();
    postTable.find('td').css('background-color', '')
    postTable.find('tr').removeAttr('selected');
    
    if (parent.attr('selected') === undefined) {
        parent.find('td').css('background-color', usercell_hovered);
        parent.attr('selected', 1);
    } else {
        parent.find('td').css('background-color', '');
        parent.removeAttr('selected');
    }
    $('#toolButtons button').removeAttr('disabled');
    
    var id = postTable.find('tr[selected] td').last().text();
    $.get("index.php", {
        ajaxEvent: "BreadPageSystem.GetPostURL",
        ajaxModule: "BreadPageSystem",
        postid: id
    }, function(returndata) {
        if (returndata === "0") {
            alert("Post does not exist!");
        } else {
            $('#EditPostButton').attr('href',returndata);
        }
    });
    $.get("index.php", {
        ajaxEvent: "BreadPageSystem.GetPostLockStatus",
        ajaxModule: "BreadPageSystem",
        postid: id
    }, function(returndata) {
        if (returndata === "1") {
            $('#LockPostButton').removeClass('active');
        } else {
            $('#LockPostButton').addClass('active');
        }
    });
});

function adminLockPost(){
    var id = postTable.find('tr[selected] td').last().text();
    $.get("index.php", {
        ajaxEvent: "BreadPageSystem.LockPost",
        ajaxModule: "BreadPageSystem",
        postid: id
    });
}

function adminDeletePost(){
    var id = postTable.find('tr[selected] td').last().text();                                   //Yes
    $.post( "index.php", { ajaxEvent: "BreadPageSystem.DeletePost",ajaxModule:"BreadPageSystem",id:id, url: document.URL}, function(returndata)
    {
        if(returndata === "1"){
            postTable.find('tr[selected]').slideUp();
        }
        else if(returndata === "3"){
            alert("Couldn't remove the post! This is a failure on bread!");
        }
        else if(returndata === "0"){
            alert("Something went wrong :|");
        }
    });
    
}