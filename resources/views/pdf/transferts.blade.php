<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Transfert d'immobilisation</title>
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
    <h2>LNB-Stock & Parc | Transfert d'immobilisation</h2>
    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Code Immobilisation</th>
                <th>Désignation</th>
                <th>Ancien Bureau</th>
                <th>Nouveau Bureau</th>
                <th>Ancien Personnel</th>
                <th>Nouveau Personnel</th>
                <th>Date Mouvement</th>
                <th>Observation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transferts as $index => $transfert)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $transfert->immobilisation->code ?? 'N/A' }}</td>
                    <td>{{ $transfert->immobilisation->designation ?? 'N/A' }}</td>
                    <td>{{ $transfert->old_bureau->libelle_bureau ?? 'N/A' }}</td>
                    <td>{{ $transfert->bureau->libelle_bureau ?? 'N/A' }}</td>
                    <td>{{ $transfert->old_employe->nom ?? '' }} {{ $transfert->old_employe->prenom ?? 'N/A' }}</td>
                    <td>{{ $transfert->employe->nom ?? '' }} {{ $transfert->employe->prenom ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($transfert->date_mouvement)->format('d/m/Y') }}</td>
                    <td>{{ $transfert->observation ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
