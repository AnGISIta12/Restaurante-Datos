<?php
/*
 * auth.php — Autenticación con PDO contra PostgreSQL.
 */

function fn_get_conn()
{
    if (!isset($GLOBALS['host'])) return null;
    try {
        $dsn  = "pgsql:host={$GLOBALS['host']};dbname={$GLOBALS['dbname']}";
        $conn = new PDO($dsn, $GLOBALS['user'], $GLOBALS['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        return $conn;
    } catch (PDOException $e) {
        return null;
    }
}

function fn_formulario_auth()
{
    $tab = $_GET['tab'] ?? 'login';

    $msg = '';
    if (isset($_GET['error'])) {
        $msg = match($_GET['error']) {
            'credenciales'   => '<div class="auth-msg auth-error">❌ Usuario o contraseña incorrectos.</div>',
            'usuario_existe' => '<div class="auth-msg auth-error">❌ Ese nombre de usuario ya está registrado.</div>',
            'campos'         => '<div class="auth-msg auth-error">❌ Por favor completa todos los campos.</div>',
            'pw_no_coincide' => '<div class="auth-msg auth-error">❌ Las contraseñas no coinciden.</div>',
            'db_error'       => '<div class="auth-msg auth-error">❌ Error de base de datos. Contacta al administrador.</div>',
            default          => ''
        };
    }
    if (isset($_GET['ok']) && $_GET['ok'] === 'registro') {
        $msg = '<div class="auth-msg auth-ok">✅ Cuenta creada. Ahora inicia sesión.</div>';
    }

    return '
    <div class="auth-container">
        <div class="auth-brand">
            <div class="auth-logo">🍽️</div>
            <h1 class="auth-title">La Chula</h1>
            <p class="auth-sub">Sistema de Gestión · Restaurante</p>
        </div>

        ' . $msg . '

        <div class="auth-tabs">
            <a href="?tab=login"
               class="auth-tab ' . ($tab === 'login' ? 'active' : '') . '">
               Iniciar Sesión
            </a>
            <a href="?tab=registro"
               class="auth-tab ' . ($tab === 'registro' ? 'active' : '') . '">
               Crear Cuenta
            </a>
        </div>

        ' . ($tab === 'login' ? '
        <div class="auth-form-wrap active">
            <form method="POST" action="login.php" class="auth-form">
                <input type="hidden" name="accion" value="login" />
                <div class="auth-field">
                    <label>Nombre de usuario</label>
                    <input type="text" name="nombre" placeholder="Ej: Ana Garcia" required autocomplete="username" />
                </div>
                <div class="auth-field">
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="••••••••" required />
                </div>
                <button type="submit" class="auth-btn">Entrar →</button>
            </form>
        </div>
        ' : '
        <div class="auth-form-wrap active">
            <form method="POST" action="login.php" class="auth-form">
                <input type="hidden" name="accion" value="registro" />
                <div class="auth-field">
                    <label>Nombre de usuario</label>
                    <input type="text" name="nombre" placeholder="Ej: Maria Gomez" required />
                </div>
                <div class="auth-field">
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="Mínimo 6 caracteres" required minlength="6" />
                </div>
                <div class="auth-field">
                    <label>Confirmar contraseña</label>
                    <input type="password" name="password2" placeholder="Repite la contraseña" required />
                </div>
                <div class="auth-field">
                    <label>Rol</label>
                    <select name="rol_nombre" required>
                        <option value="">— Selecciona tu rol —</option>
                        <option value="Administrador">⚙️ Administrador</option>
                        <option value="Maitre">🎩 Maître</option>
                        <option value="Mesero">🍷 Mesero</option>
                        <option value="Cocinero">👨‍🍳 Cocinero</option>
                        <option value="Cliente">🧑‍💼 Cliente</option>
                    </select>
                </div>
                <button type="submit" class="auth-btn">Crear cuenta →</button>
            </form>
        </div>
        ') . '
    </div>
    ';
}

function fn_procesar_auth()
{
    session_start();

    $accion = $_POST['accion'] ?? '';

    /* ---- LOGIN ---- */
    if ($accion === 'login') {
        $nombre = trim($_POST['nombre'] ?? '');
        $pw     = $_POST['password']   ?? '';

        if (!$nombre || !$pw) {
            header('Location: index.php?tab=login&error=campos'); exit;
        }

        $conn = fn_get_conn();
        if (!$conn) {
            header('Location: index.php?tab=login&error=db_error'); exit;
        }

        $sql = "
            SELECT u.id_usuario, u.nombre,
                   COALESCE(r.nombre, 'Cliente') AS rol
            FROM usuarios u
            LEFT JOIN actuaciones a ON a.usuario_id = u.id_usuario
            LEFT JOIN roles       r ON r.id_rol     = a.rol_id
            WHERE u.nombre = :nombre
              AND encode(u.clave, 'hex') = encode(sha256((:pw)::bytea), 'hex')
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([':nombre' => $nombre, ':pw' => $pw]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            header('Location: index.php?tab=login&error=credenciales'); exit;
        }

        $_SESSION['usuario_id']     = $fila['id_usuario'];
        $_SESSION['usuario_nombre'] = $fila['nombre'];
        $_SESSION['rol']            = $fila['rol'];

        header('Location: index.php?rol=' . urlencode($fila['rol'])); exit;

    /* ---- REGISTRO ---- */
    } elseif ($accion === 'registro') {
        $nombre     = trim($_POST['nombre']     ?? '');
        $pw         = $_POST['password']        ?? '';
        $pw2        = $_POST['password2']       ?? '';
        $rol_nombre = trim($_POST['rol_nombre'] ?? '');

        if (!$nombre || !$pw || !$rol_nombre) {
            header('Location: index.php?tab=registro&error=campos'); exit;
        }
        if ($pw !== $pw2) {
            header('Location: index.php?tab=registro&error=pw_no_coincide'); exit;
        }

        $conn = fn_get_conn();
        if (!$conn) {
            header('Location: index.php?tab=registro&error=db_error'); exit;
        }

        // Verificar si el nombre ya existe
        $chk = $conn->prepare("SELECT 1 FROM usuarios WHERE nombre = :nombre");
        $chk->execute([':nombre' => $nombre]);
        if ($chk->fetch()) {
            header('Location: index.php?tab=registro&error=usuario_existe'); exit;
        }

        // Obtener id del rol
        $rres = $conn->prepare("SELECT id_rol FROM roles WHERE nombre = :rol");
        $rres->execute([':rol' => $rol_nombre]);
        $rol_id = $rres->fetchColumn();
        if (!$rol_id) {
            header('Location: index.php?tab=registro&error=db_error'); exit;
        }

        // Insertar usuario con sha256
        $ins = $conn->prepare(
            "INSERT INTO usuarios (nombre, clave, fecha_clave)
             VALUES (:nombre, sha256((:pw)::bytea), NOW())
             RETURNING id_usuario"
        );
        $ins->execute([':nombre' => $nombre, ':pw' => $pw]);
        $nuevo_id = $ins->fetchColumn();

        if (!$nuevo_id) {
            header('Location: index.php?tab=registro&error=db_error'); exit;
        }

        // Insertar en actuaciones
        $act = $conn->prepare(
            "INSERT INTO actuaciones (rol_id, usuario_id) VALUES (:rol_id, :uid)"
        );
        $act->execute([':rol_id' => $rol_id, ':uid' => $nuevo_id]);

        header('Location: index.php?tab=login&ok=registro'); exit;
    }
}
?>
