
**[Bread Test Site](http://molrams.no-ip.org/bread/devbread/)**

**Currently Requires PHP 5.4 or greater**

###Required PHP Modules:
 - php-xsl(**heavily recomended**) (**used by default**) [1]
 - php-curl(**heavily recomended**) (**used by default**) [2]
  
[1]: If you do not use the standard XSLT transformations for themes (used in the standard bootstrap theme), you may skip this.

[2]: The updater and some other functions require the abiliy to request data from other web services.

###Webserver Access Setup:
* block access to */settings* and */temp* to the public eye
* */user* should block php and json files at a minimum because while PHP needs to access it, hackers can wreck havoc. Content may be hosted in there by module developers.

##Usage

* Setup */settings/config.json* and */settings/requests.json* to suit yourself.

* Set up other modules in /settings/modules/modlist.json|settings.json

* Settings files for non core stuff autogenerate so you should be safe. Modify to suit yourself.

* That's it! You will want to take a look at each modules readme just to be safe.


------------------

##Whats done/todo:
See Issues. Due to the rapid pace of development its best to search the issue board for a particular feature
and add it if you really can't find it.

##Q&As

###So what is bread?
Bread was started as a side project where I saw a gap where no content manager let you pick every detail of your site while still maintaining a functioning core site. For example, wordpress cannot let you seamlessly demo new themes and keep your old theme. That and muuuch more can be accomplished by the way bread is written. Bread has a object orientated system which allows for bits of code to plug in and out seamlessly.

Another cool thing is that I built this based on a linux distro philosophy; Cut out the crap and leave a basic system that applys to everyone but allow really easy ways to make it specialised. In particular, [Arch Linux](https://wiki.archlinux.org/index.php/The_Arch_Way) has a great understanding of this and I try to mimic that where possible.


###Is it right for me?

It depends on your needs. 

If you wish to just run a no fuss blog then I would say yes since Bread has [happily been running this users blog](http://largepixelcollider.net/bread/index.php) for some months now.

If you want to customize some details to your site such as add in your own comment system or replace the markdown editor by default; Bread allows for seamless edits to core functionality without breaking other systems provided you keep the inputs and outputs the same serverside. We do not believe in making anything hardcoded without a damn good reason.

If you want to run a massive site right now and do minimal coding then you should really not put all your faith in Bread *just yet*. We are still working on supporting more user bases but right now we have just finished up with a great blog system so the next steps are expanding that.

###So it's not ready.
Ready is relative in this instance. A lot of people will find Bread usable, while others won't. Have a look around at some of our example sites and see if its the sort of thing for you.

###I have suggestions/found bugs/need help
Well I make the best use of Github and the soon to be done devblog so you can throw some issues/bug reports at me or help code some modules.
Please note unless you have a really good reason I would prefer it if I was the only person working on the core since its so tiny but also very important
and could get easily confusing to manage.

##Example Sites
- [blog.molrams.com](http://blog.molrams.com) My blog.
- [molrams.no-ip.org](molrams.no-ip.org/bread/devbread) Bread testing site
- [largepixelcollider.net](http://largepixelcollider.net/bread/) KieranG's blog

