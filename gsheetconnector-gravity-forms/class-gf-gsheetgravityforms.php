<?php
GFForms::include_payment_addon_framework();

class Gforms_Gsheet_Connector extends GFFeedAddOn {

   protected $_version = GRAVITY_GOOGLESHEET_VERSION;
   protected $_min_gravityforms_version = '1.0';
   protected $_slug = 'gsheetconnector-gravityforms';
   protected $_path = 'gsheetconnector-gravityforms/gsheetconnector-gravityforms.php';
   protected $_full_path = __FILE__;
   protected $_title = 'Gravity Forms GSheet Connector Addon';
   protected $_short_title = 'Googlesheet';
   protected $_single_feed_submission = true;
   protected $_enable_rg_autoupgrade = true;
   private static $_instance = null;
   protected $_capabilities_form_settings = array();

   /**
    * Get an instance of this class.
    *
    * @return Gforms_Gsheet_Connector
    */
   public static function get_instance() {
      if (self::$_instance == null) {
         self::$_instance = new Gforms_Gsheet_Connector();
      }
      return self::$_instance;
   }

   //public function __construct() { // Resolved Issue Unable to render page
  public function init() {  
   parent::init();

      add_action('admin_init', array($this, 'after_save_form_settings'));
      add_action('gform_after_submission', array($this, 'after_submission'), 10, 2);
      add_action('admin_footer', array($this, 'add_gf_nonce'));
      
   }

  
   /**
    * settings for select the spreadsheets. Go Form Settings->Googlesheet->Feeds
    * @return array $fields
    * @since 1.0
    */
   public function feed_settings_fields() {
      $form_id = intval( $_GET['id'] );
      $form_data = get_post_meta( $form_id, 'gfgs_settings' );
      $sheet_data = get_option( 'gfgs_feeds' );
      // Check if the user is authenticated
       $authenticated = get_option('gfgs_token');
      
       $per = get_option('gfgs_verify');
      
  
//check user is authenticated when save existing api method
$show_setting = 0;
if ((!empty($authenticated) && $per == "valid") ) {
  return array(
         $fields['feed_settings'] = array(
              'title' => esc_html__('GSheetConnector Gravity Forms Feed Settings'),
              'fields' => array(
                  array(
                      'name' => 'feedName',
                      'label' => esc_html__('Feed Name', 'gsheetconnector-gravityforms'),
                      'type' => 'text',
                      'required' => true,
                      'class' => 'medium',
                      'tooltip' => esc_html__('Enter a feed name to uniquely identify this setup.', 'gsheetconnector-gravityforms')
                  ),
                  array(
                      'name' => 'sheet_details',
                      'label' => esc_html__('Google Sheet Settings:', 'gsheetconnector-gravityforms'),
                      'type' => 'display_sheet_details',
                  ),
                  
                  array(
                      'name' => 'condition',
                      'label' => esc_html__('Condition', 'gsheetconnector-gravityforms'),
                      'type' => 'feed_condition',
                      'checkbox_label' => esc_html__('Enable Condition', 'gsheetconnector-gravityforms'),
                      'instructions' => esc_html__('Process this feed if', 'gsheetconnector-gravityforms'),
                  ),
                  array(
                    'name' => 'gsheet_field_maps',
                    'label' => esc_html__( 'Field List:', 'gsheetconnector-gravityforms' ),
                    'type'    => 'map_form_fields',
                  ),
              ),
         ),
      );
}
else { 
//check user is not authenticated above method
$fields = array(
                array(
                  'title' => esc_html__( 'GSheetConnector Gravity forms Feed Settings' ),
                  'fields' => array(          
                    array(
                      'name' => 'feedName',
                      'label' => '',
                      'type' => 'display_note',
                      'class' => 'hide_save_btn',
                                
                      
                    ),
                  ),
                ),
              );
        }
        
        return $fields;
      }

      public function settings_display_note( $field ) {
      ?>


      
      <p class="gs-display-note">
             <strong><?php echo __("Authentication Required:", "gsheetconnector-gravityforms"); ?></strong>
                  <?php echo __("  You must have to", "gsheetconnector-gravityforms"); ?> <a href="admin.php?page=gf_googlesheet" target="_blank"><?php echo __("Authenticate using your Google Account", "gsheetconnector-gravityforms"); ?></a> <?php echo __("along with Google Drive and Google Sheets Permissions in order to enable the settings for configuration.", "gsheetconnector-gravityforms"); ?>
             
      </p>
      </br>


      
   
      <?php //$this->get_google_sheet_settings( $this->get_current_form(), $this->get_current_feed_id() );
    }



   /**
    * Display gravityforms gsheet settings for sheetname and tabname
    * @access public
    * @since 1.0
    */
    public function settings_display_sheet_details($field) {
        $form_id = intval( $_GET['id'] );
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
        $fields_inputs = array( "name", "address", "consent", "product" );
        
        echo '<table class="gf-field-list gsheet-table two-col">';
          $field_list = $this->get_form_field_list($form);
        


      $field_name = "gf-gs";
      $text_field = array(
        'name' => $field_name.'-sheet-name',
        'label' => esc_html__( 'Sheet Name', 'gsheetconnector-gravityforms' ),
        'type'    => 'text',
        'value'    => isset($get_data[0]['sheet-name']) ? esc_attr($get_data[0]['sheet-name']) : '',
      );    
      $sheet_name_text_field = $this->settings_text( $text_field, false );
      /*FIELD END*/
      
      /*FIELD*/
      $text_field = array(
        'name' => $field_name.'-sheet-id',
        'label' => esc_html__( 'Sheet Id', 'gsheetconnector-gravityforms' ),
        'type'    => 'text',
        'value'    => isset($get_data[0]['sheet-id']) ? esc_attr($get_data[0]['sheet-id']) : '',
      );    
      $sheet_id_text_field = $this->settings_text( $text_field, false );
      /*FIELD END*/
      
      /*FIELD*/
      $text_field = array(
        'name' => $field_name.'-sheet-tab-name',
        'label' => esc_html__( 'Tab Name', 'gsheetconnector-gravityforms' ),
        'type'    => 'text',
        'value'    => isset($get_data[0]['sheet-tab-name']) ? esc_attr($get_data[0]['sheet-tab-name']) : '',
      );    
      $tab_name_text_field = $this->settings_text( $text_field, false );
      /*FIELD END*/
      
      /*FIELD*/
      $text_field = array(
        'name' => $field_name.'-tab-id',
        'label' => esc_html__( 'Tab Id', 'gsheetconnector-gravityforms' ),
        'type'    => 'text',
        'value'    => isset($get_data[0]['tab-id']) ? esc_attr($get_data[0]['tab-id']) : '',
      );   
       /*FIELD*/
      $text_field = array(
        'name' => $field_name.'-tab-id',
        'label' => esc_html__( 'Google Sheet URL', 'gsheetconnector-gravityforms' ),
        'type'    => 'text',
        'value'    => isset($get_data[0]['tab-id']) ? esc_attr($get_data[0]['tab-id']) : '',
      );   

      $tab_id_text_field = $this->settings_text( $text_field, false );  
     

       // echo '<pre>';print_r( $tab_id_text_field);die;
      ?>

      <style>
        .guide { position:relative; width: 44%; float: right; text-align:center; }
        .guide span { display:block; padding-bottom:5px;  }
        .guide img { max-width:100%; border:1px dashed #000;     box-shadow: 3px 3px 3px rgba(0,0,0,.05); }
      </style>

      <div class="guide"><span>Screenshot below for Google Sheet settings</span> <a href="https://support.gsheetconnector.com/wp-content/uploads/2024/04/google-sheet-setting.png"><img src="https://support.gsheetconnector.com/wp-content/uploads/2024/04/google-sheet-setting.png" alt=""/> Click to enlarge image</a></div>
      <?php
       
       

        $settings = $this->get_feed( $this->get_current_feed_id() );
        $feed = isset($settings['meta']['gsheet_field_maps_enable'])?$settings['meta']['gsheet_field_maps_enable']:array();
        
        $checkAll = '<span class="gform-settings-input__container" disabled>
              <div id="checkall-div" class="gform-settings-choice gform-settings-field gform-settings-field__toggle">
                <input type="hidden" name="checkall" value="0">
                <input type="checkbox" id="checkall" name="checkall" class="checkbox-all-field">
                <label class="gform-field__toggle-container" for="checkall">
              <span class="gform-field__toggle-switch"></span
              ></label><span class="lbl_tog_field" disabled>Check All</span>
              </div>
            </span>';
            ?>
        <tr><td><?php echo $checkAll; ?></td></tr>

        <tr>
          <td><!-- <label><span><input type="checkbox" class="entry_id_chk" checked disabled>Entry ID</span></label></td>
          <td><input type="text" name="entry_id" value="Entry ID" class="gaddon-text" disabled> -->
          <div class="gform-settings-field gform-settings-field__toggle"disabled>
            <span class="gform-settings-input__container"><input type="checkbox" name="entry_id" id="entry_id" value="1" disabled="1"><label class="gform-field__toggle-container" for="entry_id"><span class="gform-field__toggle-switch"></span></label><span class="lbl_tog_field"><?php echo esc_html(__('Entry ID', 'gsheetconnector-gravityforms')); ?></span></span>
          </div>
          </td>
        </tr>
        
        
        <?php
        foreach( $field_list as $field ) {
          
         
          
          $field_name = $field['field_name'];
          $field_id = $field['field_id'];

          $checked = (isset($feed[$field_id]) && $feed[$field_id] == "1") ? "checked" : ""; 
          
          $checkbox_name = $parent_field_name.'_enable';
          $hidden_name = $parent_field_name.'_field_'.$field_id;
          $default_value = $field_name;
          
          /*FIELD*/
          $column_name_text = array(
            'name' => $parent_field_name."_header_cell[$field_id]",
            'type'    => 'text',
            'default_value'    => $default_value,
                "class" => "gaddon-text",
          );    
          $create_sheet_field = $this->settings_text( $column_name_text, false );
          /*FIELD END*/
          
          /*FIELD*/
          $enable_checkbox_field = array(
            'name' => $checkbox_name,
            'type'    => 'checkbox',
            "choices" => array(
              array(
                "label" => $field_name,
                "value"  => "1",  
                "name"  => $checkbox_name."[$field_id]",
                "class" => "gaddon-checkbox",
              ),            
            ),
          );
              
          $checkbox_field = '<span class="gform-settings-input__container">
              <div id="gform-settings-checkbox-choice-gsheet_field_maps_enable'.$field_id.'" class="gform-settings-choice gform-settings-field gform-settings-field__toggle">
                <input type="hidden" name="_gform_setting_gsheet_field_maps_enable['.$field_id.']" value="'.(isset($feed[$field_id]) ? $feed[$field_id] : 0).'">
                <input type="checkbox" id="gsheet_field_maps_enable'.$field_id.'" name="gsheet_field_maps_enable['.$field_id.']" class="gaddon-checkbox" '.$checked.' data-id='.$field_id.'>
                <label class="gform-field__toggle-container" for="gsheet_field_maps_enable'.$field_id.'">
              <span class="gform-field__toggle-switch"></span
              ></label><span class="lbl_tog_field">'.$field_name.'</span>
              </div>
            </span>';
          ?>
          <tr>
            <td style="width:30%!important; float: left!important;"><?php echo $checkbox_field; ?> </td>
            <td style="width:60%!important; float: left!important;" class="row_grvt" data-id="gsheet_field_maps_enable<?php echo $field_id ?>"><?php echo $create_sheet_field; ?></td>
          </tr>
          <?php
        }
        ?>


      
          <form method="post">
            <div class="gfgsheet-fields">
              <p>
                <label><?php echo esc_html(__('Sheet Name', 'gsheetconnector-gravityforms')); ?></label>
                <span class="required">(Required)</span>
                <button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip" aria-label='Go to your google account and click on "Google apps" icon and then click "Sheets". Select the name of the appropriate sheet you want to link your contact form or create a new sheet.'>
                  <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
                </button>
                <?php echo $sheet_name_text_field ?>
                <p class="gform-settings-validation__error" id="error-sheetName"></p>
              </p>
              <p>
                <label><?php echo esc_html(__('Sheet Id', 'gsheetconnector-gravityforms')); ?></label>
                <span class="required">(Required)</span>
                <button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip" aria-label='You can get the sheet id from your sheet URL'>
                  <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
                </button>
                <?php echo $sheet_id_text_field ?>
                <p class="gform-settings-validation__error" id="error-sheetId"></p>
              </p>
              <p>
                <label><?php echo esc_html(__('Tab Name', 'gsheetconnector-gravityforms')); ?></label>
                <span class="required">(Required)</span>
                <button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip" aria-label='Open your Google Sheet with which you want to link your contact form. You will notice tab names at the bottom of the screen. Copy the tab name where you want to have an entry of the contact form.'>
                  <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
                </button>
                <?php echo $tab_name_text_field ?>
                <p class="gform-settings-validation__error" id="error-tabName"></p>
              </p>
              <p>
                <label><?php echo esc_html(__('Tab Id', 'gsheetconnector-gravityforms')); ?></label>
                <span class="required">(Required)</span>
                <button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip" aria-label='You can get the tab id from your sheet URL'>
                  <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
                </button>
                <?php echo $tab_id_text_field ?>
                <p class="gform-settings-validation__error" id="error-tabId"></p>
              </p>


            
            </div>
             <p class="sheet_url" id="sheet_url"></p>

            


            <div class="pro-features">
                <hr style="color: #000; background-color: #CCCCCC; height: 2px; width: 100%; margin: 20px auto;">
                <!--  -->
                <!-- <a class="gs-woo-list-set" data-id="3" href="#0">
                  <p class="maxi_mize maxi_mize3"><i class="fa fa-plus" aria-hidden="true"></i></i></p>
                  <p class="mini_mize mini_mize3"><i class="fa fa-minus" aria-hidden="true"></i></p>
                 </a>  --> 





                 <div class="main-promotion-box small-pro-box"> 
  <div class="promotion-inner">
    <h2><?php echo __("Please proceed to the Final Step.", "gsheetconnector-gravityforms"); ?>  
      <small><?php echo __(" (For the FREE version of the plugin, you'll need to input the columns manually.)
", "gsheetconnector-gravityforms"); ?></small></h2>
   
    <p><?php echo __("Ensure that after inputting the Google Sheet Name, Sheet ID, Tab Name, and Tab ID above, you must enter the label names of your form into Row 1 of your Google Sheet, as illustrated in the image.", "gsheetconnector-gravityforms"); ?> <strong><?php echo __("Google Sheets", "gsheetconnector-gravityforms"); ?></strong>, <br />
      <?php echo __("Now available for popular", "gsheetconnector-gravityforms"); ?></p>
    <div class="button-bar">  <a href="https://www.gsheetconnector.com/docs/gravity-forms-to-google-sheet-free" target="_blank"><?php echo __("Refer Documentation", "gsheetconnector-gravityforms"); ?></a> <a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector" target="_blank"><?php echo __("Buy Now", "gsheetconnector-gravityforms"); ?></a></div>
    <p class="note"><?php echo __("In PRO version it will be managed with Below settings.", "gsheetconnector-gravityforms"); ?></p>
  </div>

<!-- <img src="<?php echo GRAVITY_GOOGLESHEET_URL; ?>/assets/image/gsheet-field-label-guide.gif" alt="" /> -->

 <div class="gsheet-plugins"><a href="https://support.gsheetconnector.com/wp-content/uploads/2024/04/gsheet-field-label-guide.gif" target="_blank"><img src="https://support.gsheetconnector.com/wp-content/uploads/2024/04/gsheet-field-label-guide.gif" alt="" /><?php echo __(" Click to enlarge image", "gsheetconnector-gravityforms"); ?></a></div> 
</div> <!-- main-promotion-box #end -->



              <label class="gform-settings-label" for="gsheet_mergeTagsMap">
                 <?php echo __("Fields Lists:", "gsheetconnector-gravityforms"); ?>  
                   <div class="upgrade-button">
                      <a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector?gsheetconnector-ref=17" target="__blank" class="upgradeLink">
                            <?php echo __("Upgrade to Pro", "gsheetconnector-gravityforms"); ?> 
                          </a>
                   </div>

                </label>



                <div id="_gform_setting_gsheet_mergeTagsMap_container" class="gform-settings-field-map__container"title="Upgrade to Pro">
                  <table class="gform-settings-generic-map__table" cellspacing="0" cellpadding="0">
                    <tbody>
                      <tr class="gform-settings-generic-map__row">
                      <hr style="color: #000; background-color: #CCCCCC; height: 2px; width: 100%; margin: 20px auto;">
                        <th class="gform-settings-generic-map__column gform-settings-generic-map__column--heading gform-settings-generic-map__column--key"><?php echo __("Column Name", "gsheetconnector-gravityforms"); ?> </th>
                        <th class="gform-settings-generic-map__column gform-settings-generic-map__column--heading gform-settings-generic-map__column--value"><?php echo __("Merge Tag / Formulas", "gsheetconnector-gravityforms"); ?> </th>
                        <th class="gform-settings-generic-map__column gform-settings-generic-map__column--heading gform-settings-generic-map__column--error"></th>
                        <th class="gform-settings-generic-map__column gform-settings-generic-map__column--heading gform-settings-generic-map__column--buttons"></th>
                      </tr>
                      <tr class="gform-settings-generic-map__row">
                        <td class="gform-settings-generic-map__column gform-settings-generic-map__column--key">
                          <span class="gform-settings-generic-map__custom">
                            <input id="_gform_setting_gsheet_mergeTagsMap_custom_key_0" type="text" placeholder="" value="gsheetconnector gravity pro" disabled>
                          </span>
                        </td>
                        <td class="gform-settings-generic-map__column gform-settings-generic-map__column--value">
                          <select id="_gform_setting_gsheet_mergeTagsMap_custom_value_0" class="" disabled>
                            <option value=""><?php echo __("Select a Field", "gsheetconnector-gravityforms"); ?> </option>
                            <optgroup label="Form Fields">
                              <option value="1"><?php echo __("Untitled", "gsheetconnector-gravityforms"); ?> </option>
                              <option value="3"><?php echo __("Untitled", "gsheetconnector-gravityforms"); ?> </option>
                            </optgroup>
                            <optgroup label="Entry Properties">
                              <option value="id"><?php echo __("Entry ID", "gsheetconnector-gravityforms"); ?> </option>
                              <option value="date_created"><?php echo __("Entry Date", "gsheetconnector-gravityforms"); ?> </option>
                              <option value="ip"><?php echo __("User IP", "gsheetconnector-gravityforms"); ?> </option>
                              <option value="source_url"><?php echo __("Source Url", "gsheetconnector-gravityforms"); ?> </option>
                              <option value="form_title"><?php echo __("Form Title", "gsheetconnector-gravityforms"); ?> </option>
                            </optgroup>
                            <option value="gf_custom"><?php echo __("Add Custom Value", "gsheetconnector-gravityforms"); ?> </option>
                          </select>
                        </td>
                        <td class="gform-settings-generic-map__column gform-settings-generic-map__column--error"></td>
                        <td class="gform-settings-generic-map__column gform-settings-generic-map__column--buttons">
                          <button class="add_field_choice gform-st-icon gform-st-icon--circle-plus gform-settings-generic-map__button gform-settings-generic-map__button--add" disabled>
                            <span class="screen-reader-text"><?php echo __("Add", "gsheetconnector-gravityforms"); ?> </span>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                   
                  
                </div>
<!--  pro feature #end -->


            <hr style="color: #000; background-color: #CCCCCC; height: 2px; width: 100%; margin: 20px auto;">

            <!--  -->
            <label class="gform-settings-label" for="gsheet_header_columns">
             <?php echo __(" Header Titles:", "gsheetconnector-gravityforms"); ?> 
              <div class="upgrade-button">
            <a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector?gsheetconnector-ref=17" target="__blank" class="upgradeLink">
                      <?php echo __("   Upgrade to Pro", "gsheetconnector-gravityforms"); ?> 
                    </a>
                </div>
            </label>


            <div class="manage-header" title="Upgrade to Pro">
              <div class="columns_manager_field">
                <input type="hidden" name="_gform_setting_gsheet_header_columns" id="gsheet_header_columns" value="" _gform_setting="" disabled>
              </div>
              <div class="columns_manager_field" disabled></div>



              <div class="fixed_entry_id" disabled><?php echo __("Entry ID", "gsheetconnector-gravityforms"); ?> </div>
              <div class="columns_manager_update" style="display: table-cell;">
                <ul disabled></ul>
              </div>
              <div class="upgrade-button">
                    
                      
                   
                  </div>
            </div>

          <hr style="color: #000; background-color: #CCCCCC; height: 2px; width: 100%; margin: 20px auto;">


      <!--  -->
        <div id="gform_setting_gsheet_freeze_header_toggle" class="gform-settings-field gform-settings-field__toggle" disabled>
              <div class="gform-settings-field__header">
                <label class="gform-settings-label" for="gsheet_freeze_header_toggle"><?php echo __("Header:", "gsheetconnector-gravityforms"); ?>&nbsp;</label><a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector">
              </div>
              <span class="gform-settings-input__container">
                <input type="checkbox" name="_gform_setting_gsheet_freeze_header_toggle" id="_gform_setting_gsheet_freeze_header_toggle" value="0" disabled>
                <label class="gform-field__toggle-container" for="_gform_setting_gsheet_freeze_header_toggle">
                  <span class="gform-field__toggle-switch"></span>
                </label>
              </span>
            </div>
            <hr style="color: #000; background-color: #CCCCCC; height: 2px; width: 100%; margin: 20px auto;">



                    <!--  -->
                    <div class="gform-settings-field__header">
                        <label class="gform-settings-label" for="gsheet_alternate_colors_enabled_toggle"><?php echo __("Colors:", "gsheetconnector-gravityforms"); ?>&nbsp;</label><a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector"></a>
                      </div>
                      <div id="gform_setting_gsheet_alternate_colors" class="gform-settings-field gform-settings-field__gsheet_alternate_colors" disabled>
                        <span class="gform-settings-input__container">
                          <div id="gform-settings-checkbox-choice-gsheet_alternate_colors_enabled" class="gform-settings-choice gform-settings-choice--inline">
                            <input type="hidden" name="_gform_setting_gsheet_alternate_colors_enabled" value="1">
                            <input type="checkbox" data_format="bool" horizontal="1" id="gsheet_alternate_colors_enabled" name="gsheet_alternate_colors_enabled" disabled>
                          </div>
                        </span>
                        <table class="alt-color-fields gsheet-table three-cols" style="">
                          <tbody>
                            <tr class="inline-colors">
                              <td>
                                <label><?php echo __("Header Color:", "gsheetconnector-gravityforms"); ?> </label>
                                <div>
                                  <span class="gform-settings-input__container">
                                    <input type="text" name="_gform_setting_gsheet_alternate_colors_header_color" value="#ffffff" id="gsheet_alternate_colors_header_color" disabled>
                                  </span>
                                </div>
                              </td>
                              <td>
                                <label><?php echo __("Odd Color:", "gsheetconnector-gravityforms"); ?> </label>
                                <div>
                                  <span class="gform-settings-input__container">
                                    <input type="text" name="_gform_setting_gsheet_alternate_colors_odd_color" value="#ffffff" id="gsheet_alternate_colors_odd_color" disabled>
                                  </span>
                                </div>
                              </td>
                              <td>
                                <label><?php echo __("Even Color:", "gsheetconnector-gravityforms"); ?> </label>
                                <div>
                                  <span class="gform-settings-input__container">
                                    <input type="text" name="_gform_setting_gsheet_alternate_colors_even_color" value="#ffffff" id="gsheet_alternate_colors_even_color" disabled>
                                  </span>
                                </div>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      <hr style="color: #000; background-color: #CCCCCC; height: 2px; width: 100%; margin: 20px auto;">



                  <!--  -->
                  <div id="gform_setting_gsheet_sort_column_enabled_toggle" class="gform-settings-field gform-settings-field__toggle disabled">
                    <div class="gform-settings-field__header">
                    <label class="gform-settings-label" for="gsheet_sort_column_enabled_toggle"><?php echo __("Sheet Sorting:", "gsheetconnector-gravityforms"); ?> &nbsp;</label><a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector"></a>
                    <span class="gform-settings-input__container">
                      <input type="checkbox" name="_gform_setting_gsheet_sort_column_enabled_toggle" id="_gform_setting_gsheet_sort_column_enabled_toggle" value="1" disabled>
                      <label class="gform-field__toggle-container" for="_gform_setting_gsheet_sort_column_enabled_toggle"><span class="gform-field__toggle-switch"></span></label>
                    </span>
                  </div>
                  <div id="gform_setting_gsheet_sort_column" class="gform-settings-field gform-settings-field__gsheet_sorting disabled">
                    <span class="gform-settings-input__container">
                      <div id="gform-settings-checkbox-choice-gsheet_sort_column_enabled" class="gform-settings-choice gform-settings-choice--inline">
                        <input type="hidden" name="_gform_setting_gsheet_sort_column_enabled" value="1">
                        <input type="checkbox" data_format="bool" horizontal="1" id="gsheet_sort_column_enabled" name="gsheet_sort_column_enabled" disabled>
                      </div>
                    </span>
                    <table class="sort-col-text-field gsheet-table two-col" style="">
                      <tbody>
                        <tr>
                          <td>
                            <label><?php echo __("Sort Column Name:", "gsheetconnector-gravityforms"); ?>&nbsp;</label>
                          </td>
                          <td>
                            <span class="gform-settings-input__container">
                              <input type="text" name="_gform_setting_gsheet_sort_column_name" value="" id="gsheet_sort_column_name" disabled>
                            </span>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <label><?php echo __("Order:", "gsheetconnector-gravityforms"); ?>&nbsp;</label>
                          </td>
                          <td>
                            <span class="gform-settings-input__container">
                              <select name="_gform_setting_gsheet_sort_column_order" id="gsheet_sort_column_order" disabled>
                                <option value="ASCENDING"><?php echo __("Ascending", "gsheetconnector-gravityforms"); ?> </option>
                                <option value="DESCENDING"><?php echo __("Descending", "gsheetconnector-gravityforms"); ?> </option>
                              </select>
                            </span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                <hr style="color: #000; background-color: #CCCCCC; height: 2px; width: 100%; margin: 20px auto;">

            <!--  -->
            <div id="gform_setting_gsheet_sync_entries" class="gform-settings-field gform-settings-field__display_sync_entries disabled">
              <div class="gform-settings-field__header" disabled>
                <label class="gform-settings-label" for="gsheet_sync_entries"><?php echo __("Sync Entries:", "gsheetconnector-gravityforms"); ?>&nbsp;</label><a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector"></a>
                
              </div>
              <input type="hidden" name="gf-ajax-sync-entries-nonce" id="gf-ajax-sync-entries-nonce" value="6dfefffeda">
              <p class="gs-sync-row">
               <?php echo __(" Save this feed to view sync all existing entries option.", "gsheetconnector-gravityforms"); ?>
              </p>
            </div>

          <hr style="color: #000; background-color: #CCCCCC; height: 2px; width: 100%; margin: 20px auto;"> 



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
    public function get_form_field_list($form) {
      $fields = $form['fields'];
      $fields_inputs = array( "name", "address", "consent", "product" );
      
      $field_list = array();
      
      foreach( $fields as $field ) {
          
        $field_name = $field->label;
        $field_id = $field->id;
        
        $type = $field->type;
        
        $field_list[] = array(
                "field_id" => $field_id,
                "field_name" => $field_name
              );
        
        if ( in_array( $type, $fields_inputs ) ) {
          $inputs = $field->inputs;
          foreach ( $inputs as $key => $value ) {
            if ( isset( $value['isHidden'] ) && $value['isHidden'] == 1 ) {
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
      
      $field_list = apply_filters( "gcgf_form_field_list", $field_list, $form );
      return $field_list;
    }
        

     
    public function get_field_list( $form_meta ) {

      $field_list = array();

      $fields_inputs = array( "name", "address", "consent", "product" );

      $data_meta = $form_meta['fields'];
      foreach ( $data_meta as $field_meta ) {

         $field_name = $field_meta->label;
         $field_id = $field_meta->id;
         $field_list[] = array(
            "field_id" => $field_id,
            "field_name" => $field_name
         );

         $type = $field_meta->type;
         if ( in_array( $type, $fields_inputs ) ) {
            $inputs = $field_meta->inputs;
            foreach ( $inputs as $key => $value ) {
               if ( isset( $value['isHidden'] ) && $value['isHidden'] == 1 ) {
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
   public function after_save_form_settings() {
      if (isset($_POST['gform-settings-save'])) {
         $gravityform_tags = array();
         $form_id = $_GET['id'];
         // echo '<pre>';print_r($_POST);die;
         $get_existing_data = get_post_meta($form_id, 'gfgs_settings');
         // get sheet name and tab name
         $sheet_name = isset($_POST['_gform_setting_gf-gs-sheet-name']) ? $_POST['_gform_setting_gf-gs-sheet-name'] : "";

         $tab_name = isset($_POST['gf-gs']['sheet-tab-name']) ? $_POST['gf-gs']['sheet-tab-name'] : "";

         $sheet_id = isset($_POST['gf-gs']['sheet-id']) ? $_POST['gf-gs']['sheet-id'] : "";

         $tab_id = isset($_POST['gf-gs']['tab-id']) ? $_POST['gf-gs']['tab-id'] : "";

         $form_meta = RGFormsModel::get_form_meta($form_id);
         $field_list = $this->get_field_list($form_meta);

         if (!empty($field_list)) {
            foreach ($field_list as $field_data) {
               $field_name = $field_data['field_name'];
               $field_id = $field_data['field_id'];
            }
         }
         if (!empty($get_existing_data) && $sheet_name == "") {
            update_post_meta($form_id, 'gfgs_settings', "");
         }

         if (!empty($sheet_name) && (!empty($tab_name) )) {
            update_post_meta($form_id, 'gfgs_settings', $_POST['gf-gs']);
         }
      }
      
   }

   public function feed_list_columns() {
      return array(
         'feedName' => esc_html__('Name', 'gsheetconnector-gravityforms'),
      );
   }

   /**
    * Insert data into Google Spreadsheet after form submission.
    *
    * @access public
    * @return array $row
    */
    public function after_submission($entry, $form) {
      // error_log("🚀 after_submission called for form ID: {$form['id']}");

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
                  $quantity_id = (string)$inputs[2]['id'];
                  $value = rgar($entry, $quantity_id);
                  // error_log("🔢 Product Field: {$field_label} - Quantity = {$value}");
              } else {
                  // Log all subfield values
                  foreach ($inputs as $input) {
                      $sub_id = (string)$input['id'];
                      $sub_value = rgar($entry, $sub_id);
                      // error_log("🔍 Multi-input Field: {$input['label']} ({$sub_id}) = {$sub_value}");
                  }
              }
          } else {
              $value = rgar($entry, (string)$field->id);
              // error_log("📝 Single Field: {$field_label} = {$value}");
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
          error_log("⚠️ No static sheet settings found for form ID: {$form_id}");
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
              // error_log("✅ Feed added (no condition): {$feed_id}");
          } else {
              $logic = rgars($feed_data, 'feed_condition_conditional_logic_object/conditionalLogic');
              if (!empty($logic) && GFCommon::evaluate_conditional_logic($logic, $form, $entry)) {
                  $processable_feeds[] = $feed;
                  // error_log("✅ Feed passed conditional logic: {$feed_id}");
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

              // error_log("🧭 Processing Feed: SheetID = {$sheetId}, SheetName = {$sheet_name}, TabID = {$tabid}, TabName = {$sheet_tab_name}");
              $this->send_entry($sheetId, $sheet_name, $tabid, $sheet_tab_name, $entry, $form);
          }
      }
  }


   
  // Modified code ahmed.
  // since v-1.0.19
    public function send_entry($sheetId, $sheet_name, $tabid, $sheet_tab_name, $entry, $form) {
      $form_id = $form['id'];
      $Date = $entry['date_created']; 

      if ($sheet_name !== "" && $sheet_tab_name !== "") {
          try {
              include_once(GRAVITY_GOOGLESHEET_ROOT . "/lib/google-sheets.php");
              $doc = new Gfgsc_googlesheet();
              $doc->auth();
              $doc->setSpreadsheetId($sheetId);
              $doc->setWorkTabId($tabid);

              $data_value['Entry Date'] = $Date;

              foreach ($form['fields'] as $field) {
                  $label = $field->label;
                  $value = is_object($field) ? $field->get_value_export($entry) : '';
                  $raw_value = isset($entry[$field->id]) ? $entry[$field->id] : '';

                  // error_log("Processing Field: Label = {$label}, Type = {$field->type}, Exported = {$value}, Raw = {$raw_value}");

                  // Address Field
                  if ($field->type == 'address' && isset($field->inputs) && !empty($field->inputs)) {
                      foreach ($field->inputs as $input) {
                          $subfield_id = $input['id'];
                          $subfield_label = isset($input['customLabel']) && !empty($input['customLabel']) ? $input['customLabel'] : $input['label'];
                          $subfield_value = isset($entry[$subfield_id]) ? $entry[$subfield_id] : '';

                          // error_log("↳ Address Subfield: {$subfield_label} = {$subfield_value}");

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
                      // error_log("↳ Checkbox Values for {$label}: " . $data_value[$label]);
                  }
                  // Dropdown / Select
                  else if ($field->type == 'select') {
                      $data_value[$label] = isset($entry[$field->id]) ? $entry[$field->id] : '';
                      // error_log("↳ Select Field Value for {$label}: " . $data_value[$label]);
                  }
                  // File Upload
                  else if ($field->type == 'fileupload') {
                      $file_url = isset($entry[$field->id]) ? $entry[$field->id] : '';
                      $data_value[$label] = !empty($file_url) ? $file_url : 'No file uploaded';
                      // error_log("↳ File Upload for {$label}: " . $data_value[$label]);
                  }
                  // Consent Field
                  else if ($field->type == 'consent') {
                      if (isset($field->checkboxLabel) && !empty($field->checkboxLabel)) {
                          $data_value[$label] = $field->checkboxLabel;
                          // error_log("↳ Consent Label for {$label}: " . $data_value[$label]);
                      }
                  }
                  // Catch-all for other fields
                  else if ($field->type == 'product' && isset($field->inputs)) {
                      $quantity_input = $field->inputs[2]['id'] ?? null;
                      $quantity_value = $quantity_input ? rgar($entry, $quantity_input) : '';
                      $data_value[$label] = $quantity_value;
                      // error_log("↳ Product Quantity for {$label}: " . $quantity_value);
                  }
                  else {
                      $final_value = !empty($value) ? $value : $raw_value;
                      $data_value[$label] = $final_value;
                      // error_log("↳ Fallback Value for {$label}: " . $final_value);
                  }
              }

              // Final log before sending to Google Sheets
              // error_log("✅ Final Data Being Sent to Sheet:\n" . print_r($data_value, true));

              $doc->add_row($data_value);

          } catch (Exception $e) {
              $error_message = $e->getMessage();
              $data['ERROR_MSG'] = $error_message;
              $data['TRACE_STK'] = $e->getTraceAsString();

              error_log("❌ Error Sending to Sheet: " . $error_message);
              GravityForms_Gs_Connector_Utility::gfgs_debug_log($data);
          }
      }
}


   //add nonce
   public function add_gf_nonce() {
      wp_nonce_field('gf-ajax-nonce', 'gf-ajax-nonce');
   }
}