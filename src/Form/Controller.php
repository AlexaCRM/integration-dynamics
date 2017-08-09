<?php

namespace AlexaCRM\WordpressCRM\Form;

/**
 * Controls form submissions.
 */
class Controller {

    /**
     * Runs form handlers in order to capture form submissions.
     */
    public static function dispatchFormHandler() {
        if ( ACRM()->request->getMethod() !== 'POST' ) {
            return;
        }

        $request = ACRM()->request->request;
        $cache = ACRM()->getCache();

        $incomingFormId = $request->get( '__submission_id' ); // FIXME: field name may be changed
        if ( $incomingFormId === null ) {
            return; // Not our concern.
        }

        /**
         * Capture submitted fields.
         *
         * These will be then applied to the form if the latter cannot be submitted.
         */
        add_filter( 'wordpresscrm_form_bounced_fields', function( $fields ) use ( $request ) {
            $fields = $request->all();

            return $fields;
        } );

        /**
         * @var $dispatchedForm Model
         */
        $dispatchedForm = $cache->get( 'wpcrm_formsub_' . $incomingFormId );
        if ( $dispatchedForm === null ) { // Form submission has not been found
            add_filter( 'wordpresscrm_form_messages', function( $messages ) {
                $messages[] = __( 'The form has expired. Please try again.', 'integration-dynamics' );

                return $messages;
            } );

            return;
        }

        if ( $dispatchedForm->hasExpired() ) { // Form submission has been found and it has expired
            $cache->delete( 'wpcrm_formsub_' . $incomingFormId );

            add_filter( 'wordpresscrm_form_messages', function( $messages ) {
                $messages[] = __( 'The form has expired. Please try again.', 'integration-dynamics' );

                return $messages;
            } );

            return;
        }

        // Process the form
        $result = $dispatchedForm->validate( $request->all() );

        add_filter( 'wordpresscrm_form_validation', function( $errors ) use ( $result ) {
            $errors = $result['payload'];

            return $errors;
        } );

        if ( $result['status'] ) {
            $record = $dispatchedForm->hydrateRecord( $result['payload'] );
            $mode = $dispatchedForm->attributes['mode'];
            if ( $mode === 'create' ) {
                ASDK()->create( $record );
            } elseif ( $mode === 'edit' ) {
                ASDK()->update( $record );
            } elseif ( $mode === 'upsert' ) {
                ASDK()->upsert( $record );
            }

            wordpresscrm_javascript_redirect( ACRM()->request->getRequestUri() );
        }
    }

    /**
     * Adds the form to the list of forms waiting for submission.
     *
     * @param Model $form
     */
    public static function registerFormHandler( Model $form ) {
        $cache = ACRM()->getCache();

        $handlerId = $form->getInstanceId();
        $cache->set( 'wpcrm_formsub_' . $handlerId, $form, 30 * MINUTE_IN_SECONDS );
    }

}
