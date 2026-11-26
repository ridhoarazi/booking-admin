@extends('layouts.admin')

@section('title', 'Booking &middot; Admin')
@section('heading', 'Manajemen Booking')

@section('content')
<div class="toolbar">
    <form method="GET" action="{{ route('admin.bookings.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;">
        <div class="field" style="margin-bottom:0;">
            <label style="display:none">Status</label>
            <select name="status" style="padding:8px 10px;">
                <option value="">Semua status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>
        <div class="field" style="margin-bottom:0;">
            <label style="display:none">Tanggal</label>
            <input type="date" name="date" value="{{ request('date') }}" style="padding:8px 10px;">
        </div>
        <div class="field" style="margin-bottom:0;">
            <label style="display:none">Cari</label>
            <input type="text" name="q" placeholder="Cari kode/nama pengguna" value="{{ request('q') }}" style="padding:8px 10px;">
        </div>
        <button type="submit" class="btn btn-primary" style="margin:0;">Filter</button>
        <a class="btn" href="{{ route('admin.bookings.index') }}">Reset</a>
    </form>
</div>

@if ($bookings->count())
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:16%">Kode Booking</th>
                    <th>Pengguna</th>
                    <th style="width:20%">Lapangan</th>
                    <th style="width:20%">Waktu</th>
                    <th>Status</th>
                    <th style="width:14%">Total</th>
                    <th style="width:20%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bookings as $b)
                    <tr>
                        <td>{{ $b->booking_code }}</td>
                        <td>{{ $b->user->name }}</td>
                        <td>{{ $b->field->name }}</td>
                        <td>
                            @php($slots = $b->slots->isNotEmpty() ? $b->slots->sortBy(fn ($s) => $s->slot?->start_time) : collect())
                            @if ($slots->isNotEmpty())
                                {{ $slots->first()->slot->schedule->date->format('d M Y') }}
                                @ {{ $slots->map(fn ($s) => substr($s->slot->start_time, 0, 5))->implode(', ') }}
                                @if ($slots->count() > 1)
                                    <br><span style="font-size:12px;color:var(--muted)">{{ $slots->count() }} slot waktu</span>
                                @endif
                            @else
                                {{ $b->slot->schedule->date->format('d M Y') }} @ {{ $b->slot->start_time }}
                            @endif
                            <br><span style="font-size:12px;color:var(--muted)">{{ $b->field->location }}</span>
                        </td>
                        <td>
                            <span class="badge badge-{{ str($b->status?->value)->replace('_', '-') }}">{{ $b->status ? str($b->status?->value)->upper() : '-' }}</span>
                        </td>
                        <td>Rp {{ number_format($b->total_price, 0, ',', '.') }}</td>
                        <td class="actions">
                            @if ($b->status?->value === 'pending')
                                <form method="POST" action="{{ route('admin.bookings.confirm', $b) }}" style="display:inline-block;margin-right:6px;">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-warn">Konfirmasi</button>
                                </form>
                                <form method="POST" action="{{ route('admin.bookings.cancel', $b) }}" style="display:inline-block;">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-danger">Batalkan</button>
                                </form>
                            @elseif ($b->status?->value === 'confirmed')
                                <form method="POST" action="{{ route('admin.bookings.cancel', $b) }}" style="display:inline-block;">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-danger">Batalkan</button>
                                </form>
                            @else
                                <span style="font-size:12px;color:var(--muted)">Tidak dapat diubah</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <nav role="navigation">{{ $bookings->links() }}</nav>
    </div>
@else
    <div class="empty">Tidak ada data booking.</div>
@endif
@endsection
