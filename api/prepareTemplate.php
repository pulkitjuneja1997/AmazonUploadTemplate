<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Exception; 

class Amazon_Integration_For_Woocommerce_Admin {

    /**
	* The reader of this plugin.
	*
	* @since    1.0.0
	* @var      string    $reader    The current version of this plugin.
	*/
	private $reader;

    /**
	* Initialize the class and set its properties.
	*
	* @since    1.0.0
	*/
	public function __construct() {

		$this->reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

    }

    public function ced_amazon_prepare_upload_template( $request_body ){

		$fileUrl   = isset( $request_body['fileUrl'] ) ? trim( $request_body['fileUrl']  ) : '';
		$fileName  = isset( $request_body['fileName'] ) ? trim( $request_body['fileName']  ) : '';

        $display_saved_values = 'no';
        $select_html = '';
        
        $select_html .= '<tr>
                            <td></td>
                            <td>
                                <input id="ced_amazon_profile_name" value="amazonTemplate" type="hidden" name="ced_amazon_profile_data[template_type]" required="">
                                <input id="ced_amazon_profile_name" value="' . $fileUrl . '" type="hidden" name="ced_amazon_profile_data[file_url]" required="">
                            </td>
                        </tr>';
        $fileContents   = file_get_contents($fileUrl);
        $localFileName = tempnam(sys_get_temp_dir(), "tempxls");
        
        file_put_contents($localFileName, $fileContents );

        $this->reader->setLoadAllSheets();
        $this->reader->setReadDataOnly(true);
        $listname_of_all_tabs_files = $this->reader->listWorksheetNames($localFileName);
        $spreadsheet = $this->reader->load($localFileName);

        // ----------------------------------------------------- PRODUCTS_TEMPLATE_FIELDS.JSON ----------------------------------------------------------

        $products_template_fields_key = array_search( 'Data Definitions', $listname_of_all_tabs_files  );
        $products_template_fields = $listname_of_all_tabs_files[$products_template_fields_key];

        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($spreadsheet->getSheetByName($products_template_fields)->getHighestColumn());
        $highestRow = $spreadsheet->getSheetByName($products_template_fields)->getHighestRow();

        $sectionHeading = ''; 
        $sectionHeadingCol = ''; $slugHeadingCol = ''; $labelHeadingCol = ''; $defAndUseHeadingCol = ''; $acceptedHeadingCol = ''; $examHeadingCol = ''; $reqHeadingCol = '';
        $final_all_complete_indexes = array();
        
        for ( $row = 2; $row <= $highestRow; ++$row ) {

            if( $row == 2 ){
                $headingArray = array( 'Group Name', 'Field Name', 'Local Label Name', 'Definition and Use', 'Accepted Values', 'Example', 'Required?' );
                for ($col = 1; $col <= $highestColumnIndex; ++$col) { 

                    $currentHeading = $spreadsheet->getSheetByName($products_template_fields)->getCellByColumnAndRow( $col, 2 )->getValue();
                    if( !empty( $currentHeading ) && in_array( $currentHeading, $headingArray ) ){

                        if( 'Group Name' == $currentHeading ){
                            $sectionHeadingCol = $col;
                        } elseif( 'Field Name' == $currentHeading ){
                            $slugHeadingCol = $col;
                        }elseif( 'Local Label Name' == $currentHeading ){
                            $labelHeadingCol = $col;
                        }elseif( 'Definition and Use' == $currentHeading ){
                            $defAndUseHeadingCol = $col;
                        }elseif( 'Accepted Values' == $currentHeading ){
                            $acceptedHeadingCol = $col;
                        }elseif( 'Example' == $currentHeading ){
                            $examHeadingCol = $col;
                        }elseif( 'Required?' == $currentHeading ){
                            $reqHeadingCol = $col;
                        }

                    }

                }

            } else{
                $value = $spreadsheet->getSheetByName($products_template_fields)->getCellByColumnAndRow(1, $row)->getValue();
                
                if ( !empty($value) ){
                    if ( !empty($value) && strpos( $value, ' - ') !== false) {
                        
                        $headingArray = explode(' - ', $value);
                        $sectionHeading = $headingArray[0];
                        
                    } else{
                        $sectionHeading = $value;
                    }

                    $final_all_complete_indexes[$sectionHeading] = array();
                    
                }

                for ($col = 1; $col <= $highestColumnIndex; ++$col) {

                    if ($col == 2 && $row != 2) {

                        $fieldName = $spreadsheet->getSheetByName($products_template_fields)->getCellByColumnAndRow( $slugHeadingCol, $row )->getValue();
                        $label = $spreadsheet->getSheetByName($products_template_fields)->getCellByColumnAndRow( $labelHeadingCol, $row )->getValue();
                        
                        $definitions_and_uses = $spreadsheet->getSheetByName($products_template_fields)->getCellByColumnAndRow( $defAndUseHeadingCol, $row )->getValue();
                        $accepted_values = $spreadsheet->getSheetByName($products_template_fields)->getCellByColumnAndRow( $acceptedHeadingCol, $row )->getValue();
                        $examples = $spreadsheet->getSheetByName($products_template_fields)->getCellByColumnAndRow( $examHeadingCol, $row )->getValue();
                        
                        $required = $spreadsheet->getSheetByName($products_template_fields)->getCellByColumnAndRow( $reqHeadingCol, $row )->getValue();
                        
                        if ( !empty($fieldName) && strpos($fieldName, '1 - ') !== false) {
                            $reapt = explode('1 - ', $fieldName);
                            $range = explode($reapt[0], $fieldName);
                            
                            $final_all_complete_indexes[$sectionHeading][$reapt[1]] =
                                array(
                                    'label'      => $label,
                                    'definition' => $definitions_and_uses,
                                    'accepted_value' => $accepted_values,
                                    'example' => $examples,
                                    'productTypeSpecific' => array( 'sub_category_id' => array( 'condition' => lcfirst( $required ) ) )
                                );
                            

                        } else {
                            if ($fieldName != '') {
                                //echo 'sectionHeading' . $sectionHeading;
                                // if( $sectionHeading == 'Required' ){ }
                                $final_all_complete_indexes[$sectionHeading][$fieldName] = array(
                                    'label'      => $label,
                                    'definition' => $definitions_and_uses,
                                    'accepted_value' => $accepted_values,
                                    'example' => $examples,
                                    'productTypeSpecific' => array( 'sub_category_id' => array( 'condition' => lcfirst( $required ) ) )
                                );

                            }


                        }


                    }

                }

            }
                    

        }

        // ----------------------------------------------------- PRODUCTS_TEMPLATE_FIELDS.JSON ----------------------------------------------------------

        // ----------------------------------------------------- VALID_VALUES.JSON ----------------------------------------------------------

        $valid_values_key = array_search( 'Valid Values', $listname_of_all_tabs_files );
        $valid_values = $listname_of_all_tabs_files[$valid_values_key];
        
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($spreadsheet->getSheetByName($valid_values)->getHighestColumn());
        $highestRow = $spreadsheet->getSheetByName($valid_values)->getHighestRow();
        
        $sectionHeading = '';
        $valid_values_array = array();
        $subCategory = '';

        for ($row = 1; $row <= $highestRow; ++$row) {
            for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                if ($col == 2 ) {
                    $label = $spreadsheet->getSheetByName($valid_values)->getCellByColumnAndRow(2, $row)->getValue();
                    if ( !empty($label) ){
                        if ( !empty($label) && strpos( $label, ' - ') !== false) {
                            
                            $labelArray = explode(' - ', $label);
                            $label = $labelArray[0];
                            $subCategory = $labelArray[1];
                            $subCategory = ltrim( $subCategory,"[ " );
                            $subCategory = rtrim( $subCategory," ]" );

                            if( empty( $subCategory ) ) { $subCategory = 'all_cat'; }
                            
                        } else{ continue;}
        
                        $valid_values_array[$label] = array();
                        $valid_values_array[$label][$subCategory] = array();
        
                    }

                } else if( $col > 2) {

                    $option = $spreadsheet->getSheetByName($valid_values)->getCellByColumnAndRow($col, $row)->getValue();
                    if( !empty( $option ) ){
                        $valid_values_array[$label][$subCategory][$option] = $option;
                    }

                }

            }
                    

        }

