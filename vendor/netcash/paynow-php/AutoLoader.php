<?php
namespace Netcash\PayNow;

/**
 * Class AutoLoader
 * For use when composer is not available or used.
 * @package Netcash\PayNow;
 *
 * Usage:
 *
 *    require 'path/to/AutoLoader.php';
 *    \Netcash\PayNow\AutoLoader::register();
 */
class AutoLoader {

    /**
     * Registers AutoLoader as an SPL autoloader.
     */
    public static function register() {
        ini_set( 'unserialize_callback_func', 'spl_autoload_call' );
        spl_autoload_register( array( 'Netcash\PayNow\AutoLoader', 'autoload' ) );
    }

    /**
     * Handles autoloading of classes.
     *
     * @param string $class A class name.
     */
    public static function autoload( $class ) {
        $parts    = explode( '\\', $class );
        $dir      = "";
        $src = "src/";

        if ( $parts[0] === 'Netcash' ) {
            $dir = "{$src}";
        }

        if ( strpos( $class, 'Exceptions' ) > 0 ) {
            $dir = "{$src}Exceptions";
        }
        if ( strpos( $class, 'Types' ) > 0 ) {
            $dir = "{$src}Types";
        }

        $classOnly = preg_replace( "/(.+\\\\)/i", "", $class );

        $file = dirname( __FILE__ ) . "/{$dir}/{$classOnly}.php";

        if ( ! is_file( $file ) ) {
            return;
        }

        require $file;
    }
}
