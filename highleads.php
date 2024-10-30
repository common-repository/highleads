<?php

/**
 * Plugin Name: Highleads
 * Plugin URI: https://www.highleads.co
 * Description: Automatically embed the Highleads chatbot on your WordPress site for enhanced lead generation and customer support.
 * Version: 1.1.1
 * Author: Highleads
 * Author URI: https://www.highleads.co/
 * License: GPL2
 * Text Domain: highleads
 * Domain Path: /admin/languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('HIGHLEADS_VERSION', '1.1.1');
define('HIGHLEADS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HIGHLEADS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Add settings link to plugin listing
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'highleads_add_settings_link');

/**
 * Add settings link to plugin listing
 *
 * @param array $links Array of plugin action links.
 * @return array Modified array of plugin action links.
 */
function highleads_add_settings_link($links)
{
    /* translators: %s: The settings page URL */
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url(admin_url('options-general.php?page=highleads_options')),
        esc_html__('Settings', 'highleads')
    );
    array_unshift($links, $settings_link);
    return $links;
}

// Hook to add an options page to the WordPress admin menu
add_action('admin_menu', 'highleads_add_options_page');

/**
 * Add the Highleads Options page to the admin menu.
 */
function highleads_add_options_page()
{
    add_options_page(
        esc_html__('Highleads Plugin Settings', 'highleads'),
        esc_html__('Highleads Options', 'highleads'),
        'manage_options',
        'highleads_options',
        'highleads_render_options_page'
    );
}

// Register settings
add_action('admin_init', 'highleads_register_settings');

/**
 * Register settings for the Highleads Options page.
 */
function highleads_register_settings()
{
    if (current_user_can('manage_options')) {
        register_setting(
            'highleads_options',
            'highleads_chatbot_id',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );
    }
}

/**
 * Enqueue admin styles and scripts.
 *
 * @param string $hook The current admin page.
 */
function highleads_enqueue_admin_assets($hook)
{
    if ('settings_page_highleads_options' !== $hook) {
        // error_log("Expected settings_page_highleads_options, got: " . $hook);
        return;
    }

    wp_enqueue_style(
        'highleads-admin-style',
        HIGHLEADS_PLUGIN_URL . 'admin/css/highleads-admin.css',
        array(),
        HIGHLEADS_VERSION
    );

    wp_enqueue_script(
        'highleads-admin-script',
        HIGHLEADS_PLUGIN_URL . 'admin/js/highleads-admin.js',
        array('jquery'),
        HIGHLEADS_VERSION,
        true
    );
}

add_action('admin_enqueue_scripts', 'highleads_enqueue_admin_assets');

/**
 * Display the content of the Highleads Options page.
 */
function highleads_render_options_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['settings-updated'])) {
        $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (wp_verify_nonce($nonce, 'highleads_options-options')) {
            add_settings_error('highleads_messages', 'highleads_message', __('Settings Saved', 'highleads'), 'updated');
        }
    }
?>
    <div class="wrap highleads-admin-wrap">
        <div class="highleads-logo-container">
            <img src="<?php echo esc_url(HIGHLEADS_PLUGIN_URL . 'admin/images/highleads-logo-black.svg'); ?>"
                alt="<?php esc_attr_e('Highleads', 'highleads'); ?>" />
        </div>

        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php settings_errors('highleads_messages'); ?>

        <div class="highleads-form-container">
            <h2><?php esc_html_e('Chatbot Configuration', 'highleads'); ?></h2>
            <p class="config-description">
                <?php esc_html_e('Configure your Highleads chatbot settings below.', 'highleads'); ?>
            </p>

            <form method="post" action="options.php">
                <?php
                settings_fields('highleads_options');
                do_settings_sections('highleads_options');
                ?>

                <div class="highleads-form-group">
                    <label for="highleads_chatbot_id" class="highleads-label">
                        <?php esc_html_e('Chatbot ID', 'highleads'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text"
                        class="regular-text"
                        name="highleads_chatbot_id"
                        id="highleads_chatbot_id"
                        value="<?php echo esc_attr(get_option('highleads_chatbot_id')); ?>"
                        required />
                    <p class="description">
                        <?php
                        printf(
                            /* translators: %s: Link to Highleads console */
                            esc_html__('Find your Chatbot ID in the %s chatbot settings tab.', 'highleads'),
                            '<a href="https://console.highleads.co/" target="_blank">Highleads Console</a>'
                        );
                        ?>
                    </p>
                </div>

                <div class="highleads-submit-btn-container">
                    <?php
                    submit_button(
                        __('Save Changes', 'highleads'),
                        'primary',
                        'submit',
                        false,
                        array('class' => 'button-primary')
                    );
                    ?>
                </div>

                <div class="highleads-help">
                    <h3><?php esc_html_e('Need Help?', 'highleads'); ?></h3>
                    <div class="highleads-contact">
                        <a href="https://www.highleads.co/contact" target="_blank" class="highleads-contact-link">
                            <span class="dashicons dashicons-email"></span>
                            <?php esc_html_e('Contact Us', 'highleads'); ?>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php
}

/**
 * Enqueue the Highleads chatbot script.
 */
function highleads_enqueue_chatbot_script()
{
    $chatbot_id = get_option('highleads_chatbot_id');
    if (!$chatbot_id) {
        return;
    }

    // Register the script
    wp_register_script(
        'highleads-chatbot',
        'https://console.highleads.co/widgets/index.js',
        array(),
        HIGHLEADS_VERSION,
        true
    );

    // Create the initialization script with API validation
    $init_script = sprintf(
        'window.__ch = {id: "%s", url: "https://console.highleads.co"};
        (async function() {
            function cleanupChatbotElements() {
                const selectors = [
                    "[class*=\'highleads\']",
                    "[id*=\'highleads\']",
                    "[class*=\'ch-widget\']",
                    "[id*=\'ch-widget\']",
                    ".ch-messenger-frame",
                    ".ch-messenger-hidden",
                    "[id*=\'ch-messenger\']",
                    "[class*=\'messenger\']",
                    "iframe[src*=\'highleads\']",
                    "iframe[src*=\'ch-widget\']"
                ];
                
                const elements = document.querySelectorAll(selectors.join(", "));
                elements.forEach(element => element.remove());
                
                const bodyClasses = document.body.className.split(" ")
                    .filter(className => !className.includes("highleads") && !className.includes("ch-"))
                    .join(" ");
                document.body.className = bodyClasses;
            }

            try {
                const response = await fetch("https://console.highleads.co/api/chatbot/%s");
                if (!response.ok) {
                    cleanupChatbotElements();
                    return;
                }
                jQuery(function($) {
                    $.getScript("https://console.highleads.co/widgets/index.js");
                });
            } catch (error) {
                console.error("Failed to validate Highleads chatbot:", error);
                cleanupChatbotElements();
            }
        })();',
        esc_js($chatbot_id),
        esc_js($chatbot_id)
    );

    // Add the initialization script
    wp_add_inline_script(
        'jquery',
        $init_script,
        'after'
    );

    // Deregister the original script to prevent double loading
    wp_deregister_script('highleads-chatbot');
}
add_action('wp_enqueue_scripts', 'highleads_enqueue_chatbot_script');

/**
 * Load plugin text domain for translations.
 */
function highleads_load_textdomain()
{
    load_plugin_textdomain('highleads', false, dirname(plugin_basename(__FILE__)) . 'admin/languages');
}
add_action('plugins_loaded', 'highleads_load_textdomain');
?>