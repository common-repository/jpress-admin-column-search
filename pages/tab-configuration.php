<?php
/**
 * configuration tab
 */

global $wpdb;
$posts_types = get_post_types();
wp_enqueue_script( 'accordion' );

if( isset( $_POST["acssubmit"] )  && current_user_can( 'manage_options' ) ) {
  unset($_POST["acssubmit"]);
  $post_data = jpress_sanitize_all( $_POST );
  if ( isset( $post_data['enable'] ) && isset( $post_data['type'] ) && isset( $post_data['field'] ) && isset( $post_data['display'] ) && isset( $post_data['operator'] ) ){
    update_option( 'jpress_acs_options', $post_data );
    jpress_refresh_transient();
  }
}

$acs_options = get_option( 'jpress_acs_options' );
$acs_settings = get_option( 'jpress_acs_settings' );

?>

<form method="post" action="options-general.php?page=admin-column-search&tab=configuration" id="acs-cpt">
      <div class="acs-tabs">
        <ul>
        <?php
        $first = true;
        foreach ( $posts_types as $pt ):
          if ( in_array( $pt, array(
            'revision',
            'attachment',
            'nav_menu_item',
          ) ) ){
            continue;
          }
          $posttype = get_post_type_object( $pt );
          ?>
          <li><a href="#tabs-<?php echo $pt ;?>"><?php echo $posttype->labels->name ;?></a></li>
          <?php $first = false;
        endforeach; ?>
</ul>

