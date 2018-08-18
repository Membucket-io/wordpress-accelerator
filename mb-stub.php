<?php
  // Some handy constants
  define( 'MB_FILENAME_ACCESSKEY', '.membucket' );
  define( 'MB_FILENAME_ASSOCIATE', '.membucketwells' );

  require_once( 'Well.class.php' );

  /**
   * Searches for $name, traveling up from $path
   *
   * Searches the directory tree starting at $path and working upwards until
   * we hit one of the restricted directories, for a file named $name.  This
   * search is case sensitive on all platforms.
   *
   * @param string $path Directory to start traversal from
   * @param string $name Filename to look for (case sensitive)
   *
   * @return string|'' Absolute Path and Filename to requested file
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
    $path = _RecursiveUpSearch( realpath( __DIR__ ), MB_FILENAME_ASSOCIATE );
    
    if ( ! file_exists( $path ) ||
         4096 < filesize( $path ) ) {
      return null;
    }
    
    $s = file_get_contents( $path );
    $s = json_decode( $s );
    $groups = [];
    $groups[ 'static' ] = ( isset( $s->static ) ? $s->static : null );
    $groups[ 'dynamic' ] = ( isset( $s->dynamic ) ? $s->dynamic : null );
    $groups[ 'author' ] = ( isset( $s->author ) ? $s->author : null );
    $groups[ 'session' ] = ( isset( $s->session ) ? $s->session : null );
    $groups[ 'default' ] = ( isset( $s->default ) ? $s->default : null );
    return $groups;
  }
