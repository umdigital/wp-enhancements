<?php

/* WPMU Domain Mapping Tweaks */
function wpenchancements_wp_get_nav_menu_items( $items )
{
    if( defined( 'DOMAIN_MAPPING' ) && function_exists( 'get_original_url' ) ) {
        foreach( $items as &$item ) {
            $item->url = str_replace(
                preg_replace( '#^https?://#i', '', get_original_url( 'siteurl' ) ),
                preg_replace( '#^https?://#i', '', get_option( 'siteurl' ) ),
                $item->url
            );

            $item->guid = str_replace(
                preg_replace( '#^https?://#i', '', get_original_url( 'siteurl' ) ),
                preg_replace( '#^https?://#i', '', get_option( 'siteurl' ) ),
                $item->guid
            );
        }
    }

    return $items;
}
add_filter( 'wp_get_nav_menu_items', 'wpenchancements_wp_get_nav_menu_items', 10 );

function wpenchancements_string_url_fix( $string )
{
    if( defined( 'DOMAIN_MAPPING' ) && function_exists( 'get_original_url' ) ) {
        $string = str_replace(
            preg_replace( '#^https?://#i', '', get_original_url( 'siteurl' ) ),
            preg_replace( '#^https?://#i', '', get_option( 'siteurl' ) ),
            $string
        );
    }

    return $string;
}
add_filter( 'theme_mod_header_image', 'wpenchancements_string_url_fix' );
add_filter( 'the_content', 'wpenchancements_string_url_fix' );
