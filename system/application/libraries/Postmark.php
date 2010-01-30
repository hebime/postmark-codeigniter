<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Postmark Email Library
 *
 * Permits email to be sent using Postmarkapp.com's Servers
 *
 * @category	Libraries
 * @author      Based on work by János Rusiczki & Markus Hedlund’s.
 * @modified    Heavily Modified by Zack Kitzmiller
 * @link        http://www.github.com/zackkitzmiller/postmark-codeigniter
*/

class Postmark {

    //private
    var $CI;
    var $api_key = '';
    var $validation = FALSE;
    
    var $from_name;
    var $from_address;
    
    var $_to_name;
    var $_to_address;
    var $_subject;
    var $_message_plain;
    var $_message_html;

    /**
     * Constructor
     *
     * @access	public
     * @param	array	initialization parameters
     */	
    function Postmark($params = array())
    {
        $this->CI =& get_instance();
        
        if (count($params) > 0)
        {
            $this->initialize($params);
        }
    	
        log_message('debug', 'Postmark Class Initialized');
    
    }

	// --------------------------------------------------------------------

	/**
	 * Initialize Preferences
	 *
	 * @access	public
	 * @param	array	initialization parameters
	 * @return	void
	 */	
    function initialize($params)
	{
        $this->clear();
		if (count($params) > 0)
        {
            foreach ($params as $key => $value)
            {
                if (isset($this->$key))
                {
                    $this->$key = $value;
                }
            }
        }
	}

	// --------------------------------------------------------------------

	/**
	 * Clear the Email Data
	 *
	 * @access	public
	 * @return	void
	 */	
    function clear() {
        $this->from_name = '';
    	$this->from_address = '';
    	
    	$this->_to_name = '';
    	$this->_to_address = '';
    	$this->_subject = '';
    	$this->_message_plain = '';
    	$this->_message_html = '';	
	}
	
	// --------------------------------------------------------------------

	/**
	 * Set Email FROM address
	 *
	 * This could also be set in the config file
	 *
	 * TODO:
	 * Validate Email Addresses ala CodeIgniter's Email Class
	 *
	 * @access	public
	 * @return	void
	 */	
	function from($address, $name = null)
	{
		
		if ( ! $this->validation == TRUE)
		{
            $this->from_address = $address;
            $this->from_name = $name;
		} 
		else
        {
            if ($this->_validate_email($address))
            {
                $this->from_address = $address;
                $this->from_name = $name;
            }
            else
            {
                show_error('You have entered an invalid sender address.');
            }
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Set Email TO address
	 *
	 * TODO:
	 * Validate Email Addresses ala CodeIgniter's Email Class
	 *
	 * @access	public
	 * @return	void
	 */	
	function to($address, $name = null)
	{
	        
		if ( ! $this->validation == TRUE)
		{
            $this->_to_address = $address;
            $this->_to_name = $name;
		} 
		else
        {
            if ($this->_validate_email($address))
            {
                $this->_to_address = $address;
                $this->_to_name = $name;
            }
            else
            {
                show_error('You have entered an invalid recipient address.');
            }
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Set Email Subject
	 *
	 * @access	public
	 * @return	void
	 */	
	function subject($subject)
	{
		$this->_subject = $subject;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Set Email Message in Plain Text
	 *
	 * @access	public
	 * @return	void
	 */	
	function message_plain($message)
	{
		$this->_message_plain = $message;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Email Message in HTML
	 *
	 * @access	public
	 * @return	void
	 */	
	function message_html($message)
	{
		$this->_message_html = $message;
	}

	// --------------------------------------------------------------------
    /**
    * Private Function to prepare and send email
    */
	function _prepare_data()
	{
        $data = array();
		$data['Subject'] = $this->_subject;
        		
		$data['From'] = is_null($this->from_name) ? $this->from_address : "{$this->from_name} <{$this->from_address}>";
		$data['To'] = is_null($this->_to_name) ? $this->_to_address : "{$this->_to_name} <{$this->_to_address}>";
		
		if (!is_null($this->_message_html)) {
			$data['HtmlBody'] = $this->_message_html;
		}
		
		if (!is_null($this->_message_plain)) {
			$data['TextBody'] = $this->_message_plain;
		}
		
		return $data;
	}
	
    function send($from_address = null, $from_name = null, $to_address = null, $to_name = null, $subject = null, $message_plain = null, $message_html = null)
	{
	
		if (!is_null($from_address)) $this->from($from_address, $from_name);
		if (!is_null($to_address)) $this->to($to_address, $to_name);
		if (!is_null($subject)) $this->subject($subject);
		if (!is_null($message_plain)) $this->message_plain($message_plain);
		if (!is_null($message_html)) $this->message_html($message_html);
	
		if (is_null($this->api_key)) {
			show_error("Postmark API key is not set!");
		}
		
		if (is_null($this->from_address)) {
			show_error("From address is not set!");
		}
		
		if (is_null($this->_to_address)) {
			show_error("To address is not set!");
		}
		
		if (is_null($this->_subject)) {
			show_error("Subject is not set!");
		}
		
		if (is_null($this->_message_plain) && is_null($this->_message_html)) {
			show_error("Please either set plain message, HTML message or both!");
		}
	
		$encoded_data = json_encode($this->_prepare_data());
		
		$headers = array(
			'Accept: application/json',
			'Content-Type: application/json',
			'X-Postmark-Server-Token: ' . $this->api_key
		);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://api.postmarkapp.com/email');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded_data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		$return = curl_exec($ch);
		log_message('debug', 'JSON: ' . $encoded_data . "\nHeaders: \n\t" . implode("\n\t", $headers) . "\nReturn:\n$return");
		
		if (curl_error($ch) != '') {
			show_error(curl_error($ch));
		}
		
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if (intval($httpCode / 100) != 2) {
			$message = json_decode($return)->Message;
			show_error('Error while mailing. Postmark returned HTTP code ' . $httpCode . ' with message "'.$message.'"');
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Email Validation
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function _validate_email($address)
	{
		return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? FALSE : TRUE;
	}	
}
