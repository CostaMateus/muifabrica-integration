<?php

/**
 * Preview products tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined("ABSPATH") || exit;

use \Muifabrica_Integration\Classes\Tables\DSM_Preview_table;

$pv_table = new DSM_Preview_table();

$cat_notice = ( get_option( "_mfi_categories_settings" ) ) ? __( "Category import is enabled.", "muifabrica_integration" ) : __( "Category import is <u>not</u> enabled.", "muifabrica_integration" );
$url_config = admin_url( "admin.php?page=muifabrica_integration&tab=settings" );
$link       = "<a href='{$url_config}' >" . __( "click here", "muifabrica_integration" ) . "</a>";
$compl      = __( "To change this setting,", "muifabrica_integration" );
$cat_msg    = "{$compl} {$link}.";
?>

<section>
	<div class="wrap-mfi" id="mfi-users-list-table" >
		<h2 class="wp-heading-inline" style="display:inline;" >
			<?php
				$name  = $pv_table->get_user_login();
				$title = __( "Supplier preview:", "muifabrica_integration" );

				echo "{$title} {$name}";
			?>
		</h2>

		<p class="notice-cat" ><?php echo $cat_notice; ?></p>
		<p><?php echo $cat_msg; ?></p>

		<form id="mfi-preview_products-list-table-form" method="post" >
			<?php
				$page  = htmlspecialchars( $_POST[ "page" ] ?? "" );
				$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

				printf( "<input type='hidden' name='page'  value='%s' />", $page  );
				printf( "<input type='hidden' name='paged' value='%d' />", $paged );

				$pv_table->prepare_items();
				$pv_table->display();
			?>
		</form>
	</div>
</section>