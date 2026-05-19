<?php

session_start();

require_once "../config/db.php";

if (!isset($_SESSION["ID_Professore"])) {
    header("Location: ../login.php");
    exit;
}

$idProfessore = $_SESSION["ID_Professore"];
$azione = $_GET["azione"] ?? "lista";
$errore = "";

$azioniConsentite = ["lista", "aggiungi", "modifica", "elimina"];

if (!in_array($azione, $azioniConsentite)) {
    header("Location: calendario.php");
    exit;
}

$tipiEvento = ["Verifica", "Compito", "Interrogazione", "Avviso"];

/*
    Recuperiamo solo le classi del professore loggato.
    Così il professore può creare eventi solo per le sue classi.
*/
$sqlClassi = "SELECT 
                    Classe.ID_Classe,
                    Classe.Numero,
                    Classe.Sezione
              FROM Classe
              JOIN ProfessoreClasse
                    ON Classe.ID_Classe = ProfessoreClasse.ID_Classe
              WHERE ProfessoreClasse.ID_Professore = :idProfessore
              ORDER BY Classe.Numero, Classe.Sezione";

$stmtClassi = $conn->prepare($sqlClassi);
$stmtClassi->execute([
    ":idProfessore" => $idProfessore
]);

$classi = $stmtClassi->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   AGGIUNGI EVENTO
========================= */

if ($azione === "aggiungi") {

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $idClasse = intval($_POST["idClasse"]);
        $tipoEvento = $_POST["tipoEvento"] ?? "";
        $dataEvento = $_POST["dataEvento"];
        $descrizione = trim($_POST["descrizione"]);

        $classeAutorizzata = false;

        foreach ($classi as $classe) {
            if ($classe["ID_Classe"] == $idClasse) {
                $classeAutorizzata = true;
            }
        }

        if (!$classeAutorizzata) {
            $errore = "Classe non autorizzata.";
        } elseif (!in_array($tipoEvento, $tipiEvento, true)) {
            $errore = "Tipo evento non valido.";
        } else {

            $sqlInserisci = "INSERT INTO Evento
                                (ID_Classe, ID_Professore, TipoEvento, DataEvento, Descrizione)
                             VALUES
                                (:idClasse, :idProfessore, :tipoEvento, :dataEvento, :descrizione)";

            $stmtInserisci = $conn->prepare($sqlInserisci);
            $stmtInserisci->execute([
                ":idClasse" => $idClasse,
                ":idProfessore" => $idProfessore,
                ":tipoEvento" => $tipoEvento,
                ":dataEvento" => $dataEvento,
                ":descrizione" => $descrizione
            ]);

            header("Location: calendario.php");
            exit;
        }
    }
}


/* =========================
   MODIFICA EVENTO
========================= */

if ($azione === "modifica") {

    if (!isset($_GET["idEvento"]) || !is_numeric($_GET["idEvento"])) {
        header("Location: calendario.php");
        exit;
    }

    $idEvento = intval($_GET["idEvento"]);

    $sqlEvento = "SELECT 
                    Evento.ID_Evento,
                    Evento.ID_Classe,
                    Evento.TipoEvento,
                    Evento.DataEvento,
                    Evento.Descrizione,
                    Classe.Numero,
                    Classe.Sezione
                  FROM Evento
                  JOIN Classe
                    ON Evento.ID_Classe = Classe.ID_Classe
                  WHERE Evento.ID_Evento = :idEvento
                  AND Evento.ID_Professore = :idProfessore";

    $stmtEvento = $conn->prepare($sqlEvento);
    $stmtEvento->execute([
        ":idEvento" => $idEvento,
        ":idProfessore" => $idProfessore
    ]);

    $eventoDaModificare = $stmtEvento->fetch(PDO::FETCH_ASSOC);

    if (!$eventoDaModificare) {
        die("Evento non trovato o accesso non autorizzato.");
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $idClasse = intval($_POST["idClasse"]);
        $tipoEvento = $_POST["tipoEvento"] ?? "";
        $dataEvento = $_POST["dataEvento"];
        $descrizione = trim($_POST["descrizione"]);

        $classeAutorizzata = false;

        foreach ($classi as $classe) {
            if ($classe["ID_Classe"] == $idClasse) {
                $classeAutorizzata = true;
            }
        }

        if (!$classeAutorizzata) {
            $errore = "Classe non autorizzata.";
        } elseif (!in_array($tipoEvento, $tipiEvento, true)) {
            $errore = "Tipo evento non valido.";
        } else {

            $sqlAggiorna = "UPDATE Evento
                            SET ID_Classe = :idClasse,
                                TipoEvento = :tipoEvento,
                                DataEvento = :dataEvento,
                                Descrizione = :descrizione
                            WHERE ID_Evento = :idEvento
                            AND ID_Professore = :idProfessore";

            $stmtAggiorna = $conn->prepare($sqlAggiorna);
            $stmtAggiorna->execute([
                ":idClasse" => $idClasse,
                ":tipoEvento" => $tipoEvento,
                ":dataEvento" => $dataEvento,
                ":descrizione" => $descrizione,
                ":idEvento" => $idEvento,
                ":idProfessore" => $idProfessore
            ]);

            header("Location: calendario.php");
            exit;
        }
    }
}


