<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Fiche Demande Immobilisation</title>

<style>

body{
font-family: Arial;
font-size:12px;
}

table{
width:100%;
border-collapse:collapse;
}

th,td{
border:1px solid black;
padding:6px;
text-align:left;
}

.header{
text-align:center;
margin-bottom:20px;
}

</style>

</head>

<body>

<div class="header">

<h2>FICHE DEMANDE D'IMMOBILISATION</h2>

N° {{ $numeroFiche }}

<br>

Généré le : {{ date('d/m/Y H:i') }}

</div>

<table>

<tr>
<td width="50%">

<strong>Demandeur</strong><br>

Nom : {{ $demande->employe->nom ?? '-' }}

{{ $demande->employe->prenom ?? '-' }}

<br>

{{-- Bureau : {{ $demande->bureau->libelle_bureau ?? '-' }}

<br> --}}

Date demande :

{{ \Carbon\Carbon::parse($demande->date_demande)->format('d/m/Y') }}

</td>

<td width="50%">

<strong>Traiteur</strong><br>

Nom : {{ $authUser->name }}

<br>

Statut :

{{ $demande->status }}

</td>

</tr>

</table>

<br>

<table>

<thead>

<tr>

<th>Code</th>

<th>Immobilisation</th>

<th>Description</th>

<th>Statut</th>

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

<br><br>

<table>

<tr>

<td>

Signature demandeur

<br><br><br>

</td>

<td>

Signature ordonnateur

<br><br><br>

</td>

</tr>

</table>

</body>

</html>
