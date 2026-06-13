<?php

if (!defined('ABSPATH'))
 exit;

$file_path = (is_plugin_active('gsheetconnector-gravity-forms/gsheetconnector-gravity-forms.php')) ? plugin_dir_path(__FILE__) . 'vendor/autoload.php' : "";

if ($file_path != "") {
 include($file_path);
}

class Gfgscf_googlesheet
{

 private $token;
 private $spreadsheet;
 private $worksheet;
 private static $instance;

 public function __construct()
 {

 }

/**
 * Set the Google Client instance.
 *
 * Stores the provided Google_Client instance in a static property
 * for reuse across the class.
 *
 * @param Google_Client|null $instance Google Client instance.
 * @return void
 */
public static function setInstance(Google_Client $instance = null)
{
  self::$instance = $instance;
}

/**
 * Get the Google Client instance.
 *
 * Returns the previously set Google_Client instance.
 * Throws an exception if the instance is not initialized.
 *
 * @throws LogicException If the client instance is not set.
 * @return Google_Client
 */
public static function getInstance()
{
  if (is_null(self::$instance)) {
         // Throw exception if instance not initialized
   throw new LogicException("Invalid Client");
 }

 return self::$instance;
}

/**
 * Preauthorize Google Client using the provided OAuth access code.
 *
 * Retrieves stored API credentials, initializes the Google Client,
 * exchanges the access code for an access token, and stores it.
 *
 * @param string $access_code OAuth authorization code.
 * @return void
 */
public static function preauth($access_code)
{
  try {
         // Get stored Google API credentials
   if (is_multisite()) {
    $api_creds = get_site_option('Gfgsc_api_creds');
  } else {
    $api_creds = get_option('Gfgsc_api_creds');
  }

         // Ensure credentials exist
  if (empty($api_creds)) {
    GravityForms_GsFree_Connector_Utility::gfgs_debug_log('API credentials are missing in options.');
    return;
  }

         // Determine which client credentials to use (web or desktop)
  $newClientSecret = get_option('is_new_client_secret_gravityformsgsc');
  $clientId = ($newClientSecret == 1) ? $api_creds['client_id_web'] : $api_creds['client_id_desk'];
  $clientSecret = ($newClientSecret == 1) ? $api_creds['client_secret_web'] : $api_creds['client_secret_desk'];

         // Validate clientId and clientSecret
  if (empty($clientId) || empty($clientSecret)) {
    GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Client ID or Secret is missing.');
    return;
  }

         // Create and configure Google client
  $client = new Google_Client();
  $client->setClientId($clientId);
  $client->setClientSecret($clientSecret);
  $client->setRedirectUri('https://oauth.gsheetconnector.com');
  $client->setScopes([
    Google_Service_Sheets::SPREADSHEETS,
    Google_Service_Drive::DRIVE_METADATA_READONLY
  ]);
  $client->setAccessType('offline');

         // Fetch the access token using the provided code
  $tokenData = $client->fetchAccessTokenWithAuthCode($access_code);

         // Check for token errors
  if (isset($tokenData['error'])) {
    GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error fetching token: ' . $tokenData['error_description']);
    return;
  }

         // Store the token using custom token updater
  Gfgscf_googlesheet::updateToken($tokenData);

} catch (Exception $e) {
         // Log any unexpected exceptions
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Exception in preauth(): ' . $e->getMessage());
}
}

/**
 * Update and store the OAuth access token.
 *
 * Adds expiration time, validates required scopes,
 * and saves token data in WordPress options.
 *
 * @param array $tokenData Token data returned from Google OAuth.
 * @return void
 */
public static function updateToken($tokenData)
{
      // Set token expiration timestamp
  $tokenData['expire'] = time() + intval($tokenData['expires_in']);

  try {
         // Check if necessary scopes are granted
   if (isset($tokenData['scope'])) {
    $permission = explode(" ", $tokenData['scope']);
    if (
     in_array("https://www.googleapis.com/auth/drive.metadata.readonly", $permission) &&
     in_array("https://www.googleapis.com/auth/spreadsheets", $permission)
   ) {
     update_option('gfgs_verify', 'valid');
   } else {
     update_option('gfgs_verify', 'invalid-auth');

     if (class_exists('gscgf_error_logs')) {
    gscgf_error_logs::log_to_db(
        'Google_Auth_Permission_Error',                                   
        403,                                                               
        'Google Drive and Google Sheets permissions not granted',         
        [                                                                  
            'error_type'             => 'Missing Permissions',
            'message'                => 'User did not grant Google Drive and/or Google Sheets permissions during OAuth authentication',
            'granted_scopes'         => $tokenData['scope'] ?? '',
            'required_drive_scope'   => 'https://www.googleapis.com/auth/drive.file OR https://www.googleapis.com/auth/drive.metadata.readonly',
            'required_sheets_scope'  => 'https://www.googleapis.com/auth/spreadsheets',
        ]
    );
}
    }
 }

         // Encode and store the token data in WordPress options
 $tokenJson = json_encode($tokenData);
 update_option('gfgs_token', $tokenJson);
} catch (Exception $e) {
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log("Token write failed: " . $e->getMessage());
}
}

/**
 * Authenticate Google Client using stored refresh token.
 *
 * Refreshes the access token and updates stored token data.
 * Also sets the authenticated client instance.
 *
 * @throws LogicException If refresh token is missing or authentication fails.
 * @return void
 */
public function auth()
{
      // Retrieve stored token
  $tokenData = json_decode(get_option('gfgs_token'), true);

  if (!isset($tokenData['refresh_token']) || empty($tokenData['refresh_token'])) {
   throw new LogicException("Auth failed: Invalid or missing OAuth2 refresh token.");
 }

 try {
         // Get client credentials based on single/multisite and secret version
   $api_creds = is_multisite() ? get_site_option('Gfgsc_api_creds') : get_option('Gfgsc_api_creds');
   $newClientSecret = get_option('is_new_client_secret_gravityformsgsc');

   $clientId = ($newClientSecret == 1) ? $api_creds['client_id_web'] : $api_creds['client_id_desk'];
   $clientSecret = ($newClientSecret == 1) ? $api_creds['client_secret_web'] : $api_creds['client_secret_desk'];

         // Initialize Google Client
   $client = new Google_Client();
   $client->setClientId($clientId);
   $client->setClientSecret($clientSecret);
   $client->setAccessType('offline');
   $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
   $client->setScopes(Google_Service_Drive::DRIVE_METADATA_READONLY);

         // Refresh the access token using the refresh token
   $client->refreshToken($tokenData['refresh_token']);

         // Update token storage (will retain original refresh_token)
   Gfgscf_googlesheet::updateToken($tokenData);

         // Set static client instance
   self::setInstance($client);

 } catch (Exception $e) {
   GravityForms_GsFree_Connector_Utility::gfgs_debug_log("Auth error: " . $e->getMessage());
   throw new LogicException('Auth failed: ' . esc_html($e->getMessage()));
 }
}

