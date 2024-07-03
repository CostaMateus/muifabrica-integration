<?php
/**
 * Provide a admin area view for the plugin
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Templates\Admin
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

?>

<div class="wrap" >
	<div class="wrap-title mfi-wrap-title" >
		<img src="<?php echo plugins_url( "assets/images/mfi-settings.png", Muifabrica_Integration::FILE ); ?>" alt="MF Logo" >
		<h1 class="wp-heading-inline" ><?php echo esc_html( get_admin_page_title() ); ?></h1>
	</div>

	<nav class="nav-tab-wrapper" >
		<?php
			foreach ( $tabs as $key => $value )
			{
				$page   = Muifabrica_Integration::DOMAIN;
				$active = ( $key == $tab ) ? "nav-tab-active" : "";

				echo "<a href='?page={$page}&tab={$key}' class='nav-tab {$active}' >{$value}</a>";
			}
		?>
	</nav>

	<div class="tab-content" >
	<?php
		if ( isset ( $tabs[ $tab ] ) )
			require_once plugin_dir_path( Muifabrica_Integration::FILE ) . "templates/admin/tabs/{$tab}.php";
		else
			require_once plugin_dir_path( Muifabrica_Integration::FILE ) . "templates/admin/tabs/settings.php";
	?>
	</div>
</div>
