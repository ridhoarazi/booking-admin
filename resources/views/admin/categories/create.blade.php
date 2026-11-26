@extends('layouts.admin')

@section('title', 'Tambah Kategori &middot; Admin')
@section('heading', 'Tambah Kategori Olahraga')

@section('content')
<div class="card" style="max-width:480px;">
    <form method="POST" action="{{ route('admin.categories.store') }}" class="form-grid">
        @csrf

        <div class="field">
            <label for="name">Nama Kategori</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Futsal">
            @error('name') <div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="icon">Nama Ikon (opsional)</label>
            <input id="icon" type="text" name="icon" value="{{ old('icon') }}" placeholder="Contoh: sports_tennis">
            <div class="hint">Nama ikon dari Material Symbols.</div>
            @error('icon') <div class="error">{{ $message }}</div>@enderror
        </div>

        <label class="check">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Aktif
        </label>

        <div class="form-actions">
            <a class="btn" href="{{ route('admin.categories.index') }}">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection
