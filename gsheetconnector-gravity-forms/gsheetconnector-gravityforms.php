<?php
/**
 * Plugin Name: GSheetConnector For Gravity Forms
 * Plugin URI: https://www.gsheetconnector.com/gravity-forms-google-sheet-connector
 * Description: Send your Gravityform  data to your Google Sheets spreadsheet.
 * Requires at least: 5.6
 * Requires PHP:7.4
 * Author: GSheetConnector
 * Author URI: https://www.gsheetconnector.com/
 * Version: 1.4.0
 * Text Domain: gsheetconnector-gravity-forms
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 */


/* Exit if accessed directly. */
if (!defined('ABSPATH')) {
 exit;
}

/* Prevent free and pro admin menu */
include_once ABSPATH . 'wp-admin/includes/plugin.php';
if (is_plugin_active('gsheetconnector-gravityforms-pro/gsheetconnector-gravityforms-pro.php')) {
  return;
}

/* Defined Global Variable for plugin activatio*/
global $activate_the_plugin;
$activate_the_plugin = false;

$plugin = plugin_basename(__FILE__);
$parent_plugins = 'gravityforms/gravityforms.php';


/**
 * Fixed multisite activation issue
 * @since 1.0.11
 */

$current_site_id = get_current_blog_id();

/* Check if Multisite and single site activated plugin code */
if ((is_multisite() && !empty($current_site_id))) {
 function get_activated_plugins_for_site($site_id)
 {
  /* Switch to the specific site */
  switch_to_blog($site_id);

  /* Get the list of activated plugins for the current site */
  $activated_plugins = get_option('active_plugins');

  /* Restore the current site */
  restore_current_blog();

  return $activated_plugins;
}

$active_plugins = get_activated_plugins_for_site($current_site_id);

if ((in_array($parent_plugins, $active_plugins))) {
  $activate_the_plugin = true;
}
}

/* Check if Multisite and network activated plugin code */
if (is_multisite()) {
 $active_plugins = get_site_option('active_sitewide_plugins');

 if ((array_key_exists($parent_plugins, $active_plugins))) {
  $activate_the_plugin = true;
}
}
/* Check if Singlesite activation of plugin code */ else {
 $active_plugins = get_option('active_plugins');

 if ((in_array($parent_plugins, $active_plugins))) {
  $activate_the_plugin = true;
}
}

