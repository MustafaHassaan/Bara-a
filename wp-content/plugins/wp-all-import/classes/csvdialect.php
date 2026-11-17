<?php
// phpcs:disable WordPress.WP.AlternativeFunctions
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * How a CSV file escapes a quote inside a quoted value: RFC 4180 (a doubled quote)
 * or PHP's backslash escape. Only an odd run of backslashes directly before a quote
 * reads differently in the two dialects, so a file without one needs no decision.
 */
class PMXI_CsvDialect {

	const AUTO      = 'auto';
	const RFC       = 'rfc';
	const BACKSLASH = 'backslash';
	const NONE      = 'none';

	const CHUNK_SIZE = 1048576;

	const CACHE_TTL = 86400;

	private static $memo = array();

	public static function modes() {
		return array( self::AUTO, self::RFC, self::BACKSLASH );
	}

	/**
	 * Imports saved before the setting existed have no key and keep the backslash escape.
	 */
	public static function mode_of( $options ) {
		$mode = ( is_array( $options ) && isset( $options['csv_escape'] ) ) ? $options['csv_escape'] : '';
		return in_array( $mode, self::modes(), true ) ? $mode : self::BACKSLASH;
	}

	/**
	 * The `csv_escape` key a WP All Export bundle writes into its template options.
	 */
	public static function marker_of( $options ) {
		if ( is_array( $options ) && isset( $options['csv_escape'] ) && in_array( $options['csv_escape'], array( self::RFC, self::BACKSLASH ), true ) ) {
			return $options['csv_escape'];
		}
		return null;
	}

	public static function escape_char( $dialect ) {
		return self::RFC === $dialect ? '' : '\\';
	}

	public static function telemetry_value( $mode, $detected ) {
		if ( self::AUTO === $mode ) {
			return 'auto-' . ( in_array( $detected, array( self::RFC, self::BACKSLASH ), true ) ? $detected : self::NONE );
		}
		return self::RFC === $mode ? self::RFC : self::BACKSLASH;
	}

	/**
	 * Decide the dialect from the whole file.
	 *
	 * @param bool $scan_only Report a file with mixed quote runs as undecided instead of reading it four more times.
	 * @return array { dialect: rfc|backslash|none, reason: string }
	 */
	public static function detect( $file, $delimiter, $length = 999999, $scan_only = false ) {
		$key = self::cache_key( $file, $delimiter, $length, $scan_only );
		if ( null !== $key ) {
			if ( isset( self::$memo[ $key ] ) ) {
				return self::$memo[ $key ];
			}
			$cached = function_exists( 'get_transient' ) ? get_transient( $key ) : false;
			if ( is_array( $cached ) && isset( $cached['dialect'], $cached['reason'] ) ) {
				return self::$memo[ $key ] = $cached;
			}
		}

		$result = self::decide( $file, $delimiter, $length, $scan_only );

		if ( null !== $key ) {
			self::$memo[ $key ] = $result;
			if ( function_exists( 'set_transient' ) ) {
				set_transient( $key, $result, self::CACHE_TTL );
			}
		}
		return $result;
	}

	public static function reset_cache() {
		self::$memo = array();
	}

	// Upload, set-up and each run re-read the same file; anything that rewrites it changes the stat or the first 64 KB.
	private static function cache_key( $file, $delimiter, $length, $scan_only ) {
		$stat = @stat( $file );
		if ( false === $stat ) {
			return null;
		}
		$head = @file_get_contents( $file, false, null, 0, 65536 );
		$path = realpath( $file );
		return 'pmxi_csv_dialect_' . md5( implode( '|', array( false === $path ? $file : $path, $stat['size'], $stat['mtime'], $stat['ino'], md5( (string) $head ), $delimiter, $length, $scan_only ? 1 : 0 ) ) );
	}

