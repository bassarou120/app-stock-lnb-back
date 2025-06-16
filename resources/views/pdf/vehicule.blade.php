<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Véhicules</title>
    <style>
        @page {
            size: landscape;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #00993E;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: normal;
            font-size: 13px;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        tr:nth-child(even) {
            background-color: #f5f5f5;
        }
        tr:nth-child(odd) {
            background-color: #ffffff;
        }
    </style>
</head>
<body>
    <h2>LNB-Stock & Parc | Liste des Véhicules</h2>
    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Immatriculation</th>
                <th>Marque</th>
                <th>Modèle</th>
                <th>Numéro de Châssis</th>
                <th>Kilométrage (km)</th>
                <th>Date de Mise en Service</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vehicules as $index => $vehicule)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $vehicule->immatriculation ?? 'N/A' }}</td>
                    <td>{{ $vehicule->marque->libelle ?? 'N/A' }}</td>
                    <td>{{ $vehicule->modele->libelle_modele }}</td>
                    <td>{{ $vehicule->numero_chassis }}</td>
                    <td>{{ $vehicule->kilometrage ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($vehicule->date_mise_en_service)->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
