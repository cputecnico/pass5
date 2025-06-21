<?php
// sugerencias.php
$conexion = new mysqli('localhost', 'root', '', 'gestor_contrasenas');
if ($conexion->connect_error) {
    die('Error de conexión: ' . $conexion->connect_error);
}
$sugerencias = [];
if (isset($_GET['buscar']) && $_GET['buscar'] !== '') {
    $buscar = $conexion->real_escape_string($_GET['buscar']);
    $res = $conexion->query("SELECT DISTINCT sitio FROM contrasenas WHERE sitio LIKE '%$buscar%' LIMIT 10");
    while ($row = $res->fetch_assoc()) {
        $sugerencias[] = $row['sitio'];
    }
}
header('Content-Type: application/json');
echo json_encode($sugerencias);
$conexion->close();
