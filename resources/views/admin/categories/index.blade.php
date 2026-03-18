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
    <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="filters">
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
    document.querySelector('textarea[name="icon_svg"]').value = '';
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
