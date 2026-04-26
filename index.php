<?php
session_start();

require_once 'etc/parametros.php';
require_once 'lib/libreria.php';
require_once 'lib/restaurante.php';
require_once 'lib/auth.php';
require_once 'lib/acciones.php';  // ← NUEVO: incluir las interfaces

// Exponer parámetros de BD en $GLOBALS para fn_get_conn() en auth.php
$GLOBALS['host']     = $host;
$GLOBALS['dbname']   = $dbname;
$GLOBALS['user']     = $user;
$GLOBALS['password'] = $password;

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$autenticado    = isset($_SESSION['usuario_id']);
$usuario_nombre = $_SESSION['usuario_nombre'] ?? '';
$rol_sesion     = $_SESSION['rol'] ?? '';
$rol_param      = $_GET['rol'] ?? '';  // ← El rol que viene del botón

// Determinar qué mostrar:
// - Si hay un parámetro 'rol' en la URL, es una acción específica
// - Si no, mostrar el panel principal del rol del usuario
$accion_especifica = false;
$rol_a_mostrar = '';

if (!empty($rol_param)) {
    // Verificar si el rol_param es una acción (como 'admin_mesas') o un rol
    $roles_validos = ['administrador', 'maitre', 'mesero', 'cocinero', 'cliente'];

    if (in_array(strtolower($rol_param), $roles_validos)) {
        // Es un rol normal
        $rol_a_mostrar = strtolower($rol_param);
    } else {
        // Es una acción específica (admin_mesas, registrar, etc.)
        $accion_especifica = true;

        // Determinar a qué rol pertenece esta acción
        if (strpos($rol_param, 'admin_') === 0 || strpos($rol_param, 'reporte_') === 0) {
            $rol_a_mostrar = 'administrador';
        } elseif (in_array($rol_param, ['registrar', 'asignar', 'verificar', 'cupo', 'proximas'])) {
            if (strtolower($rol_sesion) === 'mesero' && $rol_param === 'registrar') {
                $rol_a_mostrar = 'mesero';
            } else {
                $rol_a_mostrar = 'maitre';
            }
        } elseif (in_array($rol_param, ['agregar', 'estado', 'entrega', 'listos'])) {
            $rol_a_mostrar = 'mesero';
        } elseif (in_array($rol_param, ['preparacion', 'tiempo', 'listo'])) {
            $rol_a_mostrar = 'cocinero';
        } elseif (in_array($rol_param, ['reservar', 'hist_reservaciones', 'hist_pedidos'])) {
            $rol_a_mostrar = 'cliente';
        } else {
            $rol_a_mostrar = strtolower($rol_sesion);
        }
    }
} else {
    $rol_a_mostrar = $autenticado ? strtolower($rol_sesion) : 'auth';
}

// Si no está autenticado, mostrar formulario de auth
if (!$autenticado) {
    $rol_a_mostrar = 'auth';
}

$conn = null;
if ($autenticado) {
    $conn = pg_conectar($host, $dbname, $user, $password);
}

