<?php
// Conexión a la base de datos
$conexion = @new mysqli('localhost', 'root', '', 'gestor_contrasenas');
if ($conexion->connect_error) {
    echo '<div class="alert alert-danger">No se pudo conectar a la base de datos. Por favor, verifica que el servidor esté en funcionamiento.</div>';
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Obtener usuario_id y sitio antes de eliminar
    $stmt = $conexion->prepare('SELECT usuario_id, sitio FROM contrasenas WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($usuario_id, $sitio);
    $stmt->fetch();
    $stmt->close();
    $conexion->query("DELETE FROM contrasenas WHERE id = $id");
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
    // Registrar log de eliminación
    if ($usuario_id) {
        $conexion2 = @new mysqli('localhost', 'root', '', 'gestor_contrasenas');
        if (!$conexion2->connect_error) {
            $stmt2 = $conexion2->prepare('INSERT INTO logs (usuario_id, accion, origen) VALUES (?, ?, ?)');
            $accion = 'Eliminó una contraseña de ' . $sitio;
            $stmt2->bind_param('iss', $usuario_id, $accion, $origen);
            $stmt2->execute();
            $stmt2->close();
            $conexion2->close();
        }
    }
}
$conexion->close();
header('Location: index.php');
exit;
?>
