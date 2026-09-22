<?php

declare(strict_types=1);

require_once 'data.php';

function validatePdfUpload(array $file): array
{
  if (!isset($file['error'])) {
    return ['hasFile' => false, 'error' => 'Invalid file upload request.'];
  }

  $errorCode = (int) $file['error'];
  if ($errorCode === UPLOAD_ERR_NO_FILE) {
    return ['hasFile' => false, 'error' => ''];
  }

  if ($errorCode !== UPLOAD_ERR_OK) {
    return ['hasFile' => true, 'error' => 'File upload failed. Please try again.'];
  }

  $tmpName = (string) ($file['tmp_name'] ?? '');
  if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    return ['hasFile' => true, 'error' => 'Invalid uploaded file.'];
  }

  $originalName = (string) ($file['name'] ?? '');
  $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
  if ($extension !== 'pdf') {
    return ['hasFile' => true, 'error' => 'Only PDF files are allowed.'];
  }

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  if ($finfo === false) {
    return ['hasFile' => true, 'error' => 'Unable to validate uploaded file type.'];
  }

  $mimeType = (string) finfo_file($finfo, $tmpName);
  finfo_close($finfo);

  if ($mimeType !== 'application/pdf') {
    return ['hasFile' => true, 'error' => 'Only PDF files are allowed.'];
  }

  return ['hasFile' => true, 'error' => ''];
}

function storeUploadedPdf(array $file, string $invoiceNumber): bool
{
  $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'documents';

  if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
    return false;
  }

  $tmpName = (string) ($file['tmp_name'] ?? '');
  $destinationPath = $uploadDirectory . DIRECTORY_SEPARATOR . $invoiceNumber . '.pdf';

  if (is_file($destinationPath) && !unlink($destinationPath)) {
    return false;
  }

  return move_uploaded_file($tmpName, $destinationPath);
}

$pageTitle = 'Update Invoice - Invoice Manager';
$currentStatus = 'all';

$invoiceNumber = trim((string)($_GET['number'] ?? ''));
$invoice = $invoiceNumber !== '' ? getInvoiceByNumber($invoiceNumber) : null;

$postMessage = '';
$alertType = 'info';
$formErrors = [];
$formOld = [];

if ($invoice === null) {
  $postMessage = 'Invoice not found.';
  $alertType = 'danger';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client = trim((string)($_POST['client'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $amountRaw = str_replace([',', ' '], '', (string)($_POST['amount'] ?? ''));
    $statusPost = strtolower(trim((string)($_POST['status'] ?? 'draft')));
  $uploadedPdf = $_FILES['invoice_pdf'] ?? null;

    $validationResult = validateInvoiceForm($client, $email, $amountRaw, $statusPost);
    $formErrors = $validationResult['errors'];

  $pdfValidationResult = ['hasFile' => false, 'error' => ''];
  if (is_array($uploadedPdf)) {
    $pdfValidationResult = validatePdfUpload($uploadedPdf);
    if ($pdfValidationResult['error'] !== '') {
      $formErrors[] = $pdfValidationResult['error'];
    }
  }

    $formOld = [
        'client' => $client,
        'email' => $email,
        'amount' => $amountRaw,
        'status' => $validationResult['status'],
    ];

    if (empty($formErrors)) {
        if (updateInvoice($invoiceNumber, $client, $email, $validationResult['amount'], $validationResult['status'])) {
        $pdfSaveFailed = false;
        if ($pdfValidationResult['hasFile'] === true && is_array($uploadedPdf)) {
          $pdfSaveFailed = !storeUploadedPdf($uploadedPdf, $invoiceNumber);
        }

            $invoice = getInvoiceByNumber($invoiceNumber);
        if ($pdfSaveFailed) {
          $postMessage = 'Invoice ' . htmlspecialchars($invoiceNumber, ENT_QUOTES) . ' updated, but the PDF could not be saved.';
          $alertType = 'warning';
        } else {
          $postMessage = 'Invoice ' . htmlspecialchars($invoiceNumber, ENT_QUOTES) . ' updated successfully.';
          $alertType = 'success';
        }

            $formOld = [];
        } else {
            $postMessage = 'Invoice not found.';
            $alertType = 'danger';
        }
    }
}

$content = '<h2 class="mb-4">Update Invoice</h2>';

if ($postMessage !== '') {
    $content .= '<div class="alert alert-' . $alertType . '" role="alert">' . $postMessage . '</div>';
}

if (!empty($formErrors)) {
  $content .= '<div class="alert alert-danger" role="alert"><ul class="mb-0">';
  foreach ($formErrors as $error) {
    $content .= '<li>' . htmlspecialchars((string)$error) . '</li>';
  }
  $content .= '</ul></div>';
}

if ($invoice !== null) {
  if (!empty($formOld)) {
    $invoice['client'] = (string)($formOld['client'] ?? $invoice['client']);
    $invoice['email'] = (string)($formOld['email'] ?? $invoice['email']);
    $invoice['amount'] = (string)($formOld['amount'] ?? $invoice['amount']);
    $invoice['status'] = (string)($formOld['status'] ?? $invoice['status']);
  }

    $clientValue = htmlspecialchars((string)$invoice['client'], ENT_QUOTES);
    $emailValue = htmlspecialchars((string)$invoice['email'], ENT_QUOTES);
    $amountValue = htmlspecialchars((string)$invoice['amount'], ENT_QUOTES);
    $statusValue = htmlspecialchars((string)$invoice['status'], ENT_QUOTES);
    $invoiceNumberParam = rawurlencode($invoiceNumber);
    $draftSelected = $statusValue === 'draft' ? 'selected' : '';
    $pendingSelected = $statusValue === 'pending' ? 'selected' : '';
    $paidSelected = $statusValue === 'paid' ? 'selected' : '';

    $content .= <<<HTML
    <div class="card form-card">
  <div class="card-body">
    <form method="post" action="update.php?number={$invoiceNumberParam}" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="client" class="form-label">Client Name</label>
        <input type="text" class="form-control" id="client" name="client" value="{$clientValue}" maxlength="255" pattern="[A-Za-z ]+" title="Letters and spaces only" required>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Client Email</label>
        <input type="email" class="form-control" id="email" name="email" value="{$emailValue}" required>
      </div>

      <div class="mb-3">
        <label for="amount" class="form-label">Invoice Amount</label>
        <input type="number" step="1" min="1" class="form-control" id="amount" name="amount" value="{$amountValue}" required>
      </div>

      <div class="mb-3">
        <label for="status" class="form-label">Invoice Status</label>
        <select class="form-select" id="status" name="status" required>
          <option value="draft" {$draftSelected}>Draft</option>
          <option value="pending" {$pendingSelected}>Pending</option>
          <option value="paid" {$paidSelected}>Paid</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="invoice_pdf" class="form-label">Invoice PDF (Optional)</label>
        <input type="file" class="form-control" id="invoice_pdf" name="invoice_pdf" accept="application/pdf,.pdf">
      </div>

      <div class="form-actions mt-4">
        <button type="submit" class="btn btn-primary">Update Invoice</button>
        <a class="btn btn-outline-secondary" href="index.php">Back to Invoices</a>
      </div>
    </form>
  </div>
</div>
HTML;
}

require_once 'template.php';
