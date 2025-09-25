# 🚀 Guía de Configuración - Sistema de Encuestas DAS Hualpén

## ⚠️ IMPORTANTE: Configuración de Servidor

### ✅ CONFIGURACIÓN RECOMENDADA (XAMPP Estándar)
- **URL Principal**: `http://localhost/php/proyecto_encuestas`
- **Puerto**: 80 (Apache estándar)
- **Dashboard**: `http://localhost/php/proyecto_encuestas/admin/dashboard.php`

### ❌ EVITAR: Servidor en Puerto 8002
- **NO usar**: `http://localhost:8002`
- **Razón**: Causa conflictos con Apache estándar
- **Problema**: Se cae la conexión al levantar ambos servidores

## 📁 Estructura de Archivos CSS
```
/assets/css/
├── styles.css      (Estilos base y navbar)
├── dashboard.css   (Dashboard específico)  
├── forms.css       (Formularios)
└── lists.css       (Listas y grids)
```

## 🔧 Archivos de Testing Disponibles

### Para Diagnóstico:
- `debug_basic.php` - Test básico de PHP y archivos
- `test_static.html` - Test CSS sin PHP
- `dashboard_xampp.php` - Dashboard optimizado para XAMPP

### Dashboard Principal:
- `admin/dashboard.php` - Dashboard principal con autenticación
- `admin/dashboard_temp.php` - Dashboard temporal sin login

## 🎯 URLs de Acceso

### Principales:
- Dashboard: `http://localhost/php/proyecto_encuestas/admin/dashboard.php`
- Ver Encuestas: `http://localhost/php/proyecto_encuestas/admin/ver_encuestas.php`
- Crear Pregunta: `http://localhost/php/proyecto_encuestas/admin/crear_pregunta.php`

### Testing:
- Test XAMPP: `http://localhost/php/proyecto_encuestas/dashboard_xampp.php`
- Test HTML: `http://localhost/php/proyecto_encuestas/test_static.html`
- Debug: `http://localhost/php/proyecto_encuestas/debug_basic.php`

## ⚙️ Configuración de Sesión Temporal

En `admin/dashboard.php` hay líneas temporales para testing:
```php
// Para testing temporal - comentar cuando tengas login funcionando
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
$_SESSION['nombre'] = $_SESSION['nombre'] ?? 'Administrador';
$_SESSION['rol'] = $_SESSION['rol'] ?? 'super_admin';
```

**Para activar autenticación real:**
1. Comentar las líneas temporales
2. Descomentar la verificación de login
3. Asegurar que el sistema de login funcione

## 📝 Características Implementadas

### ✅ Navbar Completo:
- Dropdown de navegación central
- Dropdown de usuario en la derecha
- Responsive design
- Rutas absolutas optimizadas

### ✅ Dashboard:
- Cards de estadísticas con efectos hover
- Botones de acción coloridos
- Layout responsive
- CSS modularizado

### ✅ Sistema CSS:
- Variables CSS para consistencia
- Rutas absolutas para evitar conflictos
- Diseño modular y mantenible

## 🚨 Solución de Problemas

### Si las páginas no cargan:
1. Verificar que XAMPP Apache esté ejecutándose
2. NO levantar servidor en puerto 8002
3. Usar solo `http://localhost/php/proyecto_encuestas`
4. Revisar que los archivos CSS existan en `/assets/css/`

### Si los estilos no se ven:
1. Verificar rutas CSS en la consola del navegador
2. Confirmar que `/assets/css/styles.css` existe
3. Revisar permisos de archivos

## 📞 Contacto
Para dudas sobre la implementación, revisar los archivos de testing o el código fuente.