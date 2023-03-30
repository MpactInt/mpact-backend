<?php

namespace App\Exports;

use App\Models\PopupSurveyQuestion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PopupSurveyExport implements FromCollection,WithHeadings
{
    public function headings(): array
    {
        return ["Company employee id", "Question", "Answer"];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        //return PopupSurveyQuestion::all();

        $res = PopupSurveyQuestion::select('popup_survey_answers.company_employee_id', 'popup_survey_questions.question','popup_survey_answers.answer')
            ->join('popup_survey_answers', 'popup_survey_answers.question_id', 'popup_survey_questions.id')
            ->get();
        return collect($res);
    }
}
