<?php
class Sanitation_Methods{

	public function sanitize_plain_text($data)
	{
		if ( is_array($data) )
		{
			$sanitizedData = array();
			foreach ($data as $input){
				$sanitizedData[] = sanitize_text_field($input);
			}
			return $sanitizedData;
		}
		else return sanitize_text_field($data);
	}

	public function sanitize_id($data)
	{
		if ( is_array($data) )
		{
			$sanitizedData = array();
			foreach ($data as $input){
				$sanitizedData[] = preg_replace("/[^0-9A-Za-z-_]/", "", $input);
			}
			return $sanitizedData;
		}
		else return preg_replace("/[^0-9A-Za-z-_]/", "", $data);
	}

	public function sanitize_class($data)
	{
		if ( is_array($data) )
		{
			$sanitizedData = array();
			foreach ($data as $input){
				$sanitizedData[] = preg_replace("/[^0-9A-Za-z,-_]/", "", $input);
			}
			return $sanitizedData;
		}
		else return preg_replace("/[^0-9A-Za-z,-_]/", "", $data); 
	}

	public function sanitize_color_picker($data)
	{
		if ( is_array($data) )
		{
			$sanitizedData = array();
			foreach ($data as $input) 
			{
			 	if ( preg_match( '/^#[a-f0-9]{6}$/i', $input ) ) $sanitizedData[] = $input; //This will only be false if javascript was disabled AND the user injected an invalid value.
	    		else $sanitizedData[] = '#FFFFFF';
			}
			return $sanitizedData;
		}
		else
		{
			if ( preg_match( '/^#[a-f0-9]{6}$/i', $data ) ) return $data; //This will only be false if javascript was disabled AND the user injected an invalid value.
	    	else return '#FFFFFF';	
		}
    }

    public function sanitize_email($data)
    {
    	//If the PHP version is 2.5 or above use filter_var. If not then use filter_var's email regex instead.
    	if ( function_exists('filter_var') )
    	{
    		if ( is_array($data) )
			{
				$sanitizedData = array();
				foreach ($data as $input){
					$sanitizedData[] = (filter_var($input, FILTER_VALIDATE_EMAIL))? $input:'';
				}
				return $sanitizedData;
			}
			else if ( filter_var($data, FILTER_VALIDATE_EMAIL) ) return $data;
	    	else return '';
    	}
    	else
    	{
    		$regex = '/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD';
			if ( is_array($data) )
			{
				$sanitizedData = array();
				foreach ($data as $input){
					$sanitizedData[] = (preg_match($regex, $input))? $input:'';
				}
				return $sanitizedData;
			}
			else if ( preg_match($regex, $data) ) return $data;
	    	else return '';
    	}
    	
    }
    
}?>