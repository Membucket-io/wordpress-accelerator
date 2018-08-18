<?php
  // Prevent direct script access
  if ( ! defined( 'MEMBUCKET' ) ) exit;
  
  function MB_install() {
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
    global $MB_version;
    add_option( 'mb_version', $MB_version );
    
    $contents = file_get_contents( realpath( __DIR__ ) . '/object-cache.php' );
    $path = realpath( get_home_path() ) . '/wp-content/object-cache.php';
    if ( file_exists( $path ) ) {
      $file = file_get_contents( $path );
      if ( $contents === $file ) {
        return;
      }
    }
    
    if ( false === file_put_contents( $path, $contents ) ) {
      die( "Installation Failed.  Could not write {$path}, and it was not up-to-date." );
    }
  }
  
  register_activation_hook( __FILE__, 'MB_install' );
  
  function MB_uninstall() {
    $contents = file_get_contents( realpath( __DIR__ ) . '/object-cache.php' );
    $path = realpath( get_home_path() ) . '/wp-content/object-cache.php';
    if ( ! file_exists( $path ) ) {
      return;
    }
    $file = file_get_contents( $path );
    if ( $contents === $file ) {
      unlink( $path );
    }
  }
  
  register_deactivation_hook( __FILE__, 'MB_uninstall' );
