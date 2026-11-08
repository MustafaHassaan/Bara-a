<?php

function wp_all_import_index_prefix_length( $table, $long ) {
	global $wpdb;

	$suppress = $wpdb->suppress_errors();

	$format = $wpdb->get_row( $wpdb->prepare( 'SELECT ENGINE AS engine, ROW_FORMAT AS row_format FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table ) );
	if ( empty( $format ) ) {
		$format = $wpdb->get_row( 'SELECT @@default_storage_engine AS engine, @@innodb_default_row_format AS row_format' );
	}

	$wpdb->suppress_errors( $suppress );

	// InnoDB only indexes more than 767 bytes of a column on DYNAMIC and COMPRESSED rows; anywhere else a longer prefix fails the CREATE or ALTER.
	if ( ! empty( $format ) && 'innodb' === strtolower( (string) $format->engine ) && in_array( strtolower( (string) $format->row_format ), array( 'dynamic', 'compressed' ), true ) ) {
		return (int) $long;
	}

	return 191;
}
