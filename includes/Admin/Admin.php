<?php

namespace CoderEmbassy\ExpressCheckout\Admin;

/**
 * Admin class
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
class Admin
{

    /**
     * Constructor
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));

        // Premium Banner for list page
        add_action('admin_notices', array($this, 'add_list_page_banner'), 1);
        add_filter('admin_body_class', array($this, 'add_admin_body_class'));
    }

    /**
     * Add admin menu
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'edit.php?post_type=coderembassy_ec',
            esc_html__('Configuration', 'coderembassy-express-checkout'),
            esc_html__('Configuration', 'coderembassy-express-checkout'),
            'manage_options',
            'coderembassy-configuration',
            array($this, 'configuration_page')
        );
    }

    /**
     * Initialize settings
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function init_settings()
    {
        register_setting('coderembassy_configuration', 'coderembassy_global_options', array($this, 'sanitize_global_options'));
        register_setting('coderembassy_design_configuration', 'coderembassy_global_design_options', array($this, 'sanitize_design_options'));

        add_settings_section(
            'coderembassy_general_section',
            esc_html__('Global Settings', 'coderembassy-express-checkout'),
            array($this, 'general_section_callback'),
            'coderembassy_configuration'
        );

        add_settings_field(
            'coderembassy_default_ajax_cart',
            esc_html__('Default AJAX Add to Cart', 'coderembassy-express-checkout'),
            array($this, 'ajax_cart_callback'),
            'coderembassy_configuration',
            'coderembassy_general_section'
        );

        add_settings_field(
            'coderembassy_default_quick_cart',
            esc_html__('Default Quick Cart', 'coderembassy-express-checkout'),
            array($this, 'quick_cart_callback'),
            'coderembassy_configuration',
            'coderembassy_general_section'
        );

        add_settings_field(
            'coderembassy_default_product_select_type',
            esc_html__('Default Product Select Type', 'coderembassy-express-checkout'),
            array($this, 'product_select_type_callback'),
            'coderembassy_configuration',
            'coderembassy_general_section'
        );

        add_settings_field(
            'coderembassy_checkout_layout_style',
            esc_html__('Checkout Layout Style', 'coderembassy-express-checkout'),
            array($this, 'checkout_layout_style_callback'),
            'coderembassy_configuration',
            'coderembassy_general_section'
        );

        add_settings_field(
            'coderembassy_pro_theme_compatibility_css',
            esc_html__('Pro theme compatibility CSS', 'coderembassy-express-checkout'),
            array($this, 'pro_theme_compatibility_css_callback'),
            'coderembassy_configuration',
            'coderembassy_general_section'
        );
    }

    /**
     * General section callback
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function general_section_callback()
    {
        echo '<p>' . esc_html__('Configure global settings that apply to all express checkout shortcodes. Individual shortcodes can override these settings.', 'coderembassy-express-checkout') . '</p>';
    }

    /**
     * AJAX cart callback
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>     */
    public function ajax_cart_callback()
    {
        $options = get_option('coderembassy_global_options');
        $value = isset($options['default_ajax_cart']) ? $options['default_ajax_cart'] : '0';
?>
        <input type="checkbox" name="coderembassy_global_options[default_ajax_cart]" value="1" <?php checked($value, '1'); ?> />
        <label><?php esc_html_e('Enable AJAX add to cart by default for all shortcodes', 'coderembassy-express-checkout'); ?></label>
    <?php
    }

    /**
     * Quick cart callback
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function quick_cart_callback()
    {
        $options = get_option('coderembassy_global_options');
        $value = isset($options['default_quick_cart']) ? $options['default_quick_cart'] : '0';
    ?>
        <input type="checkbox" name="coderembassy_global_options[default_quick_cart]" value="1" <?php checked($value, '1'); ?> />
        <label><?php esc_html_e('Enable quick cart by default for all shortcodes', 'coderembassy-express-checkout'); ?></label>
    <?php
    }

    /**
     * Product select type callback
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function product_select_type_callback()
    {
        $options = get_option('coderembassy_global_options');
        $value = isset($options['default_product_select_type']) ? $options['default_product_select_type'] : 'checkbox';
    ?>
        <select name="coderembassy_global_options[default_product_select_type]">
            <option value="checkbox" <?php selected($value, 'checkbox'); ?>><?php esc_html_e('Checkbox (Multiple Selection)', 'coderembassy-express-checkout'); ?></option>
            <option value="radio" <?php selected($value, 'radio'); ?>><?php esc_html_e('Radio Button (Single Selection)', 'coderembassy-express-checkout'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Choose how users can select products. Checkbox allows multiple selections, Radio button allows only one selection.', 'coderembassy-express-checkout'); ?></p>
    <?php
    }

    /**
     * Checkout layout style callback
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function checkout_layout_style_callback()
    {
        $options = get_option('coderembassy_global_options');
        $value = isset($options['checkout_layout_style']) ? $options['checkout_layout_style'] : 'custom';
        $is_pro = defined('CODEREMBASSY_EXPRESS_CHECKOUT_PRO_VERSION');
    ?>
        <select name="coderembassy_global_options[checkout_layout_style]">
            <option value="custom" <?php selected($value, 'custom'); ?>><?php esc_html_e('Plugin Standard Layout (Recommended)', 'coderembassy-express-checkout'); ?></option>
            <option value="theme" <?php echo !$is_pro ? 'disabled' : ''; ?> <?php selected($value, 'theme'); ?>>
                <?php esc_html_e('Theme Layout', 'coderembassy-express-checkout'); ?>
                <?php echo !$is_pro ? esc_html__('(PRO Feature)', 'coderembassy-express-checkout') : ''; ?>
            </option>
        </select>
        <p class="description"><?php esc_html_e('The Plugin Standard Layout ensures a clean, WooCommerce-compatible checkout experience. The Theme Layout (available in PRO) allows the checkout to inherit styling directly from your active theme.', 'coderembassy-express-checkout'); ?></p>
    <?php
    }

    /**
     * Pro theme compatibility CSS callback
     * @since 1.0.0
     */
    public function pro_theme_compatibility_css_callback()
    {
        $options = get_option('coderembassy_global_options', array());
        $value = isset($options['pro_theme_compatibility_css']) ? $options['pro_theme_compatibility_css'] : '0';
    ?>
        <input type="checkbox" id="coderembassy_pro_theme_compatibility_css" name="coderembassy_global_options[pro_theme_compatibility_css]" value="1" <?php checked($value, '1'); ?> />
        <label for="coderembassy_pro_theme_compatibility_css"><?php esc_html_e('Enable compatibility CSS for pro themes (WoodMart, Avada, etc.)', 'coderembassy-express-checkout'); ?></label>
        <p class="description"><?php esc_html_e('Only enable this if the product cards or checkout form layout look broken with your theme. Leave unchecked for free themes (e.g. Astra) to avoid overriding their design.', 'coderembassy-express-checkout'); ?></p>
    <?php
    }

    /**
     * Configuration page
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function configuration_page()
    {
    ?>
        <div class="wrap ceec-pro-admin-wrap ceec-pro-config-wrap">
            <!-- Premium Header -->
            <div class="ceec-pro-page-header">
                <!-- Embassy Logo Strip -->
                <div class="ceec-banner-logo">
                    <img src="<?php echo esc_url(plugins_url('assets/images/logo.png', dirname(__DIR__))); ?>" alt="CoderEmbassy" height="42">
                </div>
                <div class="ceec-pro-page-header__content">
                    <div class="ceec-pro-page-header__left">
                        <div class="ceec-pro-page-header__icon">
                            <span class="dashicons dashicons-admin-generic"></span>
                        </div>
                        <div class="ceec-pro-page-header__text">
                            <h1><?php esc_html_e('Configuration', 'coderembassy-express-checkout'); ?></h1>
                            <p><?php esc_html_e('Configure global defaults and display settings for your express checkout.', 'coderembassy-express-checkout'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <form method="post" action="options.php" class="ceec-hero-form">
                <?php
                settings_fields('coderembassy_configuration');
                settings_fields('coderembassy_design_configuration');
                ?>

                <div class="ceec-pro-settings-layout">
                    <!-- Main Column -->
                    <div class="ceec-pro-settings-main">

                        <!-- Card: Global Settings -->
                        <div class="ceec-pro-card">
                            <div class="ceec-pro-card__header ceec-pro-card__header--accent">
                                <div class="ceec-pro-card__header-icon">
                                    <span class="dashicons dashicons-admin-settings"></span>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('Global Settings', 'coderembassy-express-checkout'); ?></h2>
                                    <p><?php esc_html_e('Core behavior settings for all shortcodes.', 'coderembassy-express-checkout'); ?></p>
                                </div>
                            </div>
                            <div class="ceec-pro-card__body">
                                <div class="ceec-settings-rows">
                                    <?php do_settings_sections('coderembassy_configuration'); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Card: Global Design -->
                        <div class="ceec-pro-card">
                            <div class="ceec-pro-card__header">
                                <div class="ceec-pro-card__header-icon">
                                    <span class="dashicons dashicons-admin-appearance"></span>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('Global Design Settings', 'coderembassy-express-checkout'); ?></h2>
                                    <p><?php esc_html_e('Visual and layout defaults for all products.', 'coderembassy-express-checkout'); ?></p>
                                </div>
                            </div>
                            <div class="ceec-pro-card__body">
                                <div class="ceec-settings-rows">
                                    <?php do_settings_sections('coderembassy_design_configuration'); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Save Bar (Premium Style) -->
                        <div class="ceec-pro-card ceec-save-card">
                            <div class="ceec-pro-card__body">
                                <?php submit_button(__('Save All Settings', 'coderembassy-express-checkout'), 'primary', 'submit', false); ?>
                                <p class="ceec-save-hint"><?php esc_html_e('Settings will be applied to all shortcodes immediately.', 'coderembassy-express-checkout'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Column -->
                    <div class="ceec-pro-settings-sidebar">
                        <div class="ceec-pro-card">
                            <div class="ceec-pro-card__header">
                                <h2><?php esc_html_e('Quick Links', 'coderembassy-express-checkout'); ?></h2>
                            </div>
                            <div class="ceec-pro-card__body ceec-quick-links">
                                <a href="<?php echo esc_url(admin_url('edit.php?post_type=coderembassy_ec')); ?>" class="ceec-quick-link">
                                    <span class="dashicons dashicons-list-view"></span> <?php esc_html_e('Manage Shortcodes', 'coderembassy-express-checkout'); ?>
                                </a>
                                <a href="https://coderembassy.com/docs" target="_blank" class="ceec-quick-link">
                                    <span class="dashicons dashicons-editor-help"></span> <?php esc_html_e('Documentation', 'coderembassy-express-checkout'); ?>
                                </a>
                                <a href="https://coderembassy.com/support" target="_blank" class="ceec-quick-link">
                                    <span class="dashicons dashicons-format-chat"></span> <?php esc_html_e('Get Support', 'coderembassy-express-checkout'); ?>
                                </a>
                            </div>
                        </div>

                        <?php if (! defined('CODEREMBASSY_EXPRESS_CHECKOUT_PRO_VERSION')) : ?>
                            <div class="ceec-pro-card ceec-upgrade-card">
                                <div class="ceec-pro-card__body">
                                    <div class="ceec-upgrade-icon"><span class="dashicons dashicons-star-filled"></span></div>
                                    <h3><?php esc_html_e('Upgrade to Pro', 'coderembassy-express-checkout'); ?></h3>
                                    <p><?php esc_html_e('Get premium templates, variation styles, custom image sizes, and priority support.', 'coderembassy-express-checkout'); ?></p>
                                    <a href="https://coderembassy.com/pro" target="_blank" class="ceec-upgrade-btn">
                                        <?php esc_html_e('Go Pro Now', 'coderembassy-express-checkout'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    <?php
    }

    /**
     * Add admin body class
     * @since 1.0.0
     */
    public function add_admin_body_class($classes)
    {
        $screen = get_current_screen();
        if (isset($screen->id) && in_array($screen->id, array('edit-coderembassy_ec', 'coderembassy_ec'))) {
            $classes .= ' ceec-pro-list-page ';
        }
        return $classes;
    }

    /**
     * Add list page banner
     * @since 1.0.0
     */
    public function add_list_page_banner()
    {
        if (defined('CODEREMBASSY_EXPRESS_CHECKOUT_PRO_VERSION')) {
            return;
        }

        $screen = get_current_screen();
        if (!isset($screen->id) || !in_array($screen->id, array('edit-coderembassy_ec', 'coderembassy_ec'))) {
            return;
        }

        $is_pro = defined('CODEREMBASSY_EXPRESS_CHECKOUT_PRO_VERSION');

        $is_edit_screen = ($screen->id === 'coderembassy_ec');
        $is_add_new     = $is_edit_screen && (!isset($_GET['post']) || empty($_GET['post']));

        $title = $is_edit_screen ? ($is_add_new ? __('Add New Shortcode', 'coderembassy-express-checkout') : __('Edit Shortcode', 'coderembassy-express-checkout')) : __('Express Checkout Shortcodes', 'coderembassy-express-checkout');
        $desc  = $is_edit_screen ? __('Configure your express checkout settings below.', 'coderembassy-express-checkout') : __('Manage your express checkout instances and copy shortcodes to your pages.', 'coderembassy-express-checkout');
        $icon  = $is_edit_screen ? 'dashicons-edit' : 'dashicons-list-view';

    ?>
        <div class="ceec-pro-list-header-wrap">
            <div class="ceec-pro-page-header">
                <!-- Embassy Logo Strip -->
                <div class="ceec-banner-logo">
                    <img src="<?php echo esc_url(plugins_url('assets/images/logo.png', dirname(__DIR__))); ?>" alt="CoderEmbassy" height="42">
                </div>
                <div class="ceec-pro-page-header__content">
                    <div class="ceec-pro-page-header__left">
                        <div class="ceec-pro-page-header__icon">
                            <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                        </div>
                        <div class="ceec-pro-page-header__text">
                            <h1><?php echo esc_html($title); ?></h1>
                            <p><?php echo esc_html($desc); ?></p>
                        </div>
                    </div>

                    <div class="ceec-pro-page-header__right">
                        <div class="ceec-pro-page-header__badges">
                            <?php if ($is_pro) : ?>
                                <span class="ceec-version-tag ceec-version-tag--pro">PRO</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!$is_add_new) : ?>
                            <div class="ceec-pro-header-actions">
                                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=coderembassy_ec')); ?>" class="ceec-save-btn">
                                    <span class="dashicons dashicons-plus"></span> <?php esc_html_e('Add New Shortcode', 'coderembassy-express-checkout'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
<?php
    }



    public function sanitize_global_options($input)
    {
        $sanitized = array();

        if (isset($input['default_ajax_cart'])) {
            $sanitized['default_ajax_cart'] = sanitize_text_field($input['default_ajax_cart']);
        }

        if (isset($input['default_quick_cart'])) {
            $sanitized['default_quick_cart'] = sanitize_text_field($input['default_quick_cart']);
        }

        if (isset($input['default_product_select_type'])) {
            $allowed_types = array('checkbox', 'radio');
            $sanitized['default_product_select_type'] = in_array($input['default_product_select_type'], $allowed_types, true)
                ? $input['default_product_select_type']
                : 'checkbox';
        }

        if (isset($input['checkout_layout_style'])) {
            $valid_styles = array('custom', 'theme');
            $style = in_array($input['checkout_layout_style'], $valid_styles, true) ? $input['checkout_layout_style'] : 'custom';

            // Free version only allows 'custom' (Plugin Standard)
            if (!defined('CODEREMBASSY_EXPRESS_CHECKOUT_PRO_VERSION')) {
                $style = 'custom';
            }
            $sanitized['checkout_layout_style'] = $style;
        }

        $sanitized['pro_theme_compatibility_css'] = (!empty($input['pro_theme_compatibility_css'])) ? '1' : '0';

        return $sanitized;
    }

    /**
     * Sanitize design options
     * @since 1.0.0
     * @param array $input Input data
     * @return array Sanitized data
     */
    public function sanitize_design_options($input)
    {
        $sanitized = array();

        if (isset($input['default_width'])) {
            $sanitized['default_width'] = sanitize_text_field($input['default_width']);
        }

        if (isset($input['default_height'])) {
            $sanitized['default_height'] = sanitize_text_field($input['default_height']);
        }

        if (isset($input['default_font_size'])) {
            $sanitized['default_font_size'] = sanitize_text_field($input['default_font_size']);
        }

        if (isset($input['default_color'])) {
            $sanitized['default_color'] = sanitize_hex_color($input['default_color']);
        }

        if (isset($input['default_grid_columns'])) {
            $allowed_columns = array('auto-fill', '1', '2', '3', '4', '5');
            $sanitized['default_grid_columns'] = in_array($input['default_grid_columns'], $allowed_columns, true)
                ? $input['default_grid_columns']
                : 'auto-fill';
        }

        if (isset($input['default_grid_gap'])) {
            $sanitized['default_grid_gap'] = sanitize_text_field($input['default_grid_gap']);
        }

        if (isset($input['default_container_border_color'])) {
            $sanitized['default_container_border_color'] = sanitize_hex_color($input['default_container_border_color']);
        }

        if (isset($input['default_container_background_color'])) {
            $sanitized['default_container_background_color'] = sanitize_hex_color($input['default_container_background_color']);
        }

        if (isset($input['default_container_padding'])) {
            $sanitized['default_container_padding'] = sanitize_text_field($input['default_container_padding']);
        }

        if (isset($input['default_show_checkout_section'])) {
            $allowed_values = array('yes', 'no');
            $sanitized['default_show_checkout_section'] = in_array($input['default_show_checkout_section'], $allowed_values, true)
                ? $input['default_show_checkout_section']
                : 'yes';
        }

        return $sanitized;
    }
}
