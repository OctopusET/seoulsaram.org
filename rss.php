<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

$site_title = "SEOULSARAM";
$site_url = "https://seoulsaram.org";
$site_description = "Sungjoon Moon's Personal Blog";
$site_language = "en-us";

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
echo '<channel>' . "\n";
echo '<title>' . $site_title . '</title>' . "\n";
echo '<link>' . $site_url . '</link>' . "\n";
echo '<description>' . $site_description . '</description>' . "\n";
echo '<language>' . $site_language . '</language>' . "\n";
echo '<atom:link href="' . $site_url . '/rss.xml" rel="self" type="application/rss+xml" />' . "\n";

function scanArticlesRecursive($base_dir, $site_url, $relative_path = 'articles') {
    $articles = array();
    $full_path = $base_dir . '/' . $relative_path;
    if (!is_dir($full_path)) {
        return $articles;
    }
    $dirs = scandir($full_path);
    foreach ($dirs as $dir) {
        if ($dir == '.' || $dir == '..') {
            continue;
        }
        $current_path = $relative_path . '/' . $dir;
        $current_full_path = $base_dir . '/' . $current_path;
        if (is_dir($current_full_path)) {
            $articles = array_merge($articles, scanArticlesRecursive($base_dir, $site_url, $current_path));
        } elseif ($dir === 'src.php') {
            $content = file_get_contents($current_full_path);

            // Extract title
            preg_match('/genheader\("([^"]+)"/', $content, $title_matches);
            $title = isset($title_matches[1]) ? $title_matches[1] : basename(dirname($current_full_path));

            // Extract publish date from div
            if (preg_match('/<div[^>]*margin-bottom:\s*2ch[^>]*>([^<]+)<\/div>/', $content, $date_matches)) {
                $date_str = trim($date_matches[1]);
                $timestamp = strtotime($date_str);
                if ($timestamp === false) {
                    http_response_code(500);
                    die("Error: Invalid date format in $current_full_path");
                }
                $pubDate = date('r', $timestamp);
            } else {
                http_response_code(500);
                die("Error: No publish date found in $current_full_path");
            }

            $article_url = $site_url . '/' . dirname($current_path) . '/';

            $article = array(
                'title' => $title,
                'link' => $article_url,
                'date' => $pubDate,
                'timestamp' => $timestamp
            );
            $articles[] = $article;
        }
    }
    return $articles;
}

date_default_timezone_set('Asia/Seoul');

$articles = scanArticlesRecursive(__DIR__, $site_url);

usort($articles, function($a, $b) {
    return $b['timestamp'] - $a['timestamp'];
});

foreach ($articles as $article) {
    echo '<item>' . "\n";
    echo '<title>' . htmlspecialchars($article['title']) . '</title>' . "\n";
    echo '<link>' . $article['link'] . '</link>' . "\n";
    echo '<guid>' . $article['link'] . '</guid>' . "\n";
    echo '<pubDate>' . $article['date'] . '</pubDate>' . "\n";
    echo '</item>' . "\n";
}

echo '</channel>' . "\n";
echo '</rss>';
?>
