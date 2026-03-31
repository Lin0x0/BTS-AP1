<?php
session_start();
include '_conf.php';

// Vérifier si l'utilisateur est connecté
if(!isset($_SESSION['utilisateur'])) {
    header('Location: index.php');
    exit();
}

$utilisateur = $_SESSION['utilisateur'];
$message = '';
$erreur = '';

// Traitement du formulaire de modification du mot de passe
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ancien_mdp']) && isset($_POST['nouveau_mdp']) && isset($_POST['confirmer_mdp'])) {
    $ancien_mdp = $_POST['ancien_mdp'];
    $nouveau_mdp = $_POST['nouveau_mdp'];
    $confirmer_mdp = $_POST['confirmer_mdp'];
    
    // Vérifier les règles de sécurité du nouveau mot de passe
    if(strlen($nouveau_mdp) < 8) {
        $erreur = "Le mot de passe doit contenir au moins 8 caractères";
    } elseif(!preg_match('/[a-z]/', $nouveau_mdp)) {
        $erreur = "Le mot de passe doit contenir au moins une minuscule";
    } elseif(!preg_match('/[A-Z]/', $nouveau_mdp)) {
        $erreur = "Le mot de passe doit contenir au moins une majuscule";
    } elseif(!preg_match('/[0-9]/', $nouveau_mdp)) {
        $erreur = "Le mot de passe doit contenir au moins un chiffre";
    } elseif(!preg_match('/[^a-zA-Z0-9]/', $nouveau_mdp)) {
        $erreur = "Le mot de passe doit contenir au moins un caractère spécial";
    } elseif($nouveau_mdp !== $confirmer_mdp) {
        $erreur = "Les mots de passe ne correspondent pas";
    } else {
        // Vérifier l'ancien mot de passe
        $bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
        $num_utilisateur = $utilisateur['num'];
        
        $requete = "SELECT motdepasse FROM utilisateur WHERE num = ?";
        $stmt = mysqli_prepare($bdd, $requete);
        mysqli_stmt_bind_param($stmt, "i", $num_utilisateur);
        mysqli_stmt_execute($stmt);
        $resultat = mysqli_stmt_get_result($stmt);
        $donnees = mysqli_fetch_assoc($resultat);
        
        if(password_verify($ancien_mdp, $donnees['motdepasse'])) {
            // Ancien mot de passe correct, mettre à jour avec le nouveau
            $nouveau_hash = password_hash($nouveau_mdp, PASSWORD_BCRYPT);
            
            $requete_update = "UPDATE utilisateur SET motdepasse = ? WHERE num = ?";
            $stmt_update = mysqli_prepare($bdd, $requete_update);
            mysqli_stmt_bind_param($stmt_update, "si", $nouveau_hash, $num_utilisateur);
            
            if(mysqli_stmt_execute($stmt_update)) {
                $message = "Mot de passe modifié avec succès !";
                // Mettre à jour la session
                $_SESSION['utilisateur']['motdepasse'] = $nouveau_hash;
            } else {
                $erreur = "Erreur lors de la mise à jour du mot de passe";
            }
        } else {
            $erreur = "Ancien mot de passe incorrect";
        }
        mysqli_close($bdd);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Mon Profil</h1>
            <p class="welcome-message">Bienvenue <?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></p>
            <a href="deconnexion.php" class="logout-btn">Déconnexion</a>
        </header>
        
        <nav>
            <ul class="nav-menu">
                <?php if($utilisateur['type'] == 1): // Professeur ?>
                    <li><a href="accueil_prof.php">Accueil</a></li>
                    <li><a href="liste_cr_eleves.php">Comptes rendus élèves</a></li>
                    <li><a href="commenter_cr.php">Commenter les CR</a></li>
                    <li><a href="gestion_stages.php">Gestion des stages</a></li>
                    <li><a href="profil.php" class="nav-active">Mon Profil</a></li>
                <?php else: // Élève ?>
                    <li><a href="accueil_eleve.php">Accueil</a></li>
                    <li><a href="liste_cr.php">Liste des comptes rendus</a></li>
                    <li><a href="creer_cr.php">Créer/modifier un compte rendu</a></li>
                    <li><a href="commentaires.php">Commentaires</a></li>
                    <li><a href="profil.php" class="nav-active">Mon Profil</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <main>
            <h2>Informations du compte</h2>
            
            <div class="profile-info">
                <p><strong>Nom :</strong> <?php echo htmlspecialchars($utilisateur['nom']); ?></p>
                <p><strong>Prénom :</strong> <?php echo htmlspecialchars($utilisateur['prenom']); ?></p>
                <p><strong>Email :</strong> <?php echo htmlspecialchars($utilisateur['email']); ?></p>
                <p><strong>Type de compte :</strong> <?php echo $utilisateur['type'] == 1 ? 'Professeur' : 'Élève'; ?></p>
            </div>
            
            <h2>Modifier le mot de passe</h2>
            
            <?php if($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if($erreur): ?>
                <div class="message error"><?php echo $erreur; ?></div>
            <?php endif; ?>
            
            <div class="password-requirements">
                <h3>Règles de sécurité du mot de passe :</h3>
                <ul id="password-rules">
                    <li id="req-length">Au moins 8 caractères</li>
                    <li id="req-lowercase">Au moins une minuscule (a-z)</li>
                    <li id="req-uppercase">Au moins une majuscule (A-Z)</li>
                    <li id="req-number">Au moins un chiffre (0-9)</li>
                    <li id="req-special">Au moins un caractère spécial (!@#$%^&*...)</li>
                </ul>
                <div id="password-strength" class="password-strength">
                    <div id="password-strength-bar" class="password-strength-bar"></div>
                </div>
            </div>
            
            <form method="post" id="password-form" onsubmit="return validatePassword()">
                <div class="form-group">
                    <label for="ancien_mdp">Ancien mot de passe :</label>
                    <input type="password" id="ancien_mdp" name="ancien_mdp" required>
                </div>
                
                <div class="form-group">
                    <label for="nouveau_mdp">Nouveau mot de passe :</label>
                    <input type="password" id="nouveau_mdp" name="nouveau_mdp" required 
                           oninput="checkPasswordStrength()">
                </div>
                
                <div class="form-group">
                    <label for="confirmer_mdp">Confirmer le nouveau mot de passe :</label>
                    <input type="password" id="confirmer_mdp" name="confirmer_mdp" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Modifier le mot de passe</button>
                <?php if($utilisateur['type'] == 1): ?>
                    <a href="accueil_prof.php" class="btn btn-secondary">Retour à l'accueil</a>
                <?php else: ?>
                    <a href="accueil_eleve.php" class="btn btn-secondary">Retour à l'accueil</a>
                <?php endif; ?>
            </form>
        </main>
    </div>
    
    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('nouveau_mdp').value;
            const strengthBar = document.getElementById('password-strength-bar');
            const requirements = {
                length: password.length >= 8,
                lowercase: /[a-z]/.test(password),
                uppercase: /[A-Z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^a-zA-Z0-9]/.test(password)
            };
            
            // Mettre à jour l'affichage des exigences
            document.getElementById('req-length').className = requirements.length ? 'valid-rule' : 'invalid-rule';
            document.getElementById('req-lowercase').className = requirements.lowercase ? 'valid-rule' : 'invalid-rule';
            document.getElementById('req-uppercase').className = requirements.uppercase ? 'valid-rule' : 'invalid-rule';
            document.getElementById('req-number').className = requirements.number ? 'valid-rule' : 'invalid-rule';
            document.getElementById('req-special').className = requirements.special ? 'valid-rule' : 'invalid-rule';
            
            // Calculer la force du mot de passe
            const strength = Object.values(requirements).filter(Boolean).length;
            strengthBar.className = 'password-strength-bar';
            strengthBar.classList.add('strength-' + strength);
        }
        
        function validatePassword() {
            const password = document.getElementById('nouveau_mdp').value;
            const confirm = document.getElementById('confirmer_mdp').value;
            
            // Vérifier toutes les règles
            const requirements = {
                length: password.length >= 8,
                lowercase: /[a-z]/.test(password),
                uppercase: /[A-Z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^a-zA-Z0-9]/.test(password),
                match: password === confirm
            };
            
            // Si toutes les conditions ne sont pas remplies
            if (!Object.values(requirements).every(Boolean)) {
                alert("Veuillez respecter toutes les règles de sécurité pour le mot de passe.");
                return false;
            }
            
            return true;
        }
        
        // Initialiser la vérification
        document.addEventListener('DOMContentLoaded', function() {
            checkPasswordStrength();
        });
    </script>
</body>
</html>