<?php

/**
 * Service class for Google Sheet Connector
 * @since 1.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Gs_Connector_Service Class
 *
 * @since 1.0
 */
class GFGS_Connector_Service
{

    protected $_short_title = 'Googlesheet';

    /**
     *  Set things up.
     *  @since 1.0
     */
    public function __construct()
    {
        add_action('wp_ajax_verify_code_integation', array($this, 'verify_code_integation'));
        add_action('wp_ajax_deactivate_gs_code_integation', array($this, 'deactivate_gs_code_integation'));
        add_action('wp_ajax_gfgs_clear_log', array($this, 'gfgs_clear_logs'));
        // clear debug logs method using ajax for system status tab
        add_action('wp_ajax_gf_clear_debug_log', array($this, 'gf_clear_debug_logs'));
        add_action('wp_ajax_gs_gravity_install_plugin', array($this, 'gs_gravity_install_plugin'));

        add_action('wp_ajax_gs_gravity_activate_plugin', array($this, 'gs_gravity_activate_plugin'));
        add_action("wp_ajax_gs_gravity_deactivate_plugin", array($this, "gs_gravity_deactivate_plugin"));
        // add_action('admin_init', array($this, 'execute_post_data'));

        add_action('wp_ajax_gscgff_save_uninstall_settings_ajax_free', array($this, 'gscgff_save_uninstall_settings_ajax_free'));

        /* Install Gravity Forms plugin */
        add_action('wp_ajax_gscgff_install_plugin', array($this, 'gscgff_install_plugin'));

        /* Activate Gravity Forms plugin */
        add_action('wp_ajax_gscgff_activate_plugin', array($this, 'gscgff_activate_plugin'));

        /* Deactivate Gravity Forms plugin */
        add_action('wp_ajax_gscgff_deactivate_plugin', array($this, 'gscgff_deactivate_plugin'));

        /** Clear Debug Log file */
        add_action('wp_ajax_gscgff_clear_log', array($this, 'gscgff_clear_log'));

        add_action('wp_ajax_dismiss_pro_notice', array($this, 'gsheet_dismiss_pro_notice'));

        /* dismiss  notification */
        add_action('wp_ajax_gscgff_dismiss_notice', array($this, 'gscgff_dismiss_notice_callback'));

        /* snooze notitiacation  */
        add_action('wp_ajax_gscgff_snooze_notice', array($this, 'gscgff_snooze_notice_callback'));




    }

 /**
 * Deactivate a plugin via AJAX request.
 *
 * Handles:
 * - Verifies AJAX nonce for security (CSRF protection)
 * - Checks user capability to deactivate plugins
 * - Retrieves and sanitizes plugin slug from request
 * - Validates plugin slug using WordPress core function
 * - Attempts plugin deactivation
 * - Confirms whether deactivation was successful
 * - Logs errors for debugging purposes
 * - Returns JSON success or error response
 *
 * @since 1.0.0
 * @return void
 */
 function gs_gravity_deactivate_plugin()
 {
        // Nonce verification
    check_ajax_referer('gs_gravity_ajax_nonce', 'security');

        // Check if user has permission to deactivate plugins
    if (!current_user_can('activate_plugins')) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error: User lacks permission to deactivate plugins.');
        wp_send_json_error(esc_html__('You do not have permission to deactivate plugins.', 'gsheetconnector-gravity-forms'));
    }
        // Safely fetch plugin slug.
    $plugin_slug = filter_input(INPUT_POST, 'plugin_slug', FILTER_SANITIZE_STRING);

    if (empty($plugin_slug)) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error: Plugin slug missing or invalid.');
        wp_send_json_error(esc_html__('Invalid plugin slug.', 'gsheetconnector-gravity-forms'));
    }

        // Validate plugin slug using core function.
    $validate = validate_plugin($plugin_slug);
    if (is_wp_error($validate)) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error: ' . $validate->get_error_message());
        wp_send_json_error(esc_html($validate->get_error_message()));
    }

        // Attempt to deactivate the plugin.
    deactivate_plugins($plugin_slug);

        // Confirm deactivation.
    if (is_plugin_active($plugin_slug)) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log("Error: Plugin deactivation failed - {$plugin_slug}");
        wp_send_json_error(esc_html__('Failed to deactivate plugin.', 'gsheetconnector-gravity-forms'));
    }

        // Success.
    wp_send_json_success(esc_html__('Plugin deactivated successfully.', 'gsheetconnector-gravity-forms'));
}


