<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Unifié des Immobilisations</title>

    <style>
        /* Importation de police pour accents (essentiel pour DomPDF) */
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
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px; /* Très petit pour tout faire tenir en paysage */
            margin: 8mm;
        }

        @page {
            size: landscape; /* Orientation paysage */
            margin: 6mm; /* Marges réduites */
        }

        h1 {
            font-size: 16px;
            text-align: center;
            text-decoration: underline;
            margin: 5px 0 2px 0;
        }
        h2 {
            font-size: 12px;
            text-align: center;
            font-style: italic;
            margin: 0 0 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            table-layout: fixed;
        }

        table, th, td {
            border: 1px solid black;
        }

        th, td {
            padding: 3px; /* Padding très réduit */
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        /* Styles Entête / Pied de page */

        .header-table {
            border: none;
            margin-bottom: 5px;
            table-layout: auto;
            font-size: 8px; /* Entête encore plus petit */
        }
        .header-table td {
            border: none;
            padding: 1px;
            text-align: left;
        }
        .header-table .right {
            text-align: right;
        }

        .software-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 10mm;
            border-top: 1px solid #ccc;
            background-color: #f9f9f9;
            padding: 1mm 5mm;
            font-size: 7pt;
            color: #666;
            display: table;
            width: 100%;
        }
        .software-info, .print-info {
            display: table-cell;
            vertical-align: middle;
        }
        .software-info { width: 70%; text-align: left; }
        .print-info { width: 30%; text-align: right; line-height: 1.2; }
        .software-logo { font-weight: bold; color: #333; }

        .signatures {
            margin-top: 15px;
            page-break-inside: avoid; /* Évite de couper le bloc de signature */
        }

        .signature-item {
            width: 32%;
            text-align: center;
            float: left;
            margin-right: 1%;
            font-size: 10px;
        }
        .signature-item:last-child {
            margin-right: 0;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
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

    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <p><strong>République du Bénin</strong></p>
                <p>LNB - Lotterie Nationale du Bénin SA</p>
            </td>
            <td style="width: 30%; text-align: right;">
                <p>Rapport généré le: {{ Carbon::now()->format('d/m/Y H:i:s') }}</p>
                @if (isset(request()->date_debut_acquisition) && isset(request()->date_fin_acquisition))
                    <p>Période d'Acquisition: Du {{ Carbon::parse(request()->date_debut_acquisition)->format('d/m/Y') }} au {{ Carbon::parse(request()->date_fin_acquisition)->format('d/m/Y') }}</p>
                @endif
                @if (isset(request()->date_debut) && isset(request()->date_fin))
                    <p>Période d'Enregistrement: Du {{ Carbon::parse(request()->date_debut)->format('d/m/Y') }} au {{ Carbon::parse(request()->date_fin)->format('d/m/Y') }}</p>
                @endif
            </td>
        </tr>
    </table>

    <div style="text-align: center;">
        <img src="images/logo1.png" alt="Logo LNB" style="height: 35px; margin-bottom: 2px;">

        <h1>
            RAPPORT DES IMMOBILISATIONS
        </h1>
        <h2>
            ({{ $typeRapport === 'inventaire' ? 'FICHE D\'INVENTAIRE' : 'ENREGISTREMENT ET SUIVI' }})
        </h2>
    </div>

    @if($immobilisations->isEmpty())
        <p class="no-data">Aucune immobilisation trouvée pour les critères de recherche spécifiés.</p>
    @else

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">N°</th>
                <th style="width: 8%;">Code</th>
                <th style="width: 15%;">Désignation</th>
                <th style="width: 7%;">Date Acq.</th>
                <th style="width: 7%;">Montant TTC</th>
                <th style="width: 5%;">État</th>
                <th style="width: 8%;">Groupe Type</th>
                <th style="width: 8%;">Sous Type</th>
                <th style="width: 7%;">Statut</th>
                <th style="width: 10%;">Localisation</th>
                <th style="width: 10%;">Affecté à</th>
                <th style="width: 8%;">Fournisseur</th>
                <th style="width: 12%;">Observation</th>
            </tr>
        </thead>
            <tbody>
                @foreach($immobilisations as $index => $immo)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $immo->code ?? '-' }}</td>
                    <td>{{ $immo->designation ?? '-' }}</td>
                    <td>{{ $immo->date_acquisition ? Carbon::parse($immo->date_acquisition)->format('d/m/Y') : '-' }}</td>
                    <td style="text-align: right;">{{ isset($immo->montant_ttc) ? number_format($immo->montant_ttc, 2, ',', ' ') : '-' }}</td>
                    <td>{{ $immo->etat ?? '-' }}</td>

                    <td>{{ optional($immo->groupeTypeImmo)->libelle ?? '-' }}</td>
                    <td>{{ optional($immo->sousTypeImmo)->libelle ?? '-' }}</td>
                    <td>{{ optional($immo->statusImmo)->libelle_status_immo ?? $immo->statut_immo ?? '-' }}</td>
                    <td>{{ optional($immo->bureau)->libelle_bureau ?? '-' }}</td>

                    <td>{{ optional($immo->employe)->nom_complet ?? '-' }}</td>
                    <td>{{ optional($immo->fournisseur)->nom ?? '-' }}</td>
                    <td>{{ $immo->observation ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
    </table>

    <p class="note" style="margin-top: 5px; text-align: left;">
        *État établi en fin de gestion et hors du but arrêté des écritures.
    </p>

    <div class="signatures clearfix">
        <div class="signature-item">Le Comptable des Matières</div>
        <div class="signature-item">Le Magasinier / Fichiste</div>
        <div class="signature-item">Nom et Prénoms des membres de la commission d’inventaire</div>
    </div>

    @endif

<div class="software-footer">
    <div class="software-info">
        <div class="software-logo">LNB- Gestion De Stock & Parc</div>
        <div class="software-details">
            Système de Gestion de Stock - Version 1.0 |
            Développé pour LNB-Lotterie National du Bénin SA
        </div>
    </div>
    <div class="print-info">
        Document généré le {{ date('d/m/Y à H:i:s') }}<br>
        Page générée par LNB- Gestion De Stock & Parc
    </div>
</div>
</body>
</html>
