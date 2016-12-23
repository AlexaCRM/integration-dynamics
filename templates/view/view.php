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
        <?php
        if ( array_key_exists( 'count', $attributes ) && (int)$attributes['count'] > 0 ) {
            $currentPage = 1;
            if ( array_key_exists( 'viewPage', $_GET ) && (int)$_GET['viewPage'] > 0 ) {
                $currentPage = (int)$_GET['viewPage'];
            }
            ?><tfoot>
            <tr>
                <td colspan="<?php echo esc_attr( count( $cells ) ); ?>">
                    <?php if ( $currentPage > 1 ) {
                        $queryParams = ACRM()->request->query->all();
                        unset( $queryParams['viewPage'] );
                        if ( $currentPage > 2 ) {
                            $queryParams[ 'viewPage'] = $currentPage - 1;
                        }

                        $url = \Symfony\Component\HttpFoundation\Request::create(
                            ACRM()->request->getPathInfo(),
                            'GET',
                            $queryParams
                        );
                        ?><a href="<?php echo esc_attr( $url->getRequestUri() ); ?>" class="btn btn-outline-primary"><?php _e( '&larr; Previous', 'integration-dynamics' ); ?></a> <?php /* the prepended space is purposeful */
                    }
                    if ( $entities->MoreRecords ) {
                        $url = \Symfony\Component\HttpFoundation\Request::create(
                            ACRM()->request->getPathInfo(),
                            'GET',
                            array_merge( ACRM()->request->query->all(), [ 'viewPage' => $currentPage + 1 ] )
                        );
                        ?><a href="<?php echo esc_attr( $url->getRequestUri() ); ?>" class="btn btn-outline-primary"><?php _e( 'Next &rarr;', 'integration-dynamics' ); ?></a><?php
                    }
                    ?>
                </td>
            </tr>
            </tfoot>
        <?php } ?>
    </table>
<?php } else {
    echo apply_filters( "wordpresscrm_no_results_view", __( "<p>No results</p>", 'integration-dynamics' ), $attributes["entity"], $attributes["name"] );
} ?>
