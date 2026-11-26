@extends('layouts.admin')

@section('title', 'Tambah Jadwal &middot; Admin')
@section('heading', 'Tambah Jadwal Operasional')

@section('content')
<div class="card" style="max-width:560px;">
    <form method="POST" action="{{ route('admin.fields.schedules.store', $field) }}" class="form-grid">
        @csrf

        <div class="field">
            <label for="date">Tanggal</label>
            <input id="date" type="date" name="date" value="{{ old('date') }}" required min="{{ now()->toDateString() }}">
            @error('date') <div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="open_time">Waktu Buka</label>
            <input id="open_time" type="time" name="open_time" value="{{ old('open_time') }}" required>
            @error('open_time') <div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="close_time">Waktu Tutup</label>
            <input id="close_time" type="time" name="close_time" value="{{ old('close_time') }}" required>
            @error('close_time') <div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label class="check">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Jadwal aktif
            </label>
            <div class="hint">Slot waktu akan dibuat otomatis per jam setelah disimpan.</div>
        </div>

        <div class="form-actions">
            <a class="btn" href="{{ route('admin.fields.schedules.index', $field) }}">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection
