<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
if ( ! defined( 'ABSPATH' ) ) exit;

function wp_all_import_json_to_xml( $json = array(), $keep_4byte_chars = false ){	

	return PMXI_ArrayToXML::toXml($json, 'data', null, 0, $keep_4byte_chars);
	
}

