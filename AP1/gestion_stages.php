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
$message_erreur = '';

// Connexion à la base de données
$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);

// Traitement des formulaires
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ===== AJOUT/MODIFICATION D'UN TUTEUR =====
    if(isset($_POST['action']) && $_POST['action'] == 'tuteur') {
        $nom = mysqli_real_escape_string($bdd, $_POST['nom']);
        $prenom = mysqli_real_escape_string($bdd, $_POST['prenom']);
        $tel = mysqli_real_escape_string($bdd, $_POST['tel']);
        $email = mysqli_real_escape_string($bdd, $_POST['email']);
        
        if(isset($_POST['num_tuteur']) && !empty($_POST['num_tuteur'])) {
            // Modification
            $num_tuteur = intval($_POST['num_tuteur']);
            $requete = "UPDATE tuteur SET nom = ?, prenom = ?, tel = ?, email = ? WHERE num = ?";
            $stmt = mysqli_prepare($bdd, $requete);
            mysqli_stmt_bind_param($stmt, "ssssi", $nom, $prenom, $tel, $email, $num_tuteur);
            $action_message = "modifié";
        } else {
            // Ajout
            $requete = "INSERT INTO tuteur (nom, prenom, tel, email) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($bdd, $requete);
            mysqli_stmt_bind_param($stmt, "ssss", $nom, $prenom, $tel, $email);
            $action_message = "ajouté";
        }
        
        if(mysqli_stmt_execute($stmt)) {
            $message = "Tuteur $action_message avec succès!";
        } else {
            $message_erreur = "Erreur lors de l'opération sur le tuteur";
        }
    }
    
    // ===== AJOUT/MODIFICATION D'UN STAGE =====
    elseif(isset($_POST['action']) && $_POST['action'] == 'stage') {
        $nom_entreprise = mysqli_real_escape_string($bdd, $_POST['nom_entreprise']);
        $adresse = mysqli_real_escape_string($bdd, $_POST['adresse']);
        $CP = intval($_POST['CP']);
        $ville = mysqli_real_escape_string($bdd, $_POST['ville']);
        $tel_entreprise = mysqli_real_escape_string($bdd, $_POST['tel_entreprise']);
        $libelleStage = mysqli_real_escape_string($bdd, $_POST['libelleStage']);
        $email_entreprise = mysqli_real_escape_string($bdd, $_POST['email_entreprise']);
        $num_tuteur = intval($_POST['num_tuteur']);
        
        if(isset($_POST['num_stage']) && !empty($_POST['num_stage'])) {
            // Modification
            $num_stage = intval($_POST['num_stage']);
            $requete = "UPDATE stage SET nom = ?, adresse = ?, CP = ?, ville = ?, tel = ?, libelleStage = ?, email = ?, num_tuteur = ? WHERE num = ?";
            $stmt = mysqli_prepare($bdd, $requete);
            mysqli_stmt_bind_param($stmt, "ssissssii", $nom_entreprise, $adresse, $CP, $ville, $tel_entreprise, $libelleStage, $email_entreprise, $num_tuteur, $num_stage);
            $action_message = "modifié";
        } else {
            // Ajout
            $requete = "INSERT INTO stage (nom, adresse, CP, ville, tel, libelleStage, email, num_tuteur) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($bdd, $requete);
            mysqli_stmt_bind_param($stmt, "ssissssi", $nom_entreprise, $adresse, $CP, $ville, $tel_entreprise, $libelleStage, $email_entreprise, $num_tuteur);
            $action_message = "ajouté";
        }
        
        if(mysqli_stmt_execute($stmt)) {
            $message = "Stage $action_message avec succès!";
        } else {
            $message_erreur = "Erreur lors de l'opération sur le stage";
        }
    }
    
    // ===== AFFECTATION D'UN ÉLÈVE À UN STAGE =====
    elseif(isset($_POST['action']) && $_POST['action'] == 'affectation') {
        $num_eleve = intval($_POST['num_eleve']);
        $num_stage = intval($_POST['num_stage_affectation']);
        
        $requete = "UPDATE utilisateur SET num_stage = ? WHERE num = ? AND (type = 0 OR type IS NULL)";
        $stmt = mysqli_prepare($bdd, $requete);
        mysqli_stmt_bind_param($stmt, "ii", $num_stage, $num_eleve);
        
        if(mysqli_stmt_execute($stmt)) {
            $message = "Élève affecté au stage avec succès!";
        } else {
            $message_erreur = "Erreur lors de l'affectation de l'élève";
        }
    }
}

