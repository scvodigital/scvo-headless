<?php

function post_published_parse_blocks( $ID, $post ) {
  $parsed = parse_blocks( $post->post_content );
  $json = json_encode( $parsed );
  update_post_meta( $ID, 'blocks', wp_slash( $json ) );
}

add_action( 'publish_post', 'post_published_parse_blocks', 10, 2 );

function register_blocks_meta_box() {
  add_meta_box( 'blocks-meta-box', 'Blocks JSON', 'display_blocks_meta_box', 'post' );
}

add_action( 'add_meta_boxes', 'register_blocks_meta_box' );

function display_blocks_meta_box( $post ) {
  $postTypes = get_post_types( array(
    'public' => true,
    '_builtin' => true
  ), 'names', 'and' );

  echo '<pre>' . print_r($postTypes, true) . '</pre>';


  $json = get_post_meta( $post->ID, 'blocks', true );
  $decoded = json_decode($json);
  $unslashed = wp_unslash($decoded);
  $prettified = json_encode($unslashed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  $escaped = htmlentities($prettified);
  echo "<div style='text-align: right;'>";
  echo "<button type='button' class='components-button is-button is-primary' onclick='document.querySelector(\"#post-blocks-json\").select();document.execCommand(\"copy\");'>Copy</button>";
  echo "</div>";
  echo "<textarea id='post-blocks-json' style='height: 50vh; width: 100%; font-family: monospace; border: 1px solid #dadada; border-radius: 0;'>$escaped</textarea>";
}
