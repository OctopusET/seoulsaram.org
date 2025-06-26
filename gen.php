<?php

$footnote_text = [];
$footnote_url = [];
$footnote_count = 0;

function footnote($text, $url)
{
    global $footnote_text, $footnote_url, $footnote_count;
    $footnote_count++;
    $footnote_text[$footnote_count] = $text;
    $footnote_url[$footnote_count] = $url;
    echo "<a name=\"back_{$footnote_count}\" style=\"text-decoration: none;\" href=\"#footnote_{$footnote_count}\"><sup>[{$footnote_count}]</sup></a>";
}

function footnotes_reset()
{
    global $footnote_count;
    $footnote_count = 0;
}

function footnote_gen_references()
{
    global $footnote_text, $footnote_url, $footnote_count;
    if ($footnote_count === 0) {
        return;
    }
    echo "<style type='text/css'>td.ref { padding-bottom: 0ch; width:0; }</style>";
    h("References");
    echo "<p id='paperbox' style='text-align:left;'><table><tbody style='vertical-align: top;'>";
    for ($i = 1; $i <= $footnote_count; $i++) {
        $target =
            $footnote_url[$i] === ""
                ? "{$footnote_text[$i]}."
                : "<a href=\"{$footnote_url[$i]}\">{$footnote_text[$i]}</a>";
        $index = $i < 10 && $footnote_count > 9 ? " {$i}" : $i;
        echo "<tr>" .
            "<td class='ref' style='width:1ch;'><a name=\"footnote_{$i}\"></a><a href=\"#back_{$i}\">^</a></td>" .
            "<td class='ref' style='width:4ch;'>[{$index}]</td>" .
            "<td style='width:100%;text-align:left;' class='ref'>{$target}</td>" .
            "</tr>";
    }
    echo "</tbody></table></p>";
}

function h($text)
{
    echo "<div class='heading'>{$text}</div><hr/>";
}

function getSvgImageSize($path)
{
    ($xml = simplexml_load_file($path)) or die("Cannot load {$path}");
    $width = $xml[0]["width"];
    $height = $xml[0]["height"];
    $viewBox = explode(" ", $xml[0]["viewBox"]);
    if (empty($width) && empty($height) && count($viewBox) === 4) {
        $width = $viewBox[2];
        $height = $viewBox[3];
    }
    return [$width, $height];
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    return $length === 0 || substr($haystack, -$length) === $needle;
}

function getDim($path)
{
    return endsWith($path, ".svg")
        ? getSvgImageSize($path)
        : getimagesize($path);
}

function img($path, $width, $style, $class = "")
{
    img_with_id($path, $width, $style, "", $class);
}

function img_with_id($path, $width, $style, $id = "", $class = "")
{
    list($w, $h) = getDim($path);
    $as_aspect = strpos($style, "aspect-ratio") !== false;
    echo '<img loading="lazy" src="',
        $path,
        '"' .
            (!empty($class) ? " class=\"{$class}\"" : "") .
            (!$as_aspect ? " width=\"{$w}\" height=\"{$h}\"" : "") .
            (!empty($id) ? " id=\"{$id}\"" : "") .
            ' style="width:',
        $width,
        "%;",
        !$as_aspect ? "height:auto;" : "",
        $style,
        '"/>';
}

function picture_with_id($path, $width, $style, $id = "", $class = "")
{
    echo '<picture><source srcset="',
        pathinfo($path, PATHINFO_FILENAME),
        '.webp" type="image/webp"/>';
    img_with_id($path, $width, $style, $id, $class);
    echo "</picture>";
}

function picture($path, $width, $style, $class = "")
{
    picture_with_id($path, $width, $style, "", $class);
}

function generate_rss()
{
    $rss_file = "rss.xml";
    $rss_script = "rss.php";
    if (!file_exists($rss_script)) {
        return;
    }
    ob_start();
    include $rss_script;
    $rss = ob_get_clean();
    file_put_contents($rss_file, $rss);
    echo "Generated {$rss_file}<br/>";
}

include "header.php";

function generate($src_dir)
{
    $dst = "{$src_dir}/index.html";
    $src = "{$src_dir}/src.php";
    $footer_date = filemtime("footer.php");
    $header_date = filemtime("header.php");

    if (file_exists($dst)) {
        $src_date = filemtime($src);
        $dst_date = filemtime($dst);
        if (
            $src_date < $dst_date &&
            $header_date < $dst_date &&
            $footer_date < $dst_date
        ) {
            return;
        }
    }

    $cwd = getcwd();
    footnotes_reset();
    ob_start();
    chdir($src_dir);
    include $src;
    include "footer.php";
    $contents = ob_get_clean();
    chdir($cwd);

    // === HTML minification ===
    $minified = preg_replace("/<!--(.|\s)*?-->/", "", $contents);
    $minified = preg_replace("/\s+/", " ", $minified);
    $minified = preg_replace("/>\s+</", "><", trim($minified));

    file_put_contents($dst, $minified);
    echo "Generated {$dst} (minified)<br/>";
}

generate(".");
generate("about");
/*
generate("articles/VDD24/howitbegan");
generate("articles/VDD24/Dday");
generate("articles/GSoC/");
generate("articles/GSoC/week1");
generate("articles/GSoC/week2");
generate("articles/GSoC/week3");
generate("articles/GSoC/week4");
 */
generate("articles/GSoC25/perf");

generate_rss();
?>