// ===== SUPPRESSION D'UN TUTEUR =====
if(isset($_GET['supprimer_tuteur'])) {
    $num_tuteur = intval($_GET['supprimer_tuteur']);
    
    // Vérifier si le tuteur est utilisé dans un stage
    $requete_verif = "SELECT COUNT(*) as nb FROM stage WHERE num_tuteur = ?";
    $stmt = mysqli_prepare($bdd, $requete_verif);
    mysqli_stmt_bind_param($stmt, "i", $num_tuteur);
    mysqli_stmt_execute($stmt);
    $resultat = mysqli_stmt_get_result($stmt);
    $donnees = mysqli_fetch_assoc($resultat);
    
    if($donnees['nb'] > 0) {
        $message_erreur = "Impossible de supprimer ce tuteur car il est associé à un ou plusieurs stages";
    } else {
        $requete = "DELETE FROM tuteur WHERE num = ?";
        $stmt = mysqli_prepare($bdd, $requete);
        mysqli_stmt_bind_param($stmt, "i", $num_tuteur);
        
        if(mysqli_stmt_execute($stmt)) {
            $message = "Tuteur supprimé avec succès!";
        } else {
            $message_erreur = "Erreur lors de la suppression du tuteur";
        }
    }
}

// ===== SUPPRESSION D'UN STAGE =====
if(isset($_GET['supprimer_stage'])) {
    $num_stage = intval($_GET['supprimer_stage']);
    
    // Vérifier si le stage est utilisé par un élève
    $requete_verif = "SELECT COUNT(*) as nb FROM utilisateur WHERE num_stage = ?";
    $stmt = mysqli_prepare($bdd, $requete_verif);
    mysqli_stmt_bind_param($stmt, "i", $num_stage);
    mysqli_stmt_execute($stmt);
    $resultat = mysqli_stmt_get_result($stmt);
    $donnees = mysqli_fetch_assoc($resultat);
    
    if($donnees['nb'] > 0) {
        $message_erreur = "Impossible de supprimer ce stage car il est attribué à un ou plusieurs élèves";
    } else {
        $requete = "DELETE FROM stage WHERE num = ?";
        $stmt = mysqli_prepare($bdd, $requete);
        mysqli_stmt_bind_param($stmt, "i", $num_stage);
        
        if(mysqli_stmt_execute($stmt)) {
            $message = "Stage supprimé avec succès!";
        } else {
            $message_erreur = "Erreur lors de la suppression du stage";
        }
    }
}

// ===== RÉCUPÉRATION DES DONNÉES =====

// Liste des tuteurs
$requete_tuteurs = "SELECT * FROM tuteur ORDER BY nom, prenom";
$resultat_tuteurs = mysqli_query($bdd, $requete_tuteurs);
$tuteurs = mysqli_fetch_all($resultat_tuteurs, MYSQLI_ASSOC);

// Liste des stages avec les tuteurs
$requete_stages = "SELECT s.*, t.nom as tuteur_nom, t.prenom as tuteur_prenom 
                   FROM stage s 
                   LEFT JOIN tuteur t ON s.num_tuteur = t.num 
                   ORDER BY s.nom";
$resultat_stages = mysqli_query($bdd, $requete_stages);
$stages = mysqli_fetch_all($resultat_stages, MYSQLI_ASSOC);

