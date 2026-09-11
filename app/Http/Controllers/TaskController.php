<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Http\BaseApiController;
use App\Models\Task;
use App\Models\Good;
use App\Models\File;
use App\Models\User;
use App\Services\EntitySyncService;
use App\Services\AcceptanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Dompdf\Dompdf;
use Dompdf\Options;

final class TaskController extends BaseApiController
{
    private const FIELDS=['name','shortname','client_id','task_type_id','status_id','planned_at','started_at','completed_at','charged_at','charged_sum','confirmed_at','comment','internal_comment','priority_id','src','order_id','warehouse_id','user_id','created_by_user_id','fact_count','status'];
    public function store(Request $request, EntitySyncService $sync, AcceptanceService $acceptances) { return response()->json(['data'=>$this->save($request,$sync,null,$acceptances)],201); }
    public function update(Request $request, string $id, EntitySyncService $sync, AcceptanceService $acceptances) { return response()->json(['data'=>$this->save($request,$sync,$id,$acceptances)]); }
    public function destroy(Request $request, string $id, EntitySyncService $sync) { $request->merge(['version'=>$request->input('version')]); $task=Task::withTrashed()->visibleTo($request->user()->tenant_id)->findOrFail($id); $this->checkVersion($sync,$request,$id); $task->delete(); return response()->json(['data'=>$sync->current(Task::class,$request->user()->tenant_id,$id)]); }
    public function history(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $tenant = $request->user()->tenant_id;
        Task::query()->visibleTo($tenant)->findOrFail($id);
        $items = DB::table('public.entity_changes')->where('entity', Task::class)->where('tenant_id', $tenant)->where('entity_id', $id)->orderByDesc('revision')->limit(100)->get();
        return response()->json(['data' => $items->map(static fn (object $item): array => [
            'revision' => (string) $item->revision,
            'operation' => $item->operation,
            'data' => $item->data ? json_decode((string) $item->data, true) : null,
            'created_at' => $item->created_at,
        ])->values()]);
    }
    public function taskDocument(Request $request, string $id): \Illuminate\Http\Response
    {
        return $this->pdf($request, $id, false);
    }
    public function pickList(Request $request, string $id): \Illuminate\Http\Response
    {
        return $this->pdf($request, $id, true);
    }
    public function uploadFile(Request $request, string $id, EntitySyncService $sync): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate(['file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png']]);
        $tenant = $request->user()->tenant_id;
        $task = Task::query()->visibleTo($tenant)->findOrFail($id);
        $uploaded = $data['file'];
        abort_unless($uploaded instanceof UploadedFile && $uploaded->isValid(), 422, 'Файл не загружен.');
        $path = $uploaded->storeAs('tasks/'.$id, Str::uuid().'.'.$uploaded->getClientOriginalExtension(), 'public');
        return DB::transaction(function () use ($task, $uploaded, $path, $tenant, $sync): \Illuminate\Http\JsonResponse {
            $file = new File;
            $file->forceFill(['name' => $uploaded->getClientOriginalName(), 'path' => $path, 'category' => 'task', 'size' => $uploaded->getSize(), 'ext' => strtolower($uploaded->getClientOriginalExtension()), 'user_id' => request()->user()->id, 'status' => 1, 'tenant_id' => $tenant, 'is_s3' => 0])->save();
            $source = is_array($task->src) ? $task->src : [];
            $source['files'] = array_values(array_unique([...array_map('strval', is_array($source['files'] ?? null) ? $source['files'] : []), (string) $file->id]));
            $task->forceFill(['src' => $source])->save();
            return response()->json(['data' => $sync->current(Task::class, $tenant, $task->id), 'file' => $sync->current(File::class, $tenant, $file->id)], 201);
        });
    }
    private function save(Request $request, EntitySyncService $sync, ?string $id=null, ?AcceptanceService $acceptances=null): array { $data=$request->only(self::FIELDS); if(!$id && empty($data['name'])) abort(422,'Название обязательно.'); if($id) $this->checkVersion($sync,$request,$id); $tenant=$request->user()->tenant_id; if(array_key_exists('user_id',$data) && $data['user_id'] !== null && !User::visibleTo($tenant)->whereKey($data['user_id'])->exists()) abort(422,'Ответственный пользователь недоступен.'); unset($data['created_by_user_id']); $sync->prepareWrite($tenant,Task::class,$id); $task=$id?Task::withTrashed()->visibleTo($tenant)->findOrFail($id):new Task; if($id && $task->trashed()) abort(422,'Задача удалена.'); if(!$id)$data['created_by_user_id']=$request->user()->id; $task->forceFill($data); if(!$id)$task->tenant_id=$tenant; $task->save(); $acceptances?->createForTaskIfNeeded($task,$tenant); return $sync->current(Task::class,$tenant,$task->id); }
    private function checkVersion(EntitySyncService $sync, Request $request, string $id): void { $version=(string)$request->input('version'); if($version==='' || !hash_equals((string)$sync->current(Task::class,$request->user()->tenant_id,$id)['version'],$version)) abort(409,'Запись уже изменена.'); }
    private function pdf(Request $request, string $id, bool $pickList): \Illuminate\Http\Response
    {
        $tenant = $request->user()->tenant_id;
        $task = Task::query()->visibleTo($tenant)->findOrFail($id);
        $src = is_array($task->src) ? $task->src : [];
        $goodsIds = collect($src['goods'] ?? [])->filter(static fn ($value): bool => is_numeric($value))->map(static fn ($value): int => (int) $value)->values();
        $goods = Good::query()->visibleTo($tenant)->whereIn('id', $goodsIds)->get()->keyBy('id');
        $escape = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $rows = $goodsIds->map(function (int $goodId) use ($goods, $escape, $src): string {
            $good = $goods->get($goodId);
            return '<tr><td>'.$escape($goodId).'</td><td>'.$escape($good?->name ?: $good?->shortname ?: 'Товар не найден').'</td><td>'.$escape($src['pieces_count'] ?? 0).'</td></tr>';
        })->implode('');
        $title = $pickList ? 'Лист подбора: '.$task->name : 'Задача: '.$task->name;
        $html = '<!doctype html><html><head><meta charset="utf-8"><style>@page{margin:70px 36px 52px}body{font-family:DejaVu Sans,sans-serif;color:#17212b;font-size:10px}header{position:fixed;top:-45px;left:0;right:0;border-bottom:2px solid #1e892f;padding-bottom:8px;font-size:18px;font-weight:bold}footer{position:fixed;bottom:-32px;left:0;right:0;border-top:1px solid #d7dce3;padding-top:8px;color:#667085;font-size:9px}table{width:100%;border-collapse:collapse;margin-top:8px}th{background:#e1f3e7;font-weight:bold}th,td{border:1px solid #d7dce3;padding:7px;text-align:left}</style></head><body><header>'.$escape($title).'</header><footer>WMS · '.now()->format('d.m.Y H:i').'</footer><p>Задача №'.$escape($task->id).'</p><table><thead><tr><th>ID товара</th><th>Товар</th><th>Количество</th></tr></thead><tbody>'.$rows.'</tbody></table></body></html>';
        $options = new Options(); $options->setDefaultFont('DejaVu Sans');
        $pdf = new Dompdf($options); $pdf->loadHtml($html, 'UTF-8'); $pdf->setPaper('A4', 'portrait'); $pdf->render();
        $filename = $pickList ? 'pick_list_'.$task->id.'.pdf' : 'task_document_'.$task->id.'.pdf';
        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$filename.'"']);
    }
}
