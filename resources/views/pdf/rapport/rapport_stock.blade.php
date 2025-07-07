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

  </style>
</head>
<body>

  <div class="header-section">
    <table>
      <tr>
        <td style="width: 50%;">
          <img src="images/logo1.png" alt="Logo LNB" style="height: 45px; margin-bottom: 5px;"><br>
          République du Bénin<br/>
          LNB-Lotterie National du Bénin SA
        </td>
        @if($reportTypeLabel === 'd\'Entrée de Stock')
            <td style="width: 50%; text-align: right;">
                Modèle n°1<br/>
                Ordre d'Entrée N° ...........<br/>
                <small>Rapport généré le: {{ date('d/m/Y H:i:s') }}</small>
            </td>
        @elseif($reportTypeLabel === 'de Sortie de Stock')
            <td style="width: 50%; text-align: right;">
                Modèle n°1<br/>
                Ordre de Sortie N° ...........<br/>
                <small>Rapport généré le: {{ date('d/m/Y H:i:s') }}</small>
            </td>
        @endif
      </tr>
      <tr>
        @if($reportTypeLabel === 'd\'Entrée de Stock')
        <td colspan="2" style="text-align: center;">
          <h2>ORDRE D'ENTRÉE</h2>
          (Période du <strong>{{ $filterLabels['date_debut'] ?? 'Toutes les dates' }}</strong> au <strong>{{ $filterLabels['date_fin'] ?? 'Toutes les dates' }}</strong>)
        </td>
        @elseif($reportTypeLabel === 'de Sortie de Stock')
                <td colspan="2" style="text-align: center;">
          <h2>ORDRE DE SORTIE</h2>
          (Période du <strong>{{ $filterLabels['date_debut'] ?? 'Toutes les dates' }}</strong> au <strong>{{ $filterLabels['date_fin'] ?? 'Toutes les dates' }}</strong>)
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
          <strong style="font-size:11px;">Article :</strong> {{ $filterLabels['article'] ?? 'Tous' }}<br/>
          @if($reportTypeLabel === 'd\'Entrée de Stock')
            <strong style="font-size:11px;">Fournisseur :</strong> {{ $filterLabels['fournisseur'] ?? 'Tous' }}<br/>
          @elseif($reportTypeLabel === 'de Sortie de Stock')
            <strong style="font-size:11px;">Demandeur :</strong> {{ $filterLabels['employe'] ?? 'Tous' }}<br/>
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
    @if($mouvements->isEmpty())
      <div style="text-align: center; font-size: 10pt; margin-top: 20px;">
        Aucun mouvement de stock trouvé pour les critères de recherche spécifiés.
      </div>
    @else
      <table>
        <thead>
          <tr>
            <th>N°</th>
            <th>Date Mouvement</th>
            <th>Article</th>
            <th>Quantité</th>
            <th>Prix Unitaire</th>
            <th>Unité de mesure</th>
            <th>CMP</th>
            @if($reportTypeLabel === 'd\'Entrée de Stock')
              <th>Code Article</th>
              <th>Fournisseur</th>
            @elseif($reportTypeLabel === 'de Sortie de Stock')
              <th>Personnel</th>
              <th>Code mouvement</th>
              <th>Bureau</th>
            @endif
            <th>Description</th>
          </tr>
        </thead>
        <tbody>
          @foreach($mouvements as $index => $mouvement)
          <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ \Carbon\Carbon::parse($mouvement->date_mouvement)->format('d/m/Y') }}</td>
            <td>{{ $mouvement->article->libelle ?? 'N/A' }}</td>
            <td>{{ $mouvement->qte }}</td>
            <td>{{ $mouvement->prixUnitaire }}</td>
            <td>{{ $mouvement->unite_de_mesure->libelle ?? 'N/A' }}</td>
            <td>{{ $mouvement->cout_moyen_pondere}}</td>
            @if($reportTypeLabel === 'd\'Entrée de Stock')
              <td>{{ $mouvement->article->code_article ?? 'N/A' }}</td>
              <td>{{ $mouvement->fournisseur->nom ?? 'N/A' }}</td>
            @elseif($reportTypeLabel === 'de Sortie de Stock')
              <td>{{ $mouvement->employe->nom ?? '' }} {{ $mouvement->employe->prenom ?? 'N/A' }}</td>
              <td>{{ $mouvement->code_mouvement ?? 'N/A' }}</td>
              <td>{{ $mouvement->bureau->libelle_bureau ?? 'N/A' }}</td>
            @endif
            <td>{{ $mouvement->description ?? 'N/A' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>

    @if($reportTypeLabel === 'd\'Entrée de Stock')
        <div class="footer-section">
            <table>
            <tr>
                <td>
                <div class="certification-box">
                    <strong style="text-align: center;">CERTIFICATION</strong>
                    <p>
                    Arrêté le présent ordre à ............ unités représentant une valeur de ......................................................... francs.
                    </p>
                    <p>
                    À ........................., le .............................
                    </p>
                    <p style="text-align: right;"><b>L'ordonnateur des matières</b></p>
                </div>
                </td>
                <td>
                <div class="augmentation-box">
                    <strong style="text-align: center;">AUGMENTATION DES PRISES EN CHARGE</strong>
                    <p>
                    Je soussigné, comptable des matières, atteste ce jour la prise en charge supplémentaire de ............. unités, d’une valeur de .................................................... francs.
                    </p>
                    <p style="text-align: right;"><b>Le Comptable des matières</b></p>
                </div>
                </td>
                <td>
                <div class="recepisse-box">
                    <strong style="text-align: center;">REÇU</strong>
                    <p>
                    Je soussigné ........................................, reconnais avoir reçu les matières portées dans le présent ordre.
                    </p>
                    <p>
                    À .................................., le .....................
                    </p>
                    <p style="text-align: right;"><b>Le Réceptionnaire</b></p>
                </div>
                </td>
            </tr>
            </table>
        </div>
    @elseif($reportTypeLabel === 'de Sortie de Stock')
        <div class="footer-section-section">
            <table>
                <tr>
                    <td>
                        <div class="diminution-prise-charge-box">
                            <strong>CERTIFICATION</strong>
                            <p>
                                Arrêté le présent ordre à ............ unités représentant une valeur de ......................................................... francs.
                            </p>
                            <p>
                                À ........................., le .............................
                            </p>
                            <p><b>L'ordonnateur des matières</b></p>
                        </div>
                    </td>
                    <td>
                        <div class="diminution-prise-charge-box">
                            <strong>DIMINUTION DES PRISES EN CHARGE</strong>
                            <p>
                                Le comptable des matières soussigné, déclare ce jour diminuées ses prises en charge de ............ unités représentant une valeur de .................................................... francs.
                            </p>
                            <p>
                                À ........................., le .............................
                            </p>
                            <p><b>Le Comptable des matières</b></p>
                        </div>
                    </td>
                    <td>
                        <div class="magasinier-fiche-box">
                            <strong>MAGASINIER FICHE DE STOCK</strong>
                            <p>
                                Je soussigné, déclare ce jour reporté sur la fiche de stock les écritures des matières désignées ci-contre.
                            </p>
                            <p>
                                À ........................., le .............................
                            </p>
                            <p><b>Le Magasinier</b></p>
                        </div>
                    </td>
                    <td>
                        <div class="recepisse-out-box">
                            <strong>RECEPPISSE</strong>
                            <p>
                                Je soussigné ........................................, reconnais avoir reçu les matières portées dans le présent ordre.
                            </p>
                            <p>
                                À .................................., le .....................
                            </p>
                            <p><b>Le Réceptionnaire</b></p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    @endif

</body>
</html>
