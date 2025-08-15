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

   }
   /**
    * Deactivate a plugin via AJAX request.
    * 
    * Security:
    * - Verifies nonce.
    * - Checks user capabilities.
    * - Validates and sanitizes plugin slug.
    * - Logs all failure conditions.
    */
   function gs_gravity_deactivate_plugin()
   {
      // Nonce verification
      check_ajax_referer('gs_gravity_ajax_nonce', 'security');

      // Check if user has permission to deactivate plugins
      if (!current_user_can('activate_plugins')) {
         GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error: User lacks permission to deactivate plugins.');
         wp_send_json_error('You do not have permission to deactivate plugins.');
      }

      // Validate presence of plugin_slug
      if (!isset($_POST['plugin_slug'])) {
         GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error: Plugin slug missing.');
         wp_send_json_error('Plugin slug is missing.');
      }

      $plugin_slug = sanitize_text_field(wp_unslash($_POST['plugin_slug'])); // ✅ Correct
      // Validate plugin slug
      if (empty($plugin_slug)) {
         GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error: Plugin slug is empty.');
         wp_send_json_error('Invalid plugin.');
      }

      // Check if the plugin file exists
      if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_slug)) {
         GravityForms_GsFree_Connector_Utility::gfgs_debug_log("Error: Plugin file does not exist - " . $plugin_slug);
         wp_send_json_error('Plugin not found.');
      }

      // Attempt to deactivate the plugin
      deactivate_plugins($plugin_slug);

      // Confirm plugin was deactivated
      if (is_plugin_active($plugin_slug)) {
         GravityForms_GsFree_Connector_Utility::gfgs_debug_log("Error: Plugin deactivation failed - " . $plugin_slug);
         wp_send_json_error('Failed to deactivate plugin.');
      }

      // Success
      wp_send_json_success('Plugin deactivated successfully.');
   }


   /**
    * Install or upgrade a plugin via AJAX request.
    * 
    * Handles:
    * - Plugin installation from remote zip
    * - Upgrade if already installed
    * - Error handling and logging
    */
   function gs_gravity_install_plugin()
   {
      // Nonce verification
      check_ajax_referer('gs_gravity_ajax_nonce', 'security');

      // Validate required fields
      if (!isset($_POST['plugin_slug'], $_POST['download_url'])) {
         GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error: Missing plugin_slug or download_url.');
         wp_send_json_error(['message' => 'Missing required parameters.']);
      }

      $plugin_slug = sanitize_text_field(wp_unslash($_POST['plugin_slug']));
      $download_url = esc_url_raw(wp_unslash($_POST['download_url']));

      // Validate input values
      if (empty($plugin_slug) || empty($download_url)) {
         GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error: Plugin slug or download URL is empty.');
         wp_send_json_error(['message' => 'Invalid plugin data.']);
      }

      // Include necessary WordPress files
      include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
      include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
      include_once ABSPATH . 'wp-admin/includes/file.php';
      include_once ABSPATH . 'wp-admin/includes/update.php';

      $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());

      $installed_plugins = get_plugins();
      $plugin_path = '';

      // Find if plugin already exists
      foreach ($installed_plugins as $path => $details) {
         if (strpos($path, $plugin_slug . '/') === 0) {
            $plugin_path = $path;
            break;
         }
      }

      if ($plugin_path) {
         // Plugin is installed, check for update
         $update_plugins = get_site_transient('update_plugins');

         if (isset($update_plugins->response[$plugin_path])) {
            // Try upgrading the plugin
            $result = $upgrader->upgrade($plugin_path);

            if (is_wp_error($result)) {
               GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Upgrade failed: ' . $result->get_error_message());
               wp_send_json_error(['message' => 'Upgrade failed: ' . $result->get_error_message()]);
            }

            wp_send_json_success(['message' => 'Plugin upgraded successfully.']);
         } else {
            GravityForms_GsFree_Connector_Utility::gfgs_debug_log('No updates available for plugin: ' . $plugin_path);
            wp_send_json_error(['message' => 'No updates available for this plugin.']);
         }
      } else {
         // Plugin is not installed, install it
         $result = $upgrader->install($download_url);

         if (is_wp_error($result)) {
            GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Installation failed: ' . $result->get_error_message());
            wp_send_json_error(['message' => 'Installation failed: ' . $result->get_error_message()]);
         }

         wp_send_json_success(['message' => 'Plugin installed successfully.']);
      }
   }


   /**
    * Activate a plugin via AJAX request.
    * 
    * Security:
    * - Verifies nonce and user permission.
    * - Validates plugin slug.
    * - Logs and returns error if activation fails.
    */
   function gs_gravity_activate_plugin()
   {
      // Nonce verification
      check_ajax_referer('gs_gravity_ajax_nonce', 'security');

      // Check permission
      if (!current_user_can('activate_plugins')) {
         GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Permission denied for plugin activation.');
         wp_send_json_error(['message' => 'Permission denied.']);
      }

      // Check and sanitize plugin slug
      if (!isset($_POST['plugin_slug'])) {
         GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Missing plugin slug for activation.');
         wp_send_json_error(['message' => 'Missing plugin slug.']);
      }

      $plugin_slug = sanitize_text_field(wp_unslash($_POST['plugin_slug']));


      // Include required file
      include_once ABSPATH . 'wp-admin/includes/plugin.php';

      // Attempt plugin activation
      $activated = activate_plugin($plugin_slug);

      if (is_wp_error($activated)) {
         GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Activation failed: ' . $activated->get_error_message());
         wp_send_json_error(['message' => $activated->get_error_message()]);
      }

      wp_send_json_success(['message' => 'Plugin activated successfully.']);
   }

    /**
    * AJAX function - verify_code_integation
    * @since 1.0
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
         GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error during verification: ' . $e->getMessage());

         wp_send_json_error();
      }
   }




   /**
    * AJAX function - deactivate activation
    * @since 1.0
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
    * AJAX function - clear log file
    * @since 1.0
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
    * AJAX function - clear log file for system status tab
    * @since 1.0
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
    * Function - fetch contant form list that is connected with google sheet
    * @since 1.0
    */
   public function get_forms_connected_to_sheet()
   {
      global $wpdb;
      $table_name = $wpdb->base_prefix . 'gf_form';

      // Check if the Gravity Forms table exists
      $result = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
      $query = [];

      if ($result === $table_name) {
         // Fetch all forms ordered by ID
         $query = $wpdb->get_results(
            "SELECT id, title FROM {$wpdb->prefix}gf_form ORDER BY id"
         );
         // Alternatively: Join with postmeta table if needed
         // $query = $wpdb->get_results("SELECT * FROM {$wpdb->base_prefix}gf_form AS gf JOIN {$wpdb->base_prefix}postmeta AS pm ON gf.id = pm.post_id WHERE pm.meta_key = 'gfgs_settings'");
      }

      return $query;
   }


}
$GFGS_Connector_Service = new GFGS_Connector_Service();