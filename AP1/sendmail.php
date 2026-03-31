
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';

$mail = new PHPMailer(true);

try {
    // Config SMTP Hostinger
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'contact@sioslam.fr';  // ⚠️ remplace par ton email Hostinger
    $mail->Password   = '&5&Y@*QHb';            // ⚠️ remplace par le mot de passe de cette boîte mail
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
    $mail->Port       = 587;

    // Expéditeur
    $mail->setFrom('contact@sioslam.fr', 'CONTACT SIOSLAM');
    // Destinataire
    $mail->addAddress('MONADRESSE@gmail.com', 'Moi');

    // Contenu
    $mail->isHTML(true);
    $mail->Subject = 'Test sujet';
    $mail->Body    = 'Mon message blablabla';
    $mail->AltBody = 'Mon message blablabla';

    $mail->send();
    echo "✅ Email envoyé avec succès !";
} catch (Exception $e) {
    echo "❌ Erreur d'envoi : {$mail->ErrorInfo}";
}
?>
