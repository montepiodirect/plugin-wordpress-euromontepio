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
}
?>