dev-bread
=====
![Bread Logo](./docs/BreadLogoVersion2.png)

**[Bread Test Site](http://molrams.no-ip.org/bread/devbread/)**

##Usage
* Get permissions set up properly, in particular block access to /settings /temp in your
webservers configuration because while PHP needs to access it, users can wreck havoc
if they knew how you setup bread. Optionally  you might want to block /user although
stuff found in there should never really include sensitive information.

* Setup /settings/config.json and requests.json (tutorial and editor panel coming in 0.2 release)

* Add modules from the module repository on github.

* Set them up in /settings/modules/modlist.json|settings.json

* Settings files for non core stuff autogenerate so you should be safe.

* That's it! You will want to take a look at each modules readme just to be safe.

##Whats done/todo:
See Issues. Due to the rapid pace of development its best to search the issue board for a particular feature
and add it if you really can't find it.

##Q&As

###So what is bread?
Bread was started as a side project where I saw a gap where no content manager let you pick every detail of your site while still maintaining
a functioning core site. For example, wordpress cannot let you seamlessly demo new themes and keep your old theme. That and muuuch more can be
accomplished by the way bread is written. Bread has a object orientated system which allows for bits of code to plug in and out seamlessly.

Another cool thing is that I built this based on a linux distro philosophy; Cut out the crap and leave a basic system that applys to everyone
but allow really easy ways to make it specialised. In particular, [Arch Linux](https://wiki.archlinux.org/index.php/The_Arch_Way) has a grerequestsat
understanding of this and I try to mimic that.


###Is it right for me?
It is a bit late in the day for that sort of question but its a good one. Bread is still in early development and requires some knowledge of PHP
to make real use of it due to missing features but already it can function as a simple page manager but we are at the tip of the iceberg. Bread has
a near complete core system and  I have personally built 5 modules for it so far and its going to get a lot easier to use with my time being dedicated
to features and usability rather than core code (except when I find bugs)


###So it's not ready.
If your the type who likes to go in early and test new features and help develop a project, then yes its mature enough to work on but not enough to
use on a production site. However that is set to change in 0.2.


###I have suggestions/found bugs/need help
Well I make the best use of Github and the soon to be done devblog so you can throw some issues/bug reports at me or help code some modules.
Please note unless you have a really good reason I would prefer it if I was the only person working on the core since its so tiny but also very important
and could get easily confusing to manage.


##Credit to

The PHP Project for being awesome.
Markdown for powering our pages.
Unirest for the awesome library which powers bread's http requesting.

