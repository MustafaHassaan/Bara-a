<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'F|aQ}gT&,zc=a$2 QsCX~4/P`4{AxDa{JY6FY2nKd+|.9i/p;hb/kpo(5hc0mh0i' );
define( 'SECURE_AUTH_KEY',   '#t<0,_2m yIq0(:NuYc!vfKHj=rN$K8tu<8oZ?Gz$220D;.8X2(1vzLJWxM%S]bF' );
define( 'LOGGED_IN_KEY',     '`A;0$=YU%~taKf17AWl1Y(7M-(;QQTPK_hqlWy>A6=1Wsh3ZM,O@XiQi,546/IvB' );
define( 'NONCE_KEY',         'I~K/yjX-:>^9c9&g+*f(E:dEW47LknJ+Oo;bq={dISk~^tD&kzShXd9f<W`&hk8q' );
define( 'AUTH_SALT',         ';L:7c!eLa/-$6QTn7&?uoaoup49H,r4?h`jPj8/Mc0-ou3HS&/v=(n9vJ9iL>4Ff' );
define( 'SECURE_AUTH_SALT',  'XC,hknf*_Ie7OJPQW4vwx+Ovd:C*>s#f@-a7j]KEB@~$H_E>4k~I.$)Tnp9%7[R@' );
define( 'LOGGED_IN_SALT',    'sz+`Nmjutn0usT6=3M!%AEyXtI~b5_g, [`dTnKr|h,,n[SH%#f8S|C4kJ%=|>+7' );
define( 'NONCE_SALT',        'J+!b#QsF#&,2w^g#.N{p_$9Y&pUzyNo<GUgx?q;PR|G--2qkN5J~Kricr<~lpzGk' );
define( 'WP_CACHE_KEY_SALT', '!i#I3P6chBl_N`#%E_UD-*vsv}7t.TEv)u<:C.$+d$FvAl3gE,MqC)o>Lb a3Aie' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
