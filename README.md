-------------------
About
-------------------
First of all, timeline could be used not only as a standalone plugin. It can be used alongside other plugins
which registers themselves to use this timeline.

Timeline changes the flow of the CMS page tree rendering on the particular pages which is configured to use timeline controller or
controller of the plugin that registered their renderers to use timeline renderer (yuo can find instruction how to do this further in this file).
Instead of usual subpages you will see a calendar that groups subpages by their date specified in the page properties.

Note: The look of the public site is not changed, it only does what it is meant to do - group pages by date and keep them well organized.

-------------------
How to install and use timeline
-------------------
1. Activate Pc_timeline in the "Modules" dialog.
2. Restart CMS
3. Configure page to use Pc_timeline controller.

Next time you click on that page to show it's subpages - calendar will appear. You can put this controller on as many pages as you wish.

-------------------
How to register my plugin to use timeline
-------------------
Make sure you did first two steps in "How to install and use timeline" section. After that, all you have to do in order to register your plugin
to use timeline tree renderer, is to copy 3 files from the 'examples' folder to your plugin`s directory:

	examples/PC_config.php
	examples/PC_plugin.php
	examples/PC_plugin.js
	
If files with the same name already exists there - just open them and re-copy code from the examples, but be careful not to overwrite your plugin`s
directives or your script could stop working correctly.

Then simply configure any page in the tree to use your plugin's controller. That's it.