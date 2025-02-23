<?php
/**
 * Plugin Name: WP-BFSG Assist
 * Plugin URI: https://github.com/luupo/WP-BFGS-Assist
 * Description: Ein Plugin zur Verbesserung der Barrierefreiheit gemäß BFSG.
 * Version: 1.0.0
 * Author: Luca Lupo (Goose Media)
 * Author URI: https://goose-media.de
 * License: GPL2
 * Text Domain: wp-bfsg-assist
 * Domain Path: /languages
 */

// Verhindert direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

class WP_BFSG_Assist {
    private static $instance = null;
    private $options;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->options = get_option('wp_bfsg_assist_options', []);
        // Hooks
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    // Sprachdateien laden
    public function load_textdomain() {
        load_plugin_textdomain('wp-bfsg-assist', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    // Admin-Menü hinzufügen
    public function add_admin_menu() {
        add_menu_page(
            __('WP-BFSG Assist', 'wp-bfsg-assist'),
            __('WP-BFSG Assist', 'wp-bfsg-assist'),
            'manage_options',
            'wp-bfsg-assist',
            array($this, 'settings_page'),
            'dashicons-universal-access-alt',
            25
        );
    }

    // Einstellungen registrieren
    public function register_settings() {
        register_setting('wp_bfsg_assist_settings', 'wp_bfsg_assist_options', array($this, 'sanitize_settings'));

        add_settings_section(
            'wp_bfsg_assist_section',
            __('Einstellungen', 'wp-bfsg-assist'),
            null,
            'wp_bfsg_assist_settings'
        );

        add_settings_field(
            'wp_bfsg_assist_language',
            __('Admin-Oberfläche Sprache', 'wp-bfsg-assist'),
            array($this, 'language_dropdown_callback'),
            'wp_bfsg_assist_settings',
            'wp_bfsg_assist_section'
        );

        // Hier rufen wir die Feature-Labels auf
        $features = $this->get_feature_labels();
        foreach ($features as $key => $label) {
            // Checkbox
            add_settings_field(
                'wp_bfsg_assist_enable_' . $key,
                esc_html($label),
                array($this, 'checkbox_callback'),
                'wp_bfsg_assist_settings',
                'wp_bfsg_assist_section',
                array('field' => $key)
            );

            // Textbox (Beschriftung / Label for ...)
            add_settings_field(
                'wp_bfsg_assist_label_' . $key,
                $this->get_label_prefix() . ' ' . esc_html($label),
                array($this, 'textbox_callback'),
                'wp_bfsg_assist_settings',
                'wp_bfsg_assist_section',
                array('field' => $key)
            );
        }
    }

    // Einstellungsseite rendern
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP-BFSG Assist Einstellungen', 'wp-bfsg-assist'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_bfsg_assist_settings');
                do_settings_sections('wp_bfsg_assist_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // Checkbox-Callback
    public function checkbox_callback($args) {
        $checked = isset($this->options['enable_' . $args['field']]) ? 'checked' : '';
        echo '<input type="checkbox" name="wp_bfsg_assist_options[enable_' . esc_attr($args['field']) . ']" value="1" ' . esc_attr($checked) . '>';
    }

    // Textbox-Callback für benutzerdefinierte Labels
    public function textbox_callback($args) {
        $value = isset($this->options['label_' . $args['field']]) ? esc_attr($this->options['label_' . $args['field']]) : '';
        echo '<input type="text" name="wp_bfsg_assist_options[label_' . esc_attr($args['field']) . ']" value="' . $value . '" class="regular-text">';
    }

    // Sprachdropdown
    public function language_dropdown_callback() {
        $language = isset($this->options['language']) ? $this->options['language'] : 'de';
        ?>
        <select name="wp_bfsg_assist_options[language]">
            <option value="de" <?php selected($language, 'de'); ?>>Deutsch</option>
            <option value="en" <?php selected($language, 'en'); ?>>English</option>
        </select>
        <?php
    }

    // Einstellungen validieren
    public function sanitize_settings($input) {
        $output = [];
        // Sprache validieren
        $output['language'] = in_array($input['language'], ['de', 'en']) ? $input['language'] : 'de';
        // Alle Features verarbeiten
        $features = $this->get_feature_labels();
        foreach ($features as $key => $label) {
            // Checkbox
            $output['enable_' . $key] = isset($input['enable_' . $key]) ? 1 : 0;
            // Textbox (wenn leer -> automatischen Text eintragen)
            if (isset($input['label_' . $key]) && !empty(trim($input['label_' . $key]))) {
                $output['label_' . $key] = sanitize_text_field($input['label_' . $key]);
            } else {
                // Standard automatisch
                $output['label_' . $key] = esc_html($label);
            }
        }
        return $output;
    }

    // Gibt uns 'Beschriftung für' oder 'Label for' zurück
    private function get_label_prefix() {
        $language = isset($this->options['language']) ? $this->options['language'] : 'de';
        if ($language === 'en') {
            return __('Label for', 'wp-bfsg-assist');
        } else {
            return __('Beschriftung für', 'wp-bfsg-assist');
        }
    }

    // Feature-Labels abrufen
    private function get_feature_labels() {
        // Je nach aktueller Sprache (aus den Options) die Labels
        $language = isset($this->options['language']) ? $this->options['language'] : 'de';
        
        $labels = [
            'de' => [
                'keyboard_nav' => __('Tastatur-Navigation', 'wp-bfsg-assist'),
                'disable_animations' => __('Animationen ausschalten', 'wp-bfsg-assist'),
                'contrast' => __('Kontrast', 'wp-bfsg-assist'),
                'increase_text' => __('Textgrößen', 'wp-bfsg-assist'),
                'decrease_text' => __('Text verkleinern', 'wp-bfsg-assist'),
                'readable_font' => __('Lesbare Schriftart', 'wp-bfsg-assist'),
                'mark_titles' => __('Titel hervorheben', 'wp-bfsg-assist'),
                'highlight_links' => __('Links & Schaltflächen hervorheben', 'wp-bfsg-assist')
            ],
            'en' => [
                'keyboard_nav' => __('Keyboard Navigation', 'wp-bfsg-assist'),
                'disable_animations' => __('Disable Animations', 'wp-bfsg-assist'),
                'contrast' => __('High Contrast', 'wp-bfsg-assist'),
                'increase_text' => __('Increase Text Size', 'wp-bfsg-assist'),
                'decrease_text' => __('Decrease Text Size', 'wp-bfsg-assist'),
                'readable_font' => __('Readable Font', 'wp-bfsg-assist'),
                'mark_titles' => __('Highlight Titles', 'wp-bfsg-assist'),
                'highlight_links' => __('Highlight Links & Buttons', 'wp-bfsg-assist')
            ]
        ];

        if (array_key_exists($language, $labels)) {
            return $labels[$language];
        }
        // Fallback, wenn Sprache nicht existiert
        return $labels['de'];
    }

    // Frontend-Skripte laden
    public function enqueue_scripts() {
        wp_enqueue_style('wp-bfsg-assist-style', plugin_dir_url(__FILE__) . 'css/style.css', [], '1.0.0');
        wp_enqueue_script('wp-bfsg-assist-script', plugin_dir_url(__FILE__) . 'js/script.js', ['jquery'], '1.0.0', true);
    }
}

// Plugin initialisieren
WP_BFSG_Assist::get_instance();
