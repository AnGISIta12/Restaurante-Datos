<?php
/**
 * login.php — Procesador de formularios de autenticación con BD
 */

require_once 'etc/parametros.php';
require_once 'lib/auth.php';

// Exponer parámetros de BD en $GLOBALS para que fn_get_conn() los encuentre
$GLOBALS['host']     = $host;
$GLOBALS['dbname']   = $dbname;
$GLOBALS['user']     = $user;
$GLOBALS['password'] = $password;

fn_procesar_auth();
?>