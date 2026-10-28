<?php

namespace App\Models\Accounts;

use App\Models\BaseModel;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Income extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id', 'id');
    }

    public function head()
    {
        return $this->belongsTo(AccountHead::class, 'income_head', 'id');
    }
}
