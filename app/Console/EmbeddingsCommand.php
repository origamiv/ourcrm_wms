<?php

declare(strict_types=1);

namespace App\Console;

use App\Services\CailaService;
use GuzzleHttp\Client;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses;
use Illuminate\Console\Command;


final class EmbeddingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'embeddings';

    /**
     * The console command description.
     */
    protected $description = 'запускает автоматизацию по расписанию';
    public CailaService $cailaService;

    public function __construct(CailaService $cailaService)
    {
        $this->cailaService = $cailaService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info(now()->toDateTimeString() . ' Старт');

        $text='Я вам сказку сейчас расскажу
и придумаю прямо сейчас
Жил на свете старейшина Жук
Начинаем мы дети, рассказ

И вначале как водится было
Это самое слово - Любовь
по Вселенной энергией плыло
растекалось оно вновь и вновь

И летело и обретало
Форму жизнь и порядок вещей
Мир новый Любовь создавала
Отражаясь лишь в Свете Теней

Мы вернулись с тобою к Началу
мы вернулись к началу Времен
мы с тобою сейчас и узнаем
как мир этот был сотворен

И летела любовь и сверкала
сотворяя животных и птиц
И на пятый день осознала
Что у Мира не будет границ

Что границей Познанья есть Разум
Бесконечость лишь грань Бытия
и в Сознаньи есть Все и Сразу
На волшебной планете Земля

..так сверкала Любовь и летела
Проявляясь во всем и всегда
ей в какой-то момент захотелось
Посмотреть на саму на себя

И тогда наступил день Творенья
шестой день от начала Времен
появился Адам на мгновенье
Отражением Любови был он.

Но любовь посчитала что мало
Одного мужика на Земле
И она ему деву создала
Чтоб он начал в любви Бытие.

И в садах они с девой гуляли
Наслаждаясь Творением всем.
Ну а дальше как все мы все знаем
Наступили века Перемен.

....Бытие это странная штука
Завершал свой рассказ наш Жук
Вот поэтому, мои внуки,
Надо видеть любовь вокруг.';

        $data=$this->cailaService->post('/embeddings', [
            'model' => 'text-embedding-3-small', // или vectorizer-caila-roberta
            'input' => $text,
        ]);

        $embedding = $data['data'][0]['embedding'] ?? null;

        print_r($embedding);
        $this->info(now()->toDateTimeString() . ' завершение');
    }


}
