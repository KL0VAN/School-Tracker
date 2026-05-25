<?php

session_start();

require_once "../config/db.php";

if (!isset($_SESSION["ID_Professore"]) || $_SESSION["IsAmministratore"] != 1) {
    header("Location: ../login.php");
    exit;
}

$sqlClassi = "SELECT 
                ID_Classe,
                Numero,
                Sezione
              FROM Classe
              ORDER BY Numero, Sezione";

$stmtClassi = $conn->prepare($sqlClassi);
$stmtClassi->execute();

$classi = $stmtClassi->fetchAll(PDO::FETCH_ASSOC);

$sqlProfessori = "SELECT 
                    Professore.Nome,
                    Professore.Cognome,
                    Materia.NomeMateria,
                    Classe.Numero,
                    Classe.Sezione
                  FROM Professore
                  LEFT JOIN Materia 
                    ON Professore.ID_Materia = Materia.ID_Materia
                  LEFT JOIN ProfessoreClasse 
                    ON Professore.ID_Professore = ProfessoreClasse.ID_Professore
                  LEFT JOIN Classe 
                    ON ProfessoreClasse.ID_Classe = Classe.ID_Classe
                  WHERE Professore.IsAmministratore = 0
                  ORDER BY Professore.Cognome, Classe.Sezione";

$stmtProfessori = $conn->prepare($sqlProfessori);
$stmtProfessori->execute();

$professori = $stmtProfessori->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Amministrazione</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<main class="page-shell">

    <section class="dashboard-hero">

        <div class="hero-content">
            <p class="eyebrow">Registro Scolastico Web</p>
            <h1>Dashboard Amministrazione</h1>

            <p class="hero-subtitle">
                Amministratore:
                <strong>
                    <?php echo htmlspecialchars($_SESSION["Nome"] . " " . $_SESSION["Cognome"]); ?>
                </strong>
            </p>
        </div>

        <nav class="hero-actions">
            <a class="btn btn-danger" href="../logout.php">Logout</a>
        </nav>

    </section>


    <section class="dashboard-section">

        <div class="section-heading">
            <div>
                <p class="section-kicker">Accesso rapido</p>
                <h2>Tutte le classi della scuola</h2>
            </div>
        </div>

        <div class="card-grid">

            <?php foreach ($classi as $classe): ?>

                <a class="class-card" href="admin_classe.php?id=<?php echo $classe["ID_Classe"]; ?>">
                    <span class="class-meta">Classe</span>
                    <span class="class-code">
                        <?php echo htmlspecialchars($classe["Numero"] . $classe["Sezione"]); ?>
                    </span>
                    <span class="class-caption">Gestisci studenti</span>
                </a>

            <?php endforeach; ?>

        </div>

    </section>


    <section class="dashboard-section" style="margin-top: 30px;">

        <div class="section-heading">
            <div>
                <p class="section-kicker">Controllo interno</p>
                <h2>Professori e classi assegnate</h2>
            </div>
        </div>

        <table>
            <tr>
                <th>Professore</th>
                <th>Materia</th>
                <th>Classe</th>
            </tr>

            <?php foreach ($professori as $prof): ?>
                <tr>
                    <td><?php echo htmlspecialchars($prof["Nome"] . " " . $prof["Cognome"]); ?></td>
                    <td><?php echo htmlspecialchars($prof["NomeMateria"]); ?></td>
                    <td>
                        <?php 
                            if ($prof["Numero"] !== null) {
                                echo htmlspecialchars($prof["Numero"] . $prof["Sezione"]);
                            } else {
                                echo "-";
                            }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

    </section>

</main>

</body>
</html>