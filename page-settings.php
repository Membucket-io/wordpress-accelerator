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
      activateCaching();
    }
  }
  
  $wells = MB_Get_System_Wells();
  $wellassoc = MB_Get_Associations();
  
  $roles = [];
  if ( is_array ( $wellassoc ) ) {
    foreach ( $wellassoc as $role => $well ) {
      if ( array_key_exists( $well, $roles ) ) {
        $roles[ $well ] = $roles[ $well ] . ', ' . ucfirst( $role );
      } else {
        $roles[ $well ] = ucfirst( $role );
      }
    }
  }
?>
<link rel="stylesheet" href="<?php echo plugins_url( 'style.css', __FILE__ ); ?>" />

<h2><?php echo MB_PROD_NAME; ?> Settings</h2>

<?php
  $checks_failed = 0;
  foreach ( $_GLOBALS[ 'mb_checks' ] as $check ) {
    if ( false === $check ) {
      $checks_failed++;
    }
  }
?>

<?php if ( 1 < $checks_failed || ( 1 === $checks_failed && true === $_GLOBALS[ 'mb_checks' ][ 3 ] ) ): ?>
  <div class="notice notice-error">
    <?php if ( ! $_GLOBALS[ 'mb_checks' ][ 0 ] ): ?>
      <p><strong>cPanel/WHM Not Detected!</strong></p>
      <p>Currently, Membucket only supports cPanel/WHM control panels. We could
        not detect that your website is being hosted under cPanel. You cannot
        continue.</p>
    <?php elseif ( ! $_GLOBALS[ 'mb_checks' ][ 1 ] ): ?>
      <p><strong>Membucket Not Found</strong></p>
      <p>Membucket was not found on your system. Your hosting provider does not
        support Membucket, or it has not been made available to your user. Ask
       your hosting provider how to continue.</p>
    <?php elseif ( ! $_GLOBALS[ 'mb_checks' ][ 4 ] ): ?>
      <p><strong>PHP Module Not Found</strong></p>
      <p>We could not find the required PHP module "Memcache", which acts as a
        client to Membucket. Ask your hosting provider to enable this module
        via the "Module Installers > PHP Pecl" section of WHM.</p>
    <?php elseif ( ! $_GLOBALS[ 'mb_checks' ][ 2 ] ): ?>
      <p><strong>Membucket Not Activated</strong></p>
      <p>Your account does not have an access key for use with Membucket. If
        you have access to SSh, please run the command: "membucket generate-key".
        Otherwise, ask your hosting provider to run this command as your user.</p>
      <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ( 0 === $checks_failed || ( 1 === $checks_failed && false === $_GLOBALS[ 'mb_checks' ][ 3 ] ) ): ?>
<?php if ( ! empty( $wellassoc[ 'default' ] ) ): ?>
  <?php if ( $did_install ): ?><p>
    <div class="notice notice-success">
      <p><strong>Caching Active!</strong></p>
      <p>Membucket is now active. If you load home page now, you should see
        data on the graphs shown in cPanel under "Membucket Stats".</p>
      <p>You can still adjust your well associations live below.</p>
    </div>
  <?php elseif ( ! $_GLOBALS[ 'mb_checks' ][ 3 ] ): ?>
  <div class="notice notice-warning">
    <p><strong>One last step...</strong></p>
    <p>Once you are happy with your well associations:</p>

    <p><form method="POST">
      <input type="hidden" name="install" value="true"/>
      <input type="submit" class="button button-primary" value="Activate Caching"/>
    </form></p>
  </div>
  <?php endif; ?>
<?php endif; ?>

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
