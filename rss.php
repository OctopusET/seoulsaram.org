<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

$site_title       = "SEOULSARAM";
$site_url         = "https://seoulsaram.org";
$site_description = "Sungjoon Moon's Personal Blog";
$site_language    = "en-us";

// List of directories to exclude from RSS feed
$excluded_paths = [
    'about', 
    '.',    
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
echo '<channel>' . "\n";
echo "<title>{$site_title}</title>\n";
echo "<link>{$site_url}</link>\n";
echo "<description>{$site_description}</description>\n";
echo "<language>{$site_language}</language>\n";
echo '<atom:link href="' . $site_url . '/rss.xml" rel="self" type="application/rss+xml" />' . "\n";

function scanArticlesRecursive($base_dir, $site_url, $relative_path = 'articles', $excluded_paths = [])
{
    $articles      = [];
    $full_path     = "{$base_dir}/{$relative_path}";
    if (!is_dir($full_path)) {
        return $articles;
    }

    foreach (scandir($full_path) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $current_rel  = "{$relative_path}/{$entry}";
        $current_full = "{$base_dir}/{$current_rel}";
        $slug         = ($relative_path === 'articles')
                      ? $entry
                      : substr($current_rel, strlen('articles/') );
        if (in_array($slug, $excluded_paths, true)) {
            continue;
        }

        if (is_dir($current_full)) {
            $articles = array_merge(
                $articles,
                scanArticlesRecursive($base_dir, $site_url, $current_rel, $excluded_paths)
            );
        }
        elseif ($entry === 'src.php') {
            // Capture the PHP-rendered HTML so the <div> date actually appears
            ob_start();
            chdir(dirname($current_full));
            include 'src.php';
            $content = ob_get_clean();
            chdir($base_dir);

            // Extract title
            preg_match('/genheader\("([^"]+)"/', $content, $title_matches);
            $title = $title_matches[1] ?? basename(dirname($current_full));

            // Extract publish date: first try the header’s <div>, then fallback to genheader’s 2nd argument
            if (preg_match('/<div[^>]*margin-bottom:\s*2ch[^>]*>([^<]+)<\/div>/', $content, $date_matches)) {
                $date_str = trim($date_matches[1]);
            }
            elseif (preg_match('/genheader\(".*?",\s*"([^"]+)"\)/', $content, $date_matches)) {
                $date_str = trim($date_matches[1]);
            }
            else {
                http_response_code(500);
                die("Error: No publish date found in {$current_full}");
            }

            $timestamp = strtotime($date_str);
            if ($timestamp === false) {
                http_response_code(500);
                die("Error: Invalid date format in {$current_full}");
            }
            $pubDate = date('r', $timestamp);

            $article_url = "{$site_url}/" . dirname($current_rel) . '/';
            $articles[]  = [
                'title'     => $title,
                'link'      => $article_url,
                'date'      => $pubDate,
                'timestamp' => $timestamp,
            ];
        }
    }

    return $articles;
}

date_default_timezone_set('Asia/Seoul');

$articles = scanArticlesRecursive(__DIR__, $site_url, 'articles', $excluded_paths);

// Sort by newest first
usort($articles, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

foreach ($articles as $item) {
    echo "<item>\n";
    echo '<title>' . htmlspecialchars($item['title'], ENT_XML1) . "</title>\n";
    echo '<link>'  . htmlspecialchars($item['link'], ENT_XML1)  . "</link>\n";
    echo '<guid>'  . htmlspecialchars($item['link'], ENT_XML1)  . "</guid>\n";
    echo '<pubDate>'.$item['date']."</pubDate>\n";
    echo "</item>\n";
}

echo "</channel>\n";
echo "</rss>";
?>
