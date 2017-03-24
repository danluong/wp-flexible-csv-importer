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
    // insert the post and set the category
    // TODO: add post filter, use isset()
    $postContent = $_POST['content'];
    $postTitle = $_POST['title'];
    $useAsFeaturedImage = $_POST['useAsFeaturedImage'];

    $postOptions = array (
        'post_type' => 'post',
        'post_title' => $postTitle,
        'post_content' => $postContent,
        'post_status' => 'publish',
    );

    $post_id = wp_insert_post($postOptions);

    if ($post_id) {
        if(isset($_POST['customFields']) && $_POST['customFields'] != '') {
            foreach ($_POST['customFields'] as $key => $value) {
                // insert post meta(s)
                add_post_meta($post_id, $key, $value);
            }
        }
    }

    // handle singular image import
    if(isset($_POST['image']) && $_POST['image'] != '' && filter_var($_POST['image'], FILTER_VALIDATE_URL)) {
        // fetch image into uploads folder
        $imageUrl = strtok($_POST['image'], '?');

        $imageExtension = pathinfo($imageUrl)['extension'];

        $uploads = wp_upload_dir();

        #$uploadPath = $uploads['baseurl'] . '/imported_image_' . $post_id . '_' . mt_rand(100000,999999) . '.' . $imageExtension;
        $uploadPath = ABSPATH . 'wp-content/uploads/imported_image_' . $post_id . '_' . mt_rand(100000,999999) . '.' . $imageExtension;

        $image = file_get_contents($imageUrl);
        file_put_contents($uploadPath, $image);

        // Check the type of file. We'll use this as the 'post_mime_type'.
        $filetype = wp_check_filetype( basename( $uploadPath ), null );

        // Get the path to the upload directory.
        $wp_upload_dir = wp_upload_dir();

        // Prepare an array of post data for the attachment.
        $attachment = array(
            'guid'           => $wp_upload_dir['url'] . '/' . basename( $uploadPath ), 
            'post_mime_type' => $filetype['type'],
            'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $uploadPath ) ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        // Insert the attachment.
        $attach_id = wp_insert_attachment( $attachment, $uploadPath, $post_id );

        // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        // Generate the metadata for the attachment, and update the database record.
        $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        set_post_thumbnail( $post_id, $attach_id );
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
