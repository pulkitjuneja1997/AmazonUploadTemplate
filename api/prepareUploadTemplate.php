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
    public $ced_amazon_general_options;
    public $addedMetaKeys;
    public $attributes;
    public $query;
    public $results;
    public $domain;
    public $seller_id;
	public $session;
	public $template_id;
	public $rowNum;

    /**
	* Initialize the class and set its properties.
	*
	* @since    1.0.0
	*/
	public function __construct() {

        error_reporting(~0);
		ini_set('display_errors', 1);

		$this->reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

    }


    /*
	*
	* Function to prepare profile fields section
	*/
	public function prepareProfileFieldsSection( $fieldsKey, $fieldsArray, $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id, $seller_id ) {

		// if( $this->rowNum > 110 ){
		// 	print_r($fieldsKey);
		// 	print_r($sub_category_id);
		// 	print_r($fieldsArray);
		// }

		if ( ! empty( $fieldsArray ) ) {
			$profileSectionHtml = '';
			$optionalFields     = array();
			$display_heading    = 0;
			$html               = '';

			$seller_id = $this->seller_id;

			// $ced_amazon_general_options_arr = get_option( 'ced_amazon_general_options', array() );

			// $ced_amazon_general_options_arr = $this->ced_amazon_general_options;
			// $ced_amazon_general_options     = $ced_amazon_general_options_arr[$seller_id];

			$ced_amazon_general_options = $this->ced_amazon_general_options;
			
			foreach ( $fieldsArray as $fieldsKey2 => $fieldsValue ) {

				if ( 'Mandantory' == $fieldsKey ) {

					$required = isset( $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ) && 'required' == $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ? ' [' . ucfirst( $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ) . ']' : '';
					$req      = isset( $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ) && 'required' == $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ? 'required' : '';

				} else {
					$required = isset( $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ) && 'required' == $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ? ' [Suggested]' : '';
					$req      = '';

				}

				$globalValue = 'no';

				if ( ' [Required]' == $required || ' [Suggested]' == $required ) {

					if ( isset( $ced_amazon_general_options[ $fieldsKey2 ] ) && is_array( $ced_amazon_general_options[ $fieldsKey2 ] ) && ( '' !== $ced_amazon_general_options[ $fieldsKey2 ]['default'] || '' !== $ced_amazon_general_options[ $fieldsKey2 ]['metakey'] ) ) {
						// $required = '';
						$req            = '';
						$globalValue    = 'yes';
						$defaultGlobal  = $ced_amazon_general_options[ $fieldsKey2 ]['default'];
						$meta_keyGlobal = $ced_amazon_general_options[ $fieldsKey2 ]['metakey'];

					} else {
						$defaultGlobal  = '';
						$meta_keyGlobal = '';
					}

					$display_heading     = 1;
					$prodileRowHTml      = $this->prepareProfileRows( $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id, $req, $required, $fieldsKey2, $fieldsValue, $globalValue, $defaultGlobal, $meta_keyGlobal, '' );
					$profileSectionHtml .= $prodileRowHTml;

				} else {
					$optionalFields[ $fieldsKey ][ $fieldsKey2 ][] = $fieldsValue;
				}

			}

			return array(
				'html'            => $profileSectionHtml,
				'display_heading' => $display_heading,
				'optionsFields'   => $optionalFields,
			);

		}

	}


	/*
	*
	* Function to prepare profile rows
	*/

	public function prepareProfileRows( $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id, $req, $required, $fieldsKey2, $fieldsValue, $globalValue, $globalValueDefault, $globalValueMetakey, $cross="no" ) {
		
        $results        = $this->results;
        $query          = $this->query;
        $addedMetaKeys  = $this->addedMetaKeys;
		
		$rowHtml  = '';
		$rowHtml .= '<tr class="categoryAttributes" id="ced_amazon_categories" data-attr="' . $req . '">';

		if ( 'yes' == $display_saved_values ) {
			$req = '';
		}

		$row_label = $fieldsValue['label'] ;

		$index =  strpos( $fieldsKey2,"_custom_field");
		if( $index  > -1 ){
			$slug  =  substr( $fieldsKey2, 0, $index );
		} else{
			$slug = $fieldsKey2;
		}

		$rowHtml .= '<td>
		<label for="" class="">' . $row_label . '<span class="ced_amazon_wal_required">' . $required . '</span></label>
		<p class="cat_attr_para"> (' . $slug . ') </p></td>';

		if ( ! empty( $current_amazon_profile ) ) {
			$saved_value = json_decode( $current_amazon_profile['category_attributes_data'], true );
			$saved_value = $saved_value[ $fieldsKey2 ];
		} else {
			$saved_value = array();
		}

		
		$default_value = isset( $saved_value['default'] ) ? $saved_value['default'] : '';
		$template_id   = $this->template_id;

		// test
		if ( empty( $default_value ) && 'yes' == $globalValue && empty( $template_id ) ) {
			$default_value = $globalValueDefault;
		}

		$rowHtml .= '<td>';
		if( 'yes' == $cross){
            $rowHtml .= '<input type="hidden" name="ced_amazon_profile_data[' . $slug . '_custom_field][label]" value="' . $row_label . '" >';
			
		} else{
			$rowHtml .= '<input type="hidden" name="ced_amazon_profile_data[ref_attribute_list][' . $fieldsKey2 . ']" />';

		}
		

		if ( ( isset( $valid_values[ $fieldsKey2 ] ) && isset( $valid_values[ $fieldsKey2 ][ $sub_category_id ] ) )  || ( isset( $valid_values[ $row_label ] ) && isset( $valid_values[ $row_label ][ $sub_category_id ] ) ) ) {
			// $rowHtml .= '<select class="custom_category_attributes_select2" id="' . $fieldsKey2 . '"  name="ced_amazon_profile_data[' . $fieldsKey2 . '][default]" ' . $req . '><option value="">--Select--</option>';
			$rowHtml .= '<select class="custom_category_attributes_select2" id="' . $fieldsKey2 . '"  name="ced_amazon_profile_data[' . $fieldsKey2 . '][default]"><option value="">--Select--</option>';

			$optionLabels = !empty( $valid_values[ $fieldsKey2 ][ $sub_category_id ] ) ? $valid_values[ $fieldsKey2 ][ $sub_category_id ] : $valid_values[ $row_label ][ $sub_category_id ];
			
			foreach ( $optionLabels as $acpt_key => $acpt_value ) {
				$selected = '';
				if ( $acpt_key == $default_value ) {
					$selected = 'selected';
				} elseif( $acpt_key == $sub_category_id && 'feed_product_type' == $fieldsKey2 ){
					$selected = 'selected';
				}
				$rowHtml .= '<option value="' . $acpt_key . '"' . $selected . '>' . $acpt_value . '</option>';
			}

			$rowHtml .= '</select>';

		} elseif ( ( isset( $valid_values[ $fieldsKey2 ] ) && isset( $valid_values[ $fieldsKey2 ]['all_cat'] ) ) || ( isset( $valid_values[ $row_label ] ) && isset( $valid_values[ $row_label ]['all_cat'] ) ) ) {

			// $rowHtml .= '<select class="custom_category_attributes_select2" id="' . $fieldsKey2 . '"  name="ced_amazon_profile_data[' . $fieldsKey2 . '][default]" ' . $req . '><option value="">--Select--</option>';
			$rowHtml .= '<select class="custom_category_attributes_select2" id="' . $fieldsKey2 . '"  name="ced_amazon_profile_data[' . $fieldsKey2 . '][default]"><option value="">--Select--</option>';

			$optionLabels = !empty( $valid_values[ $fieldsKey2 ]['all_cat'] ) ? $valid_values[ $fieldsKey2 ]['all_cat'] : $valid_values[ $row_label ]['all_cat'];

			foreach ( $optionLabels as $acpt_key => $acpt_value ) {
				$selected = '';
				if ( $acpt_key == $default_value ) {
					$selected = 'selected';
				} elseif( $acpt_key == $sub_category_id && 'feed_product_type' == $fieldsKey2 ){
					$selected = 'selected';
				}
				$rowHtml .= '<option value="' . $acpt_key . '"' . $selected . '>' . $acpt_value . '</option>';
			}
			$rowHtml .= '</select>';

		} else {
			// $rowHtml .= '<input class="custom_category_attributes_input" value="' . $default_value . '" id="' . $fieldsKey2 . '" type="text" name="ced_amazon_profile_data[' . $fieldsKey2 . '][default]" ' . $req . ' />';
			$rowHtml .= '<input class="custom_category_attributes_input" value="' . $default_value . '" id="' . $fieldsKey2 . '" type="text" name="ced_amazon_profile_data[' . $fieldsKey2 . '][default]" />';
		}

		$rowHtml .= '<span>
			<i class="fa fa-info-circle" data-tooltip-content="' . $fieldsValue['accepted_value'] . '"></i>
			</span> </td>';

		$rowHtml        .= '<td>';
		$selected_value2 = isset( $saved_value['metakey'] ) ? $saved_value['metakey'] : '';

		$template_id = $this->template_id;
		// test
		if ( empty( $selected_value2 ) && 'yes' == $globalValue && empty( $template_id ) ) {
			$selected_value2 = $globalValueMetakey;
		}

		//$selectDropdownHTML = '<select class="select2 custom_category_attributes_select"  name="ced_amazon_profile_data[' . $fieldsKey2 . '][metakey]"  ' . $req . ' >';
		$selectDropdownHTML = '<select class="select2 custom_category_attributes_select"  name="ced_amazon_profile_data[' . $fieldsKey2 . '][metakey]">';

		foreach ( $results as $key2 => $meta_key ) {
			$post_meta_keys[] = $meta_key['meta_key'];
		}

		$custom_prd_attrb = array();
		$attrOptions      = array();

		if ( ! empty( $query ) ) {
			foreach ( $query as $key3 => $db_attribute_pair ) {
				foreach ( maybe_unserialize( $db_attribute_pair['meta_value'] ) as $key4 => $attribute_pair ) {
					if ( 1 != $attribute_pair['is_taxonomy'] ) {
						$custom_prd_attrb[] = $attribute_pair['name'];
					}
				}
			}
		}

		if ( $addedMetaKeys && 0 < count( $addedMetaKeys ) ) {
			foreach ( $addedMetaKeys as $metaKey ) {
				$attrOptions[ $metaKey ] = $metaKey;
			}
		}

		$attributes = $this->attributes;

		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $attributesObject ) {
				$attrOptions[ 'umb_pattr_' . $attributesObject->attribute_name ] = $attributesObject->attribute_label;
			}
		}

		/* select dropdown setup */
		ob_start();
		$fieldID             = '{{*fieldID}}';
		$selectId            = $fieldID . '_attibuteMeta';
		$selectDropdownHTML .= '<option value=""> -- select -- </option>';

		if ( is_array( $attrOptions ) ) {
			$selectDropdownHTML .= '<optgroup label="Global Attributes">';
			foreach ( $attrOptions as $attrKey => $attrName ) {
				$selected = '';
				if ( $selected_value2 == $attrKey ) {
					$selected = 'selected';
				}
				$selectDropdownHTML .= '<option ' . $selected . ' value="' . $attrKey . '">' . $attrName . '</option>';
			}
		}

		if ( ! empty( $custom_prd_attrb ) ) {
			$custom_prd_attrb    = array_unique( $custom_prd_attrb );
			$selectDropdownHTML .= '<optgroup label="Custom Attributes">';

			foreach ( $custom_prd_attrb as $key5 => $custom_attrb ) {
				$selected = '';
				if ( 'ced_cstm_attrb_' . $custom_attrb  == $selected_value2 ) {
					$selected = 'selected';
				}
				$selectDropdownHTML .= '<option ' . $selected . ' value="ced_cstm_attrb_' . $custom_attrb . '">' . esc_html( $custom_attrb ) . '</option>';

			}
		}

		if ( ! empty( $post_meta_keys ) ) {
			$post_meta_keys      = array_unique( $post_meta_keys );
			$selectDropdownHTML .= '<optgroup label="Custom Fields">';
			foreach ( $post_meta_keys as $key7 => $p_meta_key ) {
				$selected = '';
				if ( $selected_value2 == $p_meta_key ) {
					$selected = 'selected';
				}
				$selectDropdownHTML .= '<option ' . $selected . ' value="' . $p_meta_key . '">' . $p_meta_key . '</option>';
			}
		}

		$selectDropdownHTML .= '</select>';
		if( 'yes' == $cross){
			$selectDropdownHTML .= '<i class="fa fa-times ced_amazon_remove_custom_row" ></i>';
		}
		$rowHtml            .= $selectDropdownHTML;
		$rowHtml            .= '</td>';
		$rowHtml            .= '</tr>';

		return $rowHtml;

	}
   



    public function ced_amazon_prepare_upload_template( $request_body ) {

		if( !empty( $this->session ) ){
            $this->session = json_decode($this->session, true);
		} else{
			$this->session = [];
		}

		ini_set("memory_limit", "-1");
        $fileUrl   = isset( $request_body['fileUrl'] ) ? trim( $request_body['fileUrl']  ) : '';
		$fileName  = isset( $request_body['fileName'] ) ? trim( $request_body['fileName']  ) : '';
        $this->template_id  = isset( $request_body['template_id'] ) ? trim( $request_body['template_id']  ) : '';

        $display_saved_values    = isset( $request_body['display_saved_values'] ) ? trim( $request_body['display_saved_values']  ) : 'no';
        $current_amazon_profile  = isset( $request_body['current_amazon_profile'] ) ? $request_body['current_amazon_profile'] : '';
		$last                    = isset( $request_body['last'] ) ? $request_body['last'] : "false";
		$rowName                 = isset( $request_body['rowName'] ) ? $request_body['rowName'] : "";

		$this->reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
		
		// wp_raise_memory_limit('admin');
		
        $curl = curl_init();
		$select_html = '';
		$arrContextOptions=array(
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			),
		);  
		
		if( 0 == $request_body['rowNum'] ){

			session_destroy();
			session_start();

			$fileContents = file_get_contents($fileUrl, false, stream_context_create($arrContextOptions));
			$localFileName = tempnam(sys_get_temp_dir(), 'tempxls');

			// $this->session = [];
		    file_put_contents($localFileName, $fileContents );
			$this->session['localFileName'] = $localFileName;

		} else{
			// $this->session = 
			$localFileName = $this->session['localFileName'];
		}

		$this->reader->setLoadAllSheets();
		$this->reader->setReadDataOnly(true);
		$listname_of_all_tabs_files = $this->reader->listWorksheetNames($localFileName);
		$spreadsheet                = $this->reader->load($localFileName);

        // ----------------------------------------------------- testtttttttt ----------------------------------------------------------

		if( 0 == $request_body['rowNum'] ){

			
			// $valid_values_key = array_search( 'Valid Values', $listname_of_all_tabs_files );
			// $valid_values     = $listname_of_all_tabs_files[$valid_values_key];

			$data_definitions_key = array_search( 'Data Definitions', $listname_of_all_tabs_files );
			$data_definitions     = $listname_of_all_tabs_files[$data_definitions_key];

			$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($spreadsheet->getSheetByName($data_definitions)->getHighestColumn());
			$highestRow         = $spreadsheet->getSheetByName($data_definitions)->getHighestRow();

			$fieldsNames = array();
			for($row = 3; $row <= $highestRow; ++$row) {
				$rowName = $spreadsheet->getSheetByName($data_definitions)->getCellByColumnAndRow(1, $row)->getValue();
				if( !empty($rowName) )
				$fieldNames[$row] = $rowName;
				
			}

			echo json_encode( array( 'success' => true, 'rowsData' => $fieldNames, 'session' => $this->session ) );
			die;
		}

		// ----------------------------------------------------- testtttttttt ----------------------------------------------------------


		// ----------------------------------------------------- VALID_VALUES.JSON ----------------------------------------------------------

		$valid_values_key = array_search( 'Valid Values', $listname_of_all_tabs_files );
		$valid_values     = $listname_of_all_tabs_files[$valid_values_key];

		$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($spreadsheet->getSheetByName($valid_values)->getHighestColumn());
		$highestRow         = $spreadsheet->getSheetByName($valid_values)->getHighestRow();

		$sectionHeading     = '';
		$valid_values_array = array();
		$subCategory        = '';

        if( empty( $this->session['valid_values_array'] ) ){ 

            for ($row = 1; $row <= $highestRow; ++$row) {
                for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                    if ( 2 ==  $col ) {
                        $label = $spreadsheet->getSheetByName($valid_values)->getCellByColumnAndRow(2, $row)->getValue();

                        if ( !empty($label) ) {
                            if ( !empty($label) && strpos( $label, ' - ') !== false) {

                                $labelArray  = explode(' - ', $label);
                                $label       = $labelArray[0];
                                $subCategory = $labelArray[1];
                                $subCategory = ltrim( $subCategory, '[ ' );
                                $subCategory = rtrim( $subCategory, ' ]' );


                                if ( empty( $subCategory ) ) {
                                $subCategory = 'all_cat'; 
                                }

                            } elseif( !empty($label) ){
                                $subCategory = 'all_cat'; 
                            } else { continue; }

                            if ( !isset( $valid_values_array[$label] ) ) {
                                $valid_values_array[$label] = array();
                            }

                            $valid_values_array[$label][$subCategory] = array();

                        }

                    } elseif ( 2 < $col ) {

                        $option = $spreadsheet->getSheetByName($valid_values)->getCellByColumnAndRow($col, $row)->getValue();
                        if ( !empty( $option ) ) {
                            $valid_values_array[$label][$subCategory][$option] = $option;
                        }

                    }

                }

            }

			$this->session['valid_values_array'] = $valid_values_array;
			$this->session['sub_category_id']    = $subCategory;
          
        } else{
            $valid_values_array = $this->session['valid_values_array'] ;
            $subCategory        = $this->session['sub_category_id'];
        }
    
        $sub_category_id = $subCategory;    


        // if( 48 == $request_body['rowNum'] ){
        //     print_r($this->session);
		//     die;
        // }


		// ----------------------------------------------------- VALID_VALUES.JSON ----------------------------------------------------------

		if( 0 == $request_body['rowNum'] ){
			$select_html .= '<tr>
								<td></td>
								<td>
									<input value="' . $sub_category_id . '"  type="hidden" name="ced_amazon_profile_data[secondary_category]" >
									<input id="ced_amazon_profile_name" value="amazonTemplate" type="hidden" name="ced_amazon_profile_data[template_type]" >
									<input class="ced_amazon_profile_url" id="ced_amazon_profile_name" value="' . $fileUrl . '" type="hidden" name="ced_amazon_profile_data[file_url]" >
								</td>
							</tr>';
		}					

		// ----------------------------------------------------- PRODUCTS_FIELDS.JSON ----------------------------------------------------------

		$sub_category_id = $subCategory;

		$products_fields_key = array_search( 'Data Definitions', $listname_of_all_tabs_files  );
		$products_fields     = $listname_of_all_tabs_files[$products_fields_key];

		$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($spreadsheet->getSheetByName($products_fields)->getHighestColumn());
		$highestRow         = $spreadsheet->getSheetByName($products_fields)->getHighestRow();

		$sectionHeading             = ''; 
		$sectionHeadingCol          = '';
		$slugHeadingCol             = '';
		$labelHeadingCol            = '';
		$defAndUseHeadingCol        = '';
		$acceptedHeadingCol         = '';
		$examHeadingCol             = '';
		$reqHeadingCol              = '';
		$final_all_complete_indexes = array();

        if( empty( $this->session['amazonCategoryList'] ) ){ 

            for ( $row = 2; $row <= $highestRow; ++$row ) {

                if ( 2 == $row ) {
                    $headingArray = array( 'Group Name', 'Group name', 'Field Name', 'Field name', 'Local Label Name', 'Local label name', 'Definition and Use', 'Definition and use', 'Accepted Values', 'Example', 'Required?' );
                    for ($col = 1; $col <= $highestColumnIndex; ++$col) { 

                        $currentHeading = $spreadsheet->getSheetByName($products_fields)->getCellByColumnAndRow( $col, 2 )->getValue();
                        
                        if ( !empty( $currentHeading ) && in_array( $currentHeading, $headingArray ) ) {

                            if ( 'Group Name' == $currentHeading || 'Group name' == $currentHeading ) {
                                $sectionHeadingCol = $col;
                            } elseif ( 'Field Name' == $currentHeading || 'Field name' == $currentHeading ) {
                                $slugHeadingCol = $col;
                            } elseif ( 'Local Label Name' == $currentHeading || 'Local label name' == $currentHeading ) {
                                $labelHeadingCol = $col;
                            } elseif ( 'Definition and Use' == $currentHeading || 'Definition and use' == $currentHeading ) {
                                $defAndUseHeadingCol = $col;
                            } elseif ( 'Accepted Values' == $currentHeading ) {
                                $acceptedHeadingCol = $col;
                            } elseif ( 'Example' == $currentHeading ) {
                                $examHeadingCol = $col;
                            } elseif ( 'Required?' == $currentHeading ) {
                                $reqHeadingCol = $col;
                            }

                        }

                    }

                } else {
                    $value = $spreadsheet->getSheetByName($products_fields)->getCellByColumnAndRow(1, $row)->getValue();

                    if ( !empty($value) ) {
                        if ( !empty($value) && strpos( $value, ' - ') !== false) {

                            $headingArray   = explode(' - ', $value);
                            $sectionHeading = $headingArray[0];

                        } else {
                            $sectionHeading = $value;
                        }

                        $final_all_complete_indexes[$sectionHeading] = array();

                    }

                    for ($col = 1; $col <= $highestColumnIndex; ++$col) {

                        if ( 2 == $col && 2 != $row ) {

                            $fieldName = $spreadsheet->getSheetByName($products_fields)->getCellByColumnAndRow( $slugHeadingCol, $row )->getValue();
                            $label     = $spreadsheet->getSheetByName($products_fields)->getCellByColumnAndRow( $labelHeadingCol, $row )->getValue();

                            $definitions_and_uses = $spreadsheet->getSheetByName($products_fields)->getCellByColumnAndRow( $defAndUseHeadingCol, $row )->getValue();
                            $accepted_values      = $spreadsheet->getSheetByName($products_fields)->getCellByColumnAndRow( $acceptedHeadingCol, $row )->getValue();
                            $examples             = $spreadsheet->getSheetByName($products_fields)->getCellByColumnAndRow( $examHeadingCol, $row )->getValue();

                            $required = $spreadsheet->getSheetByName($products_fields)->getCellByColumnAndRow( $reqHeadingCol, $row )->getValue();

                            if ( !empty($fieldName) && strpos($fieldName, '1 - ') !== false) {

                                $fieldNameArray = explode('1 - ', $fieldName);
                                $index          = $fieldNameArray[1];

                                $splitArray        = str_split( $index , strlen($index) - 1  );
                                $lastIndex         = $splitArray[1]; 
                                $originalFieldName = $splitArray[0];
                                //$range = explode($reapt[0], $fieldNameArray);

                                for ( $i = 1; $i <= $lastIndex; $i++ ) {
                                    $final_all_complete_indexes[$sectionHeading][$originalFieldName . $i ] =
                                        array(
                                        'label'      => $label,
                                        'definition' => $definitions_and_uses,
                                        'accepted_value' => $accepted_values,
                                        'example' => $examples,
										'row' => $row,
                                        'productTypeSpecific' => array( $sub_category_id => array( 'condition' => lcfirst( $required ) ) )
                                    );
                                }

                            } else {
                                if ( '' != $fieldName ) {
                                    // if( $sectionHeading == 'Required' ){ }
                                    $final_all_complete_indexes[$sectionHeading][$fieldName] = array(
                                        'label'      => $label,
                                        'definition' => $definitions_and_uses,
                                        'accepted_value' => $accepted_values,
                                        'example' => $examples,
										'row' => $row,
                                        'productTypeSpecific' => array( $sub_category_id => array( 'condition' => lcfirst( $required ) ) )
                                    );

                                }


                            }


                        }

                    }

                }

            }

            $amazonCategoryList = $final_all_complete_indexes;
			$this->session['amazonCategoryList'] = $amazonCategoryList;

		} else{
			$amazonCategoryList = $this->session['amazonCategoryList'];
		}    

		// ----------------------------------------------------- PRODUCTS_FIELDS.JSON ----------------------------------------------------------


		// ----------------------------------------------------- PRODUCTS_TEMPLATE_FIELDS.JSON ----------------------------------------------------------

        if( empty( $this->session['products_template_fields_json'] ) ){ 

            $products_template_fields_key = array_search( 'Template', $listname_of_all_tabs_files  );
            $products_template_fields     = $listname_of_all_tabs_files[$products_template_fields_key];

            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($spreadsheet->getSheetByName($products_template_fields)->getHighestColumn());
            $highestRow         = $spreadsheet->getSheetByName($products_template_fields)->getHighestRow();

            $sectionHeading                 = ''; 
            $products_template_fields_array = array();

            for ( $row = 1; $row <= 3; ++$row ) {
                $products_template_fields_array[$row] = array();
                for ( $col = 1; $col <= $highestColumnIndex; ++$col ) {
                    $value                                      = $spreadsheet->getSheetByName($products_template_fields)->getCellByColumnAndRow($col, $row)->getValue();
                    $products_template_fields_array[$row][$col] = $value;

                }
            }

            $products_template_fields_json = json_encode( $products_template_fields_array );
            $this->session['products_template_fields_json'] = $products_template_fields_json;

            // $saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
            // $location_for_seller  = $seller_id;

            // if ( isset( $saved_amazon_details[ $location_for_seller ] ) && ! empty( $saved_amazon_details[ $location_for_seller ] ) && is_array( $saved_amazon_details[ $location_for_seller ] ) ) {
            //     $shop_data = $saved_amazon_details[ $location_for_seller ];

            //     $userCountry = $shop_data['ced_mp_name'];
            // } else {
            //     echo wp_send_json_error( ' Unable to get Shop Data. ' );
            //     die;
            // }

            // $upload_dir = wp_upload_dir();

            // $dirname  = $upload_dir['basedir'] . '/ced-amazon/amazon-templates/' . $userCountry . '/' . $sub_category_id;
            // $fileName = $dirname . '/products_template_fields.json';

            // if ( ! is_dir( $dirname ) ) {
            //     wp_mkdir_p( $dirname );
            // }

            // wp_mkdir_p( $dirname );

            // if ( ! file_exists( $fileName ) ) {
            //     $jsonFile = fopen( $fileName, 'w' );
            //     fwrite( $jsonFile, $products_template_fields_json );
            //     fclose( $jsonFile );
            //     chmod( $fileName, 0777 );
            // }

		} else{
			$products_template_fields_json = $this->session['products_template_fields_json'];
		}

		// ----------------------------------------------------- PRODUCTS_TEMPLATE_FIELDS.JSON ----------------------------------------------------------

        // if( 1 == $request_body['rowNum'] ){
        //     echo json_encode( array( 'success' => true, 'data' => 'sessionPrepared') );
		//     die;
        // }

		// $amazonCategoryList = $final_all_complete_indexes;
		$valid_values       = $valid_values_array;

		if ( ! empty( $amazonCategoryList ) ) {

			global $wpdb;
			$optionalFields = array();
			$html           = '';

			// foreach ( $amazonCategoryList as $fieldsKey => $fieldsArray ) {
			$rowNameArray = explode(' - ', $rowName );	
			$modRowName   = $rowNameArray[0];
			if( !empty($amazonCategoryList[$modRowName]) ){	

				$select_html2 = $this->prepareProfileFieldsSection( $modRowName, $amazonCategoryList[$modRowName], $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id, $this->template_id );
				// $select_html2 = $this->prepareProfileFieldsSection( $fieldsKey, $fieldsArray, $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id, $template_id );

				// if( $request_body['rowNum'] > 110 ){

				// 	print_r($select_html2);
				// 	die('innnnnnnn');
				// }

				if ( $select_html2['display_heading'] ) {
					$select_html .= '<tr class="categoryAttributes" ><td colspan="3"></td></tr>
					<tr class="categoryAttributes "><th colspan="3" class="profileSectionHeading">
					<label style="font-size: 1.25rem;color: #6574cd;" >';

					// $select_html .= $fieldsKey;
					$select_html .= $modRowName;
					$select_html .= ' Fields </label></th></tr><tr class="categoryAttributes" ><td colspan="3"></td></tr>';

				}

				$select_html     .= $select_html2['html'];
				// $optionalFields[] = $select_html2['optionsFields'];

				// $a = isset($this->session['optionalFields']) ? $this->session['optionalFields'] : [];
				// $this->session['optionalFields'] =  array_merge( $a, $select_html2['optionsFields'] ) ;



				// $optionalFields[] = $select_html2['optionsFields'];

				$a = isset($this->session['optionalFields']) ? $this->session['optionalFields'] : [];
				$this->session['optionalFields'] =  array_merge( $a, $select_html2['optionsFields'] ) ;

				print_r($this->session['optionalFields']);
				die;


			} else {
				// if( $row > 110){
				// 	print_r($$amazonCategoryList);
				// 	print_r($modRowName);
				// 	die;
				// }
			}

			if( 'string' == gettype($last) && "true" == $last ){

				if ( 'no' == $display_saved_values ) {

					if ( ! empty( $this->session['optionalFields'] ) ) {

						$html .= '<tr class="categoryAttributes"><th colspan="3" class="profileSectionHeading" >
						<label style="font-size: 1.25rem;color: #6574cd;" > Optional Fields </label></th></tr>';

						$html .= '<tr class="categoryAttributes" ><td></td><td><select id="optionalFields"><option  value="" >--Select--</option>';

						// foreach ( $this->session['optionalFields'] as $optionalField ) {
							foreach ( $this->session['optionalFields'] as $fieldsKey1 => $fieldsValue1 ) {
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
						// }

						$html .= '</select></td>';
						$html .= '<td><button class="ced_amazon_add_rows_button" id="';
						//$html .= $fieldsKey;
						$html .= '">Add Row</button></td></tr>';
					}

					$select_html .= $html;

				} else {

					if ( ! empty( $this->session['optionalFields'] ) ) {
						$optional_fields = array_values( $optionalFields );

						$select_html .= '<tr class="categoryAttributes"><th colspan="3" class="profileSectionHeading" >
						<label style="font-size: 1.25rem;color: #6574cd;" > Optional Fields </label></th></tr>';

						$optionalFieldsHtml = '';
						$saved_value        = isset( $current_amazon_profile['category_attributes_data'] ) ? json_decode( $current_amazon_profile['category_attributes_data'], true ) : '' ;

						$html .= '<tr class="categoryAttributes"><td></td><td><select id="optionalFields"><option  value="" >--Select--</option>';
						// foreach ( $this->session['optionalFields'] as $optionalField ) {
							foreach ( $this->session['optionalFields'] as $fieldsKey1 => $fieldsValue1 ) {
								$html .= '<optgroup label="' . $fieldsKey1 . '">';
								foreach ( $fieldsValue1 as $fieldsKey2 => $fieldsValue ) {

									if ( ! array_key_exists( $fieldsKey2, $saved_value ) ) {
										$html .= '<option  value="' . htmlspecialchars( json_encode( array( $fieldsKey1 => array( $fieldsKey2 => $fieldsValue[0] ) ) ) ) . '" >' . $fieldsValue[0]['label'] . ' (' . $fieldsKey2 . ') </option>';

									} else {

										$prodileRowHTml      = $this->prepareProfileRows( $results, $query, $addedMetaKeys, $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id, '', '', $fieldsKey2, $fieldsValue[0], 'yes', '', '', '', $template_id );
										$optionalFieldsHtml .= $prodileRowHTml;
										// $prodileRowHTml      = $this->prepareProfileRows( array(), 'no', $valid_values, $sub_category_id, '', '', $fieldsKey2, $fieldsValue[0], 'yes', '', '','' );
										// $optionalFieldsHtml .= $prodileRowHTml;
									}
								}
								$html .= '</optgroup>';
							}
						// }

						$html .= '</select></td>';
						$html .= '<td><button class="ced_amazon_add_rows_button" id="' . $fieldsKey . '">Add Row</button></td></tr>';

						$select_html .= $optionalFieldsHtml;
						$select_html .= $html;

					}
				}

			} else if( 'boolean' == gettype($last) && $last ){
				
				if ( 'no' == $display_saved_values ) {

					if ( ! empty( $this->session['optionalFields'] ) ) {

						$html .= '<tr class="categoryAttributes"><th colspan="3" class="profileSectionHeading" >
						<label style="font-size: 1.25rem;color: #6574cd;" > Optional Fields </label></th></tr>';

						$html .= '<tr class="categoryAttributes" ><td></td><td><select id="optionalFields"><option  value="" >--Select--</option>';

						// foreach ( $this->session['optionalFields'] as $optionalField ) {
							foreach ( $this->session['optionalFields'] as $fieldsKey1 => $fieldsValue1 ) {
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
						// }

						$html .= '</select></td>';
						$html .= '<td><button class="ced_amazon_add_rows_button" id="';
						$html .= $fieldsKey;
						$html .= '">Add Row</button></td></tr>';
					}

					$select_html .= $html;

				} else {

					if ( ! empty( $this->session['optionalFields'] ) ) {
						$optional_fields = array_values( $optionalFields );

						$select_html .= '<tr class="categoryAttributes"><th colspan="3" class="profileSectionHeading" >
						<label style="font-size: 1.25rem;color: #6574cd;" > Optional Fields </label></th></tr>';

						$optionalFieldsHtml = '';
						$saved_value        = isset( $current_amazon_profile['category_attributes_data'] ) ? json_decode( $current_amazon_profile['category_attributes_data'], true ) : '' ;

						$html .= '<tr class="categoryAttributes"><td></td><td><select id="optionalFields"><option  value="" >--Select--</option>';
						//foreach ( $this->session['optionalFields'] as $optionalField ) {
							foreach ( $this->session['optionalFields'] as $fieldsKey1 => $fieldsValue1 ) {
								$html .= '<optgroup label="' . $fieldsKey1 . '">';
								foreach ( $fieldsValue1 as $fieldsKey2 => $fieldsValue ) {

									if ( ! array_key_exists( $fieldsKey2, $saved_value ) ) {
										$html .= '<option  value="' . htmlspecialchars( json_encode( array( $fieldsKey1 => array( $fieldsKey2 => $fieldsValue[0] ) ) ) ) . '" >' . $fieldsValue[0]['label'] . ' (' . $fieldsKey2 . ') </option>';

									} else {

										$prodileRowHTml      = $this->prepareProfileRows( $results, $query, $addedMetaKeys, $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id, '', '', $fieldsKey2, $fieldsValue[0], 'yes', '', '', '', $template_id );
										$optionalFieldsHtml .= $prodileRowHTml;
										// $prodileRowHTml      = $this->prepareProfileRows( array(), 'no', $valid_values, $sub_category_id, '', '', $fieldsKey2, $fieldsValue[0], 'yes', '', '','' );
										// $optionalFieldsHtml .= $prodileRowHTml;
									}
								}
								$html .= '</optgroup>';
							}
						//}

						$html .= '</select></td>';
						$html .= '<td><button class="ced_amazon_add_rows_button" id="' . $fieldsKey . '">Add Row</button></td></tr>';

						$select_html .= $optionalFieldsHtml;
						$select_html .= $html;

					}
				}

			}	

			
		}

		echo json_encode( array( 'success' => true, 'data' => $select_html, 'session' => $this->session )  );
		die;

	}


}

