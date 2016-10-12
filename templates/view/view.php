<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

$rowWidth = 0;
foreach ( $cells as $cell ) {
    $rowWidth += (int)$cell['width'];
}
if ( $rows ) { ?>
    <table class="mscrm-listview table">
        <thead>
        <tr>
            <?php foreach ( current( $rows ) as $cellName => $cell ) {
                $cellWidth = $cells[$cellName]['width'];
                ?>
                <th style="width:<?php echo round( ( $cellWidth / $rowWidth * 100 ), 3 ); ?>%;"><?php echo $cell["head"]; ?></th>
            <?php } ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ( $rows as $row ) { ?>
            <tr>
                <?php foreach ( $row as $key => $cell ) : ?>
                    <td><?php wordpresscrm_view_field( $cell ); ?></td>
                <?php endforeach; ?>
            </tr>
        <?php } ?>
        </tbody>
    </table>
<?php } else {
    echo apply_filters( "wordpresscrm_no_results_view", __( "<p>No results</p>", 'integration-dynamics' ), $attributes["entity"], $attributes["name"] );
} ?>