        // ----------------------------------------------------- VALID_VALUES.JSON ----------------------------------------------------------

        $amazonCategoryList = $final_all_complete_indexes;
        $valid_values       = $valid_values_array;

        if ( ! empty( $amazonCategoryList ) ) {

            global $wpdb;
            $optionalFields = array();
            $html           = '';

            foreach ( $amazonCategoryList as $fieldsKey => $fieldsArray ) {

                $select_html2 = $this->prepareProfileFieldsSection( $fieldsKey, $fieldsArray, array(), 'no', $valid_values, 'sub_category_id' );

                if ( $select_html2['display_heading'] ) {
                    $select_html .= '<tr class="categoryAttributes" ><td colspan="3"></td></tr>
                    <tr class="categoryAttributes "><th colspan="3" class="profileSectionHeading">
                    <label style="font-size: 1.25rem;color: #6574cd;" >';

                    $select_html .= $fieldsKey;
                    $select_html .= ' Fields </label></th></tr><tr class="categoryAttributes" ><td colspan="3"></td></tr>';

                }

                $select_html     .= $select_html2['html'];
                $optionalFields[] = $select_html2['optionsFields'];

            }

            if ( 'no' == $display_saved_values ) {

                if ( ! empty( $optionalFields ) ) {

                    $html .= '<tr class="categoryAttributes"><th colspan="3" class="profileSectionHeading" >
                    <label style="font-size: 1.25rem;color: #6574cd;" > Optional Fields </label></th></tr>';

                    $html .= '<tr class="categoryAttributes" ><td></td><td><select id="optionalFields"><option  value="" >--Select--</option>';

                    foreach ( $optionalFields as $optionalField ) {
                        foreach ( $optionalField as $fieldsKey1 => $fieldsValue1 ) {
                            $html .= '<optgroup label="' . $fieldsKey1 . '">';
                            foreach ( $fieldsValue1 as $fieldsKey2 => $fieldsValue ) {

                                $html .= '<option value="';
                                $html .= htmlspecialchars( json_encode( array( $fieldsKey1 => array( $fieldsKey2 => $fieldsValue[0] ) ) ) );
                                $html .= '" >';
                                $html .= $fieldsValue[0]['label'];
                                $html .= ' (';
                                $html .= $fieldsKey2;
                                $html .= ') </option>';

                            }

                            $html .= '</optgroup>';
                        }
                    }

                    $html .= '</select></td>';
                    $html .= '<td><button class="ced_amazon_add_rows_button" id="';
                    $html .= $fieldsKey;
                    $html .= '">Add Row</button></td></tr>';
                }

                $select_html .= $html;

            } else {

                if ( ! empty( $optionalFields ) ) {
                    $optional_fields = array_values( $optionalFields );

                    $select_html .= '<tr class="categoryAttributes"><th colspan="3" class="profileSectionHeading" >
                    <label style="font-size: 1.25rem;color: #6574cd;" > Optional Fields </label></th></tr>';

                    $optionalFieldsHtml = '';
                    $saved_value        = isset( $current_amazon_profile['category_attributes_data'] ) ? json_decode( $current_amazon_profile['category_attributes_data'], true ) : '' ;

                    $html .= '<tr class="categoryAttributes"><td></td><td><select id="optionalFields"><option  value="" >--Select--</option>';
                    foreach ( $optionalFields as $optionalField ) {
                        foreach ( $optionalField as $fieldsKey1 => $fieldsValue1 ) {
                            $html .= '<optgroup label="' . $fieldsKey1 . '">';
                            foreach ( $fieldsValue1 as $fieldsKey2 => $fieldsValue ) {

                                if ( ! array_key_exists( $fieldsKey2, $saved_value ) ) {
                                    $html .= '<option  value="' . htmlspecialchars( json_encode( array( $fieldsKey1 => array( $fieldsKey2 => $fieldsValue[0] ) ) ) ) . '" >' . $fieldsValue[0]['label'] . ' (' . $fieldsKey2 . ') </option>';

                                } else {

                                    $prodileRowHTml      = $this->prepareProfileRows( array(), 'no', $valid_values, 'sub_category_id', '', '', $fieldsKey2, $fieldsValue[0], 'yes', '', '','' );
                                    $optionalFieldsHtml .= $prodileRowHTml;
                                }
                            }
                            $html .= '</optgroup>';
                        }
                    }

                    $html .= '</select></td>';
                    $html .= '<td><button class="ced_amazon_add_rows_button" id="' . $fieldsKey . '">Add Row</button></td></tr>';

                    $select_html .= $optionalFieldsHtml;
                    $select_html .= $html;

                }
            }
        }

        echo esc_attr( wp_send_json_success( $select_html ) );
        wp_die();

    }

}


print_r($_POST);
$request_body = $_POST;

$instance = new Amazon_Integration_For_Woocommerce_Admin();
$instance->ced_amazon_prepare_upload_template( $request_body );

?>