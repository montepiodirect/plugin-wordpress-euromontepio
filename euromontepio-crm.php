<?php
/**
 * Plugin Name: Pruebas Alvaro
 * Plugin para hacer pruebas integrando Wordpress, Woocommerce y Zoho CRM
 * Version: 1.0.6
 * Author: Alvaro
 * Author URI: http://www.euromontepio.com
 * License: GNU General Public License version 2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
 */


// Función para enviar el contacto como Lead a Zoho CRM
function enviar_usuario_a_zoho($leadData, $updateExisting=false) { 
		$result = $this->doApiRequest('Leads', 'insertRecords', array('newFormat' => 1, 'duplicateCheck' => ($updateExisting ? 2 : 1), 'xmlData' => $this->fieldsToXml('Leads', array($leadData))));
		return !isset($result->error);
	};

         
// Añadimos la acción
add_action( 'register_new_user', 'enviar_usuario_a_zoho', 10, 1 ); 
 
 
// Conexión a la API de Zoho
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
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'pp_wczc_action_links');
function pp_wczc_action_links($links) {
	array_unshift($links, '<a href="'.esc_url(get_admin_url(null, 'admin.php?page=pp_wczc')).'">Settings</a>');
	return $links;
}
 

add_action('admin_menu', 'pp_wczc_admin_menu');
function pp_wczc_admin_menu() {
	add_submenu_page('woocommerce', 'Zoho CRM Integration', 'Zoho CRM Integration', 'manage_woocommerce', 'pp_wczc', 'pp_wczc_page');
}


