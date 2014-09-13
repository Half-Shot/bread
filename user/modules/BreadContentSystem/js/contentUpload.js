/* 
 * The MIT License
 *
 * Copyright 2014 half-shot.
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
window.acceptedTypes = [];
window.maxfilesize = {};
if (window.File && window.FileReader && window.FileList && window.Blob) {
  // Great success! All the File APIs are supported.
} else {
  alert('The File APIs are not fully supported in this browser.');
}

function CheckFileMimetype(file){
    if(window.acceptedTypes.indexOf(file.type) !== -1){
        return true;
    }
    else{
        return false;
    }
}


//Dropzone Config
$("#uploadZone-previewtmp .pull-left").html("<img data-dz-thumbnail />");
var template = $("#uploadZone-previewtmp")[0].outerHTML;
$("#uploadZone-previewtmp").hide();
var myDropzoneopts = { // Make the whole body a dropzone
url: "index.php?ajaxModule=BreadContentSystem&ajaxEvent=BreadContentSystem.UploadChunk", // Set the url
thumbnailWidth: 80,
thumbnailHeight: 80,
parallelUploads: 20,
autoProcessQueue: true,
previewTemplate: template,
autoQueue: false, // Make sure the files aren't queued until manually added
previewsContainer: "#uploadZone-thumbnail", // Define the container to display the previews
clickable: "#uploadZone-selectbutton" // Define the element that should be used as click trigger to select files.
};

var uploadZone = new Dropzone($("#content-dropzone")[0],myDropzoneopts);
uploadZone.on("addedfile", function(file) {
    console.log("File Added");
    if(!CheckFileMimetype(file)){
        alert("You can't add that type of file!");
        uploadZone.removeFile(file);
    }
    if(file.size > window.maxfilesize[file.type]){
        alert("That file is too big!");
        uploadZone.removeFile(file);
    }
    $("#uploadZone-uploadbutton").removeAttr( "disabled" );
    if(uploadZone.files.length > 1){
        $("#uploadZone-uploadbutton").text("Upload Files");
    }
    else{
        $("#uploadZone-uploadbutton").text("Upload File");
    }
    $($(".uploadZone-templateCancelButton")[uploadZone.files.length - 1]).click(function(){
        uploadZone.removeFile(file);
    });
    $($(".uploadZone-templateUploadButton")[uploadZone.files.length - 1]).click(function(){
        $(this).attr("disabled", true);
        var parent = $(this).parent().parent();
        $.ajax("index.php",{type:"POST",data:{ ajaxEvent: "BreadContentSystem.BeginUpload",name:file.name,type:file.type,size:file.size},success:function(newID)
        {
            //Start Upload
            if(newID === "0"){
                console.log("Invalid Mimetype!");
            }
            else{
                UploadFile(newID,file,parent);
            }
        }});
        //Upload
    });
});

function RemoveContentOnClick()
{
    var fileid = $(this).attr("fileid");
    var row = $(this).parent().parent();
    $.ajax("index.php",{type:"POST",data:{ ajaxEvent: "BreadContentSystem.DeleteContent",id:fileid},success:function(result)
    {
        if(result === "0"){
            console.log("Couldn't delete");
        }
        else{
            row.slideUp(400,function(){row.remove()});
        }
    }});
}

function breadContentModalSetType(majortype){
    $.ajax("index.php",{type:"POST",data:{ ajaxEvent: "BreadContentSystem.GetFileIndex",majortype:majortype},success:function(returnedData)
    {
        Files = JSON.parse(returnedData);
        console.log(Files);
        $("#breadContentModal tbody").children().remove();
        for(var i=0;i<Files.length;i++){
            var selectButton = $("#breadContentModal-selectButtonTemplate").clone();
            var deleteButton = $("#breadContentModal-deleteButtonTemplate").clone();
            selectButton.show();
            deleteButton.show();
            selectButton.click(function(){
                if($(this).hasClass("active")){
                    console.log("File " + Files[i].id + " selected!");
                }
                else{
                    console.log("File " + Files[i].id + " unselected!");
                }
            });
            var row = "<tr id='contentid-"+i+"'>";
                row += "<td>" + Files[i].filename + "</td>";
                //Date
                var d = new Date(0);
                d.setUTCSeconds(Files[i].time);
                row += "<td>" + d.toDateString() + "</br>" + d.toTimeString() + "</td>";
                row += "<td>" + (Files[i].size / 1024000).toFixed(2) + " MB</td>";
                row += "<td>" + Files[i].minortype + "</td>";
                selectButton.attr("fileid",Files[i].id);
                deleteButton.attr("fileid",Files[i].id);
                selectButton.attr("source","local");
                selectButton.attr("major",Files[i].majortype);
                selectButton.attr("minor",Files[i].minortype);
                row += "<td>"+selectButton.wrap('<p>').parent().html()+"</td>";
                row += "<td>"+deleteButton.wrap('<p>').parent().html()+"</td>";
                row += "</tr>";
            $("#breadContentModal tbody").append(row);
            $("#breadContentModal tbody tr").last().find("#breadContentModal-deleteButtonTemplate").click(RemoveContentOnClick);
        }
    }});
}

function UploadFile(newID,file,displayElement){
    console.log("Can begin upload!");
    var fileReader = new FileReader();
    var progressBar = displayElement.find('#uploadZoneProgress');
    fileReader.onloadend = function () {
      var BytesRead = 0;
      var n = 0;
      var chunkN = -1;
      var chunksRequired = Math.ceil(file.size / window.chunksize);
      while(BytesRead < file.size){
        var Chunk = this.result.slice(BytesRead,BytesRead + window.chunksize);
        var ChunkArray = new Uint8Array(Chunk);
        var ChunkString = btoa(String.fromCharCode.apply(null, ChunkArray));
        chunkN += 1;
        BytesRead += window.chunksize;
        //Upload
        $.ajax("index.php",{type:"POST",data:{ ajaxEvent: "BreadContentSystem.UploadChunk",id:newID,size:Chunk.byteLength,data:ChunkString,chunkN:chunkN},success:function(returnedData)
        {
            var chunksRequired = Math.ceil(file.size / window.chunksize);
            var progressBar = displayElement.find('#uploadZoneProgress');
            n += 1;
            if(returnedData === "0"){
                console.log("Something went wrong.");
                uploadZone.removeFile(file);
                $("#content-finished").append("<hr><b>" + file.name + "</b> failed to send due to an unknown error.");
            }
            else if(returnedData === "3"){
                uploadZone.removeFile(file);
                console.log(file.type + " is not a valid mimetype");
                $("#content-finished").append("<hr><b>" + file.name + "</b> failed to send due to being the wrong type.");
            }
            else if(returnedData === "1"){
                console.log("Chunk " + n + " recieved and processed");
                progressBar.val(progressBar.val() + ((1 / chunksRequired) * 50));
            }
            else if(returnedData === "-1"){
                console.log("Chunk " + n + " failed to send!");
            }
            else{
                //Image Location
                progressBar.val(100);
                console.log("File uploaded to " + returnedData);
                var FinishItem = displayElement.clone();
                FinishItem.find('.btn-group').remove();
                FinishItem.find('br').remove();
                FinishItem.find('#uploadZoneProgress').remove();
                FinishItem.append("<a href='" + returnedData + "'>Click To View File</a></br>");
                FinishItem.append("</br><pre>"+ returnedData + "</pre>");
                FinishItem.hide();
                $("#content-finished").append(FinishItem);
                FinishItem.fadeIn();
                uploadZone.removeFile(file);
            }
        }});
        progressBar.val(progressBar.val() + ((1 / chunksRequired) * 50));
      }
    }
    fileReader.readAsArrayBuffer(file);
}

uploadZone.on("removedfile", function(file) {
    if(uploadZone.files.length < 2){
        $("#uploadZone-uploadbutton").text("Upload File");
    }
    
    if(uploadZone.files.length == 0){
        $("#uploadZone-uploadbutton").attr("disabled", true);
    }
});

function breadContentModalinsertFiles(){
    $('#breadContentModal-fileTable td .active').each(function(i,obj){
        var fileid = $(obj).attr("fileid");
        var source = $(obj).attr("source");
        var major = $(obj).attr("major");
        var minor = $(obj).attr("minor");
        if(source === "local"){ //We have all we need.
            $.ajax("index.php",{type:"POST",data:{ ajaxEvent: "Bread.GetContentURL",contentid:fileid},success:function(returnedData)
            {
                if(returnedData !== "0"){
                    //HARDCODED IMAGE INSERT
                    if (typeof wrap !== "undefined") { 
                        // safe to use the function
                        if(major == "image"){
                            wrap('![','](' + returnedData + ')');
                        }
                        else{
                            wrap('[%]'+major+'('+ returnedData +')[%]','');
                        }
                    }
                }
            }});
        }
    });
    $('#breadContentModal').modal('hide');
}