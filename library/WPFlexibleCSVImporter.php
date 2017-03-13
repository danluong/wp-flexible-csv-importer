<?php

class WPFlexibleCSVImporter {

	private $action = 'admin.php?import=wp-flexible-csv-importer';

	public function router()
	{
        $this->showHeader();

        // perform upload
		if ( isset( $_GET['upload'] ) ) {
            $this->doUpload();
        } else {
            $this->showMainPage();
        }
	}

    private function getAction() {
        return $this->action;
    }

    private function showMainPage() {
        ?>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/4.1.4/papaparse.min.js"></script>
        <script>
          var data;
          var select = '<select>';
          select += '<option value="custom">Custom field</option>';
          select += '<option value="title">Title</option>';
          select += '<option value="description">Description</option>';
          select += '</select>';
         
          function handleFileSelect(evt) {
            var file = evt.target.files[0];
 
            Papa.parse(file, {
              header: true,
              dynamicTyping: true,
              complete: function(results) {
                data = results;
                console.log(data);
                showFieldMappings();
              }
            });
          }

          function showFieldMappings() {
            jQuery('#csv-file').hide();
            jQuery('#fieldMappings').show();

            jQuery.each(data.meta.fields, function(index, value) {
                console.log(value);
                el = '<tr><td>' + value + '</td><td>' + select + '</td><td><input /></td>'; 
                jQuery('#fieldMappingTable tbody').append(el)
            });
          }
         
          jQuery(document).ready(function(){
            jQuery("#csv-file").change(handleFileSelect);
          });
        </script>
        <input type="file" id="csv-file" name="files"/>
        
        <div id ="fieldMappings" style="display:none;">
            <h2 id="fieldMappingsTitle">Field mappings</h2>

            <table id="fieldMappingTable">
                <thead>
                    <tr>
                        <th>CSV field</th>
                        <th>WP Post field</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>

            <button class="button-primary">Import</button>
        </div>

        <?php
    }

    private function showHeader() {
        ?>
        <h1>WP Flexible CSV Importer</h1>
        <?php
    }
}
