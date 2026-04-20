<?php
/**
 * lib/acciones.php — Sistema de Gestión de Restaurante
 *
 * ordenes.estado       : 0=pendiente | 1=en preparación | 2=listo | 3=entregado
 * reservaciones.estado : 0=pendiente | 1=confirmada | 2=asignada
 */

function label_orden($e) {
    return match((int)$e) {
        0 => '<span style="color:#e67e22">⏳ Pendiente</span>',
        1 => '<span style="color:#3498db">🔥 En preparación</span>',
        2 => '<span style="color:#27ae60">✅ Listo</span>',
        3 => '<span style="color:#95a5a6">📦 Entregado</span>',
        default => "Estado $e"
    };
}
function label_reserva($e) {
    return match((int)$e) {
        0 => '<span style="color:#e67e22">⏳ Pendiente</span>',
        1 => '<span style="color:#3498db">✔️ Confirmada</span>',
        2 => '<span style="color:#27ae60">🪑 Asignada</span>',
        default => "Estado $e"
    };
}

function mostrar_interfaz($rol, $accion, $conn) {
    $id_usuario = $_SESSION['usuario_id'] ?? 0;
    $html = "<a href='?rol=$rol' class='btn-volver'>← Volver al Panel</a><br><br>";
    switch($rol) {
        case 'administrador': return $html . interfaz_admin($accion, $conn);
        case 'maitre':        return $html . interfaz_maitre($accion, $conn);
        case 'mesero':        return $html . interfaz_mesero($accion, $conn, $id_usuario);
        case 'cocinero':      return $html . interfaz_cocinero($accion, $conn, $id_usuario);
        case 'cliente':       return $html . interfaz_cliente($accion, $conn, $id_usuario);
        default:              return $html . "<p>Rol no reconocido.</p>";
    }
}