/**
 * Install or upgrade a plugin via AJAX request.
 *
 * Handles:
 * - Verifies nonce for security (CSRF protection)
 * - Checks user capabilities (install/update plugins)
 * - Validates plugin slug and download URL
 * - Restricts downloads to WordPress.org sources only
 * - Verifies remote package via HEAD request
 * - Installs or upgrades plugin using WP Upgrader API
 * - Returns JSON success or error response
 *
 * @since 1.0.0
 * @return void
 */
function gs_gravity_install_plugin()
{
        // 1) CSRF check
    check_ajax_referer('gs_gravity_ajax_nonce', 'security');

        // 2) AuthZ: only admins (or specific capability).
    if (! current_user_can('install_plugins') && ! current_user_can('update_plugins')) {
        wp_send_json_error(
            array('message' => esc_html__('You do not have permission to install or update plugins.', 'gsheetconnector-gravity-forms'))
        );
    }

        // 3) Fetch and sanitize input.
    $plugin_slug  = filter_input(INPUT_POST, 'plugin_slug', FILTER_SANITIZE_STRING);
    $download_url = filter_input(INPUT_POST, 'download_url', FILTER_SANITIZE_URL);

    if (empty($plugin_slug) || empty($download_url)) {
        wp_send_json_error(
            array('message' => esc_html__('Missing or invalid parameters.', 'gsheetconnector-gravity-forms'))
        );
    }
        // 4) Validate URL format.
    if (! wp_http_validate_url($download_url)) {
        wp_send_json_error(
            array('message' => esc_html__('Invalid download URL.', 'gsheetconnector-gravity-forms'))
        );
    }

        // 5) Restrict to WordPress.org downloads.
    $allowed_hosts = array('downloads.wordpress.org');
    $host          = wp_parse_url($download_url, PHP_URL_HOST);

    if (! in_array($host, $allowed_hosts, true)) {
        wp_send_json_error(
            array('message' => esc_html__('Unsupported download source.', 'gsheetconnector-gravity-forms'))
        );
    }

        // 6) Ensure correct path prefix.
    if (
        stripos($download_url, 'https://downloads.wordpress.org/plugin/') !== 0 &&
        stripos($download_url, 'https://downloads.wordpress.org/releases/') !== 0
    ) {
        wp_send_json_error(
            array('message' => esc_html__('Unsupported download path.', 'gsheetconnector-gravity-forms'))
        );
    }

        // 7) Include upgrader APIs.
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/update.php';

        // 8) Validate package via HEAD request.
    $head = wp_remote_head(
        $download_url,
        array(
            'timeout'     => 15,
            'redirection' => 5,
        )
    );

    if (is_wp_error($head)) {
            // Fallback to GET if HEAD is blocked.
        $head = wp_remote_get(
            $download_url,
            array(
                'method'      => 'HEAD',
                'timeout'     => 15,
                'redirection' => 5,
            )
        );
    }

    if (is_wp_error($head)) {
        wp_send_json_error(
            array('message' => esc_html__('Could not verify plugin package.', 'gsheetconnector-gravity-forms'))
        );
    }

    $ct = wp_remote_retrieve_header($head, 'content-type');
    if ($ct && stripos($ct, 'zip') === false && stripos($ct, 'octet-stream') === false) {
        wp_send_json_error(
            array('message' => esc_html__('Package is not a valid ZIP file.', 'gsheetconnector-gravity-forms'))
        );
    }

    $len = (int) wp_remote_retrieve_header($head, 'content-length');
        if ($len && $len > 50 * 1024 * 1024) { // 50MB limit.
            wp_send_json_error(
                array('message' => esc_html__('Plugin package is too large.', 'gsheetconnector-gravity-forms'))
            );
        }

        // 9) Perform the install/upgrade.
        $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
        $result   = $upgrader->install($download_url);

        if (is_wp_error($result)) {
            wp_send_json_error(
                array('message' => esc_html__('Installation failed: ', 'gsheetconnector-gravity-forms') . esc_html($result->get_error_message()))
            );
        }

        wp_send_json_success(
            array('message' => esc_html__('Plugin installed successfully.', 'gsheetconnector-gravity-forms'))
        );
    }

 /**
 * Activate a plugin via AJAX request.
 *
 * Handles:
 * - Nonce verification for security
 * - User capability check (activate_plugins)
 * - Validates plugin slug existence
 * - Activates plugin using WordPress API
 * - Logs errors if activation fails
 * - Returns JSON response
 *
 * @since 1.0.0
 * @return void
 */
 function gs_gravity_activate_plugin()
 {
        // 1) Nonce verification.
    check_ajax_referer('gs_gravity_ajax_nonce', 'security');

        // 2) Permission check.
    if (! current_user_can('activate_plugins')) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Permission denied for plugin activation.');
        wp_send_json_error(
            array('message' => esc_html__('You do not have permission to activate plugins.', 'gsheetconnector-gravity-forms'))
        );
    }

        // 3) Fetch and sanitize plugin slug.
    $plugin_slug = filter_input(INPUT_POST, 'plugin_slug', FILTER_SANITIZE_STRING);


    if (empty($plugin_slug)) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Missing plugin slug for activation.');
        wp_send_json_error(
            array('message' => esc_html__('Missing plugin slug.', 'gsheetconnector-gravity-forms'))
        );
    }


        // 4) Validate plugin.
    if (! function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $all_plugins = get_plugins();

    if (! isset($all_plugins[$plugin_slug])) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Invalid plugin slug: ' . esc_html($plugin_slug));
        wp_send_json_error(
            array('message' => esc_html__('Invalid plugin slug.', 'gsheetconnector-gravity-forms'))
        );
    }

        // 5) Load plugin functions.
    if (! function_exists('activate_plugin')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

        // 6) Attempt activation.
    $activated = activate_plugin($plugin_slug);

    if (is_wp_error($activated)) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log(
            'Activation failed: ' . esc_html($activated->get_error_message())
        );
        wp_send_json_error(
            array('message' => esc_html__('Plugin activation failed.', 'gsheetconnector-gravity-forms'))
        );
    }


        // 7) Success response.
    wp_send_json_success(
        array('message' => esc_html__('Plugin activated successfully.', 'gsheetconnector-gravity-forms'))
    );
}

