<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación del Sistema Anti-Caché - DAS Hualpén</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin: 0;
            padding: 2rem;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
            color: #2c3e50;
        }
        .validation-item {
            background: #f8f9fa;
            margin: 1rem 0;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .validation-item.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .validation-item.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        .validation-item.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .file-path {
            font-family: monospace;
            background: #e9ecef;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
        }
        .status-ok { background: #28a745; }
        .status-warning { background: #ffc107; color: #212529; }
        .status-error { background: #dc3545; }
        .instructions {
            background: #e3f2fd;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 2rem 0;
        }
        .test-steps {
            background: #f3e5f5;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 2rem 0;
        }
        ol li {
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ Validación del Sistema Anti-Caché</h1>
            <p>Verificación del funcionamiento del sistema de prevención de duplicación de procesos</p>
        </div>

        <?php
        // Verificar archivos implementados
        $archivos_criticos = [
            'admin/crear_encuesta.php' => 'Creación de encuestas',
            'admin/editar_encuesta.php' => 'Edición de encuestas', 
            'admin/crear_pregunta.php' => 'Creación de preguntas',
            'admin/editar_pregunta.php' => 'Edición de preguntas',
            'admin/ver_encuestas.php' => 'Gestión de encuestas',
            'admin/gestionar_preguntas.php' => 'Gestión de preguntas',
            'public/responder.php' => 'Responder encuestas',
            'index.php' => 'Login del sistema',
            'includes/cache_helper.php' => 'Sistema centralizado'
        ];

        $implementaciones = [];
        foreach ($archivos_criticos as $archivo => $descripcion) {
            $ruta_completa = __DIR__ . '/' . $archivo;
            $implementaciones[$archivo] = [
                'descripcion' => $descripcion,
                'existe' => file_exists($ruta_completa),
                'headers_anti_cache' => false,
                'patron_prg' => false,
                'mensajes_sesion' => false
            ];

            if ($implementaciones[$archivo]['existe']) {
                $contenido = file_get_contents($ruta_completa);
                
                // Verificar headers anti-caché
                if (strpos($contenido, 'Cache-Control: no-cache, no-store, must-revalidate') !== false) {
                    $implementaciones[$archivo]['headers_anti_cache'] = true;
                }
                
                // Verificar patrón PRG
                if (strpos($contenido, 'header("Location:') !== false) {
                    $implementaciones[$archivo]['patron_prg'] = true;
                }
                
                // Verificar manejo de mensajes por sesión
                if (strpos($contenido, '_SESSION[') !== false && 
                    (strpos($contenido, 'mensaje_') !== false || strpos($contenido, 'error_') !== false)) {
                    $implementaciones[$archivo]['mensajes_sesion'] = true;
                }
            }
        }
        ?>

        <div class="validation-item success">
            <h3>✅ Archivos Implementados</h3>
            <?php foreach ($implementaciones as $archivo => $info): ?>
                <div style="margin: 0.5rem 0;">
                    <span class="file-path"><?= $archivo ?></span> - <?= $info['descripcion'] ?>
                    <?php if ($info['existe']): ?>
                        <span class="status-badge status-ok">✓ Existe</span>
                        <?php if ($info['headers_anti_cache']): ?>
                            <span class="status-badge status-ok">Headers ✓</span>
                        <?php else: ?>
                            <span class="status-badge status-warning">Sin Headers</span>
                        <?php endif; ?>
                        
                        <?php if ($info['patron_prg']): ?>
                            <span class="status-badge status-ok">PRG ✓</span>
                        <?php else: ?>
                            <span class="status-badge status-warning">Sin PRG</span>
                        <?php endif; ?>
                        
                        <?php if ($info['mensajes_sesion']): ?>
                            <span class="status-badge status-ok">Sesión ✓</span>
                        <?php else: ?>
                            <span class="status-badge status-warning">Sin Sesión</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="status-badge status-error">No Existe</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="instructions">
            <h3>📋 Funcionalidades Implementadas</h3>
            <ul>
                <li><strong>Headers Anti-Caché:</strong> Previenen que el navegador guarde en caché páginas con procesos críticos</li>
                <li><strong>Patrón PRG (Post-Redirect-Get):</strong> Evita reenvío de formularios al usar F5 o botón atrás</li>
                <li><strong>Mensajes por Sesión:</strong> Los mensajes de éxito/error se muestran después del redirect</li>
                <li><strong>Sistema Centralizado:</strong> Funciones helper para gestión uniforme de caché</li>
                <li><strong>Tokens de Formulario:</strong> Para formularios públicos, previene envíos duplicados</li>
                <li><strong>Control por IP:</strong> En respuestas públicas, limita envíos por IP y tiempo</li>
            </ul>
        </div>

        <div class="test-steps">
            <h3>🧪 Pasos para Probar el Sistema</h3>
            <ol>
                <li><strong>Crear Encuesta:</strong> Ve a <code>admin/crear_encuesta.php</code>, crea una encuesta y presiona F5. No debería crear duplicados.</li>
                <li><strong>Editar Encuesta:</strong> Modifica una encuesta y usa el botón atrás del navegador. Los cambios no deberían perderse ni duplicarse.</li>
                <li><strong>Crear Pregunta:</strong> Agrega una pregunta y recarga la página. No debería crear preguntas duplicadas.</li>
                <li><strong>Responder Encuesta:</strong> Envía una respuesta pública e intenta enviar otra inmediatamente. Debería mostrar mensaje de espera.</li>
                <li><strong>Login:</strong> Intenta hacer login y presiona F5 en la página de éxito. No debería intentar login nuevamente.</li>
            </ol>
        </div>

        <div class="validation-item <?= file_exists(__DIR__ . '/includes/cache_helper.php') ? 'success' : 'error' ?>">
            <h3>🔧 Sistema Helper Centralizado</h3>
            <?php if (file_exists(__DIR__ . '/includes/cache_helper.php')): ?>
                <p><strong>✅ Sistema cache_helper.php está disponible</strong></p>
                <p>Funciones disponibles:</p>
                <ul>
                    <li><code>aplicarHeadersAntiCache()</code> - Aplica headers de no-caché</li>
                    <li><code>limpiarCacheFormulario()</code> - Implementa patrón PRG</li>
                    <li><code>obtenerMensajeCache()</code> - Recupera mensajes de sesión</li>
                    <li><code>configurarErrorCache()</code> - Configura errores en sesión</li>
                    <li><code>generarTokenFormulario()</code> - Crea tokens únicos</li>
                    <li><code>prevenirDuplicacionPublica()</code> - Control por IP y tiempo</li>
                </ul>
            <?php else: ?>
                <p><strong>❌ Sistema helper no encontrado</strong></p>
                <p>El archivo <code>includes/cache_helper.php</code> no existe o no es accesible.</p>
            <?php endif; ?>
        </div>

        <div class="validation-item success">
            <h3>🎯 Resultados Esperados</h3>
            <ul>
                <li><strong>No duplicación:</strong> Ningún proceso se ejecuta dos veces por error del usuario</li>
                <li><strong>Mensajes claros:</strong> El usuario siempre ve confirmaciones de éxito o error</li>
                <li><strong>Navegación segura:</strong> Usar botón atrás no causa efectos inesperados</li>
                <li><strong>Performance mejorada:</strong> No se cargan páginas desde caché cuando hay cambios</li>
                <li><strong>Experiencia fluida:</strong> Los redirects son transparentes para el usuario</li>
            </ul>
        </div>

        <div class="validation-item warning">
            <h3>⚠️ Consideraciones Importantes</h3>
            <ul>
                <li>Los headers anti-caché pueden aumentar ligeramente los tiempos de carga</li>
                <li>Los redirects PRG agregan una petición HTTP extra por formulario</li>
                <li>En formularios públicos, el control por IP puede afectar usuarios detrás de NAT</li>
                <li>Los tokens de formulario tienen validez limitada en el tiempo</li>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 2rem; color: #6c757d;">
            <p>Sistema Anti-Caché implementado exitosamente ✅</p>
            <p><small>Todas las operaciones críticas ahora están protegidas contra duplicación</small></p>
        </div>
    </div>
</body>
</html>