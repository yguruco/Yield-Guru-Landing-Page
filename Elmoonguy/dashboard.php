<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Database connection
$host   = 'localhost';
$dbUser = 'YieldGuru';
$dbPass = 'Kcj034ralio#';
$dbName = 'YieldGuru';

try {
    $dsn = "mysql:host=$host;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Fetch investments
function getInvestments() {
    global $pdo;
    return $pdo->query("SELECT * FROM tbl_investments ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch contacts
function getContacts() {
    global $pdo;
    return $pdo->query("SELECT * FROM tbl_contact ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YieldGuru Admin - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4F1964;
            --primary-dark: #3a1249;
        }

        body {
            font-family: 'Jost', sans-serif;
            background-color: #f8f9fa;
            padding-top: 4rem;
        }

        .navbar {
            background-color: var(--primary);
            padding: 0px;
        }

        .navbar-brand img {
            height: 70px;
        }

        .nav-tabs .nav-link {
            color: var(--primary);
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-dark);
            font-weight: 500;
        }

        .dashboard-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
        }

        .table thead th {
            background-color: var(--primary);
            color: white;
        }

        .btn-logout {
            color: white;
            text-decoration: none;
        }

        .btn-logout:hover {
            color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="https://www.yieldguru.network/logo-white.png" alt="YieldGuru Logo">
            </a>
            <a href="?logout" class="btn-logout">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container dashboard-container">
        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="investments-tab" data-bs-toggle="tab" 
                        data-bs-target="#investments" type="button" role="tab">
                    Registered Accounts
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="contacts-tab" data-bs-toggle="tab" 
                        data-bs-target="#contacts" type="button" role="tab">
                    Contact Inquiries
                </button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <!-- Investments Tab -->
            <div class="tab-pane fade show active" id="investments" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-striped" id="investmentsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Country</th>
                                <th>Telegram</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (getInvestments() as $investment): ?>
                            <tr>
                                <td><?= htmlspecialchars($investment['id']) ?></td>
                                <td><?= htmlspecialchars($investment['full_name']) ?></td>
                                <td><?= htmlspecialchars($investment['email']) ?></td>
                                <td><?= htmlspecialchars($investment['phone']) ?></td>
                                <td><?= htmlspecialchars($investment['country']) ?></td>
                                <td><?= htmlspecialchars($investment['telegram']) ?></td>
                                <td><?= htmlspecialchars($investment['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Contacts Tab -->
            <div class="tab-pane fade" id="contacts" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-striped" id="contactsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Message</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (getContacts() as $contact): ?>
                            <tr>
                                <td><?= htmlspecialchars($contact['id']) ?></td>
                                <td><?= htmlspecialchars($contact['contact_name']) ?></td>
                                <td><?= htmlspecialchars($contact['contact_email']) ?></td>
                                <td><?= htmlspecialchars($contact['message']) ?></td>
                                <td><?= htmlspecialchars($contact['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#investmentsTable').DataTable({
                order: [[0, 'desc']]
            });
            $('#contactsTable').DataTable({
                order: [[0, 'desc']]
            });
        });
    </script>
</body>
</html>