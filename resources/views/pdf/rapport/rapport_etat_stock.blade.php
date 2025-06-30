<!DOCTYPE html>
<html>
<head>
    <title>Rapport d'État de Stock</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif; /* Important pour les caractères spéciaux en PDF */
            font-size: 10px;
            margin: 20px;
        }
        h1, h2 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left; /* Par défaut, alignement à gauche pour les données */
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center; /* En-têtes centrés */
        }
        /* Styles pour l'en-tête et le pied de page du document */
        .header-document-top {
            position: absolute; /* Position absolue pour n'apparaître qu'une fois */
            top: 20px;
            left: 20px;
            line-height: 1.5;
            font-size: 9px;
        }
        .main-header {
            text-align: center;
            margin-top: 50px; /* Espace pour l'en-tête "République du Bénin" */
            margin-bottom: 20px;
        }
        .footer {
            width: 100%;
            text-align: center;
            position: fixed; /* Le pied de page reste fixe sur toutes les pages */
            bottom: 0;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        /* Conteneur pour les sections filtre et statistiques (côte à côte) */
        .summary-sections-container {
            display: block; /* Important pour Dompdf, float sera utilisé */
            margin-bottom: 20px;
            clear: both; /* Important pour s'assurer que cela commence après le main-header */
        }
        .filter-section, .stats-section {
            background-color: #fff;
            padding: 10px;
            border-radius: 5px;
            box-sizing: border-box;
            width: 49%; /* Chaque section prend presque la moitié de la largeur */
        }
        .filter-section {
            float: left; /* Aligne les filtres à gauche */
        }
        .stats-section {
            float: right; /* Aligne les statistiques à droite */
        }
        .summary-sections-container::after { /* Clearfix pour contenir les flottements */
            content: "";
            display: table;
            clear: both;
        }
        /* Alignement des données numériques à droite */
        .numeric-col {
            text-align: right;
        }
        /* Alignement des dates ou informations courtes */
        .center-col {
            text-align: center;
        }
    </style>
</head>
<body>
    {{-- En-tête statique du document, apparaît une seule fois sur la première page --}}
    {{-- Conditionnel pour le premier article, sinon il sera ignoré si $rapportArticles est vide --}}
    @if (!empty($rapportArticles))
        @if ($loop->first ?? true) {{-- Le ?? true est un fallback, mais $loop->first suffit si @foreach est bien là --}}
            <div class="header">
                <p>République du Bénin</p>
                <p>LNB-Lotterie National du Bénin SA</p>
                <p class="right">Rapport généré le: {{ date('d/m/Y H:i:s') }}</p>
            </div>
        @endif
    @endif

    <h1 class="main-header">Rapport d'État de Stock</h1>

    {{-- Conteneur pour les sections filtre et statistiques --}}
    <div class="summary-sections-container">
        <div class="filter-section">
            <h2>Filtres Appliqués</h2>
            <p><strong>Période :</strong> Du {{ $filterLabels['date_debut'] }} au {{ $filterLabels['date_fin'] }}</p>
            <p><strong>Article :</strong> {{ $filterLabels['article'] }}</p>
            <p><strong>Quantité Min. :</strong> {{ $filterLabels['qte_min'] }}</p>
            <p><strong>Quantité Max. :</strong> {{ $filterLabels['qte_max'] }}</p>
        </div>

        <div class="stats-section">
            <h2>Statistiques Globales</h2>
            <p><strong>Nombre d'articles analysés :</strong> {{ $statistiques['nombre_articles'] }}</p>
            <p><strong>Période d'analyse :</strong> {{ $statistiques['periode_analysee'] }}</p>
        </div>
    </div> {{-- Fin du summary-sections-container --}}

    @if(count($rapportArticles) > 0)
        <table>
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Article</th>
                    <th>Code Article</th>
                    <th>Stock Actuel (Qté)</th>
                    <th>Prix Unitaire</th>
                    <th>CMP</th>
                    <th>Total Stock</th>
                    <th>Dernière Entrée</th>
                    <th>Dernière Sortie</th>
                    <th>Total Entrées</th>
                    <th>Total Sorties</th>
                    <th>Mouvement Net</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rapportArticles as $index => $data)
                    <tr>
                        <td class="center-col">{{ $index + 1 }}</td>
                        <td>{{ $data['article']['libelle'] ?? 'N/A' }}</td>
                        <td>{{ $data['article']['code_article'] ?? 'N/A' }}</td>
                        <td class="numeric-col">{{ rtrim(rtrim(number_format($data['stock_actuel']['quantite'] ?? 0, 2, ',', ' '), '0'), ',') }}</td>
                        <td class="numeric-col">{{ rtrim(rtrim(number_format($data['stock_actuel']['prix_unitaire'] ?? 0, 2, ',', ' '), '0'), ',') }} F CFA</td>
                        <td class="numeric-col">{{ rtrim(rtrim(number_format($data['stock_actuel']['cmp'] ?? 0, 2, ',', ' '), '0'), ',') }} F CFA</td>
                        <td class="numeric-col">{{ rtrim(rtrim(number_format($data['stock_actuel']['montant_total'] ?? 0, 2, ',', ' '), '0'), ',') }} F CFA</td>

                        {{-- Dernière Entrée --}}
                        <td class="center-col">
                            @if(isset($data['derniere_entree']) && !empty($data['derniere_entree']['date']))
                                {{-- Correction ici: Utilisation de Carbon::createFromFormat --}}
                                {{ Carbon\Carbon::createFromFormat('d/m/Y H:i', $data['derniere_entree']['date'])->format('d/m/Y') }} <br>(Qté: {{ number_format($data['derniere_entree']['quantite'] ?? 0, 0, ',', ' ') }})
                            @else
                                N/A
                            @endif
                        </td>

                        {{-- Dernière Sortie --}}
                        <td class="center-col">
                            @if(isset($data['derniere_sortie']) && !empty($data['derniere_sortie']['date']))
                                {{-- Correction ici: Utilisation de Carbon::createFromFormat --}}
                                {{ Carbon\Carbon::createFromFormat('d/m/Y H:i', $data['derniere_sortie']['date'])->format('d/m/Y') }} <br>(Qté: {{ number_format($data['derniere_sortie']['quantite'] ?? 0, 0, ',', ' ') }})
                            @else
                                N/A
                            @endif
                        </td>

                        <td class="numeric-col">{{ rtrim(rtrim(number_format($data['synthese_periode']['total_entrees'] ?? 0, 2, ',', ' '), '0'), ',') }}</td>
                        <td class="numeric-col">{{ rtrim(rtrim(number_format($data['synthese_periode']['total_sorties'] ?? 0, 2, ',', ' '), '0'), ',') }}</td>
                        <td class="numeric-col">{{ rtrim(rtrim(number_format($data['synthese_periode']['mouvement_net'] ?? 0, 2, ',', ' '), '0'), ',') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center;">Aucun article ne correspond aux critères de filtre pour la période sélectionnée.</p>
    @endif

    <div class="footer">
        <p>Page <span class="page-number"></span> sur <span class="total-pages"></span></p>
    </div>
</body>
</html>
