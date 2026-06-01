<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tryout;
use App\Models\Question;
use App\Models\User;
use App\Models\UserResult;
use App\Models\Review;
use App\Models\SubCategory;
use App\Models\Bundle;
use App\Models\Voucher;
use Illuminate\Support\Facades\Hash;

class DummyDataSeeder extends Seeder
{
    public function run()
    {
        // 1. Create SubCategories
        $subs = [
            ['type' => 'TWK', 'name' => 'Nasionalisme'],
            ['type' => 'TWK', 'name' => 'Integritas'],
            ['type' => 'TWK', 'name' => 'Bela Negara'],
            ['type' => 'TIU', 'name' => 'Kemampuan Verbal'],
            ['type' => 'TIU', 'name' => 'Deret Angka'],
            ['type' => 'TIU', 'name' => 'Silogisme'],
            ['type' => 'TIU', 'name' => 'Figural'],
            ['type' => 'TKP', 'name' => 'Pelayanan Publik'],
            ['type' => 'TKP', 'name' => 'Jejaring Kerja'],
            ['type' => 'TKP', 'name' => 'Sosial Budaya'],
            ['type' => 'TKP', 'name' => 'TIK'],
            ['type' => 'TKP', 'name' => 'Profesionalisme'],
            ['type' => 'TKP', 'name' => 'Anti Radikalisme'],
        ];

        foreach ($subs as $sub) {
            SubCategory::firstOrCreate(['type' => $sub['type'], 'name' => $sub['name']]);
        }

        // 2. Create Tryouts
        $tryouts = [];
        $tryoutData = [
            ['title' => 'Taktik Ujian SKD CPNS 2026 - Paket Premium 1', 'desc' => 'Paket tryout paling komprehensif, standar HOTS BKN. Berisi soal TWK, TIU, dan TKP lengkap.', 'category' => 'SKD Umum', 'duration' => 100, 'price' => 25000, 'cover' => ''],
            ['title' => 'Tryout Kedinasan STAN/IPDN 2026', 'desc' => 'Fokus pada soal-soal numerik dan figural berkecepatan tinggi untuk seleksi kedinasan.', 'category' => 'Kedinasan', 'duration' => 100, 'price' => 35000, 'cover' => ''],
            ['title' => 'Drill Khusus TIU - Logika & Numerik', 'desc' => 'Latihan intensif khusus TIU: deret angka, silogisme, figural, dan kemampuan verbal.', 'category' => 'Khusus TIU', 'duration' => 45, 'price' => 10000, 'cover' => ''],
            ['title' => 'Simulasi Full SKD CPNS 2026 - Free Trial', 'desc' => 'Paket gratis untuk mencoba sensasi CAT di Taktik Ujian.', 'category' => 'Gratis', 'duration' => 100, 'price' => 0, 'cover' => ''],
        ];

        foreach ($tryoutData as $idx => $td) {
            $tryouts[] = Tryout::firstOrCreate(
                ['title' => $td['title']],
                [
                    'description' => $td['desc'],
                    'duration_minutes' => $td['duration'],
                    'category' => $td['category'],
                    'price' => $td['price'],
                    'cover_image' => $td['cover'] ?: null,
                ]
            );
        }

        // 3. Create Questions for Tryout 1 and Free Trial
        $t1 = $tryouts[0];
        $tFree = $tryouts[3];
        $questionsT1 = [];

        // TWK Questions
        $questionsT1[] = Question::firstOrCreate(
            ['tryout_id' => $t1->id, 'text' => 'Pancasila disahkan sebagai dasar negara pada tanggal...'],
            [
                'type' => 'TWK',
                'sub_category' => 'Nasionalisme',
                'option_a' => '17 Agustus 1945', 'option_b' => '18 Agustus 1945', 'option_c' => '1 Juni 1945', 'option_d' => '22 Juni 1945', 'option_e' => '29 Mei 1945',
                'score_a' => 0, 'score_b' => 5, 'score_c' => 0, 'score_d' => 0, 'score_e' => 0,
                'explanation' => 'Disahkan oleh PPKI pada 18 Agustus 1945.'
            ]
        );
        $questionsT1[] = Question::firstOrCreate(
            ['tryout_id' => $t1->id, 'text' => 'Sikap rela berkorban demi bangsa dan negara merupakan wujud dari nilai...'],
            [
                'type' => 'TWK',
                'sub_category' => 'Bela Negara',
                'option_a' => 'Patriotisme', 'option_b' => 'Chauvinisme', 'option_c' => 'Kosmopolitanisme', 'option_d' => 'Etnosentrisme', 'option_e' => 'Liberalisme',
                'score_a' => 5, 'score_b' => 0, 'score_c' => 0, 'score_d' => 0, 'score_e' => 0,
                'explanation' => 'Patriotisme berarti cinta tanah air dan rela berkorban.'
            ]
        );

        // TIU Questions
        $questionsT1[] = Question::firstOrCreate(
            ['tryout_id' => $t1->id, 'text' => '1, 4, 9, 16, 25, ... Angka selanjutnya?'],
            [
                'type' => 'TIU',
                'sub_category' => 'Deret Angka',
                'option_a' => '30', 'option_b' => '36', 'option_c' => '40', 'option_d' => '42', 'option_e' => '49',
                'score_a' => 0, 'score_b' => 5, 'score_c' => 0, 'score_d' => 0, 'score_e' => 0,
                'explanation' => 'Deret kuadrat: 1², 2², 3², 4², 5², 6² = 36.'
            ]
        );
        $questionsT1[] = Question::firstOrCreate(
            ['tryout_id' => $t1->id, 'text' => 'Semua pegawai berseragam. Sebagian pegawai berdasi. Kesimpulannya?'],
            [
                'type' => 'TIU',
                'sub_category' => 'Silogisme',
                'option_a' => 'Semua pegawai berseragam dan berdasi.', 'option_b' => 'Sebagian pegawai tidak berseragam.', 'option_c' => 'Sebagian pegawai berseragam dan berdasi.', 'option_d' => 'Semua yang berdasi pasti berseragam.', 'option_e' => 'Tidak ada kesimpulan.',
                'score_a' => 0, 'score_b' => 0, 'score_c' => 5, 'score_d' => 0, 'score_e' => 0,
                'explanation' => 'Sebagian pegawai masuk ke irisan berdasi dan berseragam.'
            ]
        );

        // TKP Questions
        $questionsT1[] = Question::firstOrCreate(
            ['tryout_id' => $t1->id, 'text' => 'Saat Anda sedang sibuk melayani warga, sistem komputer tiba-tiba mati...'],
            [
                'type' => 'TKP',
                'sub_category' => 'Pelayanan Publik',
                'option_a' => 'Menyuruh warga pulang dan kembali besok.', 'option_b' => 'Menunggu sampai komputer menyala sendiri.', 'option_c' => 'Meminta warga bersabar dan segera memanggil teknisi IT.', 'option_d' => 'Melayani secara manual sementara waktu agar warga tidak menunggu lama.', 'option_e' => 'Marah pada bagian IT.',
                'score_a' => 1, 'score_b' => 2, 'score_c' => 4, 'score_d' => 5, 'score_e' => 1,
                'explanation' => 'Melayani secara manual menunjukkan inisiatif pelayanan publik prima.'
            ]
        );

        // Copy questions to Free Trial
        foreach ($questionsT1 as $q) {
            $newQ = $q->replicate();
            $newQ->tryout_id = $tFree->id;
            $newQ->save();
        }

        // 4. Create Bundles
        $bundle = Bundle::firstOrCreate([
            'title' => 'Bundle SKD Super Intensif 2026',
        ], [
            'description' => 'Dapatkan akses ke 3 paket Tryout SKD premium beserta pembahasan lengkap. Dirancang khusus untuk memaksimalkan peluang lolos Passing Grade.',
            'price' => 70000,
            'discount_price' => 55000,
            'is_active' => true,
        ]);
        $bundle->tryouts()->sync([$tryouts[0]->id, $tryouts[1]->id, $tryouts[2]->id]);

        $bundle2 = Bundle::firstOrCreate([
            'title' => 'Bundle Taktik Ujian Kedinasan',
        ], [
            'description' => 'Paket Tryout komprehensif untuk Kedinasan dengan soal-soal HOTS terbaru dan pembahasan mendalam.',
            'price' => 100000,
            'discount_price' => 75000,
            'is_active' => true,
        ]);
        $bundle2->tryouts()->sync([$tryouts[1]->id, $tryouts[2]->id]);

        // 5. Create Vouchers
        Voucher::firstOrCreate(['code' => 'TAKTIK50'], [
            'discount_type' => 'percentage',
            'discount_value' => 50,
            'max_uses' => 100,
            'used_count' => 0,
            'expires_at' => now()->addMonths(1),
            'is_active' => true,
        ]);
        
        Voucher::firstOrCreate(['code' => 'POTONGAN20K'], [
            'discount_type' => 'fixed',
            'discount_value' => 20000,
            'max_uses' => 50,
            'used_count' => 0,
            'expires_at' => now()->addMonths(1),
            'is_active' => true,
        ]);

        // 6. Create Dummy Users (20 users to populate leaderboard)
        $users = [];
        for ($i = 1; $i <= 20; $i++) {
            $users[] = User::firstOrCreate(
                ['email' => "peserta{$i}@taktikujian.com"],
                [
                    'name' => "Siswa Taktik {$i}",
                    'password' => Hash::make('password'),
                    'is_admin' => false,
                ]
            );
        }

        // 7. Generate Tryout Results for the users
        $options = ['A', 'B', 'C', 'D', 'E'];
        
        foreach ($users as $user) {
            $userAnswers = [];
            $scoreTwk = 0; $scoreTiu = 0; $scoreTkp = 0;
            
            foreach ($questionsT1 as $q) {
                $chanceOfCorrect = rand(30, 90); 
                $chosenOpt = '';

                if ($q->type == 'TKP') {
                    $chosenOpt = (rand(1, 100) > 20) ? 'D' : 'C'; 
                    if ($q->text == 'Saat Anda sedang sibuk melayani warga, sistem komputer tiba-tiba mati...') $chosenOpt = (rand(1,10) > 3) ? 'D' : 'C';
                } else {
                    $correctOpt = '';
                    foreach ($options as $opt) {
                        $field = 'score_' . strtolower($opt);
                        if ($q->$field == 5) $correctOpt = $opt;
                    }
                    
                    if (rand(1, 100) <= $chanceOfCorrect) {
                        $chosenOpt = $correctOpt;
                    } else {
                        $wrongOptions = array_diff($options, [$correctOpt]);
                        $chosenOpt = $wrongOptions[array_rand($wrongOptions)];
                    }
                }
                
                $userAnswers[$q->id] = $chosenOpt;
                $scoreField = 'score_' . strtolower($chosenOpt);
                $points = $q->$scoreField ?? 0;
                
                if ($q->type == 'TWK') $scoreTwk += $points;
                if ($q->type == 'TIU') $scoreTiu += $points;
                if ($q->type == 'TKP') $scoreTkp += $points;
            }

            // Multiply scores to simulate a full tryout
            $scoreTwk = min(150, $scoreTwk * rand(10, 15));
            $scoreTiu = min(175, $scoreTiu * rand(12, 17));
            $scoreTkp = min(225, $scoreTkp * rand(30, 45)); 

            $totalScore = $scoreTwk + $scoreTiu + $scoreTkp;
            $isPassed = ($scoreTwk >= 65 && $scoreTiu >= 80 && $scoreTkp >= 166);

            UserResult::create([
                'user_id' => $user->id,
                'tryout_id' => $t1->id,
                'score_twk' => $scoreTwk,
                'score_tiu' => $scoreTiu,
                'score_tkp' => $scoreTkp,
                'total_score' => $totalScore,
                'is_passed' => $isPassed,
                'time_taken_minutes' => rand(45, 95),
                'answers' => $userAnswers,
            ]);
        }
    }
}
