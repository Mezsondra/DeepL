<?php
if (!defined('ABSPATH')) exit;

/**
 * Main filter for the_content
 * - Determines requested language (?lang=XX), compares with base language
 * - Masks exclusions
 * - Extracts plain text for translation while roughly preserving paragraph boundaries
 * - Sends to DeepL and then remaps into HTML structure (simple approach)
 * - Unmasks exclusions
 */
function dst_translate_post_content($content){
    $api_key = trim((string)get_option('dst_api_key'));
    if (!$api_key) return $content;

    $available = array_map('trim', explode(',', (string)get_option('dst_available_langs','EN,FR,TR,DE,ES')));
    $available = array_map('strtoupper', $available);

    $base_lang = strtoupper((string)get_option('dst_base_lang','EN')); // original site language

    // find target from query (?lang=XX)
    $req_lang = isset($_GET['lang']) ? strtoupper(sanitize_text_field($_GET['lang'])) : $base_lang;
    if (!in_array($req_lang, $available, true)) $req_lang = $base_lang;

    // If base language (no translation needed)
    if ($req_lang === $base_lang) return $content;

    // Mask exclusions to preserve them exactly
    list($masked, $map) = dst_mask_exclusions($content);

    // Strip HTML to translate only visible text; keep rough paragraph boundaries
    $text = wp_strip_all_tags($masked, true);
    // Avoid sending empty or tiny content
    if (trim($text) === '') return $content;

    $translated = dst_translate_text($text, $req_lang, (function_exists('get_permalink') ? get_permalink() : ''));

    // Very simple remap: wrap in paragraphs.
    // For richer mapping, you could split by original <p> counts, etc.
    $remapped = wpautop($translated);

    // Restore masked pieces
    $final = dst_unmask_exclusions($remapped, $map);
    return $final;
}
add_filter('the_content', 'dst_translate_post_content', 20);
