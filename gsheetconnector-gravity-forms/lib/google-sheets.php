<?php

if (!defined('ABSPATH'))
   exit;

//include ( plugin_dir_path(__FILE__) . 'vendor/autoload.php' );

$file_path = (is_plugin_active('gsheetconnector-gravity-forms/gsheetconnector-gravityforms.php')) ? plugin_dir_path(__FILE__) . 'vendor/autoload.php' : "";

if ($file_path != "") {
   include($file_path);
}

class Gfgsc_googlesheet
{

   private $token;
   private $spreadsheet;
   private $worksheet;
   private static $instance;

   public function __construct()
   {

   }

   public static function setInstance(Google_Client $instance = null)
   {
      self::$instance = $instance;
   }

   public static function getInstance()
   {
      if (is_null(self::$instance)) {
         throw new LogicException("Invalid Client");
      }

      return self::$instance;
   }

   //constructed on call
   public static function preauth($access_code)
   {
      if (is_multisite()) {
         // Fetch API creds
         $api_creds = get_site_option('Gfgsc_api_creds');
      } else {
         // Fetch API creds
         $api_creds = get_option('Gfgsc_api_creds');
      }

      $newClientSecret = get_option('is_new_client_secret_gravityformsgsc');
      $clientId = ($newClientSecret == 1) ? $api_creds['client_id_web'] : $api_creds['client_id_desk'];
      $clientSecret = ($newClientSecret == 1) ? $api_creds['client_secret_web'] : $api_creds['client_secret_desk'];

      $client = new Google_Client();
      $client->setClientId($clientId);
      $client->setClientSecret($clientSecret);
      $client->setRedirectUri('https://oauth.gsheetconnector.com');
      $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
      $client->setScopes(Google_Service_Drive::DRIVE_METADATA_READONLY);
      $client->setAccessType('offline');
      $client->fetchAccessTokenWithAuthCode($access_code);
      $tokenData = $client->getAccessToken();

      Gfgsc_googlesheet::updateToken($tokenData);
   }

   public static function updateToken($tokenData)
   {
      $tokenData['expire'] = time() + intval($tokenData['expires_in']);
      try {
         //$tokenJson = json_encode($tokenData);
         //update_option('gfgs_token', $tokenJson);
         //resolved - google sheet permission issues - START
         if (isset($tokenData['scope'])) {
            $permission = explode(" ", $tokenData['scope']);
            if ((in_array("https://www.googleapis.com/auth/drive.metadata.readonly", $permission)) && (in_array("https://www.googleapis.com/auth/spreadsheets", $permission))) {
               update_option('gfgs_verify', 'valid');
            } else {
               update_option('gfgs_verify', 'invalid-auth');
            }
         }
         $tokenJson = json_encode($tokenData);
         update_option('gfgs_token', $tokenJson);
         //resolved - google sheet permission issues - END

      } catch (Exception $e) {
         GravityForms_Gs_Connector_Utility::gfgs_debug_log("Token write fail! - " . $e->getMessage());
      }
   }

   public function auth()
   {
      $tokenData = json_decode(get_option('gfgs_token'), true);
      if (!isset($tokenData['refresh_token']) || empty($tokenData['refresh_token'])) {
         throw new LogicException("Auth, Invalid OAuth2 access token");
         exit();
      }

      try {
         if (is_multisite()) {
            // Fetch API creds
            $api_creds = get_site_option('Gfgsc_api_creds');
         } else {
            // Fetch API creds
            $api_creds = get_option('Gfgsc_api_creds');
         }

         $newClientSecret = get_option('is_new_client_secret_gravityformsgsc');
         $clientId = ($newClientSecret == 1) ? $api_creds['client_id_web'] : $api_creds['client_id_desk'];
         $clientSecret = ($newClientSecret == 1) ? $api_creds['client_secret_web'] : $api_creds['client_secret_desk'];

         $client = new Google_Client();
         $client->setClientId($clientId);
         $client->setClientSecret($clientSecret);
         $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
         $client->setScopes(Google_Service_Drive::DRIVE_METADATA_READONLY);
         $client->refreshToken($tokenData['refresh_token']);
         //$tokenData = array_merge($tokenData, $client->getAccessToken());
         $client->setAccessType('offline');
         Gfgsc_googlesheet::updateToken($tokenData);

         self::setInstance($client);
      } catch (Exception $e) {
         GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         throw new LogicException("Auth, Error fetching OAuth2 access token, message: " . $e->getMessage());
         exit();
      }
   }

   public function get_user_info()
   {
      $client = self::getInstance();

      $results = $this->get_spreadsheets();

      echo '<pre>';
      print_r($results);
      echo '</pre>';
      $spreadsheets = $this->get_worktabs('1mRuDMnZveDFQrmzHM9s5YkPA4F_dZkHJ1Gh81BvYB2k');
      echo '<pre>';
      print_r($spreadsheets);
      echo '</pre>';
      $this->setSpreadsheetId('1mRuDMnZveDFQrmzHM9s5YkPA4F_dZkHJ1Gh81BvYB2k');
      $this->setWorkTabId('Foglio1');
      $worksheetTab = $this->list_rows();
      echo '<pre>';
      print_r($worksheetTab);
      echo '</pre>';
   }

