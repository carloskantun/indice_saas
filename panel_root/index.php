<?php
require_once '../config.php';
$lang = require_once '../lang/es.php';
session_start();
if (!isset($_SESSION['current_role']) || $_SESSION['current_role'] !== 'root') {
    header('Location: ../auth/logout.php');
    exit;
}
// Dashboard KPIs, filters, and quick views
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang['dashboard']; ?> - <?php echo $lang['app_name']; ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1><?php echo $lang['dashboard']; ?> Global</h1>
        <div id="kpi-panels" class="row mb-4">
            <!-- KPIs -->
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $lang['companies']; ?></h5>
                        <p class="card-text" id="kpi-companies">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $lang['users'] ?? 'Usuarios'; ?></h5>
                        <p class="card-text" id="kpi-users">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $lang['plans'] ?? 'Planes'; ?></h5>
                        <p class="card-text" id="kpi-plans">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $lang['modules'] ?? 'Módulos'; ?></h5>
                        <p class="card-text" id="kpi-modules">0</p>
                    </div>
                </div>
            </div>
        </div>
        </div>
        <div class="row mb-3">
            <div class="col">
                <button class="btn btn-primary" onclick="location.href='plans.php'"><?php echo $lang['plans'] ?? 'Planes'; ?></button>
            </div>
            <div class="col text-end">
                <select id="statusFilter" class="form-select w-auto d-inline">
                    <option value="all">Todos</option>
                    <option value="active"><?php echo $lang['active']; ?></option>
                    <option value="inactive"><?php echo $lang['inactive']; ?></option>
                    <option value="trial">Prueba</option>
                </select>
            </div>
        </div>
        <div id="latest-companies" class="mb-4">
            <h4><?php echo $lang['companies']; ?> recientes</h4>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th><?php echo $lang['company_name']; ?></th>
                        <th><?php echo $lang['date']; ?></th>
                        <th><?php echo $lang['status']; ?></th>
                    </tr>
                </thead>
                <tbody id="latest-companies-list">
                    <!-- AJAX: Últimas empresas -->
                </tbody>
            </table>
        </div>
        </div>
    </div>
    <script src="js/panel_root.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        fetch('controller.php?action=dashboard')
            .then(res => res.json())
            .then(data => {
                document.getElementById('kpi-companies').textContent = data.companies ?? 0;
                document.getElementById('kpi-users').textContent = data.users ?? 0;
                document.getElementById('kpi-plans').textContent = data.plans ?? 0;
                document.getElementById('kpi-modules').textContent = data.modules ?? 0;
                // Latest companies
                const list = document.getElementById('latest-companies-list');
                list.innerHTML = '';
                (data.latest_companies ?? []).forEach(function(c) {
                    list.innerHTML += `<tr><td>${c.name}</td><td>${c.date}</td><td>${c.status}</td></tr>`;
                });
            });
    });
    </script>
</body>
</html>
