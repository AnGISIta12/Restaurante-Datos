<?php
/*------------------------------------------------------------------*/
/**
 * @brief Usuarios de prueba hardcodeados (sin BD por ahora).
 *        Formato: 'nombre' => ['password' => '...', 'rol' => '...']
 */
$USUARIOS_PRUEBA = [
    'admin'    => ['password' => '1234', 'rol' => 'administrador'],
    'maitre'   => ['password' => '1235', 'rol' => 'maitre'],
    'mesero'   => ['password' => '1236', 'rol' => 'mesero'],
    'cocinero' => ['password' => '1237', 'rol' => 'cocinero'],
    'cliente'  => ['password' => '1238', 'rol' => 'cliente'],
];

/*------------------------------------------------------------------*/
/**
 * @brief Genera el formulario de autenticación (login + registro).
 * @return string HTML completo del formulario.
 */
function fn_formulario_auth()
/*--------------------------------------------------------------------*/
{
    $msg = '';
    if (isset($_GET['error'])) {
        $msg = match($_GET['error']) {
            'credenciales'   => '<div class="auth-msg auth-error">❌ Usuario o contraseña incorrectos.</div>',
            'usuario_existe' => '<div class="auth-msg auth-error">❌ Ese nombre de usuario ya está registrado.</div>',
            'campos'         => '<div class="auth-msg auth-error">❌ Por favor completa todos los campos.</div>',
            'pw_no_coincide' => '<div class="auth-msg auth-error">❌ Las contraseñas no coinciden.</div>',
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
            <button class="auth-tab active" onclick="switchTab(\'login\', this)">Iniciar Sesión</button>
            <button class="auth-tab" onclick="switchTab(\'registro\', this)">Crear Cuenta</button>
        </div>

        <!-- LOGIN -->
        <div id="tab-login" class="auth-form-wrap active">
            <form method="POST" action="login.php" class="auth-form">
                <input type="hidden" name="accion" value="login" />
                <div class="auth-field">
                    <label>Nombre de usuario</label>
                    <input type="text" name="nombre" placeholder="Ej: admin" required autocomplete="username" />
                </div>
                <div class="auth-field">
                    <label>Contraseña</label>
                    <div class="input-pw">
                        <input type="password" name="password" id="pw-login" placeholder="••••••••" required />
                        <button type="button" class="toggle-pw" onclick="togglePw(\'pw-login\')">👁</button>
                    </div>
                </div>
                <button type="submit" class="auth-btn">Entrar →</button>
            </form>
        </div>

        <!-- REGISTRO -->
        <div id="tab-registro" class="auth-form-wrap">
            <form method="POST" action="login.php" class="auth-form">
                <input type="hidden" name="accion" value="registro" />
                <div class="auth-field">
                    <label>Nombre de usuario</label>
                    <input type="text" name="nombre" placeholder="Ej: maria_gomez" required />
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
                    <select name="rol_nombre" required>
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
 * @brief Procesa login y registro SIN base de datos.
 *        Los usuarios registrados se guardan en $_SESSION['usuarios_registrados']
 *        para que persistan durante la sesión.
 */
function fn_procesar_auth()
/*--------------------------------------------------------------------*/
{
    global $USUARIOS_PRUEBA;

    session_start();

    // Combinar usuarios hardcodeados + los registrados en esta sesión
    $usuarios_sesion = $_SESSION['usuarios_registrados'] ?? [];
    $todos = array_merge($USUARIOS_PRUEBA, $usuarios_sesion);

    $accion = $_POST['accion'] ?? '';

    /* ── LOGIN ──────────────────────────────────────────────── */
    if ($accion === 'login') {
        $nombre = trim($_POST['nombre'] ?? '');
        $pw     = $_POST['password'] ?? '';

        if (!$nombre || !$pw) {
            header('Location: index.php?error=campos'); exit;
        }

        if (!isset($todos[$nombre]) || $todos[$nombre]['password'] !== $pw) {
            header('Location: index.php?error=credenciales'); exit;
        }

        $rol = $todos[$nombre]['rol'];

        $_SESSION['usuario_id']     = $nombre; // usamos el nombre como id temporal
        $_SESSION['usuario_nombre'] = $nombre;
        $_SESSION['rol']            = $rol;

        header('Location: index.php?rol=' . urlencode($rol)); exit;

    /* ── REGISTRO ───────────────────────────────────────────── */
    } elseif ($accion === 'registro') {
        $nombre     = trim($_POST['nombre']     ?? '');
        $pw         = $_POST['password']        ?? '';
        $pw2        = $_POST['password2']       ?? '';
        $rol_nombre = trim($_POST['rol_nombre'] ?? '');

        if (!$nombre || !$pw || !$rol_nombre) {
            header('Location: index.php?error=campos'); exit;
        }
        if ($pw !== $pw2) {
            header('Location: index.php?error=pw_no_coincide'); exit;
        }
        if (isset($todos[$nombre])) {
            header('Location: index.php?error=usuario_existe'); exit;
        }

        // Guardar el nuevo usuario en la sesión
        $_SESSION['usuarios_registrados'][$nombre] = [
            'password' => $pw,
            'rol'      => $rol_nombre,
        ];

        header('Location: index.php?ok=registro'); exit;
    }
}
?>