<?php
session_start();
include '_conf.php';

// Vérifier si l'utilisateur est connecté et est un professeur
if(!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['type'] != 1) {
    header('Location: index.php');
    exit();
}

$utilisateur = $_SESSION['utilisateur'];

// Récupérer les statistiques pour l'accueil
$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);

// CORRECTION : Utiliser IS NULL pour les élèves au lieu de type = 0
// Nombre total de CR
$requete_total = "SELECT COUNT(*) as total FROM cr c JOIN utilisateur u ON c.num_utilisateur = u.num WHERE u.type IS NULL OR u.type = 0";
$resultat_total = mysqli_query($bdd, $requete_total);
$total_cr = mysqli_fetch_assoc($resultat_total)['total'];

// CR non lus
$requete_non_lus = "SELECT COUNT(*) as non_lus FROM cr c JOIN utilisateur u ON c.num_utilisateur = u.num WHERE (u.type IS NULL OR u.type = 0) AND c.vu = 0";
$resultat_non_lus = mysqli_query($bdd, $requete_non_lus);
$cr_non_lus = mysqli_fetch_assoc($resultat_non_lus)['non_lus'];

// Derniers CR
$requete_derniers = "SELECT c.*, u.prenom, u.nom 
                     FROM cr c 
                     JOIN utilisateur u ON c.num_utilisateur = u.num 
                     WHERE u.type IS NULL OR u.type = 0
                     ORDER BY c.date DESC 
                     LIMIT 3";
$resultat_derniers = mysqli_query($bdd, $requete_derniers);
$derniers_cr = mysqli_fetch_all($resultat_derniers, MYSQLI_ASSOC);

mysqli_close($bdd);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil Professeur</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Espace Professeur</h1>
            <p class="welcome-message">Bienvenue <?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></p>
            <a href="deconnexion.php" class="logout-btn">Déconnexion</a>
        </header>
        <nav>
    <ul class="nav-menu">
        <li><a href="accueil_prof.php">Accueil</a></li>
        <li><a href="liste_cr_eleves.php">Comptes rendus élèves</a></li>
        <li><a href="commenter_cr.php">Commenter les CR</a></li>
        <li><a href="gestion_stages.php">Gestion des stages</a></li>
        <li><a href="profil.php">Mon Profil</a></li>
    </ul>
</nav>
        
        <main>
            <h2>Tableau de bord</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <h3>📊 Total CR</h3>
                    <p style="font-size: 2em; margin: 10px 0;"><?php echo $total_cr; ?></p>
                    <p>Comptes rendus</p>
                </div>
                
                <div style="background: linear-gradient(135deg, #fc8181, #e53e3e); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <h3>📖 En attente</h3>
                    <p style="font-size: 2em; margin: 10px 0;"><?php echo $cr_non_lus; ?></p>
                    <p>CR non lus</p>
                </div>
                
                <div style="background: linear-gradient(135deg, #68d391, #38a169); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <h3>✅ Terminés</h3>
                    <p style="font-size: 2em; margin: 10px 0;"><?php echo $total_cr - $cr_non_lus; ?></p>
                    <p>CR consultés</p>
                </div>
            </div>
            
            <div class="cr-list">
                <h3 style="color: #4a5568; margin-bottom: 20px; text-align: left; border-bottom: 2px solid #cbd5e0; padding-bottom: 10px;">
                    Derniers comptes rendus
                </h3>
                
                <?php if(count($derniers_cr) > 0): ?>
                    <?php foreach($derniers_cr as $cr): ?>
                        <div class="cr-item <?php echo $cr['vu'] ? 'statut-vu' : 'statut-non-vu'; ?>">
                            <span class="cr-date">
                                📅 CR du <?php echo htmlspecialchars($cr['date']); ?> 
                                par <strong><?php echo htmlspecialchars($cr['prenom'] . ' ' . $cr['nom']); ?></strong>
                                <span class="badge <?php echo $cr['vu'] ? 'badge-vu' : 'badge-non-vu'; ?>">
                                    <?php echo $cr['vu'] ? '✅ Lu' : '📖 Non lu'; ?>
                                </span>
                            </span>
                            <p class="cr-preview"><?php echo nl2br(htmlspecialchars(substr($cr['description'], 0, 150))); ?><?php echo strlen($cr['description']) > 150 ? '...' : ''; ?></p>
                            <div class="cr-actions">
                                <a href="liste_cr_eleves.php" class="btn btn-primary">Voir tous les CR</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>📝 Aucun compte rendu d'élève pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="liste_cr_eleves.php" class="btn btn-success">📋 Gérer tous les comptes rendus</a>
            </div>
        </main>
    </div>
</body>
</html>