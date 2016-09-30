<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<div class="container-fluid">

    <form id='<?php echo $id; ?>' method='POST' name='entity-form' enctype='multipart/form-data'
          class='form-horizontal entity-form <?php echo implode( " ", $classes ); ?>' autocomplete="off" role="form">
        <fieldset>
