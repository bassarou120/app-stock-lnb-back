<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de Parc {{ $reportTypeLabel }}</title>
    <style>
        @page {
            size: landscape; /* Orientation paysage pour plus d'espace */
            margin: 20mm;
        }
        body {
            font-family: Arial, sans-serif; /* Utilisation de la police Arial comme dans le bon d'entrée */
            font-size: 10pt; /* Taille de police comme dans le bon d'entrée */
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        /* Styles des sections comme dans le bon d'entrée */
        .header-section, .budget-section, .table-section, .footer-section {
            margin-bottom: 20px;
        }
        .header-section table, .budget-section table, .footer-section table {
            border: none;
        }
        .header-section td, .budget-section td, .footer-section td {
            padding: 2px 0;
            vertical-align: top;
        }
        
        /* Styles de tableau génériques pour les données dynamiques */
        .dynamic-table th, .dynamic-table td {
            border: 1px solid black; /* Bordures noires comme dans le bon d'entrée */
            padding: 5px;
            text-align: center; /* Centré par défaut pour les cellules de données */
            vertical-align: middle;
        }
        .dynamic-table th {
            background-color: #f2f2f2; /* Garde la couleur verte pour les en-têtes */
            color: black;
            font-weight: bolder;
            font-size: 13px; /* Légèrement plus grand pour les en-têtes */
        }
        .dynamic-table tr:nth-child(even) {
            background-color: #fff; /* Fond clair pour les lignes paires */
        }
        .dynamic-table tr:nth-child(odd) {
            background-color: #fff; /* Fond blanc pour les lignes impaires */
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
        /* Styles spécifiques pour l'en-tête et le pied de page du document comme sur la photo */
        .document-header, .document-footer {
            font-size: 10px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .document-header div {
            margin-bottom: 5px;
        }
        .document-header .right-align {
            text-align: right;
        }
        .document-header .underline {
            text-decoration: underline;
        }
        /* Styles pour les boîtes de certification, augmentation, recepisse */
        .certification-box, .augmentation-box, .recepisse-box {
            border: 1px solid black;
            padding: 10px;
            height: 100px; /* Ajuster la hauteur au besoin */
            text-align: left;
            vertical-align: top;
            font-size: 9pt; /* Taille de police pour ces boîtes */
        }
        .footer-section table td {
            width: 33.33%;
        }
    </style>
</head>
<body>

    <!-- EN-TÊTE DU DOCUMENT (comme sur la photo du bon d'entrée) -->
    <div class="header-section">
        <table>
            <tr>
                <td style="width: 50%;">
                    République du Bénin<br><br>
                    Ministère/Institution/Collectivité locale .........................<br><br>
                    Direction/service ..........................................................
                </td>
                <td style="width: 50%; text-align: right;">
                    Gestion :....................................<br><br>
                    Rapport généré le : {{ $filterLabels['date_debut'] }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center; padding-top: 20px;">
                    <h2>Rapport {{ $reportTypeLabel }}</h2>
                    <h4>(Période du {{ $filterLabels['date_debut'] ?? 'Toutes les dates' }} au {{ $filterLabels['date_fin'] ?? 'Toutes les dates' }})</h5>
                </td>
            </tr>
        </table>
    </div>
    <!-- Bloc d'informations sur les filtres appliqués -->
    <div class="filters-info">
        <h3>Critères d'exportation    :</h3>
        
            
                <strong>Période du :</strong>
                {{ $filterLabels['date_debut'] ?? 'Toutes les dates' }} <br>
            
            
                <strong>Au :</strong>
                {{ $filterLabels['date_fin'] ?? 'Toutes les dates' }}<br>
            
            @if($typeRapport === 'vehicule')
                
                    <strong>Marque :</strong>
                    {{ $filterLabels['marque'] ?? 'Tous' }}<br>
                
                
                    <strong>Modele :</strong>
                    {{ $filterLabels['modele'] ?? 'Tous' }}<br>
                
            @elseif($typeRapport === 'intervention_vehicule')
                
                    <strong>Véhicule :</strong>
                    {{ $filterLabels['vehicule'] ?? 'Tous' }}<br>
                
                
                    <strong>Type Intervention :</strong>
                    {{ $filterLabels['modele'] ?? 'Tous' }}
                
            @endif
        
    </div><br>

    @if($data->isEmpty())
        <p class="no-data">Aucune donnée trouvée pour les critères de recherche spécifiés.</p>
    @else
        <div class="table-section">
            <table class="dynamic-table">
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
        </div>
    @endif
    <!-- PIED DE PAGE DU DOCUMENT (comme sur la photo du bon d'entrée) -->
    <div class="footer-section">
        <table>
            <tr>
                <td style="vertical-align: top;">
                    <div class="certification-box">
                        <b>Ordonnateur des maitières</b><br><br>
                        Nom : .......................<br><br>
                        Date : ......................<br><br>
                        Signature<br><br>
                    </div>
                </td>
                <td style="vertical-align: top;">
                    <div class="certification-box">
                        <b>Le comptable des maitières</b><br><br>
                        Nom : .......................<br><br>
                        Date : ......................<br><br>
                        Signature<br><br>
                    </div>
                </td>
                <td style="vertical-align: top;">
                    <div class="certification-box">
                        <b>Le Chef parc ou autres</b><br><br>
                        Nom : .......................<br><br>
                        Date : ......................<br><br>
                        Signature<br><br>
                    </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
