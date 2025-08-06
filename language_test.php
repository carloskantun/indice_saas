<?php
/**
 * Test del Sistema de Idiomas
 * Verifica que las traducciones y cambio de idioma funcionen
 */

require_once 'config.php';
require_once 'components/language_selector.php';

// Verificar autenticación (opcional para esta prueba)
$user_authenticated = checkAuth();
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['app_name']; ?> - Language Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-section {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .lang-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="h3 mb-1">
                    <i class="fas fa-language me-2"></i>
                    <?php echo $lang['app_name']; ?> - Language System Test
                </h1>
                <p class="text-muted">Testing multilingual functionality</p>
            </div>
            <div class="col-md-4 text-end">
                <?php echo renderLanguageSelector(); ?>
            </div>
        </div>

        <!-- Language Info -->
        <div class="lang-info">
            <h5><i class="fas fa-info-circle me-2"></i>Current Language Information</h5>
            <div class="row">
                <div class="col-md-3">
                    <strong>Current:</strong> <?php echo getCurrentLanguage(); ?> (<?php echo AVAILABLE_LANGUAGES[getCurrentLanguage()]; ?>)
                </div>
                <div class="col-md-3">
                    <strong>Session:</strong> <?php echo $_SESSION['language'] ?? 'Not set'; ?>
                </div>
                <div class="col-md-3">
                    <strong>Browser:</strong> <?php echo substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Unknown', 0, 2); ?>
                </div>
                <div class="col-md-3">
                    <strong>Default:</strong> <?php echo DEFAULT_LANGUAGE; ?>
                </div>
            </div>
        </div>

        <!-- Translation Tests -->
        <div class="row">
            <!-- Basic Terms -->
            <div class="col-md-6">
                <div class="test-section">
                    <h5 class="text-primary mb-3">
                        <i class="fas fa-font me-2"></i>Basic Terms
                    </h5>
                    <table class="table table-sm">
                        <tr><td><strong>Welcome:</strong></td><td><?php echo $lang['welcome']; ?></td></tr>
                        <tr><td><strong>Dashboard:</strong></td><td><?php echo $lang['dashboard']; ?></td></tr>
                        <tr><td><strong>Login:</strong></td><td><?php echo $lang['login']; ?></td></tr>
                        <tr><td><strong>Logout:</strong></td><td><?php echo $lang['logout']; ?></td></tr>
                        <tr><td><strong>Email:</strong></td><td><?php echo $lang['email']; ?></td></tr>
                        <tr><td><strong>Password:</strong></td><td><?php echo $lang['password']; ?></td></tr>
                        <tr><td><strong>Save:</strong></td><td><?php echo $lang['save']; ?></td></tr>
                        <tr><td><strong>Cancel:</strong></td><td><?php echo $lang['cancel']; ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- Business Terms -->
            <div class="col-md-6">
                <div class="test-section">
                    <h5 class="text-success mb-3">
                        <i class="fas fa-building me-2"></i>Business Terms
                    </h5>
                    <table class="table table-sm">
                        <tr><td><strong>Companies:</strong></td><td><?php echo $lang['companies']; ?></td></tr>
                        <tr><td><strong>Create Company:</strong></td><td><?php echo $lang['create_company']; ?></td></tr>
                        <tr><td><strong>Units:</strong></td><td><?php echo $lang['units']; ?></td></tr>
                        <tr><td><strong>Businesses:</strong></td><td><?php echo $lang['businesses']; ?></td></tr>
                        <tr><td><strong>Users:</strong></td><td><?php echo $lang['manage_users'] ?? 'Users'; ?></td></tr>
                        <tr><td><strong>Invite User:</strong></td><td><?php echo $lang['invite_user']; ?></td></tr>
                        <tr><td><strong>Notifications:</strong></td><td><?php echo $lang['notifications']; ?></td></tr>
                        <tr><td><strong>Settings:</strong></td><td><?php echo $lang['settings']; ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- Roles -->
            <div class="col-md-6">
                <div class="test-section">
                    <h5 class="text-warning mb-3">
                        <i class="fas fa-user-tag me-2"></i>Roles
                    </h5>
                    <table class="table table-sm">
                        <tr><td><strong>Root:</strong></td><td><?php echo $lang['root']; ?></td></tr>
                        <tr><td><strong>Support:</strong></td><td><?php echo $lang['support']; ?></td></tr>
                        <tr><td><strong>Super Admin:</strong></td><td><?php echo $lang['superadmin']; ?></td></tr>
                        <tr><td><strong>Admin:</strong></td><td><?php echo $lang['admin']; ?></td></tr>
                        <tr><td><strong>Moderator:</strong></td><td><?php echo $lang['moderator']; ?></td></tr>
                        <tr><td><strong>User:</strong></td><td><?php echo $lang['user']; ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- Status Terms -->
            <div class="col-md-6">
                <div class="test-section">
                    <h5 class="text-info mb-3">
                        <i class="fas fa-flag me-2"></i>Status Terms
                    </h5>
                    <table class="table table-sm">
                        <tr><td><strong>Active:</strong></td><td><?php echo $lang['active']; ?></td></tr>
                        <tr><td><strong>Inactive:</strong></td><td><?php echo $lang['inactive']; ?></td></tr>
                        <tr><td><strong>Suspended:</strong></td><td><?php echo $lang['suspended']; ?></td></tr>
                        <tr><td><strong>Pending:</strong></td><td><?php echo $lang['pending']; ?></td></tr>
                        <tr><td><strong>Accepted:</strong></td><td><?php echo $lang['accepted']; ?></td></tr>
                        <tr><td><strong>Expired:</strong></td><td><?php echo $lang['expired']; ?></td></tr>
                        <tr><td><strong>Error:</strong></td><td><?php echo $lang['error']; ?></td></tr>
                        <tr><td><strong>Success:</strong></td><td><?php echo $lang['success']; ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Language Selector Variants -->
        <div class="test-section">
            <h5 class="text-secondary mb-3">
                <i class="fas fa-cogs me-2"></i>Language Selector Variants
            </h5>
            <div class="row">
                <div class="col-md-4">
                    <h6>Dropdown with flags:</h6>
                    <?php echo renderLanguageSelector(null, true, true); ?>
                </div>
                <div class="col-md-4">
                    <h6>Buttons with flags:</h6>
                    <?php echo renderLanguageSelector(null, true, false); ?>
                </div>
                <div class="col-md-4">
                    <h6>Mini selector:</h6>
                    <?php echo renderLanguageSelectorMini(); ?>
                </div>
            </div>
        </div>

        <!-- Authentication Status -->
        <?php if ($user_authenticated): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong><?php echo $lang['welcome']; ?>!</strong> You are logged in as: <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Unknown'); ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong><?php echo $lang['login_required']; ?></strong> 
                <a href="auth/" class="alert-link"><?php echo $lang['login']; ?></a>
            </div>
        <?php endif; ?>

        <!-- Debug Info -->
        <div class="test-section">
            <h6 class="text-muted mb-3">
                <i class="fas fa-bug me-2"></i>Debug Information
            </h6>
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">
                        <strong>Available Languages:</strong><br>
                        <?php foreach (AVAILABLE_LANGUAGES as $code => $name): ?>
                            • <?php echo $code; ?> = <?php echo $name; ?><br>
                        <?php endforeach; ?>
                    </small>
                </div>
                <div class="col-md-6">
                    <small class="text-muted">
                        <strong>Total translations loaded:</strong> <?php echo count($lang); ?><br>
                        <strong>Session language:</strong> <?php echo $_SESSION['language'] ?? 'Not set'; ?><br>
                        <strong>GET lang param:</strong> <?php echo $_GET['lang'] ?? 'Not set'; ?><br>
                        <strong>HTTP Accept Language:</strong> <?php echo $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Not available'; ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-primary me-2">
                <i class="fas fa-home me-1"></i><?php echo $lang['dashboard']; ?>
            </a>
            <a href="auth/" class="btn btn-outline-secondary me-2">
                <i class="fas fa-sign-in-alt me-1"></i><?php echo $lang['login']; ?>
            </a>
            <a href="companies/" class="btn btn-outline-success">
                <i class="fas fa-building me-1"></i><?php echo $lang['companies']; ?>
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
