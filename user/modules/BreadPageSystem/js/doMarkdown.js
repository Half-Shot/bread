/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var mdParser = new Markdown.Converter();
Markdown.Extra.init(mdParser);
var editorState = 0;
var editorHTML ;
var editor;
var ReqPerSec = 0.2;
var timeSinceLastRequest = 0;
var tokenizedMarkdown = "";
var lastTokens;
var opts = {
  container: 'bps-editor',
  textarea: null,
  basePath: epiceditor_basepath,
  clientSideStorage: true,
  localStorageName: 'epiceditor',
  useNativeFullscreen: true,
  parser: ParseMarkdown,
  file: {
    name: 'epiceditor',
    defaultContent: '',
    autoSave: 100
  },
  theme: {
    base: 'epiceditor.css',
    preview: 'preview-dark.css',
    editor: 'epic-dark.css'
  },
  button: {
    preview: true,
    fullscreen: true,
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
    toggleEdit: 'Toggle Edit Mode',
    toggleFullscreen: 'Enter Fullscreen'
  },
  autogrow: true
};

function GetTokens(markdown)
{
    var tokens = markdown.split("[%]");
    for (var i=0;i<tokens.length;i += 2)
    {
        tokens.splice(i,1);
    }
    for (var i=0;i<tokens.length;i++)
    {
        if(!/[^\s]/.test(tokens[i]))
            tokens.splice(i,1);
    }
    return tokens;
}

function ParseMarkdown(markdown,overrideTimer)
{
    var tokens = GetTokens(markdown);
    var time = new Date().getTime();//Milliseconds.
    if(((time > timeSinceLastRequest + 1000 * (1 / ReqPerSec) || overrideTimer)) && (markdown.indexOf("[%]") != -1) && lastTokens !== tokens)
    {
        timeSinceLastRequest = time;
        $.ajax("index.php",{type:"POST",async:false,data:{ ajaxEvent: "Bread.TokenizePost",ajaxModule:"BreadPageSystem", markdown: markdown},success:function(newMarkdown)
        {
            tokenizedMarkdown = newMarkdown;
            tokenizedMarkdown = mdParser.makeHtml(tokenizedMarkdown);
            lastTokens = tokens;
        }});
    }
    else
    {
        tokenizedMarkdown = mdParser.makeHtml(markdown);
    }
    return tokenizedMarkdown;
}

function DoMarkdown()
{
    $(".bps-content").each(function(i,parent)
    {
        var child = parent.firstChild;
        var markdown = child.innerHTML;
        parent.innerHTML += "<div class='bps-html'></div>";
        parent.innerHTML += "<hr/>";
        var html = parent.children[1];
        //Add an editor too.
        if(parent.hasAttribute("editor") && !editor)
        {
            editorDOM = document.createElement('div');
            editorDOM.setAttribute("id", "bps-editor");
            editorHTML = html;
            $(parent).append(editorDOM); 
            editor = new EpicEditor(opts).load();
            editor.importFile('filename', markdown);
            $("#bps-editor-toolbar").prependTo("#bps-editor");
            $("#bps-editor").hide();
        }
        $(html).html(ParseMarkdown(markdown),true); 
    });
    $(".bps-markdown").hide();
}

function toggleMarkdown()
{
    switch(editorState){
        case 0:
            editor.on('autosave', function () {
                $(editorHTML).html(editor.exportFile()); 
            });
            //Content
            $.each($(".bps-editorinfo-input"),function(){
                $(this).removeAttr( "readonly" )
            });
            $("button.bps-editorinfo-input").removeAttr( "disabled" );
            $(".bps-html").animate({"height": "100%"},400,function(){$(".bps-html").css( "overflow-y", "scroll" )});
            $("#bps-editor").slideDown();
            $("#ig-e_categorys").slideDown();
            $("#bps-editor").animate({"height": "100%"},400,function(){editor.reflow();});
            $("#bps-mdsave").show();
            $("#bps-editor-toolbar").show();
            $("#bps-title").attr('contentEditable',true);
            $("#bps-subtitle").attr('contentEditable',true);
            $.each($("#bps-mdtoggle"),function(){$(this).html("Close Editor") });
            $("#bps-title").css("border","black dashed 1px");
            $("#bps-subtitle").css("border","black dashed 1px");
            editorState = 1;
            break;
        case 1:
            editor.removeListener('save');
            $(editorHTML).html(ParseMarkdown(editor.exportFile()),true); 
            $.each($(".bps-editorinfo-input"),function(){
                $(this).attr("readonly",true);
            });
            $("button.bps-editorinfo-input").attr("disabled",true);
            $(".bps-html").animate({"height": "100%"},400,function(){$(".bps-html").css( "overflow-y", "none" )});
            $("#bps-editor").slideUp();
            $("#bps-mdsave").hide();
            $("#bps-editor-toolbar").hide();
            $("#bps-title").attr('contentEditable',false);
            $("#bps-subtitle").attr('contentEditable',false);
            $("#ig-e_categorys").slideUp();
            $("#bps-title").css("border","none");
            $("#bps-subtitle").css("border","none");
            $.each($("#bps-mdtoggle"),function(){$(this).html("Open Editor") });
            editorState = 0;
            break;
    }
}

