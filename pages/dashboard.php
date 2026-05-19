<?php

session_start();

require_once "../config/db.php";

// Se il valore che vinee trasmesso nella sessione ID_Professore non esiste, allora vuol dire che l'utente non e loggato, quindi lo reindirizzo alla pagina di login
if (!isset($_SESSION["ID_Professore"])) {
    header("Location: ../login.php");
    exit;
}

$idProfessore = $_SESSION["ID_Professore"];

$sql = "SELECT 
            Classe.ID_Classe,
            Classe.Numero,
            Classe.Sezione
        FROM ProfessoreClasse
        JOIN Classe ON ProfessoreClasse.ID_Classe = Classe.ID_Classe
        WHERE ProfessoreClasse.ID_Professore = :idProfessore";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ":idProfessore" => $idProfessore
]);

$classi = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Professore</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

    <main class="page-shell">
        <section class="dashboard-hero">
            <div class="hero-content">
                <p class="eyebrow">Registro Scolastico Web</p>
                <h1>Dashboard Professore</h1>
                <p class="hero-subtitle">
                    Benvenuto,
                    <strong>
                        <?php echo $_SESSION["Nome"] . " " . $_SESSION["Cognome"]; ?>
                    </strong>
                </p>
                <p class="hero-subtitle">
                    Materia:
                    <strong>
                        <?php echo $_SESSION["Materia"]; ?>
                    </strong>
                </p>
            </div>

            <nav class="hero-actions" aria-label="Azioni dashboard">
                <a class="btn btn-secondary" href="calendario.php">Calendario</a>
                <a class="btn btn-danger" href="../logout.php">Logout</a>
            </nav>
        </section>

        <section class="stat-grid" aria-label="Riepilogo dashboard">
            <article class="stat-card">
                <span>Professore</span>
                <strong><?php echo $_SESSION["Nome"] . " " . $_SESSION["Cognome"]; ?></strong>
            </article>

            <article class="stat-card">
                <span>Materia</span>
                <strong><?php echo $_SESSION["Materia"]; ?></strong>
            </article>

            <article class="stat-card">
                <span>Classi gestite</span>
                <strong><?php echo count($classi); ?></strong>
            </article>
        </section>

        <section class="dashboard-section">
            <div class="section-heading">
                <div>
                    <p class="section-kicker">Accesso rapido</p>
                    <h2>Classi gestite</h2>
                </div>
            </div>

            <?php if (count($classi) > 0): ?>

                <div class="card-grid">
                    <?php foreach ($classi as $classe): ?>
                        <a class="class-card" href="classe.php?id=<?php echo $classe["ID_Classe"]; ?>">
                            <span class="class-meta">Classe</span>
                            <span class="class-code"><?php echo $classe["Numero"] . $classe["Sezione"]; ?></span>
                            <span class="class-caption">Apri registro classe</span>
                        </a>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>

                <div class="empty-state">
                    <p>Nessuna classe assegnata.</p>
                </div>

            <?php endif; ?>
        </section>
    </main>
</body>
</html>
