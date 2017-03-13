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
