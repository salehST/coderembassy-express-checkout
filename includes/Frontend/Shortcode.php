<?php

namespace CoderEmbassy\ExpressCheckout\Frontend;

/**
 * Frontend Shortcode class
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
class Shortcode
{

    /**
     * Constructor
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function __construct()
    {
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
    public function render_shortcode($atts)
    {
        // Checkout output is session-specific and should not be full-page cached.
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        $atts = shortcode_atts(array(
            'id' => 0,
            'name' => '',
        ), $atts, 'coderembassy_checkout');

        $post_id = intval($atts['id']);
        $legacy_product_id = 0;

        // If name is provided, try to find post by name as fallback
        if (!$post_id && !empty($atts['name'])) {
            $post = get_page_by_path($atts['name'], OBJECT, 'coderembassy_ec');
            if ($post) {
                $post_id = $post->ID;
            } else {
                // Backward compatibility: allow product slug in `name`.
                $legacy_product = get_page_by_path($atts['name'], OBJECT, 'product');
                if ($legacy_product) {
                    $legacy_product_id = $legacy_product->ID;
                }
            }
        }

        // Backward compatibility: allow old shortcodes that passed product ID.
        if ($post_id && get_post_type($post_id) === 'product') {
            $legacy_product_id = $post_id;
            $post_id = 0;
        }

        if (!$post_id && !$legacy_product_id) {
            return '<p>' . esc_html__('Invalid shortcode ID.', 'coderembassy-express-checkout') . '</p>';
        }

        if ($post_id && get_post_type($post_id) !== 'coderembassy_ec') {
            return '<p>' . esc_html__('Invalid shortcode ID.', 'coderembassy-express-checkout') . '</p>';
        }

        if ($post_id && get_post_status($post_id) !== 'publish') {
            return '<p>' . esc_html__('This shortcode is not published.', 'coderembassy-express-checkout') . '</p>';
        }

        // Defaults for legacy product-ID shortcode mode.
        $selected_products = array();
        $ajax_add_to_cart = '';
        $quick_cart = '';
        $product_select_type = '';
        $product_width = '';
        $product_height = '';
        $title_font_size = '';
        $title_color = '';
        $grid_columns = '';
        $grid_gap = '';
        $container_border_color = '';
        $container_background_color = '';
        $container_padding = '';

        if ($post_id) {
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
        } else {
            $selected_products = array($legacy_product_id);
        }

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
        $show_checkout_section = $post_id ? get_post_meta($post_id, '_coderembassy_show_checkout_section', true) : 'yes';
        if ($show_checkout_section === 'use_global' || empty($show_checkout_section)) {
            $show_checkout_section = isset($global_design_options['default_show_checkout_section']) ? $global_design_options['default_show_checkout_section'] : 'yes';
        }

        // ---- Pro Display Settings (read from pro plugin meta when active) ----
        $pro_product_layout  = 'grid';
        $pro_show_thumbnail  = 'yes';
        $pro_show_qty        = 'yes';
        $pro_variation_style = 'radio';
        $pro_list_columns    = '1';
        $pro_thumb_width     = '';
        $pro_thumb_height    = '';
        if ( $post_id && defined( 'CODEREMBASSY_EXPRESS_CHECKOUT_PRO_VERSION' ) ) {
            $pro_product_layout  = get_post_meta( $post_id, '_ceec_pro_product_layout',  true ) ?: 'grid';
            $pro_show_thumbnail  = get_post_meta( $post_id, '_ceec_pro_show_thumbnail',  true ) ?: 'yes';
            $pro_show_qty        = get_post_meta( $post_id, '_ceec_pro_show_qty',        true ) ?: 'yes';
            $pro_variation_style = get_post_meta( $post_id, '_ceec_pro_variation_style', true ) ?: 'radio';
            $pro_list_columns    = get_post_meta( $post_id, '_ceec_pro_list_columns',    true ) ?: '1';
            $pro_thumb_width     = get_post_meta( $post_id, '_ceec_pro_thumb_width',     true );
            $pro_thumb_height    = get_post_meta( $post_id, '_ceec_pro_thumb_height',    true );
        }
        // Build extra CSS classes for the outer container
        $pro_extra_classes  = ' ceec-layout-' . sanitize_html_class( $pro_product_layout );
        if ( 'list' === $pro_product_layout ) {
            $pro_extra_classes .= ' ceec-list-cols-' . intval( $pro_list_columns );
        }
        if ( 'no' === $pro_show_thumbnail )          { $pro_extra_classes .= ' ceec-hide-thumbnail'; }
        if ( 'no' === $pro_show_qty )                { $pro_extra_classes .= ' ceec-hide-qty'; }
        if ( 'radio' !== $pro_variation_style )      { $pro_extra_classes .= ' ceec-variation-' . sanitize_html_class( $pro_variation_style ); }

        if (!is_array($selected_products) || empty($selected_products)) {
            return '<p>' . esc_html__('No products selected for this shortcode.', 'coderembassy-express-checkout') . '</p>';
        }

        ob_start();

        // ---- Pro display: output scoped inline CSS so settings reliably override the
        //      free plugin's print_ceec_default_card_css() which runs at wp_head priority
        //      99998 with !important and forces column layout.  Selectors here use the
        //      data-shortcode-id attribute + class chains to achieve specificity 0,4,1
        //      which beats the free plugin's 0,3,1 selectors. ----
        $pro_thumb_width_px  = ( '' !== $pro_thumb_width  && intval( $pro_thumb_width )  > 0 ) ? intval( $pro_thumb_width )  : 0;
        $pro_thumb_height_px = ( '' !== $pro_thumb_height && intval( $pro_thumb_height ) > 0 ) ? intval( $pro_thumb_height ) : 0;
        // CSS values: explicit px when set, 'auto' when blank so the browser always has a defined value.
        $pro_thumb_width_css  = $pro_thumb_width_px  > 0 ? $pro_thumb_width_px  . 'px' : 'auto';
        $pro_thumb_height_css = $pro_thumb_height_px > 0 ? $pro_thumb_height_px . 'px' : 'auto';

        // Always output the style block when pro is active (thumbnail size defaults to auto).
        $pro_style_needed = ( $post_id && defined( 'CODEREMBASSY_EXPRESS_CHECKOUT_PRO_VERSION' ) )
            || 'list' === $pro_product_layout
            || 'no'   === $pro_show_thumbnail
            || 'no'   === $pro_show_qty;

        if ( $pro_style_needed ) :
            $sid = esc_attr( $post_id ); // shortcode ID
            // Base selector — high specificity (0,4,1): beats the free plugin's 0,3,1
            $base = 'body [data-shortcode-id="' . $sid . '"].coderembassy-express-checkout';
        ?>
        <style id="ceec-pro-display-<?php echo $sid; ?>">
        <?php if ( 'list' === $pro_product_layout ) :
            $lcols = intval( $pro_list_columns ) > 0 ? intval( $pro_list_columns ) : 1;
        ?>
        /* === LIST LAYOUT === */
        <?php
        // Thumbnail dimensions for the list row (use custom or fallback defaults)
        $list_img_w = $pro_thumb_width_px  > 0 ? $pro_thumb_width_px  : 64;
        $list_img_h = $pro_thumb_height_px > 0 ? $pro_thumb_height_px : 64;
        // Checkbox top: vertically centre with the image (item top padding + half image - half checkbox)
        $cb_top = 12 + intval( $list_img_h / 2 ) - 10;
        // Variation panel padding-left: aligns chips/labels under the title column
        $var_pad_left = $list_img_w + 12; // image width + column gap
        ?>
        <?php echo $base; ?> .coderembassy-products-grid {
            grid-template-columns: repeat(<?php echo $lcols; ?>, 1fr) !important;
            gap: 0 !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            background: #fff !important;
        }
        /* Product item — CSS Grid replaces flex so image / title / price never fight for width */
        <?php echo $base; ?> .coderembassy-products-grid .coderembassy-product-item {
            display: grid !important;
            grid-template-columns: <?php echo $list_img_w; ?>px 1fr auto !important;
            grid-template-rows: auto auto !important;
            column-gap: 12px !important;
            row-gap: 0 !important;
            align-items: center !important;
            width: 100% !important;
            min-height: <?php echo max( 60, $list_img_h + 16 ); ?>px !important;
            padding: 12px 16px 12px 44px !important;
            border-radius: 0 !important;
            border: none !important;
            border-bottom: 1px solid #f3f4f6 !important;
            box-shadow: none !important;
            transition: background .15s ease !important;
        }
        <?php echo $base; ?> .coderembassy-products-grid .coderembassy-product-item:last-child {
            border-bottom: none !important;
        }
        <?php echo $base; ?> .coderembassy-products-grid .coderembassy-product-item:hover {
            background: #fafafa !important;
            transform: none !important;
            box-shadow: none !important;
        }
        /* Checkbox — absolute, vertically centred with image */
        <?php echo $base; ?> .coderembassy-product-checkbox {
            position: absolute !important;
            top: <?php echo $cb_top; ?>px !important;
            left: 12px !important;
            right: auto !important;
            transform: none !important;
        }
        /* "Options ▾" badge — redundant in list view, chips are always visible */
        <?php echo $base; ?> .coderembassy-product-item.has-variations::after {
            display: none !important;
        }
        /* "✓ In Cart" badge — positioned at image top so it doesn't overlap checkbox */
        <?php echo $base; ?> .coderembassy-product-item.ceec-in-cart::before {
            top: 6px !important;
            left: 44px !important;
        }
        /* Sale badge — same anchor as In Cart badge */
        <?php echo $base; ?> .coderembassy-product-sale-badge {
            top: 6px !important;
            left: 44px !important;
            right: auto !important;
            font-size: .6rem !important;
            padding: 2px 6px !important;
        }
        /* Body — display:contents makes image & title direct grid children */
        <?php echo $base; ?> .coderembassy-product-body {
            display: contents !important;
        }
        /* Image — grid col 1, spans rows 1–2 so it aligns with both title and price */
        <?php echo $base; ?> .coderembassy-product-image {
            grid-column: 1 !important;
            grid-row: 1 / 3 !important;
            align-self: center !important;
            width: <?php echo $list_img_w; ?>px !important;
            height: <?php echo $list_img_h; ?>px !important;
            min-width: <?php echo $list_img_w; ?>px !important;
            border-radius: 8px !important;
            overflow: hidden !important;
            text-align: unset !important;
            margin: 0 !important;
            flex-shrink: unset !important;
        }
        <?php echo $base; ?> .coderembassy-product-image img {
            width: <?php echo $list_img_w; ?>px !important;
            height: <?php echo $list_img_h; ?>px !important;
            object-fit: cover !important;
            border-radius: 8px !important;
            display: block !important;
            margin: 0 !important;
        }
        /* Title — grid col 2, row 1 (bottom-aligned so it sits above the price row) */
        <?php echo $base; ?> .coderembassy-product-title {
            grid-column: 2 !important;
            grid-row: 1 !important;
            align-self: end !important;
            font-size: .875rem !important;
            font-weight: 600 !important;
            min-width: 0 !important;
            overflow-wrap: break-word !important;
            white-space: normal !important;
            padding-bottom: 3px !important;
            margin: 0 !important;
            flex: unset !important;
        }
        /* Footer (price + qty) — grid col 3, spans rows 1–2, right-aligned */
        <?php echo $base; ?> .coderembassy-product-footer {
            grid-column: 3 !important;
            grid-row: 1 / 3 !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-end !important;
            justify-content: center !important;
            align-self: center !important;
            gap: 6px !important;
            padding: 0 !important;
            border-top: none !important;
            margin-top: 0 !important;
            min-width: 0 !important;
            flex-shrink: unset !important;
        }
        /* Price — allow long range prices to wrap gracefully */
        <?php echo $base; ?> .coderembassy-product-price {
            white-space: normal !important;
            text-align: right !important;
            font-size: 14px !important;
            line-height: 1.3 !important;
        }
        /* Variations — full-width row 3, left-padded to align with title column */
        <?php echo $base; ?> .coderembassy-product-variations {
            grid-column: 1 / -1 !important;
            grid-row: 3 !important;
            position: static !important;
            opacity: 1 !important;
            visibility: visible !important;
            transform: none !important;
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            border-top: 1px solid #f3f4f6 !important;
            padding: 8px 0 6px <?php echo $var_pad_left; ?>px !important;
            margin: 0 !important;
            transition: none !important;
            /* Horizontal chip layout */
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            gap: 6px 14px !important;
        }
        /* Variation groups — inline row with label before chips */
        <?php echo $base; ?> .coderembassy-product-variations .coderembassy-variation-group {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            gap: 4px 6px !important;
            margin: 0 !important;
        }
        <?php echo $base; ?> .coderembassy-product-variations .coderembassy-variation-label {
            display: inline !important;
            font-size: 10px !important;
            white-space: nowrap !important;
            margin: 0 !important;
        }
        /* ATC/Remove button — compact inline next to chips */
        <?php echo $base; ?> .coderembassy-product-variations .ceec-variation-atc-wrap {
            border-top: none !important;
            padding: 0 !important;
            margin: 0 !important;
            grid-column: unset !important;
        }
        <?php echo $base; ?> .coderembassy-product-variations .ceec-variation-atc-btn {
            display: inline-block !important;
            width: auto !important;
            padding: 5px 14px !important;
            font-size: 12px !important;
        }
        <?php endif; // list ?>
        <?php if ( 'no' === $pro_show_thumbnail ) : ?>
        /* === HIDE THUMBNAIL === */
        <?php echo $base; ?> .coderembassy-product-image { display: none !important; }
        <?php endif; ?>
        <?php if ( 'no' === $pro_show_qty ) : ?>
        /* === HIDE QTY === */
        <?php echo $base; ?> .coderembassy-product-quantity { display: none !important; }
        <?php endif; ?>
        /* === THUMBNAIL SIZE (auto when blank) === */
        <?php echo $base; ?> .coderembassy-product-image {
            width: <?php echo $pro_thumb_width_css; ?> !important;
            min-width: <?php echo $pro_thumb_width_css; ?> !important;
            height: <?php echo $pro_thumb_height_css; ?> !important;
        }
        <?php echo $base; ?> .coderembassy-product-image img {
            width: <?php echo $pro_thumb_width_css; ?> !important;
            height: <?php echo $pro_thumb_height_css; ?> !important;
            object-fit: cover !important;
        }
        </style>
        <?php endif; // any pro display settings active ?>
        <?php
        // Store success message in data attribute if form was submitted (only for non-AJAX mode)
        $success_message = '';
        if (session_id() && isset($_SESSION['coderembassy_success_message'])) {
            // Only show notification if AJAX is disabled (non-AJAX form submission)
            $ajax_cart = $post_id ? get_post_meta($post_id, '_coderembassy_ajax_add_to_cart', true) : $ajax_add_to_cart;
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
        <div class="coderembassy-express-checkout ceec-express-page<?php echo esc_attr( $pro_extra_classes ); ?>"
            data-shortcode-id="<?php echo esc_attr($post_id); ?>"
            data-ajax-cart="<?php echo esc_attr($ajax_add_to_cart); ?>"
            data-quick-cart="<?php echo esc_attr($quick_cart); ?>"
            <?php if (!empty($success_message)): ?>data-success-message="<?php echo esc_attr($success_message); ?>" <?php endif; ?>
            style="border-color: <?php echo esc_attr($container_border_color); ?>; background-color: <?php echo esc_attr($container_background_color); ?>; padding: <?php echo esc_attr($container_padding); ?>;">
            <div class="coderembassy-products-grid" style="grid-template-columns: <?php echo esc_attr($grid_template_columns); ?>; gap: <?php echo esc_attr($grid_gap); ?>;">
                <?php foreach ($selected_products as $product_id): ?>
                    <?php
                    $product = wc_get_product($product_id);
                    if (!$product || !$product->is_purchasable()) {
                        continue;
                    }
                    ?>
                    <div class="coderembassy-product-item<?php echo esc_attr($product->is_type('variable') ? ' has-variations' : ''); ?>" style="width: <?php echo esc_attr($product_width); ?>;">
                        <?php if ($product->is_on_sale()): ?>
                            <div class="coderembassy-product-sale-badge">
                                <?php esc_html_e('Sale!', 'coderembassy-express-checkout'); ?>
                            </div>
                        <?php endif; ?>

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

                        <!-- Body: grows to fill card, keeps image + title together -->
                        <div class="coderembassy-product-body">
                            <div class="coderembassy-product-image">
                                <?php echo wp_kses_post($product->get_image('medium')); ?>
                            </div>

                            <div class="coderembassy-product-title" style="font-size: <?php echo esc_attr($title_font_size); ?>; color: <?php echo esc_attr($title_color); ?>;">
                                <a href="<?php echo esc_url($product->get_permalink()); ?>" target="_blank">
                                    <?php echo esc_html($product->get_name()); ?>
                                </a>
                            </div>
                        </div>

                        <!-- Footer: price + qty always anchored to bottom of card -->
                        <div class="coderembassy-product-footer">
                            <div class="coderembassy-product-price">
                                <?php echo wp_kses_post($product->get_price_html()); ?>
                            </div>

                            <div class="coderembassy-product-quantity">
                                <span class="coderembassy-qty-label"><?php esc_html_e('Qty:', 'coderembassy-express-checkout'); ?></span>
                                <div class="coderembassy-qty-controls">
                                    <button type="button" class="coderembassy-qty-btn coderembassy-qty-minus" aria-label="<?php esc_attr_e('Decrease quantity', 'coderembassy-express-checkout'); ?>">&#8722;</button>
                                    <input type="number"
                                        class="coderembassy-qty-input"
                                        value="1"
                                        min="1"
                                        max="99"
                                        step="1"
                                        data-product-id="<?php echo esc_attr($product_id); ?>"
                                        aria-label="<?php esc_attr_e('Quantity', 'coderembassy-express-checkout'); ?>" />
                                    <button type="button" class="coderembassy-qty-btn coderembassy-qty-plus" aria-label="<?php esc_attr_e('Increase quantity', 'coderembassy-express-checkout'); ?>">&#43;</button>
                                </div>
                            </div>
                        </div>

                        <?php if ($product instanceof \WC_Product_Variable): ?>
                            <?php
                            // Build default variation HTML (radio buttons)
                            ob_start();
                            $attributes = $product->get_variation_attributes();
                            ?>
                            <div class="coderembassy-product-variations" data-product-id="<?php echo esc_attr($product_id); ?>">
                                <?php
                                foreach ($attributes as $attribute_name => $options) {
                                    $attribute_label = wc_attribute_label($attribute_name);
                                    $attribute_slug  = sanitize_title($attribute_name);

                                    echo '<div class="coderembassy-variation-group">';
                                    echo '<label class="coderembassy-variation-label">' . esc_html($attribute_label) . ':</label>';
                                    echo '<div class="coderembassy-variation-options">';

                                    foreach ($options as $option) {
                                        if (empty($option)) continue;
                                        $option_slug = sanitize_title($option);
                                        $option_id   = 'variation_' . esc_attr($product_id) . '_' . esc_attr($attribute_slug) . '_' . esc_attr($option_slug);

                                        echo '<label class="coderembassy-variation-option" for="' . esc_attr($option_id) . '">';
                                        echo '<input type="radio" name="variation_' . esc_attr($product_id) . '_' . esc_attr($attribute_slug) . '" id="' . esc_attr($option_id) . '" value="' . esc_attr($option) . '" data-attribute="' . esc_attr($attribute_name) . '">';
                                        echo '<span>' . esc_html($option) . '</span>';
                                        echo '</label>';
                                    }

                                    echo '</div>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            <?php
                            $default_variation_html = ob_get_clean();
                            // Allow pro plugin to override variation HTML (e.g. chips or dropdown style)
                            $variation_html = apply_filters(
                                'ceec_pro_variation_html',
                                $default_variation_html,
                                $product,
                                $product_id,
                                array( 'style' => $pro_variation_style )
                            );

                            // In AJAX mode: inject the per-product "Add to Cart" button as the
                            // last child inside the variation panel, so it shows/hides with the
                            // hover panel and is always reachable without leaving the hovered area.
                            if ( $ajax_add_to_cart === '1' ) {
                                $atc_btn = '<div class="ceec-variation-atc-wrap">'
                                    . '<button type="button" class="ceec-variation-atc-btn"'
                                    . ' data-product-id="' . esc_attr( $product_id ) . '"'
                                    . ' disabled>'
                                    . esc_html__( 'Add to Cart', 'coderembassy-express-checkout' )
                                    . '</button></div>';
                                // Insert before the last </div> (closing tag of the variation panel)
                                $last_div = strrpos( $variation_html, '</div>' );
                                if ( false !== $last_div ) {
                                    $variation_html = substr( $variation_html, 0, $last_div )
                                        . $atc_btn
                                        . substr( $variation_html, $last_div );
                                }
                            }

                            echo $variation_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            ?>
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
                                // Check layout style preference
                                $checkout_layout_style = isset($global_options['checkout_layout_style']) ? $global_options['checkout_layout_style'] : 'custom';

                                if (!defined('CODEREMBASSY_EXPRESS_CHECKOUT_PRO_VERSION')) {
                                    $checkout_layout_style = 'custom';
                                }

                                // Display WooCommerce checkout form directly
                                if ($checkout_layout_style === 'custom') {
                                    echo '<div id="ceec-custom-checkout-wrapper" class="coderembassy-checkout-form coderembassy-custom-layout">';
                                } else {
                                    // Default Theme Layout: strictly raw output for native theme compatibility
                                    echo '<div class="coderembassy-checkout-form">';
                                }

                                // Trigger checkout initialization
                                if (function_exists('WC') && WC()->checkout()) {
                                    do_action('woocommerce_checkout_init', WC()->checkout());
                                }

                                // Ensure WooCommerce checkout scripts and gateway scripts are enqueued
                                if (function_exists('wc_enqueue_js')) {
                                    do_action('woocommerce_enqueue_scripts');
                                    do_action('woocommerce_frontend_scripts');
                                    do_action('woocommerce_enqueue_styles');
                                }

                                // Render WooCommerce checkout form (same as default checkout page)
                                echo do_shortcode('[woocommerce_checkout]');
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
