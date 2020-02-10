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
  return $post['title'];
}

function tfn_is_post_valid( $post ) {
  $issues = array();

  if (empty($post['title'])) {

  }
}


// "url": "/tfn-news/charities-unite-against-daily-mail-attack",
// "title": "Charities unite against Daily Mail attack",
// "copy": "Daily Mail accused of irresponsible journalism after instigating a campaign against Scotland's third sector",
// "premium": null,
// "postType": "tfn-news",
// "category": "management",
// "slug": "charities-unite-against-daily-mail-attack",
// "author": "Robert Armour",
// "date": "17th January 2017",