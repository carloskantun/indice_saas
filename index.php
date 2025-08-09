<?php
/**
 * Punto de entrada principal del sistema SaaS
 * Indice SaaS - Sistema modular para múltiples empresas
 * Ahora con navegación inteligente
 */

require_once 'config.php';
require_once 'components/language_selector.php';

// Si el usuario está autenticado, usar navegación inteligente
if (checkAuth()) {
    require_once 'includes/smart_navigation.php';
    $smartNav = new SmartNavigation();
    $optimalRoute = $smartNav->getOptimalRoute();
    redirect($optimalRoute);
}

// Si no está autenticado, mostrar página de bienvenida
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['welcome']; ?> - <?php echo $lang['app_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .welcome-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            color: white;
        }
        .lang-selector-top {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Language Selector -->
    <div class="lang-selector-top">
        <?php echo renderLanguageSelectorMini(); ?>
    </div>

    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-lg-10">
                <div class="welcome-card p-5">
                    <!-- Header -->
                    <div class="text-center mb-5">
                        <div class="feature-icon d-inline-flex mb-3">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h1 class="display-4 fw-bold text-primary mb-3">
                            <?php echo $lang['app_name']; ?>
                        </h1>
                        <p class="lead text-muted">
                            Modern SaaS Platform for Multi-Company Management
                        </p>
                    </div>

                    <!-- Features -->
                    <div class="row mb-5">
                        <div class="col-md-4 text-center mb-4">
                            <div class="feature-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <h5 class="fw-bold"><?php echo $lang['companies']; ?></h5>
                            <p class="text-muted small">Manage multiple companies with ease</p>
                        </div>
                        <div class="col-md-4 text-center mb-4">
                            <div class="feature-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h5 class="fw-bold"><?php echo $lang['manage_users']; ?></h5>
                            <p class="text-muted small">Role-based user management system</p>
                        </div>
                        <div class="col-md-4 text-center mb-4">
                            <div class="feature-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <h5 class="fw-bold"><?php echo $lang['notifications']; ?></h5>
                            <p class="text-muted small">Real-time notifications and alerts</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="text-center">
                        <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                            <a href="auth/" class="btn btn-gradient btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                <?php echo $lang['login']; ?>
                            </a>
                            <a href="auth/register.php" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>
                                <?php echo $lang['register']; ?>
                            </a>
                        </div>
                        
                        <div class="mt-4">
                            <a href="language_test.php" class="btn btn-sm btn-outline-secondary me-2">
                                <i class="fas fa-language me-1"></i>
                                Language Test
                            </a>
                            <a href="root.php" class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-crown me-1"></i>
                                Root Panel
                            </a>
                        </div>
                    </div>

                    <!-- Footer Info -->
                    <div class="text-center mt-5 pt-4 border-top">
                        <small class="text-muted">
                            <i class="fas fa-globe me-1"></i>
                            Multi-language support • 
                            <i class="fas fa-shield-alt me-1"></i>
                            Secure authentication • 
                            <i class="fas fa-mobile-alt me-1"></i>
                            Mobile responsive
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