/**
 * Verify Google Sheet integration code via AJAX.
 *
 * Handles:
 * - Nonce verification
 * - Sanitizes and stores access code
 * - Initiates Google Sheets authentication
 * - Updates verification status
 * - Returns JSON success or error
 *
 * @since 1.0.0
 * @return void
 */
public function verify_code_integation()
{

    try {
            // nonce check
        check_ajax_referer('gf-ajax-nonce', 'security');

        /* sanitize incoming data */
        $Code = isset($_POST['code']) ? sanitize_text_field(wp_unslash($_POST['code'])) : '';

        update_option('gfgs_access_code', $Code);

        if (get_option('gfgs_access_code') != '') {
            include_once(GRAVITY_GOOGLESHEET_ROOT . '/lib/google-sheets.php');
            Gfgscf_googlesheet::preauth(get_option('gfgs_access_code'));
                //update_option('gfgs_verify', 'valid');
                // After validation fetch sheetname and tabs from the user account 
            wp_send_json_success();
        } else {
            update_option('gfgs_verify', 'invalid');
            wp_send_json_error();
        }
    } catch (Exception $e) {
        /*GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error during verification: ' . $e->getMessage());*/
        wp_send_json_error();
    }
}

/**
 * Deactivate Google Sheets integration.
 *
 * Handles:
 * - Nonce verification
 * - Revokes stored access token
 * - Deletes all related plugin options
 * - Returns JSON success or error
 *
 * @since 1.0.0
 * @return void
 */
