<?php
  // Prevent direct script access
  if ( ! defined( 'MEMBUCKET' ) ) exit;
  
  function MB_install() {
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
    global $MB_version;
    add_option( 'mb_version', $MB_version );
  }
  
  register_activation_hook( __FILE__, 'MB_install' );
