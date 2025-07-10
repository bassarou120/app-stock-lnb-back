<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du Transfert d'Immobilisation</title>

    <style>
        @page {
            size: landscape; /* Format paysage pour plus de colonnes */
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

        /* Debut css pour l'entete gauche droite */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            height: 80px;
            border: none; /* Supprime toutes les bordures de la table d'en-tête */
        }
        .header-table td {
            border: none; /* Supprime les bordures des cellules de la table d'en-tête */
            vertical-align: middle;
        }
        .header-table .left-col {
            width: 70%;
            text-align: left;
        }
        .header-table .right-col {
            width: 30%;
            text-align: right;
            vertical-align: top;
        }
        .header-table img {
            height: 45px;
            margin-bottom: 5px;
            display: block; /* Assure que l'image ne prend pas de place inutile */
            margin-left: auto; /* Centre l'image si elle est plus petite que la cellule */
        }
        /* Fin css pour l'entete gauche droite */
    </style>
</head>
<body>

<table class="header-table">
    <tr>
        <td class="left-col">
            <p style="margin: 2px 0;"><strong>République du Bénin</strong></p>
            <p style="margin: 2px 0;">LNB - Lotterie Nationale du Bénin SA</p>
        </td>
        <td class="right-col">
            {{-- Assurez-vous que le chemin de l'image est accessible par dompdf --}}
            {{-- Pour les images locales, il est souvent nécessaire de les encoder en base64 ou de configurer dompdf pour qu'il puisse y accéder --}}
            <img src="images/logo1.png" alt="Logo LNB"><br>
            <p style="margin: 2px 0;">Rapport généré le: {{ date('d/m/Y H:i:s') }} (*)</p>
        </td>
    </tr>
</table>

<h1 style="font-size:25px;text-align:center">Détails Affectations/Transferts d'Immobilisation</h1>
<h2 style="font-size:18px;text-align:center">LNB-Stock & Parc</h2>

{{-- Puisqu'il s'agit d'un seul transfert, nous n'avons pas besoin de vérifier $transferts->isEmpty() --}}

<table>
    <thead>
        <tr>
            <th>N°</th>
            <th>Code Immo</th>
            <th>Désignation Immo</th>
            <th>Ancien Bureau</th>
            <th>Nouveau Bureau</th>
            <th>Ancien Responsable</th>
            <th>Nouveau Responsable</th>
            <th>Date Mouvement</th>
            <th>État</th>
            <th>Date Mise en Service</th>
            <th>Observation</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td> {{-- Numéro de ligne pour le transfert unique --}}
            <td>{{ $transfert->immobilisation->code ?? 'N/A' }}</td>
            <td>{{ $transfert->immobilisation->designation ?? 'Non défini' }}</td>
            <td>{{ $transfert->old_bureau->libelle_bureau ?? 'Magasin' }}</td>
            <td>{{ $transfert->bureau->libelle_bureau ?? 'Non défini' }}</td>
            <td>
                @if($transfert->old_employe)
                    {{ $transfert->old_employe->nom }} {{ $transfert->old_employe->prenom }}
                @else
                    Non défini
                @endif
            </td>
            <td>
                @if($transfert->employe)
                    {{ $transfert->employe->nom }} {{ $transfert->employe->prenom }}
                @else
                    Non défini
                @endif
            </td>
            <td>{{ \Carbon\Carbon::parse($transfert->date_mouvement)->format('d/m/Y') }}</td>
            <td>{{ $transfert->immobilisation->etat ?? 'Non spécifié' }}</td>
            <td>
                @if($transfert->immobilisation && $transfert->immobilisation->date_mise_en_service)
                    {{ \Carbon\Carbon::parse($transfert->immobilisation->date_mise_en_service)->format('d/m/Y') }}
                @else
                    N/A
                @endif
            </td>
            <td>{{ $transfert->observation ?? 'Aucune observation.' }}</td>
        </tr>
    </tbody>
</table>

<p class="note">(*) État établi en fin de gestion et hors du but arrêté des écritures.</p>

<div class="signatures">
    <div>Le Comptable des Matières</div>
    <div>Le Magasinier / Fichiste</div>
    <div>Nom et Prénoms des membres de la commission d’inventaire</div>
</div>

</body>
</html>
