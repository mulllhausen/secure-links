<?php

/*
Plugin Name: mulllhausen's secure links
Plugin URI: https://github.com/mulllhausen/secure-links
Description: enables a new shortcode to make download-links secure on a per-user basis. these links only resolve correctly for the logged-in user and so cannot be shared between users. administrators have access to all links by default.
Author: peter miller
Version: 1.0
Author URI: https://github.com/mulllhausen
*/

//chose a new private key. must be at least 20 characters long and contain
//at least 1 number, 1 symbol and 1 capital letter
define("mulll0_private_key", "please change this");

$uploads_dir = wp_upload_dir();
$restricted_dir = trailingslashit($uploads_dir["path"])."restricted/";

function mulll0_include_js() {
	//include the javascript file for this plugin
	global $uploads_dir;
	$handle = "mulll0_js";
	wp_register_script($handle, plugins_url("/js/mulll0.js", __FILE__));
	$triangulation_data = array(
		"restricted_dir" => trailingslashit($uploads_dir["url"])."restricted/",
		"plugin_dir" => trailingslashit(plugins_url("", __FILE__))
	);
	wp_localize_script($handle, "mulll0_data", $triangulation_data);
	wp_enqueue_script($handle);
};
add_action("admin_enqueue_scripts", "mulll0_include_js");

function mulll0_add_admin() {
	add_submenu_page(
		"tools.php",
		"mulllhausen's secure links",
		"mulll secure links",
		"edit_users",
		"mulll0", "mulll0_load_admin_page");
};
add_action("admin_menu", "mulll0_add_admin");

function mulll0_load_admin_page() {
	list($status, $warnings) = mulll0_run_security_checks();
	?>
<h2><?php echo __("mulllhausen's secure links - admin panel", "mulll0_tr"); ?></h2>
<h3>security checks</h3>
<p>all the following features must be ticked for this plugin to work correctly. please click on any that are crossed and follow the instructions to fix them, then refresh this page.</p>
<ul>
	<li><?php echo implode("</li>\n\t<li>", $warnings); ?></li>
</ul>
<hr>
<h3>shortcode usage</h3>
<p>todo</p>
	<?php
};
function mulll0_run_security_checks() {
	$tick = "<img alt='tick' src='".plugins_url("images/tick.png", __FILE__)."' />";
	$cross = "<img alt='cross' src='".plugins_url("images/cross.png", __FILE__)."' />";
	$warnings = array(); //init
	$status = true; //init. true = pass, false = fail

	//check that https is enabled
	if(empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) == "off") $warnings[] = "$tick https is enabled";
	else {
		$warnings[] = "$cross a secure connection (https) is not in use. this means that all users of this website are open to man-in-th-middle attacks. anyone performing such an attack will be able to use session cookies to imitate users on this site and download their links. this plugin will still work without https but it is an extremely bad idea.";
		$status = false;
	};

	//check that mcrypt functions exist
	if(extension_loaded("mcrypt")) $warnings[] = "$tick the mcrypt library is installed and can be used for encrypting and decrypting secure links";
	else {
		$warnings[] = "$cross the mcrypt library is not available. this plugin will not work without mcrypt.";
		$status = false;
	};

	//check that the private key has been securely modified
	$pk_warnings = array();
	if(mulll0_private_key == "please change this") $pk_warnings[] = "it has not been changed from its default value";
	if(strlen(mulll0_private_key) < 20) $pk_warnings[] = "it is less than 20 characters";
	$symbols = "!@#$%^&*()~{};',\.";
	if(!preg_match("/[$symbols]/", mulll0_private_key)) $pk_warnings[] = "it does not contain any of the following symbols: $symbols";
	if(!preg_match("/[A-Z]/", mulll0_private_key)) $pk_warnings[] = "it does not contain any capital letters";
	if(!preg_match("/[0-9]/", mulll0_private_key)) $pk_warnings[] = "it does not contain any numbers";
	if(count($pk_warnings)) {
		$warnings[] = "$cross the private key has the following errors: <ul class="mulll0-list"><li>".implode("</li><li>", $pk_warnings)."</li></ul>";
		$status = false;
	} else $warnings[] = "$tick the private key has been securely updated";

	//use javascript to check if the restricted files can be accessed via the web
	//the javascript for this plugin will do this whenever the #key-security-status element exists on the page
	$warnings[] = "<span id='key-security-status'></span>";

	return array($status, $warnings);
};
add_shortcode('mulll0', 'mulll0_encrypt_download_link');
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
	return ABSPATH."wp-content/uploads/restricted/".$basename;
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
	$key = pack("H*", hash("sha256", LINK_KEY));
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
	$key = pack("H*", hash("sha256", LINK_KEY));
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
