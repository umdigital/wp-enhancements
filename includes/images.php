<?php

/* IMAGE OPTIMIZATIONS */
class WPEnchancements_Images
{
    static private $_cacheAutoPurge = 7; // 7 days

    static public function init()
    {
        self::$_cacheAutoPurge = 60 * 60 * 24 * (self::$_cacheAutoPurge >= 1 ? self::$_cacheAutoPurge : 1);

        // we don't need any of this to happen on the admin side
        if( !is_admin() ) {
            add_filter( 'wp_get_attachment_image_src', array( __CLASS__, 'imageSize' ), 10, 3 );
            add_filter( 'wp_calculate_image_srcset', array( __CLASS__, 'imageSizeSrcSet' ), 10, 5 );
            add_filter( 'wp_get_attachment_url', array( __CLASS__, 'imageLink' ), 10 , 2 );
        }

        // cyclone slider cache path undo's
        // cyclone slider manually manipulates the filename and leads to an inaccurate location
        add_filter( 'cycloneslider_image_url', array( __CLASS__, 'cycloneImagePath' ) );
        add_filter( 'cycloneslider_image_path', array( __CLASS__, 'cycloneImagePath' ) );
        add_filter( 'cycloneslider_view_vars', array( __CLASS__, 'cycloneViewVars' ) );

        // rebuild image cache if it 404's
        add_action( 'template_redirect', function(){
            if( is_404() ) {
                $wpUpload = wp_get_upload_dir();
                $url = 'http'. (@$_SERVER['HTTPS'] ? 's' : null) .'://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

                if( strpos( $url, $wpUpload['baseurl'] ) !== false ) {
                    $uploadPath = parse_url( $wpUpload['baseurl'], PHP_URL_PATH );

                    $sourceFile = preg_replace(
                        '/^'. preg_quote( $uploadPath, '/' ) .'/',
                        '',
                        parse_url( $url, PHP_URL_PATH )
                    );
                    $source = str_replace( '/mc-image-cache/', '/', $wpUpload['basedir'] . $sourceFile );

                    if( file_exists( $source ) ) {
                        wp_redirect(
                            self::_getCacheImage( str_replace( '/mc-image-cache/', '/', $url ) )
                        );
                        exit;
                    }
                }
            }
        });

        self::_cleanup();
    }

    // handle replacing full image src with cached image src
    static public function imageSize( $image, $id, $size )
    {
        // nothing to do
        if( !$image ) {
            return $image;
        }

        // prepare editor/load remote image
        if( $size == 'full' && wp_attachment_is_image( $id ) ) {
            $image[0] = self::_getCacheImage( $image[0] );
        }
        // check in case its a unresizable $size and is using the full image
        else if( wp_basename( $image[0] ) == wp_basename( get_attached_file( $id ) ) ) {
            $image[0] = self::_getCacheImage( $image[0] );
        }
        // sometimes WP does str_replace on the base url which we modified
        else if( strpos( $image[0], 'mc-image-cache/' ) !== false ) {
            $image[0] = str_replace( 'mc-image-cache/', '', $image[0] );
        }

        return $image;
    }

    // update full image size in srcset with cached image src
    static public function imageSizeSrcSet( $sources, $sizes, $src, $meta, $id )
    {
        $wpUpload   = wp_upload_dir();
        $uploadPath = parse_url( $wpUpload['baseurl'], PHP_URL_PATH );

        // run through each source looking for the original one
        foreach( $sources as &$source ) {
            if( wp_basename( $source['url'] ) == wp_basename( $meta['file'] ) ) {
                $source['url'] = self::_getCacheImage( $source['url'] );
                break; // stop once its found
            }
        }
        unset( $source );

        return $sources;
    }

    // update full image url
    static public function imageLink( $link, $id )
    {
        if( wp_attachment_is_image( $id ) && (wp_basename( $link ) == wp_basename( get_attached_file( $id ) )) ) {
            $link = self::_getCacheImage( $link );
        }

        return $link;
    }

    // never use the cache for cyclone
    static public function cycloneImagePath( $path )
    {
        return str_replace( 'mc-image-cache/', '', $path );
    }

    // remove the cache path from images as we never want to use it in this case
    static public function cycloneViewVars( $vars )
    {
        foreach( $vars['slides'] as &$slide ) {
            //$slide['full_image_url'] = str_replace( 'mc-image-cache/', '', $slide['full_image_url'] );
            $slide['image_url'] = str_replace( 'mc-image-cache/', '', $slide['image_url'] );

            if( isset( $slide['image_thumbnails'] ) && is_array( $slide['image_thumbnails'] ) ) {
                foreach( $slide['image_thumbnails'] as &$thumb ) {
                    $thumb = str_replace( 'mc-image-cache/', '', $thumb );
                }
            }
        }
        unset( $slide );

        return $vars;
    }

    // generate an image cache file for the specified image
    static private function _getCacheImage( $src )
    {
        if( strpos( $src, 'mc-image-cache/' ) !== false ) {
            return $src;
        }

        $wpUpload   = wp_upload_dir();
        $uploadPath = parse_url( $wpUpload['baseurl'], PHP_URL_PATH );
        $sourceFile = preg_replace(
            '/^'. preg_quote( $uploadPath, '/' ) .'/',
            '',
            parse_url( $src, PHP_URL_PATH )
        );
        $source     = $wpUpload['basedir'] . $sourceFile;

        // prepare destination
        $tmp = array(
            $wpUpload['basedir'],
            'mc-image-cache',
            trim( dirname( $sourceFile ), '/' )
        );
        $cachePath    = implode( DIRECTORY_SEPARATOR, $tmp );
        $cacheFile    = $cachePath . DIRECTORY_SEPARATOR . basename( $sourceFile );
        $cacheFileURL = str_replace( $sourceFile, '', $src ) . str_replace( $wpUpload['basedir'], '', $cacheFile );

        // check if we already of the image cached and cache is still good
        if( file_exists( $cacheFile ) && (filemtime( $cacheFile ) >= filemtime( $source )) ) {
            return $cacheFileURL;
        }

        // prevent race condition on updating image
        if( file_exists( $cacheFile ) ) {
            @touch( $cacheFile );
        }

        // run the image through the WP image editor
        // to help optimize the size of the file
        $img = wp_get_image_editor( $source );
        if( !is_wp_error( $img ) ) {
            $img->set_quality();

            // make storage directory
            wp_mkdir_p( $cachePath );

            $img->save( $cacheFile );

            return $cacheFileURL;
        }

        return $src;
    }

    static private function _cleanup()
    {
        // 10% chance of cleanup
        if( mt_rand( 1, 10 ) == 3 ) {
            $wpUpload = wp_upload_dir();
            $tmp = array(
                $wpUpload['basedir'],
                'mc-image-cache'
            );
            $cachePath = implode( DIRECTORY_SEPARATOR, $tmp );

            self::_cleanupDir( $cachePath, self::$_cacheAutoPurge, true );
        }
    }

    static private function _cleanupDir( $dir, $expires, $recursive = false )
    {
        foreach( glob( $dir . DIRECTORY_SEPARATOR .'*' ) as $file ) {
            if( is_dir( $file ) ) {
                if( $recursive ) {
                    self::_cleanupDir( $file, $expires, $recursive );
                }
            }
            else if( (filemtime( $file ) + $expires) < time() ) {
                unlink( $file );
            }
        }

        // dump directory if its empty
        if( !glob( $dir . DIRECTORY_SEPARATOR .'*' ) ) {
            if( is_dir( $dir ) ) {
                rmdir( $dir );
            }
        }
    }
}

WPEnchancements_Images::init();
