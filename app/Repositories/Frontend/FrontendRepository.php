<?php

namespace App\Repositories\Frontend;

use App\Models\News;
use App\Enums\Status;
use App\Models\Event;
use App\Models\Slider;
use App\Models\Counter;
use App\Models\Gallery;
use App\Models\NoticeBoard;
use App\Models\Staff\Staff;
use Illuminate\Support\Carbon;
use App\Models\GalleryCategory;
use App\Models\WebsiteSetup\About;
use App\Models\WebsiteSetup\Contact;
use App\Models\WebsiteSetup\Subscribe;
use App\Models\WebsiteSetup\ContactInfo;
use App\Interfaces\Frontend\FrontendInterface;
use App\Models\WebsiteSetup\DepartmentContact;

class FrontendRepository implements FrontendInterface
{
    public function sliders()
    {
        return Slider::where('status', Status::ACTIVE)->orderBy('serial')->get();
    }
    public function counters()
    {
        return Counter::where('status', Status::ACTIVE)->orderBy('serial')->paginate(4);
    }

    // Abouts
    public function abouts()
    {
        $data['abouts']   = About::where('status', Status::ACTIVE)->orderBy('serial')->get();
        $data['teachers'] = Staff::where('status', Status::ACTIVE)->where('role_id', 5)->take(8)->get();
        return $data;
    }

    // News
    public function news()
    {
        return News::where('status', Status::ACTIVE)->where('publish_date', '<=', date('Y-m-d'))->orderBy('id', 'desc')->paginate(6);
    }

    public function latestNews()
    {
        return News::where('status', Status::ACTIVE)->where('publish_date', '<=', date('Y-m-d'))->orderBy('id', 'desc')->take(4)->get();
    }

    public function newsDetail($id)
    {
        return News::where('status', Status::ACTIVE)->where('id', $id)->first();
    }

    // News
    public function notices()
    {
        $currentDateTime = Carbon::now(); // Get the current datetime
        return  NoticeBoard::where('status', Status::ACTIVE)->where('is_visible_web', 1)->where('publish_date', '<=', $currentDateTime)->orderBy('id', 'desc')->paginate(6);
    }


    public function noticeDetail($id)
    {
        return NoticeBoard::where('status', Status::ACTIVE)->where('id', $id)->first();
    }

    // Events
    public function events()
    {
        return Event::where('session_id', setting('session'))->where('status', Status::ACTIVE)->orderBy('id', 'desc')->paginate(6);
    }

    public function eventDetail($id)
    {
        return Event::where('session_id', setting('session'))->where('status', Status::ACTIVE)->where('id', $id)->first();
    }

    public function comingEvents()
    {
        return Event::where('session_id', setting('session'))->where('status', Status::ACTIVE)->where('date', '>=', date('Y-m-d'))->orderBy('date','DESC')->take(4)->get();
    }

    // Gallery
    public function galleryCategory()
    {
        return GalleryCategory::where('status', Status::ACTIVE)->orderBy('name')->get();
    }
    public function gallery()
    {
        return Gallery::where('status', Status::ACTIVE)->orderBy('id', 'desc')->paginate(12);
    }

    // Contact Information

    public function contactInfo(){
        return ContactInfo::where('status', Status::ACTIVE)->get();
    }

    public function depContact(){
        return DepartmentContact::where('status', Status::ACTIVE)->get();
    }

    public function contact($request){
        try {
            $row          = new Contact();
            $row->name    = $request->name;
            $row->phone   = $request->phone;
            $row->email   = $request->email;
            $row->subject = $request->subject;
            $row->message = $request->message;
            $row->save();
            return response()->json([___('frontend.Success'), ___('frontend.send_successfully'), 'success', ___('frontend.OK')]);
        } catch (\Throwable $th) {
            return response()->json([___('frontend.Error'), ___('frontend.something_went_wrong'), 'error', ___('frontend.OK')]);
        }
    }

    public function subscribe($request){
        try {
            $row          = Subscribe::where('email', $request->email)->first();
            if($row)
                return response()->json([___('frontend.Attention'), ___('frontend.already_subscribed'), 'warning', ___('frontend.OK')]);

            $row          = new Subscribe();
            $row->email   = $request->email;
            $row->save();

            return response()->json([___('frontend.Success'),___('frontend.Subscribed'), 'success', ___('frontend.OK')]);

        } catch (\Throwable $th) {
            return response()->json([___('frontend.Error'), ___('frontend.something_went_wrong'), 'error', ___('frontend.OK')]);
        }
    }
}
