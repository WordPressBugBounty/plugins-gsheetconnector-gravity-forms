<?php 
// ahmed 2623
   $gravityforms_manual_setting = get_option('gravityforms_manual_setting');

   $Code = "";
   $header = "";
    
    if (isset($_GET['code']) && ($gravityforms_manual_setting == 0)) {
        update_option('is_new_client_secret_gravityformsgsc', 1);
        $Code = sanitize_text_field($_GET["code"]);
        $header = admin_url('admin.php?page=gf_googlesheet');
    }


?>




<div class="main-promotion-box"> <a href="#" class="close-link"></a>
  <div class="promotion-inner">
    <h2><?php echo __("A way to connect WordPress", "gsheetconnector-gravityforms"); ?> <br />
      <?php echo __("and", "gsheetconnector-gravityforms"); ?> <span><?php echo __("Google Sheets Pro", "gsheetconnector-gravityforms"); ?></span></h2>
    <p class="ratings"><?php echo __("Ratings", "gsheetconnector-gravityforms"); ?> : <span></span></p>
    <p><?php echo __("The Most Powerful Bridge Between WordPress  and", "gsheetconnector-gravityforms"); ?> <strong><?php echo __("Google Sheets", "gsheetconnector-gravityforms"); ?></strong>, <br />
      <?php echo __("Now available for popular", "gsheetconnector-gravityforms"); ?> <strong><?php echo __("Contact Forms", "gsheetconnector-gravityforms"); ?></strong>, <strong><?php echo __("Page Builder Forms", "gsheetconnector-gravityforms"); ?></strong>,<br />
      <?php echo __("and", "gsheetconnector-gravityforms"); ?> <strong><?php echo __("E-commerce", "gsheetconnector-gravityforms"); ?></strong> <?php echo __("Platforms like", "gsheetconnector-gravityforms"); ?>  <strong><?php echo __("WooCommerce", "gsheetconnector-gravityforms"); ?></strong> <br />
      <?php echo __("and", "gsheetconnector-gravityforms"); ?> <strong><?php echo __("Easy Digital Downloads", "gsheetconnector-gravityforms"); ?></strong> (<?php echo __("EDD"); ?>).</p>
    <div class="button-bar"> <a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector" target="_blank"><?php echo __("Buy Now", "gsheetconnector-gravityforms"); ?></a> <a href="https://demo.gsheetconnector.com/" target="_blank"><?php echo __("Check Demo", "gsheetconnector-gravityforms"); ?></a> </div>
  </div>
  <div class="gsheet-plugins"></div>
</div> <!-- main-promotion-box #end -->



<div class="card-gravityforms dropdownoption-gravityforms">
    <div class="lbl-drop-down-select">
        <label for="gs_gravityforms_dro_option"><?php echo esc_html__('Choose Google API Setting :', 'gsheetconnector-gravityforms'); ?></label>
    </div>
    <div class="drop-down-select-btn">
        <select id="gs_gravityforms_dro_option" name="gs_gravityforms_dro_option">
            <option value="gravityforms_existing" selected><?php echo esc_html__('Use Existing Client/Secret Key (Auto Google API Configuration)', 'gsheetconnector-gravityforms'); ?>
            </option>
            <option value="gravityforms_manual" disabled=""><?php echo esc_html__('Use Manual Client/Secret Key (Use Your Google API Configuration) (Upgrade To PRO)', 'gsheetconnector-gravityforms'); ?></option>
        </select>
        <p class="int-meth-btn-gravityforms"><a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector" target="_blank"><input type="button" name="save-method-api-gravityforms" id=""
                value="<?php _e('Upgrade To PRO', 'gsheetconnector-gravityforms'); ?>" class="button button-primary" /></a>
            <span class="tooltip"> <img src="<?php echo GRAVITY_GOOGLESHEET_URL; ?>assets/image/help.png"
                        class="help-icon"> <span
                        class="tooltiptext tooltip-right"><?php _e('Manual Client/Secret Key (Use Your Google API Configuration) method is available in the PRO version of the plugin.', 'gsheetconnector-gravityforms'); ?></span></span>
        </p>
    </div>
