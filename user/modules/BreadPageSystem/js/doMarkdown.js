/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var mdParser = new Showdown.converter();
var editorState = 0;
var editorHTML ;
var editor;
var opts = {
  container: 'bps-editor',
  textarea: null,
  basePath: epiceditor_basepath,
  clientSideStorage: true,
  localStorageName: 'epiceditor',
  useNativeFullscreen: true,
  parser: mdParser.makeHtml,
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
  autogrow: false
};

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
        if(parent.hasAttribute("editor") && !editor)
        {
            editorDOM = document.createElement('div');
            editorDOM.setAttribute("id", "bps-editor");
            editorHTML = html;
            $(parent).append(editorDOM); 
            editor = new EpicEditor(opts).load();
            editor.importFile('filename', markdown);
            editor.on('autosave', function () {
                $(editorHTML).html(mdParser.makeHtml(editor.exportFile())); 
            });
            $("#bps-editor-toolbar").prependTo("#bps-editor");
            $("#bps-editor").hide();
        }
    });
    $(".bps-markdown").hide();
}

function toggleMarkdown()
{
    switch(editorState){
        case 0:
            //Content
            $.each($(".bps-editorinfo-input"),function(){
                $(this).removeAttr( "readonly" )
            });
            $("button.bps-editorinfo-input").removeAttr( "disabled" );
            $(".bps-html").animate({"height": "400px"},400,function(){$(".bps-html").css( "overflow-y", "scroll" )});
            $("#bps-editor").slideDown();
            $("#bps-editor").animate({"height": "400px"},400,function(){editor.reflow();});
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
    $.post( "index.php", { ajaxEvent: "BreadPageSystem.SavePost",ajaxModule:"BreadPageSystem", url: document.URL, markdown: md, title: $("#bps-title").text(), subtitle: $("#bps-subtitle").text(), name: $("#e_postname")[0].value ,author: $("#e_author")[0].value}, function(returndata)
    {
        if(returndata = "1")
            alert("Saved :D");
        else
            alert("Something went wrong :|");
    });
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