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

        /* get list of all custom fields in use on all posts */
        $customFieldsList = [];
        $posts = get_posts(array(
            'posts_per_page'=>-1
        ));

        foreach($posts as $post) {
            $postMeta = get_post_meta($post->ID);

            foreach($postMeta as $key => $value) {
                // exclude other meta besides custom fields
                if ( '_' != $key{0} )
                $customFieldsList[] = $key;
            }
        }

        ?>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/4.1.4/papaparse.min.js"></script>
        <script>
        var csvData;
        var processedRows = 0;
        var select = '<select class="fieldType">';
        select += '<option value="ignore">Ignore</option>';
        select += '<option value="custom">Custom field</option>';
        select += '<option value="title">Title</option>';
        select += '<option value="content">Content</option>';
        select += '<option value="image">Image</option>';
        select += '</select>';

        var existingCustomFields = <?php echo json_encode($customFieldsList); ?>;


        function hideAllFieldOptions(target) {
            // remove all field options
            jQuery(target).closest('td').next('td').html('');
        }

        function showTitleFieldOptions(target) {
            optionsBlock = jQuery('#titleFieldOptionsBlock').clone();

            // remove ID from cloned block, to avoid duplicate IDs
            jQuery(optionsBlock).removeAttr('id');
            // append into place
            jQuery(target).closest('td').next('td').html(optionsBlock);
            // show the new block
            optionsBlock.show();
        }

        function showContentFieldOptions(target) {
            optionsBlock = jQuery('#contentFieldOptionsBlock').clone();

            // remove ID from cloned block, to avoid duplicate IDs
            jQuery(optionsBlock).removeAttr('id');
            // append into place
            jQuery(target).closest('td').next('td').html(optionsBlock);
            // show the new block
            optionsBlock.show();
        }

        function showCustomFieldOptions(target) {
            optionsBlock = jQuery('#customFieldOptionsBlock').clone();

            // remove ID from cloned block, to avoid duplicate IDs
            jQuery(optionsBlock).removeAttr('id');
            // append into place
            jQuery(target).closest('td').next('td').html(optionsBlock);
            // show the new block
            optionsBlock.show();
        }

        function showImageFieldOptions(target) {
            optionsBlock = jQuery('#imageFieldOptionsBlock').clone();

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

            if (chosenFieldType == 'title') {
                showTitleFieldOptions(target);
            } else if (chosenFieldType == 'custom') {
                showCustomFieldOptions(target);
            } else if (chosenFieldType == 'image') {
                showImageFieldOptions(target);
            } else if (chosenFieldType == 'content') {
                showContentFieldOptions(target);
            }
        }

        function handleFileSelect(evt) {
          var file = evt.target.files[0];

          Papa.parse(file, {
            header: true,
            dynamicTyping: true,
            complete: function(results) {
              csvData = results;
              console.log(csvData);
              showFieldMappings();
            }
          });
        }

        function doTheImport() {
            // validation
            // must have title (could add option for faking, but nope)

            jQuery('#doTheImportButton').hide();
            jQuery('#progressIndicator').show();

            processCSVData();

            // TODO: loop through each row in CSV

            // create post

            // set title to field mapped to title + options

            // set content to mapped field if exists + options

            // do something if an image field has been mapped + options

            // finish post creation

            // add post meta (each custom field-mapped field from CSV)

        }

        function processCSVData() {
            // process one row, increment processed num, call self on success
            console.log('processing CSV data...');
            console.log('processed rows:' + processedRows);
            console.log('data length:' + csvData.data.length);
            console.log(processedRows < csvData.data.length);

            data = 'action=create_post';
            jQuery.ajax({
                url: ajaxurl,
                data : {
                    action:'create_post'
                },
                dataType: 'html',
                method: 'POST',
                success: function(serverResponse) {
                    processedRows += 1;
                    console.log('processed rows' + processedRows);
                    if (processedRows < csvData.data.length) {
                        processCSVData();
                    } else {
                        console.log('all rows processed');
                        jQuery('#doTheImportButton').show();
                        jQuery('#progressIndicator').hide();
                    }
                }
            });

        }

        function showFieldMappings() {
          jQuery('#csv-file').hide();
          jQuery('#fieldMappings').show();

          jQuery.each(csvData.meta.fields, function(index, value) {
              console.log(value);
              el = '<tr><td>' + value + '</td><td>' + select + '</td><td class=".fieldOptions">&nbsp;</td>';
              jQuery('#fieldMappingTable tbody').append(el)
          });
        }

        jQuery(document).ready(function(){
          jQuery("#csv-file").change(handleFileSelect);

            jQuery(document).on('change','.fieldType',function(event){
              handleFieldChange(event.target);
            });

            jQuery(document).on('click','#doTheImportButton',function(event){
              doTheImport();
            });


            // prepare custom fields <select>
            var $el = jQuery("#customFieldOptionsBlock select");
            $el.empty(); // remove old options
            $el.append(jQuery("<option></option>")
                .attr("value", '').text(''));
            jQuery.each(existingCustomFields, function(key,value) {
              $el.append(jQuery("<option></option>")
                 .attr("value", value).text(value));
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

            <button id="doTheImportButton" class="button-primary">Import</button>
            <img id="progressIndicator" src="<?php echo plugins_url('../images/ellipsis.gif', __FILE__); ?>" style="display:none;" />
        </div>

        <!-- hidden fieldType option sets for cloning -->
        <div id="titleFieldOptionsBlock" style="display:none;">
            <input placeholder="prepend text" />
            <i>{ title }</i>
            <input placeholder="append text" />
        </div>

        <div id="contentFieldOptionsBlock" style="display:none;">
            <input placeholder="prepend text" />
            <i>{ content }</i>
            <input placeholder="append text" />
        </div>

        <div id="customFieldOptionsBlock" style="display:none;">
            Use existing
            <select>
            </select>

            Create new
            <input />
        </div>

        <div id="imageFieldOptionsBlock" style="display:none;">
            Use as featured image? <input type="checkbox" />

            Insert in content?
            <select>
                <option>No</option>
                <option>Above content</option>
                <option>Below content</option>
            </select>

            Save image locally? <input type="checkbox" />
        </div>

        <?php
    }

    private function showHeader() {
        ?>
        <h1>WP Flexible CSV Importer</h1>
        <?php
    }
}
