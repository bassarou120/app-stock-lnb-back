<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Rapport des Interventions sur Immobilisations</title>
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
    <p>LNB-Lotterie National du Bénin SA</p>
    <p class="right">Rapport généré le: {{ date('d/m/Y H:i:s') }} (*)</p>
  </div>

  <h1 style="font-size:25px;text-align:center">Rapport des Interventions sur Immobilisations</h1>
  <h2 style="font-size:18px;text-align:center">LNB-Stock & Parc</h2>

  @if($interventions->isEmpty())
    <p class="no-data">Aucune intervention trouvée pour les critères de recherche spécifiés.</p>
  @else

  <table>
    <thead>
      <tr>
        <th>N°</th>
        <th>Code Immo</th>
        <th>Désignation Immo</th>
        <th>Type Intervention</th>
        <th>Titre</th>
        <th>Date Intervention</th>
        <th>Coût</th>
        <th>Observation</th>
      </tr>
    </thead>
    <tbody>
      @foreach($interventions as $index => $intervention)
      <tr>
          <td>{{ $index + 1 }}</td>
          <td>{{ $intervention->immobilisation->code ?? 'N/A' }}</td>
          <td>{{ $intervention->immobilisation->designation ?? 'N/A' }}</td>
          <td>{{ $intervention->typeIntervention->libelle_type_intervention ?? 'N/A' }}</td>
          <td>{{ $intervention->titre ?? 'N/A' }}</td>
          <td>{{ \Carbon\Carbon::parse($intervention->date_intervention)->format('d/m/Y') }}</td>
          <td>{{ number_format($intervention->cout, 2, ',', ' ') ?? 'N/A' }}</td>
          <td>{{ $intervention->observation ?? 'N/A' }}</td>
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
