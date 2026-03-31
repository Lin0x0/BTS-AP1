<?php
session_start();
include '_conf.php';

// Vérifier si l'utilisateur est connecté (élève)
if(!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['type'] != 0) {
    header('Location: index.php');
    exit();
}

$utilisateur = $_SESSION['utilisateur'];
$message = '';
$erreur = '';

// Vérifier si un numéro de CR est passé en GET
if(isset($_GET['num'])) {
    $num_cr = intval($_GET['num']);
    
    // Connexion à la base de données
    $bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
    
    // Vérifier que le CR appartient bien à l'utilisateur connecté
    $requete_verif = "SELECT * FROM cr WHERE num = ? AND num_utilisateur = ?";
    $stmt_verif = mysqli_prepare($bdd, $requete_verif);
    $num_utilisateur = $utilisateur['num'];
    mysqli_stmt_bind_param($stmt_verif, "ii", $num_cr, $num_utilisateur);
    mysqli_stmt_execute($stmt_verif);
    $resultat_verif = mysqli_stmt_get_result($stmt_verif);
    
    if(mysqli_num_rows($resultat_verif) > 0) {
        // Le CR existe et appartient à l'utilisateur, on peut le supprimer
        
        // Supprimer d'abord les commentaires associés (si la table existe)
        $requete_delete_comments = "DELETE FROM commentaires WHERE num_cr = ?";
        $stmt_comments = mysqli_prepare($bdd, $requete_delete_comments);
        mysqli_stmt_bind_param($stmt_comments, "i", $num_cr);
        mysqli_stmt_execute($stmt_comments);
        
        // Puis supprimer le CR
        $requete_delete = "DELETE FROM cr WHERE num = ?";
        $stmt_delete = mysqli_prepare($bdd, $requete_delete);
        mysqli_stmt_bind_param($stmt_delete, "i", $num_cr);
        
        if(mysqli_stmt_execute($stmt_delete)) {
            $message = "Le compte rendu a été supprimé avec succès!";
        } else {
            $erreur = "Erreur lors de la suppression du compte rendu";
        }
        
        mysqli_stmt_close($stmt_delete);
        mysqli_stmt_close($stmt_comments);
    } else {
        $erreur = "Ce compte rendu n'existe pas ou ne vous appartient pas";
    }
    
    mysqli_stmt_close($stmt_verif);
    mysqli_close($bdd);
    
    // Redirection vers la page d'accueil élève avec un message
    header('Location: accueil_eleve.php?message=' . urlencode($message) . '&erreur=' . urlencode($erreur));
    exit();
} else {
    // Si aucun numéro n'est fourni, rediriger vers l'accueil
    header('Location: accueil_eleve.php');
    exit();
}
?>