<?php
// come fin'ora, è importante iniziare la sessione in ogni pagina che ha bisogno di accedere alla sessione, altrimenti non possiamo accedere alla sessione e quindi non possiamo verificare se l'utente è loggato o meno
session_start();

require_once "../config/db.php";
// verifica se l'utente è loggato, se non lo è, reindirizzalo alla pagina di login
if (!isset($_SESSION["ID_Professore"])) {
    header("Location: ../login.php");
    exit;
}

$idProfessore = $_SESSION["ID_Professore"];

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: dashboard.php");
    exit;
}
// intval significa integer value, e serve per convertire la stringa che viene passata nella query string in un numero intero, in questo modo evitiamo di avere problemi di SQL injection o di errori di tipo quando usiamo questa variabile nella query SQL, e garantiamo che stiamo lavorando con un numero intero valido
$idStudente = intval($_GET["id"]);

$sqlStudente = "SELECT 
                    Studente.ID_Studente,
                    Studente.ID_Classe,
                    Studente.Nome,
                    Studente.Cognome,
                    Studente.DataNascita,
                    Studente.Foto,
                    Classe.Numero,
                    Classe.Sezione
                FROM Studente
                JOIN Classe 
                    ON Studente.ID_Classe = Classe.ID_Classe
                JOIN ProfessoreClasse 
                    ON Classe.ID_Classe = ProfessoreClasse.ID_Classe
                WHERE Studente.ID_Studente = :idStudente
                AND ProfessoreClasse.ID_Professore = :idProfessore";

                //dammi lo studente SOLO SE appartiene a una classe del professore loggato, in questo modo evitiamo che un professore possa accedere alla scheda di uno studente che non gli appartiene, e garantiamo la sicurezza e la privacy dei dati degli studenti

$stmtStudente = $conn->prepare($sqlStudente);
$stmtStudente->execute([
    ":idStudente" => $idStudente,
    ":idProfessore" => $idProfessore
]);

$studente = $stmtStudente->fetch(PDO::FETCH_ASSOC);

if (!$studente) {
    die("Accesso non autorizzato a questo studente.");
}

$sqlVoti = "SELECT 
                Voto.ID_Voto,
                Voto.Voto,
                Voto.DataVoto,
                Voto.Descrizione,
                Materia.NomeMateria
            FROM Voto
            JOIN Materia 
                ON Voto.ID_Materia = Materia.ID_Materia
            WHERE Voto.ID_Studente = :idStudente
            AND Voto.ID_Professore = :idProfessore
            ORDER BY Voto.DataVoto DESC";

$stmtVoti = $conn->prepare($sqlVoti);
$stmtVoti->execute([
    ":idStudente" => $idStudente,
    ":idProfessore" => $idProfessore
]);

$voti = $stmtVoti->fetchAll(PDO::FETCH_ASSOC);
// AVG signifca media dei voti invece 2 arrotonda per 2 tipo 8.00
$sqlMedia = "SELECT ROUND(AVG(Voto), 2) AS MediaStudente
             FROM Voto
             WHERE ID_Studente = :idStudente
             AND ID_Professore = :idProfessore";

$stmtMedia = $conn->prepare($sqlMedia);
$stmtMedia->execute([
    ":idStudente" => $idStudente,
    ":idProfessore" => $idProfessore
]);

$media = $stmtMedia->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
  <title>Scheda Studente</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <h1>Scheda Studente</h1>

    <h2>
        <?php echo htmlspecialchars($studente["Nome"] . " " . $studente["Cognome"]); ?>
    </h2>

    <p>
        Classe:
        <strong>
            <?php echo htmlspecialchars($studente["Numero"] . $studente["Sezione"]); ?>
        </strong>
    </p>

    <p>
        Data di nascita:
        <strong>
            <?php echo htmlspecialchars($studente["DataNascita"]); ?>
        </strong>
    </p>

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
        Media personale:
        <strong>
            <?php
                if ($media["MediaStudente"] !== null) {
                    echo htmlspecialchars($media["MediaStudente"]);
                } else {
                    echo "Nessun voto inserito";
                }
            ?>
        </strong>
    </p>

    <h3>Foto studente</h3>

 <?php
    $percorsoFoto = "../" . $studente["Foto"];
?>

<?php
    $percorsoFoto = "../" . $studente["Foto"];
?>

<?php if (!empty($studente["Foto"]) && file_exists($percorsoFoto)): ?>
    <img 
        class="student-detail-photo"
        src="../<?php echo htmlspecialchars($studente["Foto"]); ?>" 
        alt="Foto studente" 
        width="460"
    >
<?php else: ?>
    <img 
        class="student-detail-photo"
        src="../uploads/studenti/default.png" 
        alt="Foto default" 
        width="460"
    >
<?php endif; ?>

    <hr>

    <h2>Voti</h2>

    <p>
        <a href="voti.php?azione=aggiungi&idStudente=<?php echo $studente["ID_Studente"]; ?>">
            Aggiungi voto
        </a>
    </p>

    <?php if (count($voti) > 0): ?>

        <table border="1" cellpadding="8">
            <tr>
                <th>Materia</th>
                <th>Voto</th>
                <th>Data</th>
                <th>Descrizione</th>
                <th>Azioni</th>
            </tr>

            <?php foreach ($voti as $voto): ?>
                <tr>
                    <td><?php echo htmlspecialchars($voto["NomeMateria"]); ?></td>

                    <td><?php echo htmlspecialchars($voto["Voto"]); ?></td>

                    <td><?php echo htmlspecialchars($voto["DataVoto"]); ?></td>

                    <td><?php echo htmlspecialchars($voto["Descrizione"]); ?></td>

                    <td>
                        <a href="voti.php?azione=modifica&idVoto=<?php echo $voto["ID_Voto"]; ?>">
                            Modifica
                        </a>
                        |
                        <a href="voti.php?azione=elimina&idVoto=<?php echo $voto["ID_Voto"]; ?>">
                            Elimina
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>

        </table>

    <?php else: ?>

        <p>Nessun voto presente per questo studente nella tua materia.</p>

    <?php endif; ?>

    <br>

    <a href="classe.php?id=<?php echo $studente["ID_Classe"]; ?>">
        Torna alla classe
    </a>
    |
    <a href="dashboard.php">Dashboard</a>
    |
    <a href="../logout.php">Logout</a>

</body>
</html>
