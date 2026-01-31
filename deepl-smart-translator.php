<?php
/**
 * Plugin Name: DeepL Smart Translator
 * Description: Translate WordPress posts and pages with DeepL API, with smart exclusions and a browser-language-aware frontend language switcher.
 * Version: 1.0.0
 * Author: Halil
 * Text Domain: deepl-smart-translator
 */

if (!defined('ABSPATH')) exit;

define('DST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DST_PLUGIN_URL', plugin_dir_url(__FILE__));

// Includes
require_once DST_PLUGIN_DIR . 'includes/deepl-api.php';
require_once DST_PLUGIN_DIR . 'includes/exclusions.php';
require_once DST_PLUGIN_DIR . 'includes/translator.php';
require_once DST_PLUGIN_DIR . 'includes/language-switcher.php';
require_once DST_PLUGIN_DIR . 'admin/settings-page.php';

/**
 * Activation: set sane defaults
 */
function dst_activate(){
    if (!get_option('dst_available_langs')) {
        update_option('dst_available_langs', 'EN,FR,TR,DE,ES,IT');
    }
    if (!get_option('dst_base_lang')) {
        // Try to derive from site language (e.g., en-US -> EN)
        $blog_lang = get_bloginfo('language'); // e.g. en-US
        $base = strtoupper(substr($blog_lang, 0, 2));
        update_option('dst_base_lang', $base ?: 'EN');
    }
}
register_activation_hook(__FILE__, 'dst_activate');

/**
 * Settings link on Plugins page
 */
function dst_settings_link($links){
    $url = admin_url('options-general.php?page=deepl-smart-translator');
    $links[] = '<a href="'.$url.'">'.__('Settings','deepl-smart-translator').'</a>';
    return $links;
}
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'dst_settings_link');
