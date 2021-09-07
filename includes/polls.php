<?php

function setup_poll_database() {
  global $wpdb;

  $charset_collate = $wpdb->get_charset_collate();
  $create_table_sql = "
    CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tfn_poll_votes (
      poll_id INT,
      fingerprint VARCHAR(255),
      ip_address VARCHAR(64),
      vote VARCHAR(255) NOT NULL,
      vote_date DATETIME NOT NULL,
      vote_weight INT NOT NULL,
      PRIMARY KEY (poll_id, fingerprint, ip_address)
    ) $charset_collate;";

  $wpdb->query( $create_table_sql );
}

add_action( 'admin_init', 'setup_poll_database' );

function place_poll_vote_callback() {
  global $wpdb;

  header( 'content-type: application/json' );
  $response = [];

  try {
    $json = file_get_contents('php://input');
    $data = json_decode($json);
    $response['post_data'] = $data;

    if ( empty( $data->poll_id ) ) throw new Exception( 'You must post a poll_id', 400 );
    if ( empty( $data->fingerprint ) ) throw new Exception( 'You must post a fingerprint', 400 );
    if ( empty( $data->ip_address ) ) throw new Exception( 'You must post a IP address', 400 );
    if ( empty( $data->vote ) ) throw new Exception( 'You must post a vote', 400 );

    $vote_date = date('Y-m-d H:i:s');

    $poll = get_post( $data->poll_id );

    if ( empty( $poll ) || $poll->post_type !== 'poll' ) throw new Exception( "Invalid poll_id '$data->poll_id'", 400 );

    $options_string = get_metadata( 'post', $data->poll_id, 'options', true ) ?? '';
    $options = explode( "\n", $options_string );
    $options = array_map( function( $option ) {
      return trim( $option );
    }, $options );

    if ( isset( $_GET['debug'] ) ) {
      $response['valid_options'] = $options;
    }

    if ( !in_array( $data->vote, $options ) ) throw new Exception( "Invalid vote '$data->vote'", 400 );

    $vote_sql = $wpdb->prepare( "
      INSERT INTO {$wpdb->prefix}tfn_poll_votes (poll_id, fingerprint, ip_address, vote, vote_date)
      VALUES (%d, %s, %s, %s, %s)
      ON DUPLICATE KEY UPDATE vote = %s
    ", $data->poll_id, $data->fingerprint, $data->ip_address, $data->vote, $vote_date, $data->vote );

    if ( isset( $_GET['debug'] ) ) {
      $response['insert_sql'] = $vote_sql;
    }

    $wpdb->query( $vote_sql );
    $error = $wpdb->last_error;

    if ( !empty( $error ) ) throw new Exception( "Could not place vote: $error ", 500 );

    $votes = get_vote_count( $data->poll_id );

    foreach ( $options as $option ) {
      $found = array_search_key_value( $votes, 'option', $option );
      if ( $found === false ) {
        array_push( $votes, [ 'option' => $option, 'votes' => 0 ] );
      } else {
        $votes[$found]['votes'] = intval($votes[$found]['votes']);
      }
    }

    update_metadata( 'post', $data->poll_id, 'votes', json_encode($votes) );

    $response['message'] = 'Vote placed successfully';
    $response['votes'] = $votes;

    $pollStatus = $poll->post_status;
    wp_transition_post_status( $pollStatus, $pollStatus, $poll );

  } catch (Exception $ex) {
    $response['error'] = $ex->getMessage();
    // $response_code = $ex->getCode() > 0 ? $ex->getCode() : 500;
    // http_response_code( $response_code );
  }

  if ( isset( $_GET['debug'] ) ) {
    $response['server'] = $_SERVER;
    $response['post'] = $_POST;
  }

  echo json_encode($response);
  exit();
}

add_action( 'wp_ajax_nopriv_place_poll_vote', 'place_poll_vote_callback' );

function get_vote_count( $poll_id ) {
  global $wpdb;

  //TODO: Need to stuff this with options that have no votes

  $sql = $wpdb->prepare( "
    SELECT
      vote AS `option`,
      SUM(vote_weight) AS votes
    FROM {$wpdb->prefix}tfn_poll_votes
    WHERE poll_id = %d
    GROUP BY vote
  ", $poll_id );

  $results = $wpdb->get_results( $sql, ARRAY_A );

  if ( !empty( $wpdb->last_error ) ) throw new Exception( $wpdb->last_error, 500 );

  return $results;
}

function array_search_key_value( $array, $key, $value ) {
  $index = 0;
  return array_reduce( $array, function( $carry, $item ) use ($key, $value, &$index) {
    if ( $carry !== false ) return $carry;
    if ( is_array($item) && $item[$key] === $value ) return $index;
    if ( is_object($item) && $item->$key === $value ) return $index;
    $index += 1;
    return false;
  }, false );
}