	private static function decide( $file, $delimiter, $length, $scan_only ) {
		$delimiter = ( "\\t" === $delimiter ) ? "\t" : (string) $delimiter;
		if ( '' === $delimiter ) {
			$delimiter = ',';
		}

		$scan = self::scan( $file, $delimiter );
		if ( false === $scan ) {
			return self::result( self::NONE, 'file not readable' );
		}
		list( $hits, $odd, $even ) = $scan;

		if ( 0 === $hits ) {
			return self::result( self::NONE, 'no backslash before a quote' );
		}
		if ( $odd >= 1 && $odd >= 2 * $even ) {
			return self::result( self::BACKSLASH, "quote runs after a backslash: odd $odd, even $even" );
		}
		if ( $even >= 1 && $even >= 2 * $odd ) {
			return self::result( self::RFC, "quote runs after a backslash: even $even, odd $odd" );
		}

		if ( $scan_only ) {
			return self::result( self::NONE, "mixed quote runs after a backslash: odd $odd, even $even" );
		}

		$backslash = self::read_summary( $file, $delimiter, '\\', $length );
		$rfc       = self::read_summary( $file, $delimiter, '', $length );
		if ( $backslash['hash'] === $rfc['hash'] ) {
			return self::result( self::NONE, 'both readings agree' );
		}

		$tokens_backslash = self::tokenize( $file, $delimiter, '\\' );
		$tokens_rfc       = self::tokenize( $file, $delimiter, '' );
		if ( $tokens_backslash['open_at_end'] !== $tokens_rfc['open_at_end'] ) {
			return self::result( $tokens_backslash['open_at_end'] ? self::RFC : self::BACKSLASH, 'unterminated quote at the end of one reading' );
		}
		if ( $backslash['consistent'] !== $rfc['consistent'] ) {
			return self::result( $backslash['consistent'] > $rfc['consistent'] ? self::BACKSLASH : self::RFC, "rows at the header width: backslash {$backslash['consistent']}, rfc {$rfc['consistent']}" );
		}
		if ( $tokens_backslash['stray'] !== $tokens_rfc['stray'] ) {
			return self::result( $tokens_backslash['stray'] < $tokens_rfc['stray'] ? self::BACKSLASH : self::RFC, "characters after a closing quote: backslash {$tokens_backslash['stray']}, rfc {$tokens_rfc['stray']}" );
		}

		return self::result( self::RFC, 'tie' );
	}

	private static function result( $dialect, $reason ) {
		return array( 'dialect' => $dialect, 'reason' => $reason );
	}

	/**
	 * Streams the file once: counts odd backslash runs before a quote, and the parity of
	 * the quote run that follows one. A backslash writer leaves the run odd; an RFC writer
	 * doubles every quote inside a value, so the run is even.
	 *
	 * @return array|false array( hits, odd, even )
	 */
	private static function scan( $file, $delimiter ) {
		$handle = @fopen( $file, 'rb' );
		if ( ! $handle ) {
			return false;
		}

		$evidence = '/(?<!\\\\)(?:\\\\\\\\)*\\\\"/';
		$parity   = '/(?<!\\\\)(?:\\\\\\\\)*\\\\("+)(?=[^"' . preg_quote( $delimiter, '/' ) . '\r\n])/';

		$hits = $odd = $even = 0;
		$tail = '';
		while ( ! feof( $handle ) ) {
			$data = fread( $handle, self::CHUNK_SIZE );
			if ( false === $data || '' === $data ) {
				break;
			}
			$chunk    = $tail . $data;
			$tail_len = strlen( $tail );

			// A match that ends inside the carried tail was already counted in the previous chunk.
			if ( preg_match_all( $evidence, $chunk, $matches, PREG_OFFSET_CAPTURE ) ) {
				foreach ( $matches[0] as $match ) {
					if ( $match[1] + strlen( $match[0] ) > $tail_len ) {
						$hits++;
					}
				}
			}
			if ( preg_match_all( $parity, $chunk, $matches, PREG_OFFSET_CAPTURE ) ) {
				foreach ( $matches[0] as $i => $match ) {
					if ( $match[1] + strlen( $match[0] ) >= $tail_len ) {
						( strlen( $matches[1][ $i ][0] ) % 2 ) ? $odd++ : $even++;
					}
				}
			}

			$cut = max( 0, strlen( $chunk ) - 64 );
			while ( $cut > 0 && ( '\\' === $chunk[ $cut - 1 ] || '"' === $chunk[ $cut - 1 ] ) ) {
				$cut--;
			}
			$tail = (string) substr( $chunk, $cut );
		}
		fclose( $handle );

		return array( $hits, $odd, $even );
	}

