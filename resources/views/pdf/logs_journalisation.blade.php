<!DOCTYPE html>
<html>
<head>
    <title>Journalisation des Actions - Export PDF</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        /* Styles de base pour Dompdf */
        body {
            font-family: sans-serif;
            font-size: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            color: #333;
            margin: 0;
        }
        .info {
            margin-bottom: 20px;
            border: 1px solid #ccc;
            padding: 10px;
            background-color: #f9f9f9;
        }
        .info p {
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #e4e4e4;
            color: #333;
            font-size: 11px;
        }
        td {
            font-size: 10px;
        }
        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #777;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>JOURNALISATION DES ACTIONS DU SYSTÈME</h1>
        <p><strong>Rapport d'Exportation PDF</strong></p>
    </div>

    <div class="info">
        <p><strong>Date de Génération :</strong> {{ $current_date }}</p>
        <p><strong>Total des Logs Exportés :</strong> {{ count($logs) }}</p>
        <p>
            <strong>Filtres Appliqués :</strong>
            @if(empty(array_filter($filters)))
                Aucun filtre. (Liste complète)
            @else
                @php
                    $appliedFilters = [];
                    if (!empty($filters['action'])) {
                        $appliedFilters[] = 'Action contenant : "' . $filters['action'] . '"';
                    }
                    if (!empty($filters['user_id'])) {
                        // Idéalement, on chercherait le nom de l'utilisateur ici, mais pour la simplicité, on affiche l'ID.
                        $appliedFilters[] = 'ID Utilisateur : ' . $filters['user_id'];
                    }
                    if (!empty($filters['date_debut'])) {
                        $appliedFilters[] = 'Après le : ' . \Carbon\Carbon::parse($filters['date_debut'])->format('d/m/Y');
                    }
                    if (!empty($filters['date_fin'])) {
                        $appliedFilters[] = 'Avant le : ' . \Carbon\Carbon::parse($filters['date_fin'])->format('d/m/Y');
                    }
                @endphp
                {{ implode(' | ', $appliedFilters) }}
            @endif
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 20%;">Date de l'Action</th>
                <th style="width: 40%;">Action Effectuée</th>
                <th style="width: 25%;">Utilisateur</th>
                <th style="width: 10%;">Adresse IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $index => $log)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($log->date_action)->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->user_name_full ?? 'Système/Invité' }}</td>
                    <td>{{ $log->ip_address }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center;">Aucun journal d'action trouvé pour les filtres spécifiés.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Rapport généré par le système LNB - Page <script type="text/php">echo $PAGE_NUM;</script> sur <script type="text/php">echo $PAGE_COUNT;</script>
    </div>

</body>
</html>