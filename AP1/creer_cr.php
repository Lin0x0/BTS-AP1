<?php
session_start();
include '_conf.php';

// Vérifier si l'utilisateur est connecté (élève)
if(!isset($_SESSION['utilisateur'])) {
    header('Location: index.php');
    exit();
}

$utilisateur = $_SESSION['utilisateur'];
$message = '';
$message_erreur = '';
$cr = null;
$date_courante = date('Y-m-d');
$num_cr = isset($_GET['num']) ? intval($_GET['num']) : 0;

// Vérifier si un message est passé via GET
if(isset($_GET['message']) && !empty($_GET['message'])) {
    $message = $_GET['message'];
}

// Récupérer le CR existant si on est en mode modification
if($num_cr > 0) {
    $bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
    $num_utilisateur = $utilisateur['num'];
    
    $requete = "SELECT * FROM cr WHERE num = ? AND num_utilisateur = ?";
    $stmt = mysqli_prepare($bdd, $requete);
    mysqli_stmt_bind_param($stmt, "ii", $num_cr, $num_utilisateur);
    mysqli_stmt_execute($stmt);
    $resultat = mysqli_stmt_get_result($stmt);
    $cr = mysqli_fetch_assoc($resultat);
    mysqli_close($bdd);
} 
// Sinon, vérifier si un CR existe pour la date courante
else {
    $bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
    $num_utilisateur = $utilisateur['num'];
    
    $requete = "SELECT * FROM cr WHERE num_utilisateur = ? AND date = ?";
    $stmt = mysqli_prepare($bdd, $requete);
    mysqli_stmt_bind_param($stmt, "is", $num_utilisateur, $date_courante);
    mysqli_stmt_execute($stmt);
    $resultat = mysqli_stmt_get_result($stmt);
    $cr = mysqli_fetch_assoc($resultat);
    if($cr) {
        $num_cr = $cr['num'];
    }
    mysqli_close($bdd);
}

// Traitement du formulaire
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
    $date = $_POST['date'];
    $description = $_POST['description'];
    $num_utilisateur = $utilisateur['num'];
    $post_num_cr = isset($_POST['num_cr']) ? intval($_POST['num_cr']) : 0;
    
    // Vérifier si la date est déjà utilisée par un autre CR du même utilisateur
    $requete_verif = "SELECT num FROM cr WHERE num_utilisateur = ? AND date = ?";
    $params = [$num_utilisateur, $date];
    $types = "is";
    
    // Si on est en mode modification, exclure le CR actuel de la vérification
    if($post_num_cr > 0) {
        $requete_verif .= " AND num != ?";
        $params[] = $post_num_cr;
        $types .= "i";
    }
    
    $stmt_verif = mysqli_prepare($bdd, $requete_verif);
    
    // Liaison dynamique des paramètres
    if($post_num_cr > 0) {
        mysqli_stmt_bind_param($stmt_verif, $types, $num_utilisateur, $date, $post_num_cr);
    } else {
        mysqli_stmt_bind_param($stmt_verif, $types, $num_utilisateur, $date);
    }
    
    mysqli_stmt_execute($stmt_verif);
    $resultat_verif = mysqli_stmt_get_result($stmt_verif);
    $cr_existant = mysqli_fetch_assoc($resultat_verif);
    
    if($cr_existant && $post_num_cr == 0) {
        // Mode création mais un CR existe déjà pour cette date - UPDATE
        $num_cr_existant = $cr_existant['num'];
        $requete = "UPDATE cr SET description = ? WHERE num = ? AND num_utilisateur = ?";
        $stmt = mysqli_prepare($bdd, $requete);
        mysqli_stmt_bind_param($stmt, "sii", $description, $num_cr_existant, $num_utilisateur);
        $action = "mis à jour";
    } 
    elseif($cr_existant && $post_num_cr > 0) {
        // Mode modification mais un autre CR existe déjà pour cette date - ERREUR
        $message_erreur = "Un compte rendu existe déjà pour cette date !";
        mysqli_close($bdd);
    }
    else {
        // Pas de conflit de date
        if($post_num_cr > 0) {
            // UPDATE du CR existant
            $requete = "UPDATE cr SET date = ?, description = ? WHERE num = ? AND num_utilisateur = ?";
            $stmt = mysqli_prepare($bdd, $requete);
            mysqli_stmt_bind_param($stmt, "ssii", $date, $description, $post_num_cr, $num_utilisateur);
            $action = "mis à jour";
        } else {
            // INSERT d'un nouveau CR
            $requete = "INSERT INTO cr (date, description, num_utilisateur, vu, datetime) VALUES (?, ?, ?, 0, NOW())";
            $stmt = mysqli_prepare($bdd, $requete);
            mysqli_stmt_bind_param($stmt, "ssi", $date, $description, $num_utilisateur);
            $action = "créé";
        }
    }
    
    // Exécuter la requête si pas d'erreur
    if(empty($message_erreur)) {
        if(mysqli_stmt_execute($stmt)) {
            $message = "Compte rendu $action avec succès!";
            
            // Redirection selon l'action
            if($post_num_cr > 0) {
                // Modification : rediriger vers l'accueil avec message de succès
                header("Location: accueil_eleve.php?message=Le compte rendu a été modifié avec succès&type=success");
                exit();
            } else {
                // Création : rediriger vers l'accueil avec message de succès
                header("Location: accueil_eleve.php?message=Le compte rendu a été créé avec succès&type=success");
                exit();
            }
        } else {
            $message_erreur = "Erreur lors de l'enregistrement";
        }
    }
    
    mysqli_close($bdd);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($cr) ? 'Modifier' : 'Créer'; ?> un compte rendu</title>
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
            <h2><?php echo isset($cr) ? 'Modifier le compte rendu' : 'Créer un compte rendu'; ?></h2>
            
            <?php if($message): ?>
                <div class="message <?php echo isset($_GET['type']) && $_GET['type'] == 'success' ? 'success' : ''; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if($message_erreur): ?>
                <div class="message error"><?php echo htmlspecialchars($message_erreur); ?></div>
            <?php endif; ?>
            
            <form method="post">
                <?php if(isset($cr) && isset($cr['num'])): ?>
                    <input type="hidden" name="num_cr" value="<?php echo $cr['num']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="date">Date :</label>
                    <input type="date" id="date" name="date" 
                           value="<?php echo isset($cr) ? $cr['date'] : date('Y-m-d'); ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description :</label>
                    <textarea id="description" name="description" rows="10" required><?php echo isset($cr) ? htmlspecialchars($cr['description']) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <?php echo isset($cr) ? 'Mettre à jour' : 'Créer'; ?> le compte rendu
                </button>
                <a href="liste_cr.php" class="btn btn-secondary">Annuler</a>
            </form>
            
            <?php if(isset($cr)): ?>
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #667eea;">
                    <p><strong>Note :</strong> Vous modifiez le compte rendu du <?php echo htmlspecialchars($cr['date']); ?></p>
                    <?php if(isset($cr['datetime'])): ?>
                        <p><small>Créé le : <?php echo htmlspecialchars($cr['datetime']); ?></small></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>