<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CoursePerformance;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;

class CertificateGenerationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'certificate_id' => 'required|exists:course_performances,id',
        ]);

        $performance = CoursePerformance::with(['course', 'user'])
            ->findOrFail($request->certificate_id);

        // Load the certificate template
        $templatePath = public_path('img/cert_temp.png');

        // Create image manager (GD driver by default in v3)
         $manager = new ImageManager(new Driver());
        $img = $manager->read($templatePath);

        // === User Full Name (centered under "presented to") ===
        $img->text($performance->user->name, 800, 570, function ($font) {
            $font->filename(public_path('fonts/Roboto-Italic.ttf')); // ✅ v3 syntax
            $font->size(77);
            $font->color('#000000');
            $font->align('center');
            $font->valign('middle');
        });

        // === Course Title ===
        $img->text($performance->course->title, 800, 810, function ($font) {
            $font->filename(public_path('fonts/Roboto-Regular.ttf'));
            $font->size(38);
            $font->color('#000000');
            $font->align('center');
            $font->valign('middle');
        });

        // === Date ===
        $img->text(now()->toFormattedDateString(), 350, 1020, function ($font) {
            $font->filename(public_path('fonts/Roboto-Italic.ttf'));
            $font->size(28);
            $font->color('#000000');
            $font->align('center');
            $font->valign('middle');
        });

        // Ensure certificates folder exists
        Storage::makeDirectory('public/certificates');

        // Save certificate in storage/app/public/certificates
        $filePath = 'certificates/' . uniqid() . '.png';
        $img->save(storage_path('app/public/' . $filePath));

        // Update DB record
        $performance->update([
            'certificate_status' => 'issued',
            'certificate_path' => $filePath,
        ]);

        return response()->json([
            'message' => 'Certificate generated successfully',
            'file' => asset('storage/' . $filePath), // Accessible via public/storage symlink
        ]);
    }
}
