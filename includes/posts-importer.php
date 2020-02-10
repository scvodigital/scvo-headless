<?php

function register_tfn_importer_page() {
  add_menu_page( 'TFN Importer', 'TFN Importer', 'publish_posts', 'tfn-importer', 'tfn_importer_page' );
}

add_action( 'admin_init', 'register_tfn_importer_page' );

function tfn_importer_page() {
  echo 'Dope';
}