<?php
// avvia la sessione PHP, poiche ricordiamo che e stateless quindi ci sono metodi attraverso cvooki e session che ci permettono di mantenere lo stato tra una pagina e l'altra, in questo caso ci serve per mantenere l'utente loggato tra una pagina e l'altra
session_start();
// comunicazione con mysql
require_once "config/db.php";

//Errori di Login
$errore = "";
// se esiste gia un valore nella sessione ID_Professore, allora vuol dire che l'utente e gia loggato, quindi lo reindirizzo alla dashboard
if (isset($_SESSION["ID_Professore"])) {
    header("Location: pages/dashboard.php");
    exit;
}
// Quando premi accedi viene inviata una richiesta POST, quindi se il metodo della richiesta e POST allora eseguo il codice al suo interno
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $mail = $_POST["mail"];
    $password = $_POST["password"];

    $sql = "SELECT 
                Professore.ID_Professore,
                Professore.Nome,
                Professore.Cognome,
                Professore.Mail,
                Professore.PasswordHash,
                Materia.NomeMateria
            FROM Professore
            JOIN Materia ON Professore.ID_Materia = Materia.ID_Materia
            WHERE Professore.Mail = :mail";

    // evitiamo di concatenare direttamente la variabile $mail nella query SQL per prevenire attacchi di SQL Injection, invece usiamo i parametri con i placeholder :mail e poi li bindiamo con i valori reali usando l'array associativo nell'esecuzione della query
    // Importantissimo per la sicurezza, rimuovendo le possibilita di SQL injection, che e una delle vulnerabilita piu comuni nelle applicazioni web, e permette agli attaccanti di eseguire comandi SQL dannosi sul database
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":mail" => $mail
    ]);

    $professore = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se il professore esiste e la password hashata corrisponde alla password inserita dall'utente, allora logghiamo l'utente, altrimenti mostriamo un messaggio di errore
    if ($professore && password_verify($password, $professore["PasswordHash"])) {

        $_SESSION["ID_Professore"] = $professore["ID_Professore"];
        $_SESSION["Nome"] = $professore["Nome"];
        $_SESSION["Cognome"] = $professore["Cognome"];
        $_SESSION["Mail"] = $professore["Mail"];
        $_SESSION["Materia"] = $professore["NomeMateria"];

        header("Location: pages/dashboard.php");
        exit;

    } else {
        $errore = "Email o password non corretti.";
    }
}

?>


<!-- INIZIO HTML cioe lato pronto per l'utente, mentre il php sopra e lato server, che viene eseguito prima di mostrare la pagina all'utente, e serve per gestire la logica di login, mentre l'html sotto e la parte visiva
che viene mostrata all'utente, e contiene il form di login e eventuali messaggi di errore -->




<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login Registro Scolastico</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <h1>Login Professore</h1>

    <?php if ($errore != ""): ?>
        <p style="color: red;"><?php echo $errore; ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <label>Email:</label>
        <br>
        <input type="email" name="mail" required>
        <br><br>

        <label>Password:</label>
        <br>
        <input type="password" name="password" required>
        <br><br>

        <button type="submit">Accedi</button>

    </form>

</body>
</html>