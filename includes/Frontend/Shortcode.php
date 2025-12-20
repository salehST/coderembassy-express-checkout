<?php

namespace CoderEmbassy\ExpressCheckout\Frontend;

/**
 * Frontend Shortcode class
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
class Shortcode {
    
    /**
     * Constructor
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function __construct() {
        add_shortcode('coderembassy_checkout', array($this, 'render_shortcode'));
        // Keep old shortcodes for backward compatibility
        add_shortcode('ceec_checkout', array($this, 'render_shortcode'));
        add_shortcode('ce_checkout', array($this, 'render_shortcode'));
        add_shortcode('coderembassy_express_checkout', array($this, 'render_shortcode'));
    }
    
    /**
     * Render shortcode
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'name' => '',
        ), $atts, 'coderembassy_checkout');
        
        $post_id = intval($atts['id']);
        
        // If name is provided, try to find post by name as fallback
        if (!$post_id && !empty($atts['name'])) {
            $post = get_page_by_path($atts['name'], OBJECT, 'coderembassy_ec');
            if ($post) {
                $post_id = $post->ID;
            }
        }
        
        if (!$post_id || get_post_type($post_id) !== 'coderembassy_ec') {
            return '<p>' . esc_html__('Invalid shortcode ID.', 'coderembassy-express-checkout') . '</p>';
        }
        
        if (get_post_status($post_id) !== 'publish') {
            return '<p>' . esc_html__('This shortcode is not published.', 'coderembassy-express-checkout') . '</p>';
        }
        
        $selected_products = get_post_meta($post_id, '_coderembassy_selected_products', true);
        $ajax_add_to_cart = get_post_meta($post_id, '_coderembassy_ajax_add_to_cart', true);
        $quick_cart = get_post_meta($post_id, '_coderembassy_quick_cart', true);
        $product_select_type = get_post_meta($post_id, '_coderembassy_product_select_type', true);
        $product_width = get_post_meta($post_id, '_coderembassy_product_width', true);
        $product_height = get_post_meta($post_id, '_coderembassy_product_height', true);
        $title_font_size = get_post_meta($post_id, '_coderembassy_title_font_size', true);
        $title_color = get_post_meta($post_id, '_coderembassy_title_color', true);
        $grid_columns = get_post_meta($post_id, '_coderembassy_grid_columns', true);
        $grid_gap = get_post_meta($post_id, '_coderembassy_grid_gap', true);
        $container_border_color = get_post_meta($post_id, '_coderembassy_container_border_color', true);
        $container_background_color = get_post_meta($post_id, '_coderembassy_container_background_color', true);
        $container_padding = get_post_meta($post_id, '_coderembassy_container_padding', true);
        
        // Get global settings
        $global_options = get_option('coderembassy_global_options', array());
        $global_design_options = get_option('coderembassy_global_design_options', array());
        
        // Apply override logic for settings
        if ($ajax_add_to_cart === 'global' || empty($ajax_add_to_cart)) {
            $ajax_add_to_cart = isset($global_options['default_ajax_cart']) ? $global_options['default_ajax_cart'] : '0';
        }
        
        if ($quick_cart === 'global' || empty($quick_cart)) {
            $quick_cart = isset($global_options['default_quick_cart']) ? $global_options['default_quick_cart'] : '0';
        }
        
        if ($product_select_type === 'global' || empty($product_select_type)) {
            $product_select_type = isset($global_options['default_product_select_type']) ? $global_options['default_product_select_type'] : 'checkbox';
        }
        
        // Apply override logic for design options
        if (empty($product_width)) {
            $product_width = isset($global_design_options['default_width']) ? $global_design_options['default_width'] : '300px';
        }
        
        if (empty($product_height)) {
            $product_height = isset($global_design_options['default_height']) ? $global_design_options['default_height'] : '400px';
        }
        
        if (empty($title_font_size)) {
            $title_font_size = isset($global_design_options['default_font_size']) ? $global_design_options['default_font_size'] : '16px';
        }
        
        if (empty($title_color)) {
            $title_color = isset($global_design_options['default_color']) ? $global_design_options['default_color'] : '#333333';
        }
        
        if (empty($grid_columns)) {
            $grid_columns = isset($global_design_options['default_grid_columns']) ? $global_design_options['default_grid_columns'] : 'auto-fill';
        }
        
        if (empty($grid_gap)) {
            $grid_gap = isset($global_design_options['default_grid_gap']) ? $global_design_options['default_grid_gap'] : '20px';
        }
        
        if (empty($container_border_color)) {
            $container_border_color = isset($global_design_options['default_container_border_color']) ? $global_design_options['default_container_border_color'] : '#e1e5e9';
        }
        
        if (empty($container_background_color)) {
            $container_background_color = isset($global_design_options['default_container_background_color']) ? $global_design_options['default_container_background_color'] : '#f8f9fa';
        }
        
        if (empty($container_padding)) {
            $container_padding = isset($global_design_options['default_container_padding']) ? $global_design_options['default_container_padding'] : '20px';
        }
        
        // Get checkout section setting
        $show_checkout_section = get_post_meta($post_id, '_coderembassy_show_checkout_section', true);
        if ($show_checkout_section === 'use_global' || empty($show_checkout_section)) {
            $show_checkout_section = isset($global_design_options['default_show_checkout_section']) ? $global_design_options['default_show_checkout_section'] : 'yes';
        }
        
        if (!is_array($selected_products) || empty($selected_products)) {
            return '<p>' . esc_html__('No products selected for this shortcode.', 'coderembassy-express-checkout') . '</p>';
        }
        
        ob_start();
        
        // Store success message in data attribute if form was submitted (only for non-AJAX mode)
        $success_message = '';
        if (session_id() && isset($_SESSION['coderembassy_success_message'])) {
            // Only show notification if AJAX is disabled (non-AJAX form submission)
            $ajax_cart = get_post_meta($post_id, '_coderembassy_ajax_cart', true);
            if ($ajax_cart !== '1') {
                $success_message = sanitize_text_field($_SESSION['coderembassy_success_message']);
            }
            unset($_SESSION['coderembassy_success_message']);
        }
        
        // Generate grid template columns CSS
        $grid_template_columns = '';
        if ($grid_columns === 'auto-fill') {
            // Use the actual product width for minmax, not hardcoded 200px
            $min_width = $product_width ?: '200px';
            $grid_template_columns = 'repeat(auto-fill, minmax(' . $min_width . ', 1fr))';
        } else {
            $grid_template_columns = 'repeat(' . intval($grid_columns) . ', 1fr)';
        }
        
        ?>
        <div class="coderembassy-express-checkout" 
             data-shortcode-id="<?php echo esc_attr($post_id); ?>" 
             data-ajax-cart="<?php echo esc_attr($ajax_add_to_cart); ?>" 
             data-quick-cart="<?php echo esc_attr($quick_cart); ?>"
             <?php if (!empty($success_message)): ?>data-success-message="<?php echo esc_attr($success_message); ?>"<?php endif; ?>
             style="border-color: <?php echo esc_attr($container_border_color); ?>; background-color: <?php echo esc_attr($container_background_color); ?>; padding: <?php echo esc_attr($container_padding); ?>;">
            <div class="coderembassy-products-grid" style="grid-template-columns: <?php echo esc_attr($grid_template_columns); ?>; gap: <?php echo esc_attr($grid_gap); ?>;">
                <?php foreach ($selected_products as $product_id): ?>
                    <?php
                    $product = wc_get_product($product_id);
                    if (!$product || !$product->is_purchasable()) {
                        continue;
                    }
                    ?>
                    <div class="coderembassy-product-item<?php echo esc_attr($product->is_type('variable') ? ' has-variations' : ''); ?>" style="width: <?php echo esc_attr($product_width); ?>; height: <?php echo esc_attr($product_height); ?>;">
                        <div class="coderembassy-product-checkbox">
                            <?php if ($product_select_type === 'radio'): ?>
                                <input type="radio" 
                                       id="coderembassy-product-<?php echo esc_attr($product_id); ?>" 
                                       name="coderembassy_products" 
                                       value="<?php echo esc_attr($product_id); ?>"
                                       class="coderembassy-product-radio-input"
                                       data-product-id="<?php echo esc_attr($product_id); ?>"
                                       <?php echo esc_attr($quick_cart ? 'data-quick-cart="true"' : ''); ?> />
                            <?php else: ?>
                                <input type="checkbox" 
                                       id="coderembassy-product-<?php echo esc_attr($product_id); ?>" 
                                       name="coderembassy_products[]" 
                                       value="<?php echo esc_attr($product_id); ?>"
                                       class="coderembassy-product-checkbox-input"
                                       data-product-id="<?php echo esc_attr($product_id); ?>"
                                       <?php echo esc_attr($quick_cart ? 'data-quick-cart="true"' : ''); ?> />
                            <?php endif; ?>
                            <label for="coderembassy-product-<?php echo esc_attr($product_id); ?>"></label>
                        </div>
                        
                        <div class="coderembassy-product-image">
                            <?php echo wp_kses_post($product->get_image('medium')); ?>
                        </div>
                        
                        <div class="coderembassy-product-title" style="font-size: <?php echo esc_attr($title_font_size); ?>; color: <?php echo esc_attr($title_color); ?>;">
                            <a href="<?php echo esc_url($product->get_permalink()); ?>" target="_blank">
                                <?php echo esc_html($product->get_name()); ?>
                            </a>
                        </div>
                        
                        <div class="coderembassy-product-price">
                            <?php echo wp_kses_post($product->get_price_html()); ?>
                        </div>
                        
                        <?php if ($product->is_type('variable')): ?>
                            <div class="coderembassy-product-variations" data-product-id="<?php echo esc_attr($product_id); ?>">
                                <?php
                                $variations = $product->get_available_variations();
                                $attributes = $product->get_variation_attributes();
                                
                                foreach ($attributes as $attribute_name => $options) {
                                    $attribute_label = wc_attribute_label($attribute_name);
                                    $attribute_slug = wc_attribute_taxonomy_slug($attribute_name);
                                    
                                    echo '<div class="coderembassy-variation-group">';
                                    echo '<label class="coderembassy-variation-label">' . esc_html($attribute_label) . ':</label>';
                                    echo '<div class="coderembassy-variation-options">';
                                    
                                    foreach ($options as $option) {
                                        if (empty($option)) continue;
                                        
                                        $option_slug = sanitize_title($option);
                                        $option_id = 'variation_' . esc_attr($product_id) . '_' . esc_attr($attribute_slug) . '_' . esc_attr($option_slug);
                                        
                                        echo '<label class="coderembassy-variation-option" for="' . esc_attr($option_id) . '">';
                                        echo '<input type="radio" name="variation_' . esc_attr($product_id) . '_' . esc_attr($attribute_slug) . '" id="' . esc_attr($option_id) . '" value="' . esc_attr($option) . '" data-attribute="' . esc_attr($attribute_slug) . '">';
                                        echo '<span>' . esc_html($option) . '</span>';
                                        echo '</label>';
                                    }
                                    
                                    echo '</div>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($product->is_on_sale()): ?>
                            <div class="coderembassy-product-sale-badge">
                                <?php esc_html_e('Sale!', 'coderembassy-express-checkout'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="coderembassy-actions">
                <button type="button" class="coderembassy-add-to-cart-btn" <?php echo esc_attr($ajax_add_to_cart ? 'data-ajax="true"' : ''); ?>>
                    <?php esc_html_e('Add Selected to Cart', 'coderembassy-express-checkout'); ?>
                </button>
                <span class="coderembassy-loading" style="display: none;"><?php esc_html_e('Adding to cart...', 'coderembassy-express-checkout'); ?></span>
            </div>
        </div>
        
        <?php if ($show_checkout_section === 'yes'): ?>
        <!-- WooCommerce Checkout Section -->
        <?php
        // Allow pro version to override the entire checkout section
        $checkout_section_html = apply_filters('coderembassy_express_checkout_section_html', '', $post_id);
        
        if (!empty($checkout_section_html)) {
            echo $checkout_section_html;
        } else {
            ?>
            <div class="coderembassy-checkout-section">
                <?php
                // Allow customization of checkout header
                $checkout_header = apply_filters('coderembassy_express_checkout_header', '', $post_id);
                if (!empty($checkout_header)) {
                    echo wp_kses_post($checkout_header);
                } else {
                    ?>
                    <div class="coderembassy-checkout-header">
                        <h3><?php esc_html_e('Complete Your Order', 'coderembassy-express-checkout'); ?></h3>
                        <p><?php esc_html_e('Fill in your details below to complete your purchase:', 'coderembassy-express-checkout'); ?></p>
                    </div>
                    <?php
                }
                ?>
                
                <div class="coderembassy-checkout-content">
                    <?php
                    // Check if WooCommerce cart has items
                    if (function_exists('WC') && WC() && WC()->cart && WC()->cart->is_empty()) {
                        echo '<p class="coderembassy-empty-cart">' . esc_html__('Your cart is empty. Please add some products above.', 'coderembassy-express-checkout') . '</p>';
                    } else {
                        // Allow pro version to override checkout form rendering
                        $checkout_form_html = apply_filters('coderembassy_express_checkout_form_html', '', $post_id);
                        
                        if (!empty($checkout_form_html)) {
                            echo $checkout_form_html;
                        } else {
                            // Display WooCommerce checkout form directly
                            echo '<div class="coderembassy-checkout-form">';
                            
                            // Render WooCommerce checkout form
                            if (function_exists('woocommerce_checkout_form')) {
                                woocommerce_checkout_form();
                            } else {
                                // Fallback: use WooCommerce checkout shortcode
                                echo do_shortcode('[woocommerce_checkout]');
                            }
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
            <?php
        }
        ?>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }
}
