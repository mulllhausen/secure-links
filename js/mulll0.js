jQuery(document).ready(function($) { //private scope, runs on dom-ready (ie before onload)

	//the following code was tested with jquery v1.11.0
	//alert($.fn.jquery); //check the jquery version

	//enable mulll-pseudo-accordions
	$(".mulll-accordion-expandable").click(accordion_click);
	function accordion_click() {
		$header = $(this);
		$content = $header.find(".mulll-accordion-content");
		if($header.data("expanded")) {
			$content.hide();
			$header.data("expanded", false);
		} else {
			$content.show();
			$header.data("expanded", true);
		};		
	};

	//only run a check for the private key if an element with id="mulll0-secure-url-status" exists on the page
	if($("#mulll0-secure-url-status").length != 0) mulll0_secure_url_status(admin_warning);
	if($(".mulll0-shortcode-error").length != 0) mulll0_secure_url_status(shortcode_warning);
	function mulll0_secure_url_status(callback) {
		//check if the secure test file can be accessed
		$.ajax({
			url: mulll0_data["secure_url"] + "mulll0_test.txt",
			complete: function(jqxhr, ajax_status) {
				//ajax_status = "success"; //debug use only
				switch(ajax_status) {
					case "success": //"success" means failure for us - we should not be able to access secure files
						callback(false);
						break;
					case "error": //"error" means success for us - we should not be able to access secure files
						callback(true);
						break;
				};
			}
		});
	};
	function admin_warning(pass) {
		var tick = "<img alt='tick' src='" + mulll0_data["plugin_url"] + "images/tick.png' />";
		var cross = "<img alt='cross' src='" + mulll0_data["plugin_url"] + "images/cross.png' />";
		if(pass) {
			$("#mulll0-secure-url-status").html(tick + " secure files are safe - they cannot be viewed from the internet.");
		} else {
			$("#mulll0-secure-url-status").html("<div class='mulll-accordion-expandable' id='mulll-accordion-js-test0'>" + cross + " your files are not properly secured - they can be viewed from the internet.<div class='mulll-accordion-content'>to fix this error you must restrict access to the <code>" + mulll0_data["secure_url"] + "</code>directory in your webserver's configuration settings.<br><br>if you want to use a different secure directory then rename the value of constant <code>mulll0_secure_dir</code> from <code>" + mulll0_data["secure_url"].split(/[\\/]/).reverse()[1] + "</code> to something more specific to your website (eg <code>" + mulll0_data["site_title"] + "-secure-downloads</code>) in file <code>" + mulll0_data["plugin_dir"] + "mulll0.php</code>. you can also update constant <code>mulll0_secure_uri</code> in this same file to display a different download url to your users.<br><br>once you have done this, restart your webserver program and refresh this page.</div></div>");
			$("#mulll-accordion-js-test0").click(accordion_click);
			$(".mulll0-security-instructions").css("display", "block");
		};
	};
	function shortcode_warning(pass) {
		if(!pass) {
			$(".mulll0-shortcode-error").show();
			$(".mulll0-shortcode-translation").hide();
		};
	};
});
