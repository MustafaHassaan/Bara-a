<?php
if ( ! defined( 'ABSPATH' ) ) exit;
function pmxi_admin_head(){
	$input = new PMXI_Input();
	$get_params = $input->get(array(
		'id' => false,
		'action' => false
	));
	
	// Both values are emitted into JavaScript string literals and both come
	// from the request, where either can be an array.
	//
	// The id is gated on the handler's own predicate and emitted verbatim
	// rather than cast, because every consumer in admin.js sends import_id
	// back as the id of the next request: it has to name the same session
	// PMXI_Handler resolved for this one, and casting breaks that agreement.
	// The action is checked before esc_js(), which casts internally.
	if ( PMXI_Handler::is_usable_import_id( $get_params['id'] ) ) {
		?>
		<script type="text/javascript">
			var import_id = '<?php echo esc_js( (string) $get_params["id"] ); ?>';
		</script>
		<?php
	}

	$wp_all_import_ajax_nonce = '';

	if ( get_current_user_id() and current_user_can( PMXI_Plugin::$capabilities )) {

		$wp_all_import_ajax_nonce = wp_create_nonce( "wp_all_import_secure" );		

	}

	?>
	<script type="text/javascript">
		var ajaxurl = '<?php echo esc_url( admin_url( "admin-ajax.php" ) ); ?>';
		var import_action = '<?php echo esc_js( is_scalar( $get_params["action"] ) ? (string) $get_params["action"] : '' ); ?>';
		var wp_all_import_security = '<?php echo esc_js(wp_unslash($wp_all_import_ajax_nonce)); ?>';
	</script>
	<?php
}