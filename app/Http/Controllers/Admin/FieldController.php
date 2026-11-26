<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FieldRequest;
use App\Models\Field;
use App\Models\SportCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FieldController extends Controller
{
    public function index(): View
    {
        $fields = Field::with('sportCategory')
            ->withCount('schedules')
            ->orderBy('name')
            ->paginate(10);

        return view('admin.fields.index', compact('fields'));
    }

    public function create(): View
    {
        $categories = SportCategory::orderBy('name')->get();

        return view('admin.fields.create', compact('categories'));
    }

    public function store(FieldRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? $data['name'];

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('fields', 'public');
        }

        unset($data['image']);

        Field::create($data);

        return redirect()->route('admin.fields.index')->with('success', 'Lapangan berhasil ditambahkan.');
    }

    public function edit(Field $field): View
    {
        $categories = SportCategory::orderBy('name')->get();

        return view('admin.fields.edit', compact('field', 'categories'));
    }

    public function update(FieldRequest $request, Field $field): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? $data['name'];

        if ($request->hasFile('image')) {
            $this->deleteImage($field);
            $data['image_path'] = $request->file('image')->store('fields', 'public');
        }

        unset($data['image']);

        $field->update($data);

        return redirect()->route('admin.fields.index')->with('success', 'Lapangan berhasil diperbarui.');
    }

    public function destroy(Field $field): RedirectResponse
    {
        if ($field->bookings()->exists()) {
            return back()->with('error', 'Lapangan sudah memiliki riwayat booking. Nonaktifkan saja agar tidak dihapus.');
        }

        $this->deleteImage($field);
        $field->delete();

        return redirect()->route('admin.fields.index')->with('success', 'Lapangan berhasil dihapus.');
    }

    private function deleteImage(Field $field): void
    {
        if ($field->image_path) {
            Storage::disk('public')->delete($field->image_path);
        }
    }
}
