@extends('layouts.admin')

@section('title', 'Tambah Lapangan &middot; Admin')
@section('heading', 'Tambah Lapangan')

@section('content')
<div class="card" style="max-width:720px;">
    <form method="POST" action="{{ route('admin.fields.store') }}" enctype="multipart/form-data" class="form-grid">
        @csrf

        <div class="field">
            <label for="name">Nama Lapangan</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Lapangan Futsal A">
            @error('name') <div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="slug">Slug (URL)</label>
            <input id="slug" type="text" name="slug" value="{{ old('slug') }}" placeholder="Dikosongkan biar otomatis dari nama">
            <div class="hint">Contoh: lapangan-futsal-a</div>
            @error('slug') <div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="sport_category_id">Kategori Olahraga</label>
            <select id="sport_category_id" name="sport_category_id" required>
                <option value="">Pilih kategori</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            @error('sport_category_id') <div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="location">Lokasi</label>
            <input id="location" type="text" name="location" value="{{ old('location') }}" required placeholder="Contoh: Mall XP, Karawang">
            @error('location') <div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description">{{ old('description') }}</textarea>
        </div>

        <div class="field">
            <label for="price_per_hour">Harga Sewa per Jam</label>
            <input id="price_per_hour" type="number" name="price_per_hour" value="{{ old('price_per_hour') }}" required min="0" placeholder="Contoh: 150000">
            @error('price_per_hour') <div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="image">Foto Lapangan</label>
            <input id="image" type="file" name="image" accept="image/*">
            <div class="hint">JPG / PNG / WebP, maksimal 2 MB.</div>
            @error('image') <div class="error">{{ $message }}</div>@enderror
        </div>

        <label class="check">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Lapangan aktif
        </label>

        <div class="form-actions">
            <a class="btn" href="{{ route('admin.fields.index') }}">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection
