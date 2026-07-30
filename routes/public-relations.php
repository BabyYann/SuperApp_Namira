<?php

use Illuminate\Support\Facades\Route;

Route::prefix('public-relations')->name('public-relations.')->middleware(['role:super_admin_yayasan|admin_yayasan|pembina_yayasan|pengawas_yayasan|humas_yayasan|admin_unit|humas_unit|kepala_sekolah'])->group(function () {
    Route::post('news/{news}/approve', [\App\Modules\PublicRelations\Controllers\NewsController::class, 'approve'])->name('news.approve');
    Route::post('news/{news}/reject', [\App\Modules\PublicRelations\Controllers\NewsController::class, 'reject'])->name('news.reject');
    Route::resource('news', \App\Modules\PublicRelations\Controllers\NewsController::class)->except(['show']);

    Route::post('events/{event}/approve', [\App\Modules\PublicRelations\Controllers\EventController::class, 'approve'])->name('events.approve');
    Route::post('events/{event}/reject', [\App\Modules\PublicRelations\Controllers\EventController::class, 'reject'])->name('events.reject');
    Route::resource('events', \App\Modules\PublicRelations\Controllers\EventController::class)->except(['show']);

    Route::post('partners/{partner}/approve', [\App\Modules\PublicRelations\Controllers\PartnerController::class, 'approve'])->name('partners.approve');
    Route::post('partners/{partner}/reject', [\App\Modules\PublicRelations\Controllers\PartnerController::class, 'reject'])->name('partners.reject');
    Route::resource('partners', \App\Modules\PublicRelations\Controllers\PartnerController::class)->except(['show']);

    Route::post('university-destinations/{universityDestination}/approve', [\App\Modules\PublicRelations\Controllers\UniversityDestinationController::class, 'approve'])->name('university-destinations.approve');
    Route::post('university-destinations/{universityDestination}/reject', [\App\Modules\PublicRelations\Controllers\UniversityDestinationController::class, 'reject'])->name('university-destinations.reject');
    Route::resource('university-destinations', \App\Modules\PublicRelations\Controllers\UniversityDestinationController::class)->except(['show']);

    Route::post('testimonials/{testimonial}/approve', [\App\Modules\PublicRelations\Controllers\TestimonialController::class, 'approve'])->name('testimonials.approve');
    Route::post('testimonials/{testimonial}/reject', [\App\Modules\PublicRelations\Controllers\TestimonialController::class, 'reject'])->name('testimonials.reject');
    Route::resource('testimonials', \App\Modules\PublicRelations\Controllers\TestimonialController::class)->except(['show']);

    Route::resource('banners', \App\Modules\PublicRelations\Controllers\BannerController::class)->except(['show']);
});
