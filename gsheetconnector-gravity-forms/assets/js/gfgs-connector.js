jQuery(document).ready(function () {
  var sheetId = jQuery("#gf-gs-sheet-id").val();
  var tabId = jQuery("#gf-gs-tab-id").val();

  if (sheetId != "" && tabId != "") {
    jQuery("#sheet_url").html(
      '<a class="gr_sheet_url sheet_url common-sheet-url btn-sheet mr-10 text-dark text-decoration-none mt-20 blinking-button" href="https://docs.google.com/spreadsheets/d/' +
        sheetId +
        "/edit#gid=" +
        tabId +
        '" target="_blank">View Spreadsheet</a><a class="gf-sheet-reset-button btn btn-reset">Reset</a>',
    );
    // jQuery("#sheet_iframe").html('<iframe src="https://docs.google.com/spreadsheets/d/'+sheetId+'/edit#gid='+tabId+'" style="position:absolute; width:100%; height:100%; border:none;  padding:0; overflow:hidden; z-index:999999;border: 2px solid #000000; margin-left: -22px; margin-top: 20%;" />');
  } else {
    if (sheetId != "") {
      var tabId = jQuery("#gf-gs-tab-id").val(0);
      var tabId = 0;
      jQuery("#sheet_url").html(
        '<a class="gr_sheet_url sheet_url common-sheet-url btn-sheet mr-10 text-dark text-decoration-none mt-20 blinking-button" href="https://docs.google.com/spreadsheets/d/' +
          sheetId +
          "/edit#gid=" +
          tabId +
          '" target="_blank">View Spreadsheet</a><a class="gf-sheet-reset-button btn btn-reset">Reset</a>',
      );
    }
  }
  jQuery("#gr_pre_sheet").on("click", function () {
    console.log("here scroll");
    jQuery([document.documentElement, document.body]).animate(
      {
        scrollTop: jQuery("#sheet_iframe").offset().top,
      },
      1000,
    );
  });
  /**
   * verify the api code
   * @since 1.0
   */
  jQuery(document).on("click", "#save-code", function () {
    jQuery(".loading-sign").addClass("loading");
    var data = {
      action: "verify_code_integation",
      code: jQuery("#gfgs-code").val(),
      security: jQuery("#gf-ajax-nonce").val(),
    };
    jQuery.post(ajaxurl, data, function (response) {
      if (response == -1) {
        return false; // Invalid nonce
      }

      if (!response.success) {
        jQuery(".loading-sign").removeClass("loading");
        jQuery("#gsc-validation-message").empty();
        jQuery(
          "<span class='gsc-msg gsc-error fw-400 text-dark text-center pt-10 pb-10 manual-margin'>Access code Can't be blank.</span>",
        ).appendTo("#gsc-validation-message");
      } else {
        jQuery(".loading-sign").removeClass("loading");
        jQuery("#gsc-validation-message").empty();
        jQuery(
          "<div class='gsc-msg gsc-success fw-400 text-dark text-center pt-10 pb-10 manual-margin'>Your Google Access Code is Authorized and Saved.</div>",
        ).appendTo("#gsc-validation-message");

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

  // Open popup instead of alert
  jQuery(document).on("click", "#deactivate-log", function () {
    jQuery("#gscgff-confirm-deactive-popup-free").removeClass("d-none");
  });

  // Cancel button
  jQuery(document).on(
    "click",
    "#gscgff-deactive-popup-free-cancel",
    function () {
      jQuery("#gscgff-confirm-deactive-popup-free").addClass("d-none");
    },
  );

  // Click outside popup to close
  jQuery(document).on(
    "click",
    "#gscgff-confirm-deactive-popup-free",
    function (e) {
      if (jQuery(e.target).is(this)) {
        jQuery(this).addClass("d-none");
      }
    },
  );

  // Confirm Deactivate
  jQuery(document).on(
    "click",
    "#gscgff-deactive-popup-free-confirm",
    function () {
      jQuery("#gscgff-confirm-deactive-popup-free").addClass("d-none");
      jQuery(".loading-sign-deactive").addClass("loading");

      var data = {
        action: "deactivate_gs_code_integation",
        security: jQuery("#gf-ajax-nonce").val(),
      };

      jQuery.post(ajaxurl, data, function (response) {
        jQuery(".loading-sign-deactive").removeClass("loading");
        jQuery("#gsc-validation-deactivate-message").empty();

        if (response === -1) {
          return false; // Invalid nonce
        }

        if (!response.success) {
          return;
        }

        jQuery(
          "<div class='gsc-msg gsc-success fw-400 text-dark text-center pt-10 pb-10 manual-margin'>Your account is removed. Reauthenticate again to integrate Gravity Forms with Google Sheet.</div>",
        ).appendTo("#gsc-validation-deactivate-message");

        setTimeout(function () {
          location.reload();
        }, 1000);
      });
    },
  );

  /**
   * Clear debug
   */
  jQuery(document).on("click", ".clear-debug", function () {
    jQuery(".clear-loading-sign").addClass("loading");
    var data = {
      action: "gfgs_clear_log",
      security: jQuery("#gf-ajax-nonce").val(),
    };
    jQuery.post(ajaxurl, data, function (response) {
      if (response == -1) {
        return false; // Invalid nonce
      }
      var clear_msg = response.data;
      if (response.success) {
        jQuery(".clear-loading-sign").removeClass("loading");
        jQuery("#gsheet-validation-message").empty();
        jQuery(
          "<span class='gs-valid-message'>" + clear_msg + "</span>",
        ).appendTo("#gsheet-validation-message");
        setTimeout(function () {
          location.reload();
        }, 1000);
      }
    });
  });
  /**
   * Clear debug for system status tab
   */
  jQuery(document).on("click", ".clear-content-logs-gf", function () {
    jQuery(".loading-sign").addClass("loading");
    var data = {
      action: "gf_clear_debug_log",
      security: jQuery("#gf-ajax-nonce").val(),
    };
    jQuery.post(ajaxurl, data, function (response) {
      if (response == -1) {
        return false; // Invalid nonce
      }

      if (response.success) {
        jQuery(".loading-sign").removeClass("loading");
        jQuery("#gsc-validation-message").empty();
        jQuery(
          "<span class='gsc-msg gsc-error fw-400 text-dark text-center pt-10 pb-10 manual-margin'>Access code Can't be blank.</span>",
        ).appendTo("#gsc-validation-message");
      } else {
        jQuery(".loading-sign").removeClass("loading");
        jQuery("#gsc-validation-message").empty();
        jQuery(
          "<div class='gsc-msg gsc-success fw-400 text-dark text-center pt-10 pb-10 manual-margin'>Your Google Access Code is Authorized and Saved.</div>",
        ).appendTo("#gsc-validation-message");
        setTimeout(function () {
          window.location.href = jQuery("#redirect_auth_gravityform").val();
        }, 1000);
      }
    });
  });

  /**
   * Sync with google account to fetch latest sheet and tab name list.
   */
  jQuery(document).on("click", "#gfgs-sync", function () {
    jQuery(this).parent().children(".loading-sign").addClass("loading");
    var integration = jQuery(this).data("init");
    var data = {
      action: "sync_with_google_account",
      isajax: "yes",
      isinit: integration,
      security: jQuery("#gf-ajax-nonce").val(),
    };

    jQuery.post(ajaxurl, data, function (response) {
      if (response == -1) {
        return false; // Invalid nonce
      }

      if (response.data.success === "yes") {
        jQuery(".loading-sign").removeClass("loading");
        jQuery("#gsheet-validation-message").empty();
        jQuery(
          "<span class='gsheet-valid-message'>Fetched all sheet details.</span>",
        ).appendTo("#gsheet-validation-message");
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
    var nonce = jQuery("#gf-ajax-nonce").val();

    jQuery(".loading-sign").addClass("loading");
    var data = {
      action: "get_tabname_list",
      sheetname: sheetnames,
      security: nonce,
    };

    jQuery.post(ajaxurl, data, function (response) {
      jQuery(".loading-sign").removeClass("loading");
      if (response == -1) {
        return false; // Invalid nonce
      }
      if (response.success) {
        jQuery("#tabname").html(html_decode(response.data));
        jQuery(".loading-sign").removeClass("loading");
      }
    });
  });

  // TODO : Combine into one
  jQuery(document).on("change", "#tabname", function () {
    var sheetname = jQuery("#sheetname").val();
    var tabname = jQuery(this).val();
    var nonce = jQuery("#gf-ajax-nonce").val();
    jQuery(".loading-sign").addClass("loading");
    var data = {
      action: "get_sheet_id_name",
      sheetname: sheetname,
      tabname: tabname,
      security: nonce,
    };
    jQuery.post(ajaxurl, data, function (response) {
      if (response == -1) {
        return false; // Invalid nonce
      }

      if (response.success) {
        jQuery("#gaddon-setting-row-sheeturl>td").html(
          html_decode(response.data),
        );
      }
      jQuery(".loading-sign").removeClass("loading");
    });
  });

  function html_decode(input) {
    var doc = new DOMParser().parseFromString(input, "text/html");
    return doc.documentElement.textContent;
  }

  jQuery(".update-renewal").click(function () {
    var data = {
      action: "update_license_expiration",
      security: jQuery("#gsgf_ajax_nonce").val(),
    };

    jQuery.post(ajaxurl, data, function (response) {
      if (response.success) {
        jQuery(".gsgf-renew").slideUp("slow");
      }
    });
  });

  jQuery(".hide-renew-box").click(function () {
    var data = {
      action: "hide_renew_box",
      security: jQuery("#gsgf_ajax_nonce").val(),
    };

    jQuery.post(ajaxurl, data, function (response) {
      if (response.success) {
        jQuery(".gsgf-renew").slideUp("slow");
      }
    });
  });

  jQuery("#gform-settings").submit(function (e) {
    var sheetName = jQuery("#gf-gs-sheet-name").val();
    var sheetId = jQuery("#gf-gs-sheet-id").val();
    var tabName = jQuery("#gf-gs-sheet-tab-name").val();
    var tabId = jQuery("#gf-gs-tab-id").val();

    jQuery("#error-sheetName").html("");
    jQuery("#error-sheetId").html("");
    jQuery("#error-tabName").html("");
    jQuery("#error-tabId").html("");

    if (sheetName == "") {
      e.preventDefault();
      jQuery("#error-sheetName").html("This field is required.");
    }

    if (sheetId == "") {
      e.preventDefault();
      jQuery("#error-sheetId").html("This field is required.");
    }

    if (tabName == "") {
      e.preventDefault();
      jQuery("#error-tabName").html("This field is required.");
    }

    if (tabId == "") {
      e.preventDefault();
      jQuery("#error-tabId").html("This field is required.");
    }
  });

  if (
    jQuery(".gform-settings-field__display_note :first-child").hasClass(
      "gs-display-note",
    )
  ) {
    jQuery("#gform-settings-save").css("display", "none");
  }
});

// Msg Hide ///

jQuery(document).ready(function ($) {
  // Check if the message has already been hidden by looking in localStorage
  if (localStorage.getItem("googleDriveMsgHidden") === "true") {
    jQuery("#google-drive-msg").hide(); // Hide the message if it's already hidden
  }

  // On button click, hide the #google-drive-msg div and store the hidden state in localStorage
  jQuery(".button_gravityformgsc").on("click", function () {
    jQuery("#google-drive-msg").hide(); // Hide the message
    localStorage.setItem("googleDriveMsgHidden", "true"); // Save the hidden state in localStorage
  });

  // On #deactivate-log click, show the #google-drive-msg div and clear localStorage
  jQuery("#deactivate-log").on("click", function () {
    // jQuery('#google-drive-msg').show(); // Show the message
    localStorage.removeItem("googleDriveMsgHidden"); // Remove the hidden state from localStorage
  });
});
jQuery(document).ready(function ($) {
  jQuery(".gf-install-plugin-btn").on("click", function () {
    var button = jQuery(this);
    var pluginSlug = button.data("plugin");
    var downloadUrl = button.data("download");
    var loaderSpan = button
      .closest(".button-bar")
      .find(".loading-sign-install");
    loaderSpan.addClass("loading");
    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "gs_gravity_install_plugin",
        plugin_slug: pluginSlug,
        download_url: downloadUrl,
        security: jQuery("#gs_gravity_ajax_nonce").val(),
      },
      success: function (response) {
        loaderSpan.removeClass("loading");
        if (response.success) {
          button.hide();
          button.closest(".button-bar").find(".gf-activate-plugin-btn").show();
        } else {
          button.html("Install").prop("disabled", false);
        }
      },
      error: function () {
        loaderSpan.removeClass("loading");
        button.html("Install").prop("disabled", false);
      },
    });
  });

  /**
   * Handle plugin activation button click via AJAX.
   *
   * - Shows loading spinner
   * - Sends plugin slug to server for activation
   * - On success, updates button to "Activated" and reloads page
   * - On error or failure, resets button and removes loading state
   */

  jQuery(document).on("click", ".gf-activate-plugin-btn", function () {
    var button = jQuery(this);
    var pluginSlug = button.data("plugin");
    var loaderSpan = button.siblings(".loading-sign-active");
    loaderSpan.addClass("loading");
    // button.prop("disabled", true);
    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "gs_gravity_activate_plugin",
        plugin_slug: pluginSlug,
        security: jQuery("#gs_gravity_ajax_nonce").val(),
      },
      success: function (response) {
        if (response.success) {
          button.text("Activated"); // Show "Activated"
          button.prop("disabled", true);
          location.reload();
        } else {
          loaderSpan.removeClass("loading"); // Clear loader
          button.prop("disabled", false);
        }
      },
      error: function () {
        loaderSpan.removeClass("loading").text(""); // Clear loader
        button.prop("disabled", false);
      },
    });
  });

  /**
   * Handle plugin deactivation button click via AJAX.
   *
   * - Sends plugin slug to server for deactivation
   * - On success, shows alert and reloads the page
   * - On error, shows AJAX error alert
   */

  jQuery(".gf-deactivate-plugin").on("click", function () {
    var pluginSlug = jQuery(this).data("plugin");
    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      dataType: "json", // Ensure JSON response
      data: {
        action: "gs_gravity_deactivate_plugin",
        plugin_slug: pluginSlug,
        security: jQuery("#gs_gravity_ajax_nonce").val(),
      },
      success: function (response) {
        if (response.success) {
          alert(response.data); // Display success message
          location.reload();
        }
      },
      error: function (xhr, status, error) {
        alert("AJAX error: " + error);
      },
    });
  });
});

