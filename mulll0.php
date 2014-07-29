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

//chose a new private key. must be at least 20 characters long and contain
//at least 1 number, 1 symbol and 1 capital letter
//define("mulll0_private_key", "please change this");
define("mulll0_private_key", "please change thisDFG345$%^");

//plugin globals
$uploads_dir = wp_upload_dir();

function mulll0_include_css() {
	wp_register_style("mulll0_css", plugins_url("css/mulll0.css", __FILE__));
	wp_enqueue_style("mulll0_css");
};
add_action("admin_enqueue_scripts", "mulll0_include_css");

function mulll0_include_js() {
	//include the javascript file for this plugin
	$handle = "mulll0-js";
	wp_register_script($handle, plugins_url("js/mulll0.js", __FILE__));
	global $uploads_dir;
	$triangulation_data = array(
		"restricted_url" => trailingslashit($uploads_dir["url"])."restricted/",
		"plugin_url" => trailingslashit(plugins_url("", __FILE__)),
		"test_file_exists" => file_exists(trailingslashit($uploads_dir["path"])."restricted/mulll0_test.txt") ? true : false
	);
	wp_localize_script($handle, "mulll0_data", $triangulation_data);
	wp_enqueue_script($handle);
};
add_action("admin_enqueue_scripts", "mulll0_include_js");

function mulll0_add_admin() {
	add_submenu_page("tools.php", "mulllhausen's secure links", "mulll secure links", "edit_users", "mulll0", "mulll0_load_admin_page");
};
add_action("admin_menu", "mulll0_add_admin");