if (!function_exists('is_plugin_active')) {
 include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (Gforms_Gsheet_Connector_Free_Init::gscgf_is_pugin_active('Gforms_Gsheet_Connector_Init')) {
 return;
}

/* Declare some global constants */
define('GRAVITY_GOOGLESHEET_VERSION', '1.4.0');
define('GRAVITY_GOOGLESHEET_DB_VERSION', '1.4.0');
define('GRAVITY_GOOGLESHEET_ROOT', dirname(__FILE__));
define('GRAVITY_GOOGLESHEET_URL', plugins_url('/', __FILE__));
define('GRAVITY_GOOGLESHEET_BASE_FILE', basename(dirname(__FILE__)) . '/gsheetconnector-gravity-forms.php');
define('GRAVITY_GOOGLESHEET_BASE_NAME', plugin_basename(__FILE__));
define('GRAVITY_GOOGLESHEET_API_URL', 'https://oauth.gsheetconnector.com/api-cred.php');
define('GRAVITY_GOOGLESHEET_PATH', plugin_dir_path(__FILE__)); //use for include files to other files

if ($activate_the_plugin) {
 /* Freemius  Start */
 if (!function_exists('gg_fs')) {
  /* Create a helper function for easy SDK access. */
  function gg_fs()
  {
   global $gg_fs;

   if (!isset($gg_fs)) {
    /* Activate multisite network integration. */
    if (!defined('WP_FS__PRODUCT_17696_MULTISITE')) {
     define('WP_FS__PRODUCT_17696_MULTISITE', true);
   }

   /* Include Freemius SDK. */
   require_once dirname(__FILE__) . '/lib/vendor/freemius/start.php';

   $gg_fs = fs_dynamic_init(array(
     'id' => '17696',
     'slug' => 'gsheetconnector-gravity-forms',
     'type' => 'plugin',
     'public_key' => 'pk_de0da0604d68aa61a14ce400551de',
     'is_premium' => false,
     'has_addons' => false,
     'has_paid_plans' => false,
     'is_org_compliant' => true,
     'menu' => array(
      'slug' => 'gsheetconnector-gravity-forms',
      'first-path' => 'admin.php?page=gf_googlesheet',
      'account' => false,
      'support' => false,
    ),
   ));
 }

 return $gg_fs;
}

/* Init Freemius. */
gg_fs();
/* Signal that SDK was initiated. */
do_action('gg_fs_loaded');
}
}
/* Freemius End */

class Gforms_Gsheet_Connector_Free_Init
{

 public function __construct()
 {
  if (!is_plugin_active('gsheetconnector-gravity-forms-pro/gsheetconnector-gravity-forms-pro.php')) {
   if (!class_exists('GravityForms_GsFree_Connector_Utility')) {
    include(GRAVITY_GOOGLESHEET_ROOT . '/includes/class-gravityforms-utility.php');
  }

  /* run on activation of plugin */
  register_activation_hook(__FILE__, array($this, 'gsheetconnector_gform_activate'));

  /* run on deactivation of plugin */
  register_deactivation_hook(__FILE__, array($this, 'gsheetconnector_gform_deactivate'));

  /* run on uninstall */
  register_uninstall_hook(__FILE__, array('Gforms_Gsheet_Connector_Free_Init', 'gsheetconnector_gform_uninstall'));

  /* validate is Gravityforms plugin exist */
  add_action('admin_init', array($this, 'validate_parent_plugin_exists'));
  add_action('admin_init', array($this, 'run_on_upgrade'));

  /* register admin menu under "Forms" > "Entries" */
  add_action('gform_addon_navigation', array($this, 'get_parent_menu'), 10, 1);

  /* load the js and css files */
  add_action('init', array($this, 'load_css_and_js_files'));

  /* load the classes */
  add_action('init', array($this, 'load_all_classes'));

  /*  Setting option */
  add_filter('plugin_action_links_' . GRAVITY_GOOGLESHEET_BASE_FILE, array($this, 'grvt_connector_pro_plugin_action_links'));

  /** For using Row Meta */
  add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);

  /* For Dashboard Setup */
  add_action('wp_dashboard_setup', array($this, 'add_gf_connector_summary_widget'));
}
}



/**
* Add function to check plugins is Activate or not
* @param string $class of plugins main class .
* @return true/false    * 
* @since 2.0.2
*/
public static function gscgf_is_pugin_active($class)
{
  if (class_exists($class)) {
   return true;
 }
 return false;
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
    'docs' => '<a href="https://support.gsheetconnector.com/kb-category/gravity-forms-gsheetconnector" target="_blank" aria-label="' . esc_attr(esc_html__('View Documentation', 'gsheetconnector-gravity-forms')) . '" target="_blank">' . esc_html__('Docs', 'gsheetconnector-gravity-forms') . '</a>',
    'ideo' => '<a href="https://www.gsheetconnector.com/support" aria-label="' . esc_attr(esc_html__('Get Support', 'gsheetconnector-gravity-forms')) . '" target="_blank">' . esc_html__('Support', 'gsheetconnector-gravity-forms') . '</a>',
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
   $this->create_errorlog_table_in_database();


   /** Schedule cleanup debug logs event */
   if (!get_option('gscgfp_cleanup_old_logs_cron')) {
    update_option('gscgfp_cleanup_old_logs_cron', current_time('timestamp'));
  }

  if (function_exists('is_multisite') && is_multisite()) {
    /*  check if it is a network activation - if so, run the activation function for each blog id */
    if ($network_wide) {
     /* Get all blog ids */
     $blogids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");
     foreach ($blogids as $blog_id) {
      switch_to_blog($blog_id);
      $this->run_for_site();
      $this->create_errorlog_table_in_database();

      restore_current_blog();
    }
    return;
  }
}
} catch (Exception $e) {
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error during plugin activation: ' . $e->getMessage());
}

/* for non-network sites only */
$this->run_for_site();

}

