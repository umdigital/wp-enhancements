<?php

/* SSL FIXES */
class WPEnchancements_SSL
{
    static public function init()
    {
        add_filter( 'admin_url', array( __CLASS__, 'fixAdminAjaxUrl' ), 99, 3 );
    }

    static public function fixAdminAjaxUrl( $url, $path, $blogID )
    {
        if( $path == 'admin-ajax.php' ) {
            $isHTTPS = isset( $_SERVER['HTTPS'] ) ? true : false;
            $url = preg_replace( '#^https?:#', 'http'. ($isHTTPS ? 's' : null) .':', $url );
        }

        return $url;
    }
}
WPEnchancements_SSL::init();