</div>

<!-- ahmed 2623 -->
<div class="gform-tab-container">
    <div class="gform-card gform-tab-content">
        <input type="hidden" name="redirect_auth_gravityforms" id="redirect_auth_gravityforms" value="<?php echo (isset($header)) ? esc_attr($header) : ''; ?>">
        <span class="title1"><?php echo __('Gravity Forms - '); ?></span>
            <span class="title"><?php echo __('Google Sheet Integration'); ?></span>
            <hr>

        <div class="inside-tab">
            <div id="google-drive-msg" class="gravityform-gs-alert-card <?php if (!empty(get_option('gfgs_token')) && get_option('gfgs_token') !== "") echo 'hidden'; ?>">
                <?php if (empty($Code)) { ?>
                     <div class="gf-gs-alert-kk">
                    <p class="gf-gs-alert-heading"><?php echo esc_html__('Authenticate with your Google account, follow these steps:', 'gsheetconnector-gravityforms'); ?></p>
                    <ol class="gf-gs-alert-steps">
                        <li><?php echo esc_html__('Click on the "Sign In With Google" button.', 'gsheetconnector-gravityforms'); ?></li>
                        <li><?php echo esc_html__('Grant permissions for the following:', 'gsheetconnector-gravityforms'); ?>
                            <ul class="gf-gs-alert-permissions">
                                <li><?php echo esc_html__('Google Drive', 'gsheetconnector-gravityforms'); ?></li>
                                <li><?php echo esc_html__('Google Sheets', 'gsheetconnector-gravityforms'); ?> <br />
                                <span><?php echo esc_html__('* Ensure that you enable the checkbox for each of these services.', 'gsheetconnector-gravityforms'); ?></span></li>
                            </ul>
                            
                        </li>
                        <li><?php echo esc_html__('This will allow the integration to access your Google Drive and Google Sheets.', 'gsheetconnector-gravityforms'); ?></li>
                    </ol>
                </div>
                <?php } ?>
            </div>


            <div class="gs-integration-layout">
                <label for="gfgs-code"><?php echo esc_html__('Google Access Code', 'gsheetconnector-gravityforms'); ?></label>
                <?php if (!empty(get_option('gfgs_token')) && get_option('gfgs_token') !== "") { ?>
                    <input type="text" name="gfgs-code" id="gfgs-code" value="" disabled placeholder="<?php echo esc_html__('Currently Active', 'gsheetconnector-gravityforms'); ?>" />
                    <input type="button" name="deactivate-log" id="deactivate-log" value="<?php _e('Deactivate', 'gsheetconnector-gravityforms'); ?>" class="button button-primary" />
                    <span class="tooltip">
                        <img src="<?php echo GRAVITY_GOOGLESHEET_URL; ?>assets/image/help.png" class="help-icon" alt="Help Icon">
                        <span class="tooltiptext tooltip-right"><?php echo esc_html__('On deactivation, all your data saved with authentication will be removed, and you need to reauthenticate with your Google account.', 'gsheetconnector-gravityforms'); ?></span>
                    </span>
                    <span class="loading-sign-deactive"></span>
                <?php } else { 
                    $redirct_uri = admin_url('admin.php?page=gf_googlesheet');
                    ?>
                    <input type="text" name="gfgs-code" id="gfgs-code" value="<?php echo esc_attr($Code); ?>" readonly placeholder="<?php echo esc_html__('Click on Sign in with Google button', 'gsheetconnector-gravityforms'); ?>" disabled oncopy="return false;" onpaste="return false;" oncut="return false;" />

                    <?php if (empty($Code)) { ?>
                        <a href="https://oauth.gsheetconnector.com/index.php?client_admin_url=<?php echo $redirct_uri; ?>&plugin=woocommercegsheetconnector" >
                            <img class="custom-image button_gravityformgsc" src="<?php echo GRAVITY_GOOGLESHEET_URL ?>/assets/image/btn_google_signin_dark_pressed_web.gif" alt="Connect Now">
                        </a>
                    <?php } ?>



                    <?php if (!empty($_GET['code'])) { ?>
                        <input type="button" name="save-code" id="save-code" value="<?php _e('Click here to Save Authentication Code', 'gsheetconnector-gravityforms'); ?>" class="custom-button button-primary blinking-button-wc" />
                    <?php } ?>
                <?php } ?>
                <span class="loading-sign"></span>
            </div>

            <?php
            //resolved - google sheet permission issues - START
            $gfgs_verify = get_option('gfgs_verify');
            if (!empty($gfgs_verify) && $gfgs_verify == "invalid-auth") {
            ?>
            
            <p style="color:#c80d0d; font-size: 14px; border: 1px solid;padding: 8px;">
               <?php echo esc_html(__('Something went wrong! It looks you have not given the permission of Google Drive and Google Sheets from your google account.Please Deactivate Auth and Re-Authenticate again with the permissions.', 'gsheetconnector-gravityforms')); ?></p>

            <p style="color:#c80d0d;border: 1px solid;padding: 8px;"><img width="350px"
                    src="<?php echo GRAVITY_GOOGLESHEET_URL; ?>assets/image/permission_screen.png"></p>
            <p style="color:#c80d0d; font-size: 14px; border: 1px solid;padding: 8px;">
                <?php echo esc_html(__('Also,', 'gsheetconnector-gravityforms')); ?><a href="https://myaccount.google.com/permissions"
                    target="_blank"> <?php echo esc_html(__('Click Here ', 'gsheetconnector-gravityforms')); ?></a>
                <?php echo esc_html(__('and if it displays "GSheetConnector for WP Contact Forms" under Third-party apps with account access then remove it.', 'gsheetconnector-gravityforms')); ?>
            </p>
            <?php
            } // Close the if condition
            //resolved - google sheet permission issues - END
            else {
                if (!empty(get_option('gfgs_token')) && get_option('gfgs_token') !== "") {
                    $google_sheet = new GFGSC_googlesheet();
                    $email_account = $google_sheet->gsheet_print_google_account_email();
                    if ($email_account) {
						 update_option( 'gravityforms_gs_auth_expired_free', 'false' );
                    ?>
                    <p class="connected-account-grvty">
                        <?php printf(__('Connected email account: %s', 'gsheetconnector-gravityforms'), $email_account); ?>
                    </p>
                    <?php
                    } else {
						 update_option( 'gravityforms_gs_auth_expired_free', 'true' );
                    ?>
                    <p class="error-message">
                        <?php echo esc_html__('Something went wrong! Your Auth code may be incorrect or expired. Please Deactivate and Re-Authenticate.', 'gsheetconnector-gravityforms'); ?>
                    </p>
                    <?php
                    }
                }
            }
            ?>

            <p>
                <label for="debug-log"><?php echo esc_html__('Debug Log', 'gsheetconnector-gravityforms'); ?></label>
                <label>
                   <!-- display error logs -->
                    <button class="gravity-logs"><?php echo __("View", "gsheetconnector-gravityforms"); ?></button>
                    <!-- <a href="<?php echo GRAVITY_GOOGLESHEET_URL . 'logs/log.txt'; ?>" target="_blank" class="debug-view">View</a> -->
                </label>
                  <!-- clear logs -->
               <label> <a class="clear-debug"><?php echo esc_html__('Clear', 'gsheetconnector-gravityforms');  ?></a></label>
                <span class="clear-loading-sign"></span>
            </p>
            <span id="deactivate-message"></span>
            <p id="gsheet-validation-message"></p>
            <div id="gf-gsc-cta" class="gf-gsc-privacy-box">
                  <div class="gf-gsc-table">
                    <div class="gf-gsc-less-free">
                        <p><i class="dashicons dashicons-lock"></i><?php echo __("We do not store any of the data from your Google account on our servers, everything is processed & stored on your server. We take your privacy extremely seriously and ensure it is never misused.", "gsheetconnector-gravityforms"); ?></p> <a href="https://gsheetconnector.com/usage-tracking/" target="_blank" rel="noopener noreferrer"><?php echo __("Learn more.", "gsheetconnector-gravityforms"); ?></a>
                    </div>
                </div>
            </div>
            <!-- Set nonce -->
            <input type="hidden" name="gf-ajax-nonce" id="gf-ajax-nonce" value="<?php echo wp_create_nonce('gf-ajax-nonce'); ?>" />
        </div>
    </div>
