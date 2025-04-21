<form id="gs_settings_form" class="role-settings" method="post" action="options.php">

    <input type="hidden" name="option_page" value="gfgs-settings">
    <input type="hidden" name="action" value="update">
    <input type="hidden" id="_wpnonce" name="_wpnonce" value="967b23d431">
    <input type="hidden" name="_wp_http_referer" value="/wp-admin/admin.php?page=gf_googlesheet&amp;tab=role_settings">

    <div>
        <div class="gfgs-card">

            <div class="badge-pro"><?php _e('Upgrade to Pro', 'gsheetconnector-gravityforms'); ?></div>

            <div>
                <label><?php _e('Roles that can access Google Sheet Page', 'gsheetconnector-gravityforms'); ?></label>
            </div>

            <label class="gfpro-toggle-role">
                <input type="checkbox" class="gs-checkbox" disabled="disabled" checked="checked">
                <span class="gfpro-slider-role"></span>
            </label>
            <label style="margin-left:10px;"><?php _e('Administrator', 'gsheetconnector-gravityforms'); ?></label><br>

            <label class="gfpro-toggle-role">
                <input type="checkbox" class="gs-checkbox" name="gfgs_page_roles_setting[]" value="editor">
                <span class="gfpro-slider-role"></span>
            </label>
            <label style="margin-left:10px;"><?php _e('Editor', 'gsheetconnector-gravityforms'); ?></label><br>

            <label class="gfpro-toggle-role">
                <input type="checkbox" class="gs-checkbox" name="gfgs_page_roles_setting[]" value="author">
                <span class="gfpro-slider-role"></span>
            </label>
            <label style="margin-left:10px;"><?php _e('Author', 'gsheetconnector-gravityforms'); ?></label><br>

            <label class="gfpro-toggle-role">
                <input type="checkbox" class="gs-checkbox" name="gfgs_page_roles_setting[]" value="contributor">
                <span class="gfpro-slider-role"></span>
            </label>
            <label style="margin-left:10px;"><?php _e('Contributor', 'gsheetconnector-gravityforms'); ?></label><br>

            <label class="gfpro-toggle-role">
                <input type="checkbox" class="gs-checkbox" name="gfgs_page_roles_setting[]" value="subscriber">
                <span class="gfpro-slider-role"></span>
            </label>
            <label style="margin-left:10px;"><?php _e('Subscriber', 'gsheetconnector-gravityforms'); ?></label><br>

            <br>

            <div>
                <label><?php _e('Roles that can access Google Sheet Tab at GravityForms', 'gsheetconnector-gravityforms'); ?></label>
            </div>

            <label class="gfpro-toggle-role">
                <input type="checkbox" class="gs-checkbox" disabled="disabled" checked="checked">
                <span class="gfpro-slider-role"></span>
            </label>
            <label style="margin-left:10px;"><?php _e('Administrator', 'gsheetconnector-gravityforms'); ?></label><br>

            <label class="gfpro-toggle-role">
                <input type="checkbox" class="gs-checkbox" name="gfgs_tab_roles_setting[]" value="editor">
                <span class="gfpro-slider-role"></span>
            </label>
            <label style="margin-left:10px;"><?php _e('Editor', 'gsheetconnector-gravityforms'); ?></label><br>

            <label class="gfpro-toggle-role">
                <input type="checkbox" class="gs-checkbox" name="gfgs_tab_roles_setting[]" value="author">
                <span class="gfpro-slider-role"></span>
            </label>
            <label style="margin-left:10px;"><?php _e('Author', 'gsheetconnector-gravityforms'); ?></label><br>

            <label class="gfpro-toggle-role">
                <input type="checkbox" class="gs-checkbox" name="gfgs_tab_roles_setting[]" value="contributor">
                <span class="gfpro-slider-role"></span>
            </label>
            <label style="margin-left:10px;"><?php _e('Contributor', 'gsheetconnector-gravityforms'); ?></label><br>

            <label class="gfpro-toggle-role">
                <input type="checkbox" class="gs-checkbox" name="gfgs_tab_roles_setting[]" value="subscriber">
                <span class="gfpro-slider-role"></span>
            </label>
            <label style="margin-left:10px;"><?php _e('Subscriber', 'gsheetconnector-gravityforms'); ?></label><br>

            <br>

            <div class="select-info">
                <input type="button" class="button button-primary" name="gfgs_settings" value="<?php _e('Save', 'gsheetconnector-gravityforms'); ?>">

                <div class="upgrade-button">
                    <a href="https://www.gsheetconnector.com/gravity-forms-google-sheet-connector?gsheetconnector-ref=17" target="__blank" class="upgradeLink">
                        <?php _e('Upgrade to Pro', 'gsheetconnector-gravityforms'); ?>
                    </a>
                </div>

            </div>
        </div>
    </div>
</form>

<?php include_once(GRAVITY_GOOGLESHEET_ROOT . "/includes/pages/admin-footer.php"); ?>
