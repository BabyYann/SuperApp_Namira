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

    if ($events->count() < 6) {
        $needed = 6 - $events->count();
        $excludeIds = $events->pluck('id')->toArray();
        $pastEvents = \App\Models\Event::with('unit')
            ->where('status', '!=', 'cancelled')
            ->whereNotIn('id', $excludeIds)
            ->orderByRaw("COALESCE(end_date, start_date) DESC")
            ->take($needed)
            ->get();
        $events = $events->concat($pastEvents);
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

Route::get('/ppdb', function () {
    return Inertia::render('Public/PpdbComingSoon');
})->name('ppdb.index');

Route::get('/spmb', function () {
    return redirect()->route('ppdb.index');
})->name('spmb.index');

Route::get('/sitemap.xml', function () {
    $baseUrl = config('app.url', 'https://namiraschool.com');
    
    $news = \App\Models\News::where('status', 'published')->latest('published_at')->get(['slug', 'updated_at']);
    $events = \App\Models\Event::where('status', '!=', 'cancelled')->latest('start_date')->get(['id', 'updated_at']);

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    
    // Main pages
    $xml .= '<url><loc>' . $baseUrl . '</loc><changefreq>daily</changefreq><priority>1.0</priority></url>';
    $xml .= '<url><loc>' . $baseUrl . '/berita</loc><changefreq>daily</changefreq><priority>0.9</priority></url>';
    $xml .= '<url><loc>' . $baseUrl . '/events</loc><changefreq>daily</changefreq><priority>0.9</priority></url>';
    $xml .= '<url><loc>' . $baseUrl . '/testimonials</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>';
    
    // News detail pages
    foreach ($news as $item) {
        $lastMod = $item->updated_at ? $item->updated_at->toAtomString() : date('c');
        $xml .= '<url><loc>' . $baseUrl . '/berita/' . urlencode($item->slug) . '</loc><lastmod>' . $lastMod . '</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>';
    }

    // Event detail pages
    foreach ($events as $item) {
        $lastMod = $item->updated_at ? $item->updated_at->toAtomString() : date('c');
        $xml .= '<url><loc>' . $baseUrl . '/events/' . $item->id . '</loc><lastmod>' . $lastMod . '</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>';
    }

    $xml .= '</urlset>';

    return response($xml, 200, [
        'Content-Type' => 'text/xml'
    ]);
});

Route::get('/cleanup-demo-student', function () {
    try {
        $user = \App\Models\User::where('email', 'siswa.demo@namiraschool.com')->first();
        if ($user) {
            $student = \App\Modules\Academic\Models\Student::where('user_id', $user->id)->first();
            if ($student) {
                // Delete demo bills & transactions
                \App\Modules\Finance\Models\StudentBill::where('student_id', $student->id)->forceDelete();
                \App\Modules\Finance\Models\Transaction::where('student_id', $student->id)->delete();
                // Delete student
                $student->forceDelete();
            }
            // Delete roles
            \Illuminate\Support\Facades\DB::table('model_has_roles')->where('model_id', $user->id)->delete();
            // Delete user
            $user->delete();
        }

        // Clean demo finance type if exists and has no other bills
        $ft = \App\Modules\Finance\Models\FinanceType::where('name', 'SPP Bulanan SMP Namira')->first();
        if ($ft && \App\Modules\Finance\Models\StudentBill::where('finance_type_id', $ft->id)->count() === 0) {
            $ft->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Akun dummy siswa demo (Ahmad Zaki Pratama) & data demo berhasil dibersihkan total!',
        ]);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

Route::get('/download-pdf-guru-sd', function () {
    $teachers = [
        ["nama" => "Abdul Adjis Afifi", "NIY" => "3259201301", "email" => "abdul@namira.school", "no_hp" => "085204854927"],
        ["nama" => "Anggun Happy Ananda, S.Pd", "NIY" => "3190201302", "email" => "Anggunhappyananda@gmail.com", "no_hp" => "085331362000"],
        ["nama" => "Hj Muthmainnah", "NIY" => "3175201506", "email" => "hj@namira.school", "no_hp" => "085204610367"],
        ["nama" => "Sudar", "NIY" => "32201607", "email" => "sudar@namira.school", "no_hp" => "-"],
        ["nama" => "Kholifatul Khoiriyah, S.Si", "NIY" => "3192201609", "email" => "kholifatulk084@gmail.com", "no_hp" => "087861564895"],
        ["nama" => "Hisyam Farih, S.E", "NIY" => "3291201711", "email" => "anahisyam45@gmail.com", "no_hp" => "082232243354"],
        ["nama" => "Riyadhatul Badiah, S.E", "NIY" => "3195201715", "email" => "riyahafidz07@gmail.com", "no_hp" => "085259122195"],
        ["nama" => "Meylinda Kurnia Sofiyani, S.Psi", "NIY" => "3192201821", "email" => "meylindakurnia12@gmail.com", "no_hp" => "085234588078"],
        ["nama" => "Maulidia Khoiry, S.Pd", "NIY" => "3199201824", "email" => "maulidiakhoiriy@gmail.com", "no_hp" => "082331530162"],
        ["nama" => "Husnul Sri Maulidiah, S.Pd", "NIY" => "3197201933", "email" => "Husnulsrimaulidiah@gmail.com", "no_hp" => "081556823582"],
        ["nama" => "Mochammad", "NIY" => "3260201934", "email" => "mochammad@namira.school", "no_hp" => "082331530162"],
        ["nama" => "Halimatus Sa'diyah, S.Pd", "NIY" => "3196201935", "email" => "Halimasadiyah238@gmail.com", "no_hp" => "085331167567"],
        ["nama" => "Cahya Arief Khoirumah S.Pd", "NIY" => "3196202037", "email" => "khoirumahcahya1104@gmail.com", "no_hp" => "085804014742"],
        ["nama" => "Dwi Arifatun Nisa' S.Pd", "NIY" => "3196202039", "email" => "dwi@namira.school", "no_hp" => "082316283056"],
        ["nama" => "Siti Anisa S.Hum", "NIY" => "3197202041", "email" => "sitiianisaa456@gmail.com", "no_hp" => "085230217949"],
        ["nama" => "Agung Prassetiyo", "NIY" => "3198202142", "email" => "agungprassetiyo511@gmail.com", "no_hp" => "085217208502"],
        ["nama" => "Azkiyah Amalina S.Pd", "NIY" => "3197202143", "email" => "azkiyahamalina79@gmail.com", "no_hp" => "085335821035"],
        ["nama" => "Rosyidah S.Pd", "NIY" => "3198201744", "email" => "rosyidahnamira123@gmail.com", "no_hp" => "082338795422"],
        ["nama" => "Mia Nurhidayati S.E", "NIY" => "3198202247", "email" => "mianurhidayati7@gmail.com", "no_hp" => "081359564307"],
        ["nama" => "Siti Aminatul Qomariyah", "NIY" => "3101202249", "email" => "syarifahaminatul@gmail.com", "no_hp" => "081233171193"],
        ["nama" => "Khusnul Hotimah S.Pd", "NIY" => "3100202251", "email" => "khusnulhotimah1123@gmail.com", "no_hp" => "081357135188"],
        ["nama" => "Nur Halimah", "NIY" => "3198202252", "email" => "Hnurhalimah091@gmail.com", "no_hp" => "085290443736"],
        ["nama" => "Fajar Ridwan Abilillah S.Pd", "NIY" => "3100202253", "email" => "fajar@namira.school", "no_hp" => "0895630439320"],
        ["nama" => "Halifah", "NIY" => "3272201654", "email" => "halifah@namira.school", "no_hp" => "-"],
        ["nama" => "Ike Nurjannah S.Pd", "NIY" => "3100202355", "email" => "ikenurjannah618@gmail.com", "no_hp" => "085608029378"],
        ["nama" => "Muhammad Farid S.Pd", "NIY" => "3100202356", "email" => "faridjenny24@gmail.com", "no_hp" => "085234789280"],
        ["nama" => "Rehanatil Jannah", "NIY" => "3101202357", "email" => "jannahrehanatil@gmail.com", "no_hp" => "082337975497"],
        ["nama" => "Iva Mutma'inah S.Pd", "NIY" => "3100202358", "email" => "ivamutmainah.1507@gmail.com", "no_hp" => "082330345815"],
        ["nama" => "Alfina Ananda Putri S.Pd", "NIY" => "3101202359", "email" => "putrialnanda12@gmail.com", "no_hp" => "082245621324"],
        ["nama" => "Firdani Sholeh Pradana S.Pd", "NIY" => "3190202360", "email" => "dani.firdani@gmail.com", "no_hp" => "082335345167"],
        ["nama" => "Ahmad Baidhowi S.Pd", "NIY" => "3197202461", "email" => "ahmadbaidhowi108@gmail.com", "no_hp" => "081336535501"],
        ["nama" => "Shofiyah Husein S.Pd", "NIY" => "3102202462", "email" => "shofiyahhusein682@gmail.com", "no_hp" => "085732439937"],
        ["nama" => "Abd Hannan", "NIY" => "3291202463", "email" => "abd@namira.school", "no_hp" => "082269523244"],
        ["nama" => "Yazid Mubtafi S.Pd", "NIY" => "3101202464", "email" => "yazimubtafi7@gmail.com", "no_hp" => "083137368121"],
        ["nama" => "Intan Maufirah", "NIY" => "3101202465", "email" => "syfintan847@gmail.com", "no_hp" => "085853664685"],
        ["nama" => "Helmi Mufidah", "NIY" => "3102202466", "email" => "helmimufida05@gmail.com", "no_hp" => "083157513651"],
        ["nama" => "Dandik Nofian Putra Pratama", "NIY" => "3299202467", "email" => "dandik@namira.school", "no_hp" => "081280356087"],
        ["nama" => "Nadifah S.Pd", "NIY" => "3103202468", "email" => "ndf.5403@gmail.com", "no_hp" => "085233858252"],
        ["nama" => "Mamluatul Hasanah S.Pd", "NIY" => "3100202469", "email" => "mamluatulhasanah1520@gmail.com", "no_hp" => "085850797267"],
        ["nama" => "Nur Aini Trischa Ananda", "NIY" => "3100202470", "email" => "nurainifriscaananda@gmail.com", "no_hp" => "083845072546"],
        ["nama" => "Hermawan Diva Ardi Wijaya", "NIY" => "3102202471", "email" => "hermawan@namira.school", "no_hp" => "083852801326"],
        ["nama" => "Putri Agustini S.Sos", "NIY" => "3101202472", "email" => "putriagustini1303@gmail.com", "no_hp" => "082266837207"],
        ["nama" => "Muhammad Syarifudin S.Pd", "NIY" => "3101202473", "email" => "muhammadsyarifudin032001@gmail.com", "no_hp" => "085648235862"],
        ["nama" => "Deny Setiawan S.Pd", "NIY" => "3102202475", "email" => "setiawandeny1602@gmail.com", "no_hp" => "081259894411"],
        ["nama" => "Hasbullah S.Pd.I", "NIY" => "3102202475", "email" => "hasbulcs1@gmail.com", "no_hp" => "085231224112"],
        ["nama" => "SARIF", "NIY" => "3105202576", "email" => "syarifsya726@gmail.com", "no_hp" => "083155792854"],
        ["nama" => "Meirinda Zahratul M. S.Pd", "NIY" => "3102202577", "email" => "meirindazm@gmail.com", "no_hp" => "081259081907"],
        ["nama" => "Rian Hidayad S. Kom", "NIY" => "3102202578", "email" => "rianbru18@gmail.com", "no_hp" => "082140560121"],
        ["nama" => "Ahmad Kamil Fadoli S.Pd", "NIY" => "3196202579", "email" => "kamilfadoli20@gmail.com", "no_hp" => "082318246720"],
        ["nama" => "Astutik, S.Pd.I", "NIY" => "3180201380", "email" => "astutik7749@gmail.com", "no_hp" => "082330221399"]
    ];

    $html = view('pdf.guru-sd-list', compact('teachers'))->render();
    
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');
    return $pdf->download('Daftar_Akun_Guru_SD_Namira.pdf');
});
