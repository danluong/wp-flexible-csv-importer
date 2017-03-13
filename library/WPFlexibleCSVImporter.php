<?php

class WPFlexibleCSVImporter {

	private $action = 'admin.php?import=wp-flexible-csv-importer';

	public function renderMainScreen()
	{
        ?>
		<form enctype="multipart/form-data" id="import-upload-form" method="post" class="wp-upload-form" action="<?php echo esc_url( add_query_arg( array( 'step' => 2 ), $this->get_action() ) ); ?>">
			<p>
				<label for="upload"><?php _e( 'Choose a .csv file from your computer.', 'wp-flexible-csv-importer' ); ?></label><br />(<?php printf( __('Maximum size: %s' ), $size ); ?>)
			</p>
			<p>
				<input type="file" id="upload" name="import" size="25" />
				<input type="hidden" name="action" value="save" />
				<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
				<?php wp_nonce_field( 'acsv-import-upload' ); ?>
			</p>
			<?php submit_button( __('Upload file and import'), 'wp-flexible-csv-importer' ); ?>
		</form>
        <?php
	}
}
