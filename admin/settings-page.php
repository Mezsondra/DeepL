<?php
if (!defined('ABSPATH')) exit;

function dst_add_admin_menu() {
    add_options_page(
        'DeepL Smart Translator',
        'DeepL Translator',
        'manage_options',
        'deepl-smart-translator',
        'dst_render_settings_page'
    );
}
add_action('admin_menu', 'dst_add_admin_menu');

function dst_render_settings_page(){
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['dst_save_settings'])) {
        check_admin_referer('dst_save_settings');
        update_option('dst_api_key', sanitize_text_field($_POST['dst_api_key'] ?? ''));
        update_option('dst_base_lang', strtoupper(sanitize_text_field($_POST['dst_base_lang'] ?? 'EN')));
        update_option('dst_available_langs', sanitize_text_field($_POST['dst_available_langs'] ?? 'EN,FR,TR,DE,ES'));
        update_option('dst_excluded_shortcodes', sanitize_text_field($_POST['dst_excluded_shortcodes'] ?? ''));
        update_option('dst_excluded_html', sanitize_text_field($_POST['dst_excluded_html'] ?? 'code,pre,script,style,noscript'));
        update_option('dst_excluded_words', sanitize_text_field($_POST['dst_excluded_words'] ?? ''));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $api = esc_attr(get_option('dst_api_key',''));
    $base = esc_attr(get_option('dst_base_lang','EN'));
    $langs = esc_attr(get_option('dst_available_langs','EN,FR,TR,DE,ES'));
    $ex_sc = esc_attr(get_option('dst_excluded_shortcodes',''));
    $ex_html = esc_attr(get_option('dst_excluded_html','code,pre,script,style,noscript'));
    $ex_words = esc_attr(get_option('dst_excluded_words',''));
    ?>
    <div class="wrap">
        <h1>DeepL Smart Translator</h1>
        <form method="POST">
            <?php wp_nonce_field('dst_save_settings'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="dst_api_key">DeepL API Key</label></th>
                    <td><input type="text" id="dst_api_key" name="dst_api_key" value="<?php echo $api; ?>" size="60" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="dst_base_lang">Site Base Language (original content)</label></th>
                    <td><input type="text" id="dst_base_lang" name="dst_base_lang" value="<?php echo $base; ?>" /> <p class="description">Two-letter code, e.g., EN, TR, DE.</p></td>
                </tr>
                <tr>
                    <th scope="row"><label for="dst_available_langs">Available Languages (comma-separated)</label></th>
                    <td><input type="text" id="dst_available_langs" name="dst_available_langs" value="<?php echo $langs; ?>" size="60" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="dst_excluded_shortcodes">Excluded Shortcodes</label></th>
                    <td><input type="text" id="dst_excluded_shortcodes" name="dst_excluded_shortcodes" value="<?php echo $ex_sc; ?>" size="60" />
                        <p class="description">Example: <code>gallery,contact-form-7</code>. These shortcodes will not be translated.</p></td>
                </tr>
                <tr>
                    <th scope="row"><label for="dst_excluded_html">Excluded HTML Tags</label></th>
                    <td><input type="text" id="dst_excluded_html" name="dst_excluded_html" value="<?php echo $ex_html; ?>" size="60" />
                        <p class="description">Example: <code>code,pre,script,style</code>. These elements will be preserved as-is.</p></td>
                </tr>
                <tr>
                    <th scope="row"><label for="dst_excluded_words">Excluded Words/Phrases</label></th>
                    <td><input type="text" id="dst_excluded_words" name="dst_excluded_words" value="<?php echo $ex_words; ?>" size="60" />
                        <p class="description">Comma-separated words/phrases to preserve, case-insensitive.</p></td>
                </tr>
            </table>
            <p><input type="submit" class="button-primary" name="dst_save_settings" value="Save Settings" /></p>
        </form>
    </div>
    <?php
}
