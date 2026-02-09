<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$files_table = $wpdb->prefix . 'ddlm_files';
$downloads_table = $wpdb->prefix . 'ddlm_downloads';

$total_files = $wpdb->get_var("SELECT COUNT(*) FROM $files_table");
$total_downloads = $wpdb->get_var("SELECT COUNT(*) FROM $downloads_table");

$recent_downloads = $wpdb->get_results("
    SELECT d.*, f.original_name 
    FROM $downloads_table d
    LEFT JOIN $files_table f ON d.file_id = f.id
    ORDER BY d.download_date DESC
    LIMIT 50
");

$top_files = $wpdb->get_results("
    SELECT f.*, COUNT(d.id) as download_count
    FROM $files_table f
    LEFT JOIN $downloads_table d ON f.id = d.file_id
    GROUP BY f.id
    ORDER BY download_count DESC
    LIMIT 10
");
?>

<div class="wrap ddlm-admin-wrap">
    <h1>Download Statistics</h1>
    
    <div class="ddlm-stats-container">
        <div class="ddlm-stats-cards">
            <div class="ddlm-stat-card">
                <div class="ddlm-stat-icon">📁</div>
                <div class="ddlm-stat-content">
                    <h3>Total Files</h3>
                    <p class="ddlm-stat-number"><?php echo number_format($total_files); ?></p>
                </div>
            </div>
            
            <div class="ddlm-stat-card">
                <div class="ddlm-stat-icon">⬇️</div>
                <div class="ddlm-stat-content">
                    <h3>Total Downloads</h3>
                    <p class="ddlm-stat-number"><?php echo number_format($total_downloads); ?></p>
                </div>
            </div>
            
            <div class="ddlm-stat-card">
                <div class="ddlm-stat-icon">📊</div>
                <div class="ddlm-stat-content">
                    <h3>Average per File</h3>
                    <p class="ddlm-stat-number">
                        <?php echo $total_files > 0 ? number_format($total_downloads / $total_files, 1) : '0'; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="ddlm-stats-section">
            <h2>Top Downloaded Files</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>File Name</th>
                        <th>Size</th>
                        <th>Upload Date</th>
                        <th>Downloads</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_files)): ?>
                        <tr>
                            <td colspan="5">No files found</td>
                        </tr>
                    <?php else: ?>
                        <?php $rank = 1; foreach ($top_files as $file): ?>
                            <tr>
                                <td><?php echo $rank++; ?></td>
                                <td><?php echo esc_html($file->original_name); ?></td>
                                <td><?php echo size_format($file->file_size); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($file->upload_date)); ?></td>
                                <td><strong><?php echo number_format($file->download_count); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="ddlm-stats-section">
            <h2>Recent Downloads</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Download Date</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_downloads)): ?>
                        <tr>
                            <td colspan="4">No downloads yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_downloads as $download): ?>
                            <tr>
                                <td><?php echo esc_html($download->original_name); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($download->download_date)); ?></td>
                                <td><?php echo esc_html($download->ip_address); ?></td>
                                <td><?php echo esc_html(substr($download->user_agent, 0, 50)) . '...'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
