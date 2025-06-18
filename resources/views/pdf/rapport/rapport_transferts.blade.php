<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport des Transferts d'Immobilisations</title>
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
    <h1>Rapport des Transferts d'Immobilisations</h1>
    <h2>LNB-Stock & Parc</h2>

    <!-- Bloc d'informations sur les filtres appliqués -->
    <div class="filters-info">
        <h3>Filtres appliqués :</h3>
        <ul>
            <li>
                <strong>Période de Mouvement :</strong>
                @if(request()->filled('date_debut') && request()->filled('date_fin'))
                    Du {{ \Carbon\Carbon::parse(request('date_debut'))->format('d/m/Y') }} au {{ \Carbon\Carbon::parse(request('date_fin'))->format('d/m/Y') }}
                @else
                    Toutes les dates de mouvement
                @endif
            </li>
            <li>
                <strong>Ancien Bureau :</strong>
                @if(request()->filled('old_bureau_id'))
                    {{ \App\Models\Bureau::find(request('old_bureau_id'))->libelle_bureau ?? 'N/A' }}
                @else
                    Tous
                @endif
            </li>
            <li>
                <strong>Nouveau Bureau :</strong>
                @if(request()->filled('bureau_id'))
                    {{ \App\Models\Bureau::find(request('bureau_id'))->libelle_bureau ?? 'N/A' }}
                @else
                    Tous
                @endif
            </li>
            <li>
                <strong>Ancien Responsable :</strong>
                @if(request()->filled('old_employe_id'))
                    @php
                        $emp = \App\Models\Employe::find(request('old_employe_id'));
                    @endphp
                    {{ $emp->nom ?? '' }} {{ $emp->prenom ?? 'N/A' }}
                @else
                    Tous
                @endif
            </li>
            <li>
                <strong>Nouveau Responsable :</strong>
                @if(request()->filled('employe_id'))
                    @php
                        $emp = \App\Models\Employe::find(request('employe_id'));
                    @endphp
                    {{ $emp->nom ?? '' }} {{ $emp->prenom ?? 'N/A' }}
                @else
                    Tous
                @endif
            </li>
        </ul>
    </div>

    @if($transferts->isEmpty())
        <p class="no-data">Aucun transfert d'immobilisation trouvé pour les critères de recherche spécifiés.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Code Immo</th>
                    <th>Désignation Immo</th>
                    <th>Ancien Bureau</th>
                    <th>Nouveau Bureau</th>
                    <th>Ancien Responsable</th>
                    <th>Nouveau Responsable</th>
                    <th>Date Mouvement</th>
                    <th>Motif</th>
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
                    <td>{{ $transfert->old_employe ? ($transfert->old_employe->nom . ' ' . $transfert->old_employe->prenom) : 'N/A' }}</td>
                    <td>{{ $transfert->employe ? ($transfert->employe->nom . ' ' . $transfert->employe->prenom) : 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($transfert->date_mouvement)->format('d/m/Y') }}</td>
                    <td>{{ $transfert->observation ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer-info">
        Rapport des Transferts d'Immobilisations - Système de Gestion de Stock et Immobilisations
    </div>
</body>
</html>