/* ══════════════════════════════════════════════════════════════════
   1. ADMINISTRADOR
══════════════════════════════════════════════════════════════════ */
function interfaz_admin($accion, $conn) {
    $html = "";
    switch($accion) {

        case 'admin_mesas':
            if (isset($_POST['nueva_mesa'])) {
                $conn->prepare("INSERT INTO mesas (sillas) VALUES (?)")->execute([$_POST['sillas']]);
                $html .= "<div class='auth-msg auth-success'>✅ Mesa añadida.</div>";
            }
            if (isset($_POST['modificar_mesa'])) {
                $conn->prepare("UPDATE mesas SET sillas = ? WHERE id_mesa = ?")->execute([$_POST['sillas'], $_POST['mesa_id']]);
                $html .= "<div class='auth-msg auth-success'>✏️ Mesa actualizada.</div>";
            }
            if (isset($_POST['eliminar_mesa'])) {
                $conn->prepare("DELETE FROM mesas WHERE id_mesa = ?")->execute([$_POST['mesa_id']]);
                $html .= "<div class='auth-msg auth-success'>🗑️ Mesa eliminada.</div>";
            }
            $mesas = procesar_query("SELECT * FROM mesas ORDER BY id_mesa", $conn);
            $cupo_total = procesar_query("SELECT COALESCE(SUM(sillas), 0) as total FROM mesas", $conn)->datos[0]['total'];
            $html .= "<h3>🪑 Gestión de Mesas</h3>
            <div class='card' style='margin-bottom:12px;background:#e8f8f5;border-left:4px solid #27ae60;'>
                <strong>Capacidad Total del Restaurante:</strong> <span style='font-size:1.2em;color:#27ae60;'>$cupo_total asientos</span>
            </div>
            <form method='POST' class='card' style='display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;'>
                <div class='auth-field' style='margin:0;flex:1;min-width:120px;'>
                    <label>Nº de sillas</label>
                    <input type='number' name='sillas' min='1' max='20' required>
                </div>
                <button type='submit' name='nueva_mesa' class='auth-btn' style='margin:0;'>+ Crear Mesa</button>
            </form>
            <div style='display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin-top:16px;'>";
            foreach($mesas->datos as $m) {
                $html .= "<div class='card' style='text-align:center;'>
                    <div style='font-size:2em;'>🪑</div>
                    <strong>Mesa {$m['id_mesa']}</strong>
                    <form method='POST' style='margin-top:8px; display:flex; flex-direction:column; gap:6px;'>
                        <input type='hidden' name='mesa_id' value='{$m['id_mesa']}'>
                        <div style='display:flex; align-items:center; justify-content:center; gap:6px;'>
                            <input type='number' name='sillas' value='{$m['sillas']}' min='1' max='50' style='width:60px; padding:4px;' required>
                            <small>sillas</small>
                        </div>
                        <div style='display:flex; gap:6px; justify-content:center; margin-top:4px;'>
                            <button type='submit' name='modificar_mesa'
                                style='background:#3498db;color:white;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-size:.8em;'>Guardar</button>
                            <button type='submit' name='eliminar_mesa'
                                style='background:#e74c3c;color:white;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-size:.8em;'
                                onclick=\"return confirm('¿Eliminar Mesa {$m['id_mesa']}?')\">Eliminar</button>
                        </div>
                    </form></div>";
            }
            $html .= "</div>";
            break;

        case 'admin_menu':
            if (isset($_POST['add_plato'])) {
                $conn->prepare("INSERT INTO platos (nombre, precio, descripcion, tipo_id, tiempo) VALUES (?,?,?,?,?)")
                     ->execute([$_POST['nom'], $_POST['pre'], $_POST['desc'], $_POST['tipo_id'], $_POST['tiempo']]);
                $html .= "<div class='auth-msg auth-success'>✅ Plato agregado al menú.</div>";
            }
            if (isset($_POST['eliminar_plato'])) {
                $conn->prepare("DELETE FROM platos WHERE id_plato = ?")->execute([$_POST['plato_id']]);
                $html .= "<div class='auth-msg auth-success'>🗑️ Plato eliminado.</div>";
            }
            $tipos  = procesar_query("SELECT * FROM tipos ORDER BY id", $conn);
            $platos = procesar_query("SELECT p.*, t.nombre as tipo FROM platos p JOIN tipos t ON p.tipo_id = t.id ORDER BY t.id, p.nombre", $conn);
            $html .= "<h3>🍽️ Gestión del Menú</h3>
            <form method='POST' class='card'>
                <h4 style='margin-top:0;'>Agregar nuevo plato</h4>
                <div style='display:grid;grid-template-columns:1fr 1fr;gap:10px;'>
                    <div class='auth-field' style='margin:0;'><label>Nombre del plato</label>
                        <input type='text' name='nom' placeholder='Ej: Pasta Carbonara' required></div>
                    <div class='auth-field' style='margin:0;'><label>Precio ($)</label>
                        <input type='number' step='0.01' name='pre' placeholder='0.00' required></div>
                    <div class='auth-field' style='margin:0;'><label>Categoría</label>
                        <select name='tipo_id' required>";
            foreach($tipos->datos as $t) $html .= "<option value='{$t['id']}'>{$t['nombre']}</option>";
            $html .= "  </select></div>
                    <div class='auth-field' style='margin:0;'><label>Tiempo prep. (HH:MM)</label>
                        <input type='text' name='tiempo' placeholder='00:30' pattern='[0-9]{2}:[0-9]{2}'></div>
                    <div class='auth-field' style='margin:0;grid-column:1/-1;'><label>Descripción</label>
                        <textarea name='desc' placeholder='Descripción del plato...' rows='2'></textarea></div>
                </div>
                <button type='submit' name='add_plato' class='auth-btn'>Agregar al Menú</button>
            </form>
            <h4>Platos actuales</h4>
            <table style='width:100%;background:white;border-radius:10px;border-collapse:collapse;'>
                <tr style='background:#f4f4f4;text-align:left;'>
                    <th style='padding:10px;'>Plato</th><th>Cat.</th><th>Precio</th><th>Tiempo</th><th></th>
                </tr>";
            foreach($platos->datos as $p) {
                $html .= "<tr style='border-top:1px solid #eee;'>
                    <td style='padding:10px;'><strong>{$p['nombre']}</strong><br>
                        <small style='color:#888;'>{$p['descripcion']}</small></td>
                    <td>{$p['tipo']}</td>
                    <td>$".number_format($p['precio'],2)."</td>
                    <td>{$p['tiempo']}</td>
                    <td><form method='POST' style='display:inline;'>
                        <input type='hidden' name='plato_id' value='{$p['id_plato']}'>
                        <button type='submit' name='eliminar_plato'
                            style='background:#e74c3c;color:white;border:none;padding:4px 8px;border-radius:4px;cursor:pointer;'
                            onclick=\"return confirm('¿Eliminar {$p['nombre']}?')\">🗑️</button>
                    </form></td></tr>";
            }
            $html .= "</table>";
            break;

        case 'admin_empleados':
            // Procesamiento de Envío de Formularios
            if (isset($_POST['nuevo_empleado'])) {
                $nombre = trim($_POST['nombre']);
                $pw = $_POST['password'];
                $rol_id = $_POST['rol_id'];
                
                $chk = $conn->prepare("SELECT 1 FROM usuarios WHERE nombre = ?");
                $chk->execute([$nombre]);
                if ($chk->fetchColumn()) {
                    $html .= "<div class='auth-msg auth-error'>❌ El usuario ya existe o el nombre no está disponible.</div>";
                } else {
                    $ins = $conn->prepare("INSERT INTO usuarios (nombre, clave, fecha_clave) VALUES (?, sha256((?)::bytea), NOW()) RETURNING id_usuario");
                    $ins->execute([$nombre, $pw]);
                    $uid = $ins->fetchColumn();
                    $conn->prepare("INSERT INTO actuaciones (rol_id, usuario_id) VALUES (?, ?)")->execute([$rol_id, $uid]);
                    $html .= "<div class='auth-msg auth-success'>✅ Empleado <strong>{$nombre}</strong> registrado correctamente.</div>";
                }
            }
            if (isset($_POST['guardar_rol'])) {
                $uid = $_POST['usuario_id'];
                $rol_id = $_POST['rol_id'];
                $conn->prepare("DELETE FROM actuaciones WHERE usuario_id = ?")->execute([$uid]);
                $conn->prepare("INSERT INTO actuaciones (rol_id, usuario_id) VALUES (?, ?)")->execute([$rol_id, $uid]);
                $html .= "<div class='auth-msg auth-success'>✏️ Rol de empleado actualizado.</div>";
            }
            if (isset($_POST['eliminar_empleado'])) {
                $uid = $_POST['usuario_id'];
                $conn->prepare("DELETE FROM actuaciones WHERE usuario_id = ?")->execute([$uid]);
                // Eliminación en cascada manual (podría fallar si este usuario tiene pedidos asociados sin un ON DELETE CASCADE o SET NULL en base de datos)
                // Se asume comportamiento genérico.
                $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?")->execute([$uid]);
                $html .= "<div class='auth-msg auth-success'>🗑️ Empleado eliminado.</div>";
            }

            // Filtrar sólo a los trabajadores del restaurante y no los clientes
            $sql = "SELECT u.id_usuario, u.nombre, r.nombre as rol, r.id_rol, u.fecha_clave
                    FROM usuarios u
                    JOIN actuaciones a ON u.id_usuario = a.usuario_id
                    JOIN roles r ON a.rol_id = r.id_rol
                    WHERE r.nombre != 'Cliente'
                    ORDER BY r.nombre, u.nombre";
            $empleados = procesar_query($sql, $conn);
            $roles = procesar_query("SELECT id_rol, nombre FROM roles WHERE nombre != 'Cliente' ORDER BY nombre", $conn);

            $iconos = ['Administrador'=>'⚙️','Maitre'=>'🎩','Mesero'=>'🍷','Cocinero'=>'👨‍🍳'];
            $html .= "<h3>👥 Gestión de Empleados</h3>
            
            <form method='POST' class='card' style='display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;background:#f9f9f9;'>
                <div class='auth-field' style='margin:0;flex:1;min-width:150px;'><label>Nombre del empleado</label>
                    <input type='text' name='nombre' required></div>
                <div class='auth-field' style='margin:0;flex:1;min-width:150px;'><label>Contraseña</label>
                    <input type='password' name='password' minlength='6' required></div>
                <div class='auth-field' style='margin:0;flex:1;'><label>Asignar Rol</label>
                    <select name='rol_id' required>";
            foreach($roles->datos as $r) {
                $html .= "<option value='{$r['id_rol']}'>{$r['nombre']}</option>";
            }
            $html .= "</select></div>
                <button type='submit' name='nuevo_empleado' class='auth-btn' style='margin:0;'>+ Registrar Empleado</button>
            </form>
            <br>
            <table style='width:100%;background:white;border-radius:10px;border-collapse:collapse;'>
                <tr style='background:#f4f4f4;text-align:left;'>
                    <th style='padding:10px;'>ID</th><th>Nombre</th><th>Modificar Rol</th><th>Registro</th><th>Acción</th>
                </tr>";
            foreach($empleados->datos as $e) {
                $icono = $iconos[$e['rol']] ?? '👤';
                $fecha = $e['fecha_clave'] ? date('d/m/Y', strtotime($e['fecha_clave'])) : '—';
                $html .= "<tr style='border-top:1px solid #eee;'>
                    <td style='padding:10px;color:#888;'>{$e['id_usuario']}</td>
                    <td><strong>{$e['nombre']}</strong><br><small>{$icono} {$e['rol']}</small></td>
                    <td>
                        <form method='POST' style='display:inline-flex; gap:6px;'>
                            <input type='hidden' name='usuario_id' value='{$e['id_usuario']}'>
                            <select name='rol_id' style='padding:4px;font-size:0.9em;border-radius:4px;'>";
                foreach($roles->datos as $r) {
                    $sel = ($r['id_rol'] == $e['id_rol']) ? 'selected' : '';
                    $html .= "<option value='{$r['id_rol']}' $sel>{$r['nombre']}</option>";
                }
                $html .= "    </select>
                            <button type='submit' name='guardar_rol' style='background:#3498db;color:white;border:none;padding:4px 8px;border-radius:4px;cursor:pointer;'>Guardar</button>
                        </form>
                    </td>
                    <td style='color:#888;font-size:.9em;'>$fecha</td>
                    <td>
                        <form method='POST' style='display:inline;'>
                            <input type='hidden' name='usuario_id' value='{$e['id_usuario']}'>
                            <button type='submit' name='eliminar_empleado'
                                style='background:#e74c3c;color:white;border:none;padding:6px 10px;border-radius:4px;cursor:pointer;'
                                onclick=\"return confirm('¿Estás seguro de ELIMINAR del sistema al empleado {$e['nombre']}? ¡Esta acción no se puede deshacer!')\">Eliminar</button>
                        </form>
                    </td>
                </tr>";
            }
            $html .= "</table><p style='color:#888;font-size:.85em;margin-top:12px;'>
                Total: {$empleados->cantidad} empleados activos (No se incluyen clientes).</p>";
            break;

        case 'admin_clientes':
            $sql = "SELECT u.id_usuario, u.nombre,
                           (SELECT COUNT(*) FROM reservaciones r WHERE r.cliente_id = u.id_usuario) as total_res,
                           (SELECT COUNT(*) FROM pedidos p WHERE p.cliente_id = u.id_usuario) as total_ped
                    FROM usuarios u
                    JOIN actuaciones a ON u.id_usuario = a.usuario_id
                    JOIN roles r ON a.rol_id = r.id_rol
                    WHERE r.nombre = 'Cliente'
                    ORDER BY u.nombre";
            $res = procesar_query($sql, $conn);
            $html .= "<h3>📖 Historial de Clientes</h3>
            <table style='width:100%;background:white;border-radius:10px;border-collapse:collapse;'>
                <tr style='background:#f4f4f4;text-align:left;'>
                    <th style='padding:10px;'>ID</th><th>Nombre del Cliente</th><th>Total Reservaciones</th><th>Total Pedidos</th>
                </tr>";
            foreach($res->datos as $r) {
                $html .= "<tr style='border-top:1px solid #eee;'>
                    <td style='padding:10px;color:#888;'>{$r['id_usuario']}</td>
                    <td><strong>{$r['nombre']}</strong></td>
                    <td style='text-align:center;font-weight:bold;'>{$r['total_res']}</td>
                    <td style='text-align:center;font-weight:bold;'>{$r['total_ped']}</td>
                </tr>";
            }
            $html .= "</table>
            <p style='color:#888;font-size:.85em;margin-top:12px;'>Total: {$res->cantidad} clientes registrados.</p>";
            break;

        case 'reporte_reservaciones':
            $sql = "SELECT r.id_reservacion, u.nombre as cliente, r.cantidad, r.estado,
                           h.inicio, m.id_mesa
                    FROM reservaciones r
                    JOIN usuarios u ON r.cliente_id = u.id_usuario
                    LEFT JOIN horarios h ON h.reservacion_id = r.id_reservacion
                    LEFT JOIN mesas m ON m.id_mesa = h.mesa_id
                    ORDER BY h.inicio DESC NULLS LAST, r.id_reservacion DESC";
            $res = procesar_query($sql, $conn);
            $html .= "<h3>📅 Reporte de Reservaciones</h3>
            <table style='width:100%;background:white;border-radius:10px;border-collapse:collapse;'>
                <tr style='background:#f4f4f4;text-align:left;'>
                    <th style='padding:10px;'>#</th><th>Cliente</th><th>Pers.</th>
                    <th>Mesa</th><th>Fecha/Hora</th><th>Estado</th>
                </tr>";
            foreach($res->datos as $r) {
                $mesa  = $r['id_mesa'] ? "Mesa {$r['id_mesa']}" : '—';
                $fecha = $r['inicio']  ? date('d/m/Y H:i', strtotime($r['inicio'])) : '—';
                $html .= "<tr style='border-top:1px solid #eee;'>
                    <td style='padding:10px;color:#888;'>{$r['id_reservacion']}</td>
                    <td>{$r['cliente']}</td><td style='text-align:center;'>{$r['cantidad']}</td>
                    <td>$mesa</td><td style='font-size:.9em;'>$fecha</td>
                    <td>".label_reserva($r['estado'])."</td></tr>";
            }
            $html .= "</table><p style='color:#888;font-size:.85em;margin-top:8px;'>Total: {$res->cantidad} reservaciones.</p>";
            break;

        case 'reporte_pedidos':
            $sql = "SELECT p.nombre, t.nombre as tipo,
                           COUNT(o.id_orden) as veces_pedido,
                           SUM(o.cantidad)   as unidades,
                           p.precio
                    FROM ordenes o
                    JOIN platos p ON o.plato_id = p.id_plato
                    JOIN tipos  t ON p.tipo_id  = t.id
                    GROUP BY p.id_plato, p.nombre, t.nombre, p.precio
                    ORDER BY unidades DESC LIMIT 20";
            $res = procesar_query($sql, $conn);
            $html .= "<h3>📊 Pedidos Más Solicitados</h3>
            <table style='width:100%;background:white;border-radius:10px;border-collapse:collapse;'>
                <tr style='background:#f4f4f4;text-align:left;'>
                    <th style='padding:10px;'>#</th><th>Plato</th><th>Categoría</th>
                    <th>Órdenes</th><th>Unidades</th><th>Precio</th>
                </tr>";
            $i = 1;
            foreach($res->datos as $r) {
                $medalla = match($i) { 1=>'🥇', 2=>'🥈', 3=>'🥉', default=>$i };
                $html .= "<tr style='border-top:1px solid #eee;'>
                    <td style='padding:10px;font-size:1.2em;'>$medalla</td>
                    <td><strong>{$r['nombre']}</strong></td>
                    <td style='color:#888;'>{$r['tipo']}</td>
                    <td style='text-align:center;font-weight:bold;'>{$r['veces_pedido']}</td>
                    <td style='text-align:center;'>{$r['unidades']}</td>
                    <td>$".number_format($r['precio'],2)."</td></tr>";
                $i++;
            }
            $html .= "</table>";
            break;

        case 'reporte_ventas':
            $sql = "SELECT p.nombre,
                           COUNT(o.id_orden)            as total_ordenes,
                           SUM(o.cantidad)              as unidades,
                           SUM(p.precio * o.cantidad)   as recaudado
                    FROM ordenes o
                    JOIN platos p ON o.plato_id = p.id_plato
                    GROUP BY p.nombre, p.precio
                    ORDER BY recaudado DESC";
            $res   = procesar_query($sql, $conn);
            $total = array_sum(array_column($res->datos, 'recaudado'));
            $html .= "<h3>💰 Reporte de Ventas por Plato</h3>
            <div class='card' style='display:inline-block;margin-bottom:16px;'>
                <span style='font-size:.9em;color:#888;'>TOTAL RECAUDADO</span><br>
                <span style='font-size:2em;font-weight:bold;color:#27ae60;'>$".number_format($total,0)."</span>
            </div>
            <table style='width:100%;background:white;border-radius:10px;border-collapse:collapse;'>
                <tr style='background:#f4f4f4;text-align:left;'>
                    <th style='padding:10px;'>Plato</th><th>Unidades</th><th>Órdenes</th><th>Total</th>
                </tr>";
            foreach($res->datos as $r) {
                $html .= "<tr style='border-top:1px solid #eee;'>
                    <td style='padding:10px;'>{$r['nombre']}</td>
                    <td style='text-align:center;'>{$r['unidades']}</td>
                    <td style='text-align:center;'>{$r['total_ordenes']}</td>
                    <td style='font-weight:bold;color:#27ae60;'>$".number_format($r['recaudado'],0)."</td></tr>";
            }
            $html .= "</table>";
            break;

        case 'desplegar_menu':
            return interfaz_desplegar_menu($conn);
    }
    return $html;
}

