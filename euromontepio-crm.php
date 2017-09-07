<?php
/**
 * Plugin Name: Euromontepio CRM
 * Description: Plugin para hacer pruebas integrando Wordpress, Woocommerce y Zoho CRM
 * Version: 1.2
 * Author: Euromontepio
 * Author URI: http://www.euromontepio.com
 * License: GNU General Public License version 2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
 */

add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'pp_wczc_action_links');
function pp_wczc_action_links($links) {
	array_unshift($links, '<a href="'.esc_url(get_admin_url(null, 'admin.php?page=pp_wczc')).'">Ajustes</a>');
	return $links;
}
 

add_action('admin_menu', 'pp_wczc_admin_menu');
function pp_wczc_admin_menu() {
	add_menu_page('Euromontepío - Integración CRM', 'Euromontepío - Integración CRM', 'manage_woocommerce', 'pp_wczc', 'pp_wczc_page', 1);
}


function pp_wczc_page() {
	
	// Print header
	echo('
		<div class="wrap">
			<h2>Conector personalizado para WordPress, WooCommerce y Zoho CRM</h2>
	');
	
	// Check for WooCommerce
	if (!class_exists('WooCommerce')) {
		echo('<div class="error"><p>Este plugin necesita que WooCommerce esté instalado y activo.</p></div></div>');
		return;
	} else if (!function_exists('wc_get_order_types')) {
		echo('<div class="error"><p>Este plugin requiere WooCommerce 2.2 o superior. Por favor, actualiza la versión de WooCommerce.</p></div></div>');
		return;
	}
	
	// Handle Zoho account fields submission
	if (!empty($_POST['pp_wczc_zoho_email']) && !empty($_POST['pp_wczc_zoho_password']) && check_admin_referer('pp_wczc_save_settings')) {
		if (!class_exists('PP_Zoho_API'))
			require_once(__DIR__.'/PP_Zoho_API.class.php');
		$apiToken = PP_Zoho_API::getApiToken($_POST['pp_wczc_zoho_email'], $_POST['pp_wczc_zoho_password']);
		if (empty($apiToken)) {
			echo('<div class="error"><p>Ha ocurrido un error intentando conectar con tu cuenta de CRM. Asegúrate de que el usuario/email y la contraseña son correctos.</p></div>');
		} else {
			update_option('pp_wczc_zoho_api_token', $apiToken);
			update_option('pp_wczc_zoho_email', $_POST['pp_wczc_zoho_email']);
			echo('<div class="updated"><p>Tu cuenta de Zoho ya está conectada correctamente.</p></div>');
		}
	} else {
		
		if (!empty($_POST['pp_wczc_zoho_disconnect']) && check_admin_referer('pp_wczc_save_settings')) {
			delete_option('pp_wczc_zoho_api_token');
			delete_option('pp_wczc_zoho_email');
		}
		
		if (get_option('pp_wczc_zoho_api_token', false) === false) {
			echo('<div class="error"><p>Todavía no hay conexión a la cuenta de CRM.</p></div>');
		}
	}
	
	// Handle other settings submission
	if (!empty($_POST) && check_admin_referer('pp_wczc_save_settings')) {
		update_option('pp_wczc_add_contacts', empty($_POST['pp_wczc_add_contacts']) ? 0 : 1);
		update_option('pp_wczc_update_contacts', empty($_POST['pp_wczc_update_contacts']) ? 0 : 1);
		update_option('pp_wczc_contacts_lead_source', empty($_POST['pp_wczc_contacts_lead_source']) ? 0 : 1);
		update_option('pp_wczc_add_leads', empty($_POST['pp_wczc_add_leads']) ? 0 : 1);
		update_option('em_wp_zc', empty($_POST['em_wp_zc']) ? 0 : 1);
		update_option('pp_wczc_update_leads', empty($_POST['pp_wczc_update_leads']) ? 0 : 1);
		update_option('pp_wczc_leads_lead_source', empty($_POST['pp_wczc_leads_lead_source']) ? 0 : 1);
		update_option('em_lac_zc', empty($_POST['em_lac_zc']) ? 0 : 1);
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
					<label>Conexión:</label>
				</th>
				<td>');
	if (get_option('pp_wczc_zoho_api_token', false) !== false)
		echo('		<p style="margin-bottom: 10px;">Ya estás conectado a tu cuenta de CRM. Para cambiar de cuenta, introduce los datos aquí abajo.</p>
					<div style="margin-bottom: 10px;">
						<label><input type="checkbox" name="pp_wczc_zoho_disconnect" value="1" /> Desconectar de la cuenta de CRM</label>
					</div>
		');
	echo('			<div style="margin-bottom: 10px;">
						<label style="display: inline-block; width: 160px;">Email/usuario de Zoho:</label>
						<input type="text" name="pp_wczc_zoho_email" value="'.htmlspecialchars(get_option('pp_wczc_zoho_email')).'" />
					</div>
					<div>
						<label style="display: inline-block; width: 160px;">Contraseña de Zoho:</label>
						<input type="password" name="pp_wczc_zoho_password" />
						<p class="description">La contraseña es solo para establecer la conexión, no se queda guardada.</p>
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label>Contactos:</label>
				</th>
				<td>
					<div style="margin-bottom: 5px;">
						<label>
							<input type="checkbox" id="pp_wczc_add_contacts" name="pp_wczc_add_contacts"'.(get_option('pp_wczc_add_contacts', 1) ? ' checked="checked"' : '').' />
							Añadir nuevos usuarios de WooCommerce como "Contactos" en el CRM
						</label>
					</div>
					<div style="margin-bottom: 5px; margin-left: 20px;">
						<label>
							<input type="checkbox" id="pp_wczc_update_contacts" name="pp_wczc_update_contacts"'.(get_option('pp_wczc_update_contacts', 0) ? ' checked="checked"' : '').' />
							Si ya existe Contacto para el usuario, actualizarlo
						</label>
					</div>
					<div style="margin-bottom: 5px; margin-left: 20px;">
						<label>
							<input type="checkbox" id="pp_wczc_contacts_lead_source" name="pp_wczc_contacts_lead_source"'.(get_option('pp_wczc_contacts_lead_source', 0) ? ' checked="checked"' : '').' />
							Establecer fuente de posible cliente (sobreescribirá el valor actual)
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
							Añadir nuevos usuarios de WooCommerce como Leads
						</label>
					</div>
					<div style="margin-bottom: 5px; margin-left: 20px;">
						<label>
							<input type="checkbox" id="pp_wczc_update_leads" name="pp_wczc_update_leads"'.(get_option('pp_wczc_update_leads', 0) ? ' checked="checked"' : '').' />
							Si ya existe Lead para el usuario, actualizarlo
						</label>
					</div>
					<div style="margin-bottom: 5px; margin-left: 20px;">
						<label>
							<input type="checkbox" id="pp_wczc_leads_lead_source" name="pp_wczc_leads_lead_source"'.(get_option('pp_wczc_leads_lead_source', 0) ? ' checked="checked"' : '').' />
							Establecer fuente de Lead (sobreescribirá el valor actual)
						</label>
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label>Usuarios de Wordpress:</label>
				</th>
				<td>
					<div style="margin-bottom: 5px;">
						<label>
							<input type="checkbox" id="em_wp_zc" name="em_wp_zc"'.(get_option('em_wp_zc', 0) ? ' checked="checked"' : '').' />
							Añadir nuevos usuarios de WordPress como Leads
						</label>
					</div>
					<div style="margin-bottom: 5px;">
						<label>
							<input type="checkbox" id="em_lac_zc" name="em_lac_zc"'.(get_option('em_lac_zc', 0) ? ' checked="checked"' : '').' />
							Convertir Lead en Contacto al realizar una compra
						</label>
					</div>
				</td>
			</tr>
		</table>
		<button type="submit" class="button-primary">Guardar ajustes</button>
		</form>
		</div> <!-- /post-body-content -->		
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
	echo('</div>'); // /wrap
}

add_action('woocommerce_checkout_update_order_meta', 'convertir_lead_a_contacto', 10, 1);
//add_action('woocommerce_order_status_completed', 'convertir_lead_a_contacto');
add_action('user_register', 'enviar_usuario_a_zoho', 10, 1); 
function enviar_usuario_a_zoho($user_id) {
    $usuario = get_userdata( $user_id );
	$zohoApiToken = get_option('pp_wczc_zoho_api_token');
	if (empty($zohoApiToken))
		return;
	if (empty($usuario))
		return;
	if (!class_exists('PP_Zoho_API'))
		require_once(__DIR__.'/PP_Zoho_API.class.php');
	$zoho = new PP_Zoho_API($zohoApiToken);	
	if (get_option('em_wp_zc', 1)) {
		$updateLeads = get_option('em_wp_zc', 0);
	 $leadData = array(
			'First Name' => $usuario->user_login,
			'Last Name' => $usuario->display_name,
			'Email' => $usuario->user_email,
			'Lead Source' => 'Registro Wordpress',
		);
		//if (get_option('pp_wczc_contacts_lead_source', 0))
		//	$contactData['Lead Source'] = 'Tienda Online';
		$zoho->addLead($leadData, !empty($updateLeads));
	}
}

function convertir_lead_a_contacto($user_id) {
	$usuario = get_userdata( $user_id );
	$email = $usuario->user_email;
	if (get_option('em_lac_zc') == 1){
	if (!class_exists('PP_Zoho_API'))
	require_once(__DIR__.'/PP_Zoho_API.class.php');
	$zohoApiToken = get_option('pp_wczc_zoho_api_token');
	$zoho = new PP_Zoho_API($zohoApiToken);
	$hayid = buscar_lead_id($email);
	if($hayid != null){
		$zoho->convertLead($hayid);
	}
	}
}

function buscar_lead_id($email) {
//	$zohoApiToken = get_option('pp_wczc_zoho_api_token');
//	$zoho = new PP_Zoho_API($zohoApiToken);	
	$params = array();
	$params['criteria'] = '(Email:'.$email.')';
	$result = $zoho->doApiSearchRequest2($params);
    $xml = simplexml_load_string($result);
	if($xml->nodata->code == 4422){
		$leadid = null;
	}else{
	$leadid = $xml->result->Leads->row[0]->FL->__toString(); 
	}
	return $leadid;
}

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
			$contactData['Lead Source'] = 'Tienda Online';
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