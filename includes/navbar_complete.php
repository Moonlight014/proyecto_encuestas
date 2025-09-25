<?php
// Asegurar que hay sesión iniciada
if (!isset($_SESSION['user_id'])) {
    return;
}

// Obtener información del usuario
$usuario_nombre = $_SESSION['nombre'] ?? 'Usuario';
$usuario_rol = $_SESSION['rol'] ?? 'admin_departamental';
$es_super_admin = ($usuario_rol === 'super_admin');

// Cargar helper de rutas si no está cargado
if (!function_exists('detectar_base_url')) {
    $is_admin_folder = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
    $helper_path = $is_admin_folder ? '../config/path_helper.php' : 'config/path_helper.php';
    if (file_exists($helper_path)) {
        require_once $helper_path;
    }
}

// Usar función helper o fallback
if (function_exists('detectar_base_url')) {
    $base_url = detectar_base_url();
} else {
    // Fallback básico si no se puede cargar el helper
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    if (strpos($host, ':') !== false && !strpos($host, ':80') && !strpos($host, ':443')) {
        $base_url = $protocol . $host;
    } else {
        $base_url = $protocol . $host . '/php/proyecto_encuestas';
    }
}

// Para compatibilidad con includes existentes
$is_admin_folder = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$relative_base = $is_admin_folder ? '../' : '';
?>

<nav class="navbar-complete">
    <div class="navbar-container">
        <!-- Logo -->
        <div class="navbar-brand">
            <a href="<?= $base_url ?>/admin/dashboard.php" class="brand-link">
                <img src="<?= $base_url ?>/webicon.png" alt="DAS Hualpén" class="brand-logo">
                <span class="brand-text">DAS Hualpén</span>
            </a>
        </div>

        <!-- Navegación central con dropdown -->
        <div class="navbar-center">
            <div class="nav-dropdown-container">
                <button class="nav-dropdown-toggle" id="navDropdown">
                    <i class="fas fa-bars"></i>
                    <span>Menú</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div class="nav-dropdown-menu" id="navDropdownMenu">
                    <a href="<?= $base_url ?>/admin/dashboard.php" class="nav-dropdown-item">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <div class="nav-dropdown-divider"></div>
                    <a href="<?= $base_url ?>/admin/ver_encuestas.php" class="nav-dropdown-item">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Ver Encuestas</span>
                    </a>
                    <a href="<?= $base_url ?>/admin/crear_encuesta.php" class="nav-dropdown-item">
                        <i class="fas fa-plus-circle"></i>
                        <span>Nueva Encuesta</span>
                    </a>
                    <div class="nav-dropdown-divider"></div>
                    <a href="<?= $base_url ?>/admin/gestionar_preguntas.php" class="nav-dropdown-item">
                        <i class="fas fa-database"></i>
                        <span>Banco de Preguntas</span>
                    </a>
                    <a href="<?= $base_url ?>/admin/crear_pregunta.php" class="nav-dropdown-item">
                        <i class="fas fa-question-circle"></i>
                        <span>Nueva Pregunta</span>
                    </a>
                    <?php if ($es_super_admin): ?>
                    <div class="nav-dropdown-divider"></div>
                    <a href="<?= $base_url ?>/admin/reportes.php" class="nav-dropdown-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reportes</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Usuario con dropdown en la derecha -->
        <div class="navbar-user">
            <div class="user-dropdown-container">
                <button class="user-dropdown-toggle" id="userDropdown">
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($usuario_nombre) ?></span>
                        <span class="user-role">
                            <?php if ($es_super_admin): ?>
                                <i class="fa-solid fa-crown"></i> Super Admin
                            <?php else: ?>
                                <i class="fas fa-user"></i> Administrador
                            <?php endif; ?>
                        </span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div class="user-dropdown-menu" id="userDropdownMenu">
                    <div class="user-dropdown-header">
                        <strong><?= htmlspecialchars($usuario_nombre) ?></strong>
                        <small><?= $es_super_admin ? 'Super Administrador' : 'Administrador Departamental' ?></small>
                    </div>
                    <div class="user-dropdown-divider"></div>
                    <a href="<?= $base_url ?>/admin/perfil.php" class="user-dropdown-item">
                        <i class="fas fa-user-circle"></i>
                        <span>Mi Perfil</span>
                    </a>
                    <a href="<?= $base_url ?>/admin/configuracion.php" class="user-dropdown-item">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                    <div class="user-dropdown-divider"></div>
                    <a href="#" class="user-dropdown-item logout-item" onclick="confirmarLogout(); return false;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Modal de Confirmación de Logout -->
