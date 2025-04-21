jQuery(document).ready(function () {

   var sheetId = jQuery('#gf-gs-sheet-id').val();
   var tabId = jQuery('#gf-gs-tab-id').val();


   if (sheetId != "" && tabId != "") {
      jQuery("#sheet_url").html('<label><b>Google Sheet URL:</b> </label><a class="gr_sheet_url" href="https://docs.google.com/spreadsheets/d/' + sheetId + '/edit#gid=' + tabId + '" target="_blank">Sheet URL</a>');
      // jQuery("#sheet_iframe").html('<iframe src="https://docs.google.com/spreadsheets/d/'+sheetId+'/edit#gid='+tabId+'" style="position:absolute; width:100%; height:100%; border:none;  padding:0; overflow:hidden; z-index:999999;border: 2px solid #000000; margin-left: -22px; margin-top: 20%;" />');

   }
   else {
      if (sheetId != "") {
         var tabId = jQuery('#gf-gs-tab-id').val(0);
         var tabId = 0;
         jQuery("#sheet_url").html('<label><b>Google Sheet URL:</b> </label><a class="gr_sheet_url" href="https://docs.google.com/spreadsheets/d/' + sheetId + '/edit#gid=' + tabId + '" target="_blank">Sheet URL</a>');
      }

   }
   jQuery("#gr_pre_sheet").on('click', function () {
      console.log('here scroll');
      jQuery([document.documentElement, document.body]).animate({
         scrollTop: jQuery("#sheet_iframe").offset().top
      }, 1000);
   });
   /**
    * verify the api code
    * @since 1.0
    */
   jQuery(document).on('click', '#save-code', function () {
      jQuery(".loading-sign").addClass("loading");
      var data = {
         action: 'verify_code_integation',
         code: jQuery('#gfgs-code').val(),
         security: jQuery('#gf-ajax-nonce').val()
      };
      jQuery.post(ajaxurl, data, function (response) {
         if (response == -1) {
            return false; // Invalid nonce
         }

         if (!response.success) {


            jQuery(".loading-sign").removeClass("loading");
            jQuery("#gsheet-validation-message").empty();
            jQuery("<span class='error-message'>Access code Can't be blank</span>").appendTo('#gsheet-validation-message');
         } else {
            jQuery(".loading-sign").removeClass("loading");
            jQuery("#gsheet-validation-message").empty();
            jQuery("<span class='gs-valid-message'>Your Google Access Code is Authorized and Saved.</span>").appendTo('#gsheet-validation-message');
            // setTimeout(function () {
            //    location.reload();
            // }, 7000);

            setTimeout(function () {
               window.location.href = jQuery("#redirect_auth_gravityforms").val();
            }, 1000);
         }
      });

   });
   /**
    * deactivate the api code
    * @since 1.0
    */
   jQuery(document).on('click', '#deactivate-log', function () {
      jQuery(".loading-sign-deactive").addClass("loading");
      var txt;
      var r = confirm("Are You sure you want to deactivate Google Integration ?");
      if (r == true) {
         var data = {
            action: 'deactivate_gs_code_integation',
            security: jQuery('#gf-ajax-nonce').val()
         };
         jQuery.post(ajaxurl, data, function (response) {
            if (response == -1) {
               return false; // Invalid nonce
            }

            if (!response.success) {
               alert('Error while deactivation');
               jQuery(".loading-sign-deactive").removeClass("loading");
               jQuery("#deactivate-message").empty();

            } else {
               jQuery(".loading-sign-deactive").removeClass("loading");
               jQuery("#deactivate-message").empty();
               jQuery("<span class='gsheet-valid-message'>Your account is removed. Reauthenticate again to integrate gravityforms with Google Sheet.</span>").appendTo('#deactivate-message');
               setTimeout(function () {
                  location.reload();
               }, 1000);
            }
         });
      } else {
         jQuery(".loading-sign-deactive").removeClass("loading");
      }



   });

   /**
    * Clear debug
    */
   jQuery(document).on('click', '.clear-debug', function () {
      jQuery(".clear-loading-sign").addClass("loading");
      var data = {
         action: 'gfgs_clear_log',
         security: jQuery('#gf-ajax-nonce').val()
      };
      jQuery.post(ajaxurl, data, function (response) {
         if (response == -1) {
            return false; // Invalid nonce
         }
         var clear_msg = response.data;
         if (response.success) {
            jQuery(".clear-loading-sign").removeClass("loading");
            jQuery("#gsheet-validation-message").empty();
            jQuery("<span class='gs-valid-message'>" + clear_msg + "</span>").appendTo('#gsheet-validation-message');
            setTimeout(function () {
               location.reload();
            }, 1000);
         }
      });
   });
   /**
       * Clear debug for system status tab
       */
   jQuery(document).on('click', '.clear-content-logs-gf', function () {

      jQuery(".clear-loading-sign-logs-gf").addClass("loading");
      var data = {
         action: 'gf_clear_debug_log',
         security: jQuery('#gf-ajax-nonce').val()
      };
      jQuery.post(ajaxurl, data, function (response) {
         if (response == -1) {
            return false; // Invalid nonce
         }

         if (response.success) {
            jQuery(".clear-loading-sign-logs-gf").removeClass("loading");
            jQuery('.clear-content-logs-msg-gf').html('Logs are cleared.');
            setTimeout(function () {
               location.reload();
            }, 1000);
         }
      });
   });



   /**
  * Display Error logs
  */

   jQuery(document).on('click', '.closeView', function () {
      jQuery('.closecf7View').text("View").removeClass('closeView');
      jQuery('button').addClass('gravity-logs');
      jQuery('.system-Error-logs-gf').hide(); // Instead of toggle, we directly hide it
   });

   jQuery(document).on('click', '.gravity-logs', function () {
      jQuery('.gravity-logs').text("Close").addClass('closeView');
      jQuery('button').removeClass('gravity-logs');
      jQuery('.system-Error-logs-gf').show(); // Instead of toggle, we directly show it
   });

   jQuery(document).ready(function ($) {
      // Hide .cf7-system-Error-logs initially
      $('.system-Error-logs-gf').hide();

      // Prevent system-Error-logs-gf from toggling when clicking the div itself
      $('.system-Error-logs-gf').on('click', function (e) {
         e.stopPropagation(); // Prevents the click event from propagating further
      });
   });






   /**
    * Sync with google account to fetch latest sheet and tab name list.
    */
   jQuery(document).on('click', '#gfgs-sync', function () {

      jQuery(this).parent().children(".loading-sign").addClass("loading");
      var integration = jQuery(this).data("init");
      var data = {
         action: 'sync_with_google_account',
         isajax: 'yes',
         isinit: integration,
         security: jQuery('#gf-ajax-nonce').val()
      };

      jQuery.post(ajaxurl, data, function (response) {

         if (response == -1) {
            return false; // Invalid nonce
         }

         if (response.data.success === "yes") {
            jQuery(".loading-sign").removeClass("loading");
            jQuery("#gsheet-validation-message").empty();
            jQuery("<span class='gsheet-valid-message'>Fetched all sheet details.</span>").appendTo('#gsheet-validation-message');
         } else {
            jQuery(this).parent().children(".loading-sign").removeClass("loading");
            location.reload(); // simply reload the page
         }
      });
   });
   /** 
    * Get tab name list 
    */
   jQuery(document).on("change", "#sheetname", function () {

      var sheetnames = jQuery(this).val();
      var nonce = jQuery('#gf-ajax-nonce').val();

      jQuery(".loading-sign").addClass("loading");
      var data = {
         action: 'get_tabname_list',
         sheetname: sheetnames,
         security: nonce
      };


      jQuery.post(ajaxurl, data, function (response) {
         jQuery(".loading-sign").removeClass("loading");
         if (response == -1) {
            return false; // Invalid nonce
         }
         if (response.success) {
            jQuery('#tabname').html(html_decode(response.data));
            jQuery(".loading-sign").removeClass("loading");
         }
      });
   });

   // TODO : Combine into one
   jQuery(document).on("change", "#tabname", function () {
      var sheetname = jQuery('#sheetname').val();
      var tabname = jQuery(this).val();
      var nonce = jQuery('#gf-ajax-nonce').val();
      jQuery(".loading-sign").addClass("loading");
      var data = {
         action: 'get_sheet_id_name',
         sheetname: sheetname,
         tabname: tabname,
         security: nonce

      };
      jQuery.post(ajaxurl, data, function (response) {

         if (response == -1) {
            return false; // Invalid nonce
         }

         if (response.success) {

            jQuery('#gaddon-setting-row-sheeturl>td').html(html_decode(response.data));

         }
         jQuery(".loading-sign").removeClass("loading");
      });
   });

   function html_decode(input) {
      var doc = new DOMParser().parseFromString(input, "text/html");
      return doc.documentElement.textContent;
   }

   jQuery('.update-renewal').click(function () {
      var data = {
         action: 'update_license_expiration',
         security: jQuery('#gsgf_ajax_nonce').val()
      };

      jQuery.post(ajaxurl, data, function (response) {
         if (response.success) {
            jQuery('.gsgf-renew').slideUp('slow');
         }
      });
   });

   jQuery('.hide-renew-box').click(function () {
      var data = {
         action: 'hide_renew_box',
         security: jQuery('#gsgf_ajax_nonce').val()
      };

      jQuery.post(ajaxurl, data, function (response) {
         if (response.success) {
            jQuery('.gsgf-renew').slideUp('slow');
         }
      });

   });

   jQuery('#gform-settings').submit(function (e) {

      var sheetName = jQuery('#gf-gs-sheet-name').val();
      var sheetId = jQuery('#gf-gs-sheet-id').val();
      var tabName = jQuery('#gf-gs-sheet-tab-name').val();
      var tabId = jQuery('#gf-gs-tab-id').val();

      jQuery('#error-sheetName').html('');
      jQuery('#error-sheetId').html('');
      jQuery('#error-tabName').html('');
      jQuery('#error-tabId').html('');

      if (sheetName == "") {
         e.preventDefault();
         jQuery('#error-sheetName').html('This field is required.');
      }

      if (sheetId == "") {
         e.preventDefault();
         jQuery('#error-sheetId').html('This field is required.');
      }

      if (tabName == "") {
         e.preventDefault();
         jQuery('#error-tabName').html('This field is required.');
      }

      if (tabId == "") {
         e.preventDefault();
         jQuery('#error-tabId').html('This field is required.');
      }
   });

   if (jQuery('.gform-settings-field__display_note :first-child').hasClass('gs-display-note')) {
      jQuery("#gform-settings-save").css("display", "none");
   }


});


