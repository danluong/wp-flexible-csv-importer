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

add_action( 'wp_ajax_wfci_create_post', 'wfci_create_post' );


function wfci_create_post() {
    $errors = new WP_Error();

    // filter, sanitize and validate input data

    // allow html, empty, multiline
    $postContent = (string) filter_input(INPUT_POST, 'content');
    $postContent = wp_kses_post($postContent);
    
    // remove html, multiline, trim spaces, mustn't be empty
    $postTitle = sanitize_text_field(filter_input(INPUT_POST, 'title'));
    if ($postTitle == '')
        $errors->add('empty', 'Post title must not be empty');

    //should be true if explicitly 1. cast to int
    $useAsFeaturedImage = (int) filter_input(INPUT_POST, 'useAsFeaturedImage');
    $useAsFeaturedImage = ($useAsFeaturedImage === 1 ? true : false);  

    // should be array, not empty 
    $customFields = $_POST['customFields'];
    if ((!is_array($customFields) || empty($customFields)))
        $customFields = false; 

    // should be valid url
    $postImage = filter_input(INPUT_POST, 'image', FILTER_VALIDATE_URL);

    // should be string of either 'above_content' or 'below_content'
    $imageLocationInPost = (string) filter_input(INPUT_POST, 'imageLocationInPost');
    if (!in_array($imageLocationInPost, array('above_content', 'below_content')))
        $imageLocationInPost = false;

    $postOptions = array (
        'post_type' => 'post',
        'post_title' => $postTitle,
        'post_content' => $postContent,
        'post_status' => 'publish',
    );

    if (empty($postTitle)) {
        $errors->add('failed', 'No post title', $postOptions);
        echo 'FAILURE';
        wp_die(); 
    }

    $post_id = wp_insert_post($postOptions);

    if(!$post_id) {
        $errors->add('failed', 'Unable to create post', $postOptions);
    } else {
        if($customFields) {
            foreach ($customFields as $key => $value) {
                $key = sanitize_text_field($key);

                // allow html, empty, multiline
                $value = wp_kses_post($value);

                add_post_meta($post_id, $key, $value);
            }
        }

        if($postImage) {
            $imageUrl = strtok($postImage, '?');

            $imageWithMarkup = media_sideload_image($imageUrl, $post_id);

            if(!empty($imageWithMarkup) && !is_wp_error($imageWithMarkup)){
                $args = array(
                    'post_type' => 'attachment',
                    'posts_per_page' => -1,
                    'post_status' => 'any',
                    'post_parent' => $post_id
                );

                $attachments = get_posts($args);

                if(isset($attachments) && is_array($attachments)){
                    foreach($attachments as $attachment){
                        $image = wp_get_attachment_image_src($attachment->ID, 'full');
                        // detect our newly attached image
                        if(strpos($imageWithMarkup, $image[0]) !== false){
                            if ($useAsFeaturedImage != '')
                                set_post_thumbnail($post_id, $attachment->ID);

                            if ($imageLocationInPost != '') {
                                $originalPost = get_post($post_id);
                                $originalContent = $originalPost->post_content;
                                $my_post = null;
                                if ($imageLocationInPost == 'above_content') {
                                    $my_post = array(
                                        'ID'           => $post_id,
                                        'post_content' => $imageWithMarkup . $originalContent,
                                    );
                                } else if ($imageLocationInPost == 'below_content') {
                                    $my_post = array(
                                        'ID'           => $post_id,
                                        'post_content' => $originalContent . $imageWithMarkup,
                                    );
                                }
                                wp_update_post($my_post);
                            }

                            break;
                        }
                    }
                }
            }
        }
    } 

    if ($errors->get_error_messages()) {
        error_log(print_r($errors, true));
    }

    wp_die();
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
