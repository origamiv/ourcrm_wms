<?php

namespace App\Console;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use App\Config\ConfigApi;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use PragmaRX\Yaml\Package\Yaml;

class ToolsCreateCommand extends Command
{
    protected $signature = 'tools:create-api {table}';

    protected $description = 'Создает API по конфигу';
    public $replace = [];


    public function prepare($fileName)
    {
        //dump($fileName);
        $z=explode('_', $fileName);
        if (count($z)==1) {return $fileName;}
        else{

            $fn='';
            foreach($z as $item){
                $fn=$fn.ucfirst($item);
            }
            //echo $fn;
            //dd($z);
            return $fn;
        }
    }
    public function control($type)
    {
        switch ($type) {
            case 'string':
                $str = 'text';
                break;
            case 'integer':
                $str = 'text';
                break;
            default:
                $str = $type;
                break;
        }
        return $str;
    }

    public function replace($file)
    {
        foreach ($this->replace as $key => $value) {
            $file = str_replace($key, $value, $file);
        }
        return $file;
    }

    public function handle()
    {
        $this->info(date('H:i:s').' Старт');

        $table=$this->argument('table'); //tasks
        $this->warn(date('H:i:s').' Создаем код для таблицы '.$table);

        $yaml=new Yaml;
        $config_table=$yaml->parseFile(app_path('Config/tables/'.$table.'.yaml'));
        if (empty($config_table['model'])) {return ;}
        $config_table['model']=$this->prepare($config_table['model']);
        $config = array_merge($config_table, ConfigApi::get($config_table));


//        unset($config['templates']);
//        unset($config['generate']);
//        unset($config['paths']);
//        dd(json_encode($config['fields']));

        $one=$config['descr']['one'];

        try {
            $data = $this->morpher($one);
            $many_r = $data['множественное']['Р']; //  $config['descr']['many_r'];
            $about = $data['П']; //$config['descr']['about'];
        }
        catch (\Exception $e) {
            $many_r = $config['descr']['many_r'] ?? 'сущностей';
            $about = $config['descr']['about'] ?? 'сущности';
        }

        $this->replace['DummyNamespace'] = 'Modules\\' . $config['module'] . '\\Models';
        $this->replace['DummyModelLower'] = strtolower($config['model']);
        $this->replace['{{ modelVariable }}'] = strtolower($config['model']);
        $this->replace['DummyTableLower'] = strtolower($config['table']);
        $this->replace['DummyTable'] = $config['table'];
        $this->replace['DummyModel'] = $config['model'];
        $this->replace['DummyRequest'] = $config['model'] . 'Request';
        $this->replace['{{ model }}'] = $config['model'];
        $this->replace['DummyClass'] = $config['model'];
        $this->replace['{{ module_lower }}'] = strtolower($config['module']);
        $this->replace['DummyModuleLower'] = strtolower($config['module']);
        $this->replace['DummyModule'] = $config['module'];
        $this->replace['{{ module }}'] = $config['module'];
        $this->replace['{descrOne}'] = $one;
        $this->replace['{descrManyR}'] = $many_r;
        //$this->replace['{descrWho}']=$config['descr']['many'];
        $this->replace['{descrAbout}'] = $about;
        $this->replace['//{$call}'] = $config['templates']['call'] . "\r\n" . '        //{$call}';
        // $this->replace['//{$menu}']=$config['templates']['menu']. "\r\n".'        //{$menu}';

        foreach ($config['generate'] as $generate => $stub) {
            if ($generate == 'model') {
                $this->replace['DummyClass'] = $config['model'];
                //----------- fillable for models -------------
                $fillableFields = $this->fillableForModels($config);
                $this->replace['//{$fillable}'] = implode("\r\n", $fillableFields);
                //----------- property for models -------------
                $properties = [];
                $templateProperty = $config['templates']['property'];
                foreach ($config['fields'] as $fieldName => $field) {
                    $property = $templateProperty;
                    $comment = (!empty($field['comment'])) ? $field['comment'] : '';
                    $property = str_replace('{type}', $field['type'], $property);
                    $property = str_replace('{name}', $fieldName, $property);
                    $property = str_replace('{comment}', $comment, $property);
                    $properties[] = $property;
                }
                $this->replace['{$property}'] = implode("\r\n", $properties);
            }
            if ($generate == 'seeder') {
                $this->replace['DummyClass'] = $config['model'] . 'Seeder';
            }
            if ($generate == 'request') {
                $this->replace['DummyClass'] = $config['model'] . 'Request';
            }
            if ($generate == 'controller') {
                $this->replace['DummyClass'] = $config['model'] . 'Controller';

            }
            if ($generate == 'migration') {



                //----------- fields for migration -------------
                $fieldsMigration = [];
                $templateField = $config['templates']['field'];
                foreach ($config['fields'] as $fieldName => $field) {
                    $additional = '';
                    if (!empty($field['nullable']) && $field['nullable']) {
                        $additional = $additional . "->nullable()";
                    }
                    if (!empty($field['comment'])) {
                        $additional = $additional . "->comment('{$field['comment']}')";
                    }
                    if (!empty($field['default'])) {
                        $additional = $additional . "->default('{$field['default']}')";
                    }
                    //dd($additional);
                    $fieldMigration = $templateField;
                    $fieldMigration = str_replace('{type}', $field['type'], $fieldMigration);
                    $fieldMigration = str_replace('{name}', $fieldName, $fieldMigration);
                    $fieldMigration = str_replace('{additional}', $additional, $fieldMigration);
                    $fieldsMigration[] = $fieldMigration;
                }
                //dd($fieldsMigration);
                $this->replace['//{$fields}'] = implode("\r\n", $fieldsMigration);
            }
            //----------- fields for rules requests -------------
            $rulesRequest = [];
            $templateRule = $config['templates']['rule'];
            foreach ($config['fields'] as $fieldName => $field) {
                $additional = '';
                $additional = (isset($field['nullable']) && !$field['nullable']) ? $additional . "required|" : $additional . "nullable|";
                //dd($additional);
                $ruleRequest = $templateRule;
                $ruleRequest = str_replace('{type}', $field['type'], $ruleRequest);
                $ruleRequest = str_replace('{name}', $fieldName, $ruleRequest);
                $ruleRequest = str_replace('{additional}', $additional, $ruleRequest);
                $rulesRequest[] = $ruleRequest;
            }
            //dd($rulesRequest);
            $this->replace['//{$rules}'] = implode("\r\n", $rulesRequest);
            //----------- fields for rules attributes -------------
            $attributes = [];
            $templateAttribute = $config['templates']['attribute'];
            foreach ($config['fields'] as $fieldName => $field) {
                $comment = (!empty($field['comment'])) ? $field['comment'] : $fieldName;
                $ruleAttribute = $templateAttribute;
                $ruleAttribute = str_replace('{type}', $field['type'], $ruleAttribute);
                $ruleAttribute = str_replace('{name}', $fieldName, $ruleAttribute);
                $ruleAttribute = str_replace('{comment}', $comment, $ruleAttribute);
                $attributes[] = $ruleAttribute;
            }
            //dd($rulesAttribute);
            $this->replace['//{$attributes}'] = implode("\r\n", $attributes);
            //---------------------------------------------
            //----------- fields for rules attributes -------------
            $attributes = [];
            $templateAttribute = $config['templates']['menu'];
            $templateField = $config['templates']['fieldMenu'];
            foreach ($config['fields'] as $fieldName => $field) {
                //$fieldAttr[]=str_replace('','',$templateField);
                $comment = (!empty($field['comment'])) ? $field['comment'] : $fieldName;
                $ruleAttribute = $templateField;
                $ruleAttribute = str_replace('{type}', $this->control($field['type']), $ruleAttribute);
                $ruleAttribute = str_replace('{name}', $fieldName, $ruleAttribute);
                $ruleAttribute = str_replace('{comment}', $comment, $ruleAttribute);
                $attributes[] = trim($ruleAttribute);
            }
            $fields = implode(',' . "\r\n", $attributes);
            $menus = str_replace('{fields}', $fields, $templateAttribute);
            //dd($rulesAttribute);

            $this->replace['//{$menu}'] = $menus . "\r\n" . '        //{$menu}';
            //---------------------------------------------
            $file = $this->replace(file_get_contents($stub));

            $filePath=$config['paths'][$generate];
            file_put_contents($filePath, $file);
            if ($generate == 'database_seeder') {
                file_put_contents($stub, $file);
            }
            if ($generate == 'menu_seeder') {
                file_put_contents($stub, $file);
            }
            Process::run('./vendor/bin/pint '.$filePath);
            //echo $f;
            //$this->warn("Код для таблицы $table сгенерирован");
        }
        $this->info(date('H:i:s').' Завершение');
    }

    /**
     * @param array $config
     * @return array
     */
    public function fillableForModels(array $config): array
    {
        $fillableFields = [];
        $templateFillable = $config['templates']['fillable'];
        foreach ($config['fields'] as $fieldName => $field) {
            $fillable = $templateFillable;
            $comment = (!empty($field['comment'])) ? $field['comment'] : '';
            $fillable = str_replace('{name}', $fieldName, $fillable);
            $fillable = str_replace('{comment}', $comment, $fillable);
            $fillableFields[] = $fillable;
        }
        return $fillableFields;
    }

    /**
     * @param mixed $one
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function morpher(mixed $one): mixed
    {
        $client = new Client();
        $response = $client->get('https://ws3.morpher.ru/russian/declension?s=' . $one);
        $body = $response->getBody();
        $content = $body->getContents();
        $data = simplexml_load_string($content);
        $data = objectToArray($data);
        return $data;
    }
}
