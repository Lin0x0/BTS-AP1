<?php
include '_conf.php';
session_start();

// Vérifier si l'utilisateur est déjà connecté
if(isset($_SESSION['utilisateur'])) {
    $type = $_SESSION['utilisateur']['type'];
    if($type == 1) {
        header('Location: accueil_prof.php');
    } else {
        header('Location: accueil_eleve.php');
    }
    exit();
}

// Traitement du formulaire de connexion
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && isset($_POST['motdepasse'])) {
    $email = $_POST['email'];
    $motdepasse = $_POST['motdepasse'];
    
    if($bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD)) {
        // Récupérer l'utilisateur par email
        $requete = "SELECT * FROM utilisateur WHERE email = ?";
        $stmt = mysqli_prepare($bdd, $requete);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $resultat = mysqli_stmt_get_result($stmt);
        
        if($utilisateur = mysqli_fetch_assoc($resultat)) {
            // Vérifier le mot de passe avec password_verify
            if(password_verify($motdepasse, $utilisateur['motdepasse'])) {
                // Connexion réussie
                $_SESSION['utilisateur'] = $utilisateur;
                
                // Redirection selon le type d'utilisateur
                if($utilisateur['type'] == 1) { // Professeur
                    header('Location: accueil_prof.php');
                } else { // Élève (type 0 ou autre)
                    header('Location: accueil_eleve.php');
                }
                exit();
            } else {
                $erreur = "Email ou mot de passe incorrect";
            }
        } else {
            $erreur = "Email ou mot de passe incorrect";
        }
    } else {
        $erreur = "Erreur de connexion à la base de données";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="login.css">
    <!-- AUCUN Font Awesome - fonctionne en localhost -->
</head>
<body class="login-page">
    <div class="login-container">  <!-- CHANGÉ : login-container au lieu de id="login-container" -->
        <h2>Connexion</h2>
        
        <?php if(isset($erreur)): ?>
            <div class="error-message"><?php echo $erreur; ?></div>
        <?php endif; ?>
        
        <form action="index.php" method="post" class="login-form">
            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" id="email" name="email" required placeholder="votre@email.com">
            </div>
            
            <div class="form-group">
                <label for="motdepasse">Mot de passe :</label>
                <input type="password" id="motdepasse" name="motdepasse" required placeholder="Votre mot de passe">
            </div>
            
            <button type="submit" class="login-btn">Se connecter</button>
        </form>
        
        <div class="login-links">
            <a href="oubli.php">Mot de passe oublié ?</a>
        </div>
    </div>
</body>
</html>