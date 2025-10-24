<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>RAPPORT DES IMMOBILISATIONS PAR BUREAU</title>
  <style>
    @page {
      size: A4 landscape;
      margin: 10mm;
      margin-bottom: 20mm; /* Espace pour le pied de page */
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 10pt; /* Taille de police légèrement réduite pour plus de colonnes */
      margin: 0;
      padding: 0;
      position: relative;
      min-height: 100vh;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    .header-section {
      margin-bottom: 10px;
    }

    .header-section h2 {
      margin: 5px 0;
      font-size: 16pt;
    }

    .header-section td {
      padding: 2px 4px;
      vertical-align: top;
      font-size: 10pt;
    }

    .table-section th,
    .table-section td {
      border: 1px solid black;
      padding: 3px;
      text-align: left;
      word-break: break-word;
      font-size: 9pt; /* Très important pour la largeur du tableau */
    }

    .table-section th {
      background-color: #f2f2f2;
      text-align: center;
    }

    .table-section {
      page-break-inside: auto;
    }

    .table-section tr {
      page-break-inside: avoid;
      page-break-after: auto;
    }

    .center { text-align: center; }
    .right { text-align: right; }
    .currency { white-space: nowrap; }

    /* Styles pour le pied de page logiciel (identique à votre modèle) */
    .software-footer {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      height: 12mm;
      border-top: 1px solid #ccc;
      background-color: #f9f9f9;
      padding: 2mm 10mm;
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

    .main-content {
      margin-bottom: 20mm;
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
          <h2>RAPPORT DES IMMOBILISATIONS PAR BUREAU</h2>
          (Période d'acquisition du <strong>{{ request('date_debut_bureau') ? \Carbon\Carbon::parse(request('date_debut_bureau'))->format('d/m/Y') : 'Toutes les dates' }}</strong> au <strong>{{ request('date_fin_bureau') ? \Carbon\Carbon::parse(request('date_fin_bureau'))->format('d/m/Y') : 'Toutes les dates' }}</strong>)
        </td>
      </tr>
    </table>
  </div>

  <div class="budget-section">
    <table>
      <tr>
        <td style="width: 50%;">
          <strong>CRITERES D'EXPORTATION</strong><br/>
          <strong style="font-size:10px;">Bureau Sélectionné :</strong>
          @if(request('bureau_id'))
            {{ $immobilisations->first()->bureau->libelle_bureau ?? 'Non trouvé' }}
          @else
            Tous les bureaux
          @endif
        </td>
        <td style="width: 50%; text-align: right;">
          <strong>Nombre d'immobilisations :</strong> {{ $immobilisations->count() }}
        </td>
      </tr>
    </table>
  </div>

  <div class="table-section">
    @if($immobilisations->isEmpty())
      <div style="text-align: center; font-size: 10pt; margin-top: 20px;">
        Aucune immobilisation trouvée pour les critères de recherche spécifiés.
      </div>
    @else
      <table class="main-table">
        <thead>
          <tr>
            <th style="width: 3%;">N°</th>
            <th style="width: 8%;">Code</th>
            <th style="width: 15%;">Désignation</th>
            <th style="width: 10%;">Montant TTC</th>
            <th style="width: 10%;">Bureau</th>
            <th style="width: 8%;">Statut</th>
            <th style="width: 8%;">Personnel</th>
            <th style="width: 10%;">Groupe Type</th>
            <th style="width: 10%;">Sous Type</th>
            <th style="width: 8%;">Date Acquis.</th>
            <th style="width: 10%;">Observation</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($immobilisations as $index => $immo)
          <tr>
            <td class="center">{{ $index + 1 }}</td>
            <td>{{ $immo->code }}</td>
            <td>{{ $immo->designation }}</td>
            <td class="right currency">{{ number_format($immo->montant_ttc, 0, ',', ' ') }} F CFA</td>
            <td>{{ $immo->bureau->libelle_bureau ?? 'Non défini' }}</td>
            <td>{{ $immo->statusImmo->libelle_status_immo ?? 'N/A' }}</td>
            <td>{{ $immo->employe->fullnameEmploye ?? 'Non défini' }}</td>
            <td>{{ $immo->groupeTypeImmo->libelle ?? 'N/A' }}</td>
            <td>{{ $immo->sousTypeImmo->libelle ?? 'N/A' }}</td>
            <td class="center">{{ \Carbon\Carbon::parse($immo->date_acquisition)->format('d/m/Y') }}</td>
            <td>{{ $immo->observation }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>

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
