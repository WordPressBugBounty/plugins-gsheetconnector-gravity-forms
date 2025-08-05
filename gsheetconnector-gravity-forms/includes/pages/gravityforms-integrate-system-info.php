<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}
$Gravityforms_gs_tools_service = new Gforms_Gsheet_Connector_Free_Init();
?>
<div class="system-statuswc">
    <div class="info-container">
        <h2 class="systemifo"><?php echo esc_html("System Info", "gsheetconnector-gravity-forms"); ?></h2>
        <button onclick="copySystemInfo()"
            class="copy-system-info-gf"><?php echo esc_html("Copy System Info to Clipboard", "gsheetconnector-gravity-forms"); ?></button>
        <?php echo wp_kses_post($Gravityforms_gs_tools_service->get_gfforms_system_info()); ?>

    </div>
</div>
<div class="system-Error">
    <div class="error-container">
        <h2 class="systemerror"><?php echo esc_html("Error Log", "gsheetconnector-gravity-forms"); ?></h2>
        <p>If you have <a href="https://www.gsheetconnector.com/how-to-enable-debugging-in-wordpress"
                target="_blank">WP_DEBUG_LOG</a> enabled, errors are stored in a log file. Here you can find the last
            100 lines in reversed order so that you or the GSheetConnector support team can view it easily. The file
            cannot be edited here.</p>
        <button onclick="copyErrorLog()"
            class="copy-error-log-gf"><?php echo esc_html("Copy Error Log to Clipboard", "gsheetconnector-gravity-forms"); ?></button>
        <button class="clear-content-logs-gf"><?php echo esc_html("Clear", "gsheetconnector-gravity-forms"); ?></button>
        <span class="clear-loading-sign-logs-gf">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
        <div class="clear-content-logs-msg-gf"></div>
        <input type="hidden" name="gf-ajax-nonce" id="gf-ajax-nonce"
            value="<?php echo esc_attr(wp_create_nonce('gf-ajax-nonce')); ?>" />

        <!-- <button onclick="clearErrorLog()" class="clearlogs">Clear</button>  -->
        <div class="copy-message" style="display: none;">
            <?php echo esc_html("Copied", "gsheetconnector-gravity-forms"); ?>
        </div> <!-- Add a hidden div for the copy message -->
        <?php echo wp_kses_post($Gravityforms_gs_tools_service->display_error_log()); ?>
    </div>
</div>



</div>
<?php include(GRAVITY_GOOGLESHEET_ROOT . "/includes/pages/admin-footer.php"); ?>