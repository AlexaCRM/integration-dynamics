"use strict";
( function( $ ) {
    var LookupDialog = function( $dialog ) {
        var ld = this, $target;

        // Close the dialog.
        $dialog.on( 'click.wpcrm-close', '.crm-popup-cancel, .crm-popup-add-button, .crm-popup-cancel-button', function( e ) {
            e.preventDefault();

            ld.closeDialog();
        } );

        // Select a row.
        $dialog.on( 'click.wpcrm-row', '.body-row', function( e ) {
            $dialog.find( '.body-row' ).removeClass( 'selected-row' );
            $( this ).addClass( 'selected-row' );
        } );

        // Associate the selected record with the field.
        $dialog.on( 'click.wpcrm-associate', '.crm-popup-add-button', function( e ) {
            var $selectedRow = $dialog.find( '.selected-row' ), entityName, recordId, displayName;

            entityName = $dialog.find( '.crm-lookup-lookuptype' ).val();
            recordId = $selectedRow.data( 'entityid' );
            displayName = $selectedRow.data( 'name' );

            // Set the new record display name to the UI control.
            $target.find( '.wpcrm-lookup-display' ).val( displayName );

            // Set the new record ID to the field input.
            $target.find( '~ input[type=hidden]' ).val( JSON.stringify( { 'LogicalName': entityName, 'Id': recordId, 'DisplayName': displayName } ) );

            $target.find( 'button[data-action=associate]' )
                .attr( 'data-action', 'disassociate' )
                .find( 'i.fa' )
                    .removeClass( 'fa-search' ).addClass( 'fa-times' );
        } );

        // Change the searched entity.
        $dialog.on( 'change.wpcrm-type', '#wpcrmLookupType', function() {
            ld.setPageNumber( 1 );
            ld.setPagingCookie( null );

            ld.retrieveRecords();
        } );

        // First page.
        $dialog.on( 'click.wpcrm-first', '.crm-lookup-popup-first-page', function() {
            ld.setPageNumber( 1 );
            ld.setPagingCookie( null );

            ld.retrieveRecords();
        } );

        // Previous page.
        $dialog.on( 'click.wpcrm-prev', '.crm-lookup-popup-prev-page', function() {
            ld.setPageNumber( ld.getPageNumber() - 1 );
            ld.setPagingCookie( null );

            ld.retrieveRecords();
        } );

        // Next page.
        $dialog.on( 'click.wpcrm-next', '.crm-lookup-popup-next-page', function() {
            ld.setPageNumber( ld.getPageNumber() + 1 );

            ld.retrieveRecords();
        } );

        // Start searching.
        $dialog.on( 'click.wpcrm-search', '.crm-lookup-searchfield-button', function() {
            ld.searchRecords();
        } );
        $dialog.on( 'keypress.wpcrm-enter', '#wpcrmLookupSearchField', function( e ) {
            if ( e.which !== 13 ) {
                return;
            }

            if ( $( this ).val().trim() === '' ) {
                $dialog.find( '.crm-lookup-searchfield-delete-search' ).hide();
                $dialog.find( '.crm-lookup-searchfield-button' ).show();
                $dialog.find( '#wpcrmLookupSearchField' ).val( '' );

                ld.retrieveRecords();

                return;
            }

            ld.searchRecords();
        } );

        $dialog.on( 'click.wpcrm-delete-search', '.crm-lookup-searchfield-delete-search', function() {
            $dialog.find( '.crm-lookup-searchfield-delete-search' ).hide();
            $dialog.find( '.crm-lookup-searchfield-button' ).show();
            $dialog.find( '#wpcrmLookupSearchField' ).val( '' );

            ld.retrieveRecords();
        } );

        /**
         * Returns the current page number.
         *
         * @returns {number}
         */
        ld.getPageNumber = function() {
            return $dialog.find( '.crm-lookup-popup-page-counter' ).text() - 0;
        };

        /**
         * Updates the current page number with the given value.
         *
         * @param pageNumber
         */
        ld.setPageNumber = function( pageNumber ) {
            $dialog.find( '.crm-lookup-popup-page-counter' ).text( pageNumber );
        };

        /**
         * Returns the paging cookie.
         */
        ld.getPagingCookie = function() {
            return $dialog.attr( 'data-cookie' );
        };

        /**
         * Updates the current paging cookie with the given value.
         *
         * @param cookie
         */
        ld.setPagingCookie = function( cookie ) {
            if ( !cookie ) {
                $dialog.removeAttr( 'data-cookie' );

                return;
            }

            $dialog.attr( 'data-cookie', cookie );
        };

        /**
         * Retrieves the records and renders the result.
         *
         * @returns {*}
         */
        ld.retrieveRecords = function() {
            var pageNumber, pagingCookie;

            $dialog.find( '.crm-lookup-popup-body-loader' ).fadeIn();

            pageNumber = ld.getPageNumber();
            pagingCookie = ld.getPagingCookie();

            return $.ajax( {
                'url': wpcrm.ajaxurl,
                'data': {
                    'action': 'retrieve_lookup_request',
                    'lookupType': $dialog.find( '#wpcrmLookupType' ).val(),
                    'pagingCookie': pagingCookie,
                    'pageNumber': pageNumber
                }
            } )
                .done( function( data ) {
                    $dialog.find( '.crm-lookup-body-grid' ).html( data.data );
                    $dialog.find( '.body-row' ).first().addClass( 'selected-row' );
                    ld.setPagingCookie( data.pagingcookie );

                    $dialog
                        .find( '.crm-lookup-popup-next-page, .crm-lookup-popup-prev-page, .crm-lookup-popup-first-page' )
                        .attr( 'disabled', 'disabled' );

                    if ( data.morerecords === '1' ) {
                        $dialog.find( '.crm-lookup-popup-next-page' ).removeAttr( 'disabled' );
                    }

                    if ( pageNumber > 1 ) {
                        $dialog.find( '.crm-lookup-popup-prev-page, .crm-lookup-popup-first-page' ).removeAttr( 'disabled' );
                    }
                } )
                .always( function() {
                    $dialog.find( '.crm-lookup-popup-body-loader' ).fadeOut();
                } );
        };

        /**
         * Searches the records.
         *
         * @returns {*}
         */
        ld.searchRecords = function() {
            $dialog.find( '.crm-lookup-popup-body-loader' ).fadeIn();

            ld.setPageNumber( 1 );
            ld.setPagingCookie( null );

            return $.ajax( {
                'url': wpcrm.ajaxurl,
                'data': {
                    'action': 'search_lookup_request',
                    'lookupType': $dialog.find( '#wpcrmLookupType' ).val(),
                    'searchstring': encodeURIComponent( $dialog.find( '#wpcrmLookupSearchField' ).val() )
                }
            } )
                .done( function( data ) {
                    $dialog.find( '.crm-lookup-body-grid' ).html( data );
                    $dialog.find( '.body-row' ).first().addClass( 'selected-row' );

                    $dialog
                        .find( '.crm-lookup-popup-next-page, .crm-lookup-popup-prev-page, .crm-lookup-popup-first-page' )
                        .attr( 'disabled', 'disabled' );

                    $dialog.find( '.crm-lookup-searchfield-button' ).hide();
                    $dialog.find( '.crm-lookup-searchfield-delete-search' ).show();
                } )
                .always( function() {
                    $dialog.find( '.crm-lookup-popup-body-loader' ).fadeOut();
                } );
        };

        /**
         * Opens the dialog.
         */
        ld.openDialog = function() {
            $dialog.fadeIn();

            if ( !$target ) {
                return;
            }

            ld.retrieveRecords();
        };

        /**
         * Closes the dialog.
         */
        ld.closeDialog = function() {
            $dialog.fadeOut();

            $dialog
                .off('click.wpcrm-close')
                .off('click.wpcrm-row')
                .off('click.wpcrm-associate')
                .off('change.wpcrm-type')
                .off('click.wpcrm-first')
                .off('click.wpcrm-prev')
                .off('click.wpcrm-next')
                .off('click.wpcrm-search')
                .off('keypress.wpcrm-enter')
                .off('click.wpcrm-delete-search');
        };

        /**
         * Sets the lookup field target.
         *
         * @param $newTarget
         */
        ld.setTarget = function( $newTarget ) {
            var lookupTypes;

            $target = $newTarget;
            $dialog.find( '#wpcrmLookupType option' ).remove();

            lookupTypes = $target.data( 'types' );
            $.each( lookupTypes, function( entityName, label ) {
                $dialog.find( '#wpcrmLookupType' ).append( '<option value="' + entityName + '">' + label + '</option>' );
            } );

            ld.setPageNumber( 1 );
            ld.setPagingCookie( null );
        };

        /**
         * Disassociates the record from the field.
         */
        ld.disassociate = function() {
            var $display = ld.getDisplayInput(), $value = ld.getValueInput();

            $display.val( '' );
            $value.val( JSON.stringify( { 'LogicalName': null, 'Id': null, 'DisplayName': null } ) );
        };

        /**
         * Retrieves the input field that displays the currently associated record.
         */
        ld.getDisplayInput = function() {
            return $target.find( '.wpcrm-lookup-display' );
        };

        /**
         * Retrieves the input field that stores the associated record.
         */
        ld.getValueInput = function() {
            return $target.find( '~ input[type=hidden]' );
        };
    };

    $( function() {

        $( '.crm-datepicker' ).datetimepicker( {
            timepicker: false,
            format: window.wpcrm.dateformat,
            scrollInput: true
        } );

        $( '.crm-datetimepicker' ).datetimepicker( {
            format: window.wpcrm.datetimeformat,
            scrollInput: false
        } );

        $( '.wpcrm-lookup' ).each( function() {
            var $lookupContainer = $( this );

            $lookupContainer.on( 'click', 'button[data-action=associate]', function() {
                var $lookupDialog = $(this).closest("form").parent().find('#wpcrmLookupDialog');
                var ld = new LookupDialog( $lookupDialog );
                ld.setTarget( $lookupContainer );
                ld.openDialog();
            } );
            $lookupContainer.on( 'click', 'button[data-action=disassociate]', function() {
                var $lookupDialog =  $(this).closest("form").parent().find('#wpcrmLookupDialog');
                var ld = new LookupDialog( $lookupDialog );
                ld.setTarget( $lookupContainer );
                ld.disassociate();
                $( this ).attr( 'data-action', 'associate' );
                $( this ).find( 'i.fa-times' ).removeClass( 'fa-times' ).addClass( 'fa-search' );
            } );
        } );
    } );
}( jQuery ) );