</div>
<!-- display content error logs -->
      <div class="system-Error-logs-gf" style="display:none;">
    <button id="copy-logs-btn" onclick="copyLogs()"><?php echo __("Copy Logs", "gsheetconnector-gravityforms"); ?></button>
    
    <div class="display-logs-gf">
        <?php
            $wpexistDebugFile = get_option('gf_gs_debug_log_file');
            // Check if debug log file exists
            if (!empty($wpexistDebugFile) && file_exists($wpexistDebugFile)) {
                $displaygffreeLogs = nl2br(file_get_contents($wpexistDebugFile));
                if (!empty($displaygffreeLogs)) {
                    echo '<pre id="log-content">' . $displaygffreeLogs . '</pre>';
                } else {
                    echo esc_html__('No errors found.', 'gsheetconnector-gravityforms');
                }
            } else {
                echo esc_html__('No log file exists as no errors are generated', 'gsheetconnector-gravityforms');
            }
        ?>
    </div>
</div>

<script>
// JavaScript function to copy the log content
function copyLogs() {
    var logContent = document.getElementById('log-content');
    
    if (logContent) {
        var range = document.createRange();
        range.selectNode(logContent);
        window.getSelection().removeAllRanges(); // Clear any existing selection
        window.getSelection().addRange(range); // Select the log content

        try {
            document.execCommand('copy'); // Copy the content to clipboard
            alert('Logs copied to clipboard');
        } catch (err) {
            alert('Failed to copy logs');
        }
        
        window.getSelection().removeAllRanges(); // Clear selection after copying
    } else {
        alert('No logs to copy');
    }
}
</script>

 

