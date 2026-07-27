<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Three programs that cover the three cases the app has to handle: a featured
 * public beginner program, a public split with a rest day in it, and a private
 * program reachable only through its access code.
 *
 * `goal` is stored as a stable slug, like `equipment` on exercises, because the
 * UI translates it.
 */
class ProgramSeeder extends Seeder
{
    /**
     * @var list<array{program: array<string, mixed>, days: list<array<string, mixed>>}>
     */
    private const PROGRAMS = [
        [
            'program' => [
                'slug' => 'beginner-full-body-3-day',
                'name_ar' => 'برنامج المبتدئ — ثلاثة أيام',
                'name_en' => 'Beginner Full-Body — 3 Days',
                'description_ar' => 'ثلاثة أيام للجسم كامل تبني أساسًا في الحركات المركّبة قبل الانتقال إلى التقسيم. ابدأ بأوزان خفيفة وركّز على إتقان الشكل، وزد الوزن أسبوعيًا بمقدار بسيط.',
                'description_en' => 'Three full-body days that build a base in the compound lifts before moving to a split. Start light, own the movement, and add a small amount of weight each week.',
                'level' => 'beginner',
                'goal' => 'general-fitness',
                'is_public' => true,
                'is_featured' => true,
                'restricted' => false,
                'sort' => 1,
            ],
            'days' => [
                [
                    'day_number' => 1,
                    'title_ar' => 'الجسم كامل — أ',
                    'title_en' => 'Full Body A',
                    'focus' => 'chest',
                    'notes_ar' => 'إحماء خمس دقائق على الدراجة، ثم جولة خفيفة قبل كل حركة أساسية.',
                    'notes_en' => 'Five minutes on the bike, then one light round before each main lift.',
                    'exercises' => [
                        ['barbell-back-squat', 3, '8-10', 120, null, null, 'انزل حتى يوازي الفخذ الأرض مع بقاء الظهر مشدودًا.', 'Descend until the thighs reach parallel with the back braced.'],
                        ['barbell-bench-press', 3, '8-10', 120, '2-1-2', null, 'انزل ببطء وتحكّم، ولا ترتد من الصدر.', 'Lower slowly and under control; do not bounce off the chest.'],
                        ['seated-cable-row', 3, '10-12', 90, null, null, null, null],
                        ['seated-dumbbell-shoulder-press', 3, '10-12', 90, null, null, null, null],
                        ['plank', 3, '٣٠ ثانية', 60, null, null, 'شد البطن والمؤخرة معًا حتى لا يهبط الورك.', 'Brace the abdomen and glutes together so the hips do not sag.'],
                    ],
                ],
                [
                    'day_number' => 2,
                    'title_ar' => 'الجسم كامل — ب',
                    'title_en' => 'Full Body B',
                    'focus' => 'back',
                    'notes_ar' => null,
                    'notes_en' => null,
                    'exercises' => [
                        ['romanian-deadlift', 3, '8-10', 120, null, null, 'ادفع الورك للخلف واحفظ انحناء بسيطًا في الركبة، الحركة من الورك لا من الظهر.', 'Push the hips back and keep a soft knee; the movement comes from the hips, not the back.'],
                        ['lat-pulldown', 3, '10-12', 90, null, null, null, null],
                        ['incline-dumbbell-press', 3, '10-12', 90, null, null, null, null],
                        ['dumbbell-lateral-raise', 3, '12-15', 60, null, null, 'وزن خفيف يكفي، المهم أن ترفع بالكتف لا بالمرجحة.', 'Light weight is enough; the point is to lift with the delt, not with momentum.'],
                        ['barbell-curl', 3, '10-12', 60, null, 'A', null, null],
                        ['cable-triceps-pushdown', 3, '10-12', 60, null, 'A', 'الحركتان جولة واحدة، انتقل بينهما بلا راحة ثم استرح دقيقة.', 'These two are one round: move between them without rest, then take a minute.'],
                    ],
                ],
                [
                    'day_number' => 3,
                    'title_ar' => 'الجسم كامل — ج',
                    'title_en' => 'Full Body C',
                    'focus' => 'legs',
                    'notes_ar' => 'تبريد خمس دقائق مشي وإطالة للفخذ الخلفي.',
                    'notes_en' => 'Five minutes of walking to cool down, plus hamstring stretching.',
                    'exercises' => [
                        ['leg-press', 3, '10-12', 120, null, null, null, null],
                        ['dumbbell-lunge', 3, '10-12 لكل رجل', 90, null, null, 'خطوة طويلة تكفي لنزول الركبة الخلفية قريبًا من الأرض.', 'Take a long enough step that the rear knee can come close to the floor.'],
                        ['one-arm-dumbbell-row', 3, '10-12', 90, null, null, null, null],
                        ['push-up', 3, 'الفشل', 90, null, null, null, null],
                        ['cable-face-pull', 3, '12-15', 60, null, null, null, null],
                        ['standing-calf-raise', 3, '15-20', 60, null, null, null, null],
                    ],
                ],
            ],
        ],

        [
            'program' => [
                'slug' => 'push-pull-legs-4-day',
                'name_ar' => 'الدفع والسحب والأرجل — أربعة أيام',
                'name_en' => 'Push Pull Legs — 4 Days',
                'description_ar' => 'تقسيم كلاسيكي يفصل حركات الدفع عن السحب عن الأرجل، مع يوم راحة في المنتصف للاستشفاء. مناسب لمن أنهى ستة أشهر على الأقل من التدريب المنتظم.',
                'description_en' => 'A classic split separating push, pull and legs, with a rest day in the middle for recovery. Suited to anyone with at least six months of consistent training.',
                'level' => 'intermediate',
                'goal' => 'hypertrophy',
                'is_public' => true,
                'is_featured' => false,
                'restricted' => false,
                'sort' => 2,
            ],
            'days' => [
                [
                    'day_number' => 1,
                    'title_ar' => 'يوم الدفع',
                    'title_en' => 'Push Day',
                    'focus' => 'chest',
                    'notes_ar' => 'إحماء بجولتين خفيفتين على أول حركة قبل جولات العمل.',
                    'notes_en' => 'Warm up with two light rounds on the first lift before the working rounds.',
                    'exercises' => [
                        ['barbell-bench-press', 4, '6-8', 120, null, null, 'ثبّت لوح الكتف وابقِ المرفق قريبًا من الجسم في النزول.', 'Set the shoulder blades and keep the elbows tucked on the way down.'],
                        ['incline-dumbbell-press', 4, '8-10', 120, null, null, null, null],
                        ['standing-overhead-press', 3, '8-10', 120, null, null, null, null],
                        ['cable-crossover', 3, '12-15', 60, null, 'A', null, null],
                        ['dumbbell-lateral-raise', 4, '12-15', 60, null, 'A', 'جولة مزدوجة، بلا راحة بين الحركتين.', 'A paired round with no rest between the two movements.'],
                        ['cable-triceps-pushdown', 3, '10-12', 60, null, null, null, null],
                        ['overhead-dumbbell-triceps-extension', 3, '12-15', 60, null, null, null, null],
                    ],
                ],
                [
                    'day_number' => 2,
                    'title_ar' => 'يوم السحب',
                    'title_en' => 'Pull Day',
                    'focus' => 'back',
                    'notes_ar' => null,
                    'notes_en' => null,
                    'exercises' => [
                        ['barbell-deadlift', 4, '5', 180, null, null, 'ابدأ بالبار قريبًا من الساق، وارفع بالأرجل قبل الظهر.', 'Start with the bar close to the shins and drive with the legs before the back.'],
                        ['pull-up', 4, 'الفشل', 120, null, null, 'لو لم تكمل ست عدات، استعن بالمطاط بدل تقصير المدى.', 'If you cannot make six reps, use a band rather than cutting the range short.'],
                        ['barbell-bent-over-row', 4, '8-10', 120, null, null, null, null],
                        ['seated-cable-row', 3, '10-12', 90, null, null, null, null],
                        ['cable-face-pull', 3, '15-20', 60, null, null, null, null],
                        ['alternating-dumbbell-curl', 3, '10-12', 60, null, 'B', null, null],
                        ['hammer-curl', 3, '12-15', 60, null, 'B', null, null],
                    ],
                ],
                [
                    'day_number' => 3,
                    'title_ar' => 'راحة',
                    'title_en' => 'Rest',
                    'focus' => null,
                    'notes_ar' => 'مشي خفيف عشرين دقيقة وإطالة للجزء السفلي. الاستشفاء جزء من البرنامج لا انقطاع عنه.',
                    'notes_en' => 'Twenty minutes of easy walking and lower-body stretching. Recovery is part of the program, not a break from it.',
                    'exercises' => [],
                ],
                [
                    'day_number' => 4,
                    'title_ar' => 'يوم الأرجل',
                    'title_en' => 'Leg Day',
                    'focus' => 'legs',
                    'notes_ar' => 'إحماء عشر دقائق ودورتان خفيفتان على السكوات قبل جولات العمل.',
                    'notes_en' => 'Ten minutes of warm-up and two light rounds of squats before the working rounds.',
                    'exercises' => [
                        ['barbell-back-squat', 4, '6-8', 180, '3-0-1', null, 'انزل بعمق مريح واحفظ الركبة باتجاه أطراف القدم.', 'Descend to a comfortable depth and track the knees over the toes.'],
                        ['romanian-deadlift', 4, '8-10', 120, null, null, null, null],
                        ['leg-press', 3, '10-12', 120, null, null, null, null],
                        ['lying-leg-curl', 3, '12-15', 60, null, null, null, null],
                        ['leg-extension', 3, '12-15', 60, null, null, null, null],
                        ['standing-calf-raise', 4, '15-20', 60, null, null, null, null],
                        ['hanging-leg-raise', 3, 'الفشل', 60, null, null, null, null],
                    ],
                ],
            ],
        ],

        [
            'program' => [
                'slug' => 'private-cut-8-weeks',
                'name_ar' => 'برنامج خاص — تنشيف ثمانية أسابيع',
                'name_en' => 'Private Cut — 8 Weeks',
                'description_ar' => 'برنامج خاص يُعطى للمتدرب برابط سري من المدرب. حجم تدريبي متوسط مع راحة قصيرة وعمل تحمّل في اليوم الثالث.',
                'description_en' => 'A private program handed to the trainee as a secret link from the coach. Moderate volume, short rest, and conditioning work on the third day.',
                'level' => 'intermediate',
                'goal' => 'fat-loss',
                'is_public' => false,
                'is_featured' => false,
                'restricted' => true,
                'sort' => 3,
            ],
            'days' => [
                [
                    'day_number' => 1,
                    'title_ar' => 'علوي',
                    'title_en' => 'Upper Body',
                    'focus' => 'chest',
                    'notes_ar' => 'الراحة قصيرة في هذا البرنامج، التزم بها ولو اضطررت لتخفيف الوزن.',
                    'notes_en' => 'Rest is deliberately short here. Stick to it even if it means dropping the weight.',
                    'exercises' => [
                        ['chest-press-machine', 4, '10-12', 90, null, null, null, null],
                        ['lat-pulldown', 4, '10-12', 90, null, null, null, null],
                        ['seated-dumbbell-shoulder-press', 3, '12-15', 90, null, null, null, null],
                        ['cable-curl', 3, '12-15', 60, null, 'C', null, null],
                        ['bench-dip', 3, 'الفشل', 60, null, 'C', null, null],
                    ],
                ],
                [
                    'day_number' => 2,
                    'title_ar' => 'سفلي',
                    'title_en' => 'Lower Body',
                    'focus' => 'legs',
                    'notes_ar' => null,
                    'notes_en' => null,
                    'exercises' => [
                        ['front-squat', 4, '8-10', 120, null, null, 'ابقِ المرفقين مرفوعين طوال الحركة، لو سقطا سقط البار معهما.', 'Keep the elbows high throughout; if they drop, the bar drops with them.'],
                        ['bulgarian-split-squat', 3, '10-12 لكل رجل', 90, null, null, null, null],
                        ['barbell-hip-thrust', 3, '12-15', 90, null, null, null, null],
                        ['lying-leg-curl', 3, '12-15', 60, null, null, null, null],
                        ['standing-calf-raise', 4, '15-20', 60, null, null, null, null],
                    ],
                ],
                [
                    'day_number' => 3,
                    'title_ar' => 'تحمّل وبطن',
                    'title_en' => 'Conditioning & Core',
                    'focus' => 'cardio',
                    'notes_ar' => 'إحماء خمس دقائق، وتبريد خمس دقائق مشي في النهاية.',
                    'notes_en' => 'Five minutes of warm-up and five minutes of walking to cool down at the end.',
                    'exercises' => [
                        ['rowing-machine', 3, 'خمس دقائق', 90, null, null, null, null],
                        ['jump-rope', 4, 'دقيقتان', 60, null, null, null, null],
                        ['cable-crunch', 3, '15-20', 60, null, null, null, null],
                        ['plank', 3, '٤٥ ثانية', 60, null, null, null, null],
                        ['farmer-carry', 3, '٤٠ مترًا', 90, null, null, 'امشِ بخطوة ثابتة، وأنزل الدمبل لو انحنى الكتف للأمام.', 'Walk with a steady stride and put the dumbbells down if the shoulders round forward.'],
                    ],
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $muscleGroups = MuscleGroup::query()->pluck('id', 'slug');
        $exercises = Exercise::query()->pluck('id', 'slug');

        foreach (self::PROGRAMS as $definition) {
            $program = $this->upsertProgram($definition['program'], count($definition['days']));

            foreach ($definition['days'] as $day) {
                $programDay = ProgramDay::query()->updateOrCreate(
                    ['program_id' => $program->getKey(), 'day_number' => $day['day_number']],
                    [
                        'title_ar' => $day['title_ar'],
                        'title_en' => $day['title_en'],
                        'focus_muscle_id' => $day['focus'] === null ? null : ($muscleGroups[$day['focus']] ?? null),
                        'is_rest_day' => $day['exercises'] === [],
                        'notes_ar' => $day['notes_ar'],
                        'notes_en' => $day['notes_en'],
                    ]
                );

                $this->syncDayExercises($programDay, $day['exercises'], $exercises);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertProgram(array $attributes, int $daysCount): Program
    {
        $existing = Program::withTrashed()->where('slug', $attributes['slug'])->first();

        // Keep an already issued access code: the trainee's secret link must not
        // change every time the seeder runs.
        $accessCode = $attributes['restricted']
            ? ($existing?->access_code ?? Str::upper(Str::random(8)))
            : null;

        return Program::query()->updateOrCreate(
            ['slug' => $attributes['slug']],
            [
                'name_ar' => $attributes['name_ar'],
                'name_en' => $attributes['name_en'],
                'description_ar' => $attributes['description_ar'],
                'description_en' => $attributes['description_en'],
                'days_count' => $daysCount,
                'level' => $attributes['level'],
                'goal' => $attributes['goal'],
                'cover_path' => null,
                'is_public' => $attributes['is_public'],
                'is_featured' => $attributes['is_featured'],
                'access_code' => $accessCode,
                'published_at' => $attributes['is_public'] ? now()->subWeeks(2) : null,
                'sort' => $attributes['sort'],
            ]
        );
    }

    /**
     * @param  list<array{0: string, 1: int, 2: string, 3: int, 4: string|null, 5: string|null, 6: string|null, 7: string|null}>  $rows
     * @param  Collection<string, int>  $exercises
     */
    private function syncDayExercises(ProgramDay $day, array $rows, $exercises): void
    {
        foreach ($rows as $sort => $row) {
            [$slug, $sets, $reps, $restSeconds, $tempo, $supersetGroup, $notesAr, $notesEn] = $row;

            $exerciseId = $exercises[$slug] ?? null;

            if ($exerciseId === null) {
                continue;
            }

            ProgramExercise::query()->updateOrCreate(
                ['program_day_id' => $day->getKey(), 'exercise_id' => $exerciseId],
                [
                    'sort' => $sort,
                    'sets' => $sets,
                    'reps' => $reps,
                    'rest_seconds' => $restSeconds,
                    'tempo' => $tempo,
                    'weight_note' => null,
                    'coach_notes_ar' => $notesAr,
                    'coach_notes_en' => $notesEn,
                    'superset_group' => $supersetGroup,
                ]
            );
        }
    }
}
