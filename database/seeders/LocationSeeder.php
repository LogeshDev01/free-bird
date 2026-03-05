<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\City;
use App\Models\Zone;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Canada Provinces (States)
        $ontario = State::updateOrCreate(
            ['iso_code' => 'ON'],
            ['name' => 'Ontario', 'country_id' => 2, 'is_active' => true] // Assuming Country 2 is Canada
        );

        $bc = State::updateOrCreate(
            ['iso_code' => 'BC'],
            ['name' => 'British Columbia', 'country_id' => 2, 'is_active' => true]
        );

        $quebec = State::updateOrCreate(
            ['iso_code' => 'QC'],
            ['name' => 'Quebec', 'country_id' => 2, 'is_active' => true]
        );

        $alberta = State::updateOrCreate(
            ['iso_code' => 'AB'],
            ['name' => 'Alberta', 'country_id' => 2, 'is_active' => true]
        );

        // 2. Cities
        $toronto = City::updateOrCreate(
            ['name' => 'Toronto', 'state_id' => $ontario->id],
            ['slug' => 'gyms-in-toronto', 'is_popular' => true, 'latitude' => 43.6532, 'longitude' => -79.3832]
        );

        $vancouver = City::updateOrCreate(
            ['name' => 'Vancouver', 'state_id' => $bc->id],
            ['slug' => 'gyms-in-vancouver', 'is_popular' => true, 'latitude' => 49.2827, 'longitude' => -123.1207]
        );

        $montreal = City::updateOrCreate(
            ['name' => 'Montreal', 'state_id' => $quebec->id],
            ['slug' => 'gyms-in-montreal', 'is_popular' => true, 'latitude' => 45.5017, 'longitude' => -73.5673]
        );

        $calgary = City::updateOrCreate(
            ['name' => 'Calgary', 'state_id' => $alberta->id],
            ['slug' => 'gyms-in-calgary', 'is_popular' => true, 'latitude' => 51.0447, 'longitude' => -114.0719]
        );

        // 3. Zones
        Zone::updateOrCreate(
            ['zone_code' => 'TOR-DT', 'city_id' => $toronto->id],
            ['name' => 'Downtown', 'is_active' => true]
        );

        Zone::updateOrCreate(
            ['zone_code' => 'TOR-NY', 'city_id' => $toronto->id],
            ['name' => 'North York', 'is_active' => true]
        );

        Zone::updateOrCreate(
            ['zone_code' => 'VAN-DT', 'city_id' => $vancouver->id],
            ['name' => 'Downtown', 'is_active' => true]
        );

        Zone::updateOrCreate(
            ['zone_code' => 'VAN-GS', 'city_id' => $vancouver->id],
            ['name' => 'Gastown', 'is_active' => true]
        );

        Zone::updateOrCreate(
            ['zone_code' => 'CAL-BL', 'city_id' => $calgary->id],
            ['name' => 'Beltline', 'is_active' => true]
        );
    }
}
