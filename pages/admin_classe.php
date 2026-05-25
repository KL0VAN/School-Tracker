<?php

session_start();

require_once "../config/db.php";

if (!isset($_SESSION["ID_Professore"]) || $_SESSION["IsAmministratore"] != 1) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: admin_dashboard.php");
    exit;
}

$idClasse = intval($_GET["id"]);
$errore = "";

$sqlClasse = "SELECT ID_Classe, Numero, Sezione
              FROM Classe
              WHERE ID_Classe = :idClasse";

$stmtClasse = $conn->prepare($sqlClasse);
$stmtClasse->execute([
    ":idClasse" => $idClasse
]);

$classe = $stmtClasse->fetch(PDO::FETCH_ASSOC);

if (!$classe) {
    die("Classe non trovata.");
}


/* =========================
   AGGIUNGI STUDENTE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST["azione"] === "aggiungi") {

    $nome = trim($_POST["nome"]);
    $cognome = trim($_POST["cognome"]);
    $dataNascita = $_POST["dataNascita"];

    if ($nome === "" || $cognome === "" || $dataNascita === "") {
        $errore = "Compila tutti i campi.";
    } else {

        $foto = "uploads/studenti/default.png";
        $fotoCaricata = isset($_FILES["foto"]) && $_FILES["foto"]["error"] !== UPLOAD_ERR_NO_FILE;
        $estensione = "";
        $estensioniConsentite = ["jpg", "jpeg", "png", "webp"];

        if ($fotoCaricata) {
            if ($_FILES["foto"]["error"] !== UPLOAD_ERR_OK) {
                $errore = "Errore durante il caricamento della foto.";
            } else {
                $estensione = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));

                if (!in_array($estensione, $estensioniConsentite, true)) {
                    $errore = "Formato foto non valido. Usa jpg, jpeg, png o webp.";
                }
            }
        }

        if ($errore === "") {

            $sqlInserisci = "INSERT INTO Studente
                                (ID_Classe, Nome, Cognome, DataNascita, Foto)
                             VALUES
                                (:idClasse, :nome, :cognome, :dataNascita, :foto)";

            $stmtInserisci = $conn->prepare($sqlInserisci);
            $stmtInserisci->execute([
                ":idClasse" => $idClasse,
                ":nome" => $nome,
                ":cognome" => $cognome,
                ":dataNascita" => $dataNascita,
                ":foto" => $foto
            ]);

            $nuovoID = $conn->lastInsertId();

            if ($fotoCaricata) {
                $cartellaUpload = realpath(__DIR__ . "/../uploads/studenti");

                if ($cartellaUpload === false) {
                    $errore = "Cartella upload non trovata.";
                } else {
                    $nomeFile = "studente_" . $nuovoID . "." . $estensione;
                    $percorsoDestinazione = $cartellaUpload . DIRECTORY_SEPARATOR . $nomeFile;
                    $percorsoRelativo = "uploads/studenti/" . $nomeFile;

                    if (move_uploaded_file($_FILES["foto"]["tmp_name"], $percorsoDestinazione)) {
                        $foto = $percorsoRelativo;
                    } else {
                        $errore = "Errore durante il salvataggio della foto.";
                    }
                }
            }

            if ($errore === "") {

                $sqlFoto = "UPDATE Studente
                            SET Foto = :foto
                            WHERE ID_Studente = :idStudente";

                $stmtFoto = $conn->prepare($sqlFoto);
                $stmtFoto->execute([
                    ":foto" => $foto,
                    ":idStudente" => $nuovoID
                ]);

                header("Location: admin_classe.php?id=" . $idClasse);
                exit;
            }
        }
    }
}


/* =========================
   RIMUOVI STUDENTE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST["azione"] === "elimina") {

    $idStudente = intval($_POST["idStudente"]);

    $sqlControllo = "SELECT ID_Studente
                     FROM Studente
                     WHERE ID_Studente = :idStudente
                     AND ID_Classe = :idClasse";

    $stmtControllo = $conn->prepare($sqlControllo);
    $stmtControllo->execute([
        ":idStudente" => $idStudente,
        ":idClasse" => $idClasse
    ]);

    $studente = $stmtControllo->fetch(PDO::FETCH_ASSOC);

    if (!$studente) {
        $errore = "Studente non trovato in questa classe.";
    } else {

        $sqlEliminaVoti = "DELETE FROM Voto
                           WHERE ID_Studente = :idStudente";

        $stmtEliminaVoti = $conn->prepare($sqlEliminaVoti);
        $stmtEliminaVoti->execute([
            ":idStudente" => $idStudente
        ]);

        $sqlEliminaStudente = "DELETE FROM Studente
                               WHERE ID_Studente = :idStudente
                               AND ID_Classe = :idClasse";

        $stmtEliminaStudente = $conn->prepare($sqlEliminaStudente);
        $stmtEliminaStudente->execute([
            ":idStudente" => $idStudente,
            ":idClasse" => $idClasse
        ]);

        header("Location: admin_classe.php?id=" . $idClasse);
        exit;
    }
}


$sqlStudenti = "SELECT 
                    ID_Studente,
                    Nome,
                    Cognome,
                    DataNascita,
                    Foto
                FROM Studente
                WHERE ID_Classe = :idClasse
                ORDER BY Cognome, Nome";

$stmtStudenti = $conn->prepare($sqlStudenti);
$stmtStudenti->execute([
    ":idClasse" => $idClasse
]);

$studenti = $stmtStudenti->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Classe</title>
    <link rel="stylesheet" href="../assets/css/base.css">
</head>
<body>

<main class="page-shell">

    <h1>
        Gestione Classe 
        <?php echo htmlspecialchars($classe["Numero"] . $classe["Sezione"]); ?>
    </h1>

    <p>
        Amministratore:
        <strong>
            <?php echo htmlspecialchars($_SESSION["Nome"] . " " . $_SESSION["Cognome"]); ?>
        </strong>
    </p>

    <?php if ($errore !== ""): ?>
        <p style="color: red;"><?php echo htmlspecialchars($errore); ?></p>
    <?php endif; ?>

    <hr>

    <section class="student-form-card card">
        <h2>Aggiungi studente</h2>

        <form class="student-form" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="azione" value="aggiungi">

            <div class="student-form-grid">
                <div class="student-form-field">
                    <label for="nomeStudente">Nome</label>
                    <input type="text" id="nomeStudente" name="nome" required>
                </div>

                <div class="student-form-field">
                    <label for="cognomeStudente">Cognome</label>
                    <input type="text" id="cognomeStudente" name="cognome" required>
                </div>

                <div class="student-form-field">
                    <label for="dataNascitaStudente">Data di nascita</label>
                    <input type="date" id="dataNascitaStudente" name="dataNascita" required>
                </div>

                <div class="student-form-field">
                    <label for="fotoStudente">Foto</label>
                    <input type="file" id="fotoStudente" name="foto" accept=".jpg,.jpeg,.png,.webp">
                </div>
            </div>

            <div class="student-form-actions">
                <button class="btn btn-primary student-form-submit" type="submit">Aggiungi studente</button>
            </div>
        </form>
    </section>

    <hr>

    <h2>Studenti della classe</h2>

    <table>
        <tr>
            <th>Foto</th>
            <th>Nome</th>
            <th>Cognome</th>
            <th>Data nascita</th>
            <th>Azione</th>
        </tr>

        <?php foreach ($studenti as $studente): ?>
            <tr>
                <td>
                    <img 
                        src="../<?php echo htmlspecialchars($studente["Foto"]); ?>" 
                        alt="Foto studente" 
                        width="55"
                    >
                </td>

                <td><?php echo htmlspecialchars($studente["Nome"]); ?></td>
                <td><?php echo htmlspecialchars($studente["Cognome"]); ?></td>
                <td><?php echo htmlspecialchars($studente["DataNascita"]); ?></td>

                <td>
                    <form method="POST" onsubmit="return confirm('Vuoi davvero rimuovere questo studente?');">
                        <input type="hidden" name="azione" value="elimina">
                        <input type="hidden" name="idStudente" value="<?php echo $studente["ID_Studente"]; ?>">
                        <button type="submit">Rimuovi</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>

    </table>

    <br>

    <a class="btn btn-secondary" href="admin_dashboard.php">Torna alla dashboard amministrazione</a>
    <a class="btn btn-danger" href="../logout.php">Logout</a>

</main>

</body>
</html>