// Msg Hide ///

jQuery(document).ready(function ($) {
   // Check if the message has already been hidden by looking in localStorage
   if (localStorage.getItem('googleDriveMsgHidden') === 'true') {
      jQuery('#google-drive-msg').hide(); // Hide the message if it's already hidden
   }

   // On button click, hide the #google-drive-msg div and store the hidden state in localStorage
   jQuery('.button_gravityformgsc').on('click', function () {
      jQuery('#google-drive-msg').hide(); // Hide the message
      localStorage.setItem('googleDriveMsgHidden', 'true'); // Save the hidden state in localStorage
   });

   // On #deactivate-log click, show the #google-drive-msg div and clear localStorage
   jQuery('#deactivate-log').on('click', function () {
      jQuery('#google-drive-msg').show(); // Show the message
      localStorage.removeItem('googleDriveMsgHidden'); // Remove the hidden state from localStorage
   });
});
jQuery(document).ready(function ($) {
   $('.install-plugin-btn').on('click', function () {
      var button = $(this);
      var pluginSlug = button.data('plugin');
      var downloadUrl = button.data('download');
      var loader = button.find('.loaderimg');

      loader.css('display', 'inline-block'); // Show loader

      button.html('<img src="' + loader.attr('src') + '" alt="Loading..."> Installing...')
         .prop('disabled', true);

      $.ajax({
         url: ajaxurl,
         type: 'POST',
         data: {
            action: 'install_plugin',
            plugin_slug: pluginSlug,
            download_url: downloadUrl
         },
         success: function (response) {
            if (response.success) {
               // Hide the "Install" button
               button.hide();

               // Show the corresponding "Activate" button
               button.closest('.button-bar').find('.activate-plugin-btn').show();
            } else {
               alert('Installation failed: ' + (response.data?.message || 'Unknown error'));
               button.html('Install').prop('disabled', false);
            }
         },

         error: function () {
            button.html('Install').prop('disabled', false);
            alert('Error installing the plugin.');
         }
      });
   });

   // Plugin Activation
   $(document).on('click', '.activate-plugin-btn', function () {
      var button = $(this);
      var pluginSlug = button.data('plugin');
      var loader = button.find('.loaderimg');
      loader.css('display', 'inline-block'); // Show loader

      button.html('<img src="' + loader.attr('src') + '" alt="Loading..."> Activating...')
         .prop('disabled', true);

      $.ajax({
         url: ajaxurl,
         type: 'POST',
         data: {
            action: 'activate_plugin',
            plugin_slug: pluginSlug
         },
         success: function (response) {
            if (response.success) {
               button.text('Activated').prop('disabled', true);
               loader.hide();
               location.reload();

            } else {
               button.html('Activate').prop('disabled', false);
               alert('Activation failed: ' + (response.data?.message || 'Unknown error'));
            }
         },
         error: function () {
            button.html('Activate').prop('disabled', false);
            alert('Error activating the plugin.');
         }
      });
   });
   $('.deactivate-plugin').on('click', function () {
      var pluginSlug = $(this).data('plugin');

      if (!pluginSlug) {
         alert('Plugin slug not found.');
         return;
      }

      $.ajax({
         url: ajaxurl,
         type: 'POST',
         dataType: 'json', // Ensure JSON response
         data: {
            action: 'deactivate_plugin',
            plugin_slug: pluginSlug
         },
         success: function (response) {
            if (response.success) {
               alert(response.data); // Display success message
               location.reload();
            }
         },
         error: function (xhr, status, error) {
            console.error(xhr.responseText);
            alert('AJAX error: ' + error);
         }
      });
   });
});