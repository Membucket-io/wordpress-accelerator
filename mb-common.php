<?php
  // Prevent direct script access
  if ( ! defined( 'MEMBUCKET' ) ) exit;
  
  // Some handy constants
  define( 'MB_FILENAME_ACCESSKEY', '.membucket' );
  define( 'MB_FILENAME_ASSOCIATE', '.membucketwells' );
  
  require( 'Well.class.php' );
  
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
  
  /**
   * Searches for $name, traveling up from $path
   *
   * Searches the directory tree starting at $path and working upwards until
   * we hit one of the restricted directories, for a file named $name.  This
   * search is case sensitive on all platforms.
   *
   * @param 
   */
  function _RecursiveUpSearch($path, $name) {
    for ( $i = 0; 10 > $i; $i++ ) {
      if ( '/'      === $path || // system root
           '/home'  === $path || // system home directory
           '/home2' === $path || // cpanel additional home directory
           '/home3' === $path || // cpanel additional home directory
           '/opt'   === $path || // system directory
           '/usr'   === $path || // system directory
           '/var'   === $path) { // system directory
        return '';
      }
      
      $file = "{$path}/{$name}";
      if ( file_exists( $file ) ) {
        return $file;
      }
      
      // Traverse up one directory
      $path = realpath( "{$path}/../" );
    }
    
    return '';
  }

  /**
   * Reads well associations from {@link MB_FILENAME_ASSOCIATE}
   *
   * Well Associations are stored above the web directory. On cPanel we can
   * automatically detect this fairly easily, as all sites are served from
   * within a user's home directory. This approach might need to be adjusted
   * when targeting another platform or control panel.
   *
   * @return array keyed as cache groups, values as Well hashes
   */
  // TODO: Check when supporting Operating Systems outside of CentOS
  function MB_Get_Associations() {
    // Get script directory without trailing slash
    $path = realpath( get_home_path() );
    $path = _RecursiveUpSearch( $path, MB_FILENAME_ASSOCIATE );
    
    if ( ! file_exists( $path ) ||
         4096 < filesize( $path ) ) {
      return null;
    }
    
    $s = file_get_contents( $path );
    $s = json_decode( $s );
    $groups = [
      'static'  => $s->static,
      'dynamic' => $s->dynamic,
      'author'  => $s->author,
      'session' => $s->session,
      'default' => $s->default
    ];
    
    return $groups;
  }
  
  function MB_Set_Associations( $well, $roles ) {
    // Get script directory without trailing slash
    $path = realpath( get_home_path() );
    $path = _RecursiveUpSearch( $path, MB_FILENAME_ASSOCIATE );
    
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
    // Get script directory without trailing slash
    $path = realpath( get_home_path() );
    
    // On CentOS under default (supported) configuration, the home Directory
    // contains the username, therefore we need the username.
    $user = _Get_User();
    
    // Currently the username must be in the path
    if ( -1 === strpos( $path, $user ) )
      return '';
    
    // Try at most 10 directories
    $path = _RecursiveUpSearch( $path, MB_FILENAME_ACCESSKEY );
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