/** Slider for integration page  */
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".gsc-slider-wrapper").forEach(function (wrapper) {
    const slider = wrapper.querySelector(".gsc-slider");
    const slides = wrapper.querySelectorAll(".gsc-slide");
    const prevBtn = wrapper.querySelector(".gsc-nav.prev");
    const nextBtn = wrapper.querySelector(".gsc-nav.next");

    let current = 0;

    function updateSlider() {
      slider.style.transform = "translateX(" + -current * 100 + "%)";
    }

    nextBtn.addEventListener("click", function () {
      current = (current + 1) % slides.length;
      updateSlider();
    });

    prevBtn.addEventListener("click", function () {
      current = (current - 1 + slides.length) % slides.length;
      updateSlider();
    });
  });
});

/** pro Sheets Integration  popup */
jQuery(document).ready(function ($) {
  $("#gs_gravityforms_dro_option").on("change", function () {
    const value = $(this).val();

    if (value === "1") {
      $("#gscgff-confirm-manual-popup-pro").removeClass("d-none");
    }
  });

  // Close popup (Cancel button)
  $(document).on("click", ".gscgff-popup-close-pro", function () {
    $("#gscgff-confirm-manual-popup-pro").addClass("d-none");
    $("#gs_gravityforms_dro_option").val("0");
  });

  // Close popup on overlay click
  $(document).on("click", "#gscgff-confirm-manual-popup-pro", function (e) {
    if ($(e.target).is(this)) {
      $(this).addClass("d-none");
      $("#gs_gravityforms_dro_option").val("0");
    }
  });
});

