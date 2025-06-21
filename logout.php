<?php
// logout.php
session_start();
session_unset();
session_destroy();
// Regenerar el ID de sesión después de logout
session_start();
session_regenerate_id(true);
header('Location: login.php');
exit;
