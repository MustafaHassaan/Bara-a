<?php
// phpcs:disable WordPress.WP.AlternativeFunctions
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The two per-import options that decide how a source file is turned into XML:
 * `csv_escape` (auto | rfc | backslash) and `keep_4byte_chars` (0 | 1).
 *
 * Neither key is in get_default_import_options(): that array is merged into every
 * saved import, and an import saved before these options existed must keep reading
 * its file the way it always has. A missing key means backslash and stripping.
 */
class PMXI_ParseOptions {

	const ESCAPE = 'csv_escape';
	const KEEP   = 'keep_4byte_chars';

	const STRIP_PATTERN = '/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u';
	const KEEP_PATTERN  = '/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]+/u';

	private static $database_supports_4byte = null;

	public static function for_new_import() {
		return array(
			self::ESCAPE => PMXI_CsvDialect::AUTO,
			self::KEEP   => 1,
		);
	}

	public static function legacy() {
		return array(
			self::ESCAPE => PMXI_CsvDialect::BACKSLASH,
			self::KEEP   => 0,
		);
	}

	public static function from_import_options( $options ) {
		return array(
			self::ESCAPE => PMXI_CsvDialect::mode_of( $options ),
			self::KEEP   => ( is_array( $options ) && ! empty( $options[ self::KEEP ] ) ) ? 1 : 0,
		);
	}

	/**
	 * Adds the new-import values for whichever of the two keys the options lack.
	 */
	public static function stamp_new_import( $options ) {
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		return $options + self::for_new_import();
	}

	/**
	 * Keeps a stored value when an options write omits the key, so replacing an import's
	 * options wholesale cannot turn a new import back into a legacy one.
	 */
	public static function carry_over( $previous, $options ) {
		if ( ! is_array( $previous ) || ! is_array( $options ) ) {
			return $options;
		}
		foreach ( array( self::ESCAPE, self::KEEP ) as $key ) {
			if ( array_key_exists( $key, $previous ) && ! array_key_exists( $key, $options ) ) {
				$options[ $key ] = $previous[ $key ];
			}
		}
		return $options;
	}

	/**
	 * A loaded template never changes how the import reads its file, except that a CSV format
	 * named by WP All Export decides it for an import set to Automatic.
	 */
	public static function apply_to_template( $current, $template_options ) {
		if ( ! is_array( $template_options ) ) {
			return $template_options;
		}
		$current = is_array( $current ) ? $current : array();
		$marker  = PMXI_CsvDialect::marker_of( $template_options );
		foreach ( array( self::ESCAPE, self::KEEP ) as $key ) {
			if ( array_key_exists( $key, $current ) ) {
				$template_options[ $key ] = $current[ $key ];
			} else {
				unset( $template_options[ $key ] );
			}
		}
		if ( null !== $marker && isset( $current[ self::ESCAPE ] ) && PMXI_CsvDialect::AUTO === $current[ self::ESCAPE ] ) {
			$template_options[ self::ESCAPE ] = $marker;
		}
		return $template_options;
	}

	/**
	 * Steps 3 and 4 of the wizard start from the session; the import's two values join the options there
	 * rather than at Step 1, because a non-empty session options array is what tells the wizard that
	 * Step 3 has been visited. The import record decides first: every record created by this version was
	 * stamped by insert(), whichever code wrote the wizard session, while a record saved before it has no
	 * keys and stays legacy. Only an import with no record yet falls back to the session.
	 */
	public static function with_wizard_defaults( $options, $import = null ) {
		if ( ! is_array( $options ) ) {
			return $options;
		}
		if ( is_object( $import ) && method_exists( $import, 'isEmpty' ) && ! $import->isEmpty() ) {
			$stored = is_array( $import->options ) ? $import->options : array();
			return $options + array_intersect_key( $stored, array_flip( array( self::ESCAPE, self::KEEP ) ) );
		}
		if ( ! empty( PMXI_Plugin::$session ) ) {
			$from_session = PMXI_Plugin::$session->get( 'parse_options' );
			if ( is_array( $from_session ) ) {
				return $options + self::from_import_options( $from_session );
			}
		}
		return $options;
	}

	public static function for_import_id( $import_id ) {
		if ( empty( $import_id ) || ! is_numeric( $import_id ) ) {
			return null;
		}
		$import = new PMXI_Import_Record();
		$import->getById( (int) $import_id );
		if ( $import->isEmpty() ) {
			return null;
		}
		return self::from_import_options( $import->options );
	}

