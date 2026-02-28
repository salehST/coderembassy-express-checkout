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
        <div class="wrap">
            <h1><?php esc_html_e('CoderEmbassy Express Checkout Configuration', 'coderembassy-express-checkout'); ?></h1>

            <div class="coderembassy-admin-container">
                <div class="coderembassy-admin-tabs">
                    <nav class="coderembassy-tab-nav">
                        <a href="#coderembassy-settings-tab" class="coderembassy-tab-link active" data-tab="settings">
                            <?php esc_html_e('Global Settings', 'coderembassy-express-checkout'); ?>
                        </a>
                        <a href="#coderembassy-design-tab" class="coderembassy-tab-link" data-tab="design">
                            <?php esc_html_e('Global Design', 'coderembassy-express-checkout'); ?>
                        </a>
                    </nav>
                </div>

                <div class="coderembassy-admin-content">
                    <div id="coderembassy-settings-tab" class="coderembassy-tab-content active">
                        <form method="post" action="options.php">
                            <?php
                            settings_fields('coderembassy_configuration');
                            do_settings_sections('coderembassy_configuration');
                            submit_button();
                            ?>
                        </form>
                    </div>

                    <div id="coderembassy-design-tab" class="coderembassy-tab-content">
                        <h2><?php esc_html_e('Global Design Settings', 'coderembassy-express-checkout'); ?></h2>
                        <p><?php esc_html_e('These settings will be used as defaults for all shortcodes. Individual shortcodes can override these settings.', 'coderembassy-express-checkout'); ?></p>

                        <form method="post" action="options.php">
                            <?php
                            settings_fields('coderembassy_design_configuration');
                            ?>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="coderembassy_default_width"><?php esc_html_e('Default Product Width', 'coderembassy-express-checkout'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="coderembassy_default_width" name="coderembassy_global_design_options[default_width]" value="<?php echo esc_attr(get_option('coderembassy_global_design_options')['default_width'] ?? '200px'); ?>" class="regular-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="coderembassy_default_height"><?php esc_html_e('Default Product Height', 'coderembassy-express-checkout'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="coderembassy_default_height" name="coderembassy_global_design_options[default_height]" value="<?php echo esc_attr(get_option('coderembassy_global_design_options')['default_height'] ?? '300px'); ?>" class="regular-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="coderembassy_default_font_size"><?php esc_html_e('Default Title Font Size', 'coderembassy-express-checkout'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="coderembassy_default_font_size" name="coderembassy_global_design_options[default_font_size]" value="<?php echo esc_attr(get_option('coderembassy_global_design_options')['default_font_size'] ?? '16px'); ?>" class="regular-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="coderembassy_default_color"><?php esc_html_e('Default Title Color', 'coderembassy-express-checkout'); ?></label>
                                    </th>
                                    <td>
                                        <input type="color" id="coderembassy_default_color" name="coderembassy_global_design_options[default_color]" value="<?php echo esc_attr(get_option('coderembassy_global_design_options')['default_color'] ?? '#333333'); ?>" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="coderembassy_default_grid_columns"><?php esc_html_e('Default Grid Columns', 'coderembassy-express-checkout'); ?></label>
                                    </th>
                                    <td>
                                        <select id="coderembassy_default_grid_columns" name="coderembassy_global_design_options[default_grid_columns]">
                                            <option value="auto-fill" <?php selected(get_option('coderembassy_global_design_options')['default_grid_columns'] ?? 'auto-fill', 'auto-fill'); ?>><?php esc_html_e('Auto Fill (Responsive)', 'coderembassy-express-checkout'); ?></option>
                                            <option value="1" <?php selected(get_option('coderembassy_global_design_options')['default_grid_columns'] ?? 'auto-fill', '1'); ?>><?php esc_html_e('1 Column', 'coderembassy-express-checkout'); ?></option>
                                            <option value="2" <?php selected(get_option('coderembassy_global_design_options')['default_grid_columns'] ?? 'auto-fill', '2'); ?>><?php esc_html_e('2 Columns', 'coderembassy-express-checkout'); ?></option>
                                            <option value="3" <?php selected(get_option('coderembassy_global_design_options')['default_grid_columns'] ?? 'auto-fill', '3'); ?>><?php esc_html_e('3 Columns', 'coderembassy-express-checkout'); ?></option>
                                            <option value="4" <?php selected(get_option('coderembassy_global_design_options')['default_grid_columns'] ?? 'auto-fill', '4'); ?>><?php esc_html_e('4 Columns', 'coderembassy-express-checkout'); ?></option>
                                            <option value="5" <?php selected(get_option('coderembassy_global_design_options')['default_grid_columns'] ?? 'auto-fill', '5'); ?>><?php esc_html_e('5 Columns', 'coderembassy-express-checkout'); ?></option>
                                        </select>
                                        <p class="description"><?php esc_html_e('Default number of columns for product grid layout.', 'coderembassy-express-checkout'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="coderembassy_default_grid_gap"><?php esc_html_e('Default Grid Gap', 'coderembassy-express-checkout'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="coderembassy_default_grid_gap" name="coderembassy_global_design_options[default_grid_gap]" value="<?php echo esc_attr(get_option('coderembassy_global_design_options')['default_grid_gap'] ?? '20px'); ?>" class="regular-text" placeholder="20px" />
                                        <p class="description"><?php esc_html_e('Default space between products (e.g., 20px, 1rem, 2em).', 'coderembassy-express-checkout'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="coderembassy_default_container_border_color"><?php esc_html_e('Default Container Border Color', 'coderembassy-express-checkout'); ?></label>
                                    </th>
                                    <td>
                                        <input type="color" id="coderembassy_default_container_border_color" name="coderembassy_global_design_options[default_container_border_color]" value="<?php echo esc_attr(get_option('coderembassy_global_design_options')['default_container_border_color'] ?? '#e1e5e9'); ?>" />
                                        <p class="description"><?php esc_html_e('Default border color for the main container.', 'coderembassy-express-checkout'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="coderembassy_default_container_background_color"><?php esc_html_e('Default Container Background Color', 'coderembassy-express-checkout'); ?></label>
                                    </th>
                                    <td>
                                        <input type="color" id="coderembassy_default_container_background_color" name="coderembassy_global_design_options[default_container_background_color]" value="<?php echo esc_attr(get_option('coderembassy_global_design_options')['default_container_background_color'] ?? '#f8f9fa'); ?>" />
                                        <p class="description"><?php esc_html_e('Default background color for the main container.', 'coderembassy-express-checkout'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="coderembassy_default_container_padding"><?php esc_html_e('Default Container Padding', 'coderembassy-express-checkout'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="coderembassy_default_container_padding" name="coderembassy_global_design_options[default_container_padding]" value="<?php echo esc_attr(get_option('coderembassy_global_design_options')['default_container_padding'] ?? '20px'); ?>" class="regular-text" placeholder="20px" />
                                        <p class="description"><?php esc_html_e('Default padding for the main container (e.g., 20px, 1rem, 2em).', 'coderembassy-express-checkout'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="coderembassy_default_show_checkout_section"><?php esc_html_e('Default Show Checkout Section', 'coderembassy-express-checkout'); ?></label>
                                    </th>
                                    <td>
                                        <select id="coderembassy_default_show_checkout_section" name="coderembassy_global_design_options[default_show_checkout_section]">
                                            <option value="yes" <?php selected(get_option('coderembassy_global_design_options')['default_show_checkout_section'] ?? 'yes', 'yes'); ?>><?php esc_html_e('Yes', 'coderembassy-express-checkout'); ?></option>
                                            <option value="no" <?php selected(get_option('coderembassy_global_design_options')['default_show_checkout_section'] ?? 'yes', 'no'); ?>><?php esc_html_e('No', 'coderembassy-express-checkout'); ?></option>
                                        </select>
                                        <p class="description"><?php esc_html_e('Default setting for showing the WooCommerce checkout form after the express checkout to allow users to complete their purchase on the same page.', 'coderembassy-express-checkout'); ?></p>
                                    </td>
                                </tr>
                            </table>
                            <?php submit_button(); ?>
                        </form>
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
