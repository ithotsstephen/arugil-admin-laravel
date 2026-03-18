@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Categories</h2>
        <p class="muted">Main and sub-categories.</p>
    </div>
</div>

<style>
    .drag-handle { cursor: grab; width: 28px; text-align: center; }
    .drag-handle:active { cursor: grabbing; }
    tr.dragging { opacity: 0.6; }
</style>

@if(session('status'))
    <div class="status">{{ session('status') }}</div>
@endif

    <div class="card" style="margin-bottom: 16px;">
    <form id="categoryForm" method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="filters">
            <input type="hidden" name="category_id" value="">
            <input type="text" name="name" placeholder="Category name" required>
            <input type="text" name="icon" placeholder="Icon URL">
            <input type="file" name="icon_file" accept="image/svg+xml">
            <textarea name="icon_svg" placeholder="Or paste raw SVG here" style="min-height:40px"></textarea>
            <select name="parent_id">
                <option value="">Main category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <input type="number" name="sort_order" placeholder="Sort" min="0">
                <button class="btn btn-primary" type="submit">Add</button>
        </div>
    </form>
</div>

<table>
    <thead>
    <tr>
        <th></th>
        <th>Name</th>
        <th>Icon</th>
        <th>Parent</th>
        <th>Sort Order</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody id="categoriesTbody">
    @forelse($categories as $category)
        <tr draggable="true" data-id="{{ $category->id }}" data-parent="" style="background: #f9fafb;">
            <td class="drag-handle" draggable="true">☰</td>
            <td><strong>{{ $category->name }}</strong></td>
            <td>
                @if(!empty($category->icon_svg))
                    {!! $category->icon_svg !!}
                @elseif(!empty($category->icon))
                    {{ $category->icon }}
                @endif
            </td>
            <td>—</td>
            <td>{{ $category->sort_order }}</td>
            <td class="actions">
                <button class="btn" onclick='editCategory({{ $category->id }}, @json($category->name), @json($category->icon), @json($category->icon_svg), {{ $category->sort_order }})'>Edit</button>
                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button class="btn" type="submit" onclick="return confirm('Delete this category?')">Delete</button>
                </form>
            </td>
        </tr>
        @foreach($category->children as $child)
            <tr draggable="true" data-id="{{ $child->id }}" data-parent="{{ $category->id }}">
                <td class="drag-handle" draggable="true">☰</td>
                <td style="padding-left: 30px;">↳ {{ $child->name }}</td>
                <td>
                    @if(!empty($child->icon_svg))
                        {!! $child->icon_svg !!}
                    @elseif(!empty($child->icon))
                        {{ $child->icon }}
                    @endif
                </td>
                <td>{{ $category->name }}</td>
                <td>{{ $child->sort_order }}</td>
                <td class="actions">
                    <button class="btn" onclick='editCategory({{ $child->id }}, @json($child->name), @json($child->icon), @json($child->icon_svg), {{ $child->sort_order }}, {{ $child->parent_id }})'>Edit</button>
                    <form method="POST" action="{{ route('admin.categories.destroy', $child) }}" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn" type="submit" onclick="return confirm('Delete this subcategory?')">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
    @empty
        <tr>
            <td colspan="5">No categories yet.</td>
        </tr>
    @endforelse
    </tbody>
</table>

    <script>
function editCategory(id, name, icon, icon_svg, sort, parent = null) {
    const form = document.getElementById('categoryForm');
    form.querySelector('input[name="name"]').value = name || '';
    form.querySelector('input[name="icon"]').value = icon || '';
    form.querySelector('textarea[name="icon_svg"]').value = icon_svg || '';
    form.querySelector('input[name="sort_order"]').value = sort || 0;
    // Clear file input when editing
    const fileInput = form.querySelector('input[name="icon_file"]');
    if (fileInput) { fileInput.value = null; }
    // Always set parent select; use empty string for main categories
    form.querySelector('select[name="parent_id"]').value = parent ?? '';
    // Set hidden category_id so server can detect update if POST accidentally used
    const hiddenId = form.querySelector('input[name="category_id"]');
    if (hiddenId) { hiddenId.value = id; }
    form.action = '/admin/categories/' + id;
    let method = form.querySelector('input[name="_method"]');
    if (!method) {
        method = document.createElement('input');
        method.type = 'hidden';
        method.name = '_method';
        method.value = 'PUT';
        form.appendChild(method);
    } else {
        method.value = 'PUT';
    }
    form.querySelector('.btn-primary').textContent = 'Update';
}
</script>
<script>
// Drag and drop ordering — move parent together with its children
(() => {
    const tbody = document.getElementById('categoriesTbody');
    let dragGroup = [];

    function findGroupFor(tr) {
        const group = [tr];
        const id = tr.dataset.id;
        let next = tr.nextElementSibling;
        while (next && next.dataset && String(next.dataset.parent) === String(id)) {
            group.push(next);
            next = next.nextElementSibling;
        }
        return group;
    }

    tbody.addEventListener('dragstart', (e) => {
        const handle = e.target.closest('.drag-handle');
        if (!handle) { e.preventDefault(); return; }
        const tr = handle.closest('tr');
        dragGroup = findGroupFor(tr);
        dragGroup.forEach(r => r.classList.add('dragging'));
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', 'drag'); } catch (err) { /* some browsers require setData */ }
    });

    tbody.addEventListener('dragover', (e) => {
        e.preventDefault();
        const tr = e.target.closest('tr');
        if (!tr || dragGroup.length === 0) return;
        // if target is inside the dragged group, ignore
        if (dragGroup.includes(tr)) return;

        const rect = tr.getBoundingClientRect();
        const after = (e.clientY - rect.top) > (rect.height / 2);

        const parent = tr.parentNode;

        if (after) {
            // insert group after target
            parent.insertBefore(dragGroup[0], tr.nextSibling);
            // ensure all group rows follow in order
            for (let i = 1; i < dragGroup.length; i++) {
                parent.insertBefore(dragGroup[i], dragGroup[i-1].nextSibling);
            }
        } else {
            // insert group before target
            parent.insertBefore(dragGroup[0], tr);
            for (let i = 1; i < dragGroup.length; i++) {
                parent.insertBefore(dragGroup[i], dragGroup[i-1].nextSibling);
            }
        }

        // (no parent-change on drop; only reorder)
    });

    tbody.addEventListener('drop', (e) => {
        e.preventDefault();
        dragGroup.forEach(r => r.classList.remove('dragging'));
        dragGroup = [];
        sendOrder();
    });

    tbody.addEventListener('dragend', (e) => {
        dragGroup.forEach(r => r.classList.remove('dragging'));
        dragGroup = [];
    });

    function sendOrder() {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const order = rows.map(r => ({ id: parseInt(r.dataset.id, 10), parent_id: r.dataset.parent ? (r.dataset.parent === '' ? null : parseInt(r.dataset.parent, 10)) : null }));

        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : (document.querySelector('input[name="_token"]') ? document.querySelector('input[name="_token"]').value : '');

        fetch('{{ route('admin.categories.reorder') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ order })
        }).then(r => r.json()).then(data => {
            if (!data.success) {
                alert('Failed to save order');
            }
        }).catch(() => alert('Failed to save order'));
    }
})();
</script>
@endsection
