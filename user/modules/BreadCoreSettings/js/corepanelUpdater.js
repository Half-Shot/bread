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
UpdChannel = -1;
UpdateRequest = null;
function requestUpdate(channel){
    $("#update-modal").modal();
    window.onbeforeunload = function() {
        return 'Bread requires this window to be open during the duration of the update!';
    };
    UpdChannel = channel;
}

function startUpdate()
{
    $("#label-complete").fadeOut();
    UpdateRequest = $.post("index.php",{ajaxEvent: "BreadCoreSettings.DoUpdate",ajaxModule:"BreadCoreSettings",channel:UpdChannel},function(data){
        element = $("#label-status span");
        if(data == "FAIL")
        {
            element.text("Update Failed!");
            element.removeClass("label-warning").removeClass("label-success").addClass("label-danger");
        }
        else if(data == "OK")
        {
            element.text("Update Complete!");
            element.removeClass("label-danger").removeClass("label-warning").addClass("label-success");
        }
        else
        {
            element.text("Server Problem!");
            element.addClass("label-warbibg").removeClass("label-success").removeClass("label-danger");
        }
        $("#label-status").fadeIn();
        window.onbeforeunload = null;
    });
}
function cancelUpdate()
{
    $("#update-modal").modal('hide');
    if(UpdateRequest !== null)
        UpdateRequest.abort();
    window.onbeforeunload = null;
}

$('#update-modal').on('hidden.bs.modal', function (e) {
    cancelUpdate();
    $("#label-status").hide();
    
})
