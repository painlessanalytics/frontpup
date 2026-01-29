/**
 * FrontPup Clear Cache JS
 * 
 * Creates admin notices for FrontPup actions
 * 
 * ID: frontpup-clear-cache-js
 * 
 * Button click ID: frontpup-adminbar-clear-cache
 */

jQuery(document).ready(function($) {
  // Select the anchor tag within the specific list item
  $('li#wp-admin-bar-frontpup-clear-cache .ab-item').on('click', function(e) {
    e.preventDefault(); // Prevent default link behavior
    
    var data = {
        'action': 'frontpup_clear_cache_action', // The PHP action hook name
        'nonce': frontpupClearCache.security_nonce // Security nonce
    };

    $.post(frontpupClearCache.ajax_url, data, function(response) {
        //alert('Server response: ' + response);
        //console.log( response );
        //alert(response.data);
        // You can update the UI here based on the response
    })
    .fail(function(response) {
        //alert('Error: ' + response.responseText);
    });
  });
});