<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Rapport d'Enregistrement des Immobilisations</title>
  <link rel="stylesheet" href="style.css">

  <style>

@page {
  size: landscape;
  margin: 8mm;
}

@media print {
  body {
    width: 100%;
  }

  table {
    font-size: 12px;
  }

  .signatures {
    flex-direction: row;
    justify-content: space-between;
  }
}


body {
  font-family: Arial, sans-serif;
  font-size: 14px;
  margin: 40px;
}


/* Debut css pour l'entete gauche droite */

.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start; /* <-- aligne les deux colonnes en haut */
  padding: 10px;
}

.left p, .right p {
  margin: 2px 0;
}

.right {
  text-align: right;
}

.right img {
  height: 60px;
  margin: 0 0 5px 0; /* supprime tout décalage vertical */
  display: block;
}


/* Fin css pour l'entete gauche droite */

.right {
  text-align: right;
}

.title {
  text-align: center;
  margin-top: 20px;
  text-decoration: underline;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
}

table, th, td {
  border: 1px solid black;
}

th, td {
  padding: 6px;
  text-align: center;
}

.note {
  font-size: 12px;
  margin-top: 10px;
}

.signatures {
  display: flex;
  justify-content: space-between;
  margin-top: 30px;
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

    <table width="100%" style="border-collapse: collapse; height: 80px; border: none;">
    <tr>
        <td style="width: 70%; text-align: left; vertical-align: middle; border: none;">
        <p style="margin: 2px 0;"><strong>République du Bénin</strong></p>
        <p style="margin: 2px 0;">LNB - Lotterie Nationale du Bénin SA</p>
        </td>
        <td style="width: 30%; text-align: right; vertical-align: top; border: none;">
        <p style="margin: 2px 0;">Rapport généré le: {{ date('d/m/Y H:i:s') }} (*)</p>
        </td>
    </tr>
    </table>

    <div style="width: 100%; margin-bottom: 10px;text-align: center;">
        <img src="images/logo1.png" alt="Logo LNB" style="height: 45px; margin-bottom: 5px;">
        <h1 style="font-size: 20px; margin: 10px 0; font-weight: bold;">
            Rapport d'Enregistrement des Immobilisations
        </h1>
        <h2 style="font-size: 16px; margin: 5px 0; font-weight: normal;">
            LNB-Stock & Parc
        </h2>
    </div>

    @if($immobilisations->isEmpty())
        <p class="no-data">Aucune immobilisation trouvée pour les critères de recherche spécifiés.</p>
    @else

    <table>
        <thead>
        <tr>
            <th>N°</th>
            <th>Code</th>
            <th>Désignation</th>
            <th>Montant TTC</th>
            <th>État</th>
            <th>Observation</th>
            <th>Groupe Type</th>
            <th>Sous Type</th>
            <th>Statut</th>
            <th>Responsable</th>
            <th>Date Acquisition</th>
        </tr>
        </thead>
        <tbody>
        @foreach($immobilisations as $index => $immo)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $immo->code ?? 'N/A' }}</td>
            <td>{{ $immo->designation ?? 'N/A' }}</td>
            <td>{{ number_format($immo->montant_ttc, 2, ',', ' ') ?? 'N/A' }}</td>
            <td>{{ $immo->etat ?? 'N/A' }}</td>
            <td>{{ $immo->observation ?? 'N/A' }}</td>
            <td>{{ $immo->groupeTypeImmo->libelle ?? 'N/A' }}</td>
            <td>{{ $immo->sousTypeImmo->libelle ?? 'N/A' }}</td>
            <td>{{ $immo->statusImmo->libelle_status_immo ?? 'N/A' }}</td>
            <td>{{ $immo->employe ? ($immo->employe->nom . ' ' . $immo->employe->prenom) : 'N/A' }}</td>
            <td>{{ $immo->date_acquisition ? \Carbon\Carbon::parse($immo->date_acquisition)->format('d/m/Y') : 'N/A' }}</td>
        </tr>
        @endforeach

        </tbody>
    </table>

    <p class="note">(*) État établi en fin de gestion et hors du but arrêté des écritures.</p>

    <div class="signatures">
        <div>Le Comptable des Matières</div>
        <div>Le Magasinier / Fichiste</div>
        <div>Nom et Prénoms des membres de la commission d’inventaire</div>
    </div>

    @endif

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
</body>
</html>
