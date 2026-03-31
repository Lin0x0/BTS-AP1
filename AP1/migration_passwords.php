<?php
// migration_passwords.php
// Script à exécuter une seule fois pour convertir les mots de passe MD5 en BCRYPT
include '_conf.php';

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);

// Récupérer tous les utilisateurs
$requete = "SELECT num, motdepasse FROM utilisateur";
$resultat = mysqli_query($bdd, $requete);

$compteur = 0;
while($utilisateur = mysqli_fetch_assoc($resultat)) {
    $num = $utilisateur['num'];
    $motdepasse_hash = $utilisateur['motdepasse'];
    
    // Vérifier si le hash est en MD5 (32 caractères hexadécimaux)
    if (strlen($motdepasse_hash) === 32 && ctype_xdigit($motdepasse_hash)) {
        // C'est probablement un MD5, on va le hasher en BCRYPT
        // Note: Nous n'avons pas le mot de passe en clair, donc nous devons demander à l'utilisateur de réinitialiser
        // Pour l'instant, on met un mot de passe par défaut "password" hashé en BCRYPT
        $nouveau_hash = password_hash('password', PASSWORD_BCRYPT);
        
        $requete_update = "UPDATE utilisateur SET motdepasse = ? WHERE num = ?";
        $stmt = mysqli_prepare($bdd, $requete_update);
        mysqli_stmt_bind_param($stmt, "si", $nouveau_hash, $num);
        
        if(mysqli_stmt_execute($stmt)) {
            $compteur++;
            echo "Utilisateur $num migré vers BCRYPT<br>";
        }
    }
}

echo "Migration terminée. $compteur utilisateurs migrés.<br>";
echo "Tous les mots de passe ont été réinitialisés à 'password' (en BCRYPT).<br>";
echo "Les utilisateurs devront utiliser le mot de passe 'password' pour se connecter.<br>";
echo "Ils devront ensuite changer leur mot de passe via la fonction mot de passe oublié.<br>";

mysqli_close($bdd);
?>