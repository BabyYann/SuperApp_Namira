<?php

namespace Database\Seeders;

use App\Models\AttendanceLocation;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class AttendanceLocationSeeder extends Seeder
{
    public function run(): void
    {
        // Example: Namira School Coordinates (Approximate)
        // Let's create a location for each Unit if possible, or a central one.
        
        // Central Office (Kantor Yayasan)
        // Using a dummy coordinate. In real life, user sets this via map.
        // Let's use a coordinate that is likely "near" the user if they are testing locally?
        // No, let's use a fixed coordinate and tell the user to mock it or move there.
        // Or better, create a location at "0,0" for testing? No.
        // Let's create a location at Monas Jakarta as default example, 
        // AND one that is "Test Location" which we might update later.
        
        $units = Unit::all();
        
        foreach ($units as $unit) {
            AttendanceLocation::create([
                'unit_id' => $unit->id,
                'name' => 'Gedung ' . $unit->name,
                'latitude' => -7.752312, // Example Probolinggo
                'longitude' => 113.215123,
                'radius' => 100, // 100 meters
            ]);
        }
    }
}
