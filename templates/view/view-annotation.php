<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if ( $rows ) {
    foreach ( $entities->Entities as $e ) {
        echo "<img src='data:" . $e->mimetype . ";base64,$e->documentbody' />";
    }
} else {
    echo apply_filters( "wordpresscrm_no_results_view", "<p>No results</p>", $attributes["entity"], $attributes["name"] );
}
