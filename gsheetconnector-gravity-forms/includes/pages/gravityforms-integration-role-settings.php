<?php
/**
 * Settings class for Gogglesheet Role settings
 * @since 1.0
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WPF_Role_Settings Class
 * @since 1.0
 */
class GravityForms_gs_role_settings
{

    /**
     * @var string group name
     */
    protected $gs_group_name = 'gs-gravityforms-settings';

    /**
     * @var string roles that can access Google Sheet page
     *@since 1.0
     */
    protected $gravityforms_gs_page_roles_setting_option_name = 'gravityforms_gs_page_roles_setting';
    /**
     * @var string roles that can access Google Sheet tab at contact form settings
     */
    protected $gravityforms_gs_tab_roles_setting_option_name = 'gravityforms_gs_tab_roles_setting';


    /**
     * Set things up.
     * @since 1.0
     */
    public function __construct()
    {
        add_action('admin_init', array($this, 'init_settings'));
    }

    // White list our options using the Settings API
    public function init_settings()
    {
        register_setting('gs-gravityforms-settings', $this->gravityforms_gs_page_roles_setting_option_name, array($this, 'validate_gs_gravityforms_access_roles'));
        register_setting('gs-gravityforms-settings', $this->gravityforms_gs_tab_roles_setting_option_name, array($this, 'validate_gs_gravityforms_access_roles'));
    }

    /**
     * do validate and sanitize selected participants
     * @param array $selected_roles
     * @return array $roles
     * @since 1.0
     */
    public function validate_gs_gravityforms_access_roles($selected_roles)
    {
        $roles = array();
        $system_roles = GravityForms_Gs_Connector_Utility::instance()->get_system_roles();


        if ($selected_roles && count($selected_roles) > 0) {

            foreach ($system_roles as $role => $display_name) {
                if (is_array($selected_roles) && in_array(esc_attr($role), $selected_roles)) {
                    // preselect specified role
                    $roles[$role] = $display_name;
                }
            }
        }
        return $roles;
    }

    public function add_role_setting_page()
    {
        if (!current_user_can('administrator')) {
            ?>
            <span class="per_not_allo">Permission Not Allowed </span>
            <?php
            return;
        }
        $gs_gravityforms_page_roles = get_option($this->gravityforms_gs_page_roles_setting_option_name);
        $gs_gravityforms_tab_roles = get_option($this->gravityforms_gs_tab_roles_setting_option_name);

        //$gs_gravityforms_tab_roles = get_option($this->gs_gravityforms_tab_roles_setting_option_name);
        ?>
        <form id="gs_gravityforms_gs_settings_form" method="post" action="options.php">
            <?php
            // adds nonce and option_page fields for the settings page
            settings_fields('gs-gravityforms-settings');
            settings_errors();
            ?>

            <div class="ff-role-settings" id="googlesheet">
                <a href="https://www.gsheetconnector.com/fluent-forms-google-sheet-connector-pro" class="pro-link"
                    target="_blank" style="text-decoration: none;"></a>
                <h2><?php echo esc_html('Roles Settings', 'gsheetconnector-gravityforms'); ?></h2>
                <div class="gs_gravityforms-gs-card">
                    <div style="margin-bottom: 20px;">
                        <label><strong><?php echo esc_html('Roles that can access Google Sheet Page', 'gsheetconnector-gravityforms'); ?></strong></label>
                    </div>
                    <?php
                    GravityForms_Gs_Connector_Utility::instance()->gravityforms_gs_checkbox_roles_multi(
                        $this->gravityforms_gs_page_roles_setting_option_name . '[]',
                        $gs_gravityforms_page_roles
                    );
                    ?>
                    <div style="margin-bottom: 20px;">
                        <label><strong><?php echo esc_html('Roles that can access Google Sheet Tab at Gravity Form Form', 'gsheetconnector-gravityforms'); ?></strong></label>
                    </div><?php
                    GravityForms_Gs_Connector_Utility::instance()->gravityforms_gs_checkbox_roles_multi(
                        $this->gravityforms_gs_tab_roles_setting_option_name . '[]',
                        $gs_gravityforms_tab_roles
                    ); ?>
                    <div class="select-info">
                        <input type="submit" class="button button-primary button-large" name="gs_gravityforms_gs_settings"
                            value="<?php echo esc_html("Save", 'gsheetconnector-gravityforms'); ?>" />
                    </div>
                </div>
            </div>

        </form>
        <?php
    }

}

$gs_gravityforms_role_settings = new GravityForms_gs_role_settings();

























