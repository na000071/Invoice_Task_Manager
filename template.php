<?php
$currentStatus = $currentStatus ?? 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Invoice Manager'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #1a1f2b;
            --ink-muted: #5b6477;
            --sunset: #f29f63;
            --ocean: #3a7ca5;
            --fog: #f6f2ec;
            --card: #ffffff;
            --ring: rgba(58, 124, 165, 0.28);
        }
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
            font-family: "Plus Jakarta Sans", "Segoe UI", sans-serif;
            color: var(--ink);
            background: radial-gradient(circle at top left, rgba(242, 159, 99, 0.16), transparent 45%),
                radial-gradient(circle at 20% 20%, rgba(58, 124, 165, 0.15), transparent 50%),
                var(--fog);
        }
        .content-wrapper {
            flex: 1;
        }
        .navbar {
            backdrop-filter: blur(12px);
            box-shadow: 0 8px 24px rgba(20, 24, 34, 0.2);
        }
        .navbar-brand {
            font-family: "Fraunces", "Times New Roman", serif;
            letter-spacing: 0.5px;
        }
        .nav-link {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.75);
        }
        .nav-link.active,
        .nav-link:hover {
            color: #fff;
        }
        .container {
            max-width: 1100px;
        }
        .page-container {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }
        .page-shell {
            background: rgba(255, 255, 255, 0.58);
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 18px;
            padding: 1.4rem;
            box-shadow: 0 14px 40px rgba(26, 31, 43, 0.08);
        }
        h2 {
            font-family: "Fraunces", "Times New Roman", serif;
            letter-spacing: 0.2px;
            margin-bottom: 1.1rem;
        }
        .card,
        .table {
            background: var(--card);
            border: none;
            box-shadow: 0 18px 45px rgba(26, 31, 43, 0.08);
        }
        .card {
            border-radius: 16px;
        }
        .table thead th {
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .table tbody tr {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .table tbody tr:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(26, 31, 43, 0.08);
        }
        .btn-primary {
            background: linear-gradient(120deg, var(--ocean), #1f5f7e);
            border: none;
            box-shadow: 0 12px 18px rgba(31, 95, 126, 0.2);
        }
        .btn-primary:hover {
            background: linear-gradient(120deg, #1f5f7e, var(--ocean));
        }
        .btn-outline-primary {
            border-color: var(--ocean);
            color: var(--ocean);
        }
        .btn-outline-primary:hover {
            background: var(--ocean);
            color: #fff;
        }
        .btn-outline-danger {
            border-color: #c2483a;
            color: #c2483a;
        }
        .btn-outline-danger:hover {
            background: #c2483a;
            color: #fff;
        }
        .form-control:focus,
        .form-select:focus,
        .btn:focus {
            box-shadow: 0 0 0 0.2rem var(--ring);
        }
        .badge {
            letter-spacing: 0.02em;
        }
        .invoice-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .invoice-actions form {
            margin: 0;
        }
        .form-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .form-card {
            max-width: 720px;
            margin: 0 auto;
        }
        footer {
            margin-top: auto;
            background: #141822;
        }
        @media (max-width: 768px) {
            .page-container {
                padding-top: 1rem;
                padding-bottom: 1.25rem;
            }
            .page-shell {
                border-radius: 14px;
                padding: 1rem;
            }
            .table-responsive {
                border-radius: 16px;
                overflow: hidden;
            }
            .invoice-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .invoice-actions .btn,
            .invoice-actions form,
            .invoice-actions form .btn,
            .form-actions .btn {
                width: 100%;
            }
            .table > :not(caption) > * > * {
                padding: 0.65rem;
            }
        }
        @media (max-width: 610px) {
            .table-responsive {
                overflow: visible;
                border-radius: 0;
            }
            .invoice-table,
            .invoice-table tbody,
            .invoice-table tr,
            .invoice-table td {
                display: block;
                width: 100%;
            }
            .invoice-table thead {
                display: none;
            }
            .invoice-table tr {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 10px 24px rgba(26, 31, 43, 0.08);
                margin-bottom: 0.9rem;
                padding: 0.35rem 0;
            }
            .invoice-table td {
                border: 0;
                border-bottom: 1px solid rgba(26, 31, 43, 0.08);
                padding: 0.7rem 0.9rem;
                text-align: left;
            }
            .invoice-table td:last-child {
                border-bottom: 0;
            }
            .invoice-table td::before {
                content: attr(data-label);
                display: block;
                font-size: 0.74rem;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: var(--ink-muted);
                margin-bottom: 0.25rem;
                font-weight: 700;
            }
            .invoice-actions {
                margin-top: 0.15rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Invoice Manager</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentStatus === 'all' ? 'active' : ''; ?>" href="index.php?status=all">All Invoices</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentStatus === 'draft' ? 'active' : ''; ?>" href="index.php?status=draft">Draft</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentStatus === 'pending' ? 'active' : ''; ?>" href="index.php?status=pending">Pending</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentStatus === 'paid' ? 'active' : ''; ?>" href="index.php?status=paid">Paid</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="content-wrapper">
        <div class="container page-container">
            <main class="page-shell">
                <?php echo $content ?? ''; ?>
            </main>
        </div>
    </div>
    <footer class="bg-dark text-white text-center py-4">
        <p>&copy; 2026 Invoice Manager. All rights reserved.</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