public function deactivate_gs_code_integation()
{
    try {
            // nonce check
        check_ajax_referer('gf-ajax-nonce', 'security');

        if (get_option('gfgs_token') !== '') {

            $accesstoken = get_option('gfgs_token');
            $client = new Gfgscf_googlesheet();
            $client->revokeToken_auto($accesstoken);

            delete_option('gfgs_feeds');
            delete_option('gfgs_sheetId');
            delete_option('gfgs_token');
            delete_option('gfgs_access_code');
            delete_option('gfgs_verify');
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    } catch (Exception $e) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error during deactivation: ' . $e->getMessage());
            // Handle any exceptions thrown during deactivation


        wp_send_json_error();
    }
}

/**
 * Clear custom plugin log file via AJAX.
 *
 * Handles:
 * - Nonce verification
 * - Checks if debug log file exists
 * - Uses WP Filesystem API to clear file contents
 * - Returns success or failure message
 *
 * @since 1.0.0
 * @return void
 */
public function gfgs_clear_logs()
{
    try {
            // nonce check
        check_ajax_referer('gf-ajax-nonce', 'security');

        $wpexistDebugFile = get_option('gf_gs_debug_log_file');
        $clear_file_msg = '';

        if (!empty($wpexistDebugFile) && file_exists($wpexistDebugFile)) {
                // Initialize WP Filesystem
            if (!function_exists('request_filesystem_credentials')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            if (WP_Filesystem()) {
                global $wp_filesystem;
                    // Clear the file content
                $wp_filesystem->put_contents($wpexistDebugFile, '', FS_CHMOD_FILE);
                $clear_file_msg = 'Logs are cleared.';
            } else {
                $clear_file_msg = 'Could not initialize WP Filesystem API.';
            }
        } else {
            $clear_file_msg = 'No log file exists to clear logs.';
        }

        wp_send_json_success($clear_file_msg);
    } catch (Exception $e) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log($e->getMessage());
        wp_send_json_error();
    }
}

/**
 * Clear WordPress debug.log file via AJAX (System Status tab).
 *
 * Handles:
 * - Nonce verification
 * - Initializes WP Filesystem API
 * - Empties debug.log file if exists
 * - Returns JSON response
 *
 * @since 1.0.0
 * @return void
 */
public function gf_clear_debug_logs()
{
        // nonce check
    check_ajax_referer('gf-ajax-nonce', 'security');

    if (!function_exists('request_filesystem_credentials')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    if (WP_Filesystem()) {
        global $wp_filesystem;

        $file_path = WP_CONTENT_DIR . '/debug.log';

        if ($wp_filesystem->exists($file_path)) {
                $wp_filesystem->put_contents($file_path, '', FS_CHMOD_FILE); // Empty the file
            }

            wp_send_json_success();
        } else {
            wp_send_json_error('Filesystem could not be initialized.');
        }
    }

/**
 * Get list of Gravity Forms connected to Google Sheets.
 *
 * Handles:
 * - Checks if Gravity Forms table exists
 * - Fetches form ID and title
 * - Returns array of forms
 *
 * @since 1.0.0
 * @return array
 */
public function get_forms_connected_to_sheet()
{
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'gf_form';

        // Check if the Gravity Forms table exists
   // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    $query = [];

    if ($result === $table_name) {
        // Fetch all forms ordered by ID
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $query = $wpdb->get_results(
            "SELECT id, title FROM {$wpdb->prefix}gf_form ORDER BY id"
        );
            // Alternatively: Join with postmeta table if needed
            // $query = $wpdb->get_results("SELECT * FROM {$wpdb->base_prefix}gf_form AS gf JOIN {$wpdb->base_prefix}postmeta AS pm ON gf.id = pm.post_id WHERE pm.meta_key = 'gfgs_settings'");
    }

    return $query;
}

/**
 * Save uninstall settings via AJAX.
 *
 * Handles:
 * - Nonce verification
 * - Sanitizes uninstall setting value
 * - Updates option in database
 * - Returns JSON success
 *
 * @since 1.0.0
 * @return void
 */
public function gscgff_save_uninstall_settings_ajax_free()
{
    check_ajax_referer('gscgff-gravity-setting-ajax-nonce', 'security');

    $value = isset($_POST['uninstall_setting']) ? intval($_POST['uninstall_setting']) : 0;

    update_option('gscgff_uninstall_setting', $value);

    wp_send_json_success();
}

/**
 * Deactivate a plugin via AJAX.
 *
 * Handles:
 * - Nonce verification
 * - Permission check (activate_plugins)
 * - Validates plugin slug
 * - Ensures plugin exists before deactivation
 * - Deactivates plugin safely
 * - Returns JSON response
 *
 * @since 1.0.0
 * @return void
 */
function gscgff_deactivate_plugin()
{
        // nonce check
    check_ajax_referer('gscgff-ajax-nonce', 'security');

    if (!current_user_can('activate_plugins')) {
        wp_send_json_error('You do not have permission to deactivate plugins.');
    }

    if (!isset($_POST['plugin_slug'])) {
        wp_send_json_error('Plugin slug is missing.');
    }

    $plugin_slug = sanitize_text_field(wp_unslash($_POST['plugin_slug']));

    if (empty($plugin_slug)) {
        wp_send_json_error('Invalid plugin.');
    }

        // Ensure plugin exists before attempting to deactivate
    if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_slug)) {
        wp_send_json_error('Plugin not found.');
    }

    deactivate_plugins($plugin_slug);

    if (is_plugin_active($plugin_slug)) {
        wp_send_json_error('Failed to deactivate plugin.');
    }

    wp_send_json_success('Plugin deactivated successfully.');
}

