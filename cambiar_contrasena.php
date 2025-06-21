<?php
// cambiar_contrasena.php
// Script para que el usuario cambie su contraseña y mantenga acceso a sus contraseñas guardadas
session_start();
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['clave_derivada'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../pepper/pepper_config.php';
$conexion = @new mysqli('localhost', 'root', '', 'gestor_contrasenas');
if ($conexion->connect_error) {
    echo '<div class="alert alert-danger">No se pudo conectar a la base de datos.</div>';
    exit;
}
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual = $_POST['contrasena_actual'];
    $nueva = $_POST['nueva_contrasena'];
    $usuario_id = $_SESSION['usuario_id'];
    // Obtener hash y salt actuales
    $stmt = $conexion->prepare('SELECT usuario, hash_contrasena, salt FROM usuarios WHERE id=?');
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $stmt->bind_result($usuario, $hash, $salt);
    if ($stmt->fetch() && password_verify($actual, $hash)) {
        // Derivar clave actual
        $clave_actual = hash_pbkdf2('sha256', PEPPER . $actual, $salt, 100000, 32, true);
        // Obtener todas las contraseñas cifradas
        $stmt->close();
        $stmt = $conexion->prepare('SELECT id, contrasena_cifrada, iv FROM contrasenas WHERE usuario_id=?');
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $stmt->bind_result($cid, $cifrada, $iv);
        $contras = [];
        while ($stmt->fetch()) {
            $descifrada = openssl_decrypt($cifrada, 'aes-256-cbc', $clave_actual, 0, $iv);
            $contras[] = ['id' => $cid, 'descifrada' => $descifrada];
        }
        $stmt->close();
        // Generar nuevo salt y hash
        $nuevo_salt = random_bytes(16);
        $nuevo_hash = password_hash($nueva, PASSWORD_DEFAULT);
        $nueva_clave = hash_pbkdf2('sha256', PEPPER . $nueva, $nuevo_salt, 100000, 32, true);
        // Volver a cifrar y actualizar
        foreach ($contras as $c) {
            if ($c['descifrada'] !== false && $c['descifrada'] !== null) {
                $nuevo_iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
                $nueva_cifrada = openssl_encrypt($c['descifrada'], 'aes-256-cbc', $nueva_clave, 0, $nuevo_iv);
                $stmt2 = $conexion->prepare('UPDATE contrasenas SET contrasena_cifrada=?, iv=? WHERE id=?');
                $stmt2->bind_param('ssi', $nueva_cifrada, $nuevo_iv, $c['id']);
                $stmt2->execute();
                $stmt2->close();
            }
        }
        // Actualizar hash y salt
        $stmt = $conexion->prepare('UPDATE usuarios SET hash_contrasena=?, salt=? WHERE id=?');
        $stmt->bind_param('ssi', $nuevo_hash, $nuevo_salt, $usuario_id);
        $stmt->execute();
        $stmt->close();
        // Actualizar clave derivada en sesión
        $_SESSION['clave_derivada'] = $nueva_clave;
        $msg = '<div class="alert alert-success">Contraseña cambiada correctamente. Tus contraseñas guardadas siguen accesibles.</div>';
        
        // Registrar log de cambio de contraseña
        $origen_ip = $_SERVER['REMOTE_ADDR'] === '::1' ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
        $origen_host = gethostbyaddr($origen_ip);
        $origen_user = getenv('USERNAME') ?: getenv('USER');
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $origen = $origen_ip . ' (' . $origen_host . ')';
        if ($origen_user && trim($origen_user) !== '') {
            $origen .= ' - Usuario Windows: ' . $origen_user;
        }
        if ($user_agent) {
            $origen .= ' - User-Agent: ' . $user_agent;
        }
        $stmt_log = $conexion->prepare('INSERT INTO logs (usuario_id, accion, origen) VALUES (?, ?, ?)');
        $accion_log = 'Cambio de contraseña';
        $stmt_log->bind_param('iss', $usuario_id, $accion_log, $origen);
        $stmt_log->execute();
        $stmt_log->close();
    } else {
        $msg = '<div class="alert alert-danger">Contraseña actual incorrecta.</div>';
    }
    // Eliminar cierres innecesarios de $stmt y $stmt2 para evitar errores de doble cierre
    // if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    //     if ($stmt->errno === null) {
    //         @$stmt->close();
    //     }
    // }
    // if (isset($stmt2) && $stmt2 instanceof mysqli_stmt) {
    //     if ($stmt2->errno === null) {
    //         @$stmt2->close();
    //     }
    // }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h2>Cambiar Contraseña</h2>
    <?php if ($msg) echo $msg; ?>
    <form method="POST">
        <div class="mb-3">
            <label>Contraseña actual</label>
            <input type="password" name="contrasena_actual" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Nueva contraseña</label>
            <input type="password" name="nueva_contrasena" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
        <a href="index.php" class="btn btn-link">Volver</a>
    </form>
    <div class="alert alert-warning mt-4">Al cambiar tu contraseña, tus contraseñas guardadas seguirán accesibles. Si olvidas tu contraseña y la restauras por el administrador, perderás acceso a tus contraseñas guardadas.</div>
</div>
</body>
</html>