<div id="logoutModal" class="logout-modal">
    <div class="logout-modal-content">
        <div class="logout-modal-header">
            <div class="logout-modal-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <button class="logout-modal-close" onclick="cerrarModalLogout()">&times;</button>
        </div>
        <div class="logout-modal-body">
            <h3>¿Cerrar sesión?</h3>
            <p>¿Está seguro que desea cerrar su sesión?</p>
            <p class="logout-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Se perderá cualquier trabajo no guardado.
            </p>
        </div>
        <div class="logout-modal-footer">
            <button class="logout-modal-btn logout-modal-cancel" onclick="cerrarModalLogout()">
                <i class="fas fa-times"></i>
                Cancelar
            </button>
            <button class="logout-modal-btn logout-modal-confirm" onclick="confirmarYCerrarSesion()">
                <i class="fas fa-sign-out-alt"></i>
                Cerrar Sesión
            </button>
        </div>
    </div>
</div>

<style>
/* ========================================
   NAVBAR COMPLETO CON DROPDOWNS
   ======================================== */
.navbar-complete {
    background: var(--color-primary);
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
    width: 100%;
}

.navbar-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 1rem;
    height: 65px;
}

/* Logo */
.navbar-brand .brand-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: var(--text-white);
    font-weight: 600;
    font-size: 1.1rem;
    transition: opacity 0.3s ease;
}

.navbar-brand .brand-link:hover {
    opacity: 0.9;
    color: var(--text-white);
    text-decoration: none;
}

.brand-logo {
    height: 40px;
    width: auto;
    margin-right: 0.75rem;
}

.brand-text {
    white-space: nowrap;
}

/* Navegación central */
.navbar-center {
    flex: 1;
    display: flex;
    justify-content: center;
}

.nav-dropdown-container {
    position: relative;
}

.nav-dropdown-toggle {
    background: none;
    border: none;
    color: var(--text-white);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.95rem;
    font-weight: 500;
}

.nav-dropdown-toggle:hover {
    background: rgba(255,255,255,0.1);
}

.nav-dropdown-toggle.active {
    background: rgba(255,255,255,0.15);
}

.dropdown-arrow {
    font-size: 0.8rem;
    transition: transform 0.3s ease;
}

.nav-dropdown-toggle.active .dropdown-arrow {
    transform: rotate(180deg);
}

.nav-dropdown-menu {
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: var(--bg-white);
    border-radius: var(--border-radius);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    min-width: 250px;
    padding: 0.5rem 0;
    opacity: 0;
    visibility: hidden;
    transform: translateX(-50%) translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1001;
}

.nav-dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
}

.nav-dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    color: var(--text-dark);
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.nav-dropdown-item:hover {
    background: var(--bg-light);
    color: var(--color-primary);
    text-decoration: none;
}

.nav-dropdown-item i {
    width: 16px;
    text-align: center;
}

.nav-dropdown-divider {
    height: 1px;
    background: var(--border-light);
    margin: 0.5rem 0;
}

/* Usuario con dropdown */
.navbar-user {
    position: relative;
}

.user-dropdown-toggle {
    background: none;
    border: none;
    color: var(--text-white);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.3s ease;
}

.user-dropdown-toggle:hover {
    background: rgba(255,255,255,0.1);
}

.user-dropdown-toggle.active {
    background: rgba(255,255,255,0.15);
}

.user-info {
    text-align: right;
    line-height: 1.2;
}

.user-name {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
}

.user-role {
    display: block;
    font-size: 0.75rem;
    opacity: 0.9;
}

.user-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--bg-white);
    border-radius: var(--border-radius);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    min-width: 200px;
    padding: 0.5rem 0;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1001;
}

.user-dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.user-dropdown-header {
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid var(--border-light);
    text-align: center;
}

.user-dropdown-header strong {
    display: block;
    color: var(--text-dark);
    font-size: 0.9rem;
}

.user-dropdown-header small {
    color: var(--text-muted);
    font-size: 0.75rem;
}

.user-dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    color: var(--text-dark);
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.user-dropdown-item:hover {
    background: var(--bg-light);
    color: var(--color-primary);
    text-decoration: none;
}

.user-dropdown-item.logout-item:hover {
    background: #fee;
    color: var(--color-danger);
}

.user-dropdown-item i {
    width: 16px;
    text-align: center;
}

.user-dropdown-divider {
    height: 1px;
    background: var(--border-light);
    margin: 0.5rem 0;
}

/* Responsive */
@media (max-width: 768px) {
    .navbar-container {
        padding: 0 0.75rem;
    }
    
    .user-info {
        display: none;
    }
    
    .user-dropdown-toggle {
        padding: 0.5rem;
    }
    
    .brand-text {
        font-size: 1rem;
    }
}

@media (max-width: 576px) {
    .navbar-center {
        display: none;
    }
    
    .navbar-container {
        justify-content: space-between;
    }
}

/* ========================================
   MODAL DE CONFIRMACIÓN DE LOGOUT
   ======================================== */
.logout-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(3px);
    animation: fadeIn 0.3s ease-out;
}

.logout-modal-content {
    position: relative;
    background-color: #ffffff;
    margin: 10% auto;
    border: none;
    border-radius: 12px;
    width: 90%;
    max-width: 450px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    animation: slideIn 0.3s ease-out;
    overflow: hidden;
}

.logout-modal-header {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 20px;
    position: relative;
    text-align: center;
}

