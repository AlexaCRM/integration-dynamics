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
        window.wpcrmValidationSettings = window.wpcrmValidationSettings || {};

        window.wpcrmValidationSettings['<?php echo esc_js( $id ); ?>'] = <?php echo json_encode( $validationSettings ); ?>;
    } )( jQuery );
</script>
