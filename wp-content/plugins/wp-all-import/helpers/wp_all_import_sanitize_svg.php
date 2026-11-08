<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
if ( ! defined( 'ABSPATH' ) ) exit;

if(!function_exists('wp_all_import_sanitize_svg')) {
	// Returns false when the SVG cannot be sanitized, so no caller keeps it unsanitized.
	function wp_all_import_sanitize_svg( $svg, $is_file_path = true ) {
		try {
			$sanitizer = new \Soflyy\WpAllImport\Vendor\enshrined\svgSanitize\Sanitizer();

			if ( $is_file_path ) {
				$svgContents  = file_get_contents( $svg );
				$sanitizedSvg = $svgContents === false ? false : $sanitizer->sanitize( $svgContents );
				if ( ! $sanitizedSvg ) {
					return false;
				}
				file_put_contents( $svg, $sanitizedSvg );
			}else{
				$svg = $sanitizer->sanitize( $svg );
			}
		} catch ( \Throwable $e ) {
			return false;
		}

		return $svg;
	}
}