<?php
$taxonomies = get_taxonomies();
foreach ( $posts_types as $pt ):
  if ( in_array( $pt, array(
    'revision',
    'attachment',
    'nav_menu_item',
  ) ) ){
    continue;
  }

  $posttype = get_post_type_object( $pt );
  $columns = get_column_headers( "edit-{$pt}" );
  $table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => $pt ) );
  $columns = array_filter( array_merge( $columns, $table->get_columns() ) );

  //compatibility with codepress admin column
  if ( $stored_columns = get_option( "cpac_options_{$pt}" ) ){
    $stored_columns = array_combine( array_keys( $stored_columns ), array_map( create_function( '$item', 'return $item["label"];' ), $stored_columns ) );
    $columns = array_filter( array_merge( $columns, $stored_columns ) );
  }

  unset( $columns['cb'] );
  unset( $columns['comments'] );

  $metakey = $wpdb->get_col(
    "SELECT DISTINCT pm.meta_key FROM {$wpdb->prefix}postmeta  AS pm
    INNER JOIN {$wpdb->prefix}posts AS p ON p.ID = pm.post_id
    WHERE p.post_type = '" . $pt . "' " .
    ( ( $acs_settings['show_hidden'] == 1 ) ? "" : "AND pm.meta_key NOT LIKE '\_%' " ) .
    "ORDER BY pm.meta_key"
  );

  ?>
  <div id="tabs-<?php echo $pt;?>">
    <div class="columns-left">
      <div id="titlediv">
        <h2><?php echo $posttype->labels->name ;?><a href="<?php echo admin_url( '/edit.php?post_type=' ) . $pt ;?>" class="add-new-h2"><?php echo __("View", "jpress-admin-column-search" );?></a></h2>
      </div>
    </div>

    <div class="inside">
      <label><input type="checkbox" name="enable[]" value="<?php echo esc_attr( $pt );?>" <?php if ( isset( $acs_options['enable'] ) && in_array( $pt, $acs_options['enable'] ) ) { echo 'checked'; }?>>  <?php echo __("Enable Admin Column Search", "jpress-admin-column-search" );?></label><br><br>

      <div id="side-sortables" class="acs-accordeon accordion-container">
        <ul class="outer-border">
          <?php foreach ( $columns as $k => $column) : ?>
            <li class="control-section accordion-section top">
              <h4 class="accordion-section-title hndle" tabindex="0">
                <span class="acs_column_sort"></span>
                <span class="acs_column_title"><?php echo $column;?></span>
              </h4>
              <div class="accordion-section-content " style="display: none;">
                <div class="inside">
                  <table class="acs-table widefat">
                    <tbody class="taxbody ui-sortable">
                    <tr>
                      <td>
                        <label><?php echo __("Type", "jpress-admin-column-search" );?></label>
                      </td>
                      <td>
                        <select name="type[<?php echo $pt;?>][<?php echo $k;?>]" class="acs-select-type">
                          <option value=""><?php echo __("None", "jpress-admin-column-search" );?></option>
                          <?php
                          $options = array(
                            'basic-field' => __("Basic field", "jpress-admin-column-search" ),
                            'taxonomy' => __("Taxonomy", "jpress-admin-column-search" ),
                            'custom-field' => __("Custom field", "jpress-admin-column-search" ),
                          )
                          ?>
                          <?php jpress_acs_render_select( $options, $acs_options['type'][$pt][$k]);?>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <label><?php echo __("Field", "jpress-admin-column-search" );?></label>
                      </td>
                      <td>
                        <select name="field[<?php echo $pt;?>][<?php echo $k;?>]" class="acs-field-select">
                          <option value=""><?php echo __("None", "jpress-admin-column-search" );?></option>
                          <optgroup data-type="basic-field" label="<?php echo __("Basic columns", "jpress-admin-column-search");?>" <?php if ( isset( $acs_options['type'][$pt] ) && $acs_options['type'][$pt][$k] == 'basic-field' ) { echo 'style="display:block;"'; } ?> >
                            <?php
                            //compatibilité jcpt create post table
                            global $wpdb;
                            $fields = $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}{$pt}s" );
                            if( ! $fields ) {
                              $fields = $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}posts" );
                            }
                            if( $fields ) {
                              $options = array_map( create_function( '$item', 'return $item->Field;' ), $fields );
                              jpress_acs_render_select( $options, $acs_options['field'][$pt][$k], true);
                            }
                            ?>
                          </optgroup>
                          <optgroup data-type="taxonomy" label="<?php echo __("Taxonomies", "jpress-admin-column-search");?>" <?php if ( isset( $acs_options['type'][$pt] ) && $acs_options['type'][$pt][$k] == 'taxonomy' ) { echo 'style="display:block;"'; } ?> >
                            <?php
                            jpress_acs_render_select( $taxonomies, $acs_options['field'][$pt][$k], true);?>
                          </optgroup>
                          <optgroup data-type="custom-field" label="<?php echo __("Custom fields", "jpress-admin-column-search");?>" <?php if ( isset( $acs_options['type'][$pt] ) && $acs_options['type'][$pt][$k] == 'custom-field' ) { echo 'style="display:block;"'; } ?> >
                            <?php jpress_acs_render_select( $metakey, $acs_options['field'][$pt][$k], true);?>
                          </optgroup>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <label><?php echo __("Display", "jpress-admin-column-search" );?></label>
                      </td>
                      <td>
                        <select name="display[<?php echo $pt;?>][<?php echo $k;?>]" class="acs-display-select">
                          <option value=""><?php echo __("None", "jpress-admin-column-search" );?></option>
                          <?php
                          $options = array(
                            'free-search' => __("Free search", "jpress-admin-column-search" ),
                            'selection' => __("Selection", "jpress-admin-column-search" ),
                            'true-false' => __("True / False", "jpress-admin-column-search" ),
                            'date-picker' => __("Date picker", "jpress-admin-column-search" ),
                            'multiple' => __("Multiple", "jpress-admin-column-search" ),
                          );
                          ?>
                          <?php jpress_acs_render_select( $options, $acs_options['display'][$pt][$k]);?>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <label><?php echo __("Operator", "jpress-admin-column-search" );?></label>
                      </td>
                      <td>
                        <select name="operator[<?php echo $pt;?>][<?php echo $k;?>]" class="acs-operator-select">
                          <option value=""><?php echo __("None", "jpress-admin-column-search" );?></option>
                          <?php
                          $options = array(
                            '=',
                            'IN',
                            'LIKE',
                            '>',
                            '<',
                            '>=',
                            '<=',
                          );
                          ?>
                          <?php jpress_acs_render_select( $options, $acs_options['operator'][$pt][$k], true);?>
                        </select>
                      </td>
                    </tr>

                    </tbody>

                  </table>

                </div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>


    </div>

  </div>

<?php endforeach; ?>

</div>
<br>
<input type="submit" class="button-primary" name="acssubmit" value="<?php echo __("Save", "jpress-admin-column-search" );?>">
</form>