
function displayBinsBetweenDates() {
	var min_date = $("input[name='min-date']").val();
	var max_date = $("input[name='max-date']").val();

	$('td.td-link').each(function() {
		// There is a bug in the HTML5 date input field...it will let you enter bogus dates like June 31st.
		// If the user does this it will throw off the code below.
		// TODO: fix this corner case
		var updated_on = $(this).find('span.updated-on').html();
		var td_guts = $(this).find('div.td-guts');
		var updated_on_obj = new Date(updated_on);
		var min_date_obj = new Date(min_date);
		var max_date_obj = new Date(max_date);

		if (updated_on_obj < min_date_obj) {
			td_guts.fadeTo('slow', 0.08);
		} else if (updated_on_obj > max_date_obj) {
			td_guts.fadeTo('slow', 0.08);
		} else {
			td_guts.fadeTo('slow', 1);
		}
	});
}

function applyBrickFadeFilters() {
	console.log("applyBrickFadeFilters called");
	var filter = $("input[name='wall-filter']:checked").val();

	if (!filter) {
		filter = $("input[name='wall-filter']").val();
	}

	$('div.filter-option').hide();
	$('div#' + filter + '-options').show();

	// Display Everything
	if (filter == 'filter-default') {
		$('td.td-link').each(function() {
			$(this).find('div.td-guts').fadeTo('slow', 1);
		});

	// Only show duplicate parts
	} else if (filter == 'filter-duplicates') {
		$('td.td-link').each(function() {
			var td_guts = $(this).find('div.td-guts');
			if (td_guts.hasClass('duplicate')) {
				td_guts.fadeTo('slow', 0.08);
			} else {
				td_guts.fadeTo('slow', 1);
			}
		});

	} else if (filter == 'filter-age') {
		displayBinsBetweenDates();

	} else if (filter == 'filter-color') {
		var target_color = $('div.color-filter.color-selected').attr('id');
		var target_type = $('div.lego-type.color-selected').html().toLowerCase();
		var target_dimension_x = $('input#dimension_x').val();
		var target_dimension_y = $('input#dimension_y').val();
		var target_dimensions = target_dimension_x + "x" + target_dimension_y;
		var target_on_pab_wall = 0;
		var target_shortage_parts = 0;

		if ($('input#on_pab_wall').is(':checked')) {
			target_on_pab_wall = 1;
		}

		if ($('input#shortage_parts').is(':checked')) {
			target_shortage_parts = 1;
			$('span.qty').hide()
			$('span.qty-shortage').show()
		} else {
			$('span.qty').show()
			$('span.qty-shortage').hide()
                }

		$('td.td-link').each(function() {
			var td_guts = $(this).find('div.td-guts');
			var color = td_guts.attr('color');
			var brick_type = td_guts.attr('brick_type');
			var brick_dimensions = td_guts.attr('brick_dimensions');
			var brick_on_pab_wall= td_guts.attr('on_pab_wall');
			var brick_shortage = td_guts.attr('shortage');

			var show_part = 1;

			// Filter by color
			if (target_color == "all-colors" || color == target_color) {
			} else {
				show_part = 0;
			}

			// Filter by type
			if (show_part && target_type != "all") {
				if (target_type == "other" ) {
					if (brick_type == "brick" || brick_type == "plate" || brick_type == "tile" || brick_type == "slope" || brick_type == "technic" || brick_type == "minifig") {
						show_part = 0;
					}
				} else {
					if (brick_type != target_type) {
						show_part = 0;
					}
				}
			}

			// Filter by dimensions
			if (show_part && target_dimension_x != 0 && target_dimension_y !=0 && brick_dimensions != null) {
				if (brick_dimensions.indexOf(target_dimensions) < 0) {
					show_part = 0;
				}
			}

			if (show_part && target_on_pab_wall) {
				if (brick_on_pab_wall == 0) {
					show_part = 0;
				}
			}

			if (show_part && target_shortage_parts) {
				if (brick_shortage == 0) {
					show_part = 0;
				}
			}

			if (show_part) {
				td_guts.fadeTo('slow', 1);
			} else {
				td_guts.fadeTo('slow', 0.08);
			}
		});
	}
}

$(document).ready(function() {

	$('div#increment_dimension_x').click(function() {
		var x = $('span#dimension_x_display').html();
		var y = $('span#dimension_y_display').html();
		x++;
		$('span#dimension_x_display').html(x);
		$('input#dimension_x').val(x);
	});


	$('div#decrement_dimension_x').click(function() {
		var x = $('span#dimension_x_display').html();
		var y = $('span#dimension_y_display').html();

		if (x > 0) {
			x--;
			$('span#dimension_x_display').html(x);
			$('input#dimension_x').val(x);
		}
	});

	$('div#increment_dimension_y').click(function() {
		var x = $('span#dimension_x_display').html();
		var y = $('span#dimension_y_display').html();
		y++;
		$('span#dimension_y_display').html(y);
		$('input#dimension_y').val(y);
	});

	$('div#decrement_dimension_y').click(function() {
		var x = $('span#dimension_x_display').html();
		var y = $('span#dimension_y_display').html();

		if (y > 0) {
			y--;
			$('span#dimension_y_display').html(y);
			$('input#dimension_y').val(y);
		}
	});

	$('div#filter-color-options div#increment_dimension_x').click(function() {
		applyBrickFadeFilters();
	});

	$('div#filter-color-options div#decrement_dimension_x').click(function() {
		applyBrickFadeFilters();
	});

	$('div#filter-color-options div#increment_dimension_y').click(function() {
		applyBrickFadeFilters();
	});

	$('div#filter-color-options div#decrement_dimension_y').click(function() {
		applyBrickFadeFilters();
	});

	// When the user selects the radio button for their wall filter display the corresponding options
	$("input[name='wall-filter']").change(function() {
		applyBrickFadeFilters();
	});
	applyBrickFadeFilters(); // And on page load

	$("input[name='min-date']").change(function() {
		applyBrickFadeFilters();
	});

	$("input[name='max-date']").change(function() {
		applyBrickFadeFilters();
	});

	$('div.color-filter div.color-fill').click(function() {
		var parent = $(this).parent();

		// Remove/Add the class that changes the text to italics and changes the shadow
		$('div.color-filter').removeClass('color-selected');
		parent.addClass('color-selected');

		applyBrickFadeFilters();
	});

	$('div#filter-color-options div.lego-type').click(function() {
		var previous_lego_type = $('div.lego-type.color-selected').attr('id');
		var current_lego_type = $(this).attr('id');

		if (previous_lego_type != current_lego_type) {
			$('div.lego-type').removeClass('color-selected');
			$(this).addClass('color-selected');
			applyBrickFadeFilters();
		}
	});

	$("input[name='on_pab_wall']").change(function() {
		applyBrickFadeFilters();
	});

	$("input[name='shortage_parts']").change(function() {
		applyBrickFadeFilters();
	});
});

