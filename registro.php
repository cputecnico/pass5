<?php
// registro.php
$conexion = @new mysqli('localhost', 'root', '', 'gestor_contrasenas');
if ($conexion->connect_error) {
    echo '<div class="alert alert-danger">No se pudo conectar a la base de datos. Por favor, verifica que el servidor esté en funcionamiento.</div>';
    exit;
}
require_once __DIR__ . '/../pepper/pepper_config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $contrasena = $_POST['contrasena'];
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);
    $salt = random_bytes(16); // 128 bits
    // Opcional: podrías usar la pepper también en el hash de la contraseña, pero NIST solo la exige para la clave de cifrado
    $stmt = $conexion->prepare('INSERT INTO usuarios (usuario, hash_contrasena, salt) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $usuario, $hash, $salt);
    $stmt->execute();
    $stmt->close();
    header('Location: login.php?registro=ok');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h2>Registro</h2>
    <form method="POST">
        <div class="mb-3">
            <label>Usuario</label>
            <input type="text" name="usuario" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Contraseña</label>
            <input type="password" name="contrasena" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Registrarse</button>
        <a href="login.php" class="btn btn-link">¿Ya tienes cuenta? Inicia sesión</a>
    </form>

    <?php
    // Mostrar log de actividad del usuario
    session_start();
    if (isset($_SESSION['usuario_id'])) {
        $usuario_id = $_SESSION['usuario_id'];
        $conexion = @new mysqli('localhost', 'root', '', 'gestor_contrasenas');
        if (!$conexion->connect_error) {
            $stmt = $conexion->prepare('SELECT fecha, accion, origen FROM logs WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 6');
            $stmt->bind_param('i', $usuario_id);
            $stmt->execute();
            $stmt->bind_result($fecha, $accion, $origen);
            $logs = [];
            while ($stmt->fetch()) {
                $logs[] = [
                    'fecha' => $fecha,
                    'accion' => $accion,
                    'origen' => $origen
                ];
            }
            if (count($logs) > 0) {
                echo '<div class="alert alert-info mt-4"><strong>Últimas actividades</strong><ul class="mb-0">';
                foreach ($logs as $log) {
                    $origen_legible = $log['origen'] ? htmlspecialchars($log['origen']) : 'Desconocido';
                    echo '<li>' . htmlspecialchars($log['fecha']) . ' - ' . htmlspecialchars($log['accion']) . ' <br><span style="font-size:smaller">' . $origen_legible . '</span></li>';
                }
                echo '</ul></div>';
            }
            $stmt->close();
            $conexion->close();
        }
    }
    ?>
</div>
</body>
</html>
