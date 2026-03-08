<?php
/*------------------------------------------------------------------*/
/**
 * @brief Genera el formulario de autenticación (login + registro).
 * @return string HTML completo del formulario de auth.
 */
function fn_formulario_auth()
/*--------------------------------------------------------------------*/
{
    $msg = '';
    if (isset($_GET['error'])) {
        $msg = match($_GET['error']) {
            'credenciales' => '<div class="auth-msg auth-error">❌ Usuario o contraseña incorrectos.</div>',
            'usuario_existe'=> '<div class="auth-msg auth-error">❌ Ese correo ya está registrado.</div>',
            'campos'       => '<div class="auth-msg auth-error">❌ Por favor completa todos los campos.</div>',
            default        => ''
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
            <button class="auth-tab active" onclick="switchTab(\'login\', this)">Iniciar Sesión</button>
            <button class="auth-tab" onclick="switchTab(\'registro\', this)">Crear Cuenta</button>
        </div>

        <!-- LOGIN -->
        <div id="tab-login" class="auth-form-wrap active">
            <form method="POST" action="auth.php" class="auth-form">
                <input type="hidden" name="accion" value="login" />
                <div class="auth-field">
                    <label>Correo electrónico</label>
                    <input type="email" name="correo" placeholder="tu@correo.com" required autocomplete="email" />
                </div>
                <div class="auth-field">
                    <label>Contraseña</label>
                    <div class="input-pw">
                        <input type="password" name="password" id="pw-login" placeholder="••••••••" required />
                        <button type="button" class="toggle-pw" onclick="togglePw(\'pw-login\')">👁</button>
                    </div>
                </div>
                <div class="auth-field">
                    <label>Ingresar como</label>
                    <select name="rol" required>
                        <option value="">— Selecciona tu rol —</option>
                        <option value="administrador">⚙️ Administrador</option>
                        <option value="maitre">🎩 Maître</option>
                        <option value="mesero">🍷 Mesero</option>
                        <option value="cocinero">👨‍🍳 Cocinero</option>
                        <option value="cliente">🧑‍💼 Cliente</option>
                    </select>
                </div>
                <button type="submit" class="auth-btn">Entrar →</button>
            </form>
        </div>

        <!-- REGISTRO -->
        <div id="tab-registro" class="auth-form-wrap">
            <form method="POST" action="auth.php" class="auth-form">
                <input type="hidden" name="accion" value="registro" />
                <div class="auth-row">
                    <div class="auth-field">
                        <label>Nombre</label>
                        <input type="text" name="nombre" placeholder="Tu nombre" required />
                    </div>
                    <div class="auth-field">
                        <label>Apellido</label>
                        <input type="text" name="apellido" placeholder="Tu apellido" required />
                    </div>
                </div>
                <div class="auth-field">
                    <label>Correo electrónico</label>
                    <input type="email" name="correo" placeholder="tu@correo.com" required autocomplete="email" />
                </div>
                <div class="auth-field">
                    <label>Contraseña</label>
                    <div class="input-pw">
                        <input type="password" name="password" id="pw-reg" placeholder="Mínimo 6 caracteres" required minlength="6" />
                        <button type="button" class="toggle-pw" onclick="togglePw(\'pw-reg\')">👁</button>
                    </div>
                </div>
                <div class="auth-field">
                    <label>Confirmar contraseña</label>
                    <div class="input-pw">
                        <input type="password" name="password2" id="pw-reg2" placeholder="Repite la contraseña" required />
                        <button type="button" class="toggle-pw" onclick="togglePw(\'pw-reg2\')">👁</button>
                    </div>
                </div>
                <div class="auth-field">
                    <label>Rol</label>
                    <select name="rol" required>
                        <option value="">— Selecciona tu rol —</option>
                        <option value="administrador">⚙️ Administrador</option>
                        <option value="maitre">🎩 Maître</option>
                        <option value="mesero">🍷 Mesero</option>
                        <option value="cocinero">👨‍🍳 Cocinero</option>
                        <option value="cliente">🧑‍💼 Cliente</option>
                    </select>
                </div>
                <button type="submit" class="auth-btn">Crear cuenta →</button>
            </form>
        </div>
    </div>
    ';
}

/*------------------------------------------------------------------*/
/**
 * @brief Procesa el formulario POST de login o registro.
 * @param resource $conn Conexión activa a PostgreSQL.
 * @return void Redirige según resultado.
 */
function fn_procesar_auth($conn)
/*--------------------------------------------------------------------*/
{
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'login') {
        $correo = pg_escape_string($conn, trim($_POST['correo'] ?? ''));
        $pw     = $_POST['password'] ?? '';
        $rol    = pg_escape_string($conn, $_POST['rol'] ?? '');

        if (!$correo || !$pw || !$rol) {
            header('Location: index.php?error=campos'); exit;
        }

        $res = pg_query($conn, "SELECT id, nombre, password_hash, rol FROM usuarios WHERE correo='$correo' LIMIT 1");
        $usr = pg_fetch_assoc($res);

        if (!$usr || !password_verify($pw, $usr['password_hash'])) {
            header('Location: index.php?error=credenciales'); exit;
        }

        session_start();
        $_SESSION['usuario_id']   = $usr['id'];
        $_SESSION['usuario_nombre'] = $usr['nombre'];
        $_SESSION['rol']          = $rol;
        header('Location: index.php?rol=' . urlencode($rol)); exit;

    } elseif ($accion === 'registro') {
        $nombre   = pg_escape_string($conn, trim($_POST['nombre']   ?? ''));
        $apellido = pg_escape_string($conn, trim($_POST['apellido'] ?? ''));
        $correo   = pg_escape_string($conn, trim($_POST['correo']   ?? ''));
        $pw       = $_POST['password']  ?? '';
        $pw2      = $_POST['password2'] ?? '';
        $rol      = pg_escape_string($conn, $_POST['rol'] ?? '');

        if (!$nombre || !$apellido || !$correo || !$pw || !$rol) {
            header('Location: index.php?error=campos'); exit;
        }
        if ($pw !== $pw2) {
            header('Location: index.php?error=pw_no_coincide'); exit;
        }

        // Verificar si ya existe
        $check = pg_query($conn, "SELECT id FROM usuarios WHERE correo='$correo' LIMIT 1");
        if (pg_num_rows($check) > 0) {
            header('Location: index.php?error=usuario_existe'); exit;
        }

        $hash = password_hash($pw, PASSWORD_DEFAULT);
        pg_query($conn, "INSERT INTO usuarios (nombre, apellido, correo, password_hash, rol)
                         VALUES ('$nombre','$apellido','$correo','$hash','$rol')");

        header('Location: index.php?ok=registro'); exit;
    }
}
?>