<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';

$mail = new PHPMailer(true); ?>



Retrouver votre mot de passe <hr>


<?php 
include "_conf.php";

function genererChaineAleatoire($longueur = 10) {
    // Lettres majuscules, minuscules, chiffres et caractères spéciaux
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{};:,.<>?';
    $chaineAleatoire = '';
    $longueurCaracteres = strlen($caracteres);

    for ($i = 0; $i < $longueur; $i++) {
        $indexAleatoire = random_int(0, $longueurCaracteres - 1);
        $chaineAleatoire .= $caracteres[$indexAleatoire];
    }

    return $chaineAleatoire;
}

// *********
// si j'ai envoyé un mail
// *********
if (isset($_POST['email']))
{
     $lemail=$_POST['email'];

     // je me connecte a la bdd
     $bdd = mysqli_connect ($serveurBDD,$userBDD, $mdpBDD, $nomBDD);

     // je selectionne l'utilisateur qui a son email et je recupere son mot de passe 

        $requete="Select * from utilisateur where email='$lemail'";
        $resultat = mysqli_query ($bdd, $requete);
        $mdp=0;
        while($donnees = mysqli_fetch_assoc($resultat))
        {
            $mdp =$donnees['motdepasse'];

        }
        if ($mdp==0) // afficher l'erreur l'email n'existe pas
        {
            echo "erreur d'envoie d'email";
        }
        else // si l'utilisateur existe = envoie d'email
        {
            echo " envoie de l'email";

            // on genere un mot de passe aleatoire 
            $newmdp = genererChaineAleatoire(10);
            
            // BCRYPT au lieu de SHA-256
            $mdphash = password_hash($newmdp, PASSWORD_BCRYPT);

            // on met a jour la BDD
            $requete2 = "UPDATE `utilisateur` SET `motdepasse` = '$mdphash' WHERE `utilisateur`.`email` = '$lemail';";
            if (!mysqli_query($bdd, $requete2)) 
            {
                echo "<br>Erreur : " . mysqli_error($bdd) . "<br>";
            }

            try {
                // Config SMTP Hostinger
                $mail->isSMTP();
                $mail->Host       = 'smtp.hostinger.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'contact@siolapie.com';
                $mail->Password   = 'EmailL@pie25';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                $mail->Port       = 587;

                // Expéditeur
                $mail->setFrom('contact@siolapie.com', 'CONTACT SIOSLAM');
                // Destinataire
                $mail->addAddress($lemail, 'Utilisateur');

                // Contenu
                $mail->isHTML(true);
                $mail->Subject = 'Mot de passe perdu sioslam CR STAGE';
                $mail->Body    = 'Voici votre nouveau mot de passe : ' . $newmdp . 
                '<br>Pensez a le changer sur votre compte';

                $mail->send();
                echo "✅ Email envoyé avec succès !";
            } catch (Exception $e) {
                echo "❌ Erreur d'envoi : {$mail->ErrorInfo}";
            }
        }
}
else // sinon pas d'email premier affichage 
{
    ?>
    <form method="post">
    <input type="email" name="email">
    <input type="submit" value="Confirmer">
    </form>

    <?php
}
?>