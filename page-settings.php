<?php
  // Prevent direct script access
  if ( ! defined( 'MEMBUCKET' ) ) exit;
  
  // If we're changing well associations
  $did_install = false;
  if ( ! empty( $_POST ) ) {
    if ( isset( $_POST[ 'well' ] ) ) {
      MB_Set_Associations( $_POST[ 'well' ], explode( ',', $_POST[ 'roles' ] ) );
    } else if ( isset( $_POST[ 'install' ] ) ) {
      $did_install = true;
      MB_install();
    }
  }
  
  $wells = MB_Get_System_Wells();
  $wellassoc = MB_Get_Associations();
  
  $roles = [];
  foreach ( $wellassoc as $role => $well ) {
    if ( array_key_exists( $well, $roles ) ) {
      $roles[ $well ] = $roles[ $well ] . ', ' . ucfirst( $role );
    } else {
      $roles[ $well ] = ucfirst( $role );
    }
  }
?>
<link rel="stylesheet" href="<?php echo plugins_url( 'style.css', __FILE__ ); ?>" />

<h2><?php echo MB_PROD_NAME; ?> Settings</h2>

<?php if ( ! $_GLOBALS[ 'mb_checks' ][ 0 ] ): ?><p>
  Your hosting control panel is not yet supported!  Currently Membucket only
  supports cPanel/WHM servers.
</p><?php elseif ( ! $_GLOBALS[ 'mb_checks' ][ 1 ] ): ?><p>
  Membucket was not found on your system!  Your hosting provider does not
  support Membucket, or has not made it available to your user.
</p><?php elseif ( ! $_GLOBALS[ 'mb_checks' ][ 4 ] ): ?><p>
  A required PHP module was not found enabled on this system. Please ask your
  hosting provider to enable the PHP module called Memcache. They can do so via
  the "Module Installers > PHP Pecl" section of WHM.
</p><?php elseif ( ! $_GLOBALS[ 'mb_checks' ][ 2 ] ): ?><p>
  Your account does not have an access key for use with Membucket.  If you
  have access to SSH, please run the command: `membucket generate-key`.
  Otherwise, ask your hosting provider to run this command as your user.
</p><?php else: ?>

<p>Here you can assign roles to wells and customize how membucket caches your site.</p>

<div>
  <h3>Wells</h3>
  <p><strong>Step 1)</strong> Select a Well</p>
<?php
  foreach ( $wells as $well ) {
    $groups = 'Unassigned';
    if ( array_key_exists( $well->ID, $roles ) ) {
      $groups = ucfirst( $roles[ $well->ID ] );
    }
?>
  <div class="mb-well" id="<?php echo $well->ID; ?>">
    <div>
      <strong>Membucket ID:</strong><br>
      <span class="mb-well-hash"><?php echo $well->ID; ?></span>
    </div>
    <div>
      <strong>Logical Name:</strong><br>
      <?php echo $well->Name; ?>
    </div>
    <div>
      <div class="mb-well-role"><?php echo $groups; ?></div>
    </div>
  </div>
<?php } ?>
  <br style="clear:both" />
</div>

<div>
  <h3>Roles</h3>
  <p><strong>Step 2)</strong> Select Roles to assign to <span id="wellName">-</span></p>
  <p>To accelerate everything, make sure every role is assigned to a Well. If
    you are unsure what to assign, <button id="mb-select-all"
    >click here</button> to select all.</p>

  <div class="mb-role" id="static">
    <h4>Static</h4>
    <ul><li>Category<li>General<li>Options<li>Plugins List<li>Site-Options<li>Themes<li>Timeinfo</ul>
  </div>
  <div class="mb-role" id="dynamic">
    <h4>Dynamic</h4>
    <ul><li>Blog-Details<li>Bookmark<li>Counts<li>RSS<li>Site-Lookup<li>Site-Transient<li>Terms<li>Transient</ul>
  </div>
  <div class="mb-role" id="author">
    <h4>Author Content</h4>
    <ul><li>Blog-ID-Cache<li>Blog-Lookup<li>Calendar<li>Comments<li>Global-Posts<li>Post-Ancestors<li>Post-Meta<li>Posts<li>Slugs</ul>
  </div>
  <div class="mb-role" id="session">
    <h4>Sessions</h4>
    <ul><li>User-Meta<li>User Email<li>User Logins<li>User Meta<li>Users<li>User Slugs</ul>
  </div>
  <div class="mb-role" id="default">
    <h4>Default</h4>
    <ul><li>Undefined Content<li>Plugins Data<li>(Catch All)</ul>
  </div>
  <br style="clear:both" />
</div>
<br/>

<input type="submit" name="submit" id="mb-submit" class="button button-primary" disabled value="Apply"/>
<span id="mb-note">(nothing to save)</span>

<form action="" method="POST" id="mb-form">
  <input type="hidden" id="mb-form-well" name="well" value="" />
  <input type="hidden" id="mb-form-roles" name="roles" value="" />
</form>

<?php wp_enqueue_script( 'jquery' ); wp_enqueue_script( 'jquery-ui-core' ) ?>
<script src="<?php echo plugins_url( 'script.js', __FILE__ ); ?>"></script>
<?php endif; ?>

<?php if ( ! empty( $wellassoc[ 'default' ] ) ): ?>
<h3>Activate Caching</h3>
<p><strong>Step 3)</strong> Now that you have well(s) associated...</p>

<?php if ( $did_install ): ?><p>
  We copied the file "object-cache.php" into "wp-content" for you!
</p><?php elseif ( ! $_GLOBALS[ 'mb_checks' ][ 3 ] ): ?><form method="POST"><p>
  Installation of the WordPress plugin has not yet been completed!  The file
  "object-cache.php" must be copied into the root of the "wp-content" folder.
  <button type="submit" class="btn btn-primary">Click Here</button> to have us
  try to do it for you. <input type="hidden" name="install" value="true"/>.
  This should be the final step to turning cache on!
</p></form><?php else: ?><div class="alert alert-success">
  Caching should be active!
</div><?php endif;?>
<?php endif; ?>
