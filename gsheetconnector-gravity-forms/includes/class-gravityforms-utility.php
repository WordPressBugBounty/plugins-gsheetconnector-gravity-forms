<?php

/*
 * Utilities class for gravityforms google sheet connector
 * @since       1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
   exit;
}

/**
 * Utilities class - singleton class
 * @since 1.0
 */
class GravityForms_GsFree_Connector_Utility
{

   private function __construct()
   {
      // Do Nothing
   }

   /**
    * Get the singleton instance of the GravityForms_GsFree_Connector_Utility class
    *
    * @return singleton instance of GravityForms_GsFree_Connector_Utility
    */
   public static function instance()
   {

      static $instance = NULL;
      if (is_null($instance)) {
         $instance = new GravityForms_GsFree_Connector_Utility();
      }
      return $instance;
   }

   /**
    * Prints message (string or array) in the debug.log file
    *
    * @param mixed $message
    */
   public function logger($message)
   {
      if (WP_DEBUG === true) {
         if (is_array($message) || is_object($message)) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
            error_log(print_r($message, true));
         } else {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log($message);
         }
      }
   }

   /**
    * Display error or success message in the admin section
    *
    * @param array $data containing type and message
    * @return string with html containing the error message
    * 
    * @since 1.0 initial version
    */
   public function admin_notice($data = array())
   {
      // extract message and type from the $data array
      $message = isset($data['message']) ? $data['message'] : '';
      $message_type = isset($data['type']) ? $data['type'] : '';
      switch ($message_type) {
         case 'error':
            $admin_notice = '<div id="message" class="error notice is-dismissible">';
            break;
         case 'update':
            $admin_notice = '<div id="message" class="updated notice is-dismissible">';
            break;
         case 'update-nag':
            $admin_notice = '<div id="message" class="update-nag">';
            break;
         case 'auth-expired-notice':
            $admin_notice = '<div id="message" class="error notice gravityform-auth-expired-adds is-dismissible">';
            break;
         case 'upgrade':
            $admin_notice = '<div id="message" class="error notice gs-upgrade is-dismissible">';
            break;
         default:
            $message = __('There\'s something wrong with your code...', 'gsheetconnector-gravity-forms');
            $admin_notice = "<div id=\"message\" class=\"error\">\n";
            break;
      }
      $admin_notice .= "    <p>" . esc_html($message, 'gsheetconnector-gravity-forms') . "</p>\n";
      $admin_notice .= "</div>\n";
      return $admin_notice;
   }

   /**
    * Utility function to get the current user's role
    *
    * @since 1.0
    */
   public function get_current_user_role()
   {
      global $wp_roles;
      foreach ($wp_roles->role_names as $role => $name):
         if (current_user_can($role)) {
            return $role;
         }
      endforeach;
   }
   /**
    * Fetch and save Auto Integration API credentials
    *
    * @since 4.3.19
    */
   public function save_api_credentials()
   {
      // Create a nonce
      $nonce = wp_create_nonce('Gfgsc_api_creds');
      // Prepare parameters for the API call
      $params = array(
         'action' => 'get_data',
         'nonce' => $nonce,
         'plugin' => 'GRAVITYFORMSGSC',
         'method' => 'get',
      );

      // Add nonce and any other security parameters to the API request
      $api_url = add_query_arg($params, GRAVITY_GOOGLESHEET_API_URL);

      // Make the API call using wp_remote_get
      $response = wp_remote_get($api_url);

      // Check for errors
      if (is_wp_error($response)) {
         // Handle error
         self::gfgs_debug_log(__METHOD__ . ' Error: ' . $response->get_error_message());
      } else {
         // API call was successful, process the data
         $response = wp_remote_retrieve_body($response);

         $decoded_response = json_decode($response);

         if (isset($decoded_response->api_creds) && (!empty($decoded_response->api_creds))) {
            $api_creds = wp_parse_args($decoded_response->api_creds);
            if (is_multisite()) {
               // If it's a multisite, update the site option (network-wide)
               update_site_option('Gfgsc_api_creds', $api_creds);
            } else {
               // If it's not a multisite, update the regular option
               update_option('Gfgsc_api_creds', $api_creds);
            }
         }
      }
   }
   /**
    * Get all editable system roles.
    *
    * @return array Associative array of role slugs and names.
    */
   public function get_system_roles()
   {
      $participating_roles = array();
      $editable_roles = get_editable_roles();
      foreach ($editable_roles as $role => $details) {
         $participating_roles[$role] = $details['name'];
      }
      return $participating_roles;
   }

   /**
    * Write debug log entries to a file in the uploads directory.
    *
    * @param mixed $error Error message, object, or array to log.
    * @return void
    */
   public static function gfgs_debug_log($error)
   {
      /** Insert error login in table */
      if (class_exists('gscgf_error_logs')) {
         gscgf_error_logs::log_from_debug($error);
      }
   }

   /**
    * Get the current date and time formatted according to site settings.
    *
    * @return string|null Formatted date/time string or null on failure.
    */
   public static function getDefaultDate()
   {
      try {
         $timeZone = get_option('timezone_string');
         $dateFormat = get_option('date_format');
         $timeFormat = get_option('time_format');

         $date = new DateTime("now", new DateTimeZone($timeZone));
         $formattedDate = $date->format($dateFormat . ' ' . $timeFormat);

         return $formattedDate;
      } catch (Exception $e) {
         GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error in getDefaultDate: ' . $e->getMessage());
      }
   }
}