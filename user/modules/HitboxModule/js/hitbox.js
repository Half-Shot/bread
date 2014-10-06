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
var teamJsonUrl = "http://api.hitbox.tv/teams/" + hitboxStreamName;
var mediaJsonUrl = "http://api.hitbox.tv/media/live/";
var embedHTMLStart = '<iframe class="streamContainer" src="http://hitbox.tv/#!/embed/';
var embedchatHTMLStart = '<iframe class="chatContainer" src="http://hitbox.tv/#!/embedchat/';
var embedHTMLEnd = '" frameborder="0" allowfullscreen></iframe>';
var team = null;
var StreamAvaliable = false;
var StreamingMember = "";
var LastGame = "";

//Get Team Object
function UpdateTeamListing (returndata){
    var team = JSON.parse(returndata).teams[0];
    $(TeamNameElement).html("<b>" + team.info.group_display_name + "</b>");
    $(TeamListElement).empty();
    for(var index in team.members){
         var member = team.members[index];
         if(member.user_is_broadcaster == false || member.group_accepted == false)
             continue;
         var Image = "<a target='_new' href='http://www.hitbox.tv/"+member.user_name+"'><img src='http://edge.sf.hitbox.tv"+member.user_logo_small+"'></img></a>";
         $(TeamListElement).append("<div id='streamerBox_"+member.user_name+"' class='userBox'>"+Image+"<b>"+member.user_name+"</b></div>");
         $.get(mediaJsonUrl + member.user_name,UpdateUserStatus);    
    } 
    if(StreamAvaliable === false && StreamingMember !== "NOSTREAM"){
        $(StreamPlayerLocation).html(embedHTMLStart+hitboxStreamName+embedHTMLEnd);
        $(ChatLocation).html(embedchatHTMLStart+hitboxStreamName+embedHTMLEnd);
        $(GameNameElement).html("Offline");
    }
}

function UpdateUserStatus (returndata){
    var LiveStream = JSON.parse(returndata).livestream[0];
    StreamAvaliable = false;
    if(LiveStream.media_is_live == "1")
    {
        $(PrefixStreamerNameElement+LiveStream.media_user_name).addClass('currentlyStreaming');
        $(GameNameElement).html("Playing " + LiveStream.category_name);
        $(GameNameElement).prepend("<img src='http://edge.sf.hitbox.tv"+LiveStream.category_logo_small+"'></img>");
        if(StreamingMember !== LiveStream.media_user_name){
            StreamingMember = LiveStream.media_user_name;
            $(StreamPlayerLocation).html(embedHTMLStart+LiveStream.media_user_name+embedHTMLEnd);
            $(ChatLocation).html(embedchatHTMLStart+LiveStream.media_user_name+embedHTMLEnd);   
        }
        StreamAvaliable = true;
    }
    else{
        $(PrefixStreamerNameElement+LiveStream.media_user_name).removeClass('currentlyStreaming');
        StreamingMember = "NOSTREAM";
    }
    return StreamAvaliable;
}
function CheckUpdates(){
    $.get(teamJsonUrl,UpdateTeamListing);
}

setInterval(CheckUpdates,5000);