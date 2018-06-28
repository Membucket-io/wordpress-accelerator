<?php
  // Prevent direct script access
  if ( ! defined( 'MEMBUCKET' ) ) exit;

  function MB_install() {
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    global $wpdb;
    dbDelta( "CREATE TABLE {$wpdb->prefix}membucket (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  groups tinytext DEFAULT '' NOT NULL,
  well tinytext NOT NULL,
  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  PRIMARY KEY (id)
) {$wpdb->get_charset_collate()};" );

    global $MB_version;
    add_option( 'mb_version', $MB_version );
  }

  register_activation_hook( __FILE__, 'MB_install' );
