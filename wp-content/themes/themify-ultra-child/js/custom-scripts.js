// Custom scripts

// Wait for window load
jQuery(function ($) {
	
	$(document).ready(function() {  
		$("html").niceScroll();
	});

	$(window).load(function() {
		// Animate loader off screen
		$(".windows8").delay(200).fadeOut("slow");
	});
});