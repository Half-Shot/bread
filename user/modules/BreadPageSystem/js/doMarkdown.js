/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var mdParser = new Showdown.converter();
function DoMarkdown()
{
    $(".bps-content").each(function(i,parent)
    {
        var child = parent.firstChild;
        var markdown = child.innerHTML;
        parent.innerHTML += "<div class='bps-html'>" + mdParser.makeHtml(markdown) + "</div>";
        parent.innerHTML += "<hr/>";
        var html = parent.children[1];
        //Add an editor too.
        if(parent.hasAttribute("editor"))
        {
            //STUB: Add Editor
        }
    });
    $(".bps-markdown").hide();
}
