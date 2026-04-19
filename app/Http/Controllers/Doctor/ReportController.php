<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorPoint;
use App\Models\DrugSample;
use App\Models\EventRequest;
use App\Models\Meeting;
use App\Models\RewardRedemption;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/doctor/report/generate",
     *     tags={"Doctor - Report"},
     *     summary="Download PDF activity report",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="PDF binary stream",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function generate(): Response
    {
        $doctor = auth('doctor-api')->user();
        if (!$doctor) {
            return $this->error('Unauthenticated', 401);
        }

        $data = [
            'doctor' => $doctor,
            'generated_at' => now(),
            'total_points' => DoctorPoint::where('doctor_id', $doctor->id)->sum('value'),
            'meetings' => Meeting::where('doctor_id', $doctor->id)
                ->with(['rep:id,full_name'])
                ->get(),
            'samples' => DrugSample::where('doctor_id', $doctor->id)
                ->with(['drug:id,name,market_name'])
                ->get(),
            'events' => EventRequest::where('doctor_id', $doctor->id)
                ->where('status', 'approved')
                ->with(['event:id,title,event_date'])
                ->get(),
            'redemptions' => RewardRedemption::where('doctor_id', $doctor->id)
                ->with(['reward:id,title,name,points_required'])
                ->get(),
        ];

        $pdf = Pdf::loadView('pdf.doctor_report', $data);

        return $pdf->download('erep-report-'.$doctor->id.'.pdf');
    }
}
