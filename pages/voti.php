<?php

session_start();

require_once "../config/db.php";

if (!isset($_SESSION["ID_Professore"])) {
    header("Location: ../login.php");
    exit;
}

$idProfessore = $_SESSION["ID_Professore"];

$azione = $_GET["azione"] ?? "";
$errore = "";

$azioniConsentite = ["aggiungi", "modifica", "elimina"];

$votiConsentiti = [
    "1", "1.5", "2", "2.5", "3", "3.5", "4", "4.5",
    "5", "5.5", "6", "6.5", "7", "7.5",
    "8", "8.5", "9", "9.5", "10"
];

if (!in_array($azione, $azioniConsentite)) {
    header("Location: dashboard.php");
    exit;
}

/*
    Il professore puo modificare/eliminare solo i suoi di voti
*/
$sqlMateriaProfessore = "SELECT ID_Materia 
                         FROM Professore 
                         WHERE ID_Professore = :idProfessore";

// Non metti i valori nella querry ma li passi come parametri, in questo modo eviti di avere problemi di SQL injection e garantisci la sicurezza dell'applicazione, e inoltre rende il codice piu pulito e facile da leggere, separando la logica della query dai dati che vengono inseriti nella query
$stmtMateriaProfessore = $conn->prepare($sqlMateriaProfessore);
$stmtMateriaProfessore->execute([
    ":idProfessore" => $idProfessore
]);

$professore = $stmtMateriaProfessore->fetch(PDO::FETCH_ASSOC);

if (!$professore) {
    die("Professore non trovato.");
}

$idMateria = $professore["ID_Materia"];


/* =========================
   AGGIUNGI VOTO
========================= */

// questa parte PARTE quanto l'URL e voti.php?azione=aggiungi&idStudente=123, quindi quando il professore vuole aggiungere un voto a uno studente specifico, e quindi viene passato l'id dello studente nella query string, e l'azione da eseguire e "aggiungi", allora eseguiamo il codice all'interno di questo blocco, che mostra un form per aggiungere un nuovo voto a quello studente, e quando il form viene inviato con una richiesta POST, allora prendiamo i dati dal form, li validiamo, e se sono validi, inseriamo il nuovo voto nel database associato a quello studente, al professore loggato, alla materia del professore, e alla classe dello studente


if ($azione === "aggiungi") {

// Controllo se lo studente e valido
    if (!isset($_GET["idStudente"]) || !is_numeric($_GET["idStudente"])) {
        header("Location: dashboard.php");
        exit;
    }

    $idStudente = intval($_GET["idStudente"]);
// verifica se il professore logato ha questo studente
    $sqlStudente = "SELECT 
                        Studente.ID_Studente,
                        Studente.ID_Classe,
                        Studente.Nome,
                        Studente.Cognome,
                        Classe.Numero,
                        Classe.Sezione
                    FROM Studente
                    JOIN Classe 
                        ON Studente.ID_Classe = Classe.ID_Classe
                    JOIN ProfessoreClasse
                        ON Classe.ID_Classe = ProfessoreClasse.ID_Classe
                    WHERE Studente.ID_Studente = :idStudente
                    AND ProfessoreClasse.ID_Professore = :idProfessore";

    $stmtStudente = $conn->prepare($sqlStudente);
    $stmtStudente->execute([
        ":idStudente" => $idStudente,
        ":idProfessore" => $idProfessore
    ]);

    $studente = $stmtStudente->fetch(PDO::FETCH_ASSOC);

    if (!$studente) {
        die("Accesso non autorizzato a questo studente.");
    }

    // il form e stato inviato, quindi processiamo i dati
    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $votoInput = $_POST["voto"] ?? "";
        $dataVoto = $_POST["dataVoto"];
        $descrizione = trim($_POST["descrizione"]);

        if (!in_array($votoInput, $votiConsentiti, true)) {
            $errore = "Voto non valido.";
        } else {

            $voto = floatval($votoInput);

            $sqlInserisci = "INSERT INTO Voto 
                                (ID_Materia, ID_Professore, ID_Studente, ID_Classe, Voto, DataVoto, Descrizione)
                             VALUES
                                (:idMateria, :idProfessore, :idStudente, :idClasse, :voto, :dataVoto, :descrizione)";

            $stmtInserisci = $conn->prepare($sqlInserisci);
            $stmtInserisci->execute([
                ":idMateria" => $idMateria,
                ":idProfessore" => $idProfessore,
                ":idStudente" => $studente["ID_Studente"],
                ":idClasse" => $studente["ID_Classe"],
                ":voto" => $voto,
                ":dataVoto" => $dataVoto,
                ":descrizione" => $descrizione
            ]);

            header("Location: studente.php?id=" . $studente["ID_Studente"]);
            exit;
        }
    }
}