jQuery(document).ready(function ($) {
  $("#gs_gravityforms_dro_option").on("change", function () {
    const value = $(this).val();

    if (value === "2") {
      $("#gscgff-confirm-service-popup-pro").removeClass("d-none");
    }
  });

  // Close popup (Cancel button)
  $(document).on("click", ".gscgff-popup-service-close-pro", function () {
    $("#gscgff-confirm-service-popup-pro").addClass("d-none");
    $("#gs_gravityforms_dro_option").val("0");
  });

  // Close popup on overlay click
  $(document).on("click", "#gscgff-confirm-service-popup-pro", function (e) {
    if ($(e.target).is(this)) {
      $(this).addClass("d-none");
      $("#gs_gravityforms_dro_option").val("0");
    }
  });
});

/**jQuery for save uninstall settings */
jQuery(document).ready(function ($) {
  const $checkbox = $("#gscgff_gravityform_uninstall_settings_free");
  const $saveBtn = $(".gscgff-uninstall-settings-save-free");
  const $msg = $("#gscgff-uninstall-msg-free");
  const $loader = $(".loading-uninstall-free");
  const $popup = $("#gscgff-confirm-uninstall-data-popup-free");

  // Page load → disable button
  $saveBtn.prop("disabled", true).addClass("common-disable");

  // Checkbox change
  $checkbox.on("change", function () {
    // If user tries to enable it → show popup
    if ($(this).is(":checked")) {
      // Uncheck temporarily
      $(this).prop("checked", false);

      // Show popup
      $popup.removeClass("d-none");

      return;
    }

    // Enable save button normally when unchecked
    $saveBtn.prop("disabled", false).removeClass("common-disable");
  });

  /* =========================
     POPUP CONFIRM BUTTON
     ========================= */

  $("#gscgff-confirm-enable-uninstall-free").on("click", function () {
    $checkbox.prop("checked", true);

    $popup.addClass("d-none");

    $saveBtn.prop("disabled", false).removeClass("common-disable");
  });

  /* =========================
     POPUP CANCEL BUTTON
     ========================= */

  $("#gscgff-cancel-uninstall-free").on("click", function () {
    $checkbox.prop("checked", false);

    $popup.addClass("d-none");
  });

  /* =========================
     SAVE SETTINGS
     ========================= */

  $saveBtn.on("click", function (e) {
    e.preventDefault();

    var isChecked = $checkbox.is(":checked");

    $.ajax({
      url: ajaxurl,
      type: "POST",
      dataType: "json",
      data: {
        action: "gscgff_save_uninstall_settings_ajax_free",
        uninstall_setting: isChecked ? 1 : 0,
        security: $("#gscgff-gravity-setting-ajax-nonce").val(),
      },

      beforeSend: function () {
        $loader.addClass("loading");
        $saveBtn.prop("disabled", true).addClass("common-disable");
      },

      success: function (response) {
        if (!response.success) return;

        $msg.removeClass("gsc-success gsc-error d-none");

        $msg
          .addClass("gsc-success")
          .text("Plugin preferences updated successfully.");

        setTimeout(function () {
          $msg.addClass("d-none").text("");
        }, 2000);
      },

      error: function () {
        $msg
          .removeClass("d-none")
          .addClass("gsc-error")
          .text("Something went wrong");

        $saveBtn.prop("disabled", false).removeClass("common-disable");
      },

      complete: function () {
        $loader.removeClass("loading");
      },
    });
  });
});

