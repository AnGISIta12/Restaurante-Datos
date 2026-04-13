<?php
// lib/acciones.php - Interfaz para cada acción por rol

function mostrar_interfaz($rol, $accion, $conn) {
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    
    switch($rol) {
        case 'administrador':
            return interfaz_admin($accion, $conn);
        case 'maitre':
            return interfaz_maitre($accion, $conn);
        case 'mesero':
            return interfaz_mesero($accion, $conn, $usuario_id);
        case 'cocinero':
            return interfaz_cocinero($accion, $conn);
        case 'cliente':
            return interfaz_cliente($accion, $conn, $usuario_id);
        default:
            return "<p>Acción no reconocida.</p>";
    }
}
/* ==================== ADMINISTRADOR ==================== */
function interfaz_admin($accion, $conn) {
    $retorno = "<div class='role-header admin-header'>
                    <div class='role-icon'>⚙️</div>
                    <div><h2>Administrador</h2><p class='role-desc'>" . ucfirst($accion) . "</p></div>
                </div>
                <a href='?rol=administrador' class='btn-volver'>← Volver al Panel</a><br><br>";
    
    switch($accion) {
        case 'admin_mesas':
            $retorno .= interfaz_gestion_mesas($conn);
            break;
        case 'admin_menu':
            $retorno .= interfaz_gestion_menu($conn);
            break;
        case 'admin_empleados':
            $retorno .= interfaz_gestion_empleados($conn);
            break;
        case 'reporte_reservaciones':
            $retorno .= interfaz_reporte_reservaciones($conn);
            break;
        case 'reporte_pedidos':
            $retorno .= interfaz_reporte_pedidos($conn);
            break;
        case 'reporte_ventas':
            $retorno .= interfaz_reporte_ventas($conn);
            break;
        default:
            $retorno .= "<p>Selecciona una opción del menú.</p>";
    }
    return $retorno;
}

function interfaz_gestion_mesas($conn) {
    $html = "<h3>📋 Gestión de Mesas</h3>";
    
    // Formulario para agregar mesa
    $html .= "<form method='POST' style='background:white; padding:20px; border-radius:16px; margin-bottom:20px;'>
                <h4>➕ Agregar Nueva Mesa</h4>
                <div class='auth-field'><label>Número de sillas</label><input type='number' name='sillas' required></div>
                <button type='submit' name='accion_mesa' value='agregar' class='auth-btn'>Agregar Mesa</button>
              </form>";
    
    // Listado de mesas existentes
    $sql = "SELECT id_mesa, sillas FROM mesas ORDER BY id_mesa";
    $res = procesar_query($sql, $conn);
    if($res->cantidad > 0) {
        $html .= "<h4>📌 Mesas actuales</h4><div class='funciones-grid'>";
        foreach($res->datos as $mesa) {
            $html .= "<div class='funcion-card'>
                        <div class='card-icon'>🪑</div>
                        <h3>Mesa #{$mesa['id_mesa']}</h3>
                        <p>{$mesa['sillas']} sillas</p>
                        <form method='POST' style='margin-top:10px;'>
                            <input type='hidden' name='mesa_id' value='{$mesa['id_mesa']}'>
                            <button type='submit' name='accion_mesa' value='eliminar' class='btn-volver' style='background:#c0392b; color:white;'>Eliminar</button>
                        </form>
                      </div>";
        }
        $html .= "</div>";
    }
    
    // Procesar POST
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_mesa'])) {
        if($_POST['accion_mesa'] === 'agregar') {
            $sillas = intval($_POST['sillas']);
            $stmt = $conn->prepare("INSERT INTO mesas (sillas) VALUES (?)");
            $stmt->execute([$sillas]);
            echo "<script>window.location='?rol=administrador&accion=admin_mesas';</script>";
        } elseif($_POST['accion_mesa'] === 'eliminar' && isset($_POST['mesa_id'])) {
            $stmt = $conn->prepare("DELETE FROM mesas WHERE id_mesa = ?");
            $stmt->execute([$_POST['mesa_id']]);
            echo "<script>window.location='?rol=administrador&accion=admin_mesas';</script>";
        }
    }
    
    return $html;
}

function interfaz_gestion_menu($conn) {
    $html = "<h3>🍽️ Gestión del Menú</h3>";
    
    // Formulario agregar plato
    $html .= "<form method='POST' style='background:white; padding:20px; border-radius:16px; margin-bottom:20px;'>
                <h4>➕ Agregar Plato</h4>
                <div class='auth-field'><label>Nombre</label><input type='text' name='nombre' required></div>
                <div class='auth-field'><label>Descripción</label><textarea name='descripcion'></textarea></div>
                <div class='auth-field'><label>Precio</label><input type='number' step='0.01' name='precio' required></div>
                <div class='auth-field'>
                    <label>Tipo</label>
                    <select name='tipo_id'>";
    $tipos = procesar_query("SELECT id, nombre FROM tipos ORDER BY id", $conn);
    foreach($tipos->datos as $tipo) {
        $html .= "<option value='{$tipo['id']}'>{$tipo['nombre']}</option>";
    }
    $html .= "</select></div>
              <button type='submit' name='accion_plato' value='agregar' class='auth-btn'>Agregar Plato</button>
              </form>";
    
    // Listado de platos
    $sql = "SELECT p.id_plato, p.nombre, p.descripcion, p.precio, t.nombre as tipo 
            FROM platos p LEFT JOIN tipos t ON p.tipo_id = t.id ORDER BY t.id, p.nombre";
    $res = procesar_query($sql, $conn);
    if($res->cantidad > 0) {
        $html .= "<h4>📌 Platos actuales</h4><div class='funciones-grid'>";
        foreach($res->datos as $plato) {
            $html .= "<div class='funcion-card'>
                        <div class='card-icon'>🍲</div>
                        <h3>{$plato['nombre']}</h3>
                        <p>{$plato['descripcion']}</p>
                        <p><strong>\${$plato['precio']}</strong> - {$plato['tipo']}</p>
                        <form method='POST' style='margin-top:10px;'>
                            <input type='hidden' name='plato_id' value='{$plato['id_plato']}'>
                            <button type='submit' name='accion_plato' value='eliminar' class='btn-volver' style='background:#c0392b; color:white;'>Eliminar</button>
                        </form>
                      </div>";
        }
        $html .= "</div>";
    }
    
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_plato'])) {
        if($_POST['accion_plato'] === 'agregar') {
            $stmt = $conn->prepare("INSERT INTO platos (nombre, descripcion, precio, tipo_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['nombre'], $_POST['descripcion'], $_POST['precio'], $_POST['tipo_id']]);
            echo "<script>window.location='?rol=administrador&accion=admin_menu';</script>";
        } elseif($_POST['accion_plato'] === 'eliminar' && isset($_POST['plato_id'])) {
            $stmt = $conn->prepare("DELETE FROM platos WHERE id_plato = ?");
            $stmt->execute([$_POST['plato_id']]);
            echo "<script>window.location='?rol=administrador&accion=admin_menu';</script>";
        }
    }
    
    return $html;
}

