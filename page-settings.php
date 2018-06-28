<?php
  // Prevent direct script access
  if ( ! defined( 'MEMBUCKET' ) ) exit;

  echo '<h2>' . MB_PROD_NAME . ' Settings</h2>';

  @ $using_cpanel = file_exists( '/usr/local/cpanel/version' );

  if ( ! $using_cpanel ) {
    echo 'Your hosting control panel is not yet supported!  If you are using cPanel and still seeing this message, ask your hosting provider if there\'s a jail in place.  If there is a jail, you can ignore this message.';
  } else {
    display_config_page();
  }
  
  require( 'mb-common.php' );

  function display_config_page() {
    @ $mb_found_system = file_exists( '/usr/bin/membucket' ) ||
                         file_exists( '/usr/bin/membucketd' );
    $mb_found_user = MB_Get_User_Key() != "";
    
    global $err_OS_NOT_SUPPORTED;
    global $err_USER_NOT_AUTHORIZED;
    
    if ( ! $mb_found_system ) {
      echo 'Membucket was not found on your system!  Please ask your hosting provider to install and activate Membucket for your account.';
      return;
    } elseif ( ! $mb_found_user ) {
      echo $err_USER_NOT_AUTHORIZED;
      return;
    }

    global $wpdb;

    // If we're changing well associations
    if ( ! empty( $_POST ) ) {
      $well = $_POST['well'];

      // Remove any existing association for this well
      $wpdb->delete( "{$wpdb->prefix}membucket",
        array(
          'well' => $well
        )
      );

      // Insert the new association
      $wpdb->insert( "{$wpdb->prefix}membucket",
        array(
          'groups' => $_POST['groups'],
          'well'   => $well,
        )
      );
    }

    // Put together information about current associations
    $wells = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}membucket;" );

    $well_ids = array();
    $well_groups = array();
    $well_info = array();
    foreach ( $wells as $well ) {
      $groups = explode( ',', $well->groups );
      for ( $i = 0; $i < count( $groups ); $i++ ) {
        $group = $groups[ $i ];

        if ( ! isset( $well_groups[ $group ] ) ) {
          $well_groups[ $group ] = array();
        }

        $well_groups[ $group ][] = $well->well;
        $well_ids[] = $well->well;
      }

      $well_info[ $well->well ] = $well->groups;
    }

    $system_wells = MB_Get_System_Wells();

    // Check that all associated wells exist
    foreach ( $well_ids as $well ) {
      $exists = false;
      foreach ( $system_wells as $system_well ) {
        if ( $system_well->ID === $well ) {
          $exists = true;
          break;
        }
      }

      if ( ! $exists ) {
        $wpdb->delete( "{$wpdb->prefix}membucket",
          array(
            'well' => $well
          )
        );

        echo "<strong>Removed invalid association to missing well:</strong> {$well}<br />" . PHP_EOL;
        echo "<strong>Please Refresh!</strong>";
        return;
      }
    }

    foreach ( $well_groups as $k => $v ) {
      if ( 1 < count( $v ) ) {
        echo "<strong>Well Role Overlap Detected</strong> [$k]: {$v[0]} {$v[1]}<br />" . PHP_EOL;
      }
    }

    wp_enqueue_script( 'jquery' );
    $css = plugins_url( 'style.css', __FILE__ );
    $js = plugins_url( 'script.js', __FILE__ );
    $file = admin_url( 'admin.php?page=accel-membucket' );
?>
<link rel="stylesheet" href="<?php echo $css; ?>" />
<p>Here you can assign roles to wells and customize how membucket caches your site.</p>
<div class="mbwells">
  <h3>Wells</h3>
  <p><strong>Step 1)</strong> Select a Well</p>
<?php
  foreach ( $system_wells as $well ) {
    $groups = ucfirst( $well_info[ $well->ID ] );

    if ( empty( $groups ) ) {
      $list = 'Unassigned';
    } else {
      $list = explode( ',', $groups );
      for ( $i = 0; $i < count( $list ); $i++ ) {
        $list[ $i ] = ucfirst( $list[ $i ] );
      }
    }
  ?>
  <div class="well" id="<?php echo $well->ID; ?>">
    <div>
      <strong>Membucket ID:</strong><br>
      <span class="amid"><?php echo $well->ID; ?></span>
    </div>
    <div>
      <strong>Logical Name:</strong><br>
      <?php echo $well->Name; ?>
    </div>
    <div>
      <?php if ( is_array( $list ) ): ?>
        <?php foreach ( $list as $g ): ?>
          <div class="amrole"><?php echo $g; ?></div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="amrole"><?php echo $list; ?></div>
      <?php endif; ?>
    </div>
  </div>
<?php
  }
?>
  <br style="clear:both" />
</div>
<div class="mbroles">
  <h3>Roles</h3>
  <p><strong>Step 2)</strong> Select Roles to assign to <span id="wellName">-</span></p>
  <p>To accelerate everything, make sure every role is assigned to a Well. If you are unsure what to assign <button id="mbSelectAll">click here</button> to select all.</p>

  <div class="rolebox" id="static">
    <h4>Static</h4>
    <ul><li>Category<li>General<li>Options<li>Plugins List<li>Site-Options<li>Themes<li>Timeinfo</ul>
  </div>
  <div class="rolebox" id="dynamic">
    <h4>Dynamic</h4>
    <ul><li>Blog-Details<li>Bookmark<li>Counts<li>RSS<li>Site-Lookup<li>Site-Transient<li>Terms<li>Transient</ul>
  </div>
  <div class="rolebox" id="author">
    <h4>Author Content</h4>
    <ul><li>Blog-ID-Cache<li>Blog-Lookup<li>Calendar<li>Comments<li>Global-Posts<li>Post-Ancestors<li>Post-Meta<li>Posts<li>Slugs</ul>
  </div>
  <div class="rolebox" id="session">
    <h4>Sessions</h4>
    <ul><li>User-Meta<li>User Email<li>User Logins<li>User Meta<li>Users<li>User Slugs</ul>
  </div>
  <div class="rolebox" id="other">
    <h4>Default</h4>
    <ul><li>Undefined Content<li>Plugins Data<li>(Catch All)</ul>
  </div>
  <br style="clear:both" />
</div>
<br/>

<input type="submit" name="submit" id="submit" class="button button-primary" disabled value="Apply"/>
<span class="mbdisabled">(nothing to save)</span>

<form action="<?php echo $file; ?>" method="POST" id="fSubmit">
  <input type="hidden" id="fWell" name="well" value="" />
  <input type="hidden" id="fGroup" name="groups" value="" />
</form>

<script src="<?php echo $js; ?>"></script>
<?php
  }