 /**
 * Retrieve user-related spreadsheet data for debugging.
 *
 * Fetches available spreadsheets, worksheet tabs,
 * sets a specific sheet and tab, and lists rows.
 *
 * @return void
 */
 public function get_user_info()
 {
  $client = self::getInstance();
      // Retrieve the list of spreadsheets the user has access to
  $results = $this->get_spreadsheets();
      // Retrieve the list of worksheet tabs for a specific spreadsheet
  $spreadsheets = $this->get_worktabs('1mRuDMnZveDFQrmzHM9s5YkPA4F_dZkHJ1Gh81BvYB2k');
      // Set active spreadsheet and worksheet tab
  $this->setSpreadsheetId('1mRuDMnZveDFQrmzHM9s5YkPA4F_dZkHJ1Gh81BvYB2k');
  $this->setWorkTabId('Foglio1');
      // List all rows from the active sheet
  $worksheetTab = $this->list_rows();
}

/**
 * Set the active spreadsheet ID.
 *
 * Stores the provided Google Spreadsheet ID after sanitizing it.
 *
 * @param string $id Spreadsheet ID.
 * @return void
 */
public function setSpreadsheetId($id)
{
  $this->spreadsheet = sanitize_text_field($id);
}

/**
 * Get the active spreadsheet ID.
 *
 * Returns the currently stored spreadsheet ID.
 *
 * @return string
 */
public function getSpreadsheetId()
{
  return $this->spreadsheet;
}

/**
 * Set the active worksheet (tab) ID or title.
 *
 * Stores the worksheet identifier after sanitization.
 *
 * @param string $id Worksheet title or ID.
 * @return void
 */
public function setWorkTabId($id)
{
  $this->worksheet = sanitize_text_field($id);
}

/**
 * Get the active worksheet (tab) ID or title.
 *
 * Returns the currently stored worksheet identifier.
 *
 * @return string
 */
public function getWorkTabId()
{
  return $this->worksheet;
}

/**
 * Add a new row to the selected worksheet.
 *
 * Matches the sheet headers with provided data and appends
 * a new row at the next available position.
 *
 * @param array $data_value Associative array of data to insert.
 * @return void|null Returns null on failure.
 */
public function add_row($data_value)
{
  try {
   $client = self::getInstance();
   $service = new Google_Service_Sheets($client);
   $spreadsheetId = $this->getSpreadsheetId();

         // Get all worksheets of the spreadsheet
   $work_sheets = $service->spreadsheets->get($spreadsheetId);

   if (!empty($work_sheets) && !empty($data_value)) {
    foreach ($work_sheets as $sheet) {
     $properties = $sheet->getProperties();
     $p_title = $properties->getSheetId();
     $w_title = $this->getWorkTabId();

     if ($p_title == $w_title) {
                  // Match the sheet title
      $w_title = $properties->getTitle();

                  // Retrieve header row from the sheet
      $worksheetCell = $service->spreadsheets_values->get($spreadsheetId, $w_title . "!1:1");

      $insert_data = array();
      if (isset($worksheetCell->values[0])) {
       foreach ($worksheetCell->values[0] as $name) {
                        // Populate data in the same order as headers
        $value = isset($data_value[$name]) ? $data_value[$name] : '';
        $insert_data[] = sanitize_text_field($value);
      }
    }

                  // Determine the next available row
    $tab_name = $w_title;
    $full_range = $tab_name . "!A1:Z";
    $response = $service->spreadsheets_values->get($spreadsheetId, $full_range);
    $get_values = $response->getValues();

    $row = ($get_values) ? count($get_values) + 1 : 1;
    $range = $tab_name . "!A" . $row . ":Z";

                  // Prepare the value range to insert
    $valueRange = new Google_Service_Sheets_ValueRange();
    $valueRange->setValues(["values" => $insert_data]);

    $conf = ["valueInputOption" => "USER_ENTERED"];

                  // Append the data to the sheet
    $result = $service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
  }
}
}
} catch (Exception $e) {
         // Log any error for debugging
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error adding row to Google Sheet: ' . $e->getMessage());
 return null;
}
}

/**
 * Check if the spreadsheet and worksheet (tab) exist.
 *
 * Verifies the presence of the spreadsheet and the selected tab.
 *
 * @return array|null ['sheet' => bool, 'tab' => bool] or null on failure.
 */
public function check_if_sheet_exist()
{
  try {
   $client = self::getInstance();
   $service = new Google_Service_Sheets($client);

         // Fetch all sheets from the spreadsheet
   $work_sheets = $service->spreadsheets->get($this->getSpreadsheetId());

   if (!empty($work_sheets)) {
    $array_v['sheet'] = true;
    $array_v['tab'] = false;

            // Loop through each worksheet and check for a matching tab ID
    foreach ($work_sheets as $sheet) {
     $properties = $sheet->getProperties();
     $p_title = $properties->getSheetId();
     $w_title = $this->getWorkTabId();

     if ($p_title == $w_title) {
      $array_v['tab'] = true;
    }
  }
} else {
  $array_v['sheet'] = false;
  $array_v['tab'] = false;
}
} catch (Exception $e) {
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log($e->getMessage());
 return null;
}

return $array_v;
}


/**
 * Retrieve header row data from the selected worksheet.
 *
 * Returns header names along with their index positions.
 *
 * @return array|null List of headers or null on failure.
 */
public function list_rows()
{
  $work_tabs_list = array();

  try {
   $client = self::getInstance();
   $service = new Google_Service_Sheets($client);
   $spreadsheetId = $this->getSpreadsheetId();

   $work_sheets = $service->spreadsheets->get($spreadsheetId);

   if (!empty($work_sheets)) {
    foreach ($work_sheets as $sheet) {
     $properties = $sheet->getProperties();
     $p_title = $properties->getSheetId();
     $w_title = $this->getWorkTabId();

     if ($p_title == $w_title) {
      $w_title = $properties->getTitle();

                  // Get header row (1st row)
      $worksheetCell = $service->spreadsheets_values->get($spreadsheetId, $w_title . "!1:1");

      if (isset($worksheetCell->values[0])) {
       foreach ($worksheetCell->values[0] as $k => $name) {
        $work_tabs_list[] = array(
         'id' => $k,
         'title' => $name,
       );
      }
    }
  }
}
}
} catch (Exception $e) {
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log($e->getMessage());
 return null;
}

return $work_tabs_list;
}