/**
 * Handle plugin deactivation process.
 *
 * This function is triggered when the plugin is deactivated.
 * It can be used to clean up temporary data, clear scheduled events,
 * or perform any required deactivation tasks.
 *
 * @param bool $network_wide Whether the plugin is deactivated network-wide (Multisite).
 * @return void
 */
public function gsheetconnector_gform_deactivate($network_wide)
{
  try {
   /* Deactivation logic */
 } catch (Exception $e) {
   GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error during plugin deactivation: ' . $e->getMessage());
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
    /* Get all blog ids; foreach of them call the uninstall procedure */
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");

    /* Get all blog ids; foreach them and call the install procedure on each of them if the plugin table is found */
    foreach ($blog_ids as $blog_id) {
     switch_to_blog($blog_id);
     Gforms_Gsheet_Connector_Free_Init::delete_for_site();
     restore_current_blog();
   }
   return;
 }
 Gforms_Gsheet_Connector_Free_Init::delete_for_site();
} catch (Exception $e) {
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error during plugin uninstallation: ' . $e->getMessage());
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
     /*  Do not sanitize it because we are destroying the variables from URL */
     unset($_GET['activate']);
   }
 }
} catch (Exception $e) {
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error during parent plugin validation: ' . $e->getMessage());
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
   $plugin_error = GravityForms_GsFree_Connector_Utility::instance()->admin_notice(array(
    'type' => 'error',
    'message' => esc_html__('Google Sheet Connector Gravityforms Add-on requires Gravityforms plugin to be installed and activated.', 'gsheetconnector-gravity-forms')
  ));
   echo wp_kses_post($plugin_error);
 } catch (Exception $e) {
   GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error during error notice display: ' . $e->getMessage());
 }
}

/**
 * Add Google Sheet parent menu to Gravity Forms menu.
 *
 * This function checks the current user's role and determines
 * whether they have permission to access the Google Sheet menu.
 * If allowed, it appends a new menu item to the Gravity Forms addon menus.
 *
 * @param array $addon_menus Existing addon menus.
 * @return array Modified addon menus with Google Sheet menu (if permitted).
 */
public function get_parent_menu($addon_menus)
{
  try {
   $current_role = GravityForms_GsFree_Connector_Utility::instance()->get_current_user_role();
   $gs_roles = get_option('gravityforms_gs_page_roles_setting');

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
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error during menu page registration: ' . $e->getMessage());
}
}

/**
 * Load Google Sheet settings page.
 *
 * This function is used as a callback for the Google Sheet menu.
 * It includes the settings page file where the UI and logic
 * for managing Google Sheet integration are defined.
 *
 * @return void
 */
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
public function add_js_files()
{
  if (! is_admin()) {
   return;
 }

 /*  Direct plugin page (top-level menu) */
 if (isset($_GET['page']) && $_GET['page'] === 'gf_googlesheet') {
   $this->enqueue_gsheetconnector_js();
   return;
 }

      // Gravity Forms → Form Settings → Google Sheet Connector
 if (
   isset($_GET['page'], $_GET['view'], $_GET['subview']) &&
   $_GET['page'] === 'gf_edit_forms' &&
   $_GET['view'] === 'settings' &&
   $_GET['subview'] === 'gsheetconnector-gravity-forms'
 ) {
   $this->enqueue_gsheetconnector_js();
 }
}

/**
 * Enqueue all required JavaScript files for the plugin.
 *
 * This function loads core JS files used across the plugin,
 * and conditionally passes authentication-related data to JS
 * when in the admin area.
 *
 * @return void
 */
