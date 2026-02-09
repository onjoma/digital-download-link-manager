<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$emails_table = $wpdb->prefix . 'ddlm_emails';

$total_emails = $wpdb->get_var("SELECT COUNT(DISTINCT email) FROM $emails_table");
$total_submissions = $wpdb->get_var("SELECT COUNT(*) FROM $emails_table");
?>

<div class="wrap ddlm-admin-wrap">
    <h1>Email List</h1>
    
    <div class="ddlm-stats-container">
        <div class="ddlm-stats-cards">
            <div class="ddlm-stat-card">
                <div class="ddlm-stat-icon">📧</div>
                <div class="ddlm-stat-content">
                    <h3>Unique Emails</h3>
                    <p class="ddlm-stat-number"><?php echo number_format($total_emails); ?></p>
                </div>
            </div>
            
            <div class="ddlm-stat-card">
                <div class="ddlm-stat-icon">📥</div>
                <div class="ddlm-stat-content">
                    <h3>Total Submissions</h3>
                    <p class="ddlm-stat-number"><?php echo number_format($total_submissions); ?></p>
                </div>
            </div>
        </div>
        
        <div class="ddlm-stats-section">
            <div class="ddlm-files-header">
                <h2>Email Submissions</h2>
                <div>
                    <button type="button" class="button" id="ddlm-refresh-emails">
                        <span class="dashicons dashicons-update"></span> Refresh
                    </button>
                    <a href="<?php echo admin_url('admin-ajax.php?action=ddlm_export_emails&nonce=' . wp_create_nonce('ddlm_admin_nonce')); ?>" class="button button-primary">
                        <span class="dashicons dashicons-download"></span> Export CSV
                    </a>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped" id="ddlm-emails-table">
                <thead>
                    <tr>
                        <th>Email Address</th>
                        <th>File Name</th>
                        <th>Submission Date</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody id="ddlm-emails-tbody">
                    <tr>
                        <td colspan="4" class="ddlm-loading">Loading emails...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="ddlm-usage-section">
            <h2>How Email Capture Works</h2>
            <div class="ddlm-usage-card">
                <h3>Enable Email Capture</h3>
                <p>In the "All Files" page, toggle the email requirement for any file. When enabled, users must enter their email address before receiving the download link.</p>
            </div>
            
            <div class="ddlm-usage-card">
                <h3>User Experience</h3>
                <p>When a user clicks a download button for a file with email capture enabled:</p>
                <ul>
                    <li>A popup appears asking for their email address</li>
                    <li>After submitting, they receive an email with the download link</li>
                    <li>The link is valid for 1 hour and can only be used once</li>
                </ul>
            </div>
            
            <div class="ddlm-usage-card">
                <h3>Export Email List</h3>
                <p>Click the "Export CSV" button above to download all collected email addresses. Use this list for your email marketing campaigns.</p>
            </div>
        </div>
    </div>
</div>
