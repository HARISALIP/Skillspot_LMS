<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use App\Services\MediaUploader;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    // ── Media manager page ─────────────────────────────────────────────
    public function index(Request $request)
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('uploads')) {
            $files   = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 24);
            $folders = collect([]);
            $stats   = ['total' => 0, 'images' => 0, 'videos' => 0, 'docs' => 0, 'size' => 0];
            return view('admin.media.index', compact('files','folders','stats'));
        }

        $query = Upload::with('uploader')->latest();

        if ($type   = $request->type)   $query->where('type',   $type);
        if ($folder = $request->folder) $query->where('folder', $folder);
        if ($search = $request->search) {
            $query->where('original_name','like',"%{$search}%");
        }

        $files   = $query->paginate(24);
        $folders = Upload::select('folder')->distinct()->pluck('folder')->sort()->values();
        $stats   = [
            'total'  => Upload::count(),
            'images' => Upload::where('type','image')->count(),
            'videos' => Upload::where('type','video')->count(),
            'docs'   => Upload::whereIn('type',['pdf','document'])->count(),
            'size'   => Upload::sum('size'),
        ];

        return view('admin.media.index', compact('files','folders','stats'));
    }

    // ── Upload endpoint (AJAX + regular form) ──────────────────────────
    public function upload(Request $request)
    {
        $request->validate([
            'file'      => ['required','file','max:512000'], // 500MB in KB
            'folder'    => ['nullable','string','max:60'],
            'is_public' => ['nullable','boolean'],
        ]);

        try {
            $upload = MediaUploader::upload(
                $request->file('file'),
                $request->input('folder','general'),
                $request->boolean('is_public', true)
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success'  => true,
                    'id'       => $upload->id,
                    'uuid'     => $upload->uuid,
                    'url'      => $upload->public_url,
                    'name'     => $upload->original_name,
                    'type'     => $upload->type,
                    'size'     => $upload->human_size,
                    'path'     => $upload->path,
                ]);
            }

            return back()->with('success', "File \"{$upload->original_name}\" uploaded ✅");

        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success'=>false,'error'=>$e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Get file URL (for picker) ──────────────────────────────────────
    public function url(Upload $upload)
    {
        return response()->json([
            'url'  => $upload->public_url,
            'name' => $upload->original_name,
            'type' => $upload->type,
        ]);
    }

    // ── Delete file ────────────────────────────────────────────────────
    public function destroy(Upload $upload)
    {
        MediaUploader::delete($upload);
        if (request()->expectsJson()) {
            return response()->json(['success'=>true]);
        }
        return back()->with('success','File deleted.');
    }

    // ── API: list files (for picker modal) ─────────────────────────────
    public function list(Request $request)
    {
        $query = Upload::latest();
        if ($type   = $request->type)   $query->where('type',   $type);
        if ($folder = $request->folder) $query->where('folder', $folder);
        if ($search = $request->search) $query->where('original_name','like',"%{$search}%");

        $files = $query->paginate(20);

        return response()->json([
            'data' => $files->map(fn($f) => [
                'id'           => $f->id,
                'uuid'         => $f->uuid,
                'name'         => $f->original_name,
                'url'          => $f->public_url,
                'type'         => $f->type,
                'size'         => $f->human_size,
                'icon'         => $f->icon,
                'extension'    => $f->extension,
                'created_at'   => $f->created_at?->diffForHumans(),
            ]),
            'next_page_url' => $files->nextPageUrl(),
            'total'         => $files->total(),
        ]);
    }
}
