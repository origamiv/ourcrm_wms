<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование ассистента</title>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- ✅ Dropzone -->
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" />
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
        }
        .dropzone {
            border: 2px dashed #007bff;
            border-radius: 8px;
            background: #f9f9f9;
            padding: 20px;
            text-align: center;
        }
        .existing-files {
            background: #f3f3f3;
            border: 1px solid #ccc;
            padding: 10px;
            font-family: monospace;
            font-size: 13px;
            margin-top: 10px;
        }
        table td { vertical-align: top; padding: 6px; }
        button { margin-top: 15px; padding: 8px 14px; cursor: pointer; }
    </style>
</head>
<body>

<form id="assistantForm" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="id" value="{{ $assistant['id'] ?? '' }}" />

    <table>
        <tr>
            <td>Название</td>
            <td><input type="text" name="name" value="{{ $assistant['name'] ?? '' }}" /></td>
        </tr>
        <tr>
            <td>Короткое имя</td>
            <td><input name="shortname" value="{{ $assistant['shortname'] ?? '' }}" /></td>
        </tr>
        <tr>
            <td>Промпт</td>
            <td><textarea name="prompt" cols="40" rows="10">{{ $assistant['prompt'] ?? '' }}</textarea></td>
        </tr>

        <tr>
            <td>Текущие данные</td>
            <td>
                <div class="existing-files">
                    @php
                        if(!empty($assistant['data'])) {
                            $json = $assistant['data'];
                            if (!empty($json)) {
                                foreach ($json as $i => $item) {
                                    echo "<div>📄 Файл ".($i+1).": ".htmlspecialchars(substr(trim($item), 0, 100))."...</div>";
                                    echo "<input type=hidden name='data[]' value='{$item}'>";
                                }
                            } else {
                                echo "<div>Нет сохранённых данных</div>";
                            }
                        } else {
                            echo "<div>Нет сохранённых данных</div>";
                        }
                    @endphp
                </div>
            </td>
        </tr>

        <tr>
            <td>Добавить файлы</td>
            <td>
                <div id="dataDropzone" class="dropzone">
                    <div class="dz-message">Перетащите сюда новые файлы или кликните для выбора</div>
                </div>
            </td>
        </tr>

        <tr>
            <td>Функции</td>
            <td><textarea name="func" cols="40" rows="10">{{ $assistant['func'] ?? '' }}</textarea></td>
        </tr>
    </table>

    <button type="submit">💾 Сохранить изменения</button>
</form>

<script>
    Dropzone.autoDiscover = false;

    const token = '1586|j2foE4NLtoOVinxsUJhFfcMfgThj9FCjm2ZEMwEX975f71c2';
    const form = document.getElementById('assistantForm');
    let uploadedFiles = [];

    // ✅ Инициализация Dropzone
    const dz = new Dropzone("#dataDropzone", {
        url: "#",
        autoProcessQueue: false,
        addRemoveLinks: true,
        paramName: "data",
        acceptedFiles: ".txt,.json,.csv,.md,.yaml,.yml",
        dictRemoveFile: "Удалить",
        dictDefaultMessage: "Перетащите сюда новые файлы или кликните для выбора",
        init: function() {
            this.on("addedfile", f => uploadedFiles.push(f));
            this.on("removedfile", f => uploadedFiles = uploadedFiles.filter(x => x!==f));
        }
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();

        const assistantId = form.querySelector('input[name="id"]').value;
        const formData = new FormData(form);
        formData.append('_method', 'PUT'); // 👈 Laravel поймёт как PUT

        // ✅ Добавляем файлы из Dropzone
        uploadedFiles.forEach(f => formData.append('data[]', f, f.name));

        console.log('⏳ Отправка запроса...');

        try {
            const response = await fetch(`/api/assistant/${assistantId}`, {
                method: 'POST', // 👈 настоящий POST + _method=PUT
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                body: formData
            });

            if (response.ok) {
                const data = await response.json();
                console.log('✅ Успешно обновлено:', data);
                alert('Ассистент успешно обновлён');
            } else {
                const err = await response.text();
                console.error('❌ Ошибка:', err);
                alert('Ошибка при сохранении:\n' + err);
            }
        } catch (error) {
            console.error('❌ Ошибка сети:', error);
            alert('Ошибка сети:\n' + error.message);
        }
    });
</script>

</body>
</html>
