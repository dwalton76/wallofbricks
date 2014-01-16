
$(document).ready(function() {
    $("#zoom-set").elevateZoom({
        zoomType : "lens",
        lensShape : "round",
        scrollZoom : true,
        lensSize : 250
    });

   // http://net.tutsplus.com/tutorials/javascript-ajax/submit-a-form-without-page-refresh-using-jquery/
   $('.jquery_set_inventory_add_part').click(function() {
      var parts_list = $(this).closest('#parts-list');
      var td = $(this).closest('.td-link');
      var brick_id = td.attr('brick_id');
      var set_id   = $("#set_id").val();
      var model    = $("#model").val();
      var filename = $("#filename").val();
      var page     = $("#page").val();
      var username = $("#username").html();
      var dataString = 'set_id=' + set_id + '&model=' +model+ '&filename=' +filename+ '&page=' +page+ '&brick_id=' +brick_id+ '&username=' +username+ '&action=add';


      var used = td.find("span.used");
      var avail = td.find("span.available");
      var used_val = parseInt(used.html(), 10);
      var avail_val = parseInt(avail.html(), 10);
      console.log("used: " + used_val)
      console.log("avail: " + avail_val)

      if (!avail_val) {
        return false;
      }

      $.ajax({
        type: "POST",
        url: "/ajax-manage-sets-model-brick-index.php",
        data: dataString,
        success: function() {
            
            if (!used_val) {
                td.addClass('brick-on-page');
            }

            used.html(used_val + 1)
            avail.html(avail_val - 1)
          /*
          own_status.html('');
          wishlist_status.html("<img src='/images/wishlist.png' width='50' alt='On Wish List' />");
          */
        }
      });
      return false;
   });

   // http://net.tutsplus.com/tutorials/javascript-ajax/submit-a-form-without-page-refresh-using-jquery/
   $('.jquery_set_inventory_del_part').click(function() {
      var parts_list = $(this).closest('#parts-on-this-page');
      var td = $(this).closest('.td-link');
      var brick_id = td.attr('brick_id');
      var set_id   = $("#set_id").val();
      var model    = $("#model").val();
      var filename = $("#filename").val();
      var page     = $("#page").val();
      var username = $("#username").html();
      var dataString = 'set_id=' + set_id + '&model=' +model+ '&filename=' +filename+ '&page=' +page+ '&brick_id=' +brick_id+ '&username=' +username+ '&action=del';

      /*
      console.log('brick_id: ' + brick_id);
      console.log('set_id: ' + set_id);
      console.log('filename: ' + filename);
      console.log('page: ' + page);
      console.log('username: ' + username);
      console.log('dataString: ' + dataString);
        */

      var used = td.find("span.used");
      var avail = td.find("span.available");
      var used_val = parseInt(used.html(), 10);
      var avail_val = parseInt(avail.html(), 10);

      if (!used_val) {
        return false;
      }

      $.ajax({
        type: "POST",
        url: "/ajax-manage-sets-model-brick-index.php",
        data: dataString,
        success: function() {
            if (used_val == 1) {
                td.removeClass('brick-on-page');
            }
            used.html(used_val - 1)
            avail.html(avail_val + 1)
        }
      });
      return false;
   });


});