// Liste des élèves (type 0) avec leur stage
$requete_eleves = "SELECT u.num, u.nom, u.prenom, u.email, s.nom as stage_nom, s.num as stage_num
                   FROM utilisateur u 
                   LEFT JOIN stage s ON u.num_stage = s.num 
                   WHERE u.type = 0 OR u.type IS NULL 
                   ORDER BY u.nom, u.prenom";
$resultat_eleves = mysqli_query($bdd, $requete_eleves);
$eleves = mysqli_fetch_all($resultat_eleves, MYSQLI_ASSOC);

// Récupérer un tuteur spécifique pour modification
$tuteur_a_modifier = null;
if(isset($_GET['modifier_tuteur'])) {
    $num_tuteur = intval($_GET['modifier_tuteur']);
    $requete = "SELECT * FROM tuteur WHERE num = ?";
    $stmt = mysqli_prepare($bdd, $requete);
    mysqli_stmt_bind_param($stmt, "i", $num_tuteur);
    mysqli_stmt_execute($stmt);
    $resultat = mysqli_stmt_get_result($stmt);
    $tuteur_a_modifier = mysqli_fetch_assoc($resultat);
}

// Récupérer un stage spécifique pour modification
$stage_a_modifier = null;
if(isset($_GET['modifier_stage'])) {
    $num_stage = intval($_GET['modifier_stage']);
    $requete = "SELECT * FROM stage WHERE num = ?";
    $stmt = mysqli_prepare($bdd, $requete);
    mysqli_stmt_bind_param($stmt, "i", $num_stage);
    mysqli_stmt_execute($stmt);
    $resultat = mysqli_stmt_get_result($stmt);
    $stage_a_modifier = mysqli_fetch_assoc($resultat);
}

