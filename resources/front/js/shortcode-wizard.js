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
            this.generateCode = _.bind( this.generateCode, this );
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
            if ( this.get( 'value' ).source === 'static' ) {
                this.setFieldValue( this.get( 'value' ).values );
            }

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

            this.getView = _.bind( this.getView, this );
        },

        getValues: function() {
            var model = this, valueSettings, payload = {}, args = {};

            valueSettings = this.get( 'value' );
            if ( valueSettings.source === 'none' ) {
                return [];
            }

            if ( valueSettings.source === 'static' ) {
                return valueSettings.values;
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
                case 'number':
                    this.view = new ShortcodeNumberFieldView( { model: this } );
                    break;
                case 'hidden':
                    this.view = new ShortcodeHiddenFieldView( { model: this } );
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

        currentShortcode: null,

        template: _.template( $( '#tpl-wpcrmShortcodeWizard' ).html() ),

        events: {
            'change .wpcrm-sw-selector': 'select'
        },

        render: function() {
            this.$el.html( this.template( { shortcodes: this.collection } ) );
            return this;
        },

        select: function() {
            var $selector = this.$( '.wpcrm-sw-selector' ),
                $description = this.$( '.wpcrm-sw-container .shortcode-description' ),
                selectedShortcode;

            if ( !$selector.val() ) {
                this.$( '.wpcrm-sw-container' ).hide();
                this.currentShortcode = null;
                return;
            }

            this.$( '.wpcrm-sw-container' ).show();

            selectedShortcode = this.collection.findWhere( { name: $selector.val() } );

            if ( !selectedShortcode ) {
                return;
            }

            if ( this.currentShortcode ) {
                this.currentShortcode.view.$el.hide();
            }

            this.currentShortcode = selectedShortcode;

            $description.find( '.name' ).text( selectedShortcode.get( 'displayName' ) );
            $description.find( '.description' ).text( selectedShortcode.get( 'description' ) );

            if ( !selectedShortcode.view ) {
                selectedShortcode.view = new ShortcodeView( { model: selectedShortcode } );
                this.$( '.wpcrm-sw-container .shortcode-container' ).append( selectedShortcode.view.render().$el );
            } else {
                selectedShortcode.view.$el.show();
            }

        }

    } );

    /**
     * Shortcode view.
     */
    var ShortcodeView = Backbone.View.extend( {

        tagName: 'div',

        template: _.template( $( '#tpl-wpcrmShortcodeWizardShortcode' ).html() ),

        render: function() {
            this.$el.html( this.template( { fields: this.model.fields } ) );
            this.model.fields.forEach( function( field ) {
                this.$( '.shortcode-fields' ).append( field.getView().render().$el );
            }, this );
            return this;
        },

        startUpdatingResult: function() {
            this.$( '.shortcode-result textarea' ).val( wpcrmShortcodeWizardI18n['generating-shortcode'] );
        },

        updateResult: function( result ) {
            this.$( '.shortcode-result' ).show().find( 'textarea' ).val( result );

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

        events: {
            'change .value': 'valueChange'
        },

        render: function() {
            var view = this;

            view.$el.html( view.loadingTemplate( { fieldName: view.model.get( 'displayName' ) } ) );

            if ( typeof this.model.getValues().done === 'function' ) {
                this.model.getValues().done( function( values ) {
                    view.$el.html( view.template( { field: view.model, values: values } ) );
                } )
                    .fail( function( response ) {
                        view.$el.html( view.errorTemplate( { message: response.message } ) );
                    } );

                return this;
            }

            view.$el.html( view.template( { field: view.model, values: this.model.getValues() } ) );

            return this;
        },

        valueChange: _.debounce( function() {
            this.model.setFieldValue( this.getValue() );
        }, 300 ),

        getValue: function() {
            return this.$( '.value' ).val();
        }

    } );

    var ShortcodeHiddenFieldView = ShortcodeFieldView.extend( {
        events: {},

        render: function() {
            return this;
        },

        getValue: function() {
            return this.model.getValues();
        }
    } );

    var ShortcodeDropdownFieldView = ShortcodeFieldView.extend( {
        template: _.template( $( '#tpl-wpcrmShortcodeWizardShortcodeDropdownField' ).html() )
    } );

    var ShortcodeNumberFieldView = ShortcodeFieldView.extend( {
        template: _.template( $( '#tpl-wpcrmShortcodeWizardShortcodeNumberField' ).html() )
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
}( jQuery ) );
