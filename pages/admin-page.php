<?php
/**
 * admin page
 */
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"></div>
    <h2><?php echo __('Admin Column Search', 'jpress-admin-column-search');?></h2>
    <br><br>

    <?php $active_tab = isset( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : 'configuration';?>
    <h2 class="nav-tab-wrapper">
      <a href="options-general.php?page=admin-column-search&tab=configuration" class="nav-tab <?php if ( $active_tab == 'configuration' ) : ?>nav-tab-active<?php endif;?>"><?php echo __('Configuration', 'jpress-admin-column-search');?></a>
      <a href="options-general.php?page=admin-column-search&tab=settings" class="nav-tab <?php if ( $active_tab == 'settings' ) : ?>nav-tab-active<?php endif;?>"><?php echo __('Settings', 'jpress-admin-column-search');?></a>
    </h2>

    <?php
    if ( is_file( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tab-' . $active_tab . '.php' ) ) {
      include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tab-' . $active_tab . '.php';
    }
    ?>

</div>