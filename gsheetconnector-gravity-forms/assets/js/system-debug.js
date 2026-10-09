/** for using settingstab -> system status */
jQuery(document).ready(function ($) {
  /**
   * Copy formatted system info and show message below the button
   */
  function copySystemInfo(btn) {
    if (!btn) return;

    var wrapper = document.querySelector("#system-info-wrapper");

    if (!wrapper) return;

    var textToCopy = "";
    function clean(el) {
      return el.textContent.replace(/\s+/g, " ").trim();
    }

    /* ===== LOOP ALL SECTIONS ===== */
    wrapper.querySelectorAll(".info-button").forEach(function (button) {
      /* ===== GET NEXT CONTENT DIV ===== */
      var contentDiv = button.parentElement.nextElementSibling;

      // No content for this section: skip it completely (no empty title)
      if (!contentDiv) return;

      /* ===== SECTION BODY ===== */
      var sectionText = "";

      /* --- Case 1: table rows (label + one or more value columns) --- */
      contentDiv.querySelectorAll("table tr").forEach(function (row) {
        var cols = row.querySelectorAll("td");

        if (cols.length >= 2) {
          var label = clean(cols[0]);

          // Column 2, 3, 4... so extra columns like "Writable" are included
          var values = [];
          for (var i = 1; i < cols.length; i++) {
            var cellText = clean(cols[i]);
            if (cellText !== "") {
              values.push(cellText);
            }
          }

          sectionText += label + ": " + values.join(" - ") + "\n";
        } else if (cols.length === 1) {
          // Row with a single cell: copy it as a plain line
          var single = clean(cols[0]);
          if (single !== "") {
            sectionText += single + "\n";
          }
        }
      });

      /* --- Case 2: no table rows (e.g. the Active Plugins list) --- */
      if (sectionText === "") {
        var items = contentDiv.querySelectorAll("li");

        if (items.length) {
          // A list: one line per item
          items.forEach(function (item) {
            var line = clean(item);
            if (line !== "") {
              sectionText += "- " + line + "\n";
            }
          });
        } else {
          // Anything else: use the text of the content div, one line per block
          contentDiv.querySelectorAll("p, div").forEach(function (block) {
            // Only blocks that do not contain other blocks, so text is not repeated
            if (block.querySelector("p, div")) return;

            var line = clean(block);
            if (line !== "") {
              sectionText += line + "\n";
            }
          });
        }
      }

      // Nothing found in this section: leave it out
      if (sectionText === "") return;

      /* ===== SECTION TITLE (extra spaces removed: "Active Plugins (23)") ===== */
      var sectionTitle = clean(button);

      textToCopy += "\n====================================\n";
      textToCopy += sectionTitle + "\n";
      textToCopy += "====================================\n\n";
      textToCopy += sectionText + "\n";
    });

    textToCopy = textToCopy.trim();

    if (!textToCopy) {
      return;
    }

    /* ===== MESSAGE ===== */
    var msgDiv = btn.parentNode.querySelector(".gsc-copy-msg");

    if (!msgDiv) {
      msgDiv = document.createElement("div");

      msgDiv.className = "gsc-copy-msg";

      msgDiv.style.display = "none";

      btn.parentNode.appendChild(msgDiv);
    }

    function showCopied() {
      msgDiv.innerHTML = "Copied successfully.";

      msgDiv.style.display = "block";

      setTimeout(function () {
        msgDiv.style.display = "none";
      }, 2000);
    }

    /* ===== COPY ===== */
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard
        .writeText(textToCopy)
        .then(showCopied)
        .catch(function (err) {
         
        });
    } else {
      var textarea = document.createElement("textarea");

      textarea.value = textToCopy;

      textarea.style.position = "fixed";

      textarea.style.left = "-9999px";

      document.body.appendChild(textarea);

      textarea.focus();

      textarea.select();

      try {
        document.execCommand("copy");

        showCopied();
      } catch (e) {
        
      }

      document.body.removeChild(textarea);
    }
  }

  /* ===== BUTTON CLICK ===== */
  $(document).on("click", "#copy-system-info-free", function () {
    copySystemInfo(this);
  });

  $("#info-container").show();

  function accordionToggle(button, container) {
    $(button).on("click", function () {
      // if current container is visible
      if ($(container).is(":visible")) {
        $(container).slideUp();
      } else {
        $(".info-content").slideUp();
        $(container).slideDown();
      }
    });
  }

  accordionToggle("#gscgff-show-active-info-button", "#active-info-container");
  accordionToggle("#gscgff-show-info-button", "#info-container");
  accordionToggle(
    "#gscgff-show-wordpress-info-button",
    "#wordpress-info-container",
  );
  accordionToggle("#gscgff-show-Drop-info-button", "#Drop-info-container");
  accordionToggle(
    "#gscgff-show-active-theme-button",
    "#active-theme-info-container",
  );
  accordionToggle(
    "#gscgff-show-netplug-info-button",
    "#netplug-info-container",
  );
  accordionToggle("#gscgff-show-acplug-info-button", "#acplug-info-container");
  accordionToggle("#gscgff-show-server-info-button", "#server-info-container");
  accordionToggle(
    "#gscgff-show-database-info-button",
    "#database-info-container",
  );
  accordionToggle("#gscgff-show-wrcons-info-button", "#wrcons-info-container");
  accordionToggle("#gscgff-show-ftps-info-button", "#ftps-info-container");
});
/**
 * Adds event listener to the copy button to trigger error log copying
 * once the DOM content is fully loaded.
 */

