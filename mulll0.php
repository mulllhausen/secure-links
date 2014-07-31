<?php

/*
Plugin Name: mulllhausen's secure links
Plugin URI: https://github.com/mulllhausen/secure-links
Description: enables a new shortcode to make download-links secure on a per-user basis. these links only resolve correctly for the logged-in user and so cannot be shared between users. administrators have access to all links by default.
Author: peter miller
Version: 1.0
Author URI: https://github.com/mulllhausen
*/

//exit if accessed directly
if(!defined('ABSPATH')) exit;

//
// these constants can safely be changed to suit your project
//

//this is the url path that the user will see as they download a file. the path
//doesn't actually exist - it is just used to identify downloads and make the
//project look pretty. the value of this constant need not be the same as the
//value of constant mulll0_secure_dir.
define("mulll0_secure_uri", "myproject-secure-downloads");

//this is the real directory that will be used to store the secure downloads.
//this plugin automatically creates this directory under /uploads. you can rename
//this directory at any time, just remember to copy the files from the old dir
//into this new dir. the plugin will warn you if you do not secure this directory.
define("mulll0_secure_dir", "myproject-secure-downloads");

//
// end constants
//

//plugin globals
$uploads_dir = wp_upload_dir();
$secure_dir = trailingslashit(trailingslashit($uploads_dir["path"]).mulll0_secure_dir);
$secure_url = trailingslashit(trailingslashit($uploads_dir["url"]).mulll0_secure_dir);
$secure_downloads_pseudo_script = trailingslashit(mulll0_secure_uri);
$plugin_url = trailingslashit(plugins_url("", __FILE__));

//
// general functions to include css & js
//
function mulll0_non_admin_include_js() {
	global $secure_url, $plugin_url;
	$admin_data = array("secure_url" => $secure_url);
	mulll0_include_js($admin_data);
};
add_action("wp_enqueue_scripts", "mulll0_non_admin_include_js");
function mulll0_include_js($page_js_data = null) {
	//include the javascript file for this plugin. different data is available
	//depending on whether the user is viewing the admin panel or the website
	$handle = "mulll0-js";
	wp_register_script($handle, plugins_url("js/mulll0.js", __file__));
	if(!empty($page_js_data)) wp_localize_script($handle, "mulll0_data", $page_js_data);
	wp_enqueue_script($handle);
};
function mulll0_include_css() {
	wp_register_style("mulll0_css", plugins_url("css/mulll0.css", __FILE__));
	wp_enqueue_style("mulll0_css");
};
add_action("wp_enqueue_scripts", "mulll0_include_css");
//
// general end functions to include css & js
//

//
// start admin panel functions
//
add_action("admin_enqueue_scripts", "mulll0_include_css");
function mulll0_admin_include_js() {
	global $secure_url, $plugin_url;
	$admin_data = array("secure_url" => $secure_url, "plugin_url" => $plugin_url);
	mulll0_include_js($admin_data);
};
add_action("admin_enqueue_scripts", "mulll0_admin_include_js");
function mulll0_load_admin_menu() {
	add_submenu_page("tools.php", "mulllhausen's secure links", "mulll secure links", "edit_users", "mulll0", "mulll0_load_admin_page");
};
add_action("admin_menu", "mulll0_load_admin_menu");

function mulll0_load_admin_page() {
	global $secure_dir;

	list($checks_pass, $warnings) = mulll0_setup_checks();
	?>
<div class="mulll0-admin-page">
	<h2>mulllhausen's secure links - admin panel</h2>
	<h3>setup &amp; security checks</h3>
		<p><span class='mulll0-security-instructions' <?php if($checks_pass) echo "style='display:none;'"; ?> >please click on all of the checks that failed for instructions on how to fix them. you will not be able to use this plugin until all the checks pass.</span></p>
		<ul><li><?php echo implode("</li><li>", $warnings); ?></li></ul>
	<br>
	<hr>
	<h3>shortcode usage</h3>
	<p><code>[mulll0 allowed_users="alice anderson, bob brown,charlie clarke"] filename.pdf [/mulll0]</code></p>
	<p></p>
	<p>notes:
		<ul class='mulll0-list'>
			<li>usernames that contain the comma (<code>,</code>) symbol will not work, since the comma is used as a separator between usernames.</li>
			<li>make sure to type shortcodes in wordpress text-mode, not visual-mode, to avoid unwanted html entering the shortcode text and breaking it.</li>
			<li>this plugin only enables secure downloads for files placed in the <code><?php echo $secure_dir; ?></code> directory. if you like you can use another plugin (eg <a href='https://wordpress.org/plugins/wp-easy-uploader/'>WP Easy Uploader</a>) to upload files directly to this location through your web-browser.</li>
			<li>file paths should not be included within the shortcode - only the file name (basename) is necessary.</li>
			<li>usernames listed within the <code>allowed_users</code> attribute of the shortcode are case insensitive.</li>
			<li>administrator level users are able to download all links by default.</li>
			<li>make sure not to upload files that have spaces at the start or end of the filename since this plugin strips whitespace from the filename specified in the shortcode.</li>
		</ul>
	</p>
</div>
	<?php
};
//
// end admin panel functions
//

