<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversor de Imagens Base64</title>
    <style>
        #drop-area {
            border: 2px dashed #ccc;
            border-radius: 20px;
            width: 100%;
            padding: 20px;
            font-family: sans-serif;
            text-align: center;
        }

        #gallery {
            margin-top: 10px;
        }

        #gallery img {
            width: 100px;
            margin: 5px;
        }

        #button {
            margin-top: 10px;
        }

        /* Estilo da modal */
        #errorModal {
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0, 0, 0);
            background-color: rgba(0, 0, 0, 0.4);
        }

        #errorModalContent {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }

        #closeModal {
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        /* Estilo do spinner de carregamento */
        #loading {
            display: none;
            position: fixed;
            z-index: 1;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            border: 16px solid #f3f3f3;
            border-radius: 50%;
            border-top: 16px solid #3498db;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
<h1>Conversor de Imagens Base64</h1>
<div id="drop-area">
    <form id="uploadForm" enctype="multipart/form-data" method="post">
        <p>Arraste e solte as imagens aqui</p>
        <input type="file" id="fileInput" name="images[]" accept="image/*" multiple>
        <br>
        <div id="gallery"></div>
        <br>
        <label for="variableSelect">Nome da variável:</label>
        <select id="variableSelect">
            <option value="cImg">cImg</option>
            <option value="IBase64">IBase64</option>
            <option value="custom">Customizado</option>
        </select>
        <br>
        <input type="text" id="customVariable" placeholder="Digite sua variável personalizada"
               style="display:none;margin: 20px auto 0 auto;">
    </form>
    <br>
    <button id="convertButton">Converter base64TOClob e Baixar</button>
    <button id="clearButton">Clear</button>
    <div id="errorModal" style="display:none;">
        <div id="errorModalContent">
            <span id="closeModal" style="cursor:pointer;">&times;</span>
            <p id="errorMessage"></p>
        </div>
    </div>
    <div id="loading"></div>
</div>
<br>
<b>Credits:</b><br>
<u>Script Developed By:</u> maicon.zucco<br>
<u>Website and Backend By:</u> ChatGPT (OpenAI) & PcL<br>
<u>Prompt Engineering By:</u> cap<br>

<script>
    let dropArea = document.getElementById('drop-area');
    let gallery = document.getElementById('gallery');
    let fileInput = document.getElementById('fileInput');
    let uploadForm = document.getElementById('uploadForm');

    dropArea.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropArea.classList.add('highlight');
    });

    dropArea.addEventListener('dragleave', () => {
        dropArea.classList.remove('highlight');
    });

    dropArea.addEventListener('drop', (event) => {
        event.preventDefault();
        dropArea.classList.remove('highlight');
        let files = event.dataTransfer.files;
        handleFiles(files);
        fileInput.files = files; // Adiciona os ficheiros ao input para envio
    });

    fileInput.addEventListener('change', (event) => {
        let files = event.target.files;
        handleFiles(files);
    });

    function handleFiles(files) {
        gallery.innerHTML = '';
        [...files].forEach(file => {
            let img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            gallery.appendChild(img);
        });
    }

    document.getElementById('variableSelect').addEventListener('change', function () {
        const customInput = document.getElementById('customVariable');
        if (this.value === 'custom') {
            customInput.style.display = 'block';
        } else {
            customInput.style.display = 'none';
        }
    });

    document.getElementById('convertButton').addEventListener('click', async function (event) {
        event.preventDefault();
        document.getElementById('convertButton').disabled = true; // Bloquear o botão
        document.getElementById('loading').style.display = 'block'; // Mostrar o indicador de carregamento

        let formData = new FormData(uploadForm);
        let selectedVariable = document.getElementById('variableSelect').value;
        if (selectedVariable === 'custom') {
            selectedVariable = document.getElementById('customVariable').value || 'cImg'; // Valor default se estiver vazio
        }
        formData.append('variable', selectedVariable); // Adiciona a variável ao formData

        const response = await fetch('converter.php', {
            method: 'POST',
            body: formData
        });

        document.getElementById('loading').style.display = 'none'; // Ocultar o indicador de carregamento
        document.getElementById('convertButton').disabled = false; // Desbloquear o botão

        if (response.status === 200) {
            const blob = await response.blob();
            const contentDisposition = response.headers.get('Content-Disposition');
            let fileName = 'converted.sql';

            if (contentDisposition && contentDisposition.indexOf('attachment') !== -1) {
                const matches = /filename="([^"]+)"/.exec(contentDisposition);
                if (matches != null && matches[1]) {
                    fileName = matches[1];
                }
            }

            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            a.remove();
        } else {
            const errorMessage = await response.text();
            console.log("ERRO", response.status, errorMessage);

            document.getElementById('errorMessage').innerText = errorMessage;
            document.getElementById('errorModal').style.display = 'block';
        }
    });

    document.getElementById('clearButton').addEventListener('click', function () {
        // Limpa a galeria de imagens
        gallery.innerHTML = '';
        // Reseta o input de ficheiros
        fileInput.value = '';
    });

    document.getElementById('closeModal').addEventListener('click', function () {
        document.getElementById('errorModal').style.display = 'none';
    });

    window.addEventListener('click', function (event) {
        if (event.target == document.getElementById('errorModal')) {
            document.getElementById('errorModal').style.display = 'none';
        }
    });
</script>
</body>
</html>
