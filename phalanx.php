#!/usr/bin/env php
<?php
require_once dirname(__FILE__) . '/QueryPath/QueryPath.php';

if ($argc == 1) {
  print "
  Usage: $argv[0] URL
  
  This program takes a URL to an HTML page and outputs a list of all 
  of the resources that the destination URL uses.
  
";
  exit;
}

$url = filter_var($argv[1], FILTER_VALIDATE_URL);

if ($url === FALSE) {
  print "The provided URL was not valid. Please check it and try again.
  
";
  exit(1);
}

// Initialize the main array.
$links = array();

// We use this later:
$url_parts = parse_url($url);

// This is the most fault-tolerant way to parse icky HTML:
$doc = new DOMDocument('1.0');
@$doc->loadHTMLFile($url);
$qp = qp($doc, NULL, array('ignore_parser_warnings' => TRUE));

// All CSS links
foreach ($qp->top('link[href]') as $item) {
  $links[] = $item->attr('href');
}

// All scripts and images from <img>
foreach ($qp->top('script[src],img[src],embed[src]') as $item) {
  $links[] = $item->attr('src');
}

// Attempt to grab imports and url()s
foreach ($qp->top('style') as $item) {
  $txt = $item->text();
  
  $lines = explode("\n", $txt);
  $matches = array();
  $found = preg_match('/\@import "([\w.\/\-\_]+)" | url\(([\w.\/\-\_]+)\)/', $txt, $matches);
  if ($found) $links[] = empty($matches[1]) ? $matches[2] : $matches[1];
}

// Fix empty paths
if (!isset($url_parts['path'])) {
  $url_parts['path'] = '';
}

// Build reference URLs
$abs = $url_parts['scheme'] . '://' . $url_parts['host'];
$rel = $abs . $url_parts['path'] . '/';

//foreach ($links as $link) {
for ($i = 0; $i < count($links); ++$i) {
  
  $link = $links[$i];
  if (!filter_var($link, FILTER_VALIDATE_URL)) {
    // Generate FQDN links where necessary.
    $link =  strpos($link, '/') === 0 ? $abs . $link : $rel . $link;
  }
  else {
    // Skip URLs from other domains.
    $lp = parse_url($link);
    if ($lp['host'] !== $url_parts['host']) {
      continue;
    }
  }
  
  print $link . PHP_EOL;
}