<!--  -->
<div class="two-col gfgsc-connector-box-help12">
    <div class="col gfgsc-connector-box12">
        <header>
            <h3><?php echo __("Next steps…", "gsheetconnector-gravityforms"); ?></h3>
        </header>
        <div class="gfgsc-connector-box-content12">
            <ul class="gfgsc-connector-list-icon12">
                <li>
                    <a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector" target="_blank">
                        <div>
                            <button class="icon-button">
                                <span class="dashicons dashicons-star-filled"></span>
                            </button>
                            <strong><?php echo __("Upgrade to PRO", "gsheetconnector-gravityforms"); ?></strong>
                            <p><?php echo __("Dynamic Fields Lists, Merge Tags and much more...", "gsheetconnector-gravityforms"); ?></p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="https://www.gsheetconnector.com/docs/gravity-forms-to-google-sheet-free" target="_blank">
                        <div>
                            <button class="icon-button">
                                <span class="dashicons dashicons-download"></span>
                            </button>
                            <strong><?php echo __("Compatibility", "gsheetconnector-gravityforms"); ?></strong>
                            <p><?php echo __("Compatibility with Gravity-Forms Third-Party Plugins", "gsheetconnector-gravityforms"); ?></p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="https://www.gsheetconnector.com/docs/gravity-forms-to-google-sheet-free" target="_blank">
                        <div>
                            <button class="icon-button">
                                <span class="dashicons dashicons-chart-bar"></span>
                            </button>
                            <strong><?php echo __("Multi Languages", "gsheetconnector-gravityforms"); ?></strong>
                            <p><?php echo __("This plugin supports multi-languages as well!", "gsheetconnector-gravityforms"); ?></p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="https://www.gsheetconnector.com/support" target="_blank">
                        <div>
                            <button class="icon-button">
                                <span class="dashicons dashicons-download"></span>
                            </button>
                            <strong><?php echo __("Support Wordpress multisites", "gsheetconnector-gravityforms"); ?></strong>
                            <p><?php echo __("With the use of a Multisite, you’ll also have a new level of user-available: the Super Admin.", "gsheetconnector-gravityforms"); ?></p>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- 2nd div -->
    <div class="col gfgsc-connector-box13">
        <header>
            <h3><?php echo __("Product Support", "gsheetconnector-gravityforms"); ?></h3>
        </header>
        <div class="gfgsc-connector-box-content13">
            <ul class="gfgsc-connector-list-icon13">
                <li>
                    <a href="https://www.gsheetconnector.com/docs/gravity-forms-to-google-sheet-free" target="_blank">
                        <span class="dashicons dashicons-book"></span>
                        <div>
                            <strong><?php echo __("Online Documentation", "gsheetconnector-gravityforms"); ?></strong>
                            <p><?php echo __("Understand all the capabilities of Gravity-Forms GsheetConnector", "gsheetconnector-gravityforms"); ?></p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="https://www.gsheetconnector.com/support" target="_blank">
                        <span class="dashicons dashicons-sos"></span>
                        <div>
                            <strong><?php echo __("Ticket Support", "gsheetconnector-gravityforms"); ?></strong>
                            <p><?php echo __("Direct help from our qualified support team", "gsheetconnector-gravityforms"); ?></p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="https://www.gsheetconnector.com/affiliate-area" target="_blank">
                        <span class="dashicons dashicons-admin-links"></span>
                        <div>
                            <strong><?php echo __("Affiliate Program", "gsheetconnector-gravityforms"); ?></strong>
                            <p><?php echo __("Earn flat 30", "gsheetconnector-gravityforms"); ?>% <?php echo __("on every sale", "gsheetconnector-gravityforms"); ?>!</p>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>


    
 <script>
    // JavaScript/jQuery code
