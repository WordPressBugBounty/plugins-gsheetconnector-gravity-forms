jQuery(document).ready(function () {

   jQuery('.gravityforms-gs-set-auth-expired-adds-interval').click(function () {
      var data = {
         action: 'gravityforms_gs_set_auth_expired_adds_interval',
         security: jQuery('#gravityforms_gs_auth_expired_adds_ajax_nonce').val()
      };

      jQuery.post(ajaxurl, data, function (response) {
         if (response.success) {
            jQuery('.gravityforms-gs-auth-expired-adds').slideUp('slow');
         }
      });
   });

   jQuery('.gravityforms-gs-close-auth-expired-adds-interval').click(function () {
      var data = {
         action: 'gravityforms_gs_close_auth_expired_adds_interval',
         security: jQuery('#gravityforms_gs_auth_expired_adds_ajax_nonce').val()
      };

      jQuery.post(ajaxurl, data, function (response) {
         if (response.success) {
            jQuery('.gravityforms-gs-auth-expired-adds').slideUp('slow');
         }
      });
   });

});
jQuery(document).ready(function () {
   jQuery('.gsheetconnector-addons-list').each(function () {
      if (jQuery(this).html().trim().length === 0) {
         jQuery(this).addClass('blank_div');
         jQuery(this).prev('h2').hide();
      }
   });
});