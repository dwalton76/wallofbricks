
//
// Use ajax to print all of the lego parts that match the search criteria
//
function updateLegoParts() {
	// console.log("updateLegoParts called");
	var lego_color = $('div.color-sample.specific.color-selected').attr('id');
	var lego_type  = $('div.lego-type.color-selected').html();
	var dimension_x = $('input#dimension_x').val();
	var dimension_y = $('input#dimension_y').val();
	var keyword = $('input#keyword_filter').val();
	var save_part = $('input#save_part').val();
	var lego_id = $('input#lego_id').val();

	if (!lego_color || !lego_type || !dimension_x || !dimension_y) {
		return;
	}

	var dataString = 'type=' + lego_type + '&color=' + lego_color;

	if (dimension_x && dimension_y) {
		dataString += "&dimension_x=" + dimension_x + "&dimension_y=" + dimension_y;
	}

	if (keyword) {
		dataString += "&keyword=" + keyword;
	}

	if (lego_id) {
		dataString += "&lego_id=" + lego_id;
	}

	if (save_part == 1) {
		dataString += "&save_part=" + save_part;
	}

	$.ajax({
	  type: "POST",
	  url: "/ajax-get-bricks.php",
	  data: dataString,
	  cache: false,
	  success: function(response) {
		  $('div#lego-choices').html(response);
		  postAjaxCode();
	  }
	});
}

