<?php
// Asegurar que hay sesión iniciada
if (!isset($_SESSION['user_id'])) {
    return;
}

// Obtener información del usuario
$usuario_nombre = $_SESSION['nombre'] ?? 'Usuario';
$usuario_rol = $_SESSION['rol'] ?? 'admin_departamental';
$es_super_admin = ($usuario_rol === 'super_admin');

// Determinar la ruta base dependiendo de la ubicación del archivo
$current_path = $_SERVER['PHP_SELF'];
$is_admin_folder = strpos($current_path, '/admin/') !== false;
$base_path = $is_admin_folder ? '../' : '';
?>

<nav class="navbar-custom">
    <div class="navbar-container">
        <!-- Lado izquierdo: Logo y navegación -->
        <div class="navbar-left">
            <!-- Logo -->
            <a href="<?= $base_path ?>admin/dashboard.php" class="navbar-logo">
                <img src="<?= $base_path ?>webicon.png" alt="DAS Hualpén" class="logo-img">
                <span class="logo-text">DAS Hualpén</span>
            </a>
            
            <!-- Navegación principal (desktop) -->
            <div class="navbar-nav desktop-nav">
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-btn" id="navDropdown">
                        <i class="fa-solid fa-bars"></i>
                        <span>Accesos Rápidos</span>
                        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="dropdown-menu" id="navDropdownMenu">
                        <a href="<?= $base_path ?>admin/dashboard.php" class="dropdown-item">
                            <i class="fa-solid fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="<?= $base_path ?>admin/ver_encuestas.php" class="dropdown-item">
                            <i class="fa-solid fa-clipboard-list"></i>
                            <span>Ver Encuestas</span>
                        </a>
                        <a href="<?= $base_path ?>admin/gestionar_preguntas.php" class="dropdown-item">
                            <i class="fa-solid fa-database"></i>
                            <span>Banco de Preguntas</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?= $base_path ?>admin/crear_encuesta.php" class="dropdown-item">
                            <i class="fa-solid fa-plus"></i>
                            <span>Nueva Encuesta</span>
                        </a>
                        <a href="<?= $base_path ?>admin/crear_pregunta.php" class="dropdown-item">
                            <i class="fa-solid fa-question"></i>
                            <span>Nueva Pregunta</span>
                        </a>
                        <?php if ($es_super_admin): ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= $base_path ?>admin/reportes.php" class="dropdown-item">
                            <i class="fa-solid fa-chart-bar"></i>
                            <span>Reportes</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lado derecho: Usuario -->
        <div class="navbar-right">
            <!-- Toggle para móvil -->
            <button class="mobile-toggle" id="mobileToggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            
            <!-- Información del usuario -->
            <div class="user-info">
                <span class="welcome-text">Bienvenido, </span>
                <span class="user-name"><?= htmlspecialchars($usuario_nombre) ?></span>
                <span class="user-role">
                    <?php if ($es_super_admin): ?>
                        <i class="fa-solid fa-crown"></i> Super Admin
                    <?php else: ?>
                        <i class="fa-solid fa-user"></i> Admin Departamental
                    <?php endif; ?>
                </span>
            </div>
            
            <!-- Dropdown del usuario -->
            <div class="user-dropdown">
                <button class="user-avatar" id="userDropdown">
                    <i class="fa-solid fa-user-circle"></i>
                    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </button>
                <div class="dropdown-menu user-dropdown-menu" id="userDropdownMenu">
                    <div class="dropdown-header">
                        <div class="user-info-dropdown">
                            <strong><?= htmlspecialchars($usuario_nombre) ?></strong>
                            <small>
                                <?= $es_super_admin ? 'Super Administrador' : 'Administrador Departamental' ?>
                            </small>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="<?= $base_path ?>admin/dashboard.php" class="dropdown-item">
                        <i class="fa-solid fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?= $base_path ?>logout.php" class="dropdown-item logout-item">
                        <i class="fa-solid fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navegación móvil -->
    <div class="mobile-nav" id="mobileNav">
        <div class="mobile-nav-content">
            <!-- Información del usuario en móvil -->
            <div class="mobile-user-info">
                <div class="mobile-user-details">
                    <strong><?= htmlspecialchars($usuario_nombre) ?></strong>
                    <small>
                        <?= $es_super_admin ? 'Super Administrador' : 'Administrador Departamental' ?>
                    </small>
                </div>
            </div>
            
            <div class="mobile-nav-divider"></div>
            
            <!-- Enlaces de navegación en móvil -->
            <div class="mobile-nav-links">
                <a href="<?= $base_path ?>admin/dashboard.php" class="mobile-nav-item">
                    <i class="fa-solid fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?= $base_path ?>admin/ver_encuestas.php" class="mobile-nav-item">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span>Ver Encuestas</span>
                </a>
                <a href="<?= $base_path ?>admin/gestionar_preguntas.php" class="mobile-nav-item">
                    <i class="fa-solid fa-database"></i>
                    <span>Banco de Preguntas</span>
                </a>
                <div class="mobile-nav-divider"></div>
                <a href="<?= $base_path ?>admin/crear_encuesta.php" class="mobile-nav-item">
                    <i class="fa-solid fa-plus"></i>
                    <span>Nueva Encuesta</span>
                </a>
                <a href="<?= $base_path ?>admin/crear_pregunta.php" class="mobile-nav-item">
                    <i class="fa-solid fa-question"></i>
                    <span>Nueva Pregunta</span>
                </a>
                <?php if ($es_super_admin): ?>
                <div class="mobile-nav-divider"></div>
                <a href="<?= $base_path ?>admin/reportes.php" class="mobile-nav-item">
                    <i class="fa-solid fa-chart-bar"></i>
                    <span>Reportes</span>
                </a>
                <?php endif; ?>
                <div class="mobile-nav-divider"></div>
                <a href="<?= $base_path ?>logout.php" class="mobile-nav-item logout-item">
                    <i class="fa-solid fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dropdown de navegación
    const navDropdown = document.getElementById('navDropdown');
    const navDropdownMenu = document.getElementById('navDropdownMenu');
    
    if (navDropdown && navDropdownMenu) {
        navDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            navDropdownMenu.classList.toggle('show');
            
            // Cerrar dropdown de usuario si está abierto
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            if (userDropdownMenu) {
                userDropdownMenu.classList.remove('show');
            }
        });
    }
    
    // Dropdown de usuario
    const userDropdown = document.getElementById('userDropdown');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    
    if (userDropdown && userDropdownMenu) {
        userDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            userDropdownMenu.classList.toggle('show');
            
            // Cerrar dropdown de navegación si está abierto
            if (navDropdownMenu) {
                navDropdownMenu.classList.remove('show');
            }
        });
    }
    
    // Toggle móvil
    const mobileToggle = document.getElementById('mobileToggle');
    const mobileNav = document.getElementById('mobileNav');
    
    if (mobileToggle && mobileNav) {
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            mobileNav.classList.toggle('show');
            
            // Cambiar icono
            const icon = mobileToggle.querySelector('i');
            if (mobileNav.classList.contains('show')) {
                icon.className = 'fa-solid fa-times';
            } else {
                icon.className = 'fa-solid fa-bars';
            }
        });
    }
    
    // Cerrar dropdowns al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (navDropdownMenu && !navDropdown.contains(e.target)) {
            navDropdownMenu.classList.remove('show');
        }
        if (userDropdownMenu && !userDropdown.contains(e.target)) {
            userDropdownMenu.classList.remove('show');
        }
        if (mobileNav && !mobileToggle.contains(e.target) && !mobileNav.contains(e.target)) {
            mobileNav.classList.remove('show');
            const icon = mobileToggle.querySelector('i');
            icon.className = 'fa-solid fa-bars';
        }
    });
    
    // Cerrar navegación móvil al hacer clic en un enlace
    const mobileNavItems = document.querySelectorAll('.mobile-nav-item');
    mobileNavItems.forEach(item => {
        item.addEventListener('click', function() {
            mobileNav.classList.remove('show');
            const icon = mobileToggle.querySelector('i');
            icon.className = 'fa-solid fa-bars';
        });
    });
});
</script>