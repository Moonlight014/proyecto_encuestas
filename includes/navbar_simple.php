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

<!-- Navbar Simplificado Temporal -->
<style>
.navbar-simple {
    background: #0d47a1;
    color: white;
    padding: 0.75rem 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar-simple .logo-section {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.navbar-simple .logo-section img {
    height: 35px;
    width: auto;
}

.navbar-simple .logo-section span {
    font-weight: 600;
    font-size: 1.1rem;
}

.navbar-simple .user-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.navbar-simple .nav-menu {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.navbar-simple .nav-link {
    color: white;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.navbar-simple .nav-link:hover {
    background: rgba(255,255,255,0.1);
    color: white;
    text-decoration: none;
}

.navbar-simple .user-info {
    font-size: 0.9rem;
}

.navbar-simple .logout-btn {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.3s;
}

.navbar-simple .logout-btn:hover {
    background: rgba(255,255,255,0.2);
    color: white;
    text-decoration: none;
}

@media (max-width: 768px) {
    .navbar-simple .nav-menu {
        display: none;
    }
    
    .navbar-simple .user-info {
        display: none;
    }
}
</style>

<nav class="navbar-simple">
    <div class="logo-section">
        <a href="<?= $base_path ?>admin/dashboard.php" style="display: flex; align-items: center; gap: 0.75rem; color: white; text-decoration: none;">
            <img src="<?= $base_path ?>webicon.png" alt="DAS Hualpén">
            <span>DAS Hualpén</span>
        </a>
    </div>
    
    <div class="nav-menu">
        <a href="<?= $base_path ?>admin/dashboard.php" class="nav-link">
            <i class="fa-solid fa-home"></i> Dashboard
        </a>
        <a href="<?= $base_path ?>admin/ver_encuestas.php" class="nav-link">
            <i class="fa-solid fa-clipboard-list"></i> Encuestas
        </a>
        <a href="<?= $base_path ?>admin/gestionar_preguntas.php" class="nav-link">
            <i class="fa-solid fa-database"></i> Preguntas
        </a>
        <a href="<?= $base_path ?>admin/crear_encuesta.php" class="nav-link">
            <i class="fa-solid fa-plus"></i> Nueva
        </a>
    </div>
    
    <div class="user-section">
        <span class="user-info">
            <?= htmlspecialchars($usuario_nombre) ?>
            <?php if ($es_super_admin): ?>
                <i class="fa-solid fa-crown" style="color: #ffd700; margin-left: 0.5rem;"></i>
            <?php endif; ?>
        </span>
        <a href="<?= $base_path ?>logout.php" class="logout-btn">
            <i class="fa-solid fa-sign-out-alt"></i> Salir
        </a>
    </div>
</nav>