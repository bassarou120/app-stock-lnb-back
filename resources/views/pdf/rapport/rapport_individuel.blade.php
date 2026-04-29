<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>RAPPORT {{ $reportTypeLabel }}</title>
  <style>
    @page {
      size: A4 landscape;
      margin: 5mm;
      margin-bottom: 15mm; /* Espace pour le pied de page */
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
    .recepisse-box {

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

    .certif-out-box,
        .diminution-prise-charge-box,
        .magasinier-fiche-box,
        .recepisse-out-box {

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

    /* Ajuster le contenu principal pour éviter le chevauchement */
    .main-content {
      margin-bottom: 15mm;
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
                Modèle n°1<br/>
                rapport N° {{ date('YmdHis') }}<br/>
                <small>Rapport généré le: {{ date('d/m/Y H:i:s') }}</small>
            </td>

      </tr>
      <tr>

        <td colspan="2" style="text-align: center;">
        <img src="images/logo1.png" alt="Logo LNB" style="height: 45px; margin-bottom: 5px;"><br>
          <h2>RAPPORT INDIVIDUEL</h2>
          (Période du <strong>{{ $filterLabels['date_debut'] ?? 'Toutes les dates' }}</strong> au <strong>{{ $filterLabels['date_fin'] ?? 'Toutes les dates' }}</strong>)
        </td>
      </tr>
    </table>
  </div>

  <div class="budget-section">
    <table>
      <tr>
        <td style="width: 50%;">
          <strong>CRITERES D'EXPORTATION</strong><br/><br/>
          <strong style="font-size:11px;">Article :</strong> {{ $filterLabels['article'] ?? 'Tous' }}<br/>
          <strong style="font-size:11px;">Catégorie :</strong> {{ optional($article->categorie)->libelle_categorie_article ?? optional($article->categorie)->libelle ?? ($article->categorie ?? 'N/A') }}<br/>
          <strong style="font-size:11px;">Fournisseur :</strong> {{ $filterLabels['fournisseur'] ?? 'Tous' }}<br/>
        </td>
        <td style="width: 50%;">
          -
        </td>
      </tr>
    </table>
  </div>

  <div class="table-section">
    @if($rapportData->isEmpty())
      <div style="text-align: center; font-size: 10pt; margin-top: 20px;">
        Aucun mouvement de stock trouvé pour les critères de recherche spécifiés.
      </div>
    @else
      <table>
        <thead>
          <tr>
                <th>Numéro</th>
                <th>Date</th>
                <th>Stock Init.</th>
                <th>Entrées</th>
                <th>Sorties</th>
                <th>Stock final</th>
                <th>PU</th>
                <th>Observations</th>

          </tr>
        </thead>
        <tbody>
            @foreach ($rapportData as $index => $mouvement)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="col-date">{{ \Carbon\Carbon::parse($mouvement['date_mouvement'])->format('d/m/Y') }}</td>

                <td class="empty-cell">
                    {{ $mouvement['stock_initial_ligne'] ?? '0' }}
                </td>
                <td class="number">
                    {{ number_format($mouvement['entrees'], 0, ',', ' ') }}
                </td>
                <td class="number">
                    {{ number_format($mouvement['sorties'], 0, ',', ' ') }}
                </td>
                <td class="number">
                    {{ number_format($mouvement['stock_final'], 0, ',', ' ') }}
                </td>
                <td class="currency">
                    {{ number_format($mouvement['pu'] ?? 0, 0, ',', ' ') }}
                </td>
                <td class="observations-text">
                    {{ $mouvement['observations'] }}
                </td>
            </tr>
            @endforeach
        </tbody>
      </table>
    @endif
  </div>


    <div class="footer-section">
        <p>Edité le 19/08/2025</p>
    </div>


</div>

<!-- Pied de page logiciel -->
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
         <!-- Utilisateur: {{ auth()->user()->name ?? 'Système' }}<br> -->
        Page générée par LNB- Gestion De Stock & Parc
    </div>
</div>

</body>
</html>
