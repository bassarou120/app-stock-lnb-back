<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Interventions de Véhicule</title>
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
    <h2>LNB-Stock & Parc | Liste des Interventions de Véhicule</h2>
    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Véhicule</th>
                <th>Type Intervention</th>
                <th>Titre</th>
                <th>Date Intervention</th>
                <th>Montant</th>
                <th>Observation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($interventions as $index => $intervention)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $intervention->vehicule->immatriculation ?? 'N/A' }}</td>
                    <td>{{ $intervention->typeIntervention->libelle_type_intervention ?? 'N/A' }}</td>
                    <td>{{ $intervention->titre }}</td>
                    <td>{{ \Carbon\Carbon::parse($intervention->date_intervention)->format('d/m/Y') }}</td>
                    <td>{{ number_format($intervention->montant, 2, ',', ' ') }}</td>
                    <td>{{ $intervention->observation ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
