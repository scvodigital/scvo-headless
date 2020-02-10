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
      $result = import_tfn_post( $post );
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
  tfn_is_post_valid( $post );

  return $post['title'];
}

function tfn_is_post_valid( $post ) {
  $postSchema = [
    "url" => is_string,
    "title" => is_string,
    "copy" => is_string,
    "postType" => is_string,
    "category" => is_string,
    "slug" => is_string,
    "author" => is_string,
    "date" => is_string,
    "ogDescription" => is_string,
    "blocks" => is_array
  ];
  $issues = array();

  foreach( $postSchema as $field => $method) {
    if (!$method($post[$field])) {
      array_push($issues, $field);
    }
  }

  if (count($issues) > 0) {
    $issuesSummary = implode(", ", $issues);
    throw new Exception("There were issues with the following fields: $issuesSummary");
  }
}