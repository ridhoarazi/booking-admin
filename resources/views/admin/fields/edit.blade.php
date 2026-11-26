@extends('layouts.admin')

@section('title', 'Ubah Lapangan &middot; Admin')
@section('heading', 'Ubah Lapangan')

@section('content')
<div class="card" style="max-width:720px;">
    <form method="POST" action="{{ route('admin.fields.update', $field) }}" enctype="multipart/form-data" class="form-grid">
        @csrf @method('PUT')

        <div class="field">
            <label for="name">Nama Lapangan</label>
            <input id="name" type="text" name="name" value="{{ old('name', $field->name) }}" required>
            @error('name') <div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="slug">Slug (URL)</label>
            <input id="slug" type="text" name="slug" value="{{ old('slug', $field->slug) }}" required>
            @error('slug') <div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="sport_category_id">Kategori Olahraga</label>
            <select id="sport_category_id" name="sport_category_id" required>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('sport_category_id', $field->sport_category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            @error('sport_category_id') <div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="location">Lokasi</label>
            <input id="location" type="text" name="location" value="{{ old('location', $field->location) }}" required>
            @error('location') <div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description">{{ old('description', $field->description) }}</textarea>
        </div>

        <div class="field">
            <label for="price_per_hour">Harga Sewa per Jam</label>
            <input id="price_per_hour" type="number" name="price_per_hour" value="{{ old('price_per_hour', $field->price_per_hour) }}" required min="0">
            @error('price_per_hour') <div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="image">Ganti Foto Lapangan</label>
            <input id="image" type="file" name="image" accept="image/*">
            @if ($field->image_path)
                <div class="hint" style="margin-top:6px;">Saat ini: <a href="{{ Storage::url($field->image_path) }}" target="_blank">lihat foto</a> (kosongkan jika tidak diubah)</div>
            @endif
            <div class="hint">JPG / PNG / WebP, maksimal 2 MB.</div>
            @error('image') <div class="error">{{ $message }}</div>@enderror
        </div>

        <label class="check">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $field->is_active))> Lapangan aktif
        </label>

        <div class="form-actions">
            <a class="btn" href="{{ route('admin.fields.index') }}">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection
