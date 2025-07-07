<?php
/*
 * Google Sheet configuration and settings page
 * @since 1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
   exit();
}

$active_tab = (isset($_GET['tab']) && sanitize_text_field($_GET["tab"])) ? sanitize_text_field($_GET['tab']) : 'integration';
// if the license info is incomplete or license status is invalid, go to the license tab
$active_tab_name = '';
if ($active_tab == 'integration') {
   $active_tab_name = 'Integration';
} elseif ($active_tab == 'Role Settings') {
   $active_tab_name = 'Role Settings';
} elseif ($active_tab == 'system-status') {
   $active_tab_name = 'System Status';
} elseif ($active_tab == 'extensions') {
   $active_tab_name = 'Extensions';
} elseif ($active_tab == 'general-setting') {
   $active_tab_name = 'General Settings';
}

$plugin_version = defined('GRAVITY_GOOGLESHEET_VERSION') ? GRAVITY_GOOGLESHEET_VERSION : 'N/A';

?>

<div class="gsheet-header">
   <div class="gsheet-logo">
      <a href="https://www.gsheetconnector.com/"><i></i></a>
   </div>
   <h1 class="gsheet-logo-text">
      <span><?php echo esc_html(__('Gravity Forms - GSheetConnector', 'gsheetconnector-gravityforms')); ?></span>
      <small><?php echo esc_html(__('Version', 'gsheetconnector-gravityforms')); ?> :
         <?php echo esc_html($plugin_version, GRAVITY_GOOGLESHEET_VERSION); ?> </small>
   </h1>
   <a href="https://support.gsheetconnector.com/kb" title="gsheet Knowledge Base" target="_blank"
      class="button gsheet-help"><i class="dashicons dashicons-editor-help"></i></a>
</div><!-- header #end -->
<span class="dashboard-gsc"><?php echo esc_html(__('DASHBOARD', 'gsheetconnector-gravityforms')); ?></span>
<span class="divider-gsc"> / </span>
<span class="modules-gsc"> <?php echo esc_html(__($active_tab_name, 'gsheetconnector-gravityforms')); ?></span>

<div class="wrap">
   <?php
   $tabs = array(
      'integration' => __('Integration', 'gsheetconnector-gravityforms'),
      'Role Settings' => __('Role Settings', 'gsheetconnector-gravityforms'),
      //'demos' => __('Demos', 'gsheetconnector-gravityforms'),
      'system-status' => __('System Status', 'gsheetconnector-gravityforms'),
      //'demos' => __('Demos', 'gsheetconnector-gravityforms'),
      'general-setting' => __('General Settings', 'gsheetconnector-gravityforms'),
      'extensions' => __('Extensions', 'gsheetconnector-gravityforms'),
   );
   echo '<div id="icon-themes" class="icon32"><br></div>';
   echo '<h2 class="nav-tab-wrapper">';
   foreach ($tabs as $tab => $name) {
      $class = ($tab == $active_tab) ? ' nav-tab-active' : '';
      echo "<a class='nav-tab$class' href='?page=gf_googlesheet&tab=$tab'>$name</a>";
   }
   echo '</h2>';
   switch ($active_tab) {
      case 'integration':
         include(GRAVITY_GOOGLESHEET_PATH . "includes/pages/gs-gravity-integration.php");
         break;
      case 'Role Settings':
         $role_settings = new GravityForms_gs_role_settings();
         $role_settings->add_role_setting_page();
         break;
      case 'system-status':
         include(GRAVITY_GOOGLESHEET_PATH . "includes/pages/gravityforms-integrate-system-info.php");
         break;
      case 'general-setting':
         include(GRAVITY_GOOGLESHEET_PATH . "includes/pages/gravityforms-general-settings.php");
         break;
      case 'extensions':
         include(GRAVITY_GOOGLESHEET_PATH . "includes/pages/extensions.php");
         break;
   }
   ?>
</div>