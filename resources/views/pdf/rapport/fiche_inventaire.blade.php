<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche d'Inventaire des Immobilisations</title>
    <!-- Le lien vers style.css n'est pas utilisé par DomPDF pour les styles en ligne -->
    <!-- <link rel="stylesheet" href="style.css"> -->

    <style>
        /* Importer une police si nécessaire pour les accents et caractères spéciaux,
           souvent DejaVu Sans est une bonne option pour DomPDF */
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ public_path("fonts/DejaVuSans.ttf") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ public_path("fonts/DejaVuSans-Bold.ttf") }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif; /* Utiliser DejaVu Sans pour une meilleure gestion des accents */
            font-size: 10px; /* Taille de police plus petite pour plus de colonnes en paysage */
            margin: 10mm; /* Marges réduites pour maximiser l'espace */
        }

        @page {
            size: landscape; /* Orientation paysage */
            margin: 8mm; /* Marges d'impression réduites */
        }

        /* Styles spécifiques pour l'impression (DomPDF gère ceci automatiquement en grande partie) */
        @media print {
            body {
                width: 100%;
            }
            table {
                font-size: 9px; /* Encore plus petit pour l'impression si nécessaire */
            }
            .signatures {
                flex-direction: row; /* Non applicable directement, mais c'est pour la flexbox */
                justify-content: space-between;
            }
        }

        .header {
            margin-bottom: 20px;
        }

        .header p {
            margin: 2px 0;
        }

        .right {
            text-align: right;
        }

        .title {
            text-align: center;
            margin-top: 15px;
            margin-bottom: 10px;
        }

        h1 {
            font-size: 20px; /* Réduit la taille pour le titre principal */
            text-align: center;
            text-decoration: underline;
        }

        h2 {
            font-size: 15px; /* Réduit la taille pour le sous-titre */
            text-align: center;
            margin-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed; /* Pour que les largeurs de colonnes fonctionnent */
        }

        table, th, td {
            border: 1px solid black;
        }

        th, td {
            padding: 4px; /* Rédduit le padding */
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word; /* Permet aux mots longs de se casser */
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .note {
            font-size: 10px; /* Taille de police pour la note */
            margin-top: 10px;
            text-align: right;
        }

        .signatures {
            margin-top: 30px;
            display: flex; /* Ceci est pour l'affichage HTML, DomPDF ne gère pas flexbox parfaitement pour les positions */
            justify-content: space-between;
            width: 100%; /* S'assurer que les signatures s'étendent */
        }

        .signature-item {
            width: 30%; /* Répartir l'espace pour les 3 signatures */
            text-align: center;
            float: left; /* Pour DomPDF, utiliser float pour le positionnement horizontal */
            margin-right: 2%; /* Espace entre les blocs de signature */
        }
        .signature-item:last-child {
            margin-right: 0;
        }

        .no-data {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #cc0000;
        }

    </style>

</head>
<body>
    @php use Carbon\Carbon; @endphp

<!--     <div class="header">
        <p><strong>République du Bénin</strong></p>
        <p>LNB-Lotterie National du Bénin SA</p>
        <p class="right">Rapport généré le: {{ Carbon::now()->format('d/m/Y H:i:s') }}</p>
        <p class="right">Période d'Acquisition: Du {{ Carbon::parse(request()->date_debut_acquisition)->format('d/m/Y') }} au {{ Carbon::parse(request()->date_fin_acquisition)->format('d/m/Y') }}</p>
    </div>

    // -->
        <table width="100%" style="border-collapse: collapse; height: 80px; border: none;">
        <tr>
            <td style="width: 70%; text-align: left; vertical-align: middle; border: none;">
                <p style="margin: 2px 0;"><strong>République du Bénin</strong></p>
                <p style="margin: 2px 0;">LNB - Lotterie Nationale du Bénin SA</p>
                </td>
                <td style="width: 30%; text-align: right; vertical-align: top; border: none;">
                <img src="images/logo1.png" alt="Logo LNB" style="height: 45px; margin-bottom: 5px;"><br>
                <p style="margin: 2px 0;">Rapport généré le: {{ Carbon::now()->format('d/m/Y H:i:s') }}</p>
                <p class="right">Période d'Acquisition: Du {{ Carbon::parse(request()->date_debut_acquisition)->format('d/m/Y') }} au {{ Carbon::parse(request()->date_fin_acquisition)->format('d/m/Y') }}</p>
            </td>
        </tr>
    </table>





    <h1 class="title">FICHE D'INVENTAIRE DES IMMOBILISATIONS</h1>
    <h2 style="font-style: italic;">(LNB-Stock & Parc)</h2>

    @if($immobilisations->isEmpty())
        <p class="no-data">Aucune immobilisation trouvée pour les critères de recherche spécifiés.</p>
    @else

    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Code</th>
                <th>Désignation</th>
                <th>Date Acq.</th>
                <th>Fournisseur</th>
                <th>Groupe Type</th>
                <th>Sous Type</th>
                <th>Statut</th>
                <th>Localisation</th>
                <th>Affecté à</th>
                <th>Observation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($immobilisations as $index => $immo)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $immo->code ?? 'N/A' }}</td>
                <td>{{ $immo->designation ?? 'N/A' }}</td>
                <td>{{ $immo->date_acquisition ? Carbon::parse($immo->date_acquisition)->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $immo->fournisseur->nom ?? 'N/A' }}</td>
                <td>{{ $immo->groupeTypeImmo->libelle ?? 'N/A' }}</td>
                <td>{{ $immo->sousTypeImmo->libelle ?? 'N/A' }}</td>
                <td>{{ $immo->statusImmo->libelle_status_immo ?? 'N/A' }}</td>
                <td>{{ $immo->bureau->libelle_bureau ?? 'N/A' }}</td>
                <td>{{ $immo->employe ? ($immo->employe->nom . ' ' . $immo->employe->prenom) : 'N/A' }}</td>
                <td>{{ $immo->observation ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="note">*État établi en fin de gestion et hors du but arrêté des écritures.</p>

    <div class="signatures">
        <div class="signature-item">Le Comptable des Matières</div>
        <div class="signature-item">Le Magasinier / Fichiste</div>
        <div class="signature-item">Nom et Prénoms des membres de la commission d’inventaire</div>
    </div>

    @endif

</body>
</html>
