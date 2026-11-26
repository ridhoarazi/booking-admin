@extends('layouts.admin')

@section('title', 'Lapangan &middot; Admin')
@section('heading', 'Manajemen Lapangan')

@section('content')
<div class="page-head">
    <div>
        <h3>Daftar Lapangan</h3>
        <p class="muted">Kelola data lapangan, kategori, lokasi, harga, dan foto.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.fields.create') }}">+ Tambah Lapangan</a>
</div>

@if ($fields->count())
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:10%">Foto</th>
                    <th>Nama</th>
                    <th style="width:16%">Kategori</th>
                    <th style="width:18%">Lokasi</th>
                    <th style="width:14%">Harga / Jam</th>
                    <th style="width:10%">Jadwal</th>
                    <th style="width:10%">Status</th>
                    <th style="width:18%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($fields as $f)
                    <tr>
                        <td>
                            @if ($f->image_path)
                                <img class="thumb" src="{{ Storage::url($f->image_path) }}" alt="{{ $f->name }}">
                            @else
                                <div class="thumb">{{ \Illuminate\Support\Str::limit($f->name, 2) }}</div>
                            @endif
                        </td>
                        <td><strong>{{ $f->name }}</strong></td>
                        <td>{{ $f->sportCategory->name }}</td>
                        <td style="font-size:12.5px">{{ $f->location }}</td>
                        <td>Rp {{ number_format($f->price_per_hour, 0, ',', '.') }}</td>
                        <td>{{ $f->schedules_count }}</td>
                        <td>
                            <span class="badge {{ $f->is_active ? 'badge-muted' : 'badge-cancelled' }}">
                                {{ $f->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="actions">
                            <a class="btn btn-sm" href="{{ route('admin.fields.edit', $f) }}">Ubah</a>
                            <form method="POST" action="{{ route('admin.fields.destroy', $f) }}" style="display:inline;" onsubmit="return confirm('Hapus lapangan ini? Lapangan dengan riwayat booking tidak dapat dihapus.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <nav role="navigation">{{ $fields->links() }}</nav>
    </div>
@else
    <div class="empty">Belum ada data lapangan.</div>
@endif
@endsection
