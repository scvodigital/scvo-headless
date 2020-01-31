<?php
/**
 * Redirect frontend requests to REST API.
 *
 * @package  SCVO_Headless
 */

if ( !empty( $_GET['update-theme'] ) ) {
  try {
    echo 'Pulling latest theme';
    $pullOutput = shell_exec( `cd /var/www/cms/wp-content/themes/scvo-headless && git reset -hard HEAD && git pull` );
    echo $pullOurput;
    echo 'Done';
  } catch (Exception $ex) {
    print_r($ex);
  }
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