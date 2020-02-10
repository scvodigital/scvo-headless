<?php

function import_tfn_posts() {
  header( 'content-type: application/json' );

  try {
    $posts = json_decode(file_get_contents('php://input'), true);

    if (!is_array($posts)) {
      throw new Exception( 'Was expecting an array of posts' );
    }

    $results = array();

    foreach( $posts as $post ) {
      $result = import_tfn_post( $post );
      array_push($results, $result);
    }

    echo json_encode($results);
  } catch( Exception $ex ) {
    echo '{ "error": ' . json_encode($ex->getMessage()) . '" }';
  }

  wp_die();
}

add_action( 'wp_ajax_nopriv_import-tfn-posts', 'import_tfn_posts' );

function import_tfn_post( $post ) {
  return $post->title;
}