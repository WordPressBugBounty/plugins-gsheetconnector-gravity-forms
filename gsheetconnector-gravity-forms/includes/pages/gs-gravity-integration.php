<?php
// ahmed 2623
$gravityforms_manual_setting = get_option('gravityforms_manual_setting');

$Code = "";
$header = "";

if (isset($_GET['code']) && ($gravityforms_manual_setting == 0)) {
    update_option('is_new_client_secret_gravityformsgsc', 1);
    $Code = sanitize_text_field(wp_unslash($_GET['code']));

    $header = admin_url('admin.php?page=gf_googlesheet');
}


?>




<div class="main-promotion-box"> <a href="#" class="close-link"></a>
    <div class="promotion-inner">
        <h2><?php echo esc_html__("A way to connect WordPress", "gsheetconnector-gravity-forms"); ?> <br />
            <?php echo esc_html__("and", "gsheetconnector-gravity-forms"); ?>
            <span><?php echo esc_html__("Google Sheets Pro", "gsheetconnector-gravity-forms"); ?></span>
        </h2>
        <p class="ratings"><?php echo esc_html__("Ratings", "gsheetconnector-gravity-forms"); ?> : <span></span></p>
        <p><?php echo esc_html__("The Most Powerful Bridge Between WordPress  and", "gsheetconnector-gravity-forms"); ?>
            <strong><?php echo esc_html__("Google Sheets", "gsheetconnector-gravity-forms"); ?></strong>, <br />
            <?php echo esc_html__("Now available for popular", "gsheetconnector-gravity-forms"); ?>
            <strong><?php echo esc_html__("Contact Forms", "gsheetconnector-gravity-forms"); ?></strong>,
            <strong><?php echo esc_html__("Page Builder Forms", "gsheetconnector-gravity-forms"); ?></strong>,<br />
            <?php echo esc_html__("and", "gsheetconnector-gravity-forms"); ?>
            <strong><?php echo esc_html__("E-commerce", "gsheetconnector-gravity-forms"); ?></strong>
            <?php echo esc_html__("Platforms like", "gsheetconnector-gravity-forms"); ?>
            <strong><?php echo esc_html__("WooCommerce", "gsheetconnector-gravity-forms"); ?></strong> <br />
            <?php echo esc_html__("and", "gsheetconnector-gravity-forms"); ?>
            <strong><?php echo esc_html__("Easy Digital Downloads", "gsheetconnector-gravity-forms"); ?></strong>
            (<?php echo esc_html__("EDD", "gsheetconnector-gravity-forms"); ?>).
        </p>
        <div class="button-bar"> <a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector"
                target="_blank"><?php echo esc_html__("Buy Now", "gsheetconnector-gravity-forms"); ?></a> <a
                href="https://demo.gsheetconnector.com/"
                target="_blank"><?php echo esc_html__("Check Demo", "gsheetconnector-gravity-forms"); ?></a> </div>
    </div>
    <div class="gsheet-plugins"></div>
</div> <!-- main-promotion-box #end -->



