<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

// Get the saved value from the options table
$uninstall_settings_saved_value = get_option('gravityforms_gs_uninstall_plugin_settings', 'No'); // Default to 'No' if option is not set

?>


<!-- uninstall plugin settings -->
<div class="system-general_setting">
    <div class="info-container">
        <h2 class="systemifo">
            <?php echo esc_html("General Settings", "gsheetconnector-gravityforms"); ?>
        </h2>
        <h3 class="systemifo">
            <?php echo esc_html("Uninstall Plugin Settings", "gsheetconnector-gravityforms"); ?>
        </h3>
        <form method="post">
            <!-- Hidden field ensures a value is always submitted -->
            <input type="hidden" name="gs_gravityforms_uninstall_settings" value="No">


            <!-- Checkbox (conditionally checked based on saved value) -->
            <input type="checkbox" id="gs_gravityforms_uninstall_settings" name="gs_gravityforms_uninstall_settings"
                value="Yes" <?php echo ($uninstall_settings_saved_value === 'Yes') ? 'checked' : ''; ?>>
            <label for="gs_gravityforms_uninstall_settings">
                <?php echo esc_html("Enable to Delete all the settings while deleting the plugin (meta data, options, etc. created by this plugin.)", "gsheetconnector-gravityforms"); ?>
            </label>

            <br /><br />
            <input type="submit" class="button button-primary  uninstall-settings-save"
                name="gs_gravityforms_save_uninstall_settings"
                value="<?php echo esc_html("Save", "gsheetconnector-gravityforms"); ?>" />

            <input type="hidden" name="gs-gravityforms-setting-ajax-nonce" id="gs-gravityforms-setting-ajax-nonce"
                value="<?php echo esc_attr(wp_create_nonce('gs-gravityforms-setting-ajax-nonce')); ?>" />
        </form>
    </div>
</div>