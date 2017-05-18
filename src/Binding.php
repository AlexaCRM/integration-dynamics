<?php

namespace AlexaCRM\WordpressCRM;

use AlexaCRM\CRMToolkit\Entity;
use AlexaCRM\CRMToolkit\KeyAttributes;
use AlexaCRM\CRMToolkit\NotAuthorizedException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Implements record binding to a certain post, exposing record's attributes.
 */
class Binding {

    /**
     * Bound entity record.
     *
     * Field is false if no bound record found.
     *
     * @var Entity|bool
     */
    private $entity;

    /**
     * Retrieve the current data-bound entity record.
     *
     * @return Entity|null
     */
    public function getEntity() {
        if ( $this->entity === false ) {
            return null;
        }

        if ( $this->entity instanceof Entity ) {
            return $this->entity;
        }

        $bindingConfig = $this->getPostBinding( get_the_ID() );
        if ( $bindingConfig === null ) {
            $this->entity = false;

            return null;
        }

        $this->entity = $this->getBoundRecord( $bindingConfig['entity'], $bindingConfig['key'], $bindingConfig['query'] );

        /**
         * Filters the data-bound record.
         *
         * @param Entity $record
         * @param \WP_Post $post,
         * @param string $entityName
         * @param string $entityKey
         * @param string $entityQuery
         */
        $this->entity = apply_filters( 'wordpresscrm_data_binding_entity', $this->entity, get_post(), $bindingConfig['entity'], $bindingConfig['key'], $bindingConfig['query'] );

        $shouldTrigger404 = ( $bindingConfig['empty'] === '404' );
        if ( $this->entity === null && apply_filters( 'wordpresscrm_data_binding_404', $shouldTrigger404 ) ) {
            global $wp_query;

            $this->entity = false;
            $wp_query->set_404();
            status_header( 404 );

            return null;
        }

        return $this->entity;
    }

    /**
     * Retrieves the default post for given entity name.
     *
     * @param string $entityName
     *
     * @return array|null|\WP_Post
     */
    public function getDefaultPost( $entityName ) {
        $defaultBinds = get_option( 'wpcrm_binding_default', [] );
        if ( !array_key_exists( $entityName, $defaultBinds ) ) {
            return null;
        }

        return get_post( $defaultBinds[$entityName] );
    }

    /**
     * Retrieves data-binding config for the given post.
     *
     * @param int $postId
     *
     * @return array|null
     */
    public function getPostBinding( $postId ) {
        $postId = intval( $postId );

        if ( !$postId ) {
            return null;
        }

        $bindingData = get_option( 'wpcrm_binding', [] );

        if ( !array_key_exists( $postId, $bindingData ) ) {
            return null;
        }

        return $bindingData[$postId];
    }

    /**
     * Builds an URL for a given entity record.
     *
     * @param Entity\EntityReference $record
     *
     * @return string
     */
    public function buildUrl( $record ) {
        $defaultBinding = get_option( 'wpcrm_binding_default', [] );

        if ( !array_key_exists( $record->logicalname, $defaultBinding ) ) {
            return '';
        }

        $postId = $defaultBinding[ $record->logicalname ];

        $bindingConfig = $this->getPostBinding( $postId );
        if ( $bindingConfig === null ) {
            return '';
        }

        $url = get_permalink( $postId );

        $params = [
            $bindingConfig['query'] => $record->{$bindingConfig['key']},
        ];

        $urlRequest = Request::create(
            $url, 'GET', $params
        );

        return $urlRequest->getRequestUri();
    }

    /**
     * Updates the post data-binding configuration.
     *
     * @param int $postId
     * @param array|null $config
     */
    public function updateBinding( $postId, $config ) {
        $bindingConfig = get_option( 'wpcrm_binding', [] );

        unset( $bindingConfig[$postId] );
        if ( is_array( $config ) ) {
            $bindingConfig[$postId] = $config;
        }

        update_option( 'wpcrm_binding', $bindingConfig );
    }

    /**
     * Updates the entity-post association for views and the lookup dialog.
     *
     * @param string $entity Entity name
     * @param int $postId Post ID
     */
    public function updateDefaultBinding( $entity, $postId ) {
        $bindingConfig = $this->getPostBinding( $postId );
        if ( $bindingConfig === null ) {
            return;
        }
        $bindingConfig['default'] = true;
        $this->updateBinding( $postId, $bindingConfig );

        $defaultBinding = get_option( 'wpcrm_binding_default', [] );

        if ( array_key_exists( $entity, $defaultBinding ) && $defaultBinding[$entity] !== $postId ) {
            $oldBoundPageConfig = $this->getPostBinding( $defaultBinding[$entity] );
            $oldBoundPageConfig['default'] = false;
            $this->updateBinding( $defaultBinding[$entity], $oldBoundPageConfig );
        }

        $defaultBinding[$entity] = $postId;
        update_option( 'wpcrm_binding_default', $defaultBinding );
    }