<div class="card-gravityforms dropdownoption-gravityforms">
	
		<h2><?php echo esc_html__('Gravity Forms - Google Sheet Integration', "gsheetconnector-gravity-forms"); ?></h2>
	<p class="sub-desc"><?php echo esc_html__('Choose your Google API Setting from the dropdown. You can select Use Existing Client/Secret Key (Auto Google API Configuration) or Use Manual Client/Secret Key (Use Your Google API Configuration - Pro Version) or Use Service Account (Recommended- Pro Version) . After saving, the related integration settings will appear, and you can complete the setup.', "gsheetconnector-gravity-forms"); ?></p>
	
	
    <div class="lbl-drop-down-select row">
        <label
            for="gs_gravityforms_dro_option"><?php echo esc_html__('Choose Google API Setting', 'gsheetconnector-gravity-forms'); ?></label>
   
    <div class="drop-down-select-btn">
        <select id="gs_gravityforms_dro_option" name="gs_gravityforms_dro_option">
            <option value="gravityforms_existing" selected>
                <?php echo esc_html__('Use Existing Client/Secret Key (Auto Google API Configuration)', 'gsheetconnector-gravity-forms'); ?>
            </option>
            <option value="gravityforms_manual" disabled="">
                <?php echo esc_html__('Use Manual Client/Secret Key (Use Your Google API Configuration) (Upgrade To PRO)', 'gsheetconnector-gravity-forms'); ?>
            </option>
            <option value="gravityforms_service" disabled="">
                <?php echo esc_html__('Service Account (Recommended) (Upgrade To PRO)', 'gsheetconnector-gravity-forms'); ?>
            </option>
        </select>
        <p class="int-meth-btn-gravityforms"><a
                href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector" target="_blank"><input
                    type="button" name="save-method-api-gravityforms" id=""
                    value="<?php echo esc_html('Upgrade To PRO', 'gsheetconnector-gravity-forms'); ?>"
                    class="update-btn" /></a> 
			
			
			
        </p>
		
		
		</div>
    </div>
</div>

