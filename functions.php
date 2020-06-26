<?php
/**
 * Theme for the SCVO Headless.
 *
 * @package  SCVO_Headless
 */

/**
 * Require modules
 */

require_once get_parent_theme_file_path( '/includes/blocks.php' );
require_once get_parent_theme_file_path( '/includes/polls.php' );
require_once get_parent_theme_file_path( '/includes/comments.php' );


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
 * Allow comments from non logged in users throught he REST API
 */

add_filter( 'rest_allow_anonymous_comments', '__return_true' );

/** */

function update_theme() {
  if ( $_SERVER['REQUEST_URI'] !== '/update-theme' ) return;

  echo '<html><body><pre>';
  try {
    echo "Pulling latest theme\n\n";
    echo shell_exec( "cd wp-content/themes/scvo-headless/ && git reset --hard HEAD 2>&1 && git pull 2>&1" ) . "\n";
    echo "Done\n";
  } catch (Exception $ex) {
    echo $ex->getMessage();
  }
  echo '</pre></body></html>';
  exit();
}
add_action( 'init', 'update_theme' );

/** */

function clear_uploads() {
  if ( $_SERVER['REQUEST_URI'] !== '/clear-uploads' ) return;

  echo '<html><body><pre>';
  try {
    echo 'Clearing uploads\n\n';
    echo shell_exec( "rm -rf /var/www/cms/wp-content/uploads 2>&1 && mkdir /var/www/cms/wp-content/uploads 2>&1" ) . "\n";
    echo "Done\n";
  } catch (Exception $ex) {
    echo $ex->getMessage();
  }
  echo '</pre></body></html>';
  exit();
}
add_action( 'init', 'clear_uploads' );

require_once get_parent_theme_file_path( 'headless.php' );