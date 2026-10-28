<?php

namespace App\Http\Controllers\Backend;

use App\Models\Search;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\DashboardRepository;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private $repo;

    function __construct(DashboardRepository $repo)
    {
        if (!Schema::hasTable('settings') && !Schema::hasTable('users')) {
            abort(400);
        }
        $this->repo = $repo;
    }

    public function index()
    {
        $data = $this->repo->index();
        return view('backend.dashboard', compact('data'));
    }

    public function searchMenuData(Request $request){
        try {
            $search = Search::query();
            if ($request->has('search')) {
                $search->where('title', 'like', '%' . $request->search . '%');
            }
            $search = $search->where('user_type', 'Admin')->take(10)->get()->map(function ($item) {
                return [
                    'title' => $item->title,
                    'route_name' => route($item->route_name),
                ];
            });
            return response()->json($search);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
