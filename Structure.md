File Structure
==============

###[NOTE] This is not acutally set in stone or even being used yet. This file is a planning file.

The file structure that is in the process of being adopted.
The system itself is based on a GNU/Linux distrobutions filesystem.
You can modify the code yourself to change the filesystem but it is recommended that you do NOT for the reason that this breaks any modules developed for Bread unless you modify them too.
We made this decision to use Linux because its a cool operating system and much more secure than any other.



#####index.php
	* This file does very little by itself. It is mainly used to startup the core.php for now.
	* No module will be able to edit this file.
	* It will only be executable, never read or write.
####/modules 
	* Keep core modules inside for editing only by the bread team itself (unless you really want to)
	* Modules will not be allowed to edit these files at all. No amount of permissions can disable the lock.
####/settings
	* The 'etc' directory. Keeps core settings files inside the directory in the format of json files.
	* Additionally any modules that need a settings file (most of them!) will keep them in a induvidal directory.
	* Modules by default will be forbidden to mingle into another module directory unless explicitly allowed by bread.
	* Passwords and personal details will be kept inside a sqlite database in here.
	
####/user
	* This directory is used to store module code and other sitewide additions.
	* Modules can only access their resource folder and module folder by default.
	* Themes are only allowed access to their folder and resource.
	/modules
		This directory will store module php documents themselves.
	/resource
		This directory keeps images and media files seperate from the binarys.
	/themes
		Theme php code, images and json documents will be stored in here.
####/content
	* The meat of the site. This is where pages and posts are stored as well as any images you might use.
	* Reading/Writing permissions to this folder is required for modules.
	* Executable files are strictly not allowed and will be destroyed if found to prevent hacking (this can be turned off but this is not really ever needed).
	/media
		Anything that might be used in a post will be stored in here. A json file will be included to describe the file.
	/posts
		Post files that can be put into folders to give them main categorys (further categorys can be added inside its json file).
		Of cource, you can just dump all your md and json files inside the folder for the same effect.
	/pages
		Posts with additonal infomation that make them static to the site( have a worded url rather than a number and are visible on the navbar by default)
		Dropdown navbar links can be achieved by placing in a directory.
####/temp
	* Frequently refreshed content like static HTML generation will be placed in here and regenerated if it has been removed.
	* Useful for storing stuff quickly.
	* This directory can be used by all modules and is not restricted in any way other than from visitors to your blog.

