<?php

require_once "class.ilnetucateResponse.php";
require_once "class.ilXmlWriter.php";
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

/**
* API to communicate with a the CMSAPI of centra
* (c) Sascha Hofmann, 2004
*  
* @author	Sascha Hofmann <saschahofmann@gmx.de>
*
* @version	$Id: class.ilnetucateXMLAPI.php 15689 2008-01-08 15:55:14Z akill $
* 
*/
class ilnetucateXMLAPI extends ilXmlWriter
{
	function ilnetucateXMLAPI()
	{
                //global $CFG;
		
		parent::ilXmlWriter();

                $this->admin_login = get_config('mod/netucate', 'netucate_admin_login');
                $this->admin_password = get_config('mod/netucate', 'netucate_admin_password');
                $this->customer_id = get_config('mod/netucate', 'netucate_customer_id');

                $this->api_url = get_config('mod/netucate', 'netucate_api_url');
                $url = parse_url($this->api_url);
                $this->server_addr = $url["host"];
                $this->server_protocol = $url["scheme"];
                $this->server_path = $url["path"];
                if ($this->server_protocol == 'https') {
                    $this->server_port = '443';
                    $this->scheme = "ssl";
                }
                else
                {
                    $this->server_port = '80';
                    $this->scheme = "http";
                }

                $this->api_timeout = get_config('mod/netucate', 'netucate_api_timeout'); 
	}

	// send request to iLinc server
	// returns true if request was successfully sent (a response returned)
	function sendRequest($a_request = '')
	{
		global $ilErr,$lng;
		
		// get request xml data
		$this->request = $this->xmlDumpMem();

		// compose request header
		$header = "Host: ".$this->server_addr."\r\n";
		$header .= "User-Agent: moodle open source\r\n";
		$header .= "Content-Type: text/xml\r\n";
		$header .= "Content-Length: ".strlen($this->request)."\r\n";
		$header .= "Connection: close\r\n\r\n";

		// open socket connection to server
                if ($this->scheme == 'ssl') {
                    //$sock = @fsockopen($this->scheme."://".$this->getServerAddr(), $this->getServerPort(), $errno, $errstr, $this->getAPITimeOut());
                    $sock = @fsockopen($this->scheme."://".$this->server_addr, $this->server_port, $errno, $errstr, $this->api_timeout);
                }
                else
                {
                    $sock = @fsockopen($this->server_addr, $this->server_port, $errno, $errstr, $this->api_timeout);
                }

		if (!$sock)
		{
                        error($errstr . "<br>" . get_string('unknownhost', 'netucate'));
		}

		// send request
		fputs($sock, "POST ".$this->server_path." HTTP/1.0\r\n");
		fputs($sock,$header.$this->request);
		
		$response = "";
		// read response data and surpress error from buggy IIS software (missing 'close_notify' cause fatal error)
		while (!feof($sock))
		{
			$response .= @fgets($sock, 128);
		}
		
		fclose($sock);
		
		// return netucate response object
		$response_obj =  new ilnetucateResponse($response);
		
		return $response_obj;
	}
	
	function AddUser(&$user_obj)
	{
		$this->xmlClear();
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
		$attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
                $attr['Method'] = "AddUser";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
                $attr['FirstName'] = $user_obj['firstname'];
                $attr['LastName'] = $user_obj['lastname'];
                $attr['Password'] = $user_obj['password'];
                $attr['Email'] = $user_obj['email'];

		$this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');	
		$this->xmlEndTag('netucate.API.Request');
	}
	
	function EditUser(&$user_obj)
	{
		$this->xmlClear();
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');

		$attr = array();
		$attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
                $attr['Method'] = "EditUser";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
		$attr['UserID'] = $user_obj['userid'];
                $attr['FirstName'] = $user_obj['firstname'];
                $attr['LastName'] = $user_obj['lastname'];
		$attr['Email'] = $user_obj['email'];

		$this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');
		$this->xmlEndTag('netucate.API.Request');
	}

       function DeleteUser($user_id)
	{
		$this->xmlClear();
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');

                $attr = array();
                $attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
                $attr['Method'] = "DeleteUser";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

                $attr = array();
                $attr['UserID'] = $user_id;
                $this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');
		$this->xmlEndTag('netucate.API.Request');
	}

	function GetUser($user_id)
	{
		$this->xmlClear();
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');

		$attr = array();
		$attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
                $attr['Method'] = "GetUser";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
                $attr['UserID'] = $user_id;
                $this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');
		$this->xmlEndTag('netucate.API.Request');
	}

	function RegisterUser($activity_id, $user_id, $isassistant='yes', $sendinviteemail='no')
	{
		$this->xmlClear();
		$this->xmlHeader();

                $this->xmlStartTag('netucate.API.Request');

		$attr = array();
                $attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
                $attr['Method'] = "RegisterUser";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

                $attr = array();
                $attr['ActivityID'] = $activity_id;
                $attr['UserID'] = $user_id;
                $attr['IsAssistant'] = $isassistant;
                $attr['SendInviteEmail'] = $sendinviteemail;
                $this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');
		$this->xmlEndTag('netucate.API.Request');
	}
	
