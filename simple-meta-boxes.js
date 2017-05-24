(function($) {     

	//Add seamless design to seamless metaboxes
	for (i = 0; i < smb.seamlessMetaBoxes.length; i++)
	{
		var metaBox = $('#' + smb.seamlessMetaBoxes[i]);
		$(metaBox).addClass('smb_seamless');
		$('> h3', metaBox).removeClass('hndle');
		$('> h2', metaBox).removeClass('hndle ui-sortable-handle');
	}

	//Hide titles from boxes with the show_title set to FALSE
	for (i = 0; i < smb.boxesWithoutTitles.length; i++)
	{
		var metaBox = $('#' + smb.boxesWithoutTitles[i]);
		$(metaBox).addClass('smb_titleless');
	}

	// Add the Wordpress color picker to all color fields.
	$('.smb_color_picker').wpColorPicker();

	$('body').on('click', 'input[type="checkbox"]', function(e){
		if ( $(this).attr('checked') ) $(this).attr('checked','checked');
		else $(this).removeAttr('checked');
	});

	// Add image deleting button functionality to all file fields.
	$('body').on('click', '.smb_thumb_container > span', function(e){
		e.preventDefault();
		var imageField = $(this).closest('.smb_field');
		$('.smb_thumb_container', imageField).remove();
		$('input', imageField).attr('value','');
	});

	 /*##############################*/
	 /*###### Required Checks ######*/
	 /*#############################*/

	var SMBFieldValidator = 
	{
		allowPublish: true,
		emptyRequiredFieldFound: false,

		/**
		*	Initialize the class and validate all SMB fields.
		*	@param NULL
		*	@return Boolean true if the form passed validation. Otherwise false.
		*
		**/
		init: function()
		{
			//Reset the class variables.
			SMBFieldValidator.allowPublish = true;
			SMBFieldValidator.emptyRequiredFieldFound = false;

			//Validate all SMB fields.
		 	$('.smb_required_message').css('display','none');
		 	SMBFieldValidator.validateInputs();
		 	SMBFieldValidator.validateTextareas();
			SMBFieldValidator.validateCheckboxs();
			SMBFieldValidator.validateFiles();

			//Display and return validation results.
			if (SMBFieldValidator.emptyRequiredFieldFound) SMBFieldValidator.showRequiredMessage();
			return SMBFieldValidator.allowPublish;
		},


		/**
		*	Validate all SMB text and email input fields.
		*	@param NULL
		*	@return NULL
		*
		**/
		validateInputs: function()
		{
			var fieldTypesToCheck = ['text','email'];
			for (i = 0; i < fieldTypesToCheck.length; i++)
			{
				$('.smb_required_' + fieldTypesToCheck[i]).each(function(){				
					if ( $('input', this).val() == '' )
					{
						SMBFieldValidator.allowPublish = false;
						SMBFieldValidator.emptyRequiredFieldFound = this;
						return false; //This will break the .each loop.
					}

					if ( fieldTypesToCheck[i] == 'email' && !($('input', this).val().match(/^([a-zA-Z0-9_.-])+@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/)) )
					{
						SMBFieldValidator.allowPublish = false;
						SMBFieldValidator.emptyRequiredFieldFound = this;
						return false; //This will break the .each loop.
					}
				});
			}			
		},


		/**
		*	Validate all SMB textarea fields.
		*	@param NULL
		*	@return NULL
		*
		**/
		validateTextareas: function()
		{
			if ( !SMBFieldValidator.allowPublish ) return;
			$('.smb_required_textarea').each(function(){
				if ( $('textarea' ,this).val() == '' )
				{
					SMBFieldValidator.allowPublish = false;
					SMBFieldValidator.emptyRequiredFieldFound = this;
					return false; //This will break the .each loop.
				}
			});
		},


		/**
		*	Validate all SMB checkbox input fields.
		*	@param NULL
		*	@return NULL
		*
		**/
		validateCheckboxs: function()
		{
			if ( !SMBFieldValidator.allowPublish ) return;
		 	$('.smb_required_checkbox').each(function(){
		 		var currentField = this;
		 		var checkedFieldFound = false;
		 		$('input[type="checkbox"]', currentField).each(function(){
		 			if ( $(this).attr('checked') && ( $(this).attr('data-flag') === undefined ) )
		 			{
		 				checkedFieldFound = true;
		 				return false; //This will break the .each loop.
		 			}
		 		});

		 		if (!checkedFieldFound) 
		 		{
		 			SMBFieldValidator.emptyRequiredFieldFound = this;
		 			SMBFieldValidator.allowPublish = false;
		 			return false; //This will break the .each loop.
		 		}
		 	});
		},


		/**
		*	Validate all SMB file fields
		*	@param NULL
		*	@return NULL
		*
		**/
		validateFiles: function()
		{
			$('.smb_required_file').each(function(){
				if ( ($('input[type="text"]', this).val() == '') && ($('input[type="file"]', this).val() == '') )
				{
					SMBFieldValidator.allowPublish = false;
					SMBFieldValidator.emptyRequiredFieldFound = this;
					return false; //This will break the .each loop.
				}
			});
		},



		/**
		*	Display the required field message for missing or erroneous fields.
		*	@param NULL
		*	@return NULL
		*
		**/
		showRequiredMessage: function()
		{
 			var currentMetaBox = $(SMBFieldValidator.emptyRequiredFieldFound).closest('.postbox');
 			if ( $(currentMetaBox).hasClass('closed') ) $(currentMetaBox).removeClass('closed');
 			$('.smb_required_message', SMBFieldValidator.emptyRequiredFieldFound).css('display','block');
 			$('html, body').animate({ scrollTop: $(SMBFieldValidator.emptyRequiredFieldFound).offset().top-100 }, 1000)
		}

	};


	 /*##############################*/
	 /*###### Repeater Fields ######*/
	 /*#############################*/

	//Add repeater (duplication) functionality to all the repeater fields.
	$('.smb_field_repeater').click(function(e){
		e.preventDefault();
		var repeater = this;
		var elementToClone = $(repeater).closest('tr').prev();
		var checkedRadioButton = false;

		//Save checked radio buttons if any exist in the group element that will be cloned.
		$('input[type="radio"]',elementToClone).each(function(){
			var checked = $(this).attr('checked');
			if ( typeof checked !== typeof undefined && checked !== false )
			{
				checkedRadioButton = $(this);
				return false; //This will break the .each loop.
			} 
		});

		//Clone the field
		var theClone = $(elementToClone).clone().insertAfter( elementToClone );

		//Enable the up down button for the element that was cloned and the up button for the clone.
		$(elementToClone).removeClass('smb_last_field');
		$(theClone).removeClass('smb_first_field');

		//Replace the duplicate group number with the clone's real group number in all fields.
		var groupNumber = $(repeater).attr('data-field-number');
		$(theClone).attr('data-field-number', groupNumber);
		var fieldTypesToChange = ['input','textarea','select'];
		for (i = 0; i < fieldTypesToChange.length; i++) 
		{
			$(fieldTypesToChange[i], theClone).each(function(){
				var currentField = $(this);
				var fieldName = $(currentField).attr('name');
				if ( fieldName )
				{
					$(currentField).attr('name', fieldName.replace(/\[\d+\]/, '[' + groupNumber + ']'));
				}
			});
		}

		//If the field to cloned was a radio button reinstate it's value.
		if ( checkedRadioButton ) $(checkedRadioButton).attr('checked','checked');

		//if the cloned field is a colorpicker reinstate its capabilities.
		var colorType = $('input.smb_color_picker', theClone).parent();
		if ( colorType )
		{
			$(colorType).closest('tr > td').prepend( $(colorType).html() );
			$('td input.wp-picker-clear', theClone).remove(); 
			$('div.wp-picker-container', theClone).remove();
			$('.smb_color_picker', theClone).wpColorPicker();
		}

		//Add deleter
		if ( $('.smb_field_deleter', theClone).val() == undefined ){
			$('> td > p.smb_description', theClone).before('<span><button class="smb_field_deleter">-</button></span>');	
		}

		//Advance the group repeater counter
		$(repeater).attr('data-field-number', parseInt(groupNumber)+1);
	});


	//Add down button functionality to all the repeater fields.
	$('body').on('click', '.smb_field_down', function(e){
		e.preventDefault();
		var elementToMoveDown = $(this).closest('tr.smb_field_repeater_field');
		var elementToMoveUp = $(elementToMoveDown).next();
		var upperElementPosition = $(elementToMoveDown).attr('data-field-number');
		changeElementPositionNumber(elementToMoveUp, upperElementPosition);
		changeElementPositionNumber(elementToMoveDown, (parseInt(upperElementPosition)+1) );

		if ( $(elementToMoveDown).hasClass('smb_first_field') )
		{
			$(elementToMoveDown).removeClass('smb_first_field');
			$(elementToMoveUp).addClass('smb_first_field');
		}
		if ( $(elementToMoveUp).hasClass('smb_last_field') )
		{
			$(elementToMoveUp).removeClass('smb_last_field');
			$(elementToMoveDown).addClass('smb_last_field');
		}

		$(elementToMoveDown).insertAfter(elementToMoveUp);
	});

	//Add up button functionality to all repeater fields.
	$('body').on('click', '.smb_field_up', function(e){
		e.preventDefault();
		var elementToMoveUp = $(this).closest('tr.smb_field_repeater_field');
		var elementToMoveDown = $(elementToMoveUp).prev();
		var upperElementPosition = $(elementToMoveDown).attr('data-field-number');
		changeElementPositionNumber(elementToMoveUp, upperElementPosition);
		changeElementPositionNumber(elementToMoveDown, (parseInt(upperElementPosition)+1) );

		if ( $(elementToMoveDown).hasClass('smb_first_field') )
		{
			$(elementToMoveDown).removeClass('smb_first_field');
			$(elementToMoveUp).addClass('smb_first_field');
		}
		if ( $(elementToMoveUp).hasClass('smb_last_field') )
		{
			$(elementToMoveUp).removeClass('smb_last_field');
			$(elementToMoveDown).addClass('smb_last_field');
		}

		$(elementToMoveDown).insertAfter(elementToMoveUp);
	});


	//Add deleter (deletion) functionality to all the repeater fields.
	$('body').on('click', '.smb_field_deleter', function(e){
		e.preventDefault();
		var elementToDelete = $(this).closest('tr.smb_field_repeater_field');
		if ( $(elementToDelete).hasClass('smb_first_field') )
		{
			var newFirstElement = $(elementToDelete).next();
			$(newFirstElement).addClass('smb_first_field');
		} 
		if ( $(elementToDelete).hasClass('smb_last_field') )
		{
			var newLastElement = $(elementToDelete).prev();
			$(newLastElement).addClass('smb_last_field');
		}
		$(elementToDelete).remove();
	});


	/**
	*	Change the position number of a field or group item. This includes adjusting all the array positions of the inputs in said item.
	*	@param Object elementToChange The DOM element the change.
	*	@param Integer newPosition The new position number
	*	@return NULL
	**/
	function changeElementPositionNumber(elementToChange,newPosition)
	{
		//if this is a group item we change its group number.
		if ( $(elementToChange).attr('data-group-number') ) $(elementToChange).attr('data-group-number', newPosition);
		else $(elementToChange).attr('data-field-number', newPosition);

		var fieldTypesToChange = ['input','textarea','select'];
		for (i = 0; i < fieldTypesToChange.length; i++) 
		{
			$(fieldTypesToChange[i], elementToChange).each(function(){
				var currentField = $(this);
				var fieldName = $(currentField).attr('name');
				if ( fieldName )
				{
					$(currentField).attr('name', fieldName.replace(/\[\d+\]/, '[' + newPosition + ']'));
				}
			});
		}

	}


	 /*##############################*/
	 /*###### Repeater Groups ######*/
	 /*#############################*/

	$('.smb_group_repeater').click(function(e){
		e.preventDefault();
		var repeater = this;
		var elementToClone = $(repeater).closest('tr').prev();

		//Save checked radio buttons if any exist in the group element that will be cloned.
		var checkedRadioButtons = [];
		var radioIndex = 0;
		$('input[type="radio"]',elementToClone).each(function(){
			var checked = $(this).attr('checked');
			if ( typeof checked !== typeof undefined && checked !== false )
			{
				checkedRadioButtons[radioIndex] = $(this);
				radioIndex++;	
			} 
		});
		
		//Clone the group
		var theClone = $(elementToClone).clone().insertAfter( elementToClone );
		
		//Replace the duplicate group number with the clone's real group number in all fields.
		var groupNumber = $(repeater).attr('data-group-number');
		var groupItemTitle = $('.smb_group_title > span', theClone).text();
		var fieldTypesToChange = ['input','textarea','select'];

		$('.smb_group_title > span', theClone).text( groupItemTitle.replace( /\d+/, parseInt(groupNumber)+1 ) );
		for (i = 0; i < fieldTypesToChange.length; i++) 
		{
			$(fieldTypesToChange[i], theClone).each(function(){
				var currentField = $(this);
				var fieldName = $(currentField).attr('name');
				if ( fieldName ){
					$(currentField).attr('name', fieldName.replace(/\[\d+\]/, '[' + groupNumber + ']'));
				}
			});
		}

		//If the element to clone was the only element enable the down button for it and the up button for the clone. Else enable the down button for the element to clone.
		var elementToCloneTitle = $('.smb_group_title', elementToClone);
		if ( $(elementToCloneTitle).hasClass('smb_first_group') && $(elementToCloneTitle).hasClass('smb_last_group') )
		{
			$(elementToCloneTitle).removeClass('smb_last_group');
			$('.smb_group_title', theClone).removeClass('smb_first_group');
		} 
		else $(elementToCloneTitle).removeClass('smb_last_group');
		

		//if the cloned group has colorpickers, reinstate their capabilities.
		var colorpickers = $('input.smb_color_picker', theClone);
		if ( colorpickers )
		{
			$( colorpickers ).each(function(){
				var colorInput = $(this).parent('span').html();
				var colorCell = $(this).closest('tr > td');
				colorCell.prepend( colorInput );
				$('.wp-picker-container', colorCell).remove();
			});

			$('.smb_color_picker', theClone).wpColorPicker();
		}

		//Reinstate radio buttons that were altered in the gropu element that was cloned.
		for (i = 0; i < checkedRadioButtons.length; i++) {
			$(checkedRadioButtons[i]).attr('checked','checked');
		}

		//Advance the group repeater counter
		$(repeater).attr('data-group-number', parseInt(groupNumber)+1);
	});


	//Add deleter (deletion) functionality to all the repeater groups.
	$('body').on('click', '.smb_group_deleter', function(e){
		e.preventDefault();
		var elementToDelete = $(this).closest('tr.smb_repeat_group_item');
		if ( $('.smb_group_title', elementToDelete).hasClass('smb_first_group') )
		{
			var newFirstElement = $(elementToDelete).next();
			$('.smb_group_title', newFirstElement).addClass('smb_first_group');
		} 
		if ( $('.smb_group_title', elementToDelete).hasClass('smb_last_group') )
		{
			var newLastElement = $(elementToDelete).prev();
			$('.smb_group_title', newLastElement).addClass('smb_last_group');
		}
		$(elementToDelete).remove();
	});

	//Add down button functionality to all the repeater groups.
	$('body').on('click', '.smb_group_down', function(e){
		e.preventDefault();
		var elementToMoveDown = $(this).closest('tr.smb_repeat_group_item');
		var elementToMoveUp = $(this).closest('tr.smb_repeat_group_item').next();
		var upperElementPosition = $(elementToMoveDown).attr('data-group-number');

		//interchange the elements positions
		changeElementPositionNumber(elementToMoveUp, upperElementPosition);
		changeElementPositionNumber(elementToMoveDown, ( parseInt(upperElementPosition)+1 ) );
		if ( $('.smb_group_title', elementToMoveDown).hasClass('smb_first_group') )
		{
			$('.smb_group_title', elementToMoveDown).removeClass('smb_first_group');
			$('.smb_group_title', elementToMoveUp).addClass('smb_first_group');
		}
		if ( $('.smb_group_title', elementToMoveUp).hasClass('smb_last_group') )
		{
			$('.smb_group_title', elementToMoveUp).removeClass('smb_last_group');
			$('.smb_group_title', elementToMoveDown).addClass('smb_last_group');
		}
		$(elementToMoveDown).insertAfter(elementToMoveUp);
		
	});

	//Add up button functionality to all the repeater groups.
	$('body').on('click', '.smb_group_up', function(e){
		e.preventDefault();
		var elementToMoveUp = $(this).closest('tr.smb_repeat_group_item');
		var elementToMoveDown = $(this).closest('tr.smb_repeat_group_item').prev();
		var upperElementPosition = $(elementToMoveDown).attr('data-group-number');

		//interchange the elements positions
		changeElementPositionNumber(elementToMoveUp, upperElementPosition);
		changeElementPositionNumber(elementToMoveDown, ( parseInt(upperElementPosition)+1 ) );
		if ( $('.smb_group_title', elementToMoveDown).hasClass('smb_first_group') )
		{
			$('.smb_group_title', elementToMoveDown).removeClass('smb_first_group');
			$('.smb_group_title', elementToMoveUp).addClass('smb_first_group');
		}
		if ( $('.smb_group_title', elementToMoveUp).hasClass('smb_last_group') )
		{
			$('.smb_group_title', elementToMoveUp).removeClass('smb_last_group');
			$('.smb_group_title', elementToMoveDown).addClass('smb_last_group');
		}
		$(elementToMoveDown).insertAfter(elementToMoveUp);
		
	});


	 /*####################################*/
	 /*######  Media Uploader Fields ######*/
	 /*####################################*/
	 
	 //Add upload button functionality for media fields.
	 $('body').on('click','.smb_media_upload', function(e) {
        mediaImg = $(this).parent('td').find('img');
        mediaUrl = $(this).parent('td').find('.smb_media_url');
        mediaContainer = $(this).parent('td').find('.smb_media_container');

        e.preventDefault();
        var custom_uploader = wp.media({
            title: 'Select Image',
            button: {
                text: 'Use This Image',
            },
            library: {
                type: 'image'
            },
            multiple: false,
        })
        .on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $(mediaImg).attr('src', attachment.url);
            $(mediaUrl).val(attachment.url);
            $(mediaContainer).css('display','table-cell');
        })
        .open();
    });

	// Add image deleting button functionality to all media fields.
	$('body').on('click', '.smb_media_container > span', function(e){
		e.preventDefault();
		var imageField = $(this).closest('.smb_field');
		$('.smb_media_container', imageField).css('display','none');
		$('input', imageField).attr('value','');
	});

	// Validate the SMB fields when the page is saved.
	$('#publish').click(function(){
	 	return SMBFieldValidator.init();
	});

})( jQuery );