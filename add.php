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

  return move_uploaded_file($tmpName, $destinationPath);
}

$pageTitle = 'Add Invoice - Invoice Manager';
$currentStatus = 'all';

$formErrors = [];
$formOld = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client = trim((string) ($_POST['client'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $amountRaw = str_replace([',', ' '], '', (string) ($_POST['amount'] ?? ''));
    $statusPost = strtolower(trim((string) ($_POST['status'] ?? 'draft')));
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
        $invoiceNumber = createInvoice(
            $client,
            $email,
            $validationResult['amount'],
            $validationResult['status']
        );

        $message = 'Invoice ' . $invoiceNumber . ' added successfully.';

        if ($pdfValidationResult['hasFile'] === true && is_array($uploadedPdf)) {
            if (!storeUploadedPdf($uploadedPdf, $invoiceNumber)) {
                $message = 'Invoice ' . $invoiceNumber . ' added, but the PDF could not be saved.';
            }
        }

        header(
            'Location: index.php?status=' . rawurlencode($validationResult['status'])
            . '&message=' . rawurlencode($message)
        );
        exit;
    }
}

$clientValue = htmlspecialchars((string)($formOld['client'] ?? ''), ENT_QUOTES);
$emailValue = htmlspecialchars((string)($formOld['email'] ?? ''), ENT_QUOTES);
$amountValue = htmlspecialchars((string)($formOld['amount'] ?? ''), ENT_QUOTES);
$statusValue = (string)($formOld['status'] ?? 'draft');
$draftSelected = $statusValue === 'draft' ? 'selected' : '';
$pendingSelected = $statusValue === 'pending' ? 'selected' : '';
$paidSelected = $statusValue === 'paid' ? 'selected' : '';

$content = '<h2 class="mb-4">Add Invoice</h2>';

if (!empty($formErrors)) {
  $content .= '<div class="alert alert-danger" role="alert"><ul class="mb-0">';
  foreach ($formErrors as $error) {
    $content .= '<li>' . htmlspecialchars((string)$error) . '</li>';
  }
  $content .= '</ul></div>';
}

$content .= <<<HTML
<div class="card form-card">
  <div class="card-body">
    <form method="post" action="add.php" enctype="multipart/form-data">
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
        <button type="submit" class="btn btn-primary">Add Invoice</button>
        <a class="btn btn-outline-secondary" href="index.php">Back to Invoices</a>
      </div>
    </form>
  </div>
</div>
HTML;

require_once 'template.php';
?>
