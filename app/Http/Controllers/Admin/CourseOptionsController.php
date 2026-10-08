<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseCategory;
use App\Models\CourseLevel;
use App\Models\CourseLanguage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseOptionsController extends Controller
{
    // ── CATEGORIES ────────────────────────────────────────────────────

    public function categories()
    {
        $items = CourseCategory::orderBy('order_col')->orderBy('name')->get();
        return view('admin.course-options.categories', compact('items'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:course_categories,name',
            'icon' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:255',
        ]);
        CourseCategory::create([
            'name'      => $request->name,
            'slug'      => Str::slug($request->name),
            'icon'      => $request->icon ?: '📚',
            'description'=> $request->description,
            'is_active' => 1,
            'order_col' => CourseCategory::max('order_col') + 1,
        ]);
        return back()->with('success', "Category \"{$request->name}\" added ✅");
    }

    public function updateCategory(Request $request, CourseCategory $category)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:course_categories,name,'.$category->id,
            'icon' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:255',
        ]);
        $category->update([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'icon'        => $request->icon ?: $category->icon,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
        ]);
        return back()->with('success', "Category updated ✅");
    }

    public function destroyCategory(CourseCategory $category)
    {
        $category->delete();
        return back()->with('success', "Category deleted.");
    }

    public function toggleCategory(CourseCategory $category)
    {
        $category->update(['is_active' => !$category->is_active]);
        return back()->with('success', $category->name.' '.($category->is_active ? 'enabled' : 'disabled').' ✅');
    }

    // ── LEVELS ────────────────────────────────────────────────────────

    public function levels()
    {
        $items = CourseLevel::orderBy('order_col')->get();
        return view('admin.course-options.levels', compact('items'));
    }

    public function storeLevel(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:60|unique:course_levels,name',
            'description' => 'nullable|string|max:255',
        ]);
        CourseLevel::create([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'description' => $request->description,
            'is_active'   => 1,
            'order_col'   => CourseLevel::max('order_col') + 1,
        ]);
        return back()->with('success', "Level \"{$request->name}\" added ✅");
    }

    public function updateLevel(Request $request, CourseLevel $level)
    {
        $request->validate([
            'name'        => 'required|string|max:60|unique:course_levels,name,'.$level->id,
            'description' => 'nullable|string|max:255',
        ]);
        $level->update([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
        ]);
        return back()->with('success', "Level updated ✅");
    }

    public function destroyLevel(CourseLevel $level)
    {
        $level->delete();
        return back()->with('success', "Level deleted.");
    }

    public function toggleLevel(CourseLevel $level)
    {
        $level->update(['is_active' => !$level->is_active]);
        return back()->with('success', $level->name.' '.($level->is_active ? 'enabled' : 'disabled').' ✅');
    }

    // ── LANGUAGES ─────────────────────────────────────────────────────

    public function languages()
    {
        $items = CourseLanguage::orderBy('order_col')->get();
        return view('admin.course-options.languages', compact('items'));
    }

    public function storeLanguage(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:60|unique:course_languages,name',
            'code' => 'required|string|max:10|unique:course_languages,code',
        ]);
        CourseLanguage::create([
            'name'      => $request->name,
            'code'      => strtolower($request->code),
            'is_active' => 1,
            'order_col' => CourseLanguage::max('order_col') + 1,
        ]);
        return back()->with('success', "Language \"{$request->name}\" added ✅");
    }

    public function updateLanguage(Request $request, CourseLanguage $language)
    {
        $request->validate([
            'name' => 'required|string|max:60|unique:course_languages,name,'.$language->id,
            'code' => 'required|string|max:10|unique:course_languages,code,'.$language->id,
        ]);
        $language->update([
            'name'      => $request->name,
            'code'      => strtolower($request->code),
            'is_active' => $request->boolean('is_active', true),
        ]);
        return back()->with('success', "Language updated ✅");
    }

    public function destroyLanguage(CourseLanguage $language)
    {
        $language->delete();
        return back()->with('success', "Language deleted.");
    }

    public function toggleLanguage(CourseLanguage $language)
    {
        $language->update(['is_active' => !$language->is_active]);
        return back()->with('success', $language->name.' '.($language->is_active ? 'enabled' : 'disabled').' ✅');
    }
}