mysqli_close($bdd);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Stages - Espace Professeur</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="stage.css">
    <script>
        function changerOnglet(ongletId) {
            // Masquer tous les onglets
            document.querySelectorAll('.stage-contenu-onglet').forEach(function(onglet) {
                onglet.classList.remove('active');
            });
            // Désactiver tous les boutons d'onglet
            document.querySelectorAll('.stage-onglet').forEach(function(bouton) {
                bouton.classList.remove('active');
            });
            // Afficher l'onglet sélectionné
            document.getElementById(ongletId).classList.add('active');
            // Activer le bouton correspondant
            event.target.classList.add('active');
        }
        
        function confirmerSuppression(message) {
            return confirm(message);
        }
    </script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Espace Professeur - Gestion des Stages</h1>
            <p class="welcome-message">Bienvenue <?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></p>
            <a href="deconnexion.php" class="logout-btn">Déconnexion</a>
        </header>
        
        <nav>
            <ul class="nav-menu">
                <li><a href="accueil_prof.php">Accueil</a></li>
                <li><a href="liste_cr_eleves.php">Comptes rendus élèves</a></li>
                <li><a href="commenter_cr.php">Commenter les CR</a></li>
                <li><a href="gestion_stages.php" class="nav-active">Gestion des stages</a></li>
                <li><a href="profil.php">Mon Profil</a></li>
            </ul>
        </nav>
        
        <main>
            <h2>Gestion des Stages et Tuteurs</h2>
            
            <?php if($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if($message_erreur): ?>
                <div class="message error"><?php echo htmlspecialchars($message_erreur); ?></div>
            <?php endif; ?>
            
            <!-- Statistiques -->
            <div class="stage-stats-grid">
                <div class="stage-stat-card" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <h3>👨‍🎓 Élèves</h3>
                    <p><?php echo count($eleves); ?></p>
                    <p>Élèves en stage</p>
                </div>
                
                <div class="stage-stat-card" style="background: linear-gradient(135deg, #ed8936, #dd6b20);">
                    <h3>🏢 Stages</h3>
                    <p><?php echo count($stages); ?></p>
                    <p>Stages disponibles</p>
                </div>
                
                <div class="stage-stat-card" style="background: linear-gradient(135deg, #48bb78, #38a169);">
                    <h3>👨‍🏫 Tuteurs</h3>
                    <p><?php echo count($tuteurs); ?></p>
                    <p>Tuteurs en entreprise</p>
                </div>
                
                <div class="stage-stat-card" style="background: linear-gradient(135deg, #f56565, #e53e3e);">
                    <h3>📊 Affectés</h3>
                    <p><?php echo count(array_filter($eleves, function($e) { return !empty($e['stage_num']); })); ?></p>
                    <p>Élèves affectés</p>
                </div>
            </div>
            
            <!-- Onglets de navigation -->
            <div class="stage-onglets">
                <div class="stage-onglet active" onclick="changerOnglet('onglet-tuteurs')">Gestion des Tuteurs</div>
                <div class="stage-onglet" onclick="changerOnglet('onglet-stages')">Gestion des Stages</div>
                <div class="stage-onglet" onclick="changerOnglet('onglet-affectation')">Affectation des Élèves</div>
                <div class="stage-onglet" onclick="changerOnglet('onglet-vue-ensemble')">Vue d'ensemble</div>
            </div>
            
            <!-- Onglet 1: Gestion des Tuteurs -->
            <div id="onglet-tuteurs" class="stage-contenu-onglet active">
                <h3>Gestion des Tuteurs en Entreprise</h3>
                
                <!-- Formulaire d'ajout/modification de tuteur -->
                <div class="stage-form-section">
                    <h4><?php echo $tuteur_a_modifier ? 'Modifier le tuteur' : 'Ajouter un nouveau tuteur'; ?></h4>
                    <form method="post">
                        <input type="hidden" name="action" value="tuteur">
                        <?php if($tuteur_a_modifier): ?>
                            <input type="hidden" name="num_tuteur" value="<?php echo $tuteur_a_modifier['num']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="nom">Nom :</label>
                            <input type="text" id="nom" name="nom" value="<?php echo $tuteur_a_modifier ? htmlspecialchars($tuteur_a_modifier['nom']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="prenom">Prénom :</label>
                            <input type="text" id="prenom" name="prenom" value="<?php echo $tuteur_a_modifier ? htmlspecialchars($tuteur_a_modifier['prenom']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="tel">Téléphone :</label>
                            <input type="tel" id="tel" name="tel" value="<?php echo $tuteur_a_modifier ? htmlspecialchars($tuteur_a_modifier['tel']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email :</label>
                            <input type="email" id="email" name="email" value="<?php echo $tuteur_a_modifier ? htmlspecialchars($tuteur_a_modifier['email']) : ''; ?>" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <?php echo $tuteur_a_modifier ? 'Mettre à jour' : 'Ajouter le tuteur'; ?>
                        </button>
                        
                        <?php if($tuteur_a_modifier): ?>
                            <a href="gestion_stages.php" class="btn btn-secondary">Annuler</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Liste des tuteurs -->
                <div class="stage-table-container">
                    <h4>Liste des tuteurs</h4>
                    <?php if(count($tuteurs) > 0): ?>
                        <table class="eleve-stage-table">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Téléphone</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($tuteurs as $tuteur): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($tuteur['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($tuteur['prenom']); ?></td>
                                        <td><?php echo htmlspecialchars($tuteur['tel']); ?></td>
                                        <td><?php echo htmlspecialchars($tuteur['email']); ?></td>
                                        <td>
                                            <a href="gestion_stages.php?modifier_tuteur=<?php echo $tuteur['num']; ?>" class="btn-stage">Modifier</a>
                                            <a href="gestion_stages.php?supprimer_tuteur=<?php echo $tuteur['num']; ?>" 
                                               class="btn-stage btn-stage-danger" 
                                               onclick="return confirmerSuppression('Êtes-vous sûr de vouloir supprimer ce tuteur ?')">Supprimer</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>👨‍🏫 Aucun tuteur enregistré pour le moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Onglet 2: Gestion des Stages -->
            <div id="onglet-stages" class="stage-contenu-onglet">
                <h3>Gestion des Stages en Entreprise</h3>
                
                <!-- Formulaire d'ajout/modification de stage -->
                <div class="stage-form-section">
                    <h4><?php echo $stage_a_modifier ? 'Modifier le stage' : 'Ajouter un nouveau stage'; ?></h4>
                    <form method="post">
                        <input type="hidden" name="action" value="stage">
                        <?php if($stage_a_modifier): ?>
                            <input type="hidden" name="num_stage" value="<?php echo $stage_a_modifier['num']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="nom_entreprise">Nom de l'entreprise :</label>
                            <input type="text" id="nom_entreprise" name="nom_entreprise" 
                                   value="<?php echo $stage_a_modifier ? htmlspecialchars($stage_a_modifier['nom']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="adresse">Adresse :</label>
                            <input type="text" id="adresse" name="adresse" 
                                   value="<?php echo $stage_a_modifier ? htmlspecialchars($stage_a_modifier['adresse']) : ''; ?>">
                        </div>
                        
                        <div class="stage-form-group-inline">
                            <div class="form-group">
                                <label for="CP">Code postal :</label>
                                <input type="text" id="CP" name="CP" 
                                       value="<?php echo $stage_a_modifier ? htmlspecialchars($stage_a_modifier['CP']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="ville">Ville :</label>
                                <input type="text" id="ville" name="ville" 
                                       value="<?php echo $stage_a_modifier ? htmlspecialchars($stage_a_modifier['ville']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="tel_entreprise">Téléphone de l'entreprise :</label>
                            <input type="tel" id="tel_entreprise" name="tel_entreprise" 
                                   value="<?php echo $stage_a_modifier ? htmlspecialchars($stage_a_modifier['tel']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email_entreprise">Email de l'entreprise :</label>
                            <input type="email" id="email_entreprise" name="email_entreprise" 
                                   value="<?php echo $stage_a_modifier ? htmlspecialchars($stage_a_modifier['email']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="libelleStage">Description du stage :</label>
                            <textarea id="libelleStage" name="libelleStage" rows="4"><?php echo $stage_a_modifier ? htmlspecialchars($stage_a_modifier['libelleStage']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="num_tuteur">Tuteur en entreprise :</label>
                            <select id="num_tuteur" name="num_tuteur" required>
                                <option value="">Sélectionnez un tuteur</option>
                                <?php foreach($tuteurs as $tuteur): ?>
                                    <option value="<?php echo $tuteur['num']; ?>"
                                        <?php echo ($stage_a_modifier && $stage_a_modifier['num_tuteur'] == $tuteur['num']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tuteur['prenom'] . ' ' . $tuteur['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if(count($tuteurs) == 0): ?>
                                <p style="color: #e53e3e; font-size: 0.9em;">
                                    ⚠️ Aucun tuteur disponible. Veuillez d'abord créer un tuteur.
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" <?php echo count($tuteurs) == 0 ? 'disabled' : ''; ?>>
                            <?php echo $stage_a_modifier ? 'Mettre à jour' : 'Ajouter le stage'; ?>
                        </button>
                        
                        <?php if($stage_a_modifier): ?>
                            <a href="gestion_stages.php" class="btn btn-secondary">Annuler</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Liste des stages -->
                <div class="stage-table-container">
                    <h4>Liste des stages disponibles</h4>
                    <?php if(count($stages) > 0): ?>
                        <table class="eleve-stage-table">
                            <thead>
                                <tr>
                                    <th>Entreprise</th>
                                    <th>Adresse</th>
                                    <th>Ville</th>
                                    <th>Tuteur</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($stages as $stage): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($stage['nom']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($stage['email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($stage['adresse']); ?></td>
                                        <td><?php echo htmlspecialchars($stage['CP'] . ' ' . $stage['ville']); ?></td>
                                        <td>
                                            <?php if($stage['tuteur_nom']): ?>
                                                <?php echo htmlspecialchars($stage['tuteur_prenom'] . ' ' . $stage['tuteur_nom']); ?>
                                            <?php else: ?>
                                                <em>Non assigné</em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if(strlen($stage['libelleStage']) > 50) {
                                                echo htmlspecialchars(substr($stage['libelleStage'], 0, 50)) . '...';
                                            } else {
                                                echo htmlspecialchars($stage['libelleStage']);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="gestion_stages.php?modifier_stage=<?php echo $stage['num']; ?>" class="btn-stage">Modifier</a>
                                            <a href="gestion_stages.php?supprimer_stage=<?php echo $stage['num']; ?>" 
                                               class="btn-stage btn-stage-danger" 
                                               onclick="return confirmerSuppression('Êtes-vous sûr de vouloir supprimer ce stage ?')">Supprimer</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>🏢 Aucun stage enregistré pour le moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Onglet 3: Affectation des Élèves -->
            <div id="onglet-affectation" class="stage-contenu-onglet">
                <h3>Affectation des Élèves aux Stages</h3>
                
                <div class="stage-info-bulle">
                    <p><strong>📋 Information :</strong> Vous pouvez ici affecter un stage à un élève. Un élève ne peut avoir qu'un seul stage à la fois.</p>
                </div>
                
                <!-- Formulaire d'affectation -->
                <div class="stage-form-section">
                    <h4>Affecter un élève à un stage</h4>
                    <form method="post">
                        <input type="hidden" name="action" value="affectation">
                        
                        <div class="form-group">
                            <label for="num_eleve">Élève :</label>
                            <select id="num_eleve" name="num_eleve" required>
                                <option value="">Sélectionnez un élève</option>
                                <?php foreach($eleves as $eleve): ?>
                                    <option value="<?php echo $eleve['num']; ?>">
                                        <?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?>
                                        <?php if($eleve['stage_nom']): ?>
                                            (Actuellement: <?php echo htmlspecialchars($eleve['stage_nom']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="num_stage_affectation">Stage :</label>
                            <select id="num_stage_affectation" name="num_stage_affectation" required>
                                <option value="">Sélectionnez un stage</option>
                                <?php foreach($stages as $stage): ?>
                                    <option value="<?php echo $stage['num']; ?>">
                                        <?php echo htmlspecialchars($stage['nom']); ?> - 
                                        <?php if($stage['tuteur_nom']): ?>
                                            <?php echo htmlspecialchars($stage['tuteur_prenom'] . ' ' . $stage['tuteur_nom']); ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" <?php echo (count($eleves) == 0 || count($stages) == 0) ? 'disabled' : ''; ?>>
                            Affecter l'élève au stage
                        </button>
                        
                        <?php if(count($eleves) == 0): ?>
                            <p style="color: #e53e3e; margin-top: 10px;">
                                ⚠️ Aucun élève disponible pour l'affectation.
                            </p>
                        <?php endif; ?>
                        <?php if(count($stages) == 0): ?>
                            <p style="color: #e53e3e; margin-top: 10px;">
                                ⚠️ Aucun stage disponible pour l'affectation.
                            </p>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Liste des élèves avec leur stage -->
                <div class="stage-table-container">
                    <h4>Élèves et leur affectation de stage</h4>
                    <?php if(count($eleves) > 0): ?>
                        <table class="eleve-stage-table">
                            <thead>
                                <tr>
                                    <th>Élève</th>
                                    <th>Email</th>
                                    <th>Stage affecté</th>
                                    <th>Tuteur</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($eleves as $eleve): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($eleve['email']); ?></td>
                                        <td>
                                            <?php if($eleve['stage_nom']): ?>
                                                <span class="statut-affecte">✅ <?php echo htmlspecialchars($eleve['stage_nom']); ?></span>
                                            <?php else: ?>
                                                <span class="statut-non-affecte">❌ Non affecté</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            // Trouver le tuteur du stage de l'élève
                                            if($eleve['stage_num']) {
                                                foreach($stages as $stage) {
                                                    if($stage['num'] == $eleve['stage_num']) {
                                                        echo htmlspecialchars($stage['tuteur_prenom'] . ' ' . $stage['tuteur_nom']);
                                                        break;
                                                    }
                                                }
                                            } else {
                                                echo '<em>Non applicable</em>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if($eleve['stage_nom']): ?>
                                                <span class="stage-badge stage-badge-success">Affecté</span>
                                            <?php else: ?>
                                                <span class="stage-badge stage-badge-danger">En attente</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>👨‍🎓 Aucun élève inscrit pour le moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Onglet 4: Vue d'ensemble -->
            <div id="onglet-vue-ensemble" class="stage-contenu-onglet">
                <h3>Vue d'ensemble des Stages</h3>
                
                <div class="stage-info-bulle">
                    <p><strong>📊 Résumé :</strong> Ce tableau présente une vue complète de tous les stages, avec les informations des entreprises, des tuteurs et des élèves affectés.</p>
                </div>
                
                <div class="stage-table-container">
                    <table class="eleve-stage-table">
                        <thead>
                            <tr>
                                <th>Entreprise</th>
                                <th>Localisation</th>
                                <th>Tuteur</th>
                                <th>Élève(s) affecté(s)</th>
                                <th>Contact</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($stages as $stage): ?>
                                <?php 
                                // Trouver les élèves affectés à ce stage
                                $eleves_stage = array_filter($eleves, function($e) use ($stage) {
                                    return $e['stage_num'] == $stage['num'];
                                });
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($stage['nom']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($stage['libelleStage']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($stage['adresse']); ?><br>
                                        <?php echo htmlspecialchars($stage['CP'] . ' ' . $stage['ville']); ?>
                                    </td>
                                    <td>
                                        <?php if($stage['tuteur_nom']): ?>
                                            <?php echo htmlspecialchars($stage['tuteur_prenom'] . ' ' . $stage['tuteur_nom']); ?><br>
                                            <small><?php echo htmlspecialchars($stage['email']); ?></small>
                                        <?php else: ?>
                                            <em>Non assigné</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(count($eleves_stage) > 0): ?>
                                            <ul style="margin: 0; padding-left: 20px;">
                                                <?php foreach($eleves_stage as $eleve): ?>
                                                    <li><?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <span style="color: #6c757d;"><em>Aucun élève affecté</em></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        Tel: <?php echo htmlspecialchars($stage['tel']); ?><br>
                                        Email: <?php echo htmlspecialchars($stage['email']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="stage-detailed-stats">
                    <h4>📈 Statistiques détaillées</h4>
                    <div class="stage-stats-details">
                        <div class="stage-stat-item">
                            <h5>Élèves sans stage</h5>
                            <p class="stage-stat-number" style="color: #dc3545;">
                                <?php echo count(array_filter($eleves, function($e) { return empty($e['stage_num']); })); ?>
                            </p>
                        </div>
                        <div class="stage-stat-item">
                            <h5>Stages sans élève</h5>
                            <p class="stage-stat-number" style="color: #ffc107;">
                                <?php 
                                $stages_sans_eleve = 0;
                                foreach($stages as $stage) {
                                    $eleves_stage = array_filter($eleves, function($e) use ($stage) {
                                        return $e['stage_num'] == $stage['num'];
                                    });
                                    if(count($eleves_stage) == 0) {
                                        $stages_sans_eleve++;
                                    }
                                }
                                echo $stages_sans_eleve;
                                ?>
                            </p>
                        </div>
                        <div class="stage-stat-item">
                            <h5>Taux d'affectation</h5>
                            <p class="stage-stat-percentage" style="color: #28a745;">
                                <?php 
                                $eleves_affectes = count(array_filter($eleves, function($e) { return !empty($e['stage_num']); }));
                                $taux = count($eleves) > 0 ? round(($eleves_affectes / count($eleves)) * 100) : 0;
                                echo $taux . '%';
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="accueil_prof.php" class="btn btn-primary">Retour à l'accueil</a>
            </div>
        </main>
    </div>
</body>
</html>