private function enqueue_gsheetconnector_js()
{

  if (is_admin() && (isset($_GET['page']) && (($_GET['page'] == 'gf_googlesheet') || ($_GET['page'] == 'gf_edit_forms')))) {
  }
  wp_enqueue_script(
   'gfgs-connector-js',
   GRAVITY_GOOGLESHEET_URL . 'assets/js/gfgs-connector.js',
   array('jquery'),
   GRAVITY_GOOGLESHEET_VERSION,
   true
 );

  wp_enqueue_script(
   'gravityforms-gs-connector-adds-js',
   GRAVITY_GOOGLESHEET_URL . 'assets/js/gravityforms-gs-connector-adds.js',
   array('jquery'),
   GRAVITY_GOOGLESHEET_VERSION,
   true
 );

  wp_enqueue_script(
   'systeminfo-gs-connector-adds-js',
   GRAVITY_GOOGLESHEET_URL . 'assets/js/system-debug.js',
   array('jquery'),
   GRAVITY_GOOGLESHEET_VERSION,
   true
 );

  if (is_admin()) {

   /* Authentication Logic */
   $selected_method = "";
   $authenticated = get_option('gfgs_token');
   $gscgff_gravityform_manual_setting = get_option('gs_gf_manual_setting');
   $gsc_gf_is_valid = get_option('gfgs_verify');


   if ((!empty($authenticated) && $gsc_gf_is_valid == 'valid' && $gscgff_gravityform_manual_setting == 0)) {
    $selected_method = esc_html__('Existing', 'gsheetconnector-gravityforms-pro');
  }


  /* Pass PHP variable to JS */
  wp_localize_script(
    'gfgs-connector-js',
    'gscExtensionVars',
    array(
     'selected_method' => $selected_method
   )
  );
}
}

/**
 * Enqueue CSS files for admin pages.
 *
 * This function conditionally loads plugin CSS files
 * only on relevant admin pages:
 * - Direct Google Sheet settings page
 * - Gravity Forms → Form Settings → Google Sheet Connector
 * - Gravity Forms edit form page (general styles)
 *
 * @return void
 */
public function add_css_files()
{
  if (! is_admin()) {
   return;
 }

 /*  Direct plugin page */
 if (isset($_GET['page']) && $_GET['page'] === 'gf_googlesheet') {
   $this->enqueue_gsheetconnector_css();
   return;
 }

 /*  Gravity Forms → Form Settings → Google Sheet Connector */
 if (
   isset($_GET['page'], $_GET['view'], $_GET['subview']) &&
   $_GET['page'] === 'gf_edit_forms' &&
   $_GET['view'] === 'settings' &&
   $_GET['subview'] === 'gsheetconnector-gravity-forms'
 ) {
   $this->enqueue_gsheetconnector_css();
 } 

 if (isset($_GET['page']) && $_GET['page'] === 'gf_edit_forms') {

  wp_enqueue_style(
   'gsc-garvity-css',
   GRAVITY_GOOGLESHEET_URL . 'assets/css/gs-gravityform.css',
   [],
   GRAVITY_GOOGLESHEET_VERSION,
   'all'
 );
}
}

/**
 * Enqueue all CSS files required for the plugin.
 *
 * This function loads all styling assets used across
 * the Google Sheet Connector plugin UI in admin.
 *
 * @return void
 */  
