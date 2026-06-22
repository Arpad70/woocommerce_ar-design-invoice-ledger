<?php

declare(strict_types=1);

namespace ArDesign\InvoiceLedger;

use ArDesign\Shared\Updates\GitHubPluginUpdater as BaseGitHubPluginUpdater;

if (!defined('ABSPATH')) {
    exit;
}

require_once WP_PLUGIN_DIR . '/ar-design-shared-support/includes/updates/GitHubPluginUpdater.php';

final class ArDesignInvoiceLedgerUpdater extends BaseGitHubPluginUpdater
{
    public function __construct(string $repositoryFullName, string $pluginBasename, string $currentVersion)
    {
        parent::__construct(
            $repositoryFullName,
            $pluginBasename,
            $currentVersion,
            array(
                'plugin_slug' => 'ar-design-invoice-ledger',
                'plugin_name' => 'AR Design Invoice Ledger for WooCommerce',
                'text_domain' => 'ar-design-invoice-ledger',
                'description' => 'Invoice ledger of issued invoices for WooCommerce PDF Invoices.',
                'author_label' => 'AR Design',
                'user_agent_slug' => 'ar-design-invoice-ledger',
                'cache_key_prefix' => 'ar_design_invoice_ledger_release_data_',
                'preferred_zip_names' => array('ar-design-invoice-ledger.zip'),
                'preferred_zip_prefixes' => array('ar-design-invoice-ledger-'),
                'allow_any_zip_fallback' => false,
            )
        );
    }
}