function interfaz_gestion_empleados($conn) {
    $html = "<h3>👥 Gestión de Empleados</h3>
             <form method='POST' style='background:white; padding:20px; border-radius:16px; margin-bottom:20px;'>
                <h4>➕ Asignar Rol a Usuario</h4>
                <div class='auth-field'><label>Usuario</label>
                    <select name='usuario_id'>";
    $usuarios = procesar_query("SELECT id_usuario, nombre FROM usuarios ORDER BY nombre", $conn);
    foreach($usuarios->datos as $usr) {
        $html .= "<option value='{$usr['id_usuario']}'>{$usr['nombre']}</option>";
    }
    $html .= "</select></div>
              <div class='auth-field'><label>Rol</label>
                <select name='rol_id'>";
    $roles = procesar_query("SELECT id_rol, nombre FROM roles ORDER BY nombre", $conn);
    foreach($roles->datos as $rol) {
        $html .= "<option value='{$rol['id_rol']}'>{$rol['nombre']}</option>";
    }
    $html .= "</select></div>
              <button type='submit' name='accion_empleado' value='asignar' class='auth-btn'>Asignar Rol</button>
              </form>";
    
    // Listar usuarios con sus roles
    $sql = "SELECT u.id_usuario, u.nombre, COALESCE(r.nombre, 'Sin rol') as rol
            FROM usuarios u
            LEFT JOIN actuaciones a ON u.id_usuario = a.usuario_id
            LEFT JOIN roles r ON a.rol_id = r.id_rol
            ORDER BY u.nombre";
    $res = procesar_query($sql, $conn);
    $html .= "<h4>📌 Empleados y sus roles</h4><div class='funciones-grid'>";
    foreach($res->datos as $emp) {
        $html .= "<div class='funcion-card'>
                    <div class='card-icon'>👤</div>
                    <h3>{$emp['nombre']}</h3>
                    <p>Rol: {$emp['rol']}</p>
                  </div>";
    }
    $html .= "</div>";
    
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_empleado']) && $_POST['accion_empleado'] === 'asignar') {
        $stmt = $conn->prepare("INSERT INTO actuaciones (usuario_id, rol_id) VALUES (?, ?) ON CONFLICT DO NOTHING");
        $stmt->execute([$_POST['usuario_id'], $_POST['rol_id']]);
        echo "<script>window.location='?rol=administrador&accion=admin_empleados';</script>";
    }
    
    return $html;
}

function interfaz_reporte_reservaciones($conn) {
    $sql = "SELECT r.id_reservacion, u.nombre as cliente, r.fecha, r.numero_personas, m.id_mesa, h.inicio
            FROM reservaciones r
            LEFT JOIN usuarios u ON r.cliente_id = u.id_usuario
            LEFT JOIN horarios h ON r.id_reservacion = h.reservacion_id
            LEFT JOIN mesas m ON h.mesa_id = m.id_mesa
            ORDER BY r.fecha DESC LIMIT 20";
    $res = procesar_query($sql, $conn);
    $html = "<div class='role-header admin-header'><div><h2>📅 Reporte de Reservaciones</h2></div></div>
             <a href='?rol=administrador' class='btn-volver'>← Volver</a><br><br>";
    if($res->cantidad > 0) {
        $html .= "<table border='1' cellpadding='10' style='width:100%; border-collapse:collapse; background:white;'>
                    <tr><th>ID</th><th>Cliente</th><th>Fecha</th><th>Personas</th><th>Mesa</th><th>Horario</th></tr>";
        foreach($res->datos as $row) {
            $html .= "<tr><td>{$row['id_reservacion']}</td><td>{$row['cliente']}</td><td>{$row['fecha']}</td>
                      <td>{$row['numero_personas']}</td><td>{$row['id_mesa']}</td><td>{$row['inicio']}</td></tr>";
        }
        $html .= "</table>";
    } else {
        $html .= "<p>No hay reservaciones registradas.</p>";
    }
    return $html;
}

function interfaz_reporte_pedidos($conn) {
    $sql = "SELECT p.nombre, COUNT(dp.id_detalle) as total_pedidos
            FROM detalle_pedidos dp
            JOIN platos p ON dp.plato_id = p.id_plato
            GROUP BY p.id_plato, p.nombre
            ORDER BY total_pedidos DESC LIMIT 10";
    $res = procesar_query($sql, $conn);
    $html = "<div class='role-header admin-header'><div><h2>📊 Top Platos Más Pedidos</h2></div></div>
             <a href='?rol=administrador' class='btn-volver'>← Volver</a><br><br>";
    if($res->cantidad > 0) {
        $html .= "<table border='1' cellpadding='10' style='width:100%; border-collapse:collapse; background:white;'>
                    <tr><th>Plato</th><th>Veces Pedido</th></tr>";
        foreach($res->datos as $row) {
            $html .= "<tr><td>{$row['nombre']}</td><td>{$row['total_pedidos']}</td></tr>";
        }
        $html .= "</table>";
    } else {
        $html .= "<p>No hay pedidos registrados.</p>";
    }
    return $html;
}

