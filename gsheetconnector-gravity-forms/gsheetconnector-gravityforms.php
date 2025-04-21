<?php
/**
 * Plugin Name: GSheetConnector Gravity Forms
 * Plugin URI: https://wordpress.org/plugins/gsheetconnector-gravity-forms
 * Description: Send your Gravityform  data to your Google Sheets spreadsheet.
 * Author: GSheetConnector
 * Author URI: https://www.gsheetconnector.com/
 * Version: 1.3.20
 * Text Domain: gsheetconnector-gravityforms
 * Domain Path: languages
 */


// Exit if accessed directly.
if (!defined('ABSPATH')) {
   exit;
}


if (!function_exists('is_plugin_active')) {
   include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

//Condition If GSheetConnector Gravity PRO Activated
if ((is_plugin_active('gsheetconnector-gravityforms-pro/gsheetconnector-gravityforms-pro.php'))) {
   return;
}
//Condition If GSheetConnector Gravity PRO Activated

define('GRAVITY_GOOGLESHEET_VERSION', '1.3.20');
define('GRAVITY_GOOGLESHEET_DB_VERSION', '1.3.20');
define('GRAVITY_GOOGLESHEET_ROOT', dirname(__FILE__));
define('GRAVITY_GOOGLESHEET_URL', plugins_url('/', __FILE__));
define('GRAVITY_GOOGLESHEET_BASE_FILE', basename(dirname(__FILE__)) . '/gsheetconnector-gravityforms.php');
define('GRAVITY_GOOGLESHEET_BASE_NAME', plugin_basename(__FILE__));
define('GRAVITY_GOOGLESHEET_API_URL', 'https://oauth.gsheetconnector.com/api-cred.php');
define('GRAVITY_GOOGLESHEET_PATH', plugin_dir_path(__FILE__)); //use for include files to other files

load_plugin_textdomain('gsheetconnector-gravityforms', false, basename(dirname(__FILE__)) . '/languages');


if (!function_exists('gg_fs')) {
   // Create a helper function for easy SDK access.
   function gg_fs()
   {
      global $gg_fs;

      if (!isset($gg_fs)) {
         // Activate multisite network integration.
         if (!defined('WP_FS__PRODUCT_17696_MULTISITE')) {
            define('WP_FS__PRODUCT_17696_MULTISITE', true);
         }

         // Include Freemius SDK.
         require_once dirname(__FILE__) . '/freemius/start.php';

         $gg_fs = fs_dynamic_init(array(
            'id' => '17696',
            'slug' => 'gsheetconnector-gravityforms',
            'type' => 'plugin',
            'public_key' => 'pk_de0da0604d68aa61a14ce400551de',
            'is_premium' => false,
            'has_addons' => false,
            'has_paid_plans' => false,
            'menu' => array(
               'slug' => 'gsheetconnector-gravityforms',
               'first-path' => 'admin.php?page=gf_googlesheet',
               'account' => false,
               'support' => false,
            ),
         ));
      }

      return $gg_fs;
   }

   // Init Freemius.
   gg_fs();
   // Signal that SDK was initiated.
   do_action('gg_fs_loaded');
}

class Gforms_Gsheet_Connector_Free_Init
{

   public function __construct()
   {
      if (!is_plugin_active('gsheetconnector-gravityforms-pro/gsheetconnector-gravityforms-pro.php')) {
         if (!class_exists('GravityForms_Gs_Connector_Utility')) {
            include(GRAVITY_GOOGLESHEET_ROOT . '/includes/class-gravityforms-utility.php');
         }

         //run on activation of plugin
         register_activation_hook(__FILE__, array($this, 'gsheetconnector_gform_activate'));

         //run on deactivation of plugin
         register_deactivation_hook(__FILE__, array($this, 'gsheetconnector_gform_deactivate'));

         //run on uninstall
         register_uninstall_hook(__FILE__, array('Gforms_Gsheet_Connector_Free_Init', 'gsheetconnector_gform_uninstall'));

         // validate is Gravityforms plugin exist
         add_action('admin_init', array($this, 'validate_parent_plugin_exists'));
         add_action('admin_init', array($this, 'run_on_upgrade'));

         // register admin menu under "Forms" > "Entries"
         add_action('gform_addon_navigation', array($this, 'get_parent_menu'), 10, 1);

         // load the js and css files
         add_action('init', array($this, 'load_css_and_js_files'));

         // load the classes
         add_action('init', array($this, 'load_all_classes'));

         // Setting option
         add_filter('plugin_action_links_' . GRAVITY_GOOGLESHEET_BASE_FILE, array($this, 'grvt_connector_pro_plugin_action_links'));

         add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);

         add_action('wp_dashboard_setup', array($this, 'add_gf_connector_summary_widget'));

      }
   }



   /**
    * Plugin row meta.
    *
    * Adds row meta links to the plugin list table
    *
    * Fired by `plugin_row_meta` filter.
    *
    * @since 1.1.4
    * @access public
    *
    * @param array  $plugin_meta An array of the plugin's metadata, including
    *                            the version, author, author URI, and plugin URI.
    * @param string $plugin_file Path to the plugin file, relative to the plugins
    *                            directory.
    *
    * @return array An array of plugin row meta links.
    */
   public function plugin_row_meta($plugin_meta, $plugin_file)
   {
      if (GRAVITY_GOOGLESHEET_BASE_NAME === $plugin_file) {
         $row_meta = [
            'docs' => '<a href="https://support.gsheetconnector.com/kb-category/gravity-forms-gsheetconnector" target="_blank" aria-label="' . esc_attr(esc_html__('View Documentation', 'gsheetconnector-gravityforms')) . '" target="_blank">' . esc_html__('Docs', 'gsheetconnector-gravityforms') . '</a>',
            'ideo' => '<a href="https://www.gsheetconnector.com/support" aria-label="' . esc_attr(esc_html__('Get Support', 'gsheetconnector-gravityforms')) . '" target="_blank">' . esc_html__('Support', 'gsheetconnector-gravityforms') . '</a>',
         ];

         $plugin_meta = array_merge($plugin_meta, $row_meta);
      }

      return $plugin_meta;
   }

   /**
    * Do things on plugin activation
    * @since 1.0
    */
   public function gsheetconnector_gform_activate($network_wide)
   {
      try {
         global $wpdb;
         $this->run_on_activation();
         if (function_exists('is_multisite') && is_multisite()) {
            // check if it is a network activation - if so, run the activation function for each blog id
            if ($network_wide) {
               // Get all blog ids
               $blogids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");
               foreach ($blogids as $blog_id) {
                  switch_to_blog($blog_id);
                  $this->run_for_site();
                  restore_current_blog();
               }
               return;
            }
         }
      } catch (Exception $e) {
        GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         // Handle any exceptions thrown during activation
         error_log('Error during plugin activation: ' . $e->getMessage());
      }

      // for non-network sites only
      $this->run_for_site();
   }

   public function gsheetconnector_gform_deactivate($network_wide)
   {
      try {
         // Deactivation logic
      } catch (Exception $e) {
        GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         // Handle any exceptions thrown during deactivation
         error_log('Error during plugin deactivation: ' . $e->getMessage());
      }
   }

   /**
    *  Runs on plugin uninstall.
    *  a static class method or function can be used in an uninstall hook
    *
    *  @since 1.0
    */
   public static function gsheetconnector_gform_uninstall()
   {
      try {
         global $wpdb;
         Gforms_Gsheet_Connector_Free_Init::run_on_uninstall();
         if (function_exists('is_multisite') && is_multisite()) {
            //Get all blog ids; foreach of them call the uninstall procedure
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");

            //Get all blog ids; foreach them and call the install procedure on each of them if the plugin table is found
            foreach ($blog_ids as $blog_id) {
               switch_to_blog($blog_id);
               Gforms_Gsheet_Connector_Free_Init::delete_for_site();
               restore_current_blog();
            }
            return;
         }
         Gforms_Gsheet_Connector_Free_Init::delete_for_site();
      } catch (Exception $e) {
        GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         // Handle any exceptions thrown during uninstallation
         error_log('Error during plugin uninstallation: ' . $e->getMessage());
      }

   }

   /**
    * Validate parent Plugin gravityforms exist and activated
    * @access public
    * @since 1.0
    */
   public function validate_parent_plugin_exists()
   {
      try {
         $plugin = plugin_basename(__FILE__);
         if ((!is_plugin_active('gravityforms/gravityforms.php'))) {
            add_action('admin_notices', array($this, 'gform_missing_notice'));
            add_action('network_admin_notices', array($this, 'gform_missing_notice'));
            deactivate_plugins($plugin);
            if (isset($_GET['activate'])) {
               // Do not sanitize it because we are destroying the variables from URL
               unset($_GET['activate']);
            }
         }
      } catch (Exception $e) {
        GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         // Handle any exceptions thrown during validation
         error_log('Error during parent plugin validation: ' . $e->getMessage());
      }
   }

   /**
    * If Gravityforms plugin is not installed or activated then throw the error
    *
    * @access public
    * @return mixed error_message, an array containing the error message
    *
    * @since 1.0 initial version
    */
   public function gform_missing_notice()
   {
      try {
         $plugin_error = GravityForms_Gs_Connector_Utility::instance()->admin_notice(array(
            'type' => 'error',
            'message' => 'Gravityforms Add-on requires Gravityforms plugin to be installed and activated.'
         ));
         echo $plugin_error;
      } catch (Exception $e) {
        GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         // Handle any exceptions thrown during error notice display
         error_log('Error during error notice display: ' . $e->getMessage());
      }
   }

   public function get_parent_menu($addon_menus)
   {
      try {
         $current_role = GravityForms_Gs_Connector_Utility::instance()->get_current_user_role();
         $gs_roles = get_option('gfgs_page_roles_setting');
         if ((is_array($gs_roles) && array_key_exists($current_role, $gs_roles)) || $current_role === "administrator") {
            $addon_menus[] = array(
               'permission' => 'gravityforms_edit_forms',
               'label' => 'Google Sheet',
               'name' => 'gf_googlesheet',
               'callback' => array($this, 'add_googlesheet_menu')
            );
         }
         return $addon_menus;
      } catch (Exception $e) {
        GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         // Handle any exceptions thrown during menu page registration
         error_log('Error during menu page registration: ' . $e->getMessage());
      }

   }

   public function add_googlesheet_menu()
   {
      require_once plugin_dir_path(__FILE__) . 'includes/pages/google-sheet-settings.php';
   }

   /**
    * Create/Register menu items for the plugin.
    * @since 1.0
    */

   public function load_css_and_js_files()
   {
      add_action('admin_print_styles', array($this, 'add_css_files'));
      add_action('admin_print_scripts', array($this, 'add_js_files'));


   }




   /**
    * Function to load all required classes
    * @since 1.3.14
    */
   public function load_all_classes()
   {
      if (!class_exists('gravityforms_gs_Connector_Adds')) {
         include(GRAVITY_GOOGLESHEET_PATH . 'includes/class-gravityforms-adds.php');
      }
   }


   /**
    * enqueue CSS files
    * @since 1.0
    */
   public function add_css_files()
   {
      if (is_admin() && (isset($_GET['page']) && (($_GET['page'] == 'gf_googlesheet') || ($_GET['page'] == 'gf_edit_forms')))) {
         wp_enqueue_style('gfgs-connector-css', GRAVITY_GOOGLESHEET_URL . 'assets/css/gravity-form-style.css', GRAVITY_GOOGLESHEET_VERSION, true);
      }
   }

   public function add_js_files()
   {
      if (is_admin() && (isset($_GET['page']) && (($_GET['page'] == 'gf_googlesheet') || ($_GET['page'] == 'gf_edit_forms')))) {
         wp_enqueue_script('gfgs-connector-js', GRAVITY_GOOGLESHEET_URL . 'assets/js/gfgs-connector.js', GRAVITY_GOOGLESHEET_VERSION, true);
         wp_enqueue_script('gravityforms-gs-connector-adds-js', GRAVITY_GOOGLESHEET_URL . 'assets/js/gravityforms-gs-connector-adds.js', GRAVITY_GOOGLESHEET_VERSION, true);
      }
   }

   /**
    * called on upgrade. 
    * checks the current version and applies the necessary upgrades from that version onwards
    * @since 1.0
    */
   public function run_on_upgrade()
   {
      try {
         $plugin_options = get_site_option('gfgs_info');
         if ($plugin_options['version'] == '1.3.19') {
            $this->upgrade_database_18();
         }

         // update the version value
         $google_sheet_info = array(
            'version' => GRAVITY_GOOGLESHEET_VERSION,

            'db_version' => GRAVITY_GOOGLESHEET_DB_VERSION
         );

         // check if debug log file exists or not
         $wplogFilePathToDelete = GRAVITY_GOOGLESHEET_PATH . "logs/log.txt";
         // Check if the log file exists before attempting to delete
         if (file_exists($wplogFilePathToDelete)) {
            unlink($wplogFilePathToDelete);
         }

         update_site_option('gfgs_info', $google_sheet_info);
      } catch (Exception $e) {
        GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         // Handle any exceptions thrown during upgrade
         error_log('Error during plugin upgrade: ' . $e->getMessage());
      }
   }
   public function upgrade_database_18()
   {
      global $wpdb;

      // look through each of the blogs and upgrade the DB
      if (function_exists('is_multisite') && is_multisite()) {
         // Get all blog ids; foreach them and call the uninstall procedure on each of them
         $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");

         // Get all blog ids; foreach them and call the install procedure on each of them if the plugin table is found
         foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            $this->upgrade_helper_18();
            restore_current_blog();
         }
      }
      $this->upgrade_helper_18();
   }

   public function upgrade_helper_18()
   {
      // Fetch and save the API credentails.
      GravityForms_Gs_Connector_Utility::instance()->save_api_credentials();
   }

   /**
    * Called on activation.
    * Creates the site_options (required for all the sites in a multi-site setup)
    * If the current version doesn't match the new version, runs the upgrade
    * @since 1.0
    */
   private function run_on_activation()
   {
      try {
         $plugin_options = get_site_option('gfgs_info');
         if (false === $plugin_options) {
            $google_sheet_info = array(
               'version' => GRAVITY_GOOGLESHEET_VERSION,
               'db_version' => GRAVITY_GOOGLESHEET_DB_VERSION
            );
            update_site_option('gfgs_info', $google_sheet_info);
         } else if (GRAVITY_GOOGLESHEET_DB_VERSION != $plugin_options['version']) {
            $this->run_on_upgrade();
         }
      } catch (Exception $e) {
        GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         // Handle any exceptions thrown during activation
         error_log('Error during plugin activation: ' . $e->getMessage());
      }
      // Fetch and save the API credentails.
      GravityForms_Gs_Connector_Utility::instance()->save_api_credentials();
   }

   private function run_for_site()
   {
      try {
         if (!get_option('gfgs_access_code')) {
            update_option('gfgs_access_code', '');
         }
         if (!get_option('gfgs_verify')) {
            update_option('gfgs_verify', 'invalid');
         }
         if (!get_option('gfgs_token')) {
            update_option('gfgs_token', '');
         }
         if (!get_option('gfgs_feeds')) {
            update_option('gfgs_feeds', '');
         }
      } catch (Exception $e) {
         GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         // Handle any exceptions thrown during site-specific tasks
         error_log('Error during site-specific tasks: ' . $e->getMessage());
      }
   }

   /**
    * Called on uninstall - deletes site specific options
    *
    * @since 1.5
    */
   private static function delete_for_site()
   {
      try {
         if (!is_plugin_active('gsheetconnector-gravityforms/gsheetconnector-gravityforms.php') || (!file_exists(plugin_dir_path(__DIR__) . 'gsheetconnector-gravityforms/gsheetconnector-gravityforms.php'))) {

            delete_option('gfgs_feeds');
            delete_option('gfgs_access_code');
            delete_option('gfgs_verify');
            delete_option('gfgs_token');
            delete_option('gfgs_feeds');
            delete_post_meta_by_key('gravity_form_fields');
            delete_post_meta_by_key('gfgs_settings');
         }
      } catch (Exception $e) {
         // Handle any exceptions thrown during deletion
        GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         error_log('Error during plugin deletion: ' . $e->getMessage());
      }
   }

   /**
    * Called on uninstall - deletes site_options
    * @since 1.0
    */
   private static function run_on_uninstall()
   {
      try {
         if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN'))
            exit();

         delete_site_option('gfgs_info');
      } catch (Exception $e) {
         // Handle any exceptions thrown during uninstallation
         GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         error_log('Error during plugin uninstallation: ' . $e->getMessage());
      }
   }

   /**
    * Build System Information String
    * @global object $wpdb
    * @return string
    * @since 1.2
    */
   public function get_gfforms_system_info()
   {

      global $wpdb;

      // Get WordPress version
      $wp_version = get_bloginfo('version');

      // Get theme info
      $theme_data = wp_get_theme();
      $theme_name_version = $theme_data->get('Name') . ' ' . $theme_data->get('Version');
      $parent_theme = $theme_data->get('Template');

      if (!empty($parent_theme)) {
         $parent_theme_data = wp_get_theme($parent_theme);
         $parent_theme_name_version = $parent_theme_data->get('Name') . ' ' . $parent_theme_data->get('Version');
      } else {
         $parent_theme_name_version = 'N/A';
      }


      // Check plugin version and subscription plan
      $plugin_version = defined('GRAVITY_GOOGLESHEET_VERSION') ? GRAVITY_GOOGLESHEET_VERSION : 'N/A';
      $subscription_plan = 'FREE';

      // Check Google Account Authentication
      // $api_token = get_option('gs_token');
      // $google_sheet = new CF7GSC_googlesheet_PRO();
      // $email_account = $google_sheet->gsheet_print_google_account_email();

      $api_token_auto = get_option('gfgs_token');

      if (!empty($api_token_auto)) {
         // The user is authenticated through the auto method
         $google_sheet_auto = new GFGSC_googlesheet();
         $email_account_auto = $google_sheet_auto->gsheet_print_google_account_email();
         $connected_email = !empty($email_account_auto) ? esc_html($email_account_auto) : 'Not Auth';
         $email_class = 'connected-email-auth'; // CSS class for authenticated user
      } else {
         // Auto authentication is the only method available
         $connected_email = 'Not Auth';
         $email_class = 'connected-email-not-auth'; // CSS class for unauthenticated user
      }

      // Check Google Permission
      $gs_verify_status = get_option('gfgs_verify');
      $search_permission = ($gs_verify_status === 'valid') ? 'Given' : 'Not Given';

      // Initialize $gscpclass
      $gscpclass = '';

      // Create the system info HTML
      $system_info = '<div class="system-statuswc">';
      $system_info .= '<h4><button id="show-info-button" class="info-button">GSheetConnector<span class="dashicons dashicons-arrow-down"></span></h4>';
      $system_info .= '<div id="info-container" class="info-content" style="display:none;">';
      $system_info .= '<h3>GSheetConnector</h3>';
      $system_info .= '<table>';
      $system_info .= '<tr><td>Plugin Version</td><td>' . esc_html($plugin_version) . '</td></tr>';
      $system_info .= '<tr><td>Plugin Subscription Plan</td><td>' . esc_html($subscription_plan) . '</td></tr>';
      $system_info .= '<tr><td>Connected Email Account</td><td class="' . $email_class . '">' . $connected_email . '</td></tr>';

      if ($search_permission == "Given") {
         $gscpclass = 'gscpermission-given';
      } else {
         $gscpclass = 'gscpermission-notgiven';
      }

      $system_info .= '<tr><td>Google Drive Permission</td><td class="' . $gscpclass . '">' . esc_html($search_permission) . '</td></tr>';
      $system_info .= '<tr><td>Google Sheet Permission</td><td class="' . $gscpclass . '">' . esc_html($search_permission) . '</td></tr>';





      //  $system_info .= '<tr><td>Google Drive Permission</td><td>' . esc_html($search_permission) . '</td></tr>';
      //  $system_info .= '<tr><td>Google Sheet Permission</td><td>' . esc_html($search_permission) . '</td></tr>';
      $system_info .= '</table>';
      $system_info .= '</div>';
      // Add WordPress info
      // Create a button for WordPress info
      $system_info .= '<h2><button id="show-wordpress-info-button" class="info-button">WordPress Info<span class="dashicons dashicons-arrow-down"></span></h2>';
      $system_info .= '<div id="wordpress-info-container" class="info-content" style="display:none;">';
      $system_info .= '<h3>WordPress Info</h3>';
      $system_info .= '<table>';
      $system_info .= '<tr><td>Version</td><td>' . get_bloginfo('version') . '</td></tr>';
      $system_info .= '<tr><td>Site Language</td><td>' . get_bloginfo('language') . '</td></tr>';
      $system_info .= '<tr><td>Debug Mode</td><td>' . (WP_DEBUG ? 'Enabled' : 'Disabled') . '</td></tr>';
      $system_info .= '<tr><td>Home URL</td><td>' . get_home_url() . '</td></tr>';
      $system_info .= '<tr><td>Site URL</td><td>' . get_site_url() . '</td></tr>';
      $system_info .= '<tr><td>Permalink structure</td><td>' . get_option('permalink_structure') . '</td></tr>';
      $system_info .= '<tr><td>Is this site using HTTPS?</td><td>' . (is_ssl() ? 'Yes' : 'No') . '</td></tr>';
      $system_info .= '<tr><td>Is this a multisite?</td><td>' . (is_multisite() ? 'Yes' : 'No') . '</td></tr>';
      $system_info .= '<tr><td>Can anyone register on this site?</td><td>' . (get_option('users_can_register') ? 'Yes' : 'No') . '</td></tr>';
      $system_info .= '<tr><td>Is this site discouraging search engines?</td><td>' . (get_option('blog_public') ? 'No' : 'Yes') . '</td></tr>';
      $system_info .= '<tr><td>Default comment status</td><td>' . get_option('default_comment_status') . '</td></tr>';

      $server_ip = $_SERVER['REMOTE_ADDR'];
      if ($server_ip == '127.0.0.1' || $server_ip == '::1') {
         $environment_type = 'localhost';
      } else {
         $environment_type = 'production';
      }
      $system_info .= '<tr><td>Environment type</td><td>' . esc_html($environment_type) . '</td></tr>';

      $user_count = count_users();
      $total_users = $user_count['total_users'];
      $system_info .= '<tr><td>User Count</td><td>' . esc_html($total_users) . '</td></tr>';

      $system_info .= '<tr><td>Communication with WordPress.org</td><td>' . (get_option('blog_publicize') ? 'Yes' : 'No') . '</td></tr>';
      $system_info .= '</table>';
      $system_info .= '</div>';

      // info about active theme
      $active_theme = wp_get_theme();

      $system_info .= '<h2><button id="show-active-info-button" class="info-button">Active Theme<span class="dashicons dashicons-arrow-down"></span></h2>';
      $system_info .= '<div id="active-info-container" class="info-content" style="display:none;">';
      $system_info .= '<h3>Active Theme</h3>';
      $system_info .= '<table>';
      $system_info .= '<tr><td>Name</td><td>' . $active_theme->get('Name') . '</td></tr>';
      $system_info .= '<tr><td>Version</td><td>' . $active_theme->get('Version') . '</td></tr>';
      $system_info .= '<tr><td>Author</td><td>' . $active_theme->get('Author') . '</td></tr>';
      $system_info .= '<tr><td>Author website</td><td>' . $active_theme->get('AuthorURI') . '</td></tr>';
      $system_info .= '<tr><td>Theme directory location</td><td>' . $active_theme->get_template_directory() . '</td></tr>';
      $system_info .= '</table>';
      $system_info .= '</div>';

      // Get a list of other plugins you want to check compatibility with
      $other_plugins = array(
         'plugin-folder/plugin-file.php', // Replace with the actual plugin slug
         // Add more plugins as needed
      );

      // Network Active Plugins
      if (is_multisite()) {
         $network_active_plugins = get_site_option('active_sitewide_plugins', array());
         if (!empty($network_active_plugins)) {
            $system_info .= '<h2><button id="show-netplug-info-button" class="info-button">Network Active plugins<span class="dashicons dashicons-arrow-down"></span></h2>';
            $system_info .= '<div id="netplug-info-container" class="info-content" style="display:none;">';
            $system_info .= '<h3>Network Active plugins</h3>';
            $system_info .= '<table>';
            foreach ($network_active_plugins as $plugin => $plugin_data) {
               $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
               $system_info .= '<tr><td>' . $plugin_data['Name'] . '</td><td>' . $plugin_data['Version'] . '</td></tr>';
            }
            // Add more network active plugin statuses here...
            $system_info .= '</table>';
            $system_info .= '</div>';
         }
      }
      // Active plugins
      $system_info .= '<h2><button id="show-acplug-info-button" class="info-button">Active plugins<span class="dashicons dashicons-arrow-down"></span></h2>';
      $system_info .= '<div id="acplug-info-container" class="info-content" style="display:none;">';
      $system_info .= '<h3>Active plugins</h3>';
      $system_info .= '<table>';

      // Retrieve all active plugins data
      $active_plugins_data = array();
      $active_plugins = get_option('active_plugins', array());
      foreach ($active_plugins as $plugin) {
         $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
         $active_plugins_data[$plugin] = array(
            'name' => $plugin_data['Name'],
            'version' => $plugin_data['Version'],
            'count' => 0, // Initialize the count to zero
         );
      }

      // Count the number of active installations for each plugin
      $all_plugins = get_plugins();
      foreach ($all_plugins as $plugin_file => $plugin_data) {
         if (array_key_exists($plugin_file, $active_plugins_data)) {
            $active_plugins_data[$plugin_file]['count']++;
         }
      }

      // Sort plugins based on the number of active installations (descending order)
      uasort($active_plugins_data, function ($a, $b) {
         return $b['count'] - $a['count'];
      });

      // Display the top 5 most used plugins
      $counter = 0;
      foreach ($active_plugins_data as $plugin_data) {
         $system_info .= '<tr><td>' . $plugin_data['name'] . '</td><td>' . $plugin_data['version'] . '</td></tr>';
         // $counter++;
         // if ($counter >= 5) {
         //     break;
         // }
      }
      $system_info .= '</table>';
      $system_info .= '</div>';
      // Webserver Configuration
      $system_info .= '<h2><button id="show-server-info-button" class="info-button">Server<span class="dashicons dashicons-arrow-down"></span></h2>';
      $system_info .= '<div id="server-info-container" class="info-content" style="display:none;">';
      $system_info .= '<h3>Server</h3>';
      $system_info .= '<table>';
      $system_info .= '<p>The options shown below relate to your server setup. If changes are required, you may need your web host’s assistance.</p>';
      // Add Server information
      $system_info .= '<tr><td>Server Architecture</td><td>' . esc_html(php_uname('s')) . '</td></tr>';
      $system_info .= '<tr><td>Web Server</td><td>' . esc_html($_SERVER['SERVER_SOFTWARE']) . '</td></tr>';
      $system_info .= '<tr><td>PHP Version</td><td>' . esc_html(phpversion()) . '</td></tr>';
      $system_info .= '<tr><td>PHP SAPI</td><td>' . esc_html(php_sapi_name()) . '</td></tr>';
      $system_info .= '<tr><td>PHP Max Input Variables</td><td>' . esc_html(ini_get('max_input_vars')) . '</td></tr>';
      $system_info .= '<tr><td>PHP Time Limit</td><td>' . esc_html(ini_get('max_execution_time')) . ' seconds</td></tr>';
      $system_info .= '<tr><td>PHP Memory Limit</td><td>' . esc_html(ini_get('memory_limit')) . '</td></tr>';
      $system_info .= '<tr><td>Max Input Time</td><td>' . esc_html(ini_get('max_input_time')) . ' seconds</td></tr>';
      $system_info .= '<tr><td>Upload Max Filesize</td><td>' . esc_html(ini_get('upload_max_filesize')) . '</td></tr>';
      $system_info .= '<tr><td>PHP Post Max Size</td><td>' . esc_html(ini_get('post_max_size')) . '</td></tr>';
      $system_info .= '<tr><td>cURL Version</td><td>' . esc_html(curl_version()['version']) . '</td></tr>';
      $system_info .= '<tr><td>Is SUHOSIN Installed?</td><td>' . (extension_loaded('suhosin') ? 'Yes' : 'No') . '</td></tr>';
      $system_info .= '<tr><td>Is the Imagick Library Available?</td><td>' . (extension_loaded('imagick') ? 'Yes' : 'No') . '</td></tr>';
      $system_info .= '<tr><td>Are Pretty Permalinks Supported?</td><td>' . (get_option('permalink_structure') ? 'Yes' : 'No') . '</td></tr>';
      $system_info .= '<tr><td>.htaccess Rules</td><td>' . esc_html(is_writable('.htaccess') ? 'Writable' : 'Non Writable') . '</td></tr>';
      $system_info .= '<tr><td>Current Time</td><td>' . esc_html(current_time('mysql')) . '</td></tr>';
      $system_info .= '<tr><td>Current UTC Time</td><td>' . esc_html(current_time('mysql', true)) . '</td></tr>';
      $system_info .= '<tr><td>Current Server Time</td><td>' . esc_html(date('Y-m-d H:i:s')) . '</td></tr>';
      $system_info .= '</table>';
      $system_info .= '</div>';

      // Database Configuration
      $system_info .= '<h2><button id="show-database-info-button" class="info-button">Database<span class="dashicons dashicons-arrow-down"></span></h2>';
      $system_info .= '<div id="database-info-container" class="info-content" style="display:none;">';
      $system_info .= '<h3>Database</h3>';
      $system_info .= '<table>';
      $database_extension = 'mysqli';
      $database_server_version = $wpdb->get_var("SELECT VERSION() as version");
      $database_client_version = $wpdb->db_version();
      $database_username = DB_USER;
      $database_host = DB_HOST;
      $database_name = DB_NAME;
      $table_prefix = $wpdb->prefix;
      $database_charset = $wpdb->charset;
      $database_collation = $wpdb->collate;
      $max_allowed_packet_size = $wpdb->get_var("SHOW VARIABLES LIKE 'max_allowed_packet'");
      $max_connections_number = $wpdb->get_var("SHOW VARIABLES LIKE 'max_connections'");

      $system_info .= '<tr><td>Extension</td><td>' . esc_html($database_extension) . '</td></tr>';
      $system_info .= '<tr><td>Server Version</td><td>' . esc_html($database_server_version) . '</td></tr>';
      $system_info .= '<tr><td>Client Version</td><td>' . esc_html($database_client_version) . '</td></tr>';
      $system_info .= '<tr><td>Database Username</td><td>' . esc_html($database_username) . '</td></tr>';
      $system_info .= '<tr><td>Database Host</td><td>' . esc_html($database_host) . '</td></tr>';
      $system_info .= '<tr><td>Database Name</td><td>' . esc_html($database_name) . '</td></tr>';
      $system_info .= '<tr><td>Table Prefix</td><td>' . esc_html($table_prefix) . '</td></tr>';
      $system_info .= '<tr><td>Database Charset</td><td>' . esc_html($database_charset) . '</td></tr>';
      $system_info .= '<tr><td>Database Collation</td><td>' . esc_html($database_collation) . '</td></tr>';
      $system_info .= '<tr><td>Max Allowed Packet Size</td><td>' . esc_html($max_allowed_packet_size) . '</td></tr>';
      $system_info .= '<tr><td>Max Connections Number</td><td>' . esc_html($max_connections_number) . '</td></tr>';
      $system_info .= '</table>';
      $system_info .= '</div>';

      // wordpress constants
      $system_info .= '<h2><button id="show-wrcons-info-button" class="info-button">WordPress Constants<span class="dashicons dashicons-arrow-down"></span></h2>';
      $system_info .= '<div id="wrcons-info-container" class="info-content" style="display:none;">';
      $system_info .= '<h3>WordPress Constants</h3>';
      $system_info .= '<table>';
      // Add WordPress Constants information
      $system_info .= '<tr><td>ABSPATH</td><td>' . esc_html(ABSPATH) . '</td></tr>';
      $system_info .= '<tr><td>WP_HOME</td><td>' . esc_html(home_url()) . '</td></tr>';
      $system_info .= '<tr><td>WP_SITEURL</td><td>' . esc_html(site_url()) . '</td></tr>';
      $system_info .= '<tr><td>WP_CONTENT_DIR</td><td>' . esc_html(WP_CONTENT_DIR) . '</td></tr>';
      $system_info .= '<tr><td>WP_PLUGIN_DIR</td><td>' . esc_html(WP_PLUGIN_DIR) . '</td></tr>';
      $system_info .= '<tr><td>WP_MEMORY_LIMIT</td><td>' . esc_html(WP_MEMORY_LIMIT) . '</td></tr>';
      $system_info .= '<tr><td>WP_MAX_MEMORY_LIMIT</td><td>' . esc_html(WP_MAX_MEMORY_LIMIT) . '</td></tr>';
      $system_info .= '<tr><td>WP_DEBUG</td><td>' . (defined('WP_DEBUG') && WP_DEBUG ? 'Yes' : 'No') . '</td></tr>';
      $system_info .= '<tr><td>WP_DEBUG_DISPLAY</td><td>' . (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'Yes' : 'No') . '</td></tr>';
      $system_info .= '<tr><td>SCRIPT_DEBUG</td><td>' . (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'Yes' : 'No') . '</td></tr>';
      $system_info .= '<tr><td>WP_CACHE</td><td>' . (defined('WP_CACHE') && WP_CACHE ? 'Yes' : 'No') . '</td></tr>';
      $system_info .= '<tr><td>CONCATENATE_SCRIPTS</td><td>' . (defined('CONCATENATE_SCRIPTS') && CONCATENATE_SCRIPTS ? 'Yes' : 'No') . '</td></tr>';
      $system_info .= '<tr><td>COMPRESS_SCRIPTS</td><td>' . (defined('COMPRESS_SCRIPTS') && COMPRESS_SCRIPTS ? 'Yes' : 'No') . '</td></tr>';
      $system_info .= '<tr><td>COMPRESS_CSS</td><td>' . (defined('COMPRESS_CSS') && COMPRESS_CSS ? 'Yes' : 'No') . '</td></tr>';
      // Manually define the environment type (example values: 'development', 'staging', 'production')
      $environment_type = 'development';

      // Display the environment type
      $system_info .= '<tr><td>WP_ENVIRONMENT_TYPE</td><td>' . esc_html($environment_type) . '</td></tr>';

      $system_info .= '<tr><td>WP_DEVELOPMENT_MODE</td><td>' . (defined('WP_DEVELOPMENT_MODE') && WP_DEVELOPMENT_MODE ? 'Yes' : 'No') . '</td></tr>';
      $system_info .= '<tr><td>DB_CHARSET</td><td>' . esc_html(DB_CHARSET) . '</td></tr>';
      $system_info .= '<tr><td>DB_COLLATE</td><td>' . esc_html(DB_COLLATE) . '</td></tr>';

      $system_info .= '</table>';
      $system_info .= '</div>';

      // Filesystem Permission
      $system_info .= '<h2><button id="show-ftps-info-button" class="info-button">Filesystem Permission <span class="dashicons dashicons-arrow-down"></span></button></h2>';
      $system_info .= '<div id="ftps-info-container" class="info-content" style="display:none;">';
      $system_info .= '<h3>Filesystem Permission</h3>';
      $system_info .= '<p>Shows whether WordPress is able to write to the directories it needs access to.</p>';
      $system_info .= '<table>';
      // Filesystem Permission information
      $system_info .= '<tr><td>The main WordPress directory</td><td>' . esc_html(ABSPATH) . '</td><td>' . (is_writable(ABSPATH) ? 'Writable' : 'Not Writable') . '</td></tr>';
      $system_info .= '<tr><td>The wp-content directory</td><td>' . esc_html(WP_CONTENT_DIR) . '</td><td>' . (is_writable(WP_CONTENT_DIR) ? 'Writable' : 'Not Writable') . '</td></tr>';
      $system_info .= '<tr><td>The uploads directory</td><td>' . esc_html(wp_upload_dir()['basedir']) . '</td><td>' . (is_writable(wp_upload_dir()['basedir']) ? 'Writable' : 'Not Writable') . '</td></tr>';
      $system_info .= '<tr><td>The plugins directory</td><td>' . esc_html(WP_PLUGIN_DIR) . '</td><td>' . (is_writable(WP_PLUGIN_DIR) ? 'Writable' : 'Not Writable') . '</td></tr>';
      $system_info .= '<tr><td>The themes directory</td><td>' . esc_html(get_theme_root()) . '</td><td>' . (is_writable(get_theme_root()) ? 'Writable' : 'Not Writable') . '</td></tr>';

      $system_info .= '</table>';
      $system_info .= '</div>';

      return $system_info;
   }

   public function display_error_log()
   {
      // Define the path to your debug log file
      $debug_log_file = WP_CONTENT_DIR . '/debug.log';

      // Check if the debug log file exists
      if (file_exists($debug_log_file)) {
         // Read the contents of the debug log file
         $debug_log_contents = file_get_contents($debug_log_file);

         // Split the log content into an array of lines
         $log_lines = explode("\n", $debug_log_contents);

         // Get the last 100 lines in reversed order
         $last_100_lines = array_slice(array_reverse($log_lines), 0, 100);

         // Join the lines back together with line breaks
         $last_100_log = implode("\n", $last_100_lines);

         // Output the last 100 lines in reversed order in a textarea
         ?>
         <textarea class="errorlog" rows="20" cols="80"><?php echo esc_textarea($last_100_log); ?></textarea>
         <?php
      } else {
         echo 'Debug log file not found.';
      }
   }

   /**
    * Add custom link for the plugin beside activate/deactivate links
    * @param array $links Array of links to display below our plugin listing.
    * @return array Amended array of links.    * 
    * @since 1.5
    */
   public function grvt_connector_pro_plugin_action_links($links)
   {
      // We shouldn't encourage editing our plugin directly.
      unset($links['edit']);

      // Define the settings link.
      $settings_link = '<a href="' . admin_url('admin.php?page=gf_googlesheet') . '">' . __('Settings', 'gsheetconnector-gravityforms') . '</a>';

      // Check if the Pro version of the plugin is installed and activated.
      if (is_plugin_active('gsheetconnector-gravityforms-pro/gsheetconnector-gravityforms-pro.php')) {
         // If Pro version is active, return links with the settings link.
         return array_merge(array($settings_link), $links);
      }

      // Define the "Get Pro" link.
      $go_pro_text = esc_html__('Get GSheetConnector Gravity Pro', 'elementor');
      $pro_link = sprintf(
         '<a href="%s" target="_blank" class="gsheetconnector-pro-link" style="color: green; font-weight: bold;">%s</a>',
         esc_url('https://www.gsheetconnector.com/gravity-forms-google-sheet-connector'),
         $go_pro_text
      );

      // Merge both links and return.
      return array_merge(array($settings_link, $pro_link), $links);
   }


   /**
    * Add widget to the dashboard
    * @since 1.0
    */
   public function add_gf_connector_summary_widget()
   {
      wp_add_dashboard_widget('gravity_dashboard', __("<img style='width:30px;margin-right: 10px;' src='" . GRAVITY_GOOGLESHEET_URL . "assets/image/gravityforms-gsc.png'><span>Gravity Forms - GSheetConnector</span>", 'gsheetconnector-gravityforms'), array($this, 'gforms_gf_connector_summary_dashboard'));
   }
   /**
    * Display widget contents
    * @since 1.0
    */
   public function gforms_gf_connector_summary_dashboard()
   {
      include_once(GRAVITY_GOOGLESHEET_ROOT . '/includes/pages/gravity-dashboard-widget.php');
   }

}


