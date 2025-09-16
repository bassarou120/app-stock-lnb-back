<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Rapport de Tickets {{ $reportTypeLabel }}</title>
  <style>
    @page {
      size: A4 landscape;
      margin: 5mm;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 11pt;
      margin: 0;
      padding: 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    .header-section,
    .budget-section,
    .table-section,
    .footer-section {
      margin-bottom: 10px;
    }

    .header-section h2 {
      margin: 5px 0;
      font-size: 15pt;
    }

    .header-section td,
    .budget-section td {
      padding: 2px 4px;
      vertical-align: top;
      font-size: 11pt;
    }

    .table-section th,
    .table-section td {
      border: 1px solid black;
      padding: 4px;
      text-align: center;
      word-break: break-word;
      font-size: 11pt;
    }

    .table-section th {
      background-color: #f2f2f2;
    }

    .table-section {
      max-height: 43vh;
      overflow: hidden;
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

    .footer-section-sortie table td {
        width: 25%; /* Quatre colonnes, chacune prend 25% */
        padding: 0 3px; /* Petit padding horizontal entre les colonnes */
        vertical-align: top;
     }

    .footer-section-sortie {
      page-break-inside: avoid;
    }

    .footer-section-sortie table {
      table-layout: fixed;
    }

    .certification-box,
    .augmentation-box,
    .recepisse-box,
    .ticket-visa-box {

      padding: 6px 8px;
      font-size: 11pt;
      line-height: 1.4;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 110px;
    }

    .ticket-visa-box { /* Added for ticket reports */
      padding: 6px 8px;
      font-size: 11pt;
      line-height: 1.4;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 110px;
      border: 1px solid black; /* Added border for consistency */
    }

    small {
      font-size: 7pt;
    }

    //

    .certif-out-box,
        .diminution-prise-charge-box,
        .magasinier-fiche-box,
        .recepisse-out-box {
            border: 1px solid black;
            padding: 5px 7px; /* Padding interne des boîtes */
            font-size: 8.5pt; /* Taille de police pour le contenu des boîtes */
            line-height: 1.2; /* Hauteur de ligne pour compacter le texte */
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Distribue l'espace verticalement */
            box-sizing: border-box;
            height: 120px; /* Hauteur fixe pour toutes les boîtes, ajustée pour le contenu */
            /* La largeur sera gérée par le table-layout: fixed et le width: 25% du td */
        }

        .certif-out-box p,
        .diminution-prise-charge-box p,
        .magasinier-fiche-box p,
        .recepisse-out-box p {
            margin: 2px 0; /* Marges réduites pour les paragraphes */
            padding: 0;
        }

        .certif-out-box strong,
        .diminution-prise-charge-box strong,
        .magasinier-fiche-box strong,
        .recepisse-out-box strong {
            display: block;
            text-align: center; /* Centrer les titres des sections */
            margin-bottom: 4px; /* Espace après le titre */
        }

        /* Aligner la dernière ligne du texte des responsables à droite */
        .certif-out-box p:last-of-type,
        .diminution-prise-charge-box p:last-of-type,
        .magasinier-fiche-box p:last-of-type,
        .recepisse-out-box p:last-of-type {
            margin-top: auto; /* Pousse la dernière ligne vers le bas */
            text-align: right;
            line-height: 1; /* Compacter la ligne de signature */
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

    .print-info {
      text-align: right;
      font-size: 7pt;
    }


  </style>
</head>
<body>

  <div class="header-section">
    <table>
      <tr>
        <td style="width: 50%;">
            <!-- <img src="images/logo1.png" alt="Logo LNB" style="height: 45px; margin-bottom: 5px;"><br> -->
          République du Bénin<br/>
          LNB-Lotterie National du Bénin SA
        </td>
        @if($typeRapport === 'entree ticket')
            <td style="width: 50%; text-align: right;">
                Modèle n°1<br/>
                Ticket d'Entré N° ...........<br/>
                <small>Rapport généré le: {{ date('d/m/Y H:i:s') }}</small>
            </td>
        @elseif($typeRapport === 'retour ticket')
            <td style="width: 50%; text-align: right;">
                Modèle n°1<br/>
                Ticket de Retour N° ...........<br/>
                <small>Rapport généré le: {{ date('d/m/Y H:i:s') }}</small>
            </td>
        @elseif($typeRapport === 'annulation ticket')
            <td style="width: 50%; text-align: right;">
                Modèle n°1<br/>
                Ticket d'Annulation N° ...........<br/>
                <small>Rapport généré le: {{ date('d/m/Y H:i:s') }}</small>
            </td>
        @else
            <td style="width: 50%; text-align: right;">
                Modèle n°1<br/>
                Ticket de sortie N° ...........<br/>
                <small>Rapport généré le: {{ date('d/m/Y H:i:s') }}</small>
            </td>
        @endif
      </tr>
      <tr>

        @if($typeRapport === 'entree ticket')
          <td colspan="2" style="text-align: center;">
            <div style="text-align: center;">
              <img src="images/logo1.png" alt="Logo LNB" style="height: 45px; margin-bottom: 5px;"><br>
              <h2 class="main-header">TICKET D'ENTRÉE</h2>
              (Période du <strong>{{ $filterLabels['date_debut'] ?? 'Toutes les dates' }}</strong> au <strong>{{ $filterLabels['date_fin'] ?? 'Toutes les dates' }}</strong>)
            </div>
          </td>
        @elseif($typeRapport === 'retour ticket')
          <td colspan="2" style="text-align: center;">
            <div style="text-align: center;">
              <img src="images/logo1.png" alt="Logo LNB" style="height: 45px; margin-bottom: 5px;"><br>
              <h2 class="main-header">TICKET DE RETOUR</h2>
              (Période du <strong>{{ $filterLabels['date_debut'] ?? 'Toutes les dates' }}</strong> au <strong>{{ $filterLabels['date_fin'] ?? 'Toutes les dates' }}</strong>)
            </div>
          </td>
        @elseif($typeRapport === 'annulation ticket')
          <td colspan="2" style="text-align: center;">
            <div style="text-align: center;">
              <img src="images/logo1.png" alt="Logo LNB" style="height: 45px; margin-bottom: 5px;"><br>
              <h2 class="main-header">TICKET D'ANNULATION</h2>
              (Période du <strong>{{ $filterLabels['date_debut'] ?? 'Toutes les dates' }}</strong> au <strong>{{ $filterLabels['date_fin'] ?? 'Toutes les dates' }}</strong>)
            </div>
          </td>
        @else
          <td colspan="2" style="text-align: center;">
            <div style="text-align: center;">
              <img src="images/logo1.png" alt="Logo LNB" style="height: 45px; margin-bottom: 5px;"><br>
              <h2 class="main-header">TICKET DE SORTIE</h2>
              (Période du <strong>{{ $filterLabels['date_debut'] ?? 'Toutes les dates' }}</strong> au <strong>{{ $filterLabels['date_fin'] ?? 'Toutes les dates' }}</strong>)
            </div>
          </td>
        @endif
      </tr>
    </table>
  </div>

  <div class="budget-section">
    <table>
      <tr>
        <td style="width: 50%;">


          <strong>CRITERES D'EXPORTATION</strong><br/><br/>

            @if($typeRapport === 'entree ticket' || $typeRapport === 'retour ticket' || $typeRapport === 'annulation ticket')
                <strong style="font-size:11px;">Coupon Ticket :</strong>{{ $filterLabels['coupon_ticket'] ?? 'Tous' }}<br/>
                <strong style="font-size:11px;">Compagnie :</strong>{{ $filterLabels['compagnie'] ?? 'Toutes' }}<br/>
            @elseif($typeRapport === 'sortie ticket')
                <strong style="font-size:11px;">Coupon Ticket :</strong>{{ $filterLabels['coupon_ticket'] ?? 'Tous' }}<br/>
                <strong style="font-size:11px;">Compagnie :</strong>{{ $filterLabels['compagnie'] ?? 'Toutes' }}<br/>
                <strong style="font-size:11px;">Employé :</strong>{{ $filterLabels['employe'] ?? 'Tous' }}<br/>
                <strong style="font-size:11px;">Véhicule :</strong>{{ $filterLabels['vehicule'] ?? 'Tous' }}<br/>
            @endif


        </td>
        <td style="width: 50%;">
          BUDGET (1)...................................Chapitre ........................................<br/>
          (2)..........................................................................................................<br/>
          (3)..........................................................................................................<br/>
          (4)..........................................................................................................<br/>
        </td>
      </tr>
    </table>
  </div>

  <div class="table-section">
    @if($data->isEmpty())
      <div style="text-align: center; font-size: 10pt; margin-top: 20px;">
        Aucun mouvement de stock trouvé pour les critères de recherche spécifiés.
      </div>
    @else
      <table>
        <thead>
                <tr>
                    <th>N°</th>
                    @if($typeRapport === 'entree ticket')
                        <th>Date Mouvement</th>
                        <th>Coupon Ticket</th>
                        <th>Compagnie</th>
                        <th>Quantité</th>
                        <th>Objet</th>
                        <th>Description</th>
                    @elseif($typeRapport === 'sortie ticket')
                        <th>Reference</th>
                        <th>Coupon Ticket</th>
                        <th>Compagnie</th>
                        <th>Quantité</th>
                        <th>Employé</th>
                        <th>Véhicule</th>
                        <th>Kilometrage</th>
                        <th>Objet</th>
                        <th>Description</th>
                    @elseif($typeRapport === 'retour ticket')
                        <th>Reference</th>
                        <th>Date Retour</th>
                        <th>Coupon Ticket</th>
                        <th>Compagnie</th>
                        <th>Quantité Retour</th>
                    @elseif($typeRapport === 'annulation ticket')
                        <th>Reference</th>
                        <th>Coupon Ticket</th>
                        <th>Compagnie</th>
                        <th>Quantité Annulée</th>
                        <th>Date Annulation</th>
                    @endif
                </tr>
        </thead>
            <tbody>
                @foreach($data as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @if($typeRapport === 'entree ticket')
                        <td>{{ $item->date }}</td>
                        <td>{{ $item->coupon_ticket->libelle ?? 'N/A' }}</td>
                        <td>{{ $item->compagniePetrolier->libelle ?? 'N/A' }}</td>
                        <td>{{ $item->qte }}</td>
                        <td>{{ $item->objet ?? 'N/A' }}</td>
                        <td>{{ $item->description ?? 'N/A' }}</td>
                    @elseif($typeRapport === 'sortie ticket')
                        <td>{{ $item->reference }}</td>
                        <td>{{ $item->coupon_ticket->libelle ?? 'N/A' }}</td>
                        <td>{{ $item->compagniePetrolier->libelle ?? 'N/A' }}</td>
                        <td>{{ $item->qte }}</td>
                        <td>{{ $item->employe->nom ?? '' }} {{ $item->employe->prenom ?? 'N/A' }}</td>
                        <td>{{ $item->vehicule->immatriculation ?? 'N/A' }}</td>
                        <td>{{ $item->kilometrage ?? 'N/A' }}</td>
                        <td>{{ $item->objet ?? 'N/A' }}</td>
                        <td>{{ $item->description ?? 'N/A' }}</td>
                    @elseif($typeRapport === 'retour ticket')
                        <td>{{ $item->mouvement->reference ?? 'N/A' }}</td>
                        <td>{{ $item->created_at ? \Carbon\Carbon::parse($item->date_retour)->format('d/m/Y') : 'N/A' }}</td>
                        <td>{{ $item->coupon->libelle ?? 'N/A' }}</td>
                        <td>{{ $item->compagnie->libelle ?? 'N/A' }}</td>
                        <td>{{ $item->qte }}</td>
                    @elseif($typeRapport === 'annulation ticket')
                        <td>{{ $item->mouvement->reference ?? 'N/A' }}</td>
                        <td>{{ $item->coupon->libelle ?? 'N/A' }}</td>
                        <td>{{ $item->compagnie->libelle ?? 'N/A' }}</td>
                        <td>{{ $item->qte }}</td>
                        <td>{{ $item->created_at ? \Carbon\Carbon::parse($item->date_retour)->format('d/m/Y') : 'N/A' }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
      </table>
    @endif
  </div>


        <div class="footer-section">
            <table>
                <tr>
                    <td>
                        <div class="ticket-visa-box">
                            <strong style="text-align: center;">VISA DU DEMANDEUR</strong>
                            <p>
                                Je soussigné(e) ...................................., atteste avoir émis le présent ticket.
                            </p>
                            <p>
                                À ........................., le .............................
                            </p>
                            <p style="text-align: right;"><b>Le Demandeur</b></p>
                        </div>
                    </td>
                    <td>
                        <div class="ticket-visa-box">
                            <strong style="text-align: center;">VISA DU CHEF DE SERVICE</strong>
                            <p>
                                Je soussigné(e) ...................................., valide l'opération décrite dans le présent ticket.
                            </p>
                            <p>
                                À ........................., le .............................
                            </p>
                            <p style="text-align: right;"><b>Le Chef de Service</b></p>
                        </div>
                    </td>
                    <td>
                        <div class="ticket-visa-box">
                            <strong style="text-align: center;">VISA DE L'ORDONNATEUR</strong>
                            <p>
                                Je soussigné(e) ...................................., autorise l'exécution de l'opération du présent ticket.
                            </p>
                            <p>
                                À .................................., le .....................
                            </p>
                            <p style="text-align: right;"><b>L'Ordonnateur</b></p>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Pied de page logiciel -->
            <div class="software-footer">
                <div class="software-info">
                    <div class="software-logo">LNB- Gestion De Stock & Parc</div>
                    <div class="software-details">
                        Système de Gestion de Stock - Version 1.0 |
                        Développé pour LNB-Lotterie National du Bénin SA
                    </div>
                </div>
                <div class="print-info" style="margin-top: -15px;">
                    Document généré le {{ date('d/m/Y à H:i:s') }}<br>
                    <!-- Utilisateur: {{ auth()->user()->name ?? 'Système' }}<br> -->
                    Page générée par LNB- Gestion De Stock & Parc
                </div>
            </div>
            <!-- Fin Pied de page logiciel -->
        </div>

</body>
</html>
