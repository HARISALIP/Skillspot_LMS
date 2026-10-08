<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPreallowedContact extends Model
{
    protected $table = 'vendor_preallowed_contacts';
    protected $fillable = ['vendor_id','contact','contact_type','added_by','used'];
    protected $casts = ['used' => 'boolean'];

    public function vendor() { return $this->belongsTo(Vendor::class); }
}
