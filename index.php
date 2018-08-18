<?php
  /*
    Plugin Name: Accelerator by Membucket.io
    Description: A plugin that improves the performance of WordPress
    Author: Membucket.io, LLC
    Version: 0.38
  */
  define( 'MB_PROD_NAME', 'Accelerator by Membucket.io' );
  define( 'MEMBUCKET', true );
  
  global $MB_version;
  $MB_version = '0.38';
  
  function uninstallObjectCache() {
    $contents = file_get_contents( __DIR__ . '/object-cache.php' );
    $path = ABSPATH . 'wp-content/object-cache.php';

    if ( ! file_exists( $path ) ) {
      return;
    }

    $file = file_get_contents( $path );
    if ( $contents === $file ) {
      unlink( $path );
    }
  }
  
  function MB_install() {
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
    global $MB_version;
    add_option( 'mb_version', $MB_version );
    
    uninstallObjectCache();
  }
  
  register_activation_hook( __FILE__, 'MB_install' );
  register_deactivation_hook( __FILE__, 'uninstallObjectCache' );

  function MB_menu() {
    $product_name = __( 'Membucket', 'textdomain' );
    
    $menu_token = 'accel-membucket';
    $menu_location = 'manage_options';
    add_menu_page(
      $product_name,
      $product_name,
      'manage_options',
      $menu_token,
      'MB_settings_page',
      plugins_url( 'membucket.svg', __FILE__ )
    );
    
    add_submenu_page(
      $menu_token,
      $product_name,
      'Settings',
      $menu_location,
      'accel-membucket',
      'MB_settings_page'
    );
    
    add_submenu_page(
      $menu_token,
      $product_name,
      'Analytics',
      $menu_location,
      'accel-analytics',
      'MB_analytics_page'
    );
    
    add_submenu_page(
      $menu_token,
      $product_name,
      'Diagnostics',
      $menu_location,
      'accel-diagnostics',
      'mb_diagnostics_page'
    );
  }
  
  /**
   * Logged in Administrators should have access to our configuration screen.
   */
  if ( is_admin() ) {
    add_action( 'admin_menu', 'MB_menu' );
  }
  
  function MB_analytics_page() {
    require( 'page-empty.php' );
  }
  
  function MB_diagnostics_page() {
    require( 'page-empty.php' );
  }
  
  function MB_settings_page() {
    require( 'mb-common.php' );     // We need access to Membucket's API
    require( 'page-settings.php' );
  }