/* =========================
   MODIFICA VOTO
========================= */

if ($azione === "modifica") {

    if (!isset($_GET["idVoto"]) || !is_numeric($_GET["idVoto"])) {
        header("Location: dashboard.php");
        exit;
    }

    $idVoto = intval($_GET["idVoto"]);

    $sqlVoto = "SELECT 
                    Voto.ID_Voto,
                    Voto.ID_Studente,
                    Voto.Voto,
                    Voto.DataVoto,
                    Voto.Descrizione,
                    Studente.Nome,
                    Studente.Cognome,
                    Classe.Numero,
                    Classe.Sezione
                FROM Voto
                JOIN Studente 
                    ON Voto.ID_Studente = Studente.ID_Studente
                JOIN Classe 
                    ON Voto.ID_Classe = Classe.ID_Classe
                WHERE Voto.ID_Voto = :idVoto
                AND Voto.ID_Professore = :idProfessore";

    $stmtVoto = $conn->prepare($sqlVoto);
    $stmtVoto->execute([
        ":idVoto" => $idVoto,
        ":idProfessore" => $idProfessore
    ]);

    $votoDaModificare = $stmtVoto->fetch(PDO::FETCH_ASSOC);

    if (!$votoDaModificare) {
        die("Voto non trovato o accesso non autorizzato.");
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $votoInput = $_POST["voto"] ?? "";
        $dataVoto = $_POST["dataVoto"];
        $descrizione = trim($_POST["descrizione"]);

        if (!in_array($votoInput, $votiConsentiti, true)) {
            $errore = "Voto non valido.";
        } else {

            $voto = floatval($votoInput);

            $sqlAggiorna = "UPDATE Voto
                            SET Voto = :voto,
                                DataVoto = :dataVoto,
                                Descrizione = :descrizione
                            WHERE ID_Voto = :idVoto
                            AND ID_Professore = :idProfessore";

            $stmtAggiorna = $conn->prepare($sqlAggiorna);
            $stmtAggiorna->execute([
                ":voto" => $voto,
                ":dataVoto" => $dataVoto,
                ":descrizione" => $descrizione,
                ":idVoto" => $idVoto,
                ":idProfessore" => $idProfessore
            ]);

            header("Location: studente.php?id=" . $votoDaModificare["ID_Studente"]);
            exit;
        }
    }
}


/* =========================
   ELIMINA VOTO
========================= */

