@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Categories</h2>
        <p class="muted">Main and sub-categories.</p>
    </div>
</div>

<div style="margin-bottom:12px;display:flex;align-items:center;gap:12px;">
    <button id="saveOrderBtn" class="btn btn-primary" disabled>Save Order</button>
    <span id="orderStatus" style="color:#b45309;display:none;font-weight:600">Unsaved changes</span>
    <span id="orderSaved" style="color:#15803d;display:none;font-weight:600">Saved</span>
</div>

<style>
    .drag-handle { cursor: grab; width: 28px; text-align: center; }
    .drag-handle:active { cursor: grabbing; }
    tr.dragging { opacity: 0.6; }
    .toast {
        position: fixed;
        right: 20px;
        bottom: 20px;
        background: #111827;
        color: #fff;
        padding: 10px 14px;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        font-weight:600;
        display:none;
        z-index: 1200;
    }
    .toast.success { background: #065f46; }
    .toast.error { background: #7f1d1d; }
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

<div id="orderToast" class="toast" role="status" aria-live="polite"></div>

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
(function () {
    const tbody = document.getElementById('categoriesTbody');
    const saveBtn = document.getElementById('saveOrderBtn');
    const statusEl = document.getElementById('orderStatus');
    let dragGroup = [];
    let dirty = false;

    const savedEl = document.getElementById('orderSaved');

    function markDirty(v = true) {
        dirty = v;
        if (dirty) {
            saveBtn.disabled = false;
            statusEl.style.display = 'inline';
            if (savedEl) savedEl.style.display = 'none';
        } else {
            saveBtn.disabled = true;
            statusEl.style.display = 'none';
            if (savedEl) savedEl.style.display = 'none';
        }
    }

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
            parent.insertBefore(dragGroup[0], tr.nextSibling);
            for (let i = 1; i < dragGroup.length; i++) {
                parent.insertBefore(dragGroup[i], dragGroup[i-1].nextSibling);
            }
        } else {
            parent.insertBefore(dragGroup[0], tr);
            for (let i = 1; i < dragGroup.length; i++) {
                parent.insertBefore(dragGroup[i], dragGroup[i-1].nextSibling);
            }
        }
        // Mark as dirty immediately when DOM order changes during drag.
        // Some browsers may not reliably fire `drop` — enable Save proactively.
        markDirty(true);
    });

    tbody.addEventListener('drop', (e) => {
        e.preventDefault();
        dragGroup.forEach(r => r.classList.remove('dragging'));
        dragGroup = [];
        // mark as changed, let user click Save
        markDirty(true);
    });

    tbody.addEventListener('dragend', (e) => {
        dragGroup.forEach(r => r.classList.remove('dragging'));
        dragGroup = [];
    });

    saveBtn.addEventListener('click', (ev) => {
        ev.preventDefault();
        if (!dirty) return;
        sendOrder();
    });

    function sendOrder() {
        const rows = Array.from(tbody.querySelectorAll('tr'));

        // Recompute parent relationships based on DOM order.
        // We'll treat any row that appears as a top-level (no indentation) row
        // as a parent; subsequent rows until the next parent are its children.
        const order = [];
        let currentParent = null;
        rows.forEach(r => {
            const id = parseInt(r.dataset.id, 10);
            // A top-level parent row is indicated in the original markup by
            // an empty string for data-parent; use the DOM order instead of
            // relying on the possibly stale dataset value.
            const originallyParent = !r.dataset.parent || r.dataset.parent === '';
            if (originallyParent) {
                currentParent = id;
                // ensure dataset marks it as a parent
                r.dataset.parent = '';
                order.push({ id, parent_id: null });
            } else {
                // child row — attach to the most recent parent found in DOM
                const parent_id = currentParent ? currentParent : null;
                r.dataset.parent = parent_id ? String(parent_id) : '';
                order.push({ id, parent_id: parent_id });
            }
        });

        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : (document.querySelector('input[name="_token"]') ? document.querySelector('input[name="_token"]').value : '');

        // Disable further interaction while saving
        saveBtn.disabled = true;
        tbody.querySelectorAll('tr').forEach(r => r.setAttribute('draggable', 'false'));

        // prefer global toast if available
        const hasGlobalToast = (typeof showToast === 'function');
        const localToast = document.getElementById('orderToast');
        if (!hasGlobalToast && localToast) { localToast.style.display = 'none'; localToast.className = 'toast'; }

        fetch('{{ route('admin.categories.reorder') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ order })
        }).then(r => r.json()).then(data => {
            if (!data.success) {
                if (hasGlobalToast) { showToast('Failed to save order', 'error'); }
                else if (localToast) { localToast.textContent = 'Failed to save order'; localToast.classList.add('error'); localToast.style.display = 'block'; }
                // re-enable dragging
                tbody.querySelectorAll('tr').forEach(r => r.setAttribute('draggable', 'true'));
                saveBtn.disabled = false;
                return;
            }

            // Update sort_order column in the UI: assign index per parent group
            const groups = {};
            rows.forEach(r => {
                const pid = r.dataset.parent === '' ? null : (r.dataset.parent ? r.dataset.parent : null);
                if (!groups[pid]) groups[pid] = [];
                groups[pid].push(r);
            });

            Object.keys(groups).forEach(pid => {
                groups[pid].forEach((r, idx) => {
                    const td = r.querySelectorAll('td')[4];
                    if (td) td.textContent = idx;
                });
            });

            markDirty(false);
            // show saved confirmation briefly
            if (savedEl) {
                savedEl.style.display = 'inline';
                setTimeout(() => { savedEl.style.display = 'none'; }, 2000);
            }

            if (hasGlobalToast) { showToast('Order saved', 'success'); }
            else if (localToast) { localToast.textContent = 'Order saved'; localToast.classList.add('success'); localToast.style.display = 'block'; setTimeout(() => { localToast.style.display = 'none'; }, 2000); }

            // re-enable dragging
            tbody.querySelectorAll('tr').forEach(r => r.setAttribute('draggable', 'true'));
            saveBtn.disabled = false;
        }).catch(() => {
            if (hasGlobalToast) { showToast('Failed to save order', 'error'); }
            else if (localToast) { localToast.textContent = 'Failed to save order'; localToast.classList.add('error'); localToast.style.display = 'block'; }
            tbody.querySelectorAll('tr').forEach(r => r.setAttribute('draggable', 'true'));
            saveBtn.disabled = false;
        });
    }
})();
</script>
@endsection
