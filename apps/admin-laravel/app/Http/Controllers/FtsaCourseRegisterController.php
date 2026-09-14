<?php

namespace App\Http\Controllers;

use App\Services\FtsaCourseService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FtsaCourseRegisterController extends Controller
{
    public function show(Request $request, FtsaCourseService $courses, ?string $code = null): View
    {
        $prefillCode = $courses->normalizeCode($code ?: (string) $request->query('code', ''));
        $course = $prefillCode !== '' ? $courses->findByCode($prefillCode) : null;

        return view('Companyprofile.ftsa-course-register', [
            'prefillCode' => $prefillCode,
            'course' => $course,
            'result' => null,
        ]);
    }

    public function store(Request $request, FtsaCourseService $courses)
    {
        $data = $request->validate([
            'registration_code' => 'required|string|max:64',
            'full_name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:32',
        ]);

        $result = $courses->register(
            $data['registration_code'],
            $data['full_name'],
            $data['email'],
            $data['phone'] ?? null,
        );

        return view('Companyprofile.ftsa-course-register', [
            'prefillCode' => $courses->normalizeCode($data['registration_code']),
            'course' => $result['course'],
            'result' => $result,
        ]);
    }
}
