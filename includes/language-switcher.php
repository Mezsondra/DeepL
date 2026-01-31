<?php
if (!defined('ABSPATH')) exit;

function dst_language_switcher(){
    if (is_admin()) return;
    $langs = array_map('trim', explode(',', (string)get_option('dst_available_langs','EN,FR,TR,DE,ES')));
    $langs = array_map('strtoupper', $langs);
    $base  = strtoupper((string)get_option('dst_base_lang','EN'));

    // current lang from query
    $current = isset($_GET['lang']) ? strtoupper(sanitize_text_field($_GET['lang'])) : $base;
    if (!in_array($current, $langs, true)) $current = $base;
    ?>
    <div id="deepl-switcher" style="position:fixed;bottom:16px;right:16px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,.08);padding:8px 10px;z-index:999999;font-family:system-ui;font-size:14px;">
        <select id="deepl-lang-select" style="border:none;outline:none;background:transparent;">
            <?php foreach($langs as $lang): ?>
                <option value="<?php echo esc_attr($lang); ?>" <?php selected($lang, $current); ?>><?php echo esc_html($lang); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <script>
    (function(){
        var sel = document.getElementById('deepl-lang-select');
        var saved = localStorage.getItem('dst_lang');
        if (saved && sel.querySelector('option[value="'+saved+'"]')) {
            sel.value = saved;
            if (!new URLSearchParams(window.location.search).get('lang')) {
                // If user has a saved preference but URL has no lang param, redirect once
                window.location.search = '?lang=' + saved;
                return;
            }
        }

        // Browser language detection on first visit
        if (!saved) {
            var nav = (navigator.language || navigator.userLanguage || 'en');
            var br = nav.split('-')[0].toUpperCase();
            var base = '<?php echo esc_js($base); ?>';
            if (sel.querySelector('option[value="'+br+'"]') && br !== base) {
                localStorage.setItem('dst_lang', br);
                if (!new URLSearchParams(window.location.search).get('lang')) {
                    window.location.search = '?lang=' + br;
                    return;
                }
            }
        }

        sel.addEventListener('change', function(){
            localStorage.setItem('dst_lang', this.value);
            var url = new URL(window.location.href);
            url.searchParams.set('lang', this.value);
            window.location.href = url.toString();
        });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'dst_language_switcher');
