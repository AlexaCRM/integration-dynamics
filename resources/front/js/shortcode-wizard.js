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

    } );

    /**
     * Shortcode field definition
     */
    var ShortcodeField = Backbone.Model.extend( {

    } );

    var ShortcodeFieldCollection = Backbone.Collection.extend( {
        model: ShortcodeField
    } );

    /**
     * Collection of shortcodes that should be available via ShortcodeWizard
     */
    var ShortcodeCollection = Backbone.Collection.extend( {
        model: Shortcode
    } );

    var ShortcodeWizardView = Backbone.View.extend( {

        template: _.template( $( '#tpl-wpcrmShortcodeWizard' ).html() ),

        render: function() {
            console.log( this.$el );
            this.$el.html( this.template() );
            return this;
        }

    } );

    var shortcodes = new ShortcodeCollection();

    _.each( window.wpcrmShortcodeWizard.shortcodes, function( shortcodeDefinition, shortcodeName ) {
        var fields = new ShortcodeFieldCollection();
        _.each( shortcodeDefinition.fields, function( fieldDefinition, fieldName ) {
            fields.add( new ShortcodeField( fieldDefinition ) );
        } );

        shortcodes.add( new Shortcode(
            _.extend( shortcodeDefinition, { fields: fields } )
        ) );
    } );

    $( function() {
        var wizardView = new ShortcodeWizardView( {
            collection: shortcodes
        } );

        $( '#wpcrmShortcodeWizard' ).append( wizardView.render().$el );

        console.log( shortcodes );
    } );

    // export
    window.wpcrm = _.extend( window.wpcrm || {}, {
        'Shortcode': Shortcode,
        'ShortcodeField': ShortcodeField,
        'ShortcodeCollection': ShortcodeCollection
    } );
} )( jQuery );
