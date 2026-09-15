<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { createEntitySync } from "../lib/entitySync";
import { http } from "../lib/http";
import ReferenceDirectory from "../Components/ReferenceDirectory.vue";
import TaskKanban from "../Components/TaskKanban.vue";
import { createUsers } from "../lib/users";
const page=usePage<any>(); const viewStorageKey=`tasks-view:${page.props.auth.id}:${page.props.auth.tenant_id}`; const kanban=ref(typeof localStorage !== "undefined" && localStorage.getItem(viewStorageKey)==="kanban"); watch(kanban,(value)=>{ if(typeof localStorage!=="undefined") localStorage.setItem(viewStorageKey,value?"kanban":"table"); }); const store=createEntitySync<any>(`${page.props.cacheVersion}:tasks-v5:${page.props.auth.id}:${page.props.auth.tenant_id}`,"tasks"); void store.start();
const statuses=createEntitySync<any>(`${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`,"task_statuses"); void statuses.start();
const clients=createEntitySync<any>(`${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`,"clients"); void clients.start();
const priorities=createEntitySync<any>(`${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`,"priorities"); void priorities.start();
const taskTypes=createEntitySync<any>(`${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`,"task_types"); void taskTypes.start();
const warehouses=createEntitySync<any>(`${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`,"warehouses"); void warehouses.start();
const users=createUsers(`${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`); void users.start();
const columns=computed(()=>statuses.rows.value.filter((s:any)=>!s.deleted_at).sort((a:any,b:any)=>a.id-b.id)); const moving=ref<string|null>(null);
async function move(task:any,status:any){ if(!task||String(task.status_id)===String(status.id)||moving.value)return; moving.value=String(task.id); try { const r=await http(`/web/fulfillment/tasks/${task.id}`,"PUT",{...task,status_id:status.id,version:task.version}); await store.apply(r.data); } finally { moving.value=null; } }
</script>
<template><Head title="Задачи"/><ReferenceDirectory entity="tasks" :task-view="kanban ? 'kanban' : 'table'" @toggle-task-view="kanban=!kanban"><template #task-kanban><TaskKanban :tasks="store.rows.value" :statuses="columns" :clients="clients.rows.value" :warehouses="warehouses.rows.value" :users="users.rows.value" :priorities="priorities.rows.value" :task-types="taskTypes.rows.value" @move="move" @open="(task) => router.visit(`/fulfillment/tasks/${task.id}/view`)" /></template></ReferenceDirectory></template>
<style scoped>
.kanban-toolbar{display:flex;align-items:center;gap:8px;margin-bottom:12px;color:#667085;font-size:12px}.kanban-toolbar button{padding:6px 12px;border:1px solid #d7e5db;border-radius:6px;background:#fff;color:#344054;cursor:pointer}.kanban-toolbar button.active{border-color:#1e892f;background:#e1f3e7;color:#1e892f;font-weight:600}.kanban-lanes{display:grid;gap:16px}.kanban-lane{padding:12px;border:1px solid #d7e5db;border-radius:10px;background:#fff}.kanban-lane h3{margin:0 0 10px;color:#0c1821;font-size:14px;font-weight:600}.kanban-columns{display:flex;flex:1;min-height:0;gap:16px;overflow:auto;padding:4px 0 12px;background:#f7faf8;border-radius:8px}
.kanban-column{background:#eef4f0;border:1px solid #d7e5db;border-radius:10px;padding:12px;min-width:250px;flex:1;min-height:100%;transition:background .15s,border-color .15s}
.kanban-column:has(article:active){background:#e4f2e9;border-color:#8cc9a0}
.kanban-column h2{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:13px;font-weight:600;color:#0c1821;margin:0 0 12px;padding:0 2px 10px;border-bottom:2px solid #a8d4a9}
.kanban-column h2 span{display:inline-grid;place-items:center;min-width:24px;height:22px;padding:0 6px;border-radius:12px;background:#d8efdf;color:#1e892f;font-size:11px;font-weight:600}
.kanban-column article{background:#fff;border:1px solid #e0e8e2;border-radius:9px;padding:13px;margin:8px 0;box-shadow:0 2px 5px rgb(16 24 40 / 8%);cursor:grab;display:grid;gap:9px;transition:transform .15s,box-shadow .15s}
.kanban-column article:hover{transform:translateY(-1px);box-shadow:0 5px 12px rgb(16 24 40 / 12%)}
.kanban-column article:active{cursor:grabbing;transform:rotate(1deg)}
.kanban-column article strong{font-size:13px;font-weight:600;color:#0c1821;overflow-wrap:anywhere}
.kanban-column small{color:#667085;font-size:11px;overflow-wrap:anywhere}
.task-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}.priority-badge{flex:0 0 auto;padding:3px 7px;border-radius:10px;background:#fff2cc;color:#9a6700;font-size:10px;white-space:nowrap}.task-card-meta{display:grid;gap:5px}.meta-icon{display:inline-grid;place-items:center;width:16px;margin-right:5px;color:#1e892f;font-size:10px}
.task-amounts{display:flex;justify-content:space-between;gap:8px;margin-top:3px;padding-top:8px;border-top:1px solid #edf0f3;color:#667085;font-size:11px}.task-amounts b{color:#0c1821;font-weight:600}
</style>
