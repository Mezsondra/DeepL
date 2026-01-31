<?php
if (!defined('ABSPATH')) exit;

/**
 * Build exclusion patterns based on admin options.
 * - Shortcodes: list of names to preserve
 * - HTML tags: tag names whose inner HTML should be preserved
 * - Words: exact words/phrases to preserve
 */
function dst_get_exclusion_rules(){
    $shortcodes = array_filter(array_map('trim', explode(',', (string)get_option('dst_excluded_shortcodes',''))));
    $html_tags  = array_filter(array_map('trim', explode(',', (string)get_option('dst_excluded_html','code,pre,script,style,noscript'))));
    $words      = array_filter(array_map('trim', explode(',', (string)get_option('dst_excluded_words',''))));
    return [
        'shortcodes' => $shortcodes,
        'html_tags'  => $html_tags,
        'words'      => $words
    ];
}

/**
 * Mask exclusions with tokens so they won't be sent to DeepL.
 * Returns array: [masked_content, map]
 */
function dst_mask_exclusions($content, $skip_html_tags = false){
    $rules = dst_get_exclusion_rules();
    $map = [];
    $masked = $content;

    // Mask HTML tag blocks
    if (!$skip_html_tags) {
        foreach ($rules['html_tags'] as $tag){
            $tag = preg_quote($tag, '/');
            $pattern = "/<{$tag}\\b[^>]*?>.*?<\\/{$tag}>/is";
            $masked = preg_replace_callback($pattern, function($m) use (&$map){
                $token = '[[DST_HTML_'.md5($m[0]).'_'.substr(wp_hash($m[0]),0,8).']]';
                $map[$token] = $m[0];
                return $token;
            }, $masked);
        }
    }

    // Mask specific shortcodes
    if (!empty($rules['shortcodes'])) {
        global $shortcode_tags; // WP registered shortcodes (we just need names)
        // Build a regex for only the provided shortcodes
        // Use WP core helper to generate a regex for all, then filter within callback.
        $pattern = get_shortcode_regex();
        $masked = preg_replace_callback('/'.$pattern.'/s', function($m) use (&$map, $rules){
            // $m[2] is the shortcode name
            if (!in_array($m[2], $rules['shortcodes'], true)) return $m[0];
            $full = $m[0];
            $token = '[[DST_SC_'.md5($full).'_'.substr(wp_hash($full),0,8).']]';
            $map[$token] = $full;
            return $token;
        }, $masked);
    }

    // Mask words/phrases (case-insensitive)
    foreach ($rules['words'] as $w){
        if ($w === '') continue;
        $w_quoted = preg_quote($w, '/');
        $masked = preg_replace_callback('/'.$w_quoted.'/i', function($m) use (&$map){
            $full = $m[0];
            $token = '[[DST_WORD_'.md5($full.'_'.uniqid('',true)).']]';
            $map[$token] = $full;
            return $token;
        }, $masked);
    }

    return [$masked, $map];
}

/**
 * Restore tokens back to original content.
 */
function dst_unmask_exclusions($content, $map){
    if (empty($map)) return $content;
    // Replace tokens with originals
    foreach ($map as $token => $original){
        $content = str_replace($token, $original, $content);
    }
    return $content;
}
