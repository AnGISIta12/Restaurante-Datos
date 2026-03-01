<?php

$rol = $_GET['rol'] ?? 'principal';

function contenido($rol) {
    switch($rol) {

        case "administrador":
            return '
            <div class="role-header admin-header">
                <div class="role-icon">⚙️</div>
                <h2>Panel Administrador</h2>
                <p class="role-desc">Control total del sistema</p>
            </div>
            <div class="funciones-grid">
                <div class="funcion-card" onclick="window.location=\'?rol=admin_mesas\'">
                    <div class="card-icon">🪑</div>
                    <h3>Gestión de Mesas</h3>
                    <p>Agregar, modificar y eliminar mesas del restaurante</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=admin_menu\'">
                    <div class="card-icon">🍽️</div>
                    <h3>Gestión del Menú</h3>
                    <p>Administrar platos, precios y categorías</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=admin_empleados\'">
                    <div class="card-icon">👥</div>
                    <h3>Gestión de Empleados</h3>
                    <p>Registrar y gestionar el personal del restaurante</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card reporte" onclick="window.location=\'?rol=reporte_reservaciones\'">
                    <div class="card-icon">📅</div>
                    <h3>Reporte de Reservaciones</h3>
                    <p>Visualizar historial y estado de reservaciones</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card reporte" onclick="window.location=\'?rol=reporte_pedidos\'">
                    <div class="card-icon">📊</div>
                    <h3>Pedidos Más Solicitados</h3>
                    <p>Ranking de platos con mayor demanda</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card reporte" onclick="window.location=\'?rol=reporte_ventas\'">
                    <div class="card-icon">💰</div>
                    <h3>Ventas Totales</h3>
                    <p>Reporte de ingresos y estadísticas de ventas</p>
                    <span class="card-arrow">→</span>
                </div>
            </div>
            <a href="index.php" class="btn-volver">← Volver al Menú Principal</a>
            ';

        case "maitre":
            return '
            <div class="role-header maitre-header">
                <div class="role-icon">🎩</div>
                <h2>Panel Maître</h2>
                <p class="role-desc">Gestión de reservaciones y asignación de mesas</p>
            </div>
            <div class="funciones-grid">
                <div class="funcion-card" onclick="window.location=\'?rol=maitre&accion=registrar\'">
                    <div class="card-icon">✍️</div>
                    <h3>Registrar Reservación</h3>
                    <p>Crear una nueva reservación para un cliente</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=maitre&accion=asignar\'">
                    <div class="card-icon">🗺️</div>
                    <h3>Asignar Mesa</h3>
                    <p>Asignar mesa según disponibilidad y capacidad</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=maitre&accion=verificar\'">
                    <div class="card-icon">✅</div>
                    <h3>Verificar Disponibilidad</h3>
                    <p>Comprobar que no se crucen reservaciones</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=maitre&accion=cupo\'">
                    <div class="card-icon">👁️</div>
                    <h3>Validar Cupo Total</h3>
                    <p>Verificar la capacidad total del restaurante</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=maitre&accion=proximas\'">
                    <div class="card-icon">🕐</div>
                    <h3>Reservaciones Próximas</h3>
                    <p>Consultar las reservaciones del día y semana</p>
                    <span class="card-arrow">→</span>
                </div>
            </div>
            <a href="index.php" class="btn-volver">← Volver al Menú Principal</a>
            ';

        case "mesero":
            return '
            <div class="role-header mesero-header">
                <div class="role-icon">🍷</div>
                <h2>Panel Mesero</h2>
                <p class="role-desc">Gestión de pedidos y atención a mesas</p>
            </div>
            <div class="funciones-grid">
                <div class="funcion-card" onclick="window.location=\'?rol=mesero&accion=registrar\'">
                    <div class="card-icon">📝</div>
                    <h3>Registrar Pedido</h3>
                    <p>Iniciar un nuevo pedido para una mesa</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=mesero&accion=agregar\'">
                    <div class="card-icon">➕</div>
                    <h3>Agregar Ítems</h3>
                    <p>Añadir múltiples platos a un pedido existente</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=mesero&accion=estado\'">
                    <div class="card-icon">🔄</div>
                    <h3>Actualizar Estado</h3>
                    <p>En preparación → Listo → Entregado</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=mesero&accion=entrega\'">
                    <div class="card-icon">🚀</div>
                    <h3>Registrar Entrega</h3>
                    <p>Confirmar la entrega de un pedido a la mesa</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card notif" onclick="window.location=\'?rol=mesero&accion=listos\'">
                    <div class="card-icon">🔔</div>
                    <h3>Pedidos Listos</h3>
                    <p>Ver notificaciones de pedidos listos para entregar</p>
                    <span class="card-arrow">→</span>
                    <span class="badge">3</span>
                </div>
            </div>
            <a href="index.php" class="btn-volver">← Volver al Menú Principal</a>
            ';

        case "cocinero":
            return '
            <div class="role-header cocinero-header">
                <div class="role-icon">👨‍🍳</div>
                <h2>Panel Cocinero</h2>
                <p class="role-desc">Gestión de la cocina y estado de pedidos</p>
            </div>
            <div class="funciones-grid">
                <div class="funcion-card" onclick="window.location=\'?rol=cocinero&accion=preparacion\'">
                    <div class="card-icon">🔥</div>
                    <h3>Pedidos en Preparación</h3>
                    <p>Ver todos los pedidos activos en la cocina</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=cocinero&accion=tiempo\'">
                    <div class="card-icon">⏱️</div>
                    <h3>Ordenar por Tiempo</h3>
                    <p>Ver pedidos ordenados por tiempo de preparación</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=cocinero&accion=listo\'">
                    <div class="card-icon">✅</div>
                    <h3>Marcar como Listo</h3>
                    <p>Actualizar el estado de un pedido a "Listo"</p>
                    <span class="card-arrow">→</span>
                </div>
            </div>
            <a href="index.php" class="btn-volver">← Volver al Menú Principal</a>
            ';

        case "cliente":
            return '
            <div class="role-header cliente-header">
                <div class="role-icon">🧑‍💼</div>
                <h2>Panel Cliente</h2>
                <p class="role-desc">Tu experiencia gastronómica personalizada</p>
            </div>
            <div class="funciones-grid">
                <div class="funcion-card" onclick="window.location=\'?rol=cliente&accion=reservar\'">
                    <div class="card-icon">📆</div>
                    <h3>Solicitar Reservación</h3>
                    <p>Reservar una mesa para tu próxima visita</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=cliente&accion=hist_reservaciones\'">
                    <div class="card-icon">📋</div>
                    <h3>Historial de Reservaciones</h3>
                    <p>Consultar todas tus reservaciones anteriores</p>
                    <span class="card-arrow">→</span>
                </div>
                <div class="funcion-card" onclick="window.location=\'?rol=cliente&accion=hist_pedidos\'">
                    <div class="card-icon">🧾</div>
                    <h3>Historial de Pedidos</h3>
                    <p>Revisar todos los pedidos que has realizado</p>
                    <span class="card-arrow">→</span>
                </div>
            </div>
            <a href="index.php" class="btn-volver">← Volver al Menú Principal</a>
            ';

        default:
            return '
            <div class="principal-hero">
                <div class="hero-tagline">Bienvenido al sistema</div>
                <h1 class="hero-title">La Chula</h1>
                <p class="hero-sub">Selecciona tu perfil para continuar</p>
            </div>
            <div class="roles-grid">
                <a href="?rol=administrador" class="rol-card rol-admin">
                    <div class="rol-emoji">⚙️</div>
                    <span class="rol-nombre">Administrador</span>
                    <span class="rol-desc">Control total</span>
                </a>
                <a href="?rol=maitre" class="rol-card rol-maitre">
                    <div class="rol-emoji">🎩</div>
                    <span class="rol-nombre">Maître</span>
                    <span class="rol-desc">Reservaciones</span>
                </a>
                <a href="?rol=mesero" class="rol-card rol-mesero">
                    <div class="rol-emoji">🍷</div>
                    <span class="rol-nombre">Mesero</span>
                    <span class="rol-desc">Pedidos y mesas</span>
                </a>
                <a href="?rol=cocinero" class="rol-card rol-cocinero">
                    <div class="rol-emoji">👨‍🍳</div>
                    <span class="rol-nombre">Cocinero</span>
                    <span class="rol-desc">Cocina</span>
                </a>
                <a href="?rol=cliente" class="rol-card rol-cliente">
                    <div class="rol-emoji">🧑‍💼</div>
                    <span class="rol-nombre">Cliente</span>
                    <span class="rol-desc">Mi experiencia</span>
                </a>
            </div>
            ';
    }
}

$contenido = contenido($rol);
$esqueleto = file_get_contents("esqueleto.html");
echo sprintf($esqueleto, $contenido);
?>