.logout-modal-icon {
    font-size: 3rem;
    margin-bottom: 10px;
    opacity: 0.9;
}

.logout-modal-close {
    position: absolute;
    right: 15px;
    top: 15px;
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    padding: 5px 10px;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.logout-modal-close:hover {
    background-color: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg);
}

.logout-modal-body {
    padding: 30px 25px 20px;
    text-align: center;
}

.logout-modal-body h3 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.5rem;
    font-weight: 600;
}

.logout-modal-body p {
    color: #666;
    margin-bottom: 15px;
    font-size: 1rem;
    line-height: 1.5;
}

.logout-warning {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 12px;
    color: #856404 !important;
    font-size: 0.9rem !important;
}

.logout-warning i {
    color: #f39c12;
    margin-right: 8px;
}

.logout-modal-footer {
    padding: 20px 25px 25px;
    display: flex;
    justify-content: space-between;
    gap: 15px;
}

.logout-modal-btn {
    flex: 1;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.logout-modal-cancel {
    background-color: #6c757d;
    color: white;
}

.logout-modal-cancel:hover {
    background-color: #5a6268;
    transform: translateY(-1px);
}

.logout-modal-confirm {
    background-color: #dc3545;
    color: white;
}

.logout-modal-confirm:hover {
    background-color: #c82333;
    transform: translateY(-1px);
}

/* Animaciones */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { 
        opacity: 0;
        transform: translateY(-50px) scale(0.9);
    }
    to { 
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .logout-modal-content {
        margin: 20% auto;
        width: 95%;
    }
    
    .logout-modal-footer {
        flex-direction: column;
    }
    
    .logout-modal-btn {
        flex: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dropdown de navegación
    const navDropdown = document.getElementById('navDropdown');
    const navDropdownMenu = document.getElementById('navDropdownMenu');
    
    if (navDropdown && navDropdownMenu) {
        navDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            const isActive = navDropdownMenu.classList.contains('show');
            
            // Cerrar todos los dropdowns
            document.querySelectorAll('.nav-dropdown-menu, .user-dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
            document.querySelectorAll('.nav-dropdown-toggle, .user-dropdown-toggle').forEach(toggle => {
                toggle.classList.remove('active');
            });
            
            if (!isActive) {
                navDropdownMenu.classList.add('show');
                navDropdown.classList.add('active');
            }
        });
    }
    
    // Dropdown de usuario
    const userDropdown = document.getElementById('userDropdown');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    
    if (userDropdown && userDropdownMenu) {
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            const isActive = userDropdownMenu.classList.contains('show');
            
            // Cerrar todos los dropdowns
            document.querySelectorAll('.nav-dropdown-menu, .user-dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
            document.querySelectorAll('.nav-dropdown-toggle, .user-dropdown-toggle').forEach(toggle => {
                toggle.classList.remove('active');
            });
            
            if (!isActive) {
                userDropdownMenu.classList.add('show');
                userDropdown.classList.add('active');
            }
        });
    }
    
    // Cerrar dropdowns al hacer clic fuera
    document.addEventListener('click', function() {
        document.querySelectorAll('.nav-dropdown-menu, .user-dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
        document.querySelectorAll('.nav-dropdown-toggle, .user-dropdown-toggle').forEach(toggle => {
            toggle.classList.remove('active');
        });
    });
    
    // Prevenir cerrar al hacer clic dentro del dropdown
    document.querySelectorAll('.nav-dropdown-menu, .user-dropdown-menu').forEach(menu => {
        menu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
});

// Funciones para el modal de logout
function confirmarLogout() {
    mostrarModalLogout();
    return false; // Prevenir navegación inmediata
}

function mostrarModalLogout() {
    const modal = document.getElementById('logoutModal');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevenir scroll del fondo
    
    // Enfocar el modal para accesibilidad
    const cancelBtn = modal.querySelector('.logout-modal-cancel');
    if (cancelBtn) {
        cancelBtn.focus();
    }
}

function cerrarModalLogout() {
    const modal = document.getElementById('logoutModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto'; // Restaurar scroll
}

function confirmarYCerrarSesion() {
    cerrarModalLogout();
    // Redirigir a logout después de cerrar el modal
    window.location.href = '<?= $base_url ?>/logout.php';
}

// Cerrar modal al hacer clic fuera de él
document.addEventListener('click', function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target === modal) {
        cerrarModalLogout();
    }
});

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('logoutModal');
        if (modal.style.display === 'block') {
            cerrarModalLogout();
        }
    }
});

// Limpiar historia del navegador después del logout para mayor seguridad
window.addEventListener('beforeunload', function() {
    // Solo limpiar si estamos en proceso de logout
    if (document.activeElement && document.activeElement.classList.contains('logout-item')) {
        if (window.history && window.history.pushState) {
            window.history.pushState(null, null, window.location.href);
            window.history.pushState(null, null, window.location.href);
            window.onpopstate = function() {
                window.history.go(1);
            };
        }
    }
});
</script>