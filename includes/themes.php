<?php

class WPEnchancements_Themes
{
    static public function init()
    {
        add_filter( 'http_request_args', array( __CLASS__, '_filterUpdates' ), 5, 2 );
    }

    /** ADD Ability to disable wordpress.org theme update checks **/
    static public function _filterUpdates( $args, $url )
    {
        if( preg_match( '#^https?://api\.wordpress\.org/themes/update-check#', $url ) ) {
            $themes = json_decode( $args['body']['themes'], true );

            foreach( $themes['themes'] as $key => $theme ) {
                $themeObj = wp_get_theme( $key );
                if( $themeObj->get( 'WP Update' ) == 'false' ) {
                    unset( $themes['themes'][ $key ] );
                }
            }
                                                                                      
            $args['body']['themes'] = json_encode( $themes );
        }

        return $args;
    }
}

WPEnchancements_Themes::init();