if ($azione === "elimina") {

    if (!isset($_GET["idVoto"]) || !is_numeric($_GET["idVoto"])) {
        header("Location: dashboard.php");
        exit;
    }

    $idVoto = intval($_GET["idVoto"]);

    $sqlVoto = "SELECT 
                    Voto.ID_Voto,
                    Voto.ID_Studente,
                    Voto.Voto,
                    Voto.DataVoto,
                    Voto.Descrizione,
                    Studente.Nome,
                    Studente.Cognome
                FROM Voto
                JOIN Studente 
                    ON Voto.ID_Studente = Studente.ID_Studente
                WHERE Voto.ID_Voto = :idVoto
                AND Voto.ID_Professore = :idProfessore";

    $stmtVoto = $conn->prepare($sqlVoto);
    $stmtVoto->execute([
        ":idVoto" => $idVoto,
        ":idProfessore" => $idProfessore
    ]);

    $votoDaEliminare = $stmtVoto->fetch(PDO::FETCH_ASSOC);

    if (!$votoDaEliminare) {
        die("Voto non trovato o accesso non autorizzato.");
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $sqlElimina = "DELETE FROM Voto
                       WHERE ID_Voto = :idVoto
                       AND ID_Professore = :idProfessore";

        $stmtElimina = $conn->prepare($sqlElimina);
        $stmtElimina->execute([
            ":idVoto" => $idVoto,
            ":idProfessore" => $idProfessore
        ]);

        header("Location: studente.php?id=" . $votoDaEliminare["ID_Studente"]);
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Voti</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <h1>Gestione Voti</h1>

    <?php if ($errore != ""): ?>
        <p style="color: red;">
            <?php echo htmlspecialchars($errore); ?>
        </p>
    <?php endif; ?>


    <?php if ($azione === "aggiungi"): ?>

        <h2>Aggiungi voto</h2>

        <p>
            Studente:
            <strong>
                <?php echo htmlspecialchars($studente["Nome"] . " " . $studente["Cognome"]); ?>
            </strong>
        </p>

        <p>
            Classe:
            <strong>
                <?php echo htmlspecialchars($studente["Numero"] . $studente["Sezione"]); ?>
            </strong>
        </p>

        <p>
            Materia:
            <strong>
                <?php echo htmlspecialchars($_SESSION["Materia"]); ?>
            </strong>
        </p>

        <form method="POST">

            <label>Voto:</label>
            <br>
            <select name="voto" required>
                <option value="">Seleziona voto</option>

                <?php foreach ($votiConsentiti as $votoOpzione): ?>
                    <option value="<?php echo htmlspecialchars($votoOpzione); ?>">
                        <?php echo htmlspecialchars($votoOpzione); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label>Data:</label>
            <br>
            <input type="date" name="dataVoto" required>
            <br><br>

            <label>Descrizione:</label>
            <br>
            <textarea name="descrizione" rows="4" cols="40"></textarea>
            <br><br>

            <button type="submit">Salva voto</button>

        </form>

        <br>

        <a href="studente.php?id=<?php echo $studente["ID_Studente"]; ?>">
            Torna allo studente
        </a>


    <?php elseif ($azione === "modifica"): ?>

        <h2>Modifica voto</h2>

        <p>
            Studente:
            <strong>
                <?php echo htmlspecialchars($votoDaModificare["Nome"] . " " . $votoDaModificare["Cognome"]); ?>
            </strong>
        </p>

        <p>
            Classe:
            <strong>
                <?php echo htmlspecialchars($votoDaModificare["Numero"] . $votoDaModificare["Sezione"]); ?>
            </strong>
        </p>

        <form method="POST">

            <label>Voto:</label>
            <br>
            <select name="voto" required>
                <option value="">Seleziona voto</option>

                <?php foreach ($votiConsentiti as $votoOpzione): ?>
                    <option 
                        value="<?php echo htmlspecialchars($votoOpzione); ?>"
                        <?php 
                            if ((float)$votoDaModificare["Voto"] == (float)$votoOpzione) {
                                echo "selected";
                            }
                        ?>
                    >
                        <?php echo htmlspecialchars($votoOpzione); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label>Data:</label>
            <br>
            <input 
                type="date" 
                name="dataVoto" 
                value="<?php echo htmlspecialchars($votoDaModificare["DataVoto"]); ?>" 
                required
            >
            <br><br>

            <label>Descrizione:</label>
            <br>
            <textarea name="descrizione" rows="4" cols="40"><?php echo htmlspecialchars($votoDaModificare["Descrizione"]); ?></textarea>
            <br><br>

            <button type="submit">Aggiorna voto</button>

        </form>

        <br>

        <a href="studente.php?id=<?php echo $votoDaModificare["ID_Studente"]; ?>">
            Torna allo studente
        </a>


    <?php elseif ($azione === "elimina"): ?>

        <h2>Elimina voto</h2>

        <p>
            Sei sicuro di voler eliminare questo voto?
        </p>

        <p>
            Studente:
            <strong>
                <?php echo htmlspecialchars($votoDaEliminare["Nome"] . " " . $votoDaEliminare["Cognome"]); ?>
            </strong>
        </p>

        <p>
            Voto:
            <strong>
                <?php echo htmlspecialchars($votoDaEliminare["Voto"]); ?>
            </strong>
        </p>

        <p>
            Data:
            <strong>
                <?php echo htmlspecialchars($votoDaEliminare["DataVoto"]); ?>
            </strong>
        </p>

        <p>
            Descrizione:
            <strong>
                <?php echo htmlspecialchars($votoDaEliminare["Descrizione"]); ?>
            </strong>
        </p>

        <form method="POST">
            <button type="submit">Conferma eliminazione</button>
        </form>

        <br>

        <a href="studente.php?id=<?php echo $votoDaEliminare["ID_Studente"]; ?>">
            Annulla
        </a>

    <?php endif; ?>

    <hr>

    <a href="dashboard.php">Dashboard</a>
    |
    <a href="../logout.php">Logout</a>

</body>
</html>