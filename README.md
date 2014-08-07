secure-links (wordpress plugin)
============

gives you a new shortcode that makes download-links secure on a per-user basis.

description
------------

mulllhausen's secure links lets you control the users who can download secure files from your site. each user sees a different download url, which is an encrypted combination of the user's id and the file name. if one user copies their download url and sends it to another user then this other user will not be able to access the secure file.

initial setup
------------

when you first install this plugin you should go to the admin panel page (under the tools menu) and make sure that everything is securely configured.

usage
------------

once you have securely configured everything then the following shortcode becomes available:

    [mulll0 allowed_users="alice anderson,bob brown,charlie clarke"]filename.pdf[/mulll0]

this will allow alice, bob and charlie to acces file `filename.pdf`. each will have a different url link to the same file. but if any of them try to copy their url link and give it to denis then denis will not be able to access file `filename.pdf`.

 * usernames that contain the comma (`,`) symbol will not work, since the comma is used as a separator between usernames.
 * full usernames must be used - partial usernames are not recognized. usernames can be found in the wordpress admin under "users > all users".
 * make sure to type shortcodes in wordpress text-mode, not visual-mode, to avoid unwanted html entering the shortcode text and breaking it.
 * this plugin only enables secure downloads for files placed in the secure-downloads directory. if you like you can use another plugin (eg [wp easy uploader](https://wordpress.org/plugins/wp-easy-uploader/)) to upload files directly to this location through your web-browser.
 * file paths should not be included within the shortcode - only the file name (basename) is necessary.
 * usernames listed within the `allowed_users` attribute of the shortcode are case insensitive.
 * administrator level users are able to download all links by default.
 * make sure not to upload files that have spaces at the start or end of the filename since this plugin strips whitespace from the filename specified in the shortcode.

installation
------------

1. to install from the plugins repository:
    * in the wordpress admin, go to "plugins > add new."
    * type "mulllhausen's secure links" in the "search" box and click "search plugins."
    * locate "mulllhausen's secure links" in the list and click "install now."

2. to install manually:
    * download mulllhausen's secure links plugin from http://wordpress.org/plugins/mulllhausens-secure-links
    * in the wordpress admin, go to "plugins > add new."
    * click the "upload" link at the top of the page.
    * browse for the zip file, select and click "install."

3. in the wordpress admin, go to "plugins > installed plugins." locate "mulllhausen's secure links" in the list and click "activate."

frequently asked questions
------------

**can i change the secure downloads url as it appears in the browser when i click on a link?**

yes, you can do this by changing the value of constant `mulll0_secure_uri` at the top of file `wp-content/plugins/mulll-secure-links/mulll0.php`.

**can i change the real location of the secure downloads directory on the server?**

yes, you can do this by changing the value of constant `mulll0_secure_dir` at the top of file `wp-content/plugins/mulll-secure-links/mulll0.php`. the secure downloads directory is always located in the `wp-content/uploads/` directory. you can rename this directory at any time, just remember to copy all files from the old directory into this new directory. you will see a warning if you do not secure the new directory.

**the plugin is claiming that the file i uploaded does not exist, even though i have uploaded it**

you need to upload files to the secure directory as specified on the admin page.

other notes
------------

**todos**

* enable the admin to change the secure url via the plugin admin panel (requires sanitization)
* enable the admin to change the secure directory name via the plugin admin panel (requires sanitization)
* include a `insert secure link` icon above the wordpress editor. this would open a popup window where users and files could be selected. this would eliminate typos in manually written shortcodes.
* make a pretty banner image for the wordpress.org/plugins page.

changelog
------------

**1.0**

* initial release.
