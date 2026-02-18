<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mise à jour CUMP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">
<div class="max-w-lg mx-auto bg-white p-8 rounded-lg shadow">
    <h2 class="text-2xl font-bold mb-6">Importer les CUMP (Excel)</h2>

    <form id="importForm" enctype="multipart/form-data">
        @csrf
        <div class="mb-4">
            <label class="block text-gray-700">Fichier Excel (.xlsx, .xls)</label>
            <input type="file" name="file" accept=".xlsx, .xls" required
                   class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
        </div>

        <button type="submit" id="btnSubmit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
            Mettre à jour les stocks
        </button>
    </form>

    <div id="responseMessage" class="mt-6 hidden p-4 rounded"></div>
</div>

<script>
    document.getElementById('importForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmit');
        const msgDiv = document.getElementById('responseMessage');

        btn.disabled = true;
        btn.innerText = 'Traitement en cours...';

        const formData = new FormData(this);

        try {
            const response = await fetch("{{ route('import.update') }}", {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const result = await response.json();

            msgDiv.classList.remove('hidden', 'bg-green-100', 'text-green-700', 'bg-red-100', 'text-red-700');

            if (response.ok) {
                msgDiv.classList.add('bg-green-100', 'text-green-700');
                msgDiv.innerText = result.message;
            } else {
                msgDiv.classList.add('bg-red-100', 'text-red-700');
                msgDiv.innerText = result.error || "Une erreur est survenue.";
            }
        } catch (error) {
            console.error(error);
        } finally {
            btn.disabled = false;
            btn.innerText = 'Mettre à jour les stocks';
        }
    });
</script>
</body>
</html>
