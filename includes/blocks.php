<?php

function load_custom_blocks() {
  $blocksDir = get_parent_theme_file_path( '/includes/blocks/*.jsx' );
  $blockFiles = glob( $blocksDir );
  foreach ( $blockFiles as $blockFile ) {
    $scriptName = basename( $blockFile, '.jsx' );
    $scriptUrl = get_theme_file_uri( "includes/blocks/$scriptName.jsx" );
    wp_enqueue_script( $scriptName . '-block', $scriptUrl, array( 'wp-blocks', 'wp-editor' ), true );
  }
}

add_action( 'enqueue_block_editor_assets', 'load_custom_blocks' );

function gutenberg_register_styles() {
  wp_register_style(
      'gutenberg-editor-styles',
      get_template_directory_uri() . '/editor.css',
      array( 'wp-edit-blocks' ),
      filemtime( plugin_dir_path( __FILE__ ) . '../editor.css' )
  );

  register_block_type( 'gutenberg-examples/gutenberg-editor-styles', array(
      'editor_style' => 'gutenberg-editor-styles'
  ) );
}
add_action( 'init', 'gutenberg_register_styles' );