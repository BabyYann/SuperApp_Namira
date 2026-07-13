<?php

use Illuminate\Support\Facades\Route;

Route::prefix('public-relations')->name('public-relations.')->middleware(['role:super_admin_yayasan|admin_yayasan|admin_unit|humas_unit|kepala_sekolah'])->group(function () {
    Route::resource('news', \App\Modules\PublicRelations\Controllers\NewsController::class)->except(['show']);
    Route::resource('events', \App\Modules\PublicRelations\Controllers\EventController::class)->except(['show']);
    Route::resource('partners', \App\Modules\PublicRelations\Controllers\PartnerController::class)->except(['show']);
    Route::resource('university-destinations', \App\Modules\PublicRelations\Controllers\UniversityDestinationController::class)->except(['show']);
    Route::resource('testimonials', \App\Modules\PublicRelations\Controllers\TestimonialController::class)->except(['show']);
    Route::resource('banners', \App\Modules\PublicRelations\Controllers\BannerController::class)->except(['show']);
});
