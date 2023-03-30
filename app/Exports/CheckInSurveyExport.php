<?php

namespace App\Exports;

use App\Models\CheckInSurveyQuestion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CheckInSurveyExport implements FromCollection,WithHeadings
{
    public function headings(): array
    {
        return ["Company employee id", "Question", "Min Desc", "Max Desc", "Answer", "Day"];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        //return CheckInSurveyQuestion::all();

        $res = CheckInSurveyQuestion::select('check_in_survey_answers.company_employee_id', 'check_in_survey_questions.question','check_in_survey_questions.min_desc','check_in_survey_questions.max_desc','check_in_survey_answers.answer','check_in_survey_questions.day')
            ->join('check_in_survey_answers', 'check_in_survey_answers.question_id', 'check_in_survey_questions.id')
            ->get();
        return collect($res);
    }
}
