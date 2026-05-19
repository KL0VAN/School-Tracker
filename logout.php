<?php
// Importante per fare il logout complete, inizia la sessione, altrimenti non possiamo accedere alla sessione e quindi non possiamo distruggerla
session_start();
// Svuota tutti i valori della sessione
session_unset();
// Distrugge la sessione, eliminando completamente la sessione e tutti i dati associati ad essa
session_destroy();

// Dopo aver distrutto la sessione, reindirizza l'utente alla pagina di login
header("Location: login.php");
// applicchiamo il best practices
exit;

?>