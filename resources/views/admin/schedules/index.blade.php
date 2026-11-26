@extends('layouts.admin')

@section('title', 'Jadwal &middot; Admin')
@section('heading', 'Jadwal Lapangan')

@section('content')
<div class="page-head">
    <div>
        <h3>Jadwal Operasional: {{ $field->name }}</h3>
        <p class="muted">Setiap jadwal otomatis menghasilkan slot waktu per jam sesuai rentang buka-tutup.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.fields.schedules.create', $field) }}">+ Tambah Jadwal</a>
    <a class="btn" href="{{ route('admin.fields.index') }}">Kembali ke Lapangan</a>
</div>

@if ($schedules->count())
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:16%">Tanggal</th>
                    <th style="width:18%">Waktu Operasi</th>
                    <th style="width:10%">Slot</th>
                    <th style="width:10%">Tersedia</th>
                    <th style="width:10%">Status</th>
                    <th style="width:16%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($schedules as $s)
                    <tr>
                        <td>{{ $s->date->format('d M Y') }}</td>
                        <td>{{ $s->open_time }} &ndash; {{ $s->close_time }}</td>
                        <td>{{ $s->slots_count }}</td>
                        <td>{{ $s->slots()->where('status', 'available')->count() }}</td>
                        <td>
                            <span class="badge {{ $s->is_active ? 'badge-muted' : 'badge-cancelled' }}">
                                {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="actions">
                            <form method="POST" action="{{ route('admin.schedules.destroy', $s) }}" style="display:inline;" onsubmit="return confirm('Hapus jadwal ini? Slot yang sudah dipesan tidak dapat dihapus.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <nav role="navigation">{{ $schedules->links() }}</nav>
    </div>
@else
    <div class="empty">Belum ada jadwal untuk lapangan ini.</div>
@endif
@endsection