//
// begin functions to translate the shortcode and render it on the user-facing page
//
function mulll0_translate_shortcode($shortcode_attrs, $basename = null) {
	global $current_user, $secure_downloads_pseudo_script;
	$basename = trim($basename);

	//if all the setup checks pass and the user has access then the shortcode
	//will be translated into a download link. otherwise the shortcode will be
	//translated into a message informing the user to correctly setup the plugin.
	//some checks can only be performed by js, so a hidden element will be placed
	//on the page beside each download link and if the js checks fail then the
	//link will be hidden and the error will be revealed.
	$insecure_error = "the mulll-secure-links plugin has not been securely configured.";

	//if the user is an admin then grant them access to all downloads
	$user_role = strtolower($current_user->roles[0]);
	if($user_role == "administrator") {
		$allowed = true; //init
		//as of wp3.9.1 there doesn't appear to be a better way to do this. this
		//will break next time wordpress is restructured.
		$insecure_error .= " please <a href='".admin_url("tools.php?page=mulll0")."'>click here</a> to configure it.";
	} else {
		$allowed = false; //init
		$insecure_error .= " please contact the ".$_SERVER["HTTP_HOST"]." site admin to fix this.";
	};

	$correctly_formatted_shortcode = true;
	//check if the 'allowed_users' attribute exists (note that there is no
	//requirement for this attribute to contain any text)
	if(!array_key_exists("allowed_users", $shortcode_attrs)) {
		$correctly_formatted_shortcode = false;
		$insecure_error = "bad shortcode for file <a class='mulll0-dud-link'>$basename</a> - the 'allowed_users' attribute is missing.";
	};
	//check if a file ($basename) has been specified
	if(!strlen($basename)) {
		$correctly_formatted_shortcode = false;
		$insecure_error = "bad shortcode - no download file has been specified.";
	};

	//if the plugin is not correctly configured then don't allow it to be used
	if($correctly_formatted_shortcode) list($checks_pass, $warnings) = mulll0_setup_checks();
	else $checks_pass = false;
	//even if all server-side checks do pass, the js checks may fail - prepare an
	//element to be revealed if so
	$hide = $checks_pass ? " style='display:none;'" : "";
	$insecure_error = "<span$hide class='mulll0-shortcode-error'>$insecure_error<br></span>";
	if(!$checks_pass) return mulll0_style_shortcode($insecure_error);

	//check if the specified file ($basename) exists
	if(!file_exists($secure_dir.$basename)) return mulll0_style_shortcode("$insecure_error<span class='mulll0-shortcode-translation'><span class='mulll0-shortcode-error'>file  <a class='mulll0-dud-link'>$basename</a> does not exist in this website's secure downloads directory.</span></span>");

	//check if the shortcode allows access to this user
	if(!$allowed) {
		$username = trim(strtolower($current_user->user_login));
		if(strlen($username)) {
			$allowed_users_arr = explode(",", $shortcode_attrs["allowed_users"]);
			$filtered_users_arr = array(); //init - this array will not contain empty elements
			foreach($allowed_users_arr as $v) {
				$v = trim(strtolower($v));
				if(strlen($v)) $filtered_users_arr[] = $v; 
			};
			if(in_array($username, $filtered_users_arr)) $allowed = true;
		};
	};

	if(!$allowed) return mulll0_style_shortcode("$insecure_error<span class='mulll0-shortcode-translation'>you do not have access to download file <a class='mulll0-dud-link'>$basename</a></span>");

	$encrypted_data = mulll0_encrypt("$current_user->ID|$basename");
	$uri = $secure_downloads_pseudo_script.$encrypted_data;

	return mulll0_style_shortcode("$insecure_error<span class='mulll0-shortcode-translation'><a href='$uri'>$basename</a></span>");
};
add_shortcode("mulll0", "mulll0_translate_shortcode");
function mulll0_style_shortcode($translated_shortcode) {
	return "<span class='mulll0-shortcode'>$translated_shortcode</span>";
};
//
// end functions to translate the shortcode and render it on the user-facing page
//

