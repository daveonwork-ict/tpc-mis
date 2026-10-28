<?php

use App\Enums\RoleEnum;
use App\Models\Academic\SubjectAssign;
use App\Models\Academic\SubjectAssignChildren;
use App\Models\Examination\ExaminationSettings;
use App\Models\Language;
use App\Models\Setting;
use App\Models\StudentInfo\SessionClassStudent;
use App\Models\StudentInfo\Student;
use App\Models\Subscription;
use App\Models\SystemNotification;
use App\Models\Upload;
use App\Models\WebsiteSetup\OnlineAdmissionSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Modules\Chat\Entities\ChatSetting;
use Modules\MainApp\Enums\PackagePaymentType;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Twilio\Rest\Client;

function getPagination($ITEM)
{
    return view('common.pagination', compact('ITEM'));
}

function setting($name, $branch_id = null)
{
    try {
        $cacheKey = "setting_{$branch_id}_{$name}";

        return Cache::rememberForever($cacheKey, function () use ($name, $branch_id) {
            $query = Setting::query();

            if ($branch_id) {
                $query->where('branch_id', $branch_id);
            }

            if ($name === 'currency_symbol') {
                // Fetch related currency_code first
                $currencyCode = (clone $query)->where('name', 'currency_code')->value('value');

                return $currencyCode ?? null;
            }

            return $query->where('name', $name)->value('value');
        });
    } catch (\Throwable $th) {
        return null;
    }
}

function settingLocale($name)
{
    if(!app_translate() ){
        return setting($name);
    }
    $setting_data = Setting::where('name', $name)->first();
    if ($setting_data) {
        return @$setting_data->defaultTranslate->value;
    }

    return null;
}

function examSetting($name)
{
    $setting_data = ExaminationSettings::where('name', $name)->where('session_id', setting('session'))->first();
    if ($setting_data) {
        return $setting_data->value;
    }

    return null;
}

function findDirectionOfLang()
{
    $locale = Session::get('locale', 'en');

    return Cache::rememberForever("language_direction_{$locale}", function () use ($locale) {
        return strtolower(Language::where('code', $locale)->value('direction') ?? '');
    });
}

// for menu active
if (! function_exists('set_menu')) {
    function set_menu(array $path, $active = 'mm-active')
    {
        foreach ($path as $route) {
            if (Route::currentRouteName() == $route) {
                return $active;
            }
        }

        return (request()->is($path)) ? $active : '';
        // return call_user_func_array('Request::is', (array) $path) ? $active : '';
    }
}

// for  submenu list item active
if (! function_exists('menu_active_by_route')) {
    function menu_active_by_route($route)
    {
        return request()->routeIs($route) ? 'mm-show' : 'in-active';
    }
}

// get upload path
if (! function_exists('uploadPath')) {
    function uploadPath($id)
    {
        $row = Upload::find($id);

        return $row->path;
    }
}

