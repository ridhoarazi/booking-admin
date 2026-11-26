@extends('layouts.admin')

@section('title', 'Kategori &middot; Admin')
@section('heading', 'Kategori Olahraga')

@section('content')
<div class="page-head">
    <div>
        <h3>Kategori Olahraga</h3>
        <p class="muted">Kelola kategori olahraga yang tersedia untuk lapangan.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.categories.create') }}">+ Tambah Kategori</a>
</div>

@if ($categories->count())
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:8%">ID</th>
                    <th>Nama</th>
                    <th style="width:16%">Slug</th>
                    <th style="width:18%">Ikon</th>
                    <th style="width:10%">Lapangan</th>
                    <th style="width:10%">Status</th>
                    <th style="width:22%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($categories as $cat)
                    <tr>
                        <td>{{ $cat->id }}</td>
                        <td>{{ $cat->name }}</td>
                        <td style="font-family:monospace;font-size:12.5px">{{ $cat->slug }}</td>
                        <td>{{ $cat->icon ?? '-' }}</td>
                        <td>{{ $cat->fields_count }}</td>
                        <td>
                            <span class="badge {{ $cat->is_active ? 'badge-muted' : 'badge-cancelled' }}">
                                {{ $cat->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="actions">
                            <a class="btn btn-sm" href="{{ route('admin.categories.edit', $cat) }}">Ubah</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" style="display:inline;" onsubmit="return confirm('Hapus kategori ini? Lapangan yang pakai kategori ini tidak boleh dihapus.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <nav role="navigation">{{ $categories->links() }}</nav>
    </div>
@else
    <div class="empty">Belum ada kategori. Tambahkan yang pertama.</div>
@endif
@endsection
