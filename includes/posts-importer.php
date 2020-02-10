<?php

function tfn_import_posts() {
  header( 'content-type: application/json' );

  try {
    $posts = json_decode(file_get_contents('php://input'), true);

    if (!is_array($posts)) {
      throw new Exception( 'Was expecting an array of posts' );
    }

    $results = array();

    foreach( $posts as $post ) {
      $result = tfn_import_post( $post );
      array_push($results, $result);
    }

    echo json_encode($results);
  } catch( Exception $ex ) {
    echo '{ "error": ' . json_encode($ex->getMessage()) . '" }';
  }

  wp_die();
}

add_action( 'wp_ajax_nopriv_import-tfn-posts', 'tfn_import_posts' );

function tfn_import_post( $post ) {
  try {
    tfn_is_post_valid( $post );

    return $post['title'];
  } catch (Exception $ex) {
    return [ 'error' => $ex->getMessage() ];
  }
}

function tfn_is_post_valid( $post ) {
  if (!is_string($post["url"])) { throw new Exception("Post url invalid"); }
  if (!is_string($post["title"])) { throw new Exception("Post title invalid"); }
  if (!is_string($post["copy"])) { throw new Exception("Post copy invalid"); }
  if (!is_string($post["postType"])) { throw new Exception("Post postType invalid"); }
  if (!is_string($post["category"])) { throw new Exception("Post category invalid"); }
  if (!is_string($post["slug"])) { throw new Exception("Post slug invalid"); }
  if (!is_string($post["author"])) { throw new Exception("Post author invalid"); }
  if (!is_string($post["date"])) { throw new Exception("Post date invalid"); }
  if (!is_string($post["ogDescription"])) { throw new Exception("Post ogDescription invalid"); }
  if (!is_array($post["blocks"])) { throw new Exception("Post blocks invalid"); }
}