<?php

declare(strict_types=1);

namespace OurCRM;

use App\Http\Traits\ResultTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;


#[
    OA\Info(
        title: 'Модуль Задачи (Tasks)',
        version: '1.0.0',
//        description: 'Swagger OpenApi Description',
//        contact: new OA\Contact(email: 'admin@admin.com'),
//        license: new OA\License(name: 'Apache 2.0', url: 'http://www.apache.org/licenses/LICENSE-2.0.html'),
    ),

    OA\SecurityScheme(
        securityScheme: 'bearerAuth',
        type: 'http',
        name: 'Authorization',
        in: 'header',
        scheme: 'bearer'
    ),

    OA\Server(url: L5_SWAGGER_CONST_HOST, description: 'Demo Server'),
]

class BaseApiController
{
    use ResultTrait;
    use SoftDeletes;
    public $model = 'App\\Models\\BaseModel';
    public $Model;
    public $query;
    public $requestClass = 'OurCRM\\BaseRequest';
    public $resource = 'base';
    public $request;
    public $table='';
    public $entity='';
    public $items;
    public $settings = [];
    public $error = null;


    public function __construct()
    {
        $this->query = $this->model::query();
        $this->Model = new $this->model;
        $this->entity = 'base';
        $this->table = (new $this->model)->getTable();
        //$this->exportService = $exportService;
        //$this->entity = __('entities.' . $this->resource);
    }

    private function applyFilter($query, $filter, $boolean = 'and')
    {
        if ($boolean == 'or') {
            if ($filter['op'] == '~') {
                //$query->orWhereJsonContains($filter['field'], $filter['val']);
                $path = explode(":", $filter['val']);
                $query->orWhere($filter['field'].'->'.$path[0], $path[1]);
            } elseif ($filter['op'] == 'in') {
                $query->orWhereIn($filter['field'], json_decode($filter['val']));
            } elseif ($filter['op'] == 'notin') {
                $query->orWhereNotIn($filter['field'], json_decode($filter['val']));
            } elseif ($filter['op'] == 'like') {
                $query->orWhere($filter['field'], $filter['op'], $filter['val'] . '%');
            } elseif ($filter['op'] == 'ilike') {
                $query->orWhere($filter['field'], $filter['op'], $filter['val'] . '%');
            } elseif ($filter['op'] == 'contain') {
                $query->orWhere($filter['field'], 'like', '%' . $filter['val'] . '%');
            } elseif ($filter['op'] == 'icontain') {
                $query->orWhere($filter['field'], 'ilike', '%' . $filter['val'] . '%');
            }  elseif ($filter['op'] == 'notcontain') {
                $query->where($filter['field'], 'not like', '%' . $filter['val'] . '%');
            } elseif ($filter['op'] == 'noticontain') {
                $query->where($filter['field'], 'not ilike', '%' . $filter['val'] . '%');
            }else {
                $query->orWhere($filter['field'], $filter['op'], $filter['val']);
            }
        } else {
            if ($filter['op'] == '~') {
                //$query->whereJsonContains($filter['field'], $filter['val']);
                $path = explode(":", $filter['val']);
                $query->where($filter['field'].'->'.$path[0], $path[1]);
            } elseif ($filter['op'] == 'in') {
                $query->whereIn($filter['field'], json_decode($filter['val']));
            } elseif ($filter['op'] == 'notin') {
                $query->whereNotIn($filter['field'], json_decode($filter['val']));
            } elseif ($filter['op'] == 'like') {
                $query->where($filter['field'], $filter['op'], $filter['val'] . '%');
            } elseif ($filter['op'] == 'ilike') {
                $query->where($filter['field'], $filter['op'], $filter['val'] . '%');
            } elseif ($filter['op'] == 'contain') {
                $query->where($filter['field'], 'like', '%' . $filter['val'] . '%');
            } elseif ($filter['op'] == 'icontain') {
                $query->where($filter['field'], 'ilike', '%' . $filter['val'] . '%');
            } elseif ($filter['op'] == 'notcontain') {
                $query->where($filter['field'], 'not like', '%' . $filter['val'] . '%');
            } elseif ($filter['op'] == 'noticontain') {
                $query->where($filter['field'], 'not ilike', '%' . $filter['val'] . '%');
            }

            else {
                $query->where($filter['field'], $filter['op'], $filter['val']);
            }
        }
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) {
        $perpage = (!empty($request->perpage)) ? $request->perpage : null;
        if (empty($perpage)) {
            $perpage=(!empty($request->size)) ? $request->size : 10;
        }
        $page = (!empty($request->page)) ? $request->page : 1;


        $query = $this->query;

        //dd($query);

        if(!empty($request->uniqField)){
            $uniqField = $query->distinct()->pluck($request->uniqField);
            $countItems = $uniqField->count();
            return $this->sendResponse($request,
                array_values($uniqField->toArray()),
                mb_ucfirst($this->entity.__(' index')),
                $countItems,
                $perpage,
                $page,
            );
        }
        //dd($request->filter);

        if (!empty($request->filter)) {
            $indx = 0;
            foreach ($request->filter as $filter_name => $filter) {

                //dd($filter);
                $indx++;
                if (
                    empty($filter['field']) ||
                    empty($filter['op']) ||
                    is_null($filter['val'])
                ) {
                    return $this->sendError(
                        __('validation.error'),
                        [__('validation.filled', ['attribute' => 'filter'])],
                        400
                    );
                }

                if (!empty($query->getModel()->first())) {
                    if (!in_array($filter['field'], array_keys($query->getModel()->first()->attributesToArray()))) {
                        return $this->sendError(
                            __('validation.error'),
                            [__('validation.not_exists', ['attribute' => $filter['field']])],
                            400
                        );
                    }
                }

                if (($request->filter_mode == 'or') && ($indx > 1)) {
                    $this->applyFilter($query, $filter, 'or');
                } else {
                    $this->applyFilter($query, $filter);
                }

            }
       }

        //dd($query);
        if (!empty($request->order)) {
            //dd($request->order);
            foreach ($request->order as $orderItem) {
                if (!empty($orderItem['by_lookup'])) {
                    continue;
                }
                $query = $query->orderBy($orderItem['field'], $orderItem['direction']);
            }
        }

        $sort_default = setting('sort_default');
        if (!empty($sort_default)) {
            $order = explode(',', $sort_default);
            if (count($order) > 1) {
                $query = $query->orderBy($order[0], $order[1]);
            }
        }

        if ($perpage == -1) {
            $countItems = $query->count();
            $this->items = $query->get();
        } else {
            $countItems = $query->count();
            $this->items = $query
                ->limit($perpage)
                ->offset(($page - 1) * $perpage)
                ->get();
        }

        $r = $this->sendResponse($request,
            array_values($this->items->toArray()),
            mb_ucfirst($this->entity.__(' index')),
            $countItems,
            $perpage,
            $page,
        );
        return $r;
    }

