<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Entrée de Stock</title>
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
    <h2>LNB-Stock & Parc | Entrée de Stock</h2>
    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Code Article</th>
                <th>Article</th>
                <th>Description</th>
                <th>Qte</th>
                <th>Date Mouvement</th>
                <th>Fournisseur</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mouvements as $index => $mouvement)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $mouvement->article->code_article ?? 'N/A' }}</td>
                    <td>{{ $mouvement->article->libelle ?? 'N/A' }}</td>
                    <td>{{ $mouvement->description }}</td>
                    <td>{{ $mouvement->qte }}</td>
                    <td>{{ \Carbon\Carbon::parse($mouvement->date_mouvement)->format('d/m/Y') }}</td>
                    <td>{{ $mouvement->fournisseur->nom ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
