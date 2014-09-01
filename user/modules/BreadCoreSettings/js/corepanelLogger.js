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

function PopulateCategoryBox(categories){
   $("#log-categorybox").find('option').remove();
   if(categories == false){
       $("#log-categorybox").prop('disabled', true);
   }
   else{
       $("#log-categorybox").prop('disabled', false);
   }
   for(var i =0;i<categories.length;i++){
        $("#log-categorybox").append($("<option></option>").text(categories[i])); 
   }
    var currentLog = $("#log-fileselectorbox").val();
    var currentCategory = $("#log-categorybox").val();
    GetLogData(currentLog,currentCategory);
}

function SetupCurrentLogBox(LogData){
    $("#log-output").html(LogData);
}

function GetLogCategories(name){
    $.post( "index.php", { ajaxEvent: "BreadCoreSettings.GetLogCategories",logname:name}, function(returndata){
        if(returndata === "0"){
            console.log("Log File not found")
            PopulateCategoryBox(false);
        }
        else if(returndata == false){
            PopulateCategoryBox(false);
        }
        else{
            PopulateCategoryBox(JSON.parse(returndata));
        }
    });
}

function GetLogData(name,category){
    $.post( "index.php", { ajaxEvent: "BreadCoreSettings.GetLog",logname:name,category:category }, function(returndata){
        if(returndata === "0"){
            console.log("Log File not found")
            SetupCurrentLogBox("Couldn't find log data!");
        }
        else{
            SetupCurrentLogBox(returndata);
        }
    });
}

$("#log-fileselectorbox").change(function() {
    var currentLog = $("#log-fileselectorbox").val();
    GetLogCategories(currentLog);
});

$("#log-categorybox").change(function() {
    var currentLog = $("#log-fileselectorbox").val();
    var currentCategory = $("#log-categorybox").val();
    GetLogData(currentLog,currentCategory);
});

var currentLog = $("#log-fileselectorbox").val();
GetLogCategories(currentLog);