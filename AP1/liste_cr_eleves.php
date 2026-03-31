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

// Traitement du marquage "vu"
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

// Traitement du marquage "non vu"
if(isset($_GET['marquer_non_vu'])) {
    $bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
    $num_cr = $_GET['marquer_non_vu'];
    
    $requete = "UPDATE cr SET vu = 0 WHERE num = ?";
    $stmt = mysqli_prepare($bdd, $requete);
    mysqli_stmt_bind_param($stmt, "i", $num_cr);
    
    if(mysqli_stmt_execute($stmt)) {
        $message = "Compte rendu marqué comme non vu!";
    } else {
        $message = "Erreur lors du marquage";
    }
    mysqli_close($bdd);
}

// Récupérer la liste des élèves avec leurs statistiques
$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);

// Statistiques par élève
$requete_eleves = "SELECT 
    u.num, 
    u.prenom, 
    u.nom, 
    u.email,
    COUNT(c.num) as total_cr,
    SUM(CASE WHEN c.vu = 0 THEN 1 ELSE 0 END) as cr_non_lus,
    SUM(CASE WHEN c.vu = 1 THEN 1 ELSE 0 END) as cr_lus,
    MAX(c.date) as dernier_cr
FROM utilisateur u 
LEFT JOIN cr c ON u.num = c.num_utilisateur 
WHERE u.type = 0 OR u.type IS NULL
GROUP BY u.num, u.prenom, u.nom, u.email
ORDER BY u.nom, u.prenom";

$resultat_eleves = mysqli_query($bdd, $requete_eleves);
$eleves = mysqli_fetch_all($resultat_eleves, MYSQLI_ASSOC);

// Récupérer tous les comptes rendus avec filtres
$filtre_eleve = isset($_GET['eleve']) ? intval($_GET['eleve']) : 0;
$filtre_statut = isset($_GET['filtre']) ? $_GET['filtre'] : 'tous';

// Construire la requête des CR avec filtres
$requete_cr = "SELECT c.*, u.prenom, u.nom, u.email 
           FROM cr c 
           JOIN utilisateur u ON c.num_utilisateur = u.num 
           WHERE (u.type = 0 OR u.type IS NULL)";

// Ajouter filtre par élève si spécifié
if($filtre_eleve > 0) {
    $requete_cr .= " AND u.num = $filtre_eleve";
}

// Ajouter filtre par statut
if($filtre_statut == 'non_vu') {
    $requete_cr .= " AND c.vu = 0";
} elseif($filtre_statut == 'vu') {
    $requete_cr .= " AND c.vu = 1";
}

$requete_cr .= " ORDER BY c.date DESC, c.vu ASC";

$resultat_cr = mysqli_query($bdd, $requete_cr);
$comptes_rendus = mysqli_fetch_all($resultat_cr, MYSQLI_ASSOC);

// Statistiques globales
$requete_stats = "SELECT 
    COUNT(DISTINCT u.num) as total_eleves,
    COUNT(c.num) as total_cr,
    SUM(CASE WHEN c.vu = 0 THEN 1 ELSE 0 END) as total_non_lus,
    SUM(CASE WHEN c.vu = 1 THEN 1 ELSE 0 END) as total_lus
FROM utilisateur u 
LEFT JOIN cr c ON u.num = c.num_utilisateur 
WHERE u.type = 0 OR u.type IS NULL";

$resultat_stats = mysqli_query($bdd, $requete_stats);
$stats = mysqli_fetch_assoc($resultat_stats);

