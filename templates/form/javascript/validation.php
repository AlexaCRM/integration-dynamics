<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$validationSettings = [
    'rules' => [],
    'messages' => [],
];
foreach ( $form->controls as $column ) {
    foreach ( $column['controls'] as $control ) {
        foreach ( $control->jsValidators as $ruleName => $ruleDefinition ) {
            $validationSettings['rules'][$control->inputname][$ruleName] = $ruleDefinition['value'];
            $validationSettings['messages'][$control->inputname][$ruleName] = $ruleDefinition['message'];
        }
    }
}
?>
<script>
    ( function( $ ) {
        var validationSettings = <?php echo json_encode( $validationSettings ); ?>,
            additionalSettings = {
                highlight: function( element, errorClass ) {
                    var $element = $( element ),
                        $formGroup = $element.parents( '.form-group' );

                    if ( $formGroup.hasClass( 'has-error' ) ) {
                        return;
                    }

                    $formGroup.addClass( 'has-error has-feedback' );
                    $element.after( '<span class="glyphicon glyphicon-remove form-control-feedback" aria-hidden="true"></span>' );
                },
                unhighlight: function( element, errorClass ) {
                    var $element = $( element ),
                        $formGroup = $element.parents( '.form-group' );

                    $formGroup.removeClass( 'has-error has-feedback' );
                    $formGroup.find( '.form-control-feedback' ).remove();
                },
                errorPlacement: function( $error, $element ) {
                    $error.appendTo( $element.parent() );
                }
            };

        $( function() {
            $( '#<?php echo $id; ?>' ).validate( $.extend( {}, validationSettings, additionalSettings ) );
        } );
    } )( jQuery );
</script>
