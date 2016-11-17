<?php

use AlexaCRM\WordpressCRM\Template;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function wordpresscrm_form_start( $classes = array(), $id ) {
    ACRM()->template->getTemplate( 'form/form-start.php', array( 'classes' => $classes, "id" => $id ) );
}

function wordpresscrm_form_end() {
    ACRM()->template->getTemplate( 'form/form-end.php' );
}

function wordpresscrm_form_errors( $errors = array() ) {
    ACRM()->template->getTemplate( 'form/messages/errors.php', array( 'errors' => $errors ) );
}

function wordpresscrm_form_notices( $notices = array() ) {
    ACRM()->template->getTemplate( 'form/messages/notices.php', array( 'notices' => $notices ) );
}

function wordpresscrm_field_start( $args = array() ) {
    ACRM()->template->getTemplate( 'form/field/field-start.php', $args );
}

function wordpresscrm_field_end() {
    ACRM()->template->getTemplate( 'form/field/field-end.php' );
}

function wordpresscrm_field_label( $args ) {
    ACRM()->template->getTemplate( 'form/field/label.php', (array) $args );
}

function wordpresscrm_field_type( $args ) {
    if ( isset( $args->type ) ) {
        ACRM()->template->getTemplate( 'form/field/field-' . strtolower( $args->type ) . '.php', (array) $args );
    }
}

function wordpresscrm_field( $args ) {
    ACRM()->template->getTemplate( 'form/field/field.php', array( 'control' => $args ) );
}

function wordpresscrm_readonly_field( $args ) {
    ACRM()->template->getTemplate( 'form/field/field-readonly.php', array( 'control' => $args ) );
}

function wordpresscrm_form_submit( $args ) {
    ACRM()->template->getTemplate( 'form/field/submit.php', (array) $args );
}

function wordpresscrm_field_type_hidden( $args ) {
    ACRM()->template->getTemplate( 'form/field/field-hidden.php', (array) $args );
}

function wordpresscrm_field_error( $args ) {
    if ( isset( $args->error ) ) {
        ACRM()->template->getTemplate( 'form/field/field-error.php', (array) $args );
    }
}

function wordpresscrm_form_validation( $id, $form ) {
    ACRM()->template->getTemplate( 'form/javascript/validation.php', array( "id" => $id, 'form' => $form ) );
}

function wordpresscrm_view_field( $args ) {
    ACRM()->template->getTemplate( 'view/field/field.php', array( 'field' => $args ) );
}

function wordpresscrm_view_field_type( $args ) {
    if ( isset( $args["properties"]->type ) && $templatePath = ACRM()->template->locateTemplate( 'view/field/field-' . strtolower( $args["properties"]->type ) . '.php' ) ) {
        ACRM()->template->getTemplate( 'view/field/field-' . strtolower( $args["properties"]->type ) . '.php', (array) $args );
    } else {
        ACRM()->template->getTemplate( 'view/field/field-default.php', (array) $args );
    }
}
