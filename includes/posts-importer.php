<?php

function import_tfn_posts() {
  header( 'content-type: application/json' );

  try {
    if (empty($_POST['posts'])) {
      throw new Exception( 'No posts provided' );
    }

    echo $_POST['posts'];
  } catch( Exception $ex ) {
    echo json_encode($ex->getMessage());
  }

  echo 'test';

  wp_die();
}

add_action( 'wp_ajax_nopriv_import-tfn-posts', 'import_tfn_posts' );