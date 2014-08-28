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
window.maxfilesize = new Map();
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
    if(file.size > window.maxfilesize.get(file.type)){
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
        $.ajax("index.php",{type:"POST",data:{ ajaxEvent: "BreadContentSystem.BeginUpload",type:file.type,size:file.size},success:function(newID)
        {
            //Start Upload
            if(newID === "0"){
                console.log("Invalid Mimetype!");
            }
            else{
                UploadFile(newID,file);
            }
        }});
        //Upload
    });
});


function UploadFile(newID,file){
    console.log("Can begin upload!");
    var fileReader = new FileReader();
    fileReader.onloadend = function () {
      var BytesRead = 0;
      var n = 0;
      var chunkN = -1;
      while(BytesRead < file.size){
        var Chunk = this.result.slice(BytesRead,BytesRead + window.chunksize);
        var ChunkArray = new Uint8Array(Chunk);
        var ChunkString = btoa(String.fromCharCode.apply(null, ChunkArray));
        chunkN += 1;
        BytesRead += window.chunksize;
        //Upload
        $.ajax("index.php",{type:"POST",data:{ ajaxEvent: "BreadContentSystem.UploadChunk",actualsize:file.size,name:file.name,id:newID,size:Chunk.byteLength,data:ChunkString,chunkN:chunkN},success:function(returnedData)
        {
            n += 1;
            if(returnedData === "0"){
                console.log("Something went wrong.");
            }
            else if(returnedData === "1"){
                console.log("Chunk " + n + " recieved and processed");
            }
            else if(returnedData === "-1"){
                console.log("Chunk " + n + " failed to send!");
            }
            else{
                //Image Location
                console.log("Image uploaded to " + returnedData);
                uploadZone.removeFile(file);
            }
        }});
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