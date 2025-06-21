<?php
session_start();
if (!isset($_SESSION['clave_derivada']) || !isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$usuario_id = $_SESSION['usuario_id'];
$clave = $_SESSION['clave_derivada'];
// Conexión a la base de datos
$conexion = @new mysqli('localhost', 'root', '', 'gestor_contrasenas');
if ($conexion->connect_error) {
    echo '<div class="alert alert-danger">No se pudo conectar a la base de datos. Por favor, verifica que el servidor esté en funcionamiento.</div>';
    exit;
}

$sitio = $_POST['sitio'];
$usuario = $_POST['usuario'];
$contrasena = $_POST['contrasena'];

$iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
$contrasena_cifrada = openssl_encrypt($contrasena, 'aes-256-cbc', $clave, 0, $iv);

$stmt = $conexion->prepare('INSERT INTO contrasenas (sitio, usuario, contrasena_cifrada, iv, usuario_id) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('ssssi', $sitio, $usuario, $contrasena_cifrada, $iv, $usuario_id);
$stmt->execute();
$stmt->close();
$conexion->close();

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
// Registrar log de guardado de contraseña con sitio y origen
$conexion2 = @new mysqli('localhost', 'root', '', 'gestor_contrasenas');
if (!$conexion2->connect_error) {
    $stmt2 = $conexion2->prepare('INSERT INTO logs (usuario_id, accion, origen) VALUES (?, ?, ?)');
    $accion = 'Guardó una contraseña de ' . $sitio;
    $stmt2->bind_param('iss', $usuario_id, $accion, $origen);
    $stmt2->execute();
    $stmt2->close();
    $conexion2->close();
}

header('Location: index.php');
exit;
