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
    <title><?php echo $lang['assign_plan'] ?? 'Asignar Plan'; ?> - <?php echo $lang['app_name']; ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1><?php echo $lang['assign_plan'] ?? 'Asignar Plan a Empresa'; ?></h1>
        <form id="assign-plan-form">
            <div class="mb-3">
                <label for="company" class="form-label"><?php echo $lang['company']; ?></label>
                <select id="company" name="company" class="form-select">
                    <!-- <?php echo $lang['companies']; ?> options -->
                </select>
            </div>
            <div class="mb-3">
                <label for="plan" class="form-label"><?php echo $lang['plans'] ?? 'Plan'; ?></label>
                <select id="plan" name="plan" class="form-select">
                    <!-- <?php echo $lang['plans'] ?? 'Planes'; ?> options -->
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo $lang['assign_plan'] ?? 'Asignar Plan'; ?></button>
        </form>
    </div>
    <script src="js/panel_root.js"></script>
</body>
</html>
