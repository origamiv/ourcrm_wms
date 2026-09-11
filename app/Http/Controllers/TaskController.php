<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Http\BaseApiController;
use App\Models\Task;
use App\Models\User;
use App\Services\EntitySyncService;
use App\Services\AcceptanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class TaskController extends BaseApiController
{
    private const FIELDS=['name','shortname','client_id','task_type_id','status_id','planned_at','started_at','completed_at','charged_at','charged_sum','confirmed_at','comment','internal_comment','priority_id','src','order_id','warehouse_id','user_id','created_by_user_id','fact_count','status'];
    public function store(Request $request, EntitySyncService $sync, AcceptanceService $acceptances) { return response()->json(['data'=>$this->save($request,$sync,null,$acceptances)],201); }
    public function update(Request $request, string $id, EntitySyncService $sync, AcceptanceService $acceptances) { return response()->json(['data'=>$this->save($request,$sync,$id,$acceptances)]); }
    public function destroy(Request $request, string $id, EntitySyncService $sync) { $request->merge(['version'=>$request->input('version')]); $task=Task::withTrashed()->visibleTo($request->user()->tenant_id)->findOrFail($id); $this->checkVersion($sync,$request,$id); $task->delete(); return response()->json(['data'=>$sync->current(Task::class,$request->user()->tenant_id,$id)]); }
    private function save(Request $request, EntitySyncService $sync, ?string $id=null, ?AcceptanceService $acceptances=null): array { $data=$request->only(self::FIELDS); if(!$id && empty($data['name'])) abort(422,'Название обязательно.'); if($id) $this->checkVersion($sync,$request,$id); $tenant=$request->user()->tenant_id; if(array_key_exists('user_id',$data) && $data['user_id'] !== null && !User::visibleTo($tenant)->whereKey($data['user_id'])->exists()) abort(422,'Ответственный пользователь недоступен.'); unset($data['created_by_user_id']); $sync->prepareWrite($tenant,Task::class,$id); $task=$id?Task::withTrashed()->visibleTo($tenant)->findOrFail($id):new Task; if($id && $task->trashed()) abort(422,'Задача удалена.'); if(!$id)$data['created_by_user_id']=$request->user()->id; $task->forceFill($data); if(!$id)$task->tenant_id=$tenant; $task->save(); $acceptances?->createForTaskIfNeeded($task,$tenant); return $sync->current(Task::class,$tenant,$task->id); }
    private function checkVersion(EntitySyncService $sync, Request $request, string $id): void { $version=(string)$request->input('version'); if($version==='' || !hash_equals((string)$sync->current(Task::class,$request->user()->tenant_id,$id)['version'],$version)) abort(409,'Запись уже изменена.'); }
}
