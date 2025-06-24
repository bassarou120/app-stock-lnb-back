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

.header p {
  margin: 2px 0;
}

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



  </style>


</head>
<body>

  <div class="header">
    <p><strong>République du Bénin</strong></p>
    <p>Ministère/Institution/Collectivité locale : _____________________________</p>
    <p>Structure/Direction/Service : _____________________________</p>
    <p class="right">Rapport généré le: {{ date('d/m/Y H:i:s') }} (*)</p>
  </div>

  <h1 style="font-size:25px;text-align:center">Rapport d'Enregistrement des Immobilisations</h1>
  <h2 style="font-size:18px;text-align:center">LNB-Stock & Parc</h2>

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

</body>
</html>
