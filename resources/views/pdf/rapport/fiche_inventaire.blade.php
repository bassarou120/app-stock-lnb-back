<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport des Immobilisations</title>

    <style>
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
            font-size: 10px;
            margin: 10mm;
        }

        @page {
            size: landscape;
            margin: 8mm;
        }

        .header, .title, table {
            width: 100%;
        }

        h1 { font-size: 20px; text-align: center; text-decoration: underline; }
        h2 { font-size: 15px; text-align: center; margin-top: 5px; font-style: italic; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }
        table, th, td { border: 1px solid black; }
        th, td {
            padding: 4px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }
        th { background-color: #f2f2f2; font-weight: bold; }

        .note { font-size: 10px; margin-top: 10px; text-align: right; }

        .signatures {
            margin-top: 30px;
            width: 100%;
            display: flex;
            justify-content: space-between;
        }
        .signature-item { width: 30%; text-align: center; float: left; margin-right: 2%; }
        .signature-item:last-child { margin-right: 0; }

        .no-data { text-align: center; margin-top: 20px; font-size: 14px; color: #cc0000; }

        .software-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 12mm;
            border-top: 1px solid #ccc;
            background-color: #f9f9f9;
            padding: 2mm 5mm;
            font-size: 8pt;
            color: #666;
            display: table;
            width: 100%;
            z-index: 1000;
        }
        .software-info { display: table-cell; vertical-align: middle; text-align: left; width: 70%; }
        .software-logo { font-weight: bold; color: #333; margin-bottom: 2px; }
        .software-details { font-style: italic; line-height: 1.2; }
        .print-info { display: table-cell; vertical-align: middle; text-align: right; width: 30%; font-size: 7pt; line-height: 1.2; }

    </style>
</head>
<body>
    @php use Carbon\Carbon; @endphp

    <table style="border: none; height: 80px;">
        <tr>
            <td style="width: 70%; text-align: left; border: none; vertical-align: middle;">
                <p><strong>République du Bénin</strong></p>
                <p>LNB - Lotterie Nationale du Bénin SA</p>
            </td>
            <td style="width: 30%; text-align: right; border: none; vertical-align: top;">
                <p>Rapport généré le: {{ Carbon::now()->format('d/m/Y H:i:s') }}</p>
                @if(request()->filled('date_debut_acquisition') && request()->filled('date_fin_acquisition'))
                    <p>Période d'Acquisition: Du {{ Carbon::parse(request()->date_debut_acquisition)->format('d/m/Y') }} au {{ Carbon::parse(request()->date_fin_acquisition)->format('d/m/Y') }}</p>
                @endif
            </td>
        </tr>
    </table>

    <div style="text-align: center; margin-bottom: 10px;">
        <img src="images/logo1.png" alt="Logo LNB" style="height: 45px; margin-bottom: 5px;">
        <h1>RAPPORT DES IMMOBILISATIONS</h1>
        <h2>(LNB-Stock & Parc)</h2>
    </div>

    @if($actifs->isEmpty())
        <p class="no-data">Aucune immobilisation trouvée pour les critères de recherche spécifiés.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Code</th>
                    <th>Désignation</th>
                    <th>Valeur</th>
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
            @foreach($actifs as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->code }}</td>
                <td>{{ $item->designation }}</td>
                <td>{{ $item->montant_ttc }}</td>
                <td>{{ $item->date_acquisition ?? '-' }}</td>
                <td>{{ $item->nom_fournisseur }}</td>
                <td>{{ $item->libelle_groupe }}</td>
                <td>{{ $item->libelle_soustype }}</td>
                <td>{{ optional($item->status_immo)['libelle_status_immo'] ?? '-' }}</td>
                <td>{{ $item->libelle_bureau }}</td>
                <td>{{ $item->affecte_a }}</td>
                <td>{{ $item->observation }}</td>
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

    <div class="software-footer">
        <div class="software-info">
            <div class="software-logo">LNB- Gestion De Stock & Parc</div>
            <div class="software-details">
                Système de Gestion de Stock - Version 1.0 | Développé pour LNB-Lotterie National du Bénin SA
            </div>
        </div>
        <div class="print-info">
            Document généré le {{ date('d/m/Y à H:i:s') }}<br>
            Page générée par LNB- Gestion De Stock & Parc
        </div>
    </div>

</body>
</html>
