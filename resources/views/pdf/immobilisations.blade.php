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
                <th>Désignation</th>
                <th>Code</th>
                <th>Groupe Type</th>
                <th>Sous Type</th>
                <th>Statut</th>
                <th>Bureau</th>
                <th>Responsable</th>
                <th>Fournisseur</th>
                <th>Date Acquisition</th>
                <th>Montant TTC</th>
            </tr>
        </thead>
        <tbody>
            @foreach($immobilisations as $index => $immo)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $immo->designation ?? 'N/A' }}</td>
                    <td>{{ $immo->code ?? 'N/A' }}</td>
                    <td>{{ $immo->groupeTypeImmo->libelle_groupe_type_immo ?? 'N/A' }}</td>
                    <td>{{ $immo->sousTypeImmo->libelle_sous_type_immo ?? 'N/A' }}</td>
                    <td>{{ $immo->statusImmo->libelle_status_immo ?? 'N/A' }}</td>
                    <td>{{ $immo->bureau->libelle_bureau ?? 'N/A' }}</td>
                    <td>{{ $immo->employe->nom ?? '' }} {{ $immo->employe->prenom ?? 'N/A' }}</td>
                    <td>{{ $immo->fournisseur->nom ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($immo->date_acquisition)->format('d/m/Y') ?? 'N/A' }}</td>
                    <td>{{ number_format($immo->montant_ttc, 2, ',', ' ') ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