jQuery(document).ready(function (jQuery) {
  /**
   * Hide empty addon sections and mark them with a CSS class on page load.
   */

  jQuery(".gsheetconnector-addons-list").each(function () {
    if (jQuery(this).html().trim().length === 0) {
      jQuery(this).addClass("blank_div");
      jQuery(this).prev("div").hide();
    }
  });

  /**
   * Handle plugin install button click via AJAX.
   *
   * - Shows loading spinner
   * - Sends plugin slug and download URL to server
   * - On success, hides install button and shows activate button
   * - On error or failure, resets button state
   */

  jQuery(".gscgff-install-plugin-btn").on("click", function () {
    var button = jQuery(this);
    var pluginSlug = button.data("plugin");
    var downloadUrl = button.data("download");
    var loaderSpan = button
      .closest(".button-bar")
      .find(".loading-sign-install");

    loaderSpan.addClass("loading");
    button.prop("disabled", true);

    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      dataType: "json",
      data: {
        action: "gscgff_install_plugin",
        plugin_slug: pluginSlug,
        download_url: downloadUrl,
        security: jQuery("#gscgff-ajax-nonce").val(),
      },

      success: function (response) {
        loaderSpan.removeClass("loading");

        if (response.success) {
          // ✅ Install success
          button.hide();

          button
            .closest(".button-bar")
            .find(".gscgff-activate-plugin-btn")
            .show();
        } else {
          // ❌ Permission or other error → open popup
          jQuery(".popup-actions-active-msg-free").text(
            response.data.message ||
              "You do not have permission to install this plugin.",
          );

          jQuery("#gscgff-confirm-active-popup-free").removeClass("d-none");

          button.prop("disabled", false);
        }
      },

      error: function () {
        loaderSpan.removeClass("loading");

        jQuery(".popup-actions-active-msg-free").text(
          "Something went wrong. Please try again.",
        );

        jQuery("#gscgff-confirm-active-popup-free").removeClass("d-none");

        button.prop("disabled", false);
      },
    });
  });

  /**
   * Handle plugin activation button click via AJAX.
   *
   * - Shows loading spinner
   * - Sends plugin slug to server for activation
   * - On success, updates button to "Activated" and reloads page
   * - On error or failure, resets button and removes loading state
   */

  jQuery(document).on("click", ".gscgff-activate-plugin-btn", function () {
    var button = jQuery(this);
    var pluginSlug = button.data("plugin");
    var loaderSpan = button.siblings(".loading-sign-active");

    loaderSpan.addClass("loading");
    button.prop("disabled", true);

    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      dataType: "json",
      data: {
        action: "gscgff_activate_plugin",
        plugin_slug: pluginSlug,
        security: jQuery("#gscgff-ajax-nonce").val(),
      },

      success: function (response) {
        loaderSpan.removeClass("loading");

        if (response.success) {
          // ✅ Success → reload only
          location.reload();
        } else {
          // ❌ Permission denied → open popup
          jQuery(".popup-actions-active-msg-free").text(
            response.data.message ||
              "You do not have permission to activate this plugin.",
          );

          jQuery("#gscgff-confirm-active-popup-free").removeClass("d-none");

          button.prop("disabled", false);
        }
      },

      error: function () {
        loaderSpan.removeClass("loading");

        jQuery(".popup-actions-active-msg-free").text(
          "Something went wrong. Please try again.",
        );

        jQuery("#gscgff-confirm-active-popup-free").removeClass("d-none");

        button.prop("disabled", false);
      },
    });
  });

  /**
   * Handle plugin deactivation button click via AJAX.
   *
   * - Sends plugin slug to server for deactivation
   * - On success, shows alert and reloads the page
   * - On error, shows AJAX error alert
   */

  let selectedPluginSlug = "";
  // Open popup on deactivate click
  jQuery(".gscgff-deactivate-plugin").on("click", function (e) {
    selectedPluginSlug = jQuery(this).data("plugin");
    jQuery("#gscgff-confirm-dective-popup-free").removeClass("d-none");
  });
  // Cancel button
  jQuery("#gscgff-dective-popup-cancel-free").on("click", function () {
    jQuery("#gscgff-confirm-dective-popup-free").addClass("d-none");
    selectedPluginSlug = "";
  });

  // Confirm deactivate
  jQuery("#gscgff-deactive-popup-confirm-free").on("click", function () {
    if (!selectedPluginSlug) return;

    jQuery("#gscgff-confirm-dective-popup-free").addClass("d-none");
    jQuery(".gscgff-deactivate-plugin")
      .siblings(".loading-sign-deactive")
      .first()
      .addClass("loading");

    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      dataType: "json",
      data: {
        action: "gscgff_deactivate_plugin",
        plugin_slug: selectedPluginSlug,
        security: jQuery("#gscgff-ajax-nonce").val(),
      },
      success: function (response) {
        if (response.success) {
          jQuery("#gscgff-confirm-dective-popup-free").addClass("d-none");
          location.reload();
        }
      },
    });
  });

  /*  filter */
  document.querySelectorAll(".market-tab").forEach((tab) => {
    tab.addEventListener("click", function () {
      let filter = this.dataset.filter;

      document
        .querySelectorAll(".market-tab")
        .forEach((t) => t.classList.remove("active"));

      this.classList.add("active");

      document.querySelectorAll(".gsc-market-item").forEach((card) => {
        if (filter === "all") {
          card.style.display = "block";
        } else {
          card.style.display = card.classList.contains(filter)
            ? "block"
            : "none";
        }
      });
    });
  });
});

