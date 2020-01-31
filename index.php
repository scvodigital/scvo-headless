<?php
/**
 * Redirect frontend requests to REST API.
 *
 * @package  SCVO_Headless
 */

if ( !empty( $_GET['update-theme'] ) ) {
  echo '<html><body><pre>';
  try {
    echo 'Pulling latest theme\r\n';
    echo shell_exec( `ls wp-content/themes/scvo-headless` );
    echo shell_exec( `git --git-dir=wp-content/themes/scvo-headless/.git pull` );
    echo 'Done\r\n';
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