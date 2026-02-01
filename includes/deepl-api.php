<?php
if (!defined('ABSPATH')) exit;

function dst_cache_key($text, $target_lang, $url){
    return 'dst_'.md5($url.'|'.$target_lang.'|'.strlen($text).'|'.wp_hash($text));
}

/**
 * Translate text using DeepL
 * @param string $text Plain text (HTML should be stripped or masked prior)
 * @param string $target_lang e.g. EN, TR, DE
 * @param string $url Current URL/permalink for better cache segmentation
 * @return string translated text (or original on failure)
 */
function dst_translate_text($text, $target_lang = 'EN', $url = '') {
    $api_key = trim((string)get_option('dst_api_key'));
    if (!$api_key || !$text) return $text;

    $target_lang = strtoupper($target_lang);
    $url = $url ?: (function_exists('get_permalink') ? get_permalink() : home_url(add_query_arg([])));

    // Transient cache
    $cache_key = dst_cache_key($text, $target_lang, $url);
    $cached = get_transient($cache_key);
    if ($cached) return $cached;

    // Determine correct endpoint based on API key suffix
    if (substr($api_key, -3) === ':fx') {
        $endpoint = 'https://api-free.deepl.com/v2/translate';
    } else {
        $endpoint = 'https://api.deepl.com/v2/translate';
    }

    $args = [
        'body' => [
            'auth_key'    => $api_key,
            'text'        => $text,
            'target_lang' => $target_lang,
            'tag_handling' => 'html',
            // You can add formality or split_sentences options if desired
            // 'formality' => 'prefer_less' / 'prefer_more',
            // 'split_sentences' => '1'
        ],
        'timeout' => 25
    ];

    $response = wp_remote_post($endpoint, $args);
    if (is_wp_error($response)) {
        return $text;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $body_json = json_decode($body, true);

    if ($code !== 200 || empty($body_json['translations'][0]['text'])) {
        // Log error for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('DeepL API Error: Code ' . $code . ' Response: ' . print_r($body, true));
        }
        return $text;
    }

    $translated = $body_json['translations'][0]['text'];

    // cache 12 hours
    set_transient($cache_key, $translated, 12 * HOUR_IN_SECONDS);
    return $translated;
}
