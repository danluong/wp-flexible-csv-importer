<?php

class WPFlexibleCSVImporter {
	private $action = 'admin.php?import=wp-flexible-csv-importer';

	public function router() {
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

        $customFieldsList = array_unique($customFieldsList);
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
            jQuery(target).closest('td').next('td').html('');
        }

        function showFieldOptions(target, fieldType) {
            optionsBlock = jQuery('#' + fieldType + 'FieldOptionsBlock').clone();

            // remove ID from cloned block, to avoid duplicate IDs
            jQuery(optionsBlock).removeAttr('id');
            // append into place
            jQuery(target).closest('td').next('td').html(optionsBlock);
            // show the new block
            optionsBlock.show();
        }
 
        function handleFieldChange(target) {
            targetField = jQuery(target);

            chosenFieldType = targetField.find(":selected").val();
            console.log(chosenFieldType);

            // hide all other field type options
            hideAllFieldOptions(target);

            // if an image field is set, disable other image fields
            if (jQuery('option:selected[value="image"]').length === 1) {
                jQuery('option[value="image"]').not(':selected').attr('disabled', 'disabled');
            }

            if (jQuery('option:selected[value="image"]').length === 0) {
                jQuery('option[value="image"]').removeAttr('disabled');
            }

            // if a title field is set, disable other title fields
            if (jQuery('option:selected[value="title"]').length === 1) {
                jQuery('option[value="title"]').not(':selected').attr('disabled', 'disabled');
            }

            if (jQuery('option:selected[value="title"]').length === 0) {
                jQuery('option[value="title"]').removeAttr('disabled');
            }

            showFieldOptions(target, chosenFieldType);
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
            jQuery('#doTheImportButton').hide();
            jQuery('#progressIndicator').show();

            processCSVData();
        }

        function processCSVData() {
            // process one row, increment processed num, call self on success
            console.log('processing CSV data...');
            console.log('processed rows:' + processedRows);
            console.log('data length:' + csvData.data.length);
            console.log(processedRows < csvData.data.length);

            titleField = jQuery('option:selected[value="title"]').closest('td').prev('td').text();
            contentField = jQuery('option:selected[value="content"]').closest('td').prev('td').text();
            imageField = jQuery('option:selected[value="image"]').closest('td').prev('td').text();
            useAsFeaturedImageOption = jQuery('option:selected[value="image"]').closest('td').next('td').find('#useAsFeaturedImage').attr('checked');
            saveImageLocallyOption = jQuery('option:selected[value="image"]').closest('td').next('td').find('#saveImageLocally').attr('checked');

            imageLocationInPost = jQuery('option:selected[value="image"]').closest('td').next('td').find('#imageLocationInPost option:selected').val()

            // mandatory fields
            postData = {
                action: 'create_post',
                title: csvData.data[processedRows][titleField],
                content: csvData.data[processedRows][contentField]
            };

            // optional fields
            if (imageField !== '') { //TODO: does this handle undefined when no image field selected?
                postData["image"] = csvData.data[processedRows][imageField] 
            }

            if (useAsFeaturedImageOption === 'checked') {
                postData["useAsFeaturedImage"] = 1;
            }

            if (saveImageLocallyOption === 'checked') {
                postData["saveImageLocallyOption"] = 1;
            }

            if (imageLocationInPost === 'above_content') {
                postData["imageLocationInPost"] = 'above_content';
            } else if (imageLocationInPost === 'below_content') {
                postData["imageLocationInPost"] = 'below_content';
            }

            // get all custom fields as array
            customFieldsForPost = [];

            // for each custom field
            jQuery('option:selected[value="custom"]').each(function(index) {
                customField = this;

                customFieldName = ''; 
                // send the value same way, whether new or existing
                // if existing dropdown is not empty, send that as value 
                customFieldDropDown = 'getdropdownrelativetoelement';
                
                customFieldFreeInput = 'getfieldrelativetoelement';
            
                // get the value for the field from the CSV data after finding out which field it's in
                csvFieldName = jQuery(customField).closest('td').prev('td').text();
                customFieldValue = csvData.data[processedRows][csvFieldName] 

                customFieldsForPost.push(['fieldname', customFieldValue]);
            });

            postData["customFields"] = customFieldsForPost;

            jQuery.ajax({
                url: ajaxurl,
                data : postData,
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
            <select class="existingCustomField">
            </select>

            Create new
            <input class="newCustomField" />
        </div>

        <div id="imageFieldOptionsBlock" style="display:none;">
            Use as featured image? <input type="checkbox" id="useAsFeaturedImage" />

            Insert in content?
            <select id="imageLocationInPost">
                <option value="none">No</option>
                <option value="above_content">Above content</option>
                <option value="below_content">Below content</option>
            </select>

            Save image locally? <input type="checkbox" id="saveImageLocally" />
        </div>

        <?php
    }

    private function showHeader() {
        ?>
        <h1>WP Flexible CSV Importer</h1>
        <?php
    }
}
