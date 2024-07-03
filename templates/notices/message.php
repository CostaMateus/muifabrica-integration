<?php
/**
 * Message notice
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Templates\Notices
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

?>

<div class="message-actions" >
    <p class="mfi-bulkactions-sync mfi-my-5 mfi-fw-bold mfi-text-danger mfi-float-left mfi-d-none" >
        <?php _e( "This may take some time.", "muifabrica_integration" ); ?>
    </p>
    <p class="mfi-bulkactions-import mfi-my-5 mfi-fw-bold mfi-text-danger mfi-float-left mfi-d-none" >
        <?php _e( "This may take some time. It is recommended to import one supplier at a time.", "muifabrica_integration" ); ?>
    </p>
    <p class="mfi-bulkactions-del-products mfi-my-5 mfi-fw-bold mfi-text-danger mfi-float-left mfi-d-none" >
        <?php _e( "You will permanently delete the products.", "muifabrica_integration" ); ?>
    </p>
    <p class="mfi-bulkactions-del-users mfi-my-5 mfi-fw-bold mfi-text-danger mfi-float-left mfi-d-none" >
        <?php _e( "You will permanently delete the suppliers and its products.", "muifabrica_integration" ); ?>
    </p>
</div>