$(document).ready(function() {

	// Show the inital matching parts (black brick 2 x 4) when the page loads
	updateLegoParts();

	//
	// As the user clicks the +/- signs for the brick dimensions increment or decrement
	// the X value or Y value as needed.  Note that I used this approach instead of a select
	// dropdown because clicking on the dropdown on my iphone causes it to zoom in which
	// is annoying because then I have to zoom out.
	//
	var dimensionChangeTimer;					//timer identifier
	var doneDimensionChangeInterval = 500;  //time in ms

	$('div#increment_dimension_x').click(function() {
		var x = $('span#dimension_x_display').html();
		var y = $('span#dimension_y_display').html();
		x++;
		$('span#dimension_x_display').html(x);
		$('input#dimension_x').val(x);
		if (y > 0) {
			clearTimeout(dimensionChangeTimer);
			dimensionChangeTimer = setTimeout(updateLegoParts, doneDimensionChangeInterval);
		}

	});


	$('div#decrement_dimension_x').click(function() {
		var x = $('span#dimension_x_display').html();
		var y = $('span#dimension_y_display').html();

		if (x > 0) {
			x--;
			$('span#dimension_x_display').html(x);
			$('input#dimension_x').val(x);

			if (y > 0) {
				clearTimeout(dimensionChangeTimer);
				dimensionChangeTimer = setTimeout(updateLegoParts, doneDimensionChangeInterval);
			}
		}
	});

	$('div#increment_dimension_y').click(function() {
		var x = $('span#dimension_x_display').html();
		var y = $('span#dimension_y_display').html();
		y++;
		$('span#dimension_y_display').html(y);
		$('input#dimension_y').val(y);

		if (x > 0) {
			clearTimeout(dimensionChangeTimer);
			dimensionChangeTimer = setTimeout(updateLegoParts, doneDimensionChangeInterval);
		}
	});

	$('div#decrement_dimension_y').click(function() {
		var x = $('span#dimension_x_display').html();
		var y = $('span#dimension_y_display').html();

		if (y > 0) {
			y--;
			$('span#dimension_y_display').html(y);
			$('input#dimension_y').val(y);
			if (x > 0) {
				clearTimeout(dimensionChangeTimer);
				dimensionChangeTimer = setTimeout(updateLegoParts, doneDimensionChangeInterval);
			}
		}
	});


	// When the user clicks on the generic color show the corresponding specific colors
	//
	var specific_color_memory = {};
	$('div.color-sample.generic div.color-fill').click(function() {
		var parent = $(this).parent();

		// Remove/Add the class that changes the text to italics and changes the shadow
		$('div.color-sample.generic').removeClass('color-selected');
		parent.addClass('color-selected');

		var generic_color = parent.attr('id');
		$('div.color-sample.specific').removeClass('color-selected');

		// Re-select the specific color we selected for this generic color
		if (specific_color_memory[generic_color]) {
			$('div#' + specific_color_memory[generic_color]).addClass('color-selected');;

		// If we haven't selected one in the past then just pick the first one
		} else {
			$('div.color-sample.specific.' + generic_color).first().addClass('color-selected');;
			specific_color_memory[generic_color] = $('div.color-sample.specific.' + generic_color).first().attr('id');
		}

		// Now slide the generic colors out of view and the specific colors into view
		$('div#brick-generic-colors').effect('slide', {"direction" : "left",  "mode" : "hide"}, 500);
		$('div.brick-specific-colors.' + generic_color).effect('slide', {"direction" : "right", "mode" : "show"}, 500);
		updateLegoParts();
	});

        // The user clicked on the "Show More Blue" button to show the corner-case blue colors
	$('div.color-sample.show-more div.color-fill').click(function() {
            // Hide the button the user clicked
	    var more_x = $(this).parent();
            more_x.hide();

            // Now find the parent object for all colors in this color_group
	    var color_group_div = more_x.parent();
            color_group_div.find('div.corner-case').fadeIn();
            $('div#brick-color-and-keyword').animate({height: 800}, 200);
	});

	// This is the <- img to take them back to generic color selection.
	// It was jumping like crazy but I found this:
	// http://stackoverflow.com/questions/16222252/jquery-slide-left-to-right-makes-div-jump-up-on-slide
	$('div.go-back-to-generic img').click(function() {
		var generic_color = $(this).parent().parent().attr('color');
		$('div.brick-specific-colors.' + generic_color).effect('slide', {"direction" : "right",  "mode" : "hide"}, 500);
		$('div#brick-generic-colors').effect('slide', {"direction" : "left", "mode" : "show"}, 500);
	});

	$('div.color-sample.specific').click(function() {
		var previous_specific_color = $('div.color-sample.specific.color-selected').attr('id');
		var current_specific_color =  $(this).attr('id');

		// If the user clicked on the same color again then do nothing
		if (previous_specific_color == current_specific_color) {
			return;
		}

		$('div.color-sample.specific').removeClass('color-selected');
		$(this).addClass('color-selected');
		updateLegoParts();

		// Remember the specific color we selected for this generic color
		var generic_color = $(this).closest('div.brick-specific-colors').attr('color');
		specific_color_memory[generic_color] = current_specific_color;
	});

	// Remove/Add the class the changes the text to italics and changes the shadow
	$('div.lego-type').click(function() {
		var previous_lego_type = $('div.lego-type.color-selected').attr('id');
		var current_lego_type = $(this).attr('id');

		if (previous_lego_type == current_lego_type) {
			return;
		}

		$('div.lego-type').removeClass('color-selected');
		$(this).addClass('color-selected');
		updateLegoParts();
	});

	//
	// Keyword text box
	//
	// http://stackoverflow.com/questions/4220126/run-javascript-function-when-user-finishes-typing-instead-of-on-key-up
	// When the user stops typing for doneTypingInterval ms call updateLegoParts
	var typingTimer; //timer identifier
	var doneTypingInterval = 500; //time in ms

	//on keyup, start the countdown
	$('#keyword_filter').keyup(function(){
		typingTimer = setTimeout(updateLegoParts, doneTypingInterval);

		// If it is blank then unfade everything.  If not though then fade out the other search parameters
		var value = $('input#keyword_filter').val();
		if (value) {
			$("#non-color-attributes").fadeTo('slow', 0.08);
			$("#lego-id-wrapper").fadeTo('slow', 0.08);
			$("#brick-color-selection").fadeTo('slow', 0.08);
		} else {
			$("#non-color-attributes").fadeTo('slow', 1);
			$("#lego-id-wrapper").fadeTo('slow', 1);
			$("#brick-color-selection").fadeTo('slow', 1);
		}
	});

	$('#lego_id').keyup(function(){
		typingTimer = setTimeout(updateLegoParts, doneTypingInterval);

		// If it is blank then unfade everything.  If not though then fade out the other search parameters
		var value = $('input#lego_id').val();
		if (value) {
			$("#brick-color-selection").fadeTo('slow', 0.08);
			$("#non-color-attributes").fadeTo('slow', 0.08);
			$("#keyword-wrapper").fadeTo('slow', 0.08);
		} else {
			$("#brick-color-selection").fadeTo('slow', 1);
			$("#non-color-attributes").fadeTo('slow', 1);
			$("#keyword-wrapper").fadeTo('slow', 1);
		}
	});


	//on keydown, clear the countdown
	$('#keyword_filter').keydown(function(){
		clearTimeout(typingTimer);
	});

	$('#lego_id').keydown(function(){
		clearTimeout(typingTimer);
	});

	// When the page loads fade in/out search filters based on whether or not there is
        // text in the keyword or lego-id input fields
	var keyword_value = $('input#keyword_filter').val();
	var lego_id_value = $('input#lego_id').val();
	if (lego_id_value) {
		$("#brick-color-selection").fadeTo('slow', 0.08);
	$("#non-color-attributes").fadeTo('slow', 0.08);
		$("#keyword-wrapper").fadeTo('slow', 0.08);
	} else if (keyword_value) {
		$("#non-color-attributes").fadeTo('slow', 0.08);
		$("#lego-id-wrapper").fadeTo('slow', 0.08);
	} else {
		$("#brick-color-selection").fadeTo('slow', 1);
		$("#non-color-attributes").fadeTo('slow', 1);
		$("#keyword-wrapper").fadeTo('slow', 1);
		$("#lego-id-wrapper").fadeTo('slow', 1);
	}
});