add_action('gform_loaded', 'load_gsheetconnector_gforms_free_version', 40);
function load_gsheetconnector_gforms_free_version()
{
   /*
    * include utility classes
    */

   if (!class_exists('GFGS_Connector_Service')) {
      include(GRAVITY_GOOGLESHEET_ROOT . '/includes/class-gravityform-gs-service.php');
   }

   if (!class_exists('Gforms_Gsheet_Connector')) {
      include(GRAVITY_GOOGLESHEET_PATH . 'class-gf-gsheetgravityforms.php');
      GFAddOn::register('Gforms_Gsheet_Connector');
   }
   //Include Library Files
   require_once GRAVITY_GOOGLESHEET_ROOT . '/lib/vendor/autoload.php';

   include_once(GRAVITY_GOOGLESHEET_ROOT . '/lib/google-sheets.php');
   //$init = new Gforms_Gsheet_Connector_Free_Init();
}

$init = new Gforms_Gsheet_Connector_Free_Init();

function gsheetconnector_gravityforms_pro_version_notice()
{
   $class = 'notice notice-success is-dismissible';
   $message = esc_html__("Deactivated GSheetConnector Gravity Forms (Pro Version) to activate GSheetConnector Gravity Forms Free.", "gsheetconnector-gravityforms-pro");
   //$message = esc_html__("Heads up! <br><br> Your site already has Gravity Forms GSheetConnector PRO is activated. If you want to switch to Gravity Forms GSheetConnector Free version then, please first go to Plugins → Installed Plugins and deactivate Gravity Forms GSheetConnector PRO. Then, you can activate Gravity Forms GSheetConnector Free." , "gsheetconnector-gravityforms-pro");
   printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}