/**
 * Install or upgrade plugin via AJAX (Free version handler).
 *
 * Handles:
 * - Nonce and permission validation
 * - Sanitizes plugin slug and download URL
 * - Checks if plugin already installed
 * - Performs upgrade if update available
 * - Installs plugin if not present
 * - Returns JSON response
 *
 * @since 1.0.0
 * @return void
 */
public function gscgff_install_plugin()
{

        // 🔐 Nonce verify
    if (! check_ajax_referer('gscgff-ajax-nonce', 'security', false)) {
        wp_send_json_error([
            'message' => __('Invalid security token', 'gsheetconnector-gravity-forms')
        ]);
    }

        // 🔐 Permission check
    if (! current_user_can('install_plugins')) {
        wp_send_json_error([
            'message' => __('You do not have permission to install plugin', 'gsheetconnector-gravity-forms')
        ]);
    }

    if (empty($_POST['plugin_slug']) || empty($_POST['download_url'])) {
        wp_send_json_error([
            'message' => __('Missing required parameters', 'gsheetconnector-gravity-forms')
        ]);
    }

    $plugin_slug  = sanitize_text_field(wp_unslash($_POST['plugin_slug']));
    $download_url = esc_url_raw(wp_unslash($_POST['download_url']));

    if (empty($plugin_slug) || empty($download_url)) {
        wp_send_json_error([
            'message' => __('Invalid plugin data', 'gsheetconnector-gravity-forms')
        ]);
    }

        // Required files
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/update.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());

    $installed_plugins = get_plugins();
    $plugin_path = '';

        // 🔎 Find installed plugin
    foreach ($installed_plugins as $path => $details) {
        if (strpos($path, $plugin_slug . '/') === 0) {
            $plugin_path = $path;
            break;
        }
    }

        // ==============================
        // 🔄 If Installed → Upgrade
        // ==============================
    if ($plugin_path) {

        $update_plugins = get_site_transient('update_plugins');

        if (isset($update_plugins->response[$plugin_path])) {

            $result = $upgrader->upgrade($plugin_path);

            if (is_wp_error($result)) {
                wp_send_json_error([
                    'message' => __('Upgrade failed: ', 'gsheetconnector-gravity-forms') . $result->get_error_message()
                ]);
            }

            wp_send_json_success([
                'message' => __('Plugin upgraded successfully', 'gsheetconnector-gravity-forms')
            ]);
        } else {

            wp_send_json_success([
                'message' => __('Plugin already installed and up to date', 'gsheetconnector-gravity-forms')
            ]);
        }
    }

        // ==============================
        // 📦 Not Installed → Install
        // ==============================
    $result = $upgrader->install($download_url);

    if (is_wp_error($result)) {
        wp_send_json_error([
            'message' => __('Installation failed: ', 'gsheetconnector-gravity-forms') . $result->get_error_message()
        ]);
    }

    wp_send_json_success([
        'message' => __('Plugin installed successfully', 'gsheetconnector-gravity-forms')
    ]);
}

