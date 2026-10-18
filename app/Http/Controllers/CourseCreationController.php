<?php

namespace App\Http\Controllers;

use App\Models\CourseCategory;
use App\Services\MediaUploader;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CourseCreationController extends Controller
{
    private function authorizeCreator(Request $request): void
    {
        $user = $request->user();
        abort_unless($user->hasRole(['admin', 'super-admin', 'teacher'])
            || in_array($user->instructorProfile?->status, ['pending', 'approved', 'rejected'], true), 403);
        abort_if($user->instructorProfile?->status === 'suspended' && !$user->hasRole(['admin', 'super-admin']), 403);
    }

    public function storeCategory(Request $request)
    {
        $this->authorizeCreator($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $name = trim($data['name']);
        if ($name === '') throw ValidationException::withMessages(['name' => 'Enter a category name.']);

        $category = CourseCategory::firstOrCreate(['name' => $name], [
            'is_active' => true,
            'order_col' => ((int) CourseCategory::max('order_col')) + 1,
        ]);

        return response()->json(['name' => $category->name], 201);
    }

    public function uploadMedia(Request $request)
    {
        $this->authorizeCreator($request);
        $request->validate(['file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,mp4,webm,mov', 'max:51200']]);

        try {
            $upload = MediaUploader::upload($request->file('file'), 'course-descriptions', true);
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['file' => 'The file could not be uploaded. Check storage settings or try again.']);
        }

        return response()->json(['url' => $upload->public_url], 201);
    }
}
