<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FtsaCourse;
use App\Services\FtsaCourseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FtsaCoursesController extends Controller
{
    public function index()
    {
        $courses = FtsaCourse::query()
            ->withCount('registrants')
            ->orderByDesc('workshop_at')
            ->orderByDesc('id')
            ->get();

        return view('admin.ftsa_courses.index', compact('courses'));
    }

    public function create(FtsaCourseService $courses)
    {
        $course = new FtsaCourse([
            'is_active' => true,
            'access_days' => 30,
            'workshop_at' => now()->toDateString(),
            'registration_code' => $courses->generateRegistrationCode('YFD', now()),
        ]);

        return view('admin.ftsa_courses.form', compact('course'));
    }

    public function store(Request $request, FtsaCourseService $courses)
    {
        $data = $this->validateCourse($request);
        if (trim((string) ($data['registration_code'] ?? '')) === '') {
            $data['registration_code'] = $courses->generateRegistrationCode(
                $data['company_name'],
                \Carbon\Carbon::parse($data['workshop_at'])
            );
        }

        FtsaCourse::create($data);

        return redirect()->route('admin.ftsa-courses.index')->with('success', 'Course FTSA ditambahkan.');
    }

    public function show(FtsaCourse $ftsa_course)
    {
        $ftsa_course->load(['registrants' => fn ($q) => $q->orderByDesc('registered_at')]);

        return view('admin.ftsa_courses.show', ['course' => $ftsa_course]);
    }

    public function edit(FtsaCourse $ftsa_course)
    {
        return view('admin.ftsa_courses.form', ['course' => $ftsa_course]);
    }

    public function update(Request $request, FtsaCourse $ftsa_course)
    {
        $ftsa_course->update($this->validateCourse($request, $ftsa_course->id));

        return redirect()->route('admin.ftsa-courses.index')->with('success', 'Course FTSA diperbarui.');
    }

    public function destroy(FtsaCourse $ftsa_course)
    {
        $ftsa_course->delete();

        return redirect()->route('admin.ftsa-courses.index')->with('success', 'Course FTSA dihapus.');
    }

    private function validateCourse(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'company_name' => 'required|string|max:160',
            'registration_code' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('ftsa_courses', 'registration_code')->ignore($ignoreId),
            ],
            'workshop_at' => 'required|date',
            'access_days' => 'nullable|integer|min:1|max:365',
            'max_registrants' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:5000',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['registration_code'] = strtoupper(trim((string) ($data['registration_code'] ?? '')));
        $data['access_days'] = (int) ($data['access_days'] ?? 30);
        $data['max_registrants'] = isset($data['max_registrants']) && $data['max_registrants'] !== ''
            ? (int) $data['max_registrants']
            : null;
        $data['is_active'] = (bool) $request->boolean('is_active', true);
        $data['notes'] = isset($data['notes']) ? trim((string) $data['notes']) : null;

        return $data;
    }
}
