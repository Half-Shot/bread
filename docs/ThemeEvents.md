Theme Events
============
###How do I create themes.

Theme creation is as simple as creating modules, you just have to do 2 more things.
Link elements and events you want to make public with one of our bread events such as "Theme.Post.Title"
After that you just return your html code (if your event requires arguements, then make sure to have a parameter set to catch passed arrays).

Simple.

###That seems alright for things like titles, but what about other modules displaying correctly across multiple themes?

They also hook into your theme events and instead of putting the theme event into the layout, they use their own module event
but also return code which your event themed.

### But what about elements inside of elements, how will that work.

If the layout features elements inside of elements and your lucky enough to be serving an event from a parent element, then
all you need to do is just keep calm and look for the array index of "_inner". This contains an array of each childs already-processed HTML AND
its layout data so you can either use the HTML provided (index of "guts") by another event or construct your own HTML.

### That seems sorta sloppy, how could I create themes to handle both cases.

Well it's not great, but basically I would encourage users to either pick a event which only deals with itself, or an event that only deals with
each child. Doing both can acutally confuse web developers more. You could have a "Theme.Layout.Grid" rather than "Theme.PostData".

### So anything else that can help me, i'm still a *little* confused.

Well, did you concider that we have a very slim and readable theme build with every version of bread called "VanillaBread".
It has no custom css or code beyond what's in its module file and is an excellent template for any new theme.
We also are currently porting Bootstrap and you can look at that for more complicated scenarios.

####Last Modified : Wed 26 Mar 2014 20:05:35 GMT 
