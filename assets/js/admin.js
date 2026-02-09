jQuery(document).ready(function($) {
    
    const fileInput = $('#ddlm-file-input');
    const browseButton = $('#ddlm-browse-button');
    const uploadArea = $('#ddlm-upload-area');
    const uploadProgress = $('#ddlm-upload-progress');
    const progressFill = $('#ddlm-progress-fill');
    const progressText = $('#ddlm-progress-text');
    const filesTable = $('#ddlm-files-tbody');
    const refreshButton = $('#ddlm-refresh-files');
    const modal = $('#ddlm-shortcode-modal');
    const modalClose = $('.ddlm-modal-close');
    
    browseButton.on('click', function() {
        fileInput.click();
    });
    
    uploadArea.on('click', function(e) {
        if (e.target === uploadArea[0] || $(e.target).closest('.ddlm-upload-icon, p').length) {
            fileInput.click();
        }
    });
    
    uploadArea.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('ddlm-dragover');
    });
    
    uploadArea.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('ddlm-dragover');
    });
    
    uploadArea.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('ddlm-dragover');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            handleFileUpload(files[0]);
        }
    });
    
    fileInput.on('change', function() {
        if (this.files.length > 0) {
            handleFileUpload(this.files[0]);
        }
    });
    
    function handleFileUpload(file) {
        const formData = new FormData();
        formData.append('action', 'ddlm_upload_file');
        formData.append('nonce', ddlmAdmin.nonce);
        formData.append('file', file);
        
        uploadArea.hide();
        uploadProgress.show();
        progressFill.css('width', '0%');
        progressText.text('Uploading...');
        
        $.ajax({
            url: ddlmAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        progressFill.css('width', percentComplete + '%');
                        progressText.text('Uploading... ' + Math.round(percentComplete) + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    progressFill.css('width', '100%');
                    progressText.text('Upload complete!');
                    
                    setTimeout(function() {
                        uploadProgress.hide();
                        uploadArea.show();
                        fileInput.val('');
                        loadFiles();
                    }, 1500);
                    
                    showNotice('File uploaded successfully!', 'success');
                } else {
                    showNotice('Upload failed: ' + response.data, 'error');
                    uploadProgress.hide();
                    uploadArea.show();
                }
            },
            error: function() {
                showNotice('Upload failed. Please try again.', 'error');
                uploadProgress.hide();
                uploadArea.show();
            }
        });
    }
    
    function loadFiles() {
        filesTable.html('<tr><td colspan="8" class="ddlm-loading">Loading files...</td></tr>');
        
        $.ajax({
            url: ddlmAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'ddlm_get_files',
                nonce: ddlmAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(function(file) {
                        const emailChecked = (parseInt(file.require_email) === 1) ? 'checked' : '';
                        html += '<tr>';
                        html += '<td>' + file.id + '</td>';
                        html += '<td><strong>' + escapeHtml(file.name) + '</strong></td>';
                        html += '<td>' + file.size + '</td>';
                        html += '<td>' + file.date + '</td>';
                        html += '<td>' + file.downloads + '</td>';
                        html += '<td><label class="ddlm-toggle"><input type="checkbox" class="ddlm-email-toggle" data-file-id="' + file.id + '" ' + emailChecked + '><span class="ddlm-toggle-slider"></span></label></td>';
                        html += '<td><code class="ddlm-shortcode-preview">' + escapeHtml(file.shortcode) + '</code></td>';
                        html += '<td>';
                        html += '<button class="button button-small ddlm-copy-shortcode" data-id="' + file.id + '" data-name="' + escapeHtml(file.name) + '">Shortcode</button> ';
                        html += '<button class="button button-small ddlm-delete-file" data-id="' + file.id + '" data-name="' + escapeHtml(file.name) + '">Delete</button>';
                        html += '</td>';
                        html += '</tr>';
                    });
                    filesTable.html(html);
                } else {
                    filesTable.html('<tr><td colspan="8">No files uploaded yet.</td></tr>');
                }
            },
            error: function() {
                filesTable.html('<tr><td colspan="8">Error loading files.</td></tr>');
            }
        });
    }
    
    $(document).on('change', '.ddlm-email-toggle', function() {
        const fileId = $(this).data('file-id');
        const requireEmail = $(this).is(':checked') ? 1 : 0;
        
        $.ajax({
            url: ddlmAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'ddlm_toggle_email_requirement',
                nonce: ddlmAdmin.nonce,
                file_id: fileId,
                require_email: requireEmail
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Email requirement updated', 'success');
                } else {
                    showNotice('Failed to update: ' + response.data, 'error');
                }
            }
        });
    });
    
    function loadEmails() {
        const emailsTable = $('#ddlm-emails-tbody');
        if (emailsTable.length === 0) return;
        
        emailsTable.html('<tr><td colspan="4" class="ddlm-loading">Loading emails...</td></tr>');
        
        $.ajax({
            url: ddlmAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'ddlm_get_emails',
                nonce: ddlmAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(function(email) {
                        html += '<tr>';
                        html += '<td><strong>' + escapeHtml(email.email) + '</strong></td>';
                        html += '<td>' + escapeHtml(email.file_name) + '</td>';
                        html += '<td>' + email.date + '</td>';
                        html += '<td>' + escapeHtml(email.ip) + '</td>';
                        html += '</tr>';
                    });
                    emailsTable.html(html);
                } else {
                    emailsTable.html('<tr><td colspan="4">No email submissions yet.</td></tr>');
                }
            },
            error: function() {
                emailsTable.html('<tr><td colspan="4">Error loading emails.</td></tr>');
            }
        });
    }
    
    $('#ddlm-refresh-emails').on('click', function() {
        loadEmails();
    });
    
    $(document).on('click', '.ddlm-copy-shortcode', function() {
        const fileId = $(this).data('id');
        const fileName = $(this).data('name');
        
        $('#ddlm-shortcode-button').val('[secure_download id="' + fileId + '" text="Download ' + fileName + '"]');
        $('#ddlm-shortcode-url').val('[secure_download_url id="' + fileId + '"]');
        
        modal.fadeIn();
    });
    
    modalClose.on('click', function() {
        modal.fadeOut();
    });
    
    $(window).on('click', function(e) {
        if (e.target === modal[0]) {
            modal.fadeOut();
        }
    });
    
    $(document).on('click', '.ddlm-copy-btn', function() {
        const targetId = $(this).data('target');
        const input = $('#' + targetId);
        
        input.select();
        document.execCommand('copy');
        
        const originalText = $(this).text();
        $(this).text('Copied!');
        
        setTimeout(() => {
            $(this).text(originalText);
        }, 2000);
        
        showNotice('Shortcode copied to clipboard!', 'success');
    });
    
    $(document).on('click', '.ddlm-delete-file', function() {
        const fileId = $(this).data('id');
        const fileName = $(this).data('name');
        
        if (!confirm('Are you sure you want to delete "' + fileName + '"? This action cannot be undone.')) {
            return;
        }
        
        $.ajax({
            url: ddlmAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'ddlm_delete_file',
                nonce: ddlmAdmin.nonce,
                file_id: fileId
            },
            success: function(response) {
                if (response.success) {
                    showNotice('File deleted successfully!', 'success');
                    loadFiles();
                } else {
                    showNotice('Delete failed: ' + response.data, 'error');
                }
            },
            error: function() {
                showNotice('Delete failed. Please try again.', 'error');
            }
        });
    });
    
    refreshButton.on('click', function() {
        loadFiles();
    });
    
    function showNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.ddlm-admin-wrap h1').after(notice);
        
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    loadFiles();
    loadEmails();
});