    /**
     * Display the specified resource.
     */
    public function show($id) {
        if ($this->query->where('id', '=', $id)->count() == 0) {
            return $this->sendError(__('not_exist.error'), 'Запись не существует', 422);
        }
        $item = $this->query->where('id', '=', $id)->first();
        if (is_null($item)) {
            return $this->sendError
            (
                __('validation.not_exists', ['attribute' => $this->entity]),
                $errorMessages = [__('validation.not_exists', ['attribute' => $this->entity])],
                $code = 404
            );
        }
        $request= new Request();

        return $this->sendResponse($request,$item->toArray(), __('Data loaded'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $this->request=$request;
        //dd($request->all());
        //$this->setLang();

//        if (!is_null(Auth::user()) && ($this->canAccess(Auth::user(), $this->resource . '.create') == false)) {
//            //  return $this->sendError(__('auth.error_access'), [__('auth.error_access_message')], 401);
//        }

        $validator = null;
        //$validator = $this->getValidatorFromOptions($request);

        $all = $request->all();
        $request = $this->requestClass::createFromBase($request);
        //dd($request);
        $request->request->replace($all);

        if (!$validator) {

            $validator = Validator::make(
                $request->all(),
                $request->createRules(),
                $request->messages()
            );
        }
        //dd($validator);
        try {
            $validator->validate();
            $validated = $validator->validated();

            $model = new $this->model;
            if (method_exists($model, 'setting')) {
                $model->setting($this->settings);
            }
            //dd($model);
            //dd($z->getFillable());
            if (isset(array_flip($model->getFillable())['owner'])) {
                $validated['owner'] = !is_null(Auth::user()) ? Auth::user()->id : null;
            }
            if ($this->beforeStore($request, $validated)) {
                $validated = $this->uploadFiles($request, $validated);
                //dd($validated);
                $this->item = $this->model::query()->create($validated);
                $item_id = $this->item->id;
                $this->item = $this->model::query()->where('id', '=', $item_id)->first();
                if (!empty($this->Model->cacheTags)) {
                    $this->Model::flushQueryCache();
                }
                $resutAfterStore=$this->afterStore($request);
                $array = (!empty($this->item)) ? $this->item->toArray() : [];
                if (!empty($request->redirect)) {
                    return redirect($request->redirect);
                }
                if($request->isItemNeeded){
                    return $this->item;
                }
                return $this->sendResponse($request,$array, mb_ucfirst($this->entity . __(' created')));
            } else {
                if (is_null($this->error)) {
                    return $this->sendError('Возникла непредвиденная ошибка');
                } else {
                    return $this->error;
                }
            }

        } catch (ValidationException $exception) {
            $errors = [];
            foreach ($validator->failed() as $field => $item) {
                foreach ($item as $rule => $values) {
                    $errors[$field][] = __(
                        'validation.'.strtolower($rule),
                        ['attribute' => $request->attributes()[$field]]
                    );
                }
            };
            return $this->sendError(__('validation.error'), $errors, 422);
        } catch (\Exception $exception) {
            dd($exception);
        }
    }

    public function beforeStore(Request $request, array $validated): int
    {
        return 1;
    }
    public function beforeUpdate(Request $request, array $validated): int
    {
        return 1;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id, Request $request) {
        $this->request=$request;
        $validator = null;
//        $validator = $this->getValidatorFromOptions($request, true);

        $redirect=$request->redirect;

        $oldRequest = $request;
        $request = $this->requestClass::createFromBase($request);

        if (!$validator) {
            $all = $oldRequest->all();
            unset($all['_method']);
            if (!empty($all['password'])) {
                $all['password'] = Hash::make($all['password']);
            }
            //dd($request);

            $validator = Validator::make(
                $all,
                $request->updateRules(),
                $request->messages()
            );
        }

        try {
            $validator->validate();
            $validated = $validator->validated();
            $this->query->where($this->table . '.id', '=', $id);
            if ($this->query->count() == 0) {
                return $this->sendError(__('not_exist.error'), 'Запись не существует', 422);
            }
            $validated = $this->uploadFiles($request, $validated);
            if ($this->beforeUpdate($oldRequest, $validated)) {
                $cnt = $this->query->update($validated);
                if (!empty($this->Model->cacheTags)) {
                    $this->Model::flushQueryCache();
                }

                $this->item = $this->query->first();
                $resultAfterUpdate=$this->afterUpdate($oldRequest);
                if (!empty($this->Model->cacheTags)) {
                    $this->Model::flushQueryCache();
                }
                $this->item = $this->query->first();
            }
            $array = (!empty($this->item)) ? $this->item->toArray() : [];
            if (!empty($redirect)) {
                return redirect($redirect);
            }
            return $this->sendResponse($request,$array, mb_ucfirst($this->entity . __(' updated')));

        } catch (ValidationException $exception) {
            $errors = [];
            foreach ($validator->failed() as $field => $item) {
                foreach ($item as $rule => $values) {
                    $errors[$field][] = __(
                        'validation.'.strtolower($rule),
                        ['attribute' => $request->attributes()[$field]]
                    );
                }
            };
            return $this->sendError(__('validation.error'), $errors, 422);
        } catch (\Exception $exception) {
            dd($exception);
        }
    }

    public function afterUpdate(Request $request): int
    {
        return 1;
    }

    public function afterStore(Request $request): int
    {
        return 1;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete($id) {
        //$this->setLang();
//        if ($this->canAccess(Auth::user(), $this->resource . '.delete') == false) {
//            return $this->sendError(__('auth.error_access'), [__('auth.error_access_message')], 403);
//        }

        $item = $this->query->find($id);
        if (is_null($item)) {
            return $this->sendError
            (
                __('validation.not_exists', ['attribute' => $this->entity]),
                $errorMessages = [__('validation.not_exists', ['attribute' => $this->entity])],
                $code = 404
            );
        }
        $itemData = $item->toArray();
        $item->delete();
        $request= new Request();

        return $this->sendResponse($request,$itemData, __('deleted soft'));
    }
    public function destroy($id){
        //$this->setLang();
//        if ($this->canAccess(Auth::user(), $this->resource . '.delete') == false) {
//            return $this->sendError(__('auth.error_access'), [__('auth.error_access_message')], 403);
//        }

        $item = $this->model::withTrashed()
            ->where($this->table . '.id', '=', $id)
            ->first();
        if (is_null($item)) {
            return $this->sendError
            (
                __('validation.not_exists', ['attribute' => $this->entity]),
                $errorMessages = [__('validation.not_exists', ['attribute' => $this->entity])],
                $code = 404
            );
        }
        $itemData = $item->toArray();
        $item->forceDelete();
        $request= new Request();

        return $this->sendResponse($request, $itemData, __('deleted hard'));
    }

    public function uploadFiles(Request $request, array $validated)
    {
        $files = [];
        $data=[];
        foreach ($request->files as $key => $filesUploaded) {
            foreach ($filesUploaded as $file) {
                $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
                $file->move(storage_path('app/public/uploads'), $filename);
                $files[] = $filename;
            }

        $data = $request->get($key) ?? [];
        $validated[$key] = array_merge($data,$files);
        }
        return $validated;
    }
}
