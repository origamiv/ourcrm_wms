<?php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Spatie\Valuestore\Valuestore;


/**
 * Get the current module.
 */
function module(): string
{
    return config('app.module.name');
}

function setting($name, $module='Profit')
{
    $setting_from_env=env(strtoupper($name), null);

    $path=base_path('settings.json');
    $valuestore = Valuestore::make($path);
    $setting_from_valuestore=$valuestore->get($name);

    if (!empty($setting_from_valuestore)) {return $setting_from_valuestore;}
    elseif (\Illuminate\Support\Facades\Schema::hasTable('main.settings')) {
        $setting_from_db=DB::query()->from('main.settings')
            ->where('name' ,'=', 'tasks.koef_iter_val')
            ->where('module' ,'=', 'tasks')
            ->first()?->val;

        return $setting_from_db;
    }
    else {
        return $setting_from_env;
    }
}

function objectWalk($object, $key = 'key', $value = 'value'): array
{
    $arr = [];
    foreach ($object as $item) {
        $arr[$item->$key] = $item->$value;
    }
    return $arr;
}

function objectAssoc($object, $key = 'key'): array
{
    $obj=(array)$object;

    $arr = [];
    foreach ($obj as $item) {
        $arr[$item[$key]] = $item;
    }
    return $arr;
}

function addContent(\ZipArchive $zip, string $path)
{
    /** @var SplFileInfo[] $files */
    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(
            $path,
            \FilesystemIterator::FOLLOW_SYMLINKS
        ),
        \RecursiveIteratorIterator::SELF_FIRST
    );

    while ($iterator->valid()) {
        if (!$iterator->isDot()) {
            $filePath = $iterator->getPathName();
            $relativePath = substr($filePath, strlen($path));

            if (!$iterator->isDir()) {
                $zip->addFile($filePath, $relativePath);
            } else {
                if ($relativePath !== false) {
                    $zip->addEmptyDir($relativePath);
                }
            }
        }
        $iterator->next();
    }
}

function deleteDirectory($dir)
{
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }

    }

    return rmdir($dir);
}

function objectToArray(&$object)
{
    return @json_decode(json_encode($object), true);
}

function arrayFlip($object, $key = 'key'): array
{
    $arr = [];
    foreach ($object as $item) {
        $arr[$item[$key]] = $item;
    }
    return $arr;
}

function arrayFlips($object, $key = 'key'): array
{
    $arr = [];
    foreach ($object as $item) {
        $arr[$item[$key]][] = $item;
    }
    return $arr;
}

function avail_module($name)
{
    $modules = json_decode(file_get_contents(public_path('../modules_available.json')), true);
    return (!empty($modules[$name])) ? $modules[$name] : false;
}

function has_module($name)
{
    $modules = json_decode(file_get_contents(public_path('../modules_statuses.json')), true);
    return (!empty($modules[$name])) ? $modules[$name] : false;
}

