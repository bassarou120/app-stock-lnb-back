<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport de Parc {{ $reportTypeLabel }}</title>
    <style>
        @page {
            size: landscape; /* Orientation paysage pour plus d'espace */
            margin: 20mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
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
            min-width: 140px;
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
            padding: 10px 8px;
            text-align: left;
            font-weight: normal;
            font-size: 13px;
            border: 1px solid #00772E;
        }
        td {
            padding: 8px;
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
    <h1>Rapport {{ $reportTypeLabel }}</h1>
    <h2>LNB-Stock & Parc</h2>

    <!-- Bloc d'informations sur les filtres appliqués -->
    <div class="filters-info">
        <h3>Filtres appliqués :</h3>
        <ul>
            @if(isset($filterLabels['date_debut']))
                <li>
                    <strong>Période du :</strong>
                    {{ $filterLabels['date_debut'] }}
                </li>
            @endif
            @if(isset($filterLabels['date_fin']))
                <li>
                    <strong>Au :</strong>
                    {{ $filterLabels['date_fin'] }}
                </li>
            @endif

            @if($typeRapport === 'vehicule')
                <li>
                    <strong>Marque :</strong>
                    {{ $filterLabels['marque'] ?? 'Toutes' }}
                </li>
                <li>
                    <strong>Modèle :</strong>
                    {{ $filterLabels['modele'] ?? 'Tous' }}
                </li>
            @elseif($typeRapport === 'intervention_vehicule')
                <li>
                    <strong>Véhicule :</strong>
                    {{ $filterLabels['vehicule'] ?? 'Tous' }}
                </li>
                <li>
                    <strong>Type d'Intervention :</strong>
                    {{ $filterLabels['type_intervention'] ?? 'Tous' }}
                </li>
            @endif
        </ul>
    </div>

    @if($data->isEmpty())
        <p class="no-data">Aucune donnée trouvée pour les critères de recherche spécifiés.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>N°</th>
                    @if($typeRapport === 'vehicule')
                        <th>Marque</th>
                        <th>Modèle</th>
                        <th>Immatriculation</th>
                        <th>N° Châssis</th>
                        <th>Date de mise en service</th>
                        <th>Kilometrage (km)</th>
                    @elseif($typeRapport === 'intervention_vehicule')
                        <th>Date Intervention</th>
                        <th>Modele</th>
                        <th>Immatriculation</th>
                        <th>Type Intervention</th>
                        <th>Titre</th>
                        <th>Coût</th>
                        <th>Observation</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($data as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @if($typeRapport === 'vehicule')
                        <td>{{ $item->marque->libelle ?? 'N/A' }}</td>
                        <td>{{ $item->modele->libelle_modele ?? 'N/A' }}</td>
                        <td>{{ $item->immatriculation ?? 'N/A' }}</td>
                        <td>{{ $item->numero_chassis ?? 'N/A' }}</td>
                        <td>{{ $item->date_mise_en_service ? \Carbon\Carbon::parse($item->date_mise_en_service)->format('d/m/Y') : 'N/A' }}</td>
                        <td>{{ $item->kilometrage ?? 'N/A' }}</td>
                    @elseif($typeRapport === 'intervention_vehicule')
                        <td>{{ $item->date_intervention ? \Carbon\Carbon::parse($item->date_intervention)->format('d/m/Y') : 'N/A' }}</td>
                        <td>{{ $item->vehicule->marque->libelle ?? 'N/A' }} - {{ $item->vehicule->modele->libelle_modele ?? 'N/A' }}</td>
                        <td>{{ $item->vehicule->immatriculation ?? 'N/A' }}</td>
                        <td>{{ $item->typeIntervention->libelle_type_intervention ?? 'N/A' }}</td>
                        <td>{{ $item->titre ?? 'N/A' }}</td>
                        <td>{{ number_format($item->montant, 2, ',', ' ') ?? 'N/A' }}</td>
                        <td>{{ $item->observation ?? 'N/A' }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer-info">
        Rapport de Parc - Système de Gestion de Stock et Immobilisations
    </div>
</body>
</html>
