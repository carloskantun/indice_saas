<?php
/**
 * SISTEMA DE NAVEGACIÓN INTELIGENTE
 * Determina la ruta óptima según la estructura del usuario
 */

require_once 'config.php';

class SmartNavigation {
    private $db;
    private $user_id;
    
    public function __construct() {
        $this->db = getDB();
        $this->user_id = $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Determinar la mejor ruta para el usuario
     */
    public function getOptimalRoute() {
        if (!$this->user_id) {
            return 'auth/';
        }
        
        try {
            // Obtener estructura del usuario
            $userStructure = $this->getUserStructure();
            
            if (empty($userStructure)) {
                return 'auth/register.php?first_time=1';
            }
            
            // Determinar navegación según estructura
            return $this->calculateBestRoute($userStructure);
            
        } catch (Exception $e) {
            error_log("Error in SmartNavigation: " . $e->getMessage());
            return 'companies/';
        }
    }
    
    /**
     * Obtener estructura completa del usuario
     */
    private function getUserStructure() {
        $stmt = $this->db->prepare("
            SELECT 
                c.id as company_id, c.name as company_name,
                u.id as unit_id, u.name as unit_name,
                b.id as business_id, b.name as business_name,
                uc.role as user_role,
                COUNT(DISTINCT c2.id) as total_companies,
                COUNT(DISTINCT u2.id) as total_units,
                COUNT(DISTINCT b2.id) as total_businesses
            FROM user_companies uc
            INNER JOIN companies c ON uc.company_id = c.id
            LEFT JOIN units u ON c.id = u.company_id
            LEFT JOIN businesses b ON u.id = b.unit_id
            -- Contadores para totales
            LEFT JOIN user_companies uc2 ON uc2.user_id = uc.user_id
            LEFT JOIN companies c2 ON uc2.company_id = c2.id
            LEFT JOIN units u2 ON c2.id = u2.company_id
            LEFT JOIN businesses b2 ON u2.id = b2.unit_id
            WHERE uc.user_id = ? AND uc.status = 'active'
            GROUP BY c.id, u.id, b.id, uc.role
            ORDER BY c.name, u.name, b.name
        ");
        
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calcular la mejor ruta según la estructura
     */
    private function calculateBestRoute($structure) {
        $totalCompanies = $structure[0]['total_companies'] ?? 0;
        $totalUnits = $structure[0]['total_units'] ?? 0;
        $totalBusinesses = $structure[0]['total_businesses'] ?? 0;
        
        // Caso 1: Usuario con 1 empresa, 1 unidad, 1 negocio → Directo a módulos
        if ($totalCompanies == 1 && $totalUnits == 1 && $totalBusinesses == 1) {
            $item = $structure[0];
            $this->setSessionContext($item);
            return "modules/?business_id={$item['business_id']}";
        }
        
        // Caso 2: Usuario con 1 empresa, 1 unidad, múltiples negocios → Lista de negocios
        if ($totalCompanies == 1 && $totalUnits == 1 && $totalBusinesses > 1) {
            $item = $structure[0];
            $this->setSessionContext($item, false); // No establecer business_id
            return "businesses/?unit_id={$item['unit_id']}";
        }
        
        // Caso 3: Usuario con 1 empresa, múltiples unidades → Lista de unidades
        if ($totalCompanies == 1 && $totalUnits > 1) {
            $item = $structure[0];
            $_SESSION['company_id'] = $item['company_id'];
            $_SESSION['company_name'] = $item['company_name'];
            $_SESSION['current_role'] = $item['user_role'];
            return "units/?company_id={$item['company_id']}";
        }
        
        // Caso 4: Usuario con múltiples empresas → Lista de empresas
        return 'companies/';
    }
    
    /**
     * Establecer contexto en sesión
     */
    private function setSessionContext($item, $setBusiness = true) {
        $_SESSION['company_id'] = $item['company_id'];
        $_SESSION['company_name'] = $item['company_name'];
        $_SESSION['unit_id'] = $item['unit_id'];
        $_SESSION['unit_name'] = $item['unit_name'];
        $_SESSION['current_role'] = $item['user_role'];
        
        if ($setBusiness && $item['business_id']) {
            $_SESSION['business_id'] = $item['business_id'];
            $_SESSION['business_name'] = $item['business_name'];
        }
    }
    
    /**
     * Verificar si el usuario tiene acceso directo (para empleados básicos)
     */
    public function hasDirectAccess() {
        try {
            $userStructure = $this->getUserStructure();
            
            if (empty($userStructure)) {
                return false;
            }
            
            // Si es rol básico (user) y tiene acceso limitado
            $roles = array_unique(array_column($userStructure, 'user_role'));
            
            return (count($roles) == 1 && in_array('user', $roles));
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtener breadcrumbs optimizados
     */
    public function getOptimalBreadcrumbs() {
        $structure = $this->getUserStructure();
        
        if (empty($structure)) {
            return [];
        }
        
        $totalCompanies = $structure[0]['total_companies'] ?? 0;
        $totalUnits = $structure[0]['total_units'] ?? 0;
        $totalBusinesses = $structure[0]['total_businesses'] ?? 0;
        
        $breadcrumbs = [];
        
        // Solo mostrar breadcrumbs si hay múltiples niveles
        if ($totalCompanies > 1) {
            $breadcrumbs[] = [
                'name' => 'Empresas',
                'url' => 'companies/',
                'icon' => 'fas fa-building'
            ];
        }
        
        if ($totalUnits > 1) {
            $breadcrumbs[] = [
                'name' => $structure[0]['unit_name'] ?? 'Unidades',
                'url' => "units/?company_id={$structure[0]['company_id']}",
                'icon' => 'fas fa-sitemap'
            ];
        }
        
        if ($totalBusinesses > 1) {
            $breadcrumbs[] = [
                'name' => $structure[0]['business_name'] ?? 'Negocios',
                'url' => "businesses/?unit_id={$structure[0]['unit_id']}",
                'icon' => 'fas fa-store'
            ];
        }
        
        return $breadcrumbs;
    }
}

// Función helper para usar en toda la aplicación
function getSmartNavigation() {
    static $navigation = null;
    if ($navigation === null) {
        $navigation = new SmartNavigation();
    }
    return $navigation;
}

// Si se llama directamente, redirigir
if (basename($_SERVER['PHP_SELF']) == 'smart_navigation.php') {
    $nav = new SmartNavigation();
    $route = $nav->getOptimalRoute();
    redirect($route);
}
?>
