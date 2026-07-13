<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $news = \App\Models\News::with('unit')->where('status', 'published')->latest('published_at')->take(4)->get();
    $now = now();
    $events = \App\Models\Event::with('unit')
        ->where('status', '!=', 'cancelled')
        ->where('start_date', '>=', $now)
        ->orderBy('start_date', 'asc')
        ->take(6)
        ->get();

    if ($events->isEmpty()) {
        $events = \App\Models\Event::with('unit')
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($now) {
                $q->where(function ($sub) use ($now) {
                    $sub->whereNotNull('end_date')->where('end_date', '<', $now);
                })->orWhere(function ($sub) use ($now) {
                    $sub->whereNull('end_date')->where('start_date', '<', $now->copy()->startOfDay());
                });
            })
            ->orderByRaw("COALESCE(end_date, start_date) DESC")
            ->take(6)
            ->get();
    }

    $partners = \App\Models\Partner::latest()->get();

    $destinations = \App\Models\UniversityDestination::with('unit')
        ->where('is_active', true)
        ->get(['id', 'unit_id', 'name', 'city', 'country', 'type', 'visit_type', 'lat', 'lng', 'visit_date', 'description']);

    $testimonials = \App\Models\Testimonial::with('unit')->where('is_active', true)->latest()->get();
    $banners = \App\Models\Banner::where('is_active', true)->orderBy('order_weight', 'asc')->latest()->get();

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => \Illuminate\Foundation\Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'latestNews' => $news,
        'upcomingEvents' => $events,
        'partners' => $partners,
        'destinations' => $destinations,
        'testimonials' => $testimonials,
        'bannersList' => $banners,
    ]);
});

Route::get('/berita', function (\Illuminate\Http\Request $request) {
    $query = \App\Models\News::with('unit', 'author')->where('status', 'published');
    
    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%");
        });
    }
    
    if ($request->filled('unit')) {
        $unitId = $request->input('unit');
        $query->where('unit_id', $unitId);
    }

    $sort = $request->input('sort', 'terbaru');
    if ($sort === 'terpopuler') {
        $query->orderByDesc('views');
    } else {
        $query->latest('published_at');
    }
    
    $news = $query->paginate(12)->withQueryString();
    
    $units = \App\Modules\Yayasan\Models\Unit::select('id', 'name')
        ->withCount(['news' => fn($q) => $q->where('status', 'published')])
        ->get();

    $trending = \App\Models\News::with('unit')
        ->where('status', 'published')
        ->orderByDesc('views')
        ->limit(5)
        ->get(['id', 'title', 'slug', 'views', 'unit_id', 'published_at']);

    $popular = \App\Models\News::with('unit')
        ->where('status', 'published')
        ->orderByDesc('views')
        ->limit(4)
        ->get(['id', 'title', 'slug', 'image_path', 'published_at', 'views', 'unit_id']);
    
    return Inertia::render('Public/NewsIndex', [
        'news'     => $news,
        'units'    => $units,
        'trending' => $trending,
        'popular'  => $popular,
        'filters'  => $request->only(['search', 'unit', 'sort']),
    ]);
})->name('news.index');

Route::get('/berita/{news:slug}', function (\App\Models\News $news) {
    if ($news->status !== 'published') abort(404);

    $news->incrementViews();

    $related = \App\Models\News::with('unit')
        ->where('status', 'published')
        ->where('unit_id', $news->unit_id)
        ->where('id', '!=', $news->id)
        ->latest('published_at')
        ->limit(6)
        ->get(['id', 'title', 'slug', 'image_path', 'published_at', 'content', 'views', 'unit_id']);

    if ($related->count() < 3) {
        $needed = 3 - $related->count();
        $excludeIds = $related->pluck('id')->push($news->id)->toArray();
        $fallback = \App\Models\News::with('unit')
            ->where('status', 'published')
            ->whereNotIn('id', $excludeIds)
            ->latest('published_at')
            ->limit($needed)
            ->get(['id', 'title', 'slug', 'image_path', 'published_at', 'content', 'views', 'unit_id']);
        $related = $related->concat($fallback);
    }

    $latest = \App\Models\News::with('unit')
        ->where('status', 'published')
        ->where('id', '!=', $news->id)
        ->latest('published_at')
        ->limit(5)
        ->get(['id', 'title', 'slug', 'image_path', 'published_at', 'unit_id']);

    return Inertia::render('Public/NewsDetail', [
        'news'    => $news->load('unit', 'author'),
        'related' => $related,
        'latest'  => $latest,
    ]);
})->name('news.show');

