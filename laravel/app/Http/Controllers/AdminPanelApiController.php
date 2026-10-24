<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminPanelApiController extends Controller
{
    private function reply(bool $success, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $success, 'message' => $message, ...$data], $status);
    }

    private function adminId(Request $request): int
    {
        return (int) $request->user()->getAuthIdentifier();
    }

    private function isSuperAdmin(Request $request): bool
    {
        return Str::contains(mb_strtolower((string) $request->user()->jabatan), 'super admin');
    }

    private function hasJabatan(Request $request, array $roles): bool
    {
        if ($this->isSuperAdmin($request)) return true;
        $jabatan = mb_strtolower((string) $request->user()->jabatan);
        foreach ($roles as $role) {
            if (Str::contains($jabatan, mb_strtolower($role))) return true;
        }
        return false;
    }

    private function denyUnlessJabatan(Request $request, array $roles)
    {
        return $this->hasJabatan($request, $roles) ? null : $this->reply(false, 'Akses tidak sesuai jabatan.', [], 403);
    }

    private function audit(Request $request, string $action): void
    {
        DB::table('audit_logs')->insert([
            'admin_id' => $this->adminId($request),
            'action' => Str::limit($action, 190, ''),
            'ip_address' => (string) $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'created_at' => now(),
        ]);
    }

    private function denyUnlessSuperAdmin(Request $request)
    {
        return $this->isSuperAdmin($request) ? null : $this->reply(false, 'Akses khusus Super Admin.', [], 403);
    }

    public function listPendaftar()
    {
        $rows = DB::table('siswa as s')
            ->leftJoin('formulir_siswa as f', 'f.siswa_id', '=', 's.id')
            ->orderByDesc('s.tanggal_daftar')
            ->select(['s.id', 's.nama', 's.sekolah_asal', 's.jurusan', 's.email', 's.status', 's.tanggal_daftar', 'f.nisn', 'f.status_formulir'])
            ->get();
        $applicants = $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'nama' => $row->nama,
            'nisn' => $row->nisn,
            'sekolah' => $row->sekolah_asal,
            'jurusan' => $row->jurusan,
            'email' => $row->email,
            'tanggal' => $row->tanggal_daftar ? date('Y-m-d', strtotime($row->tanggal_daftar)) : null,
            'nilai' => null,
            'status' => $row->status,
            'status_formulir' => $row->status_formulir,
        ]);
        return $this->reply(true, 'Data pendaftar berhasil diambil.', ['pendaftar' => $applicants]);
    }

    public function updatePendaftar(Request $request, int $studentId)
    {
        if ($denied = $this->denyUnlessJabatan($request, ['ketua panitia'])) return $denied;
        $data = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:150'],
            'nisn' => ['sometimes', 'nullable', 'string', 'max:30'],
            'sekolah' => ['sometimes', 'required', 'string', 'max:150'],
            'jurusan' => ['sometimes', 'required', 'string', 'max:100'],
        ]);
        if (!$data) return $this->reply(false, 'Tidak ada data yang diubah.', [], 422);
        return DB::transaction(function () use ($request, $studentId, $data) {
            $student = DB::table('siswa')->where('id', $studentId)->lockForUpdate()->first();
            if (!$student) return $this->reply(false, 'Pendaftar tidak ditemukan.', [], 404);
            $hasForm = DB::table('formulir_siswa')->where('siswa_id', $studentId)->exists();
            if (array_key_exists('nisn', $data) && !$hasForm) {
                return $this->reply(false, 'NISN belum dapat diubah karena formulir siswa belum tersedia.', [], 409);
            }
            $studentChanges = [];
            foreach (['nama' => 'nama', 'sekolah' => 'sekolah_asal', 'jurusan' => 'jurusan'] as $field => $column) {
                if (array_key_exists($field, $data)) $studentChanges[$column] = $data[$field];
            }
            if ($studentChanges) DB::table('siswa')->where('id', $studentId)->update($studentChanges);
            if (array_key_exists('nisn', $data)) {
                DB::table('formulir_siswa')->where('siswa_id', $studentId)->update(['nisn' => $data['nisn']]);
            }
            $this->audit($request, 'admin.pendaftar.update.' . $studentId);
            return $this->reply(true, 'Data pendaftar diperbarui.');
        });
    }

    public function deletePendaftar(Request $request, int $studentId)
    {
        if ($denied = $this->denyUnlessJabatan($request, ['ketua panitia'])) return $denied;
        return DB::transaction(function () use ($request, $studentId) {
            $student = DB::table('siswa')->where('id', $studentId)->lockForUpdate()->first();
            if (!$student) return $this->reply(false, 'Pendaftar tidak ditemukan.', [], 404);
            $files = DB::table('dokumen_siswa')->where('siswa_id', $studentId)->pluck('nama_file_server');
            $this->audit($request, 'admin.pendaftar.delete.' . $studentId);
            DB::table('siswa')->where('id', $studentId)->delete();
            DB::afterCommit(fn () => $files->each(fn ($file) => Storage::disk('local')->delete('uploads/' . basename($file))));
            return $this->reply(true, 'Data pendaftar berhasil dihapus.');
        });
    }

    public function listDocuments(Request $request, int $studentId)
    {
        if ($denied = $this->denyUnlessJabatan($request, ['verifikator', 'ketua panitia'])) return $denied;
        if (!DB::table('siswa')->where('id', $studentId)->exists()) return $this->reply(false, 'Pendaftar tidak ditemukan.', [], 404);
        $documents = DB::table('dokumen_siswa')->where('siswa_id', $studentId)->orderBy('document_id')->get()
            ->map(fn ($document) => [
                'id' => (int) $document->id,
                'document_id' => $document->document_id,
                'title' => Str::headline($document->document_id),
                'fileName' => $document->nama_file,
                'mimeType' => $document->mime_type,
                'size' => (int) $document->ukuran,
                'status' => $document->status_dokumen,
                'download_url' => url('/api/admin/dokumen/' . $document->id . '/download'),
            ]);
        return $this->reply(true, 'Dokumen berhasil diambil.', ['documents' => $documents]);
    }

    public function applicantDetails(Request $request, int $studentId)
    {
        if ($denied = $this->denyUnlessJabatan($request, ['verifikator', 'ketua panitia', 'staf administrasi'])) return $denied;
        $student = DB::table('siswa')->where('id', $studentId)
            ->first(['id','nama','email','sekolah_asal','jurusan','status','tanggal_daftar']);
        if (!$student) return $this->reply(false, 'Pendaftar tidak ditemukan.', [], 404);
        $form = DB::table('formulir_siswa')->where('siswa_id', $studentId)->first();
        return $this->reply(true, 'Detail pendaftar berhasil diambil.', ['siswa' => $student, 'formulir' => $form]);
    }

    public function downloadDocument(Request $request, int $documentId)
    {
        if ($denied = $this->denyUnlessJabatan($request, ['verifikator', 'ketua panitia'])) return $denied;
        $document = DB::table('dokumen_siswa')->where('id', $documentId)->first();
        if (!$document) return $this->reply(false, 'Dokumen tidak ditemukan.', [], 404);
        $path = 'uploads/' . basename($document->nama_file_server);
        if (!Storage::disk('local')->exists($path)) return $this->reply(false, 'File dokumen tidak tersedia.', [], 404);
        return response()->streamDownload(function () use ($path) {
            $stream = Storage::disk('local')->readStream($path);
            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, basename($document->nama_file));
    }

    public function updateVerification(Request $request, int $studentId)
    {
        if ($denied = $this->denyUnlessJabatan($request, ['verifikator', 'ketua panitia'])) return $denied;
        $data = $request->validate([
            'status' => ['required', 'in:Menunggu,Diproses,Diterima,Ditolak'],
            'catatan' => ['nullable', 'string', 'max:4000'],
        ]);
        return DB::transaction(function () use ($request, $studentId, $data) {
            if (!DB::table('siswa')->where('id', $studentId)->lockForUpdate()->exists()) {
                return $this->reply(false, 'Pendaftar tidak ditemukan.', [], 404);
            }
            DB::table('siswa')->where('id', $studentId)->update(['status' => $data['status']]);
            if (Schema::hasColumn('formulir_siswa', 'catatan_verifikasi')) {
                DB::table('formulir_siswa')->where('siswa_id', $studentId)->update(['catatan_verifikasi' => $data['catatan'] ?? null]);
            }
            $documentStatus = $data['status'] === 'Diterima' ? 'Valid' : ($data['status'] === 'Ditolak' ? 'Ditolak' : 'Menunggu');
            DB::table('dokumen_siswa')->where('siswa_id', $studentId)->update(['status_dokumen' => $documentStatus, 'updated_at' => now()]);
            $this->audit($request, 'admin.verifikasi.' . $data['status'] . '.' . $studentId);
            return $this->reply(true, 'Status verifikasi berhasil diperbarui.', ['siswa_id' => $studentId, 'status' => $data['status']]);
        });
    }

    public function stats(Request $request)
    {
        $counts = DB::table('siswa')->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(status IN ('Menunggu', 'Diproses')) AS waiting")
            ->selectRaw("SUM(status = 'Diterima') AS accepted")
            ->selectRaw("SUM(status = 'Ditolak') AS rejected")->first();
        $distribution = DB::table('siswa')->select('jurusan AS label')->selectRaw('COUNT(*) AS total')->groupBy('jurusan')->orderByDesc('total')->get();
        $daily = DB::table('siswa')->selectRaw('DATE(tanggal_daftar) AS day, COUNT(*) AS total')
            ->where('tanggal_daftar', '>=', date('Y-m-d 00:00:00', strtotime('-29 days')))->groupByRaw('DATE(tanggal_daftar)')->orderBy('day')->get();
        return $this->reply(true, 'Statistik admin berhasil diambil.', [
            'stats' => ['total' => (int) $counts->total, 'waiting' => (int) $counts->waiting, 'accepted' => (int) $counts->accepted, 'rejected' => (int) $counts->rejected],
            'distribution' => $distribution,
            'daily' => $daily->map(fn ($row) => ['label' => date('d M', strtotime($row->day)), 'total' => (int) $row->total]),
        ]);
    }

    public function listAnnouncements()
    {
        return $this->reply(true, 'Pengumuman berhasil diambil.', ['announcements' => DB::table('pengumuman')->orderByDesc('created_at')->get()]);
    }

    public function createAnnouncement(Request $request)
    {
        if ($denied = $this->denyUnlessJabatan($request, ['ketua panitia'])) return $denied;
        $data = $request->validate(['judul' => ['required','string','max:200'], 'kategori' => ['required','string','max:100'], 'target' => ['required','string','max:100'], 'isi' => ['required','string','max:20000']]);
        $announcementId = DB::table('pengumuman')->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);
        $this->audit($request, 'admin.pengumuman.create.' . $announcementId);
        return $this->reply(true, 'Pengumuman berhasil diterbitkan.', ['id' => $announcementId], 201);
    }

    public function deleteAnnouncement(Request $request, int $announcementId)
    {
        if ($denied = $this->denyUnlessJabatan($request, ['ketua panitia'])) return $denied;
        $deleted = DB::table('pengumuman')->where('id', $announcementId)->delete();
        if (!$deleted) return $this->reply(false, 'Pengumuman tidak ditemukan.', [], 404);
        $this->audit($request, 'admin.pengumuman.delete.' . $announcementId);
        return $this->reply(true, 'Pengumuman berhasil dihapus.');
    }

    public function settings()
    {
        return $this->reply(true, 'Pengaturan berhasil diambil.', ['settings' => DB::table('pengaturan_spmb')->where('id', 1)->first()]);
    }

    public function saveSettings(Request $request)
    {
        if ($denied = $this->denyUnlessSuperAdmin($request)) return $denied;
        $data = $request->validate([
            'gelombang_1' => ['required', 'boolean'], 'gelombang_2' => ['required', 'boolean'],
            'tgl_buka' => ['nullable', 'date'], 'tgl_tutup' => ['nullable', 'date', 'after_or_equal:tgl_buka'],
        ]);
        DB::table('pengaturan_spmb')->where('id', 1)->update($data + ['updated_at' => now()]);
        $this->audit($request, 'admin.settings.update');
        return $this->reply(true, 'Pengaturan SPMB berhasil disimpan.');
    }

    public function listStaff(Request $request)
    {
        if ($denied = $this->denyUnlessSuperAdmin($request)) return $denied;
        $staff = DB::table('admin')->select('id','nama','email','jabatan','status','tanggal_daftar')->orderBy('nama')->get();
        return $this->reply(true, 'Staf berhasil diambil.', ['admins' => $staff]);
    }

    public function createStaff(Request $request)
    {
        if ($denied = $this->denyUnlessSuperAdmin($request)) return $denied;
        $data = $request->validate([
            'nama' => ['required','string','max:150'], 'email' => ['required','email','max:150','unique:admin,email'],
            'role' => ['required','in:Verifikator Berkas,Staf Administrasi,Ketua Panitia SPMB,Super Admin'],
            'password' => ['required','string','min:12'],
        ]);
        $staffId = DB::table('admin')->insertGetId([
            'nama' => $data['nama'], 'email' => mb_strtolower($data['email']), 'jabatan' => $data['role'],
            'password' => bcrypt($data['password']), 'status' => 'Aktif', 'tanggal_daftar' => now(),
        ]);
        $this->audit($request, 'admin.staff.create.' . $staffId);
        return $this->reply(true, 'Staf berhasil ditambahkan.', ['id' => $staffId], 201);
    }

    public function updateStaff(Request $request, int $staffId)
    {
        if ($denied = $this->denyUnlessSuperAdmin($request)) return $denied;
        if ($staffId === $this->adminId($request)) return $this->reply(false, 'Akun yang sedang digunakan tidak dapat diedit dari panel ini.', [], 422);
        $data = $request->validate([
            'nama' => ['required','string','max:150'],
            'email' => ['required','email','max:150', Rule::unique('admin', 'email')->ignore($staffId)],
            'jabatan' => ['required','in:Verifikator Berkas,Staf Administrasi,Ketua Panitia SPMB,Super Admin'],
            'status' => ['required','in:Aktif,Nonaktif'],
            'password' => ['nullable','string','min:12'],
        ]);
        return DB::transaction(function () use ($request, $staffId, $data) {
            $staff = DB::table('admin')->where('id', $staffId)->lockForUpdate()->first();
            if (!$staff) return $this->reply(false, 'Staf tidak ditemukan.', [], 404);
            if (Str::contains(mb_strtolower($staff->jabatan), 'super admin')
                && ($data['jabatan'] !== $staff->jabatan || $data['status'] !== $staff->status)) {
                return $this->reply(false, 'Role/status akun Super Admin tidak dapat diubah melalui panel.', [], 403);
            }
            $changes = [
                'nama' => trim($data['nama']),
                'email' => mb_strtolower($data['email']),
                'jabatan' => $data['jabatan'],
                'status' => $data['status'],
            ];
            if (!empty($data['password'])) $changes['password'] = bcrypt($data['password']);
            DB::table('admin')->where('id', $staffId)->update($changes);
            $this->audit($request, 'admin.staff.update.' . $staffId);
            return $this->reply(true, 'Data staf berhasil diperbarui.');
        });
    }

    public function deleteStaff(Request $request, int $staffId)
    {
        if ($denied = $this->denyUnlessSuperAdmin($request)) return $denied;
        if ($staffId === $this->adminId($request)) return $this->reply(false, 'Akun sendiri tidak dapat dihapus.', [], 422);
        $staff = DB::table('admin')->where('id', $staffId)->first();
        if (!$staff) return $this->reply(false, 'Staf tidak ditemukan.', [], 404);
        if (Str::contains(mb_strtolower($staff->jabatan), 'super admin')) return $this->reply(false, 'Akun Super Admin tidak dapat dihapus melalui panel.', [], 403);
        $this->audit($request, 'admin.staff.delete.' . $staffId);
        DB::table('admin')->where('id', $staffId)->delete();
        return $this->reply(true, 'Staf berhasil dihapus.');
    }

    public function exportPendaftar(Request $request)
    {
        if ($denied = $this->denyUnlessJabatan($request, ['ketua panitia'])) return $denied;
        $this->audit($request, 'admin.export.csv');
        $rows = DB::table('siswa as s')->leftJoin('formulir_siswa as f', 'f.siswa_id', '=', 's.id')
            ->orderBy('s.id')->get(['s.nama','s.email','s.sekolah_asal','s.jurusan','s.status','s.tanggal_daftar','f.nisn']);
        return response()->streamDownload(function () use ($rows) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Nama','Email','NISN','Asal Sekolah','Jurusan','Status','Tanggal Daftar']);
            foreach ($rows as $row) {
                $values = [$row->nama,$row->email,$row->nisn,$row->sekolah_asal,$row->jurusan,$row->status,$row->tanggal_daftar];
                fputcsv($stream, array_map(fn ($value) => preg_match('/^[\x00-\x20]*[=+@\-]/u', (string) $value) ? "'" . $value : $value, $values));
            }
            fclose($stream);
        }, 'rekap-pendaftar.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}