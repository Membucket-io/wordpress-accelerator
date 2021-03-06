<?php
  // Prevent direct script access
  if ( ! defined( 'MEMBUCKET' ) ) exit;
  
  require_once( 'mb-stub.php' );
  
  $_GLOBALS[ 'mb_checks' ] = [
    0 => file_exists( '/usr/local/cpanel/version' ),
    1 => file_exists( '/usr/bin/membucket' ) ||
         file_exists( '/usr/bin/membucketd' ),
    2 => MB_Get_User_Key() != '',
    3 => file_exists( realpath( get_home_path() ) . '/wp-content/object-cache.php' ),
    4 => class_exists( 'Memcache' )
  ];
  
  function activateCaching() {
    $contents = file_get_contents( __DIR__ . '/object-cache.php' );
    $path = ABSPATH . 'wp-content/object-cache.php';
    if ( file_exists( $path ) ) {
      $file = file_get_contents( $path );
      if ( $contents === $file ) {
        return;
      }
    }
    file_put_contents( $path, $contents );
  }
  
  function CallAPI( $method = 'GET', $path = '', $data = false ) {
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, "http://127.0.0.1:9999/wells{$path}" );
    
    $data_string = json_encode( $data );
    $headers = [ 'Content-Type: application/json' ];
    
    if ( 'GET' == $method ) {
      curl_setopt( $curl, CURLOPT_HTTPGET, true );
    } else {
      curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $method );
      $headers[] = 'Content-Length: ' . strlen( $data_string );
      
      if ( $data ) {
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string );
      }
    }
    
    curl_setopt( $curl, CURLOPT_HTTPHEADER,     $headers );
    curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 3 );
    curl_setopt( $curl, CURLOPT_TIMEOUT,        10 );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
    $result = curl_exec( $curl );
    curl_close( $curl );
    return $result;
  }
  
  /**
   * Reads the executing user's system name
   * 
   * @return string system username
   */
  // TODO: Suitable work-around for hosts with posix_getpwuid() disabled
  function _Get_User() {
    $user = posix_getpwuid( posix_geteuid() );
    return $user[ 'name' ];
  }
  
  function MB_Set_Associations( $well, $roles ) {
    $path = _RecursiveUpSearch( realpath( __DIR__ ), MB_FILENAME_ASSOCIATE );
    
    if ( '' === $path ) {
      $path = realpath( get_home_path() ) . '/' . MB_FILENAME_ASSOCIATE;
    }
    
    $assoc = MB_Get_Associations();
    foreach ( $roles as $role ) {
      $assoc[ $role ] = $well;
    }
    
    if ( false === file_put_contents( $path, json_encode( $assoc ) ) ) {
      return false;
    }
    
    return true;
  }
  
  /**
   * Reads the membucket access key from {@link MB_FILENAME_ACCESSKEY}
   * 
   * @return string access key for this user
   */
  function MB_Get_User_Key() {
    $path = _RecursiveUpSearch( realpath( get_home_path() ), MB_FILENAME_ACCESSKEY );
    if ( $path === '' ) {
      return '';
    }
    
    return trim( file_get_contents( $path ) );
  }
  
  /**
   * lists all wells user has access to
   *
   * Calls the membucket API using the executing system user's credentials to
   * list all Wells that can be used. Includes wells of any status, including
   * those that have been disabled, or might be in use by other applications.
   * 
   * @return array|null list of {@link Well} objects
   */
  function MB_Get_System_Wells() {
    $user = _Get_User();
    $key  = MB_Get_User_Key();
    
    if ( '' === $key ) {
      return null;
    }
    
    $wells = [];
    $response = CallAPI( 'GET', "?key={$key}&keyUser={$user}" );
    foreach ( json_decode( $response, true ) as $well ) {
      // Server didn't like our key
      if ( 'Bad Arguments' === $well ) {
        return [ new Well( '', 'Not Authorized', false ) ];
      }
      
      // Skip empty or expired records
      if ( ! $well[ 'ID' ] ) continue;
      
      $wells[] = new Well(
        $well[ 'ID' ],
        $well[ 'Name' ],
        $well[ 'Running' ]
      );
    }
    
    return $wells;
  }
