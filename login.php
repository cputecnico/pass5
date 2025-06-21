<?php
// login.php
session_start();
require_once __DIR__ . '/../pepper/pepper_config.php';
$conexion = @new mysqli('localhost', 'root', '', 'gestor_contrasenas');
if ($conexion->connect_error) {
    echo '<div class="alert alert-danger">No se pudo conectar a la base de datos. Por favor, verifica que el servidor esté en funcionamiento.</div>';
    exit;
}
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $contrasena = $_POST['contrasena'];
    $stmt = $conexion->prepare('SELECT id, hash_contrasena, salt FROM usuarios WHERE usuario=?');
    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $stmt->bind_result($id, $hash, $salt);
    if ($stmt->fetch() && password_verify($contrasena, $hash)) {
        // Derivar clave con PBKDF2 usando el salt del usuario y la pepper global
        $clave_derivada = hash_pbkdf2('sha256', PEPPER . $contrasena, $salt, 100000, 32, true);
        session_regenerate_id(true); // Regenerar el ID de sesión al iniciar sesión
        $_SESSION['usuario_id'] = $id;
        $_SESSION['usuario'] = $usuario;
        $_SESSION['clave_derivada'] = $clave_derivada;
        
        $origen_ip = $_SERVER['REMOTE_ADDR'] === '::1' ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
        $origen_host = gethostbyaddr($origen_ip);
        $origen_user = getenv('USERNAME') ?: getenv('USER');
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $origen = $origen_ip . ' (' . $origen_host . ')';
        // Solo agregar Usuario Windows si NO está vacío
        if ($origen_user && trim($origen_user) !== '') {
            $origen .= ' - Usuario Windows: ' . $origen_user;
        }
        if ($user_agent) {
            $origen .= ' - User-Agent: ' . $user_agent;
        }
        // Registrar log de login exitoso con origen
        $conexion2 = @new mysqli('localhost', 'root', '', 'gestor_contrasenas');
        if (!$conexion2->connect_error) {
            $stmt2 = $conexion2->prepare('INSERT INTO logs (usuario_id, accion, origen) VALUES (?, ?, ?)');
            $accion = 'Inicio de sesión';
            $stmt2->bind_param('iss', $id, $accion, $origen);
            $stmt2->execute();
            $stmt2->close();
            $conexion2->close();
        }
        
        header('Location: index.php');
        exit;
    } else {
        $msg = 'Usuario o contraseña incorrectos';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h2>Iniciar sesión</h2>
    <?php if ($msg) echo '<div class="alert alert-danger">'.$msg.'</div>'; ?>
    <form method="POST">
        <div class="mb-3">
            <label>Usuario</label>
            <input type="text" name="usuario" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Contraseña</label>
            <input type="password" name="contrasena" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Ingresar</button>
        <a href="registro.php" class="btn btn-link">Registrarse</a>
    </form>
</div>
</body>
</html>