function contenido($rol_a_mostrar, $accion_especifica, $conn, $autenticado, $usuario_nombre) {
    
    if (!$autenticado) return fn_formulario_auth();
    
    // Si es una acción específica, mostrar la interfaz de esa acción
    if ($accion_especifica) {
        $accion = $_GET['rol'];  // La acción viene en el parámetro 'rol'
        return mostrar_interfaz($rol_a_mostrar, $accion, $conn);
    }
    
    // Si no, mostrar el panel principal según el rol
    switch($rol_a_mostrar) {
        case "administrador":
            return '
            <div class="role-header admin-header">
                <div class="role-icon">⚙️</div>
                <div><h2>Panel Administrador</h2><p class="role-desc">Control total del sistema</p></div>
            </div>
            <div class="funciones-grid">
                <div class="funcion-card" onclick="window.location=\'?rol=admin_mesas\'">
                    <div class="card-icon">🪑</div><h3>Gestión de Mesas</h3>
                    <p>Agregar, modificar y eliminar mesas del restaurante</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=admin_menu\'">
                    <div class="card-icon">🍽️</div><h3>Gestión del Menú</h3>
                    <p>Administrar platos, precios y categorías</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=admin_empleados\'">
                    <div class="card-icon">👥</div><h3>Gestión de Empleados</h3>
                    <p>Registrar y gestionar el personal del restaurante</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card reporte" onclick="window.location=\'?rol=reporte_reservaciones\'">
                    <div class="card-icon">📅</div><h3>Reporte de Reservaciones</h3>
                    <p>Visualizar historial y estado de reservaciones</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card reporte" onclick="window.location=\'?rol=reporte_pedidos\'">
                    <div class="card-icon">📊</div><h3>Pedidos Más Solicitados</h3>
                    <p>Ranking de platos con mayor demanda</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card reporte" onclick="window.location=\'?rol=reporte_ventas\'">
                    <div class="card-icon">💰</div><h3>Ventas Totales</h3>
                    <p>Reporte de ingresos y estadísticas de ventas</p>
                    <span class="card-arrow">→</span>
                </div>
            </div>
            <a href="index.php" class="btn-volver">← Menú Principal</a>';
            
        case "maitre":
            return '
            <div class="role-header maitre-header">
                <div class="role-icon">🎩</div>
                <div><h2>Panel Maître</h2><p class="role-desc">Gestión de reservaciones y asignación de mesas</p></div>
            </div>
            <div class="funciones-grid">
                <div class="funcion-card" onclick="window.location=\'?rol=registrar\'">
                    <div class="card-icon">✍️</div><h3>Registrar Reservación</h3>
                    <p>Crear una nueva reservación para un cliente</p><span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=asignar\'">
                    <div class="card-icon">🗺️</div><h3>Asignar Mesa</h3>
                    <p>Asignar mesa según disponibilidad y capacidad</p><span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=verificar\'">
                    <div class="card-icon">✅</div><h3>Verificar Disponibilidad</h3>
                    <p>Comprobar que no se crucen reservaciones</p><span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=cupo\'">
                    <div class="card-icon">👁️</div><h3>Validar Cupo Total</h3>
                    <p>Verificar la capacidad total del restaurante</p><span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=proximas\'">
                    <div class="card-icon">🕐</div><h3>Reservaciones Próximas</h3>
                    <p>Consultar las reservaciones del día y semana</p><span class="card-arrow">→</span>
                </div>
            </div>
            <a href="index.php" class="btn-volver">← Menú Principal</a>';
            
        case "mesero":
            return '
            <div class="role-header mesero-header">
                <div class="role-icon">🍷</div>
                <div><h2>Panel Mesero</h2><p class="role-desc">Gestión de pedidos y atención a mesas</p></div>
            </div>
            <div class="funciones-grid">
                <div class="funcion-card" onclick="window.location=\'?rol=registrar\'">
                    <div class="card-icon">📝</div><h3>Registrar Pedido</h3>
                    <p>Iniciar un nuevo pedido para una mesa</p><span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=agregar\'">
                    <div class="card-icon">➕</div><h3>Agregar Ítems</h3>
                    <p>Añadir múltiples platos a un pedido existente</p><span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=estado\'">
                    <div class="card-icon">🔄</div><h3>Actualizar Estado</h3>
                    <p>En preparación → Listo → Entregado</p><span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=entrega\'">
                    <div class="card-icon">🚀</div><h3>Registrar Entrega</h3>
                    <p>Confirmar la entrega de un pedido a la mesa</p><span class="card-arrow">→</span>
                </div>
                <div class="funcion-card notif" onclick="window.location=\'?rol=listos\'">
                    <div class="card-icon">🔔</div><h3>Pedidos Listos</h3>
                    <p>Ver notificaciones de pedidos listos para entregar</p>
                    <span class="card-arrow">→</span>
                </div>
            </div>
            <a href="index.php" class="btn-volver">← Menú Principal</a>';
            
        case "cocinero":
            return '
            <div class="role-header cocinero-header">
                <div class="role-icon">👨‍🍳</div>
                <div><h2>Panel Cocinero</h2><p class="role-desc">Gestión de la cocina y estado de pedidos</p></div>
            </div>
            <div class="funciones-grid">
                <div class="funcion-card" onclick="window.location=\'?rol=preparacion\'">
                    <div class="card-icon">🔥</div><h3>Pedidos en Preparación</h3>
                    <p>Ver todos los pedidos activos en la cocina</p><span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=tiempo\'">
                    <div class="card-icon">⏱️</div><h3>Ordenar por Tiempo</h3>
                    <p>Ver pedidos ordenados por tiempo de preparación</p><span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=listo\'">
                    <div class="card-icon">✅</div><h3>Marcar como Listo</h3>
                    <p>Actualizar el estado de un pedido a "Listo"</p><span class="card-arrow">→</span>
                </div>
            </div>
            <a href="index.php" class="btn-volver">← Menú Principal</a>';
            
        case "cliente":
            return '
            <div class="role-header cliente-header">
                <div class="role-icon">🧑‍💼</div>
                <div><h2>Panel Cliente</h2><p class="role-desc">Tu experiencia gastronómica personalizada</p></div>
            </div>
            <div class="funciones-grid">
                <div class="funcion-card" onclick="window.location=\'?rol=reservar\'">
                    <div class="card-icon">📆</div><h3>Solicitar Reservación</h3>
                    <p>Reservar una mesa para tu próxima visita</p><span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=hist_reservaciones\'">
                    <div class="card-icon">📋</div><h3>Historial de Reservaciones</h3>
                    <p>Consultar todas tus reservaciones anteriores</p><span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=hist_pedidos\'">
                    <div class="card-icon">🧾</div><h3>Historial de Pedidos</h3>
                    <p>Revisar todos los pedidos que has realizado</p><span class="card-arrow">→</span>
                </div>
            </div>
            <a href="index.php" class="btn-volver">← Menú Principal</a>';
            
        default:
            return '
            <div class="principal-hero">
                <div class="hero-tagline">Bienvenido, ' . htmlspecialchars($usuario_nombre) . '</div>
                <h1 class="hero-title">La Chula <span>Restaurante</span></h1>
                <p class="hero-sub">Selecciona tu función para continuar</p>
            </div>
            <div class="roles-grid">
                <a href="?rol=administrador" class="rol-card rol-admin">
                    <div class="rol-emoji">⚙️</div>
                    <span class="rol-nombre">Administrador</span><span class="rol-desc">Control total</span>
                </a>
                <a href="?rol=maitre" class="rol-card rol-maitre">
                    <div class="rol-emoji">🎩</div>
                    <span class="rol-nombre">Maître</span><span class="rol-desc">Reservaciones</span>
                </a>
                <a href="?rol=mesero" class="rol-card rol-mesero">
                    <div class="rol-emoji">🍷</div>
                    <span class="rol-nombre">Mesero</span><span class="rol-desc">Pedidos y mesas</span>
                </a>
                <a href="?rol=cocinero" class="rol-card rol-cocinero">
                    <div class="rol-emoji">👨‍🍳</div>
                    <span class="rol-nombre">Cocinero</span><span class="rol-desc">Cocina</span>
                </a>
                <a href="?rol=cliente" class="rol-card rol-cliente">
                    <div class="rol-emoji">🧑‍💼</div>
                    <span class="rol-nombre">Cliente</span><span class="rol-desc">Mi experiencia</span>
                </a>
            </div>';
    }
}

$contenido = contenido($rol_a_mostrar, $accion_especifica, $conn, $autenticado, $usuario_nombre);
$esqueleto = file_get_contents("esqueleto.html");

if (!$autenticado) {
    $nav = '';
} else {
    $nav = '
        <a href="index.php">🏠 Inicio</a>
        <span class="session-user">
            👤 ' . htmlspecialchars($usuario_nombre) . '
            &nbsp;·&nbsp;
            <a href="?logout=1" class="logout-link">Salir</a>
        </span>
    ';
}

$esqueleto = str_replace('<!-- NAV_SLOT -->', $nav, $esqueleto);
echo sprintf($esqueleto, $contenido);
?> 
