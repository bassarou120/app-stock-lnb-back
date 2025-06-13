<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>État de Stock des Tickets</title>
    <style>
        @page {
            size: landscape;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #00993E;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: normal;
            font-size: 13px;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        tr:nth-child(even) {
            background-color: #f5f5f5;
        }
        tr:nth-child(odd) {
            background-color: #ffffff;
        }
    </style>
</head>
<body>
    <h2>LNB-Stock & Parc | État de Stock des Tickets</h2>
    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Coupon Tickets</th>
                <th>Compagnie petrolière</th>
                <th>Quantité Actuelle</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stock_tickets as $index => $stock_ticket)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $stock_ticket->couponTicket->libelle ?? 'N/A' }}</td>
                    <td>{{ $stock_ticket->compagnie->libelle ?? 'N/A' }}</td>
                    <td>{{ number_format($stock_ticket->qte_actuel, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
