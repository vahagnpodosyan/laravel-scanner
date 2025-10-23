🔍 Сканирование файлов Laravel-проекта... 📦 Корневая директория: /var/www/html ✅ Активные опции сканирования: ✓ controllers ✓ models ✓ routes ✓ views 🌳 Иерархия файлов (только выбранные каталоги): ├── app │ ├── Http │ │ └── Controllers │ │ ├── AdminController.php │ │ ├── AdminLoginController.php │ │ ├── AuthController.php │ │ ├── Controller.php │ │ ├── LeaderboardController.php │ │ ├── QuestionController.php │ │ ├── ResearchPaperController.php │ │ ├── SubjectController.php │ │ ├── TestController.php │ │ ├── TestSessionController.php │ │ ├── UserController.php │ │ └── VideoSessionController.php │ └── Models │ ├── CompetitionLevel.php │ ├── Leaderboard.php │ ├── QualificationThreshold.php │ ├── QualifiedUser.php │ ├── Question.php │ ├── ResearchPaper.php │ ├── Subject.php │ ├── SubjectRegistration.php │ ├── Test.php │ ├── TestSession.php │ ├── User.php │ ├── UserAnswer.php │ └── VideoSession.php ├── routes │ ├── api.php │ ├── console.php │ └── web.php ├── resources │ └── views │ ├── admin │ │ ├── dashboard.blade.php │ │ ├── layout.blade.php │ │ ├── login.blade.php │ │ ├── subjects │ │ │ ├── create.blade.php │ │ │ ├── edit.blade.php │ │ │ ├── index.blade.php │ │ │ └── show.blade.php │ │ └── users │ │ ├── create.blade.php │ │ ├── edit.blade.php │ │ ├── index.blade.php │ │ └── show.blade.php │ └── welcome.blade.php ├── .env ├── artisan ├── composer.json └── package.json ──────────────────────────────────────────────────────────────────────────────── 📋 Список всех файлов в выбранных каталогах: 001) app/Http/Controllers/AdminController.php 002) app/Http/Controllers/AdminLoginController.php 003) app/Http/Controllers/AuthController.php 004) app/Http/Controllers/Controller.php 005) app/Http/Controllers/LeaderboardController.php 006) app/Http/Controllers/QuestionController.php 007) app/Http/Controllers/ResearchPaperController.php 008) app/Http/Controllers/SubjectController.php 009) app/Http/Controllers/TestController.php 010) app/Http/Controllers/TestSessionController.php 011) app/Http/Controllers/UserController.php 012) app/Http/Controllers/VideoSessionController.php 013) app/Models/CompetitionLevel.php 014) app/Models/Leaderboard.php 015) app/Models/QualificationThreshold.php 016) app/Models/QualifiedUser.php 017) app/Models/Question.php 018) app/Models/ResearchPaper.php 019) app/Models/Subject.php 020) app/Models/SubjectRegistration.php 021) app/Models/Test.php 022) app/Models/TestSession.php 023) app/Models/User.php 024) app/Models/UserAnswer.php 025) app/Models/VideoSession.php 026) routes/api.php 027) routes/console.php 028) routes/web.php 029) resources/views/admin/dashboard.blade.php 030) resources/views/admin/layout.blade.php 031) resources/views/admin/login.blade.php 032) resources/views/admin/subjects/create.blade.php 033) resources/views/admin/subjects/edit.blade.php 034) resources/views/admin/subjects/index.blade.php 035) resources/views/admin/subjects/show.blade.php 036) resources/views/admin/users/create.blade.php 037) resources/views/admin/users/edit.blade.php 038) resources/views/admin/users/index.blade.php 039) resources/views/admin/users/show.blade.php 040) resources/views/welcome.blade.php 041) .env 042) artisan 043) composer.json 044) package.json ──────────────────────────────────────────────────────────────────────────────── 📄 Содержимое файлов: ════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Http/Controllers/AdminController.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Test;
use App\Models\Subject;
use App\Models\ResearchPaper;
use App\Models\TestSession;
use App\Models\Leaderboard;
use App\Models\SubjectRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_tests' => Test::count(),
            'total_subjects' => Subject::count(),
            'total_research_papers' => ResearchPaper::count(),
            'active_sessions' => TestSession::where('status', 'in_progress')->count(),
            'completed_sessions' => TestSession::where('status', 'completed')->count(),
        ];

        $recentUsers = User::latest()->take(5)->get();
        $recentSessions = TestSession::with(['user', 'test'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentSessions'));
    }

    public function users(Request $request)
    {
        $query = User::with(['subjectRegistrations', 'testSessions']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('school', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $users = $query->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function userCreate()
    {
        return view('admin.users.create');
    }

    public function userStore(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:admin,judge,student',
            'date_of_birth' => 'required|date',
            'school' => 'required|string|max:255',
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'is_active' => 'boolean'
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->has('is_active');

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully');
    }

    public function userShow(User $user)
    {
        $user->load([
            'subjectRegistrations.subject', 
            'testSessions.test', 
            'researchPapers',
            'videoSessionsAsJudge.researchPaper.user'
        ]);

        $testStats = [
            'total_sessions' => $user->testSessions->count(),
            'completed_sessions' => $user->testSessions->where('status', 'completed')->count(),
            'average_score' => $user->testSessions->where('status', 'completed')->avg('total_score'),
            'total_research_papers' => $user->researchPapers->count(),
        ];

        return view('admin.users.show', compact('user', 'testStats'));
    }

    public function userEdit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function userUpdate(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id)
            ],
            'password' => 'nullable|min:8|confirmed',
            'role' => 'required|in:admin,judge,student',
            'date_of_birth' => 'required|date',
            'school' => 'required|string|max:255',
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'is_active' => 'boolean'
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->has('is_active');

        $user->update($validated);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User updated successfully');
    }

    public function userDestroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully');
    }

    public function userToggleStatus(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "User {$status} successfully");
    }

    public function tests(Request $request)
    {
        $query = Test::with(['subject', 'competitionLevel']);

        // Поиск
        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        // Фильтры
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('level_id')) {
            $query->where('competition_level_id', $request->level_id);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $tests = $query->latest()->paginate(20);
        $subjects = Subject::active()->get();
        $levels = CompetitionLevel::active()->get();

        return view('admin.tests.index', compact('tests', 'subjects', 'levels'));
    }

    public function testCreate()
    {
        $subjects = Subject::active()->get();
        $levels = CompetitionLevel::active()->get();
        return view('admin.tests.create', compact('subjects', 'levels'));
    }

    public function testStore(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'competition_level_id' => 'required|exists:competition_levels,id',
            'time_limit' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        Test::create($validated + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.tests.index')->with('success', 'Test created successfully.');
    }

    public function testEdit(Test $test)
    {
        $subjects = Subject::active()->get();
        $levels = CompetitionLevel::active()->get();
        return view('admin.tests.edit', compact('test', 'subjects', 'levels'));
    }

    public function testUpdate(Request $request, Test $test)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'competition_level_id' => 'required|exists:competition_levels,id',
            'time_limit' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $test->update($validated + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.tests.index')->with('success', 'Test updated successfully.');
    }

    public function testDestroy(Test $test)
    {
        if ($test->testSessions()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete test with existing sessions.');
        }
        $test->delete();
        return redirect()->route('admin.tests.index')->with('success', 'Test deleted successfully.');
    }

    public function testToggleStatus(Test $test)
    {
        $test->update(['is_active' => !$test->is_active]);
        return redirect()->back()->with('success', 'Test status updated.');
    }


    public function researchPapers()
    {
        $papers = ResearchPaper::with(['user', 'videoSessions'])
            ->latest()
            ->paginate(20);

        return view('admin.research-papers.index', compact('papers'));
    }

    public function leaderboard()
    {
        $leaderboard = Leaderboard::with(['user', 'subject', 'competitionLevel'])
            ->orderBy('total_score', 'desc')
            ->paginate(50);

        return view('admin.leaderboard.index', compact('leaderboard'));
    }

    public function analytics()
    {
        // User analytics
        $userStats = DB::table('users')
            ->select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->get();

        $userRegistrations = DB::table('users')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Test session analytics
        $testStats = DB::table('test_sessions')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return view('admin.analytics', compact('userStats', 'userRegistrations', 'testStats'));
    }


    
public function subjects(Request $request)
{
    $query = Subject::with(['tests', 'subjectRegistrations']);

    // Search functionality
    if ($request->has('search') && $request->search) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Filter by status
    if ($request->has('status') && $request->status) {
        if ($request->status === 'active') {
            $query->where('is_active', true);
        } elseif ($request->status === 'inactive') {
            $query->where('is_active', false);
        }
    }

    $subjects = $query->latest()->paginate(20);

    return view('admin.subjects.index', compact('subjects'));
}

public function subjectCreate()
{
    return view('admin.subjects.create');
}

public function subjectStore(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:subjects',
        'code' => 'required|string|max:50|unique:subjects',
        'description' => 'required|string',
        'image_url' => 'nullable|url',
        'color' => 'nullable|string|max:7',
        'is_active' => 'boolean'
    ]);

    $validated['is_active'] = $request->has('is_active');

    Subject::create($validated);

    return redirect()->route('admin.subjects.index')
        ->with('success', 'Subject created successfully');
}

public function subjectShow(Subject $subject)
{
    $subject->load(['tests', 'subjectRegistrations.user', 'qualificationThresholds.competitionLevel']);
    
    $stats = [
        'total_tests' => $subject->tests->count(),
        'total_registrations' => $subject->subjectRegistrations->count(),
        'active_registrations' => $subject->subjectRegistrations->where('payment_status', 'completed')->count(),
        'average_test_score' => $subject->tests->flatMap->testSessions->where('status', 'completed')->avg('total_score')
    ];

    return view('admin.subjects.show', compact('subject', 'stats'));
}

public function subjectEdit(Subject $subject)
{
    return view('admin.subjects.edit', compact('subject'));
}

public function subjectUpdate(Request $request, Subject $subject)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:subjects,name,' . $subject->id,
        'code' => 'required|string|max:50|unique:subjects,code,' . $subject->id,
        'description' => 'required|string',
        'image_url' => 'nullable|url',
        'color' => 'nullable|string|max:7',
        'is_active' => 'boolean'
    ]);

    $validated['is_active'] = $request->has('is_active');

    $subject->update($validated);

    return redirect()->route('admin.subjects.show', $subject)
        ->with('success', 'Subject updated successfully');
}

public function subjectDestroy(Subject $subject)
{
    // Check if subject has related records
    if ($subject->tests()->exists()) {
        return redirect()->back()->with('error', 'Cannot delete subject that has tests. Please delete the tests first.');
    }

    if ($subject->subjectRegistrations()->exists()) {
        return redirect()->back()->with('error', 'Cannot delete subject that has registrations.');
    }

    $subject->delete();

    return redirect()->route('admin.subjects.index')
        ->with('success', 'Subject deleted successfully');
}

public function subjectToggleStatus(Subject $subject)
{
    $subject->update([
        'is_active' => !$subject->is_active
    ]);

    $status = $subject->is_active ? 'activated' : 'deactivated';

    return redirect()->back()->with('success', "Subject {$status} successfully");
}
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Http/Controllers/AdminLoginController.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Check if user is admin or judge
            if ($user->isAdmin() || $user->isJudge()) {
                return redirect()->intended('/admin/dashboard');
            } else {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'You do not have access to the admin panel.',
                ]);
            }
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Http/Controllers/AuthController.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'date_of_birth' => 'required|date',
            'school' => 'required|string|max:255',
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'student_id_image' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $imagePath = null;
        if ($request->hasFile('student_id_image')) {
            $imagePath = $request->file('student_id_image')->store('student_ids', 'public');
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'school' => $request->school,
            'country' => $request->country,
            'city' => $request->city,
            'student_id_image' => $imagePath,
            'role' => 'student'
        ]);

        $token = $user->createToken('zhanna_education')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ], 201);
    }

    
    public function login(Request $request)
{
    \Log::info('Login attempt', ['email' => $request->email, 'ip' => $request->ip()]);

    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if ($validator->fails()) {
        \Log::warning('Login validation failed', ['errors' => $validator->errors()]);
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $user = User::whereRaw('LOWER(email) = ?', [strtolower($request->email)])->first();

    if (!$user) {
        \Log::warning('User not found', ['email' => $request->email]);
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    if (!Hash::check($request->password, $user->password)) {
        \Log::warning('Invalid password attempt', ['email' => $request->email]);
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    if (!$user->is_active) {
        \Log::warning('Inactive account attempt', ['email' => $request->email]);
        return response()->json(['message' => 'Account deactivated'], 403);
    }

    $token = $user->createToken('zhanna_education')->plainTextToken;
    
    \Log::info('Successful login', ['user_id' => $user->id]);
    
    return response()->json([
        'user' => $user,
        'token' => $token
    ]);
}
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Http/Controllers/Controller.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Http\Controllers;

abstract class Controller
{
    //
}


════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Http/Controllers/LeaderboardController.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Http\Controllers;

use App\Models\Leaderboard;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['level', 'subject', 'region', 'school']);
        
        $query = Leaderboard::with('user');
        
        foreach ($filters as $key => $value) {
            if ($value) {
                switch ($key) {
                    case 'level':
                        $query->byLevel($value);
                        break;
                    case 'subject':
                        $query->bySubject($value);
                        break;
                    case 'region':
                        $query->byRegion($value);
                        break;
                    case 'school':
                        $query->bySchool($value);
                        break;
                }
            }
        }
        
        $leaderboard = $query->orderBy('total_score', 'desc')
            ->orderBy('time_spent', 'asc')
            ->limit(100)
            ->get();
            
        return response()->json($leaderboard);
    }

    public function publicIndex(Request $request)
    {
        $filters = $request->only(['level', 'subject', 'region', 'school']);
        
        $query = Leaderboard::with(['user' => function($q) {
            $q->select('id', 'first_name', 'last_name');
        }]);
        
        foreach ($filters as $key => $value) {
            if ($value) {
                switch ($key) {
                    case 'level':
                        $query->byLevel($value);
                        break;
                    case 'subject':
                        $query->bySubject($value);
                        break;
                    case 'region':
                        $query->byRegion($value);
                        break;
                    case 'school':
                        $query->bySchool($value);
                        break;
                }
            }
        }
        
        $leaderboard = $query->orderBy('total_score', 'desc')
            ->orderBy('time_spent', 'asc')
            ->limit(50)
            ->get()
            ->map(function($item) {
                return [
                    'rank' => $item->rank,
                    'user_name' => $item->user_name,
                    'school' => $item->school,
                    'region' => $item->region,
                    'total_score' => $item->total_score,
                    'time_spent' => $item->time_spent
                ];
            });
            
        return response()->json($leaderboard);
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Http/Controllers/QuestionController.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index()
    {
        $questions = Question::with('test')->paginate(20);
        return response()->json($questions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'test_id' => 'required|exists:tests,id',
            'question_text' => 'required|string',
            'options' => 'required|array|min:2',
            'correct_answer' => 'required|integer|min:0',
            'points' => 'sometimes|integer|min:1',
            'explanation' => 'sometimes|string',
            'image_url' => 'sometimes|url',
            'time_estimate' => 'sometimes|integer|min:10',
            'difficulty' => 'sometimes|in:easy,medium,hard'
        ]);
        
        $question = Question::create($validated);
        
        return response()->json([
            'message' => 'Question created successfully',
            'question' => $question
        ], 201);
    }

    public function show(Question $question)
    {
        return response()->json($question->load('test'));
    }

    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'question_text' => 'sometimes|required|string',
            'options' => 'sometimes|required|array|min:2',
            'correct_answer' => 'sometimes|required|integer|min:0',
            'points' => 'sometimes|integer|min:1',
            'explanation' => 'sometimes|string',
            'image_url' => 'sometimes|url',
            'time_estimate' => 'sometimes|integer|min:10',
            'difficulty' => 'sometimes|in:easy,medium,hard'
        ]);
        
        $question->update($validated);
        
        return response()->json([
            'message' => 'Question updated successfully',
            'question' => $question
        ]);
    }

    public function destroy(Question $question)
    {
        $question->delete();
        
        return response()->json([
            'message' => 'Question deleted successfully'
        ]);
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Http/Controllers/ResearchPaperController.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Http\Controllers;

use App\Models\ResearchPaper;
use Illuminate\Http\Request;

class ResearchPaperController extends Controller
{
    public function index(Request $request)
    {
        $papers = ResearchPaper::where('user_id', $request->user()->id)->get();
        return response()->json($papers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'abstract' => 'required|string',
            'paper_file' => 'required|file|mimes:pdf|max:10240'
        ]);
        
        $filePath = $request->file('paper_file')->store('research-papers', 'public');
        
        $paper = ResearchPaper::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'abstract' => $validated['abstract'],
            'paper_file' => $filePath
        ]);
        
        return response()->json([
            'message' => 'Research paper submitted successfully',
            'paper' => $paper
        ], 201);
    }

    public function show(ResearchPaper $researchPaper)
    {
        $this->authorize('view', $researchPaper);
        return response()->json($researchPaper);
    }

    public function update(Request $request, ResearchPaper $researchPaper)
    {
        $this->authorize('update', $researchPaper);
        
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'abstract' => 'sometimes|required|string',
        ]);
        
        $researchPaper->update($validated);
        
        return response()->json([
            'message' => 'Research paper updated successfully',
            'paper' => $researchPaper
        ]);
    }

    public function destroy(ResearchPaper $researchPaper)
    {
        $this->authorize('delete', $researchPaper);
        $researchPaper->delete();
        
        return response()->json([
            'message' => 'Research paper deleted successfully'
        ]);
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Http/Controllers/SubjectController.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubjectRegistration;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::where('is_active', true)->get();
        return response()->json($subjects);
    }

    public function publicIndex()
    {
        $subjects = Subject::where('is_active', true)->get();
        return response()->json($subjects);
    }

    public function register(Request $request, Subject $subject)
    {
        $user = $request->user();
        
        // Check if already registered
        $existingRegistration = SubjectRegistration::where('user_id', $user->id)
            ->where('subject_id', $subject->id)
            ->first();
            
        if ($existingRegistration) {
            return response()->json([
                'message' => 'Already registered for this subject'
            ], 400);
        }
        
        // Create registration
        $registration = SubjectRegistration::create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'competition_level_id' => 1, // Start from level 1
            'payment_status' => 'pending'
        ]);
        
        return response()->json([
            'message' => 'Successfully registered for subject',
            'registration' => $registration
        ]);
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Http/Controllers/TestController.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Subject;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index(Request $request)
    {
        $level = $request->query('level');
        $subject = $request->query('subject');
        
        $query = Test::with(['subject', 'competitionLevel']);
        
        if ($level) {
            $query->where('competition_level_id', $level);
        }
        
        if ($subject) {
            $query->where('subject_id', $subject);
        }
        
        $tests = $query->where('is_active', true)->get();
        
        return response()->json([
            'tests' => $tests,
            'subjects' => Subject::where('is_active', true)->get(),
            'upcoming' => Test::where('scheduled_start', '>', now())
                ->where('is_active', true)
                ->orderBy('scheduled_start', 'asc')
                ->limit(5)
                ->get()
        ]);
    }

    public function show(Test $test)
    {
        $test->load(['questions', 'subject', 'competitionLevel']);
        return response()->json($test);
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Http/Controllers/TestSessionController.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Http\Controllers;

use App\Models\TestSession;
use App\Models\Test;
use App\Models\UserAnswer;
use App\Models\Question;
use App\Models\QualificationThreshold;
use App\Models\QualifiedUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestSessionController extends Controller
{
    public function start(Request $request, Test $test)
    {
        // Проверка доступа пользователя к тесту
        if (!$test->isActive()) {
            return response()->json([
                'message' => 'Test is not available'
            ], 403);
        }

        // Проверка, зарегистрирован ли пользователь на предмет
        if (!$request->user()->subjectRegistrations()
            ->where('subject_id', $test->subject_id)
            ->where('competition_level_id', $test->competition_level_id)
            ->where('payment_status', 'completed')
            ->exists()) {
            return response()->json([
                'message' => 'Not registered for this subject'
            ], 403);
        }

        // Проверка, не начат ли уже тест
        $existingSession = TestSession::where('user_id', $request->user()->id)
            ->where('test_id', $test->id)
            ->whereIn('status', ['in_progress', 'completed'])
            ->first();

        if ($existingSession) {
            return response()->json([
                'message' => 'Test already attempted',
                'session' => $existingSession
            ], 409);
        }

        $session = TestSession::create([
            'user_id' => $request->user()->id,
            'test_id' => $test->id,
            'start_time' => now(),
            'status' => 'in_progress',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'session' => $session,
            'test' => $test->load('questions')
        ]);
    }

    public function submitAnswer(Request $request, TestSession $session)
    {
        if ($session->status !== 'in_progress') {
            return response()->json([
                'message' => 'Test session is not active'
            ], 403);
        }

        $validated = $request->validate([
            'question_id' => 'required|exists:questions,id',
            'selected_option' => 'required|integer',
            'time_spent' => 'required|integer'
        ]);

        $question = Question::find($validated['question_id']);
        $isCorrect = $validated['selected_option'] === $question->correct_answer;

        $userAnswer = UserAnswer::updateOrCreate(
            [
                'test_session_id' => $session->id,
                'question_id' => $validated['question_id']
            ],
            [
                'selected_option' => $validated['selected_option'],
                'is_correct' => $isCorrect,
                'time_spent' => $validated['time_spent']
            ]
        );

        return response()->json([
            'answer' => $userAnswer,
            'is_correct' => $isCorrect
        ]);
    }

    public function finish(Request $request, TestSession $session)
    {
        if ($session->status !== 'in_progress') {
            return response()->json([
                'message' => 'Test session is not active'
            ], 403);
        }

        DB::transaction(function () use ($session) {
            $session->update([
                'end_time' => now(),
                'time_spent' => now()->diffInSeconds($session->start_time),
                'total_score' => $session->calculateScore(),
                'status' => 'completed'
            ]);

            // Проверка квалификации на следующий уровень
            $this->checkQualification($session);
        });

        return response()->json([
            'session' => $session->fresh(),
            'message' => 'Test completed successfully'
        ]);
    }

    public function results(TestSession $session)
    {
        $session->load(['userAnswers.question', 'test']);
        
        return response()->json([
            'session' => $session,
            'results' => [
                'total_score' => $session->total_score,
                'time_spent' => $session->time_spent,
                'correct_answers' => $session->userAnswers()->where('is_correct', true)->count(),
                'total_questions' => $session->userAnswers()->count()
            ]
        ]);
    }

    private function checkQualification(TestSession $session)
    {
        $test = $session->test;
        $threshold = QualificationThreshold::where('competition_level_id', $test->competition_level_id)
            ->where('subject_id', $test->subject_id)
            ->first();

        if (!$threshold) {
            return;
        }

        $totalSessions = TestSession::where('test_id', $test->id)
            ->where('status', 'completed')
            ->count();

        if ($threshold->threshold_type === 'percentage') {
            $betterSessions = TestSession::where('test_id', $test->id)
                ->where('status', 'completed')
                ->where('total_score', '>', $session->total_score)
                ->count();

            $percentile = (($totalSessions - $betterSessions) / $totalSessions) * 100;

            if ($percentile <= $threshold->threshold_value) {
                QualifiedUser::create([
                    'user_id' => $session->user_id,
                    'competition_level_id' => $test->competition_level_id + 1,
                    'subject_id' => $test->subject_id,
                    'qualified_at' => now()
                ]);
            }
        } else {
            // Логика для fixed_number
            $topSessions = TestSession::where('test_id', $test->id)
                ->where('status', 'completed')
                ->orderBy('total_score', 'desc')
                ->orderBy('time_spent', 'asc')
                ->limit($threshold->threshold_value)
                ->pluck('id')
                ->toArray();

            if (in_array($session->id, $topSessions)) {
                QualifiedUser::create([
                    'user_id' => $session->user_id,
                    'competition_level_id' => $test->competition_level_id + 1,
                    'subject_id' => $test->subject_id,
                    'qualified_at' => now()
                ]);
            }
        }
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Http/Controllers/UserController.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user()->load(['subjectRegistrations.subject', 'testSessions.test']);
        return response()->json($user);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'school' => 'sometimes|required|string|max:255',
            'country' => 'sometimes|required|string|max:100',
            'city' => 'sometimes|required|string|max:100',
            'password' => 'sometimes|required|min:8|confirmed'
        ]);
        
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }
        
        $user->update($validated);
        
        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Http/Controllers/VideoSessionController.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Http\Controllers;

