<!-- <!DOCTYPE html>
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
                <th>Code Immo</th>
                <th>Desigmation</th>
                <th>Ancien Bureau</th>
                <th>Nouveau Bureau</th>
                <th>Ancien Personnel</th>
                <th>Nouveau Personnel</th>
                <th>Date de création</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transferts as $transfert)
                <tr>
                    <td>{{ $transfert->immo_id  ?? '-' }}</td>
                    <td>{{ $transfert->libelle  ?? '-' }}</td>
                    <td>{{ $transfert->old_bureau_id ? $immo->bureau_id '-' }}</td>
                    <td>{{ $transfert->bureau_id ?? '-' }}</td>
                    <td>{{ $transfert->old_employe_id ? $immo->employe_id }}</td>
                    <td>{{ $transfert->employe_id ?? '-' }}</td>
                    <td>{{ $transfert->created_at ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html> -->
