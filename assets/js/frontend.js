jQuery(document).ready(function($) {
    
    // Create email modal HTML
    const modalHTML = `
        <div id="ddlm-email-modal" class="ddlm-modal" style="display: none;">
            <div class="ddlm-modal-content ddlm-email-modal-content">
                <span class="ddlm-modal-close">&times;</span>
                <div class="ddlm-email-modal-header">
                    <div class="ddlm-email-icon">📧</div>
                    <h2>Get Your Download Link</h2>
                    <p class="ddlm-file-name"></p>
                </div>
                <div class="ddlm-email-modal-body">
                    <p>Enter your email address to receive the download link:</p>
                    <form id="ddlm-email-form">
                        <input type="email" id="ddlm-email-input" placeholder="your@email.com" required>
                        <button type="submit" class="ddlm-submit-email">Send Download Link</button>
                    </form>
                    <div class="ddlm-email-message" style="display: none;"></div>
                </div>
                <div class="ddlm-email-modal-footer">
                    <p>🔒 Your email is safe with us. We'll only use it to send your download link.</p>
                </div>
            </div>
        </div>
    `;
    
    $('body').append(modalHTML);
    
    const modal = $('#ddlm-email-modal');
    const modalClose = $('.ddlm-modal-close');
    const emailForm = $('#ddlm-email-form');
    const emailInput = $('#ddlm-email-input');
    const emailMessage = $('.ddlm-email-message');
    const submitButton = $('.ddlm-submit-email');
    
    // Handle email-required download buttons
    $(document).on('click', '.ddlm-email-required', function(e) {
        e.preventDefault();
        
        const fileId = $(this).data('file-id');
        const fileName = $(this).data('file-name');
        
        $('.ddlm-file-name').text(fileName);
        emailForm.data('file-id', fileId);
        emailInput.val('');
        emailMessage.hide();
        submitButton.prop('disabled', false).text('Send Download Link');
        
        modal.fadeIn();
    });
    
    // Close modal
    modalClose.on('click', function() {
        modal.fadeOut();
    });
    
    $(window).on('click', function(e) {
        if (e.target === modal[0]) {
            modal.fadeOut();
        }
    });
    
    // Handle form submission
    emailForm.on('submit', function(e) {
        e.preventDefault();
        
        const email = emailInput.val().trim();
        const fileId = emailForm.data('file-id');
        
        if (!email || !isValidEmail(email)) {
            showMessage('Please enter a valid email address', 'error');
            return;
        }
        
        submitButton.prop('disabled', true).text('Sending...');
        
        $.ajax({
            url: ddlmFrontend.ajaxurl,
            type: 'POST',
            data: {
                action: 'ddlm_submit_email',
                nonce: ddlmFrontend.nonce,
                email: email,
                file_id: fileId
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.download_url) {
                        // Email sending failed, show download link directly
                        showMessage('✓ ' + response.data.message + '<br><br><a href="' + response.data.download_url + '" class="ddlm-direct-download-link" style="display: inline-block; margin-top: 10px; padding: 10px 20px; background: #2271b1; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">Download Now</a><br><br><small style="color: #666;">' + response.data.note + '</small>', 'success');
                        emailInput.val('');
                        submitButton.text('Download Ready!');
                        submitButton.prop('disabled', false);
                    } else {
                        // Email sent successfully
                        showMessage('✓ ' + response.data.message + ' Check your inbox.', 'success');
                        emailInput.val('');
                        submitButton.text('Email Sent!');
                        
                        setTimeout(function() {
                            modal.fadeOut();
                            submitButton.prop('disabled', false).text('Send Download Link');
                        }, 3000);
                    }
                } else {
                    showMessage('✗ ' + response.data, 'error');
                    submitButton.prop('disabled', false).text('Send Download Link');
                }
            },
            error: function() {
                showMessage('✗ An error occurred. Please try again.', 'error');
                submitButton.prop('disabled', false).text('Send Download Link');
            }
        });
    });
    
    function showMessage(message, type) {
        emailMessage
            .removeClass('ddlm-message-success ddlm-message-error')
            .addClass('ddlm-message-' + type)
            .html(message)
            .fadeIn();
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});
