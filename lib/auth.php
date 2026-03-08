<?php
$GLOBALS['USUARIOS_PRUEBA'] = [
    'admin'    => ['password' => '1234', 'rol' => 'administrador'],
    'maitre'   => ['password' => '1234', 'rol' => 'maitre'],
    'mesero'   => ['password' => '1234', 'rol' => 'mesero'],
    'cocinero' => ['password' => '1234', 'rol' => 'cocinero'],
    'cliente'  => ['password' => '1234', 'rol' => 'cliente'],
];

function fn_formulario_auth()
{
    // Qué tab mostrar
    $tab = $_GET['tab'] ?? 'login';

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
                    <input type="text" name="nombre" placeholder="Ej: admin" required autocomplete="username" />
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
                    <input type="text" name="nombre" placeholder="Ej: maria_gomez" required />
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
        ') . '
    </div>
    ';
}

function fn_procesar_auth()
{
    session_start();

    $usuarios_sesion = $_SESSION['usuarios_registrados'] ?? [];
    $todos = array_merge($GLOBALS['USUARIOS_PRUEBA'], $usuarios_sesion);

    $accion = $_POST['accion'] ?? '';

    if ($accion === 'login') {
        $nombre = trim($_POST['nombre'] ?? '');
        $pw     = $_POST['password']   ?? '';

        if (!$nombre || !$pw) {
            header('Location: index.php?tab=login&error=campos'); exit;
        }
        if (!isset($todos[$nombre]) || $todos[$nombre]['password'] !== $pw) {
            header('Location: index.php?tab=login&error=credenciales'); exit;
        }

        $rol = $todos[$nombre]['rol'];
        $_SESSION['usuario_id']     = $nombre;
        $_SESSION['usuario_nombre'] = $nombre;
        $_SESSION['rol']            = $rol;
        header('Location: index.php?rol=' . urlencode($rol)); exit;

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
        if (isset($todos[$nombre])) {
            header('Location: index.php?tab=registro&error=usuario_existe'); exit;
        }

        $_SESSION['usuarios_registrados'][$nombre] = [
            'password' => $pw,
            'rol'      => $rol_nombre,
        ];

        header('Location: index.php?tab=login&ok=registro'); exit;
    }
}
?>