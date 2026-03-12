<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Fiche Demande Immobilisation</title>

<style>

@page{
    size: A4 landscape;
    margin: 10mm;
}

body{
    font-family: Arial, sans-serif;
    font-size:11pt;
}

table{
    width:100%;
    border-collapse:collapse;
}

th,td{
    border:1px solid black;
    padding:6px;
}

th{
    background:#f2f2f2;
}

.header{
    text-align:center;
    margin-bottom:15px;
}

.header-table td{
    border:none;
}

.info-table td{
    border:none;
    padding:4px;
}

.signatures td{
    height:120px;
    vertical-align:top;
}

</style>

</head>

<body>

<!-- HEADER -->
<table class="header-table">

<tr>

<td width="30%">
République du Bénin<br>
LNB - Lotterie Nationale du Bénin SA
</td>

<td width="40%" class="header">

<img src="images/logo1.png" style="height:50px;"><br>

<h2>FICHE DEMANDE D'IMMOBILISATION</h2>

N° {{ $numeroFiche }}

<br>

<small>Généré le : {{ date('d/m/Y H:i') }}</small>

</td>

<td width="30%" style="text-align:right">

{{-- Modèle n°2 --}}

</td>

</tr>

</table>

<br>

<!-- INFORMATIONS -->
<table class="info-table">

<tr>

<td width="50%">

<strong>Informations du Demandeur</strong>

<br><br>

<strong>Nom :</strong>
{{ $demande->employe->nom ?? '-' }}
{{ $demande->employe->prenom ?? '-' }}

<br>

<strong>Date demande :</strong>
{{ \Carbon\Carbon::parse($demande->date_demande)->format('d/m/Y') }}

</td>

<td width="50%">

<strong>Informations du Traiteur</strong>

<br><br>

<strong>Nom :</strong>
{{ $authUser->name }}

<br>

<strong>Statut :</strong>
{{ $demande->status }}

</td>

</tr>

</table>

<br>

<!-- TABLEAU -->
<table>

<thead>

<tr>

<th width="15%">Code</th>

<th width="30%">Immobilisation</th>

<th width="40%">Description</th>

<th width="15%">Statut</th>

</tr>

</thead>

<tbody>

<tr>

<td>{{ $demande->immobilisation->code ?? '-' }}</td>

<td>{{ $demande->immobilisation->designation ?? '-' }}</td>

<td>{{ $demande->immobilisation->observation ?? '-' }}</td>

<td>{{ $demande->status }}</td>

</tr>

</tbody>

</table>

<br><br><br>

<!-- SIGNATURES -->
<table class="signatures">

<tr>

<td width="50%" style="text-align:center">

<strong>Signature du Demandeur</strong>

<br><br><br><br>

</td>

<td width="50%" style="text-align:center">

<strong>Signature de l'Ordonnateur</strong>

<br><br><br><br>

</td>

</tr>

</table>

</body>

</html>