function mulll0_load_admin_page() {
	list($status, $warnings) = mulll0_setup_checks();
	$warnings_html = implode("</li><li>", $warnings);
	?>
<div class="mulll0-admin-page">
	<h2><?php echo __("mulllhausen's secure links - admin panel", "mulll0_tr"); ?></h2>
	<h3>security checks</h3>
		<p><span class='mulll0-security-instructions' <?php if($status) echo "style='display:none;'"; ?> >please click on all of the checks that failed for instructions on how to fix them.</span></p>
		<ul><li><?php echo $warnings_html; ?></li></ul>
	<hr>
	<h3>shortcode usage</h3>
	<p>todo</p>
</div>
	<?php
};
function mulll0_setup_checks() {
	$tick = "<img alt='tick' src='".plugins_url("images/tick.png", __FILE__)."' />";
	$cross = "<img alt='cross' src='".plugins_url("images/cross.png", __FILE__)."' />";
	$warnings = array(); //init
	$status = true; //init. true = pass, false = fail

	//if the restricted directory does not exist then attempt to create it. if this fails then issue a warning
	global $uploads_dir;
	$restricted_dir = trailingslashit($uploads_dir["path"])."restricted";
	if(!file_exists("$restricted_dir/mulll0_test.txt")) {
		$restricted_dir_exists = false;
		if(!file_exists($restricted_dir)) {
			mkdir($restricted_dir, 0775, true);
			if(!file_exists($restricted_dir)) $warnings[] = "##fail##the restricted uploads directory <code>$restricted_dir</code> does not exist and could not be created. <div class='mulll-accordion-content'>to fix this error you will need to change the permissions of parent directory <code>".$uploads_dir["path"]."</code> to allow the webserver program to write to it. once you have fixed the permissions then simply refresh this page (there is no need to restart your webserver).</div>";
			else $restricted_dir_exists = true;
		} else $restricted_dir_exists = true;
		if($restricted_dir_exists) {
			file_put_contents("$restricted_dir/mulll0_test.txt", "the contents of this file should never be visible via the web");
			if(!file_exists("$restricted_dir/mulll0_test.txt")) $warnings[] = "##fail##the restricted uploads directory <code>$restricted_dir</code> does exist, but an attempt to create file <code>mulll0_test.txt</code> inside this directory failed. <div class='mulll-accordion-content'>to fix this error you will need to change the permissions of the restricted uploads directory to allow the webserver program to write to it. once you have fixed the permissions then simply refresh this page (there is no need to restart your webserver).</div>";
		};
	} else $warnings[] = "##pass##test file <code>mulll0_test.txt</code> exists in the restricted directory <code>$restricted_dir</code>.";

	//check that https is enabled
	if(empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) == "off") {
		$warnings[] = "##fail##a secure connection (https) is not in use. this means that all users of this website are open to man-in-the-middle attacks. anyone performing such an attack will be able to use session cookies to imitate users on this site and download their links. <div class='mulll-accordion-content'>to fix this error you need to install a security certificate (also known as an ssl certificate) for your webserver. it should be possible to find an ssl certificate for free online, just try this <a href='http://google.com/#q=free+ssl+certificate' target='_blank'>google search</a>. once you have installed the certificate, restart your webserver program and refresh this page.</div>";
		$status = false;
	} else $warnings[] = "##pass##https is enabled.";

	//check that mcrypt functions exist
	if(extension_loaded("mcrypt")) $warnings[] = "##pass##the <code>mcrypt</code> library is installed and can be used for encrypting and decrypting secure links.";
	else {
		$warnings[] = "##fail##the <code>mcrypt</code> php extension is not available. this plugin will not work without <code>mcrypt</code>. <div class='mulll-accordion-content'>to fix this error you need to install the <code>mcrypt</code> php extension (or simply enable <code>mcrypt</code> for php if it is already installed) on the server running this website. there are too many possible combinations of server, operating system, php version and php setup that you may be using to list all solutions here, so it is recommended that you serch the web for a solution, or else refer it to your sysadmin to fix. remember to restart the server program after installing new php extensions or altering the php setup.</div>";
		$status = false;
	};

	//check that the private key has been securely modified
	$pk_warnings = array();
	if(mulll0_private_key == "please change this") $pk_warnings[] = "has not been changed from its default value";
	if(strlen(mulll0_private_key) < 20) $pk_warnings[] = "is less than 20 characters long";
	$symbols = "!@#$%^&*()~{};',";
	if(!preg_match("/[$symbols]/", mulll0_private_key)) $pk_warnings[] = "does not contain any of the following symbols: $symbols";
	if(!preg_match("/[A-Z]/", mulll0_private_key)) $pk_warnings[] = "does not contain any capital letters";
	if(!preg_match("/[0-9]/", mulll0_private_key)) $pk_warnings[] = "does not contain any numbers";
	if(count($pk_warnings)) {
		$warnings[] = "##fail##the private key has the following errors: <ul class='mulll0-list'><li>it ".implode("</li><li>it ", $pk_warnings)."</li></ul> <div class='mulll-accordion-content'>to fix this error, open file <code>".__FILE__."</code> on the server that is running this website and locate the line which reads <code>define(\"mulll0_private_key\", \"xyz\");</code>. update the value of <code>xyz</code> to something secure according to the above instructions. save the file and then refresh this admin panel page. there is no need to restart the server during this process.</div>";
		$status = false;
	} else $warnings[] = "##pass##the private key has been securely updated.";

	//use javascript to check if the restricted files can be accessed via the web
	//the javascript for this plugin will do this whenever the #mulll-key-security-status element exists on the page
	$warnings[] = "<span id='mulll-key-security-status'></span>";

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
	return array($status, $warnings);
};
function mulll0_encrypt_download_link($shortcode_attrs, $basename = null) {
	global $current_user;

	//if the user is an admin then grant them access to all downloads
	$user_role = strtolower($current_user->roles[0]);
	$allowed = ($user_role == "administrator") ? true : false; //init

	//check if the shortcode allows access to this user
	if(!$allowed) {
		$username = strtolower($current_user->user_login);
		$allowed_users_arr = explode(",", $shortcode_attrs["allowed_users"]);
		foreach($allowed_users_arr as &$v) $v = trim(strtolower($v));
		if(in_array($username, $allowed_users_arr)) $allowed = true;
	};

	if(!$allowed) return "you do not have access to download file <a class='mulll0-dud-link'>$basename</a>";

	//urldecode fails here so definitely use rawurldecode
	$encrypted_data = rawurlencode(mulll0_encrypt("{$current_user->ID}|$basename"));
	$url = "/downloads.php?mulll0_data=$encrypted_data";

	return "<a href='$url'>$basename</a>";
};
add_shortcode('mulll0', 'mulll0_encrypt_download_link');
function mulll0_downloads_gatekeeper($uid_and_file_str) {
	global $current_user;
	$uid_and_file_arr = explode("|", $uid_and_file_str);
	$uid = trim($uid_and_file_arr[0]);
	$basename = trim($uid_and_file_arr[1]);
	if($uid != $current_user->ID) {
		return array(false, "user {$current_user->user_login} does not have access to file $basename");
	};
	if(!file_exists(mulll0_full_download_filename($basename))) {
		return array(false, "file $basename does not exist");
	};
	return array(true, null);
};
function mulll0_full_download_filename($basename) {
	global $uploads_dir;
	return trailingslashit($uploads_dir["path"])."restricted/$basename";
};
function mulll0_do_file_transfer($uid_and_file_str) {
	$uid_and_file_arr = explode("|", $uid_and_file_str);
	$basename = trim($uid_and_file_arr[1]);
	$file = mulll0_full_download_filename($basename);
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.$basename.'"');
	header('Content-Transfer-Encoding: binary');
	header('Connection: Keep-Alive');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: '.filesize($file));
	readfile($file);
};
function mulll0_encrypt($plaintext) {
	$key = pack("H*", hash("sha256", mulll0_private_key));
	$key_size = strlen($key);
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	$ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $plaintext, MCRYPT_MODE_CBC, $iv);
	return base64_encode($iv.$ciphertext);
};
function mulll0_decrypt($ciphertext) {
	$ciphertext_dec = base64_decode($ciphertext);
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	$iv_dec = substr($ciphertext_dec, 0, $iv_size);
	$ciphertext_dec = substr($ciphertext_dec, $iv_size);
	$key = pack("H*", hash("sha256", mulll0_private_key));
	return mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
};
function mulll0_alert($string) {
	$string = str_replace(array("\r", "\n", "'", '"'), array("", "\\n", "\'", '\\"'), $string);
	return "alert('$string');";
};
function mulll0_go_back($message) {
	die("<script>".mulll0_alert($message)." history.go(-1);</script>");
};
?>
