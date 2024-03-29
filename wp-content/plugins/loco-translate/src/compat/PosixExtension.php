<?php
/**
 * Abstraction of PHP "posix" extension.
 * Basic functionality substitution, but cannot get user/group names so falls back to numeric
 */
abstract class Loco_compat_PosixExtension {

    /**
     * @var int|null
     */
    private static $uid = null;

    /**
     * @var int|null
     */
    private static $gid = null;
    
    
    /**
     * @return int
     */
    public static function getuid(){
        if( is_null(self::$uid) ){
            // use posix function if extension available
            if( function_exists('posix_geteuid') ){
                self::$uid = posix_geteuid();
            }
            // else use temp file system to establish owner
            else {
                self::$uid = self::getuidViaTempDir(); // @codeCoverageIgnore
            }
        }
        return self::$uid;
    }

    
    /**
     * @return int
     */
    public static function getgid(){
        if( is_null(self::$gid) ){
            // use posix function if extension available
            if( function_exists('posix_getegid') ){
                self::$gid = posix_getegid();
            }
            // else use temp file system to establish group owner
            else {
                self::$gid = self::getgidViaTempDir(); // @codeCoverageIgnore
            }
        }
        return self::$gid;
    }


    /**
     * Attempt to get effective user ID by reading a temporary file
     * @return int
     */
    public static function getuidViaTempDir( $dir = '' ){
        if( ! $dir ) {
            $dir = get_temp_dir();
        }
        if( 04000 & fileperms($dir) ){
            trigger_error( sprintf('%s directory has setuid bit, getuid may not be accurate',basename($dir) ) );
        }
        $path = wp_tempnam( 'loco-sniff-'.time(), $dir );
        $uid = fileowner($path);
        unlink( $path );

        return $uid;
    }


    /**
     * Attempt to get effective group ID by reading a temporary file
     * @return int
     */
    public static function getgidViaTempDir( $dir = '' ){
        if( ! $dir ) {
            $dir = get_temp_dir();
        }
        if( 02000 & fileperms($dir) ){
            trigger_error( sprintf('%s directory has setgid bit, getgid may not be accurate',basename($dir) ) );
        }
        $path = wp_tempnam( 'loco-sniff-'.time(), $dir );
        $gid = filegroup($path);
        unlink( $path );

        return $gid;
    }


    /**
     * Get the name of the user that the web server runs under
     * This is only for visual/info purposes.
     * @return string
     */
     public static function getHttpdUser(){
        if( function_exists('posix_getpwuid') ){
            $info = posix_getpwuid( self::getuid() );
            if( isset($info['name']) ){
                return $info['name'];
            }
        }
        // @codeCoverageIgnoreStart
        foreach( ['apache','nginx'] as $name ){
            if( false !== stripos(PHP_SAPI,$name) ){
                return $name;
            }
            if( isset($_SERVER['SERVER_SOFTWARE']) && false !== stripos($_SERVER['SERVER_SOFTWARE'],$name)  ){
                return $name;
            }
        }
        // translators: used when username of web server process is unknown
        return __('the web server','loco-translate');
        // @codeCoverageIgnoreEnd
     }

}
