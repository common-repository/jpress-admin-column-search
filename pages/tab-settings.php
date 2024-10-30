<?php
/**
 * setttings tab
 */

if( isset( $_POST["acssubmit_settings"] ) && current_user_can( 'manage_options' ) ) {
  unset($_POST["acssubmit_settings"]);
  $post_data = jpress_sanitize_all( $_POST );
  update_option( 'jpress_acs_settings', $post_data );
}

$acs_settings = get_option( 'jpress_acs_settings' );

?>

<table class="form-table">
  <tbody>
    <tr class="general">
      <th scope="row">
        <h3><?php echo __("General Settings", "jpress-admin-column-search" );?></h3>
      </th>
      <td>
        <div>
          <form method="post" action="options-general.php?page=admin-column-search&tab=settings">
            <p>
              <label for="show_hidden">
                <input id="show_hidden" name="show_hidden" type="checkbox" value="1" <?php if ( $acs_settings['show_hidden'] ):?>checked="checked"<?php endif;?>>
                <?php echo __("Show hidden custom fields.", "jpress-admin-column-search" );?>
              </label>
            </p>
            <p>
              <label for="use_transient">
                <input id="use_transient" name="use_transient"  type="checkbox" value="1" <?php if ( $acs_settings['use_transient'] ):?>checked="checked"<?php endif;?>>
                <?php echo __("Use transient to store filters data.", "jpress-admin-column-search" );?>
              </label>
            </p>
            <p>
              <input type="submit" name="acssubmit_settings" class="button" value="<?php echo __("Save", "jpress-admin-column-search" );?>">
            </p>
          </form>
        </div>
      </td>
    </tr>
  </tbody>
</table>