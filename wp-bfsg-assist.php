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

 */

// Verhindert direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

// Frontend einbinden
require_once plugin_dir_path(__FILE__) . 'includes/frontend.php';

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

        // Get current language
        $language = isset($this->options['language']) ? $this->options['language'] : 'de';

        add_settings_section(
            'wp_bfsg_assist_section',
            $language === 'en' ? 'Settings' : 'Einstellungen',
            null,
            'wp_bfsg_assist_settings'
        );

        add_settings_field(
            'wp_bfsg_assist_language',
            $language === 'en' ? 'Admin Interface Language' : 'Admin-Oberfläche Sprache',
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

        // Font Awesome Option
        add_settings_field(
            'wp_bfsg_assist_use_fontawesome',
            $language === 'en' ? 'Use Font Awesome Icon' : 'Font Awesome Icon verwenden',
            array($this, 'fontawesome_checkbox_callback'),
            'wp_bfsg_assist_settings',
            'wp_bfsg_assist_section'
        );
    }

    // Einstellungsseite rendern
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Aktiver Tab (default now 'info' instead of 'settings')
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'info';
        
        // Get current language
        $language = isset($this->options['language']) ? $this->options['language'] : 'de';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP-BFSG Assist', 'wp-bfsg-assist'); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=wp-bfsg-assist&tab=info" class="nav-tab <?php echo $active_tab == 'info' ? 'nav-tab-active' : ''; ?>">
                    <?php echo $language === 'en' ? 'Info' : 'Info'; ?>
                </a>
                <a href="?page=wp-bfsg-assist&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php echo $language === 'en' ? 'Settings' : 'Einstellungen'; ?>
                </a>
            </h2>

            <?php if ($active_tab == 'info'): ?>
                <div class="wp-bfsg-info-wrapper" style="margin-top: 20px;">
                    <h2><?php echo $language === 'en' ? 'About WP-BFSG Assist' : 'Über WP-BFSG Assist'; ?></h2>
                    <div class="wp-bfsg-info-content">
                        <p><?php esc_html_e('Version: 1.0.0', 'wp-bfsg-assist'); ?></p>
                        <p><?php echo $language === 'en' 
                            ? 'WP-BFSG Assist is a plugin to improve accessibility according to BFSG.'
                            : 'WP-BFSG Assist ist ein Plugin zur Verbesserung der Barrierefreiheit gemäß BFSG.'; ?></p>
                        
                        <h3><?php echo $language === 'en' ? 'Features:' : 'Features:'; ?></h3>
                        <ul style="list-style: disc; margin-left: 20px;">
                            <li><?php echo $language === 'en' 
                                ? 'Accessible menu for website visitors'
                                : 'Barrierefreies Menü für Website-Besucher'; ?></li>
                            <li><?php echo $language === 'en' 
                                ? 'Adjustable text sizes'
                                : 'Anpassbare Textgrößen'; ?></li>
                            <li><?php echo $language === 'en' 
                                ? 'High contrast mode'
                                : 'Kontrast-Modus'; ?></li>
                            <li><?php echo $language === 'en' 
                                ? 'Readable fonts'
                                : 'Lesbare Schriftarten'; ?></li>
                            <li><?php echo $language === 'en' 
                                ? 'Keyboard navigation'
                                : 'Tastatur-Navigation'; ?></li>
                        </ul>

                        <h3><?php echo $language === 'en' ? 'Support & Donate' : 'Support & Spenden'; ?></h3>
                        <p><?php echo $language === 'en' 
                            ? 'For questions or issues contact me:'
                            : 'Bei Fragen oder Problemen kontaktiert mich:'; ?></p>
                        <p><a href="https://goose-media.de" target="_blank">Luca Lupo (Goose Media)</a></p>
                        
                        <div class="wp-bfsg-donation" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                            <h4><?php echo $language === 'en' ? 'Support Development' : 'Unterstütze die Entwicklung'; ?></h4>
                            <p><?php echo $language === 'en' 
                                ? 'If you like this plugin and want to support its development, I would appreciate a small donation:'
                                : 'Wenn dir dieses Plugin gefällt und du die Weiterentwicklung unterstützen möchtest, freue ich mich über eine kleine Spende:'; ?></p>
                            <p style="margin-top: 10px;">
                                <a href="https://www.paypal.com/paypalme/lucalupo" target="_blank" class="button button-primary">
                                    <?php echo $language === 'en' ? 'Donate via PayPal' : 'Via PayPal spenden'; ?> ❤️
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            <?php elseif ($active_tab == 'settings'): ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wp_bfsg_assist_settings');
                    do_settings_sections('wp_bfsg_assist_settings');
                    submit_button($language === 'en' ? 'Save Settings' : 'Einstellungen speichern');
                    ?>
                </form>
            <?php endif; ?>
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
        <select name="wp_bfsg_assist_options[language]" id="wp_bfsg_language_select" onchange="updateLanguage(this)">
            <option value="de" <?php selected($language, 'de'); ?>>Deutsch</option>
            <option value="en" <?php selected($language, 'en'); ?>>English</option>
        </select>
        <script>
        function updateLanguage(select) {
            // Create and submit a form with the new language setting
            var form = document.createElement('form');
            form.method = 'post';
            form.action = 'options.php';
            
            // Add WordPress nonce field
            var nonce = document.createElement('input');
            nonce.type = 'hidden';
            nonce.name = '_wpnonce';
            nonce.value = '<?php echo wp_create_nonce('wp_bfsg_assist_settings-options'); ?>';
            form.appendChild(nonce);

            // Add referrer field
            var referrer = document.createElement('input');
            referrer.type = 'hidden';
            referrer.name = '_wp_http_referer';
            referrer.value = window.location.pathname + window.location.search;
            form.appendChild(referrer);

            // Add option page field
            var optionPage = document.createElement('input');
            optionPage.type = 'hidden';
            optionPage.name = 'option_page';
            optionPage.value = 'wp_bfsg_assist_settings';
            form.appendChild(optionPage);

            // Add language setting
            var currentOptions = <?php echo json_encode($this->options); ?>;
            currentOptions.language = select.value;
            
            // Add all current options as hidden fields
            Object.keys(currentOptions).forEach(function(key) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'wp_bfsg_assist_options[' + key + ']';
                input.value = currentOptions[key];
                form.appendChild(input);
            });

            // Add action field
            var action = document.createElement('input');
            action.type = 'hidden';
            action.name = 'action';
            action.value = 'update';
            form.appendChild(action);

            document.body.appendChild(form);
            form.submit();
        }
        </script>
        <?php
    }

    // Callback für Font Awesome Checkbox
    public function fontawesome_checkbox_callback() {
        // Strikte Prüfung auf 1
        $checked = (isset($this->options['use_fontawesome']) && $this->options['use_fontawesome'] == 1) ? 'checked' : '';
        echo '<input type="checkbox" id="wp_bfsg_assist_use_fontawesome" name="wp_bfsg_assist_options[use_fontawesome]" value="1" ' . esc_attr($checked) . '>';
        echo '<p class="description">' . __('Aktivieren Sie diese Option, um das Font Awesome Rollstuhl-Icon zu verwenden. Deaktivieren Sie sie, um das Standard-Emoji zu verwenden.', 'wp-bfsg-assist') . '</p>';
    }

    // Einstellungen validieren
    public function sanitize_settings($input) {
        $output = [];
        
        // Get current and new language settings
        $old_language = isset($this->options['language']) ? $this->options['language'] : 'de';
        $new_language = in_array($input['language'], ['de', 'en']) ? $input['language'] : 'de';
        
        // If language changed, update the labels
        if ($old_language !== $new_language) {
            $features = $this->get_feature_labels($new_language);
            foreach ($features as $key => $label) {
                // Update labels to new language if they match the default
                if (isset($this->options['label_' . $key]) && 
                    $this->options['label_' . $key] === $this->get_feature_labels($old_language)[$key]) {
                    $input['label_' . $key] = $label;
                }
            }
        }

        // Rest of sanitization
        $output['language'] = $new_language;
        
        // Font Awesome Option
        $output['use_fontawesome'] = isset($input['use_fontawesome']) && $input['use_fontawesome'] == 1 ? 1 : 0;
        
        // Features verarbeiten
        $features = $this->get_feature_labels($new_language);
        foreach ($features as $key => $label) {
            $output['enable_' . $key] = isset($input['enable_' . $key]) ? 1 : 0;
            $output['label_' . $key] = isset($input['label_' . $key]) && !empty(trim($input['label_' . $key])) 
                ? sanitize_text_field($input['label_' . $key]) 
                : esc_html($label);
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
    public static function get_feature_labels($specific_language = null) {
        $instance = self::get_instance();
        $language = $specific_language ?? (isset($instance->options['language']) ? $instance->options['language'] : 'de');
    
        $labels = [
            'de' => [
                'keyboard_nav' => __('Tastatur-Navigation', 'wp-bfsg-assist'),
                'disable_animations' => __('Animationen ausschalten', 'wp-bfsg-assist'),
                'contrast' => __('Kontrast', 'wp-bfsg-assist'),
                'increase_text' => __('Text vergrößern', 'wp-bfsg-assist'),  // Changed from 'Textgrößen'
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
        // Fallback
        return $labels['de'];
    }
    // Frontend-Skripte laden
    public function enqueue_scripts() {
        wp_enqueue_style('wp-bfsg-assist-style', plugin_dir_url(__FILE__) . 'css/style.css', [], '1.0.0');
        
        // Font Awesome handling
        if (!empty($this->options['use_fontawesome'])) {
            $fa_path = plugin_dir_path(__FILE__) . 'assets/fontawesome/css/all.min.css';
            $fa_url = plugin_dir_url(__FILE__) . 'assets/fontawesome/css/all.min.css';
            
            if (file_exists($fa_path)) {
                wp_enqueue_style('fontawesome', $fa_url, [], '5.15.4');
            } else {
                // Fallback to CDN if local file doesn't exist
                wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');
                
                // Add admin notice if we're in admin area
                if (is_admin()) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-warning"><p>';
                        echo esc_html__('Font Awesome files are missing. Please ensure the assets are properly installed in the plugin directory.', 'wp-bfsg-assist');
                        echo '</p></div>';
                    });
                }
            }
        }
        
        wp_enqueue_script('wp-bfsg-assist-script', plugin_dir_url(__FILE__) . 'js/frontend.js', ['jquery'], '1.0.0', true);
        
        // Icon-Type an Frontend übergeben
        wp_localize_script('wp-bfsg-assist-script', 'wpBfsgAssist', [
            'useFontAwesome' => !empty($this->options['use_fontawesome'])
        ]);
    }
}

// Plugin initialisieren
WP_BFSG_Assist::get_instance();
