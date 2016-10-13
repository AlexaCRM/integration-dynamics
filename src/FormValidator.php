<?php

namespace AlexaCRM\WordpressCRM;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Validates fields by regexes and can sanatize them. Uses PHP filter_var built-in functions and extra regexes
 *
 * @ignore
 */
class FormValidator {

    /**
     * Regular expressions for validation
     *
     * @ignore
     * @var array regular expressions
     */
    public static $regexes = [
        'date'        => "[0-9]{1,2}\/[0-9]{1,2}\/[0-9][0-9]",
        'amount'      => "^[-]?[0-9]+\$",
        'number'      => "^[-]?[0-9,]+\$",
        'alfanum'     => "^[0-9a-zA-Z ,.-_\\s\?\!]+\$",
        'not_empty'   => "[a-z0-9A-Z]+",
        'words'       => "^[A-Za-z]+[A-Za-z \\s]*\$",
        'phone'       => "^[0-9]{10,11}\$",
        'zipcode'     => "^[1-9][0-9]{3}[a-zA-Z]{2}\$",
        'plate'       => "^([0-9a-zA-Z]{2}[-]){2}[0-9a-zA-Z]{2}\$",
        'price'       => "^[0-9.,]*(([.,][-])|([.,][0-9]{2}))?\$",
        'float'       => "/^-?(?:\d+|\d*\.\d+)$/",
        'timezone'    => "/^(Z|[+-](?:2[0-3]|[01]?[0-9])(?::?(?:[0-5]?[0-9]))?)$/",
        '2digitopt'   => "^\d+(\,\d{2})?\$",
        '2digitforce' => "^\d+\,\d\d\$",
        'anything'    => "^[\d\D]{1,}\$"
    ];

    /**
     * @ignore
     */
    private $validations, $sanatations, $mandatories, $errors, $corrects, $fields;

    /**
     * @ignore
     */
    public function __construct( $validations = array(), $mandatories = array(), $sanatations = array() ) {
        $this->validations = $validations;
        $this->sanatations = $sanatations;
        $this->mandatories = $mandatories;
        $this->errors      = array();
        $this->corrects    = array();
    }

    /**
     * Validates an array of items (if needed) and returns true or false
     *
     * @param array $items
     *
     * @return bool Validation result
     */
    public function validate( $items ) {
        $this->fields = $items;
        $haveFailures = false;

        foreach ( $items as $key => $val ) {
            if ( ( strlen( $val ) == 0 || array_search( $key, $this->validations ) === false )
                 && array_search( $key, $this->mandatories ) === false ) {
                $this->corrects[] = $key;
                continue;
            }

            $result = self::validateItem( $val, $this->validations[ $key ] );

            if ( $result === false ) {
                $haveFailures = true;
                $this->addError( $key, $this->validations[ $key ] );
            } else {
                $this->corrects[] = $key;
            }
        }

        return ( !$haveFailures );
    }

    /**
     * Adds an error to the errors array.
     *
     * @param string $field
     * @param string $type
     */
    private function addError( $field, $type = 'string' ) {
        $this->errors[ $field ] = $type;
    }

    /**
     * Validates a single var according to $type.
     *
     * @param mixed $value Value to validate.
     * @param string $type One of FormValidator::$regexes keys or email, int, boolean, ip, url.
     *
     * @return bool Validation result
     */
    public static function validateItem( $value, $type ) {
        if ( array_key_exists( $type, self::$regexes ) ) {
            $filterOptions = [
                'options' => [
                    'regexp' => '!' . self::$regexes[ $type ] . '!i',
                ],
            ];
            $returnValue = ( filter_var( $value, FILTER_VALIDATE_REGEXP, $filterOptions ) !== false );

            return ( $returnValue );
        }

        $filter = false;

        switch ( $type ) {
            case 'email':
                $value  = substr( $value, 0, 254 );
                $filter = FILTER_VALIDATE_EMAIL;
                break;
            case 'int':
                $filter = FILTER_VALIDATE_INT;
                break;
            case 'boolean':
            case 'bool':
                $filter = FILTER_VALIDATE_BOOLEAN;
                break;
            case 'ip':
                $filter = FILTER_VALIDATE_IP;
                break;
            case 'url':
                $filter = FILTER_VALIDATE_URL;
                break;
        }

        return ( $filter === false ) ? false : filter_var( $value, $filter ) !== false ? true : false;
    }

}
