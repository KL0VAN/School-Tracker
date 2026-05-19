<?php
// riprendo la sessione emi colelgo al DB
session_start();

require_once "../config/db.php";
// controllo del Login
if (!isset($_SESSION["ID_Professore"])) {
    header("Location: ../login.php");
    exit;
}

// Questo è il professore attualmente loggato.

$idProfessore = $_SESSION["ID_Professore"];

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: dashboard.php");
    exit;
}
// Apri la classe con ID specificato nella query string, ma solo se il professore loggato e associato a quella classe, altrimenti mostriamo un messaggio di errore o reindirizziamo alla dashboard
$idClasse = intval($_GET["id"]);

//questa classe esiste?
//questa classe appartiene davvero al professore loggato?
$sqlClasse = "SELECT 
                    Classe.ID_Classe,
                    Classe.Numero,
                    Classe.Sezione
              FROM Classe
              JOIN ProfessoreClasse 
                    ON Classe.ID_Classe = ProfessoreClasse.ID_Classe
              WHERE Classe.ID_Classe = :idClasse
              AND ProfessoreClasse.ID_Professore = :idProfessore";

$stmtClasse = $conn->prepare($sqlClasse);
$stmtClasse->execute([
    ":idClasse" => $idClasse,
    ":idProfessore" => $idProfessore
]);

$classe = $stmtClasse->fetch(PDO::FETCH_ASSOC);

if (!$classe) {
    die("Accesso non autorizzato a questa classe.");
}

// Prendere tutti gli studenti dal DATBASE
$sqlStudenti = "SELECT 
                    Studente.ID_Studente,
                    Studente.Nome,
                    Studente.Cognome,
                    Studente.DataNascita,
                    Studente.Foto,
                    ROUND(AVG(Voto.Voto), 2) AS MediaStudente
                FROM Studente
                LEFT JOIN Voto 
                    ON Studente.ID_Studente = Voto.ID_Studente
                    AND Voto.ID_Professore = :idProfessore
                WHERE Studente.ID_Classe = :idClasse
                GROUP BY 
                    Studente.ID_Studente,
                    Studente.Nome,
                    Studente.Cognome,
                    Studente.DataNascita,
                    Studente.Foto
                ORDER BY Studente.Cognome, Studente.Nome";

$stmtStudenti = $conn->prepare($sqlStudenti);
$stmtStudenti->execute([
    ":idClasse" => $idClasse,
    ":idProfessore" => $idProfessore
]);

$studenti = $stmtStudenti->fetchAll(PDO::FETCH_ASSOC);

$sqlMediaClasse = "SELECT ROUND(AVG(Voto), 2) AS MediaClasse
                   FROM Voto
                   WHERE ID_Classe = :idClasse
                   AND ID_Professore = :idProfessore";

$stmtMediaClasse = $conn->prepare($sqlMediaClasse);
$stmtMediaClasse->execute([
    ":idClasse" => $idClasse,
    ":idProfessore" => $idProfessore
]);

$mediaClasse = $stmtMediaClasse->fetch(PDO::FETCH_ASSOC);


?>






<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Classe <?php echo htmlspecialchars($classe["Numero"] . $classe["Sezione"]); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <h1>
        Classe <?php echo htmlspecialchars($classe["Numero"] . $classe["Sezione"]); ?>
    </h1>

    <p>
        Professore:
        <strong>
            <?php echo htmlspecialchars($_SESSION["Nome"] . " " . $_SESSION["Cognome"]); ?>
        </strong>
    </p>

    <p>
        Materia:
        <strong>
            <?php echo htmlspecialchars($_SESSION["Materia"]); ?>
        </strong>
    </p>

    <p>
        Media della classe nella tua materia:
        <strong>
            <?php 
                if ($mediaClasse["MediaClasse"] !== null) {
                    echo $mediaClasse["MediaClasse"];
                } else {
                    echo "Nessun voto inserito";
                }
            ?>
        </strong>
    </p>

    <hr>

    <h2>Studenti</h2>

    <?php if (count($studenti) > 0): ?>

        <table border="1" cellpadding="8">
            <tr>
                <th>Foto</th>
                <th>Nome</th>
                <th>Cognome</th>
                <th>Data di nascita</th>
                <th>Media</th>
                <th>Azione</th>
            </tr>

            <?php foreach ($studenti as $studente): ?>
                <tr>
                   <td>
    <?php
        $foto = $studente["Foto"] ?? "";
        $percorsoFoto = "../" . $foto;

        if (!empty($foto) && file_exists($percorsoFoto)) {
            $srcFoto = "../" . $foto;
        } else {
            $srcFoto = "../uploads/studenti/default.png";
        }
    ?>

    <img 
        src="<?php echo htmlspecialchars($srcFoto); ?>" 
        alt="Foto studente" 
        width="60"
    >
</td>

                    <td><?php echo htmlspecialchars($studente["Nome"]); ?></td>

                    <td><?php echo htmlspecialchars($studente["Cognome"]); ?></td>

                    <td><?php echo htmlspecialchars($studente["DataNascita"]); ?></td>

                    <td>
                        <?php 
                            if ($studente["MediaStudente"] !== null) {
                                echo $studente["MediaStudente"];
                            } else {
                                echo "Nessun voto";
                            }
                        ?>
                    </td>

                    <td>
                        <!-- porta alla prossima pagina -->
                        <a href="studente.php?id=<?php echo $studente["ID_Studente"]; ?>">
                            Apri scheda
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>

        </table>

    <?php else: ?>

        <p>Nessuno studente presente in questa classe.</p>

    <?php endif; ?>

    <br>

    <a href="dashboard.php">Torna alla dashboard</a>
    |
    <a href="../logout.php">Logout</a>

</body>
</html>