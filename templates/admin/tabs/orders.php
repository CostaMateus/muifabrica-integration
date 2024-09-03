<?php
/**
 * Tools tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

use \Muifabrica_Integration\Classes\Tables\MFI_Orders_table;

$or_table = new MFI_Orders_table();
?>

<section>
	<div class="wrap-mfi" id="mfi-orders-list-table" >

		<form id="mfi-orders-list-table-form" method="post" >
			<?php
				$page  = htmlspecialchars( $_POST[ "page" ] ?? "" );
				$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

				printf( "<input type='hidden' name='page'  value='%s' />", $page  );
				printf( "<input type='hidden' name='paged' value='%d' />", $paged );

				$or_table->prepare_items();
				$or_table->display();
			?>
		</form>

	</div>
</section>
