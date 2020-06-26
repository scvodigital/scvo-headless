<?php

function handle_request() {
  if ( is_admin() || $_SERVER['SCRIPT_URL'] === '/wp-login.php' || strpos( $_SERVER['SCRIPT_URL'], 'wp-json' ) !== false ) return;
  header('content-type: application/json');

  $response = [ 'status' => 'Nothing to see here' ];

  try {
    //TODO: Load these from WordPress options
    $post_meta_fields = [
      'premium' => 'boolval',
      // 'blocks' => 'json_decode', //Removed as we are moving away from parsing blocks
      'start_date' => null,
      'end_date' => null,
      'options' => null,
      'votes' => 'json_decode'
    ];
    $author_meta_fields = [
      'description' => null
    ];

    $params = get_params( $_GET );
    $query = get_query( $params, $post_meta_fields, $author_meta_fields );
    $count_query = get_count_query( $params );
    $results = get_results( $query, $post_meta_fields, $author_meta_fields );
    $count = get_count( $count_query );
    $pagination = get_pagination( $params['page'], $params['page_size'], $count, $_SERVER['REDIRECT_SCRIPT_URI'], $_GET );
    $response = [
      'pagination' => $pagination,
      'results' => $results
    ];

    if ( isset( $_GET['debug'] ) ) {
      $response['params'] = $params;
      $response['query'] = $query;
      $response['count_query'] = $count_query;
      $response['server'] = $_SERVER;
    }

  } catch(Exception $ex) {
    http_response_code( 500 );
    $response = [ 'error' => $ex->getMessage() ];
  }

  $json = json_encode( $response );
  echo $json;
  exit();
}
add_action( 'init', 'handle_request' );

function get_params( $input ) {
  $params = [
    'page' => isset( $input['page'] ) ? intval( $input['page'] ) ?? 1 : 1,
    'page_size' => isset( $input['page_size'] ) ? intval( $input['page_size'] ) ?? 10 : 10,
    'status' => isset( $input['status'] ) ? arrayify( $input['status'] ) ?? array( 'publish' ) : array( 'publish' ),
    'id' => isset( $input['id'] ) ? arrayify( $input['id'] ) ?? array() : array(),
    'author' => isset( $input['author'] ) ? arrayify( $input['author'] ) ?? array() : array(),
    'post_type' => isset( $input['post_type'] ) ? arrayify( $input['post_type'] ) ?? array() : array(),
    'terms' => [] //Not implementing until there is a need
  ];

  unset( $params['debug'] );

  foreach ( $params as $key => $value ) {
    if ( isset( $input[$key] ) ) {
      unset( $input[$key] );
    }
  }

  if ( isset( $input['p'] ) ) {
    array_push( $params['id'], $input['p'] );
    unset( $input['p'] );
  }

  $url = $_SERVER['SCRIPT_URL'];
  $post_id = url_to_postid( $url );

  if ( $post_id > 0 ) {
    array_push( $params['id'], $post_id );
  }

  foreach ( $input as $key => $value ) {
    $params['terms'][$key] = arrayify( $value );
  }

  return $params;
}