function password($length = 32, $letters = true, $numbers = true, $symbols = true, $spaces = false)
{
    return (new \Illuminate\Support\Collection())
        ->when($letters, fn ($c) => $c->merge([
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k',
            'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
            'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G',
            'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
            'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        ]))
        ->when($numbers, fn ($c) => $c->merge([
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        ]))
        ->when($symbols, fn ($c) => $c->merge([
            '~', '!', '#', '$', '%', '^', '&', '*', '(', ')', '-',
            '_', '.', ',', '<', '>', '?', '/', '\\', '{', '}', '[',
            ']', '|', ':', ';',
        ]))
        ->when($spaces, fn ($c) => $c->merge([' ']))
        ->pipe(fn ($c) => \Illuminate\Support\Collection::times($length, fn () => $c[random_int(0, $c->count() - 1)]))
        ->implode('');
}

/**
 * Convert to boolean
 *
 * @param $booleable
 * @return boolean
 */
function to_boolean($booleable)
{
    return filter_var($booleable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
}

if (!function_exists('get_svg_icon')) {
    function get_svg_icon($path, $class = null, $svgClass = null)
    {
        if (strpos($path, 'media') === false) {
            $path = theme()->getMediaUrlPath() . $path;
        }

        $file_path = public_path($path);

        if (!file_exists($file_path)) {
            return '';
        }

        $svg_content = file_get_contents($file_path);

        if (empty($svg_content)) {
            return '';
        }

        $dom = new DOMDocument();
        $dom->loadXML($svg_content);

        // remove unwanted comments
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//comment()') as $comment) {
            $comment->parentNode->removeChild($comment);
        }

        // add class to svg
        if (!empty($svgClass)) {
            foreach ($dom->getElementsByTagName('svg') as $element) {
                $element->setAttribute('class', $svgClass);
            }
        }

        // remove unwanted tags
        $title = $dom->getElementsByTagName('title');
        if ($title['length']) {
            $dom->documentElement->removeChild($title[0]);
        }
        $desc = $dom->getElementsByTagName('desc');
        if ($desc['length']) {
            $dom->documentElement->removeChild($desc[0]);
        }
        $defs = $dom->getElementsByTagName('defs');
        if ($defs['length']) {
            $dom->documentElement->removeChild($defs[0]);
        }

        // remove unwanted id attribute in g tag
        $g = $dom->getElementsByTagName('g');
        foreach ($g as $el) {
            $el->removeAttribute('id');
        }
        $mask = $dom->getElementsByTagName('mask');
        foreach ($mask as $el) {
            $el->removeAttribute('id');
        }
        $rect = $dom->getElementsByTagName('rect');
        foreach ($rect as $el) {
            $el->removeAttribute('id');
        }
        $xpath = $dom->getElementsByTagName('path');
        foreach ($xpath as $el) {
            $el->removeAttribute('id');
        }
        $circle = $dom->getElementsByTagName('circle');
        foreach ($circle as $el) {
            $el->removeAttribute('id');
        }
        $use = $dom->getElementsByTagName('use');
        foreach ($use as $el) {
            $el->removeAttribute('id');
        }
        $polygon = $dom->getElementsByTagName('polygon');
        foreach ($polygon as $el) {
            $el->removeAttribute('id');
        }
        $ellipse = $dom->getElementsByTagName('ellipse');
        foreach ($ellipse as $el) {
            $el->removeAttribute('id');
        }

        $string = $dom->saveXML($dom->documentElement);

        // remove empty lines
        $string = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $string);

        $cls = array('svg-icon');

        if (!empty($class)) {
            $cls = array_merge($cls, explode(' ', $class));
        }

        $asd = explode('/media/', $path);
        if (isset($asd[1])) {
            $path = 'assets/media/' . $asd[1];
        }

        $output = "<!--begin::Svg Icon | path: $path-->\n";
        $output .= '<span class="' . implode(' ', $cls) . '">' . $string . '</span>';
        $output .= "\n<!--end::Svg Icon-->";

        return $output;
    }
}

if (!function_exists('theme')) {
    /**
     * Get the instance of Theme class core
     *
     * @return \App\Core\Adapters\Theme|\Illuminate\Contracts\Foundation\Application|mixed
     */
    function theme()
    {
        return app(\App\Core\Adapters\Theme::class);
    }
}

if (!function_exists('util')) {
    /**
     * Get the instance of Util class core
     *
     * @return \App\Core\Adapters\Util|\Illuminate\Contracts\Foundation\Application|mixed
     */
    function util()
    {
        return app(\App\Core\Adapters\Util::class);
    }
}

if (!function_exists('bootstrap')) {
    /**
     * Get the instance of Util class core
     *
     * @return \App\Core\Adapters\Util|\Illuminate\Contracts\Foundation\Application|mixed
     * @throws Throwable
     */
    function bootstrap()
    {
        $demo = ucwords(theme()->getDemo());
        $bootstrap = "\App\Core\Bootstraps\Bootstrap$demo";

        if (!class_exists($bootstrap)) {
            abort(404, 'Demo has not been set or ' . $bootstrap . ' file is not found.');
        }

        return app($bootstrap);
    }
}

if (!function_exists('assetCustom')) {
    /**
     * Get the asset path of RTL if this is an RTL request
     *
     * @param $path
     * @param null $secure
     *
     * @return string
     */
    function assetCustom($path)
    {
        // Include rtl css file
        if (isRTL()) {
            return asset(theme()->getDemo() . '/' . dirname($path) . '/' . basename($path, '.css') . '.rtl.css');
        }

        // Include dark style css file
        if (theme()->isDarkModeEnabled() && theme()->getCurrentMode() !== 'light') {
            $darkPath = str_replace('.bundle', '.' . theme()->getCurrentMode() . '.bundle', $path);
            if (file_exists(public_path(theme()->getDemo() . '/' . $darkPath))) {
                return asset(theme()->getDemo() . '/' . $darkPath);
            }
        }

        // Include default css file
        return asset(theme()->getDemo() . '/' . $path);
    }
}

if (!function_exists('isRTL')) {
    /**
     * Check if the request has RTL param
     *
     * @return bool
     */
    function isRTL()
    {
        return isset($_REQUEST['rtl']) && $_REQUEST['rtl'] || (isset($_COOKIE['rtl']) && $_COOKIE['rtl']);
    }
}

if (!function_exists('preloadCss')) {
    /**
     * Preload CSS file
     *
     * @return bool
     */
    function preloadCss($url)
    {
        return '<link rel="preload" href="' . $url . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" type="text/css"><noscript><link rel="stylesheet" href="' . $url . '"></noscript>';
    }
}

if (!function_exists('isDarkSidebar')) {
    function isDarkSidebar()
    {
        if (isset($_COOKIE['layout'])) {
            if ($_COOKIE['layout'] === 'dark-sidebar') {
                return true;
            }
            if ($_COOKIE['layout'] === 'light-sidebar') {
                return false;
            }
        } else {
            return theme()->getOption('layout', 'aside/theme') === 'dark';
        }

        return true;
    }
}

function blankArray($array): array
{
    foreach ($array as $key => $value) {
        $array[$key] = '';
    }
    return $array;
}

function publicStorageUrl($url): string
{
    return str_replace('public/', 'storage/', $url);
}

if(!function_exists('mb_ucfirst')){
    function mb_ucfirst($string, $encoding = 'UTF-8')
    {
        $firstChar = mb_substr($string, 0, 1, $encoding);
        $then = mb_substr($string, 1, null, $encoding);
        return mb_strtoupper($firstChar, $encoding) . $then;
    }
}