/** close  error popup  in extenstion tab  */
jQuery("#gscgff-popup-active-free").on("click", function () {
  jQuery("#gscgff-confirm-active-popup-free").addClass("d-none");
  selectedPluginSlug = "";
});

/** JS for display auth method */
document.addEventListener("DOMContentLoaded", function () {
  var el = document.querySelector(".gscgff-selected-method");

  if (el && el.dataset.value && el.dataset.value.trim() !== "") {
    var badgeText = el.dataset.value.trim();

    document
      .querySelectorAll(".nav-tab-wrapper .nav-tab")
      .forEach(function (tab) {
        var href = tab.getAttribute("href") || "";

        if (href.indexOf("tab=integration") !== -1) {
          tab.style.position = "relative";

          if (tab.querySelector(".gscgff-selected-badge")) return;

          var badge = document.createElement("div");

          if (badgeText == "Auth Required") {
            badge.className = "gscgff-auth-required-selected-badge"; // ✅ IMPORTANT
            badge.textContent = badgeText;
          } else {
            badge.className = "gscgff-selected-badge"; // ✅ IMPORTANT
            badge.textContent = badgeText;
          }
          tab.appendChild(badge);
        }
      });
  }
});

/** add auth badge in GF inside of tab settings */
jQuery(document).ready(function ($) {
  if (
    typeof gscExtensionVars !== "undefined" &&
    gscExtensionVars.selected_method !== ""
  ) {
    $("nav.gform-settings__navigation a .label").each(function () {
      if ($(this).text().trim() === "Googlesheet") {
        if (!$(this).next(".gscgff-inner-tab-method-badge").length) {
          if (gscExtensionVars.selected_method == "Auth Required") {
            $(this).after(
              '<span class="gscgff-inner-tab-authrequired-badge">' +
                gscExtensionVars.selected_method +
                "</span>",
            );
          } else {
            $(this).after(
              '<span class="gscgff-inner-tab-method-badge">' +
                gscExtensionVars.selected_method +
                "</span>",
            );
          }
        }
      }
    });
  }
});

