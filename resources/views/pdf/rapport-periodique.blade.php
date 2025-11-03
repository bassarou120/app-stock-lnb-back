<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $titre }}</title>
    <style>
        /* Styles de base - Repris de votre modèle */
        @page {
            size: A4 landscape;
            margin: 5mm;
            margin-bottom: 15mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
            position: relative;
            min-height: 100vh;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-section, .table-section, .footer-section {
            margin-bottom: 10px;
        }

        .header-section h2 {
            margin: 5px 0;
            font-size: 15pt;
        }

        .header-section td {
            padding: 2px 4px;
            vertical-align: top;
            font-size: 11pt;
        }

        .table-section th, .table-section td {
            border: 1px solid black;
            padding: 4px;
            text-align: center;
            word-break: break-word;
            font-size: 11pt;
        }

        .table-section th {
            background-color: #f2f2f2;
        }

        .footer-section {
            page-break-inside: avoid;
        }

        .footer-section table {
            table-layout: fixed;
        }

        .footer-section table td {
            width: 50%;
            padding: 0 4px;
        }

        .signatures-box {
            padding: 6px 8px;
            font-size: 11pt;
            line-height: 1.4;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 110px;
        }

        small {
            font-size: 7pt;
        }

        /* Styles pour le pied de page logiciel */
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }

        .software-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .software-logo {
            font-weight: bold;
            color: #333;
        }

        .software-details {
            font-style: italic;
        }

        .print-info {
            text-align: right;
            font-size: 7pt;
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header-section">
        <table>
            <tr>
                <td style="width: 50%;">
                    République du Bénin<br/>
                    LNB-Lotterie National du Bénin SA
                </td>
                <td style="width: 50%; text-align: right;">
                    {{ $titre }}<br/>
                    <small>Rapport généré le: {{ date('d/m/Y H:i:s') }}</small>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center;">
                    <img src="images/logo1.png" alt="Logo LNB" style="height: 45px; margin-bottom: 5px;"><br>
                    <h2>Rapport d'Inventaire des Tickets</h2>
                </td>
            </tr>
        </table>
    </div>

    <div class="table-section">
        @if(!empty($rapport) && is_array($rapport))
            <table>
                <thead>
                    <tr>
                        <th rowspan="2">Période</th>
                        <th rowspan="2">Stock Initial</th>
                        <th colspan="3" style="text-align: center;">Entrées</th>
                        <th colspan="4" style="text-align: center;">Sorties</th>
                        <th rowspan="2">Stock Final</th>
                    </tr>
                    <tr>
                        <th>Acquis</th>
                        <th>Retour</th>
                        <th>Total</th>
                        <th>Dotation Agences</th>
                        <th>Dotation Chef Garage</th>
                        <th>Groupe Electrogène</th>
                        <th>Missions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rapport as $donnees)
                        <tr>
                            <td>{{ $donnees['periode'] }}</td>
                            <td>{{ $donnees['stock_initial'] }}</td>
                            <td>{{ $donnees['entrees'] }}</td>
                            <td>{{ $donnees['retours'] }}</td>
                            <td>{{ $donnees['entrees'] }}</td>
                            <!-- <td>{{ $donnees['entrees'] + $donnees['retours'] }}</td> -->
                            <td>{{ $donnees['sorties_par_categorie']['Dotation Agences'] ?? 0 }}</td>
                            <td>{{ $donnees['sorties_par_categorie']['Dotation Chef Garage'] ?? 0 }}</td>
                            <td>{{ $donnees['sorties_par_categorie']['Groupe Electrogène'] ?? 0 }}</td>
                            <td>{{ $donnees['sorties_par_categorie']['Missions'] ?? 0 }}</td>
                            <td>{{ $donnees['stock_final'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Aucune donnée de rapport disponible.</p>
        @endif
    </div>
</div>

<div class="footer-section">
    <table>
        <tr>
            <td>
                <div class="signatures-box">
                    <strong style="text-align: center;">Signature du Chef de Service</strong>
                    <br><br><br>
                    <p style="text-align: right;"><b>Le Chef de Service</b></p>
                </div>
            </td>
            <td>
                <div class="signatures-box">
                    <strong style="text-align: center;">Signature de l'Équipe d'Inventaire</strong>
                    <br><br><br>
                    <p style="text-align: right;"><b>L'Équipe d'Inventaire</b></p>
                </div>
            </td>
        </tr>
    </table>
</div>

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