<!-- ahmed 2623 -->
<div class="gform-tab-container">
    <div class="gform-card gform-tab-content">
        <input type="hidden" name="redirect_auth_gravityforms" id="redirect_auth_gravityforms"
            value="<?php echo (isset($header)) ? esc_attr($header) : ''; ?>">
       
	 

        <div class="inside-tab">
            <div id="google-drive-msg" class="gravityform-gs-alert-card <?php if (!empty(get_option('gfgs_token')) && get_option('gfgs_token') !== "")
                echo 'hidden'; ?>">
                <?php if (empty($Code)) { ?>
                    <div class="gf-gs-alert-kk">
                        <h3>
                            <?php echo esc_html__('Authenticate with your Google account, follow these steps:', 'gsheetconnector-gravity-forms'); ?>
                        </h3>
                        <ol class="gf-gs-alert-steps">
                            <li><?php echo esc_html__('Click on the "Sign In With Google" button.', 'gsheetconnector-gravity-forms'); ?>
                            </li>
                            <li><?php echo esc_html__('Grant permissions for the following:', 'gsheetconnector-gravity-forms'); ?> </li>
                                
                                    <li><?php echo esc_html__('Google Drive', 'gsheetconnector-gravity-forms'); ?></li>
                                    <li><?php echo esc_html__('Google Sheets', 'gsheetconnector-gravity-forms'); ?> <br />
                                        <span><?php echo esc_html__('* Ensure that you enable the checkbox for each of these services.', 'gsheetconnector-gravity-forms'); ?></span>
                                    </li>
                                
                           
                            <li><?php echo esc_html__('This will allow the integration to access your Google Drive and Google Sheets.', 'gsheetconnector-gravity-forms'); ?>
                            </li>
                        </ol>
                    </div>
                <?php } ?>
            </div>

			
			
			<h2><?php echo esc_html__('Google Sheet Integration - Use Existing Client/Secret Key (Auto Google API Configuration)', 'gsheetconnector-gravity-forms'); ?></h2>
			<p class="sub-desc"><?php echo esc_html__('Automatic integration allows you to connect Gravity Forms with Google Sheets using built-in Google API configuration. By authorizing your Google account, the plugin will handle API setup and authentication automatically, enabling seamless form data sync. Learn more in the documentation', "gsheetconnector-gravity-forms"); ?> <a href="https://www.gsheetconnector.com/docs/gravity-forms-gsheetconnector/integration-with-google-existing-method" target="_blank"><?php echo esc_html__('click here', 'gsheetconnector-gravity-forms'); ?></a>.</p>
			
			
            <div class="gs-integration-layout row">
                <label
                    for="gfgs-code"><?php echo esc_html__('Google Access Code ', 'gsheetconnector-gravity-forms'); ?></label>
                <?php if (!empty(get_option('gfgs_token')) && get_option('gfgs_token') !== "") { ?>
                    <input type="text" name="gfgs-code" id="gfgs-code" value="" disabled
                        placeholder="<?php echo esc_html__('Currently Active', 'gsheetconnector-gravity-forms'); ?>" />
                    <input type="button" name="deactivate-log" id="deactivate-log"
                        value="<?php echo esc_html('Deactivate', 'gsheetconnector-gravity-forms'); ?>"
                        class="button button-primary" /> 
				
					
				
                    <span class="loading-sign-deactive"></span>
                <?php } else {
                    $redirct_uri = admin_url('admin.php?page=gf_googlesheet');
                    ?>
                    <input type="text" name="gfgs-code" id="gfgs-code" value="<?php echo esc_attr($Code); ?>" readonly
                        placeholder="<?php echo esc_html__('Click on Sign in with Google button', 'gsheetconnector-gravity-forms'); ?>"
                        disabled oncopy="return false;" onpaste="return false;" oncut="return false;" />

                    <?php if (empty($Code)) { ?>
                        <a
                            href="<?php echo esc_url('https://oauth.gsheetconnector.com/index.php?client_admin_url=' . urlencode($redirct_uri) . '&plugin=woocommercegsheetconnector'); ?>">
                            <img class="custom-image button_gravityformgsc"
                                src="<?php echo esc_url(GRAVITY_GOOGLESHEET_URL . '/assets/image/btn_google_signin_dark_pressed_web.gif'); ?>"
                                alt="Connect Now">
                        </a>

                    <?php } ?>



                    <?php if (!empty($_GET['code'])) { ?>
                        <input type="button" name="save-code" id="save-code"
                            value="<?php echo esc_html__('Click here to Save Authentication Code', 'gsheetconnector-gravity-forms'); ?>"
                            class="custom-button button-primary blinking-button-wc" />
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
                    <?php echo esc_html(__('Something went wrong! It looks you have not given the permission of Google Drive and Google Sheets from your google account.Please Deactivate Auth and Re-Authenticate again with the permissions.', 'gsheetconnector-gravity-forms')); ?>
                </p>

                <p style="color:#c80d0d;border: 1px solid;padding: 8px;"><img width="350px"
                        src="<?php echo esc_url(GRAVITY_GOOGLESHEET_URL . 'assets/image/permission_screen.png'); ?>">
                </p>
                <p style="color:#c80d0d; font-size: 14px; border: 1px solid;padding: 8px;">
                    <?php echo esc_html(__('Also,', 'gsheetconnector-gravity-forms')); ?><a
                        href="https://myaccount.google.com/permissions" target="_blank">
                        <?php echo esc_html(__('Click Here ', 'gsheetconnector-gravity-forms')); ?></a>
                    <?php echo esc_html(__('and if it displays "GSheetConnector for WP Contact Forms" under Third-party apps with account access then remove it.', 'gsheetconnector-gravity-forms')); ?>
                </p>
                <?php
            } // Close the if condition
            //resolved - google sheet permission issues - END
            else {
                if (!empty(get_option('gfgs_token')) && get_option('gfgs_token') !== "") {
                    $google_sheet = new Gfgscf_googlesheet();
                    $email_account = $google_sheet->gsheet_print_google_account_email();
                    if ($email_account) {
                        update_option('gravityforms_gs_auth_expired_free', 'false');
                        ?>
                        <p class="connected-account-grvty row">
                            <label><?php printf(esc_html('Connected Email Account', 'gsheetconnector-gravity-forms'), esc_attr($email_account)); ?></label>
                            <?php printf(wp_kses('<u>%s </u>', 'gsheetconnector-gravity-forms'), esc_attr($email_account)); ?>
                        </p>

                        <?php
                    } else {
                        update_option('gravityforms_gs_auth_expired_free', 'true');
                        ?>
                        <p class="error-message">
                            <?php echo esc_html__('Something went wrong! Your Auth code may be incorrect or expired. Please Deactivate and Re-Authenticate.', 'gsheetconnector-gravity-forms'); ?>
                        </p>
                        <?php
                    }
                }
            }
            ?>

            
            <span id="deactivate-message"></span>
            <p id="gsheet-validation-message"></p>
			
			
			<div class="msg success-msg">
				<i class="fa-solid fa-lock"></i>
				
				<p><?php echo esc_html__("Note : We do not store any of the data from your Google account on our servers, everything is processed & stored on your server. We take your privacy extremely seriously and ensure it is never misused.", "gsheetconnector-gravity-forms"); ?>
				<a href="https://gsheetconnector.com/usage-tracking/" target="_blank" rel="noopener noreferrer"><?php echo esc_html__("Learn more.", "gsheetconnector-gravity-forms"); ?></a></p> 
			</div>
			
			
           
            <!-- Set nonce -->
            <input type="hidden" name="gf-ajax-nonce" id="gf-ajax-nonce"
                value="<?php echo esc_attr(wp_create_nonce('gf-ajax-nonce')); ?>" />
			
			
			
			<p>
                <label for="debug-log"><?php echo esc_html__('Debug Log', 'gsheetconnector-gravity-forms'); ?></label>
                <label>
                    <!-- display error logs -->
                    <button
                        class="gravity-logs"><?php echo esc_html__("View", "gsheetconnector-gravity-forms"); ?></button>

                </label>
                <!-- clear logs -->
                <label> <a
                        class="clear-debug"><?php echo esc_html__('Clear', 'gsheetconnector-gravity-forms'); ?></a></label>
                <span class="clear-loading-sign">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
            </p>

        </div>
    </div>
</div>
<!-- display content error logs -->
<div class="system-Error-logs-gf" style="display:none;">
    <button id="copy-logs-btn" onclick="copyLogs()">
        <?php echo esc_html__( 'Copy Logs', 'gsheetconnector-gravity-forms' ); ?>
    </button>

    <div class="display-logs-gf">
        <?php
        $wpexist_debug_file = get_option( 'gf_gs_debug_log_file' );

        if ( ! empty( $wpexist_debug_file ) && file_exists( $wpexist_debug_file ) ) {
            $display_gf_logs = file_get_contents( $wpexist_debug_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

            if ( ! empty( $display_gf_logs ) ) {
                // Convert line breaks and escape output
                echo '<pre id="log-content">' . esc_html( $display_gf_logs ) . '</pre>';
            } else {
                echo esc_html__( 'No errors found.', 'gsheetconnector-gravity-forms' );
            }
        } else {
            echo esc_html__( 'No log file exists as no errors are generated.', 'gsheetconnector-gravity-forms' );
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
            <h3><?php echo esc_html__("Next steps…", "gsheetconnector-gravity-forms"); ?></h3>
        </header>
        <div class="gfgsc-connector-box-content12">
            <ul class="gfgsc-connector-list-icon12">
                <li>
                    <a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector" target="_blank">
                        <div>
                            <button class="icon-button">
                                <span class="dashicons dashicons-star-filled"></span>
                            </button>
                            <strong><?php echo esc_html("Upgrade to PRO", "gsheetconnector-gravity-forms"); ?></strong>
                            <p><?php echo esc_html__("Dynamic Fields Lists, Merge Tags and much more...", "gsheetconnector-gravity-forms"); ?>
                            </p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="https://www.gsheetconnector.com/docs/gsheetconnnector-for-wpforms/requirements" target="_blank">
                        <div>
                            <button class="icon-button">
                                <span class="dashicons dashicons-download"></span>
                            </button>
                            <strong><?php echo esc_html__("Compatibility", "gsheetconnector-gravity-forms"); ?></strong>
                            <p><?php echo esc_html__("Compatibility with Gravity-Forms Third-Party Plugins", "gsheetconnector-gravity-forms"); ?>
                            </p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="https://www.gsheetconnector.com/docs/gsheetconnnector-for-wpforms/plugin-settings-pro-version" target="_blank">
                        <div>
                            <button class="icon-button">
                                <span class="dashicons dashicons-chart-bar"></span>
                            </button>
                            <strong><?php echo esc_html__("Multi Languages", "gsheetconnector-gravity-forms"); ?></strong>
                            <p><?php echo esc_html__("This plugin supports multi-languages as well!", "gsheetconnector-gravity-forms"); ?>
                            </p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="https://www.gsheetconnector.com/docs/gsheetconnnector-for-wpforms/plugin-settings-free-version" target="_blank">
                        <div>
                            <button class="icon-button">
                                <span class="dashicons dashicons-download"></span>
                            </button>
                            <strong><?php echo esc_html__("Support Wordpress multisites", "gsheetconnector-gravity-forms"); ?></strong>
                            <p><?php echo esc_html__("With the use of a Multisite, you’ll also have a new level of user-available: the Super Admin.", "gsheetconnector-gravity-forms"); ?>
                            </p>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- 2nd div -->
    <div class="col gfgsc-connector-box13">
        <header>
            <h3><?php echo esc_html__("Product Support", "gsheetconnector-gravity-forms"); ?></h3>
        </header>
        <div class="gfgsc-connector-box-content13">
            <ul class="gfgsc-connector-list-icon13">
                <li>
                    <a href="https://www.gsheetconnector.com/docs/gravity-forms-gsheetconnector" target="_blank">
                        <span class="dashicons dashicons-book"></span>
                        <div>
                            <strong><?php echo esc_html__("Online Documentation", "gsheetconnector-gravity-forms"); ?></strong>
                            <p><?php echo esc_html__("Understand all the capabilities of Gravity-Forms GsheetConnector", "gsheetconnector-gravity-forms"); ?>
                            </p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="https://www.gsheetconnector.com/support" target="_blank">
                        <span class="dashicons dashicons-sos"></span>
                        <div>
                            <strong><?php echo esc_html__("Ticket Support", "gsheetconnector-gravity-forms"); ?></strong>
                            <p><?php echo esc_html__("Direct help from our qualified support team", "gsheetconnector-gravity-forms"); ?>
                            </p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="https://www.gsheetconnector.com/affiliate-area" target="_blank">
                        <span class="dashicons dashicons-admin-links"></span>
                        <div>
                            <strong><?php echo esc_html__("Affiliate Program", "gsheetconnector-gravity-forms"); ?></strong>
                            <p><?php echo esc_html__("Earn flat 30", "gsheetconnector-gravity-forms"); ?>%
                                <?php echo esc_html__("on every sale", "gsheetconnector-gravity-forms"); ?>!
                            </p>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>



<script>
    // JavaScript/jQuery code
    jQuery(document).ready(function ($) {
        // Check if the account is connected
        var isAccountConnected = <?php echo (!empty(get_option('gfgs_token')) && get_option('gfgs_token') !== "") ? 'true' : 'false'; ?>;

        // Toggle the visibility of the alert card
        if (isAccountConnected) {
            $('.gravityforms-gs-alert-card').addClass('hidden');
        } else {
            $('.gravityforms-gs-alert-card').removeClass('hidden');
        }
    });


    document.addEventListener("DOMContentLoaded", function () {
        var closeButton = document.querySelector('.close-link');
        var promotionBox = document.querySelector('.main-promotion-box');

        closeButton.addEventListener('click', function (event) {
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
        window.addEventListener('beforeunload', function () {
            // Check if the box is hidden
            var isHiddenNow = promotionBox.classList.contains('hidden');
            // Store the state of hiding
            localStorage.setItem('isHidden', isHiddenNow ? 'true' : 'false');
        });

        // Reset hiding state on page refresh
        window.addEventListener('load', function () {
            localStorage.removeItem('isHidden');
            promotionBox.classList.remove('hidden');
        });
    });



</script>
<?php include(GRAVITY_GOOGLESHEET_ROOT . "/includes/pages/admin-footer.php"); ?>