/**
 * Activate plugin via AJAX (Free version handler).
 *
 * Handles:
 * - Nonce verification
 * - Permission check
 * - Validates plugin slug
 * - Checks if already active
 * - Activates plugin
 * - Returns JSON response
 *
 * @since 1.0.0
 * @return void
 */
public function gscgff_activate_plugin()
{

        // 🔐 Verify nonce
    if (! check_ajax_referer('gscgff-ajax-nonce', 'security', false)) {
        wp_send_json_error(array(
            'message' => __('Invalid security token', 'gsheetconnector-gravity-forms')
        ));
    }

        // 🔐 Permission check
    if (! current_user_can('activate_plugins')) {
        wp_send_json_error(array(
            'message' => __('You do not have permission to activate plugin', 'gsheetconnector-gravity-forms')
        ));
    }

        // 🔎 Check plugin slug
    if (empty($_POST['plugin_slug'])) {
        wp_send_json_error(array(
            'message' => __('Plugin slug is missing', 'gsheetconnector-gravity-forms')
        ));
    }

    $plugin_slug = sanitize_text_field(wp_unslash($_POST['plugin_slug']));

        // Load required file
    if (! function_exists('activate_plugin')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

        // ✅ Check if already active
    if (is_plugin_active($plugin_slug)) {
        wp_send_json_success(array(
            'message' => __('Plugin is already activated', 'gsheetconnector-gravity-forms')
        ));
    }

        // 🚀 Activate plugin
    $result = activate_plugin($plugin_slug);

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ));
    }

    wp_send_json_success(array(
        'message' => __('Plugin activated successfully', 'gsheetconnector-gravity-forms')
    ));
}

/**
 * Clear WordPress debug log file directly.
 *
 * Handles:
 * - Nonce verification
 * - Opens debug.log file in write mode
 * - Clears all contents
 * - Returns JSON success
 *
 * @since 1.0.0
 * @return void
 */
public function gscgff_clear_log(){
  check_ajax_referer('gscgff-ajax-nonce', 'security');
  // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
  $handle = fopen(WP_CONTENT_DIR . '/debug.log', 'w');
  // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
  fclose($handle);
  wp_send_json_success();

}

/**
 * Handle AJAX request to dismiss the PRO notice.
 *
 * This function:
 * - Verifies the AJAX nonce for security.
 * - Sets a browser cookie to remember that the notice is dismissed.
 * - Cookie is valid for 7 days.
 * - Returns a JSON success or error response.
 *
 * @return void
 */
public function gsheet_dismiss_pro_notice() {

    /*$nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';*/
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

    if ( ! wp_verify_nonce($nonce, 'gf-ajax-nonce') ) {
        wp_send_json_error('Invalid nonce');
    }

    setcookie(
        'gsheet_pro_notice_dismissed',
        '1',
        time() + (7 * 24 * 60 * 60),
        COOKIEPATH,
        COOKIE_DOMAIN
    );

    wp_send_json_success();
}


public function gscgff_dismiss_notice_callback(){
     
     if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'gf-ajax-nonce')) {
      wp_send_json_error('Invalid nonce');
      }

      // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
      if (!isset($_POST['key'])) {
      wp_send_json_error('Missing key');
      }
      
$key = isset($_POST['key']) ? sanitize_text_field(wp_unslash($_POST['key'])) : '';

      // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
      $key = sanitize_text_field($_POST['key']);
      update_option('gscgff_notice_' . $key, 'dismissed');
      wp_send_json_success();
    }

public function gscgff_snooze_notice_callback()
   {
      /*if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'gf-ajax-nonce')) {*/
      if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'gf-ajax-nonce')) {
      wp_send_json_error('Invalid nonce');
      }
      if (!isset($_POST['key'])) {
      wp_send_json_error('Missing key');
      }
   
      $key = sanitize_text_field(wp_unslash($_POST['key']));
      update_option('gscgff_notice_' . $key . '_time', time());
      wp_send_json_success();
   }


}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$GFGS_Connector_Service = new GFGS_Connector_Service();