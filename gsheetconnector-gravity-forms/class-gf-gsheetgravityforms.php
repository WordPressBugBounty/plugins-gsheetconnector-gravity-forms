<?php
if (!defined('ABSPATH')) {
  exit;
}

GFForms::include_payment_addon_framework();

class Gforms_Gsheet_Connector extends GFFeedAddOn
{

  protected $_version = GRAVITY_GOOGLESHEET_VERSION;
  protected $_min_gravityforms_version = '1.0';
  protected $_slug = 'gsheetconnector-gravity-forms';
  protected $_path = 'gsheetconnector-gravity-forms/gsheetconnector-gravity-forms.php';
  protected $_full_path = __FILE__;
  protected $_title = 'Gravity Forms GSheet Connector Addon';
  protected $_short_title = 'Google Sheets';
  protected $_single_feed_submission = true;
  protected $_enable_rg_autoupgrade = true;
  private static $_instance = null;
  protected $_capabilities_form_settings = array();

  /**
   * Get an instance of this class.
   *
   * @return Gforms_Gsheet_Connector
   */
  public static function get_instance()
  {
    if (self::$_instance == null) {
      self::$_instance = new Gforms_Gsheet_Connector();
    }
    return self::$_instance;
  }

  //public function __construct() { // Resolved Issue Unable to render page
  public function init()
  {
    parent::init();

    add_action('admin_init', array($this, 'after_save_form_settings'));
    add_action('gform_after_submission', array($this, 'after_submission'), 10, 2);
    add_action('admin_footer', array($this, 'add_gf_nonce'));
  }


  /**
   * Define feed settings fields for selecting and configuring Google Sheets.
   *
   * This method builds the settings UI shown under:
   * Form Settings → Google Sheet → Feeds.
   * It conditionally displays fields based on authentication status.
   *
   * @return array $fields Configuration array for feed settings UI.
   * @since 1.0
   */
  public function feed_settings_fields()
  {
    $fields = array();

    // Check if 'id' is set in $_GET and sanitize
    $form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $feed_id = isset($_GET['fid']) ? intval($_GET['fid']) : 0;

    $form_data = get_post_meta($form_id, 'gfgs_settings', true);
    $sheet_data = get_option('gfgs_feeds');
    $authenticated = get_option('gfgs_token');
    $per = get_option('gfgs_verify');

    // your form id

    if (empty($feed_id) || intval($feed_id) <= 0) {
      $feed_status = '';
    } else {
      $feed = GFAPI::get_feed($feed_id);

      if (is_wp_error($feed) || !is_array($feed)) {
        $feed_status = ' (Feed : Not Found)';
      } else {
        $feedstatus  = $feed['is_active'] == 1 ? 'Active' : 'Inactive';
        $feed_status = ' (Feed : ' . $feedstatus . ')';
      }
    }



    // Check if the user is authenticated when saving via existing API method
    if (!empty($authenticated) && $per === "valid") {

      $fields['feed_settings'] = array(
        'title' => 'GSheetConnector Gravity Forms Feed Settings' . $feed_status,
        'title_class' => 'gsheetconnector-feed-title gsheetconnector-feed-title--' . strtolower($feed_status),
        'fields' => array(
          array(
            'name' => 'feedName',
            'label' => esc_html__('Feed Name', 'gsheetconnector-gravity-forms'),
            'type' => 'text',
            // 'required' => true,
            'class' => 'medium',
            'tooltip' => esc_html__('Enter a feed name to uniquely identify this setup.', 'gsheetconnector-gravity-forms')
          ),
          array(
            'name' => 'sheet_details',
            'label' => esc_html__('Manual Google Sheets Configuration', 'gsheetconnector-gravity-forms'),
            'class' => 'gra-heading',
            'type' => 'display_sheet_details',
          ),

          array(
            'name' => 'gsheet_field_maps',
            'label' => esc_html__('Field List', 'gsheetconnector-gravity-forms'),
            'type' => 'map_form_fields',
          ),
        ),
      );
    } else {
      // When not authenticated
      $fields[] = array(
        'title' => esc_html__('GSheetConnector Gravity Forms Feed Settings', 'gsheetconnector-gravity-forms'),
        'fields' => array(
          array(
            'name' => 'feedName',
            'label' => '',
            'type' => 'display_note',
            'class' => 'hide_save_btn',
          ),
        ),
      );
    }

    return $fields;
  }

  /**
   * Display setup note when Google Sheets is not connected.
   *
   * Renders an admin UI block informing the user that Google authentication
   * is required before using the feed settings. Provides step-by-step guidance
   * and a quick link to the integration setup page.
   *
   * @param array $field Field configuration passed by Gravity Forms (unused here).
   * @return void
   */
  public function settings_display_note($field)
  {
?>

    <div class="gs-display-note">
      <div class="gscgff-setup-alert">
        <div class="gscgff-alert-icon">
          <svg width="30px" height="30px" viewBox="-0.5 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M18.2202 21.25H5.78015C5.14217 21.2775 4.50834 21.1347 3.94373 20.8364C3.37911 20.5381 2.90402 20.095 2.56714 19.5526C2.23026 19.0101 2.04372 18.3877 2.02667 17.7494C2.00963 17.111 2.1627 16.4797 2.47015 15.92L8.69013 5.10999C9.03495 4.54078 9.52077 4.07013 10.1006 3.74347C10.6804 3.41681 11.3346 3.24518 12.0001 3.24518C12.6656 3.24518 13.3199 3.41681 13.8997 3.74347C14.4795 4.07013 14.9654 4.54078 15.3102 5.10999L21.5302 15.92C21.8376 16.4797 21.9907 17.111 21.9736 17.7494C21.9566 18.3877 21.7701 19.0101 21.4332 19.5526C21.0963 20.095 20.6211 20.5381 20.0565 20.8364C19.4919 21.1347 18.8581 21.2775 18.2202 21.25V21.25Z" stroke="#9a3412" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M10.8809 17.15C10.8809 17.0021 10.9102 16.8556 10.9671 16.7191C11.024 16.5825 11.1074 16.4586 11.2125 16.3545C11.3175 16.2504 11.4422 16.1681 11.5792 16.1124C11.7163 16.0567 11.8629 16.0287 12.0109 16.03C12.2291 16.034 12.4413 16.1021 12.621 16.226C12.8006 16.3499 12.9398 16.5241 13.0211 16.7266C13.1023 16.9292 13.122 17.1512 13.0778 17.3649C13.0335 17.5786 12.9272 17.7745 12.7722 17.9282C12.6172 18.0818 12.4203 18.1863 12.2062 18.2287C11.9921 18.2711 11.7703 18.2494 11.5685 18.1663C11.3666 18.0833 11.1938 17.9426 11.0715 17.7618C10.9492 17.5811 10.8829 17.3683 10.8809 17.15ZM11.2409 14.42L11.1009 9.20001C11.0876 9.07453 11.1008 8.94766 11.1398 8.82764C11.1787 8.70761 11.2424 8.5971 11.3268 8.5033C11.4112 8.40949 11.5144 8.33449 11.6296 8.28314C11.7449 8.2318 11.8697 8.20526 11.9959 8.20526C12.1221 8.20526 12.2469 8.2318 12.3621 8.28314C12.4774 8.33449 12.5805 8.40949 12.6649 8.5033C12.7493 8.5971 12.8131 8.70761 12.852 8.82764C12.8909 8.94766 12.9042 9.07453 12.8909 9.20001L12.7609 14.42C12.7609 14.6215 12.6808 14.8149 12.5383 14.9574C12.3957 15.0999 12.2024 15.18 12.0009 15.18C11.7993 15.18 11.606 15.0999 11.4635 14.9574C11.321 14.8149 11.2409 14.6215 11.2409 14.42Z" fill="#9a3412" />
          </svg>
        </div>
        <div class="gscgff-alert-content">
          <div class="feed-alert-header"><?php esc_html_e('Google Sheets Setup Required', 'gsheetconnector-gravity-forms'); ?></div>
          <p><?php esc_html_e('your selected Method is : ', 'gsheetconnector-gravity-forms'); ?>Existing Client / Secret Key (Auto Setup).</p>
          <p><?php esc_html_e('To start sending form entries to Google Sheets, please connect your Google account first.', 'gsheetconnector-gravity-forms'); ?></p>
          <ul>
            <li><?php esc_html_e('✔ Click on the Sign in with Google button', 'gsheetconnector-gravity-forms'); ?></li>
            <li><?php esc_html_e('✔ Log in using your Google account', 'gsheetconnector-gravity-forms'); ?></li>
            <li><?php esc_html_e('✔ Select the Google account where your Sheets are stored', 'gsheetconnector-gravity-forms'); ?></li>
            <li><?php esc_html_e('✔ Grant access to: Google Drive & Google Sheets', 'gsheetconnector-gravity-forms'); ?></li>
            <li><?php esc_html_e('✔ Save the authentication code if prompted', 'gsheetconnector-gravity-forms'); ?></li>
          </ul>
          <a href="admin.php?page=gf_googlesheet&tab=integration" class="gsc-alert-btn link-hover-white">
            <?php esc_html_e('Go to Integration Setup', 'gsheetconnector-gravity-forms'); ?>
          </a>
        </div>
      </div>

    </div>

  <?php //$this->get_google_sheet_settings( $this->get_current_form(), $this->get_current_feed_id() );
  }


