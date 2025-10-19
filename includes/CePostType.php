<?php

namespace CoderEmbassy\ExpressCheckout;

/**
 * Custom Post Type for shortcodes
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
class CePostType {
    
    /**
     * Constructor
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function __construct() {
        
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('manage_ce_shortcode_posts_columns', array($this, 'add_columns'));
        add_action('manage_ce_shortcode_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
    }
    
    /**
     * Register custom post type
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function register_post_type() {
        $labels = array(
            'name' => esc_html__('Express Checkout', 'coderembassy-express-checkout'),
            'singular_name' => esc_html__('Express Checkout', 'coderembassy-express-checkout'),
            'menu_name' => esc_html__('Express Checkout', 'coderembassy-express-checkout'),
            'add_new' => esc_html__('Add New', 'coderembassy-express-checkout'),
            'add_new_item' => esc_html__('Add New', 'coderembassy-express-checkout'),
            'edit_item' => esc_html__('Edit Express Checkout', 'coderembassy-express-checkout'),
            'new_item' => esc_html__('New Express Checkout', 'coderembassy-express-checkout'),
            'view_item' => esc_html__('View Express Checkout', 'coderembassy-express-checkout'),
            'search_items' => esc_html__('Search Express Checkout', 'coderembassy-express-checkout'),
            'not_found' => esc_html__('No Express Checkout found', 'coderembassy-express-checkout'),
            'not_found_in_trash' => esc_html__('No Express Checkout found in trash', 'coderembassy-express-checkout'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 56,
            'menu_icon' => 'dashicons-cart',
            'supports' => array('title'),
            'show_in_rest' => false,
        );
        
        register_post_type('ce_shortcode', $args);
    }
    
    /**
     * Add meta boxes
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function add_meta_boxes() {
        // Shortcode metabox at the top with highest priority
        add_meta_box(
            'coderembassy_shortcode_shortcode',
            esc_html__('Shortcode', 'coderembassy-express-checkout'),
            array($this, 'shortcode_meta_box'),
            'ce_shortcode',
            'normal',
            'default'
        );
        
        // Single metabox for Settings and Design with tabs
        add_meta_box(
            'coderembassy_checkout_modification',
            esc_html__('Checkout Modification', 'coderembassy-express-checkout'),
            array($this, 'checkout_modification_meta_box'),
            'ce_shortcode',
            'normal',
            'high'
        );
    }
    
    /**
     * Checkout Modification meta box with tabs
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function checkout_modification_meta_box($post) {
        wp_nonce_field('ce_shortcode_meta_box', 'ce_shortcode_meta_box_nonce');
        
        $selected_products = get_post_meta($post->ID, '_coderembassy_selected_products', true);
        $ajax_add_to_cart = get_post_meta($post->ID, '_coderembassy_ajax_add_to_cart', true);
        $quick_cart = get_post_meta($post->ID, '_coderembassy_quick_cart', true);
        $product_select_type = get_post_meta($post->ID, '_coderembassy_product_select_type', true);
        $product_width = get_post_meta($post->ID, '_coderembassy_product_width', true);
        $product_height = get_post_meta($post->ID, '_coderembassy_product_height', true);
        $title_font_size = get_post_meta($post->ID, '_coderembassy_title_font_size', true);
        $title_color = get_post_meta($post->ID, '_coderembassy_title_color', true);
        $grid_columns = get_post_meta($post->ID, '_coderembassy_grid_columns', true);
        $grid_gap = get_post_meta($post->ID, '_coderembassy_grid_gap', true);
        $container_border_color = get_post_meta($post->ID, '_coderembassy_container_border_color', true);
        $container_background_color = get_post_meta($post->ID, '_coderembassy_container_background_color', true);
        $container_padding = get_post_meta($post->ID, '_coderembassy_container_padding', true);
        $show_checkout_section = get_post_meta($post->ID, '_coderembassy_show_checkout_section', true);
        
        if (!is_array($selected_products)) {
            $selected_products = array();
        }
        
        // Default values
        $product_width = $product_width ?: '200px';
        $product_height = $product_height ?: '300px';
        $title_font_size = $title_font_size ?: '16px';
        $title_color = $title_color ?: '#333333';
        $grid_columns = $grid_columns ?: 'auto-fill';
        $grid_gap = $grid_gap ?: '20px';
        $container_border_color = $container_border_color ?: '#e1e5e9';
        $container_background_color = $container_background_color ?: '#f8f9fa';
        $container_padding = $container_padding ?: '20px';
        $show_checkout_section = $show_checkout_section ?: 'use_global';
        
        ?>
        <div class="coderembassy-checkout-modification">
            <div class="coderembassy-tab-nav">
                <a href="#coderembassy-settings-tab" class="coderembassy-tab-link active" data-tab="settings">
                    <?php esc_html_e('Settings', 'coderembassy-express-checkout'); ?>
                </a>
                <a href="#coderembassy-design-tab" class="coderembassy-tab-link" data-tab="design">
                    <?php esc_html_e('Design', 'coderembassy-express-checkout'); ?>
                </a>
            </div>
            
            <div class="coderembassy-tab-content-wrapper">
                <div id="coderembassy-settings-tab" class="coderembassy-tab-content active">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="coderembassy_selected_products"><?php esc_html_e('Select Products', 'coderembassy-express-checkout'); ?></label>
                            </th>
                            <td>
                                <select id="coderembassy_selected_products" name="coderembassy_selected_products[]" multiple="multiple" style="width: 100%;">
                                    <?php
                                    if (!empty($selected_products)) {
                                        foreach ($selected_products as $product_id) {
                                            $product = wc_get_product($product_id);
                                            if ($product) {
                                                echo '<option value="' . esc_attr($product_id) . '" selected>' . esc_html($product->get_name()) . '</option>';
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php esc_html_e('Search and select products to display in the shortcode.', 'coderembassy-express-checkout'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="coderembassy_ajax_add_to_cart"><?php esc_html_e('AJAX Add to Cart', 'coderembassy-express-checkout'); ?></label>
                            </th>
                            <td>
                                <select id="coderembassy_ajax_add_to_cart" name="coderembassy_ajax_add_to_cart">
                                    <option value="global" <?php selected($ajax_add_to_cart, 'global'); ?>><?php esc_html_e('Use Global Setting', 'coderembassy-express-checkout'); ?></option>
                                    <option value="1" <?php selected($ajax_add_to_cart, '1'); ?>><?php esc_html_e('Enable', 'coderembassy-express-checkout'); ?></option>
                                    <option value="0" <?php selected($ajax_add_to_cart, '0'); ?>><?php esc_html_e('Disable', 'coderembassy-express-checkout'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Override global setting for this shortcode', 'coderembassy-express-checkout'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="coderembassy_quick_cart"><?php esc_html_e('Quick Cart', 'coderembassy-express-checkout'); ?></label>
                            </th>
                            <td>
                                <select id="coderembassy_quick_cart" name="coderembassy_quick_cart">
                                    <option value="global" <?php selected($quick_cart, 'global'); ?>><?php esc_html_e('Use Global Setting', 'coderembassy-express-checkout'); ?></option>
                                    <option value="1" <?php selected($quick_cart, '1'); ?>><?php esc_html_e('Enable', 'coderembassy-express-checkout'); ?></option>
                                    <option value="0" <?php selected($quick_cart, '0'); ?>><?php esc_html_e('Disable', 'coderembassy-express-checkout'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Override global setting for this shortcode', 'coderembassy-express-checkout'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="coderembassy_product_select_type"><?php esc_html_e('Product Select Type', 'coderembassy-express-checkout'); ?></label>
                            </th>
                            <td>
                                <select id="coderembassy_product_select_type" name="coderembassy_product_select_type">
                                    <option value="global" <?php selected($product_select_type, 'global'); ?>><?php esc_html_e('Use Global Setting', 'coderembassy-express-checkout'); ?></option>
                                    <option value="checkbox" <?php selected($product_select_type, 'checkbox'); ?>><?php esc_html_e('Checkbox (Multiple Selection)', 'coderembassy-express-checkout'); ?></option>
                                    <option value="radio" <?php selected($product_select_type, 'radio'); ?>><?php esc_html_e('Radio Button (Single Selection)', 'coderembassy-express-checkout'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Override global setting for this shortcode', 'coderembassy-express-checkout'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="coderembassy-design-tab" class="coderembassy-tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="coderembassy_product_width"><?php esc_html_e('Product Area Width', 'coderembassy-express-checkout'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="coderembassy_product_width" name="coderembassy_product_width" value="<?php echo esc_attr($product_width); ?>" class="regular-text" placeholder="<?php esc_attr_e('Leave empty to use global setting', 'coderembassy-express-checkout'); ?>" />
                                <p class="description"><?php esc_html_e('e.g., 300px, 25%, auto. Leave empty to use global setting.', 'coderembassy-express-checkout'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="coderembassy_product_height"><?php esc_html_e('Product Area Height', 'coderembassy-express-checkout'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="coderembassy_product_height" name="coderembassy_product_height" value="<?php echo esc_attr($product_height); ?>" class="regular-text" placeholder="<?php esc_attr_e('Leave empty to use global setting', 'coderembassy-express-checkout'); ?>" />
                                <p class="description"><?php esc_html_e('e.g., 400px, auto. Leave empty to use global setting.', 'coderembassy-express-checkout'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="coderembassy_title_font_size"><?php esc_html_e('Product Title Font Size', 'coderembassy-express-checkout'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="coderembassy_title_font_size" name="coderembassy_title_font_size" value="<?php echo esc_attr($title_font_size); ?>" class="regular-text" placeholder="<?php esc_attr_e('Leave empty to use global setting', 'coderembassy-express-checkout'); ?>" />
                                <p class="description"><?php esc_html_e('e.g., 16px, 1.2em. Leave empty to use global setting.', 'coderembassy-express-checkout'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="coderembassy_title_color"><?php esc_html_e('Product Title Color', 'coderembassy-express-checkout'); ?></label>
                            </th>
                            <td>
                                <input type="color" id="coderembassy_title_color" name="coderembassy_title_color" value="<?php echo esc_attr($title_color); ?>" />
                                <p class="description"><?php esc_html_e('Leave empty to use global setting.', 'coderembassy-express-checkout'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="coderembassy_grid_columns"><?php esc_html_e('Grid Columns', 'coderembassy-express-checkout'); ?></label>
                            </th>
                            <td>
                                <select id="coderembassy_grid_columns" name="coderembassy_grid_columns">
                                    <option value="auto-fill" <?php selected($grid_columns, 'auto-fill'); ?>><?php esc_html_e('Auto Fill (Responsive)', 'coderembassy-express-checkout'); ?></option>
                                    <option value="1" <?php selected($grid_columns, '1'); ?>><?php esc_html_e('1 Column', 'coderembassy-express-checkout'); ?></option>
                                    <option value="2" <?php selected($grid_columns, '2'); ?>><?php esc_html_e('2 Columns', 'coderembassy-express-checkout'); ?></option>
                                    <option value="3" <?php selected($grid_columns, '3'); ?>><?php esc_html_e('3 Columns', 'coderembassy-express-checkout'); ?></option>
                                    <option value="4" <?php selected($grid_columns, '4'); ?>><?php esc_html_e('4 Columns', 'coderembassy-express-checkout'); ?></option>
                                    <option value="5" <?php selected($grid_columns, '5'); ?>><?php esc_html_e('5 Columns', 'coderembassy-express-checkout'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Choose how many columns to display products in. Auto Fill will automatically adjust based on available space.', 'coderembassy-express-checkout'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="coderembassy_grid_gap"><?php esc_html_e('Grid Gap', 'coderembassy-express-checkout'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="coderembassy_grid_gap" name="coderembassy_grid_gap" value="<?php echo esc_attr($grid_gap); ?>" class="regular-text" placeholder="20px" />
                                <p class="description"><?php esc_html_e('Space between products (e.g., 20px, 1rem, 2em). Leave empty to use global default.', 'coderembassy-express-checkout'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="coderembassy_container_border_color"><?php esc_html_e('Container Border Color', 'coderembassy-express-checkout'); ?></label>
                            </th>
                            <td>
                                <input type="color" id="coderembassy_container_border_color" name="coderembassy_container_border_color" value="<?php echo esc_attr($container_border_color); ?>" />
                                <p class="description"><?php esc_html_e('Border color for the main container. Leave empty to use global default.', 'coderembassy-express-checkout'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="coderembassy_container_background_color"><?php esc_html_e('Container Background Color', 'coderembassy-express-checkout'); ?></label>
                            </th>
                            <td>
                                <input type="color" id="coderembassy_container_background_color" name="coderembassy_container_background_color" value="<?php echo esc_attr($container_background_color); ?>" />
                                <p class="description"><?php esc_html_e('Background color for the main container. Leave empty to use global default.', 'coderembassy-express-checkout'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="coderembassy_container_padding"><?php esc_html_e('Container Padding', 'coderembassy-express-checkout'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="coderembassy_container_padding" name="coderembassy_container_padding" value="<?php echo esc_attr($container_padding); ?>" class="regular-text" placeholder="20px" />
                                <p class="description"><?php esc_html_e('Padding for the main container (e.g., 20px, 1rem, 2em). Leave empty to use global default.', 'coderembassy-express-checkout'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="coderembassy_show_checkout_section"><?php esc_html_e('Show Checkout Section', 'coderembassy-express-checkout'); ?></label>
                            </th>
                            <td>
                                <select id="coderembassy_show_checkout_section" name="coderembassy_show_checkout_section">
                                    <option value="use_global" <?php selected($show_checkout_section, 'use_global'); ?>><?php esc_html_e('Use Global Setting', 'coderembassy-express-checkout'); ?></option>
                                    <option value="yes" <?php selected($show_checkout_section, 'yes'); ?>><?php esc_html_e('Yes', 'coderembassy-express-checkout'); ?></option>
                                    <option value="no" <?php selected($show_checkout_section, 'no'); ?>><?php esc_html_e('No', 'coderembassy-express-checkout'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Show the WooCommerce checkout form after the express checkout to allow users to complete their purchase on the same page.', 'coderembassy-express-checkout'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings meta box (deprecated - kept for backward compatibility)
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function settings_meta_box($post) {
        wp_nonce_field('ce_shortcode_meta_box', 'ce_shortcode_meta_box_nonce');
        
        $selected_products = get_post_meta($post->ID, '_coderembassy_selected_products', true);
        $ajax_add_to_cart = get_post_meta($post->ID, '_coderembassy_ajax_add_to_cart', true);
        $quick_cart = get_post_meta($post->ID, '_coderembassy_quick_cart', true);
        
        if (!is_array($selected_products)) {
            $selected_products = array();
        }
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="coderembassy_selected_products"><?php esc_html_e('Select Products', 'coderembassy-express-checkout'); ?></label>
                </th>
                <td>
                    <select id="coderembassy_selected_products" name="coderembassy_selected_products[]" multiple="multiple" style="width: 100%;">
                        <?php
                        if (!empty($selected_products)) {
                            foreach ($selected_products as $product_id) {
                                $product = wc_get_product($product_id);
                                if ($product) {
                                    echo '<option value="' . esc_attr($product_id) . '" selected>' . esc_html($product->get_name()) . '</option>';
                                }
                            }
                        }
                        ?>
                    </select>
                    <p class="description"><?php esc_html_e('Search and select products to display in the shortcode.', 'coderembassy-express-checkout'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="coderembassy_ajax_add_to_cart"><?php esc_html_e('AJAX Add to Cart', 'coderembassy-express-checkout'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="coderembassy_ajax_add_to_cart" name="coderembassy_ajax_add_to_cart" value="1" <?php checked($ajax_add_to_cart, '1'); ?> />
                    <label for="coderembassy_ajax_add_to_cart"><?php esc_html_e('Enable AJAX add to cart (no page reload)', 'coderembassy-express-checkout'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="coderembassy_quick_cart"><?php esc_html_e('Quick Cart', 'coderembassy-express-checkout'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="coderembassy_quick_cart" name="coderembassy_quick_cart" value="1" <?php checked($quick_cart, '1'); ?> />
                    <label for="coderembassy_quick_cart"><?php esc_html_e('Add to cart when checkbox is clicked', 'coderembassy-express-checkout'); ?></label>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Design meta box
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function design_meta_box($post) {
        $product_width = get_post_meta($post->ID, '_coderembassy_product_width', true);
        $product_height = get_post_meta($post->ID, '_coderembassy_product_height', true);
        $title_font_size = get_post_meta($post->ID, '_coderembassy_title_font_size', true);
        $title_color = get_post_meta($post->ID, '_coderembassy_title_color', true);
        
        // Default values
        $product_width = $product_width ?: '200px';
        $product_height = $product_height ?: '300px';
        $title_font_size = $title_font_size ?: '16px';
        $title_color = $title_color ?: '#333333';
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="coderembassy_product_width"><?php esc_html_e('Product Area Width', 'coderembassy-express-checkout'); ?></label>
                </th>
                <td>
                    <input type="text" id="coderembassy_product_width" name="coderembassy_product_width" value="<?php echo esc_attr($product_width); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e('e.g., 300px, 25%, auto', 'coderembassy-express-checkout'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="coderembassy_product_height"><?php esc_html_e('Product Area Height', 'coderembassy-express-checkout'); ?></label>
                </th>
                <td>
                    <input type="text" id="coderembassy_product_height" name="coderembassy_product_height" value="<?php echo esc_attr($product_height); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e('e.g., 400px, auto', 'coderembassy-express-checkout'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="coderembassy_title_font_size"><?php esc_html_e('Product Title Font Size', 'coderembassy-express-checkout'); ?></label>
                </th>
                <td>
                    <input type="text" id="coderembassy_title_font_size" name="coderembassy_title_font_size" value="<?php echo esc_attr($title_font_size); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e('e.g., 16px, 1.2em', 'coderembassy-express-checkout'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="coderembassy_title_color"><?php esc_html_e('Product Title Color', 'coderembassy-express-checkout'); ?></label>
                </th>
                <td>
                    <input type="color" id="coderembassy_title_color" name="coderembassy_title_color" value="<?php echo esc_attr($title_color); ?>" />
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Shortcode meta box
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function shortcode_meta_box($post) {
        if ($post->post_status === 'publish') {
            $post_name = sanitize_title($post->post_title);
            $shortcode = '[ce_checkout id="' . $post->ID . '" name="' . $post_name . '"]';
            ?>
            <div class="coderembassy-shortcode-container">
                <p><strong><?php esc_html_e('Use this shortcode to display the express checkout:', 'coderembassy-express-checkout'); ?></strong></p>
                <div class="coderembassy-shortcode-input-group">
                    <input type="text" id="coderembassy-shortcode-input" value="<?php echo esc_attr($shortcode); ?>" readonly class="coderembassy-shortcode-input" onclick="this.select();" />
                    <button type="button" id="coderembassy-copy-btn" class="button button-secondary coderembassy-copy-btn" data-copied-text="<?php esc_html_e('Copied!', 'coderembassy-express-checkout'); ?>">
                        <?php esc_html_e('Copy', 'coderembassy-express-checkout'); ?>
                    </button>
                </div>
                <p class="description"><?php esc_html_e('Click the input field to select all, or use the Copy button.', 'coderembassy-express-checkout'); ?></p>
            </div>
            <?php
        } else {
            ?>
            <div class="coderembassy-shortcode-container">
                <p><strong><?php esc_html_e('Publish this shortcode to get the shortcode code.', 'coderembassy-express-checkout'); ?></strong></p>
                <p class="description"><?php esc_html_e('Once published, you will see the shortcode here with a copy button.', 'coderembassy-express-checkout'); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * Save meta boxes
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['ce_shortcode_meta_box_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ce_shortcode_meta_box_nonce'])), 'ce_shortcode_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save selected products
        if (isset($_POST['coderembassy_selected_products'])) {
            $selected_products = array_map('intval', $_POST['coderembassy_selected_products']);
            update_post_meta($post_id, '_coderembassy_selected_products', $selected_products);
        } else {
            update_post_meta($post_id, '_coderembassy_selected_products', array());
        }
        
        // Save AJAX add to cart (with override support)
        $ajax_add_to_cart = isset($_POST['coderembassy_ajax_add_to_cart']) ? sanitize_text_field(wp_unslash($_POST['coderembassy_ajax_add_to_cart'])) : 'global';
        update_post_meta($post_id, '_coderembassy_ajax_add_to_cart', $ajax_add_to_cart);
        
        // Save quick cart (with override support)
        $quick_cart = isset($_POST['coderembassy_quick_cart']) ? sanitize_text_field(wp_unslash($_POST['coderembassy_quick_cart'])) : 'global';
        update_post_meta($post_id, '_coderembassy_quick_cart', $quick_cart);
        
        // Save product select type (with override support)
        $product_select_type = isset($_POST['coderembassy_product_select_type']) ? sanitize_text_field(wp_unslash($_POST['coderembassy_product_select_type'])) : 'global';
        update_post_meta($post_id, '_coderembassy_product_select_type', $product_select_type);
        
        // Save design options
        if (isset($_POST['coderembassy_product_width'])) {
            update_post_meta($post_id, '_coderembassy_product_width', sanitize_text_field(wp_unslash($_POST['coderembassy_product_width'])));
        }
        
        if (isset($_POST['coderembassy_product_height'])) {
            update_post_meta($post_id, '_coderembassy_product_height', sanitize_text_field(wp_unslash($_POST['coderembassy_product_height'])));
        }
        
        if (isset($_POST['coderembassy_title_font_size'])) {
            update_post_meta($post_id, '_coderembassy_title_font_size', sanitize_text_field(wp_unslash($_POST['coderembassy_title_font_size'])));
        }
        
        if (isset($_POST['coderembassy_title_color'])) {
            update_post_meta($post_id, '_coderembassy_title_color', sanitize_hex_color(wp_unslash($_POST['coderembassy_title_color'])));
        }
        
        if (isset($_POST['coderembassy_grid_columns'])) {
            update_post_meta($post_id, '_coderembassy_grid_columns', sanitize_text_field(wp_unslash($_POST['coderembassy_grid_columns'])));
        }
        
        if (isset($_POST['coderembassy_grid_gap'])) {
            update_post_meta($post_id, '_coderembassy_grid_gap', sanitize_text_field(wp_unslash($_POST['coderembassy_grid_gap'])));
        }
        
        if (isset($_POST['coderembassy_container_border_color'])) {
            update_post_meta($post_id, '_coderembassy_container_border_color', sanitize_hex_color(wp_unslash($_POST['coderembassy_container_border_color'])));
        }
        
        if (isset($_POST['coderembassy_container_background_color'])) {
            update_post_meta($post_id, '_coderembassy_container_background_color', sanitize_hex_color(wp_unslash($_POST['coderembassy_container_background_color'])));
        }
        
        if (isset($_POST['coderembassy_container_padding'])) {
            update_post_meta($post_id, '_coderembassy_container_padding', sanitize_text_field(wp_unslash($_POST['coderembassy_container_padding'])));
        }
        
        if (isset($_POST['coderembassy_show_checkout_section'])) {
            update_post_meta($post_id, '_coderembassy_show_checkout_section', sanitize_text_field(wp_unslash($_POST['coderembassy_show_checkout_section'])));
        }
    }
    
    /**
     * Add custom columns
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function add_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['shortcode'] = esc_html__('Shortcode', 'coderembassy-express-checkout');
        $new_columns['products_count'] = esc_html__('Products Count', 'coderembassy-express-checkout');
        
        return $new_columns;
    }
    
    /**
     * Custom column content
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'shortcode':
                if (get_post_status($post_id) === 'publish') {
                    $post = get_post($post_id);
                    $post_name = sanitize_title($post->post_title);
                    $shortcode = '[ce_checkout id="' . $post_id . '" name="' . $post_name . '"]';
                    ?>
                    <div class="coderembassy-shortcode-display">
                        <div class="coderembassy-shortcode-content">
                            <code class="coderembassy-shortcode-text"><?php echo esc_html($shortcode); ?></code>
                        </div>
                        <button type="button" class="coderembassy-copy-shortcode-btn" data-shortcode="<?php echo esc_attr($shortcode); ?>" title="<?php esc_attr_e('Copy shortcode', 'coderembassy-express-checkout'); ?>">
                            <span class="dashicons dashicons-clipboard"></span>
                        </button>
                    </div>
                    <?php
                } else {
                    echo '<em>' . esc_html__('Not published', 'coderembassy-express-checkout') . '</em>';
                }
                break;
                
            case 'products_count':
                $selected_products = get_post_meta($post_id, '_coderembassy_selected_products', true);
                if (is_array($selected_products)) {
                    echo count($selected_products);
                } else {
                    echo '0';
                }
                break;
        }
    }
}
