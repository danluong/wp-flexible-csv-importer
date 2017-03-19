<?php
/*
Plugin Name: WP Flexible CSV Importer
Plugin URI:  https://leonstafford.github.io
Description: Ease the pain of CSV imports - do it all within WP interface
Version:     0.1
Author:      Leon Stafford
Author URI:  https://leonstafford.github.io
Text Domain: wp-flexible-csv-importer

Copyright (c) 2017 Leon Stafford
 */

require_once 'library/WPFlexibleCSVImporter.php';

// this needed to come outside of the main plugin instantiation block
add_action( 'wp_ajax_create_post', 'create_post' );

function create_post() {
    error_log('creating post via ajax call');

    // insert the post and set the category
    $post_id = wp_insert_post(array (
        'post_type' => 'post',
        'post_title' => $_POST['title'],
        'post_content' => $_POST['content'],
        'post_status' => 'publish',
    ));

    if ($post_id) {
        // insert post meta(s)
        add_post_meta($post_id, 'some_field', 'some value');
    }


    wp_die();
}

if(has_action('wp_ajax_create_post')) {
        // action exists 

} else {
        // action has not been registered
        error_log('OOPS cant find action AFTER DECLARING IT');
}      

if ( is_admin() && defined('WP_LOAD_IMPORTERS') ) {

	require_once ABSPATH . 'wp-admin/includes/import.php';

	if ( ! class_exists( 'WP_Importer' ) ) {
		$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
		if ( file_exists( $class_wp_importer ) ) {
			require_once $class_wp_importer;
		}
	}

	$wpFlexibleCSVImporter = new WPFlexibleCSVImporter();
	register_importer(
		'wp-flexible-csv-importer',
		__('WP Flexible CSV Importer', 'wp-flexible-csv-importer'),
		__('Import posts, and custom fields from any csv file.', 'wp-flexible-csv-importer'),
		array (
			$wpFlexibleCSVImporter,
			'router'
		)
	);


}
