<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordre de Sortie</title>
    <style>
        @page {
            size: A4 landscape; /* Format paysage pour A4 */
            margin: 5mm; /* Marges réduites */
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9pt; /* Taille de police globale légèrement réduite */
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
            margin-bottom: 8px; /* Espacement réduit entre les sections */
            margin-top: 8px;
        }

        .header-section h2 {
            margin: 5px 0;
            font-size: 14pt; /* Taille de titre ajustée */
        }

        .header-section td,
        .budget-section td {
            padding: 1px 3px; /* Padding réduit pour les cellules d'en-tête et budget */
            vertical-align: top;
            font-size: 9pt; /* Taille de police pour ces sections */
        }

        /* Styles spécifiques au tableau de données */
        .table-section th,
        .table-section td {
            border: 1px solid black;
            padding: 2px; /* Padding réduit pour les cellules de tableau */
            text-align: center;
            word-break: break-word;
            font-size: 8.5pt; /* Taille de police pour le tableau */
        }

        .table-section th {
            background-color: #f2f2f2;
            vertical-align: middle; /* Centrer verticalement les en-têtes */
        }

        /* Largeurs des colonnes pour le tableau de données */
        .table-section .col-numeros { width: 18%; } /* Regroupe les 3 sous-colonnes de numéros */
        .table-section .col-folio { width: 6%; }
        .table-section .col-ordre { width: 6%; }
        .table-section .col-nomenclature { width: 6%; }
        .table-section .col-designation { width: 20%; }
        .table-section .col-specification { width: 15%; }
        .table-section .col-unites { width: 7%; }
        .table-section .col-quantite { width: 7%; }
        .table-section .col-prix { width: 7%; }
        .table-section .col-montant { width: 8%; }
        .table-section .col-observations { width: 12%; }

        .table-section .total-row td {
            text-align: right;
            font-weight: bold;
            padding-right: 5px;
        }

        /* Styles des boîtes du pied de page */
        .footer-section {
            page-break-inside: avoid;
            margin-top: 10px; /* Espace au-dessus du footer */
        }

        .footer-section table {
            table-layout: fixed; /* Force les colonnes à avoir une largeur fixe */
        }

        .footer-section table td {
            width: 33.33%; /* Chaque colonne prend un tiers de la largeur */
            padding: 0 3px; /* Petit padding horizontal entre les colonnes */
            vertical-align: top;
        }

        .certification-box,
        .augmentation-box,
        .recepisse-box {
            border: 1px solid black;
            padding: 5px 7px; /* Padding interne des boîtes */
            font-size: 8.5pt; /* Taille de police pour le contenu des boîtes */
            line-height: 1.2; /* Hauteur de ligne pour compacter le texte */
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Distribue l'espace verticalement */
            box-sizing: border-box;
            height: 120px; /* Hauteur fixe pour toutes les boîtes, ajustée pour le contenu */
            /* width: 100%; sera géré par le table-layout: fixed et le width: 33.33% du td */
        }

        .certification-box p,
        .augmentation-box p,
        .recepisse-box p {
            margin: 2px 0; /* Marges réduites pour les paragraphes */
            padding: 0;
        }

        .certification-box strong,
        .augmentation-box strong,
        .recepisse-box strong {
            display: block;
            text-align: center; /* Centrer les titres des sections */
            margin-bottom: 4px; /* Espace après le titre */
        }

        /* Aligner la dernière ligne du texte des responsables à droite */
        .certification-box p:last-child,
        .augmentation-box p:last-child,
        .recepisse-box p:last-child {
            margin-top: auto; /* Pousse la dernière ligne vers le bas */
            text-align: right;
            line-height: 1; /* Compacter la ligne de signature */
        }

        small {
            font-size: 7pt;
        }
    </style>
</head>
<body>

    <div class="header-section">
        <table>
            <tr>
                <td style="width: 50%;">
                    République du Bénin<br>
                    Ministère/Institution/Collectivité locale<br>
                    Direction/service
                </td>
                <td style="width: 50%; text-align: right;">
                    Modèle n°2<br>
                    Ordre de Sortie N° ...............<br>
                    <small>Rapport généré le: {{ date('d/m/Y H:i:s') }}</small>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center; padding-top: 5px;">
                    <h2>ORDRE DE SORTIE</h2>
                    (à établir en 03 exemplaires)
                </td>
            </tr>
        </table>
    </div>

    <div class="budget-section">
        <table>
            <tr>
                <td style="width: 50%;">
                    (1) National ; Etat, Fond propre ; Annexe, Spécial ; Compte fonds... etc...<br>
                    (2) Objet.<br>
                    (3) Approvisionnement en magasin ou matériel en service.<br>
                    (4) D'acquis, de donation.<br>
                    (5) Provenance (Bon de commande, Fournisseur Donateur).
                </td>
                <td style="width: 50%;">
                    BUDGET (1)......................................... Chapitre ........................................<br>
                    (2)..........................................................................................................<br>
                    (3)..........................................................................................................<br>
                    (4)..........................................................................................................<br>
                </td>
            </tr>
        </table>
    </div>

    <div class="table-section">
        <table>
            <thead>
                <tr>
                    <th colspan="3" class="col-numeros">Numéros</th>
                    <th rowspan="2" class="col-designation">Désignation des matières et objets</th>
                    <th rowspan="2" class="col-specification">Spécification des matières</th>
                    <th rowspan="2" class="col-unites">Espèce des unités</th>
                    <th rowspan="2" class="col-quantite">Quantité</th>
                    <th rowspan="2" class="col-prix">Prix Unitaire</th>
                    <th rowspan="2" class="col-montant">Montant</th>
                    <th rowspan="2" class="col-observations">OBSERVATIONS<br><small>Inscrire dans cette colonne toutes les renseignements susceptibles d'éclaircir les services du contrôle.</small></th>
                </tr>
                <tr>
                    <th class="col-folio">Du folio du grand livre</th>
                    <th class="col-ordre">D'ordre ou du journal</th>
                    <th class="col-nomenclature">De la nomenclature sommaire</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="height: 16px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td style="height: 16px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                 <tr>
                    <td style="height: 16px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                 <tr>
                    <td style="height: 16px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                 <tr>
                    <td style="height: 16px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                 <tr>
                    <td style="height: 16px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                 <tr>
                    <td style="height: 16px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                 <tr>
                    <td style="height: 16px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                 <tr>
                    <td style="height: 16px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                 <tr>
                    <td style="height: 16px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="total-row">
                    <td colspan="6" style="text-align: right;">Total</td>
                    <td colspan="4"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer-section">
        <table>
            <tr>
                <td>
                    <div class="certification-box">
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
                    <div class="augmentation-box">
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
                    <div class="recepisse-box">
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

</body>
</html>
