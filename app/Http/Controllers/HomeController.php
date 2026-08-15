<?php

namespace App\Http\Controllers;

use App\Models\Announcement; // <-- Gunakan model Announcement
use App\Models\Speaker;
use App\Models\Schedule;
use App\Models\Gallery;
use App\Models\Sponsor;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // 1. Ambil info-info yang aktif saja dari database
        $announcements = Announcement::where('is_active', true)->latest()->get();
        $speakers  = Speaker::orderBy('order', 'asc')->get();
        $schedules = Schedule::oldest()->get();
        $galleries = Gallery::latest()->take(8)->get();
        $sponsors  = Sponsor::oldest()->get();

        // Apakah seksi Jadwal Acara boleh tampil di beranda?
        $scheduleVisible = SiteSetting::value('schedule_visible', '1') === '1';

        // 2. Kirim variabel $announcements ke view welcome.blade.php
        return view('homepage', compact(
            'announcements',
            'speakers',
            'schedules',
            'galleries',
            'sponsors',
            'scheduleVisible'
        ));
    }
}