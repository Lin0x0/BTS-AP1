<?php
session_start();
include '_conf.php';

// Vérifier si l'utilisateur est connecté (élève)
if(!isset($_SESSION['utilisateur'])) {
    header('Location: index.php');
    exit();
}

$utilisateur = $_SESSION['utilisateur'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des comptes rendus</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Espace Élève</h1>
            <p>Bienvenue <?php echo htmlspecialchars($utilisateur['prenom']); ?></p>
            <a href="deconnexion.php" class="btn">Déconnexion</a>
        </header>
        
        <nav>
            <ul>
                <li><a href="accueil_eleve.php">Accueil</a></li>
                <li><a href="liste_cr.php">Liste des comptes rendus</a></li>
                <li><a href="creer_cr.php">Créer/modifier un compte rendu</a></li>
                <li><a href="commentaires.php">Commentaires</a></li>
            </ul>
        </nav>
        
        <main>
            <h2>Liste des comptes rendus</h2>
            
            <div class="cr-list">
                <?php
                // Récupérer tous les comptes rendus de l'élève
                $bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
                $num_utilisateur = $utilisateur['num'];
                
                $requete = "SELECT * FROM cr WHERE num_utilisateur = ? ORDER BY date DESC";
                $stmt = mysqli_prepare($bdd, $requete);
                mysqli_stmt_bind_param($stmt, "i", $num_utilisateur);
                mysqli_stmt_execute($stmt);
                $resultat = mysqli_stmt_get_result($stmt);
                
                if(mysqli_num_rows($resultat) > 0) {
                    while($cr = mysqli_fetch_assoc($resultat)) {
                        echo '<div class="cr-item">';
                        echo '<span class="cr-date">Compte rendu du ' . htmlspecialchars($cr['date']) . '</span>';
                        echo '<p>' . nl2br(htmlspecialchars($cr['description'])) . '</p>';
                        echo '<div class="cr-actions">';
                        echo '<a href="creer_cr.php?num=' . $cr['num'] . '" class="btn">Modifier</a>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>Aucun compte rendu pour le moment.</p>';
                }
                mysqli_close($bdd);
                ?>
            </div>
            
            <a href="creer_cr.php" class="btn">Créer un nouveau compte rendu</a>
        </main>
    </div>
</body>
</html>