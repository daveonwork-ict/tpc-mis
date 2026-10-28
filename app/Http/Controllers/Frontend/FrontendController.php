<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use App\Repositories\Frontend\FrontendRepository;
use App\Repositories\WebsiteSetup\PageRepository;

class FrontendController extends Controller
{
    private $repo;
    private $pageRepo;

    function __construct(
        FrontendRepository $repo,
        PageRepository      $pageRepo,
    )
    {
        if (!Schema::hasTable('settings') && !Schema::hasTable('users'))
            abort(400);
        $this->repo         = $repo;
        $this->pageRepo        = $pageRepo;
    }

    public function index()
    {
        $data['sliders']          = $this->repo->sliders();
        
        $data['counters']         = $this->repo->counters();
        $data['galleryCategory']  = $this->repo->galleryCategory();
        $data['gallery']          = $this->repo->gallery();
        $data['latestNews']       = $this->repo->latestNews();
        $data['comingEvents']     = $this->repo->comingEvents();

        return view('frontend.home', compact('data'));
    }

    public function about()
    {
         $data = $this->repo->abouts();

        return view('frontend.about', compact('data'));
    }

    // Blog
    public function news()
    {
        $data['news'] = $this->repo->news();
        return view('frontend.news', compact('data'));
    }
    public function newsDetail($id)
    {
        $data['allNews'] = $this->repo->news();
        $data['news']    = $this->repo->newsDetail($id);
        return view('frontend.news-detail', compact('data'));
    }

    // Event
    public function events()
    {
        $events = $this->repo->events();
        return view('frontend.events', compact('events'));
    }
    public function eventDetail($id)
    {
        $data['allEvent'] = $this->repo->events();
        $data['event']    = $this->repo->eventDetail($id);
        return view('frontend.event-detail', compact('data'));
    }


    public function page($slug)
    {
        $data['page']    = $this->pageRepo->findBySlug($slug);
        return view('frontend.page-detail', compact('data'));
    }


    // Event
    public function notices()
    {
        $data['notices'] = $this->repo->notices();
        return view('frontend.notices', compact('data'));
    }
    public function noticeDetail($id)
    {
        $data['allNotice'] = $this->repo->notices();
        $data['notice-board']    = $this->repo->noticeDetail($id);
        return view('frontend.notice-detail', compact('data'));
    }

    // Contact
    public function contact()
    {
        $data['contactInfo']    = $this->repo->contactInfo();
        $data['depContact']     = $this->repo->depContact();
        return view('frontend.contact', compact('data'));
    }

    public function storeContact(Request $request)
    {
        return $this->repo->contact($request);
    }

    public function storeSubscribe(Request $request)
    {
        return $this->repo->subscribe($request);
    }
}
