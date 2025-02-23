<?php
// Verhindert direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

class WP_BFSG_Assist_Frontend {
    private static $options;

    public static function init() {
        self::$options = get_option('wp_bfsg_assist_options', []);
        add_action('wp_footer', [__CLASS__, 'render_buttons']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    // CSS & JS einbinden
    public static function enqueue_assets() {
        wp_enqueue_style('wp-bfsg-assist-style', plugin_dir_url(__FILE__) . '../css/style.css', [], '1.0.0');
        wp_enqueue_script('wp-bfsg-assist-script', plugin_dir_url(__FILE__) . '../js/frontend.js', ['jquery'], '1.0.0', true);
    }

    // Schaltflächen rendern
    public static function render_buttons() {
        // Falls keine Features aktiv sind, nichts anzeigen
        $features = self::get_enabled_features();
        if (empty($features)) {
            return;
        }

        echo '<div id="wp-bfsg-toolbar-container">';
        echo '<button id="wp-bfsg-toggle" class="wp-bfsg-toggle"></button>';
        echo '<div id="wp-bfsg-toolbar" style="display: none;">';
        foreach ($features as $key => $label) {
            echo '<button class="wp-bfsg-btn" data-feature="' . esc_attr($key) . '">' . esc_html($label) . '</button>';
        }
        echo '</div>';
        echo '</div>';
    }

    // Aktivierte Features abrufen
    private static function get_enabled_features() {
        $features = WP_BFSG_Assist::get_feature_labels();
        $enabled = [];
        
        foreach ($features as $key => $label) {
            if (!empty(self::$options['enable_' . $key])) {
                $enabled[$key] = self::$options['label_' . $key] ?? $label;
            }
        }

        return $enabled;
    }
}

WP_BFSG_Assist_Frontend::init();
