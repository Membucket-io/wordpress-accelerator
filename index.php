<?php
  /*
    Plugin Name: Accelerator by Membucket.io
    Description: A plugin that improves the performance of WordPress
    Author: Membucket.io, LLC
    Version: 0.32
  */
  define( 'MB_PROD_NAME', 'Accelerator by Membucket.io' );
  define( 'MEMBUCKET', true );
  
  global $MB_version;
  $MB_version = '0.32';
  
  // We need access to Membucket's API
  require( 'mb-common.php' );

  // Handle "Activate" or plugin update
  require( 'mb-install-wp.php' );

  function MB_menu() {
    $product_name = __( MB_PROD_NAME, 'textdomain' );
    
    $menu_token = 'accel-membucket';
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
      'manage_options',
      'accel-membucket',
      'MB_settings_page'
    );

    add_submenu_page(
      $menu_token,
      $product_name,
      'Analytics',
      'manage_options',
      'accel-analytics',
      'MB_analytics_page'
    );

    add_submenu_page(
      $menu_token,
      $product_name,
      'Diagnostics',
      'manage_options',
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
    echo '<h2>' . MB_PROD_NAME . ' Analytics</h2>';
    echo '<p>Coming Soon!</p>';
  }

  function MB_diagnostics_page() {
    echo '<h2>' . MB_PROD_NAME . ' Diagnostics</h2>';
    echo '<p>Coming Soon!</p>';
  }

  function MB_settings_page() {
    require( 'page-settings.php' );
  }
