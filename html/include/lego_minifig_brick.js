//
// Used by brick.php
//
function updateMinifigsByBrick() {
	var brick_id = $("#brick_id").html();
	var page_number = $("#page").html();
	var last_page = $("#last_page").html();
	var dataString = 'id=' + brick_id + "&page=" + page_number + "&last_page=" + last_page;

	$.ajax({
		type: "POST",
		url: "/ajax-get-minifigs-with-brick.php",
		data: dataString,
		cache: false,
		success: function(response) {
			$('div#sets-with-this-brick').html(response);
			postAjaxCode();

			var show_prev = $("#show-prev-button").html();
			var show_next = $("#show-next-button").html();

			if (show_prev == 1) {
				$('a#prev-set-by-brick').show();
			} else {
				$('a#prev-set-by-brick').hide();
			}

			if (show_next == 1) {
				$('a#next-set-by-brick').show();
			} else {
				$('a#next-set-by-brick').hide();
			}
		}
	});
}

$(document).ready(function() {
	updateMinifigsByBrick();

	$('#prev-set-by-brick').click(function() {
		var page_number = $("#page").html();
		$("#page").html(parseInt(page_number) - 1);
		updateMinifigsByBrick();
	});

	$('#next-set-by-brick').click(function() {
		var page_number = $("#page").html();
		$("#page").html(parseInt(page_number) + 1);
		updateMinifigsByBrick();
	});
});



