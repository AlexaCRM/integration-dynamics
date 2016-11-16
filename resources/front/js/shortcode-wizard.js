( function( $ ) {
    "use strict";

    /**
     * Shortcode definition
     *
     * Fields:
     * - name: string
     * - fields: ShortcodeFieldCollection
     */
    var Shortcode = Backbone.Model.extend( {

        initialize: function() {
            _.bind( this.generateCode, this );
        },

        generateCode: function() {
            var args = {}, shortcode = this;

            this.fields.forEach( function( field ) {
                args[field.get( 'name' )] = field.getFieldValue();
            } );

            this.view.startUpdatingResult();

            wp.ajax.post( 'wpcrm_sw_result', {
                name: this.get('name'),
                fields: args
            } )
                .done( function( response ) {
                    shortcode.view.updateResult( response.result );
                } )
                .fail( function( response ) {
                    shortcode.view.updateResult( 'Error: ' + response.message );
                } );
        }

    } );

    /**
     * Shortcode field definition
     */
    var ShortcodeField = Backbone.Model.extend( {

        initialize: function() {
            this.setFieldValue( null );

            var providingFields = this.get( 'value' ).args;
            if ( providingFields && providingFields.length ) {
                _.each( providingFields, function( fieldName ) {
                    var field = this.collection.findWhere( { name: fieldName } ),
                        dependingField = this;

                    if ( !field ) {
                        return;
                    }

                    field.on( 'change:controlValue', function() {
                        var args = {};
                        args[field.get( 'name' )] = field.getFieldValue();
                        dependingField.updatedProvidingField( args );
                    }, this )
                }, this );
            }

            _.bind( this.getView, this );
        },

        getValues: function() {
            var model = this, valueSettings, payload = {}, args = {};

            valueSettings = this.get( 'value' );
            if ( valueSettings.source !== 'api' ) {
                return []; //FIXME: return a promise anyway
            }

            if ( valueSettings.args && valueSettings.args.length ) {
                _.each( valueSettings.args, function( fieldName ) {
                    args[fieldName] = model.collection.findWhere( { name: fieldName } ).getFieldValue();
                }, this );
            }

            payload = {
                shortcode: this.collection.shortcode.get( 'name' ),
                field: this.get( 'name' ),
                values: args
            };

            return wp.ajax.post( 'wpcrm_sw_field', payload );
        },

        getFieldValue: function() {
            return this.get( 'controlValue' );
        },

        setFieldValue: function( value ) {
            this.set( 'controlValue', value );

            return this;
        },

        updatedProvidingField: function( updatedFields ) {
            this.getView().render();
        },

        getView: function() {
            if ( this.view ) {
                return this.view;
            }

            switch ( this.get( 'type' ) ) {
                case 'dropdown':
                    this.view = new ShortcodeDropdownFieldView( { model: this } );
                    break;
                default:
                    this.view = new ShortcodeFieldView( { model: this } );
                    break;
            }

            return this.view;
        }

    } );

    var ShortcodeFieldCollection = Backbone.Collection.extend( {
        model: ShortcodeField,

        initialize: function( attributes, options ) {
            var fields = this;

            if ( options && options.shortcode ) {
                this.shortcode = options.shortcode;
            }

            this.on( 'change:controlValue', function( field, value ) {
                this.shortcode.generateCode();
            } );
        }
    } );

    /**
     * Collection of shortcodes that should be available via ShortcodeWizard
     */
    var ShortcodeCollection = Backbone.Collection.extend( {
        model: Shortcode
    } );

    var shortcodes = new ShortcodeCollection();

    _.each( window.wpcrmShortcodeWizard.shortcodes, function( shortcodeDefinition, shortcodeName ) {
        var fields = new ShortcodeFieldCollection(), newShortcode;

        _.each( shortcodeDefinition.fields, function( fieldDefinition, fieldName ) {
            fields.add( new ShortcodeField( fieldDefinition, { collection: fields } ) );
        } );

        newShortcode = new Shortcode(
            _.extend( shortcodeDefinition ),
            { collection: shortcodes }
        );

        fields.shortcode = newShortcode;
        newShortcode.fields = fields;

        shortcodes.add( newShortcode );
    } );

    /**
     * Shortcode Wizard view.
     */
    var ShortcodeWizardView = Backbone.View.extend( {

        template: _.template( $( '#tpl-wpcrmShortcodeWizard' ).html() ),

        events: {
            'change .wpcrm-sw-selector': 'select'
        },

        render: function() {
            this.$el.html( this.template( { shortcodes: this.collection } ) );
            return this;
        },

        select: function() {
            var $selector = this.$el.find( '.wpcrm-sw-selector' ),
                $description = this.$el.find( '.wpcrm-sw-container .shortcode-description' ),
                selectedShortcode;

            if ( !$selector.val() ) {
                this.$el.find( '.wpcrm-sw-container' ).hide();
                return;
            }

            this.$el.find( '.wpcrm-sw-container' ).show();

            selectedShortcode = this.collection.findWhere( { name: $selector.val() } );

            if ( !selectedShortcode ) {
                return;
            }

            $description.find( '.name' ).text( selectedShortcode.get( 'displayName' ) );
            $description.find( '.description' ).text( selectedShortcode.get( 'description' ) );

            if ( !selectedShortcode.view ) {
                selectedShortcode.view = new ShortcodeView( { model: selectedShortcode } );
            }

            this.$el.find( '.wpcrm-sw-container .shortcode-container' ).append( selectedShortcode.view.render().$el );
        }

    } );

    /**
     * Shortcode view.
     */
    var ShortcodeView = Backbone.View.extend( {

        template: _.template( $( '#tpl-wpcrmShortcodeWizardShortcode' ).html() ),

        render: function() {
            this.$el.html( this.template( { fields: this.model.fields } ) );
            return this;
        },

        startUpdatingResult: function() {
            this.$el.find( '.shortcode-result textarea' ).val( wpcrmShortcodeWizardI18n['generating-shortcode'] );
        },

        updateResult: function( result ) {
            this.$el.find( '.shortcode-result' ).show().find( 'textarea' ).val( result );

            return this;
        }

    } );

    /**
     * Shortcode field view.
     */
    var ShortcodeFieldView = Backbone.View.extend( {

        template: _.template( $( '#tpl-wpcrmShortcodeWizardShortcodeField' ).html() ),

        errorTemplate: _.template( $( '#tpl-wpcrmShortcodeWizardShortcodeFieldError' ).html() ),

        loadingTemplate: _.template( $( '#tpl-wpcrmShortcodeWizardShortcodeFieldLoading' ).html() ),

        render: function() {
            this.$el.html( this.template( { field: this.model } ) );
            return this;
        }

    } );

    var ShortcodeDropdownFieldView = ShortcodeFieldView.extend( {

        template: _.template( $( '#tpl-wpcrmShortcodeWizardShortcodeDropdownField' ).html() ),

        events: {
            'change .dropdown-value': 'valueChange'
        },

        render: function() {
            var view = this;

            view.$el.html( view.loadingTemplate( { fieldName: view.model.get( 'displayName' ) } ) );

            this.model.getValues().done( function( values ) {
                view.$el.html( view.template( { field: view.model, values: values } ) );
            } )
                .fail( function( response ) {
                    view.$el.html( view.errorTemplate( { message: response.message } ) );
                } );

            return this;
        },

        valueChange: function() {
            this.model.setFieldValue( this.getValue() );
        },

        getValue: function() {
            return this.$el.find( '.dropdown-value' ).val();
        }

    } );

    $( function() {
        var wizardView = new ShortcodeWizardView( {
            collection: shortcodes
        } );

        $( '#wpcrmShortcodeWizard' ).append( wizardView.render().$el );
    } );

    // export
    window.wpcrm = _.extend( window.wpcrm || {}, {
        'Shortcode': Shortcode,
        'ShortcodeField': ShortcodeField,
        'ShortcodeCollection': ShortcodeCollection
    } );
} )( jQuery );
