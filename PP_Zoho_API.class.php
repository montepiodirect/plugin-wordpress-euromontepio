<?php
/**
 * Author: Potent Plugins
 * License: GNU General Public License version 2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
 */
class PP_Zoho_API {
	
	private static $apiUrl = 'https://crm.zoho.com/crm/private/xml/';
	private static $apiTokenUrl = 'https://accounts.zoho.com/apiauthtoken/nb/create';
	private $authToken;
	
	function __construct($authToken) {
		$this->authToken = $authToken;
	}
	
	private function doApiRequest($module, $method, $params=array()) {
		$params['authtoken'] = $this->authToken;
		$params['scope'] = 'crmapi';
		$requestUrl = PP_Zoho_API::$apiUrl.$module.'/'.$method;
		$context = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'content' => http_build_query($params),
				'header' => 'Content-Type: application/x-www-form-urlencoded'
			)
		));
		$result = file_get_contents($requestUrl, false, $context);
		if ($result === false)
			return false;
		$result = simplexml_load_string($result);
		if ($result === false)
			return false;
		return $result;
	}
	
	private function doApiSearchRequest($module, $method, $criteria=array()) {
		$criteria['authtoken'] = $this->authToken;
		$criteria['scope'] = 'crmapi';
		$requestUrl = PP_Zoho_API::$apiUrl.$module.'/'.$method;
		$context = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'content' => http_build_query($criteria),
				'header' => 'Content-Type: application/x-www-form-urlencoded'
			)
		));
		$result = file_get_contents($requestUrl, false, $context);
		if ($result === false)
			return false;
		$result = simplexml_load_string($result);
		if ($result === false)
			return false;
		return $result;
	}
	
	private function doApiSearchRequest2($params=array()) {
		$token = $this->authToken;
		$url = "https://crm.zoho.com/crm/private/xml/Leads/searchRecords";
		$param = "authtoken=".$token."&scope=crmapi&criteria=".$params['criteria'];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	
	private function fieldsToXml($module, $rows) {
		$xml = new SimpleXMLElement("<$module />");
		foreach ($rows as $i => $fields) {
			$row = $xml->addChild('row');
			$row->addAttribute('no', $i + 1);
			
			foreach ($fields as $fieldName => $fieldValue) {
				$field = $row->addChild('FL', str_replace('&', '&amp;', $fieldValue));
				$field->addAttribute('val', $fieldName);
			}
		}
		return $xml->asXML();
	}
	
	private function fieldsToXml2($module) {
		$xml = new SimpleXMLElement("<$module />");
			$row = $xml->addChild('row');
			$row->addAttribute('no', 1);
			$field1 = $row->addChild('option', 'false');
			$field1->addAttribute('val', 'createPotential');
			$field2 = $row->addChild('option', 'projectmanager@montepiodirect.com');
			$field2->addAttribute('val', 'assignTo');
			$field3 = $row->addChild('option', 'true');
			$field3->addAttribute('val', 'notifyLeadOwner');
			$field4 = $row->addChild('option', 'true');
			$field4->addAttribute('val', 'notifyNewEntityOwner');
		return $xml->asXML();
	}
	
	public static function getApiToken($email, $password) {
		$context = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'content' => http_build_query(array(
					'SCOPE' => 'ZohoCRM/crmapi',
					'EMAIL_ID' => $email,
					'PASSWORD' => $password,
					'DISPLAY_NAME' => 'WooCommerce - '.substr($_SERVER['HTTP_HOST'], 0, 25)
				)),
				'header' => 'Content-Type: application/x-www-form-urlencoded'
			)
		));
		$result = file_get_contents(PP_Zoho_API::$apiTokenUrl, false, $context);
		if ($result === false)
			return false;
		foreach(explode("\n", $result) as $line) {
			$line = trim($line);
			if (strlen($line) > 10 && substr($line, 0, 10) == 'AUTHTOKEN=')
				return substr($line, 10);
		}
		return false;
	}
	
	public function addContact($contactData, $updateExisting=false) {
		$result = $this->doApiRequest('Contacts', 'insertRecords', array('newFormat' => 1, 'duplicateCheck' => ($updateExisting ? 2 : 1), 'xmlData' => $this->fieldsToXml('Contacts', array($contactData))));
		return !isset($result->error);
	}
	
	public function addLead($leadData, $updateExisting=false) {
		$result = $this->doApiRequest('Leads', 'insertRecords', array('newFormat' => 1, 'duplicateCheck' => ($updateExisting ? 2 : 1), 'xmlData' => $this->fieldsToXml('Leads', array($leadData))));
		return !isset($result->error);
	}
	
	public function convertLead($leadid) {
		$result = $this->doApiRequest('Leads', 'convertLead', array('leadId' => $leadid, 'xmlData' => $this->fieldsToXml2('Potentials')));
		return $result;
	}
	public function searchLead($criteria) {
		$parametros = $criteria;
		$result = $this->doApiSearchRequest2($parametros);
	return $result;
	}
	
}
?>