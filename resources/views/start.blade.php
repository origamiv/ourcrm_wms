@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="table-wrap">
            <x-table
                title="Лог задач"
                :columns="[
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'name', 'label' => 'Название'],
            ['key' => 'status', 'label' => 'Статус'],
            ['key' => 'created_at', 'label' => 'Дата'],
        ]"
                :rows="$rows"
            />

            </div>
            <div class="table-footer">
                <div class="pager">
                    <button aria-label="Назад">‹</button>
                    <button class="active">1</button>
                    <button>2</button>
                    <button>3</button>
                    <button aria-label="Вперёд">›</button>
                </div>
                <div class="found">Найдено 799 результатов</div>
                <div>
                    <label for="perpage" class="muted" style="margin-right:6px">На странице</label>
                    <select id="perpage">
                        <option selected>10</option>
                        <option>25</option>
                        <option>50</option>
                        <option>100</option>
                    </select>
    </div>
            </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end">
            <button class="btn-primary">+ НОВЫЙ ПУНКТ</button>
        </div>
    </main>
</div>



<script>
    // Выделить все строки
    document.getElementById('checkAll').addEventListener('change', function(){
        document.querySelectorAll('.rowcheck').forEach(cb=>{cb.checked=this.checked});
    });

    // Переключение направления сортировки (визуально)
    document.querySelectorAll('.sort').forEach(s=>{
        s.addEventListener('click',()=>{
            const dir = s.getAttribute('data-dir');
            s.setAttribute('data-dir', dir==='asc' ? 'desc' : 'asc');
        });
    });

    // Демо: заполнение прогресса случайными значениями при клике на шестерёнку
    document.querySelectorAll('.iconbtn')[2]?.addEventListener('click',()=>{
        document.querySelectorAll('.progress').forEach(p=>{
            p.style.setProperty('--v', Math.floor(Math.random()*100)+'%');
        });
    });
</script>
</body>
</html>
@endsection
