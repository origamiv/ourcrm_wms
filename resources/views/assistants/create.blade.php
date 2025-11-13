<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создание ассистента</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- ✅ Dropzone -->
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" />
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>

    <style>
        .dropzone {
            border: 2px dashed #007bff;
            border-radius: 8px;
            background: #f9f9f9;
            padding: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

<form id="assistantForm" enctype="multipart/form-data">
    <table>
        <tr>
            <td>Название</td>
            <td><input type="text" name="name" /></td>
        </tr>
        <tr>
            <td>Короткое имя</td>
            <td><input name="shortname" /></td>
        </tr>
        <tr>
            <td>Промпт</td>
            <td><textarea name="prompt" cols="40" rows="10"></textarea></td>
        </tr>
        <tr>
            <td>Данные</td>
            <td>
                <div id="dataDropzone" class="dropzone">
                    <div class="dz-message">Перетащите сюда файлы или кликните для выбора</div>
                </div>
            </td>
        </tr>
        <tr>
            <td>Функции</td>
            <td><textarea name="func" cols="40" rows="10"></textarea></td>
        </tr>
    </table>
    <button type="submit">Создать</button>
</form>

<script>
    Dropzone.autoDiscover = false;

    let uploadedFiles = [];

    // ✅ Dropzone инициализация
    const dz = new Dropzone("#dataDropzone", {
        url: "#", // не используется
        autoProcessQueue: false,
        addRemoveLinks: true,
        acceptedFiles: ".txt,.json,.csv,.md,.yaml,.yml",
        parallelUploads: 10,
        dictRemoveFile: "Удалить",
        dictDefaultMessage: "Перетащите сюда файлы или кликните для выбора",
        init: function() {
            this.on("addedfile", function(file) {
                uploadedFiles.push(file);
            });
            this.on("removedfile", function(file) {
                uploadedFiles = uploadedFiles.filter(f => f !== file);
            });
        }
    });

    $('#assistantForm').on('submit', function(e) {
        e.preventDefault();

        const token = '1586|j2foE4NLtoOVinxsUJhFfcMfgThj9FCjm2ZEMwEX975f71c2';

        const formData = new FormData();
        formData.append('name', $('input[name="name"]').val());
        formData.append('shortname', $('input[name="shortname"]').val());
        formData.append('prompt', $('textarea[name="prompt"]').val());
        formData.append('func', $('textarea[name="func"]').val());

        // ✅ Добавляем файлы как настоящие файлы
        uploadedFiles.forEach((file, index) => {
            formData.append('data[]', file, file.name);
        });

        $.ajax({
            url: '/api/assistant/create',
            method: 'POST',
            data: formData,
            processData: false,   // не сериализуем FormData
            contentType: false,   // не задаем тип вручную
            headers: {
                'Authorization': 'Bearer ' + token
            },
            beforeSend: function() {
                console.log('⏳ Отправка запроса...');
            },
            success: function(response) {
                console.log('✅ Успешно создано:', response);
                alert('Ассистент успешно создан');
            },
            error: function(xhr) {
                console.error('❌ Ошибка:', xhr.status, xhr.responseText);
                alert('Ошибка при создании ассистента:\n' + xhr.responseText);
            }
        });
    });
</script>

</body>
</html>
