jQuery( document ).ready( function() {

    jQuery( ".acs-sortable" ).sortable();
    jQuery( ".acs-sortable" ).disableSelection();
    jQuery( ".acs-tabs" ).tabs();

    jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ "fr" ] );
    jQuery( ".acs_datepicker" ).datepicker({
        dateFormat : 'yy-mm-dd',
        altField : '',
        altFormat :  'yy-mm-dd',
        changeYear: true,
        yearRange: "-100:+100",
        changeMonth: true,
        showButtonPanel : true,
        firstDay: 1
    });

    jQuery( ".acs_multiselect" ).multiselect({
        noneSelectedText: 'Select',
        selectedText: '# selected'
    });

    //do submit on press enter key
    jQuery( '#posts-filter' ).keyup( function( e ) {
        if( e.keyCode == 13 ) { // Enter button KeyCode
            jQuery( '.acs_search_submit' ).click();
        }
    });

    if( jQuery( '.wp-list-table thead' ).find( '.acs_input_cible' ).length > 0 ) {
        var td = '';
        var classes;
        for ( i = 1; i <= jQuery( '.wp-list-table thead tr th, .wp-list-table thead tr td' ).length; i++ ) {
            classes = jQuery( '.wp-list-table thead tr th, .wp-list-table thead tr td' ).eq( i-1 ).attr( 'class' );
            classes = classes.match( /column-([a-z0-9_-]+)/ );
            td += '<td class="column-' + classes[1] + '"></td>';
        }
        jQuery( '<tr class="acs_row">' + td + '</tr>' ).insertBefore( jQuery( '.wp-list-table tbody tr:first' ) );
        jQuery( '.wp-list-table thead tr th,.wp-list-table thead tr td' ).each( function( index ) {
            if( jQuery( this ).find( '.acs_input_cible' ).length > 0 ) {
                column = jQuery(this).find( '.acs_input_cible' ).data( 'col' );
                input_form = jQuery(this).find( '.acs_input_cible > *' );
                jQuery( '.wp-list-table tbody tr:first td' ).eq( index ).append( input_form );
            }
        } );
        jQuery( '.acs_input_cible' ).remove();
        jQuery( '.wp-list-table tbody tr:first td:first' ).append( '<div class="acs_search_wrap"><input type="submit" id="acs_search" name="acs_search_submit" value="Go" title="Search" class="acs_search_submit button-secondary"/></div>' );

        /*remove default wp category dropbox to fix conflict*/
        jQuery( '#cat' ).remove();
    }



    //admin page
    jQuery( '.acs-select-type' ).change( function() {
        _val = jQuery( this ).find( '> option:selected' ).val();
        _parent = jQuery( this ).parents( 'table' ).find( '.acs-field-select optgroup' ).hide();
        _parent = jQuery( this ).parents( 'table' ).find( '.acs-field-select optgroup[data-type=' + _val + ']' ).show();
        _parent = jQuery( this ).parents( 'table' ).find( '.acs-field-select option' ).eq( 0 ).attr( 'selected', 'selected' );
    } )

} );