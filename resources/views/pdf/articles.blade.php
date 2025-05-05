<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Etat du Stock</title>
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
    <h2>LNB-Stock & Parc | Etat du Stock</h2>
    <table>
        <thead>
            <tr>
                <th>Article</th>
                <th>Description</th>
                <th>Catégorie</th>
                <th>Quantité Actuelle</th>
                <th>Stock d'alerte</th>
                <th>Date de création</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($articles as $article)
                <tr>
                    <td>{{ $article->libelle  ?? '-' }}</td>
                    <td>{{ $article->description ?? '-' }}</td>
                    <td>{{ $article->categorie ? $article->categorie->libelle_categorie_article : '-' }}</td>
                    <td>{{ $article->stock ? $article->stock->Qte_actuel : 0 }}</td>
                    <td>{{ $article->stock_alerte ?? '-' }}</td>
                    <td>{{ $article->created_at ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
