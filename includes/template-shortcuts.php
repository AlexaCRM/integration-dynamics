<?php

use AlexaCRM\WordpressCRM\Template;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function wordpresscrm_form_start( $classes = array(), $id ) {
    Template::getTemplate( 'form/form-start.php', array( 'classes' => $classes, "id" => $id ) );
}

function wordpresscrm_form_end() {
    Template::getTemplate( 'form/form-end.php' );
}

function wordpresscrm_form_errors( $errors = array() ) {
    Template::getTemplate( 'form/messages/errors.php', array( 'errors' => $errors ) );
}

function wordpresscrm_form_notices( $notices = array() ) {
    Template::getTemplate( 'form/messages/notices.php', array( 'notices' => $notices ) );
}

function wordpresscrm_field_start( $args = array() ) {
    Template::getTemplate( 'form/field/field-start.php', $args );
}

function wordpresscrm_field_end() {
    Template::getTemplate( 'form/field/field-end.php' );
}

function wordpresscrm_field_label( $args ) {
    Template::getTemplate( 'form/field/label.php', (array) $args );
}

function wordpresscrm_field_type( $args ) {
    if ( isset( $args->type ) ) {
        Template::getTemplate( 'form/field/field-' . strtolower( $args->type ) . '.php', (array) $args );
    }
}

function wordpresscrm_field( $args ) {
    Template::getTemplate( 'form/field/Field.php', array( 'control' => $args ) );
}

function wordpresscrm_readonly_field( $args ) {
    Template::getTemplate( 'form/field/field-readonly.php', array( 'control' => $args ) );
}

function wordpresscrm_form_submit( $args ) {
    Template::getTemplate( 'form/field/submit.php', (array) $args );
}

function wordpresscrm_field_type_hidden( $args ) {
    Template::getTemplate( 'form/field/field-hidden.php', (array) $args );
}

function wordpresscrm_field_error( $args ) {
    if ( isset( $args->error ) ) {
        Template::getTemplate( 'form/field/field-error.php', (array) $args );
    }
}

function wordpresscrm_form_validation( $id, $form ) {
    Template::getTemplate( 'form/javascript/validation.php', array( "id" => $id, 'form' => $form ) );
}

function wordpresscrm_view_field( $args ) {
    Template::getTemplate( 'view/field/Field.php', array( 'field' => $args ) );
}

function wordpresscrm_view_field_type( $args ) {
    if ( isset( $args["properties"]->type ) && $templatePath = Template::locateTemplate( 'view/field/field-' . strtolower( $args["properties"]->type ) . '.php' ) ) {
        Template::getTemplate( 'view/field/field-' . strtolower( $args["properties"]->type ) . '.php', (array) $args );
    } else {
        Template::getTemplate( 'view/field/field-default.php', (array) $args );
    }
}
