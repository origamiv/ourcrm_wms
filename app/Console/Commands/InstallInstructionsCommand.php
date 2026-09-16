<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Instruction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class InstallInstructionsCommand extends Command
{
    protected $signature = 'instructions:install {--path=docs/help/instructions : Каталог Markdown-инструкций}';
    protected $description = 'Установить общие инструкции WMS';

    public function handle(): int
    {
        $root = base_path((string) $this->option('path'));
        if (! is_dir($root)) {
            $this->error('Каталог инструкций не найден: '.$root);
            return self::FAILURE;
        }

        $sections = Instruction::SECTIONS;
        $installed = 0;
        foreach ($sections as $section) {
            $files = glob($root.'/'.$section.'/*.md') ?: [];
            sort($files);
            foreach ($files as $order => $file) {
                $shortname = pathinfo($file, PATHINFO_FILENAME);
                $content = $this->prepareContent($section, $shortname, (string) file_get_contents($file));
                $name = $this->title($shortname, $content);
                $path = 'instructions/shared/'.$section.'/'.$shortname.'.md';
                Storage::disk('local')->put($path, $content);
                $storedPath = Storage::disk('local')->path($path);
                @chmod($storedPath, 0644);
                $directory = dirname($storedPath);
                while ($directory !== dirname(Storage::disk('local')->path('')) && str_starts_with($directory, Storage::disk('local')->path(''))) {
                    @chmod($directory, 0755);
                    $directory = dirname($directory);
                }

                $instruction = Instruction::withTrashed()->where('tenant_id', null)->where('shortname', $shortname)->first();
                if (! $instruction) {
                    $instruction = new Instruction();
                }
                $instruction->forceFill([
                    'tenant_id' => null,
                    'name' => $name,
                    'shortname' => $shortname,
                    'section_key' => $section,
                    'content_type' => 'markdown',
                    'storage_disk' => 'local',
                    'storage_path' => $path,
                    'original_filename' => $shortname.'.md',
                    'mime_type' => 'text/markdown',
                    'size' => strlen($content),
                    'sort_order' => $order,
                    'status' => 1,
                    'deleted_at' => null,
                ])->save();
                $installed++;
            }
        }

        $this->info("Установлено инструкций: {$installed}");
        return self::SUCCESS;
    }

    private function prepareContent(string $section, string $shortname, string $content): string
    {
        $asset = '/help-assets/instructions/'.$section.'/'.$shortname;
        $listImage = '![Список записей сущности «'.$shortname.'»]('.$asset.'-list.png)';
        $formImage = is_file(public_path('help-assets/instructions/'.$section.'/'.$shortname.'-form.png'))
            ? '![Форма создания или изменения сущности «'.$shortname.'»]('.$asset."-form.png)"
            : null;
        $content = preg_replace('/\n*!\[[^\]]*\]\(\/help-assets\/[^)]+\)\n*/', "\n", $content) ?? $content;
        $blocks = preg_split('/\n{2,}/', trim($content));
        if (count($blocks) >= 2) {
            array_splice($blocks, 2, 0, [$listImage]);
            $content = implode("\n\n", $blocks);
        } else {
            $content = $listImage."\n\n".$content;
        }
        $content .= "\n\n".$this->operations($section, $shortname, $formImage);

        return $content;
    }

    private function operations(string $section, string $shortname, ?string $formImage = null): string
    {
        $text = <<<MARKDOWN
## Полный цикл работы с записями

### Просмотр

Откройте **{$section} → {$shortname}**. В списке используйте колонку **#** как идентификатор записи и проверьте основные поля. Нажмите на название записи или действие **Просмотреть**, чтобы открыть карточку, увидеть все поля, связи и текущий статус. Перед операцией убедитесь, что выбрана нужная организация.

### Добавление

Нажмите **Добавить запись** (для рабочих операций может использоваться **Создать**). Заполните обязательные поля, выберите значения из справочников и проверьте ссылки на связанные сущности. Для дат используйте русский календарь, для количества и кодов сохраняйте точное значение, включая ведущие нули. Нажмите **Сохранить**, дождитесь подтверждения и проверьте новую строку в списке.

### Изменение

Откройте нужную запись, выберите **Изменить** или карандаш, измените только необходимые поля и сохраните. После сохранения снова откройте карточку и проверьте результат. Для иерархических сущностей сначала проверяйте родителя и дочерние записи; идентификатор **#** вручную не изменяйте.

### Удаление

В колонке **Действия** выберите **Удалить** и подтвердите операцию. Перед удалением проверьте документы, задачи, остатки и другие связанные записи. Если сущность участвует в рабочем процессе, сначала завершите операцию или замените связь. Удаление может быть мягким: удалённые записи не должны использоваться в новых операциях.

## Поиск, фильтры и сортировка

Введите запрос в поле поиска и проверьте, по каким колонкам выполняется поиск. Если доступны фильтры, задайте их до анализа результата; сбросьте фильтры перед новым поиском. Сортируйте список нажатием на заголовок колонки: повторное нажатие меняет направление. После фильтрации проверяйте число найденных строк и не редактируйте запись, скрытую активным фильтром.

MARKDOWN;

        if ($formImage !== null) {
            $text = str_replace("\n### Изменение", "\n\n".$formImage."\n\n### Изменение", $text);
        }

        return match ($shortname) {
            'goods' => $text."\n\nДля товаров отдельно контролируйте дерево категорий, уровень, родителя, штрихкоды и мастер-карточки маркетплейсов. Перенос товара пересчитывает уровень и категорийность потомков.",
            'tasks' => $text."\n\nДля задач доступны табличный и канбан-вид. Проверяйте историю, файлы, печатные формы, этап и статус; перемещение карточки в канбане изменяет статус только после подтверждения сервера.",
            'acceptances' => $text."\n\nДля приемок контролируйте состав товаров, количество, штрихкоды, код приемки и прогресс. При наличии операции подбора используйте сканирование и после завершения проверьте связанные задачи размещения.",
            'goods_marketplace' => $text."\n\nДля каталога маркетплейса используйте фильтры по интеграции, маркетплейсу и сопоставлению. Проверяйте внешний SKU, мастер-товар и результат автоматического сопоставления; новые мастер-товары создаются только при включённой настройке.",
            'imports' => $text."\n\nДля импорта проверяйте родительский запуск, текущий этап, число этапов, чанков и обработанных записей. При ошибке анализируйте запись в failed_jobs и контролируйте повторный запуск дочернего этапа.",
            'webhooks', 'rules' => $text."\n\nДля интеграций проверяйте сервис, тип события, порядок последовательных правил, параметры и журнал результата. Тестируйте цепочку от входного события до записи результата.",
            default => $text,
        };
    }

    private function title(string $shortname, string $content): string
    {
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }
        return mb_convert_case(str_replace('_', ' ', $shortname), MB_CASE_TITLE, 'UTF-8');
    }
}