session_start();
$request_body = $_POST;

// if( 3 == $request_body['rowNum'] ){
// 	print_r($request_body);
//     print_r($request_body['session']); die('ol');
// }

// var_dump($request_body);

$instance = new Amazon_Integration_For_Woocommerce_Admin();
$instance->ced_amazon_general_options = isset( $request_body['ced_amazon_general_options'] ) ? $request_body['ced_amazon_general_options'] : array();
$instance->addedMetaKeys              = isset( $request_body['addedMetaKeys'] ) ? $request_body['addedMetaKeys'] : array();
$instance->attributes                 = isset( $request_body['attributes'] ) ? $request_body['attributes'] : array();
$instance->query                      = isset( $request_body['query'] ) ? $request_body['query'] : array();
$instance->results                    = isset( $request_body['results'] ) ? $request_body['results'] : array();
$instance->domain                     = isset( $request_body['domain'] ) ? $request_body['domain'] : array();
$instance->seller_id                  = isset( $request_body['seller_id'] ) ? $request_body['seller_id'] : array();
$instance->session                    = isset( $request_body['session'] ) ? $request_body['session'] : array();
$instance->template_id                = isset( $request_body['template_id'] ) ? $request_body['template_id'] : 0;
$instance->rowNum                     = isset( $request_body['rowNum'] ) ? $request_body['rowNum'] : 0;




$instance->ced_amazon_prepare_upload_template( $request_body );

?>