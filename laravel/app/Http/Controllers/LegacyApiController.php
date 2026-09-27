<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class LegacyApiController extends Controller
{
    private function reply(bool $success, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $success, 'message' => $message, ...$data], $status);
    }

    private function studentExists(int $id): bool
    {
        return DB::table('siswa')->where('id', $id)->exists();
    }

    public function listSiswa()
    {
        $rows = DB::table('siswa')->orderByDesc('tanggal_daftar')->get();
        $pendaftar = $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'nama' => $row->nama,
            'sekolah' => $row->sekolah_asal,
            'jurusan' => $row->jurusan,
            'tanggal' => date('d M Y', strtotime($row->tanggal_daftar)),
            'nilai' => '-',
            'status' => $row->status,
        ]);

        return $this->reply(true, 'Data pendaftar berhasil diambil.', ['pendaftar' => $pendaftar]);
    }

    public function getSiswa(Request $request)
    {
        $id = (int) $request->query('siswa_id', 0);
        if ($id < 1) return $this->reply(false, 'ID siswa tidak valid.', [], 422);
        if ($id !== (int) $request->user()->id) return $this->reply(false, 'Akses data siswa ditolak.', [], 403);

        $student = DB::table('siswa')->where('id', $id)->first();
        if (!$student) return $this->reply(false, 'Siswa tidak ditemukan.', [], 404);

        $form = DB::table('formulir_siswa')->where('siswa_id', $id)->first();
        if ($form) $form->sekolah_asal = $student->sekolah_asal;
        $documents = DB::table('dokumen_siswa')->where('siswa_id', $id)
            ->get(['document_id', 'nama_file', 'status_dokumen']);

        return $this->reply(true, 'Data siswa berhasil diambil.', [
            'siswa' => [
                'id' => (int) $student->id,
                'nama' => $student->nama,
                'sekolah' => $student->sekolah_asal,
                'jurusan' => $student->jurusan,
                'email' => $student->email,
                'status' => $student->status,
                'tanggal_daftar' => $student->tanggal_daftar,
            ],
            'formulir' => $form,
            'dokumen' => $documents,
        ]);
    }

    public function listDokumen(Request $request)
    {
        $id = (int) $request->query('siswa_id', 0);
        if ($id < 1) return $this->reply(false, 'ID siswa tidak valid.', [], 422);

        $documents = DB::table('dokumen_siswa')->where('siswa_id', $id)->orderBy('document_id')->get()
            ->map(fn ($document) => [
                'id' => $document->document_id,
                'title' => Str::headline($document->document_id),
                'fileName' => $document->nama_file,
                'mimeType' => $document->mime_type,
                'size' => (int) $document->ukuran,
                'status' => Str::lower($document->status_dokumen),
                'url' => url('/storage/uploads/' . rawurlencode($document->nama_file_server)),
            ]);

        return $this->reply(true, 'Dokumen berhasil diambil.', ['documents' => $documents]);
    }

    public function listJadwal()
    {
        $schedules = DB::table('jadwal_tes')->where('status', 'Aktif')->orderBy('tanggal')->orderBy('jam')
            ->get(['id', 'tanggal', 'sesi', 'jam', 'jenis', 'kuota'])
            ->map(function ($schedule) {
                $schedule->tanggal = date('d M Y', strtotime($schedule->tanggal));
                return $schedule;
            });

        return $this->reply(true, 'Jadwal tes berhasil diambil.', ['jadwal' => $schedules]);
    }

    public function loginSiswa(Request $request)
    {
        $student = Student::where('email', trim((string) $request->input('email')))->first();
        if (!$student || !Hash::check((string) $request->input('password'), $student->password)) {
            return $this->reply(false, 'Email atau password salah.', [], 401);
        }

        return $this->reply(true, 'Login berhasil.', ['siswa' => [
            'id' => (int) $student->id, 'nama' => $student->nama, 'sekolah' => $student->sekolah_asal,
            'jurusan' => $student->jurusan, 'email' => $student->email, 'status' => $student->status,
            'api_token' => $student->createToken('spmb-portal')->plainTextToken,
        ]]);
    }

    public function loginAdmin(Request $request)
    {
        if (!hash_equals((string) config('spmb.admin_access_code'), (string) $request->input('access_code'))) {
            return $this->reply(false, 'Kode akses admin tidak valid.', [], 403);
        }
        $admin = Admin::where('email', trim((string) $request->input('email')))->where('status', 'Aktif')->first();
        if (!$admin || !Hash::check((string) $request->input('password'), $admin->password)) {
            return $this->reply(false, 'Email atau password salah.', [], 401);
        }

        return $this->reply(true, 'Login admin berhasil.', ['admin' => [
            'id' => (int) $admin->id, 'nama' => $admin->nama, 'jabatan' => $admin->jabatan,
            'email' => $admin->email, 'status' => $admin->status,
            'api_token' => $admin->createToken('spmb-portal')->plainTextToken,
        ]]);
    }

    public function logout(Request $request)
    {
        $token = (string) $request->bearerToken();
        $secret = Str::contains($token, '|') ? Str::after($token, '|') : $token;
        DB::table('personal_access_tokens')->where('token', hash('sha256', $secret))->delete();

        return $this->reply(true, 'Logout berhasil.');
    }

    public function registerSiswa(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'], 'sekolah' => ['required', 'string', 'max:150'],
            'jurusan' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:6'],
        ]);
        if (DB::table('siswa')->where('email', $data['email'])->exists()) {
            return $this->reply(false, 'Email sudah terdaftar.', [], 409);
        }
        $id = DB::table('siswa')->insertGetId([
            'nama' => trim($data['nama']), 'sekolah_asal' => trim($data['sekolah']),
            'jurusan' => trim($data['jurusan']), 'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']), 'status' => 'Menunggu',
        ]);

        return $this->reply(true, 'Pendaftaran siswa berhasil.', ['siswa' => [
            'id' => $id, 'nama' => $data['nama'], 'sekolah' => $data['sekolah'],
            'jurusan' => $data['jurusan'], 'email' => strtolower($data['email']), 'status' => 'Menunggu',
        ]]);
    }

    public function registerAdmin(Request $request)
    {
        if (!hash_equals((string) config('spmb.admin_access_code'), (string) $request->input('access_code'))) {
            return $this->reply(false, 'Kode akses admin tidak valid.', [], 403);
        }
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'], 'jabatan' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'], 'password' => ['required', 'string', 'min:6'],
        ]);
        if (DB::table('admin')->where('email', $data['email'])->exists()) {
            return $this->reply(false, 'Email sudah terdaftar.', [], 409);
        }
        $id = DB::table('admin')->insertGetId([
            'nama' => trim($data['nama']), 'jabatan' => trim($data['jabatan']),
            'email' => strtolower($data['email']), 'password' => Hash::make($data['password']), 'status' => 'Aktif',
        ]);

        return $this->reply(true, 'Pendaftaran admin berhasil.', ['admin' => [
            'id' => $id, 'nama' => $data['nama'], 'jabatan' => $data['jabatan'], 'email' => strtolower($data['email']),
        ]]);
    }

    public function requestReset(Request $request)
    {
        $data = $request->validate(['identifier' => ['required', 'email']]);
        $accountType = 'siswa';
        $account = DB::table('siswa')->whereRaw('LOWER(email) = ?', [strtolower($data['identifier'])])->first();
        if (!$account) {
            $accountType = 'admin';
            $account = DB::table('admin')->whereRaw('LOWER(email) = ?', [strtolower($data['identifier'])])->first();
        }
        if (!$account) return $this->reply(false, 'Akun dengan email tersebut tidak ditemukan.', [], 404);

        DB::table('password_reset_tokens')->where('account_type', $accountType)->where('account_id', $account->id)
            ->whereNull('used_at')->update(['used_at' => now()]);
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'account_type' => $accountType, 'account_id' => $account->id, 'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + 900),
        ]);

        try {
            $url = url('/src/reset-password.html') . '?token=' . urlencode($token);
            Mail::raw("Gunakan tautan berikut untuk mengatur ulang password Anda. Tautan berlaku selama 15 menit:\n\n$url", function ($message) use ($account) {
                $message->to($account->email)->subject('Reset password SPMB SMK Kasatrian');
            });
        } catch (Throwable $exception) {
            DB::table('password_reset_tokens')->where('token_hash', hash('sha256', $token))->update(['used_at' => now()]);
            return $this->reply(false, 'Email reset gagal dikirim. Periksa konfigurasi email.', [], 503);
        }

        return $this->reply(true, 'Tautan reset password berhasil dikirim ke email Anda.');
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'regex:/^[a-f0-9]{64}$/i'], 'password' => ['required', 'string', 'min:6'],
        ]);
        $tokenHash = hash('sha256', $data['token']);
        $record = DB::table('password_reset_tokens')->where('token_hash', $tokenHash)->whereNull('used_at')
            ->where('expires_at', '>', now())->first();
        if (!$record) return $this->reply(false, 'Token reset tidak valid atau sudah kedaluwarsa.', [], 400);

        $table = $record->account_type === 'admin' ? 'admin' : 'siswa';
        DB::transaction(function () use ($record, $table, $tokenHash, $data) {
            DB::table($table)->where('id', $record->account_id)->update(['password' => Hash::make($data['password'])]);
            DB::table('password_reset_tokens')->where('token_hash', $tokenHash)->update(['used_at' => now()]);
        });

        return $this->reply(true, 'Password berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'siswa_id' => ['required', 'integer', 'min:1'], 'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6'],
        ]);
        if ((int) $data['siswa_id'] !== (int) $request->user()->id) return $this->reply(false, 'Akses data siswa ditolak.', [], 403);
        $student = DB::table('siswa')->where('id', $data['siswa_id'])->first();
        if (!$student || !Hash::check($data['old_password'], $student->password)) {
            return $this->reply(false, 'Password lama tidak sesuai.', [], 401);
        }
        DB::table('siswa')->where('id', $student->id)->update(['password' => Hash::make($data['new_password'])]);

        return $this->reply(true, 'Password berhasil diperbarui.');
    }

    public function saveFormulir(Request $request)
    {
        $studentId = (int) $request->input('siswa_id', 0);
        $form = $request->input('form', []);
        $personal = is_array($form['dataDiri'] ?? null) ? $form['dataDiri'] : [];
        $parents = is_array($form['dataOrtu'] ?? null) ? $form['dataOrtu'] : [];
        $school = is_array($form['asalSekolah'] ?? null) ? $form['asalSekolah'] : [];
        if ($studentId < 1 || trim((string) ($personal['nama'] ?? '')) === '' || strlen((string) ($personal['nik'] ?? '')) !== 16 || trim((string) ($school['sekolah'] ?? '')) === '') {
            return $this->reply(false, 'Data formulir belum lengkap.', [], 422);
        }
        if ($studentId !== (int) $request->user()->id) return $this->reply(false, 'Akses data siswa ditolak.', [], 403);
        if (!$this->studentExists($studentId)) return $this->reply(false, 'Siswa tidak ditemukan.', [], 404);

        $year = (int) ($school['tahunlulus'] ?? date('Y'));
        if ($year < 1901 || $year > 2155) $year = (int) date('Y');
        $gender = in_array($personal['jk'] ?? '', ['L', 'P'], true) ? $personal['jk'] : 'L';
        $payload = [
            'nik' => trim((string) $personal['nik']), 'tempat_lahir' => trim((string) ($personal['tempat'] ?? '')),
            'tanggal_lahir' => ($personal['tgl'] ?? '') ?: null, 'jenis_kelamin' => $gender,
            'no_wa' => trim((string) ($personal['wa'] ?? '')), 'alamat' => trim((string) ($personal['alamat'] ?? '')),
            'nama_ayah' => trim((string) ($parents['ayah'] ?? '')), 'nama_ibu' => trim((string) ($parents['ibu'] ?? '')),
            'nisn' => trim((string) ($school['nisn'] ?? '')), 'tahun_lulus' => $year,
            'jurusan_utama' => trim((string) ($form['jurusanUtama'] ?? '')),
            'jurusan_cadangan' => trim((string) ($form['jurusanCadangan'] ?? '')),
            'jadwal_id' => (int) ($form['jadwalId'] ?? 0), 'status_formulir' => 'Terkirim', 'updated_at' => now(),
        ];
        DB::transaction(function () use ($studentId, $school, $form, $payload) {
            DB::table('formulir_siswa')->updateOrInsert(['siswa_id' => $studentId], $payload);
            DB::table('siswa')->where('id', $studentId)->update([
                'sekolah_asal' => trim((string) $school['sekolah']),
                'jurusan' => trim((string) ($form['jurusanUtama'] ?? '')), 'status' => 'Diproses',
            ]);
        });

        return $this->reply(true, 'Formulir berhasil dikirim.');
    }

    public function uploadDokumen(Request $request)
    {
        $studentId = (int) $request->input('siswa_id', 0);
        $documentId = preg_replace('/[^a-z0-9_-]/i', '', (string) $request->input('document_id', ''));
        $file = $request->file('file');
        if ($studentId < 1 || $documentId === '' || !$file || !$file->isValid()) {
            return $this->reply(false, 'File upload tidak valid.', [], 422);
        }
        if ($studentId !== (int) $request->user()->id) return $this->reply(false, 'Akses data siswa ditolak.', [], 403);
        if ($file->getSize() > 2 * 1024 * 1024) return $this->reply(false, 'Ukuran file maksimal 2 MB.', [], 422);
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();
        $allowed = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        if (!isset($allowed[$extension]) || $allowed[$extension] !== $mime) {
            return $this->reply(false, 'Format file harus PDF, JPG, JPEG, atau PNG.', [], 422);
        }
        if (!$this->studentExists($studentId)) return $this->reply(false, 'Siswa tidak ditemukan.', [], 404);

        $storedName = $studentId . '_' . $documentId . '_' . Str::random(16) . '.' . $extension;
        $file->storeAs('uploads', $storedName, 'public');
        DB::table('dokumen_siswa')->updateOrInsert(
            ['siswa_id' => $studentId, 'document_id' => $documentId],
            ['nama_file' => basename($file->getClientOriginalName()), 'nama_file_server' => $storedName,
                'mime_type' => $mime, 'ukuran' => $file->getSize(), 'status_dokumen' => 'Menunggu',
                'updated_at' => now()]
        );

        return $this->reply(true, 'Dokumen berhasil diunggah.', ['document' => [
            'id' => $documentId, 'fileName' => basename($file->getClientOriginalName()), 'status' => 'menunggu',
        ]]);
    }

    public function updateStatusSiswa(Request $request)
    {
        $data = $request->validate([
            'siswa_id' => ['required', 'integer', 'min:1'], 'status' => ['required', 'in:Menunggu,Diproses,Diterima,Ditolak'],
        ]);
        if (!$this->studentExists((int) $data['siswa_id'])) return $this->reply(false, 'Siswa tidak ditemukan.', [], 404);
        DB::table('siswa')->where('id', $data['siswa_id'])->update(['status' => $data['status']]);

        return $this->reply(true, 'Status siswa berhasil diperbarui.', [
            'siswa_id' => (int) $data['siswa_id'], 'status' => $data['status'], 'catatan' => $request->input('catatan'),
        ]);
    }

    public function adminStats()
    {
        $counts = DB::table('siswa')->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(status IN ('Menunggu', 'Diproses')) as waiting")
            ->selectRaw("SUM(status = 'Diterima') as accepted")
            ->selectRaw("SUM(status = 'Ditolak') as rejected")->first();
        $distribution = DB::table('siswa')->select('jurusan as label')->selectRaw('COUNT(*) as total')
            ->groupBy('jurusan')->orderByDesc('total')->get();
        $daily = DB::table('siswa')->selectRaw('DATE(tanggal_daftar) as day, COUNT(*) as total')
            ->where('tanggal_daftar', '>=', date('Y-m-d 00:00:00', strtotime('-6 days')))->groupByRaw('DATE(tanggal_daftar)')
            ->orderBy('day')->get()->map(fn ($row) => [
                'label' => date('d M', strtotime($row->day)), 'total' => (int) $row->total,
            ]);

        return $this->reply(true, 'Statistik admin berhasil diambil.', [
            'stats' => ['total' => (int) $counts->total, 'waiting' => (int) $counts->waiting,
                'accepted' => (int) $counts->accepted, 'rejected' => (int) $counts->rejected],
            'distribution' => $distribution, 'daily' => $daily,
        ]);
    }

    public function simpanPengumuman(Request $request)
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:200'], 'kategori' => ['required', 'string', 'max:100'],
            'target' => ['required', 'string', 'max:100'], 'isi' => ['required', 'string'],
        ]);
        DB::table('pengumuman')->insert($data + ['created_at' => now(), 'updated_at' => now()]);

        return $this->reply(true, 'Pengumuman berhasil disimpan.');
    }
}