  /**
   * Display Gravity Forms Google Sheet settings (Sheet + Tab details UI)
   *
   * This function renders the admin UI for manually configuring:
   * - Sheet Name
   * - Sheet ID
   * - Tab Name
   * - Tab ID
   *
   * It also handles:
   * - Pre-filling saved values
   * - Displaying field mapping UI
   * - Showing FREE vs PRO features UI blocks
   *
   * @param array $field Field configuration passed by Gravity Forms
   * @return void
   * @since 1.0
   */
  public function settings_display_sheet_details($field)
  {
    $form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    $get_data = get_post_meta($form_id, 'gfgs_settings');

    $saved_sheet_name = isset($get_data[0]['sheet-name']) ? $get_data[0]['sheet-name'] : "";
    $saved_tab_name = isset($get_data[0]['sheet-tab-name']) ? $get_data[0]['sheet-tab-name'] : "";
    $saved_sheet_id = isset($get_data[0]['sheet-id']) ? $get_data[0]['sheet-id'] : "";
    $saved_tab_id = isset($get_data[0]['tab-id']) ? $get_data[0]['tab-id'] : "";
    $sheet_data = get_option('gfgs_feeds');



    echo '<div class="gravityforms-panel-content-section-googlesheet-tab">';
    $parent_field_name = $field['name'];
    $form = $this->get_current_form();

    $fields = $form['fields'];
    $fields_inputs = array("name", "address", "consent", "product");


    $field_list = $this->get_form_field_list($form);



    $field_name = "gf-gs";
    $text_field = array(
      'name' => $field_name . '-sheet-name',
      'label' => esc_html__('Sheet Name', 'gsheetconnector-gravity-forms'),
      'type' => 'text',
      'value' => isset($get_data[0]['sheet-name']) ? esc_attr($get_data[0]['sheet-name']) : '',
    );
    $sheet_name_text_field = $this->settings_text($text_field, false);


    /*FIELD END*/

    /*FIELD*/
    $text_field = array(
      'name' => $field_name . '-sheet-id',
      'label' => esc_html__('Sheet Id', 'gsheetconnector-gravity-forms'),
      'type' => 'text',
      'value' => isset($get_data[0]['sheet-id']) ? esc_attr($get_data[0]['sheet-id']) : '',
    );
    $sheet_id_text_field = $this->settings_text($text_field, false);
    /*FIELD END*/

    /*FIELD*/
    $text_field = array(
      'name' => $field_name . '-sheet-tab-name',
      'label' => esc_html__('Tab Name', 'gsheetconnector-gravity-forms'),
      'type' => 'text',
      'value' => isset($get_data[0]['sheet-tab-name']) ? esc_attr($get_data[0]['sheet-tab-name']) : '',
    );
    $tab_name_text_field = $this->settings_text($text_field, false);
    /*FIELD END*/

    /*FIELD*/
    $text_field = array(
      'name' => $field_name . '-tab-id',
      'label' => esc_html__('Tab Id', 'gsheetconnector-gravity-forms'),
      'type' => 'text',
      'value' => isset($get_data[0]['tab-id']) ? esc_attr($get_data[0]['tab-id']) : '',
    );


    $tab_id_text_field = $this->settings_text($text_field, false);  ?>

    <?php
    $settings = $this->get_feed($this->get_current_feed_id());
    $feed = isset($settings['meta']['gsheet_field_maps_enable']) ? $settings['meta']['gsheet_field_maps_enable'] : array(); ?>

    <a class="sheet-url-gravityform common-sheet-url btn mr-10 text-dark text-decoration-none mt-30 blinking-button" href="#" target="_blank"></a>

    <p><?php echo esc_html(__('Connect your Google Sheet by entering the required sheet information.', 'gsheetconnector-gravity-forms')); ?></p>

    <div class="gsgf-free">

      <?php
      $checkAll = '';
      echo wp_kses(
        $checkAll,
        array(
          'span' => array('class' => true, 'disabled' => true),
          'div' => array('id' => true, 'class' => true),
          'input' => array('type' => true, 'name' => true, 'value' => true, 'id' => true, 'class' => true, 'disabled' => true),
          'label' => array('for' => true, 'class' => true),
        )
      ); ?>

      <form method="post">
        <div class="gfgsheet-fields">
          <p>
            <label><?php echo esc_html(__('Sheet Name', 'gsheetconnector-gravity-forms')); ?> <button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip"
                aria-label='Enter the exact name of your Google Spreadsheet (as shown in Google Sheets).'>
                <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
              </button></label>

            <?php
            echo wp_kses($sheet_name_text_field, array(
              'input' => array(
                'type' => true,
                'name' => true,
                'value' => true,
                'class' => true,
                'id' => true,
                'placeholder' => true,
              ),
              'label' => array(
                'for' => true,
              ),
              'br' => array(),
              'div' => array(
                'class' => true,
                'id' => true,
              ),
              'span' => array(
                'class' => true,
              ),
            ));
            echo wp_kses_post($sheet_name_text_field); ?>
            <span class="gform-settings-validation__error" id="error-sheetName"></span>
          </p>
          <p>
            <label><?php echo esc_html(__('Sheet Id', 'gsheetconnector-gravity-forms')); ?>
              <button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip"
                aria-label='Paste the Spreadsheet ID from your Google Sheet URL.'>
                <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
              </button>
            </label>

            <?php
            echo wp_kses($sheet_id_text_field, array(
              'input' => array(
                'type' => true,
                'name' => true,
                'value' => true,
                'class' => true,
                'id' => true,
                'placeholder' => true,
              ),
              'label' => array(
                'for' => true,
              ),
              'br' => array(),
              'div' => array(
                'class' => true,
                'id' => true,
              ),
              'span' => array(
                'class' => true,
              ),
            ));
            echo wp_kses_post($sheet_id_text_field) ?>
            <span class="gform-settings-validation__error" id="error-sheetId"></span>
          </p>
          <p>
            <label><?php echo esc_html(__('Tab Name', 'gsheetconnector-gravity-forms')); ?>
              <button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip"
                aria-label='Get the Tab ID from your sheet URL after gid=.'>
                <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
              </button>

            </label>
            <?php /*?><span class="required">(Required)</span><?php */ ?>

            <?php
            echo wp_kses($tab_name_text_field, array(
              'input' => array(
                'type' => true,
                'name' => true,
                'value' => true,
                'class' => true,
                'id' => true,
                'placeholder' => true,
              ),
              'label' => array(
                'for' => true,
              ),
              'br' => array(),
              'div' => array(
                'class' => true,
                'id' => true,
              ),
              'span' => array(
                'class' => true,
              ),
            ));
            echo wp_kses_post($tab_name_text_field) ?>
            <span class="gform-settings-validation__error" id="error-tabName"></span>
          </p>
          <p>
            <label><?php echo esc_html(__('Tab Id', 'gsheetconnector-gravity-forms')); ?>
              <button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip"
                aria-label='You can get the tab id from your sheet URL'>
                <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
              </button>
            </label>
            <?php
            echo wp_kses($tab_id_text_field, array(
              'input' => array(
                'type' => true,
                'name' => true,
                'value' => true,
                'class' => true,
                'id' => true,
                'placeholder' => true,
              ),
              'label' => array(
                'for' => true,
              ),
              'br' => array(),
              'div' => array(
                'class' => true,
                'id' => true,
              ),
              'span' => array(
                'class' => true,
              ),
            ));
            echo wp_kses_post($tab_id_text_field) ?>
            <span class="gform-settings-validation__error" id="error-tabId"></span>
          </p>

        </div>

        <span id="sheet_url"></span>

        <?php /*?><p class="sheet_url" id="sheet_url"></p><?php */ ?>
    </div>
    </div>
    <div class="gsgf-free">

      <!-- unlock advanced feature #start -->

      <div class="edit-gs-pro-card shadow-box mt-40 mb-30">

        <div class="edit-gs-pro-header p-20">
          <div class="edit-gs-pro-icon">
            <span class="pro-badge">
              <i class="fas fa-lock gsc-pro-icon"></i>
            </span>
          </div>

          <div class="edit-gs-pro-title">
            <div class="heading mt-0">
              <?php esc_html_e('Unlock Advanced Features with Form Feeds', 'gsheetconnector-gravity-forms'); ?>
            </div>

            <div class="d-flex align-items-center gap-15">
              <span class="edit-gs-pro-badge">
                <?php esc_html_e('Advanced options are available in PRO', 'gsheetconnector-gravity-forms'); ?>
              </span>

              <span class="edit-gs-upgrade-btn">
                <a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector" target="_blank" class="text-decoration-none link-hover-white">
                  <?php esc_html_e('Get Advanced Features', 'gsheetconnector-gravity-forms'); ?>
                </a>
              </span>
            </div>
          </div>
        </div>

        <!-- Toggle checkbox -->
        <input type="checkbox" id="toggle-features">

        <div class="edit-gs-pro-features p-20">

          <div class="edit-gs-feature-col">
            <div class="mb-20">
              <a href="#gform_setting_gs_sheet_select">
                <?php esc_html_e('Automatic Google Sheets Configuration', 'gsheetconnector-gravity-forms'); ?>
              </a>
            </div>

            <div class="gsc-pro-grid">
              <ul>
                <li><?php esc_html_e('Auto fetch Google Sheets list', 'gsheetconnector-gravity-forms'); ?></li>
                <li><?php esc_html_e('Auto detect sheet tabs', 'gsheetconnector-gravity-forms'); ?></li>
                <li><?php esc_html_e('One-click configuration', 'gsheetconnector-gravity-forms'); ?></li>
                <li><?php esc_html_e('Real-time entry sync', 'gsheetconnector-gravity-forms'); ?></li>
              </ul>
            </div>
          </div>

          <div class="edit-gs-feature-col">
            <div class="mb-20">
              <a href="#field-mapping">
                <?php esc_html_e('Select Fields to Sync', 'gsheetconnector-gravity-forms'); ?>
              </a>
            </div>
            <div class="gsc-pro-grid">
              <ul>
                <li><?php esc_html_e('Drag & drop field reordering', 'gsheetconnector-gravity-forms'); ?></li>
                <li><?php esc_html_e('Rename column headers', 'gsheetconnector-gravity-forms'); ?></li>
                <li><?php esc_html_e('Select specific fields to sync', 'gsheetconnector-gravity-forms'); ?></li>
              </ul>
            </div>
          </div>

          <div class="edit-gs-feature-col">
            <div class="mb-20">
              <a href="#header-settings-sheet-sorting">
                <?php esc_html_e('Header Settings &amp; Sheet Order', 'gsheetconnector-gravity-forms'); ?>
              </a>
            </div>

            <div class="gsc-pro-grid">
              <ul>
                <li><?php esc_html_e('Freeze header row', 'gsheetconnector-gravity-forms'); ?></li>
                <li><?php esc_html_e('Custom font styling', 'gsheetconnector-gravity-forms'); ?></li>
                <li><?php esc_html_e('Header &amp; row color control', 'gsheetconnector-gravity-forms'); ?></li>
                <li><?php esc_html_e('Sort by any column', 'gsheetconnector-gravity-forms'); ?></li>
              </ul>
            </div>
          </div>


          <div class="edit-gs-feature-col">
            <div class="mb-20">
              <a href="#gform_setting_condition">
                <?php echo esc_html(__('Conditional Logic', 'gsheetconnector-gravity-forms')); ?>
              </a>
            </div>
            <div class="gsc-pro-grid">
              <ul>
                <li><?php esc_html_e('Apply rules based on form values', 'gsheetconnector-gravity-forms'); ?></li>
                <li><?php esc_html_e('Sync data only when conditions match', 'gsheetconnector-gravity-forms'); ?></li>
                <li><?php esc_html_e('Filter unwanted or incomplete entries', 'gsheetconnector-gravity-forms'); ?></li>
                <li><?php esc_html_e('Create dynamic workflows automatically', 'gsheetconnector-gravity-forms'); ?></li>
                <li><?php esc_html_e('Map data conditionally to sheet columns', 'gsheetconnector-gravity-forms'); ?></li>
                <li><?php esc_html_e('Support multiple conditions (AND / OR)', 'gsheetconnector-gravity-forms'); ?></li>
                <li><?php esc_html_e('Improve accuracy and reduce extra data', 'gsheetconnector-gravity-forms'); ?></li>
              </ul>
            </div>
          </div>
        </div>

        <div class="edit-gs-pro-footer">
          <label for="toggle-features" class="edit-gs-show-btn show">
            <?php esc_html_e('Show Features', 'gsheetconnector-gravity-forms'); ?>
            <svg fill="#000000" width="800px" height="800px" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg">
              <path d="M0.256 8.606c0-0.269 0.106-0.544 0.313-0.75 0.412-0.412 1.087-0.412 1.5 0l14.119 14.119 13.913-13.912c0.413-0.412 1.087-0.412 1.5 0s0.413 1.088 0 1.5l-14.663 14.669c-0.413 0.413-1.088 0.413-1.5 0l-14.869-14.869c-0.213-0.213-0.313-0.481-0.313-0.756z"></path>
            </svg>
          </label>

          <label for="toggle-features" class="edit-gs-show-btn hide">
            <?php esc_html_e('Hide Features', 'gsheetconnector-gravity-forms'); ?>
            <svg width="800px" height="800px" viewBox="0 -4.5 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
              <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                <g id="Dribbble-Light-Preview" transform="translate(-260.000000, -6684.000000)" fill="#000000">
                  <g id="icons" transform="translate(56.000000, 160.000000)">
                    <path d="M223.707692,6534.63378 L223.707692,6534.63378 C224.097436,6534.22888 224.097436,6533.57338 223.707692,6533.16951 L215.444127,6524.60657 C214.66364,6523.79781 213.397472,6523.79781 212.616986,6524.60657 L204.29246,6533.23165 C203.906714,6533.6324 203.901717,6534.27962 204.282467,6534.68555 C204.671211,6535.10081 205.31179,6535.10495 205.70653,6534.69695 L213.323521,6526.80297 C213.714264,6526.39807 214.346848,6526.39807 214.737591,6526.80297 L222.294621,6534.63378 C222.684365,6535.03868 223.317949,6535.03868 223.707692,6534.63378" id="arrow_up-[#337]">
                    </path>
                  </g>
                </g>
              </g>
            </svg>
          </label>
        </div>
      </div>
    </div>
    <!-- unlock advanced feature #end -->
    <div class="pro-features">

      <!--  -->
      <!-- <a class="gs-woo-list-set" data-id="3" href="#0">
                  <p class="maxi_mize maxi_mize3"><i class="fa fa-plus" aria-hidden="true"></i></i></p>
                  <p class="mini_mize mini_mize3"><i class="fa fa-minus" aria-hidden="true"></i></p>
                </a>  -->
      <div class="main-promotion-box small-pro-box">
        <div class="promotion-inner">
          <h2><?php echo esc_html__("Please proceed to the Final Step.", "gsheetconnector-gravity-forms"); ?>
            <small><?php echo esc_html__(" (For the FREE version of the plugin, you'll need to input the columns manually.)
                    ", "gsheetconnector-gravity-forms"); ?></small>
          </h2>
          <p>
            <?php echo esc_html__("Ensure that after inputting the Google Sheet Name, Sheet ID, Tab Name, and Tab ID above, you must enter the label names of your form into Row 1 of your Google Sheet, as illustrated in the image.", "gsheetconnector-gravity-forms"); ?>
            <strong><?php echo esc_html__("Google Sheets", "gsheetconnector-gravity-forms"); ?></strong>, <br />
            <?php echo esc_html__("Now available for popular", "gsheetconnector-gravity-forms"); ?>
          </p>
          <div class="button-bar"> <a href="https://www.gsheetconnector.com/docs/gravity-forms-gsheetconnector"
              target="_blank"><?php echo esc_html__("Refer Documentation", "gsheetconnector-gravity-forms"); ?></a> | <a
              href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector"
              target="_blank"><?php echo esc_html__("Buy Now", "gsheetconnector-gravity-forms"); ?></a></div>
          <p class="note">
            <?php echo esc_html__("In PRO version it will be managed with Below settings.", "gsheetconnector-gravity-forms"); ?>
          </p>
        </div>

        <!--<img src="<?php echo esc_url(GRAVITY_GOOGLESHEET_URL . '/assets/image/gsheet-field-label-guide.gif'); ?>" alt="" />-->

        <div class="gsheet-plugins"><a
            href="https://gsheetconnector.com/wp-content/uploads/2024/04/gsheet-field-label-guide.gif"
            target="_blank"><img
              src="https://gsheetconnector.com/wp-content/uploads/2024/04/gsheet-field-label-guide.gif"
              alt="" /><?php echo esc_html__(" Click to enlarge image", "gsheetconnector-gravity-forms"); ?></a></div>
      </div> <!-- main-promotion-box #end -->


      <!-- static section start -->
     

      <div id="gform_setting_gs_sheet_select" class="gform-settings-field gform-settings-field__sheets_selection">
        <div class="gform-settings-field__header"><label class="gform-settings-label" for="gs_sheet_select"> <?php echo esc_html__("Automatic Google Sheets Configuration", "gsheetconnector-gravity-forms"); ?></label> <span class="pro-ver"><?php echo esc_html__("PRO", "gsheetconnector-gravity-forms"); ?></span>
          <p><?php echo esc_html__("Automatic configure your Google Sheets and start syncing form submissions in real time.", "gsheetconnector-gravity-forms"); ?></p>
        </div>
        <div class="gf-gs-fields">
          <div class="gsgf-free">
            <div class="row">
              <div class="col-6">
                <div class="mr-10 form-group">
                  <label>Sheet Name <button disabled class="gf_tooltip tooltip" tooltip_google_sheet_name="" aria-label="Select google sheet from your google account.">
                      <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
                    </button> </label>
                  <span class="gform-settings-input__container"><select name="_gform_setting_gs_sheet_select_name" id="gs_sheet_select_name" disabled>
                      <option value="gs-select-sheet">Select Spreadsheet</option>
                      <option value="1_eSS2Xref0o3QZgvdbFQ9FigW5dIEVO5N2fCRrqgW1Q">0</option>
                    </select></span> <span class="gform-settings-validation__error" id="error-sheetName"></span>
                  <span class="loading-sign"></span>
                </div>
              </div> <!-- col-6 #end -->

              <div class="create-ss-wrapper col-6" style="display:none" ;="">
                <label>
                  Create Spreadsheet <button class="gf_tooltip tooltip" tooltip_create_spreadsheet="" aria-label="Create a new spreadsheet to your authorized google account. ">
                    <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
                  </button> </label>
                <span class="gform-settings-input__container"><input type="text" name="_gform_setting_gs_sheet_select_create_sheet" value="" id="gs_sheet_select_create_sheet"> </span>
                <div class="mt-20"> <a id="gfgs-create-sheet" href="javascript:void(0);">
                    Create </a>
                </div>
                <span class="clear-loading-sign">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>

              </div>



              <div class="sheet-tab-name col-6">
                <div class="mr-10 form-group">
                  <label>
                    Sheet Tab Name <button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip" tooltip_google_sheet_tab_name="" aria-label="Select tab from above chosen google sheet.">
                      <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
                    </button> </label>
                  <span class="gform-settings-input__container"><select name="_gform_setting_gs_sheet_select_tab" id="gs_sheet_select_tab" disabled>
                      <option value="0" selected="selected">free</option>
                      <option value="1897537975">pro</option>
                      <option value="982369790">auto</option>
                      <option value="1251754752">NewForm</option>
                      <option value="1473881771">livesite</option>
                      <option value="1476121759">NewTest</option>
                      <option value="1588729200">stagingdemo</option>
                    </select></span>
                  <textarea id="gs_sheet_select_sheets_list" style="display:none">	  			</textarea>
                  <span class="gform-settings-validation__error" id="error-tabName"></span>
                </div>
              </div> <!-- col-6 #end -->

            </div> <!-- row #end -->
            <div class="create-ss-wrapper-message  col-6"> </div>



            <div class="sheet-url" id="sheet-url">
             
            </div>

          </div>
        </div>

      </div>

      <!-- static section #end -->

      <label id="field-mapping" class="gform-settings-label select-field-to-sync" for="gsheet_mergeTagsMap">
        <?php echo esc_html__("Select Fields to Sync", "gsheetconnector-gravity-forms"); ?>
        <?php /*?> <div class="upgrade-button">
                      <a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector?gsheetconnector-ref=17"
                      target="__blank" class="upgradeLink">
                      <?php echo esc_html__("Upgrade to Pro", "gsheetconnector-gravity-forms"); ?>
                    </a>
                    </div><?php */ ?> <span class="pro-ver">PRO</span>

      </label>
      <p><?php echo esc_html__("Enable the fields you want to send to Google Sheets and rename columns if needed.", "gsheetconnector-gravity-forms"); ?></p>
      <div class="gsgf-free">
        <span class="gform-settings-input__container">
          <div id="checkall-div" class="gform-settings-choice gform-settings-field gform-settings-field__toggle">

            <input type="hidden" name="checkall" value="0">

            <label class="gform-field__toggle-container" for="checkall">
              <span class="gform-field__toggle-switch"></span>
            </label>

            <span class="lbl_tog_field">
              <?php echo esc_html__("Check All", "gsheetconnector-gravity-forms"); ?>
            </span>
          </div>
        </span>
        <div class="ui-sortable">

          <!-- Entry ID (Disabled) -->
          <div class="gform-settings-field gform-settings-field__toggle card form-field-toggle non-sortable disable-submission-id active ui-sortable-handle">
            <div class="gform-settings-input__container">
              <div class="card-content">
                <div class="toggle-button special_mail_tags_bg">

                  <!-- Hidden (for saving) -->
                  <input type="hidden" name="entry_id" value="1">


                  <!-- Toggle UI -->
                  <label class="gform-field__toggle-container" for="entry_id">
                    <span class="gform-field__toggle-switch"></span>
                  </label>

                  <span class="lbl_tog_field">
                    <?php echo esc_html(__('Entry ID', 'gsheetconnector-gravity-forms')); ?>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <?php
          foreach ($field_list as $field) {

            $field_name = $field['field_name'];
            $field_id   = $field['field_id'];

            $checked = (isset($feed[$field_id]) && $feed[$field_id] == "1") ? "checked" : "";

            $column_name_text = array(
              'name' => $parent_field_name . "_header_cell[$field_id]",
              'type' => 'text',
              'default_value' => $field_name,
              "class" => "gaddon-text",
            );

            $create_sheet_field = $this->settings_text($column_name_text, false);
          ?>

            <!-- FIELD TOGGLE -->
            <div class="gform-settings-input__container card form-field-toggle non-sortable disable-submission-id active ui-sortable-handle">
              <div class="card-content">
                <div class="toggle-button">

                  <div class="gform-settings-choice gform-settings-field gform-settings-field__toggle">

                    <!-- Hidden (GF save support) -->
                    <input type="hidden"
                      name="_gform_setting_gsheet_field_maps_enable[<?php echo esc_attr($field_id); ?>]"
                      value="<?php echo isset($feed[$field_id]) ? esc_attr($feed[$field_id]) : 0; ?>">


                    <!-- Toggle UI -->
                    <label class="gform-field__toggle-container"
                      for="gsheet_field_maps_enable<?php echo esc_attr($field_id); ?>">
                      <span class="gform-field__toggle-switch"></span>
                    </label>

                    <!-- Label -->
                    <span class="lbl_tog_field">
                      <?php echo esc_html($field_name); ?>
                    </span>
                    <input type="text" name="" value="<?php echo esc_html($field_name); ?>" class="gaddon-text-free" id="" readonly>

                  </div>

                </div>
              </div>
            </div>

            <!-- FIELD INPUT -->
            <div class="row_grvt"
              data-id="gsheet_field_maps_enable<?php echo esc_attr($field_id); ?>"
              style="<?php echo $checked ? '' : 'display:none;'; ?>">
              <?php echo wp_kses_post($create_sheet_field); ?>
            </div>
          <?php } ?>
        </div>
      </div><!-- gsgf-free #end -->

      <div id="_gform_setting_gsheet_mergeTagsMap_container" class="gform-settings-field-map__container gform-settings-field__header"
        title="Upgrade to Pro">

        <div class="gform-settings-field__header "><label class="gform-settings-label" for="gsheet_mergeTagsMap">Custom Tags </label><button onkeypress="return false;" class="gf_tooltip tooltip" aria-label="Enter the exact name of your Google Spreadsheet (as shown in Google Sheets).">
            <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
          </button></div>

        <table class="gform-settings-generic-map__table" cellspacing="0" cellpadding="0">

          <tbody>
            <tr class="gform-settings-generic-map__row">

              <th
                class="gform-settings-generic-map__column gform-settings-generic-map__column--heading gform-settings-generic-map__column--key">
                <?php echo esc_html__("Column Name", "gsheetconnector-gravity-forms"); ?>
              </th>
              <th
                class="gform-settings-generic-map__column gform-settings-generic-map__column--heading gform-settings-generic-map__column--value">
                <?php echo esc_html__("Merge Tag / Formulas", "gsheetconnector-gravity-forms"); ?>
              </th>
              <th
                class="gform-settings-generic-map__column gform-settings-generic-map__column--heading gform-settings-generic-map__column--error">
              </th>
              <th
                class="gform-settings-generic-map__column gform-settings-generic-map__column--heading gform-settings-generic-map__column--buttons">
              </th>
            </tr>
            <tr class="gform-settings-generic-map__row">
              <td class="gform-settings-generic-map__column gform-settings-generic-map__column--key">
                <span class="gform-settings-generic-map__custom">
                  <input id="_gform_setting_gsheet_mergeTagsMap_custom_key_0" type="text" placeholder=""
                    value="gsheetconnector gravity pro" disabled>
                </span>
              </td>
              <td class="gform-settings-generic-map__column gform-settings-generic-map__column--value">
                <select id="_gform_setting_gsheet_mergeTagsMap_custom_value_0" class="" disabled>
                  <option value=""><?php echo esc_html__("Select a Field", "gsheetconnector-gravity-forms"); ?> </option>
                  <optgroup label="Form Fields">
                    <option value="1"><?php echo esc_html__("Untitled", "gsheetconnector-gravity-forms"); ?> </option>
                    <option value="3"><?php echo esc_html__("Untitled", "gsheetconnector-gravity-forms"); ?> </option>
                  </optgroup>
                  <optgroup label="Entry Properties">
                    <option value="id"><?php echo esc_html__("Entry ID", "gsheetconnector-gravity-forms"); ?> </option>
                    <option value="date_created"><?php echo esc_html__("Entry Date", "gsheetconnector-gravity-forms"); ?>
                    </option>
                    <option value="ip"><?php echo esc_html__("User IP", "gsheetconnector-gravity-forms"); ?> </option>
                    <option value="source_url"><?php echo esc_html__("Source Url", "gsheetconnector-gravity-forms"); ?>
                    </option>
                    <option value="form_title"><?php echo esc_html__("Form Title", "gsheetconnector-gravity-forms"); ?>
                    </option>
                  </optgroup>
                  <option value="gf_custom"><?php echo esc_html__("Add Custom Value", "gsheetconnector-gravity-forms"); ?>
                  </option>
                </select>
              </td>
              <td class="gform-settings-generic-map__column gform-settings-generic-map__column--error"></td>
              <td class="gform-settings-generic-map__column gform-settings-generic-map__column--buttons">
                <button
                  class="add_field_choice gform-st-icon gform-st-icon--circle-plus gform-settings-generic-map__button gform-settings-generic-map__button--add"
                  disabled>
                  <span class="screen-reader-text"><?php echo esc_html__("Add", "gsheetconnector-gravity-forms"); ?>
                  </span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <!--  pro feature #end -->



      <div class="gsgf-free ">

        <div id="header-settings-sheet-sorting" class="gform-settings-field gform-settings-field__toggle pro-field">

          <div class="gform-settings-field__header">
            <label class="gform-settings-label font-size-18" for="gsheet_freeze_header_toggle">
              <?php echo esc_html__("Header Settings", "gsheetconnector-gravity-forms"); ?>
              <button
                onclick="return false;"
                onkeypress="return false;"
                class="gf_tooltip tooltip"
                tooltip_gsheet_headers=""
                aria-label="Control formatting of header. ">
                <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
              </button>


              <span class="pro-ver"><?php echo esc_html__("PRO", "gsheetconnector-gravity-forms"); ?></span>

            </label>
            <p>
              <label class="gform-settings-label font-size-14" for="gsheet_freeze_header_toggle">
                <?php echo esc_html__("Customize the appearance and behavior of your sheet headers and rows for better readability and organization.", "gsheetconnector-gravity-forms"); ?>
              </label>
            </p>



          </div>


          <div class="gform-settings-field__header-undot">
            <label class="gform-settings-label" for="gsheet_freeze_header_toggle">
              <?php echo esc_html__("Freeze Header", "gsheetconnector-gravity-forms"); ?>
              <button
                onclick="return false;"
                onkeypress="return false;"
                class="gf_tooltip tooltip"
                tooltip_gsheet_headers=""
                aria-label="Control formatting of header. Check freeze header if you want to freeze first row considered as header.">
                <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
              </button>

            </label>




            <label class="gform-field__toggle-container" for="_gform_setting_gsc_feed_condition_conditional_logic_toggle">
              <span class="screen-reader-text">
                Condition&nbsp;
                <button
                  onclick="return false;"
                  onkeypress="return false;"
                  class="gf_tooltip tooltip"
                  tooltip_gsheet_sorting
                  aria-label="Set up this field if you want data to be sorted automatically upon submission based on the column.">
                  <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
                </button>
              </span>

              <span class="gform-field__toggle-switch"></span>
            </label>

          </div>

          <div class="gform-settings-field__header-undot">
            <label class="gform-settings-label" for="gsheet_freeze_header_toggle">
              <?php echo esc_html__("Header Style", "gsheetconnector-gravity-forms"); ?>
              <button
                onclick="return false;"
                onkeypress="return false;"
                class="gf_tooltip tooltip"
                tooltip_gsheet_headers=""
                aria-label="Customize the font size and style of your Google Sheet header row.">
                <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
              </button>
            </label>




            <label class="gform-field__toggle-container" for="_gform_setting_gsc_feed_condition_conditional_logic_toggle">
              <span class="screen-reader-text">
                Condition&nbsp;
                <button
                  onclick="return false;"
                  onkeypress="return false;"
                  class="gf_tooltip tooltip"
                  tooltip_gsheet_sorting
                  aria-label="Set up this field if you want data to be sorted automatically upon submission based on the column.">
                  <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
                </button>
              </span>

              <span class="gform-field__toggle-switch"></span>
            </label>

          </div>


        </div>

        <div class="gform-settings-field__header-undot gform-settings-field__toggle pro-field">

          <label class="gform-settings-label" for="gsheet_freeze_header_toggle">
            <?php echo esc_html__("Color Appearance", "gsheetconnector-gravity-forms"); ?>
            <button
              onclick="return false;"
              onkeypress="return false;"
              class="gf_tooltip tooltip"
              tooltip_gsheet_headers=""
              aria-label="Control formatting of header. Check freeze header if you want to freeze first row considered as header.">
              <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
            </button>
          </label>


          <label class="gform-field__toggle-container" for="_gform_setting_gsheet_alternate_colors_enabled_toggle">
            <span class="screen-reader-text">
              <?php echo esc_html__("Colors", "gsheetconnector-gravity-forms"); ?> <span class="pro-ver"><?php echo esc_html__("PRO", "gsheetconnector-gravity-forms"); ?></span>
              <button
                onclick="return false;"
                onkeypress="return false;"
                class="gf_tooltip tooltip"
                tooltip_gsheet_colors
                aria-label="Customize the background colors for odd and even rows, along with the header row background.">
                <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
              </button>
            </span>
            <span class="gform-field__toggle-switch"></span>
          </label>
        </div>



        <div id="gform_setting_gsheet_sort_column_enabled_toggle"
          class="gform-settings-field gform-settings-field__toggle pro-field disabled">
          <div class="gform-settings-field__header">
            <label class="gform-settings-label"
              for="gsheet_sort_column_enabled_toggle"><?php echo esc_html__("Sheet Sorting", "gsheetconnector-gravity-forms"); ?>
            </label> <a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector"></a>
            <button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip" aria-label="The Enable Conditional Logic option in the field settings allows you to create rules to dynamically display or hide the submission to google sheet based on values">
              <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
            </button> <span class="pro-ver"><?php echo esc_html__("PRO", "gsheetconnector-gravity-forms"); ?></span>
            <span class="gform-settings-input__container">
              <input type="checkbox" name="_gform_setting_gsheet_sort_column_enabled_toggle"
                id="_gform_setting_gsheet_sort_column_enabled_toggle" value="1" disabled> </span>

            <label class="gform-field__toggle-container" for="_gform_setting_gsc_feed_condition_conditional_logic_toggle">
              <span class="screen-reader-text">
                <?php echo esc_html__("Condition", "gsheetconnector-gravity-forms"); ?>
                
                <button
                  onclick="return false;"
                  onkeypress="return false;"
                  class="gf_tooltip tooltip"
                  tooltip_gsheet_sorting
                  aria-label="The Enable Conditional Logic option in the field settings allows you to create rules to dynamically display or hide the submission to google sheet based on values">
                  <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
                </button>


              </span>
              <span class="gform-field__toggle-switch"></span>
            </label>

          </div>
          <div id="gform_setting_gsheet_sort_column"
            class="gform-settings-field gform-settings-field__gsheet_sorting disabled">
            <span class="gform-settings-input__container">
              <div id="gform-settings-checkbox-choice-gsheet_sort_column_enabled"
                class="gform-settings-choice gform-settings-choice--inline">
                <input type="hidden" name="_gform_setting_gsheet_sort_column_enabled" value="1">
                <input type="checkbox" data_format="bool" horizontal="1" id="gsheet_sort_column_enabled"
                  name="gsheet_sort_column_enabled" disabled>
              </div>
            </span>
            <table class="sort-col-text-field gsheet-table two-col" style="">
              <tbody>
                <tr>
                  <td>
                    <label><?php echo esc_html__("Sort Column Name", "gsheetconnector-gravity-forms"); ?>&nbsp;</label>
                    <button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip" tooltip_integration_mode="" aria-label="Selection of chosing google sheet for data submission.">
                      <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
                    </button>
                  </td>
                  <td>
                    <span class="gform-settings-input__container">
                      <input type="text" name="_gform_setting_gsheet_sort_column_name" value="" id="gsheet_sort_column_name"
                        disabled>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td>
                    <label><?php echo esc_html__("Order", "gsheetconnector-gravity-forms"); ?>&nbsp;</label>
                    <button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip" tooltip_integration_mode="" aria-label="Selection of chosing google sheet for data submission.">
                      <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
                    </button>
                  </td>
                  <td>
                    <span class="gform-settings-input__container">
                      <select name="_gform_setting_gsheet_sort_column_order" id="gsheet_sort_column_order" disabled>
                        <option value="ASCENDING"><?php echo esc_html__("Ascending", "gsheetconnector-gravity-forms"); ?>
                        </option>
                        <option value="DESCENDING"><?php echo esc_html__("Descending", "gsheetconnector-gravity-forms"); ?>
                        </option>
                      </select>
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>


          <div id="gform_setting_condition" class="gform-settings-field gform-settings-field__conditional_logic">
            <div class="gform-settings-field__header"><label class="gform-settings-label" for="condition"> <?php echo esc_html__("Condition", "gsheetconnector-gravity-forms"); ?></label> <button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip" aria-label="The Enable Conditional Logic option in the field settings allows you to create rules to dynamically display or hide the submission to google sheet based on values">
                <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
              </button> <span class="pro-ver"><?php echo esc_html__("PRO", "gsheetconnector-gravity-forms"); ?></span>

              <label class="gform-field__toggle-container" for="_gform_setting_gsc_feed_condition_conditional_logic_toggle">
                <span class="screen-reader-text">
                  <?php echo esc_html__("Condition", "gsheetconnector-gravity-forms"); ?>
                  <button
                    onclick="return false;"
                    onkeypress="return false;"
                    class="gf_tooltip tooltip"
                    tooltip_gsheet_sorting
                    aria-label="Set up this field if you want data to be sorted automatically upon submission based on the column.">
                    <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
                  </button>
                </span>

                <span class="gform-field__toggle-switch"></span>
              </label>


            </div>




            <span class="gform-settings-input__container">
              <input type="hidden" name="_gform_setting_feed_condition_conditional_logic_object" value="{}" id="feed_condition_conditional_logic_object">


          </div>



          <div id="gform_setting_gsheet_sync_entries"
            class="gform-settings-field gform-settings-field__display_sync_entries disabled">

           


            <div class="gform-settings-field__header">
              <P>
                <label class="gform-settings-label" for="gsheet_sync_entries">
                  <?php echo esc_html__("Spreadsheet Download", "gsheetconnector-gravity-forms"); ?>
                </label>
               </P>
               <P class="gscgff-download-btn">
                <a href="#" class="gscgff-sdownload-button">
                  <i class="fa-regular fa-file-zipper text-dark fw-500 mr-5">
                  </i>  <?php echo esc_html__("Download", "gsheetconnector-gravity-forms"); ?>
                </a>
              </P>
            


              <label class="gform-settings-label gsheet_sync_entries" for="gsheet_sync_entries">
                <?php echo esc_html__("Google Sheets Data Sync", "gsheetconnector-gravity-forms"); ?>
              </label>
              <a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector"></a>
              <button
                onclick="return false;"
                onkeypress="return false;"
                class="gf_tooltip tooltip"
                aria-label="Enter the exact name of your Google Spreadsheet (as shown in Google Sheets).">
                <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
              </button> <span class="pro-ver"> <?php echo esc_html__("PRO", "gsheetconnector-gravity-forms"); ?> </span>
            </div>

            <input type="hidden" name="gf-ajax-sync-entries-nonce" id="gf-ajax-sync-entries-nonce" value="6dfefffeda">

            <div class="gs-sync-row mt-20">
              <a id="gfgs-entires-sync-launch" class="gfgs-fetch btn text-decoration-none" data-form_id="1" data-feed_id="5">
                <?php echo esc_html__("Sync Entries", "gsheetconnector-gravity-forms"); ?>
              </a>
            </div>

          </div>


        </div>
      </div>


  <?php
  }
  /** 
   * Gforms_Gsheet_Connector_Feeds::get_form_field_list
   * Fetch all form fields and format them in array
   * @since 4.0 
   * @param array $form
   * @return array $field_list
   **/
  public function get_form_field_list($form)
  {
    $fields = $form['fields'];
    $fields_inputs = array("name", "address", "consent", "product");

    $field_list = array();

    foreach ($fields as $field) {

      $field_name = $field->label;
      $field_id = $field->id;

      $type = $field->type;

      $field_list[] = array(
        "field_id" => $field_id,
        "field_name" => $field_name
      );

      if (in_array($type, $fields_inputs)) {
        $inputs = $field->inputs;
        foreach ($inputs as $key => $value) {
          if (isset($value['isHidden']) && $value['isHidden'] == 1) {
            continue;
          }
          $field_id = str_replace(".", "_", $value['id']);
          $field_name = $value['label'];
          $field_list[] = array(
            "field_id" => $field_id,
            "field_name" => $field_name
          );
        }
      }
    }

    $field_list = apply_filters("gcgf_form_field_list", $field_list, $form);
    return $field_list;
  }



  public function get_field_list($form_meta)
  {

    $field_list = array();

    $fields_inputs = array("name", "address", "consent", "product");

    $data_meta = $form_meta['fields'];
    foreach ($data_meta as $field_meta) {

      $field_name = $field_meta->label;
      $field_id = $field_meta->id;
      $field_list[] = array(
        "field_id" => $field_id,
        "field_name" => $field_name
      );

      $type = $field_meta->type;
      if (in_array($type, $fields_inputs)) {
        $inputs = $field_meta->inputs;
        foreach ($inputs as $key => $value) {
          if (isset($value['isHidden']) && $value['isHidden'] == 1) {
            continue;
          }
          $field_id = $value['id'];
          $field_name = $value['label'];
          $field_list[] = array(
            "field_id" => $field_id,
            "field_name" => $field_name
          );
        }
      }
    }
    return $field_list;
  }


  /**
   * Set Google Sheet settings with GravityForms
   * @since 1.0
   */
  public function after_save_form_settings()
  {
    if (isset($_POST['gform-settings-save'])) {
      $gravityform_tags = array();
      $form_id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;

      // echo '<pre>';print_r($_POST);die;
      $get_existing_data = get_post_meta($form_id, 'gfgs_settings');
      // get sheet name and tab name
      $sheet_name = isset($_POST['_gform_setting_gf-gs-sheet-name'])
        ? sanitize_text_field(wp_unslash($_POST['_gform_setting_gf-gs-sheet-name']))
        : '';

      $tab_name = isset($_POST['gf-gs']['sheet-tab-name'])
        ? sanitize_text_field(wp_unslash($_POST['gf-gs']['sheet-tab-name']))
        : '';

      $sheet_id = isset($_POST['gf-gs']['sheet-id'])
        ? sanitize_text_field(wp_unslash($_POST['gf-gs']['sheet-id']))
        : '';

      $tab_id = isset($_POST['gf-gs']['tab-id'])
        ? sanitize_text_field(wp_unslash($_POST['gf-gs']['tab-id']))
        : '';


      $form_meta = RGFormsModel::get_form_meta($form_id);
      $field_list = $this->get_field_list($form_meta);

      if (!empty($field_list)) {
        foreach ($field_list as $field_data) {
          $field_name = $field_data['field_name'];
          $field_id = $field_data['field_id'];
        }
      }
      if (!empty($get_existing_data) && $sheet_name === '') {
        update_post_meta($form_id, 'gfgs_settings', '');
      }


      if (!empty($sheet_name) && !empty($tab_name) && isset($_POST['gf-gs']) && is_array($_POST['gf-gs'])) {
        $raw_data = wp_unslash($_POST['gf-gs']);
        $sanitized_data = array_map('sanitize_text_field', $raw_data);
        update_post_meta($form_id, 'gfgs_settings', $sanitized_data);
      }
    }
  }

  public function feed_list_columns()
  {
    return array(
      'feedName' => esc_html__('Name', 'gsheetconnector-gravity-forms'),
    );
  }

  /**
   * Insert data into Google Spreadsheet after form submission.
   *
   * @access public
   * @return array $row
   */
  public function after_submission($entry, $form)
  {
    // Iterate over all fields and dynamically extract values
    foreach ($form['fields'] as $field) {
      $field_type = $field->type;
      $field_label = $field->label;
      $value = '';

      // Handle multi-input fields like 'product'
      if (is_array($field->inputs)) {
        $inputs = $field->inputs;

        if ($field_type === 'product' && isset($inputs[2])) {
          // Grab Quantity input (usually 3rd subfield)
          $quantity_id = (string) $inputs[2]['id'];
          $value = rgar($entry, $quantity_id);
        } else {
          // Log all subfield values
          foreach ($inputs as $input) {
            $sub_id = (string) $input['id'];
            $sub_value = rgar($entry, $sub_id);
          }
        }
      } else {
        $value = rgar($entry, (string) $field->id);
      }
    }

    // Static settings from post_meta (old method)
    $form_id = $form['id'];
    $static_settings = get_post_meta($form_id, 'gfgs_settings');

    if ($static_settings && isset($static_settings[0]['sheet-id'])) {
      $sheetId = $static_settings[0]['sheet-id'];
      $sheet_name = $static_settings[0]['sheet-name'] ?? '';
      $tabid = $static_settings[0]['tab-id'] ?? '';
      $sheet_tab_name = $static_settings[0]['sheet-tab-name'] ?? '';

      // error_log("⚙️ Static Sheet Settings Found — Sending to Sheet");
      $this->send_entry($sheetId, $sheet_name, $tabid, $sheet_tab_name, $entry, $form);
    } else {
    }

    // Dynamic feed-based integration
    $feeds = $this->get_active_feeds($form['id']);


    $processable_feeds = [];
    foreach ($feeds as $feed) {
      $feed_data = $feed['meta'];
      $feed_id = $feed['id'];

      $is_condition_enabled = rgar($feed_data, 'feed_condition_conditional_logic');
      if (!$is_condition_enabled) {
        $processable_feeds[] = $feed;
      } else {
        $logic = rgars($feed_data, 'feed_condition_conditional_logic_object/conditionalLogic');
        if (!empty($logic) && GFCommon::evaluate_conditional_logic($logic, $form, $entry)) {
          $processable_feeds[] = $feed;
        }
      }
    }

    $processable_feeds = apply_filters("gcgf_processable_feeds", $processable_feeds, $entry, $form);

    if (!empty($processable_feeds)) {
      foreach ($processable_feeds as $feed) {
        $settings = $feed['meta'];
        $sheetId = $settings['gf-gs-sheet-id'] ?? '';
        $tabid = $settings['gf-gs-tab-id'] ?? '';
        $sheet_tab_name = $settings['gf-gs-sheet-tab-name'] ?? '';
        $sheet_name = $settings['gf-gs-sheet-name'] ?? '';

        $this->send_entry($sheetId, $sheet_name, $tabid, $sheet_tab_name, $entry, $form);
      }
    }
  }




  // since v-1.0.19
  public function send_entry($sheetId, $sheet_name, $tabid, $sheet_tab_name, $entry, $form)
  {
    $form_id = $form['id'];
    $entry_id = $entry['id'];





    $utc_date = $entry['date_created'];


    $date = new DateTime($utc_date, new DateTimeZone('UTC'));
    $date->setTimezone(wp_timezone());

    $Date = wp_date(
      get_option('date_format') . ' ' . get_option('time_format'),
      $date->getTimestamp(),
      wp_timezone()
    );

    if ($sheet_name !== "" && $sheet_tab_name !== "") {
      try {
        include_once(GRAVITY_GOOGLESHEET_ROOT . "/lib/google-sheets.php");
        $doc = new Gfgscf_googlesheet();
        $doc->auth();
        $doc->setSpreadsheetId($sheetId);
        $doc->setWorkTabId($tabid);

        $data_value['Entry Date'] = $Date;
        $data_value['date'] = $Date;
        $data_value['Date'] = $Date;
        $data_value['Submission Date'] = $Date;
        $data_value['Entry ID'] = $entry_id;

        foreach ($form['fields'] as $field) {
          $label = $field->label;
          $value = is_object($field) ? $field->get_value_export($entry) : '';
          $raw_value = isset($entry[$field->id]) ? $entry[$field->id] : '';




          /*  Name Field */
          if ($field->type == 'name' && isset($field->inputs) && !empty($field->inputs)) {
            foreach ($field->inputs as $input) {
              if (isset($input['isHidden']) && $input['isHidden'] == true) {
                continue;
              }

              $subfield_id = (string) $input['id'];
              $subfield_value = rgar($entry, $subfield_id);

              // Map to exact sheet column header names
              $label_map = [
                'First' => 'First',   // <-- change right side to match your sheet header exactly
                'Last'  => 'Last',    // <-- change right side to match your sheet header exactly
              ];

              $subfield_label = isset($input['customLabel']) && !empty($input['customLabel'])
                ? $input['customLabel']
                : $input['label'];

              $mapped_label = isset($label_map[$subfield_label]) ? $label_map[$subfield_label] : $subfield_label;
              $data_value[$mapped_label] = $subfield_value;
            }
          }


          /*  Address Field */
          if ($field->type == 'address' && isset($field->inputs) && !empty($field->inputs)) {
            foreach ($field->inputs as $input) {
              if (isset($input['isHidden']) && $input['isHidden'] == true) {
                continue;
              }
              $subfield_id    = (string) $input['id'];
              $subfield_label = isset($input['customLabel']) && !empty($input['customLabel'])
                ? $input['customLabel']
                : $input['label'];
              $subfield_value = rgar($entry, $subfield_id);

              if (!empty($subfield_label)) {
                $data_value[$subfield_label] = $subfield_value;
              }
            }

            // Populate the combined 'Address' column
            $data_value['Address'] = $field->get_value_export($entry);
          }

          if ($field->type == 'fileupload') {
            $field_id   = (string) $field->id;
            $raw_value  = rgar($entry, $field_id);

            // Parse JSON array if needed (e.g. ["http://...\/file.jpg"])
            $decoded = json_decode($raw_value, true);
            if (is_array($decoded)) {
              $file_value = implode(', ', $decoded); // multiple files joined, or use $decoded[0] for first only
            } else {
              $file_value = $raw_value; // plain URL string
            }

            $field_label = isset($field->label) ? $field->label : 'File';
            $data_value[$field_label] = $file_value;
          }
          /*  Checkbox Field */ else if ($field->type == 'checkbox' && isset($field->inputs)) {
            $checkbox_values = [];
            foreach ($field->inputs as $input) {
              $checkbox_id = $input['id'];
              if (isset($entry[$checkbox_id]) && !empty($entry[$checkbox_id])) {
                $checkbox_values[] = $entry[$checkbox_id];
              }
            }
            $data_value[$label] = implode(', ', $checkbox_values);
          }
          /*  Dropdown / Select */ else if ($field->type == 'select') {
            $data_value[$label] = isset($entry[$field->id]) ? $entry[$field->id] : '';
          }
          /*  File Upload */ else if ($field->type == 'fileupload') {
            $file_url = isset($entry[$field->id]) ? $entry[$field->id] : '';
            $data_value[$label] = !empty($file_url) ? $file_url : 'No file uploaded';
          }


          /*  Consent Field */ else if ($field->type == 'consent') {
            if (isset($field->checkboxLabel) && !empty($field->checkboxLabel)) {
              $data_value[$label] = $field->checkboxLabel;
            }
          }
          /*  Catch-all for other fields */ else if ($field->type == 'product' && isset($field->inputs)) {
            $quantity_input = $field->inputs[2]['id'] ?? null;
            $quantity_value = $quantity_input ? rgar($entry, $quantity_input) : '';
            $data_value[$label] = $quantity_value;
          } else if ($field->type == 'date') {

            $date_value = isset($entry[$field->id]) ? $entry[$field->id] : '';
            if (!empty($date_value)) {

              $format_map = [
                'mdy'        => 'm/d/Y',
                'dmy'        => 'd/m/Y',
                'dmy_dash'   => 'd-m-Y',
                'dmy_dot'    => 'd.m.Y',
                'ymd_slash'  => 'Y/m/d',
                'ymd_dash'   => 'Y-m-d',
                'ymd_dot'    => 'Y.m.d',
              ];

              if (!empty($field->dateFormat) && isset($format_map[$field->dateFormat])) {
                // Use the format set in the Gravity Forms field
                $php_format = $format_map[$field->dateFormat];
                $data_value[$label] = date($php_format, strtotime($date_value));
              } else {
                // Fallback: use WordPress site date format
                $data_value[$label] = date_i18n(get_option('date_format'), strtotime($date_value));
              }
            } else {
              $data_value[$label] = '';
            }
          } else if ($field->type == 'time') {

            $time_value = isset($entry[$field->id]) ? $entry[$field->id] : '';
            if (!empty($time_value)) {

                // Check the time format configured on the Gravity Forms field
                // $field->timeFormat is '12' or '24' — fall back to WP setting if not set
                $gf_time_format = isset($field->timeFormat) ? $field->timeFormat : '';

                if ($gf_time_format === '24') {
                    $time_format = 'H:i';        // 24-hour: 14:30
                } elseif ($gf_time_format === '12') {
                    $time_format = 'g:i A';      // 12-hour: 2:30 PM
                } else {
                    $time_format = get_option('time_format'); // WordPress global fallback
                }

                $data_value[$label] = date_i18n($time_format, strtotime($time_value));

            } else {
                $data_value[$label] = '';
            }

        } else {
            $final_value = !empty($value) ? $value : $raw_value;
            $data_value[$label] = $final_value;
        }
        }


        if ($field->type == 'post_image') {
          $field_id  = (string) $field->id;
          $raw_value = rgar($entry, $field_id);

         
          $parts      = explode('|:|', $raw_value);
          $file_value = isset($parts[0]) ? esc_url(trim($parts[0])) : '';

          $field_label = isset($field->label) ? $field->label : 'Post Image';
          $data_value[$field_label] = $file_value;
        }


        $doc->add_row($data_value);
      } catch (Exception $e) {
        $error_message = $e->getMessage();
        $data['ERROR_MSG'] = $error_message;
        $data['TRACE_STK'] = $e->getTraceAsString();
        GravityForms_GsFree_Connector_Utility::gfgs_debug_log($data);
      }
    }
  }


  //add nonce
  public function add_gf_nonce()
  {
    wp_nonce_field('gf-ajax-nonce', 'gf-ajax-nonce');
  }
}