mysqli_close($bdd);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Comptes rendus des élèves - Espace Professeur</title>
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
                <li><a href="liste_cr_eleves.php" class="nav-active">Comptes rendus élèves</a></li>
                <li><a href="commenter_cr.php">Commenter les CR</a></li>
                <li><a href="gestion_stages.php">Gestion des stages</a></li>
                <li><a href="profil.php">Mon Profil</a></li>
            </ul>
        </nav>
        
        <main>
            <h2>Gestion des comptes rendus des élèves</h2>
            
            <?php if($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <!-- Statistiques globales -->
            <div class="stats-grid">
                <div class="stat-card-enhanced" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <h3>👨‍🎓 Élèves</h3>
                    <p><?php echo $stats['total_eleves']; ?></p>
                    <p>Élèves inscrits</p>
                </div>
                
                <div class="stat-card-enhanced" style="background: linear-gradient(135deg, #ed8936, #dd6b20);">
                    <h3>📊 Total CR</h3>
                    <p><?php echo $stats['total_cr']; ?></p>
                    <p>Comptes rendus</p>
                </div>
                
                <div class="stat-card-enhanced" style="background: linear-gradient(135deg, #f56565, #e53e3e);">
                    <h3>📖 En attente</h3>
                    <p><?php echo $stats['total_non_lus']; ?></p>
                    <p>CR non lus</p>
                </div>
                
                <div class="stat-card-enhanced" style="background: linear-gradient(135deg, #48bb78, #38a169);">
                    <h3>✅ Terminés</h3>
                    <p><?php echo $stats['total_lus']; ?></p>
                    <p>CR lus</p>
                </div>
            </div>
            
            <!-- Liste des élèves -->
            <div class="eleves-list">
                <h3 class="card-title">Liste des élèves et leurs statistiques</h3>
                
                <div style="overflow-x: auto;">
                    <table class="eleves-table">
                        <thead>
                            <tr>
                                <th>Élève</th>
                                <th>Email</th>
                                <th>Comptes rendus</th>
                                <th>Dernier CR</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($eleves) > 0): ?>
                                <?php foreach($eleves as $eleve): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($eleve['email']); ?></td>
                                        <td>
                                            <span class="badge-stat badge-total">Total: <?php echo $eleve['total_cr']; ?></span>
                                            <span class="badge-stat badge-lu">Lus: <?php echo $eleve['cr_lus']; ?></span>
                                            <span class="badge-stat badge-non-lu">Non lus: <?php echo $eleve['cr_non_lus']; ?></span>
                                        </td>
                                        <td>
                                            <?php echo $eleve['dernier_cr'] ? htmlspecialchars($eleve['dernier_cr']) : 'Aucun CR'; ?>
                                        </td>
                                        <td>
                                            <a href="?eleve=<?php echo $eleve['num']; ?>" class="btn-eleve <?php echo $filtre_eleve == $eleve['num'] ? 'active' : ''; ?>">
                                                Voir les CR
                                            </a>
                                            <a href="mailto:<?php echo htmlspecialchars($eleve['email']); ?>" class="btn-eleve">
                                                📧 Contacter
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px;">
                                        <div class="empty-state">
                                            <p>👨‍🎓 Aucun élève inscrit pour le moment.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Filtres avancés -->
            <div class="filtres-avances">
                <h3 class="card-title">Filtres des comptes rendus</h3>
                
                <form method="get" action="liste_cr_eleves.php">
                    <div class="filtres-row">
                        <div class="filtre-group">
                            <label for="eleve">Filtrer par élève :</label>
                            <select id="eleve" name="eleve">
                                <option value="0">Tous les élèves</option>
                                <?php foreach($eleves as $eleve): ?>
                                    <option value="<?php echo $eleve['num']; ?>" <?php echo $filtre_eleve == $eleve['num'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?> (<?php echo $eleve['total_cr']; ?> CR)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filtre-group">
                            <label for="filtre">Statut des CR :</label>
                            <select id="filtre" name="filtre">
                                <option value="tous" <?php echo $filtre_statut == 'tous' ? 'selected' : ''; ?>>Tous les CR</option>
                                <option value="non_vu" <?php echo $filtre_statut == 'non_vu' ? 'selected' : ''; ?>>CR non lus</option>
                                <option value="vu" <?php echo $filtre_statut == 'vu' ? 'selected' : ''; ?>>CR lus</option>
                            </select>
                        </div>
                        
                        <div class="filtre-group">
                            <button type="submit" class="btn btn-primary w-100">Appliquer les filtres</button>
                            <a href="liste_cr_eleves.php" class="btn btn-secondary w-100 mt-20">Réinitialiser</a>
                        </div>
                    </div>
                </form>
                
                <?php if($filtre_eleve > 0): ?>
                    <?php 
                    $eleve_filtre = null;
                    foreach($eleves as $e) {
                        if($e['num'] == $filtre_eleve) {
                            $eleve_filtre = $e;
                            break;
                        }
                    }
                    if($eleve_filtre): ?>
                        <div class="message success mt-20">
                            <strong>Filtre actif :</strong> Vous visualisez les comptes rendus de 
                            <strong><?php echo htmlspecialchars($eleve_filtre['prenom'] . ' ' . $eleve_filtre['nom']); ?></strong>
                            (<?php echo $eleve_filtre['total_cr']; ?> CR au total, 
                            <?php echo $eleve_filtre['cr_non_lus']; ?> non lus, 
                            <?php echo $eleve_filtre['cr_lus']; ?> lus)
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Liste des comptes rendus -->
            <div class="cr-list">
                <h3 class="card-title"><?php echo $filtre_eleve > 0 ? 'Comptes rendus de l\'élève' : 'Tous les comptes rendus'; ?></h3>
                
                <?php if(count($comptes_rendus) > 0): ?>
                    <?php foreach($comptes_rendus as $cr): ?>
                        <div class="cr-item <?php echo $cr['vu'] ? 'statut-vu' : 'statut-non-vu'; ?>">
                            <div class="cr-header">
                                <div class="cr-info">
                                    <span class="cr-date">
                                        📅 CR du <?php echo htmlspecialchars($cr['date']); ?> 
                                        par <strong><?php echo htmlspecialchars($cr['prenom'] . ' ' . $cr['nom']); ?></strong>
                                        <span class="badge <?php echo $cr['vu'] ? 'badge-vu' : 'badge-non-vu'; ?>">
                                            <?php echo $cr['vu'] ? '✅ Lu' : '📖 Non lu'; ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="cr-actions">
                                    <?php if($cr['vu'] == 0): ?>
                                        <a href="liste_cr_eleves.php?marquer_vu=<?php echo $cr['num']; ?>&eleve=<?php echo $filtre_eleve; ?>&filtre=<?php echo $filtre_statut; ?>" class="btn btn-success">
                                            ✅ Marquer comme lu
                                        </a>
                                    <?php else: ?>
                                        <a href="liste_cr_eleves.php?marquer_non_vu=<?php echo $cr['num']; ?>&eleve=<?php echo $filtre_eleve; ?>&filtre=<?php echo $filtre_statut; ?>" class="btn btn-secondary">
                                            ↩️ Marquer comme non lu
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="commenter_cr.php?cr=<?php echo $cr['num']; ?>" class="btn btn-primary">
                                        💬 Commenter
                                    </a>
                                    
                                    <a href="mailto:<?php echo htmlspecialchars($cr['email']); ?>" class="btn">
                                        📧 Contacter
                                    </a>
                                </div>
                            </div>
                            
                            <p class="cr-preview"><?php echo nl2br(htmlspecialchars($cr['description'])); ?></p>
                            
                            <?php if($cr['vu'] == 1 && isset($cr['datetime'])): ?>
                                <div style="margin-top: 10px; font-size: 0.9em; color: #666;">
                                    <em>Consulté le : <?php echo htmlspecialchars($cr['datetime']); ?></em>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>📭 Aucun compte rendu correspondant aux filtres sélectionnés.</p>
                        <p>Essayez de modifier vos critères de recherche.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Statistiques de la page actuelle -->
            <div class="card mt-30">
                <h3 class="card-title">📊 Statistiques de la page</h3>
                <?php
                $total_cr_page = count($comptes_rendus);
                $cr_lus_page = count(array_filter($comptes_rendus, function($cr) { return $cr['vu'] == 1; }));
                $cr_non_lus_page = $total_cr_page - $cr_lus_page;
                ?>
                <p>Comptes rendus affichés : <strong><?php echo $total_cr_page; ?></strong></p>
                <p>Comptes rendus lus : <strong style="color: #28a745;"><?php echo $cr_lus_page; ?></strong></p>
                <p>Comptes rendus non lus : <strong style="color: #dc3545;"><?php echo $cr_non_lus_page; ?></strong></p>
            </div>
        </main>
    </div>
</body>
</html>