/* ══════════════════════════════════════════════════════════════════
   2. MAÎTRE
══════════════════════════════════════════════════════════════════ */
function interfaz_maitre($accion, $conn) {
    $html = "";

    // NOTIFICACIÓN: Reservaciones próximas a comenzar (siguientes 30 mins)
    $notif_res = procesar_query(
        "SELECT r.id_reservacion, u.nombre, h.inicio, m.id_mesa
         FROM reservaciones r
         JOIN usuarios u ON r.cliente_id = u.id_usuario
         JOIN horarios h ON r.id_reservacion = h.reservacion_id
         WHERE h.inicio BETWEEN NOW() AND NOW() + INTERVAL '30 minutes'
         ORDER BY h.inicio ASC", $conn);
         
    if ($notif_res->cantidad > 0) {
        $html .= "<div style='background:#f39c12; color:white; padding:12px; border-radius:8px; margin-bottom:16px; box-shadow:0 2px 4px rgba(0,0,0,0.1);'>";
        $html .= "<strong>🔔 ¡Atención Maître!</strong> Tienes {$notif_res->cantidad} reservación(es) próxima(s) a comenzar:<br><ul style='margin:5px 0 0 20px; padding:0;'>";
        foreach($notif_res->datos as $nr) {
            $hora = date('H:i', strtotime($nr['inicio']));
            $html .= "<li>Reserva #{$nr['id_reservacion']} de <strong>{$nr['nombre']}</strong> en <strong>Mesa {$nr['id_mesa']}</strong> a las $hora</li>";
        }
        $html .= "</ul></div>";
    }

    if ($accion == 'registrar') {
        if (isset($_POST['btn_reservar'])) {
            $conn->prepare("INSERT INTO reservaciones (cliente_id, cantidad, estado) VALUES (?,?,0)")
                 ->execute([$_POST['cliente_id'], $_POST['cantidad']]);
            $html .= "<div class='auth-msg auth-success'>✅ Reservación registrada correctamente.</div>";
        }
        $clientes = procesar_query(
            "SELECT u.id_usuario, u.nombre FROM usuarios u
             JOIN actuaciones a ON u.id_usuario = a.usuario_id
             JOIN roles r ON a.rol_id = r.id_rol
             WHERE r.nombre = 'Cliente' ORDER BY u.nombre", $conn);
        $html .= "<h3>✍️ Registrar Reservación</h3>
        <form method='POST' class='card'>
            <div class='auth-field'><label>Cliente</label>
                <select name='cliente_id' required>";
        foreach($clientes->datos as $c)
            $html .= "<option value='{$c['id_usuario']}'>{$c['nombre']}</option>";
        $html .= "  </select></div>
            <div class='auth-field'><label>Número de personas</label>
                <input type='number' name='cantidad' min='1' max='20' required></div>
            <button type='submit' name='btn_reservar' class='auth-btn'>Registrar Reservación</button>
        </form>";
        return $html;
    }

    if ($accion == 'asignar') {
        if (isset($_POST['btn_asignar'])) {
            $mesa_id = $_POST['mesa_id'];
            $res_id = $_POST['res_id'];

            // 1. Validar que no se supere el cupo total del restaurante
            $cupo_total = (int)procesar_query("SELECT COALESCE(SUM(sillas),0) as t FROM mesas", $conn)->datos[0]['t'];
            
            // Asientos ocupados en este momento (reserva que cruza las 2 horas de asignación estándar)
            $ocupados_actual = (int)procesar_query(
                "SELECT COALESCE(SUM(r.cantidad),0) as t 
                 FROM horarios h 
                 JOIN reservaciones r ON h.reservacion_id = r.id_reservacion 
                 WHERE h.inicio BETWEEN NOW() - INTERVAL '2 hours' AND NOW() + INTERVAL '2 hours'", $conn)->datos[0]['t'];
                 
            // Cupo que pretende emplear esta reserva
            $reserva_info = procesar_query("SELECT cantidad FROM reservaciones WHERE id_reservacion = $res_id", $conn)->datos[0];
            $personas_reserva = (int)$reserva_info['cantidad'];

            if (($ocupados_actual + $personas_reserva) > $cupo_total) {
                $html .= "<div class='auth-msg auth-error'>❌ <strong>ERROR:</strong> No se puede asignar esta mesa.<br>La sumatoria de las personas actuales ocupando mesas más esta reserva superaría el cupo total del restaurante (Máx: $cupo_total asientos en simultáneo).</div>";
            } else {
                $conn->prepare("INSERT INTO horarios (mesa_id, reservacion_id, inicio, duracion) VALUES (?,?,NOW(),'01:30:00')")
                     ->execute([$mesa_id, $res_id]);
                $conn->prepare("UPDATE reservaciones SET estado = 2 WHERE id_reservacion = ?")
                     ->execute([$res_id]);
                $html .= "<div class='auth-msg auth-success'>✅ Mesa asignada correctamente. Validado el cupo en el sistema.</div>";
            }
        }
        $reservas = procesar_query(
            "SELECT r.id_reservacion, u.nombre, r.cantidad
             FROM reservaciones r
             JOIN usuarios u ON r.cliente_id = u.id_usuario
             WHERE r.estado IN (0,1) ORDER BY r.id_reservacion", $conn);
        $html .= "<h3>🗺️ Asignar Mesa</h3>";
        if ($reservas->cantidad == 0) {
            $html .= "<div class='card'><p>No hay reservaciones pendientes de asignación.</p></div>";
            return $html;
        }
        foreach($reservas->datos as $r) {
            $mesas = procesar_query(
                "SELECT m.id_mesa, m.sillas FROM mesas m
                 WHERE m.id_mesa NOT IN (
                     SELECT mesa_id FROM horarios h
                     WHERE h.inicio BETWEEN NOW() - INTERVAL '2 hours' AND NOW() + INTERVAL '2 hours'
                 ) AND m.sillas >= {$r['cantidad']} ORDER BY m.sillas ASC", $conn);
            $html .= "<form method='POST' class='pedido-card'>
                <p><strong>{$r['nombre']}</strong> · {$r['cantidad']} personas
                   <small style='color:#888;'> · Reserva #{$r['id_reservacion']}</small></p>
                <input type='hidden' name='res_id' value='{$r['id_reservacion']}'>
                <div style='display:flex;gap:10px;align-items:center;flex-wrap:wrap;'>
                    <select name='mesa_id' class='auth-field' style='margin:0;flex:1;min-width:150px;' required>";
            if ($mesas->cantidad == 0) {
                $html .= "<option disabled>Sin mesas disponibles con capacidad suficiente</option>";
            } else {
                foreach($mesas->datos as $m)
                    $html .= "<option value='{$m['id_mesa']}'>Mesa {$m['id_mesa']} ({$m['sillas']} sillas)</option>";
            }
            $html .= "  </select>
                    <button type='submit' name='btn_asignar' class='auth-btn' style='margin:0;'
                        ".($mesas->cantidad==0?"disabled":"").">Asignar</button>
                </div></form><br>";
        }
        return $html;
    }

    if ($accion == 'verificar') {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $html .= "<h3>✅ Verificar Disponibilidad de Mesas</h3>
        <form method='GET' class='card' style='display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;'>
            <input type='hidden' name='rol' value='verificar'>
            <div class='auth-field' style='margin:0;flex:1;'>
                <label>Fecha a consultar</label>
                <input type='date' name='fecha' value='$fecha'>
            </div>
            <button type='submit' class='auth-btn' style='margin:0;'>Consultar</button>
        </form>";
        $sql = "SELECT m.id_mesa, m.sillas, h.inicio, h.duracion, u.nombre as cliente, r.cantidad as personas
                FROM mesas m
                LEFT JOIN horarios h ON h.mesa_id = m.id_mesa AND DATE(h.inicio) = '$fecha'
                LEFT JOIN reservaciones r ON r.id_reservacion = h.reservacion_id
                LEFT JOIN usuarios u ON u.id_usuario = r.cliente_id
                ORDER BY m.id_mesa, h.inicio";
        $res = procesar_query($sql, $conn);
        $mesas_map = [];
        foreach($res->datos as $fila) {
            $id = $fila['id_mesa'];
            if (!isset($mesas_map[$id])) $mesas_map[$id] = ['sillas'=>$fila['sillas'], 'ocup'=>[]];
            if ($fila['inicio']) $mesas_map[$id]['ocup'][] = $fila;
        }
        $html .= "<h4 style='margin-top:20px;'>Mesas para el ".date('d/m/Y', strtotime($fecha))."</h4>
        <div style='display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;'>";
        foreach($mesas_map as $id => $m) {
            $libre = empty($m['ocup']);
            $color = $libre ? '#27ae60' : '#e74c3c';
            $html .= "<div class='card' style='border-left:4px solid $color;'>
                <strong>Mesa $id</strong> · {$m['sillas']} sillas<br>
                <span style='color:$color;font-size:.85em;'>".($libre?'● Libre':'● Ocupada')."</span>";
            foreach($m['ocup'] as $oc) {
                $hora = date('H:i', strtotime($oc['inicio']));
                $html .= "<br><small style='color:#888;'>$hora · {$oc['cliente']} ({$oc['personas']} pers.)</small>";
            }
            $html .= "</div>";
        }
        $html .= "</div>";
        return $html;
    }

    if ($accion == 'cupo') {
        $cap       = (int)procesar_query("SELECT COALESCE(SUM(sillas),0) as t FROM mesas", $conn)->datos[0]['t'];
        $num_mesas = (int)procesar_query("SELECT COUNT(*) as t FROM mesas", $conn)->datos[0]['t'];
        $ocupadas  = (int)procesar_query(
            "SELECT COUNT(DISTINCT mesa_id) as t FROM horarios
             WHERE inicio BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '1 day'", $conn)->datos[0]['t'];
        $libres = $num_mesas - $ocupadas;
        $pct    = $num_mesas > 0 ? round($ocupadas / $num_mesas * 100) : 0;
        $bar_color = $pct > 80 ? '#e74c3c' : ($pct > 50 ? '#e67e22' : '#27ae60');
        $html .= "<h3>👁️ Capacidad del Restaurante (Hoy)</h3>
        <div style='display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;'>
            <div class='card' style='text-align:center;'>
                <div style='font-size:.85em;color:#888;'>CAPACIDAD TOTAL</div>
                <div style='font-size:2.5em;font-weight:bold;'>$cap</div>
                <div style='font-size:.85em;'>personas</div>
            </div>
            <div class='card' style='text-align:center;'>
                <div style='font-size:.85em;color:#888;'>MESAS OCUPADAS HOY</div>
                <div style='font-size:2.5em;font-weight:bold;color:#e74c3c;'>$ocupadas</div>
                <div style='font-size:.85em;'>de $num_mesas mesas</div>
            </div>
            <div class='card' style='text-align:center;'>
                <div style='font-size:.85em;color:#888;'>MESAS LIBRES</div>
                <div style='font-size:2.5em;font-weight:bold;color:#27ae60;'>$libres</div>
                <div style='font-size:.85em;'>disponibles</div>
            </div>
        </div>
        <div class='card'>
            <div style='display:flex;justify-content:space-between;margin-bottom:6px;'>
                <span>Ocupación del restaurante</span><strong>$pct%</strong>
            </div>
            <div style='background:#eee;border-radius:8px;height:18px;'>
                <div style='background:$bar_color;width:$pct%;height:18px;border-radius:8px;'></div>
            </div>
        </div>";
        $hoy = procesar_query(
            "SELECT m.id_mesa, u.nombre, r.cantidad, h.inicio, h.duracion
             FROM horarios h
             JOIN mesas m ON m.id_mesa = h.mesa_id
             JOIN reservaciones r ON r.id_reservacion = h.reservacion_id
             JOIN usuarios u ON u.id_usuario = r.cliente_id
             WHERE h.inicio BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '1 day'
             ORDER BY h.inicio", $conn);
        if ($hoy->cantidad > 0) {
            $html .= "<h4>Reservas de hoy</h4>
            <table style='width:100%;background:white;border-radius:10px;border-collapse:collapse;'>
                <tr style='background:#f4f4f4;'>
                    <th style='padding:8px;text-align:left;'>Mesa</th>
                    <th>Cliente</th><th>Personas</th><th>Hora</th><th>Duración</th>
                </tr>";
            foreach($hoy->datos as $h) {
                $html .= "<tr style='border-top:1px solid #eee;'>
                    <td style='padding:8px;'>Mesa {$h['id_mesa']}</td>
                    <td>{$h['nombre']}</td>
                    <td style='text-align:center;'>{$h['cantidad']}</td>
                    <td>".date('H:i',strtotime($h['inicio']))."</td>
                    <td>{$h['duracion']}</td></tr>";
            }
            $html .= "</table>";
        }
        return $html;
    }

    if ($accion == 'proximas') {
        $sql = "SELECT r.id_reservacion, u.nombre as cliente, r.cantidad, r.estado,
                       h.inicio, m.id_mesa
                FROM reservaciones r
                JOIN usuarios u ON r.cliente_id = u.id_usuario
                LEFT JOIN horarios h ON h.reservacion_id = r.id_reservacion
                LEFT JOIN mesas m ON m.id_mesa = h.mesa_id
                WHERE h.inicio >= NOW() OR r.estado IN (0,1)
                ORDER BY h.inicio ASC NULLS LAST LIMIT 30";
        $res  = procesar_query($sql, $conn);
        $html .= "<h3>🕐 Reservaciones Próximas</h3>";
        if ($res->cantidad == 0) {
            $html .= "<div class='card'><p>No hay reservaciones próximas.</p></div>";
        } else {
            $html .= "<table style='width:100%;background:white;border-radius:10px;border-collapse:collapse;'>
                <tr style='background:#f4f4f4;text-align:left;'>
                    <th style='padding:10px;'>#</th><th>Cliente</th><th>Pers.</th>
                    <th>Mesa</th><th>Fecha / Hora</th><th>Estado</th>
                </tr>";
            foreach($res->datos as $r) {
                $mesa  = $r['id_mesa'] ? "Mesa {$r['id_mesa']}" : '<span style="color:#e67e22;">Sin asignar</span>';
                $fecha = $r['inicio']  ? date('d/m/Y H:i', strtotime($r['inicio'])) : '—';
                $html .= "<tr style='border-top:1px solid #eee;'>
                    <td style='padding:10px;color:#888;'>{$r['id_reservacion']}</td>
                    <td><strong>{$r['cliente']}</strong></td>
                    <td style='text-align:center;'>{$r['cantidad']}</td>
                    <td>$mesa</td><td>$fecha</td>
                    <td>".label_reserva($r['estado'])."</td></tr>";
            }
            $html .= "</table>";
        }
        return $html;
    }

    return "<p>Acción no reconocida para Maître: '$accion'</p>";
}

/* ══════════════════════════════════════════════════════════════════
   3. MESERO
══════════════════════════════════════════════════════════════════ */
function interfaz_mesero($accion, $conn, $usuario_id) {
    $html_notif = "";

    // NOTIFICACIÓN: Platos listos para entregar
    $notif_listos = procesar_query(
        "SELECT COUNT(*) as total FROM ordenes o
         JOIN pedidos pe ON o.pedido_id = pe.id_pedido
         WHERE pe.mesero_id = $usuario_id AND o.estado = 2", $conn);
         
    if ($notif_listos->cantidad > 0 && $notif_listos->datos[0]['total'] > 0) {
        $total_listos = $notif_listos->datos[0]['total'];
        $html_notif .= "<div style='background:#27ae60; color:white; padding:12px; border-radius:8px; margin-bottom:16px; box-shadow:0 2px 4px rgba(0,0,0,0.1);'>";
        $html_notif .= "<strong>🔔 ¡Atención Mesero!</strong> Tienes $total_listos plato(s) esperando ser entregado(s).";
        $html_notif .= "</div>";
    }

    $out = _interfaz_mesero_impl($accion, $conn, $usuario_id);
    return $html_notif . $out;
}

function _interfaz_mesero_impl($accion, $conn, $usuario_id) {

    if ($accion == 'registrar') {
        if (isset($_POST['btn_abrir_pedido'])) {
            $stmt = $conn->prepare("INSERT INTO pedidos (cliente_id, mesero_id) VALUES (?,?) RETURNING id_pedido");
            $stmt->execute([$_POST['cliente_id'], $usuario_id]);
            $nuevo = $stmt->fetchColumn();
            return "<div class='auth-msg auth-success'>✅ Pedido #$nuevo abierto correctamente.</div>";
        }
        $clientes = procesar_query(
            "SELECT u.id_usuario, u.nombre FROM usuarios u
             JOIN actuaciones a ON u.id_usuario = a.usuario_id
             JOIN roles r ON a.rol_id = r.id_rol
             WHERE r.nombre = 'Cliente' ORDER BY u.nombre", $conn);
        $html = "<h3>📝 Abrir Comanda</h3>
        <form method='POST' class='card'>
            <div class='auth-field'><label>Cliente</label>
                <select name='cliente_id' required>";
        foreach($clientes->datos as $c)
            $html .= "<option value='{$c['id_usuario']}'>{$c['nombre']}</option>";
        $html .= "  </select></div>
            <button type='submit' name='btn_abrir_pedido' class='auth-btn'>Iniciar Pedido</button>
        </form>";
        return $html;
    }

    if ($accion == 'agregar') {
        if (isset($_POST['confirmar_item'])) {
            $conn->prepare("INSERT INTO ordenes (plato_id, pedido_id, estado, cantidad, solicitado) VALUES (?,?,0,?,NOW())")
                 ->execute([$_POST['plato_id'], $_POST['p_id'], $_POST['cant']]);
            return "<div class='auth-msg auth-success'>✅ Ítem agregado al pedido.</div>";
        }
        $pedidos = procesar_query(
            "SELECT pe.id_pedido, u.nombre as cliente
             FROM pedidos pe JOIN usuarios u ON pe.cliente_id = u.id_usuario
             WHERE pe.mesero_id = $usuario_id ORDER BY pe.id_pedido DESC LIMIT 20", $conn);
        $platos  = procesar_query("SELECT id_plato, nombre, precio FROM platos ORDER BY nombre", $conn);
        $html = "<h3>➕ Agregar Ítems al Pedido</h3>
        <form method='POST' class='card'>
            <div class='auth-field'><label>Pedido</label>
                <select name='p_id' required>";
        foreach($pedidos->datos as $p)
            $html .= "<option value='{$p['id_pedido']}'>Pedido #{$p['id_pedido']} — {$p['cliente']}</option>";
        $html .= "  </select></div>
            <div class='auth-field'><label>Plato</label>
                <select name='plato_id' required>";
        foreach($platos->datos as $p)
            $html .= "<option value='{$p['id_plato']}'>{$p['nombre']} — $".number_format($p['precio'],2)."</option>";
        $html .= "  </select></div>
            <div class='auth-field'><label>Cantidad</label>
                <input type='number' name='cant' value='1' min='1' max='20' required></div>
            <button type='submit' name='confirmar_item' class='auth-btn'>Añadir al Pedido</button>
        </form>";
        return $html;
    }

    if ($accion == 'estado') {
        if (isset($_POST['actualizar_estado'])) {
            $conn->prepare("UPDATE ordenes SET estado = ? WHERE id_orden = ?")
                 ->execute([(int)$_POST['nuevo_estado'], $_POST['orden_id']]);
            return "<div class='auth-msg auth-success'>✅ Estado actualizado.</div>
                    <a href='?rol=mesero&rol=estado' class='auth-btn' style='display:inline-block;margin-top:8px;'>Ver más órdenes</a>";
        }
        $sql = "SELECT o.id_orden, p.nombre as plato, o.cantidad, o.estado,
                       o.solicitado, pe.id_pedido
                FROM ordenes o
                JOIN platos p   ON o.plato_id  = p.id_plato
                JOIN pedidos pe ON o.pedido_id = pe.id_pedido
                WHERE pe.mesero_id = $usuario_id AND o.estado < 3
                ORDER BY o.solicitado ASC";
        $res  = procesar_query($sql, $conn);
        $html = "<h3>🔄 Actualizar Estado de Órdenes</h3>";
        if ($res->cantidad == 0) {
            $html .= "<div class='card'><p>No hay órdenes activas asignadas a ti.</p></div>";
            return $html;
        }
        foreach($res->datos as $o) {
            $hora  = date('H:i d/m', strtotime($o['solicitado']));
            $html .= "<div class='pedido-card'>
                <div style='display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;'>
                    <div>
                        <strong>Pedido #{$o['id_pedido']}</strong> · Orden #{$o['id_orden']}<br>
                        🍽️ {$o['plato']} × {$o['cantidad']} · <small>{$hora}</small><br>
                        ".label_orden($o['estado'])."
                    </div>
                    <form method='POST' style='display:flex;gap:6px;align-items:center;'>
                        <input type='hidden' name='orden_id' value='{$o['id_orden']}'>
                        <select name='nuevo_estado' class='auth-field' style='margin:0;padding:6px;'>
                            <option value='0'".($o['estado']==0?' selected':'').">⏳ Pendiente</option>
                            <option value='1'".($o['estado']==1?' selected':'').">🔥 En preparación</option>
                            <option value='2'".($o['estado']==2?' selected':'').">✅ Listo</option>
                            <option value='3'".($o['estado']==3?' selected':'').">📦 Entregado</option>
                        </select>
                        <button type='submit' name='actualizar_estado' class='auth-btn' style='margin:0;'>Guardar</button>
                    </form>
                </div></div><br>";
        }
        return $html;
    }

    if ($accion == 'entrega') {
        if (isset($_POST['confirmar_entrega'])) {
            $conn->prepare("UPDATE ordenes SET estado = 3 WHERE id_orden = ?")
                 ->execute([$_POST['orden_id']]);
            return "<div class='auth-msg auth-success'>✅ Entrega registrada.</div>";
        }
        $sql = "SELECT o.id_orden, p.nombre as plato, o.cantidad, pe.id_pedido, u.nombre as cliente
                FROM ordenes o
                JOIN platos p   ON o.plato_id  = p.id_plato
                JOIN pedidos pe ON o.pedido_id = pe.id_pedido
                JOIN usuarios u ON pe.cliente_id = u.id_usuario
                WHERE pe.mesero_id = $usuario_id AND o.estado = 2
                ORDER BY o.id_orden ASC";
        $res  = procesar_query($sql, $conn);
        $html = "<h3>🚀 Registrar Entrega</h3>";
        if ($res->cantidad == 0) {
            $html .= "<div class='card'><p>No hay platos listos para entregar ahora.</p></div>";
            return $html;
        }
        foreach($res->datos as $o) {
            $html .= "<div class='pedido-card' style='display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;'>
                <div><strong>Pedido #{$o['id_pedido']}</strong> · {$o['cliente']}<br>
                    🍽️ {$o['plato']} × {$o['cantidad']}
                    <span style='color:#27ae60;'> ✅ Listo</span></div>
                <form method='POST'>
                    <input type='hidden' name='orden_id' value='{$o['id_orden']}'>
                    <button type='submit' name='confirmar_entrega' class='auth-btn' style='margin:0;background:#27ae60;'>
                        Confirmar Entrega
                    </button>
                </form></div><br>";
        }
        return $html;
    }

    if ($accion == 'listos') {
        $sql = "SELECT o.id_orden, p.nombre as plato, o.cantidad, pe.id_pedido, u.nombre as cliente
                FROM ordenes o
                JOIN platos p   ON o.plato_id  = p.id_plato
                JOIN pedidos pe ON o.pedido_id = pe.id_pedido
                JOIN usuarios u ON pe.cliente_id = u.id_usuario
                WHERE pe.mesero_id = $usuario_id AND o.estado = 2
                ORDER BY o.id_orden ASC";
        $res  = procesar_query($sql, $conn);
        $html = "<h3>🔔 Platos Listos para Servir</h3>";
        if ($res->cantidad == 0) {
            $html .= "<div class='card'><p>🎉 Todo entregado. No hay platos pendientes.</p></div>";
        } else {
            $html .= "<div style='margin-bottom:12px;color:#e67e22;font-weight:bold;'>{$res->cantidad} plato(s) esperando ser servidos</div>";
            foreach($res->datos as $o) {
                $html .= "<div class='pedido-card' style='border-left:4px solid #27ae60;'>
                    <strong>Pedido #{$o['id_pedido']}</strong> · {$o['cliente']}<br>
                    🍽️ {$o['plato']} × {$o['cantidad']}
                    <span style='color:#27ae60;font-weight:bold;'> ✅ LISTO</span>
                </div><br>";
            }
        }
        return $html;
    }

    return "<p>Acción de Mesero no reconocida: '$accion'</p>";
}

/* ══════════════════════════════════════════════════════════════════
   4. COCINERO
══════════════════════════════════════════════════════════════════ */
function interfaz_cocinero($accion, $conn, $id_cocinero) {
    if (isset($_GET['listo'])) {
        $conn->prepare("UPDATE ordenes SET estado = 2 WHERE id_orden = ?")
             ->execute([(int)$_GET['listo']]);
    }

    if ($accion == 'preparacion' || $accion == 'listo') {
        $sql = "SELECT o.id_orden, p.nombre as plato, p.tiempo as t_prep,
                       o.cantidad, o.estado, o.solicitado, pe.id_pedido
                FROM ordenes o
                JOIN platos p   ON o.plato_id  = p.id_plato
                JOIN pedidos pe ON o.pedido_id = pe.id_pedido
                WHERE o.estado IN (0, 1)
                ORDER BY o.solicitado ASC";
        $res  = procesar_query($sql, $conn);
        $html = "<h3>🔥 Monitor de Cocina</h3>";
        if ($res->cantidad == 0) {
            $html .= "<div class='card'><p>🎉 No hay órdenes pendientes en cocina.</p></div>";
            return $html;
        }
        foreach($res->datos as $o) {
            $hora  = date('H:i', strtotime($o['solicitado']));
            $mins  = $o['t_prep'] ? substr($o['t_prep'], 3, 2) : '?';
            $color = ($o['estado'] == 1) ? '#3498db' : '#e67e22';
            $html .= "<div class='pedido-card' style='border-left:4px solid $color;'>
                <div style='display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;'>
                    <div>
                        <strong>Orden #{$o['id_orden']}</strong> · Pedido #{$o['id_pedido']}<br>
                        🍽️ {$o['plato']} × {$o['cantidad']} · ⏱️ ~{$mins} min · <small>{$hora}</small><br>
                        ".label_orden($o['estado'])."
                    </div>
                    <a href='?rol=cocinero&listo={$o['id_orden']}' class='auth-btn'
                       style='background:#27ae60;margin:0;'
                       onclick=\"return confirm('¿Marcar como listo?')\">✅ Marcar Listo</a>
                </div></div><br>";
        }
        return $html;
    }

    if ($accion == 'tiempo') {
        $sql = "SELECT o.id_orden, p.nombre as plato, p.tiempo as t_prep,
                       o.cantidad, o.estado, o.solicitado, pe.id_pedido
                FROM ordenes o
                JOIN platos p   ON o.plato_id  = p.id_plato
                JOIN pedidos pe ON o.pedido_id = pe.id_pedido
                WHERE o.estado IN (0, 1)
                ORDER BY p.tiempo ASC NULLS LAST, o.solicitado ASC";
        $res  = procesar_query($sql, $conn);
        $html = "<h3>⏱️ Órdenes por Tiempo de Preparación</h3>
        <p style='color:#888;font-size:.9em;'>Ordenadas de menor a mayor tiempo requerido.</p>";
        if ($res->cantidad == 0) {
            $html .= "<div class='card'><p>No hay órdenes activas.</p></div>";
            return $html;
        }
        $html .= "<table style='width:100%;background:white;border-radius:10px;border-collapse:collapse;'>
            <tr style='background:#f4f4f4;text-align:left;'>
                <th style='padding:10px;'>Orden</th><th>Plato</th><th>Cant.</th>
                <th>⏱️ Prep.</th><th>Estado</th><th>Acción</th>
            </tr>";
        foreach($res->datos as $o) {
            $html .= "<tr style='border-top:1px solid #eee;'>
                <td style='padding:10px;color:#888;'>#{$o['id_orden']}</td>
                <td><strong>{$o['plato']}</strong></td>
                <td style='text-align:center;'>{$o['cantidad']}</td>
                <td>{$o['t_prep']}</td>
                <td>".label_orden($o['estado'])."</td>
                <td><a href='?rol=cocinero&listo={$o['id_orden']}'
                       style='background:#27ae60;color:white;padding:4px 10px;border-radius:4px;text-decoration:none;font-size:.85em;'
                       onclick=\"return confirm('¿Marcar listo?')\">✅ Listo</a></td>
            </tr>";
        }
        $html .= "</table>";
        return $html;
    }

    return "<p>Acción de Cocinero no reconocida: '$accion'</p>";
}

/* ══════════════════════════════════════════════════════════════════
   5. CLIENTE
══════════════════════════════════════════════════════════════════ */
function interfaz_cliente($accion, $conn, $id_usuario) {
    switch($accion) {

        case 'reservar':
            if (isset($_POST['btn_reservar'])) {
                $conn->prepare("INSERT INTO reservaciones (cliente_id, cantidad, estado) VALUES (?,?,0)")
                     ->execute([$id_usuario, $_POST['cantidad']]);
                return "<div class='auth-msg auth-success'>✅ Reservación enviada. El maître te confirmará pronto.</div>";
            }
            return "<h3>📆 Solicitar Reservación</h3>
            <form method='POST' class='card'>
                <div class='auth-field'><label>Número de personas</label>
                    <input type='number' name='cantidad' min='1' max='20' placeholder='Ej: 4' required></div>
                <p style='color:#888;font-size:.85em;'>El maître verificará disponibilidad y asignará tu mesa.</p>
                <button type='submit' name='btn_reservar' class='auth-btn'>Enviar Reservación</button>
            </form>";

        case 'hist_reservaciones':
            $sql = "SELECT r.id_reservacion, r.cantidad, r.estado, h.inicio, m.id_mesa
                    FROM reservaciones r
                    LEFT JOIN horarios h ON h.reservacion_id = r.id_reservacion
                    LEFT JOIN mesas m ON m.id_mesa = h.mesa_id
                    WHERE r.cliente_id = $id_usuario
                    ORDER BY h.inicio DESC NULLS LAST, r.id_reservacion DESC";
            $res  = procesar_query($sql, $conn);
            $html = "<h3>📋 Mis Reservaciones</h3>";
            if ($res->cantidad == 0) {
                $html .= "<div class='card'><p>Aún no tienes reservaciones. <a href='?rol=reservar'>¡Haz una!</a></p></div>";
                return $html;
            }
            $html .= "<table style='width:100%;background:white;border-radius:10px;border-collapse:collapse;'>
                <tr style='background:#f4f4f4;text-align:left;'>
                    <th style='padding:10px;'>#</th><th>Personas</th><th>Mesa</th><th>Fecha</th><th>Estado</th>
                </tr>";
            foreach($res->datos as $r) {
                $mesa  = $r['id_mesa'] ? "Mesa {$r['id_mesa']}" : '—';
                $fecha = $r['inicio']  ? date('d/m/Y H:i', strtotime($r['inicio'])) : '—';
                $html .= "<tr style='border-top:1px solid #eee;'>
                    <td style='padding:10px;color:#888;'>{$r['id_reservacion']}</td>
                    <td style='text-align:center;'>{$r['cantidad']}</td>
                    <td>$mesa</td><td>$fecha</td>
                    <td>".label_reserva($r['estado'])."</td></tr>";
            }
            $html .= "</table>";
            return $html;

        case 'hist_pedidos':
            $sql = "SELECT pe.id_pedido,
                           COUNT(o.id_orden)           as num_items,
                           SUM(pl.precio * o.cantidad) as total,
                           MAX(o.solicitado)            as ultima_orden
                    FROM pedidos pe
                    JOIN ordenes o ON pe.id_pedido = o.pedido_id
                    JOIN platos pl  ON o.plato_id  = pl.id_plato
                    WHERE pe.cliente_id = $id_usuario
                    GROUP BY pe.id_pedido ORDER BY ultima_orden DESC";
            $stmt = $conn->prepare($sql); $stmt->execute();
            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $html = "<h3>🧾 Mis Pedidos</h3>";
            if (empty($pedidos)) {
                $html .= "<div class='card'><p>Aún no tienes pedidos registrados.</p></div>";
                return $html;
            }
            foreach($pedidos as $p) {
                $fecha = $p['ultima_orden'] ? date('d/m/Y H:i', strtotime($p['ultima_orden'])) : '—';
                $items = procesar_query(
                    "SELECT pl.nombre, o.cantidad, pl.precio, o.estado
                     FROM ordenes o JOIN platos pl ON o.plato_id = pl.id_plato
                     WHERE o.pedido_id = {$p['id_pedido']}", $conn);
                $html .= "<div class='pedido-card'>
                    <div style='display:flex;justify-content:space-between;align-items:center;'>
                        <strong>Pedido #{$p['id_pedido']}</strong>
                        <span style='font-size:1.2em;font-weight:bold;color:#27ae60;'>
                            $".number_format($p['total'],0)."
                        </span>
                    </div>
                    <small style='color:#888;'>$fecha · {$p['num_items']} ítem(s)</small>
                    <table style='width:100%;margin-top:8px;font-size:.9em;border-collapse:collapse;'>
                        <tr style='color:#888;'><td>Plato</td><td>Cant.</td><td>Precio</td><td>Estado</td></tr>";
                foreach($items->datos as $it) {
                    $html .= "<tr style='border-top:1px solid #f0f0f0;'>
                        <td style='padding:4px 0;'>{$it['nombre']}</td>
                        <td>×{$it['cantidad']}</td>
                        <td>$".number_format($it['precio'],2)."</td>
                        <td>".label_orden($it['estado'])."</td></tr>";
                }
                $html .= "</table></div><br>";
            }
            return $html;
    }
    return "<p>Acción de Cliente no reconocida: '$accion'</p>";
}

/* ══════════════════════════════════════════════════════════════════
   MENÚ PÚBLICO
══════════════════════════════════════════════════════════════════ */
function interfaz_desplegar_menu($conn) {
    $res  = procesar_query(
        "SELECT p.nombre, p.precio, p.descripcion, p.tiempo, t.nombre as categoria
         FROM platos p JOIN tipos t ON p.tipo_id = t.id
         ORDER BY t.id, p.nombre", $conn);
    $html = "<h3>📖 Nuestro Menú</h3>";
    $cat  = "";
    foreach($res->datos as $p) {
        if ($cat != $p['categoria']) {
            if ($cat) $html .= "</div>";
            $cat   = $p['categoria'];
            $icono = match($cat) { 'entrada'=>'🥗','plato fuerte'=>'🍽️','bebida'=>'🥤', default=>'🍴' };
            $html .= "<h4 style='margin-top:20px;text-transform:capitalize;'>$icono ".ucfirst($cat)."</h4>
                      <div style='display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px;'>";
        }
        $mins  = $p['tiempo'] ? substr($p['tiempo'], 3, 2).' min' : '';
        $html .= "<div class='pedido-card' style='position:relative;'>
            <strong>{$p['nombre']}</strong>
            <span style='position:absolute;top:12px;right:12px;font-weight:bold;color:#27ae60;'>
                $".number_format($p['precio'],2)."</span><br>
            <small style='color:#888;'>{$p['descripcion']}</small>".
            ($mins ? "<br><small style='color:#aaa;'>⏱️ $mins</small>" : "")."
        </div>";
    }
    if ($cat) $html .= "</div>";
    return $html;
}
