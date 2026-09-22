<?php

declare(strict_types=1);

const INVOICE_DB_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'invoice_manager.sqlite';
const INVOICE_ALLOWED_STATUSES = ['draft', 'pending', 'paid'];

function renderDatabaseErrorAndExit(): never
{
    http_response_code(500);
    echo '<!DOCTYPE html>';
    echo '<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Database Error - Invoice Manager</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:24px;}';
    echo '.card{max-width:640px;margin:40px auto;background:#fff;border-radius:12px;padding:24px;box-shadow:0 8px 24px rgba(0,0,0,.12);}';
    echo 'h1{margin-top:0;font-size:1.5rem;}p{line-height:1.6;color:#333;}a{display:inline-block;margin-top:12px;color:#0d6efd;text-decoration:none;font-weight:600;}</style>';
    echo '</head><body><div class="card">';
    echo '<h1>We could not complete your request</h1>';
    echo '<p>A temporary database issue occurred. Please try again in a moment.</p>';
    echo '<a href="index.php">Return Home</a>';
    echo '</div></body></html>';
    exit;
}

function getDatabaseConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = new PDO('sqlite:' . INVOICE_DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS statuses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                status VARCHAR(255) NOT NULL
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS invoices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                number VARCHAR(255) NOT NULL,
                client VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                amount INTEGER NOT NULL,
                status_id INTEGER NOT NULL,
                FOREIGN KEY (status_id) REFERENCES statuses(id)
            )'
        );

        $statusInsertStatement = $pdo->prepare('INSERT INTO statuses (status) SELECT :status WHERE NOT EXISTS (SELECT 1 FROM statuses WHERE status = :status)');
        foreach (INVOICE_ALLOWED_STATUSES as $allowedStatus) {
            $statusInsertStatement->execute([':status' => $allowedStatus]);
        }

        return $pdo;
    } catch (PDOException $exception) {
        renderDatabaseErrorAndExit();
    }
}

function getAllowedStatuses(): array
{
    return ['all', 'draft', 'pending', 'paid'];
}

function normalizeStatus(string $status): string
{
    $normalizedStatus = strtolower(trim($status));

    return in_array($normalizedStatus, getAllowedStatuses(), true)
        ? $normalizedStatus
        : 'all';
}

function getInvoices(string $status = 'all'): array
{
    try {
        $pdo = getDatabaseConnection();
        $normalizedStatus = normalizeStatus($status);

        if ($normalizedStatus === 'all') {
            $statement = $pdo->query(
                'SELECT i.number, i.amount, s.status, i.client, i.email
                 FROM invoices i
                 INNER JOIN statuses s ON s.id = i.status_id
                 ORDER BY i.number ASC'
            );

            return $statement->fetchAll();
        }

        $statement = $pdo->prepare(
            'SELECT i.number, i.amount, s.status, i.client, i.email
             FROM invoices i
             INNER JOIN statuses s ON s.id = i.status_id
             WHERE s.status = :status
             ORDER BY i.number ASC'
        );
        $statement->execute([':status' => $normalizedStatus]);

        return $statement->fetchAll();
    } catch (PDOException $exception) {
        renderDatabaseErrorAndExit();
    }
}

function getInvoiceByNumber(string $invoiceNumber): ?array
{
    try {
        $pdo = getDatabaseConnection();
        $statement = $pdo->prepare(
            'SELECT i.number, i.amount, s.status, i.client, i.email
             FROM invoices i
             INNER JOIN statuses s ON s.id = i.status_id
             WHERE i.number = :number
             LIMIT 1'
        );
        $statement->execute([':number' => $invoiceNumber]);
        $invoice = $statement->fetch();

        return $invoice !== false ? $invoice : null;
    } catch (PDOException $exception) {
        renderDatabaseErrorAndExit();
    }
}

function deleteInvoiceByNumber(string $invoiceNumber): bool
{
    try {
        $pdo = getDatabaseConnection();
        $statement = $pdo->prepare('DELETE FROM invoices WHERE number = :number');
        $statement->execute([':number' => $invoiceNumber]);

        if ($statement->rowCount() <= 0) {
            return false;
        }

        if (!deleteInvoiceDocument($invoiceNumber)) {
            return false;
        }

        return true;
    } catch (PDOException $exception) {
        renderDatabaseErrorAndExit();
    }
}