function get_query( $params, $post_meta_fields, $author_meta_fields ) {
  global $wpdb;

  $wheres = [];

  $post_meta_fields_in = array_to_sql_in( array_keys( $post_meta_fields ), 'pm.meta_key' );
  $author_meta_fields_in = array_to_sql_in( array_keys( $author_meta_fields ), 'um.meta_key' );

  $field_map = [
    'status' => 'p.post_status',
    'id' => 'p.ID',
    'author' => 'u.ID',
    'post_type' => 'p.post_type'
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

  $limit = intval($params['page_size']);
  $offset = intval(($params['page'] - 1) * $limit);
  $query = <<<EOD
SELECT
  p.ID AS post_id,
  p.post_type AS post_type,
  p.post_name AS slug,
  p.post_title AS title,
  p.post_content AS content,
  p.post_excerpt AS excerpt,
  p.post_parent AS parent_id,
  p.post_status AS status,
  p.comment_status,
  UNIX_TIMESTAMP(p.post_date_gmt) * 1000 AS date,
  UNIX_TIMESTAMP(p.post_modified_gmt) * 1000 AS modified,
  (
    SELECT
      JSON_MERGE_PRESERVE(
        JSON_OBJECT(
          'id', u.ID,
          'author_slug', u.user_login,
          'author_name', u.display_name
        ),
        CASE WHEN um.meta_key IS NULL THEN JSON_OBJECT() ELSE
          JSON_OBJECTAGG(
            IFNULL(um.meta_key, 'null'),
            um.meta_value
          )
        END
      )
    FROM {$wpdb->prefix}users u
      LEFT JOIN {$wpdb->prefix}usermeta um ON um.user_id = u.ID AND $author_meta_fields_in
    WHERE u.ID = p.post_author
  ) AS author,
  (
    SELECT
      CASE WHEN c.comment_ID IS NULL THEN JSON_ARRAY() ELSE
        JSON_ARRAYAGG(
          JSON_OBJECT(
            'id', c.comment_ID,
            'author', c.comment_author,
            'email', c.comment_author_email,
            'date', UNIX_TIMESTAMP(c.comment_date_gmt) * 1000,
            'content', c.comment_content
          )
        )
      END
    FROM {$wpdb->prefix}comments c
    WHERE c.comment_post_ID = p.ID AND c.comment_approved = 1
  ) AS comments,
  (
    SELECT
      CASE WHEN tr.term_taxonomy_id IS NULL THEN JSON_ARRAY() ELSE
        JSON_ARRAYAGG(
          JSON_OBJECT(
            'taxonomy', tt.taxonomy,
            'id', tt.term_taxonomy_id,
            'term_name', t.name,
            'term_slug', t.slug,
            'term_id', t.term_id
          )
        )
      END
    FROM {$wpdb->prefix}term_relationships tr
      JOIN {$wpdb->prefix}term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
      JOIN {$wpdb->prefix}terms t ON t.term_id = tt.term_id
    WHERE tr.object_id = p.ID
  ) AS taxonomy,
  (
    SELECT
      CASE WHEN pm.meta_key IS NULL THEN JSON_OBJECT() ELSE
        JSON_OBJECTAGG(
          pm.meta_key,
          pm.meta_value
        )
      END
    FROM {$wpdb->prefix}postmeta pm
    WHERE
      pm.post_id = p.ID AND
      $post_meta_fields_in
  ) AS post_meta
FROM {$wpdb->prefix}posts p
$where
LIMIT $limit OFFSET $offset
EOD;

  return $query;
}

function get_count_query( $params ) {
  global $wpdb;

  $wheres = [];

  $field_map = [
    'status' => 'post_status',
    'id' => 'ID',
    'author' => 'post_author',
    'post_type' => 'post_type'
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
SELECT COUNT(ID) AS total
FROM {$wpdb->prefix}posts p
$where
EOD;

  return $query;
}

function get_results( $sql, $post_meta_fields, $author_meta_fields ) {
  global $wpdb;

  $results = $wpdb->get_results( $sql );

  foreach ( $results as $result ) {
    $result->post_id = intval( $result->post_id );
    $result->parent_id = intval( $result->parent_id );
    $result->date = intval( $result->date );
    $result->modified = intval( $result->modified );
    $result->comments = json_decode( $result->comments );
    $result->post_meta = json_decode( $result->post_meta );
    $result->author = json_decode( $result->author );
    $result->taxonomy = json_decode( $result->taxonomy );
    $result->permalink = get_permalink( $result->post_id );

    foreach ( $post_meta_fields as $field => $method ) {
      if ( $method == null || empty( $result->post_meta->$field ) ) continue;
      try {
        $result->post_meta->$field = $method( $result->post_meta->$field );
      } catch (Exception $ex) { }
    }

    foreach ( $author_meta_fields as $field => $method ) {
      if ( $method == null || empty( $result->author_meta->$field ) ) continue;
      try {
        $result->author_meta->$field = $method( $result->author_meta->$field );
      } catch (Exception $ex) { }
    }
  }

  return $results;
}

function get_count( $sql ) {
  global $wpdb;

  $results = $wpdb->get_results( $sql );

  return intval($results[0]->total);
}

function get_pagination( $current_page, $page_size, $total_results, $base_url, $params ) {
  $total_pages = ceil( $total_results / $page_size );

  $pagination = [
    'current_page' => $current_page,
    'page_size' => $page_size,
    'total_results' => $total_results,
    'total_pages' => $total_pages,
  ];

  if ( $current_page < $total_pages ) {
    $params['page'] = $current_page + 1;
    $new_query = http_build_query( $params );
    $pagination['next_page'] = "$base_url?$new_query";
  }

  if ( $current_page > 1 ) {
    $params['page'] = $current_page - 1;
    $new_query = http_build_query( $params );
    $pagination['prev_page'] = "$base_url?$new_query";
  }

  return $pagination;
}

function arrayify( $val ) {
  if ( empty( $val ) ) return null;
  if ( is_array( $val ) ) return $val;
  return array( $val );
}

function array_to_sql_in( $arr, $field ) {
  if ( !is_array( $arr ) ) return null;
  array_filter( $arr );
  if ( empty( $arr ) ) return null;

  array_walk( $arr, function( &$value, $key ) {
    $value = "'" . esc_sql( $value ) . "'";
  } );
  $values = implode( $arr, ', ' );

  return "$field IN ($values)";
}