	/**
	 * The options of the import this request works on: the import named by the request or
	 * the CLI, then the wizard session of an import not saved yet, then legacy behavior.
	 */
	public static function current() {
		$from_import = self::for_import_id( function_exists( 'wp_all_import_get_import_id' ) ? wp_all_import_get_import_id() : 0 );
		if ( null !== $from_import ) {
			return $from_import;
		}
		if ( ! empty( PMXI_Plugin::$session ) ) {
			$from_session = PMXI_Plugin::$session->get( 'parse_options' );
			if ( is_array( $from_session ) ) {
				return self::from_import_options( $from_session );
			}
		}
		return self::legacy();
	}

	public static function csv_parser_args( $parse_options ) {
		return array(
			'csv_escape'       => PMXI_CsvDialect::mode_of( $parse_options ),
			'keep_4byte_chars' => self::keep_4byte_chars( $parse_options ),
		);
	}

	public static function keep_requested( $parse_options ) {
		return is_array( $parse_options ) && ! empty( $parse_options[ self::KEEP ] );
	}

	/**
	 * A 4-byte character written to a table that is not utf8mb4 loses the whole value or
	 * the whole record, so it is only kept where every table it can reach can store it.
	 */
	public static function keep_4byte_chars( $parse_options ) {
		return self::keep_requested( $parse_options ) && self::database_supports_4byte();
	}

	public static function database_supports_4byte() {
		if ( null === self::$database_supports_4byte ) {
			global $wpdb;
			$supported = true;
			if ( isset( $wpdb ) && is_object( $wpdb ) && method_exists( $wpdb, 'get_col_charset' ) ) {
				$columns = array(
					array( $wpdb->posts, 'post_title' ),
					array( $wpdb->posts, 'post_content' ),
					array( $wpdb->postmeta, 'meta_value' ),
					array( $wpdb->terms, 'name' ),
					array( $wpdb->prefix . 'pmxi_posts', 'unique_key' ),
				);
				foreach ( $columns as $column ) {
					$charset = $wpdb->get_col_charset( $column[0], $column[1] );
					if ( is_string( $charset ) && '' !== $charset && 'utf8mb4' !== $charset ) {
						$supported = false;
						break;
					}
				}
			}
			self::$database_supports_4byte = (bool) apply_filters( 'wp_all_import_database_supports_4byte_chars', $supported );
		}
		return self::$database_supports_4byte;
	}

	public static function reset_cache() {
		self::$database_supports_4byte = null;
	}

	public static function invalid_chars_pattern( $keep_4byte_chars ) {
		return $keep_4byte_chars ? self::KEEP_PATTERN : self::STRIP_PATTERN;
	}

	public static function remove_invalid_chars( $value, $keep_4byte_chars ) {
		return preg_replace( self::invalid_chars_pattern( $keep_4byte_chars ), ' ', $value );
	}

	public static function log_messages( $csv_dialect ) {
		if ( empty( $csv_dialect ) || ! is_array( $csv_dialect ) ) {
			return array();
		}
		$mode     = isset( $csv_dialect['mode'] ) ? $csv_dialect['mode'] : '';
		$detected = isset( $csv_dialect['detected'] ) ? $csv_dialect['detected'] : '';
		$messages = array();
		if ( PMXI_CsvDialect::AUTO === $mode ) {
			if ( PMXI_CsvDialect::BACKSLASH === $detected ) {
				$messages[] = __( 'CSV format: Automatic, read as Backslash-escaped (PHP).', 'wp-all-import' );
			} elseif ( PMXI_CsvDialect::RFC === $detected ) {
				$messages[] = __( 'CSV format: Automatic, read as Standard (RFC 4180).', 'wp-all-import' );
			} else {
				$messages[] = __( 'CSV format: Automatic, no backslash before a quote, read as Standard (RFC 4180).', 'wp-all-import' );
			}
		} elseif ( PMXI_CsvDialect::BACKSLASH === $mode && PMXI_CsvDialect::RFC === $detected ) {
			$messages[] = __( '<b>WARNING</b>: This file looks like standard CSV (RFC 4180), but the import reads it as Backslash-escaped (PHP), so values with a backslash before a quote may be misread. To read it correctly, set CSV format to Automatic in Import Settings.', 'wp-all-import' );
		}
		return $messages;
	}

	public static function keep_skipped_message() {
		return __( '<b>WARNING</b>: Emoji and other 4-byte characters are removed because the database tables cannot store them.', 'wp-all-import' );
	}
}
