<?php

declare(strict_types=1);

namespace ArDesign\InvoiceLedger;

if (!defined('ABSPATH')) {
    exit;
}

final class AdminPage
{
    private const OPTION_EXPORT_SOFTWARE = 'ar_design_invoice_ledger_export_software';
    private const PAGE_SLUG = 'ar-design-invoice-ledger';
    private const NONCE_ACTION = 'ar_design_invoice_ledger_filters';

    public static function register(): void
    {
        add_action('admin_menu', array(__CLASS__, 'registerMenu'), 60);
        add_action('admin_post_ar_design_invoice_ledger_export', array(__CLASS__, 'handleExport'));
    }

    public static function registerMenu(): void
    {
        $parentSlug = 'woocommerce';

        if (!empty($GLOBALS['admin_page_hooks']) && is_array($GLOBALS['admin_page_hooks']) && isset($GLOBALS['admin_page_hooks']['wpo_wcpdf_options_page'])) {
            $parentSlug = 'wpo_wcpdf_options_page';
        }

        add_submenu_page(
            $parentSlug,
            __('Invoice ledger', 'ar-design-invoice-ledger'),
            __('Invoice ledger', 'ar-design-invoice-ledger'),
            'manage_woocommerce',
            self::PAGE_SLUG,
            array(__CLASS__, 'renderPage')
        );
    }

    public static function renderPage(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to view this page.', 'ar-design-invoice-ledger'));
        }

        $filters = self::readFiltersFromRequest();

        if (isset($_GET['export_software'])) {
            check_admin_referer(self::NONCE_ACTION);
            $software = sanitize_key(wp_unslash($_GET['export_software']));
            if (isset(self::getSoftwareOptions()[$software])) {
                update_option(self::OPTION_EXPORT_SOFTWARE, $software, false);
            }
        }

        $selectedSoftware = self::getSelectedSoftware();
        $entries = self::getLedgerEntries($filters);
        $softwareOptions = self::getSoftwareOptions();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Invoice ledger', 'ar-design-invoice-ledger'); ?></h1>
            <p><?php echo esc_html__('Overview of issued WooCommerce PDF invoices with export to accounting software.', 'ar-design-invoice-ledger'); ?></p>

