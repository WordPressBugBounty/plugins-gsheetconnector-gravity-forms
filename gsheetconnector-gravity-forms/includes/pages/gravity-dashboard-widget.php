<?php
/*
 * Gravity Forms Google sheet connector Dashboard Widget
 * @since 1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}
?>
<div class="dashboard-content">
    <?php
    $gs_connector_service = new GFGS_Connector_Service();

    $forms_list = $gs_connector_service->get_forms_connected_to_sheet();
    ?>
    <div class="main-content">
        <div>
            <h3><?php echo esc_html("Gravity Forms connected with Google Sheets.", "gsheetconnector-gravity-forms"); ?>
            </h3>

            <style>
                .widget-table {
                    border: 1px solid #eee;
                    width: 100%;
                }

                .widget-table th {
                    text-align: left;
                    background: #eee;
                    padding: 2px 3px;
                    border-bottom: 1px solid #eee;
                }

                .widget-table td {
                    text-align: left;
                    background: #fff;
                    padding: 2px 3px;
                    word-wrap: break-word;
                }

                .widget-table td:nth-child(1) {
                    width: 50%;
                }
            </style>


            <table class="widget-table">
                <tbody>
                    <tr>
                        <th>Form Name</th>
                        <!-- <th>Sheet URL</th> -->
                    </tr>
                    <?php


                    if (!empty($forms_list)) {
                        foreach ($forms_list as $key => $value) {
                            if (!empty($value->title)) {
                                // Assuming $value->sheet_url contains the URL for the connected Google Sheet
                                // $sheet_url = !empty($value->sheet_url) ? esc_url($value->sheet_url) : '#'; // Use '#' if no URL is provided
                                // Assuming $value->sheet_name contains the name of the Google Sheet
                                // $sheet_name = !empty($value->sheet_name) ? esc_html($value->sheet_name) : __('No Sheet Name available', 'gsheetconnector-gravity-forms');
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=gf_edit_forms&view=settings&subview=gsheetconnector-gravity-forms&id=' . intval($value->id))); ?>"
                                            target="_blank">
                                            <?php echo esc_html($value->title); ?>
                                        </a>

                                    </td>

                                </tr>
                                <?php
                            }
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="2">
                                <?php echo esc_html("No Gravity Forms are connected with Google Sheets.", "gsheetconnector-gravity-forms"); ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>



        </div>
    </div> <!-- main-content end -->
</div> <!-- dashboard-content end -->
<style type="text/css">
    .postbox-header .hndle {
        justify-content: flex-start !important;
    }
</style>