/* =========================
   ELIMINA EVENTO
========================= */

if ($azione === "elimina") {

    if (!isset($_GET["idEvento"]) || !is_numeric($_GET["idEvento"])) {
        header("Location: calendario.php");
        exit;
    }

    $idEvento = intval($_GET["idEvento"]);

    $sqlEvento = "SELECT 
                    Evento.ID_Evento,
                    Evento.TipoEvento,
                    Evento.DataEvento,
                    Evento.Descrizione,
                    Classe.Numero,
                    Classe.Sezione
                  FROM Evento
                  JOIN Classe
                    ON Evento.ID_Classe = Classe.ID_Classe
                  WHERE Evento.ID_Evento = :idEvento
                  AND Evento.ID_Professore = :idProfessore";

    $stmtEvento = $conn->prepare($sqlEvento);
    $stmtEvento->execute([
        ":idEvento" => $idEvento,
        ":idProfessore" => $idProfessore
    ]);

    $eventoDaEliminare = $stmtEvento->fetch(PDO::FETCH_ASSOC);

    if (!$eventoDaEliminare) {
        die("Evento non trovato o accesso non autorizzato.");
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $sqlElimina = "DELETE FROM Evento
                       WHERE ID_Evento = :idEvento
                       AND ID_Professore = :idProfessore";

        $stmtElimina = $conn->prepare($sqlElimina);
        $stmtElimina->execute([
            ":idEvento" => $idEvento,
            ":idProfessore" => $idProfessore
        ]);

        header("Location: calendario.php");
        exit;
    }
}


/* =========================
   LISTA EVENTI
========================= */

$sqlEventi = "SELECT
                Evento.ID_Evento,
                Evento.TipoEvento,
                Evento.DataEvento,
                Evento.Descrizione,
                Classe.Numero,
                Classe.Sezione
              FROM Evento
              JOIN Classe
                ON Evento.ID_Classe = Classe.ID_Classe
              WHERE Evento.ID_Professore = :idProfessore
              ORDER BY Evento.DataEvento ASC";

$stmtEventi = $conn->prepare($sqlEventi);
$stmtEventi->execute([
    ":idProfessore" => $idProfessore
]);

