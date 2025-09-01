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
         wp_send_json_error( esc_html__( 'You do not have permission to deactivate plugins.', 'gsheetconnector-gravity-forms' ) );
      }
     // Safely fetch plugin slug.
      $plugin_slug = filter_input( INPUT_POST, 'plugin_slug', FILTER_SANITIZE_STRING );

       if ( empty( $plugin_slug ) ) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log( 'Error: Plugin slug missing or invalid.' );
        wp_send_json_error( esc_html__( 'Invalid plugin slug.', 'gsheetconnector-gravity-forms' ) );
       }

    // Validate plugin slug using core function.
    $validate = validate_plugin( $plugin_slug );
    if ( is_wp_error( $validate ) ) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log( 'Error: ' . $validate->get_error_message() );
        wp_send_json_error( esc_html( $validate->get_error_message() ) );
    }

      // Attempt to deactivate the plugin.
      deactivate_plugins( $plugin_slug );

       // Confirm deactivation.
    if ( is_plugin_active( $plugin_slug ) ) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log( "Error: Plugin deactivation failed - {$plugin_slug}" );
        wp_send_json_error( esc_html__( 'Failed to deactivate plugin.', 'gsheetconnector-gravity-forms' ) );
    }

    // Success.
    wp_send_json_success( esc_html__( 'Plugin deactivated successfully.', 'gsheetconnector-gravity-forms' ) );
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
      // 1) CSRF check
      check_ajax_referer('gs_gravity_ajax_nonce', 'security');

     // 2) AuthZ: only admins (or specific capability).
    if ( ! current_user_can( 'install_plugins' ) && ! current_user_can( 'update_plugins' ) ) {
        wp_send_json_error(
            array( 'message' => esc_html__( 'You do not have permission to install or update plugins.', 'gsheetconnector-gravity-forms' ) )
        );
    }

      // 3) Fetch and sanitize input.
    $plugin_slug  = filter_input( INPUT_POST, 'plugin_slug', FILTER_SANITIZE_STRING );
    $download_url = filter_input( INPUT_POST, 'download_url', FILTER_SANITIZE_URL );

      if ( empty( $plugin_slug ) || empty( $download_url ) ) {
        wp_send_json_error(
            array( 'message' => esc_html__( 'Missing or invalid parameters.', 'gsheetconnector-gravity-forms' ) )
        );
    }
      // 4) Validate URL format.
    if ( ! wp_http_validate_url( $download_url ) ) {
        wp_send_json_error(
            array( 'message' => esc_html__( 'Invalid download URL.', 'gsheetconnector-gravity-forms' ) )
        );
    }

     // 5) Restrict to WordPress.org downloads.
    $allowed_hosts = array( 'downloads.wordpress.org' );
    $host          = wp_parse_url( $download_url, PHP_URL_HOST );

    if ( ! in_array( $host, $allowed_hosts, true ) ) {
        wp_send_json_error(
            array( 'message' => esc_html__( 'Unsupported download source.', 'gsheetconnector-gravity-forms' ) )
        );
    }

 // 6) Ensure correct path prefix.
    if (
        stripos( $download_url, 'https://downloads.wordpress.org/plugin/' ) !== 0 &&
        stripos( $download_url, 'https://downloads.wordpress.org/releases/' ) !== 0
    ) {
        wp_send_json_error(
            array( 'message' => esc_html__( 'Unsupported download path.', 'gsheetconnector-gravity-forms' ) )
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

      if ( is_wp_error( $head ) ) {
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

      if ( is_wp_error( $head ) ) {
        wp_send_json_error(
            array( 'message' => esc_html__( 'Could not verify plugin package.', 'gsheetconnector-gravity-forms' ) )
        );
    }

    $ct = wp_remote_retrieve_header( $head, 'content-type' );
    if ( $ct && stripos( $ct, 'zip' ) === false && stripos( $ct, 'octet-stream' ) === false ) {
        wp_send_json_error(
            array( 'message' => esc_html__( 'Package is not a valid ZIP file.', 'gsheetconnector-gravity-forms' ) )
        );
    }

    $len = (int) wp_remote_retrieve_header( $head, 'content-length' );
    if ( $len && $len > 50 * 1024 * 1024 ) { // 50MB limit.
        wp_send_json_error(
            array( 'message' => esc_html__( 'Plugin package is too large.', 'gsheetconnector-gravity-forms' ) )
        );
    }

    // 9) Perform the install/upgrade.
    $upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
    $result   = $upgrader->install( $download_url );

       if ( is_wp_error( $result ) ) {
        wp_send_json_error(
            array( 'message' => esc_html__( 'Installation failed: ', 'gsheetconnector-gravity-forms' ) . esc_html( $result->get_error_message() ) )
        );
    }

    wp_send_json_success(
        array( 'message' => esc_html__( 'Plugin installed successfully.', 'gsheetconnector-gravity-forms' ) )
    );
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
      // 1) Nonce verification.
    check_ajax_referer( 'gs_gravity_ajax_nonce', 'security' );

    // 2) Permission check.
    if ( ! current_user_can( 'activate_plugins' ) ) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log( 'Permission denied for plugin activation.' );
        wp_send_json_error(
            array( 'message' => esc_html__( 'You do not have permission to activate plugins.', 'gsheetconnector-gravity-forms' ) )
        );
    }

      // 3) Fetch and sanitize plugin slug.
    $plugin_slug = filter_input( INPUT_POST, 'plugin_slug', FILTER_SANITIZE_STRING );


     if ( empty( $plugin_slug ) ) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log( 'Missing plugin slug for activation.' );
        wp_send_json_error(
            array( 'message' => esc_html__( 'Missing plugin slug.', 'gsheetconnector-gravity-forms' ) )
        );
    }
    

     // 4) Validate plugin.
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $all_plugins = get_plugins();

    if ( ! isset( $all_plugins[ $plugin_slug ] ) ) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log( 'Invalid plugin slug: ' . esc_html( $plugin_slug ) );
        wp_send_json_error(
            array( 'message' => esc_html__( 'Invalid plugin slug.', 'gsheetconnector-gravity-forms' ) )
        );
    }

    // 5) Load plugin functions.
    if ( ! function_exists( 'activate_plugin' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

      // 6) Attempt activation.
    $activated = activate_plugin( $plugin_slug );

    if ( is_wp_error( $activated ) ) {
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log(
            'Activation failed: ' . esc_html( $activated->get_error_message() )
        );
        wp_send_json_error(
            array( 'message' => esc_html__( 'Plugin activation failed.', 'gsheetconnector-gravity-forms' ) )
        );
    }


       // 7) Success response.
    wp_send_json_success(
        array( 'message' => esc_html__( 'Plugin activated successfully.', 'gsheetconnector-gravity-forms' ) )
    );
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