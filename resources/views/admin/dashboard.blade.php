@extends('layouts.admin')

@section('title', 'Dashboard &middot; Admin')
@section('heading', 'Dashboard Admin')

@section('content')
<div class="grid grid-4">
    <div class="card stat">
        <div class="label">Lapangan</div>
        <div class="value">{{ $stats['fields'] }}</div>
        <div class="hint">{{ $stats['active_fields'] }} aktif</div>
    </div>
    <div class="card stat">
        <div class="label">Kategori Olahraga</div>
        <div class="value">{{ $stats['categories'] }}</div>
        <div class="hint">terdaftar</div>
    </div>
    <div class="card stat">
        <div class="label">Booking Pending</div>
        <div class="value badge badge-pending">{{ $stats['pending'] }}</div>
        <div class="hint">menunggu konfirmasi</div>
    </div>
    <div class="card stat">
        <div class="label">Revenue Terkonfirmasi</div>
        <div class="value">Rp {{ number_format($stats['revenue'] ?? 0, 0, ',', '.') }}</div>
        <div class="hint">konfirmasi + selesai</div>
    </div>
</div>

<div class="page-head">
    <h3>Booking Terbaru</h3>
</div>

<div class="card">
    @if ($recent->count())
        <table class="table">
            <thead>
                <tr>
                    <th style="width:16%">Kode</th>
                    <th>Pengguna</th>
                    <th style="width:22%">Lapangan</th>
                    <th style="width:18%">Waktu</th>
                    <th>Status</th>
                    <th style="width:16%">Total</th>
                    <th style="width:20%">Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recent as $b)
                    <tr>
                        <td>{{ $b->booking_code }}</td>
                        <td>{{ $b->user->name }}</td>
                        <td>{{ $b->field->name }}</td>
                        <td>
                            {{ $b->slot->schedule->date->format('d M Y') }} @ {{ $b->slot->start_time }}<br>
                            <span style="font-size:12px;color:var(--muted)">{{ $b->slot->schedule->field->location }}</span>
                        </td>
                        <td>
                            <span class="badge badge-{{ str($b->status?->value)->replace('_', '-') }}">{{ $b->status ? str($b->status?->value)->upper() : 'PENDING' }}</span>
                        </td>
                        <td>Rp {{ number_format($b->total_price, 0, ',', '.') }}</td>
                        <td style="font-size:12px;color:var(--muted)">{{ $b->created_at->format('d M Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">Belum ada data booking.</div>
    @endif
</div>
@endsection
