<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasUnitScope;
use App\Models\Event;
use App\Models\News;
use App\Models\Partner;
use App\Models\UniversityDestination;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicRelationsApiController extends Controller
{
    use HasUnitScope;

    // GET /pr/news
    public function news(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);
        $items = News::with('unit')
            ->where('unit_id', $unitId)
            ->where('status', 'published')
            ->latest('published_at')
            ->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    // GET /pr/news/:id
    public function newsShow(Request $request, $id): JsonResponse
    {
        $news = News::with(['unit', 'author'])->findOrFail($id);
        $news->incrementViews();

        return response()->json([
            'data' => [
                'id' => $news->id,
                'title' => $news->title,
                'content' => $news->content,
                'image_url' => $news->image_path,
                'views' => $news->views,
                'published_at' => $news->published_at?->format('Y-m-d H:i'),
                'unit' => $news->unit?->name,
                'author' => $news->author?->name,
            ],
        ]);
    }

    // GET /pr/events
    public function events(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);
        $items = Event::with('unit')
            ->where('unit_id', $unitId)
            ->where('status', '!=', 'cancelled')
            ->latest('start_date')
            ->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    // GET /pr/events/:id
    public function eventShow(Request $request, $id): JsonResponse
    {
        $event = Event::with(['unit', 'author'])->findOrFail($id);
        $event->incrementViews();

        return response()->json([
            'data' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'image_url' => $event->image_path,
                'location' => $event->location,
                'start_date' => $event->start_date?->format('Y-m-d H:i'),
                'end_date' => $event->end_date?->format('Y-m-d H:i'),
                'status' => $event->computed_status,
                'registration_link' => $event->registration_link,
                'contact_person' => $event->contact_person,
                'views' => $event->views,
                'unit' => $event->unit?->name,
            ],
        ]);
    }

    // GET /pr/destinations
    public function destinations(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);
        $items = UniversityDestination::with('unit')
            ->where('unit_id', $unitId)
            ->where('is_active', true)
            ->latest('visit_date')
            ->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    // GET /pr/destinations/:id
    public function destinationShow($id): JsonResponse
    {
        $destination = UniversityDestination::with(['unit', 'creator'])->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $destination->id,
                'name' => $destination->name,
                'city' => $destination->city,
                'country' => $destination->country,
                'type' => $destination->type,
                'visit_type' => $destination->visit_type,
                'lat' => $destination->lat,
                'lng' => $destination->lng,
                'visit_date' => $destination->visit_date?->format('Y-m-d'),
                'description' => $destination->description,
                'unit' => $destination->unit?->name,
            ],
        ]);
    }
}
