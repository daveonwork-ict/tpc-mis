<?php

namespace App\Http\Controllers;

use App\Enums\Settings;
use App\Models\StudentInfo\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\MainApp\Entities\School;
use Illuminate\Support\Facades\Config;
use Modules\MainApp\Entities\Subscription;

class SubscriptionController extends Controller
{
    public function index(){

        $data['title']              = ___('common.Subscription');
        $data['subscriptions']      = Subscription::orderBy('id', 'desc')->paginate(Settings::PAGINATE);
        $data['activeSubscription'] = Subscription::where('status', 1)->first();
        $data['totalStudents']      = Student::count();
        return view('backend.subscriptions', compact('data'));
    }
}
