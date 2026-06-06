<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        .button {
            background-color: #F97316; /* Ton Orange */
            border: none;
            color: white;
            padding: 12px 24px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 8px;
            font-weight: bold;
        }
    </style>
</head>
<body style="font-family: sans-serif; background-color: #f8fafc; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; bg-color: #ffffff; padding: 40px; border-radius: 12px; border: 1px solid #e2e8f0; background: white;">
        
        <h2 style="color: #1D4ED8; text-align: center;">Mon<span style="color: #F97316;">App</span></h2>
        
        <h1 style="font-size: 20px; color: #1e293b;">Bonjour,</h1>
        
        <p style="color: #475569; line-height: 1.6;">
            Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.
        </p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" class="button" style="color: white;">Réinitialiser mon mot de passe</a>
        </div>
        
        <p style="color: #475569; line-height: 1.6;">
            Ce lien expirera dans 60 minutes. Si vous n'avez pas demandé de réinitialisation, aucune action supplémentaire n'est requise.
        </p>
        
        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 30px 0;">
        
        <p style="font-size: 12px; color: #94a3b8; text-align: center;">
            Si le bouton ne fonctionne pas, copiez et collez l'URL suivante dans votre navigateur : <br>
            <span style="color: #1D4ED8;">{{ $url }}</span>
        </p>
    </div>
</body>
</html>