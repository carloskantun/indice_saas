<?php
require_once '../config.php';
require_once '../components/language_selector.php';

// Si ya está autenticado, redirigir al dashboard
if (checkAuth()) {
    redirect('companies/');
}

$error = '';
$success = '';

if ($_POST) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = $lang['email_required'] . ' / ' . $lang['password_required'];
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, email, password, name, status FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Obtener rol por defecto o el último usado
                $stmt = $db->prepare("SELECT role FROM user_companies WHERE user_id = ? ORDER BY last_accessed DESC LIMIT 1");
                $stmt->execute([$user['id']]);
                $roleData = $stmt->fetch();
                $_SESSION['current_role'] = $roleData['role'] ?? 'user';
                
                $success = $lang['login_success'];
                redirect('companies/');
            } else {
                $error = $lang['invalid_credentials'];
            }
        } catch (Exception $e) {
            $error = $lang['error'] . ': ' . $lang['network_error'];
        }
    }
}
?>
<!DOCTYPE html>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['login']; ?> - <?php echo $lang['app_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            border: none;
        }
        .lang-selector-auth {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body class="d-flex align-items-center">
    <!-- Language Selector -->
    <div class="lang-selector-auth">
        <?php echo renderLanguageSelectorMini(); ?>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="fas fa-chart-line fa-3x text-primary"></i>
                            </div>
                            <h2 class="h4 text-primary fw-bold"><?php echo $lang['app_name']; ?></h2>
                            <p class="text-muted"><?php echo $lang['welcome']; ?></p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i><?php echo $lang['email']; ?>
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       placeholder="<?php echo $lang['email']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-1"></i><?php echo $lang['password']; ?>
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="<?php echo $lang['password']; ?>" required>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    <?php echo $lang['remember_me'] ?? 'Remember me'; ?>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-login text-white w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i><?php echo $lang['login']; ?>
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <p class="mb-2">
                                <a href="register.php" class="text-decoration-none">
                                    <i class="fas fa-user-plus me-1"></i><?php echo $lang['register']; ?>
                                </a>
                            </p>
                            <p class="mb-2">
                                <a href="forgot_password.php" class="text-muted text-decoration-none">
                                    <i class="fas fa-key me-1"></i><?php echo $lang['forgot_password'] ?? 'Forgot password?'; ?>
                                </a>
                            </p>
                            <p class="mb-0">
                                <a href="../" class="text-muted text-decoration-none">
                                    <i class="fas fa-arrow-left me-1"></i><?php echo $lang['back']; ?>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Demo credentials -->
                <div class="text-center mt-3">
                    <small class="text-white bg-dark bg-opacity-75 px-3 py-2 rounded">
                        <i class="fas fa-info-circle me-1"></i>
                        Demo: root@system.com / admin123
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