/** add auth badge in GF outer of tab settings */
jQuery(document).ready(function ($) {
  if (
    typeof gscExtensionVars !== "undefined" &&
    gscExtensionVars.selected_method !== ""
  ) {
    if (gscExtensionVars.selected_method == "") {
      $(
        '.simplebar-content a[href*="subview=gsheetconnector-gravity-forms"]',
      ).append(
        ' <span class="gscgff-outer-tab-authrequired-badge">' +
          gscExtensionVars.selected_method +
          "</span>",
      );
    } else {
      $(
        '.simplebar-content a[href*="subview=gsheetconnector-gravity-forms"]',
      ).append(
        ' <span class="gscgff-outer-tab-method-badge">' +
          gscExtensionVars.selected_method +
          "</span>",
      );
    }
  }
});

/** hide notice  */
jQuery(document).on("click", "#pro-dismiss-header-notice", function () {
  var nonce = jQuery("#gf-ajax-nonce").val();

  jQuery("#pro-notice-bar").hide();

  jQuery.post(ajaxurl, {
    action: "dismiss_pro_notice",
    nonce: nonce,
  });
});

jQuery(document).ready(function ($) {
  var el = $('a[href*="gsheetconnector-gravity-forms"] i.gform-icon');
  el.removeClass().addClass("dashicons dashicons-update-alt");
});

