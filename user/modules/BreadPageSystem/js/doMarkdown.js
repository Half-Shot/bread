/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function DoMarkdown()
{
    var converter = new Showdown.converter();
    $(".bps-content").each(function(i,parent)
    {
        var child = parent.firstChild;
        var markdown = child.innerHTML;
        parent.innerHTML += "<div class='bps-html'>";
        parent.innerHTML += converter.makeHtml(markdown);
        parent.innerHTML += "</div>";
    });
    $(".bps-markdown").hide();
}