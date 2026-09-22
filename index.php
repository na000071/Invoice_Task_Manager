<?php

declare(strict_types=1);

require_once 'data.php';

$postMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $deleteNumber = trim((string)($_POST['number'] ?? ''));

    if ($deleteNumber !== '' && deleteInvoiceByNumber($deleteNumber)) {
        $postMessage = 'Invoice ' . htmlspecialchars($deleteNumber, ENT_QUOTES) . ' deleted successfully.';
    } else {
        $postMessage = 'Invoice not found.';
    }
}

if (($_GET['message'] ?? '') !== '') {
    $postMessage = htmlspecialchars((string) $_GET['message'], ENT_QUOTES);
}

$status = strtolower(trim((string)($_GET['status'] ?? 'all')));
if (!in_array($status, getAllowedStatuses(), true)) {
    $status = 'all';
}

$currentStatus = $status;

$pageTitleMap = [
    'all' => 'All Invoices - Invoice Manager',
    'draft' => 'Draft Invoices - Invoice Manager',
    'pending' => 'Pending Invoices - Invoice Manager',
    'paid' => 'Paid Invoices - Invoice Manager',
];

$headingMap = [
    'all' => 'All Invoices',
    'draft' => 'Draft Invoices',
    'pending' => 'Pending Invoices',
    'paid' => 'Paid Invoices',
];

$pageTitle = $pageTitleMap[$status];
$content = '<div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center gap-2 mb-3">';
$content .= '<h2 class="mb-0">' . $headingMap[$status] . '</h2>';
$content .= '<a class="btn btn-primary" href="add.php">Add Invoice</a>';
$content .= '</div>';

// show post message if present
if ($postMessage !== '') {
    $alertType = stripos($postMessage, 'successfully') !== false ? 'success' : 'danger';
    $content .= '<div class="alert alert-' . $alertType . '" role="alert">' . $postMessage . '</div>';
}

$filtered = getInvoices($status);

if (count($filtered) > 0) {
    $content .= '<div class="table-responsive">';
    $content .= '<table class="table table-striped table-hover align-middle invoice-table">';
    $content .= '<thead class="table-dark"><tr>';
    $content .= '<th>Invoice Number</th>';
    $content .= '<th>Client</th>';
    $content .= '<th>Amount</th>';
    $content .= '<th>Status</th>';
    $content .= '<th>Actions</th>';
    $content .= '</tr></thead>';
    $content .= '<tbody>';

    foreach ($filtered as $invoice) {
        $invoiceNumber = htmlspecialchars((string) $invoice['number'], ENT_QUOTES);
        $invoiceEmail = htmlspecialchars((string) $invoice['email'], ENT_QUOTES);
        $invoiceClient = htmlspecialchars((string) $invoice['client'], ENT_QUOTES);
        $invoiceStatus = htmlspecialchars((string) $invoice['status'], ENT_QUOTES);
        $invoicePdfPath = getInvoicePdfPath((string) $invoice['number']);

        $badgeClass = match ($invoice['status']) {
            'paid' => 'badge bg-success',
            'pending' => 'badge bg-warning',
            'draft' => 'badge bg-secondary',
            default => 'badge bg-dark'
        };

        $content .= '<tr>';
        $content .= '<td data-label="Invoice Number"><strong>' . $invoiceNumber . '</strong></td>';
        $content .= '<td data-label="Client"><a href="mailto:' . $invoiceEmail . '">' . $invoiceClient . '</a></td>';
        $content .= '<td data-label="Amount">$' . number_format($invoice['amount'], 2) . '</td>';
        $content .= '<td data-label="Status"><span class="' . $badgeClass . '">' . ucfirst($invoiceStatus) . '</span></td>';
        $content .= '<td data-label="Actions"><div class="invoice-actions">';

        if ($invoicePdfPath !== null) {
            $content .= '<a class="btn btn-sm btn-outline-primary" href="' . htmlspecialchars($invoicePdfPath, ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer">View</a>';
        }

        $content .= '<a class="btn btn-sm btn-outline-primary" href="update.php?number=' . rawurlencode((string) $invoice['number']) . '">Edit</a>'
            . '<form method="post" action="index.php" class="m-0" onsubmit="return confirm(\'Are you sure you want to delete invoice ' . $invoiceNumber . '?\');">'
            . '<input type="hidden" name="action" value="delete">'
            . '<input type="hidden" name="number" value="' . $invoiceNumber . '">' 
            . '<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>'
            . '</form>'
            . '</div></td>';
        $content .= '</tr>';
    }

    $content .= '</tbody></table></div>';
} else {
    if ($status === 'all') {
        $content .= '<div class="alert alert-info" role="alert">No invoices found.</div>';
    } else {
        $content .= '<div class="alert alert-info" role="alert">No ' . strtolower($headingMap[$status]) . ' found.</div>';
    }
}

require_once 'template.php';