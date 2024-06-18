<?php 
// server should keep session data for AT LEAST 1 hour
ini_set('session.gc_maxlifetime', 3600);
// each client should remember their session id for EXACTLY 1 hour
session_set_cookie_params(3600);
session_start(); ?>

<!DOCTYPE html>
<head>
    <title>Seguimiento Ejercicios</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="database.png"/>
    <style>
        .jumbotron{
            margin:10px 10px 10px 10px;
            padding: 0px 10px 10px 40px;
            border-radius:10px;
            background:#0047c0;
            background-image: url(logo.png);
            background-repeat: no-repeat;
            background-size: auto;
            background-position: 10px 10px;
        }
    </style>
</head>
<body>

<?php
include("funcaux.php");
$ok = true;
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true && $_SESSION['admin']==1 && isset($_GET['ej'])) {
    $conn = connect();
    $curso = $_SESSION['curso'];
    $tipo = $_SESSION['tipo'];
    $dia = get_num_dia($curso,$conn);
    $ahora= new DateTime("now", new DateTimeZone('Europe/Madrid'));
    $hora = $ahora->format('H:i');
    $ej = clear_input($_GET["ej"]);

    // obtener el listado de alumnos
    $stmt = $conn -> prepare("SELECT a.dni, a.curso, CONCAT_WS(' ',a.nom,a.ape1,a.ape2) as nombre, 
	        IFNULL(h.ejer,?) AS ejer, IFNULL(h.hecho,0) AS hecho
        FROM alumnos a 
        LEFT JOIN hecho h ON (a.dni = h.dni AND a.curso = h.curso AND h.ejer = ?)
        WHERE a.curso = ?
        ORDER BY hecho, dni");
    $stmt->bind_param('sss',$ej,$ej,$curso);
    $stmt->execute();
    $ejer = $stmt->get_result();

    // obtener recuento de hechos
    $stmt = $conn -> prepare("SELECT SUM(hecho) AS hechos FROM hecho WHERE curso = ? AND ejer = ? GROUP BY ejer");
    $stmt->bind_param('ss',$curso,$ej);
    $stmt->execute();
    $totals = $stmt->get_result();
    $hechos = 0;
    if ($totals->num_rows > 0) {
        $t = mysqli_fetch_array($totals);
        $hechos = $t["hechos"];
    }

    $stmt = $conn -> prepare("SELECT COUNT(DISTINCT(dni)) AS total FROM alumnos WHERE curso = ?");
    $stmt->bind_param('s',$curso);
    $stmt->execute();
    $totals = $stmt->get_result();
    $t = mysqli_fetch_array($totals);
    $total = $t["total"];
    $resum = "(".$hechos."/".$total." ".round(100*$hechos/$total)."%)";
} else {
    $ok = false;
}
$conn->close();
?>

<?php if ($ok) { ?>
    <div class="container">
        <div class="jumbotron text-white text-center">
            <h4>Seguimiento Ejercicios</h4>
            <?php echo "<h5>".$_SESSION['title']."</h5>";?>
            <h5>Día <?php echo $dia; ?>, Hora <?php echo $hora; ?> - Ejer: <?php echo $ej; ?> <?php echo $resum; ?></h5>
            <a class="btn btn-secondary" href="init.php">Atrás</a>
            <a class="btn btn-secondary" href="logout.php">Salir</a>
        </div>
    </div>

    <div class="container w-50 p-3">
        <table class="table">
        <thead>
            <tr>
                <th scope="col"><div class='text-center'>DNI</div></th>
                <th scope="col"><div class='text-center'>Nombre</div></th>
                <th scope="col"><div class='text-center'>Hecho</div></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($ejer as $r) { ?>
            <tr>
                <td><div class='text-center'><?php echo $r["dni"]; ?></div></td>
                <td><div class='text-center'><?php echo $r["nombre"]; ?></div></td>
                <td><div class='text-center'><?php echo ($r["hecho"]==1 ? "Sí" : "No"); ?></div></td>
            </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
<?php } else {
    header("Location: logout.php");
}?>


</body>
</html>