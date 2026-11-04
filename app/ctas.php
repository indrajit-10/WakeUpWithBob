<?php
/**
 * Sidebar buttons — the maroon call-to-action buttons at the top of the right
 * column (and of the hamburger menu wherever the right column is hidden).
 *
 * Bob edits their text and links in Admin → Settings → Sidebar buttons. They are
 * stored in the settings table as cta1_text / cta1_url / cta1_icon / cta1_on
 * (and cta2_…).
 * Until Bob saves anything, the starting values below are shown.
 */

/** Starting values for each button, used until they are saved in the admin. */
function sidebar_cta_defaults(): array {
    return [
        1 => ['text' => 'Send Happy Birthday eCards', 'url' => 'https://www.123greetings.com/birthday/happy_birthday/',   'icon' => 'cake',  'on' => '1'],
        2 => ['text' => 'Send Thanksgiving eCards',   'url' => 'https://www.123greetings.com/events/thanksgiving/wishes/', 'icon' => 'ecard', 'on' => '1'],
    ];
}

/** The icons Bob can pick for a button: key => [label in the admin, SVG shapes].
 *  Drawn in currentColor (white on the maroon button); no image files needed. */
function sidebar_cta_icons(): array {
    return [
        ''      => ['label' => 'No icon',       'svg' => ''],
        'cake'  => ['label' => 'Birthday cake', 'svg' => '<path d="M12 6c1.11 0 2-.9 2-2 0-.38-.1-.73-.29-1.03L12 0l-1.71 2.97c-.19.3-.29.65-.29 1.03 0 1.1.9 2 2 2zm4.6 9.99l-1.07-1.07-1.08 1.07c-1.3 1.3-3.58 1.31-4.89 0l-1.07-1.07-1.09 1.07C6.75 16.64 5.88 17 4.96 17c-.73 0-1.4-.23-1.96-.61V21c0 .55.45 1 1 1h16c.55 0 1-.45 1-1v-4.61c-.56.38-1.23.61-1.96.61-.92 0-1.79-.36-2.44-1.01zM18 9h-5V7h-2v2H6c-1.66 0-3 1.34-3 3v1.54c0 1.08.88 1.96 1.96 1.96.52 0 1.02-.2 1.38-.57l2.14-2.13 2.13 2.13c.74.74 2.03.74 2.77 0l2.14-2.13 2.13 2.13c.37.37.86.57 1.38.57 1.08 0 1.96-.88 1.96-1.96V12C21 10.34 19.66 9 18 9z"/>'],
        'ecard' => ['label' => 'eCard',         'svg' => '<path fill-rule="evenodd" d="M3 4h18a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zm1 2.3V18h16V6.3l-8 5.4-8-5.4zM5.9 6 12 10.1 18.1 6H5.9z"/><path d="M17.5 11.6c-.9 0-1.5.6-1.5.6s-.6-.6-1.5-.6c-.9 0-1.6.7-1.6 1.6 0 1.6 3.1 3.6 3.1 3.6s3.1-2 3.1-3.6c0-.9-.7-1.6-1.6-1.6z"/>'],
        'gift'  => ['label' => 'Gift',          'svg' => '<path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 11 8.76l1-1.36 1 1.36L15.38 12 17 10.83 14.92 8H20v6z"/>'],
        'heart' => ['label' => 'Heart',         'svg' => '<path d="M12 21S3.5 15.6 3.5 9.6C3.5 6.5 5.9 4.6 8.4 4.6c1.7 0 3 .9 3.6 2 .6-1.1 1.9-2 3.6-2 2.5 0 4.9 1.9 4.9 5 0 6-8.5 11.4-8.5 11.4z"/>'],
    ];
}

/** The <svg> for one icon key, or '' for "No icon" / an unknown key. */
function sidebar_cta_icon_svg(string $key): string {
    $icon = sidebar_cta_icons()[$key] ?? null;
    if (!$icon || $icon['svg'] === '') {
        return '';
    }
    return '<svg class="ico cta-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $icon['svg'] . '</svg>';
}

/** Every button with its saved (or starting) values — what the admin form shows. */
function sidebar_ctas_all(): array {
    $all = [];
    foreach (sidebar_cta_defaults() as $n => $d) {
        $all[$n] = [
            'text' => (string) setting_get("cta{$n}_text", $d['text']),
            'url'  => (string) setting_get("cta{$n}_url", $d['url']),
            'icon' => (string) setting_get("cta{$n}_icon", $d['icon']),
            'on'   => setting_get("cta{$n}_on", $d['on']) === '1',
        ];
    }
    return $all;
}

/** Only the buttons to show: switched on, with text and a valid http(s) link.
 *  Read once per page — sidebar.php is included twice (right column + menu). */
function sidebar_ctas(): array {
    static $shown = null;
    if ($shown === null) {
        $shown = [];
        foreach (sidebar_ctas_all() as $n => $c) {
            $url = clean_http_url($c['url']);
            if ($c['on'] && trim($c['text']) !== '' && $url !== '') {
                $shown[$n] = ['text' => trim($c['text']), 'url' => $url, 'icon' => $c['icon']];
            }
        }
    }
    return $shown;
}
