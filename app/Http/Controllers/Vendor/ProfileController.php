<?php
namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    use ResolvesVendor;

    // ── Vendor Branding ───────────────────────────────────────────────
    public function index()
    {
        $vendor = $this->vendor();
        return view('vendor.profile', compact('vendor'));
    }

    public function update(Request $request)
    {
        $vendor = $this->vendor();
        $data = $request->validate([
            'brand_name'    => 'required|string|max:120',
            'tagline'       => 'nullable|string|max:200',
            'description'   => 'nullable|string',
            'email'         => 'nullable|email',
            'phone'         => 'nullable|string|max:20',
            'website'       => 'nullable|url',
            'primary_color' => 'nullable|string|max:20',
            'accent_color'  => 'nullable|string|max:20',
            'logo'          => 'nullable|image|max:2048',
            'banner_image'  => 'nullable|image|max:4096',
            'favicon'       => 'nullable|image|max:512',
        ]);

        foreach (['logo','banner_image','favicon'] as $field) {
            if ($request->hasFile($field)) {
                if ($vendor->$field) Storage::disk('public')->delete($vendor->$field);
                $data[$field] = $request->file($field)->store("vendors/{$field}s", 'public');
            }
        }
        $vendor->update($data);
        return back()->with('success', 'Branding updated ✅');
    }

    // ── Personal Profile (vendor user's own account) ──────────────────
    public function myProfile()
    {
        $vendor = $this->vendor();
        $user   = auth()->user();
        return view('vendor.my-profile', compact('vendor','user'));
    }

    public function updateMyProfile(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'name'  => 'required|string|max:120',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
        ]);
        $user->update($data);
        return back()->with('success', 'Profile updated ✅');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = auth()->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'Password changed ✅');
    }
}
