<?php

if (!defined('ABSPATH'))
 exit;

class Gfgscf_googlesheet
{

 private $token;
 private $spreadsheet;
 private $worksheet;

 public function __construct()
 {

 }


/**
* Thin wrapper around wp_remote_request() for Google REST calls.
*
* @param string $method HTTP method.
* @param string $url    Full REST endpoint URL.
* @param string $token  Bearer access token.
* @param array  $args   Extra wp_remote_request() args (body/headers).
* @return array|WP_Error Decoded JSON body, or WP_Error on failure.
*/
private static function request($method, $url, $token, $args = array())
   {
      $args['method']  = $method;
      $args['headers'] = array_merge(
         array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
         ),
         isset($args['headers']) ? $args['headers'] : array()
      );

      if (isset($args['body']) && is_array($args['body'])) {
         $args['body'] = wp_json_encode($args['body']);
      }

      $response = wp_remote_request($url, $args);

      if (is_wp_error($response)) {
         return $response;
      }

      $code = wp_remote_retrieve_response_code($response);
      $body = json_decode(wp_remote_retrieve_body($response), true);

      if ($code < 200 || $code >= 300) {
         $message = isset($body['error']['message']) ? $body['error']['message'] : 'Unknown Google API error.';
         return new WP_Error('gsc_api_error', $message, array('status' => $code, 'body' => $body));
      }

      return $body;
   }


private static function base64url($data)
   {
      return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
   }


private static function creds()
   {
   return is_multisite()
   ? get_site_option('Gfgsc_api_creds')
   : get_option('Gfgsc_api_creds');
   }

/**
* Retrieve the client ID/secret pair to use for OAuth requests.
*
* @return array{0: string, 1: string} [client_id, client_secret]
*/
private static function client_credentials()
   {
      $creds           = self::creds();
      $newClientSecret = get_option('is_new_client_secret_gravityformsgsc');

      $clientId     = ($newClientSecret == 1) ? $creds['client_id_web'] : $creds['client_id_desk'];
      $clientSecret = ($newClientSecret == 1) ? $creds['client_secret_web'] : $creds['client_secret_desk'];

      return array($clientId, $clientSecret);
   }