            <form method="get" action="">
                <input type="hidden" name="page" value="<?php echo esc_attr(self::PAGE_SLUG); ?>" />
                <?php wp_nonce_field(self::NONCE_ACTION); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="status"><?php echo esc_html__('Order status', 'ar-design-invoice-ledger'); ?></label></th>
                        <td>
                            <select id="status" name="status">
                                <option value=""><?php echo esc_html__('All', 'ar-design-invoice-ledger'); ?></option>
                                <?php foreach (wc_get_order_statuses() as $statusKey => $statusLabel) : ?>
                                    <?php $statusSlug = str_replace('wc-', '', (string) $statusKey); ?>
                                    <option value="<?php echo esc_attr($statusSlug); ?>" <?php selected($filters['status'], $statusSlug); ?>><?php echo esc_html($statusLabel); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="date_from"><?php echo esc_html__('Date from', 'ar-design-invoice-ledger'); ?></label></th>
                        <td><input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($filters['date_from']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="date_to"><?php echo esc_html__('Date to', 'ar-design-invoice-ledger'); ?></label></th>
                        <td><input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($filters['date_to']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="customer"><?php echo esc_html__('Customer', 'ar-design-invoice-ledger'); ?></label></th>
                        <td><input type="text" id="customer" name="customer" class="regular-text" value="<?php echo esc_attr($filters['customer']); ?>" placeholder="<?php echo esc_attr__('Name, company, or email', 'ar-design-invoice-ledger'); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="invoice_number"><?php echo esc_html__('Invoice number contains', 'ar-design-invoice-ledger'); ?></label></th>
                        <td><input type="text" id="invoice_number" name="invoice_number" value="<?php echo esc_attr($filters['invoice_number']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="export_software"><?php echo esc_html__('Default accounting software', 'ar-design-invoice-ledger'); ?></label></th>
                        <td>
                            <select id="export_software" name="export_software">
                                <?php foreach ($softwareOptions as $softwareKey => $softwareLabel) : ?>
                                    <option value="<?php echo esc_attr($softwareKey); ?>" <?php selected($selectedSoftware, $softwareKey); ?>><?php echo esc_html($softwareLabel); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Filter', 'ar-design-invoice-ledger'); ?></button>
                </p>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="ar_design_invoice_ledger_export" />
                <?php wp_nonce_field(self::NONCE_ACTION); ?>
                <input type="hidden" name="status" value="<?php echo esc_attr($filters['status']); ?>" />
                <input type="hidden" name="date_from" value="<?php echo esc_attr($filters['date_from']); ?>" />
                <input type="hidden" name="date_to" value="<?php echo esc_attr($filters['date_to']); ?>" />
                <input type="hidden" name="customer" value="<?php echo esc_attr($filters['customer']); ?>" />
                <input type="hidden" name="invoice_number" value="<?php echo esc_attr($filters['invoice_number']); ?>" />
                <input type="hidden" name="export_software" value="<?php echo esc_attr($selectedSoftware); ?>" />
                <p>
                    <button type="submit" class="button button-secondary"><?php echo esc_html__('Export by filter', 'ar-design-invoice-ledger'); ?></button>
                </p>
            </form>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Invoice', 'ar-design-invoice-ledger'); ?></th>
                        <th><?php echo esc_html__('Order', 'ar-design-invoice-ledger'); ?></th>
                        <th><?php echo esc_html__('Date', 'ar-design-invoice-ledger'); ?></th>
                        <th><?php echo esc_html__('Customer', 'ar-design-invoice-ledger'); ?></th>
                        <th><?php echo esc_html__('Total', 'ar-design-invoice-ledger'); ?></th>
                        <th><?php echo esc_html__('Currency', 'ar-design-invoice-ledger'); ?></th>
                        <th><?php echo esc_html__('Status', 'ar-design-invoice-ledger'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)) : ?>
                        <tr><td colspan="7"><?php echo esc_html__('No issued invoices found for the selected filter.', 'ar-design-invoice-ledger'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($entries as $entry) : ?>
                            <tr>
                                <td><?php echo esc_html($entry['invoice_number']); ?></td>
                                <td><a href="<?php echo esc_url($entry['order_edit_link']); ?>">#<?php echo esc_html((string) $entry['order_id']); ?></a></td>
                                <td><?php echo esc_html($entry['invoice_date']); ?></td>
                                <td><?php echo esc_html($entry['customer']); ?></td>
                                <td><?php echo esc_html($entry['total']); ?></td>
                                <td><?php echo esc_html($entry['currency']); ?></td>
                                <td><?php echo esc_html($entry['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * @return array<string, string>
     */
    private static function readFiltersFromRequest(): array
    {
        $status = isset($_REQUEST['status']) ? sanitize_key(wp_unslash($_REQUEST['status'])) : '';
        $dateFrom = isset($_REQUEST['date_from']) ? sanitize_text_field(wp_unslash($_REQUEST['date_from'])) : '';
        $dateTo = isset($_REQUEST['date_to']) ? sanitize_text_field(wp_unslash($_REQUEST['date_to'])) : '';
        $customer = isset($_REQUEST['customer']) ? sanitize_text_field(wp_unslash($_REQUEST['customer'])) : '';
        $invoiceNumber = isset($_REQUEST['invoice_number']) ? sanitize_text_field(wp_unslash($_REQUEST['invoice_number'])) : '';

        return array(
            'status' => $status,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'customer' => $customer,
            'invoice_number' => $invoiceNumber,
        );
    }

    private static function getSelectedSoftware(): string
    {
        $selected = sanitize_key((string) get_option(self::OPTION_EXPORT_SOFTWARE, 'generic_csv'));
        $options = self::getSoftwareOptions();

        return isset($options[$selected]) ? $selected : 'generic_csv';
    }

    /**
     * @return array<string, string>
     */
    private static function getSoftwareOptions(): array
    {
        return array(
            'generic_csv' => __('Generic CSV', 'ar-design-invoice-ledger'),
            'pohoda_csv' => __('POHODA (CSV)', 'ar-design-invoice-ledger'),
            'money_s3_csv' => __('Money S3 (CSV)', 'ar-design-invoice-ledger'),
            'oberon_csv' => __('OBERON (CSV)', 'ar-design-invoice-ledger'),
        );
    }

    /**
     * @param array<string, string> $filters
     * @return array<int, array<string, string|int>>
     */
    private static function getLedgerEntries(array $filters): array
    {
        if (!function_exists('wc_get_orders') || !function_exists('wcpdf_get_document')) {
            return array();
        }

        $orderQueryArgs = array(
            'limit' => 200,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects',
            'type' => 'shop_order',
        );

        if (!empty($filters['status'])) {
            $orderQueryArgs['status'] = array($filters['status']);
        }

        $dateQuery = self::buildWcDateQuery($filters['date_from'], $filters['date_to']);
        if ('' !== $dateQuery) {
            $orderQueryArgs['date_created'] = $dateQuery;
        }

        $orders = wc_get_orders($orderQueryArgs);
        if (empty($orders)) {
            return array();
        }

        $entries = array();
        $invoiceNumberFilter = mb_strtolower(trim((string) $filters['invoice_number']));
        $customerFilter = mb_strtolower(trim((string) $filters['customer']));

        $dateFormat = function_exists('wc_date_format') ? \wc_date_format() : (string) get_option('date_format');

        foreach ($orders as $order) {
            if (!is_object($order) || !is_callable(array($order, 'get_id'))) {
                continue;
            }

            $invoice = wcpdf_get_document('invoice', $order);
            if (!is_object($invoice) || !is_callable(array($invoice, 'exists')) || !$invoice->exists()) {
                continue;
            }

            $invoiceNumber = is_callable(array($invoice, 'get_number'))
                ? trim(wp_strip_all_tags((string) $invoice->get_number()))
                : '';
            if ('' === $invoiceNumber && is_callable(array($invoice, 'get_number_title'))) {
                $invoiceNumber = trim(wp_strip_all_tags((string) $invoice->get_number_title()));
            }

            $customerName = trim((string) $order->get_formatted_billing_full_name());
            $customerEmail = trim((string) $order->get_billing_email());
            $customerCompany = trim((string) $order->get_billing_company());
            $customerComposite = trim(implode(' ', array_filter(array($customerName, $customerCompany, $customerEmail))));

            if ('' !== $invoiceNumberFilter && false === mb_stripos(mb_strtolower($invoiceNumber), $invoiceNumberFilter)) {
                continue;
            }

            if ('' !== $customerFilter && false === mb_stripos(mb_strtolower($customerComposite), $customerFilter)) {
                continue;
            }

            $invoiceDate = '';
            if (is_callable(array($invoice, 'get_date'))) {
                $date = $invoice->get_date();
                if (is_object($date) && is_callable(array($date, 'date_i18n'))) {
                    $invoiceDate = (string) $date->date_i18n($dateFormat);
                }
            }
            if ('' === $invoiceDate && is_callable(array($order, 'get_date_created')) && $order->get_date_created()) {
                $invoiceDate = (string) $order->get_date_created()->date_i18n($dateFormat);
            }

            $statusName = function_exists('wc_get_order_status_name')
                ? \wc_get_order_status_name((string) $order->get_status())
                : (string) $order->get_status();

            $entries[] = array(
                'order_id' => (int) $order->get_id(),
                'order_edit_link' => (string) get_edit_post_link((int) $order->get_id(), ''),
                'invoice_number' => '' !== $invoiceNumber ? $invoiceNumber : '—',
                'invoice_date' => '' !== $invoiceDate ? $invoiceDate : '—',
                'customer' => '' !== $customerComposite ? $customerComposite : '—',
                'total' => wp_strip_all_tags((string) $order->get_formatted_order_total()),
                'currency' => (string) $order->get_currency(),
                'status' => $statusName,
            );
        }

        return $entries;
    }

    private static function buildWcDateQuery(string $dateFrom, string $dateTo): string
    {
        $dateFrom = trim($dateFrom);
        $dateTo = trim($dateTo);

        if ('' === $dateFrom && '' === $dateTo) {
            return '';
        }

        if ('' !== $dateFrom && '' !== $dateTo) {
            return $dateFrom . '...' . $dateTo;
        }

        if ('' !== $dateFrom) {
            return '>=' . $dateFrom;
        }

        return '<=' . $dateTo;
    }

    public static function handleExport(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to export.', 'ar-design-invoice-ledger'));
        }

        check_admin_referer(self::NONCE_ACTION);

        $filters = self::readFiltersFromRequest();
        $software = isset($_POST['export_software'])
            ? sanitize_key(wp_unslash($_POST['export_software']))
            : self::getSelectedSoftware();

        if (!isset(self::getSoftwareOptions()[$software])) {
            $software = 'generic_csv';
        }

        $entries = self::getLedgerEntries($filters);
        $filename = 'invoice-ledger-' . $software . '-' . gmdate('Ymd-His') . '.csv';

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        if (false === $output) {
            exit;
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv(
            $output,
            array(
                __('Invoice number', 'ar-design-invoice-ledger'),
                __('Order ID', 'ar-design-invoice-ledger'),
                __('Invoice date', 'ar-design-invoice-ledger'),
                __('Customer', 'ar-design-invoice-ledger'),
                __('Total', 'ar-design-invoice-ledger'),
                __('Currency', 'ar-design-invoice-ledger'),
                __('Order status', 'ar-design-invoice-ledger'),
            ),
            ';'
        );

        foreach ($entries as $entry) {
            fputcsv(
                $output,
                array(
                    $entry['invoice_number'],
                    $entry['order_id'],
                    $entry['invoice_date'],
                    $entry['customer'],
                    $entry['total'],
                    $entry['currency'],
                    $entry['status'],
                ),
                ';'
            );
        }

        fclose($output);
        exit;
    }
}