	function UnRegisterUser($activity_id, $user_id)
	{
		$this->xmlClear();
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
                $attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
                $attr['Method'] = "UnRegisterUser";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

                $attr = array();
                $attr['ActivityID'] = $activity_id;
                $attr['UserID'] = $user_id;
                $this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');
		$this->xmlEndTag('netucate.API.Request');
	}
	
	function AddActivity(&$activity_obj)
	{
		$this->xmlClear();
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
		$attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
                $attr['Method'] = "AddActivity";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
                $attr['ActivityType'] = $activity_obj['activity_type'];
		$attr['Title'] = $activity_obj['title'];
                //$attr['OwnerID'] = $activity_obj['owner_id'];
		$attr['LeaderID'] = $activity_obj['leader_id'];
		$attr['Description'] = $activity_obj['desc'];

		$this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');		
		$this->xmlEndTag('netucate.API.Request');
	}

	function EditActivity($activity_id, &$activity_obj)
	{
		$this->xmlClear();
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');

		$attr = array();
		$attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
                $attr['Method'] = "EditActivity";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
                $attr['ActivityID'] = $activity_id;
		$attr['Title'] = $activity_obj['title'];
                if (isset($activity_obj['owner_id'])) {
                    $attr['OwnerID'] = $activity_obj['owner_id'];
                }
                if (isset($activity_obj['$activity_obj'])) {
                    $attr['LeaderID'] = $activity_obj['leader_id'];
                }

                $this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');
		$this->xmlEndTag('netucate.API.Request');
	}

        function DeleteActivity($activity_id)
	{
		$this->xmlClear();
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');

                $attr = array();
                $attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
                $attr['Method'] = "DeleteActivity";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

                $attr = array();
                $attr['ActivityID'] = $activity_id;
                $this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');
		$this->xmlEndTag('netucate.API.Request');
	}

	function GetActivity($activity_id)
	{
		$this->xmlClear();
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');

		$attr = array();
		$attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
		$attr['id'] = "";
                $attr['Method'] = "GetActivity";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
                $attr['ActivityID'] = $activity_id;
                $this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');
		$this->xmlEndTag('netucate.API.Request');
	}

	function GetActivitySchedule($activity_id)
	{
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');

		$attr = array();
                $attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
                $attr['Method'] = "GetActivitySchedule";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
                $attr['ActivityID'] = $activity_id;
                $this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');
		$this->xmlEndTag('netucate.API.Request');
	}

	function EditActivitySchedule($activity_id, &$activity_obj)
	{
		$this->xmlClear();
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');

		$attr = array();
                $attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
                $attr['Method'] = "EditActivitySchedule";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

                $attr = array();
                $attr['ActivityID'] = $activity_id;
                $attr['ScheduleType'] = $activity_obj['scheduletype'];
                if ($activity_obj['scheduletype'] == "single") {
                    $attr['StartDate'] = $activity_obj['startdate'];
                    $attr['Duration'] = $activity_obj['duration'];
                    $attr['JoinBuffer'] = $activity_obj['joinbuffer'];
                }
                $this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');
		$this->xmlEndTag('netucate.API.Request');
	}

        function GetUserRegistration($user_id)
	{
		$this->xmlClear();
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');

		$attr = array();
		$attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
		$attr['id'] = "";
                $attr['Method'] = "GetUserRegistration";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
                $attr['UserID'] = $user_id;
                $attr['UserRoleType'] = 'assistant';
		$attr['IncludeExpired'] = 'yes';
                $this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');
		$this->xmlEndTag('netucate.API.Request');
	}
	
	function GetJoinURL($user_id, $activity_id)
	{
		$this->xmlClear();
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');

		$attr = array();
                $attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
                $attr['Method'] = "GetJoinURL";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');               
                
                $attr = array();
                $attr['ActivityID'] = $activity_id;
                $attr['UserID'] = $user_id;
                $this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');
		$this->xmlEndTag('netucate.API.Request');
	}
	
	function GetLoginURL($user_id)
	{
		$this->xmlClear();
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
                
		$attr = array();
                $attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
                $attr['Method'] = "GetLoginURL";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
                $attr['UserID'] = $user_id;                
                $this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');
		$this->xmlEndTag('netucate.API.Request');
	}
	
	function GetUploadUserPictureURL($user_id)
	{
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');

		$attr = array();
                $attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
                $attr['Method'] = "GetUploadUserPictureURL";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
                $attr['UserID'] = $user_id;
                $this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');
		$this->xmlEndTag('netucate.API.Request');
	}

	function EncryptText($user_id)
	{
		$this->xmlClear();
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');

		$attr = array();
		$attr['User'] = $this->admin_login;
		$attr['Password'] = $this->admin_password;
		$attr['CustomerID'] = $this->customer_id;
                $attr['Method'] = "EncryptText";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
                $attr['Text'] = $user_id;
                $attr['Method'] = '2';
                $this->xmlStartTag('netucate.Parameters',$attr);
		$this->xmlEndTag('netucate.Parameters');
		$this->xmlEndTag('netucate.API.Request');
	}
}
?>