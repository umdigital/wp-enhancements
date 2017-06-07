<?php

class WPEnchancements_Navigation
{
    static public function init()
    {
        add_filter( 'nav_menu_css_class', array( __CLASS__, 'nav_menu_classes' ), 10, 2 );
        add_filter( 'bu_navigation_filter_item_attrs', array( __CLASS__, 'nav_menu_classes' ), 10, 2 );

        add_filter( 'bu_navigation_post_types', array( __CLASS__, 'bu_navigation_post_types' ) );

        add_filter( 'widget_bu_pages_args', array( __CLASS__, 'widget_bu_pages_args' ), 9999 );
    }

    // ADD ANCESTOR/CURRENT NAV CLASSES FOR CPT PARENTS
    static public function nav_menu_classes( $classes, $item )
    {
        global $wpdb;
        global $post;

        if( !is_object( $post ) ) {
            return $classes;
        }

        if( is_archive() ) {
            $postType = get_queried_object();
            $parts = explode( '/', @$postType->rewrite['slug'] );
            if( count( $parts ) > 1 ) {
                array_pop( $parts );
            }
            $slug = implode( '/', $parts );
        }
        else {
            $postType = get_post_type_object( $post->post_type );
            $slug = $postType->rewrite['slug'];
        }

        // FIND PARENT ID
        $postTypeParent = null;
        if( $slug ) {
            $parts      = explode( '/', $slug );
            $partsTotal = count( $parts );

            for( $i = 0; $i < $partsTotal; $i++ ) {
                if( $pPost = get_page_by_path( implode( '/', $parts ), OBJECT, get_post_types( array( 'public' => true ) ) ) ) {
                    $postTypeParent = $pPost->ID;
                    break;
                }
                else if( count( $parts ) == 1 ) {
                    $res = $wpdb->get_row( $wpdb->prepare(
                        "SELECT id FROM {$wpdb->posts} WHERE post_name = %s",
                        $parts[0]
                    ));

                    if( $res->id ) {
                        $postTypeParent = $res->id;
                    }

                    break;
                }

                array_pop( $parts );
            }
        }
        else if( $post->post_type == 'post' ) {
            $postTypeParent = get_option( 'page_for_posts' );
        }

        // add ancestor class
        if( $postTypeParent ) {
            $postsPageAncestors = get_post_ancestors( $postTypeParent );

            $id = $item->ID;
            if( $item->post_type == 'nav_menu_item' ) {
                $id = $item->object_id;
            }

            // if its an ancestor or the parent page then pop the ancestor on
            if( in_array( $id, $postsPageAncestors ) || (!is_home() && ($postTypeParent == $id)) ) {
                array_push( $classes, 'current_page_ancestor' );
                array_push( $classes, 'current-page-ancestor' );
            }
            else if( $item->url == get_post_type_archive_link( $post->post_type ) ) {
                if( is_archive() ) {
                    array_push( $classes, 'current_page_item' );
                    array_push( $classes, 'current-page-item' );
                }
                else {
                    array_push( $classes, 'current_page_ancestor' );
                    array_push( $classes, 'current-page-ancestor' );
                }
            }
            else if( preg_match( '#^https?://'. preg_quote( $_SERVER['HTTP_HOST'], '#' ) . preg_quote( $_SERVER['REQUEST_URI'], '#' ) .'$#i', $item->url ) ) {
                array_push( $classes, 'current_page_item' );
                array_push( $classes, 'current-page-item' );
            }
        }

        // add classes to home links
        $itemUrl = trim( preg_replace( '#^https?://#i', '', $item->url ), '/' );
        $siteUrl = trim( preg_replace( '#^https?://#i', '', get_option( 'siteurl' ) ), '/' );
        if( $itemUrl == $siteUrl ) {
            // add home class
            if( !in_array( 'menu-item-home', $classes ) ) {
                array_push( $classes, 'menu-item-home' );
            }

            // add current class
            if( is_home() ) {
                $tClasses = array(
                    'current-menu-item',
                    'current_page_item',
                    'current-page-item'
                );
                foreach( $tClasses as $tClass ) {
                    if( !in_array( $tClass, $classes ) ) {
                        array_push( $classes, $tClass );
                    }
                }
            }
        }

        return $classes;
    }

    // ADD CURRENT PAGE POST TYPE TO BU-NAV supported post types
    static public function bu_navigation_post_types( $post_types )
    {
        global $post;
        
        if( $post ) {
            $post_types['post'] = $post->post_type;
        }

        return $post_types;
    }


    // BU-NAVIGATION CHILD CPT OVERRIDES
    static public function widget_bu_pages_args( $list_args )
    {
        global $wpdb;
        global $post;

        $postType = get_post_type_object( $post->post_type );

        $slug = $postType ? $postType->rewrite['slug'] : trim( $_SERVER['REQUEST_URI'], '/' );

        // FIND PARENT ID
        $postTypeParent = null;
        if( $slug ) {
            $parts      = explode( '/', $slug );
            $partsTotal = count( $parts );

            for( $i = 0; $i < $partsTotal; $i++ ) {
                if( $pPost = get_page_by_path( implode( '/', $parts ), OBJECT, get_post_types( array( 'public' => true ) ) ) ) {
                    $postTypeParent = $pPost->ID;
                    break;
                }
                else if( count( $parts ) == 1 ) {
                    $res = $wpdb->get_row( $wpdb->prepare(
                        "SELECT id FROM {$wpdb->posts} WHERE post_name = %s",
                        $parts[0]
                    ));

                    if( $res->id ) {
                        $postTypeParent = $res->id;
                    }

                    break;
                }

                array_pop( $parts );
            }
        }
        else if( $post->post_type == 'post' ) {
            $postTypeParent = get_option( 'page_for_posts' );
        }

        if( $postTypeParent ) {
            $list_args['page_id']    = $postTypeParent;
            $list_args['post_types'] = 'page';
        }

        return $list_args;
    }
}
WPEnchancements_Navigation::init();
