<?php
/**
 * General tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

$_email    = get_option( "_mfi_email_settings" );
$_category = ( !empty( get_option( "_mfi_categories_settings" ) ) ) ? "checked" : "";
$_debug    = ( !empty( get_option( "_mfi_debug"              ) ) ) ? "checked" : "";

?>

<section>
	<div class="wrap-mfi" id="" >
		<h2 class="wp-heading-inline" style="display:inline;" >
			<?php _e( "General", "muifabrica_integration" ); ?>
		</h2>

		<form id="mfi-settings-form" class="mfi-form" method="post" action="<?php echo admin_url( "admin-post.php" ); ?>" >
			<input type="hidden" name="action" value="mfi_save_settings" />
			<?php wp_nonce_field( "mfi_save_settings" ); ?>

			<table class="form-table" >
				<tbody>
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="mfi_email_settings" >
								<?php _e( "E-mail account", "muifabrica_integration" ); ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="mfi_email_settings" class="input-text regular-input" type="email" name="mfi_email_settings" value="<?php echo $_email; ?>" >
							<p class="description" ><?php _e( "The supplier must create an account with this email. And this e-mail will be used for communication between the systems (shopkeeper/supplier). If you leave it blank, the default WP email will be used. Forward it to the supplier.", "muifabrica_integration" ); ?></p>
							<p class="description" ><?php echo __( "Default: ", "muifabrica_integration" ) . get_bloginfo( "admin_email" ); ?></p>
						</td>
					</tr>
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="mfi_categories_settings" >
								<?php _e( "Import categories", "muifabrica_integration" ); ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="mfi_categories_settings" class="input-text regular-input" type="checkbox" name="mfi_categories_settings" <?php echo $_category;?> >
							<?php _e( "Enable / Disable", "muifabrica_integration" ); ?>
							<p class="description" ><?php _e( "If enabled, it imports products from each supplier with their respective categories. If a category does not exist, it will be created at import time.", "muifabrica_integration" ); ?></p>
							<p class="description" ><?php _e( "If not, all products will be imported without categories.", "muifabrica_integration" ); ?></p>
						</td>
					</tr>
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="mfi_debug_settings" >
								<?php _e( "Debug Log", "muifabrica_integration" ); ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="mfi_debug_settings" class="input-text regular-input" type="checkbox" name="mfi_debug_settings" <?php echo $_debug; ?> >
							<?php _e( "Enable / Disable", "muifabrica_integration" ); ?>
							<p class="description" ><?php _e( "If enabled, will generate detailed log. If not, it will only generate a crash log.", "muifabrica_integration" ); ?></p>
							<p class="description" ><?php echo $debug; ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit" style="clear: both;" >
				<input type="submit" class="button-primary" value="<?php _e( "Update settings", "muifabrica_integration" );?>" ></input>
			</p>
		</form>
	</div>
</section>
