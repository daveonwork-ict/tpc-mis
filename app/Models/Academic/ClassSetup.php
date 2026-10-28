<?php

namespace App\Models\Academic;

use App\Models\BaseModel;
use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClassSetup extends BaseModel
{
    use HasFactory;

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function session()
    {
        return $this->belongsTo(Session::class, 'session_id', 'id');
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'classes_id', 'id');
    }

    public function classSetupChildren()
    {
        return $this->belongsTo(ClassSetupChildren::class, 'id', 'class_setup_id');
    }

    public function classSetupChildrenAll()
    {
        return $this->hasMany(ClassSetupChildren::class, 'class_setup_id', 'id');
    }

    public function sections()
    {
        return $this->hasManyThrough(
            Section::class,
            ClassSetupChildren::class,
            'class_setup_id',   // Foreign key on ClassSetupChildren referencing ClassSetup
            'id',               // Local key on Section
            'id',               // Local key on ClassSetup
            'section_id'        // Foreign key on ClassSetupChildren referencing Section
        );
    }



    
}
