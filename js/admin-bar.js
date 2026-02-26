/**
 * FrontPup Clear Cache JS
 * 
 * Creates admin notices for FrontPup actions 
 * Button click ID: frontpup-adminbar-clear-cache
 */

document.addEventListener("DOMContentLoaded", function(event) {

    document.querySelector('li#wp-admin-bar-frontpup-clear-cache .ab-item').addEventListener('click', function(event) {
        // Handle the click event
        event.preventDefault(); // Prevent default link behavior

        const formData = new FormData();
        formData.append('action', 'frontpup_clear_cache_action');
        formData.append('nonce', frontpupClearCache.security_nonce);
        
        // Configuration options for the fetch request
        const options = {
            method: 'POST', // Specify the HTTP method
            body: formData // Use FormData as the request body
        };

        const statusElementTop = document.getElementById('wp-admin-bar-frontpup-adminbar-menu');
        if (statusElementTop) {
          statusElementTop.classList.remove('frontpup-success');
          statusElementTop.classList.remove('frontpup-error');
        }
        const statusElement = document.getElementById('wp-admin-bar-frontpup-clear-cache-status');
        if (statusElement) {
            const innerElement = statusElement.querySelector('.ab-item');
            if (innerElement) {
                innerElement.textContent = frontpupClearCache.processing;
            }
        }

        statusElementTop.classList.add('frontpup-loading');
        fetch(frontpupClearCache.ajax_url, options)
            .then(response => {
                // Handle potential HTTP errors
                if (!response.ok) {
                    throw new Error('Error: ' + response.status);
                }
                // Parse the response body as JSON
                return response.json();
            })
            .then(response => {
                // Handle the successful response data
                if( response && response.data && response.success !== undefined ) {
                    frontpupShowNotice(response.data, response.success ? 'success' : 'error');
                } else {
                    frontpupShowNotice('Unexpected response format', 'error');
                }
            })
            .catch(error => {
                frontpupShowNotice('Error: ' + error.message, 'error');
            });
    });
});

function frontpupShowNotice(message, type = 'success') {
    const statusElementTop = document.getElementById('wp-admin-bar-frontpup-adminbar-menu');
    if (statusElementTop) {
        statusElementTop.classList.remove('frontpup-loading');
          // Add class based on the type
        if (type === 'success') {
            statusElementTop.classList.remove('frontpup-error');
            statusElementTop.classList.add('frontpup-success');
        } else if (type === 'error') {
            statusElementTop.classList.remove('frontpup-success');
            statusElementTop.classList.add('frontpup-error');
        }
    }
    const statusElement = document.getElementById('wp-admin-bar-frontpup-clear-cache-status');
    if (statusElement) {
        const innerElement = statusElement.querySelector('.ab-item');
        if (innerElement) {
            innerElement.textContent = message;
        }
    }
}

// eof