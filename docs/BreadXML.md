Bread XML
=========
###The extensible markup language that lets you theme and layout a hell of a lot easier than json

### Why? What's the point of ANOTHER markup language

Bread's main goal was to make sure the system is light, easy to code for AND configurable.
Designing themes and to a lesser extent layouts reaches NONE of those goals.
 * theme.php files are good for converting data to html, but only in simpilistic terms
 * coding multiple ways to show an element, say a sidebar, is very hard to make easily configurable in pure code.
 * Coding something that is going to look like HTML at the end result is plain rude for web developers.

So BreadXML aim's to change that by writing good looking xml files that show the end result HTML but also
allow the developer to put in conditionals, loops, iterations and boolean expressions.

### So this will replace theme.php

Not at all, this is just a built in library inside bread to help out.
If code makes more sense to you or its too late in development, you can just do php.
However if you want to give it a go then check out the bootstrap theme as I have used
it EXTENSIVLY in there.