Route::get('/events', function (\Illuminate\Http\Request $request) {
    $query = \App\Models\Event::with('unit');

    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    if ($request->filled('unit')) {
        $unitId = $request->input('unit');
        $query->where('unit_id', $unitId);
    }

    $now = now();
    if ($request->filled('status')) {
        $status = $request->input('status');
        if ($status === 'upcoming') {
            $query->where('status', '!=', 'cancelled')
                  ->where('start_date', '>', $now);
        } elseif ($status === 'ongoing') {
            $query->where('status', '!=', 'cancelled')
                  ->where('start_date', '<=', $now)
                  ->where(function($q) use ($now) {
                      $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $now);
                  });
        } elseif ($status === 'completed') {
            $query->where('status', '!=', 'cancelled')
                  ->where(function($q) use ($now) {
                      $q->where(function($sub) use ($now) {
                            $sub->whereNotNull('end_date')
                                ->where('end_date', '<', $now);
                        })
                        ->orWhere(function($sub) use ($now) {
                            $sub->whereNull('end_date')
                                ->where('start_date', '<', $now->copy()->startOfDay());
                        });
                  });
        } elseif ($status === 'cancelled') {
            $query->where('status', 'cancelled');
        }
    } else {
        $query->where('status', '!=', 'cancelled');
    }

    $sort = $request->input('sort', 'latest');
    if ($sort === 'nearest') {
        $query->orderByRaw("CASE WHEN start_date >= ? THEN 0 ELSE 1 END", [now()])
              ->orderBy('start_date', 'asc');
    } elseif ($sort === 'oldest') {
        $query->orderBy('start_date', 'asc');
    } elseif ($sort === 'popular') {
        $query->orderByDesc('views');
    } else {
        $query->orderByDesc('created_at');
    }

    $events = $query->paginate(12)->withQueryString();

    $units = \App\Modules\Yayasan\Models\Unit::select('id', 'name')->get();

    $nowFeatured = now();

    $featuredEvent = \App\Models\Event::with('unit')
        ->where('status', '!=', 'cancelled')
        ->where('start_date', '>', $nowFeatured)
        ->orderBy('start_date', 'asc')
        ->first();

    if (!$featuredEvent) {
        $featuredEvent = \App\Models\Event::with('unit')
            ->where('status', '!=', 'cancelled')
            ->where('start_date', '<=', $nowFeatured)
            ->where(function($q) use ($nowFeatured) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $nowFeatured);
            })
            ->orderBy('start_date', 'asc')
            ->first();
    }

    if (!$featuredEvent) {
        $featuredEvent = \App\Models\Event::with('unit')
            ->where('status', '!=', 'cancelled')
            ->where(function($q) use ($nowFeatured) {
                $q->where(function($sub) use ($nowFeatured) {
                    $sub->whereNotNull('end_date')->where('end_date', '<', $nowFeatured);
                })->orWhere(function($sub) use ($nowFeatured) {
                    $sub->whereNull('end_date')->where('start_date', '<', $nowFeatured->copy()->startOfDay());
                });
            })
            ->orderByDesc('end_date')
            ->first();
    }

    return Inertia::render('Public/EventIndex', [
        'events'        => $events,
        'featuredEvent' => $featuredEvent,
        'units'         => $units,
        'filters'       => $request->only(['search', 'unit', 'status', 'sort']),
    ]);
})->name('events.index');

Route::get('/events/{event:slug}', function (\App\Models\Event $event) {
    if ($event->status === 'cancelled') abort(404);

    $viewedSessionKey = 'viewed_events';
    $viewedEvents = session()->get($viewedSessionKey, []);
    if (!in_array($event->id, $viewedEvents)) {
        $event->incrementViews();
        $viewedEvents[] = $event->id;
        session()->put($viewedSessionKey, $viewedEvents);
    }

    $now = now();
    $related = \App\Models\Event::with('unit')
        ->where('unit_id', $event->unit_id)
        ->where('id', '!=', $event->id)
        ->where('status', '!=', 'cancelled')
        ->orderByRaw("CASE WHEN start_date >= ? THEN 0 ELSE 1 END", [$now])
        ->orderByDesc('start_date')
        ->limit(6)
        ->get();

    if ($related->count() < 3) {
        $needed = 3 - $related->count();
        $excludeIds = $related->pluck('id')->push($event->id)->toArray();
        $fallback = \App\Models\Event::with('unit')
            ->whereNotIn('id', $excludeIds)
            ->where('status', '!=', 'cancelled')
            ->orderByRaw("CASE WHEN start_date >= ? THEN 0 ELSE 1 END", [$now])
            ->orderByDesc('start_date')
            ->limit($needed)
            ->get();
        $related = $related->concat($fallback);
    }

    return Inertia::render('Public/EventDetail', [
        'event'   => $event->load('unit'),
        'related' => $related,
    ]);
})->name('events.show');

Route::get('/testimonials', function () {
    $testimonials = \App\Models\Testimonial::with('unit')->where('is_active', true)->latest()->get();
    return Inertia::render('Public/TestimonialIndex', [
        'testimonials' => $testimonials
    ]);
})->name('testimonials.index');
