<?php
  // Used by wp-multisite and other managers
  if ( ! defined( 'MB_CACHE_SALT' ) ) {
    define( 'MB_CACHE_SALT', '' );
  }

  /**
   * Instance of caching manager for use with membucket
   */
  class MB_WP_Cache {
    var $enabled    = true;
    var $epoch      = null;  // Used to manage generations of the cache
    var $expiration = 0;     // Default expiration time, 0 = no expiry
    
    var $prefix_blog   = '';
    var $prefix_global = '';
    var $salt          = '';
    
    var $groups   = [ 'WP_Object_Cache_global' ];
    var $npgroups = [];
    var $wells    = [];
    
    var $last_cache = [];
    
    function get_groupings() {
      return [
        'static' => [
          'blog-details',
          'category',
          'general',
          'options',
          'plugins',
          'themes',
          'widgets'],
        'dynamic' => [
          'blog-id-cache',
          'blog-lookup',
          'counts',
          'rss',
          'site-lookup',
          'site-options',
          'site-transient',
          'transient',
          'terms'],
        'author' => [
          'bookmark',
          'calendar',
          'comment',
          'post_ancestors',
          'post_meta',
          'posts',
          'global-posts',
          'slugs'],
        'session' => [
          'user_meta',
          'useremail',
          'userlogins',
          'usermeta',
          'users',
          'userslugs'],
        'default' => [ 'default' ]
      ];
    }
    
  	function __construct() {
      // load well associations
      $assoc = MB_Get_Associations();
      $path = realpath( __DIR__ );
      $path = realpath( "{$path}/../.." );
      
      // no assocations found
      if ( empty( $assoc ) || $assoc[ 'default' ] === null ) {
        $this->enabled = false;
        return;
      }
      
      $groupings = $this->get_groupings();
      $cb = array( $this, 'failure_callback' );
      foreach ( $assoc as $container => $well ) {
        $url = "{$path}/.{$well}.sock";
        if ( ! file_exists( $url ) ) {
          continue; // well not found
        }
        
        $conn = new Memcache();
        $conn->addServer( "unix://{$url}", 0, true, 1, 1, 15, true, $cb, 500 );
        $conn->setCompressThreshold( 16384, 0.15 );
        
        // assign this server to each covered group
        foreach ( $groupings[ $container ] as $group ) {
          $this->wells[ $group ] = $conn;
        }
      }
      
  		global $blog_id, $table_prefix;
  		$this->prefix_blog = ( is_multisite() ? $blog_id : $table_prefix );
      $this->prefix_global = ( is_multisite() || defined('CUSTOM_USER_TABLE') && defined('CUSTOM_USER_META_TABLE') ) ? '' : $table_prefix;
  	}
    
  	function failure_callback($file, $port) {
      trigger_error("Could not connect to Well at $file\n", E_USER_ERROR);
      $this->enabled = false;
  	}
    
    /**
     * Generates a unique key based on our epoch, a base key and a group name
     */
    function key( $key, $group = 'default' ) {
      $prefix = '';
      if ( false === array_search( $group, $this->groups ) ) {
        $prefix = $this->prefix_blog;
      } else {
        $prefix = $this->prefix_global;
      }
      
      return preg_replace( '/\s+/', '', "{$this->salt}:{$prefix}:{$group}:{$key}" );
    }
    
  	function &get_well( $group ) {
  		if ( array_key_exists( $group, $this->wells ) ) {
        return $this->wells[ $group ];
      }
      return $this->wells[ 'default' ];
  	}
    
  	function close() {
      foreach ( $this->wells as $group => $server ) {
        $server->close();
      }
  	}
    
  	function add_global_groups( $groups ) {
  		if ( ! is_array( $groups ) ) {
  			$groups = (array) $groups;
      }

  		$this->groups = array_unique( array_merge( $this->groups, $groups ) );
  	}
    
  	function add_non_persistent_groups( $groups ) {
  		if ( ! is_array( $groups ) ) {
  			$groups = (array) $groups;
      }
      
  		$this->npgroups = array_unique( array_merge( $this->npgroups, $groups ) );
  	}
    
  	function switch_to_blog( $blog_id ) {
  		global $table_prefix;
  		$blog_id = (int) $blog_id;
  		$this->prefix_blog = ( is_multisite() ? $blog_id : $table_prefix );
  	}
    
    /**
     * Inserts a new record into cache storage
     */
    function add( $key, $value, $group = 'default', $expire = 0 ) {
      // Expiry should default to $this->expiration global setting
      $expire = ( $expire === 0 ? $this->expiration : $expire );
      $key    = $this->key( $key, $group );
      $result = true;
      
      if ( is_object( $value ) ) {
        $value = clone $value;
      }
      
      if ( ! in_array( $group, $this->npgroups ) ) {
        $result = $this->get_well( $group )->add( $key, $value, false, $expire );
  		}
      
      if ( false !== $result ) {
        $this->last_cache[ $key ] = $value;
  		}
      
  		return $result;
    }
    
    function replace( $key, $value, $group = 'default', $expire = 0) {
      // Expiry should default to $this->expiration global setting
      $expire = ( $expire === 0 ? $this->expiration : $expire );
      $key    = $this->key( $key, $group );

      if ( is_object( $value ) ) {
        $value = clone $value;
      }
      
      $result = $this->get_well( $group )->replace( $key, $value, false, $expire );
      if ( false !== $result ) {
        $this->local_cache[ $key ] = $value;
      }
      
      return $result;
    }
    
    function get( $key, $group = 'default', $force = false ) {
      $key = $this->key( $key, $group );
      
      $value = '';
      if ( isset( $this->local_cache[ $key ] ) && ( ! $force || in_array( $group, $this->npgroups ) ) ) {
        if ( is_object( $this->local_cache[ $key ] ) ) {
          $value = clone $this->local_cache[ $key ];
        } else {
          $value = $this->local_cache[ $key ];
        }
        
        return $value;
      } else if ( in_array( $group, $this->npgroups ) ) {
        $this->local_cache[ $key ] = $value = false;
      } else {
        $value = $this->get_well( $group )->get( $key );
        $this->cache[ $key ] = $value;
      }
      
      return $value;
    }
    
  	function set( $key, $value, $group = 'default', $expire = 0 ) {
      // Expiry should default to $this->expiration global setting
      $expire = ( $expire === 0 ? $this->expiration : $expire );
      $key    = $this->key( $key, $group );
      
      if ( is_object( $value ) ) {
        $value = clone $value;
      }
      
      $this->local_cache[ $key ] = $value;
      if ( in_array( $group, $this->npgroups ) ) {
        return true;
      }
      
      return $this->get_well( $group )->set( $key, $value, false, $expire );
  	}

    function incr( $key, $i = 1, $group = 'default' ) {
      $key = $this->key( $key, $group );
      $this->local_cache[ $key ] = $this->get_well( $group )->increment( $key, $i );
      return $this->local_cache[ $key ];
    }
    
    function decr( $key, $i = 1, $group = 'default' ) {
      $key = $this->key( $key, $group );
      $this->local_cache[ $key ] = $this->get_well( $group )->decrement( $key, $i );
      return $this->local_cache[ $key ];
    }
    
    function delete( $key, $group = 'default' ) {
      $key    = $this->key( $key, $group );
      $result = true;
      
      if ( ! in_array( $group, $this->npgroups ) ) {
        $result = $this->get_well( $group )->delete( $key );
      }
      
      if ( false !== $result ) {
        unset( $this->local_cache[ $key ] );
      }
      
      return $result;
    }
    
  	function flush() {
      // Clear the local cache
      $this->local_cache = [];
      
      $this->add( 'epoch', intval( microtime( true ) * 1e6 ), 'WP_Object_Cache' );
      $this->epoch[ $this->prefix_blog ] = $this->incr( 'epoch', 1, 'WP_Object_Cache' );
      
      if ( is_main_site() ) {
        $this->add( 'epoch', intval( microtime( true ) * 1e6 ), 'WP_Object_Cache_global' );
        $this->epoch[ '_global' ] = $this->incr( 'epoch', 1, 'WP_Object_Cache_global' );
      }
  	}
  }

  //
  // WordPress Bindings
  //

  function wp_cache_add($key, $value, $group = '', $expire = 0) {
    global $mb_cache;
    return $mb_cache->add($key, $value, $group, $expire);
  }
  
  function wp_cache_incr($key, $n = 1, $group = '') {
  	global $mb_cache;
  	return $mb_cache->incr($key, $n, $group);
  }
  
  function wp_cache_decr($key, $n = 1, $group = '') {
  	global $mb_cache;
  	return $mb_cache->decr($key, $n, $group);
  }
  
  function wp_cache_close() {
  	global $mb_cache;
  	return $mb_cache->close();
  }
  
  function wp_cache_delete($key, $group = '') {
  	global $mb_cache;
  	return $mb_cache->delete($key, $group);
  }
  
  function wp_cache_flush() {
  	global $mb_cache;
  	return $mb_cache->flush();
  }
  
  function wp_cache_get($key, $group = '', $force = false) {
  	global $mb_cache;
  	return $mb_cache->get($key, $group, $force);
  }

  function wp_cache_init() {
    require_once(__DIR__ . '/plugins/accelerator-by-membucket/mb-stub.php');
    
  	global $mb_cache;
  	$mb_cache = new MB_WP_Cache();
  }

  function wp_cache_replace($key, $value, $group = '', $expire = 0) {
  	global $mb_cache;
  	return $mb_cache->replace($key, $value, $group, $expire);
  }
  
  function wp_cache_set($key, $value, $group = '', $expire = 0) {
  	global $mb_cache;
  
    if ( defined( 'WP_INSTALLING' ) ) {
      return $mb_cache->delete($key, $group);
    }
  
    return $mb_cache->set($key, $value, $group, $expire);
  }

  function wp_cache_switch_to_blog( $blog_id ) {
  	global $mb_cache;
  	return $mb_cache->switch_to_blog( $blog_id );
  }

  function wp_cache_add_global_groups( $groups ) {
  	global $mb_cache;
  	$mb_cache->add_global_groups($groups);
  }

  function wp_cache_add_non_persistent_groups( $groups ) {
  	global $mb_cache;
  	$mb_cache->add_non_persistent_groups($groups);
  }
