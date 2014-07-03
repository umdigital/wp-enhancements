<?php
/*
Plugin Name: WP Enhancements
Description: A plugin that provides enhancements to Wordpress by adding or fixing functionality.
Version: 0.1
Author: Patrick Springstubbe
Author URI: http://springstubbe.us
*/


// ADD ANCESTOR/CURRENT NAV CLASSES FOR CPT PARENTS
function ummcmods_nav_menu_css_class( $classes, $item )
{
    global $post;

    $postType = get_post_type_object( $post->post_type );

    // FIND PARENT ID
    $postTypeParent = null;
    if( $postType->rewrite['slug'] ) {
        $parts      = explode( '/', $postType->rewrite['slug'] );
        $partsTotal = count( $parts );

        for( $i = 0; $i < $partsTotal; $i++ ) {
            if( $postTypeParent = url_to_postid( implode( '/', $parts ) ) ) {
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
    }

    return $classes;
}
add_filter( 'nav_menu_css_class', 'ummcmods_nav_menu_css_class', 10, 2 );
add_filter( 'bu_navigation_filter_item_attrs', 'ummcmods_nav_menu_css_class', 10, 2 );

// ADD CURRENT PAGE POST TYPE TO BU-NAV supported post types
function president_bu_navigation_post_types( $post_types )
{
    global $post;
    $post_types['post'] = $post->post_type;

    return $post_types;
}
add_filter( 'bu_navigation_post_types', 'president_bu_navigation_post_types' );


// BU-NAVIGATION CHILD CPT OVERRIDES
function president_widget_bu_pages_args( $list_args )
{
    global $post;

    $postType = get_post_type_object( $post->post_type );

    // FIND PARENT ID
    $postTypeParent = null;
    if( $postType->rewrite['slug'] ) {
        $parts      = explode( '/', $postType->rewrite['slug'] );
        $partsTotal = count( $parts );

        for( $i = 0; $i < $partsTotal; $i++ ) {
            if( $postTypeParent = url_to_postid( implode( '/', $parts ) ) ) {
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
add_filter( 'widget_bu_pages_args', 'president_widget_bu_pages_args', 9999 );
