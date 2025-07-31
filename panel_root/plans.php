<?php
require_once '../config.php';
$lang = require_once '../lang/es.php';
session_start();
if (!isset($_SESSION['current_role']) || $_SESSION['current_role'] !== 'root') {
    header('Location: ../auth/logout.php');
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang['plans'] ?? 'Planes'; ?> - <?php echo $lang['app_name']; ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1><?php echo $lang['plans'] ?? 'Planes'; ?></h1>
        <button class="btn btn-success mb-3" onclick="openPlanModal()"><?php echo $lang['create'] . ' ' . ($lang['plans'] ?? 'Plan'); ?></button>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th><?php echo $lang['name']; ?></th>
                    <th><?php echo $lang['users_max'] ?? 'Usuarios Máx.'; ?></th>
                    <th><?php echo $lang['units_max'] ?? 'Unidades Máx.'; ?></th>
                    <th><?php echo $lang['modules'] ?? 'Módulos'; ?></th>
                    <th><?php echo $lang['status']; ?></th>
                    <th><?php echo $lang['actions']; ?></th>
                </tr>
            </thead>
            <tbody id="plans-list">
                <!-- <?php echo $lang['plans'] ?? 'Planes'; ?> will be loaded here -->
            </tbody>
        </table>
    </div>
    <script src="js/panel_root.js"></script>
</body>
</html>