function generateInvoiceNumber(): string
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $invoiceNumber = '';

    for ($index = 0; $index < 5; $index++) {
        $invoiceNumber .= $characters[random_int(0, 25)];
    }

    return $invoiceNumber;
}

function createInvoice(string $client, string $email, int $amount, string $status): string
{
    try {
        $pdo = getDatabaseConnection();

        $statusId = getStatusIdByStatusName($status);
        if ($statusId === null) {
            $statusId = getStatusIdByStatusName('draft');
        }

        $statement = $pdo->prepare(
            'INSERT INTO invoices (number, amount, client, email, status_id)
             VALUES (:number, :amount, :client, :email, :status_id)'
        );

        $newInvoiceNumber = '';
        $attempt = 0;

        do {
            $newInvoiceNumber = generateInvoiceNumber();
            $attempt++;
        } while (getInvoiceByNumber($newInvoiceNumber) !== null && $attempt < 25);

        $statement->execute([
            ':number' => $newInvoiceNumber,
            ':amount' => $amount,
            ':client' => $client,
            ':email' => $email,
            ':status_id' => $statusId,
        ]);

        return $newInvoiceNumber;
    } catch (PDOException $exception) {
        renderDatabaseErrorAndExit();
    }
}

function updateInvoice(string $invoiceNumber, string $client, string $email, int $amount, string $status): bool
{
    try {
        $pdo = getDatabaseConnection();

        $statusId = getStatusIdByStatusName($status);
        if ($statusId === null) {
            $statusId = getStatusIdByStatusName('draft');
        }

        $statement = $pdo->prepare(
            'UPDATE invoices
             SET amount = :amount,
                 client = :client,
                 email = :email,
                 status_id = :status_id
             WHERE number = :number'
        );

        $statement->execute([
            ':number' => $invoiceNumber,
            ':amount' => $amount,
            ':client' => $client,
            ':email' => $email,
            ':status_id' => $statusId,
        ]);

        return $statement->rowCount() > 0;
    } catch (PDOException $exception) {
        renderDatabaseErrorAndExit();
    }
}

function getStatusIdByStatusName(string $status): ?int
{
    try {
        $pdo = getDatabaseConnection();
        $statement = $pdo->prepare('SELECT id FROM statuses WHERE status = :status LIMIT 1');
        $statement->execute([':status' => $status]);
        $statusId = $statement->fetchColumn();

        return $statusId !== false ? (int) $statusId : null;
    } catch (PDOException $exception) {
        renderDatabaseErrorAndExit();
    }
}

function validateInvoiceForm(string $client, string $email, string $amountRaw, string $status): array
{
    $errors = [];

    if ($client === '') {
        $errors[] = 'Client name is required.';
    } elseif (strlen($client) > 255) {
        $errors[] = 'Client name must be 255 characters or fewer.';
    } elseif (!preg_match('/^[A-Za-z ]+$/', $client)) {
        $errors[] = 'Client name may contain only letters and spaces.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid client email is required.';
    }

    $amountValue = filter_var($amountRaw, FILTER_VALIDATE_INT);
    if ($amountValue === false) {
        $errors[] = 'Invoice amount must be an integer.';
    } elseif ((int) $amountValue <= 0) {
        $errors[] = 'Invoice amount must be greater than zero.';
    }

    if (!in_array($status, INVOICE_ALLOWED_STATUSES, true)) {
        $errors[] = 'Invoice status is invalid.';
    }

    return [
        'errors' => $errors,
        'amount' => $amountValue !== false ? (int) $amountValue : 0,
        'status' => in_array($status, INVOICE_ALLOWED_STATUSES, true) ? $status : 'draft',
    ];
}

function getInvoicePdfPath(string $invoiceNumber): ?string
{
    $documentPath = __DIR__ . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . $invoiceNumber . '.pdf';

    if (!is_file($documentPath)) {
        return null;
    }

    return 'documents/' . rawurlencode($invoiceNumber) . '.pdf';
}

function deleteInvoiceDocument(string $invoiceNumber): bool
{
    $documentPath = __DIR__ . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . $invoiceNumber . '.pdf';

    if (!is_file($documentPath)) {
        return true;
    }

    return unlink($documentPath);
}
