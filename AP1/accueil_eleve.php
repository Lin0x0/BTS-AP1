<?php
session_start();
include '_conf.php';

// Vérifier si l'utilisateur est connecté (élève)
if(!isset($_SESSION['utilisateur'])) {
    header('Location: index.php');
    exit();
}

$utilisateur = $_SESSION['utilisateur'];
$message_success = '';
$message_erreur = '';

// Vérifier si un message est passé via GET
if(isset($_GET['message']) && !empty($_GET['message'])) {
    $message_success = urldecode($_GET['message']);
}
if(isset($_GET['erreur']) && !empty($_GET['erreur'])) {
    $message_erreur = urldecode($_GET['erreur']);
}


$utilisateur = $_SESSION['utilisateur'];
$message_success = '';
$message_erreur = '';

// Vérifier si un message est passé via GET
if(isset($_GET['message']) && !empty($_GET['message'])) {
    if(isset($_GET['type']) && $_GET['type'] == 'success') {
        $message_success = urldecode($_GET['message']);
    } else {
        $message_erreur = urldecode($_GET['message']);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil Élève</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Espace Élève</h1>
            <p class="welcome-message">Bienvenue <?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></p>
            <a href="deconnexion.php" class="logout-btn">Déconnexion</a>
        </header>
        
        <nav>
            <ul class="nav-menu">
                <li><a href="accueil_eleve.php">Accueil</a></li>
                <li><a href="liste_cr.php">Liste des comptes rendus</a></li>
                <li><a href="creer_cr.php">Créer/modifier un compte rendu</a></li>
                <li><a href="commentaires.php">Commentaires</a></li>
                <li><a href="profil.php">Mon Profil</a></li>
            </ul>
        </nav>
        
        <main>
            <h2>Les comptes rendus</h2>
            
            <?php if($message_success): ?>
                <div class="message success"><?php echo htmlspecialchars($message_success); ?></div>
            <?php endif; ?>
            
            <?php if($message_erreur): ?>
                <div class="message error"><?php echo htmlspecialchars($message_erreur); ?></div>
            <?php endif; ?>
            
            <div class="cr-list">
                <h3 style="color: #4a5568; margin-bottom: 20px; text-align: left; border-bottom: 2px solid #cbd5e0; padding-bottom: 10px;">
                    Derniers comptes rendus
                </h3>
                
                <?php
                // Récupérer les comptes rendus de l'élève
                $bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
                $num_utilisateur = $utilisateur['num'];
                
                $requete = "SELECT * FROM cr WHERE num_utilisateur = ? ORDER BY date DESC LIMIT 5";
                $stmt = mysqli_prepare($bdd, $requete);
                mysqli_stmt_bind_param($stmt, "i", $num_utilisateur);
                mysqli_stmt_execute($stmt);
                $resultat = mysqli_stmt_get_result($stmt);
                
                if(mysqli_num_rows($resultat) > 0) {
                    while($cr = mysqli_fetch_assoc($resultat)) {
                        echo '<div class="cr-item">';
                        echo '<span class="cr-date">📅 Compte rendu du ' . htmlspecialchars($cr['date']) . '</span>';
                        echo '<p class="cr-preview">' . nl2br(htmlspecialchars(substr($cr['description'], 0, 150))) . 
                             (strlen($cr['description']) > 150 ? '...' : '') . '</p>';
                        echo '<div class="cr-actions">';
                        echo '<a href="creer_cr.php?num=' . $cr['num'] . '" class="btn btn-secondary">Modifier</a>';
                        echo '<a href="supprimer_cr.php?num=' . $cr['num'] . '" class="btn btn-danger" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer ce compte rendu ? Cette action est irréversible.\');">Supprimer</a>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="empty-state">';
                    echo '📝<br>';
                    echo '<p>Aucun compte rendu pour le moment.</p>';
                    echo '</div>';
                }
                mysqli_close($bdd);
                ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="creer_cr.php" class="btn btn-success">➕ Créer un nouveau compte rendu</a>
            </div>
        </main>
    </div>
</body>
</html>