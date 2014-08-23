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
window.CommentMaxChars = false;
var date = new Date();
window.lastpostTime = date.getTime();
function EditCommentHandler(){
    alert("Edited!");
}
function DeleteCommentHandler(){
    var parent = $(this).parent().parent().parent().parent();
    var index = parseInt(parent.find("index").text());
    $.post( "index.php", {ajaxModule:"BreadCommentSystem",ajaxEvent:"BreadCommentSystem.deleteComment",index:index,uniqueid:window.pageuniqueid}, function(returndata){
        if(returndata === "1"){
            parent.fadeOut(500);
        }
        else if(returndata === "0"){
            alert("Failed to delete for some reason.");
        }
    });
}

function SaveResponseHandler(returndata){
    if(returndata === "0")
    {
        //Failed to comment.
        alert("Failed to write comment!");
    }
    else if(returndata === "1")
    {
        var date = new Date();
        var timeleft = date.getTime() - window.lastpostTime;
        timeleft = window.commenttimeout - timeleft;
        alert("You're posting too fast, please allow another " + timeleft / 1000 + " seconds.");
    }
    else
    {
        var date = new Date();
        window.lastpostTime = date.getTime();
        var element = $(NewCommentEditor.element).parent().parent().parent().parent().append(returndata);
        element.hide();
        element.fadeIn(500);
        RegenerateAllCommentHTML(newcomment);
    }
}

function SaveCommentHandler(index,text){
    var date = new Date();
    var postArgs = {ajaxModule:"BreadCommentSystem",
                    ajaxEvent:"BreadCommentSystem.writeComment",
                    text:text,
                    uniqueid:window.pageuniqueid
                    }
    if(index !== false){
        postArgs.index = index;
    }
    if(text !== ""){
        $.post( "index.php", postArgs,SaveResponseHandler);
    }
}

function scoreComment(upvote,button){
    var parent = $(button).parent().parent().parent().parent();
    var index = parseInt(parent.find("index").text());
    if(upvote){
        ajaxEvent = "BreadCommentSystem.UpvoteComment";
        modifier = 1 //Don't get excited, this only changes your javascript view. The vote still only counts for 1.
    }
    else{
        ajaxEvent = "BreadCommentSystem.DownvoteComment";
        modifier = -1
    }
    $.post( "index.php", {ajaxModule:"BreadCommentSystem",ajaxEvent:ajaxEvent,commentid:index,uniqueid:window.pageuniqueid}, function(returndata)
    {
        if(returndata !== "Fail"){
            var score = parent.find(".stats .score .badge");
            score.text(returndata);
        }
        else{
            alert("There was a problem scoring this comment.")
        }
    });
}
function CommentsApplyClickEvents(element){
    $(element).find(".editcomment-button").click(EditCommentHandler);
    $(element).find(".deletecomment-button").click(DeleteCommentHandler);
    $(element).find(".savecomment-button").click(function(){
        var parent = $(this).parent().parent().parent().parent();
        var index = parseInt(parent.find("index").text());
        SaveCommentHandler(index);
    });
    (element).find(".savecomment-button").hide();

    $(element).find(".upvotecomment-button").click(function(){
        scoreComment(true,this);
    });

    $(element).find(".downvotecomment-button").click(function(){
        scoreComment(false,this);
    });
}


/*
 * Markdown Stuff
 */
window.mdParser = new Markdown.Converter();
Markdown.Extra.init(window.mdParser);
var opts = {
      container: 'bcs-editor',
      basePath: epiceditor_basepath,
      clientSideStorage: false,
      parser: window.mdParser.makeHtml,
      file: {
        name: 'epiceditor',
        defaultContent: ''
      },
      theme: {
        base: 'epiceditor.css',
        preview: 'preview-dark.css',
        editor: 'epic-dark.css'
      },
      button: {
        preview: true,
        bar: "auto"
      },
      focusOnLoad: false,
      shortcut: {
        modifier: 18,
        fullscreen: 70,
        preview: 80
      },
      string: {
        togglePreview: 'Toggle Preview Mode',
        toggleEdit: 'Toggle Edit Mode'
      },
      autogrow: true
    };
NewCommentEditor = new EpicEditor(opts).load();

$(document).bind("editorChange",function(obj,text){
    commentBox = NewCommentEditor.element;
    var CharLeftElement = $(commentBox).parent().find(".commentcharsleft");
    if(window.CommentMaxChars === false){
        window.CommentMaxChars = parseInt(CharLeftElement.text())
    }
    
    var CharsExceeding = text.length - window.CommentMaxChars;
    if(CharsExceeding > 0)
    {
       $(NewCommentEditor.editor).focus().text('').text(text.slice(0,-CharsExceeding));
    }
    
    CharLeftElement.text(window.CommentMaxChars - text.length);
});

$(NewCommentEditor.editor).keyup(function(){
    $(parent.document).trigger("editorChange",this.textContent);
});

function RegenerateCommentHTML(comment){
        var markdown = $(comment).text();
        var HTMLElement = $(comment).parent().find(".bcs-html");
        var html = window.mdParser.makeHtml(markdown);
        CommentsApplyClickEvents($(comment).parent());
        HTMLElement.html(html);
}

function RegenerateAllCommentHTML(){
    $(".bcs-markdown").each(function(){
        RegenerateCommentHTML(this);
    })
}
$("#newcomment .savecomment-button").click(function(){SaveCommentHandler(false,NewCommentEditor.editor.textContent)});