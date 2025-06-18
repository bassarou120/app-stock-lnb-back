<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport de Stock {{ $reportTypeLabel }}</title>
    <style>
        @page {
            size: landscape; /* Orientation paysage */
            margin: 20mm; /* Marge générale pour toutes les pages */
        }
        body {
            font-family: 'DejaVu Sans', sans-serif; /* Conservez DejaVu Sans pour la compatibilité UTF-8 en PDF */
            font-size: 12px;
            margin: 0; /* Les marges sont gérées par @page */
            padding: 0;
        }
        h1 {
            text-align: center;
            margin-bottom: 5px; /* Réduit l'espace après le titre principal */
            font-size: 20px; /* Taille légèrement plus grande pour le titre principal */
            color: #333;
        }
        h2 {
            text-align: center;
            margin-top: 0; /* Colle au titre principal */
            margin-bottom: 25px; /* Plus d'espace après la section titre/marque */
            font-size: 14px; /* Taille pour le sous-titre/marque */
            color: #555;
        }
        .header-info {
            text-align: right;
            font-size: 9px;
            margin-bottom: 10px;
            color: #777;
        }
        .filters-info {
            font-size: 11px; /* Légèrement plus grand pour les filtres */
            margin-bottom: 20px;
            border: 1px solid #e0e0e0; /* Cadre plus clair */
            padding: 12px; /* Plus de padding */
            background-color: #f8f8f8; /* Arrière-plan très léger */
            border-radius: 4px; /* Coins légèrement arrondis */
        }
        .filters-info h3 {
            font-size: 13px;
            margin-top: 0;
            margin-bottom: 10px;
            color: #333;
        }
        .filters-info ul {
            list-style: none; /* Supprime les puces */
            padding: 0;
            margin: 0;
        }
        .filters-info li {
            margin-bottom: 5px;
        }
        .filters-info strong {
            display: inline-block;
            min-width: 140px; /* Alignement des étiquettes */
            color: #00993E; /* Couleur de la marque pour les étiquettes de filtre */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px; /* Espace après les filtres */
        }
        th {
            background-color: #00993E; /* Vert pour l'en-tête */
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: normal; /* Normalise le poids de la police */
            font-size: 13px;
            border: 1px solid #00772E; /* Bordure foncée pour l'en-tête */
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
            border-left: 1px solid #f0f0f0; /* Bordures latérales très légères */
            border-right: 1px solid #f0f0f0;
            vertical-align: top; /* Alignement en haut pour le contenu */
        }
        tr:nth-child(even) {
            background-color: #f5f5f5; /* Lignes paires gris clair */
        }
        tr:nth-child(odd) {
            background-color: #ffffff; /* Lignes impaires blanches */
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
    <h1>Rapport {{ $reportTypeLabel }}</h1>
    <h2>LNB-Stock & Parc</h2>

    <!-- Bloc d'informations sur les filtres appliqués -->
    <div class="filters-info">
        <h3>Filtres appliqués :</h3>
        <ul>
            <li>
                <strong>Période du :</strong>
                {{ $filterLabels['date_debut'] ?? 'Toutes les dates' }}
            </li>
            <li>
                <strong>Au :</strong>
                {{ $filterLabels['date_fin'] ?? 'Toutes les dates' }}
            </li>
            <li>
                <strong>Article :</strong>
                {{ $filterLabels['article'] ?? 'Tous' }}
            </li>
            @if($reportTypeLabel === 'd\'Entrée de Stock')
                <li>
                    <strong>Fournisseur :</strong>
                    {{ $filterLabels['fournisseur'] ?? 'Tous' }}
                </li>
            @elseif($reportTypeLabel === 'de Sortie de Stock')
                <li>
                    <strong>Demandeur :</strong>
                    {{ $filterLabels['employe'] ?? 'Tous' }}
                </li>
            @endif
        </ul>
    </div>

    @if($mouvements->isEmpty())
        <p class="no-data">Aucun mouvement de stock trouvé pour les critères de recherche spécifiés.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Date Mouvement</th>
                    <th>Article</th>
                    
                    <th>Quantité</th>
                    <!-- Colonnes conditionnelles basées sur le type de rapport -->
                    @if($reportTypeLabel === 'd\'Entrée de Stock')
                        <th>Code Article</th>
                        <th>Fournisseur</th>
                    @elseif($reportTypeLabel === 'de Sortie de Stock')
                        <th>Personel</th>
                        <th>Code mouvement</th>
                        <th>Bureau</th>
                    @endif
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mouvements as $index => $mouvement)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($mouvement->date_mouvement)->format('d/m/Y') }}</td>
                    <td>{{ $mouvement->article->libelle ?? 'N/A' }}</td>
                    
                    <td>{{ $mouvement->qte }}</td>
                    @if($reportTypeLabel === 'd\'Entrée de Stock')
                        <td>{{ $mouvement->article->code_article ?? 'N/A' }}</td>
                        <td>{{ $mouvement->fournisseur->nom ?? 'N/A' }}</td>
                    @elseif($reportTypeLabel === 'de Sortie de Stock')
                        <td>{{ $mouvement->employe->nom ?? '' }} {{ $mouvement->employe->prenom ?? 'N/A' }}</td>
                        <td>{{ $mouvement->code_mouvement ?? 'N/A' }}</td>
                        <td>{{ $mouvement->bureau->libelle_bureau ?? 'N/A' }}</td>
                    @endif
                    <td>{{ $mouvement->description ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer-info">
        Rapport de Stock - Système de Gestion de Stock et Immobilisations
    </div>
</body>
</html>