private function enqueue_gsheetconnector_css()
{
      /*wp_enqueue_style(
         'gfgs-connector-css',
         GRAVITY_GOOGLESHEET_URL . 'assets/css/gravity-form-style.css',
         array(),
         GRAVITY_GOOGLESHEET_VERSION
      );
      */

      wp_enqueue_style(
       'gfgs-connector-font-awesome',
       GRAVITY_GOOGLESHEET_URL . 'assets/css/fontawesome.css',
       array(),
       '6.5.0'
     );

      wp_enqueue_style(
       'gfgs-systeminfo',
       GRAVITY_GOOGLESHEET_URL . 'assets/css/system-debug.css',
       array(),
       GRAVITY_GOOGLESHEET_VERSION
     );
      wp_enqueue_style(
       'gsc-connector-header-free',
       GRAVITY_GOOGLESHEET_URL . 'assets/css/header.css',
       [],
       GRAVITY_GOOGLESHEET_VERSION,
       'all'
     );

      wp_enqueue_style(
       'gsc-connector-footer-free',
       GRAVITY_GOOGLESHEET_URL . 'assets/css/footer.css',
       [],
       GRAVITY_GOOGLESHEET_VERSION,
       'all'
     );

      wp_enqueue_style(
       'gsc-connector-extra-style-free',
       GRAVITY_GOOGLESHEET_URL . 'assets/css/extra-style.css',
       [],
       GRAVITY_GOOGLESHEET_VERSION,
       'all'
     );

      wp_enqueue_style(
       'gsc-connector-global-free',
       GRAVITY_GOOGLESHEET_URL . 'assets/css/global.css',
       [],
       GRAVITY_GOOGLESHEET_VERSION,
       'all'
     );

      wp_enqueue_style(
       'gsc-connector-responsive-free',
       GRAVITY_GOOGLESHEET_URL . 'assets/css/responsive.css',
       [],
       GRAVITY_GOOGLESHEET_VERSION,
       'all'
     );

      wp_enqueue_style(
       'gsc-connector-pro-feature',
       GRAVITY_GOOGLESHEET_URL . 'assets/css/pro-feature.css',
       [],
       GRAVITY_GOOGLESHEET_VERSION,
       'all'
     );
      wp_enqueue_style(
       'gsc-garvity-css',
       GRAVITY_GOOGLESHEET_URL . 'assets/css/gs-gravityform.css',
       [],
       GRAVITY_GOOGLESHEET_VERSION,
       'all'
     );

      wp_enqueue_style(
       'gsc-connector-font-awesome-free',
       GRAVITY_GOOGLESHEET_URL . 'assets/css/fontawesome.css',
       [],
       GRAVITY_GOOGLESHEET_VERSION,
       'all'
     );
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

  if ($plugin_options['version'] == '1.3.31') {
    $this->upgrade_database_1331();
  }

  /* update the version value */
  $google_sheet_info = array(
    'version' => GRAVITY_GOOGLESHEET_VERSION,

    'db_version' => GRAVITY_GOOGLESHEET_DB_VERSION
  );

  /* check if debug log file exists or not */
  $wplogFilePathToDelete = GRAVITY_GOOGLESHEET_PATH . "logs/log.txt";
  /*  Check if the log file exists before attempting to delete */
  if (file_exists($wplogFilePathToDelete)) {
    wp_delete_file($wplogFilePathToDelete);
  }
  update_site_option('gfgs_info', $google_sheet_info);
} catch (Exception $e) {
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error during plugin upgrade: ' . $e->getMessage());
}
}

/**
 * Upgrade database for version 1.8.
 *
 * This function handles database upgrade logic for both
 * single-site and multisite WordPress installations.
 * In multisite, it loops through all blogs and applies
 * the upgrade प्रक्रिया individually.
 *
 * @return void
 */
public function upgrade_database_18()
{
  global $wpdb;

  /*  look through each of the blogs and upgrade the DB */
  if (function_exists('is_multisite') && is_multisite()) {
   /* Get all blog ids; foreach them and call the uninstall procedure on each of them */
   $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");

   /* Get all blog ids; foreach them and call the install procedure on each of them if the plugin table is found */
   foreach ($blog_ids as $blog_id) {
    switch_to_blog($blog_id);
    $this->upgrade_helper_18();
    restore_current_blog();
  }
}
$this->upgrade_helper_18();
}

/**
 * Upgrade database for version 1.3.3.1.
 *
 * This function creates the error log table during plugin update.
 *
 * @return void
 */
public function upgrade_database_1331(){

 /** Create Error LOg Table during  plugin update */
 $this->create_errorlog_table_in_database();

}

/**
 * Helper function for version 1.8 upgrade.
 *
 * This function is responsible for saving API credentials
 * during the upgrade process.
 *
 * @return void
 */
public function upgrade_helper_18()
{
  /* Fetch and save the API credentails. */
  GravityForms_GsFree_Connector_Utility::instance()->save_api_credentials();

}

/**
 * Create debug log table during plugin activation/upgrade.
 *
 * This function creates a custom database table to store
 * error logs generated by the plugin. It ensures the table
 * is created only if it does not already exist.
 *
 * @return void
 */