//
// begin functions to handle the encryption, decryption and retrieval of secure files
//
function mulll0_redirect() {
	$uri = $_SERVER["REQUEST_URI"];
	$uri_parts = explode("/", $uri);
	$final_path = $uri_parts[count($uri_parts) - 2];
	$cyphertext = $uri_parts[count($uri_parts) - 1]; //still mulll base64 encoded at this point

	//if this uri is not for the mulll-secure-downloads pseudo-dir then exit here
	if($final_path != mulll0_secure_uri) return;
	//if we get to this line then the user is attempting to download a secure file

	$plaintext = mulll0_decrypt($cyphertext);
	list($uid, $basename) = explode("|", $plaintext);
	list($allow, $message) = mulll0_downloads_gatekeeper($uid, $basename);
	if($allow) mulll0_do_file_transfer($uid, $basename);
	else mulll0_go_back($message);
	exit;
};
add_action("template_redirect", "mulll0_redirect");

function mulll0_downloads_gatekeeper($uid, $basename) {
	global $current_user, $secure_dir;
	if($uid != $current_user->ID) {
		return array(false, "user {$current_user->user_login} does not have access to file $basename");
	};
	if(!file_exists($secure_dir.$basename)) {
		return array(false, "file $basename does not exist");
	};
	return array(true, null);
};
function mulll0_do_file_transfer($uid, $basename) {
	global $secure_dir;
	header('Content-Description: File Transfer');
	//overwrite the wordpress 404 "not found" error (due to the pseudo url path)
	//with 200 "ok"
	header('Content-Type: application/octet-stream', true, 200);
	header('Content-Disposition: attachment; filename="'.$basename.'"');
	header('Content-Transfer-Encoding: binary');
	header('Connection: Keep-Alive');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: '.filesize($secure_dir.$basename));
	readfile($secure_dir.$basename);
};
function mulll0_encrypt($plaintext) {
	$key = pack("H*", hash("sha256", SECURE_AUTH_KEY));
	$key_size = strlen($key);
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	$ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $plaintext, MCRYPT_MODE_CBC, $iv);
	return mulll0_base64_url_encode($iv.$ciphertext);
};
function mulll0_decrypt($ciphertext) {
	$ciphertext_dec = mulll0_base64_url_decode($ciphertext);
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	$iv_dec = substr($ciphertext_dec, 0, $iv_size);
	$ciphertext_dec = substr($ciphertext_dec, $iv_size);
	$key = pack("H*", hash("sha256", SECURE_AUTH_KEY));
	$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
	return rtrim($decrypted, "\0"); //http://stackoverflow.com/a/1062220/339874
};
//thanks to http://stackoverflow.com/a/5835352/339874
//base64 encoding contains the following character set:
//ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=
//make sure the +/= do not exist in the url
function mulll0_base64_url_encode($s) {
	return strtr(base64_encode($s), '+/=', '-_.');
};
function mulll0_base64_url_decode($s) {
	return base64_decode(strtr($s, '-_.', '+/='));
};
function mulll0_alert($message) {
	$message = str_replace(array("\r", "\n", "'", '"'), array("", "\\n", "\'", '\\"'), $message);
	return "alert('$message');";
};
function mulll0_go_back($message) {
	die("<script>".mulll0_alert($message)." history.go(-1);</script>");
};
//
// end functions to handle the encryption, decryption and retrieval of secure files
//

