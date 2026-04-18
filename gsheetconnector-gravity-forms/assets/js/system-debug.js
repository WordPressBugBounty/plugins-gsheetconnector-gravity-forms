/** for using settingstab -> system status */
jQuery(document).ready(function ($) {
  function copySystemInfo() {
    const systemInfoContainer = document.querySelector(".info-container");
    if (!systemInfoContainer) return;

    const systemInfoElements = systemInfoContainer.querySelectorAll(
      ".info-content h3, .info-content td",
      );

    let systemInfoText = "";

    systemInfoElements.forEach((element) => {
      const tagName = element.tagName.toLowerCase();

      if (tagName === "h3") {
        systemInfoText += `\n${element.innerText.trim()}\n\n`;
      }

      if (tagName === "td") {
        const labelElement = element.previousElementSibling;
        if (labelElement) {
          systemInfoText += `${labelElement.innerText.trim()}: ${element.innerText.trim()}\n`;
        }
      }
    });

    systemInfoText = systemInfoText.trim();

    // copy (modern + fallback)
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(systemInfoText).then(showSuccessMsg);
    } else {
      const textarea = document.createElement("textarea");
      textarea.value = systemInfoText;
      textarea.style.position = "fixed";
      textarea.style.opacity = "0";

      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand("copy");
      document.body.removeChild(textarea);

      showSuccessMsg();
    }
  }

  function showSuccessMsg() {
    // purana message hatao
    $(".gsc-copy-msg").remove();

    const msgDiv = document.createElement("div");
    msgDiv.className = "gsc-copy-msg";
    msgDiv.innerText = "Copied successfully";

    // 🔹 BUTTON KE NICHE ADD KARO
    $("#copy-system-info-free").after(msgDiv);

    // auto remove
    setTimeout(() => {
      $(msgDiv).fadeOut(300, function () {
        $(this).remove();
      });
    }, 3000);
  }

  // button click
  $(document).on("click", "#copy-system-info-free", function () {
    copySystemInfo();
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
        console.error("Unable to copy error log:", err);
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
