<?php
/**
 * Plugin Name: WP Enhancements
 * Plugin URI: https://github.com/umichcreative/WP-Enhancements/
 * Description: A plugin that provides enhancements to Wordpress by adding or fixing functionality.
 * Version: 1.2
 * Author: U-M: Michigan Creative
 * Author URI: http://creative.umich.edu
 */

define( 'WPENHANCEMENTS_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );

include WPENHANCEMENTS_PATH .'includes'. DIRECTORY_SEPARATOR .'navigation.php';
include WPENHANCEMENTS_PATH .'includes'. DIRECTORY_SEPARATOR .'domain.php';
include WPENHANCEMENTS_PATH .'includes'. DIRECTORY_SEPARATOR .'themes.php';

function wpenhancements_github_updater_init()
{

    // UPDATER SETUP
    if( !class_exists( 'WP_GitHub_Updater' ) ) {
        include_once WPENHANCEMENTS_PATH .'includes'. DIRECTORY_SEPARATOR .'updater.php';
    }
    if( isset( $_GET['force-check'] ) && $_GET['force-check'] ) {
            define( 'WP_GITHUB_FORCE_UPDATE', true );
    }
    if( is_admin() ) {
        new WP_GitHub_Updater(array(
            // this is the slug of your plugin
            'slug' => plugin_basename(__FILE__),
            // this is the name of the folder your plugin lives in
            'proper_folder_name' => dirname( plugin_basename( __FILE__ ) ),
            // the github API url of your github repo
            'api_url' => 'https://api.github.com/repos/umichcreative/WP-Enhancements',
            // the github raw url of your github repo
            'raw_url' => 'https://raw.githubusercontent.com/umichcreative/WP-Enhancements/master',
            // the github url of your github repo
            'github_url' => 'https://github.com/umichcreative/WP-Enhancements',
             // the zip url of the github repo
            'zip_url' => 'https://github.com/umichcreative/WP-Enhancements/zipball/master',
            // wether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
            'sslverify' => true,
            // which version of WordPress does your plugin require?
            'requires' => '3.0',
            // which version of WordPress is your plugin tested up to?
            'tested' => '3.9.1',
            // which file to use as the readme for the version number
            'readme' => 'README.md',
            // Access private repositories by authorizing under Appearance > Github Updates when this example plugin is installed
            'access_token' => '',
        ));
    }
}
add_action( 'init', 'wpenhancements_github_updater_init' );