//
// begin functions to check that the plugin has been securely configured
//
function mulll0_setup_checks() {
	$tick = "<img alt='tick' src='".plugins_url("images/tick.png", __FILE__)."' />";
	$cross = "<img alt='cross' src='".plugins_url("images/cross.png", __FILE__)."' />";
	$warnings = array(); //init
	$checks_pass = true; //init

	//if the secure directory does not exist then attempt to create it. if this fails then issue a warning
	global $uploads_dir, $secure_dir;
	$secure_dir_exists = false; //init
	$test_file_exists = false; //init
	if(!file_exists("{$secure_dir}mulll0_test.txt")) {
		if(!file_exists($secure_dir)) {
			mkdir($secure_dir, 0775, true);
			if(!file_exists($secure_dir)) $warnings["test_file"] = "##fail##the secure uploads directory <code>$secure_dir</code> does not exist and could not be created. <div class='mulll-accordion-content'>to fix this error you will need to change the permissions of parent directory <code>".$uploads_dir["path"]."</code> to allow the webserver program to write to it. once you have fixed the permissions then simply refresh this page (there is no need to restart your webserver).</div>";
			else $secure_dir_exists = true;
		} else $secure_dir_exists = true;
		if($secure_dir_exists) {
			file_put_contents("{$secure_dir}mulll0_test.txt", "the contents of this file should never be visible via the web");
			if(!file_exists("{$secure_dir}mulll0_test.txt")) $warnings["test_file"] = "##fail##the secure uploads directory <code>$secure_dir</code> does exist, but an attempt to create file <code>mulll0_test.txt</code> inside this directory failed. <div class='mulll-accordion-content'>to fix this error you will need to change the permissions of the secure uploads directory to allow the webserver program to write to it. once you have fixed the permissions then simply refresh this page (there is no need to restart your webserver).</div>";
			else $test_file_exists = true;
		};
	} else $test_file_exists = true;
	if($test_file_exists) $warnings["test_file"] = "##pass##test file <code>mulll0_test.txt</code> exists in the secure directory <code>$secure_dir</code>.";
	else $checks_pass = false;

	//check that https is enabled
	if(empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) == "off") {
		$warnings["https"] = "##fail##a secure connection (https) is not in use. this means that all users of this website are open to man-in-the-middle attacks. anyone performing such an attack will be able to use session cookies to imitate users on this site and download their links. <div class='mulll-accordion-content'>to fix this error you need to install a security certificate (also known as an ssl certificate) for your webserver. it should be possible to find an ssl certificate for free online, just try this <a href='http://google.com/#q=free+ssl+certificate' target='_blank'>google search</a>. once you have installed the certificate, restart your webserver program and refresh this page.</div>";
		$checks_pass = false;
	} else $warnings["https"] = "##pass##https is enabled.";

	//check that mcrypt functions exist
	if(extension_loaded("mcrypt")) $warnings["mcrypt"] = "##pass##the <code>mcrypt</code> library is installed and can be used for encrypting and decrypting secure links.";
	else {
		$warnings["mcrypt"] = "##fail##the <code>mcrypt</code> php extension is not available. this plugin will not work without <code>mcrypt</code>. <div class='mulll-accordion-content'>to fix this error you need to install the <code>mcrypt</code> php extension (or simply enable <code>mcrypt</code> for php if it is already installed) on the server running this website. there are too many possible combinations of server, operating system, php version and php setup that you may be using to list all solutions here, so it is recommended that you serch the web for a solution, or else refer it to your sysadmin to fix. remember to restart the server program after installing new php extensions or altering the php setup.</div>";
		$checks_pass = false;
	};

	//check that the private key has been securely modified
	$pk_warnings = array();
	if(strlen(SECURE_AUTH_KEY) < 20) $pk_warnings[] = "it is less than 20 characters long";
	$symbols = "!@#$%^&*()~{};',";
	if(!preg_match("/[$symbols]/", SECURE_AUTH_KEY)) $pk_warnings[] = "it does not contain any of the following symbols: $symbols";
	if(!preg_match("/[A-Z]/", SECURE_AUTH_KEY)) $pk_warnings[] = "it does not contain any capital letters";
	if(!preg_match("/[0-9]/", SECURE_AUTH_KEY)) $pk_warnings[] = "does not contain any numbers";
	if(count($pk_warnings)) {
		$warnings["private_key"] = "##fail##the private key has the following errors: <ul class='mulll0-list'><li>".implode("</li><li>", $pk_warnings)."</li></ul> <div class='mulll-accordion-content'>to fix this error, open file <code>".trailingslashit(ABSPATH)."wp-config.php</code> on the server that is running this website and locate the line which reads <code>define('SECURE_AUTH_KEY', 'xyz');</code>. update the value of <code>xyz</code> to something secure according to the above instructions. save the file and then refresh this admin panel page. there is no need to restart the server during this process.</div>";
		$checks_pass = false;
	} else $warnings["private_key"] = "##pass##the private key has been securely updated.";

	//use javascript to check if the secure files can be accessed via the web
	//the javascript for this plugin will run this check whenever a
	//#mulll0-secure-url-status element exists on the page.
	//we do not want to run this check if the test file does not exist on the
	//server because in this case a javascript search for this file will return
	//false - not because the secure directory is secure, but because the
	//file does not exist. therefore, do not run this javascript check on the
	//security of the secure dir since it will yield no useful results -
	//only false positives.
	if($test_file_exists) $warnings["secure_dir"] = "<span id='mulll0-secure-url-status'></span>";

	foreach($warnings as &$w) {
		$each_status = substr($w, 0, 8);
		switch($each_status) {
			case "##pass##":
				$w = substr($w, 8);
				$w = "$tick $w";
				break;
			case "##fail##":
				$w = substr($w, 8);
				$w = "<div class='mulll-accordion-expandable'>$cross $w</div>";
				break;
		};
		$w = "<div class='mulll-accordion-header'>$w</div>";
	};
	//$checks_pass = false; //debug use only
	return array($checks_pass, $warnings);
};
//
// end functions to check that the plugin has been securely configured
//
?>