	/**
	 * Reads the file the way PMXI_CsvParser::toXML() does, with its line-ending filter where the
	 * edition has one, and summarizes the rows without keeping them in memory.
	 */
	private static function read_summary( $file, $delimiter, $escape, $length ) {
		$handle = fopen( $file, 'rb' );
		if ( in_array( 'pmxi_line_ending', stream_get_filters(), true ) ) {
			stream_filter_append( $handle, 'pmxi_line_ending' );
		}

		$hash       = hash_init( 'md5' );
		$header     = null;
		$consistent = 0;
		while ( $row = fgetcsv( $handle, $length, $delimiter, '"', $escape ) ) {
			$empty = 0;
			foreach ( $row as $cell ) {
				if ( empty( $cell ) || preg_replace( '%\s%', '', (string) $cell ) == '' ) {
					$empty++;
				}
			}
			if ( $empty == count( $row ) ) {
				continue;
			}
			hash_update( $hash, serialize( $row ) );
			if ( null === $header ) {
				$header = count( $row );
			} elseif ( count( $row ) === $header ) {
				$consistent++;
			}
		}
		fclose( $handle );

		return array(
			'hash'       => hash_final( $hash ),
			'consistent' => $consistent,
		);
	}

	/**
	 * A small CSV tokenizer: whether a reading ends inside an open quote, and how many
	 * characters follow a closing quote before the next delimiter.
	 */
	private static function tokenize( $file, $delimiter, $escape ) {
		$handle = fopen( $file, 'rb' );

		$state      = 0;
		$stray      = 0;
		$skip_next  = false;
		$carry_cr   = false;
		$enclosure  = '"';
		while ( ! feof( $handle ) ) {
			$data = fread( $handle, self::CHUNK_SIZE );
			if ( false === $data || '' === $data ) {
				break;
			}
			if ( $carry_cr ) {
				$data     = "\r" . $data;
				$carry_cr = false;
			}
			if ( "\r" === substr( $data, -1 ) && ! feof( $handle ) ) {
				$data     = substr( $data, 0, -1 );
				$carry_cr = true;
			}
			$buffer = str_replace( array( "\r\n", "\r" ), "\n", $data );
			$n      = strlen( $buffer );
			for ( $i = 0; $i < $n; $i++ ) {
				if ( $skip_next ) {
					$skip_next = false;
					continue;
				}
				$c = $buffer[ $i ];
				if ( 2 === $state ) {
					if ( '' !== $escape && $c === $escape ) {
						if ( $i + 1 < $n ) {
							$i++;
						} else {
							$skip_next = true;
						}
						continue;
					}
					if ( $c === $enclosure ) {
						$state = 3;
					}
					continue;
				}
				if ( 3 === $state ) {
					if ( $c === $enclosure ) {
						$state = 2;
						continue;
					}
					$state = 4;
				}
				if ( $c === $delimiter || "\n" === $c ) {
					$state = 0;
					continue;
				}
				if ( 4 === $state ) {
					$stray++;
					$state = 1;
					continue;
				}
				if ( 0 === $state ) {
					$state = ( $c === $enclosure ) ? 2 : 1;
				}
			}
		}
		fclose( $handle );

		return array(
			'open_at_end' => ( 2 === $state ),
			'stray'       => $stray,
		);
	}
}
