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
$conn = connect();
// verificar si hay un curso activo por fecha
$today = date('Y-m-d');
$stmt = $conn -> prepare("SELECT c.cod, c.tipo, d.descrip FROM cursos c 
                          LEFT JOIN dcursos d ON c.tipo = d.cod
                          WHERE fini<=? AND ffin>=?");
$stmt->bind_param('ss', $today, $today);
$stmt->execute();
$cursos = $stmt->get_result();
$activo = ($cursos->num_rows > 0);
$r = mysqli_fetch_array($cursos);
$title = ($activo ? $r["descrip"] : "No hay cursos abiertos");
$tipo = ($activo ? $r["tipo"] : 0);
$curso = ($activo ? $r["cod"] : "");
$cursos->free();

$Err = $user = $pswd = "";
if ($activo) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $dni = clear_input($_POST["dni"]);
        $pswd = clear_input($_POST["pswd"]);

        // verificar si hay un alumno con el dni, curso y contraseña indicados
        $stmt = $conn -> prepare("SELECT * FROM alumnos WHERE dni = ? AND (curso = ? OR tipo = 0) AND pswd=?");
        $stmt->bind_param('sss', $dni, $curso, $pswd);
        $stmt->execute();
        $users = $stmt->get_result();
        $nrows = $users->num_rows;
        if ($nrows > 0) {
            $r = mysqli_fetch_array($users);
            $_SESSION['loggedin'] = true;
            $_SESSION['dni'] = $dni;
            $_SESSION['title'] = $title;
            $_SESSION['curso'] = $curso;
            $_SESSION['tipo'] = $tipo;
            $_SESSION['admin'] = $r['admin'];
            header("Location: init.php");
        } else {
            $Err = "DNI/NIE inválido o contraseña incorrecta";
        }
        $users->free();
    }
}
$conn->close();
?>

<div class="container">
    <div class="jumbotron text-white text-center">
    <h4>Seguimiento Ejercicios</h4>
        <?php if ($activo) { ?>
            <?php echo "<h5>".$title."</h5>";?>
            <h6>Introduzca sus credenciales</h6> 
        <?php } else { ?>
            <h6>No hay ningún curso activo</h6> 
        <?php } ?>
    </div>

    <?php if ($activo) { ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
        <div class="mb-3 mt-3 mx-auto" style="width: 200px">
            <label for="dni">DNI/NIE:</label>
            <input type="text" class="form-control" name="dni" required>
        </div>
        <div class="mb-3 mt-3 mx-auto" style="width: 200px">
            <label for="pswd">Contraseña:</label>
            <input type="password" class="form-control" name="pswd" required>
            <span class="error text-danger"><?php echo $Err;?></span>
        </div>
        <div class="mb-3 mt-3 mx-auto" style="width: 200px">
            <button type="submit" class="btn btn-primary">Entrar</button>
        </div>
        </form>
    <?php } ?>
</div>

</body>
</html>