function interfaz_reporte_ventas($conn) {
    $sql = "SELECT SUM(p.precio) as total_ventas
            FROM detalle_pedidos dp
            JOIN platos p ON dp.plato_id = p.id_plato
            JOIN pedidos pe ON dp.pedido_id = pe.id_pedido";
    $res = procesar_query($sql, $conn);
    $total = $res->datos[0]['total_ventas'] ?? 0;
    $html = "<div class='role-header admin-header'><div><h2>💰 Ventas Totales</h2></div></div>
             <a href='?rol=administrador' class='btn-volver'>← Volver</a><br><br>
             <div style='background:white; padding:40px; text-align:center; border-radius:16px;'>
                <h1 style='font-size:48px; color:navy;'>$" . number_format($total, 2) . "</h1>
                <p>Ingresos totales generados</p>
             </div>";
    return $html;
}

/* ==================== MAITRE ==================== */
function interfaz_maitre($accion, $conn) {
    $retorno = "<div class='role-header maitre-header'>
                    <div><h2>🎩 Maître - " . ucfirst($accion) . "</h2></div>
                </div>
                <a href='?rol=maitre' class='btn-volver'>← Volver</a><br><br>";
    
    switch($accion) {
        case 'registrar':
            $retorno .= interfaz_registrar_reservacion($conn);
            break;
        case 'asignar':
            $retorno .= interfaz_asignar_mesa($conn);
            break;
        case 'verificar':
            $retorno .= interfaz_verificar_disponibilidad($conn);
            break;
        case 'cupo':
            $retorno .= interfaz_validar_cupo($conn);
            break;
        case 'proximas':
            $retorno .= interfaz_reservaciones_proximas($conn);
            break;
        default:
            $retorno .= "<p>Selecciona una acción.</p>";
    }
    return $retorno;
}

function interfaz_registrar_reservacion($conn) {
    $html = "<h3>✍️ Registrar Nueva Reservación</h3>
             <form method='POST' style='background:white; padding:20px; border-radius:16px;'>
                <div class='auth-field'><label>Cliente (ID)</label><input type='number' name='cliente_id' placeholder='ID del usuario' required></div>
                <div class='auth-field'><label>Fecha (YYYY-MM-DD)</label><input type='date' name='fecha' required></div>
                <div class='auth-field'><label>Número de personas</label><input type='number' name='personas' required></div>
                <div class='auth-field'><label>Horario</label><input type='datetime-local' name='inicio' required></div>
                <button type='submit' name='crear_reservacion' class='auth-btn'>Crear Reservación</button>
             </form>";
    
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_reservacion'])) {
        $stmt = $conn->prepare("INSERT INTO reservaciones (cliente_id, fecha, numero_personas) VALUES (?, ?, ?) RETURNING id_reservacion");
        $stmt->execute([$_POST['cliente_id'], $_POST['fecha'], $_POST['personas']]);
        $res_id = $stmt->fetchColumn();
        $stmt2 = $conn->prepare("INSERT INTO horarios (reservacion_id, inicio) VALUES (?, ?)");
        $stmt2->execute([$res_id, $_POST['inicio']]);
        $html .= "<div class='auth-msg auth-ok'>✅ Reservación creada con ID: $res_id</div>";
    }
    return $html;
}

function interfaz_asignar_mesa($conn) {
    $html = "<h3>🗺️ Asignar Mesa a Reservación</h3>
             <form method='POST' style='background:white; padding:20px; border-radius:16px;'>
                <div class='auth-field'><label>ID Reservación</label><input type='number' name='reservacion_id' required></div>
                <div class='auth-field'><label>ID Mesa</label><input type='number' name='mesa_id' required></div>
                <button type='submit' name='asignar_mesa' class='auth-btn'>Asignar</button>
             </form>";
    
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_mesa'])) {
        $stmt = $conn->prepare("UPDATE horarios SET mesa_id = ? WHERE reservacion_id = ?");
        $stmt->execute([$_POST['mesa_id'], $_POST['reservacion_id']]);
        $html .= "<div class='auth-msg auth-ok'>✅ Mesa asignada correctamente</div>";
    }
    return $html;
}

