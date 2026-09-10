<?php

namespace App\Http\Controllers;

use OurCRM\BaseApiController;
use App\Models\DummyModel;
use Illuminate\Http\Request;
use App\Http\Requests\DummyRequest;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\ScopeBindings;

class DummyClass extends BaseApiController
{
    public $model = 'App\\Models\\DummyModel';
    public $requestClass = DummyRequest::class;
    public $resource = '{{ modelVariable }}';



    #[Post('/api/{{ modelVariable }}/list', name: '{{ modelVariable }}.index', middleware: 'auth:sanctum')]
    public function index(Request $request)
    {
        return parent::index($request);
    }


    #[Post('/api/{{ modelVariable }}/create', name: '{{ modelVariable }}.store', middleware: 'auth:sanctum')]
    public function store(Request $request, bool $isAuthNeeded = true)
    {
        return parent::store($request);
    }


    #[Put('/api/{{ modelVariable }}/{id}', name: '{{ modelVariable }}.update', middleware: 'auth:sanctum')]
    public function update($id, Request $request, bool $isAuthNeeded = true)
    {
        return parent::update($id, $request);
    }


    #[Get('/api/{{ modelVariable }}/{id}', name: '{{ modelVariable }}.show', middleware: 'auth:sanctum')]
    public function show($id, bool $isAuthNeeded = true)
    {
        return parent::show($id);
    }


    #[Delete('/api/{{ modelVariable }}/{id}/delete', name: '{{ modelVariable }}.delete', middleware: 'auth:sanctum')]
    public function delete($id)
    {
        return parent::delete($id);
    }


    #[Delete('/api/{{ modelVariable }}/{id}/destroy', name: '{{ modelVariable }}.destroy', middleware: 'auth:sanctum')]
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
