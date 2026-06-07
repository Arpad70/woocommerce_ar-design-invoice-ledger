<?php
/**
 * Plugin Name: AR Design Invoice Ledger for WooCommerce
 * Description: Evidenčná kniha vystavených WooCommerce faktúr pod menu PDF Invoices s exportom do ekonomického SW podľa filtrov.
 * Version: 1.0.1
 * Author: Arpád Horák
 * Author URI: https://arpad-horak.cz
 * Update URI: https://github.com/Arpad70/woocommerce_ar-design-invoice-ledger
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ar-design-invoice-ledger
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.9.4
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 10.6.1
 */

namespace ArDesign\InvoiceLedger;

defined('ABSPATH') || exit;

$plugin_dir = str_replace(basename(__FILE__), '', plugin_basename(__FILE__));
$plugin_dir = substr($plugin_dir, 0, strlen($plugin_dir) - 1);

define('AR_DESIGN_INVOICE_LEDGER_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AR_DESIGN_INVOICE_LEDGER_PLUGIN_DIR', $plugin_dir);
define('AR_DESIGN_INVOICE_LEDGER_PLUGIN_INDEX', __FILE__);
define('AR_DESIGN_INVOICE_LEDGER_VERSION', '1.0.1');
define('AR_DESIGN_INVOICE_LEDGER_BASENAME', plugin_basename(__FILE__));
define('AR_DESIGN_INVOICE_LEDGER_REPOSITORY', 'Arpad70/woocommerce_ar-design-invoice-ledger');
define('AR_DESIGN_INVOICE_LEDGER_TEXT_DOMAIN', 'ar-design-invoice-ledger');

require_once AR_DESIGN_INVOICE_LEDGER_PLUGIN_PATH . 'includes' . DIRECTORY_SEPARATOR . 'Updater.php';
require_once AR_DESIGN_INVOICE_LEDGER_PLUGIN_PATH . 'includes' . DIRECTORY_SEPARATOR . 'AdminPage.php';

function ard_invoice_ledger_is_woocommerce_ready(): bool {
	return class_exists('WooCommerce') && function_exists('wc_get_orders');
}

function ard_invoice_ledger_is_pdf_invoices_ready(): bool {
	return function_exists('wcpdf_get_document');
}

function ard_invoice_ledger_load_textdomain(): void {
	load_plugin_textdomain(
		AR_DESIGN_INVOICE_LEDGER_TEXT_DOMAIN,
		false,
		dirname(AR_DESIGN_INVOICE_LEDGER_BASENAME) . '/languages/'
	);
}

function ard_invoice_ledger_dependencies_notice(): void {
	if (!current_user_can('activate_plugins')) {
		return;
	}

	$messages = array();

	if (!ard_invoice_ledger_is_woocommerce_ready()) {
		$messages[] = __('AR Design Invoice Ledger plugin requires active WooCommerce.', AR_DESIGN_INVOICE_LEDGER_TEXT_DOMAIN);
	}

	if (!ard_invoice_ledger_is_pdf_invoices_ready()) {
		$messages[] = __('AR Design Invoice Ledger plugin requires active WooCommerce PDF Invoices & Packing Slips plugin.', AR_DESIGN_INVOICE_LEDGER_TEXT_DOMAIN);
	}

	if (empty($messages)) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	echo esc_html(implode(' ', $messages));
	echo '</p></div>';
}

function ard_invoice_ledger_bootstrap(): void {
	ard_invoice_ledger_load_textdomain();

	if (!ard_invoice_ledger_is_woocommerce_ready()) {
		add_action('admin_notices', __NAMESPACE__ . '\\ard_invoice_ledger_dependencies_notice');
		return;
	}

	$admin_page_class = __NAMESPACE__ . '\\AdminPage';
	if (class_exists($admin_page_class) && is_callable(array($admin_page_class, 'register'))) {
		$admin_page_class::register();
	}
}

add_action('plugins_loaded', __NAMESPACE__ . '\\ard_invoice_ledger_bootstrap', 20);

$ard_invoice_ledger_repository = apply_filters(
	'ard_invoice_ledger_repository',
	AR_DESIGN_INVOICE_LEDGER_REPOSITORY
);

$updater_class = __NAMESPACE__ . '\\ArDesignInvoiceLedgerUpdater';
if (class_exists($updater_class)) {
	$ard_invoice_ledger_updater = new $updater_class(
		(string) $ard_invoice_ledger_repository,
		AR_DESIGN_INVOICE_LEDGER_BASENAME,
		AR_DESIGN_INVOICE_LEDGER_VERSION
	);

	if (is_object($ard_invoice_ledger_updater) && is_callable(array($ard_invoice_ledger_updater, 'register'))) {
		$ard_invoice_ledger_updater->register();
	}
}

register_uninstall_hook(AR_DESIGN_INVOICE_LEDGER_PLUGIN_INDEX, __NAMESPACE__ . '\\ard_invoice_ledger_uninstall');

function ard_invoice_ledger_uninstall(): void {
	delete_option('ar_design_invoice_ledger_export_software');
}
