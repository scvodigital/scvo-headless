<?php

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
