<?php

/**
 * Action to automatically get Gutenberg JSON on save of any Gutenberg content
 */

function status_transition_update_blocks_json( $new_status, $old_status, $post ) {
  $validStatuses = [ "publish", "pending", "future", "private", "trash" ];
  if ( in_array( $new_status, $validStatuses ) && isset( $post->post_content ) ) {
    $parsed = parse_blocks( $post->post_content );
    $json = json_encode( $parsed );
    $ID = $post->ID;
    update_post_meta( $ID, 'blocks', wp_slash( $json ) );
  }
}
add_action( 'transition_post_status',  'status_transition_update_blocks_json', 10, 3 );

/**
 * Add a meta box to pages that have Gutenberg content to display their JSON
 */

function register_blocks_meta_box() {
  $args = array(
    'public' => true,
    '_builtin' => true
  );

  $postTypes = get_post_types( $args, 'names', 'and' );
  $screenIds = array();

  foreach ( $postTypes as $key => $value ) {
    array_push($screenIds, $key, "edit-$key");
  }

  add_meta_box( 'blocks-meta-box', 'Blocks JSON', 'display_blocks_meta_box', $screenIds );
}
add_action( 'add_meta_boxes', 'register_blocks_meta_box' );

function display_blocks_meta_box( $post ) {
  $id = $post->ID;
  $json = get_post_meta( $id, 'blocks', true );

  $decoded = json_decode($json);
  $unslashed = wp_unslash($decoded);
  $prettified = json_encode($unslashed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  $escaped = htmlentities($prettified);

  echo 'Wut';
}

/**
 * Ajax action to get a post's Gutenberg JSON
 */

function get_post_gutenberg_json() {
  header( 'content-type: application/json' );

  try {
    if (empty($_GET['id'])) {
      throw new Exception( 'No content Id provided' );
    }

    $json = get_post_meta( $_GET['id'], 'blocks', true );

    $decoded = json_decode($json);
    $unslashed = wp_unslash($decoded);
    $prettified = json_encode($unslashed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    echo $prettified;
  } catch( Exception $ex) {
    echo json_encode($ex);
  }

  wp_die();
}

add_action( 'wp_ajax_get-post-gutenberg-json', 'get_post_gutenberg_json' );