jQuery(document).ready(function ($) {
  var $field = $("#gform_setting_feedName");

  var $label = $field.find(".gform-settings-label");
  var $tooltip = $field.find(".gf_tooltip");

  $label.append($tooltip);
});

/* save button will enable then the all field input */
jQuery(document).ready(function ($) {
  var fields = [
    "#gf-gs-sheet-name",
    "#gf-gs-sheet-id",
    "#gf-gs-sheet-tab-name",
    "#gf-gs-tab-id",
    "#feedName",
  ];

  function fieldVal(id) {
    var el = $(id);
    return el.length ? el.val().trim() : "";
  }

  function isFormValid() {
    return fields.every(function (id) {
      return fieldVal(id) !== "";
    });
  }

  function toggleButton() {
    if (isFormValid()) {
      $("#gform-settings-save")
        .removeClass("disabled")
        .css({ "pointer-events": "auto", opacity: "1" });
      $("#sheet_url").removeClass("d-none");
    } else {
      $("#gform-settings-save")
        .addClass("disabled")
        .css({ "pointer-events": "none", opacity: "0.5" });
      $("#sheet_url").addClass("d-none");
    }
  }

  // Initial check
  toggleButton();

  // Listen to all fields
  $(document).on("keyup change", fields.join(", "), function () {
    toggleButton();
  });
});

jQuery(document).ready(function ($) {
  // CHECK ALL
  $("#checkall").on("change", function () {
    var isChecked = $(this).is(":checked");

    $(".gsgf-toggle").each(function () {
      if ($(this).is(":disabled")) return;

      $(this).prop("checked", isChecked);

      // Toggle UI class
      $(this)
        .closest(".gform-settings-field__toggle")
        .toggleClass("gform-field__toggle--on", isChecked);

      // Show/hide row
      var id = $(this).attr("id");
      $('.row_grvt[data-id="' + id + '"]').toggle(isChecked);
    });
  });

  // INDIVIDUAL TOGGLE
  $(document).on("change", ".gsgf-toggle", function () {
    var isChecked = $(this).is(":checked");

    $(this)
      .closest(".gform-settings-field__toggle")
      .toggleClass("gform-field__toggle--on", isChecked);

    var id = $(this).attr("id");
    $('.row_grvt[data-id="' + id + '"]').toggle(isChecked);

    // Update Check All
    var total = $(".gsgf-toggle:not(:disabled)").length;
    var checked = $(".gsgf-toggle:checked").length;

    $("#checkall").prop("checked", total === checked);
  });
});

jQuery(document).ready(function ($) {
  let totalSlides = $(
    ".notification-gscgff-slider-track .notification-gscgff-slide",
  ).length;

  if (totalSlides <= 1) {
  }

  function showNextSlide(currentSlide) {
    var track = currentSlide.closest(".notification-gscgff-slider-track");
    var slides = track.find(".notification-gscgff-slide");
    var currentIndex = slides.index(currentSlide);
    var nextIndex = currentIndex + 1;

    currentSlide.remove();

    slides = track.find(".notification-gscgff-slide");

    if (slides.length > 0) {
      if (nextIndex >= slides.length) {
        nextIndex = 0;
      }
      slides.hide().eq(nextIndex).show();
    }
    location.reload();
  }

  /** For JQuery for Dismiss notification */
  jQuery(document).on(
    "click",
    ".gscgff-review-close, .gscgff-review-dismiss-btn  , .gscgff-showpro-close, .gscgff-addons-close, .gscgff-enhance-btn-later, .gscgff-review-btn-later, .gscgff-enhance-close",
    function () {
      var key = jQuery(this).data("key");
      var currentSlide = jQuery(this).closest(".notification-gscgff-slide");

      jQuery.post(
        ajaxurl,
        {
          action: "gscgff_dismiss_notice",
          key: key,
          security: jQuery("#gf-ajax-nonce").val(),
        },
        function () {
          showNextSlide(currentSlide);
        },
      );
    },
  );

  /** For JQuery for snooze notification */
  jQuery(document).on(
    "click",
    ".gscgfp-review-btn-later, .gscgff-Showpro-btn-later, .gscgfp-addons-btn-later",
    function () {
      var key = jQuery(this).data("key");
      var currentSlide = jQuery(this).closest(".notification-gscgff-slide");

      jQuery.post(
        ajaxurl,
        {
          action: "gscgff_snooze_notice",
          key: key,
          security: jQuery("#gf-ajax-nonce").val(),
        },
        function () {
          showNextSlide(currentSlide);
        },
      );
    },
  );
});

