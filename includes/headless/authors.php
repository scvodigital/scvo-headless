<?php

function handle_authors() {
  global $wpdb;

  $params = get_params( $_GET );
  $query = get_query( $params );
  $authors = $wpdb->get_results( $query );
  $authors = process_authors( $authors );

  $response = [
    'results' => $authors
  ];

  if ( isset( $_GET['debug'] ) ) {
    $response['params'] = $params;
    $response['query'] = $query;
    $response['avatars_query'] = $avatarsQuery;
    $response['avatars'] = $avatars;
    $response['server'] = $_SERVER;
  }

  return $response;
}

function get_params( $input ) {
  $params = [
    'id' => isset( $input['id'] ) ? arrayify( $input['id'] ) ?? array() : array(),
    'slug' => isset( $input['slug'] ) ? arrayify( $input['slug'] ) ?? array() : array()
  ];
  return $params;
}

function get_query( $params ) {
  global $wpdb;

  $wheres = [];

  $field_map = [
    'slug' => 'u.user_login',
    'id' => 'u.ID'
  ];
  foreach ( $field_map as $param => $field ) {
    $sql_in = array_to_sql_in( $params[$param], $field );
    if ( !empty( $sql_in ) ) {
      array_push( $wheres, $sql_in );
    }
  }

  $where = "";
  if ( !empty( $wheres ) ) {
    $where = "WHERE \n    " . implode( $wheres, " AND\n    " );
  }

  $query = <<<EOD
SELECT
  u.ID AS author_id,
  u.user_login,
  u.user_email,
  u.user_url,
  u.display_name,
  u.user_registered,
  (
    SELECT
      CASE WHEN um.meta_key IS NULL THEN JSON_OBJECT() ELSE
        JSON_OBJECTAGG(
          um.meta_key,
          um.meta_value
        )
      END
    FROM {$wpdb->prefix}usermeta um
    WHERE
      um.user_id = u.ID AND
      um.meta_key IN ('description', 'avatar', 'twitter', 'facebook', 'linkedin', 'organisation', 'job_title')
  ) AS user_meta
FROM {$wpdb->prefix}users u
$where
EOD;

  return $query;
}

function process_authors( $authors ) {
  foreach ( $authors as $author ) {
    $author->user_meta = json_decode( $author->user_meta );
    $author->author_id = intval( $author->author_id );

    if ( !empty( $author->user_meta->avatar ) ) {
      $avatar_id = $author->user_meta->avatar;
      error_log( "#### get_avatars : AVATAR_ID: $avatar_id" );
      $avatar_url = wp_get_attachment_url( $author->user_meta->avatar );
      error_log( "#### get_avatars : AVATAR_URL: $avatar_url" );
      $author->user_meta->avatar = $avatar_url;
    }
  }

  return $authors;
}