function interfaz_verificar_disponibilidad($conn) {
    $html = "<h3>✅ Verificar Disponibilidad de Mesa</h3>
             <form method='GET' style='background:white; padding:20px; border-radius:16px;'>
                <div class='auth-field'><label>Mesa ID</label><input type='number' name='mesa_check' required></div>
                <div class='auth-field'><label>Fecha y Hora</label><input type='datetime-local' name='hora_check' required></div>
                <button type='submit' class='auth-btn'>Verificar</button>
             </form>";
    
    if(isset($_GET['mesa_check'])) {
        $sql = "SELECT COUNT(*) FROM horarios h 
                JOIN reservaciones r ON h.reservacion_id = r.id_reservacion
                WHERE h.mesa_id = ? AND r.fecha = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$_GET['mesa_check'], date('Y-m-d', strtotime($_GET['hora_check']))]);
        $count = $stmt->fetchColumn();
        if($count == 0) {
            $html .= "<div class='auth-msg auth-ok'>✅ Mesa disponible</div>";
        } else {
            $html .= "<div class='auth-msg auth-error'>❌ Mesa ocupada en esa fecha</div>";
        }
    }
    return $html;
}

function interfaz_validar_cupo($conn) {
    $sql = "SELECT COUNT(*) as ocupadas FROM horarios h 
            JOIN reservaciones r ON h.reservacion_id = r.id_reservacion 
            WHERE r.fecha = CURRENT_DATE";
    $res = procesar_query($sql, $conn);
    $ocupadas = $res->datos[0]['ocupadas'] ?? 0;
    $total_mesas = procesar_query("SELECT COUNT(*) as total FROM mesas", $conn)->datos[0]['total'] ?? 1;
    $disponibles = $total_mesas - $ocupadas;
    
    return "<h3>👁️ Cupo Total del Restaurante</h3>
            <div style='background:white; padding:30px; text-align:center; border-radius:16px;'>
                <p>Mesas ocupadas hoy: <strong>$ocupadas</strong></p>
                <p>Mesas disponibles: <strong>$disponibles</strong></p>
                <p>Total mesas: <strong>$total_mesas</strong></p>
            </div>";
}

function interfaz_reservaciones_proximas($conn) {
    $sql = "SELECT r.id_reservacion, u.nombre, r.fecha, r.numero_personas, m.id_mesa
            FROM reservaciones r
            JOIN usuarios u ON r.cliente_id = u.id_usuario
            LEFT JOIN horarios h ON r.id_reservacion = h.reservacion_id
            LEFT JOIN mesas m ON h.mesa_id = m.id_mesa
            WHERE r.fecha >= CURRENT_DATE
            ORDER BY r.fecha LIMIT 20";
    $res = procesar_query($sql, $conn);
    $html = "<h3>🕐 Próximas Reservaciones</h3><div class='funciones-grid'>";
    foreach($res->datos as $row) {
        $html .= "<div class='funcion-card'><h3>Reserva #{$row['id_reservacion']}</h3>
                  <p>Cliente: {$row['nombre']}</p><p>Fecha: {$row['fecha']}</p>
                  <p>Personas: {$row['numero_personas']}</p><p>Mesa: {$row['id_mesa']}</p></div>";
    }
    $html .= "</div>";
    return $html;
}

/* ==================== MESERO ==================== */
function interfaz_mesero($accion, $conn, $usuario_id) {
    $retorno = "<div class='role-header mesero-header'><div><h2>🍷 Mesero - " . ucfirst($accion) . "</h2></div></div>
                 <a href='?rol=mesero' class='btn-volver'>← Volver</a><br><br>";
    
    switch($accion) {
        case 'registrar':
            $retorno .= interfaz_registrar_pedido($conn, $usuario_id);
            break;
        case 'agregar':
            $retorno .= interfaz_agregar_items($conn);
            break;
        case 'estado':
            $retorno .= interfaz_actualizar_estado($conn);
            break;
        case 'entrega':
            $retorno .= interfaz_registrar_entrega($conn);
            break;
        case 'listos':
            $retorno .= interfaz_pedidos_listos($conn);
            break;
        default:
            $retorno .= "<p>Selecciona una acción.</p>";
    }
    return $retorno;
}

