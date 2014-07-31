secure-links (wordpress plugin)
============

this plugin enables a new wordpress shortcode to make download-links secure on a per-user basis. these links only resolve correctly for the logged-in user and so cannot be shared between users.

when the user first installs the plugin they should go to the admin panel page for this plugin (under the tools menu) and make sure that the plugin is securely configured.

shortcode usage
------------

    [mulll0 allowed_users="alice anderson, bob brown,charlie clarke"] filename.pdf [/mulll0]

**notes:**

 * usernames that contain the comma (`,`) symbol will not work, since the comma is used as a separator between usernames.
 * make sure to type shortcodes in wordpress text-mode, not visual-mode, to avoid unwanted html entering the shortcode text and breaking it.
 * this plugin only enables secure downloads for files placed in the secure-downloads directory. if you like you can use another plugin (eg [WP Easy Uploader](https://wordpress.org/plugins/wp-easy-uploader/)) to upload files directly to this location through your web-browser.
 * file paths should not be included within the shortcode - only the file name (basename) is necessary.
 * usernames listed within the `allowed_users` attribute of the shortcode are case insensitive.
 * administrator level users are able to download all links by default.
 * make sure not to upload files that have spaces at the start or end of the filename since this plugin strips whitespace from the filename specified in the shortcode.
