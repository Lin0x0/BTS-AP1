<?php
session_start();
include '_conf.php';

// Vérifier si l'utilisateur est connecté (professeur)
if(!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['type'] != 1) {
    header('Location: index.php');
    exit();
}

$utilisateur = $_SESSION['utilisateur'];
$message = '';

// Traitement du marquage "vu" depuis la page de commentaire
if(isset($_GET['marquer_vu'])) {
    $bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
    $num_cr = $_GET['marquer_vu'];
    
    $requete = "UPDATE cr SET vu = 1 WHERE num = ?";
    $stmt = mysqli_prepare($bdd, $requete);
    mysqli_stmt_bind_param($stmt, "i", $num_cr);
    
    if(mysqli_stmt_execute($stmt)) {
        $message = "Compte rendu marqué comme vu!";
    } else {
        $message = "Erreur lors du marquage";
    }
    mysqli_close($bdd);
}

// Traitement du formulaire de commentaire
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['num_cr']) && isset($_POST['commentaire'])) {
    $bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
    $num_cr = $_POST['num_cr'];
    $commentaire = $_POST['commentaire'];
    $num_professeur = $utilisateur['num'];
    
    $requete = "INSERT INTO commentaires (num_cr, num_professeur, contenu) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($bdd, $requete);
    mysqli_stmt_bind_param($stmt, "iis", $num_cr, $num_professeur, $commentaire);
    
    if(mysqli_stmt_execute($stmt)) {
        $message = "Commentaire ajouté avec succès!";
        
        // Marquer automatiquement comme vu lors de l'ajout d'un commentaire
        $requete_vu = "UPDATE cr SET vu = 1 WHERE num = ?";
        $stmt_vu = mysqli_prepare($bdd, $requete_vu);
        mysqli_stmt_bind_param($stmt_vu, "i", $num_cr);
        mysqli_stmt_execute($stmt_vu);
        
    } else {
        $message = "Erreur lors de l'ajout du commentaire";
    }
    mysqli_close($bdd);
}

// Récupérer les CR à commenter
$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
$requete = "SELECT c.*, u.prenom, u.nom 
           FROM cr c 
           JOIN utilisateur u ON c.num_utilisateur = u.num 
           ORDER BY c.vu ASC, c.date DESC";
$resultat = mysqli_query($bdd, $requete);
$comptes_rendus = mysqli_fetch_all($resultat, MYSQLI_ASSOC);
mysqli_close($bdd);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Commenter les comptes rendus</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Espace Professeur</h1>
            <p class="welcome-message">Bienvenue <?php echo htmlspecialchars($utilisateur['prenom']); ?></p>
            <a href="deconnexion.php" class="logout-btn">Déconnexion</a>
        </header>
        
        <nav>
            <ul class="nav-menu">
                <li><a href="accueil_prof.php">Accueil</a></li>
                <li><a href="liste_cr_eleves.php">Comptes rendus élèves</a></li>
                <li><a href="commenter_cr.php">Commenter les CR</a></li>
                <li><a href="gestion_stages.php">Gestion des stages</a></li>
            </ul>
        </nav>
        
        <main>
            <h2>Commenter les comptes rendus</h2>
            
            <?php if($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="cr-list">
                <?php foreach($comptes_rendus as $cr): ?>
                <div class="cr-item <?php echo $cr['vu'] ? 'statut-vu' : 'statut-non-vu'; ?>">
                    <div class="cr-header">
                        <div class="cr-info">
                            <span class="cr-date">📅 CR du <?php echo htmlspecialchars($cr['date']); ?> par <?php echo htmlspecialchars($cr['prenom'] . ' ' . $cr['nom']); ?></span>
                            <span class="badge <?php echo $cr['vu'] ? 'badge-vu' : 'badge-non-vu'; ?>">
                                <?php echo $cr['vu'] ? '✅ Lu' : '📖 Non lu'; ?>
                            </span>
                        </div>
                        <div class="cr-actions">
                            <?php if(!$cr['vu']): ?>
                                <a href="commenter_cr.php?marquer_vu=<?php echo $cr['num']; ?>" class="btn btn-success" style="padding: 8px 12px; font-size: 0.9em;">
                                    ✅ Marquer comme lu
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <p class="cr-preview"><?php echo nl2br(htmlspecialchars($cr['description'])); ?></p>
                    
                    <form method="post" class="comment-form">
                        <input type="hidden" name="num_cr" value="<?php echo $cr['num']; ?>">
                        <div class="form-group">
                            <label for="commentaire_<?php echo $cr['num']; ?>">Votre commentaire :</label>
                            <textarea id="commentaire_<?php echo $cr['num']; ?>" name="commentaire" required placeholder="Ajoutez votre commentaire..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajouter le commentaire</button>
                    </form>
                    
                    <!-- Afficher les commentaires existants -->
                    <?php
                    $bdd_comments = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
                    $requete_comments = "SELECT c.*, u.prenom, u.nom 
                                       FROM commentaires c 
                                       JOIN utilisateur u ON c.num_professeur = u.num 
                                       WHERE c.num_cr = ? 
                                       ORDER BY c.date_commentaire DESC";
                    $stmt_comments = mysqli_prepare($bdd_comments, $requete_comments);
                    mysqli_stmt_bind_param($stmt_comments, "i", $cr['num']);
                    mysqli_stmt_execute($stmt_comments);
                    $resultat_comments = mysqli_stmt_get_result($stmt_comments);
                    $commentaires = mysqli_fetch_all($resultat_comments, MYSQLI_ASSOC);
                    mysqli_close($bdd_comments);
                    
                    if(count($commentaires) > 0): ?>
                        <div class="comment-section" style="margin-top: 20px;">
                            <h4 style="color: #4a5568; margin-bottom: 15px;">Commentaires existants :</h4>
                            <?php foreach($commentaires as $commentaire): ?>
                                <div class="comment">
                                    <p><strong><?php echo htmlspecialchars($commentaire['prenom'] . ' ' . $commentaire['nom']); ?> :</strong></p>
                                    <p><?php echo nl2br(htmlspecialchars($commentaire['contenu'])); ?></p>
                                    <p class="comment-date">Posté le <?php echo date('d/m/Y à H:i', strtotime($commentaire['date_commentaire'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html>