    /**
     * Retrieves a bound record from Dynamics CRM.
     *
     * @param string $entityName
     * @param string $entityKey
     * @param string $entityQuery
     *
     * @return Entity|null
     */
    private function getBoundRecord( $entityName, $entityKey, $entityQuery ) {
        $query = ACRM()->request->query;

        if ( !$query->get( $entityQuery ) ) {
            return null;
        }

        try {
            $entityRequestValue = $query->get( $entityQuery );
            if ( trim( $entityRequestValue ) === '' ) {
                return null;
            }

            $columnSet = $this->getCurrentColumns();

            // get record fields and related entity references! (not records yet)
            $entityColumns = array_unique( array_map( function( $field ) {
                return preg_replace( '~^(.*?)[\.$].*~', '$1', $field );
            }, $columnSet ) );

            $entityAttributes = ACRM()->getMetadata()->getEntityDefinition( $entityName )->attributes;
            $entityColumns = array_filter( $entityColumns, function( $field ) use ( $entityAttributes ) {
                return array_key_exists( $field, $entityAttributes );
            } );

            if ( $entityKey !== 'id' ) {
                $entityMetadata = ASDK()->entity( $entityName )->metadata();

                if ( !array_key_exists( $entityKey, $entityMetadata->keys ) ) {
                    return null;
                }

                /**
                 * @var $key \AlexaCRM\CRMToolkit\Entity\EntityKey
                 */
                $key = $entityMetadata->keys[ $entityKey ];
                $entityKeyAttribute = $key->getKeyAttributes();

                if ( is_array( $entityKeyAttribute ) ) {
                    $entityKeyAttribute = $entityKeyAttribute[0]; //TODO: support for multikeys
                }

                $entityRequestValue = new KeyAttributes();
                $entityRequestValue->add( $entityKeyAttribute, $query->get( $entityQuery ) );
            }

            $record = ASDK()->entity( $entityName, $entityRequestValue, $entityColumns );

            if ( !$record || !$record->exists ) {
                return null;
            }

            $relatedFields = [];
            foreach ( $columnSet as $field ) {
                if ( strpos( $field, '.' ) == false ) {
                    continue;
                }

                $relatedField = explode( '.', $field ); // [ entityName, entityId ]
                if ( !array_key_exists( $relatedField[0], $relatedFields ) ) {
                    $relatedFields[ $relatedField[0] ] = [];
                }

                // Related field value is not set - continue without it
                if ( $record->{$relatedField[0]} === null ) {
                    unset( $relatedFields[ $relatedField[0] ] );
                    continue;
                }

                $entityAttributes = ACRM()->getMetadata()->getEntityDefinition( $record->{$relatedField[0]}->logicalname )->attributes;
                if ( !array_key_exists( $relatedField[1], $entityAttributes ) ) {
                    continue;
                }

                $relatedFields[ $relatedField[0] ][] = $relatedField[1];
            }

            // retrieve related records
            foreach ( $relatedFields as $relatedFieldName => $recordFields ) {
                $record->{$relatedFieldName} = ASDK()->entity( $record->{$relatedFieldName}->logicalname, $record->{$relatedFieldName}->id, $recordFields );
            }

            return $record;
        } catch ( NotAuthorizedException $e ) {
            ACRM()->getLogger()->critical( 'CRM Toolkit returned a NotAuthorizedException while retrieving the bound record.', [ 'exception' => $e, 'arguments' => [ $entityName, $entityKey, $entityQuery ] ] );
        } catch ( \Exception $ex ) {
            ACRM()->getLogger()->error( 'An exception occured while retrieving the bound record.', [ 'exception' => $ex, 'arguments' => [ $entityName, $entityKey, $entityQuery ] ] );
        }

        return null;
    }

    /**
     * Retrieves a list of entity columns based on [msdyncrm_field] usage.
     *
     * @return array
     */
    private function getCurrentColumns() {
        global $post;
        $content = $post->post_content;

        /**
         * Allows specifying extra columns to retrieve from CRM.
         *
         * @param array $fields     List of columns to retrieve
         * @param string $content   Post content
         * @param int $postId       Post ID
         */
        $fields = apply_filters( 'wordpresscrm_data_binding_columns', [], $content, $post->ID );

        $shortcodeRegex = get_shortcode_regex( [ Plugin::PREFIX . 'field' ] );

        $count = preg_match_all( '/' . $shortcodeRegex . '/', $content, $shortcodes );

        if ( !$count ) {
            return $fields;
        }

        foreach ( $shortcodes[3] as $attributesString ) {
            $attributes = shortcode_parse_atts( $attributesString );

            if ( !array_key_exists( 'field', $attributes ) ) {
                continue;
            }

            array_push( $fields, $attributes['field'] );
        }

        $fields = array_unique( $fields );

        return $fields;
    }

}
