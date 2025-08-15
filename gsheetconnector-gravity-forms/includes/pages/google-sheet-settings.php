<?php
/*
 * Google Sheet configuration and settings page
 * @since 1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
   exit();
}

$active_tab = 'integration';

if (isset($_GET['tab'])) {
   $tab = sanitize_text_field(wp_unslash($_GET['tab']));

   $active_tab = $tab;
}
// if the license info is incomplete or license status is invalid, go to the license tab
$active_tab_name = '';
if ($active_tab == 'integration') {
   $active_tab_name = 'Integration';
} elseif ($active_tab == 'system-status') {
   $active_tab_name = 'System Status';
} elseif ($active_tab == 'extensions') {
   $active_tab_name = 'Extensions';
} 
// elseif ($active_tab == 'general-setting') {
//    $active_tab_name = 'General Settings';
// }

$plugin_version = defined('GRAVITY_GOOGLESHEET_VERSION') ? GRAVITY_GOOGLESHEET_VERSION : 'N/A';

?>

<div class="gsheet-header">
   <div class="gsheet-logo">
      <a href="https://www.gsheetconnector.com/"><i></i></a>
   </div>
   <h1 class="gsheet-logo-text">
      <span><?php echo esc_html(__('Gravity Forms - GSheetConnector', 'gsheetconnector-gravity-forms')); ?></span>
      <small><?php echo esc_html(__('Version', 'gsheetconnector-gravity-forms')); ?> :
         <?php echo esc_html($plugin_version, GRAVITY_GOOGLESHEET_VERSION); ?> </small>
   </h1>
  
	<ul>
		<li><a href="<?php echo admin_url( 'admin.php?page=gf_googlesheet&tab=extensions', 'gsheetconnector-gravity-forms' ); ?>" title="Extensions">
          <i class="fa-solid fa-puzzle-piece"></i></a></li>
        <li><a href="https://www.gsheetconnector.com/docs/gravity-forms-gsheetconnector" title="Document" target="_blank" rel="noopener noreferrer"><i class="fa-regular fa-file-lines"></i></a></li>
        <li><a href="https://www.gsheetconnector.com/support" title="Support" target="_blank" rel="noopener noreferrer"><i class="fa-regular fa-life-ring"></i></a></li>
        <li><a href="https://wordpress.org/plugins/gsheetconnector-gravity-forms/#developers" title="Changelog" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-bullhorn"></i></a></li>
    </ul>
	
</div><!-- header #end -->

<div class="breadcrumb">
<span class="dashboard-gsc"><?php echo esc_html(__('DASHBOARD', 'gsheetconnector-gravity-forms')); ?></span>
<span class="divider-gsc"> / </span>
<span class="modules-gsc"> <?php echo esc_html($active_tab_name) ?></span>
</div>
	

   <?php
   $tabs = array(
      'integration' => __('Integration', 'gsheetconnector-gravity-forms'),
      //'Role Settings' => __('Role Settings', 'gsheetconnector-gravity-forms'),
      //'demos' => __('Demos', 'gsheetconnector-gravity-forms'),
      'system-status' => __('System Status', 'gsheetconnector-gravity-forms'),
      //'demos' => __('Demos', 'gsheetconnector-gravity-forms'),
      // 'general-setting' => __('General Settings', 'gsheetconnector-gravity-forms'),
      'extensions' => __('Extensions', 'gsheetconnector-gravity-forms'),
   );
   echo '<div id="icon-themes" class="icon32"><br></div>';
   echo '<div class="nav-tab-wrapper">';
   foreach ($tabs as $tab => $name) {
      $class = ($tab == $active_tab) ? ' nav-tab-active' : '';
      echo '<a class="nav-tab' . esc_attr($class) . '" href="?page=gf_googlesheet&amp;tab=' . esc_attr($tab) . '">'
         . esc_html($name) .
         '</a>';

   }
   echo '</div> <div class="wrap-gsc">';
   switch ($active_tab) {
      case 'integration':
         include(GRAVITY_GOOGLESHEET_PATH . "includes/pages/gs-gravity-integration.php");
         break;
      case 'system-status':
         include(GRAVITY_GOOGLESHEET_PATH . "includes/pages/gravityforms-integrate-system-info.php");
         break;
      // case 'general-setting':
      //    include(GRAVITY_GOOGLESHEET_PATH . "includes/pages/gravityforms-general-settings.php");
      //    break;
      case 'extensions':
         include(GRAVITY_GOOGLESHEET_PATH . "includes/pages/extensions.php");
         break;
   }
   ?>
</div>