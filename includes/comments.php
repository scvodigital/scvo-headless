<?php

function post_comment_callback() {
  global $wpdb;

  header( 'content-type: application/json' );
  $response = [];

  try {
    $json = file_get_contents('php://input');
    $data = json_decode($json);
    $response['post_data'] = $data;

    if ( empty( $data->author_name ) ) throw new Exception( 'You must post an author_name', 400 );
    if ( empty( $data->author_email ) ) throw new Exception( 'You must post an author_email', 400 );
    if ( empty( $data->author_ip ) ) throw new Exception( 'You must post an author_ip', 400 );
    if ( empty( $data->author_user_agent ) ) throw new Exception( 'You must post an author_user_agent', 400 );
    if ( empty( $data->content ) ) throw new Exception( 'You must post an content', 400 );
    if ( empty( $data->post ) ) throw new Exception( 'You must post an post', 400 );

    $result = wp_insert_comment([
      'comment_agent' => $data->author_user_agent,
      'comment_approved' => $data->approved,
      'comment_author' => $data->author_name,
      'comment_author_email' => $data->author_email,
      'comment_author_IP' => $data->author_ip,
      'comment_content' => $data->content,
      'comment_post_ID' => $data->post
    ]);

    $response['message'] = 'Comment placed successfully';
    $response['comment'] = $result;
  } catch (Exception $ex) {
    $response['error'] = $ex->getMessage();
    $response_code = $ex->getCode() > 0 ? $ex->getCode() : 500;
    http_response_code( $response_code );
  }

  if ( isset( $_GET['debug'] ) ) {
    $response['server'] = $_SERVER;
    $response['post'] = $_REQUEST;
  }

  echo json_encode($response);
  exit();
}

add_action( 'wp_ajax_nopriv_post_comment', 'post_comment_callback' );