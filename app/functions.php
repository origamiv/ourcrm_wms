<?php
declare(strict_types=1);

use \Illuminate\Support\Facades\DB;

function date2(): string
{
    return Carbon\Carbon::now()->toDateTimeString();
}

function automate_params($automate, $param = null)
{
    $r = json_decode($automate['params'], true);
    if (!empty(($param))) {
        return $r[$param];
    }
    return $r;
}

function send_photos(array $args): array
{
//    $arr[] = "https://cab.intimlife18.com/media/cache/girl_photo_for_list/girl_photos/2022/04/11/6254757a41ef6252223778.jpg";
//    $arr[] = "https://cab.intimlife18.com/media/cache/girl_photo_for_list/girl_photos/2022/04/11/6254757a3ee1d330213156.jpg";
//    $arr[] = "https://cab.intimlife18.com/media/cache/girl_photo_for_list/girl_photos/2022/04/11/6254757a3dfe8008107582.jpg";
//    $arr[] = "https://cab.intimlife18.com/media/cache/girl_photo_1024x/girl_photos/2022/04/11/6254757a429a3435718545.jpg";

    $arr = automate_params($args['automate'], 'photos');
    $result = [];
    $allText = '';
    foreach ($arr as $img) {
        $allText = $allText . "\r\n" . $img;
    }
    $item = ['text' => ['value' => $allText]];
    $result[] = $item;

    $r['content'] = $result;
    return $r;
}

function book_appointment(array $args): array
{
    dump($args);
    $client_name=$args['client_name'];
    $duration = (int)$args['duration'];
    $time = \Illuminate\Support\Carbon::parse($args['date'] . ' ' . $args['time']);
    DB::table('messenger.events')->insert([
        'name' => $args['client_name'],
        'title' => $args['client_name'],
        'params'=>json_encode($args['assistant_chat'], JSON_UNESCAPED_UNICODE),
        'start' => $time->toDateTimeString(),
        'duration' => $duration,
        'end' => $time->addHours($duration)->toDateTimeString(),
        'created_at' => now()->toDateTimeString(),
    ]);

    $r = [];
    $myName = automate_params($args['automate'], 'name');
    $answer= "Готово, записала. $client_name, до встречи!";
    // $r['cancel_run'] = false;
    $r['answer'] = $answer;
    $r['content'] = [
        [
            'text' => [
                'value' => trim($answer),
            ]
        ],
    ];

    return $r;
}

function get_address(array $args): array
{
    //$address = '';
    $address = automate_params($args['automate'], 'address');

    $r = [];
    //$r['cancel_run'] = false;
    $r['answer'] = $address;

    $r['content'] = [
        [
            'text' => [
                'value' => trim($address)
            ]
        ],
    ];

    return $r;
}

function get_metro(array $args): array
{
    $address = automate_params($args['automate'], 'metro');

    $r = [];
    //$r['cancel_run'] = false;
    $r['answer'] = $address;

    $r['content'] = [
        [
            'text' => [
                'value' => trim($address)
            ]
        ],
    ];

    return $r;
}

function get_kv(array $args): array
{
    $address = automate_params($args['automate'], 'kvartira');

    $r = [];
    //$r['cancel_run'] = false;
    $r['answer'] = $address;

    $r['content'] = [
        [
            'text' => [
                'value' => trim($address)
            ]
        ],
    ];

    return $r;
}

function get_name(array $args): array
{
    $data = automate_params($args['automate'], 'name');

    $r = [];
    $r['content'] = [
        [
            'text' => [
                'value' => 'Меня зовут '.trim($data)
            ]
        ],
    ];

    return $r;
}

function tariffs(array $args): array
{
    $data = automate_params($args['automate'], 'tariffs');

    $r = [];
    $r['content'] = [
        [
            'text' => [
                'value' => trim($data)
            ]
        ],
    ];

    return $r;
}

function defaultFunc(): array
{
    $r['content'] = [
        [
            'text' => [
                'value' => 'Готово.'
            ]
        ],
    ];

    return $r;
}
