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
add_action( 'wp_ajax_wfci_create_post', 'wfci_create_post' );

function wfci_create_post() {
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
        $imageUrl = strtok($_POST['image'], '?');

        // magic sideload image returns an HTML image, not an ID
        $media = media_sideload_image($imageUrl, $post_id);

        // therefore we must find it so we can set it as featured ID
        if(!empty($media) && !is_wp_error($media)){
            $args = array(
                'post_type' => 'attachment',
                'posts_per_page' => -1,
                'post_status' => 'any',
                'post_parent' => $post_id
            );

            // reference new image to set as featured
            $attachments = get_posts($args);

            if(isset($attachments) && is_array($attachments)){
                foreach($attachments as $attachment){
                    // grab source of full size images (so no 300x150 nonsense in path)
                    $image = wp_get_attachment_image_src($attachment->ID, 'full');
                    // determine if in the $media image we created, the string of the URL exists
                    if(strpos($media, $image[0]) !== false){
                        if (isset($_POST['useAsFeaturedImage']) && $_POST['useAsFeaturedImage'] != '') {
                            set_post_thumbnail($post_id, $attachment->ID);
                        }

                        if (isset($_POST['imageLocationInPost']) && $_POST['useAsFeaturedImage'] != '') {
                            // get original post content to mix with image
                            $originalPost = get_post($post_id);
                            $originalContent = $originalPost->post_content;
                            $my_post = null;
                            if ($_POST['imageLocationInPost'] == 'above_content') {
                                $my_post = array(
                                    'ID'           => $post_id,
                                    'post_content' => $media . $originalContent,
                                );
                            } else if ($_POST['imageLocationInPost'] == 'below_content') {
                                $my_post = array(
                                    'ID'           => $post_id,
                                    'post_content' => $originalContent . $media,
                                );
                            }
                            // do the image/post mix
                            wp_update_post($my_post);
                        }

                        // only want one image
                        break;
                    }
                }
            }
        }
    }

    wp_die();
}

if(has_action('wp_ajax_wfci_create_post')) {
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