jQuery(document).ready(function($) {
  // Check if the account is connected
  var isAccountConnected = <?php echo (!empty(get_option('gfgs_token')) && get_option('gfgs_token') !== "") ? 'true' : 'false'; ?>;

  // Toggle the visibility of the alert card
  if (isAccountConnected) {
    $('.gravityforms-gs-alert-card').addClass('hidden');
  } else {
    $('.gravityforms-gs-alert-card').removeClass('hidden');
  }
});


document.addEventListener("DOMContentLoaded", function() {
  var closeButton = document.querySelector('.close-link');
  var promotionBox = document.querySelector('.main-promotion-box');

  closeButton.addEventListener('click', function(event) {
    event.preventDefault();
    // Add URL to open in a new window
    var url = 'https://www.gsheetconnector.com/'; // Replace 'https://example.com' with your desired URL
    window.open(url, '_blank');
    
    // Hide the promotion box
    promotionBox.classList.add('hidden');
    
    // Store the state of hiding
    localStorage.setItem('isHidden', 'true');
  });

  // Check if the item is hidden in local storage
  var isHidden = localStorage.getItem('isHidden');
  if (isHidden === 'true') {
    promotionBox.classList.add('hidden');
  }

  // Listen for page refresh events
  window.addEventListener('beforeunload', function() {
    // Check if the box is hidden
    var isHiddenNow = promotionBox.classList.contains('hidden');
    // Store the state of hiding
    localStorage.setItem('isHidden', isHiddenNow ? 'true' : 'false');
  });

  // Reset hiding state on page refresh
  window.addEventListener('load', function() {
    localStorage.removeItem('isHidden');
    promotionBox.classList.remove('hidden');
  });
});



</script>
<?php  include( GRAVITY_GOOGLESHEET_ROOT . "/includes/pages/admin-footer.php" ) ;?>

