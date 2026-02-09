<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de Passe Mis à Jour</title>
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #F5FFF9;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px #ddd;
            margin: 20px;
            text-align: left;
            margin: 0 auto;
        }
        h1 {
            color: #00993E;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        ul li {
            margin: 8px 0;
        }
        .footer {
            background-color: #00993E;
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 14px;
            margin-top: 20px;
            border-radius: 5px;
        }
        .header {
            margin-bottom: 20px;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Application de Gestion de Stock et Pack</h1>
        </div>

        <p>Bonjour Mr/Mrs {{ $name }},</p>
        <p>Votre mot de passe a été mis à jour avec succès.</p>
        <p>Voici vos nouveaux identifiants :</p>
        <ul>
            <li><strong>Email :</strong> {{ $email }}</li>
            <li><strong>Nouveau mot de passe :</strong> {{ $password }}</li>
        </ul>
        <p>Si vous n'êtes pas à l'origine de cette modification, veuillez contacter immédiatement l'administrateur.</p>

        <p>Cordialement,</p>
        <p>L'équipe</p>
    </div>

    <div class="footer">
        <p>Application de Gestion de Stock et Pack</p>
    </div>
</body>
</html>
