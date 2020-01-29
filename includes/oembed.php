<?php

class WPEnhancements_Oembed
{
    static private $_options = array(
        'fluid' => '0'
    );

    static public function init()
    {
        add_filter( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
        add_filter( 'embed_oembed_html', array( __CLASS__, 'oembedResult' ), 10, 4 );

        add_action( 'um_wpe_admin', function(){
            include WPENHANCEMENTS_PATH .'templates'. DIRECTORY_SEPARATOR .'admin_oembed.tpl';
        });

        $options = array_replace_recursive(
            array(
                'oembed' => self::$_options
            ),
            get_option( 'um_wpe_options' ) ?: array()
        );

        self::$_options = $options['oembed'];

        if( self::$_options['fluid'] ) {
            // the wp enhancements plugin will take over actually resizing the iframe in the correct aspect ratio
            add_filter( 'embed_oembed_html', function( $html, $url, $attr, $post_ID ){
                if( self::checkSourceUrl( $url ) ) {
                    // append class if class attr exists
                    if( preg_match( '#<iframe .*?(class="(.+?)")#', $html, $cMatch ) ) {
                        $html = str_replace( $cMatch[1], 'class="'. $cMatch[2] .' fluid"', $html );
                    }
                    // add class attr to iframe
                    else {
                        $html = str_replace( 'iframe ', 'iframe class="fluid" ', $html );
                    }
                }

                return $html;
            }, 10, 4 );


            add_filter( 'oembed_result', function( $html, $url, $args ){
                return apply_filters( 'embed_oembed_html', $html, $url, null, null );
            }, 10, 3);
        }
    }

    static public function enqueue()
    {
        wp_enqueue_script('umich-oembed-params', plugins_url('assets/oembed.js', dirname( __FILE__ )), array( 'jquery' ) );
    }

    static public function oembedResult( $html, $url, $attr, $post_ID )
    {
        // filter for youtube
        if( self::checkSourceUrl( $url ) == 'youtube' ) {
            if( preg_match( '/iframe .*?src="(.+?)"/i', $html, $match ) ) {
                $srcArgs = array();
                parse_str( parse_url( $match[1], PHP_URL_QUERY ), $srcArgs );

                // get orig url args
                $origArgs = array();
                parse_str( parse_url( $url, PHP_URL_QUERY ), $origArgs );
                unset( $origArgs['v'] ); // ignore

                // check width
                if( isset( $origArgs['width'] ) ) {
                    // leave width attr intact and add fluid class to iframe
                    // js will calc aspect and resize
                    if( $origArgs['width'] == '100%' ) {
                        // append class if class attr exists
                        if( preg_match( '#<iframe .*?(class="(.+?)")#', $html, $cMatch ) ) {
                            $html = str_replace( $cMatch[1], 'class="'. $cMatch[2] .' fluid"', $html );
                        }
                        // add class attr to iframe
                        else {
                            $html = str_replace( 'iframe ', 'iframe class="fluid" ', $html );
                        }
                    }
                    // change width
                    else if( $origArgs['width'] ) {
                        $html = preg_replace( '/width="(.+?)"/', 'width="'. $origArgs['width'] .'"', $html );
                    }
                    // remove width attr
                    else {
                        $html = preg_replace( '/width="(.+?)"/', '', $html );
                    }
                }

                // check height
                if( isset( $origArgs['height'] ) ) {
                    // change height
                    if( $origArgs['height'] ) {
                        $html = preg_replace( '/height="(.+?)"/', 'height="'. $origArgs['height'] .'"', $html );
                    }
                    // remove height attr
                    else {
                        $html = preg_replace( '/height="(.+?)"/', '', $html );
                    }
                }

                // rebuild iframe url with new url args
                $newArgs = array_merge( $srcArgs, $origArgs );
                $newSrcUrl = str_replace(
                    http_build_query( $srcArgs ),
                    http_build_query( $newArgs ),
                    $match[1]
                );

                $html = str_replace( $match[1], $newSrcUrl, $html );
            }
        }

        return $html;
    }

    static public function checkSourceUrl( $url )
    {
        $sources = array(
            '#https?://((m|www)\.)?youtube\.com/(watch|playlist).*#i' => 'youtube',
            '#https?://youtu\.be/.*#i'                                => 'youtube'
        );

        foreach( $sources as $regex => $source ) {
            if( preg_match( $regex, $url ) ) {
                return $source;
            }
        }
    }
}
WPEnhancements_Oembed::init();