 /**
 * Retrieve all accessible Google Spreadsheets.
 *
 * Uses Google Drive API to list spreadsheet files.
 *
 * @return array|null List of spreadsheets or null on failure.
 */
 public function get_spreadsheets()
 {
  $all_sheets = array();

  try {
   $client = self::getInstance();
   $service = new Google_Service_Drive($client);

         // Search only for Google Sheets files
   $optParams = array(
    'q' => "mimeType='application/vnd.google-apps.spreadsheet'"
  );

   $results = $service->files->listFiles($optParams);

   foreach ($results->files as $spreadsheet) {
    if (isset($spreadsheet['kind']) && $spreadsheet['kind'] == 'drive#file') {
     $all_sheets[] = array(
      'id' => $spreadsheet['id'],
      'title' => $spreadsheet['name'],
    );
   }
 }
} catch (Exception $e) {
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log($e->getMessage());
 return null;
}

return $all_sheets;
}


/**
 * Retrieve all worksheet tabs from a spreadsheet.
 *
 * @param string $spreadsheet_id Google Spreadsheet ID.
 * @return array|null List of tabs or null on failure.
 */
public function get_worktabs($spreadsheet_id)
{
  $work_tabs_list = array();

  try {
   $client = self::getInstance();
   $service = new Google_Service_Sheets($client);

   $work_sheets = $service->spreadsheets->get($spreadsheet_id);

   foreach ($work_sheets as $sheet) {
    $properties = $sheet->getProperties();

    $work_tabs_list[] = array(
     'id' => $properties->getSheetId(),
     'title' => $properties->getTitle(),
   );
  }
} catch (Exception $e) {
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log($e->getMessage());
 return null;
}

return $work_tabs_list;
}


/**
 * Retrieve and store the connected Google account email.
 *
 * Authenticates the user and saves the email in options.
 *
 * @return string|false Email address or false on failure.
 */
public function gsheet_print_google_account_email()
{
  try {
         // Authenticate with Google and retrieve email
   $google_sheet = new Gfgscf_googlesheet();
   $google_sheet->auth();
   $email = $google_sheet->gsheet_get_google_account_email();

   update_option("gfgs_email_account", $email);

   return $email;
 } catch (Exception $e) {

   GravityForms_GsFree_Connector_Utility::gfgs_debug_log($e->getMessage());
   return false;
 }
}

/**
 * Get Google account email from OAuth user info.
 *
 * @return string Email address or empty string if unavailable.
 */
public function gsheet_get_google_account_email()
{
  $google_account = $this->gsheet_get_google_account();

  if ($google_account) {
   return $google_account->email;
 } else {
   return "";
 }
}


/**
 * Retrieve Google account information using OAuth2 service.
 *
 * Uses the authenticated Google Client to fetch user profile details
 * such as email and other account information.
 *
 * @return Google_Service_Oauth2_Userinfo|false User info object on success, false on failure.
 */
public function gsheet_get_google_account()
{
  try {
   $client = self::getInstance();

   if (!$client) {
    return false;
  }

  $service = new Google_Service_Oauth2($client);
  $user = $service->userinfo->get();
} catch (Exception $e) {
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log($e->getMessage());
 return false;
}

return $user;
}


/**
 * Revoke a Google OAuth2 access token programmatically.
 *
 * Decodes the provided token data, initializes a Google Client,
 * and revokes the access token using Google's API.
 *
 * @param string $access_code JSON string containing access_token.
 * @return void
 */
public static function revokeToken_auto($access_code)
{
      // Get API credentials based on multisite setup
  if (is_multisite()) {
   $api_creds = get_site_option('Gfgsc_api_creds');
 } else {
   $api_creds = get_option('Gfgsc_api_creds');
 }

 $newClientSecret = get_option('is_new_client_secret_gravityformsgsc');
 $clientId = ($newClientSecret == 1) ? $api_creds['client_id_web'] : $api_creds['client_id_desk'];
 $clientSecret = ($newClientSecret == 1) ? $api_creds['client_secret_web'] : $api_creds['client_secret_desk'];

 $client = new Google_Client();
 $client->setClientId($clientId);
 $client->setClientSecret($clientSecret);

 $tokendecode = json_decode($access_code);
 $token = $tokendecode->access_token;

      // Revoke token using Google's OAuth client
 $client->revokeToken($token);
}

}