document.addEventListener("DOMContentLoaded", function () {
  /**
   * Copies the content of the error log textarea to the clipboard
   * and shows a temporary "Copied" confirmation message.
   */

  function copyErrorLog() {
    // Select the textarea containing the error log
    var textarea = document.querySelector(".errorlog");

    // Select the message div (button na niche no div)
    var copyMessage = document.querySelector(".gsc-copy-msg");

    if (textarea && copyMessage) {
      textarea.select();

      try {
        // Copy text
        document.execCommand("copy");

        // Show message
        copyMessage.classList.remove("d-none");

        // Hide message after 3 seconds
        setTimeout(function () {
          copyMessage.classList.add("d-none");
        }, 3000);
      } catch (err) {
      
      }

      textarea.blur();
    }
  }

  var copyButton = document.querySelector(".copy");

  if (copyButton) {
    copyButton.addEventListener("click", function (event) {
      event.preventDefault();
      copyErrorLog();
    });
  }
});
jQuery(document).ready(function ($) {
  $("#gscgff-copy-logs-info").on("click", function (e) {
    e.preventDefault();

    var rows = $("table tbody tr");
    var copyText = "";

    if (!rows.length) {
      alert("No error logs found.");
      return;
    }

    rows.each(function () {
      var cols = $(this).find("td");

      if (cols.length >= 4) {
        copyText += $(cols[0]).text().trim() + "\n"; // Date
        copyText += $(cols[1]).text().trim() + "\n"; // Type
        copyText += $(cols[2]).text().trim() + "\n"; // Message
        copyText += $(cols[3]).text().trim() + "\n"; // File
        copyText += "----------------------------------------\n\n";
      }
    });

    /*  Temporary textarea copy */
    var tempTextarea = $("<textarea>");
    $("body").append(tempTextarea);
    tempTextarea.val(copyText).select();
    document.execCommand("copy");
    tempTextarea.remove();

    /*  Show success message */
    var $msg = $(".gsc-copy-msg");

    $msg.text("Copied successfully").removeClass("d-none");

    setTimeout(function () {
      $msg.addClass("d-none");
    }, 3000);
  });
});

jQuery(document).ready(function ($) {
  $("#gscgff-csv-info").on("click", function (e) {
    e.preventDefault();

    var rows = $("table.widefat tr");
    var csvContent = "";

    if (rows.length === 0) {
      alert("No error logs found.");
      return;
    }

    rows.each(function () {
      var cols = $(this).find("th, td");
      var rowData = [];

      cols.each(function () {
        var text = $(this).text().trim();

        /*  Escape quotes */
        text = text.replace(/"/g, '""');

        rowData.push('"' + text + '"');
      });

      csvContent += rowData.join(",") + "\n";
    });

    /*  Create Blob */
    var blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });

    var link = document.createElement("a");
    var url = URL.createObjectURL(blob);

    link.setAttribute("href", url);
    link.setAttribute("download", "debug-log.csv");
    link.style.visibility = "hidden";

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });
});

/** Functin for using clear log  */
jQuery(document).on("click", ".gsgff-clear-content-logs", function () {
  jQuery(".clear-loading-sign").addClass("loading");

  var data = {
    action: "gscgff_clear_log",
    security: jQuery("#gscgff-ajax-nonce").val(),
  };

  jQuery.post(ajaxurl, data, function (response) {
    var clear_msg = response.data;
    if (response == -1) {
      return false; /*  Invalid nonce */
    }

    if (response.success) {
      jQuery(".clear-loading-sign").removeClass("loading");
      jQuery(".gscgff-validation-message").empty();
      jQuery("<span class='gscgff-valid-message'>Cleared Log</span>").appendTo(
        ".gscgff-validation-message",
      );
      setTimeout(function () {
        location.reload();
      }, 1000);
    }
  });
});
