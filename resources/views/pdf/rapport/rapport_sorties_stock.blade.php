<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport des Sorties de Stock</title>
    <style>
        @page {
            size: landscape; /* Orientation paysage pour plus de colonnes */
            margin: 20mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        h1 {
            text-align: center;
            margin-bottom: 5px;
            font-size: 20px;
            color: #333;
        }
        h2 {
            text-align: center;
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 14px;
            color: #555;
        }
        .header-info {
            text-align: right;
            font-size: 9px;
            margin-bottom: 10px;
            color: #777;
        }
        .filters-info {
            font-size: 11px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            padding: 12px;
            background-color: #f8f8f8;
            border-radius: 4px;
        }
        .filters-info h3 {
            font-size: 13px;
            margin-top: 0;
            margin-bottom: 10px;
            color: #333;
        }
        .filters-info ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .filters-info li {
            margin-bottom: 5px;
        }
        .filters-info strong {
            display: inline-block;
            min-width: 160px;
            color: #00993E;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #00993E;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: normal;
            font-size: 11px;
            border: 1px solid #00772E;
        }
        td {
            padding: 6px;
            border-bottom: 1px solid #e0e0e0;
            border-left: 1px solid #f0f0f0;
            border-right: 1px solid #f0f0f0;
            vertical-align: top;
        }
        tr:nth-child(even) {
            background-color: #f5f5f5;
        }
        tr:nth-child(odd) {
            background-color: #ffffff;
        }
        .no-data {
            text-align: center;
            color: #999;
            padding: 20px;
            font-size: 14px;
            border: 1px dashed #ccc;
            margin-top: 20px;
            background-color: #fffafa;
        }
        .footer-info {
            text-align: center;
            font-size: 9px;
            margin-top: 30px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header-info">
        Rapport généré le: {{ date('d/m/Y H:i:s') }}
    </div>
    <h1>Rapport des Sorties de Stock</h1>
    <h2>LNB-Stock & Parc</h2>

    <!-- Bloc d'informations sur les filtres appliqués -->
    <div class="filters-info">
        <h3>Filtres appliqués :</h3>
        <ul>
            <li>
                <strong>Période de Mouvement :</strong>
                Du {{ \Carbon\Carbon::parse($filters['date_debut'])->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($filters['date_fin'])->format('d/m/Y') }}
            </li>
            <li>
                <strong>Article :</strong>
                @if(isset($filters['id_Article']) && !empty($filters['id_Article']))
                    @php
                        $article = \App\Models\Article::find($filters['id_Article']);
                    @endphp
                    {{ $article->code_article ?? '' }} - {{ $article->libelle ?? 'N/A' }}
                @else
                    Tous
                @endif
            </li>
            <li>
                <strong>Demandeur / Employé :</strong>
                @if(isset($filters['id_employe']) && !empty($filters['id_employe']))
                    @php
                        $employe = \App\Models\Employe::find($filters['id_employe']);
                    @endphp
                    {{ $employe->nom ?? '' }} {{ $employe->prenom ?? 'N/A' }}
                @else
                    Tous
                @endif
            </li>
        </ul>
    </div>

    @if($mouvements->isEmpty())
        <p class="no-data">Aucune sortie de stock trouvée pour les critères de recherche spécifiés.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Date Mouvement</th>
                    <th>Article</th>
                    <th>Code Article</th>
                    <th>Quantité</th>
                    <th>Demandeur</th>
                    <th>Type Mouvement</th>
                    <th>Description</th>
                    <th>N° Bordereau</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mouvements as $index => $mouvement)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($mouvement->date_mouvement)->format('d/m/Y') }}</td>
                    <td>{{ $mouvement->article->libelle ?? 'N/A' }}</td>
                    <td>{{ $mouvement->article->code_article ?? 'N/A' }}</td>
                    <td>{{ number_format($mouvement->qte, 0, ',', ' ') }}</td>
                    <td>{{ $mouvement->employe->nom ?? '' }} {{ $mouvement->employe->prenom ?? 'N/A' }}</td>
                    <td>{{ $mouvement->type_mouvement->libelle_type_mouvement ?? 'N/A' }}</td>
                    <td>{{ $mouvement->description ?? 'N/A' }}</td>
                    <td>{{ $mouvement->numero_borderau ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer-info">
        Rapport des Sorties de Stock - Système de Gestion de Stock et Immobilisations
    </div>
</body>
</html>
