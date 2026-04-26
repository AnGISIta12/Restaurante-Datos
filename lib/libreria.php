<?php

/*------------------------------------------------------------------*/
/**
 * Establece una conexión PDO con PostgreSQL.
 */
function pg_conectar($host, $dbname, $user, $password = '')
{
    try {
        $dsn  = "pgsql:host=$host;dbname=$dbname";
        $conn = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        return $conn;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

/*------------------------------------------------------------------*/
function Mostrar($variable)
{
    $retorno = "<pre>".var_export($variable, true)."</pre>";
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * Ejecuta una sentencia SQL y devuelve los resultados en un objeto.
 */
function procesar_query($sentencia, $conexion)
{
    try {
        $stmt = $conexion->query($sentencia);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return (object) ['cantidad' => count($datos), 'datos' => $datos];
    } catch (PDOException $e) {
        die("Error en query: " . $e->getMessage());
    }
}
?>
