<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( $rows ) { ?>
    <table class="mscrm-listview">
        <thead>
        <tr>
            <?php foreach ( current( $rows ) as $cell ) : ?>
                <th><?php echo $cell["head"]; ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ( $rows as $row ) : ?>
            <tr>
                <?php foreach ( $row as $key => $cell ) : ?>
                    <td><?php wordpresscrm_view_field( $cell ); ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php } else {
    echo apply_filters( "wordpresscrm_no_results_view", __( "<p>No results</p>", 'wordpresscrm' ), $attributes["entity"], $attributes["name"] );
} ?>
