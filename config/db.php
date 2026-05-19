<?php

$host = "localhost";
$dbname = "registro_scolastico";
$username = "root";
$password = "";

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// importantissimo poiche dice al PDO di mostrare l'errore se c'è un errore di connessione al database
} catch (PDOException $e) {
    die("Errore di connessione al database: " . $e->getMessage());
    //Blocca il programma
}