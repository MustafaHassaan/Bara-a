<?php

if ( ! function_exists( 'wp_all_import_has_template_token' ) ) {
	/**
	 * An unescaped `{` opens an XPath token, matching XmlImportTemplateScanner's
	 * grammar. `[` also opens a function call there, but it is deliberately not
	 * treated as a signal here because shortcodes would read as mappings; function
	 * calls wrap an XPath token in practice, so the braces still match.
	 *
	 * The character class excludes `{` so an unterminated run of braces cannot
	 * backtrack quadratically, and an all-whitespace token does not count.
	 */
	function wp_all_import_has_template_token( $value ) {
		if ( ! is_scalar( $value ) ) {
			return false;
		}

		$value = (string) $value;

		if ( '' === trim( $value ) || false === strpos( $value, '{' ) ) {
			return false;
		}

		if ( ! preg_match_all( '/(?<!\\\\)\{([^{}]+)\}/', $value, $matches ) ) {
			return false;
		}

		foreach ( $matches[1] as $token ) {
			if ( '' !== trim( $token ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'wp_all_import_is_mapped_value' ) ) {
	function wp_all_import_is_mapped_value( $value ) {
		return is_scalar( $value ) && '' !== trim( (string) $value );
	}
}

if ( ! function_exists( 'wp_all_import_has_mapped_token' ) ) {
	/**
	 * Whether any leaf carries a template token. Add-on option arrays cannot be
	 * tested for mere non-emptiness: RapidAddon seeds every enum field with its
	 * first choice, and the add-on API's field markup posts sticky sub-keys such
	 * as search_logic=by_url and mode=fixed with no user input at all, so a
	 * non-empty test reports a mapping on an untouched import.
	 */
	function wp_all_import_has_mapped_token( $values ) {
		if ( is_array( $values ) ) {
			foreach ( $values as $value ) {
				if ( wp_all_import_has_mapped_token( $value ) ) {
					return true;
				}
			}

			return false;
		}

		return wp_all_import_has_template_token( $values );
	}
}

if ( ! function_exists( 'wp_all_import_has_mapped_value' ) ) {
	/**
	 * Recursive because add-on values nest (repeater rows, checkbox arrays).
	 */
	function wp_all_import_has_mapped_value( $values ) {
		if ( is_array( $values ) ) {
			foreach ( $values as $value ) {
				if ( wp_all_import_has_mapped_value( $value ) ) {
					return true;
				}
			}

			return false;
		}

		// A cleared toggle round-trips as '0', which is not a mapping.
		if ( is_scalar( $values ) && '0' === trim( (string) $values ) ) {
			return false;
		}

		return wp_all_import_is_mapped_value( $values );
	}
}

if ( ! function_exists( 'wp_all_import_section_has_mapping' ) ) {
	/**
	 * Whether a template section holds at least one value the user bound to their
	 * file, as opposed to a statically configured option left at or changed from
	 * its default. Drives whether the section renders expanded.
	 *
	 * @param string $section One of the import-step3-* section slugs.
	 * @param array  $post    Import options.
	 * @param string $prefix  Section slug prefix used by the repeatable images section.
	 */
	function wp_all_import_section_has_mapping( $section, $post, $prefix = '' ) {
		$get = function ( $key, $default = '' ) use ( $post, $prefix ) {
			return isset( $post[ $prefix . $key ] ) ? $post[ $prefix . $key ] : $default;
		};

		// 'xpath' radio plus a filled companion field.
		$xpath_choice = function ( $key, $companion ) use ( $get ) {
			return 'xpath' === $get( $key ) && wp_all_import_is_mapped_value( $get( $companion ) );
		};

		// Post/page template and parent invert the convention: 'no' is the XPath option.
		$inverted_choice = function ( $key, $companion ) use ( $get ) {
			return 'no' === $get( $key ) && wp_all_import_is_mapped_value( $get( $companion ) );
		};

		$dates_mapped = function () use ( $get ) {
			if ( 'random' === $get( 'date_type' ) ) {
				return wp_all_import_has_template_token( $get( 'date_start' ) )
					|| wp_all_import_has_template_token( $get( 'date_end' ) );
			}

			return wp_all_import_has_template_token( $get( 'date' ) );
		};

		switch ( $section ) {
			case 'title-content':
				return wp_all_import_has_template_token( $get( 'title' ) )
					|| wp_all_import_has_template_token( $get( 'content' ) )
					|| wp_all_import_has_template_token( $get( 'post_excerpt' ) );

			case 'custom-fields':
				// A named row is the mapping; a blank value is a supported choice,
				// so it must not disqualify the row.
				foreach ( (array) $get( 'custom_name', array() ) as $name ) {
					if ( wp_all_import_is_mapped_value( $name ) ) {
						return true;
					}
				}

				return false;

			case 'taxonomies':
				foreach ( (array) $get( 'tax_assing', array() ) as $tax => $assigned ) {
					if ( empty( $assigned ) ) {
						continue;
					}

					$single = $get( 'tax_single_xpath', array() );
					$multiple = $get( 'tax_multiple_xpath', array() );
					$hierarchical = $get( 'tax_hierarchical_xpath', array() );
					$manual = $get( 'post_taxonomies', array() );
					$mapping = $get( 'tax_mapping', array() );

					// Both of these are JSON strings the step-3 submit rewrites for every
					// assigned taxonomy, so an untouched one is still non-empty: term
					// mapping arrives as "[]", and the manual hierarchy as a full tree
					// whose nodes all carry assign=false and a blank xpath.
					$rules = isset( $mapping[ $tax ] ) ? json_decode( (string) $mapping[ $tax ], true ) : null;
					$tree = isset( $manual[ $tax ] ) ? json_decode( (string) $manual[ $tax ], true ) : null;
					$tree_mapped = false;

					if ( is_array( $tree ) ) {
						foreach ( $tree as $node ) {
							if ( ! empty( $node['assign'] ) || wp_all_import_is_mapped_value( isset( $node['xpath'] ) ? $node['xpath'] : '' ) ) {
								$tree_mapped = true;
								break;
							}
						}
					}

					if ( wp_all_import_is_mapped_value( isset( $single[ $tax ] ) ? $single[ $tax ] : '' )
						|| wp_all_import_is_mapped_value( isset( $multiple[ $tax ] ) ? $multiple[ $tax ] : '' )
						|| wp_all_import_has_mapped_value( isset( $hierarchical[ $tax ] ) ? $hierarchical[ $tax ] : array() )
						|| $tree_mapped
						|| ( is_array( $rules ) && ! empty( $rules ) ) ) {
						return true;
					}
				}

				return false;

			case 'featured-image':
				// Mode-agnostic: a value the user typed stays a mapping when they flip the radio.
				// Includes the nested advanced block, which CSS hides while this parent is closed.
				return wp_all_import_is_mapped_value( $get( 'download_featured_image' ) )
					|| wp_all_import_is_mapped_value( $get( 'gallery_featured_image' ) )
					|| wp_all_import_is_mapped_value( $get( 'featured_image' ) )
					|| wp_all_import_section_has_mapping( 'images-advanced', $post, $prefix );

			case 'other':
				return $xpath_choice( 'status', 'status_xpath' )
					|| $xpath_choice( 'comment_status', 'comment_status_xpath' )
					|| $xpath_choice( 'ping_status', 'ping_status_xpath' )
					|| $xpath_choice( 'post_format', 'post_format_xpath' )
					|| $inverted_choice( 'is_multiple_page_template', 'single_page_template' )
					|| $inverted_choice( 'is_multiple_page_parent', 'single_page_parent' )
					|| ( ! empty( $get( 'is_override_post_type' ) ) && wp_all_import_is_mapped_value( $get( 'post_type_xpath' ) ) )
					// Free text with an empty default: filling it at all is deliberate,
					// so a literal counts. wp_all_import_has_mapped_value() discards the
					// '0' that order and parent default to.
					|| wp_all_import_has_mapped_value( $get( 'post_slug' ) )
					|| wp_all_import_has_mapped_value( $get( 'author' ) )
					|| wp_all_import_has_mapped_value( $get( 'attachments' ) )
					|| wp_all_import_has_mapped_value( $get( 'order' ) )
					|| wp_all_import_has_mapped_value( $get( 'parent' ) )
					// Dates default to 'now', so only a token counts.
					|| $dates_mapped();

			case 'term-other':
				return $xpath_choice( 'taxonomy_slug', 'taxonomy_slug_xpath' )
					|| $xpath_choice( 'taxonomy_display_type', 'taxonomy_display_type_xpath' )
					|| wp_all_import_has_mapped_value( $get( 'taxonomy_parent' ) );

			case 'comments-author':
				// Includes the nested advanced block, which CSS hides while this parent is closed.
				return wp_all_import_has_mapped_value( $get( 'comment_author' ) )
					|| wp_all_import_has_mapped_value( $get( 'comment_author_email' ) )
					|| wp_all_import_section_has_mapping( 'comments-author-advanced', $post, $prefix );

			case 'comments-author-advanced':
				return $xpath_choice( 'comment_user_id', 'comment_user_id_xpath' )
					|| wp_all_import_has_mapped_value( $get( 'comment_author_url' ) )
					|| wp_all_import_has_mapped_value( $get( 'comment_author_IP' ) )
					|| wp_all_import_has_mapped_value( $get( 'comment_agent' ) );

			case 'comments-content-advanced':
				// The date fields render in the parent section, not this one.
				return $xpath_choice( 'comment_approved', 'comment_approved_xpath' )
					|| $xpath_choice( 'comment_verified', 'comment_verified_xpath' )
					|| $xpath_choice( 'comment_type', 'comment_type_xpath' )
					|| wp_all_import_has_mapped_value( $get( 'comment_karma' ) )
					|| wp_all_import_has_mapped_value( $get( 'comment_parent' ) );

			case 'images-advanced':
				$paired = array(
					'set_image_meta_title'       => 'image_meta_title',
					'set_image_meta_caption'     => 'image_meta_caption',
					'set_image_meta_alt'         => 'image_meta_alt',
					'set_image_meta_description' => 'image_meta_description',
					'auto_rename_images'         => 'auto_rename_images_suffix',
					'auto_set_extension'         => 'new_extension',
				);

				foreach ( $paired as $toggle => $field ) {
					if ( ! empty( $get( $toggle ) ) && wp_all_import_is_mapped_value( $get( $field ) ) ) {
						return true;
					}
				}

				return false;
		}

		return false;
	}
}

if ( ! function_exists( 'wp_all_import_section_class' ) ) {
	/**
	 * Returns the `closed` class for a section, omitting it when the section holds
	 * mappings so it renders expanded.
	 */
	function wp_all_import_section_class( $section, $post, $prefix = '' ) {
		$closed = ! wp_all_import_section_has_mapping( $section, $post, $prefix );
		$closed = apply_filters( 'wp_all_import_template_section_closed', $closed, $section, $post, $prefix );

		return $closed ? 'closed' : '';
	}
}
