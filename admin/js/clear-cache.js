/**
 * FrontPup Clear Cache JS
 * 
 * Creates admin notices for FrontPup actions 
 * Button click ID: frontpup-adminbar-clear-cache
 */

jQuery(document).ready(function($) {
    // Select the anchor tag within the specific list item
    $('li#wp-admin-bar-frontpup-clear-cache .ab-item').on('click', function(e) {
        e.preventDefault(); // Prevent default link behavior
        // Hide the menu on click
        $(this).closest('li.menupop').removeClass('hover');

        // Clear any existing frontpup notices
        const existingNotices = document.querySelectorAll('.frontpup-notice');
        existingNotices.forEach(function(notice) {
            notice.remove();
        });
        
        var data = {
            'action': 'frontpup_clear_cache_action', // The PHP action hook name
            'nonce': frontpupClearCache.security_nonce // Security nonce
        };

        $.post(frontpupClearCache.ajax_url, data, function(response) {
            // You can update the UI here based on the response
            if(response.success) {
                frontpupShowAdminNotice(response.data, 'success');
            } else {
                frontpupShowAdminNotice(response.data, 'error');
            }
        })
        .fail(function(response) {
            frontpupShowAdminNotice('Error: ' + response.responseText, 'error');
        });
    });
});

function frontpupShowAdminNotice(message, type = 'success') {

    // Create a unique ID for the notice
    const noticeId = 'frontpup-notice-' + Date.now();

    // Create the notice element
    const notice = document.createElement('div');
    notice.id = noticeId;
    notice.style.marginTop = '36px'; // Just enough to be below the "Screen Options" and "Help" tabs at the top right
    notice.className = `frontpup-notice notice notice-${type} is-dismissible`;
    notice.innerHTML = `<p><strong>${message}</strong></p>`;
    notice.innerHTML += `<button type="button" class="notice-dismiss"><span class="screen-reader-text">${frontpupClearCache.dismiss}</span></button>`;

    // Append the notice to the admin notices container
    const adminNoticesContainer = document.getElementById('wpbody-content').querySelector('.wrap');
    if (adminNoticesContainer) {
        adminNoticesContainer.prepend(notice);
    }

    // Add event listener to remove the notice when dismissed
    notice.querySelector('.notice-dismiss').addEventListener('click', function() {
        notice.remove();
    });
}

// eof