<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create error log table
 * NOTE: Call this from main plugin file on activation
 */
function gscfg_create_error_log_table()
{
    global $wpdb;

    $table = $wpdb->prefix . 'gscgf_error_logs';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    error_id VARCHAR(191) NOT NULL,
    code INT NOT NULL,
    message TEXT NOT NULL,
    details LONGTEXT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY error_id (error_id),
    KEY code (code)
) {$charset};";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql);
}

if (!class_exists('gscgf_error_logs')) {

    class gscgf_error_logs
    {

        public function __construct()
        {
            add_action('admin_post_gsgf_clear_logs', [$this, 'clear_logs']);
            add_action('admin_post_gsgf_download_logs', [$this, 'download_logs']);
        }

/**
 * Static entry point to render the Error Logs admin page.
 *
 * Handles:
 * - Creates a new instance of the current class
 * - Calls the main render method to display the admin UI
 * - Provides a static access wrapper for hooks or callbacks
 *
 * @since 1.0.0
 * @return void
 */
public static function render_page()
{
    (new self())->gsgf_render_page_html();
}

/**
 * Insert error log into the database.
 *
 * Handles:
 * - Verifies if custom error logs table exists
 * - Normalizes error details (array/string/JSON)
 * - Converts JSON string details into array if valid
 * - Falls back to raw error format if not valid JSON
 * - Encodes details safely using wp_json_encode()
 * - Inserts error record with timestamp into database
 *
 * @since 1.0.0
 * @param string       $error_id Unique identifier for the error
 * @param int          $code     Error/status code
 * @param string       $message  Error message
 * @param array|string $details  Additional error details (array or JSON string)
 * @return int|false   Number of rows inserted on success, false on failure
 */
public static function log_to_db($error_id, $code, $message, $details = [])
{
    global $wpdb;

    $table = $wpdb->prefix . 'gscgf_error_logs';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        return false;
    }

            // 🔥 IMPORTANT FIX START
    if (is_string($details)) {
        $decoded = json_decode($details, true);

        if (json_last_error() === JSON_ERROR_NONE) {
                    $details = $decoded; // already JSON → convert to array
                } else {
                    $details = ['raw_error' => $details];
                }
            }
            // 🔥 IMPORTANT FIX END

            return $wpdb->insert(
                $table,
                [
                    'error_id'   => (string) $error_id,
                    'code'       => (int) $code,
                    'message'    => (string) $message,
                    'details'    => wp_json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'created_at' => current_time('mysql'),
                ],
                ['%s', '%d', '%s', '%s', '%s']
            );
        }

/**
 * Get current request context for error logging.
 *
 * Handles:
 * - Captures request URL and method
 * - Retrieves current HTTP response status code
 * - Collects client information (IP address, user agent, referrer)
 * - Adds current timestamp based on WordPress settings
 * - Returns structured context data for debugging/logging purposes
 *
 * @since 1.0.0
 * @return array Associative array containing request context details
 */
public static function get_request_context()
{
    return [
        'request_url'    => esc_url_raw($_SERVER['REQUEST_URI'] ?? ''),
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'status_code'    => http_response_code(),
        'remote_ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent'     => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'referrer'       => $_SERVER['HTTP_REFERER'] ?? '',
        'timestamp'      => current_time('mysql'),
    ];
}


/**
 * Normalize and log debug errors into the database.
 *
 * Handles:
 * - Accepts error data in string, array, or object format
 * - Attempts to decode JSON string errors
 * - Converts structured data (array/object) into log format
 * - Falls back to raw error string if not structured
 * - Stores error details using log_to_db() with standard format
 *
 * @since 1.0.0
 * @param mixed $error Error data (string, array, or object)
 * @return void
 */
public static function log_from_debug($error)
{
            // JSON string hoy to decode try karo
    if (is_string($error)) {
        $decoded = json_decode($error, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $error = $decoded;
        }
    }

    if (is_array($error) || is_object($error)) {

        self::log_to_db(
            'GravityForm_gsheet_error',
            500,
            'GravityForm Google Sheets Error',
            (array) $error
        );
    } else {

        self::log_to_db(
            'GravityForm_gsheet_error',
            500,
            'GravityForm Google Sheets Error',
            [
                'type'      => 'error',
                'raw_error' => trim((string) $error),
            ]
        );
    }
}


/**
 * Render the Error Logs admin page.
 *
 * Handles:
 * - Checks if the custom error logs table exists
 * - Fetches error logs from the database (latest first)
 * - Displays logs in a structured table format
 * - Provides actions:
 *   - Clear Logs (truncate table)
 *   - Download logs as CSV
 *   - Copy logs to clipboard
 * - Formats date/time based on WordPress settings
 * - Decodes and displays JSON error details safely
 * - Includes UI enhancements like "More info" toggle for long messages
 *
 * @since 1.0.0
 * @return void
 */
public function gsgf_render_page_html()
{
    global $wpdb;
    $table = $wpdb->prefix . 'gscgf_error_logs';

    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        echo '<div class="notice notice-error"><p>Log table not found.</p></div>';
        return;
    }

    $logs = $wpdb->get_results(
        "SELECT * FROM {$table} ORDER BY created_at DESC",
        ARRAY_A
    );
    ?>
    <div class="error-log-main shadow-box mt-40 p-30">

        <div class="error-log-head flex-wrap gap-20">
            <div>
                <div class="heading mt-0 mb-0"><?php echo esc_html__("Error Log", 'gsheetconnector-gravity-forms'); ?> </div>

                <p><?php echo esc_html__('Error logs are saved in the database. Please clear them regularly to avoid increasing the database size.', 'gsheetconnector-gravity-forms'); ?></p>

            </div>


            <?php if (!empty($logs)) : ?>

                <div class="errorlog-button-list">
                    <a href="<?php echo esc_url(
                        wp_nonce_url(
                            admin_url('admin-post.php?action=gsgf_clear_logs'),
                            'gsc_clear_logs_nonce'
                        )
                        ); ?>"
                        class="button btn-logs"><?php echo esc_html__("Clear Logs", 'gsheetconnector-gravity-forms'); ?></a>

                        <a href="<?php echo esc_url(
                            wp_nonce_url(
                                admin_url('admin-post.php?action=gsgf_download_logs'),
                                'gsc_download_logs_nonce'
                            )
                            ); ?>"
                            class="button button-primary"><?php echo esc_html__("Download CSV", 'gsheetconnector-gravity-forms'); ?></a>

                            <button type="button" id="gscgff-copy-logs-info"
                            class="button btn-logs"><?php echo esc_html__("Copy Logs", 'gsheetconnector-gravity-forms'); ?></button>
                            <div class="gsc-copy-msg d-none"></div>
                        </div>
                    <?php endif; ?>

                </div> <!-- error head #end -->


                <div class="debug-log-div">
                    <table class="widefat striped error-log-table mt-30">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__("Date", 'gsheetconnector-gravity-forms'); ?></th>
                                <th><?php echo esc_html__("Error ID", 'gsheetconnector-gravity-forms'); ?></th>
                                <th><?php echo esc_html__("Code", 'gsheetconnector-gravity-forms'); ?></th>
                                <th><?php echo esc_html__("Message", 'gsheetconnector-gravity-forms'); ?></th>
                                <th><?php echo esc_html__("Details", 'gsheetconnector-gravity-forms'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($logs): foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php
                                    $format = get_option('date_format') . ' ' . get_option('time_format');
                                    echo esc_html(mysql2date($format, $log['created_at'], false));
                                    ?></td>
                                    <td><?php echo esc_html($log['error_id']); ?></td>
                                    <td>
                                        <span class="sb-error-code" data-code="<?php echo esc_attr($log['code']); ?>">
                                            <?php echo esc_html($log['code']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($log['message']); ?></td>
                                    <td>
                                        <?php

                                        $details = (isset($log['details']) && is_string($log['details']))
                                        ? json_decode($log['details'], true)
                                        : [];

                                        if (json_last_error() === JSON_ERROR_NONE && is_array($details)) :
                                            ?>
                                        <div class="gsc-error-details">
                                            <div class="more-error-display">

                                                <?php
                                                $decoded = json_decode($log['details'], true);
                                                $display = '';

                                                if (is_array($decoded) && !empty($decoded['raw_error'])) {

                                                    $raw = $decoded['raw_error'];

                                                            // Extract text after "message:"
                                                    if (strpos($raw, 'message:') !== false) {
                                                        $parts = explode('message:', $raw);
                                                        $display = trim(end($parts));
                                                    } else {
                                                        $display = $raw;
                                                    }
                                                } else {
                                                    $display = $log['details'];
                                                }

                                                echo esc_html($display);
                                                ?>
                                            </div>
                                        </div>

                                        <?php else: ?>
                                            <?php echo esc_html($log['details']); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="5" class="text-center"><?php echo esc_html__("No error logs found.", 'gsheetconnector-gravity-forms'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div> <!-- deubg logo div show / hide --->


                <script>
                    jQuery(document).ready(function($) {

                        var debugStateKey = 'debug_log_state';

                    // ---------- More info toggle (UNCHANGED) ----------
                    $('.more-error-display').each(function() {

                       var box = $(this);
                       var maxHeight = 75;

                       box.css({
                        'max-height': maxHeight + 'px',
                        'overflow': 'hidden'
                    });

                       var clone = box.clone();
                       clone.css({
                        'max-height': 'none',
                        'height': 'auto',
                        'position': 'absolute',
                        'visibility': 'hidden',
                        'overflow': 'visible'
                    });

                       $('body').append(clone);

                       if (clone.outerHeight() > maxHeight) {
                        if (box.next('.more-error-toggle').length === 0) {
                            var link = $('<a href="#" class="more-error-toggle">More info</a>');
                            box.after(link);
                        }
                    } 

                    clone.remove();
                });

                    $(document).on('click', '.more-error-toggle', function(e) {
                        e.preventDefault();

                        var link = $(this);
                        var box = link.prev('.more-error-display');

                        if (box.hasClass('expanded')) {
                            box.removeClass('expanded').css('max-height', '75px');
                            link.text('More info');
                        } else {
                            box.addClass('expanded').css('max-height', 'none');
                            link.text('Less info');
                        }
                    });


                });
            </script>
        </div>
        <?php
    }

/**
 * Clear all error logs from the database.
 *
 * Handles:
 * - Verifies user capability (manage_options)
 * - Validates admin nonce for security
 * - Truncates custom error logs table
 * - Redirects back to referring page after execution
 *
 * @since 1.0.0
 * @return void
 */
public function clear_logs()
{
 if (!current_user_can('manage_options')) {
    wp_die('Permission denied.');
}

check_admin_referer('gsc_clear_logs_nonce');
global $wpdb;
$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}gscgf_error_logs");

wp_safe_redirect(wp_get_referer());
exit;
}

/**
 * Log JavaScript errors via AJAX request.
 *
 * Handles:
 * - Checks user capability (manage_options)
 * - Retrieves log data from POST request
 * - Decodes JSON string if log is passed as string
 * - Sanitizes and prepares error message and status
 * - Stores error details into database using log_to_db()
 * - Includes additional context like request info and payload
 * - Returns JSON success or error response
 *
 * @since 1.0.0
 * @return void
 */
public static function log_js_error()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error();
    }

    $log = $_POST['log'] ?? [];

    if (is_string($log)) {
        $decoded = json_decode($log, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $log = $decoded;
        }
    }

    self::log_to_db(
        'js_error',
        intval($log['status'] ?? 400),
        sanitize_text_field($log['message'] ?? 'JavaScript Error'),
        [
            'type'    => $log['type'] ?? 'js',
            'request' => self::get_request_context(),
            'payload' => $log,
        ]
    );

    wp_send_json_success();
}

/**
 * Download error logs as a CSV file.
 *
 * Handles:
 * - Verifies user capability (manage_options)
 * - Validates admin nonce for security
 * - Fetches error logs from custom database table
 * - Redirects back if no logs are found
 * - Sends appropriate headers for CSV file download
 * - Outputs log data in CSV format (Date, Error ID, Code, Message, Details)
 *
 * @since 1.0.0
 * @return void
 */
public function download_logs()
{

    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Permission denied.', 'gsheetconnector-gravity-forms'));
    }

    check_admin_referer('gsc_download_logs_nonce');

    global $wpdb;
    $table = $wpdb->prefix . 'gscgf_error_logs';

    $logs = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_A);

    if (empty($logs)) {
        wp_safe_redirect(wp_get_referer());
        exit;
    }

    nocache_headers();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=gsc-error-logs.csv');

    $output = fopen('php://output', 'w');

    if (false === $output) {
        exit;
    }

            // CSV Header Row
    fputcsv($output, array('Date', 'Error ID', 'Code', 'Message', 'Details'));

    foreach ($logs as $log) {
        fputcsv($output, array(
            $log['created_at'],
            $log['error_id'],
            $log['code'],
            $log['message'],
            $log['details'],
        ));
    }

            // fclose optional here (php://output auto closes)
    exit;
}
}

new gscgf_error_logs();
}
add_action('wp_ajax_gsc_log_js_error', ['gscgf_error_logs', 'log_js_error']);