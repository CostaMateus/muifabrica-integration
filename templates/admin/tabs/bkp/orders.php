<?php
/**
 * Tools tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

use Muifabrica_Integration\Classes\Tables\DSM_Orders_table;

$o_table = new DSM_Orders_table();
?>

<section>
	<div class="wrap-mfi" id="" >
		<h2 class="wp-heading-inline" style="display:inline;" >
			<?php _e( "Orders", "muifabrica_integration" ); ?>
		</h2>

		<h3>
			<?php _e( "Instructions:", "muifabrica_integration" ); ?>
			<a id="mfi-instructions-toggle" class="" href="#" >
				<small>
					<?php _e( "Show/Hide", "muifabrica_integration" ); ?>
				</small>
			</a>
		</h3>
		<div id="mfi-instructions" class="mfi-d-none" >
			<p>
				<?php _e( "- Only orders that contain at least one product, which is from a supplier registered through this plugin, will be listed here.", "muifabrica_integration" ); ?>
				<br>
				<?php _e( "- In the 'Suppliers' column it shows the list of suppliers that have products in that order and, in parentheses, the number of different products. Example <strong>Supplier_name (2)</strong>.", "muifabrica_integration" ); ?>
			</p>
			<p>
				<?php _e( "- <strong>Set status shipped</strong>:", "muifabrica_integration" ); ?>
				<br>
				<?php _e( "(1) Will update all orders with status 'processing' to 'shipped'.", "muifabrica_integration" ); ?>
			</p>
		</div>

		<form id="mfi-orders-list-table-form" method="post" >
			<?php
				$page  = htmlspecialchars( $_POST[ "page" ] ?? "" );
				$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

				printf( "<input type='hidden' name='page'  value='%s' />", $page  );
				printf( "<input type='hidden' name='paged' value='%d' />", $paged );

				$o_table->prepare_items();
				$o_table->display();
			?>
		</form>
	</div>
</section>
a