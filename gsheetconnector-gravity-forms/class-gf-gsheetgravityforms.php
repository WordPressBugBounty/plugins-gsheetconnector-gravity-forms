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
  protected $_short_title = 'Google Sheet';
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

  $form_data = get_post_meta($form_id, 'gfgs_settings', true);
  $sheet_data = get_option('gfgs_feeds');
  $authenticated = get_option('gfgs_token');
  $per = get_option('gfgs_verify');


    // Check if the user is authenticated when saving via existing API method
  if (!empty($authenticated) && $per === "valid") {
    $fields['feed_settings'] = array(
      'title' => esc_html__('GSheetConnector Gravity Forms Feed Settings', 'gsheetconnector-gravity-forms'),
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
          'label' => esc_html__('Manually Google Sheets Configuration', 'gsheetconnector-gravity-forms'),
          'class' => 'gra-heading',
          'type' => 'display_sheet_details',
        ),
//          array(
//            'name' => 'condition',
//            'label' => esc_html__('Condition', 'gsheetconnector-gravity-forms'),
//            'type' => 'feed_condition',
//            'checkbox_label' => esc_html__('Enable Condition', 'gsheetconnector-gravity-forms'),
//            'instructions' => esc_html__('Process this feed if', 'gsheetconnector-gravity-forms'),
//          ),
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
        <div class="feed-alert-header"><?php esc_html_e('Google Sheets Setup Required', 'gsheetconnector-gravityforms-pro'); ?></div>
        <p><?php esc_html_e('your selected Method is : ', 'gsheetconnector-gravityforms-pro'); ?>Existing Client / Secret Key (Auto Setup).</p>
        <p><?php esc_html_e('To start sending form entries to Google Sheets, please connect your Google account first.', 'gsheetconnector-gravityforms-pro'); ?></p>
        <ul>
          <li><?php esc_html_e('✔ Click on the Sign in with Google button', 'gsheetconnector-gravityforms-pro'); ?></li>
          <li><?php esc_html_e('✔ Log in using your Google account', 'gsheetconnector-gravityforms-pro'); ?></li>
          <li><?php esc_html_e('✔ Select the Google account where your Sheets are stored', 'gsheetconnector-gravityforms-pro'); ?></li>
          <li><?php esc_html_e('✔ Grant access to: Google Drive & Google Sheets', 'gsheetconnector-gravityforms-pro'); ?></li>
          <li><?php esc_html_e('✔ Save the authentication code if prompted', 'gsheetconnector-gravityforms-pro'); ?></li>
        </ul>
        <a href="admin.php?page=gf_googlesheet&tab=integration" class="gscgff-alert-btn link-hover-white">
          <?php esc_html_e('Go to Integration Setup', 'gsheetconnector-gravityforms-pro'); ?>
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
//    $text_field = array(
//      'name' => $field_name . '-tab-id',
//      'label' => esc_html__('Google Sheet URL', 'gsheetconnector-gravity-forms'),
//      'type' => 'text',
//      'value' => isset($get_data[0]['tab-id']) ? esc_attr($get_data[0]['tab-id']) : '',
//    );

  $tab_id_text_field = $this->settings_text($text_field, false);


    // echo '<pre>';print_r( $tab_id_text_field);die;
  ?>



    <?php /*?><div class="guide">
      <span><?php esc_html_e('Screenshot below for Google Sheet settings', 'gsheetconnector-gravity-forms'); ?></span>
      <a href="<?php echo esc_url('https://www.gsheetconnector.com/wp-content/uploads/2024/04/google-sheet-setting.png'); ?>"
        target="_blank">
        <img
        src="<?php echo esc_url('https://www.gsheetconnector.com/wp-content/uploads/2024/04/google-sheet-setting.png'); ?>"
        alt="<?php esc_attr_e('Google Sheet settings screenshot', 'gsheetconnector-gravity-forms'); ?>" />
        <?php esc_html_e('Click to enlarge image', 'gsheetconnector-gravity-forms'); ?>
      </a>
      </div><?php */?>

      <?php
      $settings = $this->get_feed($this->get_current_feed_id());
      $feed = isset($settings['meta']['gsheet_field_maps_enable']) ? $settings['meta']['gsheet_field_maps_enable'] : array(); ?>

      <a class="sheet-url-fluentform common-sheet-url btn mr-10 text-dark text-decoration-none mt-30 blinking-button" href="#" target="_blank"></a>

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
    <!-- <label><span><input type="checkbox" class="entry_id_chk" checked disabled>Entry ID</span></label></td>
      <td><input type="text" name="entry_id" value="Entry ID" class="gaddon-text" disabled> -->
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
            aria-label='Paste the Spreadsheet ID from your Google Sheet URL. (Example:  https://docs.google.com/spreadsheets/d/**SPREADSHEET_ID**/edit)'>
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
    <?php /*?><span class="required">(Required)</span><?php */?>

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

<?php /*?><p class="sheet_url" id="sheet_url"></p><?php */?>
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
    <?php esc_html_e('Unlock Advanced Features with Form Feeds', 'gsheetconnector-gravityforms-pro'); ?>
  </div>

  <div class="d-flex align-items-center gap-15">
    <span class="edit-gs-pro-badge">
     <?php esc_html_e('FREE users get special upgrade pricing', 'gsheetconnector-gravityforms-pro'); ?>
   </span>

   <span class="edit-gs-upgrade-btn">
     <a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector" target="_blank" class="text-decoration-none link-hover-white">
      <?php esc_html_e('Get Advanced Features', 'gsheetconnector-gravityforms-pro'); ?>
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
    <a href="#auto-googlesheet-configuration">
     <?php esc_html_e('Automatically Google Sheet Configuration', 'gsheetconnector-gravityforms-pro'); ?>
   </a>
 </div>

 <div class="gsc-pro-grid">
  <ul>
   <li><?php esc_html_e('Auto fetch Google Sheets list', 'gsheetconnector-gravityforms-pro'); ?></li>
   <li><?php esc_html_e('Auto detect sheet tabs', 'gsheetconnector-gravityforms-pro'); ?></li>
   <li><?php esc_html_e('One-click configuration', 'gsheetconnector-gravityforms-pro'); ?></li>
   <li><?php esc_html_e('Real-time entry sync', 'gsheetconnector-gravityforms-pro'); ?></li>
 </ul>
</div>
</div>

<div class="edit-gs-feature-col">
 <div class="mb-20">
  <a href="#field-mapping">
   <?php esc_html_e('Select Fields to Sync', 'gsheetconnector-gravityforms-pro'); ?>
 </a>
</div>
<div class="gsc-pro-grid">
  <ul>
   <li><?php esc_html_e('Drag & drop field reordering', 'gsheetconnector-gravityforms-pro'); ?></li>
   <li><?php esc_html_e('Rename column headers', 'gsheetconnector-gravityforms-pro'); ?></li>
   <li><?php esc_html_e('Select specific fields to sync', 'gsheetconnector-gravityforms-pro'); ?></li>
 </ul>
</div>
</div>

<div class="edit-gs-feature-col">
 <div class="mb-20">
  <a href="#header-settings-sheet-sorting">
   <?php esc_html_e('Header Settings &amp; Sheet Sorting', 'gsheetconnector-gravityforms-pro'); ?>
 </a>
</div>

<div class="gsc-pro-grid">
  <ul>
   <li><?php esc_html_e('Freeze header row', 'gsheetconnector-gravityforms-pro'); ?></li>
   <li><?php esc_html_e('Custom font styling', 'gsheetconnector-gravityforms-pro'); ?></li>
   <li><?php esc_html_e('Header &amp; row color control', 'gsheetconnector-gravityforms-pro'); ?></li>
   <li><?php esc_html_e('Sort by any column', 'gsheetconnector-gravityforms-pro'); ?></li>
 </ul>
</div>
</div>

<div class="edit-gs-feature-col">
 <div class="mb-20">
  <a href="#spreadsheet-download-sync">
   <?php esc_html_e('Spreadsheet Download', 'gsheetconnector-gravityforms-pro'); ?>
 </a>
</div>

<div class="gsc-pro-grid">
  <ul>
   <li><?php esc_html_e('Download spreadsheet as file', 'gsheetconnector-gravityforms-pro'); ?></li>
 </ul>
</div>
</div>
</div>

<div class="edit-gs-pro-footer">
  <label for="toggle-features" class="edit-gs-show-btn show">
   <?php esc_html_e('Show Features', 'gsheetconnector-gravityforms-pro'); ?>  
   <svg fill="#000000" width="800px" height="800px" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg">
    <path d="M0.256 8.606c0-0.269 0.106-0.544 0.313-0.75 0.412-0.412 1.087-0.412 1.5 0l14.119 14.119 13.913-13.912c0.413-0.412 1.087-0.412 1.5 0s0.413 1.088 0 1.5l-14.663 14.669c-0.413 0.413-1.088 0.413-1.5 0l-14.869-14.869c-0.213-0.213-0.313-0.481-0.313-0.756z"></path>
  </svg>
</label>

<label for="toggle-features" class="edit-gs-show-btn hide">
 <?php esc_html_e('Hide Features', 'gsheetconnector-gravityforms-pro'); ?>  
 <svg width="800px" height="800px" viewBox="0 -4.5 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
  <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
    <g id="Dribbble-Light-Preview" transform="translate(-260.000000, -6684.000000)" fill="#000000">
      <g id="icons" transform="translate(56.000000, 160.000000)">
        <path d="M223.707692,6534.63378 L223.707692,6534.63378 C224.097436,6534.22888 224.097436,6533.57338 223.707692,6533.16951 L215.444127,6524.60657 C214.66364,6523.79781 213.397472,6523.79781 212.616986,6524.60657 L204.29246,6533.23165 C203.906714,6533.6324 203.901717,6534.27962 204.282467,6534.68555 C204.671211,6535.10081 205.31179,6535.10495 205.70653,6534.69695 L213.323521,6526.80297 C213.714264,6526.39807 214.346848,6526.39807 214.737591,6526.80297 L222.294621,6534.63378 C222.684365,6535.03868 223.317949,6535.03868 223.707692,6534.63378" id="arrow_up-[#337]">
        </path>
      </g></g></g></svg>
    </label>
  </div>
</div>
</div> 
<!-- unlock advanced feature #end -->
<div class="pro-features">
  <hr>
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
                  <div class="button-bar"> <a href="https://www.gsheetconnector.com/docs/gravity-forms-to-google-sheet-free"
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
                  <label id="field-mapping" class="gform-settings-label" for="gsheet_mergeTagsMap">
                    <?php echo esc_html__("Select Fields to Sync (PRO)", "gsheetconnector-gravity-forms"); ?>
                   <?php /*?> <div class="upgrade-button">
                      <a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector?gsheetconnector-ref=17"
                      target="__blank" class="upgradeLink">
                      <?php echo esc_html__("Upgrade to Pro", "gsheetconnector-gravity-forms"); ?>
                    </a>
                    </div><?php */?>

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
                            <div class="toggle-button special_mail_tags_bg">

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

              <div id="_gform_setting_gsheet_mergeTagsMap_container" class="gform-settings-field-map__container blur-pro-feature"
              title="Upgrade to Pro">
              <table class="gform-settings-generic-map__table" cellspacing="0" cellpadding="0">
                <tbody>
                  <tr class="gform-settings-generic-map__row">
                    <hr>
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


<hr>
<div class="gsgf-free blur-pro-feature">
	
	<div class="auto-section shadow-box mt-40 p-30  blur-pro-feature" id="auto-googlesheet-configuration">
   <div class="gsc-fields">
    <div class="sheet-details ">
     <div class="heading mt-0"><?php echo esc_html__("Automatically Google Sheet Configuration", "gsheetconnector-gravity-forms"); ?></div>
     <p><?php echo esc_html__("Automatically configure your Google Sheet and start syncing form submissions in real time.", "gsheetconnector-gravity-forms"); ?></p>
     <div class="row">
      <div class="col-5">
       <div class="mr-10">
        <label><?php echo esc_html__("Sheet Name", "gsheetconnector-gravity-forms"); ?></label>
        <select name="gscf-ff[gsc-fluentform-sheet-id]" class="auto-select-display w-100 mt-5" id="gsc-fluentform-sheet-id">
         <option value="">
         Select </option>
         <option value="create_new">
         Create New </option>
       </select>
     </div>
   </div>
   <span class="error_msg" id="error_spread"></span>
   <span class="loading-sign">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
   <i class="errorSelect errorSelectsheet"></i>
   <div class="col-5">
     <label><?php echo esc_html__("Sheet Tab Name", "gsheetconnector-gravity-forms"); ?></label>
     <select name="gscf-ff[gs-sheet-tab-name]" class="auto-select-display w-100 mt-5" id="gscfff-sheet-tab-name">
      <option value="">
      Select </option>
    </select>
  </div>
</div>
</div>
</div>
</div>



<div class="freez_order_sort form-fields-list gscfff-list-set shadow-box mt-40 p-30">
  <div id="header-settings-sheet-sorting">
   <div class="heading mt-0"><?php echo esc_html__("Header Settings", "gsheetconnector-gravity-forms"); ?></div>
   <p><?php echo esc_html__("Customize the appearance and behavior of your sheet headers and rows for better readability and organization.", "gsheetconnector-gravity-forms"); ?></p>
   <!-- SECTION 1: Header Behavior -->
   <div class="header-styling-sheet">
    <div class="settings-card mb-20 w-100 bg-white">
     <div class="mt-0 header-settings-ineer-size fw-600 mb-20"><?php echo esc_html__("Header Behavior", "gsheetconnector-gravity-forms"); ?></div>
     <div class="gscfrmnt-cards gscfrmnt-card setting-row">
      <div class="toggle-button freeze-header-toggle d-flex align-items-center justify-between mb-15">
       <span class="label-text fw-400"><?php echo esc_html__("Freeze Header", "gsheetconnector-gravity-forms"); ?></span>
       <label class="switch">
        <input type="checkbox" id="freeze-header-checkbox" name="gscf-ff[freeze_header]" value="true" checked="">
        <span class="slider round button-toggle"></span>
      </label>
    </div>
  </div>
  <div class="sheet_sorting sheet_formatting">
    <div class="gscfrmnt-cards">
     <div class="toggle-button sheet-sorting-toggle d-flex align-items-center justify-between">
      <span class="label-text fw-400"><?php echo esc_html__("Sheet Sorting", "gsheetconnector-gravity-forms"); ?></span>
      <label class="switch" for="sheet-sorting-checkbox">
       <input type="checkbox" id="sheet-sorting-checkbox" name="gscf-ff[sheet_sorting]" value="1" checked="">
       <span class="slider round button-toggle"></span>
     </label>
   </div>
 </div>
 <div class="sheet-sorting-settings" id="sheet-sorting-settings">
   <div class="settings-grid">
    <div class="gscfrmnt-row form-group">
     <label for="sort-column-name">
      <?php echo esc_html__("Sort Column", "gsheetconnector-gravity-forms"); ?>
    </label>
    <select id="sort-column-name" name="gscf-ff[sort_column]">
    </select>
  </div>
  <div class="gscfrmnt-row form-group">
   <label for="sort-order"><?php echo esc_html__("Sort Order", "gsheetconnector-gravity-forms"); ?></label>
   <select id="sort-order" name="gscf-ff[sort_order]">
    <option value="ASCENDING"><?php echo esc_html__("Ascending", "gsheetconnector-gravity-forms"); ?></option>
    <option value="DESCENDING"><?php echo esc_html__("Descending", "gsheetconnector-gravity-forms"); ?></option>
  </select>
</div>
</div>
</div>
</div>
</div>
<!-- SECTION 2:Conditional Logic-->
<div class="settings-card mb-20 w-100 bg-white">

 <div class="mt-0 header-settings-ineer-size fw-600 mb-20">
  <?php echo esc_html__("Conditional Logic", "gsheetconnector-gravity-forms"); ?>
</div>

<div class="gscfrmnt-cards gscfrmnt-card setting-row">

  <div class="misc-conditional-inner30 misc-options-inner-color-multi-cf7gs-hidden">
   <div class="process-row">
     <label> <?php echo esc_html__("Process this feed if", "gsheetconnector-gravity-forms"); ?></label>
     <select name="cf7-gs30[enable_conditional_logic_type_feed]" class="enableConditionalLogic">
      <option value="all">All</option>
      <option value="any">Any</option>
    </select>
    <label><?php echo esc_html__("of the following match:", "gsheetconnector-gravity-forms"); ?></label>
  </div>
  <div>

   <select name="" class="enableConditionalLogic gsc-select">
    <option value="your-name"><?php echo esc_html__('your-name', 'gsheetconnector-gravity-forms'); ?></option>
    <option value="your-email"><?php echo esc_html__('your-email', 'gsheetconnector-gravity-forms'); ?></option>
    <option value="your-subject"><?php echo esc_html__('your-subject', 'gsheetconnector-gravity-forms'); ?></option>
    <option value="your-message"><?php echo esc_html__('your-message', 'gsheetconnector-gravity-forms'); ?></option>
  </select>

  <!-- Conditional logic operator dropdown -->
  <select name="" class="enableConditionalLogic gsc-select">

    <!-- Equality condition -->
    <option value="is"><?php echo esc_html__("is", "gsheetconnector-gravity-forms"); ?></option>

    <!-- Not equal condition -->
    <option value="isnot"><?php echo esc_html__("is not", "gsheetconnector-gravity-forms"); ?></option>

    <!-- Greater than condition -->
    <option value="greaterthan"><?php echo esc_html__("greater than", "gsheetconnector-gravity-forms"); ?></option>

    <!-- Less than condition -->
    <option value="lessthan"><?php echo esc_html__("less than", "gsheetconnector-gravity-forms"); ?></option>

    <!-- Contains condition -->
    <option value="contains"><?php echo esc_html__("contains", "gsheetconnector-gravity-forms"); ?></option>

    <!-- Starts with condition -->
    <option value="starts_with"><?php echo esc_html__("starts with", "gsheetconnector-gravity-forms"); ?></option>

    <!-- Ends with condition -->
    <option value="ends_with"><?php echo esc_html__("ends with", "gsheetconnector-gravity-forms"); ?></option>

  </select>

  <input type="text" name="cf7-gs30[conditional_feed][0][enable_conditional_logic_rule_value_feed]" value="" placeholder="Enter a value" class="enableConditionalLogic">

  <button type="button" class="add_field_choice-multi circle-plus" data-id="30">
    +
  </button>
</div>

<div class="conditional-logic-container-multi30" style="margin-top:10px"></div>
</div>

</div>

</div>
<!-- SECTION 2: Header Appearance -->
<div class="settings-card  mb-20 w-100 bg-white">
 <div class="mt-0 header-settings-ineer-size fw-600 mb-20"><?php echo esc_html__("Header Styling", "gsheetconnector-gravity-forms"); ?></div>
 <div class="sheet_formatting setting-row">
  <div class="gscfrmnt-sheet_formatting gscfrmnt-sheet_formatting">
   <div class="toggle-button sheet_formatting-header-toggle d-flex align-items-center justify-between mb-15">
    <span class="label-texts  fw-400"><?php echo esc_html__("Header Appearance", "gsheetconnector-gravity-forms"); ?> </span>
    <label class="switch" for="sheet_formatting-header-checkbox">
     <input type="checkbox" id="sheet_formatting-header-checkbox" name="gscf-ff[sheet_formatting_header]" value="1" checked="">
     <span class="slider round button-toggle"></span>
   </label>
 </div>
</div>
<div class="font-styling-settings" id="font-styling-settings">
 <div class="settings-grid">
  <div class="font-style row-format form-group">
   <label for="font-size"><?php echo esc_html__("Font Style", "gsheetconnector-gravity-forms"); ?></label>
   <div class="d-flex gap-5">
    <label class="style-btn active"><input type="checkbox" name="font_styles[]" class="toggle-input active" value="normal">
      <?php echo esc_html__('Normal', 'gsheetconnector-gravity-forms'); ?></label>
      <label class="style-btn"><input type="checkbox" name="font_styles[]" value="italic">
       <?php echo esc_html__('Italic', 'gsheetconnector-gravity-forms'); ?></label>
       <label class="style-btn"><input type="checkbox" name="font_styles[]" value="bold"><?php echo esc_html__('Bold', 'gsheetconnector-gravity-forms'); ?></label>
     </div>
   </div>

   <div class="font-size row-format form-group">
     <label for="font-size"><?php echo esc_html__("Font Size", "gsheetconnector-gravity-forms"); ?></label>
     <input type="number" id="font-size" name="gscf-ff[font_size]" value=""> px
   </div>
   <div class="font-color row-format form-group">
     <label for="font-color"><?php echo esc_html__("Font Color", "gsheetconnector-gravity-forms"); ?></label>
     <input type="color" id="font-color" name="gscf-ff[font_color]" class="bg-color-set-input" value="">
   </div>
   <div class="gscfrmnt-cards-sbg gscfrmnt-cards-sbg form-group">

     <label for="gscfrmnt-header-color" class="button-gscfrmnt-toggle-color"></label>
     <label><?php echo esc_html__("Header Background", "gsheetconnector-gravity-forms"); ?></label>
     <input type="color" id="header-color" name="gscf-ff[header-color]" class="bg-color-set-input" value="&lt; ?php echo esc_attr($header_color); ?&gt;">
   </div>
 </div>
</div>
</div>
<!-- SECTION 3: Row Appearance -->
<div class="sheet_formatting">
  <div class="gscfrmnt-sheet_formatting_row gscfrmnt-sheet_formatting_row">
   <div class="toggle-button sheet_formatting-row-toggle d-flex align-items-center justify-between">
    <span class="label-texts  fw-400"><?php echo esc_html__("Row Appearance", "gsheetconnector-gravity-forms"); ?> </span>
    <label class="switch" for="sheet_formatting-row-checkbox">
     <input type="checkbox" id="sheet_formatting-row-checkbox" name="gscf-ff[sheet_formatting_row]" value="1" checked="">
     <span class="slider round button-toggle"></span>
   </label>
 </div>
</div>
<div class="font-styling-settings-row" id="font-styling-settings-row">
 <div class="settings-grid">
  <div class="font-style row-format form-group">
   <label><?php echo esc_html__("Font Style", "gsheetconnector-gravity-forms"); ?></label>
   <div class="d-flex gap-5">
    <label class="style-btn active"><input type="checkbox" name="font_styles[]" class="toggle-input active" value="normal">
     <?php echo esc_html__("Normal", "gsheetconnector-gravity-forms"); ?></label>
     <label class="style-btn"><input type="checkbox" name="font_styles[]" value="italic">
       <?php echo esc_html__("Italic", "gsheetconnector-gravity-forms"); ?></label>
       <label class="style-btn"><input type="checkbox" name="font_styles[]" value="bold"><?php echo esc_html__("Bold", "gsheetconnector-gravity-forms"); ?></label>
     </div>
   </div>

   <div class="font-size row-format form-group">
     <label for="font-size-row"><?php echo esc_html__("Font Size", "gsheetconnector-gravity-forms"); ?></label>
     <input type="number" id="font-size-row" name="gscf-ff[font_size_row]" value=""> px
   </div>
   <div class="font-color row-format form-group">
     <label for="font-color-row"><?php echo esc_html__("Font Color", "gsheetconnector-gravity-forms"); ?></label>
     <input type="color" id="font-color-row" name="gscf-ff[font_color_row]" class="bg-color-set-input" value="">
   </div>
   <div class="gscfrmnt-cards-sbg gscfrmnt-cards-sbg form-group">

     <label for="gscfrmnt-odd-color" class="button-gscfrmnt-toggle-color"></label>
     <label><?php echo esc_html__("Odd Row Color", "gsheetconnector-gravity-forms"); ?></label>
     <input type="color" id="odd-color" name="gscf-ff[odd-color]" class="bg-color-set-input" value="&lt; ?php echo esc_attr($odd_color); ?&gt;">
   </div>
   <div class="gscfrmnt-cards-sbg gscfrmnt-cards-sbg form-group">
     <label for="gscfrmnt-even-color" class="button-gscfrmnt-toggle-color"></label>
     <label><?php echo esc_html__("Even Row Color", "gsheetconnector-gravity-forms"); ?></label>
     <input type="color" id="even-color" name="gscf-ff[even-color]" class="bg-color-set-input" value="&lt; ?php echo esc_attr($even_color); ?&gt;">
   </div>
 </div>
</div>
</div>
</div>
</div>
</div>
<!-- SECTION 4: Spreadsheet Download -->
<div id="spreadsheet-download-sync">
 <div class="settings-card mb-20 w-100 bg-white">
  <div class="toggle-button sheet-sorting-toggle d-flex align-items-center justify-between" id="downloads-toggle-checkbox">
   <span class="label-text  fw-400"><?php echo esc_html__("Spreadsheet Download", "gsheetconnector-gravity-forms"); ?></span>
   <label class="switch" for="download-toggle-checkbox">
    <input type="checkbox" id="download-toggle-checkbox" name="gscf-ff[download_spreadsheet]" value="1" checked="">
    <span class="slider round button-toggle"></span>
  </label>
</div>
<div id="download-button-wrapper" class="mt-15">
 <!-- style="display:none;"&gt; -->
 <a href="<?php echo esc_url('#'); ?>" class="sheet-url-fluentform common-sheet-url text-dark fw-700 download-spreadsheet">
  <i class="fa-regular fa-file-zipper text-dark fw-500 mr-5"></i>
  <?php echo esc_html__("Download", "gsheetconnector-gravity-forms"); ?>
</a>
</div>
</div>
</div>
</div>


<div class="settings-card mt-20 mb-20 w-100 bg-white blur-pro-feature">
  <div class="heading mt-0"><?php echo esc_html__("Google Sheets Data Sync", "gsheetconnector-gravity-forms"); ?></div>
  <p><?php echo esc_html__("Easily sync past form submissions to your connected Google Sheet. Choose a date range and ensure your spreadsheet stays complete and up to date.", "gsheetconnector-gravity-forms"); ?></p>

  <div class="gs-wpcore-sync-entries">
    <div class="d-flex gap-10">
      <div class="form-group w-100">
        <label for="sync-from-date"><?php echo esc_html__("From Date", "gsheetconnector-gravity-forms"); ?></label>
        <input type="date" id="sync-from-date" name="sync_from_date" value="" class="wpgs-date-picker">
      </div>

      <div class="form-group w-100">
        <label for="sync-to-date"><?php echo esc_html__("To Date", "gsheetconnector-gravity-forms"); ?></label>
        <input type="date" id="sync-to-date" name="sync_to_date" value="" class="wpgs-date-picker">
      </div>
    </div>

    <a id="gs-sync-entries" data-init="yes" class="sync-button-entries back-btn btn btn-primary text-decoration-none mt-20">
      <?php echo esc_html__("Sync Entries", "gsheetconnector-gravity-forms"); ?>
    </a>
    <span class="loading-sign">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
    <input type="hidden" name="gscfff-ajax-nonce" id="gscfff-ajax-nonce" value="5b7c708b0c">

    <div id="gs-message-popup" style="display:none;">
      <div class="popup-content">
        <p id="popup-message"></p>
        <button id="popup-close"><?php echo esc_html__("Close", "gsheetconnector-gravity-forms"); ?></button>
      </div>
    </div>
  </div>
</div>

</div> <!-- gsgf-free #end -->

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
      GravityForms_GsFree_Connector_Utility::gfgs_debug_log("⚠️ No static sheet settings found for form ID: {$form_id} ");
    }

    // Dynamic feed-based integration
    $feeds = $this->get_active_feeds($form['id']);
    // error_log("📦 Found " . count($feeds) . " active feeds for form ID: {$form_id}");

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



  // Modified code ahmed.
  // since v-1.0.19
  public function send_entry($sheetId, $sheet_name, $tabid, $sheet_tab_name, $entry, $form)
  {
    $form_id = $form['id'];
    $entry_id = $entry['id'];

    // $Date = $entry['date_created'];
    // Convert UTC to WordPress timezone (IST)
    $utc_date = $entry['date_created'];

    // Create DateTime object in UTC
    $date = new DateTime($utc_date, new DateTimeZone('UTC'));

    // Convert to WordPress timezone (auto picks Kolkata if set)
    $wp_timezone = wp_timezone(); // WP timezone
    $date->setTimezone($wp_timezone);

    // Format date as needed
    $Date = $date->format('d-m-Y H:i:s');

    if ($sheet_name !== "" && $sheet_tab_name !== "") {
      try {
        include_once(GRAVITY_GOOGLESHEET_ROOT . "/lib/google-sheets.php");
        $doc = new Gfgscf_googlesheet();
        $doc->auth();
        $doc->setSpreadsheetId($sheetId);
        $doc->setWorkTabId($tabid);

        $data_value['Entry Date'] = $Date;
        $data_value['Entry ID'] = $entry_id;

        foreach ($form['fields'] as $field) {
          $label = $field->label;
          $value = is_object($field) ? $field->get_value_export($entry) : '';
          $raw_value = isset($entry[$field->id]) ? $entry[$field->id] : '';

          
          // Address Field
          if ($field->type == 'address' && isset($field->inputs) && !empty($field->inputs)) {
            foreach ($field->inputs as $input) {
              $subfield_id = $input['id'];
              $subfield_label = isset($input['customLabel']) && !empty($input['customLabel']) ? $input['customLabel'] : $input['label'];
              $subfield_value = isset($entry[$subfield_id]) ? $entry[$subfield_id] : '';

              
              if (!empty($subfield_label)) {
                $data_value[$subfield_label] = $subfield_value;
              }
            }
          }
          // Checkbox Field
          else if ($field->type == 'checkbox' && isset($field->inputs)) {
            $checkbox_values = [];
            foreach ($field->inputs as $input) {
              $checkbox_id = $input['id'];
              if (isset($entry[$checkbox_id]) && !empty($entry[$checkbox_id])) {
                $checkbox_values[] = $entry[$checkbox_id];
              }
            }
            $data_value[$label] = implode(', ', $checkbox_values);

          }
          // Dropdown / Select
          else if ($field->type == 'select') {
            $data_value[$label] = isset($entry[$field->id]) ? $entry[$field->id] : '';
            
          }
          // File Upload
          else if ($field->type == 'fileupload') {
            $file_url = isset($entry[$field->id]) ? $entry[$field->id] : '';
            $data_value[$label] = !empty($file_url) ? $file_url : 'No file uploaded';

          }
          // Consent Field
          else if ($field->type == 'consent') {
            if (isset($field->checkboxLabel) && !empty($field->checkboxLabel)) {
              $data_value[$label] = $field->checkboxLabel;
              
            }
          }
          // Catch-all for other fields
          else if ($field->type == 'product' && isset($field->inputs)) {
            $quantity_input = $field->inputs[2]['id'] ?? null;
            $quantity_value = $quantity_input ? rgar($entry, $quantity_input) : '';
            $data_value[$label] = $quantity_value;
            
          } else {
            $final_value = !empty($value) ? $value : $raw_value;
            $data_value[$label] = $final_value;
            
          }
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
