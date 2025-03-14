<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de votre mot de passe</title>
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #F5FFF9;
            padding: 20px;
            text-align: center;
        }
        .container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px #ddd;
            align-items: center;
        }
        .otp {
            font-size: 24px;
            font-weight: bold;
            background-color: #00993E;
            color: white;
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }
        .header, .header2 {
            text-align: center;
            margin-bottom: 20px;
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
        h1 {
            color: #00993E;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Application de Gestion de Stock et Pack </h1>
        </div>

        <h2> Réinitialisation de votre mot de passe</h2>
        <p>Voici votre code OTP pour réinitialiser votre mot de passe :</p>

        <!-- OTP centered -->
        <div class="otp">{{ $otp }}</div>

        <p>⚠ Ce code expire dans 10 minutes.</p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Application de Gestion de Stock et Pack</p>
    </div>
</body>
</html>
