<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ddlm-admin-wrap">
    <h1>LindaWP Download Manager</h1>
    
    <div class="ddlm-admin-container">
        <div class="ddlm-upload-section">
            <h2>Upload New File</h2>
            <div class="ddlm-upload-area" id="ddlm-upload-area">
                <div class="ddlm-upload-icon">📁</div>
                <p>Drag and drop file here or click to browse</p>
                <input type="file" id="ddlm-file-input" style="display: none;">
                <button type="button" class="button button-primary" id="ddlm-browse-button">Browse Files</button>
            </div>
            <div class="ddlm-upload-progress" id="ddlm-upload-progress" style="display: none;">
                <div class="ddlm-progress-bar">
                    <div class="ddlm-progress-fill" id="ddlm-progress-fill"></div>
                </div>
                <p class="ddlm-progress-text" id="ddlm-progress-text">Uploading...</p>
            </div>
        </div>
        
        <div class="ddlm-files-section">
            <h2>Uploaded Files</h2>
            <div class="ddlm-files-header">
                <button type="button" class="button" id="ddlm-refresh-files">
                    <span class="dashicons dashicons-update"></span> Refresh
                </button>
            </div>
            
            <table class="wp-list-table widefat fixed striped" id="ddlm-files-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>File Name</th>
                        <th>Size</th>
                        <th>Upload Date</th>
                        <th>Downloads</th>
                        <th>Require Email</th>
                        <th>Shortcode</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="ddlm-files-tbody">
                    <tr>
                        <td colspan="8" class="ddlm-loading">Loading files...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="ddlm-usage-section">
            <h2>How to Use</h2>
            <div class="ddlm-usage-card">
                <h3>Method 1: Button Shortcode</h3>
                <p>Use this shortcode to display a styled download button:</p>
                <code>[secure_download id="123" text="Download Now"]</code>
                <p><small>Replace <strong>123</strong> with your file ID and customize the button text.</small></p>
            </div>
            
            <div class="ddlm-usage-card">
                <h3>Method 2: URL Only</h3>
                <p>Get just the URL for custom buttons or links:</p>
                <code>[secure_download_url id="123"]</code>
                <p><small>Use this in any HTML element or page builder.</small></p>
            </div>
            
            <div class="ddlm-usage-card">
                <h3>Method 3: Custom Button</h3>
                <p>Create your own styled button:</p>
                <code>&lt;a href="[secure_download_url id='123']" class="my-button"&gt;Download&lt;/a&gt;</code>
            </div>
            
            <div class="ddlm-usage-card">
                <h3>Additional Parameters</h3>
                <ul>
                    <li><strong>id</strong> - File ID (required)</li>
                    <li><strong>text</strong> - Button text (default: "Download File")</li>
                    <li><strong>class</strong> - Custom CSS class (default: "ddlm-download-button")</li>
                </ul>
            </div>
            
            <div class="ddlm-notice">
                <span class="dashicons dashicons-info"></span>
                <p><strong>Security Note:</strong> Each download link is unique and can only be used once. After a file is downloaded, the link expires immediately and a new link must be generated.</p>
            </div>
        </div>
    </div>
</div>

<div id="ddlm-shortcode-modal" class="ddlm-modal" style="display: none;">
    <div class="ddlm-modal-content">
        <span class="ddlm-modal-close">&times;</span>
        <h2>Copy Shortcode</h2>
        <div class="ddlm-shortcode-options">
            <div class="ddlm-shortcode-option">
                <label>Button Shortcode:</label>
                <input type="text" readonly class="ddlm-shortcode-input" id="ddlm-shortcode-button">
                <button type="button" class="button button-primary ddlm-copy-btn" data-target="ddlm-shortcode-button">Copy</button>
            </div>
            <div class="ddlm-shortcode-option">
                <label>URL Only:</label>
                <input type="text" readonly class="ddlm-shortcode-input" id="ddlm-shortcode-url">
                <button type="button" class="button button-primary ddlm-copy-btn" data-target="ddlm-shortcode-url">Copy</button>
            </div>
        </div>
    </div>
</div>