function interfaz_registrar_pedido($conn, $usuario_id) {
    $html = "<h3>📝 Registrar Nuevo Pedido</h3>
             <form method='POST' style='background:white; padding:20px; border-radius:16px;'>
                <div class='auth-field'><label>Mesa ID</label><input type='number' name='mesa_id' required></div>
                <div class='auth-field'><label>Plato ID</label><input type='number' name='plato_id' required></div>
                <div class='auth-field'><label>Cantidad</label><input type='number' name='cantidad' value='1' required></div>
                <button type='submit' name='crear_pedido' class='auth-btn'>Crear Pedido</button>
             </form>";
    
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_pedido'])) {
        $stmt = $conn->prepare("INSERT INTO pedidos (mesa_id, mesero_id, estado) VALUES (?, ?, 'en preparación') RETURNING id_pedido");
        $stmt->execute([$_POST['mesa_id'], $usuario_id]);
        $pedido_id = $stmt->fetchColumn();
        $stmt2 = $conn->prepare("INSERT INTO detalle_pedidos (pedido_id, plato_id, cantidad) VALUES (?, ?, ?)");
        $stmt2->execute([$pedido_id, $_POST['plato_id'], $_POST['cantidad']]);
        $html .= "<div class='auth-msg auth-ok'>✅ Pedido #$pedido_id creado</div>";
    }
    return $html;
}

function interfaz_agregar_items($conn) {
    $html = "<h3>➕ Agregar Ítems a Pedido Existente</h3>
             <form method='POST' style='background:white; padding:20px; border-radius:16px;'>
                <div class='auth-field'><label>Pedido ID</label><input type='number' name='pedido_id' required></div>
                <div class='auth-field'><label>Plato ID</label><input type='number' name='plato_id' required></div>
                <div class='auth-field'><label>Cantidad</label><input type='number' name='cantidad' value='1' required></div>
                <button type='submit' name='agregar_item' class='auth-btn'>Agregar</button>
             </form>";
    
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_item'])) {
        $stmt = $conn->prepare("INSERT INTO detalle_pedidos (pedido_id, plato_id, cantidad) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['pedido_id'], $_POST['plato_id'], $_POST['cantidad']]);
        $html .= "<div class='auth-msg auth-ok'>✅ Ítem agregado al pedido</div>";
    }
    return $html;
}

function interfaz_actualizar_estado($conn) {
    $html = "<h3>🔄 Actualizar Estado de Pedido</h3>
             <form method='POST' style='background:white; padding:20px; border-radius:16px;'>
                <div class='auth-field'><label>Pedido ID</label><input type='number' name='pedido_id' required></div>
                <div class='auth-field'><label>Nuevo Estado</label>
                    <select name='estado'><option>en preparación</option><option>listo</option><option>entregado</option></select>
                </div>
                <button type='submit' name='actualizar_estado' class='auth-btn'>Actualizar</button>
             </form>";
    
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
        $stmt = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id_pedido = ?");
        $stmt->execute([$_POST['estado'], $_POST['pedido_id']]);
        $html .= "<div class='auth-msg auth-ok'>✅ Estado actualizado</div>";
    }
    return $html;
}

function interfaz_registrar_entrega($conn) {
    $html = "<h3>🚀 Registrar Entrega de Pedido</h3>
             <form method='POST' style='background:white; padding:20px; border-radius:16px;'>
                <div class='auth-field'><label>Pedido ID</label><input type='number' name='pedido_id' required></div>
                <button type='submit' name='entregar' class='auth-btn'>Marcar como Entregado</button>
             </form>";
    
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entregar'])) {
        $stmt = $conn->prepare("UPDATE pedidos SET estado = 'entregado' WHERE id_pedido = ?");
        $stmt->execute([$_POST['pedido_id']]);
        $html .= "<div class='auth-msg auth-ok'>✅ Pedido entregado</div>";
    }
    return $html;
}