public function create_errorlog_table_in_database()
{
  global $wpdb;

    // Define table name with WordPress prefix
  $table = $wpdb->prefix . 'gscgf_error_logs';

    // Get database charset and collation
  $charset = $wpdb->get_charset_collate();

    /**
     * Check if table already exists in database
     * If exists → skip creation
     */
    $table_exists = $wpdb->get_var(
      $wpdb->prepare("SHOW TABLES LIKE %s", $table)
    );

    if ($table_exists === $table) {
        // Table already exists, no need to create again
      return;
    }

    /**
     * SQL query to create error log table
     * Columns:
     * - id         : Primary key
     * - error_id   : Unique error identifier
     * - code       : Error code
     * - message    : Error message
     * - details    : Additional debug details
     * - created_at : Timestamp of error
     */
    $sql = "CREATE TABLE {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    error_id VARCHAR(191) NOT NULL,
    code INT NOT NULL,
    message TEXT NOT NULL,
    details LONGTEXT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY error_id (error_id),
    KEY code (code)
  ) {$charset};";

    // Include WordPress upgrade functions for dbDelta
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Execute table creation (safe for structure updates as well)
  dbDelta($sql);
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
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error during plugin activation: ' . $e->getMessage());
}
/* Fetch and save the API credentails. */
GravityForms_GsFree_Connector_Utility::instance()->save_api_credentials();
}

/**
 * Run site-specific setup tasks during plugin activation.
 *
 * This function initializes required plugin options with default values
 * if they do not already exist, and ensures required database tables
 * are created for the plugin.
 *
 * @return void
 */
private function run_for_site()
{
  try {
   if (!get_option('gfgs_access_code')) {
    update_option('gfgs_access_code', '');
  }
  if (!get_option('gfgs_verify')) {
    update_option('gfgs_verify', '');
  }
  if (!get_option('gfgs_token')) {
    update_option('gfgs_token', '');
  }
  if (!get_option('gfgs_feeds')) {
    update_option('gfgs_feeds', '');
  }
  if (!get_option('gravityforms_gs_page_roles_setting')) {
    update_option('gravityforms_gs_page_roles_setting', array());
  }
  if (!get_option('gravityforms_gs_tab_roles_setting')) {
    update_option('gravityforms_gs_tab_roles_setting', array());
  }

  /**Create Error Log Table in activation hook */
  $this->create_errorlog_table_in_database();
} catch (Exception $e) {
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error during site-specific tasks: ' . $e->getMessage());
}
}

/**
 * Delete site-specific data on plugin uninstall.
 *
 * This function removes plugin options and database tables
 * only if uninstall cleanup is enabled and the plugin is not active.
 *
 * @since 1.5
 * @return void
 */
private static function delete_for_site()
{
  if (!is_plugin_active('gsheetconnector-gravity-forms/gsheetconnector-gravityforms.php') || (!file_exists(plugin_dir_path(__DIR__) . 'gsheetconnector-gravity-forms/gsheetconnector-gravityforms.php'))) {

   $saved_value = get_option('gscgff_uninstall_setting');
   if ($saved_value === '1') {

    /** Delete gravity form free */
    delete_option('gfgs_feeds');
    delete_option('gfgs_token');
    delete_option('gfgs_verify');
    delete_option('gfgs_access_code');
    delete_option('gf_gs_debug_log_file');
    delete_option('gravityforms_gs_auth_expired_display_add_interval');
    delete_option('gravityforms_gs_auth_expired_close_add_interval');
    delete_option('gravityforms_gs_auth_expired_free');
    delete_option('gravityforms_manual_setting');
    delete_option('is_new_client_secret_gravityformsgsc');
    delete_option('gfgs_email_account');

    // delete api credentails
    delete_option('Gfgsc_api_creds');
    delete_site_option('Gfgsc_api_creds');

    /** Delete gravity form error log table  */
    global $wpdb;
    $wpdb->query(
     "DROP TABLE IF EXISTS {$wpdb->prefix}gscgf_error_logs"
   );
  }
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
 /* Handle any exceptions thrown during uninstallation */
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error during plugin uninstallation: ' . $e->getMessage());
}
}

