<?php

$rol = $_GET['rol'] ?? 'principal';

function contenido($rol) {

    switch($rol) {

        case "administrador":
            return "
            <h2>Menú Administrador</h2>
            <ul>
                <li>Gestión de Mesas (Agregar / Modificar / Eliminar)</li>
                <li>Gestión del Menú</li>
                <li>Gestión de Empleados</li>
                <li>Reporte de Reservaciones</li>
                <li>Reporte de Pedidos Más Solicitados</li>
                <li>Reporte de Ventas Totales</li>
            </ul>
            <a href='index.php'>Volver al Menú Principal</a>
            ";

        case "maitre":
            return "
            <h2>Menú Maitre</h2>
            <ul>
                <li>Registrar Reservación</li>
                <li>Asignar Mesa</li>
                <li>Verificar Disponibilidad</li>
                <li>Validar Cupo del Restaurante</li>
                <li>Ver Reservaciones Próximas</li>
            </ul>
            <a href='index.php'>Volver al Menú Principal</a>
            ";

        case "mesero":
            return "
            <h2>Menú Mesero</h2>
            <ul>
                <li>Registrar Pedido</li>
                <li>Agregar Ítems al Pedido</li>
                <li>Actualizar Estado (En preparación / Listo / Entregado)</li>
                <li>Registrar Entrega</li>
                <li>Ver Notificaciones de Pedidos Listos</li>
            </ul>
            <a href='index.php'>Volver al Menú Principal</a>
            ";

        case "cocinero":
            return "
            <h2>Menú Cocinero</h2>
            <ul>
                <li>Ver Pedidos en Preparación</li>
                <li>Ver Pedidos Ordenados por Tiempo de Preparación</li>
                <li>Actualizar Pedido a 'Listo'</li>
            </ul>
            <a href='index.php'>Volver al Menú Principal</a>
            ";

        case "cliente":
            return "
            <h2>Menú Cliente</h2>
            <ul>
                <li>Solicitar Reservación</li>
                <li>Consultar Historial de Reservaciones</li>
                <li>Consultar Historial de Pedidos</li>
            </ul>
            <a href='index.php'>Volver al Menú Principal</a>
            ";

        default:
            return "
            <h2>Menú Principal</h2>
            <ul>
                <li><a href='?rol=administrador'>Administrador</a></li>
                <li><a href='?rol=maitre'>Maitre</a></li>
                <li><a href='?rol=mesero'>Mesero</a></li>
                <li><a href='?rol=cocinero'>Cocinero</a></li>
                <li><a href='?rol=cliente'>Cliente</a></li>
            </ul>
            ";
    }
}

$contenido = contenido($rol);
$esqueleto = file_get_contents("esqueleto.html");

echo sprintf($esqueleto, $contenido);

?>