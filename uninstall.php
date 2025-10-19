<?php
/**
 * Uninstall script for CoderEmbassy Express Checkout
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up options
delete_option('coderembassy_options');
delete_option('coderembassy_design_options');
delete_option('coderembassy_express_checkout_version');

// Clean up custom post type posts
$posts = get_posts(array(
    'post_type' => 'ce_shortcode',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($posts as $post) {
    wp_delete_post($post->ID, true);
}

// Flush rewrite rules
flush_rewrite_rules();