/**
 * Display the last 100 lines from the WordPress debug log file.
 *
 * This function reads the debug.log file from the wp-content directory,
 * extracts the most recent 100 log entries, and displays them in a textarea.
 * If the file does not exist, it shows an appropriate message.
 *
 * @return void
 */
public function display_error_log()
{
  /* Define the path to your debug log file */
  $debug_log_file = WP_CONTENT_DIR . '/debug.log';

  /* Check if the debug log file exists */
  if (file_exists($debug_log_file)) {
   /* Read the contents of the debug log file */
   $debug_log_contents = file_get_contents($debug_log_file);

   /* Split the log content into an array of lines */
   $log_lines = explode("\n", $debug_log_contents);

   /* Get the last 100 lines in reversed order */
   $last_100_lines = array_slice(array_reverse($log_lines), 0, 100);

   /* Join the lines back together with line breaks */
   $last_100_log = implode("\n", $last_100_lines);

   /* Output the last 100 lines in reversed order in a textarea */
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
  /* We shouldn't encourage editing our plugin directly. */
  unset($links['edit']);

  /* Define the settings link. */
  $settings_link = '<a href="' . admin_url('admin.php?page=gf_googlesheet') . '">' . __('Settings', 'gsheetconnector-gravity-forms') . '</a>';

  /* Check if the Pro version of the plugin is installed and activated. */
  if (is_plugin_active('gsheetconnector-gravity-forms-pro/gsheetconnector-gravity-forms-pro.php')) {
   /* If Pro version is active, return links with the settings link. */
   return array_merge(array($settings_link), $links);
 }

 /* Define the "Get Pro" link. */
 $go_pro_text = esc_html__('Get GSheetConnector Gravity Pro', 'gsheetconnector-gravity-forms');
 $pro_link = sprintf(
   '<a href="%s" target="_blank" class="gsheetconnector-pro-link" style="color: green; font-weight: bold;">%s</a>',
   esc_url('https://www.gsheetconnector.com/gravity-forms-google-sheet-connector'),
   $go_pro_text
 );

 /* Merge both links and return. */
 return array_merge(array($settings_link, $pro_link), $links);
}


/**
* Add widget to the dashboard
* @since 1.0
*/
public function add_gf_connector_summary_widget()
{
  $img_url = esc_url(GRAVITY_GOOGLESHEET_URL . 'assets/image/gravityforms-gsc.svg');
  $title_text = esc_html__('GSheetConnector For Gravity Forms', 'gsheetconnector-gravity-forms');
  $title = "<img style='width:30px;margin-right: 10px;' src='{$img_url}'><span>{$title_text}</span>";

  wp_add_dashboard_widget(
   'gravity_dashboard',
   $title,
   array($this, 'gforms_gf_connector_summary_dashboard')
 );
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

  if (!class_exists('gscgf_error_logs')) {
    include(GRAVITY_GOOGLESHEET_PATH . 'includes/class-gsc-error-logs.php');
  }

  /* Include Library Files */
  require_once GRAVITY_GOOGLESHEET_ROOT . '/lib/vendor/autoload.php';

  include_once(GRAVITY_GOOGLESHEET_ROOT . '/lib/google-sheets.php');
  /* $init = new Gforms_Gsheet_Connector_Free_Init(); */
}

$init = new Gforms_Gsheet_Connector_Free_Init();

function gsheetconnector_gravityforms_pro_version_notice()
{
 $class = 'notice notice-success is-dismissible';
 $message = esc_html__("Deactivated GSheetConnector Gravity Forms (Pro Version) to activate GSheetConnector Gravity Forms Free.", "gsheetconnector-gravity-forms");
 /* $message = esc_html__("Heads up! <br><br> Your site already has Gravity Forms GSheetConnector PRO is activated. If you want to switch to Gravity Forms GSheetConnector Free version then, please first go to Plugins → Installed Plugins and deactivate Gravity Forms GSheetConnector PRO. Then, you can activate Gravity Forms GSheetConnector Free." , "gsheetconnector-gravity-forms");*/
 printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}