   //preg_match is a key of error handle in this case
   public function setSpreadsheetId($id)
   {
      $this->spreadsheet = $id;
   }

   public function getSpreadsheetId()
   {

      return $this->spreadsheet;
   }

   //finished setting the title
   public function setWorkTabId($id)
   {
      $this->worksheet = $id;
   }

   public function getWorkTabId()
   {
      return $this->worksheet;
   }

   public function add_row($data_value)
   {
      try {

         $client = self::getInstance();

         $service = new Google_Service_Sheets($client);

         $spreadsheetId = $this->getSpreadsheetId();


         $work_sheets = $service->spreadsheets->get($spreadsheetId);



         if (!empty($work_sheets) && !empty($data_value)) {
            foreach ($work_sheets as $sheet) {
               $properties = $sheet->getProperties();

               $p_title = $properties->getSheetId();

               $w_title = $this->getWorkTabId();

               if ($p_title == $w_title) {
                  $w_title = $properties->getTitle();

                  $worksheetCell = $service->spreadsheets_values->get($spreadsheetId, $w_title . "!1:1");

                  $insert_data = array();
                  if (isset($worksheetCell->values[0])) {
                     $insert_data_index = 0;

                     foreach ($worksheetCell->values[0] as $k => $name) {

                        if ($insert_data_index == 0) {
                           if (isset($data_value[$name]) && $data_value[$name] != '') {




                              $insert_data[] = $data_value[$name];
                           } else {
                              $insert_data[] = '';
                           }
                        } else {
                           if (isset($data_value[$name]) && $data_value[$name] != '') {
                              $insert_data[] = $data_value[$name];
                           } else {
                              $insert_data[] = '';
                           }
                        }
                        $insert_data_index++;
                     }
                  }


                  /*RASHID*/
                  $tab_name = $w_title;
                  $full_range = $tab_name . "!A1:Z";
                  $response = $service->spreadsheets_values->get($spreadsheetId, $full_range);
                  $get_values = $response->getValues();

                  if ($get_values) {
                     $row = count($get_values) + 1;
                  } else {
                     $row = 1;
                  }
                  $range = $tab_name . "!A" . $row . ":Z";


                  $range_new = $w_title;

                  // Create the value range Object
                  $valueRange = new Google_Service_Sheets_ValueRange();


                  // You need to specify the values you insert
                  $valueRange->setValues(["values" => $insert_data]);


                  // Add two values
                  // Then you need to add some configuration
                  $conf = ["valueInputOption" => "USER_ENTERED", "insertDataOption" => "INSERT_ROWS"];
                  $conf = ["valueInputOption" => "USER_ENTERED"];

                  // append the spreadsheet
                  $result = $service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
               }
            }
         }
      } catch (Exception $e) {
         GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         return null;
         exit();
      }
   }

   public function check_if_sheet_exist()
   {
      try {
         $client = self::getInstance();
         $service = new Google_Service_Sheets($client);
         $work_sheets = $service->spreadsheets->get($this->getSpreadsheetId());
         if (!empty($work_sheets)) {
            $array_v['sheet'] = true;
            $array_v['tab'] = false;
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
         GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         return null;
         exit();
      }

      return $array_v;
   }

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
         GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         return null;
         exit();
      }
      return $work_tabs_list;
   }

   //get all the sheets
   public function get_spreadsheets()
   {
      $all_sheets = array();
      try {
         $client = self::getInstance();
         $service = new Google_Service_Drive($client);
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
         GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         return null;
         exit();
      }
      return $all_sheets;
   }

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
         GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         return null;
         exit();
      }

      return $work_tabs_list;
   }


   public function gsheet_print_google_account_email()
   {

      try {
         // $google_account = get_option("gfgs_email_account");
         // if( false && $google_account ) {
         //    return $google_account;
         // }
         // else {
         $google_sheet = new GFGSC_googlesheet();
         $google_sheet->auth();
         $email = $google_sheet->gsheet_get_google_account_email();
         update_option("gfgs_email_account", $email);
         return $email;
         // }
      } catch (Exception $e) {
         GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         return false;
      }


   }

   public function gsheet_get_google_account_email()
   {
      $google_account = $this->gsheet_get_google_account();

      if ($google_account) {
         return $google_account->email;
      } else {
         return "";
      }
   }

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
         GravityForms_Gs_Connector_Utility::gfgs_debug_log($e->getMessage());
         return false;
      }

      return $user;
   }

   public static function revokeToken_auto($access_code)
   {
      if (is_multisite()) {
         // Fetch API creds
         $api_creds = get_site_option('Gfgsc_api_creds');
      } else {
         // Fetch API creds
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
      $client->revokeToken($token);
   }


}
