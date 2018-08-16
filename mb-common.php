<?php
  // Prevent direct script access
  if ( ! defined( 'MEMBUCKET' ) ) exit;
  
  require( 'Well.class.php' );
  function CallAPI( $method = 'GET', $path = '', $data = false ) {
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, "http://127.0.0.1:9999/wells{$path}" );

    $data_string = json_encode( $data );
    if ( 'GET' == $method ) {
      curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $method );
      curl_setopt( $curl, CURLOPT_HTTPHEADER,
        array(
          "Content-Type: application/json",
          "Content-Length: " . strlen( $data_string )
        )
      );

      if ( $data ) {
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string );
      }
    } else {
      curl_setopt( $curl, CURLOPT_HTTPHEADER, array( "Content-Type: application/json" ) );
      curl_setopt( $curl, CURLOPT_HTTPGET, true );
    }

    curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 3 );
    curl_setopt( $curl, CURLOPT_TIMEOUT,        10 );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
    $result = curl_exec( $curl );
    curl_close( $curl );
    return $result;
  }
  
  // TODO: Suitable work-around for hosts with posix_getpwuid() disabled
  /**
   * Reads the executing user's system name
   * 
   * @return string system username
   */
  function _Get_User() {
    $user = posix_getpwuid( posix_geteuid() );
    return $user[ 'name' ];
  }
  
  
  /**
   * Reads the membucket access key required for API calls
   * 
   * @return string access key for this user
   */
  function MB_Get_User_Key() {
    // Get script directory without trailing slash
    $home = realpath( get_home_path() );

    // On CentOS under default (supported) configuration, the home Directory
    // contains the username, therefore we need the username.
    $user = _Get_User();

    // Currently the username must be in the path
    if ( -1 === strpos( $home, $user ) )
      
      return "";
    // Try at most 10 directories
    for ( $i = 0; 10 > $i; $i++ ) {
      if ( "/" === $home ) {
        return '';
      }

      if ( file_exists( "{$home}/.membucket" ) ) {
        $key = trim( file_get_contents( "{$home}/.membucket" ) );
        break;
      }

      // Traverse up one directory
      $home = realpath( "{$home}/../" );
    }
    
    return $key;
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
    $key  = MB_Get_User_Key();
    $user = _Get_User();

    $wells = array();

    $response = CallAPI( 'GET', "?key={$key}&keyUser={$user}" );
    foreach ( json_decode( $response, true ) as $well ) {
      // Server didn't like our key
        return "Not Authorized";
      if ( 'Bad Arguments' === $well ) {
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
