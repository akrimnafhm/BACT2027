<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Speaker;
use App\Models\Schedule;
use App\Models\Gallery;
use App\Models\Sponsor;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContentController extends Controller
{
    /**
     * Tampilan Utama Kelola Konten
     */
    public function index()
    {
        $announcements = Announcement::latest()->get();
        $speakers      = Speaker::orderBy('order', 'asc')->get();
        $schedules     = Schedule::orderBy('day', 'asc')
                             ->orderBy('start_time', 'asc')
                             ->get();
        $galleries     = Gallery::latest()->get();
        $sponsors      = Sponsor::oldest()->get();
        $scheduleVisible = SiteSetting::value('schedule_visible', '1') === '1';

        return view('admin.content', compact(
            'announcements',
            'speakers',
            'schedules',
            'galleries',
            'sponsors',
            'scheduleVisible'
        ));
    }

    // ==========================================
    // 1. CRUD TAB INFO & PENGUMUMAN
    // ==========================================

    /**
     * Simpan Info Baru
     */
    public function storeAnnouncement(Request $request)
    {
        $request->validate([
            'badge'   => 'required|string|max:50',
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        Announcement::create([
            // Gunakan ->input(...) agar tidak bentrok dengan properti internal Laravel
            'badge'     => strtoupper($request->input('badge')),
            'title'     => $request->input('title'),
            'content'   => $request->input('content'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Info pengumuman baru berhasil ditambahkan!');
    }

    /**
     * Update Info
     */
    public function updateAnnouncement(Request $request, $id)
    {
        $request->validate([
            'badge'   => 'required|string|max:50',
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $announcement = Announcement::findOrFail($id);
        $announcement->update([
            // Gunakan ->input(...) agar bersih dari error linter VS Code
            'badge'   => strtoupper($request->input('badge')),
            'title'   => $request->input('title'),
            'content' => $request->input('content'),
        ]);

        return back()->with('success', 'Info pengumuman berhasil diperbarui!');
    }

    /**
     * Hapus Info
     */
    public function destroyAnnouncement($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return back()->with('success', 'Info pengumuman berhasil dihapus!');
    }

    /**
     * Toggle Status Aktif/Nonaktif Info
     */
    public function toggleAnnouncementStatus($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->is_active = !$announcement->is_active;
        $announcement->save();

        return back()->with('success', 'Status tayang info berhasil diubah!');
    }

    // ==========================================
    // 2. CRUD TAB PEMBICARA SIMPOSIUM
    // ==========================================

    /**
     * Simpan Pembicara Baru
     */
    public function storeSpeaker(Request $request)
    {
        if ($reject = $this->rejectOversizedUploads($request, 'image')) {
            return $reject;
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'image'       => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'image.max'   => 'Ukuran foto pembicara melebihi batas maksimal 2 MB.',
        ]);

        $imagePath = $request->file('image')->store('speakers', 'public');

        // Cari nomor urut terbesar saat ini, lalu tambah 1
        $nextOrder = (Speaker::max('order') ?? 0) + 1;

        Speaker::create([
            'name'        => $request->input('name'),
            'institution' => $request->input('institution'),
            'order'       => $nextOrder, // <-- Otomatis terisi oleh sistem
            'image'       => $imagePath,
        ]);

        return back()->with('success', 'Data pembicara baru berhasil ditambahkan!');
    }

    /**
     * Helper privat: Memperbaiki angka order yang rusak/kembar (0, 0 -> jadi 1, 2, 3...)
     */
    /**
     * Helper privat: Memperbaiki angka order yang rusak/0 agar otomatis urut 1, 2, 3...
     */
    private function reindexSpeakersOrder()
    {
        $speakers = Speaker::orderBy('order', 'asc')->orderBy('id', 'asc')->get();
        foreach ($speakers as $index => $spk) {
            $expectedOrder = $index + 1;
            if ($spk->order !== $expectedOrder) {
                $spk->order = $expectedOrder;
                $spk->save();
            }
        }
    }

    /**
     * Geser Pembicara ke Atas (Up)
     */
    public function moveUpSpeaker($id)
    {
        $this->reindexSpeakersOrder(); // Pastikan urutannya rapi dulu (1, 2, 3...)

        $speaker = Speaker::findOrFail($id);
        
        // Cari pembicara yang posisinya tepat di atasnya
        $previousSpeaker = Speaker::where('order', '<', $speaker->order)
                                  ->orderBy('order', 'desc')
                                  ->first();

        if ($previousSpeaker) {
            // Tukar posisi urutannya menggunakan save() langsung
            $tempOrder = $speaker->order;
            
            $speaker->order = $previousSpeaker->order;
            $speaker->save();

            $previousSpeaker->order = $tempOrder;
            $previousSpeaker->save();
        }

        return back();
    }

    /**
     * Geser Pembicara ke Bawah (Down)
     */
    public function moveDownSpeaker($id)
    {
        $this->reindexSpeakersOrder(); // Pastikan urutannya rapi dulu (1, 2, 3...)

        $speaker = Speaker::findOrFail($id);
        
        // Cari pembicara yang posisinya tepat di bawahnya
        $nextSpeaker = Speaker::where('order', '>', $speaker->order)
                              ->orderBy('order', 'asc')
                              ->first();

        if ($nextSpeaker) {
            // Tukar posisi urutannya menggunakan save() langsung
            $tempOrder = $speaker->order;
            
            $speaker->order = $nextSpeaker->order;
            $speaker->save();

            $nextSpeaker->order = $tempOrder;
            $nextSpeaker->save();
        }

        return back();
    }

    /**
     * Update Pembicara
     */
    public function updateSpeaker(Request $request, $id)
    {
        if ($reject = $this->rejectOversizedUploads($request, 'image')) {
            return $reject;
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'image.max'   => 'Ukuran foto pembicara melebihi batas maksimal 2 MB.',
        ]);

        $speaker = Speaker::findOrFail($id);
        
        $data = [
            'name'        => $request->input('name'),
            'institution' => $request->input('institution'),
        ];

        // Jika admin mengupload foto profil baru
        if ($request->hasFile('image')) {
            // Hapus foto lama jika ada di storage
            if ($speaker->image && Storage::disk('public')->exists($speaker->image)) {
                Storage::disk('public')->delete($speaker->image);
            }
            $data['image'] = $request->file('image')->store('speakers', 'public');
        }

        $speaker->update($data);

        return back()->with('success', 'Data pembicara berhasil diperbarui!');
    }

    /**
     * Hapus Pembicara
     */
    public function destroySpeaker($id)
    {
        $speaker = Speaker::findOrFail($id);

        // Hapus file foto dari folder storage
        if ($speaker->image && Storage::disk('public')->exists($speaker->image)) {
            Storage::disk('public')->delete($speaker->image);
        }

        $speaker->delete();

        return back()->with('success', 'Data pembicara berhasil dihapus!');
    }

    // ==========================================
    // 3. CRUD TAB JADWAL ACARA
    // ==========================================

    /**
     * Simpan Jadwal Baru
     */
    public function storeSchedule(Request $request)
    {
        $request->validate([
            'day'        => 'required|integer|min:1',
            'start_time' => 'required|string|max:10',
            'end_time'   => 'required|string|max:10',
            'title'      => 'required|string|max:255',
            'speaker'    => 'nullable|string|max:255',
        ]);

        Schedule::create([
            'day'        => $request->input('day'),
            'start_time' => $request->input('start_time'),
            'end_time'   => $request->input('end_time'),
            'title'      => $request->input('title'),
            'speaker'    => $request->input('speaker'),
        ]);

        return back()->with('success', 'Agenda jadwal baru berhasil ditambahkan!');
    }

    /**
     * Update Jadwal
     */
    public function updateSchedule(Request $request, $id)
    {
        $request->validate([
            'day'        => 'required|integer|min:1',
            'start_time' => 'required|string|max:10',
            'end_time'   => 'required|string|max:10',
            'title'      => 'required|string|max:255',
            'speaker'    => 'nullable|string|max:255',
        ]);

        $schedule = Schedule::findOrFail($id);
        $schedule->update([
            'day'        => $request->input('day'),
            'start_time' => $request->input('start_time'),
            'end_time'   => $request->input('end_time'),
            'title'      => $request->input('title'),
            'speaker'    => $request->input('speaker'),
        ]);

        return back()->with('success', 'Agenda jadwal berhasil diperbarui!');
    }

    /**
     * Hapus Jadwal
     */
    public function destroySchedule($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return back()->with('success', 'Agenda jadwal berhasil dihapus!');
    }

    /**
     * Tampilkan/Sembunyikan Seluruh Seksi Jadwal Acara di Beranda
     */
    public function toggleScheduleSectionVisibility()
    {
        $setting = SiteSetting::firstOrCreate(
            ['key' => 'schedule_visible'],
            ['value' => '1']
        );

        $newValue  = $setting->value === '1' ? '0' : '1';
        $setting->update(['value' => $newValue]);

        return back()->with('success', $newValue === '1'
            ? 'Seksi Jadwal Acara kini TAMPIL di halaman utama.'
            : 'Seksi Jadwal Acara kini DISEMBUNYIKAN dari halaman utama.');
    }

    // ==========================================
    // 4. CRUD TAB GALERI FOTO
    // ==========================================

    /**
     * Simpan Foto Galeri Baru
     */
    public function storeGallery(Request $request)
    {
        if ($reject = $this->rejectOversizedUploads($request, 'image')) {
            return $reject;
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:3072', // Maks. 3MB
        ], [
            'image.max' => 'Ukuran foto galeri melebihi batas maksimal 3 MB.',
        ]);

        $imagePath = $request->file('image')->store('galleries', 'public');

        Gallery::create([
            'image' => $imagePath,
        ]);

        return back()->with('success', 'Foto galeri baru berhasil diunggah!');
    }

    /**
     * Hapus Foto Galeri
     */
    public function destroyGallery($id)
    {
        $gallery = Gallery::findOrFail($id);

        // Hapus file foto fisik dari storage
        if ($gallery->image && Storage::disk('public')->exists($gallery->image)) {
            Storage::disk('public')->delete($gallery->image);
        }

        $gallery->delete();

        return back()->with('success', 'Foto galeri berhasil dihapus!');
    }

    // ==========================================
    // 5. CRUD TAB SPONSOR & MITRA
    // ==========================================

    /**
     * Simpan Sponsor Baru
     */
    public function storeSponsor(Request $request)
    {
        if ($reject = $this->rejectOversizedUploads($request, 'logo')) {
            return $reject;
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'link' => 'nullable|string|max:255',
            'logo' => 'required|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ], [
            'logo.max' => 'Ukuran logo sponsor melebihi batas maksimal 2 MB.',
        ]);

        $logoPath = $request->file('logo')->store('sponsors', 'public');

        Sponsor::create([
            'name' => $request->input('name'),
            'link' => $request->input('link'),
            'logo' => $logoPath,
        ]);

        return back()->with('success', 'Logo sponsor baru berhasil ditambahkan!');
    }

    /**
     * Update Sponsor
     */
    public function updateSponsor(Request $request, $id)
    {
        if ($reject = $this->rejectOversizedUploads($request, 'logo')) {
            return $reject;
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'link' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ], [
            'logo.max' => 'Ukuran logo sponsor melebihi batas maksimal 2 MB.',
        ]);

        $sponsor = Sponsor::findOrFail($id);
        $data = [
            'name' => $request->input('name'),
            'link' => $request->input('link'),
        ];

        // Jika admin mengganti file logo
        if ($request->hasFile('logo')) {
            if ($sponsor->logo && Storage::disk('public')->exists($sponsor->logo)) {
                Storage::disk('public')->delete($sponsor->logo);
            }
            $data['logo'] = $request->file('logo')->store('sponsors', 'public');
        }

        $sponsor->update($data);

        return back()->with('success', 'Data sponsor berhasil diperbarui!');
    }

    /**
     * Hapus Sponsor
     */
    public function destroySponsor($id)
    {
        $sponsor = Sponsor::findOrFail($id);

        if ($sponsor->logo && Storage::disk('public')->exists($sponsor->logo)) {
            Storage::disk('public')->delete($sponsor->logo);
        }

        $sponsor->delete();

        return back()->with('success', 'Logo sponsor berhasil dihapus!');
    }
}