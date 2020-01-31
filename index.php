<?php
/**
 * Redirect frontend requests to REST API.
 *
 * @package  SCVO_Headless
 */

if ( !empty( $_GET['update-theme'] ) ) {
  echo '<html><body><pre>';
  try {
    echo "Pulling latest theme\n\n";
    echo shell_exec( `git --git-dir=wp-content/themes/scvo-headless/.git pull 2>&1` );
    echo "\nDone\n";
  } catch (Exception $ex) {
    print_r($ex);
  }
  echo '</pre></body></html>';
  exit();
}


//echo "<html><body><pre>";


// Redirect individual posts to the REST API endpoint.
if (is_singular()) {
  header(
    sprintf(
      'Location: /wp-json/wp/v2/%s/%s',
      get_post_type_object(get_post_type())->rest_base,
      get_post()->ID
    )
  );
} else {
  header('Location: /wp-json/');
}


//echo "</pre></body></html>";