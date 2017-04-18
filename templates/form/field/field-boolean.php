<?php
use AlexaCRM\CRMToolkit\OptionSetValue;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<?php if ( $classid == '{67FAC785-CD58-4F9F-ABB3-4B7DDC6ED5ED}' ) : ?>
    <?php foreach ( $options as $option => $val ) : ?>
        <div class="radio">
            <label>
                <input <?php if ( ( $value instanceof OptionSetValue && (bool)$value->value === (bool)$option ) || ( !( $value instanceof  OptionSetValue ) && $value == (bool) $option ) ) {
                    echo "checked='checked'";
                } ?> type='radio' name='<?php echo $inputname; ?>'
                     value='<?php echo $option; ?>' <?php echo( ( $disabled ) ? "disabled='disabled'" : "" ); ?> <?php echo( ( $readonly ) ? "readonly='readonly'" : "" ); ?>> <?php echo $val; ?>
            </label>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ( $classid == '{B0C6723A-8503-4FD7-BB28-C8A06AC933C2}' ) { ?>
    <input type="hidden" name="<?php echo esc_attr( $inputname ); ?>" value="0">
    <div class="checkbox">
        <label>
            <input <?php if ( $value ) {
                echo 'checked="checked"';
            } ?> type="checkbox" name="<?php echo esc_attr( $inputname ); ?>"
                 value="1" <?php echo( ( $disabled ) ? 'disabled="disabled"' : '' ); ?> <?php echo( ( $readonly ) ? 'readonly="readonly"' : '' ); ?>>
        </label>
    </div><?php
}

if ( $classid == '{3EF39988-22BB-4F0B-BBBE-64B5A3748AEE}' ) : ?>
    <select class='selectmenu crm-select form-control' name='<?php echo $inputname; ?>'
            id='<?php echo $inputname; ?>' <?php echo( ( $disabled ) ? "disabled='disabled'" : "" ); ?> <?php echo( ( $readonly ) ? "readonly='readonly'" : "" ); ?>>
        <option value=''></option>
        <?php foreach ( $options as $option => $val ) : ?>
            <option value='<?php echo $option; ?>' <?php if ( $value == (bool) $option ) {
                echo "selected='selected'";
            } ?>><?php echo $val; ?></option>
        <?php endforeach; ?>
    </select>
<?php endif; ?>