$eventi = $stmtEventi->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Calendario</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <h1>Calendario</h1>

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

    <?php if ($errore != ""): ?>
        <p style="color: red;">
            <?php echo htmlspecialchars($errore); ?>
        </p>
    <?php endif; ?>

    <hr>

    <?php if ($azione === "lista"): ?>

        <h2>Eventi programmati</h2>

        <p>
            <a href="calendario.php?azione=aggiungi">Aggiungi evento</a>
        </p>

        <?php if (count($eventi) > 0): ?>

            <table border="1" cellpadding="8">
                <tr>
                    <th>Data</th>
                    <th>Classe</th>
                    <th>Tipo</th>
                    <th>Descrizione</th>
                    <th>Azioni</th>
                </tr>

                <?php foreach ($eventi as $evento): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($evento["DataEvento"]); ?></td>

                        <td>
                            <?php echo htmlspecialchars($evento["Numero"] . $evento["Sezione"]); ?>
                        </td>

                        <td><?php echo htmlspecialchars($evento["TipoEvento"]); ?></td>

                        <td><?php echo htmlspecialchars($evento["Descrizione"]); ?></td>

                        <td>
                            <a href="calendario.php?azione=modifica&idEvento=<?php echo $evento["ID_Evento"]; ?>">
                                Modifica
                            </a>
                            |
                            <a href="calendario.php?azione=elimina&idEvento=<?php echo $evento["ID_Evento"]; ?>">
                                Elimina
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

            </table>

        <?php else: ?>

            <p>Nessun evento presente.</p>

        <?php endif; ?>


    <?php elseif ($azione === "aggiungi"): ?>

        <h2>Aggiungi evento</h2>

        <form method="POST">

            <label>Classe:</label>
            <br>
            <select name="idClasse" required>
                <option value="">Seleziona classe</option>

                <?php foreach ($classi as $classe): ?>
                    <option value="<?php echo $classe["ID_Classe"]; ?>">
                        <?php echo htmlspecialchars($classe["Numero"] . $classe["Sezione"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label>Tipo evento:</label>
            <br>
            <select name="tipoEvento" required>
                <option value="">Seleziona tipo</option>

                <?php foreach ($tipiEvento as $tipo): ?>
                    <option value="<?php echo htmlspecialchars($tipo); ?>">
                        <?php echo htmlspecialchars($tipo); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label>Data:</label>
            <br>
            <input type="date" name="dataEvento" required>
            <br><br>

            <label>Descrizione:</label>
            <br>
            <textarea name="descrizione" rows="4" cols="40"></textarea>
            <br><br>

            <button type="submit">Salva evento</button>

        </form>

        <br>

        <a href="calendario.php">Torna al calendario</a>


    <?php elseif ($azione === "modifica"): ?>

        <h2>Modifica evento</h2>

        <form method="POST">

            <label>Classe:</label>
            <br>
            <select name="idClasse" required>
                <?php foreach ($classi as $classe): ?>
                    <option 
                        value="<?php echo $classe["ID_Classe"]; ?>"
                        <?php
                            if ($classe["ID_Classe"] == $eventoDaModificare["ID_Classe"]) {
                                echo "selected";
                            }
                        ?>
                    >
                        <?php echo htmlspecialchars($classe["Numero"] . $classe["Sezione"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label>Tipo evento:</label>
            <br>
            <select name="tipoEvento" required>
                <?php foreach ($tipiEvento as $tipo): ?>
                    <option 
                        value="<?php echo htmlspecialchars($tipo); ?>"
                        <?php
                            if ($tipo == $eventoDaModificare["TipoEvento"]) {
                                echo "selected";
                            }
                        ?>
                    >
                        <?php echo htmlspecialchars($tipo); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label>Data:</label>
            <br>
            <input 
                type="date" 
                name="dataEvento" 
                value="<?php echo htmlspecialchars($eventoDaModificare["DataEvento"]); ?>" 
                required
            >
            <br><br>

            <label>Descrizione:</label>
            <br>
            <textarea name="descrizione" rows="4" cols="40"><?php echo htmlspecialchars($eventoDaModificare["Descrizione"]); ?></textarea>
            <br><br>

            <button type="submit">Aggiorna evento</button>

        </form>

        <br>

        <a href="calendario.php">Torna al calendario</a>


    <?php elseif ($azione === "elimina"): ?>

        <h2>Elimina evento</h2>

        <p>Sei sicuro di voler eliminare questo evento?</p>

        <p>
            Classe:
            <strong>
                <?php echo htmlspecialchars($eventoDaEliminare["Numero"] . $eventoDaEliminare["Sezione"]); ?>
            </strong>
        </p>

        <p>
            Tipo:
            <strong>
                <?php echo htmlspecialchars($eventoDaEliminare["TipoEvento"]); ?>
            </strong>
        </p>

        <p>
            Data:
            <strong>
                <?php echo htmlspecialchars($eventoDaEliminare["DataEvento"]); ?>
            </strong>
        </p>

        <p>
            Descrizione:
            <strong>
                <?php echo htmlspecialchars($eventoDaEliminare["Descrizione"]); ?>
            </strong>
        </p>

        <form method="POST">
            <button type="submit">Conferma eliminazione</button>
        </form>

        <br>

        <a href="calendario.php">Annulla</a>

    <?php endif; ?>

    <hr>

    <a href="dashboard.php">Dashboard</a>
    |
    <a href="../logout.php">Logout</a>

</body>
</html>