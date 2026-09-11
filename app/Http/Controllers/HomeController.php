<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Speaker;
use App\Models\Schedule;
use App\Models\Gallery;
use App\Models\Sponsor;
use App\Models\SiteSetting;
use App\Models\TicketBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // Livestream settings
        $livestreamIsActive = SiteSetting::value('livestream_is_active', '0') === '1';
        $livestreamVideoId = SiteSetting::value('livestream_video_id');
        $livestreamEmbedUrl = SiteSetting::value('livestream_embed_url');
        $livestreamUrl = SiteSetting::value('livestream_youtube_url');

        // Cek apakah user memiliki tiket Online yang sudah Lunas
        $hasOnlinePaidTicket = false;
        if (Auth::check()) {
            $hasOnlinePaidTicket = TicketBooking::where('user_id', Auth::id())
                ->where('ticket_category', 'Online')
                ->where('status', 'paid')
                ->exists();
        }

        // 2. Kirim variabel ke view homepage
        return view('homepage', compact(
            'announcements',
            'speakers',
            'schedules',
            'galleries',
            'sponsors',
            'scheduleVisible',
            'livestreamIsActive',
            'livestreamVideoId',
            'livestreamEmbedUrl',
            'livestreamUrl',
            'hasOnlinePaidTicket'
        ));
    }

    /**
     * Halaman Live Streaming khusus peserta tiket Online Lunas
     */
    public function livestream()
    {
        // Livestream settings
        $livestreamIsActive = SiteSetting::value('livestream_is_active', '0') === '1';
        $livestreamVideoId = SiteSetting::value('livestream_video_id');
        $livestreamEmbedUrl = SiteSetting::value('livestream_embed_url');
        $livestreamUrl = SiteSetting::value('livestream_youtube_url');

        // Cek apakah user memiliki tiket Online yang sudah Lunas
        $hasOnlinePaidTicket = TicketBooking::where('user_id', Auth::id())
            ->where('ticket_category', 'Online')
            ->where('status', 'paid')
            ->exists();

        // Jika livestream tidak aktif atau user tidak punya tiket Online Lunas
        if (! $livestreamIsActive || empty($livestreamVideoId) || empty($livestreamEmbedUrl) || ! $hasOnlinePaidTicket) {
            abort(403, 'Akses Live Streaming hanya untuk peserta tiket Online yang sudah Lunas.');
        }

        return view('livestream', compact(
            'livestreamVideoId',
            'livestreamEmbedUrl',
            'livestreamUrl'
        ));
    }
}