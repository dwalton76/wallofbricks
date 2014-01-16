
function saveStoreIDCountryCookies() {
	// Remember the store in a cookie
	var pab_store_id = $('#pab_store_id').val();
	$.cookie("pab_store_id", pab_store_id);

	// Remember the country in a cookie
	var pab_country = $('#country').val();
	$.cookie("pab_country", pab_country);
}

//
// The html that is loaded via updateLegoParts also needs some jquery.
// Any jquery needed by that html should go here.
//
function postAjaxCode() {
	$('div.lego-part').on('click', function(event) {

		// Change the way it looks so the user knows the click worked
		$('div.lego-part').removeClass('selected shadow');
		$(this).addClass('selected shadow');

		// Update the hidden input that tells the form which part was selected
		var id = $(this).attr('id');
		$('input#part-id').val(id);
	});

	$('#save-part').click(function() {
		saveStoreIDCountryCookies();
	});

	$('.dom-link').click(function() {
		if ($(this).attr("url")) {
			window.location = $(this).attr("url");
		}
	});

   // When the user clicks on the "+ Wish List" button we submit the "form" via ajax
   // http://net.tutsplus.com/tutorials/javascript-ajax/submit-a-form-without-page-refresh-using-jquery/
   $('.jquery_add_set.wishlist').click(function() {
      var myform = $(this).closest('.set_info');
      //var id = myform.find("input[name='add_id']").val();
      var id = $(this).attr('add_id');
      var own_status = myform.find(".own-status");
      var wishlist_status = myform.find(".wishlist-status");
      var username = $("#username").html();
      var dataString = 'id=' + id + '&username=' + username + '&action=add';

      $.ajax({
        type: "POST",
        url: "/ajax-manage-sets-wishlist.php",
        data: dataString,
        success: function() {
          own_status.html('');
          wishlist_status.html("<img src='/images/wishlist.png' width='50' alt='On Wish List' />");
        }
      });
      return false;
   });

   $('.jquery_remove_set.wishlist').click(function() {
      var myform = $(this).closest('.set_display');
      var id = $(this).attr('remove_id');
      var username = $("#username").html();
      var dataString = 'id=' + id + '&username=' + username + '&action=remove';

      $.ajax({
        type: "POST",
        url: "/ajax-manage-sets-wishlist.php",
        data: dataString,
        success: function() {
          myform.fadeOut();
        }
      });
      return false;
   });

   // When the user clicks on the "+ I Own It" button we submit the "form" via ajax
   // http://net.tutsplus.com/tutorials/javascript-ajax/submit-a-form-without-page-refresh-using-jquery/
   $('.jquery_add_set.owned').click(function() {
      var myform = $(this).closest('.set_info');
      var id = $(this).attr('add_id');

      var own_status = myform.find(".own-status");
      var wishlist_status = myform.find(".wishlist-status");
      var username = $("#username").html();
      var dataString = 'id=' + id + '&username=' + username + '&action=add';

      $.ajax({
        type: "POST",
        url: "/ajax-manage-sets-i-own.php",
        data: dataString,
        success: function() {
          own_status.html("<img src='/images/Checkmark-128.png' width='50' alt='I Own It' />");
          wishlist_status.html('');
        }
      });
      return false;
   });

   // console.log(".jquery_remove_set.owned loaded");
   $('.jquery_remove_set.owned').click(function() {
      var myform = $(this).closest('.set_display');
      var id = $(this).attr('remove_id');
      var own_status = myform.find(".own-status");
      var wishlist_status = myform.find(".wishlist-status");
      var username = $("#username").html();
      var dataString = 'id=' + id + '&username=' + username + '&action=remove';

      $.ajax({
        type: "POST",
        url: "/ajax-manage-sets-i-own.php",
        data: dataString,
        success: function() {
          myform.fadeOut();
        }
      });
      return false;
   });

}

$(document).ready(function() {
	// Applies to all pages
	// Update the "View A Store" link so that it points to the store saved in the cookie
	var view_a_store = $('#view-a-store');
	if (view_a_store.attr('href') == "/pab-display.php") {
		var pab_store_id = $.cookie("pab_store_id");
		var pab_country = $.cookie("pab_country");
		if (pab_country && pab_store_id) {
			view_a_store.attr('href', '/pab-display.php?pab_store_id='+ pab_store_id +'&country=' + pab_country);
		}
	}

	// Only applies to set.php
	var img_height = $('div#set_display_big img').height();
	if (img_height != 0) {
		$('div#set_display_big').height(img_height + 40);
	}

	//
	// If a td has the class td-link then the td must also have a url=''.
	// Navigate to that url when the user clicks that td.
	//
	$('td.td-link').click(function() {
		if ($(this).attr("url")) {
			window.location = $(this).attr("url");
		}
	});

	//
	// pab-update page...only show the 'Then Go' methods that make sense according to the "Start From The" selection
	//
	$("input[name='starting_cell']").change(function(){
		var value = $(this).val();
		var then_go_value = $("input[name='then_go']").val();
		$('.then-go').hide();
		// console.log("VALUE : " + value);
		// console.log("THENGO: " + then_go_value);

		if (value == 'topleft') {
			$('.startleft').show();
			$('.starttop').show();
			var then_go_visible= $("input[name='then_go']:checked").is(':visible');
			if (!then_go_visible) {
				$('#leftrightsnake').prop('checked',true);
			}

		} else if (value == 'bottomleft') {
			$('.startleft').show();
			$('.startbottom').show();
			var then_go_visible= $("input[name='then_go']:checked").is(':visible');
			if (!then_go_visible) {
				$('#leftrightsnake').prop('checked',true);
			}

		} else if (value == 'topright') {
			$('.startright').show();
			$('.starttop').show();
			var then_go_visible= $("input[name='then_go']:checked").is(':visible');
			if (!then_go_visible) {
				$('#rightleftsnake').prop('checked',true);
			}

		} else if (value == 'bottomright') {
			$('.startright').show();
			$('.startbottom').show();
			var then_go_visible= $("input[name='then_go']:checked").is(':visible');
			if (!then_go_visible) {
				$('#rightleftsnake').prop('checked',true);
			}
		}
	});

	// Index page...handle "Learn More" div
	$('div#learn-more').click(function() {
		window.location = $(this).attr("url");
	});


	// Menu Header
	// http://www.joepettersson.com/demo/jquery-powered-navigation-bar/
	// ======================================================
	// Requried: Navigation bar drop-down
	$("nav ul li").hover(function() {
		$(this).addClass("active");
		$(this).find("ul").show().animate({opacity: 1}, 400);
		},function() {
		$(this).find("ul").hide().animate({opacity: 0}, 200);
		$(this).removeClass("active");
	});

	// Requried: Addtional styling elements
	$('nav ul li ul li:first-child').prepend('<li class="arrow"></li>');
	$('nav ul li:first-child').addClass('first');
	$('nav ul li:last-child').addClass('last');
	$('nav ul li ul').parent().append('<span class="dropdown"></span>').addClass('drop');

	postAjaxCode();

});

