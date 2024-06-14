<?php
function connect(){
    require '../dbconfig.php';
    $con = mysqli_connect($dbconfig['server'],$dbconfig['username'],$dbconfig['password'],$dbconfig['db']);
    if(!$con){
        die("Failed to connect to Database");
    }
    $con->query("SET NAMES 'utf8'");
    $con->query("SET CHARACTER SET utf8");
    $con->query("SET SESSION collation_connection = 'utf8_unicode_ci'");

    return $con;
}

function get_num_dia($curso, $conn) {
    // obtener el día de inicio del curso de la BD
    $stmt = $conn -> prepare("SELECT * FROM cursos WHERE cod = ?");
    $stmt->bind_param('s', $curso);
    $stmt->execute();
    $cursos = $stmt->get_result();
    $r = mysqli_fetch_array($cursos);
    // calcular la diferencia en días
    $now = new DateTime("now");
    $ini = new DateTime($r["fini"]);
    $interval = $now->diff($ini);
    return $interval->days+1;
}

function clear_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>