<?php
/**
 * Theme for the SCVO Headless.
 *
 * @package  SCVO_Headless
 */

/**
 * Some constants
 */

define( "DEBUG_MODE", true );
define( "REAL_HOST", 'https://tfn.scot' );
define( "HOME_UPLOADS_PREFIX", 'http://cms.tfn.scot/wp-content/uploads/' );
define( "CLOUD_UPLOADS_PREFIX", 'https://storage.googleapis.com/scvo-content/' );
define( "INDEXER_PREFIX", 'https://internals.scvo.org/indexer' );
define( "POST_TYPE_BASE_MAP", [
  'opinion' => 'opinion/',
  'poll' => 'polls/',
  'news' => 'news/',
  'list' => 'lists/',
  'feature' => 'features/',
  'post' => 'posts/',
  'page' => '/',
  'magazine_issue' => 'magazine/'
] );

/**
 * Debug utility function checking for `debug` in querystrings
 */

function debug_log( $message ) {
  //if ( DEBUG_MODE || isset( $_GET['debug'] ) ) {
    error_log( $message );
  //}
}

/**
 * Require modules
 */

require_once get_parent_theme_file_path( '/includes/blocks.php' );
require_once get_parent_theme_file_path( '/includes/polls.php' );
require_once get_parent_theme_file_path( '/includes/comments.php' );

add_theme_support( 'post-thumbnails' );

/**
 * Fix invalid SSL issue on localhost
 * May need to wrap this in an `if` to only implement when on localhost
 */

add_filter( 'https_local_ssl_verify', '__return_false' );
add_filter( 'http_request_args', 'curlArgs', 10, 2 );

function curlArgs( $r, $url ) {
  $r['sslverify'] = false;
  return $r;
}

/**
 * Call indexer when there has been a change to some content
 */

function status_transition_update_index( $new_status, $old_status, $post ) {
  $validStatuses = [ "publish", "pending", "future", "private", "trash", "draft" ];
  $validPostTypes = [ "feature", "list", "news", "opinion", "poll", "post", "page", "advertisement", "magazine_issue" ];
  if ( in_array( $new_status, $validStatuses ) && isset( $post->post_content ) && in_array( $post->post_type, $validPostTypes ) ) {
    $post_id = $post->ID;
    $featured_image_id = get_post_thumbnail_id($post);
    if ($featured_image_id) {
      $featured_image_url = wp_get_attachment_url($featured_image_id);
      if ($featured_image_url) {
        update_metadata('post', $post_id, 'featured_image', $featured_image_url);
      }
    }

    debug_log( "#### RE-INDEXING POST '$post_id'" );
    $url = INDEXER_PREFIX . "/index-wordpress-post?site=cms.tfn.scot&id=$post_id";
    try {
      $response = file_get_contents( $url );
      debug_log( "RE-INDEXING POST '$post_id' WAS SUCCESSFUL" );
    } catch ( Exception $ex ) {
      debug_log( "######## FAILED TO RE-INDEX POST '$post_id' USING '$url'" );
    }
  }
}
add_action( 'transition_post_status',  'status_transition_update_index', 10, 3 );

/**
 * Call indexer when there has been a change to a user profile
 */

function user_profile_update( $author_id ) {
  debug_log( "#### RE-INDEXING AUTHOR '$author_id'" );
  $url = INDEXER_PREFIX . "/index-wordpress-author?site=cms.tfn.scot&id=$author_id";
  try {
    $response = file_get_contents( $url );
    debug_log( "RE-INDEXING AUTHOR '$author_id' WAS SUCCESSFUL" );
  } catch (Exception $ex) {
    debug_log( "######## FAILED TO RE-INDEX AUTHOR '$author_id' USING '$url'" );
  }

  debug_log( "#### RE-INDEXING POSTS BY AUTHOR '$author_id'" );
  $posts_url = INDEXER_PREFIX . "/index-wordpress-post?site=cms.tfn.scot&page_size=100&author=$author_id";
  try {
    $posts_response = file_get_contents( $posts_url );
    debug_log( "RE-INDEXING POSTS BY AUTHOR '$author_id' WAS SUCCESSFUL" );
  } catch (Exception $ex) {
    debug_log( "######## FAILED TO RE-INDEX POSTS BY AUTHOR '$author_id' USING '$posts_url'" );
  }
}
add_action( 'edit_user_profile_update', 'user_profile_update' );

