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

	//only run a check for the private key if an element with id="mulll-key-security-status" exists on the page
	if($("#mulll-key-security-status").length != 0) mulll0_key_security_status();
	function mulll0_key_security_status() {
		//firstly, if the test file does not exist on the server then a javascript
		//search for this file will return false - not because the restricted
		//directory is secure, but because the file does not exist. therefore,
		//do not run this javascript check on the security of the restricted dir
		//since it will yield no useful results - only false positives.
		if(!mulll0_data["test_file_exists"]) {
			$("#mulll-key-security-status").closest("li").remove();
			return;
		};
		//at this point we know that the restricted test file does exist on the server
		//so check if the restricted test file can be accessed
		var tick = "<img alt='tick' src='" + mulll0_data["plugin_url"] + "images/tick.png' />";
		var cross = "<img alt='cross' src='" + mulll0_data["plugin_url"] + "images/cross.png' />";
		$.ajax({
			url: mulll0_data["restricted_url"] + "mulll0_test.txt",
			complete: function(jqxhr, ajax_status) {
				//ajax_status = "success"; //dev debug use only
				switch(ajax_status) {
					case "success": //"success" means failure for us - we should not be able to access restricted files
						$("#mulll-key-security-status").html("<div class='mulll-accordion-expandable' id='mulll-accordion-js-test0'>" + cross + " your files are not properly restricted - they can be viewed from the internet.<div class='mulll-accordion-content'>to fix this error you must restrict access to the <code>" + mulll0_data["restricted_url"] + "</code>directory in your webserver's configuration settings. once you have done this, restart your webserver program and refresh this page.</div></div>");
						$("#mulll-accordion-js-test0").click(accordion_click);
						$(".mulll0-security-instructions").css("display", "block");
						break;
					case "error": //"error" means success for us - we should not be able to access restricted files
						$("#mulll-key-security-status").html(tick + " restricted files are safe - they cannot be viewed from the internet.");
						break;
				};
			}
		});
	};
});
