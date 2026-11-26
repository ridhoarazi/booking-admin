@extends('layouts.admin')

@section('title', 'Ubah Kategori &middot; Admin')
@section('heading', 'Ubah Kategori Olahraga')

@section('content')
<div class="card" style="max-width:480px;">
    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="form-grid">
        @csrf @method('PUT')

        <div class="field">
            <label for="name">Nama Kategori</label>
            <input id="name" type="text" name="name" value="{{ old('name', $category->name) }}" required>
            @error('name') <div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="icon">Nama Ikon (opsional)</label>
            <input id="icon" type="text" name="icon" value="{{ old('icon', $category->icon) }}" placeholder="Contoh: sports_tennis">
            <div class="hint">Nama ikon dari Material Symbols.</div>
            @error('icon') <div class="error">{{ $message }}</div>@enderror
        </div>

        <label class="check">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))> Aktif
        </label>

        <div class="form-actions">
            <a class="btn" href="{{ route('admin.categories.index') }}">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection
