<?php
/**
 * Plugin Name: LindaWP Download Manager
 * Plugin URI: https://lindawp.com
 * Description: Protect digital file downloads with unique, temporary one-time-use links that expire immediately after download.
 * Version: 1.0.0
 * Author: LindaWP
 * Author URI: https://lindawp.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: digital-download-link-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DDLM_VERSION', '1.0.0');
define('DDLM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DDLM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DDLM_UPLOAD_DIR', WP_CONTENT_DIR . '/ddlm-secure-files/');

class Digital_Download_Link_Manager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('query_vars', array($this, 'add_query_vars'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        add_shortcode('secure_download', array($this, 'secure_download_shortcode'));
        add_shortcode('secure_download_url', array($this, 'secure_download_url_shortcode'));
        
        add_action('template_redirect', array($this, 'handle_download_request'));
        
        add_action('wp_ajax_ddlm_upload_file', array($this, 'ajax_upload_file'));
        add_action('wp_ajax_ddlm_delete_file', array($this, 'ajax_delete_file'));
        add_action('wp_ajax_ddlm_get_files', array($this, 'ajax_get_files'));
        add_action('wp_ajax_ddlm_toggle_email_requirement', array($this, 'ajax_toggle_email_requirement'));
        add_action('wp_ajax_ddlm_submit_email', array($this, 'ajax_submit_email'));
        add_action('wp_ajax_nopriv_ddlm_submit_email', array($this, 'ajax_submit_email'));
        add_action('wp_ajax_ddlm_get_emails', array($this, 'ajax_get_emails'));
        add_action('wp_ajax_ddlm_export_emails', array($this, 'ajax_export_emails'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_init', array($this, 'handle_flush_rewrite'));
    }
    
    public function activate() {
        $this->create_database_tables();
        $this->create_upload_directory();
        $this->create_htaccess_protection();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $files_table = $wpdb->prefix . 'ddlm_files';
        $tokens_table = $wpdb->prefix . 'ddlm_tokens';
        $downloads_table = $wpdb->prefix . 'ddlm_downloads';
        
        $sql_files = "CREATE TABLE IF NOT EXISTS $files_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            file_name varchar(255) NOT NULL,
            original_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size bigint(20) UNSIGNED NOT NULL,
            mime_type varchar(100) NOT NULL,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP,
            total_downloads bigint(20) UNSIGNED DEFAULT 0,
            require_email tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY file_name (file_name)
        ) $charset_collate;";
        
        $sql_tokens = "CREATE TABLE IF NOT EXISTS $tokens_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            token varchar(64) NOT NULL,
            file_id bigint(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            is_used tinyint(1) DEFAULT 0,
            used_at datetime DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY file_id (file_id),
            KEY is_used (is_used),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        $sql_downloads = "CREATE TABLE IF NOT EXISTS $downloads_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            file_id bigint(20) UNSIGNED NOT NULL,
            token_id bigint(20) UNSIGNED NOT NULL,
            download_date datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY file_id (file_id),
            KEY download_date (download_date)
        ) $charset_collate;";
        
        $emails_table = $wpdb->prefix . 'ddlm_emails';
        $sql_emails = "CREATE TABLE IF NOT EXISTS $emails_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            file_id bigint(20) UNSIGNED NOT NULL,
            token_id bigint(20) UNSIGNED DEFAULT NULL,
            submitted_date datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY email (email),
            KEY file_id (file_id),
            KEY submitted_date (submitted_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_files);
        dbDelta($sql_tokens);
        dbDelta($sql_downloads);
        dbDelta($sql_emails);
    }
    
    private function create_upload_directory() {
        if (!file_exists(DDLM_UPLOAD_DIR)) {
            wp_mkdir_p(DDLM_UPLOAD_DIR);
        }
    }
    
    private function create_htaccess_protection() {
        $htaccess_file = DDLM_UPLOAD_DIR . '.htaccess';
        $htaccess_content = "# Deny direct access to files\n";
        $htaccess_content .= "Order Deny,Allow\n";
        $htaccess_content .= "Deny from all\n";
        
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, $htaccess_content);
        }
        
        $index_file = DDLM_UPLOAD_DIR . 'index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }
    
    public function init() {
        $this->check_database_migration();
        $this->clean_expired_tokens();
    }
    
    private function check_database_migration() {
        global $wpdb;
        $files_table = $wpdb->prefix . 'ddlm_files';
        
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s 
            AND TABLE_NAME = %s 
            AND COLUMN_NAME = 'require_email'",
            DB_NAME,
            $files_table
        ));
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $files_table ADD COLUMN require_email tinyint(1) DEFAULT 0");
        }
        
        $emails_table = $wpdb->prefix . 'ddlm_emails';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$emails_table'");
        
        if ($table_exists != $emails_table) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql_emails = "CREATE TABLE $emails_table (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                email varchar(255) NOT NULL,
                file_id bigint(20) UNSIGNED NOT NULL,
                token_id bigint(20) UNSIGNED DEFAULT NULL,
                submitted_date datetime DEFAULT CURRENT_TIMESTAMP,
                ip_address varchar(45) DEFAULT NULL,
                PRIMARY KEY (id),
                KEY email (email),
                KEY file_id (file_id),
                KEY submitted_date (submitted_date)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_emails);
        }
    }
    
    public function add_rewrite_rules() {
        add_rewrite_rule('^ddlm-download/([^/]+)/?$', 'index.php?ddlm_token=$matches[1]', 'top');
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'ddlm_token';
        return $vars;
    }
    
    private function clean_expired_tokens() {
        global $wpdb;
        $tokens_table = $wpdb->prefix . 'ddlm_tokens';
        
        if (rand(1, 100) <= 5) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $tokens_table WHERE expires_at < %s AND is_used = 0",
                current_time('mysql')
            ));
        }
    }
    
    public function admin_notices() {
        if (get_transient('ddlm_flush_rewrite_notice')) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong>LindaWP Download Manager:</strong> Download links may not work properly. <a href="<?php echo admin_url('admin.php?page=ddlm-manager&ddlm_flush=1'); ?>">Click here to fix</a></p>
            </div>
            <?php
        }
    }
    
    public function handle_flush_rewrite() {
        if (isset($_GET['ddlm_flush']) && $_GET['ddlm_flush'] == '1' && current_user_can('manage_options')) {
            $this->add_rewrite_rules();
            flush_rewrite_rules();
            delete_transient('ddlm_flush_rewrite_notice');
            wp_redirect(admin_url('admin.php?page=ddlm-manager&flushed=1'));
            exit;
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Linda Downloads',
            'Linda Downloads',
            'manage_options',
            'ddlm-manager',
            array($this, 'render_admin_page'),
            'dashicons-download',
            30
        );
        
        add_submenu_page(
            'ddlm-manager',
            'All Files',
            'All Files',
            'manage_options',
            'ddlm-manager',
            array($this, 'render_admin_page')
        );
        
        add_submenu_page(
            'ddlm-manager',
            'Download Statistics',
            'Statistics',
            'manage_options',
            'ddlm-statistics',
            array($this, 'render_statistics_page')
        );
        
        add_submenu_page(
            'ddlm-manager',
            'Email List',
            'Email List',
            'manage_options',
            'ddlm-emails',
            array($this, 'render_emails_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ddlm-') === false) {
            return;
        }
        
        wp_enqueue_style('ddlm-admin-css', DDLM_PLUGIN_URL . 'assets/css/admin.css', array(), DDLM_VERSION);
        wp_enqueue_script('ddlm-admin-js', DDLM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), DDLM_VERSION, true);
        
        wp_localize_script('ddlm-admin-js', 'ddlmAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ddlm_admin_nonce')
        ));
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('ddlm-frontend-css', DDLM_PLUGIN_URL . 'assets/css/frontend.css', array(), DDLM_VERSION);
        wp_enqueue_script('ddlm-frontend-js', DDLM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), DDLM_VERSION, true);
        
        wp_localize_script('ddlm-frontend-js', 'ddlmFrontend', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ddlm_frontend_nonce')
        ));
    }
    
    public function render_admin_page() {
        include DDLM_PLUGIN_DIR . 'includes/admin-page.php';
    }
    
    public function render_statistics_page() {
        include DDLM_PLUGIN_DIR . 'includes/statistics-page.php';
    }
    
    public function render_emails_page() {
        include DDLM_PLUGIN_DIR . 'includes/emails-page.php';
    }
    
    public function ajax_upload_file() {
        check_ajax_referer('ddlm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        if (empty($_FILES['file'])) {
            wp_send_json_error('No file uploaded');
        }
        
        $file = $_FILES['file'];
        
        $allowed_types = apply_filters('ddlm_allowed_file_types', array(
            'pdf', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'jpg', 'jpeg', 'png', 'gif', 'mp3', 'mp4', 'avi', 'mov',
            'txt', 'csv', 'epub', 'mobi', 'psd', 'ai', 'svg'
        ));
        
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            wp_send_json_error('File type not allowed');
        }
        
        $max_size = apply_filters('ddlm_max_file_size', 100 * 1024 * 1024);
        if ($file['size'] > $max_size) {
            wp_send_json_error('File size exceeds limit');
        }
        
        $unique_filename = uniqid() . '_' . sanitize_file_name($file['name']);
        $upload_path = DDLM_UPLOAD_DIR . $unique_filename;
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            global $wpdb;
            $files_table = $wpdb->prefix . 'ddlm_files';
            
            $wpdb->insert($files_table, array(
                'file_name' => $unique_filename,
                'original_name' => sanitize_file_name($file['name']),
                'file_path' => $upload_path,
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
                'upload_date' => current_time('mysql')
            ));
            
            $file_id = $wpdb->insert_id;
            
            wp_send_json_success(array(
                'id' => $file_id,
                'name' => sanitize_file_name($file['name']),
                'size' => size_format($file['size']),
                'date' => current_time('mysql')
            ));
        } else {
            wp_send_json_error('Failed to upload file');
        }
    }
    
    public function ajax_delete_file() {
        check_ajax_referer('ddlm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $file_id = intval($_POST['file_id']);
        
        global $wpdb;
        $files_table = $wpdb->prefix . 'ddlm_files';
        
        $file = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $files_table WHERE id = %d",
            $file_id
        ));
        
        if (!$file) {
            wp_send_json_error('File not found');
        }
        
        if (file_exists($file->file_path)) {
            unlink($file->file_path);
        }
        
        $wpdb->delete($files_table, array('id' => $file_id));
        
        wp_send_json_success('File deleted successfully');
    }
    
    public function ajax_get_files() {
        check_ajax_referer('ddlm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        $files_table = $wpdb->prefix . 'ddlm_files';
        
        $files = $wpdb->get_results("SELECT * FROM $files_table ORDER BY upload_date DESC");
        
        $formatted_files = array();
        foreach ($files as $file) {
            $formatted_files[] = array(
                'id' => $file->id,
                'name' => $file->original_name,
                'size' => size_format($file->file_size),
                'date' => date('Y-m-d H:i:s', strtotime($file->upload_date)),
                'downloads' => $file->total_downloads,
                'require_email' => $file->require_email,
                'shortcode' => '[secure_download id="' . $file->id . '"]'
            );
        }
        
        wp_send_json_success($formatted_files);
    }
    
    public function ajax_toggle_email_requirement() {
        check_ajax_referer('ddlm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $file_id = intval($_POST['file_id']);
        $require_email = intval($_POST['require_email']);
        
        global $wpdb;
        $files_table = $wpdb->prefix . 'ddlm_files';
        
        $wpdb->update(
            $files_table,
            array('require_email' => $require_email),
            array('id' => $file_id)
        );
        
        wp_send_json_success('Email requirement updated');
    }
    
    public function ajax_submit_email() {
        check_ajax_referer('ddlm_frontend_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $file_id = intval($_POST['file_id']);
        
        if (!is_email($email)) {
            wp_send_json_error('Invalid email address');
        }
        
        global $wpdb;
        $files_table = $wpdb->prefix . 'ddlm_files';
        $emails_table = $wpdb->prefix . 'ddlm_emails';
        
        $file = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $files_table WHERE id = %d",
            $file_id
        ));
        
        if (!$file) {
            wp_send_json_error('File not found');
        }
        
        $token = $this->generate_token($file_id);
        $download_url = add_query_arg('ddlm_token', $token, home_url('/'));
        
        $token_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ddlm_tokens WHERE token = %s",
            $token
        ));
        
        $wpdb->insert($emails_table, array(
            'email' => $email,
            'file_id' => $file_id,
            'token_id' => $token_id,
            'submitted_date' => current_time('mysql'),
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''
        ));
        
        $to = $email;
        $subject = 'Your Download Link - ' . $file->original_name;
        $message = $this->get_email_template($file->original_name, $download_url);
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $sent = wp_mail($to, $subject, $message, $headers);
        
        if ($sent) {
            wp_send_json_success(array(
                'message' => 'Download link sent to your email!',
                'email' => $email,
                'download_url' => ''
            ));
        } else {
            wp_send_json_success(array(
                'message' => 'Email saved! Here is your download link:',
                'email' => $email,
                'download_url' => $download_url,
                'note' => 'Email sending is not configured on this server, but your download link is ready below.'
            ));
        }
    }
    
    public function ajax_get_emails() {
        check_ajax_referer('ddlm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        $emails_table = $wpdb->prefix . 'ddlm_emails';
        $files_table = $wpdb->prefix . 'ddlm_files';
        
        $emails = $wpdb->get_results("
            SELECT e.*, f.original_name 
            FROM $emails_table e
            LEFT JOIN $files_table f ON e.file_id = f.id
            ORDER BY e.submitted_date DESC
            LIMIT 100
        ");
        
        $formatted_emails = array();
        foreach ($emails as $email) {
            $formatted_emails[] = array(
                'id' => $email->id,
                'email' => $email->email,
                'file_name' => $email->original_name,
                'date' => date('Y-m-d H:i:s', strtotime($email->submitted_date)),
                'ip' => $email->ip_address
            );
        }
        
        wp_send_json_success($formatted_emails);
    }
    
    public function ajax_export_emails() {
        check_ajax_referer('ddlm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $emails_table = $wpdb->prefix . 'ddlm_emails';
        $files_table = $wpdb->prefix . 'ddlm_files';
        
        $emails = $wpdb->get_results("
            SELECT e.email, f.original_name, e.submitted_date, e.ip_address
            FROM $emails_table e
            LEFT JOIN $files_table f ON e.file_id = f.id
            ORDER BY e.submitted_date DESC
        ");
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="download-emails-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Email', 'File Name', 'Date', 'IP Address'));
        
        foreach ($emails as $email) {
            fputcsv($output, array(
                $email->email,
                $email->original_name,
                $email->submitted_date,
                $email->ip_address
            ));
        }
        
        fclose($output);
        exit;
    }
    
    private function get_email_template($file_name, $download_url) {
        $template = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2271b1 0%, #135e96 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; background: linear-gradient(135deg, #2271b1 0%, #135e96 100%); color: white !important; text-decoration: none; padding: 15px 40px; border-radius: 6px; font-weight: bold; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Your Download is Ready!</h1>
                </div>
                <div class="content">
                    <p>Thank you for your interest in <strong>' . esc_html($file_name) . '</strong>.</p>
                    <p>Click the button below to download your file:</p>
                    <p style="text-align: center;">
                        <a href="' . esc_url($download_url) . '" class="button">Download Now</a>
                    </p>
                    <p><strong>Important:</strong> This download link is valid for 1 hour and can only be used once for security purposes.</p>
                    <p>If you have any issues with your download, please contact support.</p>
                </div>
                <div class="footer">
                    <p>This email was sent because you requested a download link.</p>
                    <p>&copy; ' . date('Y') . ' ' . get_bloginfo('name') . '</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        return $template;
    }
    
    private function generate_token($file_id) {
        global $wpdb;
        $tokens_table = $wpdb->prefix . 'ddlm_tokens';
        
        $token = bin2hex(random_bytes(32));
        
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $wpdb->insert($tokens_table, array(
            'token' => $token,
            'file_id' => $file_id,
            'created_at' => current_time('mysql'),
            'expires_at' => $expires_at,
            'is_used' => 0
        ));
        
        return $token;
    }
    
    public function secure_download_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'text' => 'Download File',
            'class' => 'ddlm-download-button'
        ), $atts);
        
        $file_id = intval($atts['id']);
        
        if ($file_id <= 0) {
            return '<p class="ddlm-error">Invalid file ID</p>';
        }
        
        global $wpdb;
        $files_table = $wpdb->prefix . 'ddlm_files';
        
        $file = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $files_table WHERE id = %d",
            $file_id
        ));
        
        if (!$file) {
            return '<p class="ddlm-error">File not found</p>';
        }
        
        if ($file->require_email == 1) {
            return sprintf(
                '<a href="#" class="%s ddlm-email-required" data-file-id="%d" data-file-name="%s">%s</a>',
                esc_attr($atts['class']),
                $file_id,
                esc_attr($file->original_name),
                esc_html($atts['text'])
            );
        } else {
            $token = $this->generate_token($file_id);
            $download_url = add_query_arg('ddlm_token', $token, home_url('/'));
            
            return sprintf(
                '<a href="%s" class="%s">%s</a>',
                esc_url($download_url),
                esc_attr($atts['class']),
                esc_html($atts['text'])
            );
        }
    }
    
    public function secure_download_url_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);
        
        $file_id = intval($atts['id']);
        
        if ($file_id <= 0) {
            return '';
        }
        
        global $wpdb;
        $files_table = $wpdb->prefix . 'ddlm_files';
        
        $file = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $files_table WHERE id = %d",
            $file_id
        ));
        
        if (!$file) {
            return '';
        }
        
        $token = $this->generate_token($file_id);
        $download_url = add_query_arg('ddlm_token', $token, home_url('/'));
        
        return esc_url($download_url);
    }
    
    public function handle_download_request() {
        $token = get_query_var('ddlm_token');
        
        if (empty($token)) {
            return;
        }
        
        $token = sanitize_text_field($token);
        
        global $wpdb;
        $tokens_table = $wpdb->prefix . 'ddlm_tokens';
        $files_table = $wpdb->prefix . 'ddlm_files';
        $downloads_table = $wpdb->prefix . 'ddlm_downloads';
        
        $referer = wp_get_referer();
        $back_link = $referer ? $referer : home_url('/');
        
        $token_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tokens_table WHERE token = %s",
            $token
        ));
        
        if (!$token_data) {
            $this->display_download_error(
                'Invalid or Expired Link',
                'This download link is invalid or has expired.',
                $back_link
            );
        }
        
        if (strtotime($token_data->expires_at) < time()) {
            $this->display_download_error(
                'Link Expired',
                'This download link has expired (valid for 1 hour).',
                $back_link
            );
        }
        
        $download_completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $downloads_table WHERE token_id = %d",
            $token_data->id
        ));
        
        if ($download_completed > 0) {
            $this->display_download_error(
                'Link Already Used',
                'This download link has already been used. Each link can only be used once for security.',
                $back_link
            );
        }
        
        $file = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $files_table WHERE id = %d",
            $token_data->file_id
        ));
        
        if (!$file || !file_exists($file->file_path)) {
            wp_die('File not found');
        }
        
        $wpdb->update(
            $tokens_table,
            array(
                'is_used' => 1,
                'used_at' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
            ),
            array('id' => $token_data->id)
        );
        
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        nocache_headers();
        
        header('Content-Type: ' . $file->mime_type);
        header('Content-Disposition: attachment; filename="' . $file->original_name . '"');
        header('Content-Length: ' . $file->file_size);
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        
        set_time_limit(0);
        
        $handle = fopen($file->file_path, 'rb');
        
        if ($handle === false) {
            wp_die('Unable to open file');
        }
        
        while (!feof($handle)) {
            if (connection_aborted()) {
                fclose($handle);
                exit;
            }
            
            echo fread($handle, 8192);
            flush();
        }
        
        fclose($handle);
        
        $wpdb->insert($downloads_table, array(
            'file_id' => $file->id,
            'token_id' => $token_data->id,
            'download_date' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
        ));
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $files_table SET total_downloads = total_downloads + 1 WHERE id = %d",
            $file->id
        ));
        
        exit;
    }
    
    private function display_download_error($title, $message, $back_url) {
        wp_die(
            '<div style="text-align: center; padding: 40px 20px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen-Sans, Ubuntu, Cantarell, \'Helvetica Neue\', sans-serif;">
                <div style="max-width: 500px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div style="font-size: 48px; margin-bottom: 20px;">🔒</div>
                    <h2 style="color: #d63638; margin: 0 0 15px 0; font-size: 24px;">' . esc_html($title) . '</h2>
                    <p style="color: #646970; font-size: 16px; line-height: 1.6; margin: 0 0 30px 0;">' . esc_html($message) . '</p>
                    <a href="' . esc_url($back_url) . '" style="display: inline-block; background: linear-gradient(135deg, #2271b1 0%, #135e96 100%); color: #fff; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-weight: 600; font-size: 16px; transition: all 0.3s ease;">
                        ← Get New Download Link
                    </a>
                    <p style="color: #8c8f94; font-size: 13px; margin: 25px 0 0 0;">
                        Click the button above to return to the page and generate a fresh download link.
                    </p>
                </div>
            </div>',
            $title,
            array('response' => 403)
        );
    }
}

Digital_Download_Link_Manager::get_instance();
