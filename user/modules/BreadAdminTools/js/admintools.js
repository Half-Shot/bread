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

function ShowAlert(type,text)
{
    switch(type){
        case 0:
            element = $("#admin-messagetray .alert-template.alert-success");
            break;
        case 1:
            element = $("#admin-messagetray .alert-template.alert-info");
            break;
        case 2:
            element = $("#admin-messagetray .alert-template.alert-warning");
            break;
        case 3:
            element = $("#admin-messagetray .alert-template.alert-danger");
            break;
    }
    newElement = element.clone().removeClass("alert-template").appendTo("#admin-messagetray");
    newElement.append("<p>" + text + "</p>");
    newElement.fadeIn();
    
    getHeight = function(){
        elements = $("#admin-messagetray .alert").not(".alert-template");
        var elementsHeight = 0;
        elements.each(function(){
            elementsHeight += $(this).outerHeight();
        });
        return elementsHeight;
    }
    
    //Check Size
    containerHeight = $("#admin-messagetray").height();
    elementsHeight = getHeight();
    if(elementsHeight > containerHeight && $("#admin-messagetray .alert").not(".alert-template").length > 1){
        elements.first().slideUp(400, function() { $(this).remove(); } );
        elementsHeight = getHeight();
    }
}

