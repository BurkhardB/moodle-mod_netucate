<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/


require_once("class.ilSaxParser.php");

/**
* process reponse from Centra Server
* (c) Sascha Hofmann, 2004
*  
* @author	Sascha Hofmann <saschahofmann@gmx.de>
* @version	$Id: class.ilnetucateResponse.php 15689 2008-01-08 15:55:14Z akill $
* 
*/
class ilnetucateResponse extends ilSaxParser
{
	/**
	* Constructor
	* @access	public
	*/
	function ilnetucateResponse($a_str)
	{
		$xml_str = $this->validateInput($a_str);

		parent::ilSaxParser($xml_str);
				
		$this->startParsing();
	}
	
	function validateInput($a_str)
	{
		$response = split("\r\n\r\n",$a_str);
	
		$header = $response[0];
		$response_data = $response[1];
		
		if (strpos($response_data,"<?xml") === false)
		{
			//echo "netucateResponse::validateInput() : No valid response data!<br/>";
			//error("netucateResponse::validateInput() : No valid response data!<br/>" . var_dump($header,$response_data));
                        error("Header:<br>" . $header . "<p>Response_Data:" . $response_data);
                        //var_dump($header,$response_data);
			//exit;
		}
        
        return chop($response_data);
	}
	
	function isError()
	{
		if ($this->data['result']['ErrorID'] != 0)
		{
			return true;
		}
		
		return false;
	}
	
	function getErrorMsg()
	{
		if ($this->data['result']['ErrorID'] != 0)
		{
			return "netucate/iLinc Error: " . trim($this->data['result']['cdata'].' ('. $this->data['result']['ErrorID'].')');
		}
	}
	
	//function getResultMsg()
	//{
	//	return trim($this->data['return']['cdata']);
	//}
	
	function getFirstID()
	{
		reset($this->data['id']);
		return current($this->data['id']);
	}
		

	/**
	 * set event handler
	 * should be overwritten by inherited class
	 * @access	private
	 */
	function setHandlers($a_xml_parser)
	{
		xml_set_object($a_xml_parser,$this);
		xml_set_element_handler($a_xml_parser,'handlerBeginTag','handlerEndTag');
		xml_set_character_data_handler($a_xml_parser,'handlerCharacterData');
	}

	/**
	 * start the parser
	 */
	function startParsing()
	{
		$xml_parser = $this->createParser();
		$this->setOptions($xml_parser);
		$this->setHandlers($xml_parser);
		$this->parse($xml_parser,$this->xml_file);
		$this->freeParser($xml_parser);
		return true;
	}
	
	/**
	* parse xml file
	* 
	* @access	private
	*/
	function parse($a_xml_parser,$a_xml_str)
	{
		$parseOk = xml_parse($a_xml_parser,$a_xml_str,true);

		if (!$parseOk && (xml_get_error_code($a_xml_parser) != XML_ERROR_NONE))
		{
				$this->ilias->raiseError("XML Parse Error: ".xml_error_string(xml_get_error_code($a_xml_parser)),$this->ilias->error_obj->FATAL);
		}
	}


	/**
	 * handler for begin of element
	 */
	function handlerBeginTag($a_xml_parser, $a_name, $a_attribs)
	{
		global $ilErr;

		switch($a_name)
		{
			case "netucate.API.Response":
				break;

			case "netucate.Result":
				$this->data['result']['ErrorID'] = $a_attribs['ErrorID'];
				$this->data['result']['Method'] = $a_attribs['Method'];
				break;

 			case "netucate.Return":
                                if ($this->data['result']['Method'] == 'AddActivity') {
                                    $this->data['return']['ActivityID'] = $a_attribs['ActivityID'];
                                    $this->data['return']['EncryptedActivityID'] = $a_attribs['EncryptedActivityID'];
                                    break;
                                }
                                if ($this->data['result']['Method'] == 'AddUser') {
                                    $this->data['return']['UserID'] = $a_attribs['UserID'];
                                    $this->data['return']['EncryptedUserID'] = $a_attribs['EncryptedUserID'];
                                    break;
                                }                                
                                if ($this->data['result']['Method'] == 'GetJoinURL') {
                                    $this->data['return']['cdata'] = $this->cdata;
                                    break;
                                }

                                if ($this->data['result']['Method'] == 'GetLoginURL') {
                                    $this->data['return']['cdata'] = $this->cdata;
                                    break;
                                }

                                if ($this->data['result']['Method'] == 'GetUploadUserPictureURL') {
                                    $this->data['return']['cdata'] = $this->cdata;
                                    break;
                                }

                                if ($this->data['result']['Method'] == 'GetActivitySchedule') {
                                    $this->data['return']['ScheduleType'] = $a_attribs['ScheduleType'];
                                    if ($a_attribs['ScheduleType'] == 'single') {
                                        $this->data['return']['StartDate'] = $a_attribs['StartDate'];
                                        $this->data['return']['Duration'] = $a_attribs['Duration'];
                                        $this->data['return']['JoinBuffer'] = $a_attribs['JoinBuffer'];
                                    }
                                    break;
                                }

                                if ($this->data['result']['Method'] == 'GetActivity') {
                                    break;
                                }

                                if ($this->data['result']['Method'] == 'GetUser') {
                                    $this->data['return']['FirstName'] = $a_attribs['FirstName'];
                                    $this->data['return']['LastName'] = $a_attribs['LastName'];
                                    $this->data['return']['Email'] = $a_attribs['Email'];
                                    break;
                                }

                                if ($this->data['result']['Method'] == 'GetUserRegistration') {
                                    break;
                                }
				
			case "netucate.Activity":
                            if ($this->data['result']['Method'] == 'GetActivity') {
                                $this->data['return']['ActivityType'] = $a_attribs['ActivityType'];
                                $this->data['return']['Title'] = $a_attribs['Title'];
                                $this->data['return']['OwnerID'] = $a_attribs['OwnerID'];
                                $this->data['return']['LeaderID'] = $a_attribs['LeaderID'];
                                $this->data['return']['EncryptedLeaderID'] = $a_attribs['EncryptedLeaderID'];
                                //echo "hier richtig";
                                break;
                            }
                            
                            if ($this->data['result']['Method'] == 'GetUserRegistration') {
                                $this->data['ActivityIDs'][$a_attribs['ActivityID']] = $a_attribs['ActivityID'];
                                break;
                            }
		}
	}


	function handlerEndTag($a_xml_parser, $a_name)
	{
		switch($a_name)
		{
			case "netucate.API.Response":
				$this->data['response']['cdata'] = $this->cdata;
				break;

			case "netucate.Result":
				$this->data['result']['cdata'] = $this->cdata;
				break;

			case "netucate.Return":
				$this->data['return']['cdata'] = $this->cdata;
				break;
				
			case "netucate.Activity":
			case "netucate.User":
				break;
		}
		
		$this->cdata = '';
	}
	
	/**
	 * handler for character data
	 */
	function handlerCharacterData($a_xml_parser, $a_data)
	{
		if(!empty($a_data))
		{
			$this->cdata .= $a_data;
                        //echo $this->cdata;
		}
	}
	
}
?>