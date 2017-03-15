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
        var select = '<select class="fieldType">';
        select += '<option value="ignore">Ignore</option>';
        select += '<option value="custom">Custom field</option>';
        select += '<option value="title">Title</option>';
        select += '<option value="content">Content</option>';
        select += '<option value="image">Image</option>';
        select += '</select>';

        function hideAllFieldOptions(target) {
            // remove all field options
            jQuery(target).closest('td').next('td').html('');
        }

        function showTitleFieldOptions(target) {

            // add title field options

            // clone empty title field options set
            optionsBlock = jQuery('#titleFieldOptionsBlock').clone();

            // remove ID from cloned block, to avoid duplicate IDs
            jQuery(optionsBlock).removeAttr('id');
            // append into place
            jQuery(target).closest('td').next('td').html(optionsBlock);
            // show the new block
            optionsBlock.show();
        }
 
        function handleFieldChange(target) {
            targetField = jQuery(target);

            // get chosen field type
            chosenFieldType = targetField.find(":selected").val();
            console.log(chosenFieldType);

            // hide all other field type options
            hideAllFieldOptions(target);

            // handle a "title" field
            if (chosenFieldType == 'title') {
                showTitleFieldOptions(target);
            }
            // handle a "custom" field

            // handle a "content" field

            // handle an "image" field
         
        }

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
              el = '<tr><td>' + value + '</td><td>' + select + '</td><td class=".fieldOptions"><input /></td>';
              jQuery('#fieldMappingTable tbody').append(el)
          });
        }

        jQuery(document).ready(function(){
          jQuery("#csv-file").change(handleFileSelect);

            jQuery(document).on('change','.fieldType',function(event){
              handleFieldChange(event.target);
            });
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

        <!-- hidden fieldType option sets for cloning -->
        <div id="titleFieldOptionsBlock" style="display:none;">
            Hi, I'm a clone of the titleFieldOptionsBlock
        </div>

        <?php
    }

    private function showHeader() {
        ?>
        <h1>WP Flexible CSV Importer</h1>
        <?php
    }
}