function pp_wczc_page() {
	
	// Print header
	echo('
		<div class="wrap">
			<h2>Connector for WooCommerce and Zoho CRM</h2>
	');
	
	// Check for WooCommerce
	if (!class_exists('WooCommerce')) {
		echo('<div class="error"><p>This plugin requires that WooCommerce is installed and activated.</p></div></div>');
		return;
	} else if (!function_exists('wc_get_order_types')) {
		echo('<div class="error"><p>This plugin requires WooCommerce 2.2 or higher. Please update your WooCommerce install.</p></div></div>');
		return;
	}
	
	// Handle Zoho account fields submission
	if (!empty($_POST['pp_wczc_zoho_email']) && !empty($_POST['pp_wczc_zoho_password']) && check_admin_referer('pp_wczc_save_settings')) {
		if (!class_exists('PP_Zoho_API'))
			require_once(__DIR__.'/PP_Zoho_API.class.php');
		$apiToken = PP_Zoho_API::getApiToken($_POST['pp_wczc_zoho_email'], $_POST['pp_wczc_zoho_password']);
		if (empty($apiToken)) {
			echo('<div class="error"><p>An error occurred while attempting to connect your Zoho CRM account. Please ensure that your email/username and password is correct.</p></div>');
		} else {
			update_option('pp_wczc_zoho_api_token', $apiToken);
			update_option('pp_wczc_zoho_email', $_POST['pp_wczc_zoho_email']);
			echo('<div class="updated"><p>Your Zoho CRM account was connected successfully.</p></div>');
		}
	} else {
		
		if (!empty($_POST['pp_wczc_zoho_disconnect']) && check_admin_referer('pp_wczc_save_settings')) {
			delete_option('pp_wczc_zoho_api_token');
			delete_option('pp_wczc_zoho_email');
		}
		
		if (get_option('pp_wczc_zoho_api_token', false) === false) {
			echo('<div class="error"><p>You haven\'t connected your Zoho CRM account yet.</p></div>');
		}
	}
	
	// Handle other settings submission
	if (!empty($_POST) && check_admin_referer('pp_wczc_save_settings')) {
		update_option('pp_wczc_add_contacts', empty($_POST['pp_wczc_add_contacts']) ? 0 : 1);
		update_option('pp_wczc_update_contacts', empty($_POST['pp_wczc_update_contacts']) ? 0 : 1);
		update_option('pp_wczc_contacts_lead_source', empty($_POST['pp_wczc_contacts_lead_source']) ? 0 : 1);
		update_option('pp_wczc_add_leads', empty($_POST['pp_wczc_add_leads']) ? 0 : 1);
		update_option('pp_wczc_update_leads', empty($_POST['pp_wczc_update_leads']) ? 0 : 1);
		update_option('pp_wczc_leads_lead_source', empty($_POST['pp_wczc_leads_lead_source']) ? 0 : 1);
		echo('<div class="updated"><p>Your settings have been saved.</p></div>');
	}
	
	echo('<form action="" method="post" style="margin-bottom: 30px;">');
	wp_nonce_field('pp_wczc_save_settings');
	echo('<div id="poststuff">
			<div id="post-body" class="columns-2">
				<div id="post-body-content" style="position: relative;">
					<form action="#hm_sbp_table" method="post">
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label>Connection:</label>
				</th>
				<td>');
	if (get_option('pp_wczc_zoho_api_token', false) !== false)
		echo('		<p style="margin-bottom: 10px;">You have already connected your Zoho CRM account. To connect a different account, enter your login details below.</p>
					<div style="margin-bottom: 10px;">
						<label><input type="checkbox" name="pp_wczc_zoho_disconnect" value="1" /> Disconnect Zoho CRM account</label>
					</div>
		');
	echo('			<div style="margin-bottom: 10px;">
						<label style="display: inline-block; width: 160px;">Zoho Email/Username:</label>
						<input type="text" name="pp_wczc_zoho_email" value="'.htmlspecialchars(get_option('pp_wczc_zoho_email')).'" />
					</div>
					<div>
						<label style="display: inline-block; width: 160px;">Zoho Password:</label>
						<input type="password" name="pp_wczc_zoho_password" />
						<p class="description">Your password will be used to establish the connection to your Zoho CRM account and will not be stored.</p>
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label>Contacts:</label>
				</th>
				<td>
					<div style="margin-bottom: 5px;">
						<label>
							<input type="checkbox" id="pp_wczc_add_contacts" name="pp_wczc_add_contacts"'.(get_option('pp_wczc_add_contacts', 1) ? ' checked="checked"' : '').' />
							Add new WooCommerce customers as Zoho CRM contacts
						</label>
					</div>
					<div style="margin-bottom: 5px; margin-left: 20px;">
						<label>
							<input type="checkbox" id="pp_wczc_update_contacts" name="pp_wczc_update_contacts"'.(get_option('pp_wczc_update_contacts', 0) ? ' checked="checked"' : '').' />
							If a contact already exists for the customer, update it
						</label>
					</div>
					<div style="margin-bottom: 5px; margin-left: 20px;">
						<label>
							<input type="checkbox" id="pp_wczc_contacts_lead_source" name="pp_wczc_contacts_lead_source"'.(get_option('pp_wczc_contacts_lead_source', 0) ? ' checked="checked"' : '').' />
							Set Lead Source field (will overwrite existing value)
						</label>
					</div>
					<div style="margin-left: 20px;">
						<label>
							<input type="checkbox" disabled="disabled" />
							Add a note to the contact with order details (names and quantities of products ordered) <sup style="color: #f00;">PRO</sup>
						</label>
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label>Leads:</label>
				</th>
				<td>
					<div style="margin-bottom: 5px;">
						<label>
							<input type="checkbox" id="pp_wczc_add_leads" name="pp_wczc_add_leads"'.(get_option('pp_wczc_add_leads', 0) ? ' checked="checked"' : '').' />
							Add new WooCommerce customers as Zoho CRM leads
						</label>
					</div>
					<div style="margin-bottom: 5px; margin-left: 20px;">
						<label>
							<input type="checkbox" id="pp_wczc_update_leads" name="pp_wczc_update_leads"'.(get_option('pp_wczc_update_leads', 0) ? ' checked="checked"' : '').' />
							If a lead already exists for the customer, update it
						</label>
					</div>
					<div style="margin-bottom: 5px; margin-left: 20px;">
						<label>
							<input type="checkbox" id="pp_wczc_leads_lead_source" name="pp_wczc_leads_lead_source"'.(get_option('pp_wczc_leads_lead_source', 0) ? ' checked="checked"' : '').' />
							Set Lead Source field (will overwrite existing value)
						</label>
					</div>
					<div style="margin-left: 20px;">
						<label>
							<input type="checkbox" disabled="disabled" />
							Add a note to the lead with order details (names and quantities of products ordered) <sup style="color: #f00;">PRO</sup>
						</label>
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label>Potentials:</label>
				</th>
				<td>
					<div>
						<label>
							<input type="checkbox" disabled="disabled" />
							Create a potential for the order <sup style="color: #f00;">PRO</sup>
						</label>
						<p class="description">If the option to add the customer as a contact is enabled, the potential will be associated with that contact.<br />The account name will be the billing company (if specified) or the billing name.</p>
					</div>
				</td>
			</tr>
		</table>
		<button type="submit" class="button-primary">Save Settings</button>
		</form>
		</div> <!-- /post-body-content -->
			
		<div id="postbox-container-1" class="postbox-container">
			<div id="side-sortables" class="meta-box-sortables">
			
				<div class="postbox">
					<h2><a href="https://potentplugins.com/downloads/woocommerce-zoho-crm-connector-pro-plugin/?utm_source=connector-for-woocommerce-and-zoho-crm&amp;utm_medium=link&amp;utm_campaign=wp-plugin-upgrade-link" target="_blank">Upgrade to Pro</a></h2>
					<div class="inside">
						<p><strong>Upgrade to <a href="https://potentplugins.com/downloads/woocommerce-zoho-crm-connector-pro-plugin/?utm_source=connector-for-woocommerce-and-zoho-crm&amp;utm_medium=link&amp;utm_campaign=wp-plugin-upgrade-link" target="_blank">WooCommerce and Zoho CRM Connector Pro</a> for the following additional features:</strong></p>
						<ul style="list-style-type: disc; padding-left: 1.5em;">
<li>Add order details (product names and quantities) as a note to the contact and/or lead corresponding to the customer.</li>
<li>Create a potential based on the order and linked to the customer’s contact record (if one was found or created).</li>
<li>Manually send individual orders to Zoho CRM from the Order Actions menu on the Edit Order page.</li>
<li>Manually send orders to Zoho CRM individually or in bulk from the order list.</li>
						</ul>
						<p>
							<a href="https://potentplugins.com/downloads/woocommerce-zoho-crm-connector-pro-plugin/?utm_source=connector-for-woocommerce-and-zoho-crm&amp;utm_medium=link&amp;utm_campaign=wp-plugin-upgrade-link" target="_blank">Buy Now &gt;</a>
						</p>
					</div>
				</div>
				
			</div> <!-- /side-sortables-->
		</div><!-- /postbox-container-1 -->
		
		</div> <!-- /post-body -->
		<br class="clear" />
		</div> <!-- /poststuff -->
		<script>
			jQuery(\'#pp_wczc_add_contacts\').change(function() {
				if (jQuery(this).is(\':checked\')) {
					jQuery(\'#pp_wczc_update_contacts\').attr(\'disabled\', false);
				} else {
					jQuery(\'#pp_wczc_update_contacts\').attr(\'checked\', false).attr(\'disabled\', true);
				}
			});
			jQuery(\'#pp_wczc_add_contacts\').change();
			jQuery(\'#pp_wczc_add_leads\').change(function() {
				if (jQuery(this).is(\':checked\')) {
					jQuery(\'#pp_wczc_update_leads\').attr(\'disabled\', false);
				} else {
					jQuery(\'#pp_wczc_update_leads\').attr(\'checked\', false).attr(\'disabled\', true);
				}
			});
			jQuery(\'#pp_wczc_add_leads\').change();
		</script>
	');
	$potent_slug = 'connector-for-woocommerce-and-zoho-crm';
	include(__DIR__.'/plugin-credit.php');
	echo('</div>'); // /wrap
}

add_action('woocommerce_checkout_update_order_meta', 'pp_wczc_process_order');
function pp_wczc_process_order($orderId) {
	global $woocommerce;
	$zohoApiToken = get_option('pp_wczc_zoho_api_token');
	if (empty($zohoApiToken))
		return;
	$order = $woocommerce->order_factory->get_order($orderId);
	
	if (empty($order))
		return;
	
	if (!class_exists('PP_Zoho_API'))
		require_once(__DIR__.'/PP_Zoho_API.class.php');
	$zoho = new PP_Zoho_API($zohoApiToken);
	
	if (get_option('pp_wczc_add_contacts', 1)) {
		$updateContacts = get_option('pp_wczc_update_contacts', 0);
		$contactData = array(
			'First Name' => $order->billing_first_name,
			'Last Name' => $order->billing_last_name,
			'Phone' => $order->billing_phone,
			'Email' => $order->billing_email,
			'Mailing Street' => $order->billing_address_1.(empty($order->billing_address_2) ? '' : ' '.$order->billing_address_2),
			'Mailing City' => $order->billing_city,
			'Mailing State' => $order->billing_state,
			'Mailing Zip' => $order->billing_postcode,
			'Mailing Country' => $order->billing_country
		);
		if (get_option('pp_wczc_contacts_lead_source', 0))
			$contactData['Lead Source'] = 'OnlineStore';
		$zoho->addContact($contactData, !empty($updateContacts));
	}
	
	if (get_option('pp_wczc_add_leads', 0)) {
		$updateLeads = get_option('pp_wczc_update_leads', 0);
		$leadData = array(
			'First Name' => $order->billing_first_name,
			'Last Name' => $order->billing_last_name,
			'Company' => (empty($order->billing_company) ? 'Individual' : $order->billing_company),
			'Phone' => $order->billing_phone,
			'Email' => $order->billing_email,
			'Street' => $order->billing_address_1.(empty($order->billing_address_2) ? '' : ' '.$order->billing_address_2),
			'City' => $order->billing_city,
			'State' => $order->billing_state,
			'Zip Code' => $order->billing_postcode,
			'Country' => $order->billing_country
		);
		if (get_option('pp_wczc_leads_lead_source', 0))
			$leadData['Lead Source'] = 'OnlineStore';
		$zoho->addLead($leadData, !empty($updateLeads));
	}
	
}

?>