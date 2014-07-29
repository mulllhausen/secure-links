jQuery(document).ready(function($) { //private scope, runs on dom-ready (ie before onload)

	//the following code was tested with jquery v1.11.0
	//alert($.fn.jquery); //check the jquery version

	//only run a check for the private key if an element with id="key-security-status" exists on the page
	if($("#key-security-status").length != 0) mulll0_key_security_status();
	function mulll0_key_security_status() {
		//check if the restricted test file can be accessed
		var tick = "<img alt='tick' src='" + mulll0_data["plugin_dir"] + "images/tick.png' />";
		var cross = "<img alt='cross' src='" + mulll0_data["plugin_dir"] + "images/cross.png' />";
		$.ajax({
			url: mulll0_data["restricted_dir"] + "mulll0_test.txt",
			complete: function(jqxhr, ajax_status) {
				switch(ajax_status) {
					case "success": //"success" means failure for us - we should not be able to access restricted files
						var warning = cross + " your files are not properly restricted - they can be viewed from the internet.";
						break;
					case "error": //"error" means success for us - we should not be able to access restricted files
						var warning = tick + " restricted files are safe - they cannot be viewed from the internet.";
						break;
				};
				//write the warning to the page
				$("#key-security-status").html(warning);
			}
		});
	};
});
