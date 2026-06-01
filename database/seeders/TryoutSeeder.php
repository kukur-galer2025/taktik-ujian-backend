<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tryout;
use App\Models\Question;

class TryoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tryout = Tryout::create([
            'title' => 'Simulasi SKD CPNS 2026 - Paket 1',
            'description' => 'Tryout lengkap TWK, TIU, TKP sesuai kisi-kisi terbaru BKN.',
            'duration_minutes' => 100,
        ]);

        // Soal TWK
        Question::create([
            'tryout_id' => $tryout->id,
            'type' => 'TWK',
            'text' => 'Pancasila disahkan sebagai dasar negara pada tanggal...',
            'option_a' => '17 Agustus 1945',
            'option_b' => '18 Agustus 1945',
            'option_c' => '1 Juni 1945',
            'option_d' => '22 Juni 1945',
            'option_e' => '29 Mei 1945',
            'score_b' => 5, // Jawaban benar bernilai 5
            'explanation' => 'Pancasila disahkan sebagai dasar negara oleh Panitia Persiapan Kemerdekaan Indonesia (PPKI) pada sidang pertamanya tanggal 18 Agustus 1945, bersamaan dengan pengesahan UUD 1945.'
        ]);

        // Soal TIU
        Question::create([
            'tryout_id' => $tryout->id,
            'type' => 'TIU',
            'text' => '1, 4, 9, 16, ... Angka selanjutnya adalah?',
            'option_a' => '20',
            'option_b' => '24',
            'option_c' => '25',
            'option_d' => '36',
            'option_e' => '49',
            'score_c' => 5,
            'explanation' => 'Deret angka tersebut merupakan deret bilangan kuadrat (1², 2², 3², 4²). Maka angka selanjutnya adalah 5² = 25.'
        ]);

        // Soal TKP (Nilai 1-5)
        Question::create([
            'tryout_id' => $tryout->id,
            'type' => 'TKP',
            'text' => 'Saat Anda ditugaskan ke daerah terpencil yang tidak ada sinyal internet, sikap Anda...',
            'option_a' => 'Menolak dengan halus karena internet penting bagi saya.',
            'option_b' => 'Menerima tugas tersebut dengan berat hati.',
            'option_c' => 'Menerima tugas tersebut dan mencoba beradaptasi.',
            'option_d' => 'Menerima tugas dan mencari solusi komunikasi lain yang tersedia.',
            'option_e' => 'Menerima tugas sebagai tantangan dan peluang mengabdi pada negara secara tulus.',
            'score_a' => 1,
            'score_b' => 2,
            'score_c' => 3,
            'score_d' => 4,
            'score_e' => 5,
            'explanation' => 'Jawaban terbaik (skor 5) adalah E. Hal ini menunjukkan profesionalisme, integritas, dan pengabdian yang tinggi sebagai seorang ASN dalam kondisi apapun. Opsi D juga baik (skor 4) karena proaktif mencari solusi.'
        ]);
    }
}
