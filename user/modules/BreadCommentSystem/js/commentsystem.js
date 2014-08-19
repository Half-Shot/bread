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
$(".editable").keyup(function(){
    var CharLeftElement = $(this).parent().find(".commentcharsleft");
    if(window.CommentMaxChars == false){
        window.CommentMaxChars = parseInt(CharLeftElement.text())
    }
    
    var CharsExceeding = this.textContent.length - window.CommentMaxChars;
    if(CharsExceeding > 0)
    {
       $(this).text(this.textContent.slice(0,-CharsExceeding));
    }
    
    CharLeftElement.text(window.CommentMaxChars - this.textContent.length);
});

$("#editcomment-button").click(function(){
    alert("Edited!");
});

$("#deletecomment-button").click(function(){
    alert("Deleted!");
});


$("#savecomment-button").click(function(){
    alert("Saved!");
});

$(".upvotecomment-button").click(function(){
    var parent = $(this).parent().parent().parent();
    var index = parseInt(parent.find("index").text());
    $.post( "index.php", {ajaxModule:"BreadCommentSystem",ajaxEvent:"BreadCommentSystem.UpvoteComment",commentid:index,uniqueid:window.pageuniqueid}, function(returndata)
    {
        if(returndata === "1")
        {
            var score = parent.find(".stats .score .badge");
            var value = parseInt(score.text());
            value += 1
            score.text(value);
            
        }
        else if(returndata === "2"){
            var score = parent.find(".stats .score .badge");
            var value = parseInt(score.text());
            value -= 1
            score.text(value);
        }
        else{
            alert("There was a problem upvoting this comment.")
        }
    });
});

$("#downvotecomment-button").click(function(){
    alert("Downvoted!")
});