<?php

function handle_request() {
  $response = [ 'status' => 'Nothing to see here' ];

  $path = parse_url( $_SERVER['SCRIPT_URL'], PHP_URL_PATH );

  try {
    switch( $path ) {
      case ('/posts'):
        header('content-type: application/json');
        require_once get_parent_theme_file_path( 'includes/headless/posts.php' );
        $response = handle_posts();
        break;
      case ('/authors'):
        header('content-type: application/json');
        require_once get_parent_theme_file_path( 'includes/headless/authors.php' );
        $response = handle_authors();
        break;
      case ('/'):
        header('content-type: application/json');
        require_once get_parent_theme_file_path( 'includes/headless/default.php' );
        $response = handle_default();
      default: return;
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