function saveMarkdown()
{
    var md = editor.exportFile();
    $.post( "index.php", { ajaxEvent: "BreadPageSystem.SavePost",ajaxModule:"BreadPageSystem", url: document.URL, markdown: md, title: $("#bps-title").text(), subtitle: $("#bps-subtitle").text(), name: $("#e_postname")[0].value ,author: $("#e_author")[0].value, timereleased: $("#e_timereleased")[0].value, categorys: CategoryArray()}, function(returndata)
    {
        if(returndata != "0"){
            alert("Saved :D");
            window.location = returndata;
        }
        else
            alert("Something went wrong :|");
    });
}

function deletePost()
{
    $.post( "index.php", { ajaxEvent: "BreadPageSystem.DeletePost",ajaxModule:"BreadPageSystem", url: document.URL}, function(returndata)
    {
        if(returndata === "1"){
            alert("The deed is done.");
        }
        else if(returndata === "2"){
            alert("You're trying to delete a new post that hasn't been created yet!");
        }
        else if(returndata === "3"){
            alert("Couldn't remove the post! This is a failure on bread!");
        }
        else if(returndata === "0"){
            alert("Something went wrong :|");
        }
    });
    window.location = '//' + location.host + location.pathname;
}

function wrap(tagStart,tagEnd) {
    var sel, range;
    var selectedText;
    var editorDocument = $('#bps-editor').find('iframe')[0].contentDocument.body.firstChild.firstChild.contentDocument;
    var editorWindow =   $('#bps-editor').find('iframe')[0].contentDocument.body.firstChild.firstChild.contentWindow
    if (editorWindow.getSelection) {
        sel = editorWindow.getSelection();

        if (sel.rangeCount) {
            range = sel.getRangeAt(0);
            selectedText = range.toString();
            range.deleteContents();
            range.insertNode(editorDocument.createTextNode(tagStart + selectedText + tagEnd));
        }
    }
    else if (editorDocument.selection && editorDocument.selection.createRange) {
        range = editorDocument.selection.createRange();
        selectedText = editorDocument.selection.createRange().text + "";
        range.text = tagStart + selectedText + tagEnd;
    }

}

$("#bps-mdsave").hide();

//Category Selector

AddOnClick = function(event){
    if(ContainsCategory($(this),$('#bps-selectcategories')) == false){
        $(this).clone().click(RemoveOnClick).appendTo('#bps-selectcategories');
    }
}

$('#bps-listcategories .badge').click(AddOnClick);

RemoveOnClick = function(event){
    $(this).remove();
}

function ContainsCategory(category,element)
{
    isContained = false;
    element.each(function(i,scategory){
        if(category.text() == scategory.textContent){
            isContained = true;
            return true;
        }
    });
    return isContained;
}
$('#bps-selectcategories .badge').click(RemoveOnClick);

function addNewCategory()
{
    if($('#e_newcategory').val() == ""){
        return false;
    }
    var Element = $('#bps-listcategories .badge').first().clone().text($('#e_newcategory').val()).click(AddOnClick);;
    if(!ContainsCategory(Element,$('#bps-listcategories .badge'))){
        Element.appendTo('#bps-listcategories');
    }
}

function CategoryArray()
{
    var categorys = [];
    $('#bps-selectcategories .badge').each(function(){
        categorys.push($(this).text());
    });
    return categorys;
}