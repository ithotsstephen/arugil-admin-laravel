@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Categories</h2>
        <p class="muted">Main and sub-categories.</p>
    </div>
</div>

@if(session('status'))
    <div class="status">{{ session('status') }}</div>
@endif

<div class="card" style="margin-bottom: 16px;">
    <form method="POST" action="{{ route('admin.categories.store') }}">
        @csrf
        <div class="filters">
            <input type="text" name="name" placeholder="Category name" required>
            <input type="text" name="icon" placeholder="Icon URL">
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
        <th>Name</th>
        <th>Icon</th>
        <th>Parent</th>
        <th>Sort Order</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    @forelse($categories as $category)
        <tr style="background: #f9fafb;">
            <td><strong>{{ $category->name }}</strong></td>
            <td>{{ $category->icon }}</td>
            <td>—</td>
            <td>{{ $category->sort_order }}</td>
            <td class="actions">
                <button class="btn" onclick="editCategory({{ $category->id }}, '{{ $category->name }}', '{{ $category->icon }}', {{ $category->sort_order }})">Edit</button>
                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button class="btn" type="submit" onclick="return confirm('Delete this category?')">Delete</button>
                </form>
            </td>
        </tr>
        @foreach($category->children as $child)
            <tr>
                <td style="padding-left: 30px;">↳ {{ $child->name }}</td>
                <td>{{ $child->icon }}</td>
                <td>{{ $category->name }}</td>
                <td>{{ $child->sort_order }}</td>
                <td class="actions">
                    <button class="btn" onclick="editCategory({{ $child->id }}, '{{ $child->name }}', '{{ $child->icon }}', {{ $child->sort_order }}, {{ $child->parent_id }})">Edit</button>
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
function editCategory(id, name, icon, sort, parent = null) {
    document.querySelector('input[name="name"]').value = name;
    document.querySelector('input[name="icon"]').value = icon || '';
    document.querySelector('input[name="sort_order"]').value = sort;
    if (parent) {
        document.querySelector('select[name="parent_id"]').value = parent;
    }
    const form = document.querySelector('form');
    form.action = '/admin/categories/' + id;
    const method = document.createElement('input');
    method.type = 'hidden';
    method.name = '_method';
    method.value = 'PUT';
    if (!form.querySelector('input[name="_method"]')) {
        form.appendChild(method);
    }
    document.querySelector('.btn-primary').textContent = 'Update';
}
</script>
@endsection
