<?php
require_once '../config.php';
$lang = require_once '../lang/es.php';
session_start();
if (!isset($_SESSION['current_role']) || $_SESSION['current_role'] !== 'root') {
    header('Location: ../auth/logout.php');
    exit;
}
// Companies listing, actions, and filters
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang['companies_list'] ?? 'Lista de Empresas'; ?> - <?php echo $lang['app_name']; ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1><?php echo $lang['companies_list'] ?? 'Lista de Empresas'; ?></h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th><?php echo $lang['company_name']; ?></th>
                    <th><?php echo $lang['plans'] ?? 'Plan'; ?></th>
                    <th><?php echo $lang['date']; ?></th>
                    <th><?php echo $lang['status']; ?></th>
                    <th><?php echo $lang['actions']; ?></th>
                </tr>
            </thead>
            <tbody id="companies-list">
                <!-- <?php echo $lang['companies']; ?> will be loaded here -->
            </tbody>
        </table>
    </div>
    <script src="js/panel_root.js"></script>
</body>
</html>
