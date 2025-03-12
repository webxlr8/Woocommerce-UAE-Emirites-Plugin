<?php
/**
 * Plugin Name: WooCommerce UAE Emirates
 * Plugin URI: https://webxlr8.com/
 * Description: Adds UAE Emirates as selectable states and enhances checkout experience
 * Version: 1.0.0
 * Author: YIB Global Technology Services LLP
 * Author URI: https://webxlr8.com/
 * Text Domain: wc-uae-emirates
 * Requires Plugins: woocommerce
 */

defined('ABSPATH') || exit;

class WC_UAE_Emirates_Plugin {

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function activate() {
        if (!get_option('wc_uae_emirates_list')) {
            update_option('wc_uae_emirates_list', $this->get_default_emirates());
        }
    }

    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Core functionality
        add_filter('woocommerce_states', array($this, 'add_emirates_states'));
        add_filter('woocommerce_get_country_locale', array($this, 'modify_state_label'));
        add_filter('woocommerce_checkout_fields', array($this, 'handle_mandatory_fields'), 20);

        // Admin interface
        if (is_admin()) {
            new WC_UAE_Emirates_Admin();
        }
    }

    private function get_default_emirates() {
        return array(
            'AJ' => __('Ajman', 'wc-uae-emirates'),
            'AZ' => __('Abu Dhabi', 'wc-uae-emirates'),
            'DU' => __('Dubai', 'wc-uae-emirates'),
            'FU' => __('Fujairah', 'wc-uae-emirates'),
            'RK' => __('Ras Al Khaimah', 'wc-uae-emirates'),
            'SH' => __('Sharjah', 'wc-uae-emirates'),
            'UQ' => __('Umm Al Quwain', 'wc-uae-emirates')
        );
    }

    public function woocommerce_missing_notice() {
        echo '<div class="error"><p>';
        _e('WooCommerce UAE Emirates requires WooCommerce to be installed and activated!', 'wc-uae-emirates');
        echo '</p></div>';
    }

    public function add_emirates_states($states) {
        $emirates = get_option('wc_uae_emirates_list', array());
        if (!empty($emirates)) {
            $states['AE'] = $emirates;
        }
        return $states;
    }

    public function modify_state_label($locales) {
        $locales['AE']['state']['label'] = __('Emirate', 'wc-uae-emirates');
        if (get_option('wc_uae_emirates_mandatory', 'yes') === 'yes') {
            $locales['AE']['state']['required'] = true;
            $locales['AE']['state']['hidden'] = false;
        }
        return $locales;
    }

    public function handle_mandatory_fields($fields) {
        if (get_option('wc_uae_emirates_mandatory', 'yes') === 'yes') {
            $fields['billing']['billing_state']['required'] = true;
            $fields['billing']['billing_state']['label'] = __('Emirate', 'wc-uae-emirates');
            $fields['shipping']['shipping_state']['required'] = true;
            $fields['shipping']['shipping_state']['label'] = __('Emirate', 'wc-uae-emirates');
        }
        return $fields;
    }
}

class WC_UAE_Emirates_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_menu() {
        add_submenu_page(
            'woocommerce',
            __('UAE Emirates Settings', 'wc-uae-emirates'),
            __('UAE Emirates', 'wc-uae-emirates'),
            'manage_woocommerce',
            'wc-uae-emirates',
            array($this, 'render_settings_page')
        );
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'woocommerce_page_wc-uae-emirates') return;

        wp_enqueue_style(
            'wc-uae-admin-css',
            plugins_url('admin/admin-ui.css', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'admin/admin-ui.css')
        );

        wp_enqueue_script(
            'wc-uae-admin-js',
            plugins_url('admin/admin-ui.js', __FILE__),
            array('jquery', 'wp-util'),
            filemtime(plugin_dir_path(__FILE__) . 'admin/admin-ui.js'),
            true
        );
    }

    public function register_settings() {
        register_setting('wc_uae_emirates_settings', 'wc_uae_emirates_list', array(
            'sanitize_callback' => array($this, 'sanitize_emirates_list')
        ));

        register_setting('wc_uae_emirates_settings', 'wc_uae_emirates_mandatory', array(
            'type' => 'string',
            'default' => 'yes'
        ));
    }

    public function sanitize_emirates_list($input) {
        if (!current_user_can('manage_woocommerce') || !wp_verify_nonce($_POST['_wpnonce'], 'wc_uae_emirates_settings-options')) {
            return get_option('wc_uae_emirates_list');
        }

        $output = array();
        foreach ((array)$input as $key => $data) {
            $code = sanitize_text_field($data['code']);
            $name = sanitize_text_field($data['name']);
            if ($code && $name) {
                $output[$code] = $name;
            }
        }
        return $output;
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('UAE Emirates Settings', 'wc-uae-emirates') ?></h1>
            
            <form method="post" action="options.php">
                <?php 
                settings_fields('wc_uae_emirates_settings');
                $emirates = get_option('wc_uae_emirates_list');
                $mandatory = get_option('wc_uae_emirates_mandatory', 'yes');
                ?>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Code', 'wc-uae-emirates') ?></th>
                            <th><?php _e('Emirate Name', 'wc-uae-emirates') ?></th>
                            <th width="100"><?php _e('Actions', 'wc-uae-emirates') ?></th>
                        </tr>
                    </thead>
                    <tbody id="emirates-list">
                        <?php foreach ($emirates as $code => $name) : ?>
                            <tr>
                                <td><input type="text" name="wc_uae_emirates_list[<?php echo esc_attr($code) ?>][code]" value="<?php echo esc_attr($code) ?>" required></td>
                                <td><input type="text" name="wc_uae_emirates_list[<?php echo esc_attr($code) ?>][name]" value="<?php echo esc_attr($name) ?>" required></td>
                                <td><button class="button-link delete"><?php _e('Remove', 'wc-uae-emirates') ?></button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p>
                    <button id="add-emirate" class="button"><?php _e('Add New Emirate', 'wc-uae-emirates') ?></button>
                </p>

                <hr>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Checkout Requirements', 'wc-uae-emirates') ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="wc_uae_emirates_mandatory" value="yes" <?php checked($mandatory, 'yes') ?>>
                                <?php _e('Require Emirate selection during checkout', 'wc-uae-emirates') ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize the plugin
new WC_UAE_Emirates_Plugin();
