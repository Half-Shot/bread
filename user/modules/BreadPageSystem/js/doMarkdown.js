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
            $(".bps-html").animate({"height": "400px"},400,function(){$(".bps-html").css( "overflow-y", "scroll" )});
            $("#bps-editor").slideDown();
            $("#bps-editor").animate({"height": "400px"},400,function(){editor.reflow();});
            $.each($("#bps-mdtoggle"),function(){$(this).html("Close Editor") });
            editorState = 1;
            break;
        case 1:
            $(".bps-html").animate({"height": "100%"},400,function(){$(".bps-html").css( "overflow-y", "none" )});
            $("#bps-editor").slideUp();
            $.each($("#bps-mdtoggle"),function(){$(this).html("Open Editor") });
            editorState = 0;
            break;
    }
}
