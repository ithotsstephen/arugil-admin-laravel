@extends('admin.layout')

@section('content')
<div class="header"><h2>Mobile Users</h2></div>
<table>
    <thead><tr><th>ID</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Created</th></tr></thead>
    <tbody>
        @foreach($users as $u)
            <tr>
                <td>{{ $u->id }}</td>
                <td>{{ $u->full_name }}</td>
                <td>{{ $u->email }}</td>
                <td>{{ $u->phone }}</td>
                <td>{{ $u->created_at }}</td>
            </tr>
        @endforeach
    </tbody>
    </table>

    {{ $users->links() }}

@endsection
