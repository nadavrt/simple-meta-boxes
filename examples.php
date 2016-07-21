<?php 
	include 'simple-meta-boxes/simple-meta-boxes.php';

	$metaboxes = array();

	//Examples of all the fields types
	$metaboxes['movie_information'] = array(
		'id' => 'movie_information',
		'title' => 'Movie Information',
		'post_type' => array( 'movies' ),
		'seamless' => TRUE,
		'description' => 'You can add the movie\'s information here.',
		'fields' => array(
	        array(
			    'title' => 'Production Studio',
			    'id' => 'production_studio',
			    'type' => 'text',
			    'sanitize' => array('plain_text'),
			),
			array(
			    'title' => 'Studio Email',
			    'id' => 'studio_email',
			    'type' => 'email',
			    'required' => 'Please provide an email address.',
			),
			array(
			    'title' => 'Short Description',
			    'id' => 'short_description',
			    'type' => 'textarea',
			    'description' => 'A short description for the movie',
			),
			array(
				'title' => 'Genres',
				'id' => 'movie_genres',
				'description' => 'Select the movie genres.',
				'type' => 'checkbox',
				'choices' => array(
					'action' => 'Action',
					'comedy' => 'Comedy',
					'drama' => 'Drama',
					'documentary' => 'Documentary'
				),
			),
			array(
				'title' => 'Released on Blu-ray',
				'id' => 'released_on_bd',
				'type' => 'radio',
				'choices' => array(
					'yes' => 'Yes',
					'no' => 'No',
				),
			),
			array(
				'title' => 'Good Movie Snack',
				'id' => 'movie_snack',
				'type' => 'select',
				'choices' => array(
					'popcorn' => 'Popcorn',
					'chocolate' => 'Chocolate',
					'none' => 'None',
				),
			),
			array(
			    'title' => 'Movie Page Color',
			    'id' => 'movie_color',
			    'description' => 'Select a color that represents this movie.',
			    'type' => 'colorpicker',
			),
			array(
				'title' => 'Movie Poster',
				'id' => 'movie_poster',
				'type' => 'media',
			),
			array(
				'title' => 'Movie Clip',
				'id' => 'movie_clip',
				'type' => 'file',
			),
			array(
				'title' => 'Movie Review',
				'id' => 'movie_review',
				'type' => 'wysiwyg',
				'args' => array(
					'media_buttons' => FALSE, //hide the media buttons
				),
			),

    	)
	);

	//Example of creating a metabox for a specific page template, and a repeater field.
	$metaboxes['special_settings'] = array(
		'id' => 'special_settings',
		'title' => 'Special Settings',
		'page_templates' => array( 'page-special.php' ),
		'fields' => array(
	        array(
			    'title' => 'Page Color',
			    'id' => 'page_color',
			    'description' => 'Select a color that represents this page.',
			    'type' => 'colorpicker',
			),
	        array(
			    'title' => 'Page Remarks',
			    'id' => 'page_remarks',
			    'description' => 'Add as many remarks as you want',
			    'type' => 'text',
			    'repeater' => TRUE,
			),			
		)
	);

	//Example of creating a metabox with custom fields that will appear in all posts and pages.
	$metaboxes['seo_fields'] = array(
		'id' => 'seo_fields',
		'title' => 'SEO',
		'post_type' => 'post',
		'page_template' => 'page',
		'fields' => array(
	        array(
			    'title' => 'Meta Title',
			    'id' => 'meta_title',
			    'type' => 'text',
			),
			array(
			    'title' => 'Meta Descriptions',
			    'id' => 'meta_description',
			    'type' => 'textarea',
			),
			array(
			    'title' => 'Meta Keys',
			    'id' => 'meta_keys',
			    'type' => 'text',
			),
		)
	);

	//Example of using a repeater group
	$metaboxes['contact_us_fields'] = array(
		'id' => 'contact_us_fields',
		'title' => 'Contacts List',
		'page_templates' => array( 'page-contact-us.php' ),
		'repeater_group' => array(
			'title' => 'Contact Person',
			'numbering_prefix' => '#',
		),
		'fields' => array(
	        array(
			    'title' => 'Name',
			    'id' => 'name',
			    'type' => 'text',
			),
			array(
			    'title' => 'Phone',
			    'id' => 'phone',
			    'type' => 'text',
			),
			array(
			    'title' => 'Email',
			    'id' => 'email',
			    'type' => 'email',
			),
		)
	);


	//Example of using a seamless repeater group with no title.
	$metaboxes['copmanies'] = array(
		'id' => 'copmanies',
		'title' => 'Companies',
		'page_templates' => array( 'page-companies.php' ),
		'seamless' => TRUE,
		'repeater_group' => array(
			'title' => '', //Let's not have a title for the groups
			'numbering' => FALSE,
		),
		'fields' => array(
	        array(
			    'title' => 'Company Name',
			    'id' => 'company_name',
			    'type' => 'text',
			),
			array(
			    'title' => 'Company Emails',
			    'id' => 'company_email',
			    'type' => 'email',
			),
		)
	);

	$smb = new Simple_Meta_Boxes($metaboxes);
?>