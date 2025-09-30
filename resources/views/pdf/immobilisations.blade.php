<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Immobilisations</title>
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
    <h2>LNB-Stock & Parc | Liste des Immobilisations</h2>
    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Code</th>
                <th>Désignation</th>
                <th>Montant</th>
                <th>Etat</th>
                <th>Observation</th>
                <th>Groupe Type</th>
                <th>Sous Type</th>
                <th>Statut</th>
                <!-- <th>Bureau</th> -->
                <th>Personnel</th>
                <!-- <th>Fournisseur</th> -->
                <th>Date Acquisition</th>
                
            </tr>
        </thead>
        <tbody>
            @foreach($immobilisations as $index => $immo)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $immo->code ?? '-' }}</td>
                    <td>{{ $immo->designation ?? '-' }}</td>
                    <td>{{ number_format($immo->montant_ttc, 2, ',', ' ') ?? '-' }}</td>
                    <td>{{ $immo->etat ?? '-' }}</td>
                    <td>{{ $immo->observation ?? '-' }}</td>
                    <td>{{ $immo->groupeTypeImmo->libelle ?? '-' }}</td>
                    <td>{{ $immo->sousTypeImmo->libelle ?? '-' }}</td>
                    <td>{{ $immo->statusImmo->libelle_status_immo ?? '-' }}</td>
                    <!-- <td>{{ $immo->bureau->libelle_bureau ?? 'N/A' }}</td> -->
                    <td>{{ $immo->employe->nom ?? '' }} {{ $immo->employe->prenom ?? '-' }}</td>
                    <!-- <td>{{ $immo->fournisseur->nom ?? 'N/A' }}</td> -->
                    <td>{{ \Carbon\Carbon::parse($immo->date_acquisition)->format('d/m/Y') ?? '-' }}</td>
                    
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
