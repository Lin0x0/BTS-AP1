<?php
session_start();
include '_conf.php';

// Vérifier si l'utilisateur est connecté (élève)
if(!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['type'] != 0) {
    header('Location: index.php');
    exit();
}

$utilisateur = $_SESSION['utilisateur'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Commentaires</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Espace Élève</h1>
            <p class="welcome-message">Bienvenue <?php echo htmlspecialchars($utilisateur['prenom']); ?></p>
            <a href="deconnexion.php" class="logout-btn">Déconnexion</a>
        </header>
        
        <nav>
            <ul class="nav-menu">
                <li><a href="accueil_eleve.php">Accueil</a></li>
                <li><a href="liste_cr.php">Liste des comptes rendus</a></li>
                <li><a href="creer_cr.php">Créer/modifier un compte rendu</a></li>
                <li><a href="commentaires.php">Commentaires</a></li>
            </ul>
        </nav>
        
        <main>
            <h2>Commentaires de vos comptes rendus</h2>
            
            <div class="comment-section">
                <?php
                // Récupérer les commentaires
                $bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
                $num_utilisateur = $utilisateur['num'];
                
                $requete = "SELECT c.*, com.contenu, com.date_commentaire, u.prenom as prof_prenom, u.nom as prof_nom
                           FROM cr c 
                           JOIN commentaires com ON c.num = com.num_cr 
                           JOIN utilisateur u ON com.num_professeur = u.num 
                           WHERE c.num_utilisateur = ? 
                           ORDER BY com.date_commentaire DESC";
                $stmt = mysqli_prepare($bdd, $requete);
                mysqli_stmt_bind_param($stmt, "i", $num_utilisateur);
                mysqli_stmt_execute($stmt);
                $resultat = mysqli_stmt_get_result($stmt);
                
                if(mysqli_num_rows($resultat) > 0) {
                    while($commentaire = mysqli_fetch_assoc($resultat)) {
                        echo '<div class="comment">';
                        echo '<p><strong>📅 Compte rendu du ' . htmlspecialchars($commentaire['date']) . '</strong></p>';
                        echo '<p><strong>👨‍🏫 Commentaire de ' . htmlspecialchars($commentaire['prof_prenom'] . ' ' . $commentaire['prof_nom']) . ' :</strong></p>';
                        echo '<p>' . nl2br(htmlspecialchars($commentaire['contenu'])) . '</p>';
                        echo '<p class="comment-date">Posté le ' . date('d/m/Y à H:i', strtotime($commentaire['date_commentaire'])) . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="empty-state">';
                    echo '💬<br>';
                    echo '<p>Aucun commentaire pour le moment.</p>';
                    echo '<p>Vos comptes rendus n\'ont pas encore été commentés par votre professeur.</p>';
                    echo '</div>';
                }
                mysqli_close($bdd);
                ?>
            </div>
        </main>
    </div>
</body>
</html>