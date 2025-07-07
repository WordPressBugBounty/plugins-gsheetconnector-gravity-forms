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
class GravityForms_Gs_Connector_Utility
{

   private function __construct()
   {
      // Do Nothing
   }

   /**
    * Get the singleton instance of the GravityForms_Gs_Connector_Utility class
    *
    * @return singleton instance of GravityForms_Gs_Connector_Utility
    */
   public static function instance()
   {

      static $instance = NULL;
      if (is_null($instance)) {
         $instance = new GravityForms_Gs_Connector_Utility();
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
            error_log(print_r($message, true));
         } else {
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
      $message = isset($data['message']) ? $data['message'] : "";
      $message_type = isset($data['type']) ? $data['type'] : "";
      switch ($message_type) {
         case 'error':
            $admin_notice = '<div id="message" class="error notice is-dismissible">';
            break;
         case 'update':
            $admin_notice = '<div id="message" class="updated notice is-dismissible">';
            break;
         case 'auth-expired-notice':
            $admin_notice = '<div id="message" class="error notice gravityforms-gs-auth-expired-adds is-dismissible">';
            break;
         case 'update-nag':
            $admin_notice = '<div id="message" class="update-nag">';
            break;
         case 'renew':
            $admin_notice = '<div id="message" class="error notice gsgf-renew is-dismissible">';
            break;
         default:
            $message = __('There\'s something wrong with your code...', 'gsheetconnector-gravityforms');
            $admin_notice = "<div id=\"message\" class=\"error\">\n";
            break;
      }

      $admin_notice .= "    <p>" . __($message, 'gsheetconnector-gravityforms') . "</p>\n";
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

   public function gravityforms_gs_checkbox_roles_multi($setting_name, $selected_roles)
   {
      $selected_row = '';
      $checked = '';
      $roles = array();
      $system_roles = $this->get_system_roles();

      if (!empty($selected_roles)) {
         foreach ($selected_roles as $role => $display_name) {
            array_push($roles, $role);
         }
      }

      $selected_row .= "<label style='display: block;'> <input type='checkbox' class='gs-checkbox' disabled='disabled' checked='checked'/>";
      $selected_row .= __("Administrator", "gsheetconnector-gravityforms");
      $selected_row .= "</label>";

      foreach ($system_roles as $role => $display_name) {
         if ($role === "administrator") {
            continue;
         }
         if (!empty($roles) && is_array($roles) && in_array(esc_attr($role), $roles)) { // preselect specified role
            $checked = " ' checked='checked' ";
         } else {
            $checked = '';
         }

         $selected_row .= "<label style='display: block;'> <input type='checkbox' class='gs-checkbox'
          name='" . $setting_name . "' value='" . esc_attr($role) . "'" . $checked . "/>";
         $selected_row .= __($display_name, "gsheetconnector-gravityforms");
         $selected_row .= "</label>";
      }
      echo $selected_row;
   }

   public function get_system_roles()
   {
      $participating_roles = array();
      $editable_roles = get_editable_roles();
      foreach ($editable_roles as $role => $details) {
         $participating_roles[$role] = $details['name'];
      }
      return $participating_roles;
   }

   public static function gfgs_debug_log($error)
   {
      try {
         if (!is_dir(GRAVITY_GOOGLESHEET_PATH . 'logs')) {
            mkdir(GRAVITY_GOOGLESHEET_PATH . 'logs', 0755, true);
         }
      } catch (Exception $e) {

      }
      try {
         $gflogFilePathToDelete = GRAVITY_GOOGLESHEET_PATH . "logs/log.txt";
         // Check if the log file exists before attempting to delete
         if (file_exists($gflogFilePathToDelete)) {
            unlink($gflogFilePathToDelete);
         }

         // check if debug unique log file exists or not
         $gfexistDebugFile = get_option('gf_gs_debug_log_file');
         if (!empty($gfexistDebugFile) && file_exists($gfexistDebugFile)) {
            $gflog = fopen($gfexistDebugFile, 'a');
            if (is_array($error)) {
               fwrite($gflog, print_r(date_i18n('j F Y H:i:s', current_time('timestamp')) . " \t PHP " . phpversion(), TRUE));
               fwrite($gflog, print_r($error, TRUE));
            } else {
               $result = fwrite($gflog, print_r(date_i18n('j F Y H:i:s', current_time('timestamp')) . " \t PHP " . phpversion() . " \t $error \r\n", TRUE));
            }
            fclose($gflog);
         } else {
            // if unique log file not exists then create new file code
            // Your log content (you can customize this)
            $gf_unique_log_content = "Log created at " . date('Y-m-d H:i:s');
            // Create the log file
            $gflogfileName = 'log-' . uniqid() . '.txt';
            // Define the file path
            $gflogUniqueFile = GRAVITY_GOOGLESHEET_PATH . "logs/" . $gflogfileName;
            if (file_put_contents($gflogUniqueFile, $gf_unique_log_content)) {
               // save debug unique file in table
               update_option('gf_gs_debug_log_file', $gflogUniqueFile);
               // Success message
               // echo "Log file created successfully: " . $logUniqueFile;
               $gflog = fopen($gflogUniqueFile, 'a');
               if (is_array($error)) {
                  fwrite($gflog, print_r(date_i18n('j F Y H:i:s', current_time('timestamp')) . " \t PHP " . phpversion(), TRUE));
                  fwrite($gflog, print_r($error, TRUE));
               } else {
                  $result = fwrite($gflog, print_r(date_i18n('j F Y H:i:s', current_time('timestamp')) . " \t PHP " . phpversion() . " \t $error \r\n", TRUE));
               }
               fclose($gflog);

            } else {
               // Error message
               echo "Error - Not able to create Log File.";
            }
         }

      } catch (Exception $e) {

      }
   }

   public static function getDefaultDate()
   {
      try {
         $timeZone = get_option('timezone_string');
         $dateFormate = get_option('date_format');
         $timeFormate = get_option('time_format');
         $date = new DateTime("now", new DateTimeZone($timeZone));
         $Date = $date->format($dateFormate . ' ' . $timeFormate);
         return $Date;
      } catch (Exception $e) {

      }
   }

}