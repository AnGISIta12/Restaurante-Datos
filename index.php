<?php

$rol = $_GET['rol'] ?? 'principal';

function mostrarContenido($rol) {

    switch($rol) {

        case "administrador":
            return "
            <h2>Panel Administrador</h2>
            <ul>
                <li>Gestionar Mesas</li>
                <li>Gestionar Menú</li>
                <li>Registrar Empleados</li>
                <li>Reporte de Ventas</li>
                <li>Reporte de Reservaciones</li>
            </ul>
            ";

        case "maitre":
            return "
            <h2>Panel Maitre</h2>
            <ul>
                <li>Registrar Reservación</li>
                <li>Asignar Mesa</li>
                <li>Validar Cupo</li>
                <li>Ver Reservaciones Próximas</li>
            </ul>
            ";

        case "mesero":
            return "
            <h2>Panel Mesero</h2>
            <ul>
                <li>Registrar Pedido</li>
                <li>Agregar Ítems</li>
                <li>Actualizar Estado</li>
                <li>Registrar Entrega</li>
            </ul>
            ";

        case "cocinero":
            return "
            <h2>Panel Cocinero</h2>
            <ul>
                <li>Ver Pedidos en Preparación</li>
                <li>Ordenar por Tiempo</li>
                <li>Marcar como Listo</li>
            </ul>
            ";

        case "cliente":
            return "
            <h2>Panel Cliente</h2>
            <ul>
                <li>Solicitar Reservación</li>
                <li>Consultar Historial</li>
            </ul>
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

$contenido = mostrarContenido($rol);

$plantilla = file_get_contents("esqueleto.html");
$plantilla = str_replace("{{contenido}}", $contenido, $plantilla);

echo $plantilla;

?>