function ___($key = null, $replace = [], $locale = null)
{

    $input = explode('.', $key);
    $term = $input[1] ?? $key;
    $app_local = Session::get('locale') ?: 'en';
    try {
        if (str_contains($key, '.')) {
            [$file_name, $trans_key] = explode('.', $key, 2); // allow for multiple dots
            $file_path = base_path('lang/'.$app_local.'/'.$file_name.'.json');
            if (env('APP_SAAS')) {
                $tenancy = tenant() ? tenant()->id : null;
                $file_path = base_path('lang/'.$tenancy.'/'.$app_local.'/'.$file_name.'.json');
            }
        }
        $dir = dirname($file_path);
        if (! file_exists($dir)) {
            mkdir($dir, 0755, true); // recursive = true
        }

        if (! file_exists($file_path)) {
            file_put_contents($file_path, json_encode([]));
            chmod($file_path, 0755);
        }

        $file_data = json_decode(file_get_contents($file_path), true) ?? [];
        if (! array_key_exists($trans_key, $file_data)) {
            // Transform: replace underscores and convert to title case
            $default_value = ucwords(str_replace('_', ' ', $trans_key));
            $file_data[$trans_key] = $default_value;
            file_put_contents($file_path, json_encode($file_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return $file_data[$trans_key];
    } catch (\Exception $e) {
        info($e);

        return ucwords(str_replace('_', ' ', $term));
    }
}

// function ___($key = null, $replace = [], $locale = null)
// {

//     $input = explode('.', $key);
//     $file = $input[0];
//     $term = $input[1] ?? '';
//     $app_local = Session::get('locale') ? Session::get('locale') : 'en';
//     $file_path = base_path('lang/' . $app_local . '/' . $file . '.json');
//     $term = str_replace('_', ' ', $term);

//     if (!is_dir(dirname($file_path))) {
//         mkdir(dirname($file_path), 0777, true);
//     }
//     if (!file_exists($file_path)) {
//         $data = [];
//         file_put_contents($file_path, json_encode($data, JSON_UNESCAPED_UNICODE));
//     }

//     $jsonString = file_get_contents($file_path);
//     $data = json_decode($jsonString, true);

//     if (@$data[$term]) {
//         return $data[$term];
//     } else {
//         $data[$term] = $term;
//         file_put_contents($file_path, json_encode($data, JSON_UNESCAPED_UNICODE));
//     }

//     return $term;
// }

// global thumbnails
if (! function_exists('globalAsset')) {
    function globalAsset($path, $default_image = null)
    {
        if ($path == '') {
            return globalAsset(setting('light_logo'));
        } else {
            try {
                if (setting('file_system') == 's3' && Storage::disk('s3')->exists($path) && $path != '') {
                    return Storage::disk('s3')->url($path);
                } elseif (setting('file_system') == 'local' && file_exists(@$path)) {
                    return url($path);
                } else {
                    if ($default_image == null) {
                        return url('backend/uploads/default-images/user2.jpg');
                    } else {
                        return url("backend/uploads/default-images/$default_image");
                    }
                }
            } catch (\Exception $c) {
                return url("backend/uploads/default-images/$default_image");
            }
        }
    }
}

// Permission check
if (! function_exists('hasPermission')) {
    function hasPermission($keyword)
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Super Admin
        if ($user->role_id === 1) {
            return true;
        }

        // Role-based permissions (role 6 or 7)
        if (in_array($user->role_id, [6, 7]) &&
            in_array($keyword, $user->role->permissions ?? [])
        ) {
            return true;
        }

        // User-specific permissions
        return in_array($keyword, $user->permissions ?? []);
    }
}

// Date format
if (! function_exists('dateFormat')) {
    function dateFormat($keyword)
    {
        return date('d M Y', strtotime($keyword));
    }
}
if (! function_exists('timeFormat')) {
    function timeFormat($keyword)
    {
        return date('g:i A', strtotime($keyword));
    }
}
if (! function_exists('userTheme')) {
    function userTheme()
    {
        $session_theme = Session::get('user_theme');

        if (isset($session_theme)) {
            return $session_theme;
        } else {
            return 'default-theme';
        }
    }
}

if (! function_exists('leadingZero')) {
    function withLeadingZero($number)
    {

        // $strNumber = $number;
        // if(strlen($strNumber) < 10){
        //     return $strNumber;
        // }

        return $number;
    }
}

if (! function_exists('setEnvironmentValue')) {
    function setEnvironmentValue($envKey, $envValue)
    {
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);

        $str .= "\n"; // In case the searched variable is in the last line without \n
        $keyPosition = strpos($str, "{$envKey}=");
        $endOfLinePosition = strpos($str, PHP_EOL, $keyPosition);
        $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
        $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
        $str = substr($str, 0, -1);

        $fp = fopen($envFile, 'w');
        fwrite($fp, $str);
        fclose($fp);
    }
}

if (! function_exists('s3Upload')) {
    function s3Upload($directory, $file)
    {
        $directory = 'public/'.$directory;

        return Storage::disk('s3')->put($directory, $file, 'public');
    }
}

if (! function_exists('s3ObjectCheck')) {
    function s3ObjectCheck($path)
    {
        return Storage::disk('s3')->exists($path);
    }
}

if (! function_exists('include_route_files')) {
    /**
     * Loops through a folder and requires all PHP files
     * Searches sub-directories as well.
     */
    function include_route_files($folder)
    {
        try {
            $rdi = new RecursiveDirectoryIterator($folder);
            $it = new RecursiveIteratorIterator($rdi);

            while ($it->valid()) {
                if (! $it->isDot() && $it->isFile() && $it->isReadable() && $it->current()->getExtension() === 'php') {
                    require $it->key();
                }

                $it->next();
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    function getAllDaysInMonth($year, $month)
    {
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $days = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day);
            $days[] = $date->format('Y-m-d');
        }

        return $days;
    }

    function getSubdomainName()
    {
        $parsedUrl = parse_url(url()->full());
        $hostParts = explode('.', $parsedUrl['host']);

        return $hostParts;
    }
}

if (! function_exists('saasMiddleware')) {
    function saasMiddleware()
    {
        if (env('APP_SAAS')) {
            return [
                'web',
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
            ];
        }

        return [
            'web',
        ];
    }
}

if (! function_exists('saasApiMiddleware')) {
    function saasApiMiddleware()
    {

        if (env('APP_SAAS')) {
            return [
                'api',
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
            ];
        }

        return [
            'api',
        ];
    }
}

function activeSubscriptionStudentLimit()
{
    if (env('APP_SAAS')) {
        return cache()->rememberForever('activeSubscriptionStudentLimit', function () {
            $subscription = Subscription::active()->first();

            if ($subscription) {
                return $subscription->payment_type == PackagePaymentType::PREPAID ? $subscription->student_limit : 99999999;
            }

            return null;
        });
    }

    return null;
}

function activeSubscriptionStaffLimit()
{
    if (env('APP_SAAS')) {
        return cache()->rememberForever('activeSubscriptionStaffLimit', function () {
            $subscription = Subscription::active()->first();

            if ($subscription) {
                return $subscription->payment_type == PackagePaymentType::PREPAID ? $subscription->staff_limit : 99999999;
            }

            return null;
        });
    }

    return null;
}

function activeSubscriptionExpiryDate()
{
    if (env('APP_SAAS')) {
        return cache()->rememberForever('activeSubscriptionExpiryDate', function () {
            $subscription = Subscription::active()->first();
            if ($subscription) {
                if ($subscription->expiry_date) { // expiry gate null menas this is lifetime package
                    if (date('Y-m-d') <= date('Y-m-d', strtotime($subscription->expiry_date))) {
                        return true;
                    }

                    return false;
                }

                return true;
            }

            return false;
        });
    }

    return true;
}

function activeSubscriptionFeatures()
{
    if (env('APP_SAAS')) {
        return cache()->rememberForever('activeSubscriptionFeatures', function () {
            return Subscription::active()->first()?->features;
        });
    }

    return null;
}

// Feature check
if (! function_exists('hasFeature')) {
    function hasFeature($keyword)
    {
        if (! env('APP_SAAS')) {
            return true;
        }
        // if (in_array($keyword, Setting('features') ?? [])) {
        if (in_array($keyword, activeSubscriptionFeatures() ?? [])) {
            return true;
        }

        return false;
    }
}

function sessionClassStudent()
{

    $sesionClassStudent = SessionClassStudent::query()
        ->where('student_id', request()->filled('student_id') ? request('student_id') : @auth()->user()->student->id)
        ->first();

    if ($sesionClassStudent) {
        return $sesionClassStudent;
    }

    if (sessionClassStudentByParent()) {
        return sessionClassStudentByParent();
    }

    if (isStudentAccessInAPI()) {
        $data = SessionClassStudent::query()
            ->where('student_id', request()->filled('student_id') ? request('student_id') : @auth()->user()->student->id)
            ->whereHas('session', function ($q) {
                $q->whereYear('start_date', '<=', date('Y'))
                    ->whereYear('end_date', '>=', date('Y'));
            })->first();

        return $data;
    }

    return null;
}

function getDayNum($date)
{
    $day = Str::lower(Carbon::createFromFormat('Y-m-d', $date)->format('l'));

    switch ($day) {
        case $day == 'saturday':
            return 1;
            break;
        case $day == 'sunday':
            return 2;
            break;
        case $day == 'monday':
            return 3;
            break;
        case $day == 'tuesday':
            return 4;
            break;
        case $day == 'wednesday':
            return 5;
            break;
        case $day == 'thursday':
            return 6;
            break;
        default:
            return 7;
    }
}

function teacherSubjects()
{
    return SubjectAssignChildren::with('subject')
        ->when(Auth::user()->role_id == 5, function ($query) {
            return $query->where('staff_id', Auth::user()->staff->id);
        })
        ->pluck('subject_id')
        ->toArray();
}

if (! function_exists('get_base_url')) {
    function get_base_url($subDomainKey = null)
    {
        $scheme = env('APP_HTTPS') ? 'https://' : 'http://';
        $mainDomain = env('APP_MAIN_APP_URL'); // e.g. example.com

        if ($subDomainKey) {
            return $scheme.$subDomainKey.'.'.$mainDomain;
        }

        return $scheme.$mainDomain;
    }
}

if (! function_exists('getAttendanceType')) {

    function getAttendanceType($type)
    {
        if ($type == 1) {
            return 'PRESENT';
        } elseif ($type == 2) {
            return 'LATE';
        } elseif ($type == 3) {
            return 'ABSENT';
        } elseif ($type == 4) {
            return 'HALFDAY';
        } else {
            return '';
        }
    }
}

if (! function_exists('send_web_notification')) {
    function send_web_notification($title, $message, $reciever_id, $url = null)
    {
        try {
            $notification = new SystemNotification;
            $notification->title = $title;
            $notification->message = $message;
            $notification->reciver_id = $reciever_id;
            $notification->url = $url;
            $notification->save();
        } catch (\Throwable $th) {
            Log::info('NOtification store::'.$th);
        }
    }
}

if (! function_exists('send_message')) {
    function send_message_twillo($message, $recipients)
    {
        Log::info('To Number ::'.$recipients.'Message::  '.$message);
        try {
            $sid = setting('twilio_account_sid');
            $token = setting('twilio_auth_token');
            $twilio_number = setting('twilio_phone_number');

            if ($sid && $token && $twilio_number) {
                $twilio = new Client($sid, $token);

                return $twilio->messages
                    ->create(
                        $recipients,
                        [
                            'body' => $message,
                            'from' => $twilio_number,
                        ]
                    );
            }
        } catch (\Throwable $th) {
            Log::info('Twillo Msg Error'.$th->getMessage());
        }
    }
}

if (! function_exists('send_flutter_notification')) {
    function send_flutter_notification($title, $message, $img = null)
    {

        try {
            $url = 'https://fcm.googleapis.com/fcm/send';
            $dataArr = ['click_action' => 'FLUTTER_NOTIFICATION_CLICK', 'status' => 'done'];
            $notification = ['title' => $title, 'text' => $message, 'image' => $img, 'sound' => 'default', 'badge' => '1'];
            $arrayToSend = ['notification' => $notification, 'data' => $dataArr, 'priority' => 'high'];
            $fields = json_encode($arrayToSend);
            $headers = [
                'Authorization: key='.setting('FCM_SECRET_KEY'),
                'Content-Type: application/json',
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            $result = curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable $th) {
            Log::info('Flutter Push Msg Error'.$th->getMessage());
        }
    }
}

function getAttendanceType($type)
{
    if ($type == 1) {
        return 'PRESENT';
    } elseif ($type == 2) {
        return 'LATE';
    } elseif ($type == 3) {
        return 'ABSENT';
    } elseif ($type == 4) {
        return 'HALFDAY';
    } else {
        return '';
    }
}

function send_web_notification($title, $message, $reciever_id, $url = null)
{
    try {
        $notification = new SystemNotification;
        $notification->title = $title;
        $notification->message = $message;
        $notification->reciver_id = $reciever_id;
        $notification->url = $url;
        $notification->save();
    } catch (\Throwable $th) {
        Log::info('NOtification store::'.$th);
        //   Log::info($th->getMessage());
    }
}

if (! function_exists('hasModule')) {
    function hasModule($name): bool
    {
        $filePath = base_path('modules_statuses.json');
        $statuses = json_decode(file_get_contents($filePath), true);
        if (isset($statuses[$name])) {
            $isModuleEnabled = $statuses[$name];
            if ($isModuleEnabled) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

if (! function_exists('isSuperAdmin')) {
    function isSuperAdmin()
    {
        $role = auth()->user()?->role_id;
        if ($role == \App\Enums\RoleEnum::SUPERADMIN) {
            return true;
        }

        return false;
    }
}

if (! function_exists('admission_fields')) {
    function admission_fields()
    {
        // dd(OnlineAdmissionSetting::where('type', 'online_admission')->where('is_show',1)->get()->pluck('field')->values(),2);
        try {
            if (Cache::has('online_admission_field_is_show') && Cache::get('online_admission_field_is_show')) {
                return Cache::get('online_admission_field_is_show');
            }

            return Cache::rememberForever('online_admission_setting', function () {
                return OnlineAdmissionSetting::where('type', 'online_admission')->where('is_show', 1)->get()->pluck(['field'])->toArray();
            });
        } catch (\Throwable $th) {
            return [];
        }
    }
}

if (! function_exists('is_show')) {
    function is_show($field)
    {
        try {
            $field_array = admission_fields();

            return in_array($field, $field_array);
        } catch (\Throwable $th) {
            return false;
        }
    }
}

if (! function_exists('is_required')) {
    function is_required($field)
    {
        try {
            $field_array = admission_required_fields();

            return in_array($field, $field_array);
        } catch (\Throwable $th) {
            return false;
        }
    }
}

if (! function_exists('admission_required_fields')) {
    function admission_required_fields()
    {
        try {
            if (Cache::has('online_admission_field_is_require') && Cache::get('online_admission_field_is_require')) {
                return Cache::get('online_admission_field_is_require');
            }

            return Cache::rememberForever('online_admission_field_is_require', function () {
                return OnlineAdmissionSetting::where('type', 'online_admission')->where('is_required', 1)->get()->pluck(['field'])->toArray();
            });
        } catch (\Throwable $th) {
            return [];
        }
    }
}

if (! function_exists('app_translate')) {
    function app_translate()
    {
        try {
            return env('APP_TRANSLATE');
        } catch (\Throwable $th) {
            return false;
        }
    }
}

if (! function_exists('isParentUserAccessStudentInAPI')) {
    function isParentUserAccessStudentInAPI()
    {
        return auth()->check() &&
            @auth()->user()->role_id == 7 &&
            request()->filled('student_id')
            ? true : false;
    }
}

if (! function_exists('isStudentAccessInAPI')) {
    function isStudentAccessInAPI()
    {
        return ! isParentUserAccessStudentInAPI() &&
            (
                (auth()->check() && @auth()->user()->role_id == 6) ||
                request()->filled('student_id')
            )
            ? true : false;
    }
}

if (! function_exists('sessionClassStudentByParent')) {
    function sessionClassStudentByParent()
    {

        return SessionClassStudent::query()
            ->where('student_id', request('student_id'))
            ->whereHas('student', fn ($q) => $q->where('parent_guardian_id', @auth()->user()->parent->id))
            ->whereHas('session', function ($q) {
                $q->whereYear('start_date', '<=', date('Y'))
                    ->whereYear('end_date', '>=', date('Y'));
            })
            ->first();
    }
}

// encrypt id
if (! function_exists('encryptFunction')) {
    function encryptFunction($number = null)
    {

        return openssl_encrypt($number, 'AES-128-CTR', 'CodeSpeedyKeybj54HH', 0, '8565825542115032');
    }
}

// decrypt id
if (! function_exists('decryptFunction')) {
    function decryptFunction($encrypted = null)
    {
        return openssl_decrypt($encrypted, 'AES-128-CTR', 'CodeSpeedyKeybj54HH', 0, '8565825542115032');
    }
}

if (! function_exists('humanReadableDate')) {
    function humanReadableDate($date)
    {
        $date = Carbon::parse($date);

        // Check if the date is within one day
        if ($date->diffInDays() >= 1) {
            // Show in a standard date format (e.g., 'Y-m-d H:i:s')
            $formattedDate = $date->format('jS F Y h:i A');
        } else {
            // Show human-readable time difference (e.g., '2 hours ago')
            $formattedDate = $date->diffForHumans();
        }

        return $formattedDate;
    }
}

if (! function_exists('saasTenantMigrationPaths')) {
    function saasTenantMigrationPaths()
    {
        $filePath = base_path('modules_statuses.json');
        $migrationPaths = [
            database_path('migrations/tenant'), // Core tenant migrations
        ];

        if (file_exists($filePath)) {
            $json_content = file_get_contents($filePath);
            $modules = json_decode($json_content, true);

            // Exclude MainApp and Installer
            unset($modules['MainApp']);
            unset($modules['Installer']);

            // Loop through only enabled (true) modules
            foreach ($modules as $module => $status) {
                if ($status === true) {
                    $migrationPaths[] = base_path("Modules/$module/Database/Migrations");
                }
            }
        }

        return $migrationPaths;
    }
}

if (! function_exists('createAvatarAndUpload')) {
    function createAvatarAndUpload($firstName, $lastName, $path = 'backend/uploads/users')
    {
        // Step 1: Generate avatar initials
        $initials = strtoupper(Str::substr($firstName, 0, 1).Str::substr($lastName, 0, 1));

        // Step 2: Create image with Intervention
        $backgroundColor = '#17095f'; // Generate random hex color
        $image = Image::canvas(200, 200, $backgroundColor);
        $image->text($initials, 100, 100, function ($font) {
            $font->file(public_path('backend/assets/fonts/OpenSans-Bold.ttf')); // Ensure font exists
            $font->size(80);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('center');
        });

        // Step 3: Encode image and prepare file name
        $encodedImage = $image->encode('png');
        $filename = time().Str::random(10).'.png';

        // Step 4: Prepare Upload record
        $upload = new Upload;

        if (setting('file_system') === 's3') {
            // Upload to S3
            $filePath = $path.'/'.$filename;
            Storage::disk('s3')->put($filePath, (string) $encodedImage);
            if (Storage::disk('s3')->exists($filePath)) {
                $upload->path = $filePath;
            }
        } else {
            // Local storage
            if (! File::exists(public_path($path))) {
                File::makeDirectory(public_path($path), 0755, true);
            }
            $fullPath = public_path($path.'/'.$filename);
            file_put_contents($fullPath, $encodedImage);
            $upload->path = $path.'/'.$filename;
        }

        // Step 5: Save record and return ID
        $upload->save();

        return $upload->id;
    }
}

if (! function_exists('isTeacherCreated')) {
    function isTeacherCreated($createdBy)
    {

        if (($createdBy == Auth::user()->id) && (Auth::user()->role_id == 5)) {
            return true;
        }

        return true;
    }
}

if (! function_exists('studentsClassSection')) {
    function studentsClassSection()
    {
        $student = Student::where('user_id', Auth::user()->id)->first();

        return $classSection = SessionClassStudent::where('session_id', setting('session'))->where('student_id', $student->id)->latest()->first();
    }
}

if (! function_exists('increaseServerLoad')) {
    function increaseServerLoad()
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);
        ini_set('pcre.backtrack_limit', 10000000);
    }
}

if (! function_exists('numberToWords')) {
    function numberToWords($num)
    {
        $locale = Session::get('locale', 'en');

        // ---------- ENGLISH ----------
        if ($locale !== 'ar') {

            $ones = [
                '', 'one', 'two', 'three', 'four', 'five', 'six',
                'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve',
                'thirteen', 'fourteen', 'fifteen', 'sixteen',
                'seventeen', 'eighteen', 'nineteen',
            ];

            $tens = [
                '', '', 'twenty', 'thirty', 'forty', 'fifty',
                'sixty', 'seventy', 'eighty', 'ninety',
            ];

            if ($num == 0) {
                return 'Zero';
            }

            if ($num < 20) {
                return ucwords($ones[$num]);
            }

            if ($num < 100) {
                $text = $tens[intval($num / 10)]
                    .(($num % 10) ? ' '.$ones[$num % 10] : '');

                return ucwords(trim($text));
            }

            if ($num < 1000) {
                $text = $ones[intval($num / 100)].' hundred'
                    .(($num % 100) ? ' '.numberToWords($num % 100) : '');

                return ucwords(trim($text));
            }

            if ($num < 10000) {
                $text = $ones[intval($num / 1000)].' thousand'
                    .(($num % 1000) ? ' '.numberToWords($num % 1000) : '');

                return ucwords(trim($text));
            }
        }

        // ---------- ARABIC ----------
        $ones = [
            '', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة',
            'ستة', 'سبعة', 'ثمانية', 'تسعة', 'عشرة',
            'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر',
            'خمسة عشر', 'ستة عشر', 'سبعة عشر',
            'ثمانية عشر', 'تسعة عشر',
        ];

        $tens = [
            '', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون',
            'ستون', 'سبعون', 'ثمانون', 'تسعون',
        ];

        $hundreds = [
            '', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة',
            'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة',
        ];

        if ($num == 0) {
            return 'صفر';
        }

        if ($num < 20) {
            return $ones[$num];
        }

        if ($num < 100) {
            $ten = intval($num / 10);
            $one = $num % 10;

            return $one
                ? $ones[$one].' و'.$tens[$ten]
                : $tens[$ten];
        }

        if ($num < 1000) {
            $hundred = intval($num / 100);
            $remainder = $num % 100;

            return $remainder
                ? $hundreds[$hundred].' و'.numberToWords($remainder)
                : $hundreds[$hundred];
        }

        // 1000–5000
        if ($num <= 5000) {
            $thousand = intval($num / 1000);
            $remainder = $num % 1000;

            if ($thousand == 1) {
                $thousandWord = 'ألف';
            } elseif ($thousand == 2) {
                $thousandWord = 'ألفان';
            } else {
                $thousandWord = $ones[$thousand].' آلاف';
            }

            return $remainder
                ? $thousandWord.' و'.numberToWords($remainder)
                : $thousandWord;
        }

        return (string) $num; // fallback
    }
}

if (! function_exists('chatSettings')) {
    function chatSettings($purpose = null)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole == RoleEnum::SUPERADMIN || $userRole == RoleEnum::ADMIN) {
            return true;
        }
        $settings = ChatSetting::first();
        if ($purpose === 'group_settings') {
            if ($settings) {
                return $settings->std_create_groups
                    || $settings->gdn_create_groups
                    || $settings->tcr_create_groups;
            }
        }
        if ($purpose === 'chat_archieve') {
            if ($settings) {
                return $settings->std_archive_chat || $settings->gdn_archive_chat || $settings->tcr_archive_chat;
            }
        }

        return false;
    }
}

if (! function_exists('chatSettingRow')) {
    function chatSettingRow()
    {
        return ChatSetting::first();
    }
}

// For activityLog Module event trigger
if (! function_exists('ActivityLogEvent')) {
    function ActivityLogEvent($activityName, $userId = null, $description = null, $createdBy = null)
    {

        try {
            if (hasModule('ActivityLog')) {
                $userId = $userId ?? auth()->id();
                event(new \Modules\ActivityLog\Events\ActivityLogEvent(
                    activityName: $activityName,
                    userId: $userId,
                    description: $description,
                    createdBy: $createdBy
                ));
            }
        } catch (\Throwable $th) {
            info($th->getMessage());
            throw $th;
        }
    }
}