/** notificatin slider arrow button will hide when slider is 1 or 0 */
jQuery(document).ready(function ($) {
  if ($(".notification-gscgff-slide").length <= 1) {
    $(".notification-gscgff-slider-arrows").hide();
  }

  if ($(".notification-gscgff-slide").length == 0) {
    $(".notification-gscgff-notice-slider").hide();
  }

  $(".gravity-free-counter").each(function () {
    let $this = $(this);
    let countTo = parseFloat($this.attr("data-count"));

    $({ countNum: 0 }).animate(
      {
        countNum: countTo,
      },
      {
        duration: 2500,
        easing: "swing",

        step: function () {
          if (countTo % 1 !== 0) {
            $this.text(this.countNum.toFixed(1));
          } else {
            $this.text(Math.floor(this.countNum));
          }
        },

        complete: function () {
          if (countTo % 1 !== 0) {
            $this.text(countTo.toFixed(1));
          } else {
            $this.text(countTo);
          }
        },
      },
    );
  });
});

/***new slider for without permission for existing method */
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".gsc-slider-wrapper").forEach(function (wrapper) {
    const slider = wrapper.querySelector(".gsc-slider");
    const slides = wrapper.querySelectorAll(".gsc-slide");
    const prevBtn = wrapper.querySelector(".gsc-nav.prev");
    const nextBtn = wrapper.querySelector(".gsc-nav.next");
    let current = 0;
    function updateSlider() {
      slider.style.transform = "translateX(" + -current * 100 + "%)";
    }
    nextBtn?.addEventListener("click", function () {
      current = (current + 1) % slides.length;
      updateSlider();
    });
    prevBtn?.addEventListener("click", function () {
      current = (current - 1 + slides.length) % slides.length;
      updateSlider();
    });
    /*  attach function */
    wrapper.goToSlide = function (index) {
      current = index;
      updateSlider();
    };
  });

  /*  AUTO MOVE TO STEP 4   */
  setTimeout(function () {
    const errorBox = document.querySelector(".gscgff-permission-error");
    const target = document.querySelector(".gscgff-connection-guide-slider");
    if (!errorBox) {
      /* console.log("No permission error"); */
      return;
    }
    if (!target) {
      /* console.log("Slider not found"); */
      return;
    }
    if (target.goToSlide) {
      target.goToSlide(3);
      target.scrollIntoView({
        behavior: "smooth",
        block: "center",
      });
      console.log("Auto moved to Step 4");
    } else {
      console.log("goToSlide not available");
    }
  }, 800);
});

/** when the return auth with code will scroll down to token save button */
jQuery(document).ready(function ($) {
  /* Check if URL has "code" parameter */
  const code = new URLSearchParams(window.location.search).get("code");

  if (!code) return;

  /*possible targets */
  const selectors = ["#gfgs-code"];

  let target = null;

  /* find which ID exists  */
  for (let sel of selectors) {
    if (document.querySelector(sel)) {
      target = sel;
      break;
    }
  }

  if (target) {
    window.location.hash = target.replace("#", "");

    document.querySelector(target).scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
  }
});


/** Reset sheet details */
jQuery(document).ready(function ($) {
  jQuery('.gf-sheet-reset-button').on('click', function() {
      jQuery('#gf-gs-sheet-name, #gf-gs-sheet-id, #gf-gs-sheet-tab-name, #gf-gs-tab-id').val('');
      jQuery('#sheet_url').addClass('d-none');
      jQuery('.gf-sheet-reset-button').addClass('d-none');
        jQuery("#gform-settings-save")
        .addClass("disabled")
        .css({ "pointer-events": "none", opacity: "0.5" });
  });
});