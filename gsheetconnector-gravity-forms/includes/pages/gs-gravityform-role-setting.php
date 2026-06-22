<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
if (!current_user_can('administrator')) {
?>
    <span class="per_not_allo"><?php echo esc_html__("Permission Not Allowed", 'gsheetconnector-gravity-forms'); ?></span>
<?php
    return;
}
$gsheetconnector_allowed_accress_roles  = array('administrator', 'editor', 'author', 'contributor');
$participating_roles = array();
$editable_roles = get_editable_roles();

foreach ($editable_roles as $role => $details) {

    if (in_array($role, $gsheetconnector_allowed_accress_roles )) {
        $participating_roles[$role] = $details['name'];
    }
}
?>
<form id="gsc_gravityform_settings_form" method="post" action="options.php">
    <!--Start Pro Setting(Roll Permissions)-->
    <div class="gsc-pro-promo ml-15 mr-pro-15">

        <div class="gsc-pro-header">
            <div class="gsc-pro-icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 19c-1 1-2 1-3 1 0-1 0-2 1-3l4-4"></path>
                    <path d="M14 3l7 7"></path>
                    <path d="M9 18l-4 4"></path>
                    <path d="M15 3c2 0 6 4 6 6-2 2-6 6-8 8l-6-6c2-2 6-8 8-8z"></path>
                    <circle cx="15" cy="9" r="1.5"></circle>
                </svg>

            </div>

            <div>
                <div class="unlock-header">
                    <?php echo esc_html(__('Unlock Role-Based Access Control', 'gsheetconnector-gravity-forms')); ?>
                </div>
                <span
                    class="gsc-pro-badge"><?php echo esc_html(__('Advanced options are available in PRO', 'gsheetconnector-gravity-forms')); ?></span>
            </div>
        </div>

        <!-- Feature Tabs -->
        <div class="gsc-pro-tabs pt-20 pb-20 pl-20 pr-20">
            <div>
                <div class="mb-20 fw-600 text-dark pro-roll-sub-header">
                    <?php echo esc_html(__('Role Permissions', 'gsheetconnector-gravity-forms')); ?></div>
                <div class="gsc-pro-grid">
                    <ul>
                        <li><?php esc_html_e('Allow specific WordPress roles', 'gsheetconnector-gravity-forms'); ?></li>
                        <li><?php esc_html_e('Enable/disable integration access', 'gsheetconnector-gravity-forms'); ?>
                        </li>
                        <li><?php esc_html_e('Control form feed visibility', 'gsheetconnector-gravity-forms'); ?></li>
                        <li><?php esc_html_e('Restrict settings management', 'gsheetconnector-gravity-forms'); ?></li>
                    </ul>
                </div>
            </div>

            <div>
                <div class="mb-20 fw-600 text-dark pro-roll-sub-header">
                    <?php echo esc_html(__('Security Control', 'gsheetconnector-gravity-forms')); ?></div>
                <div class="gsc-pro-grid">
                    <ul>
                        <li><?php esc_html_e('Prevent unauthorized changes', 'gsheetconnector-gravity-forms'); ?></li>
                        <li><?php esc_html_e('Secure Google Sheet credentials', 'gsheetconnector-gravity-forms'); ?>
                        </li>
                        <li><?php esc_html_e('Role-based configuration control', 'gsheetconnector-gravity-forms'); ?>
                        </li>
                        <li><?php esc_html_e('Protect integration settings', 'gsheetconnector-gravity-forms'); ?></li>
                    </ul>
                </div>
            </div>

            <div>
                <div class="mb-20 fw-600 text-dark pro-roll-sub-header">
                    <?php echo esc_html(__('Management Benefits', 'gsheetconnector-gravity-forms')); ?></div>
                <div class="gsc-pro-grid">
                    <ul>
                        <li><?php esc_html_e('Grant access to trusted editors', 'gsheetconnector-gravity-forms'); ?>
                        </li>
                        <li><?php esc_html_e('Hide settings from subscribers', 'gsheetconnector-gravity-forms'); ?></li>
                        <li><?php esc_html_e('Team-based permission structure', 'gsheetconnector-gravity-forms'); ?>
                        </li>
                        <li><?php esc_html_e('Improved dashboard security', 'gsheetconnector-gravity-forms'); ?></li>
                    </ul>
                </div>
            </div>

            <div>
                <div class="mb-20 fw-600 text-dark pro-roll-sub-header">
                    <?php echo esc_html(__('Audit & Monitoring', 'gsheetconnector-gravity-forms')); ?></div>
                <div class="gsc-pro-grid">
                    <ul>
                        <li><?php esc_html_e('Track role-based changes', 'gsheetconnector-gravity-forms'); ?></li>
                        <li><?php esc_html_e('Monitor integration access', 'gsheetconnector-gravity-forms'); ?></li>
                        <li><?php esc_html_e('Review permission updates', 'gsheetconnector-gravity-forms'); ?></li>
                        <li><?php esc_html_e('Maintain admin accountability', 'gsheetconnector-gravity-forms'); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="gsc-pro-footer text-center">
            <a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector" target="_blank"
                class="btn btn-primary text-decoration-none link-hover-white">
                <?php echo esc_html(__('Upgrade to Unlock', 'gsheetconnector-gravity-forms')); ?>
            </a>
        </div>

    </div>
    <!--End Pro Setting(Roll Permissions)-->

    <div class="gscgff-role-settings" id="gsc-googlesheet">
        <div class="wrap w-100 m-0">
            <div class="inner-wrap w-100 bg-white p-40 blur-pro-feature">
                <div class="heading mt-0"><?php echo esc_html__('Access Management', 'gsheetconnector-gravity-forms'); ?>
                </div>
                <p><?php echo esc_html__('Control which user roles are allowed to access and manage the Google Sheets integration for your forms.', 'gsheetconnector-gravity-forms'); ?>
                </p>
                <div class="gscgff_gravityform-card">
                    <?php



                    $selected_row = '';
                    $selected_row .= "<div class='gsc-access-wrapper mt-30'>";
                    $selected_row .= "<div class='gsc-access-box bg-white pt-15 pb-15 pl-15 pr-15'>
        <div class='para-heading fw-600 mb-20'>" . esc_html__('Plugin Access Control', 'gsheetconnector-gravity-forms') . "</div>";


                    $selected_row .= "<div class='gsc-role-card mb-10'>
        <div class='custom-check d-flex justify-between alien-center'>
        <label class='role-label gsc-switch'> ";
                    $selected_row .= __("Administrator", 'gsheetconnector-gravity-forms');
                    $selected_row .= "</label>";
                    $selected_row .= "<input type='checkbox' class='check-toggle' disabled='disabled' checked='checked' /><label class='button-toggle'></label>";
                    $selected_row .= "
        </div>";
                    $selected_row .= "
        </div>";
                    foreach ($participating_roles as $role => $display_name) {
                        if ($role === "administrator") {
                            continue;
                        }
                        if (!empty($roles) && is_array($roles) && in_array(esc_attr($role), $roles)) { // preselect specified role
                            $checked = " checked='checked'";
                        } else {
                            $checked = '';
                        }

                        $selected_row .= "<div class='gsc-role-card mb-10'>
                        <div class='custom-check d-flex justify-between alien-center'><label class='role-label gsc-switch'>";
                        $selected_row .= esc_html($display_name, 'gsheetconnector-gravity-forms');
                        $selected_row .= "</label><input type='checkbox' class='' check-toggle'
                        name='" . esc_attr($role) . "[]' value='" . esc_attr($role) . "'" . $checked . " />";
                        $selected_row .= "<label class='button-toggle'></label>";
                        $selected_row .= "</div>
                        </div>";
                    }



                    echo wp_kses($selected_row, array(
                        'div' => array('class' => array()),
                        'label' => array('class' => array(), 'for' => array()),
                        'input' => array(
                            'type' => array(),
                            'class' => array(),
                            'name' => array(),
                            'value' => array(),
                            'checked' => array(),
                            'disabled' => array()
                        ),
                        'span' => array('class' => array())
                    ));
                    ?>
                </div>
                <div class="gsc-access-info">
                    <div class="para-heading fw-600 mb-20">
                        <?php echo esc_html__('Permission Guidelines', 'gsheetconnector-gravity-forms'); ?></div>
                    <ul>
                        <li><?php echo esc_html__('Control which user roles can access the GSheetConnector plugin', 'gsheetconnector-gravity-forms'); ?>Grant
                            access only to users you trust</li>
                        <li><?php echo esc_html__('Allow selected users to manage Google Sheets integration settings', 'gsheetconnector-gravity-forms'); ?>
                        </li>
                        <li><?php echo esc_html__('Restrict access to sensitive features like feeds, logs, and configurations', 'gsheetconnector-gravity-forms'); ?>
                        </li>
                        <li><?php echo esc_html__('Only enabled roles will see the plugin menu in the admin panel', 'gsheetconnector-gravity-forms'); ?>
                        </li>
                        <li><?php echo esc_html__('Recommended: Allow only Administrators and trusted Editors', 'gsheetconnector-gravity-forms'); ?>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="">
                <div class="select-info text-right mt-30">
                    <input type="submit" class="btn btn-primary button-large" name="gscgff_gravityform_settings"
                        value="<?php echo esc_html__("Save Settings", 'gsheetconnector-gravity-forms'); ?>" />
                </div>
            </div>
        </div>
    </div>
</form>
</div>
</div>