/**
* Fetch a spreadsheet's sheet/tab metadata via the Sheets REST API.
*
* @param string $spreadsheet_id Google Spreadsheet ID.
* @return array|WP_Error List of sheet entries (each with a `properties` array), or WP_Error on failure.
*/
private function get_spreadsheet_meta($spreadsheet_id)
   {
      $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($spreadsheet_id) . '?fields=' . rawurlencode('sheets.properties');

      $body = self::request('GET', $url, $this->token);

      if (is_wp_error($body)) {
         return $body;
      }

      return isset($body['sheets']) ? $body['sheets'] : array();
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
//constructed on call
   public static function preauth($access_code)
   {

      try {
         $creds = self::creds();
         if (!$creds) return;

         $response = wp_remote_post(
            'https://oauth2.googleapis.com/token',
            [
            'body' => [
               'code'          => $access_code,
               'client_id'     => $creds['client_id_web'],
               'client_secret' => $creds['client_secret_web'],
               'redirect_uri'  => 'https://oauth.gsheetconnector.com',
               'grant_type'    => 'authorization_code'
            ]
            ]
         );
         if (is_wp_error($response)) {
            return false;
         }

         $body = json_decode(wp_remote_retrieve_body($response), true);
         if (!is_array($body)) {
            $body = [];
         }

         self::updateToken($body);

         return !empty($body['access_token']);
      } catch (Exception $e) {
         GravityForms_GsFree_Connector_Utility::gfgs_debug_log('[Auth Exception]. ' . $e->getMessage());
         throw new LogicException('Auth error: ' . esc_html($e->getMessage()));
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
      $expires_in = isset($tokenData['expires_in']) ? intval($tokenData['expires_in']) : 0;
      $tokenData['expire'] = time() + $expires_in;
      try {

         if (isset($tokenData['scope'])) {
            $permission = explode(" ", $tokenData['scope']);
            if ((in_array("https://www.googleapis.com/auth/drive.metadata.readonly", $permission)) && (in_array("https://www.googleapis.com/auth/spreadsheets", $permission))) {
               update_option('gfgs_verify', 'valid');
            } else {
               update_option('gfgs_verify', 'invalid-auth');

                // Log permission error to error logs
                 if (class_exists('gscgf_error_logs')) {
                  gscgf_error_logs::log_to_db(
                    'Google_Auth_Permission_Error',
                    403,
                    'Google Drive and Google Sheets permissions not granted',
                    [
                      'error_type' => 'Missing Permissions',
                      'message' => 'User did not grant Google Drive and/or Google Sheets permissions during OAuth authentication',
                      'granted_scopes' => $tokenData['scope'] ?? '',
                      'required_drive_scope' => 'https://www.googleapis.com/auth/drive.file OR https://www.googleapis.com/auth/drive.metadata.readonly',
                      'required_sheets_scope' => 'https://www.googleapis.com/auth/spreadsheets',
                    ]
                  );
                }
            }
         }
         $tokenJson = json_encode($tokenData);
         update_option('gfgs_token', $tokenJson);


      } catch (Exception $e) {
         	GravityForms_GsFree_Connector_Utility::gfgs_debug_log("Token write fail! - " . $e->getMessage());
      }
   }

/**
 * Authenticate using the stored refresh token.
 *
 * Exchanges the stored refresh token for a fresh access token via
 * Google's OAuth2 token endpoint and keeps it on the instance for
 * subsequent REST calls.
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
   list($clientId, $clientSecret) = self::client_credentials();

   $response = wp_remote_post(
      'https://oauth2.googleapis.com/token',
      [
      'body' => [
         'refresh_token' => $tokenData['refresh_token'],
         'client_id'     => $clientId,
         'client_secret' => $clientSecret,
         'grant_type'    => 'refresh_token',
      ]
      ]
   );

   if (is_wp_error($response)) {
      throw new LogicException($response->get_error_message());
   }

   $body = json_decode(wp_remote_retrieve_body($response), true);

   if (!is_array($body) || empty($body['access_token'])) {
      $message = isset($body['error_description']) ? $body['error_description'] : 'Unable to refresh access token.';
      throw new LogicException($message);
   }

         // Google does not return the refresh_token on a refresh grant; keep the original.
   $body['refresh_token'] = $tokenData['refresh_token'];

         // Update token storage (will retain original refresh_token)
   Gfgscf_googlesheet::updateToken($body);

         // Keep the fresh access token on this instance for subsequent REST calls
   $this->token = $body['access_token'];

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
   $spreadsheetId = $this->getSpreadsheetId();

         // Get all worksheets of the spreadsheet
   $work_sheets = $this->get_spreadsheet_meta($spreadsheetId);

   if (is_wp_error($work_sheets)) {
    GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error adding row to Google Sheet: ' . $work_sheets->get_error_message());
    return null;
  }

   if (!empty($work_sheets) && !empty($data_value)) {
    foreach ($work_sheets as $sheet) {
     $properties = $sheet['properties'];
     $p_title = $properties['sheetId'];
     $w_title = $this->getWorkTabId();

     if ($p_title == $w_title) {
                  // Match the sheet title
      $w_title = $properties['title'];

                  // Retrieve header row from the sheet
      $worksheetCell = self::request('GET', 'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($spreadsheetId) . '/values/' . rawurlencode($w_title . '!1:1'), $this->token);

      $insert_data = array();
      if (!is_wp_error($worksheetCell) && isset($worksheetCell['values'][0])) {
       foreach ($worksheetCell['values'][0] as $name) {
                        // Populate data in the same order as headers
        $value = isset($data_value[$name]) ? $data_value[$name] : '';
        $insert_data[] = sanitize_text_field($value);
      }
    }

                  // Append the data to the sheet; the Sheets API finds the next empty row automatically
    $range = $w_title . '!A1:Z';
    $append_url = 'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($spreadsheetId) . '/values/' . rawurlencode($range) . ':append?valueInputOption=USER_ENTERED';

    $result = self::request('POST', $append_url, $this->token, array(
      'body' => array('values' => array($insert_data)),
    ));

    if (is_wp_error($result)) {
     GravityForms_GsFree_Connector_Utility::gfgs_debug_log('Error adding row to Google Sheet: ' . $result->get_error_message());
    }

    break;
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
  $array_v = array('sheet' => false, 'tab' => false);

  try {
         // Fetch all sheets from the spreadsheet
   $work_sheets = $this->get_spreadsheet_meta($this->getSpreadsheetId());

   if (is_wp_error($work_sheets)) {
    return $array_v;
  }

   if (!empty($work_sheets)) {
    $array_v['sheet'] = true;

            // Loop through each worksheet and check for a matching tab ID
    foreach ($work_sheets as $sheet) {
     $properties = $sheet['properties'];
     $p_title = $properties['sheetId'];
     $w_title = $this->getWorkTabId();

     if ($p_title == $w_title) {
      $array_v['tab'] = true;
    }
  }
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
   $spreadsheetId = $this->getSpreadsheetId();

   $work_sheets = $this->get_spreadsheet_meta($spreadsheetId);

   if (is_wp_error($work_sheets)) {
    return null;
  }

   if (!empty($work_sheets)) {
    foreach ($work_sheets as $sheet) {
     $properties = $sheet['properties'];
     $p_title = $properties['sheetId'];
     $w_title = $this->getWorkTabId();

     if ($p_title == $w_title) {
      $w_title = $properties['title'];

                  // Get header row (1st row)
      $worksheetCell = self::request('GET', 'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($spreadsheetId) . '/values/' . rawurlencode($w_title . '!1:1'), $this->token);

      if (!is_wp_error($worksheetCell) && isset($worksheetCell['values'][0])) {
       foreach ($worksheetCell['values'][0] as $k => $name) {
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
 * Uses the Google Drive REST API to list spreadsheet files.
 *
 * @return array|null List of spreadsheets or null on failure.
 */
 public function get_spreadsheets()
 {
  $all_sheets = array();

  try {
         // Search only for Google Sheets files
   $query = "mimeType='application/vnd.google-apps.spreadsheet'";
   $url = 'https://www.googleapis.com/drive/v3/files?q=' . rawurlencode($query) . '&fields=' . rawurlencode('files(id,name,mimeType)');

   $results = self::request('GET', $url, $this->token);

   if (is_wp_error($results) || empty($results['files'])) {
    return $all_sheets;
  }

   foreach ($results['files'] as $spreadsheet) {
    if (isset($spreadsheet['mimeType']) && $spreadsheet['mimeType'] == 'application/vnd.google-apps.spreadsheet') {
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
   $work_sheets = $this->get_spreadsheet_meta($spreadsheet_id);

   if (is_wp_error($work_sheets)) {
    return null;
  }

   foreach ($work_sheets as $sheet) {
    $properties = $sheet['properties'];

    $work_tabs_list[] = array(
     'id' => $properties['sheetId'],
     'title' => $properties['title'],
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

  if ($google_account && isset($google_account['email'])) {
   return $google_account['email'];
 } else {
   return "";
 }
}

/**
 * Retrieve Google account information via the OAuth2 userinfo REST endpoint.
 *
 * Uses the stored access token to fetch user profile details
 * such as email and other account information.
 *
 * @return array|false User info array on success, false on failure.
 */
public function gsheet_get_google_account()
{
  try {
   if (empty($this->token)) {
    return false;
  }

  $user = self::request('GET', 'https://www.googleapis.com/oauth2/v2/userinfo', $this->token);

  if (is_wp_error($user)) {
   return false;
 }
} catch (Exception $e) {
 GravityForms_GsFree_Connector_Utility::gfgs_debug_log($e->getMessage());
 return false;
}

return $user;
}


/**
 * Revoke a Google OAuth2 access token programmatically.
 *
 * Decodes the provided token data and revokes the access token
 * using Google's OAuth2 revoke REST endpoint.
 *
 * @param string $access_code JSON string containing access_token.
 * @return bool True if the revoke request was sent, false otherwise.
 */
public static function revokeToken_auto($access_code)
{
    // Guard against empty / invalid / already-array input
    if (empty($access_code)) {
        return false; // nothing to revoke
    }

    $tokendecode = is_array($access_code) ? (object) $access_code : json_decode($access_code);

    // json_decode failed, or no access_token present
    if (!is_object($tokendecode) || empty($tokendecode->access_token)) {
        return false;
    }

    $response = wp_remote_post(
        'https://oauth2.googleapis.com/revoke',
        [
            'body' => [
                'token' => $tokendecode->access_token,
            ],
        ]
    );

    return !is_wp_error($response);
}

}