/**
 * Call indexer when a comment has been modified
 */

function status_transition_update_comment_post_index( $new_status, $old_status, $comment ) {
  debug_log( "#### COMMENT STATUS CHANGED '$comment->comment_ID'" );
  $post = get_post( $comment->comment_post_ID );
  $post_status = $post->post_status;
  wp_transition_post_status( $post_status, $post_status, $post );
}
add_action( 'transition_comment_status', 'status_transition_update_comment_post_index', 10, 3 );

// function comment_post_update_post_index( $comment_id, $comment_approved ) {
//   error_log( "#### COMMENT POSTED '$comment_id', APPROVED: $comment_approved, COMMENT: " . print_r( $comment, true ) );
//   $comment = get_comment( $comment_id );
//   $post = get_post( $comment->comment_post_ID );
//   $post_status = $post->post_status;
//   wp_transition_post_status( $post_status, $post_status, $post );
// }
// add_action( 'comment_post', 'comment_post_update_post_index', 20, 2 );


/**
 * Allow comments from non logged in users throught he REST API
 */

add_filter( 'rest_allow_anonymous_comments', '__return_true' );

/** */

function fix_permalinks( $url, $post ) {
  if ( is_numeric( $post ) ) {
    $post = get_post( $post );
  }

  $postType = $post->post_type;
  $base = POST_TYPE_BASE_MAP[$postType] ?? "$postType/";
  $postName = $post->post_name;
  $outputUrl = REAL_HOST . "/${base}${postName}";

  return $outputUrl;
}
add_filter( "post_link", 'fix_permalinks', 10, 2 );
add_filter( "post_type_link", 'fix_permalinks', 10, 2 );

function fix_page_permalinks( $url, $post ) {
  $path = parse_url( $url, PHP_URL_PATH );
  $outputUrl = rtrim( REAL_HOST . $path, "/" );

  return $outputUrl;
}
add_filter( "page_link", 'fix_page_permalinks', 10, 2 );

//https://storage.googleapis.com/scvo-content/2020/08/Tux.png


// Replace src paths
add_filter( 'wp_get_attachment_url', function ( $url ) {
  if( file_exists( $url ) ) {
    debug_log("#### file_exists( $url )");
    return $url;
  }
  $transformed = str_replace( HOME_UPLOADS_PREFIX, CLOUD_UPLOADS_PREFIX, $url );
  return $transformed;
} );

//** */

add_filter( 'wp_generate_attachment_metadata', function( $metadata ) {
  sleep(5);
  return $metadata;
});

add_filter( 'image_make_intermediate_size', function( $file ) {
  $info = pathinfo( $file );
  preg_match( "/(\d+)(?:x\d+$)/", $info['filename'], $size );

  if ( !is_array( $size ) || count( $size ) !== 2 ) {
    return $file;
  }

  switch( $size[1] ) {
    case( 400 ):
      $named_size = '__small';
      break;
    case( 800 ):
      $named_size = '__medium';
      break;
    case( 1024 ):
      $named_size = '__large';
      break;
  }

  if ( empty( $named_size ) ) {
    return $file;
  }

  $new_name = preg_replace( "/-\d+x\d+$/", $named_size, $info['filename'] );
  $new_path = $info['dirname'] . '/' . $new_name . '.' . $info['extension'];

  $renamed = rename($file, $new_path);

  if ($renamed) {
    return $new_path;
  }

  return $file;
} );

require_once get_parent_theme_file_path( 'includes/headless/headless.php' );