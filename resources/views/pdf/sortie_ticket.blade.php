<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon de Sortie de Tickets</title>
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

        .header-section, .budget-section, .table-section, .footer-section {
            margin-bottom: 10px;
        }

        .header-section h2 {
            margin: 5px 0;
            font-size: 15pt;
        }

        .header-section td, .budget-section td {
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
            width: 33.33%;
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
                    Bon de Sortie N° {{ $reference }}<br/>
                    <small>Bon généré le: {{ date('d/m/Y H:i:s') }}</small>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center;">
                    <img src="images/logo1.png" alt="Logo LNB" style="height: 45px; margin-bottom: 5px;"><br>
                    <h2>BON DE SORTIE DE TICKETS</h2>
                </td>
            </tr>
        </table>
    </div>

    <div class="budget-section">
        <table>
            <tr>
                <td style="width: 50%;"><strong>Véhicule:</strong> {{ $vehicule?->immatriculation ?? '-' }}</td>
                <td style="width: 50%;"><strong>Kilométrage Début:</strong> {{ $kilometrage ?? '-' }} km</td>
            </tr>
            <tr>
                <td><strong>Employé:</strong> {{ $employe?->fullnameEmploye ?? '-' }}</td>
                <td><strong>Kilométrage Fin:</strong> {{ $kilometrage_de_fin ?? 'Non renseigné' }} km</td>
            </tr>
            <tr>
                <td><strong>Objet:</strong> {{ $objet ?? 'Non spécifié' }}</td>
                <td><strong>Date:</strong> {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td><strong>Trajet Départ:</strong> {{ $communeDepart?->libelle_commune ?? '-' }}</td>
                <td><strong>Trajet Arrivée:</strong> {{ $communeArriver?->libelle_commune ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Trajet A/R:</strong> {{ $trajet_aller_retour ? 'Oui' : 'Non' }}</td>
            </tr>
            {{-- Ajoutez cette nouvelle ligne --}}
            <tr>
                <td><strong>Catégorie de Sortie:</strong> {{ $categorieSortieTicket?->libelle ?? '-' }}</td>
            </tr>
            {{-- Fin de l'ajout --}}
        </table>
    </div>

    <div class="table-section">
        @if($mouvements->isEmpty())
        <div style="text-align: center; font-size: 10pt; margin-top: 20px;">
            Aucun mouvement de ticket trouvé.
        </div>
        @else
        <table>
            <thead>
                <tr>
                    <th>Compagnie Pétrolière</th>
                    <th>Coupon / Ticket</th>
                    <th>Quantité (Qté)</th>
                    <th>Valeur unitaire</th>
                    <th>Valeur Totale</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mouvements as $mouvement)
                <tr>
                    <td>{{ $mouvement->compagniePetrolier?->libelle ?? '-' }}</td>
                    <td>{{ $mouvement->coupon_ticket?->libelle ?? '-' }}</td>
                    <td>{{ $mouvement->qte }}</td>
                    <td>{{ number_format($mouvement->coupon_ticket?->valeur ?? 0, 0, ',', '.') }} F</td>
                    <td>{{ number_format($mouvement->qte * ($mouvement->coupon_ticket?->valeur ?? 0), 0, ',', '.') }} F</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

</div>

<div class="footer-section">
    <table>
        <tr>
            <td>
                <div class="signatures-box">
                    <strong style="text-align: center;">Signature</strong>
                    <p>
                        Je soussigné, ........................................, atteste ce jour avoir initié cette demande.
                    </p>
                    <p style="text-align: right;"><b>Le Demandeur</b></p>
                </div>
            </td>
            <td>
                <div class="signatures-box">
                    <strong style="text-align: center;"></strong>
                    <p>
                        </p>
                    <p style="text-align: right;"><b></b></p>
                </div>
            </td>
            <td>
                <div class="signatures-box">
                    <strong style="text-align: center;">Signature</strong>
                    <p>
                        .............................................
                    </p>
                    <p style="text-align: right;"><b>L'Ordonnateur</b></p>
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