function interfaz_pedidos_listos($conn) {
    $sql = "SELECT id_pedido, mesa_id FROM pedidos WHERE estado = 'listo'";
    $res = procesar_query($sql, $conn);
    $html = "<h3>🔔 Pedidos Listos para Entregar</h3><div class='funciones-grid'>";
    foreach($res->datos as $pedido) {
        $html .= "<div class='funcion-card'><h3>Pedido #{$pedido['id_pedido']}</h3>
                  <p>Mesa: {$pedido['mesa_id']}</p>
                  <form method='POST'><input type='hidden' name='pedido_id' value='{$pedido['id_pedido']}'>
                  <button type='submit' name='entregar' class='auth-btn'>Entregar</button></form></div>";
    }
    $html .= "</div>";
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entregar'])) {
        $stmt = $conn->prepare("UPDATE pedidos SET estado = 'entregado' WHERE id_pedido = ?");
        $stmt->execute([$_POST['pedido_id']]);
        echo "<script>window.location='?rol=mesero&accion=listos';</script>";
    }
    return $html;
}

/* ==================== COCINERO ==================== */
function interfaz_cocinero($accion, $conn) {
    $retorno = "<div class='role-header cocinero-header'><div><h2>👨‍🍳 Cocinero - " . ucfirst($accion) . "</h2></div></div>
                 <a href='?rol=cocinero' class='btn-volver'>← Volver</a><br><br>";
    
    switch($accion) {
        case 'preparacion':
            $retorno .= interfaz_pedidos_preparacion($conn);
            break;
        case 'tiempo':
            $retorno .= interfaz_ordenar_tiempo($conn);
            break;
        case 'listo':
            $retorno .= interfaz_marcar_listo($conn);
            break;
        default:
            $retorno .= "<p>Selecciona una acción.</p>";
    }
    return $retorno;
}

function interfaz_pedidos_preparacion($conn) {
    $sql = "SELECT id_pedido, mesa_id FROM pedidos WHERE estado = 'en preparación'";
    $res = procesar_query($sql, $conn);
    $html = "<h3>🔥 Pedidos en Preparación</h3><div class='funciones-grid'>";
    foreach($res->datos as $pedido) {
        $html .= "<div class='funcion-card'><h3>Pedido #{$pedido['id_pedido']}</h3><p>Mesa: {$pedido['mesa_id']}</p></div>";
    }
    $html .= "</div>";
    return $html;
}

function interfaz_ordenar_tiempo($conn) {
    $sql = "SELECT id_pedido, mesa_id, created_at FROM pedidos WHERE estado = 'en preparación' ORDER BY created_at ASC";
    $res = procesar_query($sql, $conn);
    $html = "<h3>⏱️ Pedidos por Tiempo (más antiguos primero)</h3><div class='funciones-grid'>";
    foreach($res->datos as $pedido) {
        $html .= "<div class='funcion-card'><h3>Pedido #{$pedido['id_pedido']}</h3>
                  <p>Mesa: {$pedido['mesa_id']}</p><p>Creado: {$pedido['created_at']}</p></div>";
    }
    $html .= "</div>";
    return $html;
}

function interfaz_marcar_listo($conn) {
    $html = "<h3>✅ Marcar Pedido como Listo</h3>
             <form method='POST' style='background:white; padding:20px; border-radius:16px;'>
                <div class='auth-field'><label>Pedido ID</label><input type='number' name='pedido_id' required></div>
                <button type='submit' name='marcar_listo' class='auth-btn'>Marcar Listo</button>
             </form>";
    
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_listo'])) {
        $stmt = $conn->prepare("UPDATE pedidos SET estado = 'listo' WHERE id_pedido = ?");
        $stmt->execute([$_POST['pedido_id']]);
        $html .= "<div class='auth-msg auth-ok'>✅ Pedido marcado como listo</div>";
    }
    return $html;
}

/* ==================== CLIENTE ==================== */
function interfaz_cliente($accion, $conn, $usuario_id) {
    $retorno = "<div class='role-header cliente-header'><div><h2>🧑‍💼 Cliente