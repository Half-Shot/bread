/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var converter = new Showdown.converter();
for (i = 0; i < $(".bps-content").length; i++)
{
    var parent = $(".bps-content").select(0);
    var child = parent.find(".bps-markdown").select(0);
    var markdown = child.html();
    parent.append("<div class='bps-html'>");
    parent.append(converter.makeHtml(markdown));
    parent.append("</div>");
    child.hide();
}
