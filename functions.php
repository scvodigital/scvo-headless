<?php
/**
 * Theme for the SCVO Headless.
 *
 * @package  SCVO_Headless
 */

/**
 * Require modules
 */

require_once get_parent_theme_file_path( '/blocks/index.php' );

/**
 * Fix invalid SSL issue on localhost
 * May need to wrap this in an `if` to only implement when on localhost
 */

add_filter( 'https_local_ssl_verify', '__return_false' );
add_filter( 'http_request_args', 'curlArgs', 10, 2 );

function curlArgs($r, $url) {
  $r['sslverify'] = false;
  return $r;
}

/**
 * Ajax Hook to create API to convert Gutenberg block JSON to HTML
 */

function gutenberg_to_html_requested() {
  header('content-type: application/json');
  echo json_encode($_SERVER);
  wp_die();
}

add_action( 'wp_ajax_gutenberg-to-html', 'gutenberg_to_html_requested' );

/*
function post_published_webhook( $ID, $post ) {
  $metadata = get_post_meta($ID);
  $headers = [
    'content-type' => 'application/json',
    'test' => 'this is a test'
  ];
  $body = [
    'site' => 'tfn',
    'id' => $ID,
    'title' => $post->post_title,
    'author' => $post->post_author,
    'content' => $post->post_content,
    'metadata' => $metadata
  ];
  $request = new WP_Http();
  $request->request('https://wp-indexer.scvo.local:8080/index', [
    'method' => 'POST',
    'headers' => $headers,
    'body' => json_encode($body)
  ]);

  $path = '/home/tonicblue/code/scvo/sites/sites/wp-indexer/configuration/templates/last-published-post.json';
  file_put_contents($path, json_encode($body));
}

add_action( 'publish_post', 'post_published_webhook', 100, 2 );
*/