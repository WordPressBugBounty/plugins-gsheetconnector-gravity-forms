jQuery(document).ready(function ($) {

    function copySystemInfo() {
        const systemInfoContainer = document.querySelector('.info-container');
        const systemInfoElements = systemInfoContainer.querySelectorAll('.info-content h3, .info-content td');
        let systemInfoText = '';
        let currentRow = '';

        systemInfoElements.forEach((element) => {
            if (element.innerText) {
                const tagName = element.tagName.toLowerCase();

                // Handle section headers (h3 tags)
                if (tagName === 'h3') {
                    if (currentRow !== '') {
                        systemInfoText += currentRow.trim() + '\n\n'; // Add two newlines between sections
                    }
                    systemInfoText += `**${element.innerText}**\n\n`; // Make h3 bold and add extra space after it
                    currentRow = '';
                }

                // Handle table data (td tags)
                else if (tagName === 'td') {
                    const labelElement = element.previousElementSibling;

                    if (labelElement && labelElement.innerText) {
                        let label = labelElement.innerText.trim(); // Keep the label as is (no underscores)
                        systemInfoText += `${label}: ${element.innerText.trim()}\n`; // Format the row as key-value pair
                    }

                }
            }
        });

        // Add the last row to the final text
        systemInfoText += currentRow.trim();

        // Copy the formatted text to the clipboard
        navigator.clipboard.writeText(systemInfoText.trim())
            .then(() => {
                const messageElement = document.createElement('div');
                messageElement.textContent = 'System info copied!';
                messageElement.classList.add('copy-success-message');
                document.body.appendChild(messageElement);

                setTimeout(() => {
                    messageElement.remove();
                }, 3000);
            })
            .catch((error) => {
                console.error('Unable to copy system info:', error);
            });
    }




    jQuery(document).ready(function ($) {
        $("#show-info-button").click(function () {
            $("#info-container").slideToggle();
        });
        $("#show-wordpress-info-button").click(function () {
            $("#wordpress-info-container").slideToggle();
        });
        $("#show-Drop-info-button").click(function () {
            $("#Drop-info-container").slideToggle();
        });
        $("#show-active-info-button").click(function () {
            $("#active-info-container").slideToggle();
        });
        $("#show-netplug-info-button").click(function () {
            $("#netplug-info-container").slideToggle();
        });
        $("#show-acplug-info-button").click(function () {
            $("#acplug-info-container").slideToggle();
        });
        $("#show-server-info-button").click(function () {
            $("#server-info-container").slideToggle();
        });
        $("#show-database-info-button").click(function () {
            $("#database-info-container").slideToggle();
        });
        $("#show-wrcons-info-button").click(function () {
            $("#wrcons-info-container").slideToggle();
        });
        $("#show-ftps-info-button").click(function () {
            $("#ftps-info-container").slideToggle();
        });
    });
    // JavaScript function to copy the error log to the clipboard
    function copyErrorLog() {
        // Select the textarea containing the error log
        var textarea = document.querySelector('.errorlog');
        // Select the message div
        var copyMessage = document.querySelector('.copy-message');

        // Check if the textarea and message div exist
        if (textarea && copyMessage) {
            // Select the text within the textarea
            textarea.select();

            try {
                // Attempt to copy the selected text to the clipboard
                document.execCommand('copy');
                // Display the "Copied" message
                copyMessage.style.display = 'block';

                // Hide the message after a few seconds (e.g., 3 seconds)
                setTimeout(function () {
                    copyMessage.style.display = 'none';
                }, 3000);
            } catch (err) {
                console.error('Unable to copy error log: ' + err);
                alert('Error log copy failed. Please copy it manually.');
            }

            // Deselect the text
            textarea.blur();
        }
    }

    // Add an event listener to call the copyErrorLog function when the button is clicked
    document.addEventListener('DOMContentLoaded', function () {
        var copyButton = document.querySelector('.copy');

        if (copyButton) {
            copyButton.addEventListener('click', function (event) {
                event.preventDefault();
                copyErrorLog();
            });
        }
    });

    // JavaScript function to clear the error log textarea
    // function clearErrorLog() {
    //     var textarea = document.querySelector('.errorlog');

    //     if (textarea) {
    //         // Clear the textarea content
    //         textarea.value = '';
    //     }
    // }

    // Add an event listener to call the clearErrorLog function when the "Clear" button is clicked
    document.addEventListener('DOMContentLoaded', function () {
        var clearButton = document.querySelector('.clear');

        if (clearButton) {
            clearButton.addEventListener('click', function (event) {
                event.preventDefault();
                clearErrorLog();
            });
        }
    });
    // Bind copy and clear buttons
    $('.copy-system-info-gf').on('click', function (e) {
        e.preventDefault();
        copySystemInfo();
    });

    $('.copy-error-log-gf').on('click', function (e) {
        e.preventDefault();
        copyErrorLog();
    });

    $('.clear-content-logs-gf').on('click', function (e) {
        e.preventDefault();
        clearErrorLog();
    });
});
