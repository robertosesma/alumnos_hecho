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
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true && isset($_SESSION['dni'])) {
    $conn = connect();
    $admin = $_SESSION['admin'];
    $dni = $_SESSION['dni'];
    $curso = $_SESSION['curso'];
    $tipo = $_SESSION['tipo'];

    $dia = get_num_dia($curso,$conn);
    $ahora= new DateTime("now", new DateTimeZone('Europe/Madrid'));
    $hora = $ahora->format('H:i');

    if ($admin==0 && $_SERVER["REQUEST_METHOD"] == "POST") {
        // obtener los ejercicios para el alumno y la sesión
        $stmt = $conn -> prepare("SELECT e.ejer, h.hecho 
        FROM ejercicios e
        LEFT JOIN hecho h ON (h.dni = ? AND h.curso = ? AND h.tipo = ? AND e.ejer = h.ejer) 
        WHERE (e.tipo = ? AND e.dia = ? AND e.hini <= ? AND e.hfin >= ?)");
        $stmt->bind_param('ssiiiss',$dni,$curso,$tipo,$tipo,$dia,$hora,$hora);
        $stmt->execute();
        $ejer = $stmt->get_result();

        // procesar el submit del formulario
        foreach ($ejer as $r) {
            $ej = $r['ejer'];
            $hecho = (strtolower(clear_input($_POST[$ej])) === "on" ? 1 : 0);
            $stmt = $conn -> prepare("REPLACE INTO hecho (dni,curso,tipo,ejer,hecho) VALUES (?,?,?,?,?)");
            $stmt->bind_param('ssisi',$dni,$curso,$tipo,$ej,$hecho);
            $stmt->execute();
        }
    }

    if ($admin==0) {
        // obtener los ejercicios para el alumno y la sesión
        $stmt = $conn -> prepare("SELECT e.ejer, h.hecho 
        FROM ejercicios e
        LEFT JOIN hecho h ON (h.dni = ? AND h.curso = ? AND h.tipo = ? AND e.ejer = h.ejer) 
        WHERE (e.tipo = ? AND e.dia = ? AND e.hini <= ? AND e.hfin >= ?)");
        $stmt->bind_param('ssiiiss',$dni,$curso,$tipo,$tipo,$dia,$hora,$hora);
        $stmt->execute();
        $ejer = $stmt->get_result();
    }
    if ($admin==1) {
        // obtener el listado de ejercicios hechos
        $stmt = $conn -> prepare("SELECT e.ejer, IFNULL(h.hechos,0) AS hechos, 
            (SELECT COUNT(DISTINCT(dni)) FROM alumnos WHERE curso = ?) AS total
            FROM ejercicios e
            LEFT JOIN (SELECT ejer, SUM(hecho) AS hechos FROM hecho WHERE curso = ?
                GROUP BY ejer) h USING(ejer)
            WHERE e.tipo = ? AND e.dia = ? AND e.hini <= ? AND e.hfin >= ?;");
        $stmt->bind_param('ssiiss',$curso,$curso,$tipo,$dia, $hora, $hora);
        $stmt->execute();
        $ejer = $stmt->get_result();
    }
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
            <h5>Día <?php echo $dia; ?>, Hora <?php echo $hora?></h5>
            <a class="btn btn-secondary" href="init.php">Actualizar</a>
            <a class="btn btn-secondary" href="logout.php">Salir</a>
        </div>
    </div>

    <?php if ($admin==0) { ?>
        <div class="text-center">
            <p>Por favor, marque los ejercicios completados</p>
        </div>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
            <?php foreach ($ejer as $r) {
                $ej = $r["ejer"];
                $hecho = (is_null($r["hecho"]) ? 0 : $r["hecho"]);
                $checked = ($hecho==1 ? "checked" : "");
                echo "<div class='mb-3 mx-auto' style='width: 75px;'>";
                echo "<label><input type='checkbox' name='$ej' ".$checked."> $ej</label>";
                echo "</div>";
            } ?>
            <div class="mx-auto" style="width: 75px;">
                <button type="submit" class="btn btn-primary">Enviar</button>
            </div>
        </form>
    <?php } else { ?>
        <div class="container w-50 p-3">
            <table class="table">
            <thead>
                <tr>
                    <th scope="col">Ejercicio</th>
                    <th scope="col"><div class='text-center'>Hechos</div></th>
                    <th scope="col"><div class='text-center'>Total</div></th>
                    <th scope="col"><div class='text-center'>%</div></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ejer as $r) { 
                $p = round(100*$r["hechos"]/$r["total"]); ?>
                <tr>
                    <td><?php echo "<a href='ejer.php?ej=".$r["ejer"]."'>".$r["ejer"]."</a>";?></td>
                    <td><div class='text-center'><?php echo $r["hechos"]; ?></div></td>
                    <td><div class='text-center'><?php echo $r["total"]; ?></div></td>
                    <td><div class='text-center'><?php echo $p; ?></div></td>
                </tr>
            <?php } ?>
            </tbody>
            </table>
        </div>
    <?php } ?>

<?php } else {
    header("Location: logout.php");
}?>

</body>
</html>