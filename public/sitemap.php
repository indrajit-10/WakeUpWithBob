<?php
/**
 * XML sitemap  (  served at /sitemap.xml via .htaccess  )
 *
 * Built from the database on every request, so a post published this morning is
 * in it immediately — there is no file to regenerate and no upload step. Google
 * re-fetches the URL on its own schedule and always gets the current list.
 *
 * published_sql() is load-bearing: without it a SCHEDULED post would be handed
 * to Google the moment it is saved, leaking it before its morning. Every other
 * public query in this app is gated the same way.
 */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

$base = rtrim(defined('SITE_URL') ? (string) SITE_URL : '', '/');
if ($base === '') {                       // same fallback share_link() uses
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host  = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $base  = $host !== '' ? ($https ? 'https://' : 'http://') . $host : '';
}

$posts  = $pdo->query('SELECT id, created_at FROM questions WHERE ' . published_sql()
                      . ' ORDER BY created_at DESC')->fetchAll();
$newest = $posts ? $posts[0]['created_at'] : null;

/** One <url> entry. loc is XML-escaped so an & in any future query string can't break the feed. */
$url = static function (string $loc, ?string $lastmod, string $freq, string $pri): string {
    $x = '  <url><loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
    if ($lastmod !== null) { $x .= '<lastmod>' . date('Y-m-d', db_time($lastmod)) . '</lastmod>'; }
    return $x . '<changefreq>' . $freq . '</changefreq><priority>' . $pri . "</priority></url>\n";
};

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

echo $url($base . '/', $newest, 'daily', '1.0');
foreach ($posts as $p) {
    echo $url($base . '/question.php?id=' . (int) $p['id'], $p['created_at'], 'monthly', '0.8');
}
// contact / ask / feedback / comment are omitted on purpose: forms and POST
// handlers, with nothing to index.
foreach ([['/archive.php','0.6'],['/about.php','0.5'],['/faq.php','0.4'],
          ['/terms.php','0.2'],['/privacy.php','0.2']] as [$path, $pri]) {
    echo $url($base . $path, null, 'monthly', $pri);
}
echo "</urlset>\n";
