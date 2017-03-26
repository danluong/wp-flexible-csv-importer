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
    // TODO: does filter_input() negate need for isset() ?
    $postContent = filter_input(INPUT_POST, 'content');
    $postTitle = filter_input(INPUT_POST, 'postTitle');
    $useAsFeaturedImage = filter_input(INPUT_POST, 'useAsFeaturedImage');
    $customFields = filter_input(INPUT_POST, 'customfields');
    // TODO: this OK or use filter_var() ?
    $postImage = filter_input(INPUT_POST, 'image', FILTER_VALIDATE_URL);
    $imageLocationInPost =  = filter_input(INPUT_POST, 'image');

    $postOptions = array (
        'post_type' => 'post',
        'post_title' => $postTitle,
        'post_content' => $postContent,
        'post_status' => 'publish',
    );

    $post_id = wp_insert_post($postOptions);

    if ($post_id) {
        if($customFields != '') {
            foreach ($customFields as $key => $value) {
                add_post_meta($post_id, $key, $value);
            }
        }
    }

    if($postImage != '') {
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
                        if ($useAsFeaturedImage != '') {
                            set_post_thumbnail($post_id, $attachment->ID);
                        }

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
