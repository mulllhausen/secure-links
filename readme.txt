=== mulllhausen's secure links ===
Contributors: petermiller1986
Donate link:
Tags: secure link, secure download, secure file, restricted link, restricted download, restricted file, user specific link, user specific download, user specific file, protected download, protected link, protected file
Requires at least: 2.6.0
Tested up to: 3.9.1
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Gives you a new shortcode that makes download-links secure on a per-user basis.

== Description ==

Mulllhausen's Secure Links lets you control the users who can download secure files from your site. Each user sees a different download URL, which is an encrypted combination of the user's ID and the file name. If one user copies their download URL and sends it to another user then this other user will not be able to access the secure file.

When you first install this plugin you should go to the admin panel page (under the tools menu) and make sure that everything is securely configured.

Once you have securely configured everything then the following shortcode becomes available:

`[mulll0 allowed_users="alice anderson,bob brown,charlie clarke"]filename.pdf[/mulll0]`

This will allow alice, bob and charlie to acces file `filename.pdf`. Each will have a different URL link to the same file. But if any of them try to copy their URL link and give it to denis then denis will not be able to access to file `filename.pdf`.

**notes:**

 * Usernames that contain the comma (`,`) symbol will not work, since the comma is used as a separator between usernames.
 * Make sure to type shortcodes in wordpress text-mode, not visual-mode, to avoid unwanted html entering the shortcode text and breaking it.
 * This plugin only enables secure downloads for files placed in the secure-downloads directory. If you like you can use another plugin (eg [WP Easy Uploader](https://wordpress.org/plugins/wp-easy-uploader/)) to upload files directly to this location through your web-browser.
 * File paths should not be included within the shortcode - only the file name (basename) is necessary.
 * Usernames listed within the `allowed_users` attribute of the shortcode are case insensitive.
 * Administrator level users are able to download all links by default.
 * Make sure not to upload files that have spaces at the start or end of the filename since this plugin strips whitespace from the filename specified in the shortcode.

== Installation ==

1. To install from the Plugins repository:
    * In the WordPress Admin, go to "Plugins > Add New."
    * Type "mulllhausen's secure links" in the "Search" box and click "Search Plugins."
    * Locate "mulllhausen's secure links" in the list and click "Install Now."

2. To install manually:
    * Download Mulllhausen's Secure Links plugin from http://wordpress.org/plugins/mulllhausens-secure-links
    * In the WordPress Admin, go to "Plugins > Add New."
    * Click the "Upload" link at the top of the page.
    * Browse for the zip file, select and click "Install."

3. In the WordPress Admin, go to "Plugins > Installed Plugins." Locate "mulllhausen's secure links" in the list and click "Activate."

== Frequently Asked Questions ==

= Can I change the secure downloads URL as it appears in the browser when I clicks on a link? =

Yes, you can do this by changing the value of constant `mulll0_secure_uri` at the top of file `wp-content/plugins/mulll-secure-links/mulll0.php`.

= Can I change the real location of the secure downloads directory on the server? =

Yes, you can do this by changing the value of constant `mulll0_secure_dir` at the top of file `wp-content/plugins/mulll-secure-links/mulll0.php`. The secure downloads directory is always located in the `wp-content/uploads/` directory. You can rename this directory at any time, just remember to copy all files from the old directory into this new directory. You will see a warning if you do not secure the new directory.

= The plugin is claiming that the file I uploaded does not exist, even though I have uploaded it =

You need to upload files to the secure directory as specified on the admin page.

== Other Notes ==

**TODOs**

* Enable the admin to change the secure URL via the plugin admin panel (requires sanitization)
* Enable the admin to change the secure directory name via the plugin admin panel (requires sanitization)
* Include a `insert secure link` icon above the wordpress editor. This would open a popup window where users and files could be selected. This would eliminate typos in manually written shortcodes.
* Make a pretty banner image for the wordpress.org/plugins page.

== Changelog ==

= 1.0 =

* Initial release.
