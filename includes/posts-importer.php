<?php

function import_tfn_posts() {
  header( 'content-type: application/json' );

  try {
    $posts = json_decode(file_get_contents('php://input'), true);

    echo json_encode($data);
  } catch( Exception $ex ) {
    echo '{ "error": ' . json_encode($ex->getMessage()) . '" }';
  }

  wp_die();
}

add_action( 'wp_ajax_nopriv_import-tfn-posts', 'import_tfn_posts' );