<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
if ( ! defined( 'ABSPATH' ) ) exit;
if (!function_exists('is_exists_term')){
	
	function is_exists_term( $term, $taxonomy = '', $parent = null, $slug_only = false ){		

		if ( $slug_only ) {
			// term_exists() falls back to a name search, which strips a slug's percent-encoded bytes, so `tag%f0%9f%94%a5` would find `tag`.
			$args = array( 'taxonomy' => $taxonomy, 'slug' => $term, 'hide_empty' => false, 'number' => 1 );
			if ( null !== $parent ) {
				$args['parent'] = (int) $parent;
			}
			$found = get_terms( $args );
			$exists = ( ! empty( $found ) && ! is_wp_error( $found ) ) ? array( 'term_id' => (string) $found[0]->term_id, 'term_taxonomy_id' => (string) $found[0]->term_taxonomy_id ) : null;
			return apply_filters( 'wp_all_import_term_exists', $exists, $taxonomy, $term, $parent );
		}

		return apply_filters( 'wp_all_import_term_exists', term_exists( $term, $taxonomy, $parent ), $taxonomy, $term, $parent );
		
	}
}