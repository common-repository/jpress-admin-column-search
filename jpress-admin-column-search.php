<?php
/*
 * Plugin Name: jPress Admin Column Search
 * Plugin URI:
 * Text Domain: jpress-admin-column-search
 * Description: Add an advanced data search and filter on admin page list content for each declared columns
 * Author: Johary Ranarimanana (Netapsys)
 * Author URI: http://www.netapsys.fr/
 * Version: 1.1.1
 * License: GPLv2 or later
 * Domain Path: /languages/
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//add an admin page menu
add_action( 'admin_menu', 'jpress_acs_add_custom_admin_page' );
function jpress_acs_add_custom_admin_page () {
  add_submenu_page( 'options-general.php', __('Admin Column Search', 'jpress-admin-column-search'), __('Admin Column Search', 'jpress-admin-column-search'), 'manage_options', 'admin-column-search', 'jpress_acs_admin_page' );
}

//plugin register traduction
add_action( 'plugins_loaded', 'jpress_acs_plugins_loaded' );
function jpress_acs_plugins_loaded() {
  //localisation
  load_plugin_textdomain( 'jpress-admin-column-search', false, dirname(plugin_basename(__FILE__)).'/languages/' );
}

//admin page callback
function jpress_acs_admin_page () {
  include( 'pages/admin-page.php' );
}

//admin  init action
add_action('admin_init', 'jpress_acs_init');
function jpress_acs_init () {
  //manage columns values for all post type
  $post_types = get_post_types();
  foreach ( $post_types as $pt ) {
    add_filter( 'manage_edit-' . $pt . '_columns', 'jpress_acs_manage_columns', 555 );
  }

  //admin styles
  wp_enqueue_style( 'jpress-acs-style', plugins_url( '/assets/css/acs-styles.css', __FILE__ ) );
  wp_enqueue_style( 'jpress-acs-ui', plugins_url( '/assets/css/acs-ui.css', __FILE__ ) );
  wp_enqueue_style( 'jpress-acs-date-picker', plugins_url( '/assets/css/jquery-ui-datepicker.css', __FILE__ ) );
  wp_enqueue_style( 'jpress-acs-multiselect', plugins_url( '/assets/css/jquery.multiselect.css', __FILE__ ) );

  //admin script
  wp_enqueue_script( 'jpress-acs-script', plugins_url( '/assets/js/acs-script.js', __FILE__ ), array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-tabs' ) );
  wp_enqueue_script( 'jpress-acs-date-picker', plugins_url( '/assets/js/jquery.ui.datepicker.js', __FILE__ ), array( 'jquery' ) );
  wp_enqueue_script( 'jpress-acs-date-picker-fr', plugins_url( '/assets/js/jquery.ui.datepicker-fr.js', __FILE__ ), array( 'jquery' ) );
  wp_enqueue_script( 'jpress-acs-multiselect', plugins_url( '/assets/js/jquery.multiselect.js', __FILE__ ), array( 'jquery' ) );

}
//callback for manage columns values for all post type
function jpress_acs_manage_columns ( $columns ) {
  global $current_screen;
  $pt = $current_screen->post_type;
  $acs_options = get_option( 'jpress_acs_options' );

  //if options is not loaded
  if ( !$acs_options )
  	return $columns;
  
  //if admin search column is not active for the post type
  if ( ! is_array( $acs_options['enable'] ) || ! in_array( $pt, $acs_options['enable'] ) )
    return  $columns;

  if ( isset( $acs_options['type'][$pt] ) ) {
    $input_form = jpress_acs_input_column();
    foreach ( $columns as $k => $col ) {
      if ( ! empty( $acs_options['type'][$pt][$k] ) ) {
        $columns[$k] = $columns[$k] . '<div style="display:none;" class="acs_input_cible" data-col="' . $k . '">' . $input_form[$k] . '</div>';
      }
    }
  }
  return $columns;
}

//save post action
add_action( 'save_post', 'jpress_acs_save_post' );
function jpress_acs_save_post ( $post_id ) {
  jpress_refresh_transient();
}
function jpress_refresh_transient () {
  global $wpdb;
  //delete transient on content update
  $wpdb->query( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%acs_input_column_%'" );
}

function jpress_acs_render_select( $options, $selected, $has_no_key = false, $echo = true, $args = array() ){
  $html = '';
  foreach ( $options as $value => $label ){
    $value = ($has_no_key) ? $label : $value ;
    if ( isset( $args['key'] ) ){
      $value = $label->$args['key'];
    }
    if ( isset( $args['value'] ) ){
      $label = $label->$args['value'];
    }

    $value = html_entity_decode( $value, ENT_QUOTES, 'utf-8' );
    if ( empty( $label ) ) continue;

    if ( is_array( $selected ) ){
      $html .= '<option value="' . esc_attr( $value ) . '" ' . ( in_array( $value, $selected ) ? 'selected' : '' ) . '>' . esc_html( $label ) . '</option>' ;
    } else {
      $html .= '<option value="' . esc_attr( $value ) . '" ' . ( ( isset($selected) && !empty( $selected ) && $selected == $value ) ? 'selected' : '' ) . '>' . esc_html( $label ) . '</option>' ;
    }
  }
  if ( $echo ) {
    echo $html;
  } else {
    return $html;
  }
}

//manage input field column search
function jpress_acs_input_column () {
	global $current_screen, $wpdb;
  $pt = $current_screen->post_type;
  $acs_options = get_option( 'jpress_acs_options' );
  $acs_settings = get_option( 'jpress_acs_settings' );

  if ( ! isset( $pt ) ){
    return false;
  }

  if ( ! isset( $acs_options['enable'] ) || empty( $acs_options['enable'] ) ) {
    return false;
  }

  //check if enable
  if ( ! in_array( $pt, $acs_options['enable'] ) ) {
    return false;
  }

  //use transient to load input data
  //$transient_name = 'acs_input_column_' . $pt;
	//$inputform = get_transient( $transient_name );
	//if ( $inputform ) return $inputform;

  $inputform = array();
  foreach ( $acs_options['type'][$pt] as $column_name => $type ) {
    //check if column not enable
    if ( empty( $type ) ) continue;

    //load column search info
    $field = isset( $acs_options['field'][$pt][$column_name] ) ? $acs_options['field'][$pt][$column_name] : '';
    //check if field not enable
    if ( empty( $field ) ) continue;

    $display = isset( $acs_options['display'][$pt][$column_name] ) ? $acs_options['display'][$pt][$column_name] : 'free-search';
    $operator = isset( $acs_options['operator'][$pt][$column_name] ) ? $acs_options['operator'][$pt][$column_name] : '=';

    //current search value
    $current_value = '';
    if ( isset( $_GET['acs_search'] ) ) {
      $current_value = esc_attr( $_GET['acs_search'][$column_name] );
    }

    $html = '';
    switch ( $display ){
      case 'free-search' :
        $html = '<input type="text" name="acs_search[' . $column_name . ']" class="acs_input" value="' . $current_value . '"/>';
        break;

      case 'selection' :
      case 'multiple' :
        $html = '<select name="acs_search[' . $column_name . ']' . ( ( $display == 'multiple' ) ? '[]' : '' ) . '" class="acs_select ' . ( ( $display == 'multiple' ) ? 'acs_multiselect' : '' ) . '" ' . ( ( $display == 'multiple' ) ? 'multiple' : '' ) . '><option value="">' . __( "None", "jpress-admin-column-search" ) . '</option>';
        switch ( $type ) {
          case 'basic-field' :
            //compatibility with jcpt plugins
            $table = $wpdb->posts;
            if ( function_exists( 'jcpt_whereis' ) ) {
              global $jcpt_options;
              if ( is_null( $jcpt_options ) ) {
                $jcpt_options = get_option( 'jcpt_options' );
              }
              if ( in_array( $pt, $jcpt_options['enable'] ) ) {
                $table = $wpdb->prefix . $pt . 's';
              }
            }
            if ( $field == 'post_author' ) {
              $sql = "SELECT DISTINCT p.{$field}, u.display_name FROM {$table} as p INNER JOIN {$wpdb->users} as u ON p.post_author = u.ID WHERE p.post_type = %s";
              if ( $acs_settings['use_transient'] == 1 ) {
                $options = jpress_acs_get_options( 'get_results', $sql, $pt, $column_name );
              } else {
                $options = $wpdb->get_results( $wpdb->prepare( $sql, $pt ) );
              }
              $html .= jpress_acs_render_select( $options, $current_value, false, false, array( 'key' => 'post_author', 'value' => 'display_name' ) );
            } else {
              $sql = "SELECT DISTINCT {$field} FROM {$table} WHERE post_type = %s";
              if ( $acs_settings['use_transient'] == 1 ) {
                $options = jpress_acs_get_options( 'get_col', $sql, $pt, $column_name );
              } else {
                $options = $wpdb->get_col( $wpdb->prepare( $sql, $pt ) );
              }
              $html .= jpress_acs_render_select( $options, $current_value, true, false );
            }
            break;
          case 'taxonomy' :
            $terms = get_terms(
              $field,
              array(
                'orderby' => 'name',
                'order' => 'ASC',
                'hide_empty' => false
              )
            );
            $html .= jpress_acs_render_select( $terms, $current_value, true, false, array( 'key' => 'term_id', 'value' => 'name' ) );
            break;
          case 'custom-field' :
            $sql = "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} AS pm
            INNER JOIN {$wpdb->posts} AS p ON ( p.ID = pm.post_id )
            WHERE pm.meta_key = %s
            AND p.post_type = %s
            ORDER BY pm.meta_value ASC";
            if ( $acs_settings['use_transient'] == 1 ) {
              $options = jpress_acs_get_options( 'get_col', $sql, $pt, $column_name );
            } else {
              $options = $wpdb->get_col( $wpdb->prepare( $sql, $field, $pt ) );
            }
            $html .= jpress_acs_render_select( $options, $current_value, true, false );
            break;
        }
        $html .= '</select>';
        break;

      case 'date-picker' :
        $html = '<input type="text" name="acs_search[' . $column_name . ']" placeholder="YYYY-MM-DD" readonly class="acs_input acs_datepicker" value="' . $current_value . '"/>';
        break;

      case 'true-false' :
        $html = '<select name="acs_search[' . $column_name . ']" class="acs_select"><option value="">' . __( "None", "jpress-admin-column-search" ) . '</option>';
        $options = array(
          '1' => __( "True", "jpress-admin-column-search" ),
          '0' => __( "False", "jpress-admin-column-search" )
        );
        $html .= jpress_acs_render_select( $options, $current_value, false, false );
        $html .= '</select>';
        break;

      default :
        break;
    }

    $inputform[$column_name] = $html;

  }

  return $inputform;
}

function jpress_acs_get_options( $func, $sql, $pt, $col){
  global $wpdb;

  $trans = get_transient( 'acs_input_column_' . $pt . '_' . $col );
  if ( $trans ) {
    return $trans;
  } else {
    $results = $wpdb->$func( $wpdb->prepare( $sql ) );
    set_transient( 'acs_input_column_' . $pt . '_' . $col, $results );
    return $results;
  }
}

function jpress_sanitize_all( $data ){

  // Initialize the new array that will hold the sanitize values
  $new_input = array();
  // Loop through the input and sanitize each of the values
  foreach ( $data as $key => $val ) {
    if ( is_array( $data[$key] ) ){
      $new_input[$key] = jpress_sanitize_all( $data[$key] );
    } else {
      $new_input[$key] = sanitize_text_field( $val );
    }
  }
  return $new_input;
}

//add filter form
if ( is_admin() ) add_filter( 'parse_query', 'acs_admin_posts_filter' );
function acs_admin_posts_filter ( $query ) {
  global $current_screen, $pagenow;
  $pt = $current_screen->post_type;
  $acs_options = get_option( 'jpress_acs_options' );
  if ( $pagenow == 'edit.php' && is_admin() && isset( $_GET['acs_search_submit'] ) && isset( $_GET['acs_search'] ) ) {
  	if ( ! isset( $query->query['post_type'] ) || $query->query['post_type'] != $pt ) return $query;
    foreach ( $_GET['acs_search'] as $k => $v) {
      if ( $v == "" ) continue;

      $type = $acs_options['type'][$pt][$k];
      if ( empty( $type ) ) continue;

      $field = $acs_options['field'][$pt][$k];
      if ( empty( $field ) ) continue;

      $display = isset( $acs_options['display'][$pt][$k] ) ? $acs_options['display'][$pt][$k] : 'free-search';
      $operator = isset( $acs_options['operator'][$pt][$k] ) ? $acs_options['operator'][$pt][$k] : '=';

      switch ( $type ){
        case 'basic-field' :
          if ( $field == 'post_title' || $field == 'post_content' || $field == 'post_excerpt' ){
            $v = htmlentities( $v, ENT_QUOTES, 'utf-8' );
            $query->query_vars['s'] = $v;
          } else if ( $field == 'post_author' ) {
            if ( $display == 'multiple' ){
              $query->query_vars['author__in'] = $v;
            } else {
              $query->query_vars['author'] = $v;
            }
          } else if (
            $field == 'post_date' ||
            $field == 'post_date_gmt' ||
            $field == 'post_modified' ||
            $field == 'post_modified_gmt'
          ) {
            if ( preg_match( '!([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})!', $v, $matches ) ){
              list( $date, $year, $month, $day, $hour, $minute, $second ) = $matches;
              $query->query_vars['date_query'] = array(
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'hour' => $hour,
                'minute' => $minute,
                'second' => $second,
                'column' => $field,
                'compare' => $operator
              );
            } else if ( preg_match( '!([0-9]{4})-([0-9]{2})-([0-9]{2})!', $v, $matches ) ) {
              list( $date, $year, $month, $day ) = $matches;
              $query->query_vars['date_query'] = array(
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'column' => $field,
                'compare' => $operator
              );
            }
          } else {
            $query->query_vars[$field] = $v;
          }
          break;

        case 'taxonomy' :
          if ( is_array( $v ) ) $v = array_filter( $v );
          if ( empty( $v ) ) continue;

          $tq = array (
            'taxonomy' => $field,
            'terms' => $v,
            'include_children' => true
          );
          if ( $display == 'selection' ) {
            $tq['field'] = 'id';
          } elseif ( $display == 'multiple' ) {
            $tq['field'] = 'id';
            $tq['operator'] = 'IN';
          } else {
            $tq['field'] = 'name';
          }
          $query->query_vars['tax_query'][] = $tq;
          $query->query_vars['tax_query']['relation'] = 'AND';
          break;

        case 'custom-field' :
          $mq = array(
            'key' => $field,
            'value' => $v,
          );
          if ( $display == 'selection' ) {
            $mq['compare'] = '=';
          } else if ( $display == 'multiple' ) {
            $mq['compare'] = 'IN';
          } else {
            $mq['compare'] = $operator;
          }
          $query->query_vars['meta_query'][] = $mq;
          $query->query_vars['meta_query']['relation'] = 'AND';
          break;

        default :
          break;
      }
    }
  }

  return $query;
}

//plugin compatibility
//The event calendar column compatibility
/*meta query filter for event*/
add_filter( 'jpress_acs_meta_query_filter', 'tec_acs_meta_query_filter', 10, 3 );
function tec_acs_meta_query_filter ($mq, $mk,$mv ) {
	if ( is_admin() ) {
		if ( $mk == '_EventStartDate' ) {
      if ( strpos( $mv, '/' ) ) $mv = substr( $mv, 6, 4 ) . '-' . substr( $mv, 3, 2 ) . '-' . substr( $mv, 0, 2 );
			$mq = array(
        'key' => $mk,
        'value' => $mv,
        'type' => 'DATE',
        'compare' => '>='
      );
		} elseif ( $mk == '_EventEndDate' ) {
      if ( strpos( $mv, '/' ) ) $mv = substr( $mv, 6, 4 ) . '-' . substr( $mv, 3, 2 ) . '-' . substr( $mv, 0, 2 );
			$mq = array(
        'key' => $mk,
        'value' => $mv,
        'type' => 'DATE',
        'compare' => '<='
      );
		}
	}
	return $mq;
}

add_filter( 'parse_query', 'tec_acs_query' );
function tec_acs_query ( $q ) {
  if ( $q->query_vars['meta_key'] == 'start-date' ) {
    $q->query_vars['meta_key'] = '_EventStartDate';
  }
  if ( $q->query_vars['meta_key'] == 'end-date' ) {
    $q->query_vars['meta_key'] = '_EventEndDate';
  }
	return $q;
}