use App\Models\VideoSession;
use Illuminate\Http\Request;

class VideoSessionController extends Controller
{
    public function index(Request $request)
    {
        $sessions = VideoSession::where('judge_id', $request->user()->id)
            ->orWhereHas('researchPaper', function($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->with(['researchPaper.user', 'judge'])
            ->get();
            
        return response()->json($sessions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'research_paper_id' => 'required|exists:research_papers,id',
            'scheduled_time' => 'required|date',
            'duration' => 'required|integer|min:15|max:120'
        ]);
        
        $session = VideoSession::create([
            'research_paper_id' => $validated['research_paper_id'],
            'judge_id' => $request->user()->id,
            'scheduled_time' => $validated['scheduled_time'],
            'duration' => $validated['duration'],
            'meeting_url' => $this->generateMeetingUrl()
        ]);
        
        return response()->json([
            'message' => 'Video session scheduled successfully',
            'session' => $session
        ], 201);
    }

    public function show(VideoSession $videoSession)
    {
        $this->authorize('view', $videoSession);
        return response()->json($videoSession->load(['researchPaper.user', 'judge']));
    }

    public function update(Request $request, VideoSession $videoSession)
    {
        $this->authorize('update', $videoSession);
        
        $validated = $request->validate([
            'scheduled_time' => 'sometimes|required|date',
            'duration' => 'sometimes|required|integer|min:15|max:120'
        ]);
        
        $videoSession->update($validated);
        
        return response()->json([
            'message' => 'Video session updated successfully',
            'session' => $videoSession
        ]);
    }

    public function destroy(VideoSession $videoSession)
    {
        $this->authorize('delete', $videoSession);
        $videoSession->delete();
        
        return response()->json([
            'message' => 'Video session deleted successfully'
        ]);
    }

    public function join(VideoSession $videoSession)
    {
        $this->authorize('join', $videoSession);
        
        return response()->json([
            'meeting_url' => $videoSession->meeting_url
        ]);
    }

    private function generateMeetingUrl()
    {
        // This would typically integrate with a video conferencing API
        return 'https://meet.zhanna-education.com/' . uniqid();
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Models/CompetitionLevel.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
        'description',
        'is_active',
        'minimum_score',
        'qualification_threshold'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'level' => 'integer',
        'minimum_score' => 'integer',
        'qualification_threshold' => 'integer'
    ];

    public function tests()
    {
        return $this->hasMany(Test::class);
    }

    public function qualificationThresholds()
    {
        return $this->hasMany(QualificationThreshold::class);
    }

    public function qualifiedUsers()
    {
        return $this->hasMany(QualifiedUser::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getNextLevelAttribute()
    {
        return self::where('level', $this->level + 1)
            ->active()
            ->first();
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Models/Leaderboard.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leaderboard extends Model
{
    use HasFactory;

    protected $table = 'leaderboards';

    protected $fillable = [
        'user_id',
        'competition_level_id',
        'subject_id',
        'total_score',
        'time_spent',
        'rank',
        'region',
        'school'
    ];

    protected $casts = [
        'total_score' => 'integer',
        'time_spent' => 'integer',
        'rank' => 'integer'
    ];

    /**
     * Отношение к пользователю
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Отношение к уровню соревнования
     */
    public function competitionLevel()
    {
        return $this->belongsTo(CompetitionLevel::class);
    }

    /**
     * Отношение к предмету
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Scope для фильтрации по уровню
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('competition_level_id', $level);
    }

    /**
     * Scope для фильтрации по предмету
     */
    public function scopeBySubject($query, $subject)
    {
        return $query->where('subject_id', $subject);
    }

    /**
     * Scope для фильтрации по региону
     */
    public function scopeByRegion($query, $region)
    {
        return $query->where('region', 'like', "%{$region}%");
    }

    /**
     * Scope для фильтрации по школе
     */
    public function scopeBySchool($query, $school)
    {
        return $query->where('school', 'like', "%{$school}%");
    }

    /**
     * Получить полное имя пользователя через отношение
     */
    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->first_name . ' ' . $this->user->last_name : 'Unknown User';
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Models/QualificationThreshold.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualificationThreshold extends Model
{
    use HasFactory;

    protected $fillable = [
        'competition_level_id',
        'subject_id',
        'threshold_type',
        'threshold_value'
    ];
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Models/QualifiedUser.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualifiedUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'competition_level_id',
        'subject_id',
        'qualified_at'
    ];

    protected $casts = [
        'qualified_at' => 'datetime'
    ];
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Models/Question.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id', 'question_text', 'options', 'correct_answer', 
        'points', 'explanation', 'image_url', 'time_estimate', 'difficulty'
    ];

    protected $casts = [
        'options' => 'array'
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Models/ResearchPaper.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResearchPaper extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'abstract', 'paper_file', 'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function videoSessions()
    {
        return $this->hasMany(VideoSession::class);
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Models/Subject.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'image_url',
        'color'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function tests()
    {
        return $this->hasMany(Test::class);
    }

    public function subjectRegistrations()
    {
        return $this->hasMany(SubjectRegistration::class);
    }

    public function qualificationThresholds()
    {
        return $this->hasMany(QualificationThreshold::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Models/SubjectRegistration.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'competition_level_id',
        'payment_status',
        'registered_at',
        'payment_reference'
    ];

    protected $casts = [
        'registered_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function competitionLevel()
    {
        return $this->belongsTo(CompetitionLevel::class);
    }

    public function isPaid()
    {
        return $this->payment_status === 'completed';
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Models/Test.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'competition_level_id',
        'title',
        'description',
        'time_limit',
        'total_questions',
        'total_score',
        'passing_score',
        'scheduled_start',
        'scheduled_end',
        'is_active',
        'options'
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'is_active' => 'boolean',
        'options' => 'array',
        'time_limit' => 'integer',
        'total_questions' => 'integer',
        'total_score' => 'integer',
        'passing_score' => 'integer'
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function competitionLevel()
    {
        return $this->belongsTo(CompetitionLevel::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function testSessions()
    {
        return $this->hasMany(TestSession::class);
    }

    public function isActive()
    {
        return $this->is_active && 
               ($this->scheduled_start === null || now()->gte($this->scheduled_start)) &&
               ($this->scheduled_end === null || now()->lte($this->scheduled_end));
    }

    public function isUpcoming()
    {
        return $this->scheduled_start && now()->lt($this->scheduled_start);
    }

    public function isCompleted()
    {
        return $this->scheduled_end && now()->gt($this->scheduled_end);
    }

    public function getTimeRemainingAttribute()
    {
        if (!$this->scheduled_end) {
            return null;
        }

        return now()->diffInSeconds($this->scheduled_end, false);
    }

    public function getStatusAttribute()
    {
        if ($this->isUpcoming()) {
            return 'upcoming';
        }

        if ($this->isActive()) {
            return 'active';
        }

        if ($this->isCompleted()) {
            return 'completed';
        }

        return 'inactive';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('scheduled_start')
                  ->orWhere('scheduled_start', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('scheduled_end')
                  ->orWhere('scheduled_end', '>=', now());
            });
    }

    public function scopeUpcoming($query)
    {
        return $query->where('is_active', true)
            ->where('scheduled_start', '>', now())
            ->orderBy('scheduled_start', 'asc');
    }

    public function getAverageScoreAttribute()
    {
        $completedSessions = $this->testSessions()
            ->where('status', 'completed')
            ->count();

        if ($completedSessions === 0) {
            return 0;
        }

        return $this->testSessions()
            ->where('status', 'completed')
            ->avg('total_score');
    }

    public function getCompletionRateAttribute()
    {
        $totalSessions = $this->testSessions()->count();
        $completedSessions = $this->testSessions()
            ->where('status', 'completed')
            ->count();

        if ($totalSessions === 0) {
            return 0;
        }

        return ($completedSessions / $totalSessions) * 100;
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Models/TestSession.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'test_id',
        'start_time',
        'end_time',
        'time_spent',
        'total_score',
        'status',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'options' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }

    public function calculateScore()
    {
        $correctAnswers = $this->userAnswers()->where('is_correct', true)->count();
        $timeBonus = max(0, ($this->test->time_limit * 60 - $this->time_spent) / 10);
        
        return $correctAnswers + $timeBonus;
    }

    public function isActive()
    {
        return $this->status === 'in_progress' && 
               now()->lt($this->start_time->addMinutes($this->test->time_limit));
    }

    public function timeRemaining()
    {
        if (!$this->isActive()) {
            return 0;
        }

        $endTime = $this->start_time->addMinutes($this->test->time_limit);
        return now()->diffInSeconds($endTime, false);
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Models/User.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'date_of_birth',
        'school',
        'country',
        'city',
        'role',
        'student_id_image',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    public function subjectRegistrations(): HasMany
    {
        return $this->hasMany(SubjectRegistration::class);
    }

    public function testSessions(): HasMany
    {
        return $this->hasMany(TestSession::class);
    }

    public function researchPapers(): HasMany
    {
        return $this->hasMany(ResearchPaper::class);
    }

    public function videoSessionsAsJudge(): HasMany
    {
        return $this->hasMany(VideoSession::class, 'judge_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isJudge(): bool
    {
        return $this->role === 'judge' || $this->isAdmin();
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Models/UserAnswer.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_session_id', 'question_id', 'selected_option', 
        'is_correct', 'time_spent'
    ];

    public function testSession()
    {
        return $this->belongsTo(TestSession::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: app/Models/VideoSession.php ════════════════════════════════════════════════════════════════════════════════ <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'research_paper_id', 'judge_id', 'scheduled_time', 
        'duration', 'meeting_url', 'status', 'recorded_video_url'
    ];

    public function researchPaper()
    {
        return $this->belongsTo(ResearchPaper::class);
    }

    public function judge()
    {
        return $this->belongsTo(User::class, 'judge_id');
    }
}

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: routes/api.php ════════════════════════════════════════════════════════════════════════════════ <?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TestSessionController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ResearchPaperController;
use App\Http\Controllers\VideoSessionController;
use App\Http\Controllers\QuestionController;

// Public routes
Route::get('/subjects', [SubjectController::class, 'publicIndex']);
Route::get('/leaderboard', [LeaderboardController::class, 'publicIndex']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // Tests
    Route::get('/tests', [TestController::class, 'index']);
    Route::get('/tests/{test}', [TestController::class, 'show']);
    Route::post('/tests/{test}/start', [TestSessionController::class, 'start']);

    // Test Sessions
    Route::post('/sessions/{session}/answer', [TestSessionController::class, 'submitAnswer']);
    Route::post('/sessions/{session}/finish', [TestSessionController::class, 'finish']);
    Route::get('/sessions/{session}/results', [TestSessionController::class, 'results']);

    // Subjects
    Route::post('/subjects/{subject}/register', [SubjectController::class, 'register']);

    // Leaderboard
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);

    // User Profile
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'update']);

    // Research Papers
    Route::apiResource('research-papers', ResearchPaperController::class)
        ->names([
            'index' => 'api.research-papers.index',
            'store' => 'api.research-papers.store',
            'show' => 'api.research-papers.show', 
            'update' => 'api.research-papers.update',
            'destroy' => 'api.research-papers.destroy'
        ]);
    // Video Sessions
    Route::apiResource('video-sessions', VideoSessionController::class);
    Route::post('/video-sessions/{session}/join', [VideoSessionController::class, 'join']);

    // Questions (Admin only)
    Route::middleware('admin')->group(function () {
        Route::apiResource('questions', QuestionController::class);
    });
});

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: routes/console.php ════════════════════════════════════════════════════════════════════════════════ <?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


════════════════════════════════════════════════════════════════════════════════ 📂 Файл: routes/web.php ════════════════════════════════════════════════════════════════════════════════ <?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AdminController;

Route::get('/', function () {
    return view('welcome');
});

// Admin Authentication
Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login.post');

// Admin Protected Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // User Management Routes
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/users/create', [AdminController::class, 'userCreate'])->name('users.create');
    Route::post('/users', [AdminController::class, 'userStore'])->name('users.store');
    Route::get('/users/{user}', [AdminController::class, 'userShow'])->name('users.show');
    Route::get('/users/{user}/edit', [AdminController::class, 'userEdit'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'userUpdate'])->name('users.update');
    Route::patch('/users/{user}/toggle-status', [AdminController::class, 'userToggleStatus'])->name('users.toggle-status');
    Route::delete('/users/{user}', [AdminController::class, 'userDestroy'])->name('users.destroy');
    
    // Tests Management
    Route::get('/tests', [AdminController::class, 'tests'])->name('tests.index');
    Route::get('/tests/create', [AdminController::class, 'testCreate'])->name('tests.create');
    Route::post('/tests', [AdminController::class, 'testStore'])->name('tests.store');
    Route::get('/tests/{test}', [AdminController::class, 'testShow'])->name('tests.show');
    Route::get('/tests/{test}/edit', [AdminController::class, 'testEdit'])->name('tests.edit');
    Route::put('/tests/{test}', [AdminController::class, 'testUpdate'])->name('tests.update');
    Route::delete('/tests/{test}', [AdminController::class, 'testDestroy'])->name('tests.destroy');
    Route::patch('/tests/{test}/toggle-status', [AdminController::class, 'testToggleStatus'])->name('tests.toggle-status');

    Route::get('/research-papers', [AdminController::class, 'researchPapers'])->name('research-papers.index');
    Route::get('/leaderboard', [AdminController::class, 'leaderboard'])->name('leaderboard.index');
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');

        // Subjects Management Routes
    Route::get('/subjects', [AdminController::class, 'subjects'])->name('subjects.index');
    Route::get('/subjects/create', [AdminController::class, 'subjectCreate'])->name('subjects.create');
    Route::post('/subjects', [AdminController::class, 'subjectStore'])->name('subjects.store');
    Route::get('/subjects/{subject}', [AdminController::class, 'subjectShow'])->name('subjects.show');
    Route::get('/subjects/{subject}/edit', [AdminController::class, 'subjectEdit'])->name('subjects.edit');
    Route::put('/subjects/{subject}', [AdminController::class, 'subjectUpdate'])->name('subjects.update');
    Route::delete('/subjects/{subject}', [AdminController::class, 'subjectDestroy'])->name('subjects.destroy');
    Route::patch('/subjects/{subject}/toggle-status', [AdminController::class, 'subjectToggleStatus'])->name('subjects.toggle-status');
});

// Logout
Route::post('/logout', function (\Illuminate\Http\Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/admin/login');
})->name('logout');

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: resources/views/admin/dashboard.blade.php ════════════════════════════════════════════════════════════════════════════════ @extends('admin.layout')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-users text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-600">Total Users</h3>
                <p class="text-3xl font-bold text-gray-800">{{ $stats['total_users'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-file-alt text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-600">Total Tests</h3>
                <p class="text-3xl font-bold text-gray-800">{{ $stats['total_tests'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-book text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-600">Total Subjects</h3>
                <p class="text-3xl font-bold text-gray-800">{{ $stats['total_subjects'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <i class="fas fa-file-pdf text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-600">Research Papers</h3>
                <p class="text-3xl font-bold text-gray-800">{{ $stats['total_research_papers'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <i class="fas fa-play-circle text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-600">Active Sessions</h3>
                <p class="text-3xl font-bold text-gray-800">{{ $stats['active_sessions'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-600">Completed Sessions</h3>
                <p class="text-3xl font-bold text-gray-800">{{ $stats['completed_sessions'] }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Users -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">Recent Users</h2>
        </div>
        <div class="p-4">
            @foreach($recentUsers as $user)
            <div class="flex items-center justify-between py-3 border-b last:border-b-0">
                <div>
                    <p class="font-medium text-gray-800">{{ $user->full_name }}</p>
                    <p class="text-sm text-gray-600">{{ $user->email }}</p>
                </div>
                <span class="px-2 py-1 text-xs rounded-full 
                    {{ $user->role === 'admin' ? 'bg-red-100 text-red-800' : 
                       ($user->role === 'judge' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                    {{ ucfirst($user->role) }}
                </span>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Recent Test Sessions -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">Recent Test Sessions</h2>
        </div>
        <div class="p-4">
            @foreach($recentSessions as $session)
            <div class="py-3 border-b last:border-b-0">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-medium text-gray-800">{{ $session->user->full_name }}</p>
                        <p class="text-sm text-gray-600">{{ $session->test->title ?? 'N/A' }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs rounded-full 
                        {{ $session->status === 'completed' ? 'bg-green-100 text-green-800' : 
                           ($session->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                        {{ ucfirst(str_replace('_', ' ', $session->status)) }}
                    </span>
                </div>
                @if($session->total_score)
                <p class="text-sm text-gray-600 mt-1">Score: {{ $session->total_score }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: resources/views/admin/layout.blade.php ════════════════════════════════════════════════════════════════════════════════ <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Zhanna Education</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('[role="alert"]');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000); // исчезнет через 5 секунд
    });
});
</script>
</head>
<body class="bg-gray-100">
   <!-- Notifications -->
@if(session('success'))
<div class="fixed top-4 right-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 z-50 shadow-lg rounded-md animate-fade-in" role="alert">
    <div class="flex">
        <div class="py-1">
            <i class="fas fa-check-circle text-green-500 mr-3"></i>
        </div>
        <div>
            <p class="font-bold">Success</p>
            <p>{{ session('success') }}</p>
        </div>
    </div>
</div>
@endif

@if(session('error'))
<div class="fixed top-4 right-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 z-50 shadow-lg rounded-md animate-fade-in" role="alert">
    <div class="flex">
        <div class="py-1">
            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
        </div>
        <div>
            <p class="font-bold">Error</p>
            <p>{{ session('error') }}</p>
        </div>
    </div>
</div>
@endif

@if($errors->any())
<div class="fixed top-4 right-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 z-50 shadow-lg rounded-md animate-fade-in" role="alert">
    <div class="flex">
        <div class="py-1">
            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
        </div>
        <div>
            <p class="font-bold">Please fix the following errors:</p>
            <ul class="list-disc list-inside mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

    <!-- Sidebar -->
    <div class="flex h-screen">
        <div class="w-64 bg-blue-800 text-white">
            <div class="p-4">
                <h1 class="text-2xl font-bold">Zhanna Education</h1>
                <p class="text-blue-200">Admin Panel</p>
            </div>
            <nav class="mt-6">
                <a href="{{ route('admin.dashboard') }}" class="block py-2 px-4 hover:bg-blue-700 {{ request()->routeIs('admin.dashboard') ? 'bg-blue-700' : '' }}">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="{{ route('admin.users.index') }}" class="block py-2 px-4 hover:bg-blue-700 {{ request()->routeIs('admin.users.*') ? 'bg-blue-700' : '' }}">
                    <i class="fas fa-users mr-2"></i>Users
                </a>
                <a href="{{ route('admin.tests.index') }}" class="block py-2 px-4 hover:bg-blue-700 {{ request()->routeIs('admin.tests.*') ? 'bg-blue-700' : '' }}">
                    <i class="fas fa-file-alt mr-2"></i>Tests
                </a>
                <a href="{{ route('admin.subjects.index') }}" class="block py-2 px-4 hover:bg-blue-700 {{ request()->routeIs('admin.subjects.*') ? 'bg-blue-700' : '' }}">
                    <i class="fas fa-book mr-2"></i>Subjects
                </a>
                <a href="{{ route('admin.research-papers.index') }}" class="block py-2 px-4 hover:bg-blue-700 {{ request()->routeIs('admin.research-papers.*') ? 'bg-blue-700' : '' }}">
                    <i class="fas fa-file-pdf mr-2"></i>Research Papers
                </a>
                <a href="{{ route('admin.leaderboard.index') }}" class="block py-2 px-4 hover:bg-blue-700 {{ request()->routeIs('admin.leaderboard.*') ? 'bg-blue-700' : '' }}">
                    <i class="fas fa-trophy mr-2"></i>Leaderboard
                </a>
                <a href="{{ route('admin.analytics') }}" class="block py-2 px-4 hover:bg-blue-700 {{ request()->routeIs('admin.analytics') ? 'bg-blue-700' : '' }}">
                    <i class="fas fa-chart-bar mr-2"></i>Analytics
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full text-left block py-2 px-4 hover:bg-blue-700">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </button>
                </form>
            </nav>
        </div>

        <!-- Main content -->
        <div class="flex-1 overflow-auto">
            <div class="p-6">
                @yield('content')
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: resources/views/admin/login.blade.php ════════════════════════════════════════════════════════════════════════════════ <!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админку</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 300px;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
        }
        input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .error {
            color: red;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>Вход в админку</h2>
        
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Войти</button>
        </form>
    </div>
</body>
</html>

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: resources/views/admin/subjects/create.blade.php ════════════════════════════════════════════════════════════════════════════════ @extends('admin.layout')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Create New Subject</h1>
        <a href="{{ route('admin.subjects.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Subjects
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.subjects.store') }}" method="POST">
            @csrf
            
            <div class="space-y-6">
                <!-- Basic Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Basic Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Subject Name *</label>
                            <input type="text" name="name" id="name" 
                                   value="{{ old('name') }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                            @error('name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Subject Code *</label>
                            <input type="text" name="code" id="code" 
                                   value="{{ old('code') }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase"
                                   required>
                            @error('code')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                        <textarea name="description" id="description" rows="4"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  required>{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <!-- Additional Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Additional Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="image_url" class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
                            <input type="url" name="image_url" id="image_url" 
                                   value="{{ old('image_url') }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="https://example.com/image.jpg">
                            @error('image_url')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                            <input type="color" name="color" id="color" 
                                   value="{{ old('color', '#4f46e5') }}"
                                   class="w-full h-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('color')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mt-4 flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" 
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                            Active Subject
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 mt-8">
                <a href="{{ route('admin.subjects.index') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition duration-200">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200">
                    Create Subject
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: resources/views/admin/subjects/edit.blade.php ════════════════════════════════════════════════════════════════════════════════ @extends('admin.layout')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Edit Subject: {{ $subject->name }}</h1>
        <a href="{{ route('admin.subjects.show', $subject) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Subject
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.subjects.update', $subject) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="space-y-6">
                <!-- Basic Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Basic Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Subject Name *</label>
                            <input type="text" name="name" id="name" 
                                   value="{{ old('name', $subject->name) }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                            @error('name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Subject Code *</label>
                            <input type="text" name="code" id="code" 
                                   value="{{ old('code', $subject->code) }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase"
                                   required>
                            @error('code')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                        <textarea name="description" id="description" rows="4"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  required>{{ old('description', $subject->description) }}</textarea>
                        @error('description')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <!-- Additional Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Additional Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="image_url" class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
                            <input type="url" name="image_url" id="image_url" 
                                   value="{{ old('image_url', $subject->image_url) }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="https://example.com/image.jpg">
                            @error('image_url')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                            <input type="color" name="color" id="color" 
                                   value="{{ old('color', $subject->color ?: '#4f46e5') }}"
                                   class="w-full h-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('color')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mt-4 flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" 
                               {{ old('is_active', $subject->is_active) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                            Active Subject
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 mt-8">
                <a href="{{ route('admin.subjects.show', $subject) }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition duration-200">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200">
                    Update Subject
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: resources/views/admin/subjects/index.blade.php ════════════════════════════════════════════════════════════════════════════════ @extends('admin.layout')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Subject Management</h1>
    <a href="{{ route('admin.subjects.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
        <i class="fas fa-plus mr-2"></i> Add New Subject
    </a>
</div>

<!-- Search and Filters -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form action="{{ route('admin.subjects.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" 
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="Search subjects...">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg w-full">
                <i class="fas fa-filter mr-2"></i> Filter
            </button>
        </div>
    </form>
</div>

<!-- Subjects Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tests</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registrations</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($subjects as $subject)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            @if($subject->image_url)
                            <div class="flex-shrink-0 h-10 w-10">
                                <img class="h-10 w-10 rounded-full object-cover" src="{{ $subject->image_url }}" alt="{{ $subject->name }}">
                            </div>
                            @else
                            <div class="flex-shrink-0 h-10 w-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold" 
                                 style="{{ $subject->color ? 'background-color: ' . $subject->color : '' }}">
                                {{ substr($subject->name, 0, 1) }}
                            </div>
                            @endif
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $subject->name }}
                                </div>
                                <div class="text-sm text-gray-500">{{ Str::limit($subject->description, 50) }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <code class="bg-gray-100 px-2 py-1 rounded">{{ $subject->code }}</code>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $subject->tests_count ?? $subject->tests->count() }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $subject->subject_registrations_count ?? $subject->subjectRegistrations->count() }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $subject->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $subject->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $subject->created_at->format('M j, Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="{{ route('admin.subjects.show', $subject) }}" 
                               class="text-blue-600 hover:text-blue-900" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.subjects.edit', $subject) }}" 
                               class="text-green-600 hover:text-green-900" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.subjects.toggle-status', $subject) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-orange-600 hover:text-orange-900" 
                                        title="{{ $subject->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="fas {{ $subject->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.subjects.destroy', $subject) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" 
                                        onclick="return confirm('Are you sure you want to delete this subject?')"
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                        No subjects found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        {{ $subjects->links() }}
    </div>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-book text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-600">Total Subjects</h3>
                <p class="text-3xl font-bold text-gray-800">{{ $subjects->total() }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-600">Active Subjects</h3>
                <p class="text-3xl font-bold text-gray-800">{{ $subjects->where('is_active', true)->count() }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-file-alt text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-600">Total Tests</h3>
                <p class="text-3xl font-bold text-gray-800">{{ $subjects->sum(function($subject) { return $subject->tests->count(); }) }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <i class="fas fa-users text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-600">Total Registrations</h3>
                <p class="text-3xl font-bold text-gray-800">{{ $subjects->sum(function($subject) { return $subject->subjectRegistrations->count(); }) }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: resources/views/admin/subjects/show.blade.php ════════════════════════════════════════════════════════════════════════════════ @extends('admin.layout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center">
            @if($subject->image_url)
            <img class="h-16 w-16 rounded-full object-cover mr-4" src="{{ $subject->image_url }}" alt="{{ $subject->name }}">
            @else
            <div class="h-16 w-16 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-2xl mr-4"
                 style="{{ $subject->color ? 'background-color: ' . $subject->color : '' }}">
                {{ substr($subject->name, 0, 1) }}
            </div>
            @endif
            <div>
                <h1 class="text-3xl font-bold text-gray-800">{{ $subject->name }}</h1>
                <p class="text-gray-600">
                    <code class="bg-gray-100 px-2 py-1 rounded">{{ $subject->code }}</code>
                    • 
                    <span class="text-sm text-gray-500">Created {{ $subject->created_at->format('M j, Y') }}</span>
                </p>
            </div>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.subjects.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
            <a href="{{ route('admin.subjects.edit', $subject) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-edit mr-2"></i> Edit
            </a>
            <form action="{{ route('admin.subjects.toggle-status', $subject) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" 
                        class="bg-{{ $subject->is_active ? 'orange' : 'blue' }}-600 hover:bg-{{ $subject->is_active ? 'orange' : 'blue' }}-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas {{ $subject->is_active ? 'fa-eye-slash' : 'fa-eye' }} mr-2"></i>
                    {{ $subject->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
            <form action="{{ route('admin.subjects.destroy', $subject) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        onclick="return confirm('Are you sure you want to delete this subject?')"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-trash mr-2"></i> Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Subject Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-file-alt text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-600">Total Tests</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ $stats['total_tests'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-600">Total Registrations</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ $stats['total_registrations'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-600">Active Registrations</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ $stats['active_registrations'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-600">Avg Test Score</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['average_test_score'] ?? 0, 1) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Subject Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">Subject Information</h2>
                </div>
                <div class="p-4">
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Name</label>
                            <p class="text-gray-900">{{ $subject->name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Code</label>
                            <p class="text-gray-900"><code class="bg-gray-100 px-2 py-1 rounded">{{ $subject->code }}</code></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Description</label>
                            <p class="text-gray-900">{{ $subject->description }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600">Status</label>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $subject->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $subject->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Created</label>
                                <p class="text-gray-900">{{ $subject->created_at->format('M j, Y') }}</p>
                            </div>
                        </div>
                        @if($subject->image_url)
                        <div>
                            <label class="text-sm font-medium text-gray-600">Image</label>
                            <div class="mt-2">
                                <img src="{{ $subject->image_url }}" alt="{{ $subject->name }}" class="h-32 w-32 object-cover rounded-lg">
                            </div>
                        </div>
                        @endif
                        @if($subject->color)
                        <div>
                            <label class="text-sm font-medium text-gray-600">Color</label>
                            <div class="flex items-center mt-2">
                                <div class="w-8 h-8 rounded-full border border-gray-300 mr-2" style="background-color: {{ $subject->color }}"></div>
                                <span class="text-gray-900">{{ $subject->color }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tests -->
            @if($subject->tests->count() > 0)
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">Tests ({{ $subject->tests->count() }})</h2>
                </div>
                <div class="p-4">
                    <div class="space-y-3">
                        @foreach($subject->tests->take(5) as $test)
                        <div class="flex justify-between items-center py-2 border-b last:border-b-0">
                            <div>
                                <p class="font-medium text-gray-800">{{ $test->title }}</p>
                                <p class="text-sm text-gray-600">
                                    Level: {{ $test->competitionLevel->name ?? 'N/A' }} • 
                                    Questions: {{ $test->questions_count ?? $test->questions->count() }} • 
                                    Time: {{ $test->time_limit }}min
                                </p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full 
                                {{ $test->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $test->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        @endforeach
                        @if($subject->tests->count() > 5)
                        <div class="text-center pt-2">
                            <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">
                                View all {{ $subject->tests->count() }} tests
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Recent Registrations -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">Recent Registrations</h2>
                </div>
                <div class="p-4">
                    @if($subject->subjectRegistrations->count() > 0)
                    <div class="space-y-3">
                        @foreach($subject->subjectRegistrations->take(5) as $registration)
                        <div class="py-2 border-b last:border-b-0">
                            <p class="font-medium text-gray-800 text-sm">{{ $registration->user->full_name }}</p>
                            <div class="flex justify-between items-center mt-1">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    {{ $registration->payment_status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($registration->payment_status) }}
                                </span>
                                <span class="text-xs text-gray-500">{{ $registration->registered_at->format('M j') }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-gray-500 text-center py-4">No registrations yet</p>
                    @endif
                </div>
            </div>

            <!-- Qualification Thresholds -->
            @if($subject->qualificationThresholds->count() > 0)
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">Qualification Thresholds</h2>
                </div>
                <div class="p-4">
                    <div class="space-y-3">
                        @foreach($subject->qualificationThresholds as $threshold)
                        <div class="py-2 border-b last:border-b-0">
                            <p class="font-medium text-gray-800 text-sm">Level {{ $threshold->competitionLevel->level ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-600">
                                Type: {{ $threshold->threshold_type }} • 
                                Value: {{ $threshold->threshold_value }}
                            </p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: resources/views/admin/users/create.blade.php ════════════════════════════════════════════════════════════════════════════════ @extends('admin.layout')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Create New User</h1>
        <a href="{{ route('admin.users.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Users
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Personal Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Personal Information</h3>
                    
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <input type="text" name="first_name" id="first_name" 
                               value="{{ old('first_name') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        @error('first_name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                        <input type="text" name="last_name" id="last_name" 
                               value="{{ old('last_name') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        @error('last_name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="email" id="email" 
                               value="{{ old('email') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        @error('email')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" 
                               value="{{ old('date_of_birth') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        @error('date_of_birth')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <!-- Account Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Account Information</h3>
                    
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                        <select name="role" id="role" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                            <option value="">Select Role</option>
                            <option value="student" {{ old('role') == 'student' ? 'selected' : '' }}>Student</option>
                            <option value="judge" {{ old('role') == 'judge' ? 'selected' : '' }}>Judge</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                        <input type="password" name="password" id="password" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        @error('password')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" 
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                            Active Account
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Location Information -->
            <div class="border-t pt-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Location Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="school" class="block text-sm font-medium text-gray-700 mb-1">School *</label>
                        <input type="text" name="school" id="school" 
                               value="{{ old('school') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        @error('school')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                        <input type="text" name="country" id="country" 
                               value="{{ old('country') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        @error('country')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                        <input type="text" name="city" id="city" 
                               value="{{ old('city') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        @error('city')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4">
                <a href="{{ route('admin.users.index') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition duration-200">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: resources/views/admin/users/edit.blade.php ════════════════════════════════════════════════════════════════════════════════ @extends('admin.layout')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Edit User: {{ $user->first_name }} {{ $user->last_name }}</h1>
        <a href="{{ route('admin.users.show', $user) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to User
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Personal Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Personal Information</h3>
                    
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <input type="text" name="first_name" id="first_name" 
                               value="{{ old('first_name', $user->first_name) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        @error('first_name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                        <input type="text" name="last_name" id="last_name" 
                               value="{{ old('last_name', $user->last_name) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        @error('last_name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="email" id="email" 
                               value="{{ old('email', $user->email) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        @error('email')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" 
                               value="{{ old('date_of_birth', $user->date_of_birth->format('Y-m-d')) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        @error('date_of_birth')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <!-- Account Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Account Information</h3>
                    
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                        <select name="role" id="role" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                            <option value="">Select Role</option>
                            <option value="student" {{ old('role', $user->role) == 'student' ? 'selected' : '' }}>Student</option>
                            <option value="judge" {{ old('role', $user->role) == 'judge' ? 'selected' : '' }}>Judge</option>
                            <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" id="password" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Leave blank to keep current password">
                        @error('password')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Confirm new password">
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" 
                               {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                            Active Account
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Location Information -->
            <div class="border-t pt-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Location Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="school" class="block text-sm font-medium text-gray-700 mb-1">School *</label>
                        <input type="text" name="school" id="school" 
                               value="{{ old('school', $user->school) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        @error('school')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                        <input type="text" name="country" id="country" 
                               value="{{ old('country', $user->country) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        @error('country')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                        <input type="text" name="city" id="city" 
                               value="{{ old('city', $user->city) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        @error('city')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4">
                <a href="{{ route('admin.users.show', $user) }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition duration-200">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: resources/views/admin/users/index.blade.php ════════════════════════════════════════════════════════════════════════════════ @extends('admin.layout')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
    <a href="{{ route('admin.users.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
        <i class="fas fa-plus mr-2"></i> Add New User
    </a>
</div>

<!-- Search and Filters -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form action="{{ route('admin.users.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" 
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="Search users...">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
            <select name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Roles</option>
                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="judge" {{ request('role') == 'judge' ? 'selected' : '' }}>Judge</option>
                <option value="student" {{ request('role') == 'student' ? 'selected' : '' }}>Student</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg w-full">
                <i class="fas fa-filter mr-2"></i> Filter
            </button>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">School</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                                {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $user->first_name }} {{ $user->last_name }}
                                </div>
                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $user->role === 'admin' ? 'bg-red-100 text-red-800' : 
                               ($user->role === 'judge' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $user->school ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $user->city }}, {{ $user->country }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $user->created_at->format('M j, Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="{{ route('admin.users.show', $user) }}" 
                               class="text-blue-600 hover:text-blue-900" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.users.edit', $user) }}" 
                               class="text-green-600 hover:text-green-900" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-orange-600 hover:text-orange-900" 
                                        title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="fas {{ $user->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                </button>
                            </form>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" 
                                        onclick="return confirm('Are you sure you want to delete this user?')"
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                        No users found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        {{ $users->links() }}
    </div>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-users text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-600">Total Users</h3>
                <p class="text-3xl font-bold text-gray-800">{{ $users->total() }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-user-graduate text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-600">Students</h3>
                <p class="text-3xl font-bold text-gray-800">{{ $users->where('role', 'student')->count() }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-gavel text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-600">Judges</h3>
                <p class="text-3xl font-bold text-gray-800">{{ $users->where('role', 'judge')->count() }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <i class="fas fa-user-shield text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-600">Admins</h3>
                <p class="text-3xl font-bold text-gray-800">{{ $users->where('role', 'admin')->count() }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: resources/views/admin/users/show.blade.php ════════════════════════════════════════════════════════════════════════════════ @extends('admin.layout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">{{ $user->first_name }} {{ $user->last_name }}</h1>
            <p class="text-gray-600">{{ $user->email }}</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.users.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
            <a href="{{ route('admin.users.edit', $user) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-edit mr-2"></i> Edit
            </a>
            <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" 
                        class="bg-{{ $user->is_active ? 'orange' : 'blue' }}-600 hover:bg-{{ $user->is_active ? 'orange' : 'blue' }}-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas {{ $user->is_active ? 'fa-user-slash' : 'fa-user-check' }} mr-2"></i>
                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
            @if($user->id !== auth()->id())
            <form action="{{ route('admin.users.destroy', $user) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        onclick="return confirm('Are you sure you want to delete this user?')"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-trash mr-2"></i> Delete
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- User Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-file-alt text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-600">Test Sessions</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ $testStats['total_sessions'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-600">Completed</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ $testStats['completed_sessions'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-600">Avg Score</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($testStats['average_score'] ?? 0, 1) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-file-pdf text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-600">Research Papers</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ $testStats['total_research_papers'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">Basic Information</h2>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Full Name</label>
                            <p class="text-gray-900">{{ $user->first_name }} {{ $user->last_name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Email</label>
                            <p class="text-gray-900">{{ $user->email }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Date of Birth</label>
                            <p class="text-gray-900">{{ $user->date_of_birth->format('M j, Y') }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Role</label>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $user->role === 'admin' ? 'bg-red-100 text-red-800' : 
                                   ($user->role === 'judge' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Status</label>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Member Since</label>
                            <p class="text-gray-900">{{ $user->created_at->format('M j, Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location Information -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">Location Information</h2>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">School</label>
                            <p class="text-gray-900">{{ $user->school }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Country</label>
                            <p class="text-gray-900">{{ $user->country }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">City</label>
                            <p class="text-gray-900">{{ $user->city }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subject Registrations -->
            @if($user->subjectRegistrations->count() > 0)
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">Subject Registrations</h2>
                </div>
                <div class="p-4">
                    <div class="space-y-3">
                        @foreach($user->subjectRegistrations as $registration)
                        <div class="flex justify-between items-center py-2 border-b last:border-b-0">
                            <div>
                                <p class="font-medium text-gray-800">{{ $registration->subject->name }}</p>
                                <p class="text-sm text-gray-600">Level: {{ $registration->competitionLevel->name ?? 'N/A' }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full 
                                {{ $registration->payment_status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst($registration->payment_status) }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Recent Test Sessions -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">Recent Test Sessions</h2>
                </div>
                <div class="p-4">
                    @if($user->testSessions->count() > 0)
                    <div class="space-y-3">
                        @foreach($user->testSessions->take(5) as $session)
                        <div class="py-2 border-b last:border-b-0">
                            <p class="font-medium text-gray-800 text-sm">{{ $session->test->title ?? 'N/A' }}</p>
                            <div class="flex justify-between items-center mt-1">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    {{ $session->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                       ($session->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ ucfirst(str_replace('_', ' ', $session->status)) }}
                                </span>
                                @if($session->total_score)
                                <span class="text-sm font-medium text-gray-700">{{ $session->total_score }} pts</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ $session->created_at->format('M j, Y') }}</p>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-gray-500 text-center py-4">No test sessions yet</p>
                    @endif
                </div>
            </div>

            <!-- Research Papers -->
            @if($user->researchPapers->count() > 0)
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">Research Papers</h2>
                </div>
                <div class="p-4">
                    <div class="space-y-3">
                        @foreach($user->researchPapers->take(3) as $paper)
                        <div class="py-2 border-b last:border-b-0">
                            <p class="font-medium text-gray-800 text-sm">{{ Str::limit($paper->title, 50) }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $paper->created_at->format('M j, Y') }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Judge Sessions -->
            @if($user->videoSessionsAsJudge->count() > 0)
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">Judge Sessions</h2>
                </div>
                <div class="p-4">
                    <div class="space-y-3">
                        @foreach($user->videoSessionsAsJudge->take(3) as $session)
                        <div class="py-2 border-b last:border-b-0">
                            <p class="font-medium text-gray-800 text-sm">Research Paper Review</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $session->scheduled_time->format('M j, Y H:i') }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

════════════════════════════════════════════════════════════════════════════════ 📂 Файл: resources/views/welcome.blade.php ════════════════════════════════════════════════════════════════════════════════ <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */@layer theme{:root,:host{--font-sans:'Instrument Sans',ui-sans-serif,system-ui,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";--font-serif:ui-serif,Georgia,Cambria,"Times New Roman",Times,serif;--font-mono:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;--color-red-50:oklch(.971 .013 17.38);--color-red-100:oklch(.936 .032 17.717);--color-red-200:oklch(.885 .062 18.334);--color-red-300:oklch(.808 .114 19.571);--color-red-400:oklch(.704 .191 22.216);--color-red-500:oklch(.637 .237 25.331);--color-red-600:oklch(.577 .245 27.325);--color-red-700:oklch(.505 .213 27.518);--color-red-800:oklch(.444 .177 26.899);--color-red-900:oklch(.396 .141 25.723);--color-red-950:oklch(.258 .092 26.042);--color-orange-50:oklch(.98 .016 73.684);--color-orange-100:oklch(.954 .038 75.164);--color-orange-200:oklch(.901 .076 70.697);--color-orange-300:oklch(.837 .128 66.29);--color-orange-400:oklch(.75 .183 55.934);--color-orange-500:oklch(.705 .213 47.604);--color-orange-600:oklch(.646 .222 41.116);--color-orange-700:oklch(.553 .195 38.402);--color-orange-800:oklch(.47 .157 37.304);--color-orange-900:oklch(.408 .123 38.172);--color-orange-950:oklch(.266 .079 36.259);--color-amber-50:oklch(.987 .022 95.277);--color-amber-100:oklch(.962 .059 95.617);--color-amber-200:oklch(.924 .12 95.746);--color-amber-300:oklch(.879 .169 91.605);--color-amber-400:oklch(.828 .189 84.429);--color-amber-500:oklch(.769 .188 70.08);--color-amber-600:oklch(.666 .179 58.318);--color-amber-700:oklch(.555 .163 48.998);--color-amber-800:oklch(.473 .137 46.201);--color-amber-900:oklch(.414 .112 45.904);--color-amber-950:oklch(.279 .077 45.635);--color-yellow-50:oklch(.987 .026 102.212);--color-yellow-100:oklch(.973 .071 103.193);--color-yellow-200:oklch(.945 .129 101.54);--color-yellow-300:oklch(.905 .182 98.111);--color-yellow-400:oklch(.852 .199 91.936);--color-yellow-500:oklch(.795 .184 86.047);--color-yellow-600:oklch(.681 .162 75.834);--color-yellow-700:oklch(.554 .135 66.442);--color-yellow-800:oklch(.476 .114 61.907);--color-yellow-900:oklch(.421 .095 57.708);--color-yellow-950:oklch(.286 .066 53.813);--color-lime-50:oklch(.986 .031 120.757);--color-lime-100:oklch(.967 .067 122.328);--color-lime-200:oklch(.938 .127 124.321);--color-lime-300:oklch(.897 .196 126.665);--color-lime-400:oklch(.841 .238 128.85);--color-lime-500:oklch(.768 .233 130.85);--color-lime-600:oklch(.648 .2 131.684);--color-lime-700:oklch(.532 .157 131.589);--color-lime-800:oklch(.453 .124 130.933);--color-lime-900:oklch(.405 .101 131.063);--color-lime-950:oklch(.274 .072 132.109);--color-green-50:oklch(.982 .018 155.826);--color-green-100:oklch(.962 .044 156.743);--color-green-200:oklch(.925 .084 155.995);--color-green-300:oklch(.871 .15 154.449);--color-green-400:oklch(.792 .209 151.711);--color-green-500:oklch(.723 .219 149.579);--color-green-600:oklch(.627 .194 149.214);--color-green-700:oklch(.527 .154 150.069);--color-green-800:oklch(.448 .119 151.328);--color-green-900:oklch(.393 .095 152.535);--color-green-950:oklch(.266 .065 152.934);--color-emerald-50:oklch(.979 .021 166.113);--color-emerald-100:oklch(.95 .052 163.051);--color-emerald-200:oklch(.905 .093 164.15);--color-emerald-300:oklch(.845 .143 164.978);--color-emerald-400:oklch(.765 .177 163.223);--color-emerald-500:oklch(.696 .17 162.48);--color-emerald-600:oklch(.596 .145 163.225);--color-emerald-700:oklch(.508 .118 165.612);--color-emerald-800:oklch(.432 .095 166.913);--color-emerald-900:oklch(.378 .077 168.94);--color-emerald-950:oklch(.262 .051 172.552);--color-teal-50:oklch(.984 .014 180.72);--color-teal-100:oklch(.953 .051 180.801);--color-teal-200:oklch(.91 .096 180.426);--color-teal-300:oklch(.855 .138 181.071);--color-teal-400:oklch(.777 .152 181.912);--color-teal-500:oklch(.704 .14 182.503);--color-teal-600:oklch(.6 .118 184.704);--color-teal-700:oklch(.511 .096 186.391);--color-teal-800:oklch(.437 .078 188.216);--color-teal-900:oklch(.386 .063 188.416);--color-teal-950:oklch(.277 .046 192.524);--color-cyan-50:oklch(.984 .019 200.873);--color-cyan-100:oklch(.956 .045 203.388);--color-cyan-200:oklch(.917 .08 205.041);--color-cyan-300:oklch(.865 .127 207.078);--color-cyan-400:oklch(.789 .154 211.53);--color-cyan-500:oklch(.715 .143 215.221);--color-cyan-600:oklch(.609 .126 221.723);--color-cyan-700:oklch(.52 .105 223.128);--color-cyan-800:oklch(.45 .085 224.283);--color-cyan-900:oklch(.398 .07 227.392);--color-cyan-950:oklch(.302 .056 229.695);--color-sky-50:oklch(.977 .013 236.62);--color-sky-100:oklch(.951 .026 236.824);--color-sky-200:oklch(.901 .058 230.902);--color-sky-300:oklch(.828 .111 230.318);--color-sky-400:oklch(.746 .16 232.661);--color-sky-500:oklch(.685 .169 237.323);--color-sky-600:oklch(.588 .158 241.966);--color-sky-700:oklch(.5 .134 242.749);--color-sky-800:oklch(.443 .11 240.79);--color-sky-900:oklch(.391 .09 240.876);--color-sky-950:oklch(.293 .066 243.157);--color-blue-50:oklch(.97 .014 254.604);--color-blue-100:oklch(.932 .032 255.585);--color-blue-200:oklch(.882 .059 254.128);--color-blue-300:oklch(.809 .105 251.813);--color-blue-400:oklch(.707 .165 254.624);--color-blue-500:oklch(.623 .214 259.815);--color-blue-600:oklch(.546 .245 262.881);--color-blue-700:oklch(.488 .243 264.376);--color-blue-800:oklch(.424 .199 265.638);--color-blue-900:oklch(.379 .146 265.522);--color-blue-950:oklch(.282 .091 267.935);--color-indigo-50:oklch(.962 .018 272.314);--color-indigo-100:oklch(.93 .034 272.788);--color-indigo-200:oklch(.87 .065 274.039);--color-indigo-300:oklch(.785 .115 274.713);--color-indigo-400:oklch(.673 .182 276.935);--color-indigo-500:oklch(.585 .233 277.117);--color-indigo-600:oklch(.511 .262 276.966);--color-indigo-700:oklch(.457 .24 277.023);--color-indigo-800:oklch(.398 .195 277.366);--color-indigo-900:oklch(.359 .144 278.697);--color-indigo-950:oklch(.257 .09 281.288);--color-violet-50:oklch(.969 .016 293.756);--color-violet-100:oklch(.943 .029 294.588);--color-violet-200:oklch(.894 .057 293.283);--color-violet-300:oklch(.811 .111 293.571);--color-violet-400:oklch(.702 .183 293.541);--color-violet-500:oklch(.606 .25 292.717);--color-violet-600:oklch(.541 .281 293.009);--color-violet-700:oklch(.491 .27 292.581);--color-violet-800:oklch(.432 .232 292.759);--color-violet-900:oklch(.38 .189 293.745);--color-violet-950:oklch(.283 .141 291.089);--color-purple-50:oklch(.977 .014 308.299);--color-purple-100:oklch(.946 .033 307.174);--color-purple-200:oklch(.902 .063 306.703);--color-purple-300:oklch(.827 .119 306.383);--color-purple-400:oklch(.714 .203 305.504);--color-purple-500:oklch(.627 .265 303.9);--color-purple-600:oklch(.558 .288 302.321);--color-purple-700:oklch(.496 .265 301.924);--color-purple-800:oklch(.438 .218 303.724);--color-purple-900:oklch(.381 .176 304.987);--color-purple-950:oklch(.291 .149 302.717);--color-fuchsia-50:oklch(.977 .017 320.058);--color-fuchsia-100:oklch(.952 .037 318.852);--color-fuchsia-200:oklch(.903 .076 319.62);--color-fuchsia-300:oklch(.833 .145 321.434);--color-fuchsia-400:oklch(.74 .238 322.16);--color-fuchsia-500:oklch(.667 .295 322.15);--color-fuchsia-600:oklch(.591 .293 322.896);--color-fuchsia-700:oklch(.518 .253 323.949);--color-fuchsia-800:oklch(.452 .211 324.591);--color-fuchsia-900:oklch(.401 .17 325.612);--color-fuchsia-950:oklch(.293 .136 325.661);--color-pink-50:oklch(.971 .014 343.198);--color-pink-100:oklch(.948 .028 342.258);--color-pink-200:oklch(.899 .061 343.231);--color-pink-300:oklch(.823 .12 346.018);--color-pink-400:oklch(.718 .202 349.761);--color-pink-500:oklch(.656 .241 354.308);--color-pink-600:oklch(.592 .249 .584);--color-pink-700:oklch(.525 .223 3.958);--color-pink-800:oklch(.459 .187 3.815);--color-pink-900:oklch(.408 .153 2.432);--color-pink-950:oklch(.284 .109 3.907);--color-rose-50:oklch(.969 .015 12.422);--color-rose-100:oklch(.941 .03 12.58);--color-rose-200:oklch(.892 .058 10.001);--color-rose-300:oklch(.81 .117 11.638);--color-rose-400:oklch(.712 .194 13.428);--color-rose-500:oklch(.645 .246 16.439);--color-rose-600:oklch(.586 .253 17.585);--color-rose-700:oklch(.514 .222 16.935);--color-rose-800:oklch(.455 .188 13.697);--color-rose-900:oklch(.41 .159 10.272);--color-rose-950:oklch(.271 .105 12.094);--color-slate-50:oklch(.984 .003 247.858);--color-slate-100:oklch(.968 .007 247.896);--color-slate-200:oklch(.929 .013 255.508);--color-slate-300:oklch(.869 .022 252.894);--color-slate-400:oklch(.704 .04 256.788);--color-slate-500:oklch(.554 .046 257.417);--color-slate-600:oklch(.446 .043 257.281);--color-slate-700:oklch(.372 .044 257.287);--color-slate-800:oklch(.279 .041 260.031);--color-slate-900:oklch(.208 .042 265.755);--color-slate-950:oklch(.129 .042 264.695);--color-gray-50:oklch(.985 .002 247.839);--color-gray-100:oklch(.967 .003 264.542);--color-gray-200:oklch(.928 .006 264.531);--color-gray-300:oklch(.872 .01 258.338);--color-gray-400:oklch(.707 .022 261.325);--color-gray-500:oklch(.551 .027 264.364);--color-gray-600:oklch(.446 .03 256.802);--color-gray-700:oklch(.373 .034 259.733);--color-gray-800:oklch(.278 .033 256.848);--color-gray-900:oklch(.21 .034 264.665);--color-gray-950:oklch(.13 .028 261.692);--color-zinc-50:oklch(.985 0 0);--color-zinc-100:oklch(.967 .001 286.375);--color-zinc-200:oklch(.92 .004 286.32);--color-zinc-300:oklch(.871 .006 286.286);--color-zinc-400:oklch(.705 .015 286.067);--color-zinc-500:oklch(.552 .016 285.938);--color-zinc-600:oklch(.442 .017 285.786);--color-zinc-700:oklch(.37 .013 285.805);--color-zinc-800:oklch(.274 .006 286.033);--color-zinc-900:oklch(.21 .006 285.885);--color-zinc-950:oklch(.141 .005 285.823);--color-neutral-50:oklch(.985 0 0);--color-neutral-100:oklch(.97 0 0);--color-neutral-200:oklch(.922 0 0);--color-neutral-300:oklch(.87 0 0);--color-neutral-400:oklch(.708 0 0);--color-neutral-500:oklch(.556 0 0);--color-neutral-600:oklch(.439 0 0);--color-neutral-700:oklch(.371 0 0);--color-neutral-800:oklch(.269 0 0);--color-neutral-900:oklch(.205 0 0);--color-neutral-950:oklch(.145 0 0);--color-stone-50:oklch(.985 .001 106.423);--color-stone-100:oklch(.97 .001 106.424);--color-stone-200:oklch(.923 .003 48.717);--color-stone-300:oklch(.869 .005 56.366);--color-stone-400:oklch(.709 .01 56.259);--color-stone-500:oklch(.553 .013 58.071);--color-stone-600:oklch(.444 .011 73.639);--color-stone-700:oklch(.374 .01 67.558);--color-stone-800:oklch(.268 .007 34.298);--color-stone-900:oklch(.216 .006 56.043);--color-stone-950:oklch(.147 .004 49.25);--color-black:#000;--color-white:#fff;--spacing:.25rem;--breakpoint-sm:40rem;--breakpoint-md:48rem;--breakpoint-lg:64rem;--breakpoint-xl:80rem;--breakpoint-2xl:96rem;--container-3xs:16rem;--container-2xs:18rem;--container-xs:20rem;--container-sm:24rem;--container-md:28rem;--container-lg:32rem;--container-xl:36rem;--container-2xl:42rem;--container-3xl:48rem;--container-4xl:56rem;--container-5xl:64rem;--container-6xl:72rem;--container-7xl:80rem;--text-xs:.75rem;--text-xs--line-height:calc(1/.75);--text-sm:.875rem;--text-sm--line-height:calc(1.25/.875);--text-base:1rem;--text-base--line-height: 1.5 ;--text-lg:1.125rem;--text-lg--line-height:calc(1.75/1.125);--text-xl:1.25rem;--text-xl--line-height:calc(1.75/1.25);--text-2xl:1.5rem;--text-2xl--line-height:calc(2/1.5);--text-3xl:1.875rem;--text-3xl--line-height: 1.2 ;--text-4xl:2.25rem;--text-4xl--line-height:calc(2.5/2.25);--text-5xl:3rem;--text-5xl--line-height:1;--text-6xl:3.75rem;--text-6xl--line-height:1;--text-7xl:4.5rem;--text-7xl--line-height:1;--text-8xl:6rem;--text-8xl--line-height:1;--text-9xl:8rem;--text-9xl--line-height:1;--font-weight-thin:100;--font-weight-extralight:200;--font-weight-light:300;--font-weight-normal:400;--font-weight-medium:500;--font-weight-semibold:600;--font-weight-bold:700;--font-weight-extrabold:800;--font-weight-black:900;--tracking-tighter:-.05em;--tracking-tight:-.025em;--tracking-normal:0em;--tracking-wide:.025em;--tracking-wider:.05em;--tracking-widest:.1em;--leading-tight:1.25;--leading-snug:1.375;--leading-normal:1.5;--leading-relaxed:1.625;--leading-loose:2;--radius-xs:.125rem;--radius-sm:.25rem;--radius-md:.375rem;--radius-lg:.5rem;--radius-xl:.75rem;--radius-2xl:1rem;--radius-3xl:1.5rem;--radius-4xl:2rem;--shadow-2xs:0 1px #0000000d;--shadow-xs:0 1px 2px 0 #0000000d;--shadow-sm:0 1px 3px 0 #0000001a,0 1px 2px -1px #0000001a;--shadow-md:0 4px 6px -1px #0000001a,0 2px 4px -2px #0000001a;--shadow-lg:0 10px 15px -3px #0000001a,0 4px 6px -4px #0000001a;--shadow-xl:0 20px 25px -5px #0000001a,0 8px 10px -6px #0000001a;--shadow-2xl:0 25px 50px -12px #00000040;--inset-shadow-2xs:inset 0 1px #0000000d;--inset-shadow-xs:inset 0 1px 1px #0000000d;--inset-shadow-sm:inset 0 2px 4px #0000000d;--drop-shadow-xs:0 1px 1px #0000000d;--drop-shadow-sm:0 1px 2px #00000026;--drop-shadow-md:0 3px 3px #0000001f;--drop-shadow-lg:0 4px 4px #00000026;--drop-shadow-xl:0 9px 7px #0000001a;--drop-shadow-2xl:0 25px 25px #00000026;--ease-in:cubic-bezier(.4,0,1,1);--ease-out:cubic-bezier(0,0,.2,1);--ease-in-out:cubic-bezier(.4,0,.2,1);--animate-spin:spin 1s linear infinite;--animate-ping:ping 1s cubic-bezier(0,0,.2,1)infinite;--animate-pulse:pulse 2s cubic-bezier(.4,0,.6,1)infinite;--animate-bounce:bounce 1s infinite;--blur-xs:4px;--blur-sm:8px;--blur-md:12px;--blur-lg:16px;--blur-xl:24px;--blur-2xl:40px;--blur-3xl:64px;--perspective-dramatic:100px;--perspective-near:300px;--perspective-normal:500px;--perspective-midrange:800px;--perspective-distant:1200px;--aspect-video:16/9;--default-transition-duration:.15s;--default-transition-timing-function:cubic-bezier(.4,0,.2,1);--default-font-family:var(--font-sans);--default-font-feature-settings:var(--font-sans--font-feature-settings);--default-font-variation-settings:var(--font-sans--font-variation-settings);--default-mono-font-family:var(--font-mono);--default-mono-font-feature-settings:var(--font-mono--font-feature-settings);--default-mono-font-variation-settings:var(--font-mono--font-variation-settings)}}@layer base{*,:after,:before,::backdrop{box-sizing:border-box;border:0 solid;margin:0;padding:0}::file-selector-button{box-sizing:border-box;border:0 solid;margin:0;padding:0}html,:host{-webkit-text-size-adjust:100%;-moz-tab-size:4;tab-size:4;line-height:1.5;font-family:var(--default-font-family,ui-sans-serif,system-ui,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji");font-feature-settings:var(--default-font-feature-settings,normal);font-variation-settings:var(--default-font-variation-settings,normal);-webkit-tap-highlight-color:transparent}body{line-height:inherit}hr{height:0;color:inherit;border-top-width:1px}abbr:where([title]){-webkit-text-decoration:underline dotted;text-decoration:underline dotted}h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}a{color:inherit;-webkit-text-decoration:inherit;text-decoration:inherit}b,strong{font-weight:bolder}code,kbd,samp,pre{font-family:var(--default-mono-font-family,ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace);font-feature-settings:var(--default-mono-font-feature-settings,normal);font-variation-settings:var(--default-mono-font-variation-settings,normal);font-size:1em}small{font-size:80%}sub,sup{vertical-align:baseline;font-size:75%;line-height:0;position:relative}sub{bottom:-.25em}sup{top:-.5em}table{text-indent:0;border-color:inherit;border-collapse:collapse}:-moz-focusring{outline:auto}progress{vertical-align:baseline}summary{display:list-item}ol,ul,menu{list-style:none}img,svg,video,canvas,audio,iframe,embed,object{vertical-align:middle;display:block}img,video{max-width:100%;height:auto}button,input,select,optgroup,textarea{font:inherit;font-feature-settings:inherit;font-variation-settings:inherit;letter-spacing:inherit;color:inherit;opacity:1;background-color:#0000;border-radius:0}::file-selector-button{font:inherit;font-feature-settings:inherit;font-variation-settings:inherit;letter-spacing:inherit;color:inherit;opacity:1;background-color:#0000;border-radius:0}:where(select:is([multiple],[size])) optgroup{font-weight:bolder}:where(select:is([multiple],[size])) optgroup option{padding-inline-start:20px}::file-selector-button{margin-inline-end:4px}::placeholder{opacity:1;color:color-mix(in oklab,currentColor 50%,transparent)}textarea{resize:vertical}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-date-and-time-value{min-height:1lh;text-align:inherit}::-webkit-datetime-edit{display:inline-flex}::-webkit-datetime-edit-fields-wrapper{padding:0}::-webkit-datetime-edit{padding-block:0}::-webkit-datetime-edit-year-field{padding-block:0}::-webkit-datetime-edit-month-field{padding-block:0}::-webkit-datetime-edit-day-field{padding-block:0}::-webkit-datetime-edit-hour-field{padding-block:0}::-webkit-datetime-edit-minute-field{padding-block:0}::-webkit-datetime-edit-second-field{padding-block:0}::-webkit-datetime-edit-millisecond-field{padding-block:0}::-webkit-datetime-edit-meridiem-field{padding-block:0}:-moz-ui-invalid{box-shadow:none}button,input:where([type=button],[type=reset],[type=submit]){-webkit-appearance:button;-moz-appearance:button;appearance:button}::file-selector-button{-webkit-appearance:button;-moz-appearance:button;appearance:button}::-webkit-inner-spin-button{height:auto}::-webkit-outer-spin-button{height:auto}[hidden]:where(:not([hidden=until-found])){display:none!important}}@layer components;@layer utilities{.absolute{position:absolute}.relative{position:relative}.static{position:static}.inset-0{inset:calc(var(--spacing)*0)}.-mt-\[4\.9rem\]{margin-top:-4.9rem}.-mb-px{margin-bottom:-1px}.mb-1{margin-bottom:calc(var(--spacing)*1)}.mb-2{margin-bottom:calc(var(--spacing)*2)}.mb-4{margin-bottom:calc(var(--spacing)*4)}.mb-6{margin-bottom:calc(var(--spacing)*6)}.-ml-8{margin-left:calc(var(--spacing)*-8)}.flex{display:flex}.hidden{display:none}.inline-block{display:inline-block}.inline-flex{display:inline-flex}.table{display:table}.aspect-\[335\/376\]{aspect-ratio:335/376}.h-1{height:calc(var(--spacing)*1)}.h-1\.5{height:calc(var(--spacing)*1.5)}.h-2{height:calc(var(--spacing)*2)}.h-2\.5{height:calc(var(--spacing)*2.5)}.h-3{height:calc(var(--spacing)*3)}.h-3\.5{height:calc(var(--spacing)*3.5)}.h-14{height:calc(var(--spacing)*14)}.h-14\.5{height:calc(var(--spacing)*14.5)}.min-h-screen{min-height:100vh}.w-1{width:calc(var(--spacing)*1)}.w-1\.5{width:calc(var(--spacing)*1.5)}.w-2{width:calc(var(--spacing)*2)}.w-2\.5{width:calc(var(--spacing)*2.5)}.w-3{width:calc(var(--spacing)*3)}.w-3\.5{width:calc(var(--spacing)*3.5)}.w-\[448px\]{width:448px}.w-full{width:100%}.max-w-\[335px\]{max-width:335px}.max-w-none{max-width:none}.flex-1{flex:1}.shrink-0{flex-shrink:0}.translate-y-0{--tw-translate-y:calc(var(--spacing)*0);translate:var(--tw-translate-x)var(--tw-translate-y)}.transform{transform:var(--tw-rotate-x)var(--tw-rotate-y)var(--tw-rotate-z)var(--tw-skew-x)var(--tw-skew-y)}.flex-col{flex-direction:column}.flex-col-reverse{flex-direction:column-reverse}.items-center{align-items:center}.justify-center{justify-content:center}.justify-end{justify-content:flex-end}.gap-3{gap:calc(var(--spacing)*3)}.gap-4{gap:calc(var(--spacing)*4)}:where(.space-x-1>:not(:last-child)){--tw-space-x-reverse:0;margin-inline-start:calc(calc(var(--spacing)*1)*var(--tw-space-x-reverse));margin-inline-end:calc(calc(var(--spacing)*1)*calc(1 - var(--tw-space-x-reverse)))}.overflow-hidden{overflow:hidden}.rounded-full{border-radius:3.40282e38px}.rounded-sm{border-radius:var(--radius-sm)}.rounded-t-lg{border-top-left-radius:var(--radius-lg);border-top-right-radius:var(--radius-lg)}.rounded-br-lg{border-bottom-right-radius:var(--radius-lg)}.rounded-bl-lg{border-bottom-left-radius:var(--radius-lg)}.border{border-style:var(--tw-border-style);border-width:1px}.border-\[\#19140035\]{border-color:#19140035}.border-\[\#e3e3e0\]{border-color:#e3e3e0}.border-black{border-color:var(--color-black)}.border-transparent{border-color:#0000}.bg-\[\#1b1b18\]{background-color:#1b1b18}.bg-\[\#FDFDFC\]{background-color:#fdfdfc}.bg-\[\#dbdbd7\]{background-color:#dbdbd7}.bg-\[\#fff2f2\]{background-color:#fff2f2}.bg-white{background-color:var(--color-white)}.p-6{padding:calc(var(--spacing)*6)}.px-5{padding-inline:calc(var(--spacing)*5)}.py-1{padding-block:calc(var(--spacing)*1)}.py-1\.5{padding-block:calc(var(--spacing)*1.5)}.py-2{padding-block:calc(var(--spacing)*2)}.pb-12{padding-bottom:calc(var(--spacing)*12)}.text-sm{font-size:var(--text-sm);line-height:var(--tw-leading,var(--text-sm--line-height))}.text-\[13px\]{font-size:13px}.leading-\[20px\]{--tw-leading:20px;line-height:20px}.leading-normal{--tw-leading:var(--leading-normal);line-height:var(--leading-normal)}.font-medium{--tw-font-weight:var(--font-weight-medium);font-weight:var(--font-weight-medium)}.text-\[\#1b1b18\]{color:#1b1b18}.text-\[\#706f6c\]{color:#706f6c}.text-\[\#F53003\],.text-\[\#f53003\]{color:#f53003}.text-white{color:var(--color-white)}.underline{text-decoration-line:underline}.underline-offset-4{text-underline-offset:4px}.opacity-100{opacity:1}.shadow-\[0px_0px_1px_0px_rgba\(0\,0\,0\,0\.03\)\,0px_1px_2px_0px_rgba\(0\,0\,0\,0\.06\)\]{--tw-shadow:0px 0px 1px 0px var(--tw-shadow-color,#00000008),0px 1px 2px 0px var(--tw-shadow-color,#0000000f);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.shadow-\[inset_0px_0px_0px_1px_rgba\(26\,26\,0\,0\.16\)\]{--tw-shadow:inset 0px 0px 0px 1px var(--tw-shadow-color,#1a1a0029);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.\!filter{filter:var(--tw-blur,)var(--tw-brightness,)var(--tw-contrast,)var(--tw-grayscale,)var(--tw-hue-rotate,)var(--tw-invert,)var(--tw-saturate,)var(--tw-sepia,)var(--tw-drop-shadow,)!important}.filter{filter:var(--tw-blur,)var(--tw-brightness,)var(--tw-contrast,)var(--tw-grayscale,)var(--tw-hue-rotate,)var(--tw-invert,)var(--tw-saturate,)var(--tw-sepia,)var(--tw-drop-shadow,)}.transition-all{transition-property:all;transition-timing-function:var(--tw-ease,var(--default-transition-timing-function));transition-duration:var(--tw-duration,var(--default-transition-duration))}.transition-opacity{transition-property:opacity;transition-timing-function:var(--tw-ease,var(--default-transition-timing-function));transition-duration:var(--tw-duration,var(--default-transition-duration))}.delay-300{transition-delay:.3s}.duration-750{--tw-duration:.75s;transition-duration:.75s}.not-has-\[nav\]\:hidden:not(:has(:is(nav))){display:none}.before\:absolute:before{content:var(--tw-content);position:absolute}.before\:top-0:before{content:var(--tw-content);top:calc(var(--spacing)*0)}.before\:top-1\/2:before{content:var(--tw-content);top:50%}.before\:bottom-0:before{content:var(--tw-content);bottom:calc(var(--spacing)*0)}.before\:bottom-1\/2:before{content:var(--tw-content);bottom:50%}.before\:left-\[0\.4rem\]:before{content:var(--tw-content);left:.4rem}.before\:border-l:before{content:var(--tw-content);border-left-style:var(--tw-border-style);border-left-width:1px}.before\:border-\[\#e3e3e0\]:before{content:var(--tw-content);border-color:#e3e3e0}@media (hover:hover){.hover\:border-\[\#1915014a\]:hover{border-color:#1915014a}.hover\:border-\[\#19140035\]:hover{border-color:#19140035}.hover\:border-black:hover{border-color:var(--color-black)}.hover\:bg-black:hover{background-color:var(--color-black)}}@media (width>=64rem){.lg\:-mt-\[6\.6rem\]{margin-top:-6.6rem}.lg\:mb-0{margin-bottom:calc(var(--spacing)*0)}.lg\:mb-6{margin-bottom:calc(var(--spacing)*6)}.lg\:-ml-px{margin-left:-1px}.lg\:ml-0{margin-left:calc(var(--spacing)*0)}.lg\:block{display:block}.lg\:aspect-auto{aspect-ratio:auto}.lg\:w-\[438px\]{width:438px}.lg\:max-w-4xl{max-width:var(--container-4xl)}.lg\:grow{flex-grow:1}.lg\:flex-row{flex-direction:row}.lg\:justify-center{justify-content:center}.lg\:rounded-t-none{border-top-left-radius:0;border-top-right-radius:0}.lg\:rounded-tl-lg{border-top-left-radius:var(--radius-lg)}.lg\:rounded-r-lg{border-top-right-radius:var(--radius-lg);border-bottom-right-radius:var(--radius-lg)}.lg\:rounded-br-none{border-bottom-right-radius:0}.lg\:p-8{padding:calc(var(--spacing)*8)}.lg\:p-20{padding:calc(var(--spacing)*20)}}@media (prefers-color-scheme:dark){.dark\:block{display:block}.dark\:hidden{display:none}.dark\:border-\[\#3E3E3A\]{border-color:#3e3e3a}.dark\:border-\[\#eeeeec\]{border-color:#eeeeec}.dark\:bg-\[\#0a0a0a\]{background-color:#0a0a0a}.dark\:bg-\[\#1D0002\]{background-color:#1d0002}.dark\:bg-\[\#3E3E3A\]{background-color:#3e3e3a}.dark\:bg-\[\#161615\]{background-color:#161615}.dark\:bg-\[\#eeeeec\]{background-color:#eeeeec}.dark\:text-\[\#1C1C1A\]{color:#1c1c1a}.dark\:text-\[\#A1A09A\]{color:#a1a09a}.dark\:text-\[\#EDEDEC\]{color:#ededec}.dark\:text-\[\#F61500\]{color:#f61500}.dark\:text-\[\#FF4433\]{color:#f43}.dark\:shadow-\[inset_0px_0px_0px_1px_\#fffaed2d\]{--tw-shadow:inset 0px 0px 0px 1px var(--tw-shadow-color,#fffaed2d);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.dark\:before\:border-\[\#3E3E3A\]:before{content:var(--tw-content);border-color:#3e3e3a}@media (hover:hover){.dark\:hover\:border-\[\#3E3E3A\]:hover{border-color:#3e3e3a}.dark\:hover\:border-\[\#62605b\]:hover{border-color:#62605b}.dark\:hover\:border-white:hover{border-color:var(--color-white)}.dark\:hover\:bg-white:hover{background-color:var(--color-white)}}}@starting-style{.starting\:translate-y-4{--tw-translate-y:calc(var(--spacing)*4);translate:var(--tw-translate-x)var(--tw-translate-y)}}@starting-style{.starting\:translate-y-6{--tw-translate-y:calc(var(--spacing)*6);translate:var(--tw-translate-x)var(--tw-translate-y)}}@starting-style{.starting\:opacity-0{opacity:0}}}@keyframes spin{to{transform:rotate(360deg)}}@keyframes ping{75%,to{opacity:0;transform:scale(2)}}@keyframes pulse{50%{opacity:.5}}@keyframes bounce{0%,to{animation-timing-function:cubic-bezier(.8,0,1,1);transform:translateY(-25%)}50%{animation-timing-function:cubic-bezier(0,0,.2,1);transform:none}}@property --tw-translate-x{syntax:"*";inherits:false;initial-value:0}@property --tw-translate-y{syntax:"*";inherits:false;initial-value:0}@property --tw-translate-z{syntax:"*";inherits:false;initial-value:0}@property --tw-rotate-x{syntax:"*";inherits:false;initial-value:rotateX(0)}@property --tw-rotate-y{syntax:"*";inherits:false;initial-value:rotateY(0)}@property --tw-rotate-z{syntax:"*";inherits:false;initial-value:rotateZ(0)}@property --tw-skew-x{syntax:"*";inherits:false;initial-value:skewX(0)}@property --tw-skew-y{syntax:"*";inherits:false;initial-value:skewY(0)}@property --tw-space-x-reverse{syntax:"*";inherits:false;initial-value:0}@property --tw-border-style{syntax:"*";inherits:false;initial-value:solid}@property --tw-leading{syntax:"*";inherits:false}@property --tw-font-weight{syntax:"*";inherits:false}@property --tw-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-shadow-color{syntax:"*";inherits:false}@property --tw-inset-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-inset-shadow-color{syntax:"*";inherits:false}@property --tw-ring-color{syntax:"*";inherits:false}@property --tw-ring-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-inset-ring-color{syntax:"*";inherits:false}@property --tw-inset-ring-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-ring-inset{syntax:"*";inherits:false}@property --tw-ring-offset-width{syntax:"<length>";inherits:false;initial-value:0}@property --tw-ring-offset-color{syntax:"*";inherits:false;initial-value:#fff}@property --tw-ring-offset-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-blur{syntax:"*";inherits:false}@property --tw-brightness{syntax:"*";inherits:false}@property --tw-contrast{syntax:"*";inherits:false}@property --tw-grayscale{syntax:"*";inherits:false}@property --tw-hue-rotate{syntax:"*";inherits:false}@property --tw-invert{syntax:"*";inherits:false}@property --tw-opacity{syntax:"*";inherits:false}@property --tw-saturate{syntax:"*";inherits:false}@property --tw-sepia{syntax:"*";inherits:false}@property --tw-drop-shadow{syntax:"*";inherits:false}@property --tw-duration{syntax:"*";inherits:false}@property --tw-content{syntax:"*";inherits:false;initial-value:""}
            </style>
        @endif
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden">
            @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4">
                    @auth
                        <a
                            href="{{ url('/dashboard') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal"
                        >
                            Dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
                        >
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>
        <div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
            <main class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row">
                <div class="text-[13px] leading-[20px] flex-1 p-6 pb-12 lg:p-20 bg-white dark:bg-[#161615] dark:text-[#EDEDEC] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] rounded-bl-lg rounded-br-lg lg:rounded-tl-lg lg:rounded-br-none">
                    <h1 class="mb-1 font-medium">Let's get started</h1>
                    <p class="mb-2 text-[#706f6c] dark:text-[#A1A09A]">Laravel has an incredibly rich ecosystem. <br>We suggest starting with the following.</p>
                    <ul class="flex flex-col mb-4 lg:mb-6">
                        <li class="flex items-center gap-4 py-2 relative before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A] before:top-1/2 before:bottom-0 before:left-[0.4rem] before:absolute">
                            <span class="relative py-1 bg-white dark:bg-[#161615]">
                                <span class="flex items-center justify-center rounded-full bg-[#FDFDFC] dark:bg-[#161615] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] w-3.5 h-3.5 border dark:border-[#3E3E3A] border-[#e3e3e0]">
                                    <span class="rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A] w-1.5 h-1.5"></span>
                                </span>
                            </span>
                            <span>
                                Read the
                                <a href="https://laravel.com/docs" target="_blank" class="inline-flex items-center space-x-1 font-medium underline underline-offset-4 text-[#f53003] dark:text-[#FF4433] ml-1">
                                    <span>Documentation</span>
                                    <svg
                                        width="10"
                                        height="11"
                                        viewBox="0 0 10 11"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="w-2.5 h-2.5"
                                    >
                                        <path
                                            d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001"
                                            stroke="currentColor"
                                            stroke-linecap="square"
                                        />
                                    </svg>
                                </a>
                            </span>
                        </li>
                        <li class="flex items-center gap-4 py-2 relative before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A] before:bottom-1/2 before:top-0 before:left-[0.4rem] before:absolute">
                            <span class="relative py-1 bg-white dark:bg-[#161615]">
                                <span class="flex items-center justify-center rounded-full bg-[#FDFDFC] dark:bg-[#161615] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] w-3.5 h-3.5 border dark:border-[#3E3E3A] border-[#e3e3e0]">
                                    <span class="rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A] w-1.5 h-1.5"></span>
                                </span>
                            </span>
                            <span>
                                Watch video tutorials at
                                <a href="https://laracasts.com" target="_blank" class="inline-flex items-center space-x-1 font-medium underline underline-offset-4 text-[#f53003] dark:text-[#FF4433] ml-1">
                                    <span>Laracasts</span>
                                    <svg
                                        width="10"
                                        height="11"
                                        viewBox="0 0 10 11"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="w-2.5 h-2.5"
                                    >
                                        <path
                                            d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001"
                                            stroke="currentColor"
                                            stroke-linecap="square"
                                        />
                                    </svg>
                                </a>
                            </span>
                        </li>
                    </ul>
                    <ul class="flex gap-3 text-sm leading-normal">
                        <li>
                            <a href="https://cloud.laravel.com" target="_blank" class="inline-block dark:bg-[#eeeeec] dark:border-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white dark:hover:border-white hover:bg-black hover:border-black px-5 py-1.5 bg-[#1b1b18] rounded-sm border border-black text-white text-sm leading-normal">
                                Deploy now
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="bg-[#fff2f2] dark:bg-[#1D0002] relative lg:-ml-px -mb-px lg:mb-0 rounded-t-lg lg:rounded-t-none lg:rounded-r-lg aspect-[335/376] lg:aspect-auto w-full lg:w-[438px] shrink-0 overflow-hidden">
                    {{-- Laravel Logo --}}
                    <svg class="w-full text-[#F53003] dark:text-[#F61500] transition-all translate-y-0 opacity-100 max-w-none duration-750 starting:opacity-0 starting:translate-y-6" viewBox="0 0 438 104" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.2036 -3H0V102.197H49.5189V86.7187H17.2036V-3Z" fill="currentColor" />
                        <path d="M110.256 41.6337C108.061 38.1275 104.945 35.3731 100.905 33.3681C96.8667 31.3647 92.8016 30.3618 88.7131 30.3618C83.4247 30.3618 78.5885 31.3389 74.201 33.2923C69.8111 35.2456 66.0474 37.928 62.9059 41.3333C59.7643 44.7401 57.3198 48.6726 55.5754 53.1293C53.8287 57.589 52.9572 62.274 52.9572 67.1813C52.9572 72.1925 53.8287 76.8995 55.5754 81.3069C57.3191 85.7173 59.7636 89.6241 62.9059 93.0293C66.0474 96.4361 69.8119 99.1155 74.201 101.069C78.5885 103.022 83.4247 103.999 88.7131 103.999C92.8016 103.999 96.8667 102.997 100.905 100.994C104.945 98.9911 108.061 96.2359 110.256 92.7282V102.195H126.563V32.1642H110.256V41.6337ZM108.76 75.7472C107.762 78.4531 106.366 80.8078 104.572 82.8112C102.776 84.8161 100.606 86.4183 98.0637 87.6206C95.5202 88.823 92.7004 89.4238 89.6103 89.4238C86.5178 89.4238 83.7252 88.823 81.2324 87.6206C78.7388 86.4183 76.5949 84.8161 74.7998 82.8112C73.004 80.8078 71.6319 78.4531 70.6856 75.7472C69.7356 73.0421 69.2644 70.1868 69.2644 67.1821C69.2644 64.1758 69.7356 61.3205 70.6856 58.6154C71.6319 55.9102 73.004 53.5571 74.7998 51.5522C76.5949 49.5495 78.738 47.9451 81.2324 46.7427C83.7252 45.5404 86.5178 44.9396 89.6103 44.9396C92.7012 44.9396 95.5202 45.5404 98.0637 46.7427C100.606 47.9451 102.776 49.5487 104.572 51.5522C106.367 53.5571 107.762 55.9102 108.76 58.6154C109.756 61.3205 110.256 64.1758 110.256 67.1821C110.256 70.1868 109.756 73.0421 108.76 75.7472Z" fill="currentColor" />
                        <path d="M242.805 41.6337C240.611 38.1275 237.494 35.3731 233.455 33.3681C229.416 31.3647 225.351 30.3618 221.262 30.3618C215.974 30.3618 211.138 31.3389 206.75 33.2923C202.36 35.2456 198.597 37.928 195.455 41.3333C192.314 44.7401 189.869 48.6726 188.125 53.1293C186.378 57.589 185.507 62.274 185.507 67.1813C185.507 72.1925 186.378 76.8995 188.125 81.3069C189.868 85.7173 192.313 89.6241 195.455 93.0293C198.597 96.4361 202.361 99.1155 206.75 101.069C211.138 103.022 215.974 103.999 221.262 103.999C225.351 103.999 229.416 102.997 233.455 100.994C237.494 98.9911 240.611 96.2359 242.805 92.7282V102.195H259.112V32.1642H242.805V41.6337ZM241.31 75.7472C240.312 78.4531 238.916 80.8078 237.122 82.8112C235.326 84.8161 233.156 86.4183 230.614 87.6206C228.07 88.823 225.251 89.4238 222.16 89.4238C219.068 89.4238 216.275 88.823 213.782 87.6206C211.289 86.4183 209.145 84.8161 207.35 82.8112C205.554 80.8078 204.182 78.4531 203.236 75.7472C202.286 73.0421 201.814 70.1868 201.814 67.1821C201.814 64.1758 202.286 61.3205 203.236 58.6154C204.182 55.9102 205.554 53.5571 207.35 51.5522C209.145 49.5495 211.288 47.9451 213.782 46.7427C216.275 45.5404 219.068 44.9396 222.16 44.9396C225.251 44.9396 228.07 45.5404 230.614 46.7427C233.156 47.9451 235.326 49.5487 237.122 51.5522C238.917 53.5571 240.312 55.9102 241.31 58.6154C242.306 61.3205 242.806 64.1758 242.806 67.1821C242.805 70.1868 242.305 73.0421 241.31 75.7472Z" fill="currentColor" />
                        <path d="M438 -3H421.694V102.197H438V-3Z" fill="currentColor" />
                        <path d="M139.43 102.197H155.735V48.2834H183.712V32.1665H139.43V102.197Z" fill="currentColor" />
                        <path d="M324.49 32.1665L303.995 85.794L283.498 32.1665H266.983L293.748 102.197H314.242L341.006 32.1665H324.49Z" fill="currentColor" />
                        <path d="M376.571 30.3656C356.603 30.3656 340.797 46.8497 340.797 67.1828C340.797 89.6597 356.094 104 378.661 104C391.29 104 399.354 99.1488 409.206 88.5848L398.189 80.0226C398.183 80.031 389.874 90.9895 377.468 90.9895C363.048 90.9895 356.977 79.3111 356.977 73.269H411.075C413.917 50.1328 398.775 30.3656 376.571 30.3656ZM357.02 61.0967C357.145 59.7487 359.023 43.3761 376.442 43.3761C393.861 43.3761 395.978 59.7464 396.099 61.0967H357.02Z" fill="currentColor" />
                    </svg>

                    {{-- Light Mode 12 SVG --}}
                    <svg class="w-[448px] max-w-none relative -mt-[4.9rem] -ml-8 lg:ml-0 lg:-mt-[6.6rem] dark:hidden" viewBox="0 0 440 376" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g class="transition-all delay-300 translate-y-0 opacity-100 duration-750 starting:opacity-0 starting:translate-y-4">
                            <path d="M188.263 355.73L188.595 355.73C195.441 348.845 205.766 339.761 219.569 328.477C232.93 317.193 242.978 308.205 249.714 301.511C256.34 294.626 260.867 287.358 263.296 279.708C265.725 272.058 264.565 264.121 259.816 255.896C254.516 246.716 247.062 239.352 237.454 233.805C227.957 228.067 217.908 225.198 207.307 225.198C196.927 225.197 190.136 227.97 186.934 233.516C183.621 238.872 184.726 246.331 190.247 255.894L125.647 255.891C116.371 239.825 112.395 225.481 113.72 212.858C115.265 200.235 121.559 190.481 132.602 183.596C143.754 176.52 158.607 172.982 177.159 172.983C196.594 172.984 215.863 176.523 234.968 183.6C253.961 190.486 271.299 200.241 286.98 212.864C302.661 225.488 315.14 239.833 324.416 255.899C333.03 270.817 336.841 283.918 335.847 295.203C335.075 306.487 331.376 316.336 324.75 324.751C318.346 333.167 308.408 343.494 294.936 355.734L377.094 355.737L405.917 405.656L217.087 405.649L188.263 355.73Z" fill="black" />
                            <path d="M9.11884 226.339L-13.7396 226.338L-42.7286 176.132L43.0733 176.135L175.595 405.649L112.651 405.647L9.11884 226.339Z" fill="black" />
                            <path d="M188.263 355.73L188.595 355.73C195.441 348.845 205.766 339.761 219.569 328.477C232.93 317.193 242.978 308.205 249.714 301.511C256.34 294.626 260.867 287.358 263.296 279.708C265.725 272.058 264.565 264.121 259.816 255.896C254.516 246.716 247.062 239.352 237.454 233.805C227.957 228.067 217.908 225.198 207.307 225.198C196.927 225.197 190.136 227.97 186.934 233.516C183.621 238.872 184.726 246.331 190.247 255.894L125.647 255.891C116.371 239.825 112.395 225.481 113.72 212.858C115.265 200.235 121.559 190.481 132.602 183.596C143.754 176.52 158.607 172.982 177.159 172.983C196.594 172.984 215.863 176.523 234.968 183.6C253.961 190.486 271.299 200.241 286.98 212.864C302.661 225.488 315.14 239.833 324.416 255.899C333.03 270.817 336.841 283.918 335.847 295.203C335.075 306.487 331.376 316.336 324.75 324.751C318.346 333.167 308.408 343.494 294.936 355.734L377.094 355.737L405.917 405.656L217.087 405.649L188.263 355.73Z" stroke="#1B1B18" stroke-width="1" />
                            <path d="M9.11884 226.339L-13.7396 226.338L-42.7286 176.132L43.0733 176.135L175.595 405.649L112.651 405.647L9.11884 226.339Z" stroke="#1B1B18" stroke-width="1" />
                            <path d="M204.592 327.449L204.923 327.449C211.769 320.564 222.094 311.479 235.897 300.196C249.258 288.912 259.306 279.923 266.042 273.23C272.668 266.345 277.195 259.077 279.624 251.427C282.053 243.777 280.893 235.839 276.145 227.615C270.844 218.435 263.39 211.071 253.782 205.524C244.285 199.786 234.236 196.917 223.635 196.916C213.255 196.916 206.464 199.689 203.262 205.235C199.949 210.59 201.054 218.049 206.575 227.612L141.975 227.61C132.699 211.544 128.723 197.2 130.048 184.577C131.593 171.954 137.887 162.2 148.93 155.315C160.083 148.239 174.935 144.701 193.487 144.702C212.922 144.703 232.192 148.242 251.296 155.319C270.289 162.205 287.627 171.96 303.308 184.583C318.989 197.207 331.468 211.552 340.745 227.618C349.358 242.536 353.169 255.637 352.175 266.921C351.403 278.205 347.704 288.055 341.078 296.47C334.674 304.885 324.736 315.213 311.264 327.453L393.422 327.456L422.246 377.375L233.415 377.368L204.592 327.449Z" fill="#F8B803" />
                            <path d="M25.447 198.058L2.58852 198.057L-26.4005 147.851L59.4015 147.854L191.923 377.368L128.979 377.365L25.447 198.058Z" fill="#F8B803" />
                            <path d="M204.592 327.449L204.923 327.449C211.769 320.564 222.094 311.479 235.897 300.196C249.258 288.912 259.306 279.923 266.042 273.23C272.668 266.345 277.195 259.077 279.624 251.427C282.053 243.777 280.893 235.839 276.145 227.615C270.844 218.435 263.39 211.071 253.782 205.524C244.285 199.786 234.236 196.917 223.635 196.916C213.255 196.916 206.464 199.689 203.262 205.235C199.949 210.59 201.054 218.049 206.575 227.612L141.975 227.61C132.699 211.544 128.723 197.2 130.048 184.577C131.593 171.954 137.887 162.2 148.93 155.315C160.083 148.239 174.935 144.701 193.487 144.702C212.922 144.703 232.192 148.242 251.296 155.319C270.289 162.205 287.627 171.96 303.308 184.583C318.989 197.207 331.468 211.552 340.745 227.618C349.358 242.536 353.169 255.637 352.175 266.921C351.403 278.205 347.704 288.055 341.078 296.47C334.674 304.885 324.736 315.213 311.264 327.453L393.422 327.456L422.246 377.375L233.415 377.368L204.592 327.449Z" stroke="#1B1B18" stroke-width="1" />
                            <path d="M25.447 198.058L2.58852 198.057L-26.4005 147.851L59.4015 147.854L191.923 377.368L128.979 377.365L25.447 198.058Z" stroke="#1B1B18" stroke-width="1" />
                        </g>
                        <g style="mix-blend-mode: hard-light" class="transition-all delay-300 translate-y-0 opacity-100 duration-750 starting:opacity-0 starting:translate-y-4">
                            <path d="M217.342 305.363L217.673 305.363C224.519 298.478 234.844 289.393 248.647 278.11C262.008 266.826 272.056 257.837 278.792 251.144C285.418 244.259 289.945 236.991 292.374 229.341C294.803 221.691 293.643 213.753 288.895 205.529C283.594 196.349 276.14 188.985 266.532 183.438C257.035 177.7 246.986 174.831 236.385 174.83C226.005 174.83 219.214 177.603 216.012 183.149C212.699 188.504 213.804 195.963 219.325 205.527L154.725 205.524C145.449 189.458 141.473 175.114 142.798 162.491C144.343 149.868 150.637 140.114 161.68 133.229C172.833 126.153 187.685 122.615 206.237 122.616C225.672 122.617 244.942 126.156 264.046 133.233C283.039 140.119 300.377 149.874 316.058 162.497C331.739 175.121 344.218 189.466 353.495 205.532C362.108 220.45 365.919 233.551 364.925 244.835C364.153 256.12 360.454 265.969 353.828 274.384C347.424 282.799 337.486 293.127 324.014 305.367L406.172 305.37L434.996 355.289L246.165 355.282L217.342 305.363Z" fill="#F0ACB8" />
                            <path d="M38.197 175.972L15.3385 175.971L-13.6505 125.765L72.1515 125.768L204.673 355.282L141.729 355.279L38.197 175.972Z" fill="#F0ACB8" />
                            <path d="M217.342 305.363L217.673 305.363C224.519 298.478 234.844 289.393 248.647 278.11C262.008 266.826 272.056 257.837 278.792 251.144C285.418 244.259 289.945 236.991 292.374 229.341C294.803 221.691 293.643 213.753 288.895 205.529C283.594 196.349 276.14 188.985 266.532 183.438C257.035 177.7 246.986 174.831 236.385 174.83C226.005 174.83 219.214 177.603 216.012 183.149C212.699 188.504 213.804 195.963 219.325 205.527L154.725 205.524C145.449 189.458 141.473 175.114 142.798 162.491C144.343 149.868 150.637 140.114 161.68 133.229C172.833 126.153 187.685 122.615 206.237 122.616C225.672 122.617 244.942 126.156 264.046 133.233C283.039 140.119 300.377 149.874 316.058 162.497C331.739 175.121 344.218 189.466 353.495 205.532C362.108 220.45 365.919 233.551 364.925 244.835C364.153 256.12 360.454 265.969 353.828 274.384C347.424 282.799 337.486 293.127 324.014 305.367L406.172 305.37L434.996 355.289L246.165 355.282L217.342 305.363Z" stroke="#1B1B18" stroke-width="1" />
                            <path d="M38.197 175.972L15.3385 175.971L-13.6505 125.765L72.1515 125.768L204.673 355.282L141.729 355.279L38.197 175.972Z" stroke="#1B1B18" stroke-width="1" />
                        </g>
                        <g style="mix-blend-mode: plus-darker" class="transition-all delay-300 translate-y-0 opacity-100 duration-750 starting:opacity-0 starting:translate-y-4">
                            <path d="M230.951 281.792L231.282 281.793C238.128 274.907 248.453 265.823 262.256 254.539C275.617 243.256 285.666 234.267 292.402 227.573C299.027 220.688 303.554 213.421 305.983 205.771C308.412 198.12 307.253 190.183 302.504 181.959C297.203 172.778 289.749 165.415 280.142 159.868C270.645 154.13 260.596 151.26 249.995 151.26C239.615 151.26 232.823 154.033 229.621 159.579C226.309 164.934 227.413 172.393 232.935 181.956L168.335 181.954C159.058 165.888 155.082 151.543 156.407 138.92C157.953 126.298 164.247 116.544 175.289 109.659C186.442 102.583 201.294 99.045 219.846 99.0457C239.281 99.0464 258.551 102.585 277.655 109.663C296.649 116.549 313.986 126.303 329.667 138.927C345.349 151.551 357.827 165.895 367.104 181.961C375.718 196.88 379.528 209.981 378.535 221.265C377.762 232.549 374.063 242.399 367.438 250.814C361.033 259.229 351.095 269.557 337.624 281.796L419.782 281.8L448.605 331.719L259.774 331.712L230.951 281.792Z" fill="#F3BEC7" />
                            <path d="M51.8063 152.402L28.9479 152.401L-0.0411453 102.195L85.7608 102.198L218.282 331.711L155.339 331.709L51.8063 152.402Z" fill="#F3BEC7" />
                            <path d="M230.951 281.792L231.282 281.793C238.128 274.907 248.453 265.823 262.256 254.539C275.617 243.256 285.666 234.267 292.402 227.573C299.027 220.688 303.554 213.421 305.983 205.771C308.412 198.12 307.253 190.183 302.504 181.959C297.203 172.778 289.749 165.415 280.142 159.868C270.645 154.13 260.596 151.26 249.995 151.26C239.615 151.26 232.823 154.033 229.621 159.579C226.309 164.934 227.413 172.393 232.935 181.956L168.335 181.954C159.058 165.888 155.082 151.543 156.407 138.92C157.953 126.298 164.247 116.544 175.289 109.659C186.442 102.583 201.294 99.045 219.846 99.0457C239.281 99.0464 258.551 102.585 277.655 109.663C296.649 116.549 313.986 126.303 329.667 138.927C345.349 151.551 357.827 165.895 367.104 181.961C375.718 196.88 379.528 209.981 378.535 221.265C377.762 232.549 374.063 242.399 367.438 250.814C361.033 259.229 351.095 269.557 337.624 281.796L419.782 281.8L448.605 331.719L259.774 331.712L230.951 281.792Z" stroke="#1B1B18" stroke-width="1" />
                            <path d="M51.8063 152.402L28.9479 152.401L-0.0411453 102.195L85.7608 102.198L218.282 331.711L155.339 331.709L51.8063 152.402Z" stroke="#1B1B18" stroke-width="1" />
                        </g>
                        <g class="transition-all delay-300 translate-y-0 opacity-100 duration-750 starting:opacity-0 starting:translate-y-4">
                            <path d="M188.467 355.363L188.798 355.363C195.644 348.478 205.969 339.393 219.772 328.11C233.133 316.826 243.181 307.837 249.917 301.144C253.696 297.217 256.792 293.166 259.205 288.991C261.024 285.845 262.455 282.628 263.499 279.341C265.928 271.691 264.768 263.753 260.02 255.529C254.719 246.349 247.265 238.985 237.657 233.438C228.16 227.7 218.111 224.831 207.51 224.83C197.13 224.83 190.339 227.603 187.137 233.149C183.824 238.504 184.929 245.963 190.45 255.527L125.851 255.524C116.574 239.458 112.598 225.114 113.923 212.491C114.615 206.836 116.261 201.756 118.859 197.253C122.061 191.704 126.709 187.03 132.805 183.229C143.958 176.153 158.81 172.615 177.362 172.616C196.797 172.617 216.067 176.156 235.171 183.233C254.164 190.119 271.502 199.874 287.183 212.497C302.864 225.121 315.343 239.466 324.62 255.532C333.233 270.45 337.044 283.551 336.05 294.835C335.46 303.459 333.16 311.245 329.151 318.194C327.915 320.337 326.515 322.4 324.953 324.384C318.549 332.799 308.611 343.127 295.139 355.367L377.297 355.37L406.121 405.289L217.29 405.282L188.467 355.363Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M9.32197 225.972L-13.5365 225.971L-42.5255 175.765L43.2765 175.768L175.798 405.282L112.854 405.279L9.32197 225.972Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M345.247 111.915C329.566 99.2919 312.229 89.5371 293.235 82.6512L235.167 183.228C254.161 190.114 271.498 199.869 287.179 212.492L345.247 111.915Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M382.686 154.964C373.41 138.898 360.931 124.553 345.25 111.93L287.182 212.506C302.863 225.13 315.342 239.475 324.618 255.541L382.686 154.964Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M293.243 82.6472C274.139 75.57 254.869 72.031 235.434 72.0303L177.366 172.607C196.801 172.608 216.071 176.147 235.175 183.224L293.243 82.6472Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M394.118 194.257C395.112 182.973 391.301 169.872 382.688 154.953L324.619 255.53C333.233 270.448 337.044 283.55 336.05 294.834L394.118 194.257Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M235.432 72.0311C216.88 72.0304 202.027 75.5681 190.875 82.6442L132.806 183.221C143.959 176.145 158.812 172.607 177.363 172.608L235.432 72.0311Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M265.59 124.25C276.191 124.251 286.24 127.12 295.737 132.858L237.669 233.435C228.172 227.697 218.123 224.828 207.522 224.827L265.59 124.25Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M295.719 132.859C305.326 138.406 312.78 145.77 318.081 154.95L260.013 255.527C254.712 246.347 247.258 238.983 237.651 233.436L295.719 132.859Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M387.218 217.608C391.227 210.66 393.527 202.874 394.117 194.25L336.049 294.827C335.459 303.451 333.159 311.237 329.15 318.185L387.218 217.608Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M245.211 132.577C248.413 127.03 255.204 124.257 265.584 124.258L207.516 224.835C197.136 224.834 190.345 227.607 187.143 233.154L245.211 132.577Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M318.094 154.945C322.842 163.17 324.002 171.107 321.573 178.757L263.505 279.334C265.934 271.684 264.774 263.746 260.026 255.522L318.094 154.945Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M176.925 96.6737C180.127 91.1249 184.776 86.4503 190.871 82.6499L132.803 183.227C126.708 187.027 122.059 191.702 118.857 197.25L176.925 96.6737Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M387.226 217.606C385.989 219.749 384.59 221.813 383.028 223.797L324.96 324.373C326.522 322.39 327.921 320.326 329.157 318.183L387.226 217.606Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M317.269 188.408C319.087 185.262 320.519 182.045 321.562 178.758L263.494 279.335C262.451 282.622 261.019 285.839 259.201 288.985L317.269 188.408Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M245.208 132.573C241.895 137.928 243 145.387 248.522 154.95L190.454 255.527C184.932 245.964 183.827 238.505 187.14 233.15L245.208 132.573Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M176.93 96.6719C174.331 101.175 172.686 106.255 171.993 111.91L113.925 212.487C114.618 206.831 116.263 201.752 118.862 197.249L176.93 96.6719Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M317.266 188.413C314.853 192.589 311.757 196.64 307.978 200.566L249.91 301.143C253.689 297.216 256.785 293.166 259.198 288.99L317.266 188.413Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M464.198 304.708L435.375 254.789L377.307 355.366L406.13 405.285L464.198 304.708Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M353.209 254.787C366.68 242.548 376.618 232.22 383.023 223.805L324.955 324.382C318.55 332.797 308.612 343.124 295.141 355.364L353.209 254.787Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M435.37 254.787L353.212 254.784L295.144 355.361L377.302 355.364L435.37 254.787Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M183.921 154.947L248.521 154.95L190.453 255.527L125.853 255.524L183.921 154.947Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M171.992 111.914C170.668 124.537 174.643 138.881 183.92 154.947L125.852 255.524C116.575 239.458 112.599 225.114 113.924 212.491L171.992 111.914Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M307.987 200.562C301.251 207.256 291.203 216.244 277.842 227.528L219.774 328.105C233.135 316.821 243.183 307.832 249.919 301.139L307.987 200.562Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M15.5469 75.1797L44.5359 125.386L-13.5321 225.963L-42.5212 175.756L15.5469 75.1797Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M277.836 227.536C264.033 238.82 253.708 247.904 246.862 254.789L188.794 355.366C195.64 348.481 205.965 339.397 219.768 328.113L277.836 227.536Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M275.358 304.706L464.189 304.713L406.12 405.29L217.29 405.283L275.358 304.706Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M44.5279 125.39L67.3864 125.39L9.31834 225.967L-13.5401 225.966L44.5279 125.39Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M101.341 75.1911L233.863 304.705L175.795 405.282L43.2733 175.768L101.341 75.1911ZM15.5431 75.19L-42.525 175.767L43.277 175.77L101.345 75.1932L15.5431 75.19Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M246.866 254.784L246.534 254.784L188.466 355.361L188.798 355.361L246.866 254.784Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M246.539 254.781L275.362 304.701L217.294 405.277L188.471 355.358L246.539 254.781Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M67.3906 125.391L170.923 304.698L112.855 405.275L9.32257 225.967L67.3906 125.391Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                            <path d="M170.921 304.699L233.865 304.701L175.797 405.278L112.853 405.276L170.921 304.699Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="bevel" />
                        </g>
                        <g style="mix-blend-mode: hard-light" class="transition-all delay-300 translate-y-0 opacity-100 duration-750 starting:opacity-0 starting:translate-y-4">
                            <path d="M246.544 254.79L246.875 254.79C253.722 247.905 264.046 238.82 277.849 227.537C291.21 216.253 301.259 207.264 307.995 200.57C314.62 193.685 319.147 186.418 321.577 178.768C324.006 171.117 322.846 163.18 318.097 154.956C312.796 145.775 305.342 138.412 295.735 132.865C286.238 127.127 276.189 124.258 265.588 124.257C255.208 124.257 248.416 127.03 245.214 132.576C241.902 137.931 243.006 145.39 248.528 154.953L183.928 154.951C174.652 138.885 170.676 124.541 172 111.918C173.546 99.2946 179.84 89.5408 190.882 82.6559C202.035 75.5798 216.887 72.0421 235.439 72.0428C254.874 72.0435 274.144 75.5825 293.248 82.6598C312.242 89.5457 329.579 99.3005 345.261 111.924C360.942 124.548 373.421 138.892 382.697 154.958C391.311 169.877 395.121 182.978 394.128 194.262C393.355 205.546 389.656 215.396 383.031 223.811C376.627 232.226 366.688 242.554 353.217 254.794L435.375 254.797L464.198 304.716L275.367 304.709L246.544 254.79Z" fill="#F0ACB8" />
                            <path d="M246.544 254.79L246.875 254.79C253.722 247.905 264.046 238.82 277.849 227.537C291.21 216.253 301.259 207.264 307.995 200.57C314.62 193.685 319.147 186.418 321.577 178.768C324.006 171.117 322.846 163.18 318.097 154.956C312.796 145.775 305.342 138.412 295.735 132.865C286.238 127.127 276.189 124.258 265.588 124.257C255.208 124.257 248.416 127.03 245.214 132.576C241.902 137.931 243.006 145.39 248.528 154.953L183.928 154.951C174.652 138.885 170.676 124.541 172 111.918C173.546 99.2946 179.84 89.5408 190.882 82.6559C202.035 75.5798 216.887 72.0421 235.439 72.0428C254.874 72.0435 274.144 75.5825 293.248 82.6598C312.242 89.5457 329.579 99.3005 345.261 111.924C360.942 124.548 373.421 138.892 382.697 154.958C391.311 169.877 395.121 182.978 394.128 194.262C393.355 205.546 389.656 215.396 383.031 223.811C376.627 232.226 366.688 242.554 353.217 254.794L435.375 254.797L464.198 304.716L275.367 304.709L246.544 254.79Z" stroke="#1B1B18" stroke-width="1" stroke-linejoin="round" />
                        </g>
                        <g style="mix-blend-mode: hard-light" class="transition-all delay-300 translate-y-0 opacity-100 duration-750 starting:opacity-0 starting:translate-y-4">
                            <path d="M67.41 125.402L44.5515 125.401L15.5625 75.1953L101.364 75.1985L233.886 304.712L170.942 304.71L67.41 125.402Z" fill="#F0ACB8" />
                            <path d="M67.41 125.402L44.5515 125.401L15.5625 75.1953L101.364 75.1985L233.886 304.712L170.942 304.71L67.41 125.402Z" stroke="#1B1B18" stroke-width="1" />
                        </g>
                    </svg>

                    {{-- Dark Mode 12 SVG --}}
                    <svg class="w-[448px] max-w-none relative -mt-[4.9rem] -ml-8 lg:ml-0 lg:-mt-[6.6rem] hidden dark:block" viewBox="0 0 440 376" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g class="transition-all delay-300 translate-y-0 opacity-100 duration-750 starting:opacity-0 starting:translate-y-4">
                            <path d="M188.263 355.73L188.595 355.73C195.441 348.845 205.766 339.761 219.569 328.477C232.93 317.193 242.978 308.205 249.714 301.511C256.34 294.626 260.867 287.358 263.296 279.708C265.725 272.058 264.565 264.121 259.816 255.896C254.516 246.716 247.062 239.352 237.454 233.805C227.957 228.067 217.908 225.198 207.307 225.198C196.927 225.197 190.136 227.97 186.934 233.516C183.621 238.872 184.726 246.331 190.247 255.894L125.647 255.891C116.371 239.825 112.395 225.481 113.72 212.858C115.265 200.235 121.559 190.481 132.602 183.596C143.754 176.52 158.607 172.982 177.159 172.983C196.594 172.984 215.863 176.523 234.968 183.6C253.961 190.486 271.299 200.241 286.98 212.864C302.661 225.488 315.14 239.833 324.416 255.899C333.03 270.817 336.841 283.918 335.847 295.203C335.075 306.487 331.376 316.336 324.75 324.751C318.346 333.167 308.408 343.494 294.936 355.734L377.094 355.737L405.917 405.656L217.087 405.649L188.263 355.73Z" fill="black"/>
                            <path d="M9.11884 226.339L-13.7396 226.338L-42.7286 176.132L43.0733 176.135L175.595 405.649L112.651 405.647L9.11884 226.339Z" fill="black"/>
                            <path d="M188.263 355.73L188.595 355.73C195.441 348.845 205.766 339.761 219.569 328.477C232.93 317.193 242.978 308.205 249.714 301.511C256.34 294.626 260.867 287.358 263.296 279.708C265.725 272.058 264.565 264.121 259.816 255.896C254.516 246.716 247.062 239.352 237.454 233.805C227.957 228.067 217.908 225.198 207.307 225.198C196.927 225.197 190.136 227.97 186.934 233.516C183.621 238.872 184.726 246.331 190.247 255.894L125.647 255.891C116.371 239.825 112.395 225.481 113.72 212.858C115.265 200.235 121.559 190.481 132.602 183.596C143.754 176.52 158.607 172.982 177.159 172.983C196.594 172.984 215.863 176.523 234.968 183.6C253.961 190.486 271.299 200.241 286.98 212.864C302.661 225.488 315.14 239.833 324.416 255.899C333.03 270.817 336.841 283.918 335.847 295.203C335.075 306.487 331.376 316.336 324.75 324.751C318.346 333.167 308.408 343.494 294.936 355.734L377.094 355.737L405.917 405.656L217.087 405.649L188.263 355.73Z" stroke="#FF750F" stroke-width="1"/>
                            <path d="M9.11884 226.339L-13.7396 226.338L-42.7286 176.132L43.0733 176.135L175.595 405.649L112.651 405.647L9.11884 226.339Z" stroke="#FF750F" stroke-width="1"/>
                            <path d="M204.592 327.449L204.923 327.449C211.769 320.564 222.094 311.479 235.897 300.196C249.258 288.912 259.306 279.923 266.042 273.23C272.668 266.345 277.195 259.077 279.624 251.427C282.053 243.777 280.893 235.839 276.145 227.615C270.844 218.435 263.39 211.071 253.782 205.524C244.285 199.786 234.236 196.917 223.635 196.916C213.255 196.916 206.464 199.689 203.262 205.235C199.949 210.59 201.054 218.049 206.575 227.612L141.975 227.61C132.699 211.544 128.723 197.2 130.048 184.577C131.593 171.954 137.887 162.2 148.93 155.315C160.083 148.239 174.935 144.701 193.487 144.702C212.922 144.703 232.192 148.242 251.296 155.319C270.289 162.205 287.627 171.96 303.308 184.583C318.989 197.207 331.468 211.552 340.745 227.618C349.358 242.536 353.169 255.637 352.175 266.921C351.403 278.205 347.704 288.055 341.078 296.47C334.674 304.885 324.736 315.213 311.264 327.453L393.422 327.456L422.246 377.375L233.415 377.368L204.592 327.449Z" fill="#391800"/>
                            <path d="M25.447 198.058L2.58852 198.057L-26.4005 147.851L59.4015 147.854L191.923 377.368L128.979 377.365L25.447 198.058Z" fill="#391800"/>
                            <path d="M204.592 327.449L204.923 327.449C211.769 320.564 222.094 311.479 235.897 300.196C249.258 288.912 259.306 279.923 266.042 273.23C272.668 266.345 277.195 259.077 279.624 251.427C282.053 243.777 280.893 235.839 276.145 227.615C270.844 218.435 263.39 211.071 253.782 205.524C244.285 199.786 234.236 196.917 223.635 196.916C213.255 196.916 206.464 199.689 203.262 205.235C199.949 210.59 201.054 218.049 206.575 227.612L141.975 227.61C132.699 211.544 128.723 197.2 130.048 184.577C131.593 171.954 137.887 162.2 148.93 155.315C160.083 148.239 174.935 144.701 193.487 144.702C212.922 144.703 232.192 148.242 251.296 155.319C270.289 162.205 287.627 171.96 303.308 184.583C318.989 197.207 331.468 211.552 340.745 227.618C349.358 242.536 353.169 255.637 352.175 266.921C351.403 278.205 347.704 288.055 341.078 296.47C334.674 304.885 324.736 315.213 311.264 327.453L393.422 327.456L422.246 377.375L233.415 377.368L204.592 327.449Z" stroke="#FF750F" stroke-width="1"/>
                            <path d="M25.447 198.058L2.58852 198.057L-26.4005 147.851L59.4015 147.854L191.923 377.368L128.979 377.365L25.447 198.058Z" stroke="#FF750F" stroke-width="1"/>
                        </g>
                        <g class="transition-all delay-300 translate-y-0 opacity-100 duration-750 starting:opacity-0 starting:translate-y-4" style="mix-blend-mode:hard-light">
                            <path d="M217.342 305.363L217.673 305.363C224.519 298.478 234.844 289.393 248.647 278.11C262.008 266.826 272.056 257.837 278.792 251.144C285.418 244.259 289.945 236.991 292.374 229.341C294.803 221.691 293.643 213.753 288.895 205.529C283.594 196.349 276.14 188.985 266.532 183.438C257.035 177.7 246.986 174.831 236.385 174.83C226.005 174.83 219.214 177.603 216.012 183.149C212.699 188.504 213.804 195.963 219.325 205.527L154.725 205.524C145.449 189.458 141.473 175.114 142.798 162.491C144.343 149.868 150.637 140.114 161.68 133.229C172.833 126.153 187.685 122.615 206.237 122.616C225.672 122.617 244.942 126.156 264.046 133.233C283.039 140.119 300.377 149.874 316.058 162.497C331.739 175.121 344.218 189.466 353.495 205.532C362.108 220.45 365.919 233.551 364.925 244.835C364.153 256.12 360.454 265.969 353.828 274.384C347.424 282.799 337.486 293.127 324.014 305.367L406.172 305.37L434.996 355.289L246.165 355.282L217.342 305.363Z" fill="#733000"/>
                            <path d="M38.197 175.972L15.3385 175.971L-13.6505 125.765L72.1515 125.768L204.673 355.282L141.729 355.279L38.197 175.972Z" fill="#733000"/>
                            <path d="M217.342 305.363L217.673 305.363C224.519 298.478 234.844 289.393 248.647 278.11C262.008 266.826 272.056 257.837 278.792 251.144C285.418 244.259 289.945 236.991 292.374 229.341C294.803 221.691 293.643 213.753 288.895 205.529C283.594 196.349 276.14 188.985 266.532 183.438C257.035 177.7 246.986 174.831 236.385 174.83C226.005 174.83 219.214 177.603 216.012 183.149C212.699 188.504 213.804 195.963 219.325 205.527L154.725 205.524C145.449 189.458 141.473 175.114 142.798 162.491C144.343 149.868 150.637 140.114 161.68 133.229C172.833 126.153 187.685 122.615 206.237 122.616C225.672 122.617 244.942 126.156 264.046 133.233C283.039 140.119 300.377 149.874 316.058 162.497C331.739 175.121 344.218 189.466 353.495 205.532C362.108 220.45 365.919 233.551 364.925 244.835C364.153 256.12 360.454 265.969 353.828 274.384C347.424 282.799 337.486 293.127 324.014 305.367L406.172 305.37L434.996 355.289L246.165 355.282L217.342 305.363Z" stroke="#FF750F" stroke-width="1"/>
                            <path d="M38.197 175.972L15.3385 175.971L-13.6505 125.765L72.1515 125.768L204.673 355.282L141.729 355.279L38.197 175.972Z" stroke="#FF750F" stroke-width="1"/>
                        </g>
                        <g class="transition-all delay-300 translate-y-0 opacity-100 duration-750 starting:opacity-0 starting:translate-y-4">
                            <path d="M217.342 305.363L217.673 305.363C224.519 298.478 234.844 289.393 248.647 278.11C262.008 266.826 272.056 257.837 278.792 251.144C285.418 244.259 289.945 236.991 292.374 229.341C294.803 221.691 293.643 213.753 288.895 205.529C283.594 196.349 276.14 188.985 266.532 183.438C257.035 177.7 246.986 174.831 236.385 174.83C226.005 174.83 219.214 177.603 216.012 183.149C212.699 188.504 213.804 195.963 219.325 205.527L154.726 205.524C145.449 189.458 141.473 175.114 142.798 162.491C144.343 149.868 150.637 140.114 161.68 133.229C172.833 126.153 187.685 122.615 206.237 122.616C225.672 122.617 244.942 126.156 264.046 133.233C283.039 140.119 300.377 149.874 316.058 162.497C331.739 175.121 344.218 189.466 353.495 205.532C362.108 220.45 365.919 233.551 364.925 244.835C364.153 256.12 360.454 265.969 353.828 274.384C347.424 282.799 337.486 293.127 324.014 305.367L406.172 305.37L434.996 355.289L246.165 355.282L217.342 305.363Z" stroke="#FF750F" stroke-width="1"/>
                            <path d="M38.197 175.972L15.3385 175.971L-13.6505 125.765L72.1515 125.768L204.673 355.282L141.729 355.279L38.197 175.972Z" stroke="#FF750F" stroke-width="1"/>
                        </g>
                        <g class="transition-all delay-300 translate-y-0 opacity-100 duration-750 starting:opacity-0 starting:translate-y-4">
                            <path d="M188.467 355.363L188.798 355.363C195.644 348.478 205.969 339.393 219.772 328.11C233.133 316.826 243.181 307.837 249.917 301.144C253.696 297.217 256.792 293.166 259.205 288.991C261.024 285.845 262.455 282.628 263.499 279.341C265.928 271.691 264.768 263.753 260.02 255.529C254.719 246.349 247.265 238.985 237.657 233.438C228.16 227.7 218.111 224.831 207.51 224.83C197.13 224.83 190.339 227.603 187.137 233.149C183.824 238.504 184.929 245.963 190.45 255.527L125.851 255.524C116.574 239.458 112.598 225.114 113.923 212.491C114.615 206.836 116.261 201.756 118.859 197.253C122.061 191.704 126.709 187.03 132.805 183.229C143.958 176.153 158.81 172.615 177.362 172.616C196.797 172.617 216.067 176.156 235.171 183.233C254.164 190.119 271.502 199.874 287.183 212.497C302.864 225.121 315.343 239.466 324.62 255.532C333.233 270.45 337.044 283.551 336.05 294.835C335.46 303.459 333.16 311.245 329.151 318.194C327.915 320.337 326.515 322.4 324.953 324.384C318.549 332.799 308.611 343.127 295.139 355.367L377.297 355.37L406.121 405.289L217.29 405.282L188.467 355.363Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M9.32197 225.972L-13.5365 225.971L-42.5255 175.765L43.2765 175.768L175.798 405.282L112.854 405.279L9.32197 225.972Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M345.247 111.915C329.566 99.2919 312.229 89.5371 293.235 82.6512L235.167 183.228C254.161 190.114 271.498 199.869 287.179 212.492L345.247 111.915Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M382.686 154.964C373.41 138.898 360.931 124.553 345.25 111.93L287.182 212.506C302.863 225.13 315.342 239.475 324.618 255.541L382.686 154.964Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M293.243 82.6472C274.139 75.57 254.869 72.031 235.434 72.0303L177.366 172.607C196.801 172.608 216.071 176.147 235.175 183.224L293.243 82.6472Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M394.118 194.257C395.112 182.973 391.301 169.872 382.688 154.953L324.619 255.53C333.233 270.448 337.044 283.55 336.05 294.834L394.118 194.257Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M235.432 72.0311C216.88 72.0304 202.027 75.5681 190.875 82.6442L132.806 183.221C143.959 176.145 158.812 172.607 177.363 172.608L235.432 72.0311Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M265.59 124.25C276.191 124.251 286.24 127.12 295.737 132.858L237.669 233.435C228.172 227.697 218.123 224.828 207.522 224.827L265.59 124.25Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M295.719 132.859C305.326 138.406 312.78 145.77 318.081 154.95L260.013 255.527C254.712 246.347 247.258 238.983 237.651 233.436L295.719 132.859Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M387.218 217.608C391.227 210.66 393.527 202.874 394.117 194.25L336.049 294.827C335.459 303.451 333.159 311.237 329.15 318.185L387.218 217.608Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M245.211 132.577C248.413 127.03 255.204 124.257 265.584 124.258L207.516 224.835C197.136 224.834 190.345 227.607 187.143 233.154L245.211 132.577Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M318.094 154.945C322.842 163.17 324.002 171.107 321.573 178.757L263.505 279.334C265.934 271.684 264.774 263.746 260.026 255.522L318.094 154.945Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M176.925 96.6737C180.127 91.1249 184.776 86.4503 190.871 82.6499L132.803 183.227C126.708 187.027 122.059 191.702 118.857 197.25L176.925 96.6737Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M387.226 217.606C385.989 219.749 384.59 221.813 383.028 223.797L324.96 324.373C326.522 322.39 327.921 320.326 329.157 318.183L387.226 217.606Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M317.269 188.408C319.087 185.262 320.519 182.045 321.562 178.758L263.494 279.335C262.451 282.622 261.019 285.839 259.201 288.985L317.269 188.408Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M245.208 132.573C241.895 137.928 243 145.387 248.522 154.95L190.454 255.527C184.932 245.964 183.827 238.505 187.14 233.15L245.208 132.573Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M176.93 96.6719C174.331 101.175 172.686 106.255 171.993 111.91L113.925 212.487C114.618 206.831 116.263 201.752 118.862 197.249L176.93 96.6719Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M317.266 188.413C314.853 192.589 311.757 196.64 307.978 200.566L249.91 301.143C253.689 297.216 256.785 293.166 259.198 288.99L317.266 188.413Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M464.198 304.708L435.375 254.789L377.307 355.366L406.13 405.285L464.198 304.708Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M353.209 254.787C366.68 242.548 376.618 232.22 383.023 223.805L324.955 324.382C318.55 332.797 308.612 343.124 295.141 355.364L353.209 254.787Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M435.37 254.787L353.212 254.784L295.144 355.361L377.302 355.364L435.37 254.787Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M183.921 154.947L248.521 154.95L190.453 255.527L125.853 255.524L183.921 154.947Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M171.992 111.914C170.668 124.537 174.643 138.881 183.92 154.947L125.852 255.524C116.575 239.458 112.599 225.114 113.924 212.491L171.992 111.914Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M307.987 200.562C301.251 207.256 291.203 216.244 277.842 227.528L219.774 328.105C233.135 316.821 243.183 307.832 249.919 301.139L307.987 200.562Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M15.5469 75.1797L44.5359 125.386L-13.5321 225.963L-42.5212 175.756L15.5469 75.1797Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M277.836 227.536C264.033 238.82 253.708 247.904 246.862 254.789L188.794 355.366C195.64 348.481 205.965 339.397 219.768 328.113L277.836 227.536Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M275.358 304.706L464.189 304.713L406.12 405.29L217.29 405.283L275.358 304.706Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M44.5279 125.39L67.3864 125.39L9.31834 225.967L-13.5401 225.966L44.5279 125.39Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M101.341 75.1911L233.863 304.705L175.795 405.282L43.2733 175.768L101.341 75.1911ZM15.5431 75.19L-42.525 175.767L43.277 175.77L101.345 75.1932L15.5431 75.19Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M246.866 254.784L246.534 254.784L188.466 355.361L188.798 355.361L246.866 254.784Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M246.539 254.781L275.362 304.701L217.294 405.277L188.471 355.358L246.539 254.781Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M67.3906 125.391L170.923 304.698L112.855 405.275L9.32257 225.967L67.3906 125.391Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                            <path d="M170.921 304.699L233.865 304.701L175.797 405.278L112.853 405.276L170.921 304.699Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="bevel"/>
                        </g>
                        <g class="transition-all delay-300 translate-y-0 opacity-100 duration-750 starting:opacity-0 starting:translate-y-4" style="mix-blend-mode:hard-light">
                            <path d="M246.544 254.79L246.875 254.79C253.722 247.905 264.046 238.82 277.849 227.537C291.21 216.253 301.259 207.264 307.995 200.57C314.62 193.685 319.147 186.418 321.577 178.768C324.006 171.117 322.846 163.18 318.097 154.956C312.796 145.775 305.342 138.412 295.735 132.865C286.238 127.127 276.189 124.258 265.588 124.257C255.208 124.257 248.416 127.03 245.214 132.576C241.902 137.931 243.006 145.39 248.528 154.953L183.928 154.951C174.652 138.885 170.676 124.541 172 111.918C173.546 99.2946 179.84 89.5408 190.882 82.6559C202.035 75.5798 216.887 72.0421 235.439 72.0428C254.874 72.0435 274.144 75.5825 293.248 82.6598C312.242 89.5457 329.579 99.3005 345.261 111.924C360.942 124.548 373.421 138.892 382.697 154.958C391.311 169.877 395.121 182.978 394.128 194.262C393.355 205.546 389.656 215.396 383.031 223.811C376.627 232.226 366.688 242.554 353.217 254.794L435.375 254.797L464.198 304.716L275.367 304.709L246.544 254.79Z" fill="#4B0600"/>
                            <path d="M246.544 254.79L246.875 254.79C253.722 247.905 264.046 238.82 277.849 227.537C291.21 216.253 301.259 207.264 307.995 200.57C314.62 193.685 319.147 186.418 321.577 178.768C324.006 171.117 322.846 163.18 318.097 154.956C312.796 145.775 305.342 138.412 295.735 132.865C286.238 127.127 276.189 124.258 265.588 124.257C255.208 124.257 248.416 127.03 245.214 132.576C241.902 137.931 243.006 145.39 248.528 154.953L183.928 154.951C174.652 138.885 170.676 124.541 172 111.918C173.546 99.2946 179.84 89.5408 190.882 82.6559C202.035 75.5798 216.887 72.0421 235.439 72.0428C254.874 72.0435 274.144 75.5825 293.248 82.6598C312.242 89.5457 329.579 99.3005 345.261 111.924C360.942 124.548 373.421 138.892 382.697 154.958C391.311 169.877 395.121 182.978 394.128 194.262C393.355 205.546 389.656 215.396 383.031 223.811C376.627 232.226 366.688 242.554 353.217 254.794L435.375 254.797L464.198 304.716L275.367 304.709L246.544 254.79Z" stroke="#FF750F" stroke-width="1" stroke-linejoin="round"/>
                        </g>
                        <g class="transition-all delay-300 translate-y-0 opacity-100 duration-750 starting:opacity-0 starting:translate-y-4" style="mix-blend-mode:hard-light">
                            <path d="M67.41 125.402L44.5515 125.401L15.5625 75.1953L101.364 75.1985L233.886 304.712L170.942 304.71L67.41 125.402Z" fill="#4B0600"/>
                            <path d="M67.41 125.402L44.5515 125.401L15.5625 75.1953L101.364 75.1985L233.886 304.712L170.942 304.71L67.41 125.402Z" stroke="#FF750F" stroke-width="1"/>
                        </g>
                    </svg>
                    <div class="absolute inset-0 rounded-t-lg lg:rounded-t-none lg:rounded-r-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"></div>
                </div>
            </main>
        </div>

        @if (Route::has('login'))
            <div class="h-14.5 hidden lg:block"></div>
        @endif
    </body>
</html>


════════════════════════════════════════════════════════════════════════════════ 📂 Файл: .env ════════════════════════════════════════════════════════════════════════════════
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:4vT9ry5qFn5HL2Rb6NHvZNCl995jIMN7bxojtIVUumY=
APP_DEBUG=true
APP_URL=85.198.83.112
APP_ENV=dev

# Session configuration for subdirectory
SESSION_DOMAIN=85.198.83.112
SESSION_PATH=/
API_PREFIX=api
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

# .env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,localhost:5173,::1
SESSION_DOMAIN=localhost

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=188.225.40.162
DB_PORT=3306
DB_DATABASE=cm64192_zhanna
DB_USERNAME=cm64192_zhanna
DB_PASSWORD=H37k3ihx

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
# CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"


════════════════════════════════════════════════════════════════════════════════ 📂 Файл: artisan ════════════════════════════════════════════════════════════════════════════════
#!/usr/bin/env php
<?php

use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArgvInput;

define('LARAVEL_START', microtime(true));

// Register the Composer autoloader...
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel and handle the command...
/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$status = $app->handleCommand(new ArgvInput);

exit($status);


════════════════════════════════════════════════════════════════════════════════ 📂 Файл: composer.json ════════════════════════════════════════════════════════════════════════════════
{
    "$schema": "https://getcomposer.org/schema.json",
    "name": "laravel/laravel",
    "type": "project",
    "description": "The skeleton application for the Laravel framework.",
    "keywords": ["laravel", "framework"],
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "intervention/image": "*",
        "laravel/framework": "^12.0",
        "laravel/sanctum": "^4.2",
        "laravel/tinker": "^2.10.1"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pail": "^1.2.2",
        "laravel/pint": "^1.24",
        "laravel/sail": "^1.41",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.6",
        "phpunit/phpunit": "^11.5.3"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi",
            "@php artisan filament:upgrade"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi",
            "@php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\"",
            "@php artisan migrate --graceful --ansi"
        ],
        "dev": [
            "Composer\\Config::disableProcessTimeout",
            "npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite --kill-others"
        ],
        "test": [
            "@php artisan config:clear --ansi",
            "@php artisan test"
        ]
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true,
            "php-http/discovery": true
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}


════════════════════════════════════════════════════════════════════════════════ 📂 Файл: package.json ════════════════════════════════════════════════════════════════════════════════
{
    "$schema": "https://json.schemastore.org/package.json",
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite"
    },
    "devDependencies": {
        "@tailwindcss/vite": "^4.0.0",
        "axios": "^1.11.0",
        "concurrently": "^9.0.1",
        "laravel-vite-plugin": "^2.0.0",
        "tailwindcss": "^4.0.0",
        "vite": "^7.0.4"
    }
}


✅ Сканирование завершено. Показаны только выбранные каталоги.
