<?php
/*
  Software Name: Simple Meta Boxes
  Plugin URI: http://github.com/nadavrt/simple-meta-boxes/
  Description: A simple PHP class for creating Wordpress meta boxes and custom fields.
  Version: 1.2
  Author: Nadav Rotchild
  Author URI: http://www.nadavr.com
  License: MIT license
  Wordpress Version: >= 3.5
*/

include 'sanitation-methods.php'; 
Class Simple_Meta_Boxes extends Sanitation_Methods{

	private $classUrl = ''; //The relative URL of this file.
	private $metaboxes = array(); //The metaboxes that will be passed to the class.
	private $sanitizationMethods = array(); //A list of all available sanitization methods.
	private $permittedUploadFormats = array('jpg','jpeg','png','gif','pdf','txt','docx','xlsx','pptx'); //A list of file formats that can be uploaded using this class.

	public function __construct( $metaboxes = array() )
	{
		if ( (empty($metaboxes)) || ( is_admin() !== TRUE ) ) return;
		
		//Define initial variables.
		$this->metaboxes = $metaboxes;
		$this->define_sanitization_methods();

		add_action('admin_enqueue_scripts', array($this, 'init') ); //The earliest stage at which we have access to the $post variable in the admin panel.
		add_action('post_edit_form_tag', array($this, 'enable_file_uploading') ); // Enable file uploading.
		add_action( 'save_post', array( $this, 'save_meta_data' ) );
	}

	public function init()
	{
		if ( !$this->check_if_metaboxes_exist() ) return;
		
		$this->classUrl = $this->get_class_url();

		//Create metaboxes and metabox functionality
		$this->add_metaboxes();

		//Add class styles and scripts
		$this->register_styles_and_scripts();
	}

	public function enable_file_uploading()
	{
		echo ' enctype="multipart/form-data"';
	}


	/**
	*	This function checks if the current post/page has any smb metaboxes in it. It also filters and removes any uneeded metaboxes from the $metaboxes array for this particular post/page.
	*	@return boolean TRUE if metaboxes exist. Otherwise FALSE.
	**/
	public function check_if_metaboxes_exist()
	{
		//Check if this is a valid editable post or page. 
		if ( !isset($pagenow) ) global $pagenow;
		if ( !isset($post) ) global $post;

		if ( ($pagenow != 'post.php') && ($pagenow != 'post-new.php') ) return FALSE;

		$currentPostType = get_current_screen();
		$currentPostType = $currentPostType->post_type;

		if ( $currentPostType == 'page' )
		{
			//Make sure this page was already saved once.
			if ( $post )
			{
				$currentPageTemplate = get_post_meta( $post->ID, '_wp_page_template', true );
				foreach ($this->metaboxes as $metaboxName => $metaboxArgs) 
				{
					if ( !isset($metaboxArgs['page_templates']) )
					{
						unset($this->metaboxes[$metaboxName]);
						continue;
					}

					if ( !is_array($metaboxArgs['page_templates']) )	
					{
						$arrayContent = $metaboxArgs['page_templates'];
						$metaboxArgs['page_templates'] = array( $arrayContent ); 
					}

					if ( isset($metaboxArgs['page_templates']) )
					{
						if ( ( !in_array('page', $metaboxArgs['page_templates']) ) && ( !in_array($currentPageTemplate, $metaboxArgs['page_templates']) ) ) 
						{
							unset($this->metaboxes[$metaboxName]);
							continue;
						}
					}

					$this->metaboxes[$metaboxName]['post_type'] = 'page';
				}
			}
		}
		else //This is a post
		{
			foreach ($this->metaboxes as $metaboxName => $metaboxArgs) 
			{

				if ( !isset($metaboxArgs['post_type']) )
				{
					unset($this->metaboxes[$metaboxName]);
					continue;
				}

				if ( isset($metaboxArgs['post_type']) )
				{
					if ( !is_array($metaboxArgs['post_type']) )	
					{
						$arrayContent = $metaboxArgs['post_type'];
						$metaboxArgs['post_type'] = array( $arrayContent ); 
					}

					if ( ( !in_array('post', $metaboxArgs['post_type']) ) && ( !in_array($currentPostType, $metaboxArgs['post_type']) ) )
					{
						unset($this->metaboxes[$metaboxName]);
						continue;	
					}
					else
					{
						$this->metaboxes[$metaboxName]['post_type'] = $currentPostType;
					}
				}
			}
		}
			
		return ( !empty($this->metaboxes) );
	}


	public function register_styles_and_scripts()
	{

		wp_register_style( 'smb_style', $this->classUrl . 'simple-meta-boxes.css');
		wp_enqueue_style( 'smb_style');

		//Check for boxes with no titles and seamless boxes before registering the script.
		$boxesWithoutTitles = array();
		$seamlessMetaBoxes = array();
		foreach ($this->metaboxes as $metabox) {
			if ( isset($metabox['show_title']) && ($metabox['show_title'] == FALSE) ) $boxesWithoutTitles[] = $metabox['id'];
			if ( isset($metabox['seamless']) && $metabox['seamless'] ) $seamlessMetaBoxes[] = $metabox['id'];
		}

		$jsObj =  array(
			'boxesWithoutTitles' => $boxesWithoutTitles,
			'seamlessMetaBoxes' => $seamlessMetaBoxes,
		);

		wp_enqueue_style( 'wp-color-picker' ); 
		
		wp_register_script('smb_script', $this->classUrl . 'simple-meta-boxes.js', array( 'jquery', 'wp-color-picker' ), FALSE, TRUE);
		wp_localize_script( 'smb_script', 'smb', $jsObj );
		wp_enqueue_script('smb_script');
	}

	/**
	*	Find and return the class directory name.
	*	@param NULL
	*	@return string The class url.
	*
	**/
	public function get_class_url()
	{
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' )
		{
			// Windows
			$contentDir = str_replace( '/', DIRECTORY_SEPARATOR, WP_CONTENT_DIR );
			$contentUrl = str_replace( $contentDir, WP_CONTENT_URL, dirname(__FILE__) );
			$classUrl = str_replace( DIRECTORY_SEPARATOR, '/', $contentUrl );

		} 
		else 
		{
		  $classUrl = str_replace(
				array(WP_CONTENT_DIR, WP_PLUGIN_DIR),
				array(WP_CONTENT_URL, WP_PLUGIN_URL),
				dirname( __FILE__ )
			);
		}

		return trailingslashit( $classUrl );
	}



	/**
	*	Create a list of all available sanitation methods.
	* 	@param NULL
	*	@return NULL
	**/
	public function define_sanitization_methods()
	{
		//Define all available sanitization methods.
		$sanitizationMethods = array();
		$classMethods = get_class_methods('Simple_Meta_Boxes');
		foreach ($classMethods as $method) 
		{
			if (strpos($method,'sanitize_') !== FALSE)
			{
				$method = explode('sanitize_', $method);
				if ( isset($method[1]) )
				{
					$sanitizationMethods[] = $method[1];	
				}
			} 
		}

		$this->sanitizationMethods = $sanitizationMethods;
	}


	/**
	*	Create meta boxes.
	* 	@param NULL
	*	@return NULL
	**/
	public function add_metaboxes()
	{
		foreach ($this->metaboxes as $metabox) {
			if ( !isset( $metabox['title']) || ($metabox['title'] == '' ) ) $metabox['title'] = ' ';
			add_meta_box( $metabox['id'] , $metabox['title'], array($this,'render_metabox_view'), $metabox['post_type'], 'advanced', 'high', array('metabox' => $metabox) );
		}	
	}
	 

	/**
	* 	@param object $post The WP_Post object for the current post/page.
	*	@param array $args Contains the curernt metabox data, among other things.
	*	@return NULL
	**/
	public function render_metabox_view($post, $args)
	{
		$metabox = $args['args']['metabox'];
		$seamless = isset($metabox['seamless']);
		$repeaterGroup = isset($metabox['repeater_group']);
		
		// Add an nonce field so we can check for it later.
		wp_nonce_field( $metabox['id'], $metabox['id'] . '_nonce' );

		if ( empty($metabox['fields']) )
		{
			echo 'Just another empty box.';
			return;
		}

		if ( $repeaterGroup ) echo '<table class="smb_metabox smb_repeat_group">';
		else echo '<table class="smb_metabox">';

		if ( isset($metabox['description']) ) echo '<tr><th class="smb_metabox_description" colspan="2">' . $metabox['description'] . '</th></tr>';
		
		if ( $repeaterGroup ) $this->render_repeater_group($post, $metabox);
		else
		{
			foreach ($metabox['fields'] as $field) 
			{
				if ( isset($field['repeater']) ) $this->render_repeater_field($post, $field);
				else $this->render_field($post, $field, $seamless);
			}	
		}
		
		echo '</table>';
	}


	/**
	*	Render the HTML content of custom meta fields within a metabox. The $metaData, $repeatGroup, $groupId and $groupCounter parameters are
	*	only required for repeatable groups, which is why they are marked as optional.
	*	@param object $post The global post variable.
	*	@param array $field The field to render.
	*	@param array $metaData Metadata that was already fetched by the render_repeater_group method. Used when calling this function from a repeater group (optional).
	*	@param boolean $repeatGroup Used when calling this function from a repeater group (optional).
	*	@param string $groupId The id of the current field group. Used when calling this function from a repeater group (optional).
	*	@param int $groupCounter The number of the current field group. Used when calling this function from a repeater group (optional).
	*	@return NULL
	**/
	public function render_field($post, $field, $seamless = FALSE, $metaData = FALSE, $repeatGroup = FALSE, $groupId = FALSE, $groupCounter = FALSE)
	{      //$this->render_field($post, $field, FALSE, $metaData, TRUE, $metabox['id'], $groupCounter);
		if ( $repeatGroup )
		{
			$fieldName = $groupId . '['. $groupCounter .']' . '[' . $field['id'] . ']';
			if ( !$metaData ) $metaData = '';
		}

		//If this is not an empty group try fetching the custom field,
		if ( $metaData === FALSE ) $metaData = get_post_meta($post->ID, $field['id'], TRUE);
		if ( !$repeatGroup && is_array($metaData) && isset($metaData[0]) ) $metaData = $metaData[0]; // This happens when a field that was a repeater is then reset to a regular field.
		if ( ($metaData == '') && isset($field['default']) ) $metaData = $field['default'];
		if ( is_array($metaData) )
	    {
           foreach ($metaData as $key => $value) {
                $metaData[$key] = esc_html($value);
           }
	    }
        else $metaData = esc_html($metaData);
		
		if ( !isset($field['title']) ) $field['title'] = '';
		if ( !isset($field['class']) ) $field['class'] = array('');
		$field['description'] = (isset($field['description']))? $field['description']:'';

		//Create the table row for this field.
		if ( !$repeatGroup ) $fieldName = $field['id'];
		if ( isset($field['required']) ) echo '<tr class="smb_field smb_required_' . $field['type'] . '"><th style="width:18%"><label for="'. $fieldName .'">' . $field['title'] . '<span class="smb_required_mark">*</span></label></th>';
		else echo '<tr class="smb_field"><th style="width:18%"><label for="'. $fieldName .'">' . $field['title'] . '</label></th>';

		//Generate the field's table data depending on its type.
		if ( !isset($field['type']) ) $field['type'] = 'text';
		switch ($field['type'])
		{
			case 'colorpicker':
				$field['class'][] = 'smb_color_picker';
				echo '<td><input type="' . $field['type'] . '" id="' . $field['id'] . $repeatGroup . '" class="' . trim(implode(' ', $field['class'])) . '" name="' . $fieldName . '" value="' . $metaData . '" />';
				break;

			case 'checkbox':
				echo '<td>';

				if ( isset($field['choices']) && is_array($field['choices']) )
				{
					foreach ($field['choices'] as $choiceName => $choiceValue) 
					{
						$isChecked = (is_array($metaData) && in_array($choiceName, $metaData))? 'checked':'';
						echo '<input type="checkbox" class="' . trim(implode(' ', $field['class'])) . '" name="' . $fieldName . '[' . $choiceName . ']" value="' . $choiceName . '" ' . $isChecked . '/><span class="smb_checkbox_title">' . $choiceValue . '</span>';
					}
					echo '<input type="checkbox" class="' . trim(implode(' ', $field['class'])) . '" name="' . $fieldName . '[existanceFlag]" value="1" checked="checked" data-flag="true" style="display:none;" />';
				}
				break;

			case 'email':
				echo '<td><input type="' . $field['type'] . '" id="' . $fieldName . '" class="' . trim(implode(' ', $field['class'])) . '" name="' . $fieldName . '" value="' . $metaData . '" size="25" />';
				break;

			case 'file':
				echo '<td>';
				echo '<input type="text" id="' . $fieldName . '" class="' . trim(implode(' ', $field['class'])) . ' smb_file_input" name="' . $fieldName . '" value="' . $metaData . '" size="25" />';
				echo '<input type="' . $field['type'] . '" id="' . $fieldName . '" class="' . trim(implode(' ', $field['class'])) . '" name="' . $fieldName . '" value="" />';
				if ( $metaData && ($metaData !== '') ) $this->add_file_image($metaData);
				break;

			case 'media':
				echo '<td>';
				echo '<input type="text" id="' . $fieldName . '" class="' . trim(implode(' ', $field['class'])) . ' widefat smb_media_url" name="' . $fieldName . '" value="' . $metaData . '" />';
		        echo '<a href="#" class="button smb_media_upload">Choose Image</a>';
				$this->add_media_image($metaData);
				break;

			case 'radio':
				if ( isset($field['choices']) && is_array($field['choices']) )
				{
					echo '<td>';
					foreach ($field['choices'] as $choiceName => $choiceValue) 
					{
						if ( !$metaData ) $metaData = $choiceName; //If no default was defined and no value exists default to the first radio button.
						
						$isChecked = ($metaData == $choiceName)? 'checked="checked"':'';
						echo '<input type="radio" id="' . $fieldName . '" class="' . trim(implode(' ', $field['class'])) . '" name="' . $fieldName . '" value="' . $choiceName . '" ' . $isChecked . '/><span class="smb_checkbox_title">' . $choiceValue . '</span>';
					}
				}
				break;

			case 'select':
				echo '<td>';
				if ( isset($field['choices']) && is_array($field['choices']) )
				{
					echo '<select id="'. $fieldName .'" class="' . trim(implode(' ', $field['class'])) . '" name="' . $fieldName . '">';
					foreach ($field['choices'] as $selectName => $selectValue) 
					{
						$isSelected = ($metaData==$selectName)? 'selected':'';
						echo '<option value="' . $selectName . '" ' . $isSelected . '/>' . $selectValue . '</option>';
					}
					echo '</select>';
				}
				break;


			case 'textarea':
				echo '<td><textarea id="' . $fieldName . '" name="' . $fieldName . '" class="' . trim(implode(' ', $field['class'])) . '" cols="60" rows="10" >' . $metaData . '</textarea>';
				break;

			case 'wysiwyg':
					if ( $seamless && !$repeatGroup )
					{
						if ( isset($field['args']) ) $args = $field['args'];
						else $args = array();
						
						echo '<td>';
						wp_editor( html_entity_decode(stripcslashes($metaData)), $field['id'], $args );
					}
					else echo '<td><textarea id="' . $fieldName . '" name="' . $fieldName . '" class="' . trim(implode(' ', $field['class'])) . '" cols="60" rows="10" >' . $metaData . '</textarea>';
					break;
					
			default:
				echo '<td><input type="text" id="' . $fieldName . '" class="' . trim(implode(' ', $field['class'])) . '" name="' . $fieldName . '" value="' . $metaData . '" size="25" />';
		}

		echo '<p class="smb_description">' . $field['description'] . '</p>';
		if ( isset($field['required']) ) echo '<p class="smb_required_message">'. $field['required'] . '</p>';
		echo '</td></tr>';
	}


	/**
	*	Render the HTML content of a repeatable custom meta field within a metabox.
	*	@param NULL
	*	@return NULL
	**/
	public function render_repeater_field($post, $field)
	{
		$metaDataArray = get_post_meta($post->ID, $field['id'], TRUE);
		if ( !is_array($metaDataArray) ) $metaDataArray = array($metaDataArray);
		$repeaterCounter = count($metaDataArray);
		$cloneDataNumber = 0;
		foreach ($metaDataArray as $metaData)
		{
			if ( $metaData === FALSE ) $metaData = get_post_meta($post->ID, $field['id'], TRUE);
			if ( ($metaData == '') && isset($field['default']) ) $metaData = $field['default'];
			if ( !isset($field['title']) ) $field['title'] = '';
			if ( !isset($field['description']) ) $field['description'] = '';
			if ( !isset($field['class']) ) $field['class'] = array('');

			$field['name'] = $field['id'] . '[' . $cloneDataNumber . ']';
			$fieldId = $field['id'] . '_' . $cloneDataNumber;

			$specialClasses = '';
			if ( isset($field['required']) && ($cloneDataNumber == 0) ) $specialClasses.= 'smb_required_' . $field['type'];
			if ( $cloneDataNumber == 0 ) $specialClasses.=' smb_first_field';
			if ( ($cloneDataNumber+1) ==  $repeaterCounter ) $specialClasses.= ' smb_last_field';
			
			echo '<tr class="smb_field smb_field_repeater_field'. $specialClasses . '" data-field-number="' . $cloneDataNumber . '"><th style="width:18%"><label for="'. $fieldId .'">' . $field['title'] . '</label></th>';

			if ( !isset($field['type']) ) $field['type'] = 'text';
			switch ($field['type'])
			{

				case 'colorpicker':
					$field['class'][] = 'smb_color_picker';
					echo '<td><input type="' . $field['type'] . '" id="' . $fieldId . '" class="' . trim(implode(' ', $field['class'])) . '" name="' . $field['name'] . '" value="' . $metaData . '" />';
					break;

				case 'checkbox':
					echo '<td>';
					if ( isset($field['choices']) && is_array($field['choices']) )
					{
						foreach ($field['choices'] as $choiceName => $choiceValue) 
						{
							$isChecked = (is_array($metaData) && in_array($choiceName, $metaData))? 'checked':'';
							echo '<input type="checkbox" class="' . trim(implode(' ', $field['class'])) . '" name="' . $field['name'] .'[' . $choiceName . ']" value="' . $choiceName . '" ' . $isChecked . '/><span class="smb_checkbox_title">' . $choiceValue . '</span>';
						}
						echo '<input type="checkbox" class="' . trim(implode(' ', $field['class'])) . '" name="' . $field['name'] .'[existanceFlag]" value="1" checked="checked" style="display:none;" />';
					}
					break;

				case 'email':
					echo '<td><input type="' . $field['type'] . '" id="' . $fieldId . '" class="' . trim(implode(' ', $field['class'])) . '" name="' . $field['name'] . '" value="' . $metaData . '" size="25" />';
					break;

				case 'file':
					echo '<td>';
					echo '<input type="text" id="' . $fieldId . '" class="' . trim(implode(' ', $field['class'])) . ' smb_file_input" name="' . $field['name'] . '" value="' . $metaData . '" size="25" />';
					echo '<input type="' . $field['type'] . '" id="' . $fieldId . '" class="' . trim(implode(' ', $field['class'])) . '" name="' . $field['name'] . '" value="" />';
					break;

				case 'media':
					echo '<td>';
					echo '<input type="text" id="' . $fieldId . '" class="' . trim(implode(' ', $field['class'])) . ' widefat smb_media_url" name="' . $field['name'] . '" value="' . $metaData . '" />';
			        echo '<a href="#" class="button smb_media_upload">Choose Image</a>';
					break;

				case 'radio':
					if ( isset($field['choices']) && is_array($field['choices']) )
					{
						echo '<td>';
						foreach ($field['choices'] as $choiceName => $choiceValue) 
						{
							if ( !$metaData ) $metaData = $choiceName; //If no default was defined and no value exists default to the first radio button.
							$isChecked = ($metaData == $choiceName)? 'checked="checked"':'';
							echo '<input type="radio" id="' . $fieldId . '" class="' . trim(implode(' ', $field['class'])) . '" name="' . $field['name'] . '" value="' . $choiceName . '" ' . $isChecked . '/><span class="smb_checkbox_title">' . $choiceValue . '</span>';
						}
					}
					break;

				case 'select':
					echo '<td>';
					if ( isset($field['choices']) && is_array($field['choices']) )
					{
						echo '<select class="' . trim(implode(' ', $field['class'])) . '" name="' . $field['name'] . '">';
						foreach ($field['choices'] as $selectName => $selectValue) 
						{
							$isSelected = ($metaData==$selectName)? 'selected':'';
							echo '<option value="' . $selectName . '" ' . $isSelected . '/>' . $selectValue . '</option>';
						}
						echo '</select>';
					}
					break;				

				case 'textarea':
					echo '<td><textarea id="' . $fieldId . '" name="' . $field['name'] . '" class="' . trim(implode(' ', $field['class'])) . '" cols="60" rows="10" >' . $metaData . '</textarea>';
					break;

				default:
					echo '<td><input type="text" id="' . $fieldId . '" class="' . trim(implode(' ', $field['class'])) . '" name="' . $field['name'] . '" value="' . $metaData . '" size="25" />';
			}

			echo '<button class="smb_field_up">∧</button><button class="smb_field_down">∨</button><button class="smb_field_deleter">-</button>';
			echo '<p class="smb_description">' . $field['description'] . '</p>';

			if ( $field['type'] == 'file' && $metaData && ($metaData !== '') ) $this->add_file_image($metaData);
			if ( $field['type'] == 'media' ) $this->add_media_image($metaData);
			if ( isset($field['required']) ) echo '<p class="smb_required_message">'. $field['required'] . '</p>';
			echo '</td></tr>';

			if ( ($repeaterCounter-1) ==  $cloneDataNumber ) echo '<tr class="smb_repeater_row"><td></td><td><button class="smb_field_repeater" data-field-label-id="' . $field['id']  .'_" data-field-number="' . $repeaterCounter . '">+</button></td></tr>';
			$cloneDataNumber++;
		}
	}



	/**
	*	Render the HTML content of a fields group. Creates the table structure of each group item and then calls the render_field method to generate each indivisual field.
	*	@param object $post The current post data. 
	*	@param array $metabox The metabox arguments,
	*	@return NULL
	**/
	public function render_repeater_group($post, $metabox)
	{
		$groupTitle = (isset($metabox['repeater_group']['title']))? $metabox['repeater_group']['title']:'Group';
		$firstGroup = TRUE;
		$numbering = (isset($metabox['repeater_group']['numbering']))? $metabox['repeater_group']['numbering']:TRUE;
		$numberingPrefix = (isset($metabox['repeater_group']['numbering_prefix']))? $metabox['repeater_group']['numbering_prefix']:'';
		$guiButtons = '<button class="smb_group_up">&wedge;</button><button class="smb_group_down">&vee;</button><button class="smb_group_deleter">&mdash;</button>';
		$groupMetaData = get_post_meta($post->ID, $metabox['id'], TRUE);
		
		//If this is an empty group create empty starter fields for this group.
		if ( empty($groupMetaData) )
		{
			foreach ($metabox['fields'] as $field) {
				$groupMetaData[0][$field['id']] = ''; 
			}
		}

		$numberOfGroups = count($groupMetaData);
		
		//Loop through all the field in each group.
		foreach ($groupMetaData as $groupCounter => $groupFields)
		{
			$firstItemInThisGroup = TRUE;
			foreach ($metabox['fields'] as $field) 
			{
				
				if ($field['type'] == 'checkbox') 
				{
					$metaData = $groupFields[$field['id']];
					
					//If the checkboxes array is not empty dig into the multi-dimentional array and fetch the checked boxes.
					if ( is_array($metaData) && (count($metaData) != count($metaData, COUNT_RECURSIVE)) ) $metaData = $metaData[0];
				}
				else $metaData = $groupFields[$field['id']];

				if ( $firstItemInThisGroup )
				{
					echo '<tr class="smb_repeat_group_item" data-group-number="' . $groupCounter . '">';
					echo '<td colspan="2"><table class="smb_repeater_table">';
					
					if ($numbering === TRUE) $groupNumber = ' ' . $numberingPrefix . ($groupCounter+1);
					else $groupNumber = '';

					//Is this the only item (repeatable group) in this group?
					if ( $firstGroup && (($groupCounter+1) == $numberOfGroups) ) echo '<th class="smb_metabox_description smb_group_title smb_first_group smb_last_group" colspan="2"><span>' . $groupTitle . $groupNumber . '</span>' . $guiButtons . '</th>';
					else if ($firstGroup)
					{
						echo '<th class="smb_metabox_description smb_group_title smb_first_group" colspan="2"><span>' . $groupTitle . $groupNumber . '</span>' . $guiButtons . '</th>';
						$firstGroup = FALSE;	
					}
					else if ( ($groupCounter+1) == $numberOfGroups ) echo '<th class="smb_metabox_description smb_group_title smb_last_group" colspan="2"><span>' . $groupTitle . $groupNumber . '</span>' . $guiButtons . '</th>';
					else echo '<th class="smb_metabox_description smb_group_title" colspan="2"><span>' . $groupTitle . $groupNumber . '</span>' . $guiButtons . '</th>';

					$firstItemInThisGroup = FALSE;
				}
				$this->render_field($post, $field, FALSE, $metaData, TRUE, $metabox['id'], $groupCounter);
			}

			echo '</table></td>'; //Close the group's table.
			echo '</tr>'; //Close the group's table row
		}

		echo '<tr><td><button class="smb_group_repeater" data-group-number="' . $numberOfGroups . '">+</button></td></tr>';
	}


	/**
	*	Render a visual representation of a file if it is a supported image format.
	*	@param string $file The file's url
	*	@return NULL
	**/
	public function add_file_image($file)
	{
		$fileFormat = explode('.', $file);
		$fileFormat = $fileFormat[count($fileFormat)-1];
		$imageFormats = array('jpg','jpeg','png','gif');
		if ( in_array($fileFormat, $imageFormats) ) echo '<div class="smb_thumb_container"><span>x</span><img class="smb_thumbnail" src="' . $file .'"/></div>';
	}

	/**
	*	Render a visual representation of a media image if it is a supported image format.
	*	@param string $file The file's url
	*	@return NULL
	**/
	public function add_media_image($file)
	{
		if ( $file && ($file !== '') )
		{
			$fileFormat = explode('.', $file);
			$fileFormat = $fileFormat[count($fileFormat)-1];
			$imageFormats = array('jpg','jpeg','png','gif');
			if ( in_array($fileFormat, $imageFormats) ) echo '<div class="smb_media_container"><span>x</span><img class="smb_media_thumbnail" src="' . $file .'"/></div>';
			return;
		}
		
		echo '<div class="smb_media_container" style="display:none;"><span>x</span><img class="smb_media_thumbnail" src=""/></div>';
	}


	/**
	*	Rename array key numbers to make sure arrays don't have "holes" between their keys.
	*	@param array $currentArray An array to work on
	*	@return array $rectifiedArray The sorted and arranged array.
	**/
	public function rectify_array($currentArray)
	{
		$rectifiedArray = array();
		foreach ($currentArray as $val) {
			$rectifiedArray[] = $val;
		}
		return $rectifiedArray;
	}


	/**
	*	Make sure a file's format is permitted for uploading and then upload said file to the wordpress' uploads directory.
	*	@param array $file The file data as collected by the $_FILES variable.
	*	@return string $url The url element of the uploaded file or an empty string if the file could not be uploaded.
	**/
	private function upload_file($file)
	{	
		$url = '';
		$fileFormat = explode('.', $file['name']);
		$fileFormat = $fileFormat[count($fileFormat)-1];
		if ( in_array($fileFormat, $this->permittedUploadFormats) || in_array(strtolower($fileFormat), $this->permittedUploadFormats) )
		{
			$uploadedFile = wp_upload_bits( $file['name'], null, file_get_contents($file['tmp_name']) );
	    	if( $uploadedFile['error'] == false) $url = $uploadedFile['url'];	
		}
    	
    	return $url;
	}


	/**
	*	Process the meta data submitted by the user and sanitize it if needed.
	*	@since 1.2
	*	@param array $field The meta field's information.
	*	@param mixed $metaData Meta data that was extracted from a group's array. An optional parameter that will only be available if the current meta box is a repeater group.
	*	@param Array $file The file array to be used for uploading a file. This param only exists if the file was uploaded as part of a repeater group.
	*	@return mixed $metaData The proccessed meta data.
	**/
	public function process_meta_data($field, $metaData = FALSE, $file = FALSE)
	{
		if ( !$metaData )
		{
			$metaData = $_POST[$field['id']];
			$multiDimentionalArray = FALSE;
			if ( is_array($metaData) && (count($metaData) != count($metaData, COUNT_RECURSIVE)) ) $multiDimentionalArray = TRUE;	
		}

		if ( ($field['type'] == 'checkbox') && is_array($metaData) )
		{
			if ( $multiDimentionalArray )
			{
				foreach ($metaData as $key => $fieldData) {
					unset($metaData[$key]['existanceFlag']);
					if ( empty($metaData[$key]) ) $metaData[$key] = array(FALSE);
				}
			}
			else
			{
			 unset($metaData['existanceFlag']);
			 if ( empty($metaData) ) $metaData = array(FALSE);
			}
		}

		if ( $field['type'] == 'file' )
		{
			//If this is a group item use local $file variable to upload the file
			if ($file) $metaData = $this->upload_file($file);
			else
			{
				if( !empty($_FILES) && isset($_FILES[$field['id']]) ) 
			    {
			    	//Check if there are multiple files or just one file. Even if there is one file if the field is a repeater we should treat it as an array!
			    	$fileCount = count( $_FILES[$field['id']]['name'] );
			        if ( $fileCount > 1 || is_array($_FILES[$field['id']]['name']) )
			        {
			        	$files = $_FILES[$field['id']];
			        	$newMetadata = array();
			    		//Update existing metadata values
			    		foreach ($metaData as $key => $value) 
			    		{
			    			if ( ($files['name'][$key] != '') && ($files['type'][$key] != '') && ($files['tmp_name'][$key] != '') && ($files['error'][$key] == 0) && ($files['size'][$key] != '') )
			    			{
			    				$file = array(
				        			'name' => $files['name'][$key],
				        			'type' => $files['type'][$key],
				        			'tmp_name' => $files['tmp_name'][$key],
				        			'error' => $files['error'][$key],
				        			'size' => $files['size'][$key]
			        			); 		
			        			$newMetadata[] = $this->upload_file($file);
			    			}
			    			else $newMetadata[] = $value;
			    		}
			    		$metaData = $newMetadata;
			    	}
			    	else
			    	{
			    		$file = $_FILES[$field['id']];
			        	if ( ($file['name'] != '') && ($file['type'] != '') && ($file['tmp_name'] != '') && ($file['error'] == 0) && ($file['size'] != '') )
			        		$metaData = $this->upload_file($file);
			    	}
			    }
			}
		}

		//Sanitize the field if needed.
		if ( isset($field['sanitize']) && is_array($field['sanitize']) && !empty($field['sanitize']) )
		{
			foreach ($field['sanitize'] as $sanitizationMethod) 
			{
				if ( !in_array($sanitizationMethod, $this->sanitizationMethods) ) continue;

				$methodName = 'sanitize_' . $sanitizationMethod;
				$metaData = $this->$methodName($metaData);
			}
		}

		//Mandatory Sanitize Methods:
		if ( $field['type'] == 'colorpicker') $metaData = $this->sanitize_color_picker($metaData);
		if ( $field['type'] == 'email') $metaData = $this->sanitize_email($metaData);

		//Save the meta field's new value.
		if ( is_array($metaData) && $multiDimentionalArray ) $metaData = $this->rectify_array($metaData);
		
		return $metaData;
	}


	/**
	*	Validate, sanitize and save all the custom meta fields.
	*	@param string $post_id The post id.
	*	@return NULL
	**/
	public function save_meta_data($post_id)
	{
		// If this is an autosave or a new post/page being created (not published) our form has not been submitted so we don't need to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( !isset($_POST['post_type']) ) return;
		if ( !$this->check_if_metaboxes_exist() ) return;

		// Check the user's permissions.
		if ( $_POST['post_type'] == 'page' )
		{
			if ( !current_user_can( 'edit_page', $post_id ) ) return;
		} 
		else 
		{
			if ( !current_user_can( 'edit_post', $post_id ) ) return;
		}

		// Verify this came from our screen and with proper authorization by checking if our nonce is set.
		foreach ($this->metaboxes as $metabox) 
		{
			$nonceName = $metabox['id'].'_nonce';
			if ( ( !isset( $_POST[$nonceName]) ) || ( !wp_verify_nonce($_POST[$nonceName], $metabox['id'] ) ) ) return;
		}

		// Update all the metaboxes' fields.
		foreach ($this->metaboxes as $metabox)
		{
			if ( isset($metabox['repeater_group']) )
			{
				if ( !isset($_POST[$metabox['id']]) || !is_array($_POST[$metabox['id']]) || empty($_POST[$metabox['id']]) ) continue;
				$groups = $_POST[$metabox['id']];
				$newGroupsData = array();
				$groupFields = array();

				//Get the group's available fields.
				foreach ($metabox['fields'] as $field) {
					$groupFields[$field['id']] = $field;
				}

				//Loop through all the groups
				foreach ($groups as $groupCounter => $group) 
				{
					//process and check each meta field within this group
					foreach ($group as $fieldId => $metaData) 
					{
						if ( !isset($groupFields[$fieldId]) ) continue; //If a field was injected to the DOM but does not exist in the metabox PHP array ignore it.
						
						if ( ($groupFields[$fieldId]['type'] == 'file') && !empty($_FILES) && isset($_FILES[$metabox['id']]['name'][$groupCounter][$fieldId]) && !empty($_FILES[$metabox['id']]['name'][$groupCounter][$fieldId]) )
						{
							$file = array(
								'name' => $_FILES[$metabox['id']]['name'][$groupCounter][$fieldId],
								'tmp_name' => $_FILES[$metabox['id']]['tmp_name'][$groupCounter][$fieldId]
							);
							$newGroupsData[$groupCounter][$fieldId] = $this->process_meta_data($groupFields[$fieldId], $metaData, $file);	
						}
						else $newGroupsData[$groupCounter][$fieldId] = $this->process_meta_data($groupFields[$fieldId], $metaData);
					}
				}
				update_post_meta( $post_id, $metabox['id'] , $this->rectify_array($newGroupsData) );
			}
			else
			{
				foreach ($metabox['fields'] as $field)
				{
					if ( !isset($_POST[$field['id']]) && !isset($_FILES[$field['id']]) ) continue;
					$metaData = $this->process_meta_data($field);
					update_post_meta( $post_id, $field['id'] , $metaData );
				}
			}
		}
	}
} //End of Simple_Meta_Boxes Class
?>